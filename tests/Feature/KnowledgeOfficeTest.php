<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Knowledge\KnowledgePipelineService;
use App\Services\Knowledge\OfficeTextExtractor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use ZipArchive;

class KnowledgeOfficeTest extends TestCase
{
    use RefreshDatabase;

    public function test_extrae_texto_de_docx(): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'.
            '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'.
            '<w:body><w:p><w:r><w:t>Reparaci\u00f3n de bombas hidr\u00e1ulicas</w:t></w:r></w:p>'.
            '<w:p><w:r><w:t>Instalaci\u00f3n de sistemas de riego.</w:t></w:r></w:p>'.
            '</w:body></w:document>';

        $text = app(OfficeTextExtractor::class)->extract($this->writeZip([
            'word/document.xml' => $xml,
        ]), 'docx');

        $this->assertStringContainsString('Reparaci\u00f3n de bombas hidr\u00e1ulicas', $text);
        $this->assertStringContainsString('Instalaci\u00f3n de sistemas de riego', $text);
        $this->assertStringNotContainsString('<w:', $text);
    }

    public function test_extrae_texto_de_docx_con_atributos_y_entidades(): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'.
            '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'.
            '<w:body>'.
            '<w:p><w:r><w:t xml:space="preserve">Precios de bombas: </w:t></w:r></w:p>'.
            '<w:p><w:r><w:t>USD 400 + IVA. Repuestos desde USD 120.</w:t></w:r></w:p>'.
            '</w:body></w:document>';

        $text = app(OfficeTextExtractor::class)->extract($this->writeZip([
            'word/document.xml' => $xml,
        ]), 'docx');

        $this->assertStringContainsString('Precios de bombas', $text);
        $this->assertStringContainsString('USD 400', $text);
        $this->assertStringNotContainsString('<w:', $text);
        $this->assertStringNotContainsString('standalone', $text);
    }

    public function test_extrae_texto_de_xlsx_incluyendo_shared_strings(): void
    {
        $shared = '<?xml version="1.0"?><sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'.
            '<si><t>Horario</t></si><si><t>Lunes a viernes de 9 a 18</t></si></sst>';

        $sheet = '<?xml version="1.0"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'.
            '<sheetData><row r="1"><c r="A1" t="s"><v>0</v></c><c r="B1" t="s"><v>1</v></c></row>'.
            '</sheetData></worksheet>';

        $text = app(OfficeTextExtractor::class)->extract($this->writeZip([
            'xl/sharedStrings.xml' => $shared,
            'xl/worksheets/sheet1.xml' => $sheet,
        ]), 'xlsx');

        $this->assertStringContainsString('Horario', $text);
        $this->assertStringContainsString('Lunes a viernes de 9 a 18', $text);
    }

    public function test_extrae_texto_de_pptx(): void
    {
        $slide = '<?xml version="1.0"?><p:sld xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main">'.
            '<p:cSld><p:spTree><p:sp><p:txBody><a:p xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main">'.
            '<a:r><a:t>Promoción de verano</a:t></a:r></a:p></p:txBody></p:sp>'.
            '<p:sp><p:txBody><a:p xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main">'.
            '<a:r><a:t>20% de descuento en servicios</a:t></a:r></a:p></p:txBody></p:sp>'.
            '</p:spTree></p:cSld></p:sld>';

        $text = app(OfficeTextExtractor::class)->extract($this->writeZip([
            'ppt/slides/slide1.xml' => $slide,
        ]), 'pptx');

        $this->assertStringContainsString('Promoción de verano', $text);
        $this->assertStringContainsString('20% de descuento en servicios', $text);
    }

    public function test_pipeline_procesa_un_docx_subido(): void
    {
        $tenant = $this->makeTenant('Office Tenant', 'office-tenant');
        $this->switchTenant($tenant);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'.
            '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'.
            '<w:body><w:p><w:r><w:t>Guía de servicios: instalación y reparación.</w:t></w:r></w:p>'.
            '</w:body></w:document>';

        $zipPath = $this->writeZip(['word/document.xml' => $xml]);

        $file = new UploadedFile($zipPath, 'guia.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true);

        $pipeline = app(KnowledgePipelineService::class);
        $document = $pipeline->createFromUpload($tenant, $file, 'Guía');
        $pipeline->process($document);

        $document->refresh();

        $this->assertSame('ready', $document->status);
        $this->assertGreaterThan(0, $document->chunk_count);
        $this->assertStringContainsString('instalación', $document->chunks()->first()->content);
    }

    public function test_upload_valida_mime_no_permitido(): void
    {
        $tenant = $this->makeTenant('Mime Tenant', 'mime-tenant');
        $this->switchTenant($tenant);

        $user = User::factory()->create();
        $tenant->users()->attach($user->id, ['role' => 'owner']);
        $this->actingAs($user)->withSession(['current_tenant_id' => $tenant->id]);

        $file = UploadedFile::fake()->create('malware.exe', 1024, 'application/x-msdownload');

        $this->post('/app/knowledge/upload', ['file' => $file, 'title' => 'x'])
            ->assertSessionHasErrors('file');
    }

    private function writeZip(array $entries): string
    {
        $path = sys_get_temp_dir().'/office-'.uniqid().'.zip';
        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->fail('No se pudo crear el ZIP de prueba.');
        }

        foreach ($entries as $name => $content) {
            $zip->addFromString($name, $content);
        }

        $zip->close();

        return $path;
    }
}

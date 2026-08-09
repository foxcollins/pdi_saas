<?php

namespace Tests\Feature;

use App\Services\Ai\RetrievalService;
use App\Services\Knowledge\KnowledgePipelineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KnowledgeRagTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_pipeline_procesa_contenido_y_el_retrieval_devuelve_resultados(): void
    {
        $tenant = $this->makeTenant('Andina Tests', 'andina-tests', 'Hidráulica');
        $this->switchTenant($tenant);

        $text = <<<TXT
        Horario de atención: lunes a viernes de 9 a 18 horas y sábados de 10 a 14.
        Realizamos reparación y venta de bombas hidráulicas e instalación de sistemas de riego.
        TXT;

        $document = app(KnowledgePipelineService::class)->createFromText($tenant, 'Información general', $text);
        app(KnowledgePipelineService::class)->process($document);

        $document->refresh();

        $this->assertSame('ready', $document->status);
        $this->assertGreaterThan(0, $document->chunk_count);
        $this->assertSame('ready', $document->source->status);

        $results = app(RetrievalService::class)->search('¿Cuál es el horario de atención?');

        $this->assertNotEmpty($results);
        $this->assertStringContainsString('Horario', $results[0]['content']);
    }

    public function test_el_retrieval_no_filtra_datos_de_otros_tenants(): void
    {
        $a = $this->makeTenant('Tenant RAG A', 'tenant-rag-a');
        $b = $this->makeTenant('Tenant RAG B', 'tenant-rag-b');

        $this->switchTenant($a);
        $docA = app(KnowledgePipelineService::class)->createFromText(
            $a,
            'Doc A',
            'Horario único de la empresa Alfa: lunes a viernes de 8 a 12 horas.'
        );
        app(KnowledgePipelineService::class)->process($docA);

        $this->switchTenant($b);
        $this->assertEmpty(app(RetrievalService::class)->search('horario'));

        $this->switchTenant($a);
        $results = app(RetrievalService::class)->search('horario');

        $this->assertNotEmpty($results);
        $this->assertStringContainsString('Alfa', $results[0]['content']);
    }
}

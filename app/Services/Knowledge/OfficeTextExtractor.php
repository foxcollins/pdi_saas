<?php

namespace App\Services\Knowledge;

use RuntimeException;
use ZipArchive;

class OfficeTextExtractor
{
    /**
     * Extrae texto plano de documentos Office OOXML (DOCX, XLSX, PPTX).
     * Los tres formatos son contenedores ZIP con XML en su interior.
     */
    public function extract(string $path, string $extension): string
    {
        $text = match (strtolower(ltrim($extension, '.'))) {
            'docx' => $this->extractDocx($path),
            'xlsx' => $this->extractXlsx($path),
            'pptx' => $this->extractPptx($path),
            default => throw new RuntimeException("Formato de Office no soportado: {$extension}."),
        };

        return trim($text);
    }

    private function extractDocx(string $path): string
    {
        $xml = $this->readEntry($path, 'word/document.xml');

        if ($xml === null) {
            throw new RuntimeException('El documento DOCX no contiene word/document.xml.');
        }

        $xml = preg_replace('~</w:p>~i', "\n", $xml);

        return $this->stripXmlTags($xml, 'w:t');
    }

    private function extractXlsx(string $path): string
    {
        $shared = $this->readSharedStrings($path);

        $rows = [];

        foreach ($this->worksheetPaths($path) as $sheetPath) {
            $xml = $this->readEntry($path, $sheetPath);

            if ($xml === null) {
                continue;
            }

            $rowTexts = [];

            if (preg_match_all('~<c\b([^>]*)>(.*?)</c>~is', $xml, $cells, PREG_SET_ORDER)) {
                $values = [];

                foreach ($cells as $cell) {
                    $isShared = str_contains($cell[1], 't="s"') || str_contains($cell[1], "t='s'");
                    $value = $this->cellValue($cell[2]);

                    if ($isShared && is_numeric($value) && isset($shared[(int) $value])) {
                        $values[] = $shared[(int) $value];
                    } elseif (! $isShared) {
                        $values[] = $value;
                    }
                }

                if ($values !== []) {
                    $rowTexts[] = implode(' ', $values);
                }
            }

            if ($rowTexts !== []) {
                $rows[] = implode("\n", $rowTexts);
            }
        }

        return implode("\n\n", array_filter($rows));
    }

    private function cellValue(string $cellXml): string
    {
        if (preg_match('~<v>(.*?)</v>~is', $cellXml, $m)) {
            return trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'));
        }

        return '';
    }

    /**
     * @return string[]
     */
    private function readSharedStrings(string $path): array
    {
        $xml = $this->readEntry($path, 'xl/sharedStrings.xml');

        if ($xml === null) {
            return [];
        }

        $values = [];

        if (preg_match_all('~<t(?:\s[^>]*)?>(.*?)</t>~is', $xml, $m)) {
            foreach ($m[1] as $text) {
                $values[] = trim(html_entity_decode($text, ENT_QUOTES, 'UTF-8'));
            }
        }

        return $values;
    }

    private function extractPptx(string $path): string
    {
        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            throw new RuntimeException('No se pudo abrir el archivo PPTX.');
        }

        $slides = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);

            if (! preg_match('~^ppt/slides/slide[0-9]+\.xml$~', $name)) {
                continue;
            }

            $xml = $zip->getFromName($name);
            $text = $this->stripXmlTags($xml, 'a:t');

            if (trim($text) !== '') {
                $slides[] = trim($text);
            }
        }

        $zip->close();

        if ($slides === []) {
            throw new RuntimeException('El PPTX no contiene diapositivas legibles.');
        }

        return implode("\n\n", $slides);
    }

    private function readEntry(string $path, string $entry): ?string
    {
        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            throw new RuntimeException('No se pudo abrir el archivo Office.');
        }

        $content = $zip->getFromName($entry) ?: null;
        $zip->close();

        return $content;
    }

    /**
     * Devuelve las rutas de hojas de cálculo en orden, manejando el libro con relaciones.
     *
     * @return string[]
     */
    private function worksheetPaths(string $path): array
    {
        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            throw new RuntimeException('No se pudo abrir el archivo XLSX.');
        }

        $paths = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);

            if (preg_match('~^xl/worksheets/sheet[0-9]+\.xml$~', $name)) {
                $paths[] = $name;
            }
        }

        $zip->close();

        sort($paths);

        return $paths;
    }

    /**
     * @return string[]
     */
    private function separateEntries(string $text): array
    {
        $entries = preg_split('/\n+/', $text);

        return array_values(array_filter(array_map('trim', $entries), fn ($e) => $e !== ''));
    }

    private function stripXmlTags(string $xml, string $tag): string
    {
        $pattern = '~<'.$tag.'(?:\s[^>]*)?>(.*?)</'.$tag.'>~is';
        $text = preg_replace($pattern, '$1', $xml);

        return trim(html_entity_decode((string) $text, ENT_QUOTES, 'UTF-8'));
    }
}

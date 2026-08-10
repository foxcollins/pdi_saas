<?php

namespace App\Services\Quotes;

use App\Models\Quote;

class QuotePdfGenerator
{
    public function generate(Quote $quote): string
    {
        $lines = $this->buildLines($quote);
        $content = $this->render($lines);

        return $content;
    }

    public function fileName(Quote $quote): string
    {
        return "cotizacion-{$quote->number}.pdf";
    }

    private function buildLines(Quote $quote): array
    {
        $lines = [];
        $lines[] = 'COTIZACION '.$quote->number;
        $lines[] = 'Estado: '.$quote->status;
        $lines[] = '';

        foreach ($quote->items as $item) {
            $lines[] = sprintf(
                '%s x %d  ...  %s %s',
                $item['title'],
                $item['quantity'],
                $quote->currency,
                number_format((float) $item['amount'], 2)
            );
        }

        $lines[] = '';
        $lines[] = 'Subtotal: '.$quote->currency.' '.number_format((float) $quote->subtotal, 2);
        $lines[] = 'Impuestos ('.$quote->tax_rate.'%): '.$quote->currency.' '.number_format((float) $quote->tax_amount, 2);
        $lines[] = 'TOTAL: '.$quote->currency.' '.number_format((float) $quote->total, 2);

        return $lines;
    }

    private function render(array $lines): string
    {
        $pdf = "%PDF-1.4\n";
        $objects = [];
        $offsets = [];

        $stream = '';
        foreach ($lines as $line) {
            $escaped = $this->escape($line);
            $stream .= 'BT /F1 11 Tf 40 '.(750 - (count(array_slice($lines, 0, array_search($line, $lines, true))) * 18))." Td ($escaped) Tj ET\n";
        }

        $objects[] = "<< /Type /Catalog /Pages 2 0 R >>\n";
        $objects[] = "<< /Type /Pages /Kids [3 0 R] /Count 1 >>\n";
        $objects[] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>\n";
        $objects[] = '<< /Length '.strlen($stream)." >>\nstream\n".$stream."endstream\n";
        $objects[] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\n";

        $current = strlen($pdf);

        foreach ($objects as $i => $object) {
            $num = $i + 1;
            $offsets[$num] = $current;
            $pdf .= $num." 0 obj\n".$object."endobj\n";
            $current = strlen($pdf);
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n";
        $pdf .= "0000000000 65535 f \n";

        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        $pdf .= "trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\n";
        $pdf .= "startxref\n{$xrefOffset}\n%%EOF\n";

        return $pdf;
    }

    private function escape(string $text): string
    {
        return str_replace(
            ['\\', '(', ')'],
            ['\\\\', '\\(', '\\)'],
            $text
        );
    }
}

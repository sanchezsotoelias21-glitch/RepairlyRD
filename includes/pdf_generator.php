<?php

declare(strict_types=1);

/**
 * Minimal PDF generator (no external dependencies).
 *
 * Implements a small subset of the classic FPDF-like API used by src/pages/reportes.php:
 * - addPage(), setFont(), cell(), ln(), output()
 *
 * Output is a real PDF (text-only). Tables are rendered as monospaced lines.
 */
class SimplePDF
{
    private string $fontFamily = 'Helvetica';
    private int $fontSize = 10;

    /** @var list<string> */
    private array $lines = [];

    /** @var list<string> */
    private array $currentRow = [];

    public function addPage(): void
    {
    }

    public function setFont(string $family, string $style = '', int $size = 10): void
    {
        $this->fontFamily = $family !== '' ? $family : 'Helvetica';
        $this->fontSize = $size > 0 ? $size : 10;
    }

    public function cell(int|float $w, int|float $h, string $txt = '', int $border = 0, int $ln = 0): void
    {
        $t = trim($txt);
        $this->currentRow[] = $t;
        if ($ln > 0) {
            $this->ln();
        }
    }

    public function ln(int|float $h = 0): void
    {
        if (!empty($this->currentRow)) {
            $this->lines[] = implode(' | ', $this->currentRow);
            $this->currentRow = [];
        } else {
            $this->lines[] = '';
        }
    }

    public function output(): string
    {
        if (!empty($this->currentRow)) {
            $this->ln();
        }

        $contentLines = [];
        $y = 800;
        $leading = max(12, (int)round($this->fontSize * 1.3));

        foreach ($this->lines as $line) {
            if ($y < 60) {
                $contentLines[] = 'ET';
                break;
            }
            $escaped = $this->pdfEscape($line);
            $contentLines[] = "72 {$y} Td ({$escaped}) Tj";
            $contentLines[] = "0 -" . $leading . " Td";
            $y -= $leading;
        }

        $contentStream = "BT\n/F1 {$this->fontSize} Tf\n" . implode("\n", $contentLines) . "\nET\n";

        // Build a minimal single-page PDF with one standard font.
        $objects = [];
        $objects[] = "<< /Type /Catalog /Pages 2 0 R >>"; // 1
        $objects[] = "<< /Type /Pages /Kids [3 0 R] /Count 1 >>"; // 2
        $objects[] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>"; // 3 (A4)
        $objects[] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>"; // 4
        $objects[] = "<< /Length " . strlen($contentStream) . " >>\nstream\n{$contentStream}\nendstream"; // 5

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        for ($i = 0; $i < count($objects); $i++) {
            $offsets[] = strlen($pdf);
            $objNum = $i + 1;
            $pdf .= "{$objNum} 0 obj\n{$objects[$i]}\nendobj\n";
        }

        $xrefPos = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n{$xrefPos}\n%%EOF";
        return $pdf;
    }

    private function pdfEscape(string $s): string
    {
        $s = str_replace("\\", "\\\\", $s);
        $s = str_replace("(", "\\(", $s);
        $s = str_replace(")", "\\)", $s);
        $s = str_replace(["\r", "\n"], ' ', $s);
        // Best-effort: use WinAnsi (cp1252) so accented Spanish characters render with Helvetica.
        $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $s);
        if (is_string($converted) && $converted !== '') {
            $s = $converted;
        } else {
            $s = preg_replace('/[^\x20-\x7E]/', '?', $s) ?? $s;
        }
        return $s;
    }
}

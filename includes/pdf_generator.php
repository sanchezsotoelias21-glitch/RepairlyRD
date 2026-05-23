<?php

require_once __DIR__ . '/fpdf/fpdf.php';

class RepairlyPDF extends FPDF {

    private $reportTitle = 'Reporte';
    private $reportSubtitle = '';

    public function setReportMeta($title, $subtitle = '') {
        $this->reportTitle = $title;
        $this->reportSubtitle = $subtitle;
    }

    /**
     * Convierte texto UTF-8 para compatibilidad con FPDF
     */
    private function encodeText($text) {
        return mb_convert_encoding($text, 'ISO-8859-1', 'UTF-8');
    }

    function Header() {

        $this->SetFont('Arial', 'B', 18);
        $this->Cell(0, 10, 'RepairlyRD', 0, 1, 'C');

        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 8, $this->encodeText($this->reportTitle), 0, 1, 'C');

        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 6, $this->encodeText($this->reportSubtitle), 0, 1, 'C');

        $this->Ln(5);
    }

    function Footer() {

        $this->SetY(-15);

        $this->SetFont('Arial', 'I', 8);

        $this->Cell(
            0,
            10,
            $this->encodeText('Página ') . $this->PageNo(),
            0,
            0,
            'C'
        );
    }

    public function addDataTable(array $headers, array $rows, array $widths = []) {
        $this->Ln(4);

        $this->SetFont('Arial', 'B', 9);

        if (empty($widths)) {
            $width = floor(190 / max(count($headers), 1));
            $widths = array_fill(0, count($headers), $width);
        }

        // Encabezados
        foreach ($headers as $i => $header) {
            $w = $widths[$i] ?? 30;

            $this->SetFillColor(41, 128, 185);
            $this->SetTextColor(255, 255, 255);

            $this->Cell(
                $w,
                8,
                $this->encodeText($header),
                1,
                0,
                'C',
                true
            );
        }

        $this->Ln();

        // Filas
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(0, 0, 0);

        foreach ($rows as $row) {

            foreach ($row as $i => $value) {

                $w = $widths[$i] ?? 30;

                $text = mb_substr((string)$value, 0, 35);

                $this->Cell(
                    $w,
                    7,
                    $this->encodeText($text),
                    1,
                    0,
                    'L'
                );
            }

            $this->Ln();

            if ($this->GetY() > 260) {
                $this->AddPage();
            }
        }

        $this->Ln(4);
    }
}
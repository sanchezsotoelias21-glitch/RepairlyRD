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
}
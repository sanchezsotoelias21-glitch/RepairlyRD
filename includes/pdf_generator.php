<?php

require_once __DIR__ . '/fpdf/fpdf.php';

class RepairlyPDF extends FPDF {

    private $reportTitle = 'Reporte';
    private $reportSubtitle = '';

    public function setReportMeta($title, $subtitle = '') {
        $this->reportTitle = $title;
        $this->reportSubtitle = $subtitle;
    }

    function Header() {

        $this->SetFont('Arial', 'B', 18);
        $this->Cell(0, 10, 'RepairlyRD', 0, 1, 'C');

        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 8, utf8_decode($this->reportTitle), 0, 1, 'C');

        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 6, utf8_decode($this->reportSubtitle), 0, 1, 'C');

        $this->Ln(5);
    }

    function Footer() {

        $this->SetY(-15);

        $this->SetFont('Arial', 'I', 8);

        $this->Cell(0, 10,
            utf8_decode('Página ') . $this->PageNo(),
            0,
            0,
            'C'
        );
    }
}
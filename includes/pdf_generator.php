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

    // Agregar fila de KPIs
    public function addKpiRow($kpiStats) {
        $this->Ln(5);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(0, 6, utf8_decode('Resumen'), 0, 1, 'L');
        $this->Ln(2);

        $colWidth = 45;
        foreach ($kpiStats as $kpi) {
            $this->SetFont('Arial', 'B', 9);
            $this->Cell($colWidth, 6, utf8_decode($kpi['label']), 0, 0, 'L');
            $this->SetFont('Arial', '', 9);
            $this->Cell($colWidth, 6, utf8_decode($kpi['value']), 0, 1, 'L');
        }
        $this->Ln(5);
    }

    // Agregar badges de filtros
    public function addFilterBadges($date_from, $date_to, $statusDisplay) {
        $this->Ln(3);
        $this->SetFont('Arial', '', 9);
        $filters = [];
        if ($date_from || $date_to) {
            $range = trim(($date_from ? 'Desde ' . $date_from : '') . ($date_to ? '  Hasta ' . $date_to : ''));
            $filters[] = $range;
        }
        if ($statusDisplay) {
            $filters[] = 'Estado: ' . $statusDisplay;
        }
        if (!empty($filters)) {
            $this->Cell(0, 6, utf8_decode('Filtros: ' . implode(' | ', $filters)), 0, 1, 'L');
        }
        $this->Ln(5);
    }

    // Agregar tabla de datos
    public function addDataTable($headers, $tableRows, $colWidths) {
        $this->Ln(3);
        $this->SetFont('Arial', 'B', 9);
        foreach ($headers as $i => $header) {
            $this->Cell($colWidths[$i], 7, utf8_decode($header), 1, 0, 'C');
        }
        $this->Ln();

        $this->SetFont('Arial', '', 8);
        foreach ($tableRows as $row) {
            foreach ($row as $i => $cell) {
                $this->Cell($colWidths[$i], 6, utf8_decode($cell), 1, 0, 'C');
            }
            $this->Ln();
        }
    }
}
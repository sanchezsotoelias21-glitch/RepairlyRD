<?php



require_once __DIR__ . '/fpdf.php';

/**
 * RepairlyPDF – Generador de reportes profesionales con diseño atractivo.
 * Usa FPDF (puro PHP, sin dependencias externas).
 */
class RepairlyPDF extends FPDF
{
    private string $reportTitle   = '';
    private string $reportSubtitle = '';
    private string $generatedAt   = '';

    // Paleta de colores Repairly
    private const COLOR_PRIMARY   = [31,  92, 139];   // #1F5C8B azul
    private const COLOR_ACCENT    = [ 0, 170,  68];   // #00AA44 verde
    private const COLOR_DARK      = [28,  26,  23];   // #1C1A17
    private const COLOR_MUTED     = [140, 132, 121];  // #8C8479
    private const COLOR_LIGHT_BG  = [245, 245, 245];  // #F5F5F5
    private const COLOR_WHITE     = [255, 255, 255];
    private const COLOR_BORDER    = [208, 204, 198];  // #D0CCC6
    private const COLOR_ROW_ALT   = [250, 250, 248];  // #FAFAF8
    private const COLOR_HEADER_BG = [31,  92, 139];   // mismo que primary
    private const COLOR_WARNING   = [255, 149,   0];

    // ------------------------------------------------------------------ setup

    public function setReportMeta(string $title, string $subtitle = '', string $generatedAt = ''): void
    {
        $this->reportTitle    = $title;
        $this->reportSubtitle = $subtitle;
        $this->generatedAt    = $generatedAt !== '' ? $generatedAt : date('d/m/Y H:i:s');
    }

    // ---------------------------------------------------------------- header

    public function Header(): void
    {
        // Banda superior azul
        $this->SetFillColor(...self::COLOR_PRIMARY);
        $this->Rect(0, 0, 210, 28, 'F');

        // Nombre del sistema
        $this->SetFont('Arial', '', 18);
        $this->SetTextColor(...self::COLOR_WHITE);
        $this->SetXY(12, 6);
        $this->Cell(80, 8, 'REPAIRLY', 0, 0, 'L');

        // Subtítulo del sistema
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(180, 210, 235);
        $this->SetXY(12, 15);
        $this->Cell(80, 5, 'Sistema de Gestión de Reparaciones', 0, 0, 'L');

        // Fecha en la derecha
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(...self::COLOR_WHITE);
        $this->SetXY(120, 10);
        $this->Cell(78, 5, 'Generado: ' . $this->generatedAt, 0, 0, 'R');

        // Número de página
        $this->SetFont('Arial', '', 7);
        $this->SetTextColor(180, 210, 235);
        $this->SetXY(120, 17);
        $this->Cell(78, 4, 'Página ' . $this->PageNo(), 0, 0, 'R');

        // Línea decorativa debajo de la banda
        $this->SetDrawColor(...self::COLOR_ACCENT);
        $this->SetLineWidth(0.8);
        $this->Line(0, 28, 210, 28);

        // Bloque del título del reporte
        if ($this->reportTitle !== '') {
            $this->SetXY(0, 32);
            $this->SetFillColor(...self::COLOR_LIGHT_BG);
            $this->Rect(0, 30, 210, 18, 'F');

            $this->SetFont('Arial', '', 13);
            $this->SetTextColor(...self::COLOR_PRIMARY);
            $this->SetXY(12, 32);
            $this->Cell(140, 8, $this->toWin($this->reportTitle), 0, 0, 'L');

            if ($this->reportSubtitle !== '') {
                $this->SetFont('Arial', '', 8);
                $this->SetTextColor(...self::COLOR_MUTED);
                $this->SetXY(12, 40);
                $this->Cell(140, 5, $this->toWin($this->reportSubtitle), 0, 0, 'L');
            }

            // Línea separadora
            $this->SetDrawColor(...self::COLOR_BORDER);
            $this->SetLineWidth(0.3);
            $this->Line(12, 48, 198, 48);
        }

        $this->SetY(52);
        $this->SetLineWidth(0.2);
    }

    // ---------------------------------------------------------------- footer

    public function Footer(): void
    {
        $this->SetY(-14);
        $this->SetFillColor(...self::COLOR_LIGHT_BG);
        $this->Rect(0, $this->GetY(), 210, 14, 'F');

        $this->SetDrawColor(...self::COLOR_BORDER);
        $this->SetLineWidth(0.3);
        $this->Line(0, $this->GetY(), 210, $this->GetY());

        $this->SetFont('Arial', '', 7);
        $this->SetTextColor(...self::COLOR_MUTED);
        $this->SetX(12);
        $this->Cell(90, 10, $this->toWin('Repairly RD · Reporte confidencial'), 0, 0, 'L');
        $this->Cell(96, 10, 'Página ' . $this->PageNo() . ' / {nb}', 0, 0, 'R');
    }

    // ------------------------------------------------ Bloque KPI (resumen)

    /**
     * @param list<array{label:string,value:string,color?:string}> $stats
     */
    public function addKpiRow(array $stats): void
    {
        if (empty($stats)) {
            return;
        }

        $this->Ln(4);
        $count   = count($stats);
        $boxW    = (186 / $count);
        $boxH    = 22;
        $startX  = 12;
        $startY  = $this->GetY();

        foreach ($stats as $i => $s) {
            $x = $startX + $i * ($boxW + 2);

            // Fondo de la tarjeta
            $this->SetFillColor(...self::COLOR_WHITE);
            $this->SetDrawColor(...self::COLOR_BORDER);
            $this->SetLineWidth(0.3);
            $this->RoundedRect($x, $startY, $boxW, $boxH, 3, 'FD');

            // Línea de acento izquierda
            $this->SetFillColor(...self::COLOR_PRIMARY);
            $this->Rect($x, $startY, 2, $boxH, 'F');

            // Label
            $this->SetFont('Arial', '', 6.5);
            $this->SetTextColor(...self::COLOR_MUTED);
            $this->SetXY($x + 5, $startY + 3);
            $this->Cell($boxW - 7, 5, $this->toWin(strtoupper($s['label'])), 0, 0, 'L');

            // Valor
            $color = $s['color'] ?? 'primary';
            $rgb   = match ($color) {
                'green'  => self::COLOR_ACCENT,
                'orange' => self::COLOR_WARNING,
                default  => self::COLOR_PRIMARY,
            };
            $this->SetFont('Arial', '', 13);
            $this->SetTextColor(...$rgb);
            $this->SetXY($x + 5, $startY + 9);
            $this->Cell($boxW - 7, 9, $this->toWin($s['value']), 0, 0, 'L');
        }

        $this->SetY($startY + $boxH + 6);
    }

    // ------------------------------------------------ Tabla de datos

    /**
     * @param list<string>            $headers
     * @param list<array<int,string>> $rows
     * @param list<int>|null          $colWidths  ancho en mm por columna (opcional)
     */
    public function addDataTable(array $headers, array $rows, ?array $colWidths = null): void
    {
        if (empty($headers)) {
            return;
        }

        $usable  = 186; // mm entre márgenes
        $colCnt  = count($headers);

        if ($colWidths === null || count($colWidths) !== $colCnt) {
            $colWidths = array_fill(0, $colCnt, (int)round($usable / $colCnt));
        }

        // Cabecera de la tabla
        $this->SetFillColor(...self::COLOR_HEADER_BG);
        $this->SetTextColor(...self::COLOR_WHITE);
        $this->SetFont('Arial', '', 7.5);
        $this->SetDrawColor(...self::COLOR_BORDER);
        $this->SetLineWidth(0.2);

        $this->SetX(12);
        foreach ($headers as $k => $h) {
            $this->Cell($colWidths[$k], 9, $this->toWin(strtoupper($h)), 0, 0, 'L', true);
        }
        $this->Ln();

        // Filas
        $this->SetFont('Arial', '', 7.5);
        $alt = false;
        foreach ($rows as $row) {
            // Salto de página automático con re-cabecera
            if ($this->GetY() + 8 > $this->PageBreakTrigger) {
                $this->AddPage();
                // Re-dibujar cabecera de tabla
                $this->SetFillColor(...self::COLOR_HEADER_BG);
                $this->SetTextColor(...self::COLOR_WHITE);
                $this->SetFont('Arial', '', 7.5);
                $this->SetX(12);
                foreach ($headers as $k => $h) {
                    $this->Cell($colWidths[$k], 9, $this->toWin(strtoupper($h)), 0, 0, 'L', true);
                }
                $this->Ln();
                $this->SetFont('Arial', '', 7.5);
                $alt = false;
            }

            $bgColor = $alt ? self::COLOR_ROW_ALT : self::COLOR_WHITE;
            $this->SetFillColor(...$bgColor);
            $this->SetTextColor(...self::COLOR_DARK);
            $this->SetX(12);

            $rowArr = array_values($row);
            foreach ($headers as $k => $_) {
                $val = $this->toWin(substr((string)($rowArr[$k] ?? ''), 0, 35));
                $this->Cell($colWidths[$k], 8, $val, 0, 0, 'L', true);
            }
            $this->Ln();

            // Línea divisoria sutil
            $this->SetDrawColor(...self::COLOR_BORDER);
            $this->SetLineWidth(0.1);
            $this->Line(12, $this->GetY(), 198, $this->GetY());

            $alt = !$alt;
        }

        $this->Ln(4);
    }

    // ------------------------------------------------ Aviso de filtros activos

    public function addFilterBadges(string $dateFrom, string $dateTo, string $status): void
    {
        if ($dateFrom === '' && $dateTo === '' && $status === '') {
            return;
        }

        $this->SetFillColor(227, 242, 253); // azul muy claro
        $this->SetTextColor(...self::COLOR_PRIMARY);
        $this->SetFont('Arial', '', 7);
        $this->SetX(12);
        $this->Cell(186, 7, $this->toWin('  Filtros aplicados:'), 0, 1, 'L', true);

        $this->SetFillColor(240, 247, 253);
        $this->SetFont('Arial', '', 7);
        $parts = [];
        if ($dateFrom !== '') {
            $parts[] = 'Desde: ' . $dateFrom;
        }
        if ($dateTo !== '') {
            $parts[] = 'Hasta: ' . $dateTo;
        }
        if ($status !== '') {
            $parts[] = 'Estado: ' . $status;
        }
        $this->SetX(12);
        $this->Cell(186, 6, $this->toWin('  ' . implode('   |   ', $parts)), 0, 1, 'L', true);
        $this->Ln(3);
    }

    // ------------------------------------------------ Rounded rect helper

    private function RoundedRect(float $x, float $y, float $w, float $h, float $r, string $style): void
    {
        $k  = $this->k;
        $hp = $this->h;
        if ($style === 'F') {
            $op = 'f';
        } elseif ($style === 'FD' || $style === 'DF') {
            $op = 'B';
        } else {
            $op = 'S';
        }
        $MyArc = 4 / 3 * (sqrt(2) - 1);
        $this->_out(sprintf('%.2f %.2f m', ($x + $r) * $k, ($hp - $y) * $k));
        $xc = $x + $w - $r;
        $yc = $y + $r;
        $this->_out(sprintf('%.2f %.2f l', $xc * $k, ($hp - $y) * $k));
        $this->_Arc($xc + $r * $MyArc, $yc - $r, $xc + $r, $yc - $r * $MyArc, $xc + $r, $yc);
        $xc = $x + $w - $r;
        $yc = $y + $h - $r;
        $this->_out(sprintf('%.2f %.2f l', ($x + $w) * $k, ($hp - $yc) * $k));
        $this->_Arc($xc + $r, $yc + $r * $MyArc, $xc + $r * $MyArc, $yc + $r, $xc, $yc + $r);
        $xc = $x + $r;
        $yc = $y + $h - $r;
        $this->_out(sprintf('%.2f %.2f l', $xc * $k, ($hp - ($y + $h)) * $k));
        $this->_Arc($xc - $r * $MyArc, $yc + $r, $xc - $r, $yc + $r * $MyArc, $xc - $r, $yc);
        $xc = $x + $r;
        $yc = $y + $r;
        $this->_out(sprintf('%.2f %.2f l', $x * $k, ($hp - $yc) * $k));
        $this->_Arc($xc - $r, $yc - $r * $MyArc, $xc - $r * $MyArc, $yc - $r, $xc, $yc - $r);
        $this->_out($op);
    }

    private function _Arc(float $x1, float $y1, float $x2, float $y2, float $x3, float $y3): void
    {
        $h = $this->h;
        $this->_out(sprintf(
            '%.2f %.2f %.2f %.2f %.2f %.2f c',
            $x1 * $this->k,
            ($h - $y1) * $this->k,
            $x2 * $this->k,
            ($h - $y2) * $this->k,
            $x3 * $this->k,
            ($h - $y3) * $this->k
        ));
    }

    // ------------------------------------------------ Encoding helper

    private function toWin(string $s): string
    {
        $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $s);
        if (is_string($converted) && $converted !== '') {
            return $converted;
        }
        return preg_replace('/[^\x20-\x7E]/', '?', $s) ?? $s;
    }
}

/**
 * Alias de compatibilidad – el código antiguo instanciaba SimplePDF.
 * Ya no se usa, pero lo dejamos por si algo lo referencia.
 */
class SimplePDF extends RepairlyPDF
{
    /** @var list<string> */
    private array $lines = [];
    /** @var list<string> */
    private array $currentRow = [];

    public function addPage(string $orientation = '', string $size = '', int $rotation = 0): void
    {
        parent::AddPage($orientation, $size, $rotation);
    }

    public function setFont(string $family, string $style = '', int $size = 10): void
    {
        parent::SetFont($family ?: 'Arial', $style, $size);
    }

    public function cell(int|float $w, int|float $h, string $txt = '', int $border = 0, int $ln = 0): void
    {
        $this->currentRow[] = trim($txt);
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
        return parent::Output('S');
    }
}

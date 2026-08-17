<?php
require_once __DIR__ . '/../includes/functions.php';
require_permission('risultati', 'read');

$id = get_int('id');
$format = strtolower((string)($_GET['format'] ?? 'csv'));
$questionario = get_questionario($id);
if (!$questionario) {
    http_response_code(404);
    exit('Questionario non trovato.');
}

$gruppi = get_requisiti_revisionati($id);
$servizi = get_risultati_servizi($id, true);
$baseName = safe_export_filename('risultati_' . ($questionario['codice_progetto'] ?: $questionario['nome_progetto'] ?: 'questionario_' . $id));

if ($format === 'csv') {
    export_csv($questionario, $gruppi, $servizi, $baseName);
} elseif ($format === 'xls') {
    export_xls($questionario, $gruppi, $servizi, $baseName);
} elseif ($format === 'pdf') {
    export_pdf($questionario, $gruppi, $servizi, $baseName);
} elseif ($format === 'confluence') {
    export_confluence($questionario, $gruppi, $servizi, $baseName);
}

http_response_code(400);
exit('Formato export non supportato.');

function safe_export_filename(string $name): string {
    $name = preg_replace('/[^A-Za-z0-9_.-]+/', '_', trim($name));
    return trim($name, '_') ?: 'risultati_questionario';
}

function spreadsheet_safe_value(mixed $value): string {
    $value = (string)$value;
    return preg_match('/^\s*[=+\-@]/', $value) ? "'" . $value : $value;
}

function h_sheet(mixed $value): string {
    return h(spreadsheet_safe_value($value));
}

function export_rows(array $questionario, array $gruppi, array $servizi): array {
    $rows = [];
    foreach ([['specifici', 'Requisito specifico di progetto'], ['catalogo', 'Requisito catalogo'], ['standard', 'Requisito standard']] as [$key, $label]) {
        foreach ($gruppi[$key] ?? [] as $requisito) {
            $rows[] = [
                'Tipo' => $label,
                'Codice' => $requisito['codice'] ?? '',
                'Titolo/Nome' => $requisito['titolo'] ?? '',
                'Categoria/Macro' => $requisito['categoria'] ?? '',
                'Importanza/Tipo' => $requisito['importanza'] ?? '',
                'Owner/Reparto' => $requisito['owner'] ?? '',
                'Task/STD' => ($key === 'specifici') ? ($requisito['task_jira'] ?? '') : (($requisito['standard_dove'] ?? '') ?: ($requisito['std'] ?? '')),
                'Descrizione' => $requisito['descrizione'] ?? '',
            ];
        }
    }
    foreach ($servizi as $servizio) {
        $rows[] = [
            'Tipo' => 'Servizio',
            'Codice' => '',
            'Titolo/Nome' => $servizio['servizio_elementare'] ?? '',
            'Categoria/Macro' => $servizio['macro_service'] ?? '',
            'Importanza/Tipo' => $servizio['tipo_canone_ci'] ?? '',
            'Owner/Reparto' => $servizio['reparto_owner'] ?? '',
            'Task/STD' => '',
            'Descrizione' => $servizio['descrizione'] ?? '',
        ];
    }
    return $rows;
}

function questionnaire_meta_rows(array $questionario): array {
    return [
        ['Campo', 'Valore'],
        ['ID questionario', (string)$questionario['id']],
        ['Nome progetto', (string)$questionario['nome_progetto']],
        ['Codice progetto', (string)$questionario['codice_progetto']],
        ['Nome servizio', (string)$questionario['nome_servizio']],
        ['Business line', (string)$questionario['business_line']],
        ['PM Project Manager', (string)$questionario['pm']],
        ['PM Product Manager', (string)($questionario['pm_product_manager'] ?? '')],
        ['PO Product Owner', (string)$questionario['po']],
        ['TPO Technical Product Owner', (string)$questionario['tpo']],
        ['Tipologia progetto', (string)$questionario['tipologia_progetto']],
        ['Task JIRA', (string)($questionario['task_jira'] ?? '')],
        ['Stato', (string)$questionario['stato']],
    ];
}

function export_csv(array $questionario, array $gruppi, array $servizi, string $baseName): void {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $baseName . '.csv"');
    echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Tipo', 'Codice', 'Titolo/Nome', 'Categoria/Macro', 'Importanza/Tipo', 'Owner/Reparto', 'Task/STD', 'Descrizione'], ';');
    foreach (export_rows($questionario, $gruppi, $servizi) as $row) {
        fputcsv($out, array_map('spreadsheet_safe_value', $row), ';');
    }
    fclose($out);
    exit;
}

function export_xls(array $questionario, array $gruppi, array $servizi, string $baseName): void {
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $baseName . '.xls"');
    echo "\xEF\xBB\xBF";
    ?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
table { border-collapse: collapse; }
th, td { border: 1px solid #999; padding: 4px; vertical-align: top; }
th { background: #d9eaf7; font-weight: bold; }
h1, h2 { font-family: Arial, sans-serif; }
.standard td { background: #eeeeee; color: #666666; }
.empty { color: #777777; font-style: italic; }
</style>
</head>
<body>
<h1>Risultati questionario</h1>
<h2>Anagrafica</h2>
<table>
<?php foreach (questionnaire_meta_rows($questionario) as $row): ?>
  <tr><?php foreach ($row as $cell): ?><td><?= h_sheet($cell) ?></td><?php endforeach; ?></tr>
<?php endforeach; ?>
</table>

<?php render_xls_requisiti_section('REQUISITI SPECIFICI DI PROGETTO', $gruppi['specifici'] ?? [], false, true); ?>
<?php render_xls_requisiti_section('REQUISITI CATALOGO', $gruppi['catalogo'] ?? []); ?>
<?php render_xls_requisiti_section('REQUISITI STANDARD', $gruppi['standard'] ?? [], true); ?>
<?php render_xls_servizi_section('SERVIZI', $servizi); ?>
</body>
</html>
<?php
    exit;
}

function render_xls_requisiti_section(string $title, array $requisiti, bool $standard = false, bool $specifici = false): void {
    ?>
<h2><?= h($title) ?></h2>
<?php if (!$requisiti): ?>
<p class="empty">Nessun elemento.</p>
<?php return; endif; ?>
<table>
  <tr><th>Codice</th><th>Titolo</th><th>Categoria</th><th>Importanza</th><th>Owner</th><th><?= $specifici ? 'Task JIRA' : 'STD' ?></th><th>Descrizione</th></tr>
  <?php foreach ($requisiti as $r): ?>
  <tr class="<?= $standard ? 'standard' : '' ?>">
    <td><?= h_sheet($r['codice'] ?? '') ?></td>
    <td><?= h_sheet($r['titolo'] ?? '') ?></td>
    <td><?= h_sheet($r['categoria'] ?? '') ?></td>
    <td><?= h_sheet($r['importanza'] ?? '') ?></td>
    <td><?= h_sheet($r['owner'] ?? '') ?></td>
    <td><?= h_sheet($specifici ? ($r['task_jira'] ?? '') : (($r['standard_dove'] ?? '') ?: ($r['std'] ?? ''))) ?></td>
    <td><?= h_sheet($r['descrizione'] ?? '') ?></td>
  </tr>
  <?php endforeach; ?>
</table>
<?php
}

function render_xls_servizi_section(string $title, array $servizi): void {
    ?>
<h2><?= h($title) ?></h2>
<?php if (!$servizi): ?>
<p class="empty">Nessun elemento.</p>
<?php return; endif; ?>
<table>
  <tr><th>Servizio</th><th>Reparto owner</th><th>Macro service</th><th>Categoria</th><th>Tipo</th><th>Descrizione</th></tr>
  <?php foreach ($servizi as $s): ?>
  <tr>
    <td><?= h_sheet($s['servizio_elementare'] ?? '') ?></td>
    <td><?= h_sheet($s['reparto_owner'] ?? '') ?></td>
    <td><?= h_sheet($s['macro_service'] ?? '') ?></td>
    <td><?= h_sheet($s['categoria'] ?? '') ?></td>
    <td><?= h_sheet($s['tipo_canone_ci'] ?? '') ?></td>
    <td><?= h_sheet($s['descrizione'] ?? '') ?></td>
  </tr>
  <?php endforeach; ?>
</table>
<?php
}

function export_confluence(array $questionario, array $gruppi, array $servizi, string $baseName): void {
    header('Content-Type: text/plain; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $baseName . '_confluence.txt"');

    echo 'h1. Risultati questionario - ' . confluence_cell($questionario['nome_progetto']) . "\n\n";
    echo "h2. Anagrafica\n";
    echo "||Campo||Valore||\n";
    foreach (array_slice(questionnaire_meta_rows($questionario), 1) as $row) {
        echo '|' . confluence_cell($row[0]) . '|' . confluence_cell($row[1]) . "|\n";
    }

    confluence_requirements_section('REQUISITI SPECIFICI DI PROGETTO', $gruppi['specifici'] ?? [], true);
    confluence_requirements_section('REQUISITI CATALOGO', $gruppi['catalogo'] ?? []);
    confluence_requirements_section('REQUISITI STANDARD', $gruppi['standard'] ?? []);

    echo "\nh2. SERVIZI\n";
    echo "||Servizio||Reparto owner||Macro service||Categoria||Tipo||\n";
    foreach ($servizi as $s) {
        echo '|'
            . confluence_cell($s['servizio_elementare'] ?? '') . '|'
            . confluence_cell($s['reparto_owner'] ?? '') . '|'
            . confluence_cell($s['macro_service'] ?? '') . '|'
            . confluence_cell($s['categoria'] ?? '') . '|'
            . confluence_cell($s['tipo_canone_ci'] ?? '') . "|\n";
    }
    exit;
}

function confluence_requirements_section(string $title, array $requisiti, bool $specifici = false): void {
    echo "\nh2. {$title}\n";
    if (!$requisiti) {
        echo "_Nessun elemento._\n";
        return;
    }
    echo $specifici
        ? "||Codice||Titolo||Categoria||Importanza||Owner||Task JIRA||\n"
        : "||Codice||Titolo||Categoria||Importanza||Owner||STD||\n";
    foreach ($requisiti as $r) {
        echo '|'
            . confluence_cell($r['codice'] ?? '') . '|'
            . confluence_cell($r['titolo'] ?? '') . '|'
            . confluence_cell($r['categoria'] ?? '') . '|'
            . confluence_cell($r['importanza'] ?? '') . '|'
            . confluence_cell($r['owner'] ?? '') . '|'
            . confluence_cell($specifici ? ($r['task_jira'] ?? '') : (($r['standard_dove'] ?? '') ?: ($r['std'] ?? ''))) . "|\n";
    }
}

function confluence_cell(?string $value): string {
    $value = preg_replace('/\s+/', ' ', trim((string)$value));
    return str_replace('|', '\\|', $value);
}

function export_pdf(array $questionario, array $gruppi, array $servizi, string $baseName): void {
    $pdf = new ResultsPdfReport();
    $pdf->addInfoPage(
        $questionario,
        count($gruppi['catalogo'] ?? []),
        count($gruppi['standard'] ?? []),
        count($gruppi['specifici'] ?? []),
        count($servizi)
    );
    $pdf->addCategoryPiePage($gruppi);
    $pdf->addRequirementsTable('REQUISITI SPECIFICI DI PROGETTO', $gruppi['specifici'] ?? [], false, true);
    $pdf->addRequirementsTable('REQUISITI CATALOGO', $gruppi['catalogo'] ?? []);
    $pdf->addRequirementsTable('REQUISITI STANDARD', $gruppi['standard'] ?? [], true);
    if ($servizi) {
        $pdf->addServicesTable('SERVIZI', $servizi);
    }

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $baseName . '.pdf"');
    echo $pdf->render();
    exit;
}

class ResultsPdfReport {
    private array $pages = [];
    private const PORTRAIT_W = 595;
    private const PORTRAIT_H = 842;
    private const LANDSCAPE_W = 842;
    private const LANDSCAPE_H = 595;

    public function addInfoPage(array $questionario, int $catalogCount, int $standardCount, int $specificCount, int $servicesCount): void {
        $content = "";
        $content .= $this->filledRect(0, 780, self::PORTRAIT_W, 62, '0.01 0.16 0.46');
        $content .= $this->text(42, 812, 'Risultati questionario requisiti SEC', 18, true, '1 1 1');
        $content .= $this->text(42, 790, 'Scheda dati iniziali', 11, false, '0.86 0.92 1');

        $content .= $this->text(42, 735, 'Dati iniziali', 16, true, '0.01 0.16 0.46');
        $rows = [
            ['Nome progetto', $questionario['nome_progetto'] ?? ''],
            ['Codice progetto', $questionario['codice_progetto'] ?? ''],
            ['Nome servizio', $questionario['nome_servizio'] ?? ''],
            ['Business line', $questionario['business_line'] ?? ''],
            ['PM Project Manager', $questionario['pm'] ?? ''],
            ['PM Product Manager', $questionario['pm_product_manager'] ?? ''],
            ['PO Product Owner', $questionario['po'] ?? ''],
            ['TPO Technical Product Owner', $questionario['tpo'] ?? ''],
            ['Tipologia progetto', $questionario['tipologia_progetto'] ?? ''],
            ['Task JIRA', $questionario['task_jira'] ?? ''],
            ['Stato', $questionario['stato'] ?? ''],
            ['Requisiti specifici di progetto', (string)$specificCount],
            ['Requisiti catalogo', (string)$catalogCount],
            ['Requisiti standard', (string)$standardCount],
            ['Servizi suggeriti', (string)$servicesCount],
        ];

        $y = 705;
        foreach ($rows as $index => $row) {
            $fill = $index % 2 === 0 ? '0.96 0.98 1' : '1 1 1';
            $content .= $this->filledRect(42, $y - 18, 510, 26, $fill);
            $content .= $this->strokedRect(42, $y - 18, 510, 26, '0.78 0.83 0.90');
            $content .= $this->text(54, $y - 2, $row[0], 9, true, '0.18 0.24 0.32');
            $content .= $this->wrappedText(190, $y - 2, (string)$row[1], 9, 72, 2, false, '0.05 0.05 0.05');
            $y -= 28;
        }

        $content .= $this->text(42, 120, 'Nota', 12, true, '0.01 0.16 0.46');
        $content .= $this->wrappedText(
            42,
            100,
            'Le pagine successive sono in formato orizzontale per rendere leggibile la tabella dei requisiti e dei servizi esportati.',
            9,
            95,
            3,
            false,
            '0.25 0.25 0.25'
        );
        $this->pages[] = ['w' => self::PORTRAIT_W, 'h' => self::PORTRAIT_H, 'content' => $content];
    }

    public function addRequirementsTable(string $title, array $requisiti, bool $standard = false, bool $specifici = false): void {
        $headers = ['Codice', 'Titolo', 'Categoria', 'Imp.', 'Owner', $specifici ? 'Task JIRA' : 'STD', 'Descrizione'];
        $widths = [76, 190, 130, 42, 78, 65, 211];
        $rows = [];
        foreach ($requisiti as $r) {
            $rows[] = [
                $r['codice'] ?? '',
                $r['titolo'] ?? '',
                $r['categoria'] ?? '',
                $r['importanza'] ?? '',
                $r['owner'] ?? '',
                $specifici ? ($r['task_jira'] ?? '') : (($r['standard_dove'] ?? '') ?: ($r['std'] ?? '')),
                $r['descrizione'] ?? '',
            ];
        }
        $this->addLandscapeTable($title, $headers, $rows, $widths, 45, 7.2, $standard);
    }

    public function addCategoryPiePage(array $gruppi): void {
        $counts = [];
        foreach (['specifici', 'catalogo', 'standard'] as $key) {
            foreach ($gruppi[$key] ?? [] as $requisito) {
                $categoria = trim((string)($requisito['categoria'] ?? 'Senza categoria')) ?: 'Senza categoria';
                $counts[$categoria] = ($counts[$categoria] ?? 0) + 1;
            }
        }
        if (!$counts) {
            return;
        }
        arsort($counts);
        $top = array_slice($counts, 0, 8, true);
        if (count($counts) > 8) {
            $top['Altre categorie'] = array_sum(array_slice($counts, 8, null, true));
        }
        $total = array_sum($top);
        $colors = ['0.05 0.33 0.67','0.10 0.58 0.38','0.88 0.33 0.20','0.62 0.35 0.75','0.95 0.62 0.16','0.18 0.55 0.68','0.55 0.55 0.55','0.75 0.18 0.32','0.35 0.45 0.95'];

        $content = '';
        $content .= $this->text(42, 805, 'Distribuzione requisiti per categoria', 18, true, '0.01 0.16 0.46');
        $content .= $this->text(42, 782, 'Torta calcolata sui requisiti specifici, catalogo e standard presenti nel risultato.', 9, false, '0.25 0.25 0.25');

        $centerX = 210;
        $centerY = 455;
        $radius = 145;
        $angle = 0.0;
        $index = 0;
        foreach ($top as $categoria => $count) {
            $span = ($count / $total) * 360.0;
            $content .= $this->pieSlice($centerX, $centerY, $radius, $angle, $angle + $span, $colors[$index % count($colors)]);
            $angle += $span;
            $index++;
        }
        $content .= $this->strokedCircle($centerX, $centerY, $radius, '1 1 1');

        $legendX = 405;
        $legendY = 610;
        $index = 0;
        foreach ($top as $categoria => $count) {
            $percent = round(($count / $total) * 100, 1);
            $y = $legendY - ($index * 42);
            $content .= $this->filledRect($legendX, $y - 10, 14, 14, $colors[$index % count($colors)]);
            $content .= $this->wrappedText($legendX + 22, $y, "{$categoria} — {$count} ({$percent}%)", 8.5, 34, 2, false, '0.06 0.08 0.12');
            $index++;
        }

        $this->pages[] = ['w' => self::PORTRAIT_W, 'h' => self::PORTRAIT_H, 'content' => $content];
    }

    public function addServicesTable(string $title, array $servizi): void {
        $headers = ['Servizio', 'Reparto', 'Macro service', 'Categoria', 'Tipo', 'Descrizione'];
        $widths = [175, 100, 155, 135, 48, 179];
        $rows = [];
        foreach ($servizi as $s) {
            $rows[] = [
                $s['servizio_elementare'] ?? '',
                $s['reparto_owner'] ?? '',
                $s['macro_service'] ?? '',
                $s['categoria'] ?? '',
                $s['tipo_canone_ci'] ?? '',
                $s['descrizione'] ?? '',
            ];
        }
        $this->addLandscapeTable($title, $headers, $rows, $widths, 45, 7.2);
    }

    private function addLandscapeTable(string $title, array $headers, array $rows, array $widths, int $rowHeight, float $fontSize, bool $muted = false): void {
        $marginX = 24;
        $top = 548;
        $headerHeight = 22;
        $bottom = 30;
        $rowsPerPage = max(1, (int)floor(($top - 42 - $headerHeight - $bottom) / $rowHeight));
        $chunks = array_chunk($rows, $rowsPerPage);
        if (!$chunks) {
            $chunks = [[]];
        }

        foreach ($chunks as $pageIndex => $chunk) {
            $content = '';
            $content .= $this->text($marginX, 570, $title . ' - pagina ' . ($pageIndex + 1) . ' di ' . count($chunks), 13, true, '0.01 0.16 0.46');
            $content .= $this->filledRect($marginX, $top - $headerHeight, array_sum($widths), $headerHeight, '0.01 0.16 0.46');

            $x = $marginX;
            foreach ($headers as $i => $header) {
                $content .= $this->strokedRect($x, $top - $headerHeight, $widths[$i], $headerHeight, '0.75 0.80 0.88');
                $content .= $this->wrappedText($x + 4, $top - 8, $header, 7.8, $this->charsForWidth($widths[$i], 7.8), 1, true, '1 1 1');
                $x += $widths[$i];
            }

            $y = $top - $headerHeight;
            foreach ($chunk as $rowIndex => $row) {
                $y -= $rowHeight;
                $fill = $muted ? ($rowIndex % 2 === 0 ? '0.90 0.91 0.92' : '0.95 0.95 0.95') : ($rowIndex % 2 === 0 ? '0.98 0.99 1' : '1 1 1');
                $content .= $this->filledRect($marginX, $y, array_sum($widths), $rowHeight, $fill);
                $x = $marginX;
                foreach ($row as $i => $cell) {
                    $content .= $this->strokedRect($x, $y, $widths[$i], $rowHeight, '0.82 0.86 0.92');
                    $content .= $this->wrappedText(
                        $x + 4,
                        $y + $rowHeight - 10,
                        (string)$cell,
                        $fontSize,
                        $this->charsForWidth($widths[$i], $fontSize),
                        max(1, (int)floor(($rowHeight - 8) / ($fontSize + 2))),
                        $i === 0,
                        $muted ? '0.35 0.35 0.35' : '0.06 0.08 0.12'
                    );
                    $x += $widths[$i];
                }
            }
            $this->pages[] = ['w' => self::LANDSCAPE_W, 'h' => self::LANDSCAPE_H, 'content' => $content];
        }
    }

    private function charsForWidth(int $width, float $fontSize): int {
        return max(6, (int)floor($width / ($fontSize * 0.48)));
    }

    private function wrappedText(float $x, float $y, string $text, float $size, int $chars, int $maxLines, bool $bold = false, string $color = '0 0 0'): string {
        $text = preg_replace('/\s+/', ' ', trim($this->toPdfText($text)));
        if ($text === '') {
            return '';
        }
        $lines = explode("\n", wordwrap($text, $chars, "\n", true));
        if (count($lines) > $maxLines) {
            $lines = array_slice($lines, 0, $maxLines);
            $lines[$maxLines - 1] = rtrim(substr($lines[$maxLines - 1], 0, max(0, $chars - 3))) . '...';
        }
        $content = '';
        foreach ($lines as $index => $line) {
            $content .= $this->text($x, $y - ($index * ($size + 2)), $line, $size, $bold, $color);
        }
        return $content;
    }

    private function text(float $x, float $y, string $text, float $size = 9, bool $bold = false, string $color = '0 0 0'): string {
        $font = $bold ? 'F2' : 'F1';
        return "BT\n{$color} rg\n/{$font} {$size} Tf\n1 0 0 1 " . round($x, 2) . ' ' . round($y, 2) . " Tm\n(" . $this->esc($this->toPdfText($text)) . ") Tj\nET\n";
    }

    private function filledRect(float $x, float $y, float $w, float $h, string $color): string {
        return "q\n{$color} rg\n" . round($x, 2) . ' ' . round($y, 2) . ' ' . round($w, 2) . ' ' . round($h, 2) . " re f\nQ\n";
    }

    private function strokedRect(float $x, float $y, float $w, float $h, string $color): string {
        return "q\n{$color} RG\n0.4 w\n" . round($x, 2) . ' ' . round($y, 2) . ' ' . round($w, 2) . ' ' . round($h, 2) . " re S\nQ\n";
    }

    private function strokedCircle(float $x, float $y, float $radius, string $color): string {
        $k = 0.5522847498;
        $c = $radius * $k;
        return "q\n{$color} RG\n0.8 w\n"
            . round($x + $radius, 2) . ' ' . round($y, 2) . " m\n"
            . round($x + $radius, 2) . ' ' . round($y + $c, 2) . ' ' . round($x + $c, 2) . ' ' . round($y + $radius, 2) . ' ' . round($x, 2) . ' ' . round($y + $radius, 2) . " c\n"
            . round($x - $c, 2) . ' ' . round($y + $radius, 2) . ' ' . round($x - $radius, 2) . ' ' . round($y + $c, 2) . ' ' . round($x - $radius, 2) . ' ' . round($y, 2) . " c\n"
            . round($x - $radius, 2) . ' ' . round($y - $c, 2) . ' ' . round($x - $c, 2) . ' ' . round($y - $radius, 2) . ' ' . round($x, 2) . ' ' . round($y - $radius, 2) . " c\n"
            . round($x + $c, 2) . ' ' . round($y - $radius, 2) . ' ' . round($x + $radius, 2) . ' ' . round($y - $c, 2) . ' ' . round($x + $radius, 2) . ' ' . round($y, 2) . " c S\nQ\n";
    }

    private function pieSlice(float $cx, float $cy, float $radius, float $startDeg, float $endDeg, string $color): string {
        $points = [[$cx, $cy]];
        $steps = max(3, (int)ceil(abs($endDeg - $startDeg) / 8));
        for ($i = 0; $i <= $steps; $i++) {
            $deg = $startDeg + (($endDeg - $startDeg) * ($i / $steps));
            $rad = deg2rad($deg - 90);
            $points[] = [$cx + cos($rad) * $radius, $cy + sin($rad) * $radius];
        }
        $path = "q\n{$color} rg\n";
        foreach ($points as $index => $point) {
            $path .= round($point[0], 2) . ' ' . round($point[1], 2) . ($index === 0 ? " m\n" : " l\n");
        }
        return $path . "h f\nQ\n";
    }

    private function toPdfText(string $text): string {
        $converted = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $text);
        return $converted === false ? preg_replace('/[^\x20-\x7E]/', '?', $text) : $converted;
    }

    private function esc(string $text): string {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }

    public function render(): string {
        $objects = [];
        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
        $objects[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>';

        $kids = [];
        $nextId = 5;
        foreach ($this->pages as $page) {
            $contentId = $nextId++;
            $pageId = $nextId++;
            $stream = $page['content'];
            $objects[$contentId] = '<< /Length ' . strlen($stream) . " >>\nstream\n" . $stream . "endstream";
            $objects[$pageId] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ' . $page['w'] . ' ' . $page['h'] . '] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents ' . $contentId . ' 0 R >>';
            $kids[] = $pageId . ' 0 R';
        }
        $objects[2] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . count($kids) . ' >>';
        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $id => $object) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id . " 0 obj\n" . $object . "\nendobj\n";
        }
        $xref = strlen($pdf);
        $maxId = max(array_keys($objects));
        $pdf .= "xref\n0 " . ($maxId + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= $maxId; $i++) {
            $pdf .= isset($offsets[$i]) ? sprintf("%010d 00000 n \n", $offsets[$i]) : "0000000000 00000 f \n";
        }
        $pdf .= "trailer\n<< /Size " . ($maxId + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n" . $xref . "\n%%EOF";
        return $pdf;
    }
}

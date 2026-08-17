<?php
require_once __DIR__ . '/../includes/functions.php';
require_permission('pir', 'read');

$id = get_int('id');
$questionario = get_questionario($id);
if (!$questionario) {
    http_response_code(404);
    exit('Questionario non trovato.');
}
if (($questionario['pir_stato'] ?? 'in_corso') !== 'completata') {
    http_response_code(400);
    exit('Il report PDF è disponibile solo per PIR completate.');
}

$requirements = pir_project_requirements($id);
$reviews = get_pir_reviews_map($id);
$meetings = get_pir_meetings($id);
$participants = get_pir_all_participants($id);
$unsatisfied = [];
foreach ($requirements as $req) {
    $key = $req['pir_tipo'] . ':' . $req['pir_ref_id'];
    $review = $reviews[$key] ?? [];
    if (in_array((string)($review['stato'] ?? ''), ['KO', 'parziale'], true)) {
        $req['pir_review'] = $review;
        $unsatisfied[] = $req;
    }
}

$pdf = new PirPdfReport();
$pdf->addCover($questionario, count($requirements), count($unsatisfied));
$pdf->addMeetingsPage($meetings, $participants);
$pdf->addUnsatisfiedChart($unsatisfied);
$pdf->addUnsatisfiedDetails($unsatisfied);

$name = preg_replace('/[^A-Za-z0-9_.-]+/', '_', trim('PIR_' . ($questionario['codice_progetto'] ?: $questionario['nome_progetto'] ?: $id)));
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . ($name ?: 'PIR_report') . '.pdf"');
echo $pdf->render();
exit;

class PirPdfReport {
    private array $pages = [];
    private const W = 595;
    private const H = 842;

    public function addCover(array $q, int $total, int $unsatisfied): void {
        $c = $this->filledRect(0, 780, self::W, 62, '0.01 0.16 0.46');
        $c .= $this->text(42, 812, 'Report PIR sicurezza', 20, true, '1 1 1');
        $c .= $this->text(42, 790, 'Post Implementation Review', 11, false, '0.86 0.92 1');
        $rows = [
            ['Progetto', $q['nome_progetto'] ?? ''],
            ['Codice progetto', $q['codice_progetto'] ?? ''],
            ['Servizio', $q['nome_servizio'] ?? ''],
            ['Business line', $q['business_line'] ?? ''],
            ['Task JIRA', $q['task_jira'] ?? ''],
            ['Analista PIR', $q['pir_analista_nome'] ?? ''],
            ['Stato PIR', 'COMPLETATA'],
            ['Requisiti verificati', (string)$total],
            ['Requisiti non soddisfatti', (string)$unsatisfied],
        ];
        $y = 715;
        foreach ($rows as $i => $row) {
            $c .= $this->filledRect(42, $y - 18, 510, 26, $i % 2 === 0 ? '0.96 0.98 1' : '1 1 1');
            $c .= $this->strokedRect(42, $y - 18, 510, 26, '0.78 0.83 0.90');
            $c .= $this->text(54, $y - 2, $row[0], 9, true, '0.18 0.24 0.32');
            $c .= $this->wrappedText(190, $y - 2, (string)$row[1], 9, 72, 2);
            $y -= 28;
        }
        $this->pages[] = ['content' => $c];
    }

    public function addMeetingsPage(array $meetings, array $participants): void {
        $c = $this->text(42, 805, 'Riunioni e partecipanti', 18, true, '0.01 0.16 0.46');
        $y = 765;
        $c .= $this->text(42, $y, 'Date riunioni', 12, true);
        $y -= 24;
        foreach ($meetings as $m) {
            $c .= $this->text(54, $y, date('d/m/Y', strtotime((string)$m['data_riunione'])) . ' - ' . substr((string)($m['note'] ?? ''), 0, 85), 9);
            $y -= 16;
        }
        $y -= 18;
        $c .= $this->text(42, $y, 'Partecipanti invitati', 12, true);
        $y -= 22;
        foreach ($participants as $p) {
            if ($y < 70) {
                $this->pages[] = ['content' => $c];
                $c = $this->text(42, 805, 'Partecipanti - continua', 18, true, '0.01 0.16 0.46');
                $y = 765;
            }
            $present = (int)($p['partecipato'] ?? 1) === 1 ? 'presente' : 'assente';
            $c .= $this->wrappedText(54, $y, trim($p['nome'] . ' - ' . ($p['ruolo'] ?? '') . ' - ' . ($p['reparto'] ?? '') . ' - ' . $present), 8.5, 95, 1);
            $y -= 15;
        }
        $this->pages[] = ['content' => $c];
    }

    public function addUnsatisfiedChart(array $requirements): void {
        $counts = [];
        foreach ($requirements as $r) {
            $cat = trim((string)($r['categoria'] ?? 'Senza categoria')) ?: 'Senza categoria';
            $counts[$cat] = ($counts[$cat] ?? 0) + 1;
        }
        $c = $this->text(42, 805, 'Requisiti non soddisfatti per categoria', 18, true, '0.01 0.16 0.46');
        if (!$counts) {
            $c .= $this->text(42, 760, 'Nessun requisito KO o parziale.', 11);
            $this->pages[] = ['content' => $c];
            return;
        }
        arsort($counts);
        $total = array_sum($counts);
        $colors = ['0.88 0.22 0.16','0.95 0.62 0.16','0.62 0.35 0.75','0.05 0.33 0.67','0.10 0.58 0.38','0.55 0.55 0.55'];
        $angle = 0.0;
        $i = 0;
        foreach ($counts as $cat => $count) {
            $span = ($count / $total) * 360;
            $c .= $this->pieSlice(210, 470, 145, $angle, $angle + $span, $colors[$i % count($colors)]);
            $angle += $span;
            $i++;
        }
        $y = 620;
        $i = 0;
        foreach ($counts as $cat => $count) {
            $c .= $this->filledRect(390, $y - 10, 14, 14, $colors[$i % count($colors)]);
            $c .= $this->wrappedText(412, $y, $cat . ' - ' . $count . ' (' . round($count / $total * 100, 1) . '%)', 8.5, 32, 2);
            $y -= 38;
            $i++;
        }
        $this->pages[] = ['content' => $c];
    }

    public function addUnsatisfiedDetails(array $requirements): void {
        $c = $this->text(42, 805, 'Dettaglio requisiti non soddisfatti', 18, true, '0.01 0.16 0.46');
        $y = 765;
        foreach ($requirements as $r) {
            if ($y < 145) {
                $this->pages[] = ['content' => $c];
                $c = $this->text(42, 805, 'Dettaglio requisiti non soddisfatti - continua', 18, true, '0.01 0.16 0.46');
                $y = 765;
            }
            $rev = $r['pir_review'] ?? [];
            $catalogStatus = $r['pir_catalog_status'] ?? [];
            $c .= $this->text(42, $y, ($r['codice'] ?? '') . ' - ' . ($r['titolo'] ?? ''), 10, true);
            $y -= 16;
            $c .= $this->wrappedText(54, $y, 'Categoria: ' . ($r['categoria'] ?? '') . ' | Stato: ' . ($rev['stato'] ?? '') . ' | Referente: ' . ($rev['referente_nome'] ?? ''), 8.5, 105, 2);
            $y -= 30;
            $c .= $this->wrappedText(54, $y, 'Evoluzione catalogo: ' . ($catalogStatus['label'] ?? '') . ' - ' . ($catalogStatus['detail'] ?? ''), 8.5, 105, 2);
            $y -= 30;
            $c .= $this->wrappedText(54, $y, 'Note: ' . ($rev['note'] ?? ''), 8.5, 105, 3);
            $y -= 42;
            $c .= $this->wrappedText(54, $y, 'Applicazione/Motivazione: ' . ($rev['applicazione'] ?? ''), 8.5, 105, 3);
            $y -= 42;
            $c .= $this->wrappedText(54, $y, 'Rientro/Eccezione: ' . ($rev['rientro_eccezione'] ?? ''), 8.5, 105, 2);
            $y -= 44;
        }
        $this->pages[] = ['content' => $c];
    }

    private function wrappedText(float $x, float $y, string $text, float $size, int $chars, int $maxLines, bool $bold = false): string {
        $text = preg_replace('/\s+/', ' ', trim($this->toPdfText($text)));
        if ($text === '') return '';
        $lines = explode("\n", wordwrap($text, $chars, "\n", true));
        if (count($lines) > $maxLines) {
            $lines = array_slice($lines, 0, $maxLines);
            $lines[$maxLines - 1] = rtrim(substr($lines[$maxLines - 1], 0, max(0, $chars - 3))) . '...';
        }
        $c = '';
        foreach ($lines as $i => $line) $c .= $this->text($x, $y - ($i * ($size + 2)), $line, $size, $bold);
        return $c;
    }

    private function text(float $x, float $y, string $text, float $size = 9, bool $bold = false, string $color = '0 0 0'): string {
        return "BT\n{$color} rg\n/" . ($bold ? 'F2' : 'F1') . " {$size} Tf\n1 0 0 1 " . round($x, 2) . ' ' . round($y, 2) . " Tm\n(" . $this->esc($this->toPdfText($text)) . ") Tj\nET\n";
    }
    private function filledRect(float $x, float $y, float $w, float $h, string $color): string { return "q\n{$color} rg\n" . round($x, 2) . ' ' . round($y, 2) . ' ' . round($w, 2) . ' ' . round($h, 2) . " re f\nQ\n"; }
    private function strokedRect(float $x, float $y, float $w, float $h, string $color): string { return "q\n{$color} RG\n0.4 w\n" . round($x, 2) . ' ' . round($y, 2) . ' ' . round($w, 2) . ' ' . round($h, 2) . " re S\nQ\n"; }
    private function pieSlice(float $cx, float $cy, float $radius, float $startDeg, float $endDeg, string $color): string {
        $path = "q\n{$color} rg\n" . round($cx, 2) . ' ' . round($cy, 2) . " m\n";
        $steps = max(3, (int)ceil(abs($endDeg - $startDeg) / 8));
        for ($i = 0; $i <= $steps; $i++) {
            $rad = deg2rad($startDeg + (($endDeg - $startDeg) * ($i / $steps)) - 90);
            $path .= round($cx + cos($rad) * $radius, 2) . ' ' . round($cy + sin($rad) * $radius, 2) . " l\n";
        }
        return $path . "h f\nQ\n";
    }
    private function toPdfText(string $text): string { $c = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $text); return $c === false ? preg_replace('/[^\x20-\x7E]/', '?', $text) : $c; }
    private function esc(string $text): string { return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text); }
    public function render(): string {
        $objects = [1 => '<< /Type /Catalog /Pages 2 0 R >>', 3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>', 4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>'];
        $kids = [];
        $next = 5;
        foreach ($this->pages as $page) {
            $contentId = $next++;
            $pageId = $next++;
            $stream = $page['content'];
            $objects[$contentId] = '<< /Length ' . strlen($stream) . " >>\nstream\n" . $stream . "endstream";
            $objects[$pageId] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ' . self::W . ' ' . self::H . '] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents ' . $contentId . ' 0 R >>';
            $kids[] = $pageId . ' 0 R';
        }
        $objects[2] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . count($kids) . ' >>';
        ksort($objects);
        $pdf = "%PDF-1.4\n"; $offsets = [0];
        foreach ($objects as $id => $object) { $offsets[$id] = strlen($pdf); $pdf .= "$id 0 obj\n$object\nendobj\n"; }
        $xref = strlen($pdf); $max = max(array_keys($objects));
        $pdf .= "xref\n0 " . ($max + 1) . "\n0000000000 65535 f \n";
        for ($i = 1; $i <= $max; $i++) $pdf .= isset($offsets[$i]) ? sprintf("%010d 00000 n \n", $offsets[$i]) : "0000000000 00000 f \n";
        return $pdf . "trailer\n<< /Size " . ($max + 1) . " /Root 1 0 R >>\nstartxref\n$xref\n%%EOF";
    }
}

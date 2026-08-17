<?php
require_once __DIR__ . "/../includes/functions.php";
require_permission("threat_analysis", "read");

$analysisId = (int)($_GET["analysis_id"] ?? 0);
$analysis = $analysisId > 0 ? get_threat_analysis($analysisId) : false;
if (!$analysis || (string)$analysis["status"] !== "ok") {
    http_response_code(404);
    echo "Threat Analysis non trovata.";
    exit;
}

$questionario = get_questionario((int)$analysis["questionario_id"]);
$sections = ensure_threat_analysis_sections($analysisId);

$pdf = new ThreatAnalysisPdf();
$pdf->addCover($analysis, $questionario ?: [], count($sections));
foreach ($sections as $section) {
    $pdf->addSection($section);
}

$filename = "threat-analysis-" . $analysisId . ".pdf";
header("Content-Type: application/pdf");
header('Content-Disposition: attachment; filename="' . $filename . '"');
echo $pdf->render();
exit;

class ThreatAnalysisPdf {
    private array $pages = [];
    private const W = 595;
    private const H = 842;

    public function addCover(array $analysis, array $questionario, int $sectionCount): void {
        $content = "";
        $content .= $this->filledRect(0, 780, self::W, 62, "0.01 0.16 0.46");
        $content .= $this->text(42, 812, "Threat Analysis", 20, true, "1 1 1");
        $content .= $this->text(42, 790, "Documento generato da questionario e normalizzato in sezioni editabili", 9, false, "0.86 0.92 1");
        $content .= $this->text(42, 735, "Dati progetto", 16, true, "0.01 0.16 0.46");

        $rows = [
            ["Progetto", (string)($questionario["nome_progetto"] ?? $analysis["nome_progetto"] ?? "")],
            ["Codice progetto", (string)($questionario["codice_progetto"] ?? $analysis["codice_progetto"] ?? "")],
            ["Task JIRA", (string)($questionario["task_jira"] ?? "")],
            ["Business line", (string)($questionario["business_line"] ?? "")],
            ["Servizio", (string)($questionario["nome_servizio"] ?? "")],
            ["Analista", (string)($questionario["analista_questionario_nome"] ?? "")],
            ["Modello IA", (string)($analysis["model_name"] ?? "")],
            ["Provider", (string)($analysis["ollama_base_url"] ?? "")],
            ["Data analisi", (string)($analysis["created_at"] ?? "")],
            ["Sezioni", (string)$sectionCount],
        ];

        $y = 705;
        foreach ($rows as $index => $row) {
            $fill = $index % 2 === 0 ? "0.96 0.98 1" : "1 1 1";
            $content .= $this->filledRect(42, $y - 18, 510, 26, $fill);
            $content .= $this->strokedRect(42, $y - 18, 510, 26, "0.78 0.83 0.90");
            $content .= $this->text(54, $y - 2, $row[0], 9, true, "0.18 0.24 0.32");
            $content .= $this->wrappedText(190, $y - 2, $row[1], 9, 72, 2);
            $y -= 28;
        }

        $this->pages[] = ["w" => self::W, "h" => self::H, "content" => $content];
    }

    public function addSection(array $section): void {
        $title = trim(((string)($section["section_number"] ?? "") !== "" ? (string)$section["section_number"] . ". " : "") . (string)($section["title"] ?? "Sezione"));
        $text = threat_analysis_html_to_text((string)($section["content_html"] ?? ""));
        if ($text === "") {
            $text = (string)($section["content_text"] ?? "");
        }
        $paragraphs = array_values(array_filter(array_map("trim", preg_split('/\n{1,}/', $text) ?: [])));
        if (!$paragraphs) {
            $paragraphs = [""];
        }

        $content = "";
        $y = 805;
        $content .= $this->text(42, $y, $title, 15, true, "0.01 0.16 0.46");
        $y -= 30;
        foreach ($paragraphs as $paragraph) {
            $lines = $this->wrapLines($paragraph, 104);
            foreach ($lines as $line) {
                if ($y < 70) {
                    $this->pages[] = ["w" => self::W, "h" => self::H, "content" => $content];
                    $content = "";
                    $y = 805;
                    $content .= $this->text(42, $y, $title . " (continua)", 13, true, "0.01 0.16 0.46");
                    $y -= 30;
                }
                $content .= $this->text(42, $y, $line, 9, false, "0.06 0.08 0.12");
                $y -= 13;
            }
            $y -= 8;
        }
        $this->pages[] = ["w" => self::W, "h" => self::H, "content" => $content];
    }

    private function wrapLines(string $text, int $chars): array {
        $text = preg_replace('/\s+/', ' ', trim($this->toPdfText($text))) ?? "";
        if ($text === "") {
            return [""];
        }
        return explode("\n", wordwrap($text, $chars, "\n", true));
    }

    private function wrappedText(float $x, float $y, string $text, float $size, int $chars, int $maxLines, bool $bold = false, string $color = "0 0 0"): string {
        $lines = $this->wrapLines($text, $chars);
        if (count($lines) > $maxLines) {
            $lines = array_slice($lines, 0, $maxLines);
            $lines[$maxLines - 1] = rtrim(substr($lines[$maxLines - 1], 0, max(0, $chars - 3))) . "...";
        }
        $content = "";
        foreach ($lines as $index => $line) {
            $content .= $this->text($x, $y - ($index * ($size + 2)), $line, $size, $bold, $color);
        }
        return $content;
    }

    private function text(float $x, float $y, string $text, float $size = 9, bool $bold = false, string $color = "0 0 0"): string {
        $font = $bold ? "F2" : "F1";
        return "BT\n{$color} rg\n/{$font} {$size} Tf\n1 0 0 1 " . round($x, 2) . " " . round($y, 2) . " Tm\n(" . $this->esc($this->toPdfText($text)) . ") Tj\nET\n";
    }

    private function filledRect(float $x, float $y, float $w, float $h, string $color): string {
        return "q\n{$color} rg\n" . round($x, 2) . " " . round($y, 2) . " " . round($w, 2) . " " . round($h, 2) . " re f\nQ\n";
    }

    private function strokedRect(float $x, float $y, float $w, float $h, string $color): string {
        return "q\n{$color} RG\n0.4 w\n" . round($x, 2) . " " . round($y, 2) . " " . round($w, 2) . " " . round($h, 2) . " re S\nQ\n";
    }

    private function toPdfText(string $text): string {
        $converted = @iconv("UTF-8", "ISO-8859-1//TRANSLIT//IGNORE", $text);
        return $converted === false ? (preg_replace('/[^\x20-\x7E]/', "?", $text) ?? $text) : $converted;
    }

    private function esc(string $text): string {
        return str_replace(["\\", "(", ")"], ["\\\\", "\\(", "\\)"], $text);
    }

    public function render(): string {
        $objects = [];
        $objects[1] = "<< /Type /Catalog /Pages 2 0 R >>";
        $objects[3] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";
        $objects[4] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>";

        $kids = [];
        $nextId = 5;
        foreach ($this->pages as $page) {
            $contentId = $nextId++;
            $pageId = $nextId++;
            $stream = $page["content"];
            $objects[$contentId] = "<< /Length " . strlen($stream) . " >>\nstream\n" . $stream . "endstream";
            $objects[$pageId] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 " . $page["w"] . " " . $page["h"] . "] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents " . $contentId . " 0 R >>";
            $kids[] = $pageId . " 0 R";
        }
        $objects[2] = "<< /Type /Pages /Kids [" . implode(" ", $kids) . "] /Count " . count($kids) . " >>";
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

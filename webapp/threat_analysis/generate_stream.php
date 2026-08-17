<?php
require_once __DIR__ . "/../includes/functions.php";
require_permission("threat_analysis", "create");

@set_time_limit(max(60, OLLAMA_TIMEOUT_SECONDS + 60));
ignore_user_abort(false);

function threat_stream_send(array $event): void {
    echo requisito_version_json($event) . "\n";
    while (ob_get_level() > 0) {
        @ob_end_flush();
    }
    @flush();
}

header('Content-Type: application/x-ndjson; charset=UTF-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('X-Accel-Buffering: no');

try {
    verify_csrf();
    $questionarioId = (int)post("questionario_id");
    $provider = get_ai_provider((int)post("provider_id")) ?: get_default_ai_provider();
    $model = trim((string)post("model", ""));
    $prompt = trim((string)post("prompt", ""));

    if (!get_questionario($questionarioId)) {
        threat_stream_send(["type" => "error", "message" => "Questionario non valido."]);
        exit;
    }
    if ($prompt === "") {
        threat_stream_send(["type" => "error", "message" => "Prompt non valorizzato."]);
        exit;
    }

    threat_stream_send(["type" => "status", "message" => "Preparo contesto questionario..."]);
    $context = build_questionario_ai_context($questionarioId);
    if (!$context) {
        threat_stream_send(["type" => "error", "message" => "Impossibile costruire il contesto del questionario."]);
        exit;
    }

    threat_stream_send([
        "type" => "status",
        "message" => "Invio richiesta alla IA...",
        "meta" => [
            "provider" => (string)($provider["nome"] ?? ""),
            "provider_type" => (string)($provider["provider_type"] ?? ""),
            "model" => $model ?: (string)($provider["default_model"] ?? ""),
            "endpoint" => ai_provider_base_url($provider),
            "risposte" => count($context["risposte"] ?? []),
            "requisiti" => count($context["requisiti_catalogo_applicabili"] ?? []),
            "standard" => count($context["requisiti_standard"] ?? []),
            "specifici" => count($context["requisiti_specifici_progetto"] ?? []),
            "servizi" => count($context["servizi_applicabili"] ?? []),
        ],
    ]);

    $fullPrompt = threat_analysis_full_prompt($prompt, $context);
    $lastBeat = time();
    $result = ai_generate_stream($provider, $model, $fullPrompt, function (string $chunk) use (&$lastBeat): void {
        threat_stream_send(["type" => "chunk", "text" => $chunk]);
        if (time() - $lastBeat >= 10) {
            $lastBeat = time();
            threat_stream_send(["type" => "status", "message" => "La IA sta ancora generando..."]);
        }
    });

    if (!$result["ok"]) {
        threat_stream_send(["type" => "status", "message" => "Salvo esito con errore..."]);
        $analysisId = save_threat_analysis(
            $questionarioId,
            (string)($result["model"] ?? $model),
            ai_provider_base_url($provider),
            $prompt,
            $context,
            (string)($result["response"] ?? ""),
            "error",
            (string)($result["error"] ?? "Errore IA")
        );
        threat_stream_send(["type" => "error", "message" => (string)($result["error"] ?? "Errore IA"), "analysis_id" => $analysisId]);
        exit;
    }

    threat_stream_send(["type" => "status", "message" => "Salvo Threat Analysis..."]);
    $analysisId = save_threat_analysis(
        $questionarioId,
        (string)$result["model"],
        ai_provider_base_url($provider),
        $prompt,
        $context,
        (string)$result["response"],
        "ok"
    );
    threat_stream_send(["type" => "done", "message" => "Threat Analysis completata.", "analysis_id" => $analysisId]);
} catch (Throwable $e) {
    threat_stream_send(["type" => "error", "message" => "Errore applicativo durante la generazione."]);
}

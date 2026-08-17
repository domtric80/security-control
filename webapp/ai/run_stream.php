<?php
require_once __DIR__ . '/../includes/functions.php';
require_permission('ai_assistant', 'create');

@set_time_limit(max(60, OLLAMA_TIMEOUT_SECONDS + 120));
ignore_user_abort(false);

function ai_stream_send(array $event): void {
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
    $questionarioId = (int)post('questionario_id');
    $analysisType = (string)post('analysis_type', 'specific_requirements');
    $provider = get_ai_provider((int)post('provider_id')) ?: get_default_ai_provider();
    $model = trim((string)post('model', ''));

    if (!get_questionario($questionarioId)) {
        ai_stream_send(['type' => 'error', 'message' => 'Questionario non valido.']);
        exit;
    }
    if (!array_key_exists($analysisType, ai_analysis_types())) {
        ai_stream_send(['type' => 'error', 'message' => 'Tipo analisi IA non valido.']);
        exit;
    }

    $typeMeta = ai_analysis_type($analysisType);
    ai_stream_send(['type' => 'status', 'message' => 'Preparo contesto per ' . $typeMeta['label'] . '...']);
    $context = build_ai_assistant_context($questionarioId, $analysisType);
    $prompt = ai_assistant_prompt($analysisType, $context);
    ai_stream_send([
        'type' => 'status',
        'message' => 'Invio richiesta alla IA...',
        'meta' => [
            'provider' => (string)($provider['nome'] ?? ''),
            'endpoint' => ai_provider_base_url($provider),
            'model' => $model ?: (string)($provider['default_model'] ?? ''),
            'prompt_chars' => strlen($prompt),
        ],
    ]);

    $started = microtime(true);
    $lastBeat = time();
    $result = ai_generate_stream($provider, $model, $prompt, function (string $chunk) use (&$lastBeat): void {
        ai_stream_send(['type' => 'chunk', 'text' => $chunk]);
        if (time() - $lastBeat >= 10) {
            $lastBeat = time();
            ai_stream_send(['type' => 'status', 'message' => 'La IA sta ancora generando...']);
        }
    });
    $durationMs = (int)round((microtime(true) - $started) * 1000);

    $response = (string)($result['response'] ?? '');
    $parsed = $result['ok'] ? ai_extract_json($response) : ['summary' => '', 'suggestions' => []];
    ai_stream_send(['type' => 'status', 'message' => 'Salvo analisi e suggerimenti...']);
    $runId = save_ai_analysis_run(
        $questionarioId,
        (int)($provider['id'] ?? 0),
        $analysisType,
        (string)($result['model'] ?? $model),
        $prompt,
        $context,
        $response,
        $parsed,
        $result['ok'] ? 'ok' : 'error',
        (string)($result['error'] ?? ''),
        $durationMs
    );

    if (!$result['ok']) {
        ai_stream_send([
            'type' => 'error',
            'message' => 'Errore IA: ' . (string)($result['error'] ?? 'errore non specificato'),
            'run_id' => $runId,
        ]);
        exit;
    }

    ai_stream_send([
        'type' => 'done',
        'message' => 'Analisi IA completata.',
        'run_id' => $runId,
        'suggestions_count' => count(is_array($parsed['suggestions'] ?? null) ? $parsed['suggestions'] : []),
    ]);
} catch (Throwable $e) {
    ai_stream_send(['type' => 'error', 'message' => 'Errore applicativo durante analisi IA.']);
}

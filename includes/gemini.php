<?php
/**
 * Cliente ligero para la API de Google AI Studio (Gemini) vía REST + cURL.
 * No requiere SDK — una sola llamada HTTP.
 */

function gemini_generate(string $prompt, float $temperature = 0.6): ?string
{
    $cfg = MEGA_SECRETS['gemini'];
    if (empty($cfg['api_key'])) {
        return null;
    }

    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$cfg['model']}:generateContent";

    $payload = [
        'contents' => [
            ['parts' => [['text' => $prompt]]],
        ],
        'generationConfig' => [
            'temperature' => $temperature,
            'maxOutputTokens' => 700,
            // Los modelos "latest" de Gemini activan razonamiento extendido por defecto,
            // lo que puede agotar maxOutputTokens antes de emitir la respuesta final.
            'thinkingConfig' => ['thinkingBudget' => 0],
        ],
    ];

    // La capa gratuita de Gemini responde ocasionalmente 503 (alta demanda) o
    // se cuelga: reintentamos un par de veces con backoff corto antes de rendirnos.
    $maxAttempts = 3;
    for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-goog-api-key: ' . $cfg['api_key'],
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 15,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if (!$curlErr && $httpCode === 200) {
            $data = json_decode($response, true);
            $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
            if ($text !== null) {
                return $text;
            }
        }

        $retryable = $curlErr || in_array($httpCode, [429, 500, 503], true);
        error_log("Gemini intento $attempt/$maxAttempts falló (HTTP $httpCode): " . ($curlErr ?: $response));
        if (!$retryable || $attempt === $maxAttempts) {
            return null;
        }
        usleep(600000 * $attempt); // 0.6s, 1.2s...
    }

    return null;
}

/**
 * Analiza el riesgo de una obra con datos reales y devuelve un veredicto
 * estructurado: ['nivel' => bajo|medio|alto, 'analisis' => texto].
 */
function gemini_analizar_riesgo_obra(array $obra, array $partidas, array $reportesRecientes): array
{
    $partidasTxt = implode("\n", array_map(fn($p) => "- {$p['nombre']}: {$p['avance_pct']}%", $partidas));
    $diasSinReporte = $reportesRecientes ? floor((time() - strtotime($reportesRecientes[0]['created_at'])) / 86400) : 999;

    $prompt = "Eres el motor de IA predictiva de una plataforma de gestión de obras de construcción (BIM Coordination) en Perú.
Analiza esta obra y responde SOLO con este formato exacto, sin texto adicional:
NIVEL: [bajo|medio|alto]
ANALISIS: [1-2 frases en español, tono profesional y directo, con una recomendación accionable]

Datos de la obra:
- Nombre: {$obra['nombre']}
- Avance general: {$obra['avance_pct']}%
- Monto contratado: S/ {$obra['monto_contratado']}
- Monto ejecutado: S/ {$obra['monto_ejecutado']}
- Estado actual: {$obra['estado']}
- Días desde el último reporte de avance: {$diasSinReporte}
- Partidas:
{$partidasTxt}";

    $raw = gemini_generate($prompt, 0.4);
    if (!$raw) {
        return ['nivel' => $obra['riesgo_ia'] ?? 'bajo', 'analisis' => null];
    }

    $nivel = 'bajo';
    if (preg_match('/NIVEL:\s*(bajo|medio|alto)/i', $raw, $m)) {
        $nivel = strtolower($m[1]);
    }
    $analisis = null;
    if (preg_match('/ANALISIS:\s*(.+)/is', $raw, $m)) {
        $analisis = trim($m[1]);
    }

    return ['nivel' => $nivel, 'analisis' => $analisis ?: trim($raw)];
}

/** Respuesta del asistente (bot) de WhatsApp/portal cliente, con contexto real de la obra. */
function gemini_asistente_obra(array $obra, array $partidas, string $preguntaCliente): string
{
    $partidasTxt = implode("\n", array_map(fn($p) => "- {$p['nombre']}: {$p['avance_pct']}%", $partidas));

    $prompt = "Eres el asistente virtual de MegaEnsambler, una constructora. Respondes por WhatsApp a un cliente sobre el avance de SU obra.
Sé breve (máximo 3-4 líneas), cálido y usa como máximo 2 emojis relevantes (🏠📸⏱️✅⏳). Responde SOLO con base en estos datos reales, no inventes nada que no esté aquí.

Datos de la obra del cliente:
- Nombre: {$obra['nombre']}
- Avance general: {$obra['avance_pct']}%
- Fecha estimada de finalización: {$obra['fecha_fin_estimada']}
- Partidas:
{$partidasTxt}

Pregunta del cliente: \"{$preguntaCliente}\"

Tu respuesta:";

    $respuesta = gemini_generate($prompt, 0.7);
    return $respuesta ?: 'En este momento no puedo consultar el sistema de IA. Por favor intenta de nuevo en unos segundos.';
}

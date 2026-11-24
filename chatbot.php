<?php
/**
 * chatbot.php — bridge between the homepage chat widget and the AB STORE AI service
 * (https://github.com/benardkahayi/ab-store-ai, FastAPI: POST /api/support/chat).
 *
 * The browser only ever talks to this file, so the AI server address stays private
 * and there are no CORS issues. Configure the AI server URL in ai_config.php
 * (copy ai_config.example.php) or with the AB_STORE_AI_URL environment variable.
 *
 *   GET  chatbot.php?health=1                       -> {"online": true|false}
 *   POST chatbot.php {message, language, language_chosen?, reset?}
 *                                                   -> {reply, human_support_required, language, offline?}
 */
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

// ── Configuration ──
$config = ['url' => 'http://127.0.0.1:8000', 'timeout' => 60, 'api_key' => ''];
if (is_file(__DIR__ . '/ai_config.php')) {
    $file_config = require __DIR__ . '/ai_config.php';
    if (is_array($file_config)) $config = array_merge($config, $file_config);
}
if (getenv('AB_STORE_AI_URL')) $config['url'] = getenv('AB_STORE_AI_URL');
if (getenv('AB_STORE_AI_KEY')) $config['api_key'] = getenv('AB_STORE_AI_KEY');
$ai_base = rtrim((string)$config['url'], '/');
$timeout = max(5, (int)$config['timeout']);
$api_key = (string)$config['api_key'];

const CHAT_MAX_CHARS      = 2000;   // matches SupportRequest.question max_length
const CHAT_RATE_LIMIT     = 12;     // messages ...
const CHAT_RATE_WINDOW    = 60;     // ... per this many seconds, per visitor

$FALLBACK = [
    'en' => "Sorry, our AI assistant is unavailable right now. Please chat with the AB Store team on WhatsApp and we'll help you.",
    'sw' => "Samahani, msaidizi wetu wa AI hapatikani kwa sasa. Tafadhali wasiliana na timu ya AB Store kupitia WhatsApp tukusaidie.",
];

function respond(int $status, array $body): void {
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit;
}

/** Call the AI service. Returns [http_code, decoded_json_or_null, curl_error]. */
function ai_request(string $url, ?array $payload, int $timeout, string $api_key = ''): array {
    $ch = curl_init($url);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_HTTPHEADER     => ['Accept: application/json'],
    ];
    // Identifies this website to the AI server (must match API_KEY in the AI's .env).
    if ($api_key !== '') $opts[CURLOPT_HTTPHEADER][] = 'X-API-Key: ' . $api_key;
    if ($payload !== null) {
        $opts[CURLOPT_POST]       = true;
        $opts[CURLOPT_POSTFIELDS] = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $opts[CURLOPT_HTTPHEADER][] = 'Content-Type: application/json';
    }
    curl_setopt_array($ch, $opts);
    $raw  = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    return [$code, is_string($raw) ? json_decode($raw, true) : null, $err];
}

// ── Health check (used by the widget to show Online / Offline) ──
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    session_write_close();
    if (!isset($_GET['health'])) respond(405, ['error' => 'Method not allowed']);
    [$code] = ai_request($ai_base . '/health', null, 5);
    respond(200, ['online' => $code === 200]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['error' => 'Method not allowed']);
}

// ── Validate input ──
$data     = json_decode(file_get_contents('php://input'), true);
$data     = is_array($data) ? $data : [];
$message  = trim((string)($data['message'] ?? ''));
$language = strtolower(trim((string)($data['language'] ?? 'en')));
if (!in_array($language, ['en', 'sw'], true)) $language = 'en';
// Only force a language when the visitor picked one with the EN/SW switch;
// otherwise the AI detects English or Kiswahili from the message itself.
$language_chosen = !empty($data['language_chosen']);

// "New chat" — rotate the conversation id so the AI starts with fresh memory.
if (!empty($data['reset']) || empty($_SESSION['ai_chat_nonce'])) {
    $_SESSION['ai_chat_nonce'] = bin2hex(random_bytes(8));
}
if (!empty($data['reset']) && $message === '') {
    respond(200, ['ok' => true]);
}

if ($message === '') {
    respond(400, ['error' => 'Empty message']);
}
if (mb_strlen($message) > CHAT_MAX_CHARS) {
    respond(400, ['error' => 'Message is too long']);
}

// ── Simple per-visitor rate limit ──
$now  = time();
$hits = array_filter($_SESSION['ai_chat_hits'] ?? [], fn($t) => $t > $now - CHAT_RATE_WINDOW);
if (count($hits) >= CHAT_RATE_LIMIT) {
    respond(429, ['error' => 'Too many messages. Please wait a moment.']);
}
$hits[] = $now;
$_SESSION['ai_chat_hits'] = array_values($hits);

// Stable, non-guessable conversation id: the AI keeps chat memory per session_id.
$ai_session = 'web-' . substr(hash('sha256', session_id() . '|' . $_SESSION['ai_chat_nonce']), 0, 40);

// Release the session lock before the (possibly slow) AI call so other pages stay responsive.
session_write_close();

$payload = ['question' => $message, 'session_id' => $ai_session];
if ($language_chosen) $payload['language'] = $language;

[$code, $body, $err] = ai_request($ai_base . '/api/support/chat', $payload, $timeout, $api_key);

if ($code !== 200 || !is_array($body) || !isset($body['answer'])) {
    error_log(sprintf('[chatbot] AB STORE AI request failed: http=%d err=%s', $code, $err));
    respond(200, [
        'reply'                  => $FALLBACK[$language],
        'human_support_required' => true,
        'offline'                => true,
    ]);
}

respond(200, [
    'reply'                  => (string)$body['answer'],
    'human_support_required' => !empty($body['human_support_required']),
    'language'               => $body['language'] ?? $language,
]);

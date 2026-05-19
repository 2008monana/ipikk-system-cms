<?php
/**
 * Endpoint de tradução para o site público.
 * Recebe JSON: { texto, source, target }
 *
 * Estratégia de cache:
 * 1) sessão do utilizador (rápido)
 * 2) base de dados (partilhado por todos)
 * 3) API externa (Google Cloud / fallback MyMemory)
 */

require_once __DIR__ . '/../config/index.php';

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método inválido']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

$texto  = trim((string)($data['texto'] ?? ''));
$source = strtolower(trim((string)($data['source'] ?? 'pt')));
$target = strtolower(trim((string)($data['target'] ?? 'en')));

if ($texto === '') {
    echo json_encode(['success' => true, 'translatedText' => '']);
    exit;
}

if (!in_array($source, ['pt', 'en'], true) || !in_array($target, ['pt', 'en'], true)) {
    echo json_encode(['success' => false, 'message' => 'Idiomas não suportados']);
    exit;
}

if ($source === $target) {
    echo json_encode(['success' => true, 'translatedText' => $texto]);
    exit;
}

if (mb_strlen($texto) > 1200) {
    $texto = mb_substr($texto, 0, 1200);
}

$cacheKey = hash('sha256', $source . '|' . $target . '|' . $texto);
$cacheSessaoKey = 'traducao_' . $cacheKey;

if (!isset($_SESSION['traducao_cache'])) {
    $_SESSION['traducao_cache'] = [];
}

if (isset($_SESSION['traducao_cache'][$cacheSessaoKey])) {
    echo json_encode([
        'success' => true,
        'translatedText' => $_SESSION['traducao_cache'][$cacheSessaoKey],
        'cached' => true,
        'cache_layer' => 'session',
    ]);
    exit;
}

$db = getDB();

// Cache compartilhado em base de dados
$stmt = $db->prepare("SELECT texto_traduzido FROM traducoes_cache WHERE source_lang = ? AND target_lang = ? AND texto_hash = ? LIMIT 1");
$stmt->execute([$source, $target, $cacheKey]);
$cacheDb = $stmt->fetch();

if ($cacheDb && !empty($cacheDb['texto_traduzido'])) {
    $translatedText = (string)$cacheDb['texto_traduzido'];
    $_SESSION['traducao_cache'][$cacheSessaoKey] = $translatedText;
    echo json_encode([
        'success' => true,
        'translatedText' => $translatedText,
        'cached' => true,
        'cache_layer' => 'database',
    ]);
    exit;
}

$googleApiKey = getenv('GOOGLE_CLOUD_TRANSLATE_API_KEY') ?: getenv('GOOGLE_TRANSLATE_API_KEY') ?: '';
$translated = '';
$provider = '';

if ($googleApiKey !== '') {
    $provider = 'google';
    $url = 'https://translation.googleapis.com/language/translate/v2?key=' . urlencode($googleApiKey);

    $payload = json_encode([
        'q' => $texto,
        'source' => $source,
        'target' => $target,
        'format' => 'text',
    ], JSON_UNESCAPED_UNICODE);

    $ctx = stream_context_create([
        'http' => [
            'method' => 'POST',
            'timeout' => 12,
            'ignore_errors' => true,
            'header' => "Content-Type: application/json\r\n",
            'content' => $payload,
        ],
    ]);

    $resp = @file_get_contents($url, false, $ctx);
    if ($resp !== false) {
        $json = json_decode($resp, true);
        $translated = trim((string)($json['data']['translations'][0]['translatedText'] ?? ''));
    }
}

// Fallback para MyMemory quando Google não está configurado ou falhar
if ($translated === '') {
    $provider = 'mymemory';
    $url = 'https://api.mymemory.translated.net/get?q=' . urlencode($texto) . '&langpair=' . urlencode($source . '|' . $target);

    $ctx = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 10,
            'ignore_errors' => true,
            'header' => "User-Agent: IPIKK-CMS-Translator/1.0\r\n",
        ],
    ]);

    $resp = @file_get_contents($url, false, $ctx);
    if ($resp !== false) {
        $json = json_decode($resp, true);
        $translated = trim((string)($json['responseData']['translatedText'] ?? ''));
    }
}

if ($translated === '') {
    echo json_encode(['success' => false, 'message' => 'Falha ao traduzir conteúdo']);
    exit;
}

// Guardar em DB para reutilização global
$upsert = $db->prepare("INSERT INTO traducoes_cache (source_lang, target_lang, texto_hash, texto_original, texto_traduzido, provider)
VALUES (?, ?, ?, ?, ?, ?)
ON DUPLICATE KEY UPDATE texto_traduzido = VALUES(texto_traduzido), provider = VALUES(provider), updated_at = CURRENT_TIMESTAMP");
$upsert->execute([$source, $target, $cacheKey, $texto, $translated, $provider]);

// Guardar em sessão
$_SESSION['traducao_cache'][$cacheSessaoKey] = $translated;
if (count($_SESSION['traducao_cache']) > 300) {
    $_SESSION['traducao_cache'] = array_slice($_SESSION['traducao_cache'], -200, null, true);
}

echo json_encode([
    'success' => true,
    'translatedText' => $translated,
    'provider' => $provider,
    'cached' => false,
]);

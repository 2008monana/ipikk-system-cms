<?php
/**
 * Endpoint de tradução para o site público.
 * Recebe JSON: { texto, source, target }
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

// Evitar payloads abusivos
if (mb_strlen($texto) > 1200) {
    $texto = mb_substr($texto, 0, 1200);
}

$cacheKey = 'traducao_' . md5($source . '|' . $target . '|' . $texto);
if (!isset($_SESSION['traducao_cache'])) {
    $_SESSION['traducao_cache'] = [];
}

if (isset($_SESSION['traducao_cache'][$cacheKey])) {
    echo json_encode([
        'success' => true,
        'translatedText' => $_SESSION['traducao_cache'][$cacheKey],
        'cached' => true,
    ]);
    exit;
}

$url = 'https://api.mymemory.translated.net/get?q=' . urlencode($texto) . '&langpair=' . urlencode($source . '|' . $target);

$ctx = stream_context_create([
    'http' => [
        'method' => 'GET',
        'timeout' => 10,
        'ignore_errors' => true,
        'header' => "User-Agent: IPIKK-CMS-Translator/1.0\r\n",
    ],
]);

$response = @file_get_contents($url, false, $ctx);
if ($response === false) {
    echo json_encode(['success' => false, 'message' => 'Falha ao contactar serviço de tradução']);
    exit;
}

$json = json_decode($response, true);
$translated = trim((string)($json['responseData']['translatedText'] ?? ''));

if ($translated === '') {
    echo json_encode(['success' => false, 'message' => 'Serviço de tradução sem retorno']);
    exit;
}

$_SESSION['traducao_cache'][$cacheKey] = $translated;

// Limite simples do cache em sessão
if (count($_SESSION['traducao_cache']) > 300) {
    $_SESSION['traducao_cache'] = array_slice($_SESSION['traducao_cache'], -200, null, true);
}

echo json_encode(['success' => true, 'translatedText' => $translated]);

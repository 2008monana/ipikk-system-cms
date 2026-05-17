<?php
require_once '../config/index.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$acao = $input['acao'] ?? '';
$db = getDB();

if ($acao === 'subscrever') {
    $endpoint = trim((string)($input['endpoint'] ?? ''));
    $p256dh = trim((string)($input['p256dh'] ?? ''));
    $auth = trim((string)($input['auth'] ?? ''));
    if ($endpoint === '' || $p256dh === '' || $auth === '') {
        echo json_encode(['success' => false, 'message' => 'Dados de subscrição inválidos']); exit;
    }

    $stmt = $db->prepare("INSERT INTO push_subscricoes (endpoint, p256dh, auth, ativo, created_at, updated_at)
                          VALUES (?, ?, ?, 1, NOW(), NOW())
                          ON DUPLICATE KEY UPDATE p256dh = VALUES(p256dh), auth = VALUES(auth), ativo = 1, updated_at = NOW()");
    $ok = $stmt->execute([$endpoint, $p256dh, $auth]);
    echo json_encode(['success' => (bool)$ok]);
    exit;
}

if ($acao === 'cancelar') {
    $endpoint = trim((string)($input['endpoint'] ?? ''));
    if ($endpoint === '') { echo json_encode(['success' => false]); exit; }
    $stmt = $db->prepare("UPDATE push_subscricoes SET ativo = 0, updated_at = NOW() WHERE endpoint = ?");
    $ok = $stmt->execute([$endpoint]);
    echo json_encode(['success' => (bool)$ok]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Ação inválida']);
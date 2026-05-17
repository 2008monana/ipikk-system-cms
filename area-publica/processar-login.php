<?php
// processar-login.php

require_once '../config/index.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

$email = trim($input['email'] ?? '');
$senha = $input['senha'] ?? '';
$manter_conectado = $input['manter_conectado'] ?? false;

if (empty($email) || empty($senha)) {
    echo json_encode(['success' => false, 'message' => 'Preencha todos os campos']);
    exit;
}

$db = getDB();
$stmt = $db->prepare("SELECT id, nome, email, senha, nivel, permissoes, ativo FROM utilizadores WHERE email = ?");
$stmt->execute([$email]);
$utilizador = $stmt->fetch();

if (!$utilizador) {
    echo json_encode(['success' => false, 'message' => 'Credenciais inválidas']);
    exit;
}

if (!$utilizador['ativo']) {
    echo json_encode(['success' => false, 'message' => 'Conta desativada. Contacte o administrador']);
    exit;
}

if (!password_verify($senha, $utilizador['senha'])) {
    echo json_encode(['success' => false, 'message' => 'Credenciais inválidas']);
    exit;
}

if ($manter_conectado) {
    ini_set('session.cookie_lifetime', 30 * 24 * 3600);
    ini_set('session.gc_maxlifetime', 30 * 24 * 3600);
    session_set_cookie_params(30 * 24 * 3600);
}

// Decodificar as permissões
$permissoes = json_decode($utilizador['permissoes'] ?? '[]', true);
if (!is_array($permissoes)) {
    $permissoes = [];
}

// Se for admin, garantir que tem todas as permissões
if ($utilizador['nivel'] === 'admin') {
    $permissoes = ['dashboard', 'conteudo_site', 'oferta_formativa', 'noticias', 'galeria', 'inscricoes', 'contactos', 'utilizadores', 'configuracoes', 'logs', 'lixeira'];
}

// DEFINIR PÁGINA DE REDIRECIONAMENTO CONFORME PERMISSÕES
$redirect_url = '../area-restrita/admin-dashboard.php'; // padrão para admin

// Se NÃO for admin, redirecionar SEMPRE para o perfil
if ($utilizador['nivel'] !== 'admin') {
    $redirect_url = '../area-restrita/admin-perfil.php';
}

// Login bem-sucedido
$_SESSION['utilizador_id'] = $utilizador['id'];
$_SESSION['utilizador_nome'] = $utilizador['nome'];
$_SESSION['utilizador_email'] = $utilizador['email'];
$_SESSION['utilizador_nivel'] = $utilizador['nivel'];
$_SESSION['utilizador_permissoes'] = $permissoes;

// Atualizar último login
$stmt = $db->prepare("UPDATE utilizadores SET ultimo_login = NOW() WHERE id = ?");
$stmt->execute([$utilizador['id']]);

$stmt = $db->prepare("INSERT INTO logs (utilizador_id, acao, tabela, ip_address, user_agent) VALUES (?, 'login', 'utilizadores', ?, ?)");
$stmt->execute([$utilizador['id'], $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null]);

echo json_encode([
    'success' => true,
    'nome' => $utilizador['nome'],
    'redirect_url' => $redirect_url,
    'message' => 'Login realizado com sucesso'
]);
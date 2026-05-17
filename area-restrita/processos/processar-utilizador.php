<?php
/**
 * processar-utilizador.php
 */

// Desabilitar completamente qualquer output de erro
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Limpar buffers
if (ob_get_level()) ob_end_clean();
ob_start();

// Definir header JSON primeiro
header('Content-Type: application/json');

$base_path = dirname(__DIR__, 2);
require_once $base_path . '/config/index.php';

// Função para enviar resposta JSON
function enviarResposta($success, $message, $data = null) {
    global $db;
    ob_clean();
    $resposta = ['success' => $success, 'message' => $message];
    if ($data) {
        $resposta = array_merge($resposta, $data);
    }
    echo json_encode($resposta);
    exit;
}

// Verificar sessão
if (!isset($_SESSION['utilizador_id'])) {
    enviarResposta(false, 'Sessão não iniciada.');
}

// Verificar se é admin
$stmt = $db->prepare("SELECT nivel FROM utilizadores WHERE id = ?");
$stmt->execute([$_SESSION['utilizador_id']]);
$user = $stmt->fetch();
if (!$user || $user['nivel'] !== 'admin') {
    enviarResposta(false, 'Apenas administradores podem gerir utilizadores.');
}

$action = $_POST['action'] ?? '';

// ============================================
// UPLOAD FOTO
// ============================================
if ($action === 'upload_foto') {
    if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
        enviarResposta(false, 'Erro no upload da foto.');
    }
    
    $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (!in_array($ext, $allowed)) {
        enviarResposta(false, 'Formato não permitido.');
    }
    
    $upload = uploadArquivoNuvem($_FILES['foto'], 'utilizadores');
    if ($upload['success']) {
        enviarResposta(true, 'Foto carregada!', ['foto_url' => $upload['url']]);
    }
    enviarResposta(false, $upload['message']);
}

// ============================================
// SALVAR UTILIZADOR
// ============================================
if ($action === 'salvar_utilizador') {
    $id = isset($_POST['id']) && $_POST['id'] != '' ? (int)$_POST['id'] : null;
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $departamento = trim($_POST['departamento'] ?? '');
    $cargo = trim($_POST['cargo'] ?? '');
    $nivel = $_POST['nivel'] ?? 'editor';
    $avatar_icone = $_POST['avatar_icone'] ?? 'fa-user';
    $foto_url = $_POST['foto_url'] ?? 'foto/sem_foto.png';
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    $forcar_alteracao_senha = isset($_POST['forcar_alteracao_senha']) ? 1 : 0;
    $senha = $_POST['senha'] ?? '';
    $permissoes_raw = $_POST['permissoes'] ?? '[]';
    
    // Validar dados
    if (empty($nome)) {
        enviarResposta(false, 'Nome é obrigatório.');
    }
    if (empty($email)) {
        enviarResposta(false, 'Email é obrigatório.');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        enviarResposta(false, 'Email inválido.');
    }
    
    // Processar permissões
    $permissoes_array = [];
    if (is_string($permissoes_raw)) {
        $permissoes_array = json_decode($permissoes_raw, true);
    } elseif (is_array($permissoes_raw)) {
        $permissoes_array = $permissoes_raw;
    }
    if (!is_array($permissoes_array)) {
        $permissoes_array = [];
    }
    $permissoes_json = json_encode($permissoes_array);
    
    // Verificar email duplicado
    if ($id) {
        $stmt = $db->prepare("SELECT id FROM utilizadores WHERE email = ? AND id != ?");
        $stmt->execute([$email, $id]);
    } else {
        $stmt = $db->prepare("SELECT id FROM utilizadores WHERE email = ?");
        $stmt->execute([$email]);
    }
    
    if ($stmt->fetch()) {
        enviarResposta(false, 'Email já está em uso.');
    }
    
    try {
        if ($id) {
            // EDIÇÃO
            if (!empty($senha)) {
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE utilizadores SET 
                    nome = ?, email = ?, telefone = ?, departamento = ?, 
                    cargo = ?, nivel = ?, avatar_icone = ?, foto_url = ?, 
                    ativo = ?, forcar_alteracao_senha = ?, senha = ?, 
                    permissoes = ?, updated_at = NOW() 
                    WHERE id = ?");
                $success = $stmt->execute([
                    $nome, $email, $telefone, $departamento, $cargo, 
                    $nivel, $avatar_icone, $foto_url, $ativo, 
                    $forcar_alteracao_senha, $senha_hash, $permissoes_json, $id
                ]);
                $msg = $success ? 'Utilizador atualizado com sucesso! Senha alterada.' : 'Erro ao atualizar.';
            } else {
                $stmt = $db->prepare("UPDATE utilizadores SET 
                    nome = ?, email = ?, telefone = ?, departamento = ?, 
                    cargo = ?, nivel = ?, avatar_icone = ?, foto_url = ?, 
                    ativo = ?, forcar_alteracao_senha = ?, permissoes = ?, 
                    updated_at = NOW() 
                    WHERE id = ?");
                $success = $stmt->execute([
                    $nome, $email, $telefone, $departamento, $cargo, 
                    $nivel, $avatar_icone, $foto_url, $ativo, 
                    $forcar_alteracao_senha, $permissoes_json, $id
                ]);
                $msg = $success ? 'Utilizador atualizado com sucesso! Senha mantida.' : 'Erro ao atualizar.';
            }
            enviarResposta($success, $msg);
        } else {
            // CRIAÇÃO
            if (empty($senha)) {
                $senha = substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789'), 0, 10);
            }
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            
            $stmt = $db->prepare("INSERT INTO utilizadores 
                (nome, email, telefone, departamento, cargo, nivel, 
                 avatar_icone, foto_url, ativo, forcar_alteracao_senha, 
                 senha, permissoes, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
            $success = $stmt->execute([
                $nome, $email, $telefone, $departamento, $cargo, 
                $nivel, $avatar_icone, $foto_url, $ativo, 
                $forcar_alteracao_senha, $senha_hash, $permissoes_json
            ]);
            
            if ($success) {
                enviarResposta(true, 'Utilizador criado com sucesso! Senha: ' . $senha);
            } else {
                enviarResposta(false, 'Erro ao criar utilizador.');
            }
        }
    } catch (PDOException $e) {
        enviarResposta(false, 'Erro no banco de dados: ' . $e->getMessage());
    }
}

// ============================================
// ELIMINAR UTILIZADOR
// ============================================
if ($action === 'eliminar_utilizador') {
    $id = (int)($_POST['id'] ?? 0);
    
    if ($id <= 0 || $id == $_SESSION['utilizador_id']) {
        enviarResposta(false, 'Não é possível eliminar este utilizador.');
    }
    
    try {
        $stmt = $db->prepare("DELETE FROM utilizadores WHERE id = ?");
        $success = $stmt->execute([$id]);
        enviarResposta($success, $success ? 'Utilizador eliminado!' : 'Erro ao eliminar.');
    } catch (PDOException $e) {
        enviarResposta(false, 'Erro: ' . $e->getMessage());
    }
}

// ============================================
// REENVIAR CREDENCIAIS
// ============================================
if ($action === 'reenviar_credenciais') {
    $id = (int)($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        enviarResposta(false, 'ID inválido.');
    }
    
    try {
        $stmt = $db->prepare("SELECT nome, email FROM utilizadores WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            enviarResposta(false, 'Utilizador não encontrado.');
        }
        
        $nova_senha = substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789'), 0, 10);
        $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
        
        $stmt = $db->prepare("UPDATE utilizadores SET senha = ?, forcar_alteracao_senha = 1 WHERE id = ?");
        $stmt->execute([$senha_hash, $id]);
        
        enviarResposta(true, 'Credenciais geradas!', ['senha' => $nova_senha]);
    } catch (PDOException $e) {
        enviarResposta(false, 'Erro: ' . $e->getMessage());
    }
}

// ============================================
// FORCAR MUDANCA DE SENHA
// ============================================
if ($action === 'forcar_mudanca_senha') {
    $id = (int)($_POST['id'] ?? 0);
    
    try {
        $stmt = $db->prepare("UPDATE utilizadores SET forcar_alteracao_senha = 1 WHERE id = ?");
        $success = $stmt->execute([$id]);
        enviarResposta($success, $success ? 'Pedido enviado!' : 'Erro ao processar.');
    } catch (PDOException $e) {
        enviarResposta(false, 'Erro: ' . $e->getMessage());
    }
}

// Se chegou aqui, ação não reconhecida
enviarResposta(false, 'Ação não reconhecida: ' . $action);

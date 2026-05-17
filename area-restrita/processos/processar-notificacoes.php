<?php
/**
 * processar-notificacoes.php
 * Processa as operações AJAX para as notificações
 */

header('Content-Type: application/json');

$base_path = dirname(__DIR__, 2);
require_once $base_path . '/config/index.php';

if (!isset($_SESSION['utilizador_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sessão não iniciada.']);
    exit;
}

$db = getDB();
$action = $_POST['action'] ?? '';

// ============================================
// MARCAR NOTIFICAÇÃO COMO LIDA
// ============================================

if ($action === 'marcar_lida') {
    $id = (int)($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID inválido.']);
        exit;
    }
    
    // Verificar se a notificação pertence ao utilizador ou é global
    $stmt = $db->prepare("
        SELECT id FROM notificacoes 
        WHERE id = ? AND (para_utilizador_id IS NULL OR para_utilizador_id = ?)
    ");
    $stmt->execute([$id, $_SESSION['utilizador_id']]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Notificação não encontrada.']);
        exit;
    }
    
    $stmt = $db->prepare("UPDATE notificacoes SET lida = 1 WHERE id = ?");
    $success = $stmt->execute([$id]);
    
    echo json_encode(['success' => $success, 'message' => $success ? 'Notificação marcada como lida.' : 'Erro ao marcar notificação.']);
    exit;
}

// ============================================
// MARCAR TODAS COMO LIDAS
// ============================================

if ($action === 'marcar_todas_lidas') {
    $stmt = $db->prepare("
        UPDATE notificacoes 
        SET lida = 1 
        WHERE para_utilizador_id IS NULL OR para_utilizador_id = ?
    ");
    $success = $stmt->execute([$_SESSION['utilizador_id']]);
    
    echo json_encode(['success' => $success, 'message' => $success ? 'Todas notificações marcadas como lidas.' : 'Erro ao marcar notificações.']);
    exit;
}

// ============================================
// ELIMINAR NOTIFICAÇÃO
// ============================================

if ($action === 'eliminar_notificacao') {
    $id = (int)($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID inválido.']);
        exit;
    }
    
    // Verificar permissão (admin elimina qualquer, editor apenas as suas)
    if ($_SESSION['utilizador_nivel'] === 'admin') {
        $stmt = $db->prepare("DELETE FROM notificacoes WHERE id = ?");
        $success = $stmt->execute([$id]);
    } else {
        $stmt = $db->prepare("
            DELETE FROM notificacoes 
            WHERE id = ? AND (para_utilizador_id IS NULL OR para_utilizador_id = ?)
        ");
        $success = $stmt->execute([$id, $_SESSION['utilizador_id']]);
    }
    
    echo json_encode(['success' => $success, 'message' => $success ? 'Notificação eliminada.' : 'Erro ao eliminar notificação.']);
    exit;
}

// ============================================
// CRIAR NOTIFICAÇÃO (apenas admin)
// ============================================

if ($action === 'criar_notificacao') {
    if ($_SESSION['utilizador_nivel'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Permissão negada.']);
        exit;
    }
    
    $tipo = $_POST['tipo'] ?? 'sistema';
    $prioridade = $_POST['prioridade'] ?? 'media';
    $titulo = trim($_POST['titulo'] ?? '');
    $mensagem = trim($_POST['mensagem'] ?? '');
    $para_utilizador_id = !empty($_POST['para_utilizador_id']) ? (int)$_POST['para_utilizador_id'] : null;
    $acao_link = trim($_POST['acao_link'] ?? '');
    $referencia_tabela = trim($_POST['referencia_tabela'] ?? '');
    $referencia_id = !empty($_POST['referencia_id']) ? (int)$_POST['referencia_id'] : null;
    
    if (empty($titulo) || empty($mensagem)) {
        echo json_encode(['success' => false, 'message' => 'Título e mensagem são obrigatórios.']);
        exit;
    }
    
    $stmt = $db->prepare("
        INSERT INTO notificacoes (tipo, prioridade, titulo, mensagem, para_utilizador_id, acao_link, referencia_tabela, referencia_id, data_criacao) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $success = $stmt->execute([$tipo, $prioridade, $titulo, $mensagem, $para_utilizador_id, $acao_link, $referencia_tabela, $referencia_id]);
    
    echo json_encode(['success' => $success, 'message' => $success ? 'Notificação enviada com sucesso!' : 'Erro ao enviar notificação.']);
    exit;
}

// ============================================
// LISTAR NOTIFICAÇÕES (para dropdown do header)
// ============================================

if ($action === 'listar') {
    $limit = (int)($_GET['limit'] ?? 10);
    
    if ($_SESSION['utilizador_nivel'] === 'admin') {
        $stmt = $db->prepare("
            SELECT * FROM notificacoes 
            ORDER BY data_criacao DESC 
            LIMIT ?
        ");
        $stmt->execute([$limit]);
    } else {
        $stmt = $db->prepare("
            SELECT * FROM notificacoes 
            WHERE para_utilizador_id IS NULL OR para_utilizador_id = ? 
            ORDER BY data_criacao DESC 
            LIMIT ?
        ");
        $stmt->execute([$_SESSION['utilizador_id'], $limit]);
    }
    
    $notificacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $nao_lidas = 0;
    
    foreach ($notificacoes as $notif) {
        if ($notif['lida'] == 0) $nao_lidas++;
    }
    
    echo json_encode([
        'success' => true,
        'notificacoes' => $notificacoes,
        'nao_lidas' => $nao_lidas
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Ação não reconhecida.']);
?>
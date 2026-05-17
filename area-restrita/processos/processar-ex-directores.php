<?php
/**
 * processar-ex-directores.php
 * Processa todas as operações CRUD para os ex-directores
 */

header('Content-Type: application/json');

$base_path = dirname(__DIR__, 2);
require_once $base_path . '/config/index.php';

if (!isset($_SESSION['utilizador_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sessão não iniciada.']);
    exit;
}

$db = getDB();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ============================================
// SALVAR EX-DIRECTOR (CRIAR/EDITAR)
// ============================================

if ($action === 'salvar' || ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['foto']))) {
    $id = isset($_POST['id']) && $_POST['id'] != '' ? (int)$_POST['id'] : null;
    $nome = trim($_POST['nome'] ?? '');
    $cargo = trim($_POST['cargo'] ?? 'Director Geral');
    $periodo_inicio = trim($_POST['periodo_inicio'] ?? '');
    $periodo_fim = trim($_POST['periodo_fim'] ?? '');
    $biografia = trim($_POST['biografia'] ?? '');
    $ordem = (int)($_POST['ordem'] ?? 0);
    $ativo = 1;
    
    // Validações
    if (empty($nome)) {
        echo json_encode(['success' => false, 'message' => 'Nome é obrigatório.']);
        exit;
    }
    if (empty($periodo_inicio)) {
        echo json_encode(['success' => false, 'message' => 'Período de início é obrigatório.']);
        exit;
    }
    
    // Processar upload da foto
    $foto_url = null;
    $upload_dir = dirname(__DIR__) . '/../area-publica/uploads/ex-directores/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['foto'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (!in_array($ext, $allowed)) {
            echo json_encode(['success' => false, 'message' => 'Formato de imagem não permitido. Use JPG, PNG, GIF ou WEBP.']);
            exit;
        }
        
        if ($file['size'] > 5 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'Arquivo muito grande. Máximo 5MB.']);
            exit;
        }
        
        $upload = uploadArquivoNuvem($file, 'ex-directores');
        if ($upload['success']) {
            $foto_url = $upload['url'];
        } else {
            echo json_encode(['success' => false, 'message' => $upload['message']]);
            exit;
        }
    } elseif ($id) {
        // Manter foto existente
        $stmt = $db->prepare("SELECT foto_url FROM ex_diretores WHERE id = ?");
        $stmt->execute([$id]);
        $existente = $stmt->fetch();
        if ($existente) {
            $foto_url = $existente['foto_url'];
        }
    }
    
    // Se não há foto, usar padrão
    if (empty($foto_url)) {
        $foto_url = 'foto/sem_foto.png';
    }
    
    try {
        if ($id) {
            // Atualizar ex-director existente
            $stmt = $db->prepare("UPDATE ex_diretores SET 
                nome = ?, cargo = ?, periodo_inicio = ?, periodo_fim = ?, 
                biografia = ?, foto_url = ?, ordem = ?, ativo = ? 
                WHERE id = ?");
            $success = $stmt->execute([$nome, $cargo, $periodo_inicio, $periodo_fim, $biografia, $foto_url, $ordem, $ativo, $id]);
            $message = $success ? 'Ex-Director atualizado com sucesso!' : 'Erro ao atualizar ex-director.';
        } else {
            // Inserir novo ex-director
            $stmt = $db->prepare("INSERT INTO ex_diretores 
                (nome, cargo, periodo_inicio, periodo_fim, biografia, foto_url, ordem, ativo, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $success = $stmt->execute([$nome, $cargo, $periodo_inicio, $periodo_fim, $biografia, $foto_url, $ordem, $ativo]);
            $message = $success ? 'Ex-Director adicionado com sucesso!' : 'Erro ao adicionar ex-director.';
        }
        
        echo json_encode(['success' => $success, 'message' => $message]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
    }
    exit;
}

// ============================================
// BUSCAR EX-DIRECTOR PARA EDIÇÃO
// ============================================

if ($action === 'buscar') {
    $id = (int)($_GET['id'] ?? 0);
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID inválido.']);
        exit;
    }
    
    try {
        $stmt = $db->prepare("SELECT * FROM ex_diretores WHERE id = ?");
        $stmt->execute([$id]);
        $ex_director = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($ex_director) {
            echo json_encode(['success' => true, 'ex_director' => $ex_director]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Ex-Director não encontrado.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
    }
    exit;
}

// ============================================
// ELIMINAR EX-DIRECTOR
// ============================================

if ($action === 'eliminar') {
    $id = (int)($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID inválido.']);
        exit;
    }
    
    try {
        // Buscar foto para deletar se for local
        $stmt = $db->prepare("SELECT foto_url FROM ex_diretores WHERE id = ?");
        $stmt->execute([$id]);
        $ex_director = $stmt->fetch();
        
        if ($ex_director && $ex_director['foto_url'] && $ex_director['foto_url'] !== 'foto/sem_foto.png') {
            $caminho_foto = dirname(__DIR__) . '/../area-publica/' . $ex_director['foto_url'];
            if (file_exists($caminho_foto)) {
                unlink($caminho_foto);
            }
        }
        
        $stmt = $db->prepare("DELETE FROM ex_diretores WHERE id = ?");
        $success = $stmt->execute([$id]);
        
        echo json_encode(['success' => $success, 'message' => $success ? 'Ex-Director eliminado com sucesso!' : 'Erro ao eliminar ex-director.']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
    }
    exit;
}

// ============================================
// LISTAR EX-DIRECTORES
// ============================================

if ($action === 'listar') {
    try {
        $stmt = $db->query("SELECT * FROM ex_diretores WHERE ativo = 1 ORDER BY ordem, periodo_inicio DESC");
        $ex_diretores = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'ex_diretores' => $ex_diretores]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro ao listar: ' . $e->getMessage()]);
    }
    exit;
}

// ============================================
// ATUALIZAR ORDEM
// ============================================

if ($action === 'ordenar') {
    $input = json_decode(file_get_contents('php://input'), true);
    $ordem = $input['ordem'] ?? [];
    
    if (empty($ordem)) {
        echo json_encode(['success' => false, 'message' => 'Nenhuma ordem fornecida.']);
        exit;
    }
    
    try {
        $db->beginTransaction();
        $stmt = $db->prepare("UPDATE ex_diretores SET ordem = ? WHERE id = ?");
        
        foreach ($ordem as $item) {
            $stmt->execute([$item['ordem'], $item['id']]);
        }
        
        $db->commit();
        echo json_encode(['success' => true, 'message' => 'Ordem atualizada com sucesso!']);
    } catch (PDOException $e) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar ordem: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Ação não reconhecida: ' . $action]);
?>

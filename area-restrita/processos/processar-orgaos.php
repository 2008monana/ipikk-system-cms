<?php
/**
 * processar-orgaos.php
 * Processa todas as operações CRUD para os membros da equipa (órgãos directivos)
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
// SALVAR MEMBRO (CRIAR/EDITAR)
// ============================================

if ($action === 'salvar' || ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['foto']))) {
    $id = isset($_POST['id']) && $_POST['id'] != '' ? (int)$_POST['id'] : null;
    $nome = trim($_POST['nome'] ?? '');
    $cargo = trim($_POST['cargo'] ?? '');
    $categoria = $_POST['categoria'] ?? 'outros';
    $tipo_card = $_POST['tipo_card'] ?? 'pequeno';
    $ordem = (int)($_POST['ordem'] ?? 0);
    $ativo = 1;
    
    // Validações
    if (empty($nome)) {
        echo json_encode(['success' => false, 'message' => 'Nome é obrigatório.']);
        exit;
    }
    if (empty($cargo)) {
        echo json_encode(['success' => false, 'message' => 'Cargo é obrigatório.']);
        exit;
    }
    
    // Validar categoria
    $categorias_validas = ['direcao_executiva', 'coordenador_curso', 'coordenador_disciplina', 'chefe_area', 'outros'];
    if (!in_array($categoria, $categorias_validas)) {
        $categoria = 'outros';
    }
    
    // Processar upload da foto
    $foto_url = null;
    $upload_dir = dirname(__DIR__) . '/../area-publica/uploads/equipe/';
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
        
        $upload = uploadArquivoNuvem($file, 'equipe');
        if ($upload['success']) {
            $foto_url = $upload['url'];
        } else {
            echo json_encode(['success' => false, 'message' => $upload['message']]);
            exit;
        }
    } elseif ($id) {
        // Manter foto existente
        $stmt = $db->prepare("SELECT foto_url FROM equipe WHERE id = ?");
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
            // Atualizar membro existente
            $stmt = $db->prepare("UPDATE equipe SET 
                nome = ?, cargo = ?, categoria = ?, tipo_card = ?, 
                foto_url = ?, ordem = ?, ativo = ? 
                WHERE id = ?");
            $success = $stmt->execute([$nome, $cargo, $categoria, $tipo_card, $foto_url, $ordem, $ativo, $id]);
            $message = $success ? 'Membro atualizado com sucesso!' : 'Erro ao atualizar membro.';
        } else {
            // Inserir novo membro
            $stmt = $db->prepare("INSERT INTO equipe 
                (nome, cargo, categoria, tipo_card, foto_url, ordem, ativo, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            $success = $stmt->execute([$nome, $cargo, $categoria, $tipo_card, $foto_url, $ordem, $ativo]);
            $message = $success ? 'Membro adicionado com sucesso!' : 'Erro ao adicionar membro.';
        }
        
        echo json_encode(['success' => $success, 'message' => $message]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
    }
    exit;
}

// ============================================
// BUSCAR MEMBRO PARA EDIÇÃO
// ============================================

if ($action === 'buscar') {
    $id = (int)($_GET['id'] ?? 0);
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID inválido.']);
        exit;
    }
    
    try {
        $stmt = $db->prepare("SELECT * FROM equipe WHERE id = ?");
        $stmt->execute([$id]);
        $membro = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($membro) {
            echo json_encode(['success' => true, 'membro' => $membro]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Membro não encontrado.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
    }
    exit;
}

// ============================================
// ELIMINAR MEMBRO
// ============================================

if ($action === 'eliminar') {
    $id = (int)($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID inválido.']);
        exit;
    }
    
    try {
        // Buscar foto para deletar se for local
        $stmt = $db->prepare("SELECT foto_url FROM equipe WHERE id = ?");
        $stmt->execute([$id]);
        $membro = $stmt->fetch();
        
        if ($membro && $membro['foto_url'] && $membro['foto_url'] !== 'foto/sem_foto.png') {
            $caminho_foto = dirname(__DIR__) . '/../area-publica/' . $membro['foto_url'];
            if (file_exists($caminho_foto)) {
                unlink($caminho_foto);
            }
        }
        
        $stmt = $db->prepare("DELETE FROM equipe WHERE id = ?");
        $success = $stmt->execute([$id]);
        
        echo json_encode(['success' => $success, 'message' => $success ? 'Membro eliminado com sucesso!' : 'Erro ao eliminar membro.']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
    }
    exit;
}

// ============================================
// LISTAR MEMBROS POR CATEGORIA
// ============================================

if ($action === 'listar') {
    $categoria = $_GET['categoria'] ?? '';
    
    $sql = "SELECT * FROM equipe WHERE 1=1";
    $params = [];
    
    if (!empty($categoria)) {
        $sql .= " AND categoria = ?";
        $params[] = $categoria;
    }
    
    $sql .= " ORDER BY ordem, id";
    
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $membros = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'membros' => $membros]);
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
        $stmt = $db->prepare("UPDATE equipe SET ordem = ? WHERE id = ?");
        
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

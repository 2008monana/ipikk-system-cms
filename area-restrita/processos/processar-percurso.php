<?php
/**
 * processar-percurso.php
 * Processa todas as operações CRUD para os alumni (histórias de sucesso)
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
// SALVAR ALUMNI (CRIAR/EDITAR)
// ============================================

if ($action === 'salvar' || ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['foto']))) {
    $id = isset($_POST['id']) && $_POST['id'] != '' ? (int)$_POST['id'] : null;
    $nome = trim($_POST['nome'] ?? '');
    $curso_id = (int)($_POST['curso_id'] ?? 0);
    $ano_conclusao = trim($_POST['ano_conclusao'] ?? '');
    $empresa = trim($_POST['empresa'] ?? '');
    $cargo_atual = trim($_POST['cargo_atual'] ?? '');
    $percurso_texto = trim($_POST['percurso_texto'] ?? '');
    $destaque = isset($_POST['destaque']) ? (int)$_POST['destaque'] : 0;
    $ordem = (int)($_POST['ordem'] ?? 0);
    $ativo = 1;
    
    // Validações
    if (empty($nome)) {
        echo json_encode(['success' => false, 'message' => 'Nome é obrigatório.']);
        exit;
    }
    if ($curso_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Selecione um curso.']);
        exit;
    }
    if (empty($ano_conclusao)) {
        echo json_encode(['success' => false, 'message' => 'Ano de conclusão é obrigatório.']);
        exit;
    }
    if (empty($empresa)) {
        echo json_encode(['success' => false, 'message' => 'Empresa é obrigatória.']);
        exit;
    }
    if (empty($percurso_texto)) {
        echo json_encode(['success' => false, 'message' => 'Percurso profissional é obrigatório.']);
        exit;
    }
    
    // Buscar nome do curso para exibição (opcional)
    $stmt = $db->prepare("SELECT nome FROM cursos WHERE id = ?");
    $stmt->execute([$curso_id]);
    $curso = $stmt->fetch();
    $curso_nome = $curso ? $curso['nome'] : '';
    
    // Processar upload da foto
    $foto_url = null;
        
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['foto'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (!in_array($ext, $allowed)) {
            echo json_encode(['success' => false, 'message' => 'Formato de imagem não permitido.']);
            exit;
        }
        
        if ($file['size'] > 5 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'Arquivo muito grande. Máximo 5MB.']);
            exit;
        }
        
        $upload = uploadArquivoNuvem($file, 'alumni');
        if ($upload['success']) {
            $foto_url = $upload['url'];
        } else {
            echo json_encode(['success' => false, 'message' => $upload['message']]);
            exit;
        }
    } elseif ($id) {
        // Manter foto existente
        $stmt = $db->prepare("SELECT foto_url FROM alumni WHERE id = ?");
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
            // Atualizar alumni existente (usando curso_id)
            $stmt = $db->prepare("UPDATE alumni SET 
                nome = ?, curso_id = ?, ano_conclusao = ?, 
                empresa = ?, cargo_atual = ?, percurso_texto = ?, 
                destaque = ?, foto_url = ?, ordem = ?, ativo = ? 
                WHERE id = ?");
            $success = $stmt->execute([
                $nome, $curso_id, $ano_conclusao,
                $empresa, $cargo_atual, $percurso_texto,
                $destaque, $foto_url, $ordem, $ativo, $id
            ]);
            $message = $success ? 'Alumni atualizado com sucesso!' : 'Erro ao atualizar alumni.';
        } else {
            // Inserir novo alumni (usando curso_id)
            $stmt = $db->prepare("INSERT INTO alumni 
                (nome, curso_id, ano_conclusao, empresa, cargo_atual, 
                 percurso_texto, destaque, foto_url, ordem, ativo, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $success = $stmt->execute([
                $nome, $curso_id, $ano_conclusao,
                $empresa, $cargo_atual, $percurso_texto,
                $destaque, $foto_url, $ordem, $ativo
            ]);
            $message = $success ? 'Alumni adicionado com sucesso!' : 'Erro ao adicionar alumni.';
        }
        
        echo json_encode(['success' => $success, 'message' => $message]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
    }
    exit;
}

// ============================================
// BUSCAR ALUMNI PARA EDIÇÃO
// ============================================

if ($action === 'buscar') {
    $id = (int)($_GET['id'] ?? 0);
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID inválido.']);
        exit;
    }
    
    try {
        // Buscar incluindo o nome do curso via JOIN
        $stmt = $db->prepare("
            SELECT a.*, c.nome as curso_nome 
            FROM alumni a
            LEFT JOIN cursos c ON a.curso_id = c.id
            WHERE a.id = ?
        ");
        $stmt->execute([$id]);
        $alumni = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($alumni) {
            echo json_encode(['success' => true, 'alumni' => $alumni]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Alumni não encontrado.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
    }
    exit;
}

// ============================================
// ELIMINAR ALUMNI
// ============================================

if ($action === 'eliminar') {
    $id = (int)($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID inválido.']);
        exit;
    }
    
    try {
        // Buscar foto para deletar se for local
        $stmt = $db->prepare("SELECT foto_url FROM alumni WHERE id = ?");
        $stmt->execute([$id]);
        $alumni = $stmt->fetch();
        
        if ($alumni && $alumni['foto_url'] && $alumni['foto_url'] !== 'foto/sem_foto.png') {
            $caminho_foto = dirname(__DIR__) . '/../area-publica/' . $alumni['foto_url'];
            if (file_exists($caminho_foto)) {
                unlink($caminho_foto);
            }
        }
        
        $stmt = $db->prepare("DELETE FROM alumni WHERE id = ?");
        $success = $stmt->execute([$id]);
        
        echo json_encode(['success' => $success, 'message' => $success ? 'Alumni eliminado com sucesso!' : 'Erro ao eliminar alumni.']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
    }
    exit;
}

// ============================================
// LISTAR ALUMNI
// ============================================

if ($action === 'listar') {
    $destaque = $_GET['destaque'] ?? '';
    $curso_id = (int)($_GET['curso_id'] ?? 0);
    
    $sql = "SELECT a.*, c.nome as curso_nome FROM alumni a LEFT JOIN cursos c ON a.curso_id = c.id WHERE a.ativo = 1";
    $params = [];
    
    if ($destaque !== '') {
        $sql .= " AND a.destaque = ?";
        $params[] = (int)$destaque;
    }
    if ($curso_id > 0) {
        $sql .= " AND a.curso_id = ?";
        $params[] = $curso_id;
    }
    
    $sql .= " ORDER BY a.destaque DESC, a.ordem, a.ano_conclusao DESC";
    
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $alumni = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'alumni' => $alumni]);
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
        $stmt = $db->prepare("UPDATE alumni SET ordem = ? WHERE id = ?");
        
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

<?php
/**
 * processar-depoimentos.php
 * Processa todas as operações CRUD para depoimentos
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

function garantirEstruturaDepoimentos($db) {
    static $ok = false;
    if ($ok) return;
    $db->exec("ALTER TABLE depoimentos ADD COLUMN IF NOT EXISTS tipo_depoimento ENUM('atual','ex_aluno') NOT NULL DEFAULT 'ex_aluno' AFTER curso_id");
    $db->exec("ALTER TABLE depoimentos ADD COLUMN IF NOT EXISTS ano_atual VARCHAR(20) NULL AFTER turma");
    $ok = true;
}

// ============================================
// SALVAR DEPOIMENTO (CRIAR/EDITAR)
// ============================================

if ($action === 'salvar' || $action === 'editar' || ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['foto']))) {
    garantirEstruturaDepoimentos($db);
    $id = isset($_POST['id']) && $_POST['id'] != '' ? (int)$_POST['id'] : null;
    $nome = trim($_POST['nome'] ?? '');
    $curso_id = (int)($_POST['curso_id'] ?? 0);
    $turma = trim($_POST['turma'] ?? '');
    $ano_atual = trim($_POST['ano_atual'] ?? '');
    $empresa = trim($_POST['empresa'] ?? '');
    $tipo_depoimento = ($_POST['tipo_depoimento'] ?? 'ex_aluno') === 'atual' ? 'atual' : 'ex_aluno';
    $texto = trim($_POST['texto'] ?? '');
    $destaque = isset($_POST['destaque']) ? (int)$_POST['destaque'] : 0;
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
    if (empty($turma)) {
        echo json_encode(['success' => false, 'message' => $tipo_depoimento === 'atual' ? 'Classe atual é obrigatória.' : 'Ano de conclusão é obrigatório.']);
        exit;
    }
    if ($tipo_depoimento === 'atual' && empty($ano_atual)) {
        echo json_encode(['success' => false, 'message' => 'Ano atual é obrigatório para aluno atual.']);
        exit;
    }
    if ($tipo_depoimento === 'ex_aluno') $ano_atual = null;
    if ($tipo_depoimento === 'ex_aluno' && empty($empresa)) {
        echo json_encode(['success' => false, 'message' => 'Empresa é obrigatória para ex-aluno.']);
        exit;
    }
    if ($tipo_depoimento === 'atual') $empresa = 'Estudante';
    if (empty($texto)) {
        echo json_encode(['success' => false, 'message' => 'Texto do depoimento é obrigatório.']);
        exit;
    }
    
    // Buscar nome do curso
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
        
        $upload = uploadArquivoNuvem($file, 'depoimentos');
        if ($upload['success']) {
            $foto_url = $upload['url'];
        } else {
            echo json_encode(['success' => false, 'message' => $upload['message']]);
            exit;
        }
    } elseif ($id) {
        // Manter foto existente
        $stmt = $db->prepare("SELECT foto_url FROM depoimentos WHERE id = ?");
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
    
    // Buscar a maior ordem atual
    $stmt = $db->prepare("SELECT MAX(ordem) as max_ordem FROM depoimentos");
    $stmt->execute();
    $max_ordem = $stmt->fetch()['max_ordem'] ?? 0;
    $ordem = $max_ordem + 1;
    
    try {
        if ($id) {
            // Atualizar depoimento existente
            $stmt = $db->prepare("UPDATE depoimentos SET 
                nome = ?, curso_id = ?, curso_nome = ?, turma = ?, ano_atual = ?, 
                empresa = ?, tipo_depoimento = ?, texto = ?, destaque = ?, foto_url = ?, 
                updated_at = NOW() 
                WHERE id = ?");
            $success = $stmt->execute([$nome, $curso_id, $curso_nome, $turma, $ano_atual, $empresa, $tipo_depoimento, $texto, $destaque, $foto_url, $id]);
            $message = $success ? 'Depoimento atualizado com sucesso!' : 'Erro ao atualizar depoimento.';
        } else {
            // Inserir novo depoimento
            $stmt = $db->prepare("INSERT INTO depoimentos 
                (nome, curso_id, tipo_depoimento, curso_nome, turma, ano_atual, empresa, texto, destaque, 
                 foto_url, ordem, ativo, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
            $success = $stmt->execute([$nome, $curso_id, $tipo_depoimento, $curso_nome, $turma, $ano_atual, $empresa, $texto, $destaque, $foto_url, $ordem, $ativo]);
            $message = $success ? 'Depoimento adicionado com sucesso!' : 'Erro ao adicionar depoimento.';
        }
        
        echo json_encode(['success' => $success, 'message' => $message]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
    }
    exit;
}

// ============================================
// ELIMINAR DEPOIMENTO
// ============================================

if ($action === 'eliminar') {
    $id = (int)($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID inválido.']);
        exit;
    }
    
    try {
        // Buscar foto para deletar se for local
        $stmt = $db->prepare("SELECT foto_url FROM depoimentos WHERE id = ?");
        $stmt->execute([$id]);
        $depoimento = $stmt->fetch();
        
        if ($depoimento && $depoimento['foto_url'] && $depoimento['foto_url'] !== 'foto/sem_foto.png') {
            $caminho_foto = dirname(__DIR__) . '/../area-publica/' . $depoimento['foto_url'];
            if (file_exists($caminho_foto)) {
                unlink($caminho_foto);
            }
        }
        
        $stmt = $db->prepare("DELETE FROM depoimentos WHERE id = ?");
        $success = $stmt->execute([$id]);
        
        echo json_encode(['success' => $success, 'message' => $success ? 'Depoimento eliminado com sucesso!' : 'Erro ao eliminar depoimento.']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Ação não reconhecida: ' . $action]);
?>

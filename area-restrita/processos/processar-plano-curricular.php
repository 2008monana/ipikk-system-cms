<?php
/**
 * processar-plano-curricular.php
 * Processa as operações CRUD para o plano curricular
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
// LISTAR DISCIPLINAS
// ============================================

if ($action === 'listar') {
    $curso_id = (int)($_GET['curso_id'] ?? 0);
    
    if ($curso_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Curso inválido.']);
        exit;
    }
    
    try {
        $stmt = $db->prepare("
            SELECT * FROM planos_curriculares 
            WHERE curso_id = ? AND ativo = 1 
            ORDER BY ordem, id
        ");
        $stmt->execute([$curso_id]);
        $disciplinas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'disciplinas' => $disciplinas]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro ao listar: ' . $e->getMessage()]);
    }
    exit;
}

// ============================================
// SALVAR DISCIPLINA (CRIAR/EDITAR)
// ============================================

if ($action === 'salvar' || $action === 'editar') {
    $id = isset($_POST['id']) && $_POST['id'] != '' ? (int)$_POST['id'] : null;
    $curso_id = (int)($_POST['curso_id'] ?? 0);
    $disciplina = trim($_POST['disciplina'] ?? '');
    $componente = $_POST['componente'] ?? 'tecnica';
    $ordem = (int)($_POST['ordem'] ?? 0);
    $ativo = isset($_POST['ativo']) ? (int)$_POST['ativo'] : 1;
    
    $horas_10a = (int)($_POST['horas_10a'] ?? 0);
    $horas_11a = (int)($_POST['horas_11a'] ?? 0);
    $horas_12a = (int)($_POST['horas_12a'] ?? 0);
    $horas_13a = (int)($_POST['horas_13a'] ?? 0);
    
    if ($curso_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Curso inválido.']);
        exit;
    }
    
    if (empty($disciplina)) {
        echo json_encode(['success' => false, 'message' => 'Nome da disciplina é obrigatório.']);
        exit;
    }
    
    try {
        if ($id) {
            // Atualizar disciplina existente
            $stmt = $db->prepare("
                UPDATE planos_curriculares SET 
                    disciplina = ?, componente = ?, ordem = ?, ativo = ?,
                    horas_10a = ?, horas_11a = ?, horas_12a = ?, horas_13a = ?
                WHERE id = ?
            ");
            $success = $stmt->execute([
                $disciplina, $componente, $ordem, $ativo,
                $horas_10a, $horas_11a, $horas_12a, $horas_13a, $id
            ]);
            $message = $success ? 'Disciplina atualizada com sucesso!' : 'Erro ao atualizar disciplina.';
        } else {
            // Verificar duplicata
            $stmt = $db->prepare("
                SELECT id FROM planos_curriculares 
                WHERE curso_id = ? AND disciplina = ?
            ");
            $stmt->execute([$curso_id, $disciplina]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Esta disciplina já existe para este curso.']);
                exit;
            }
            
            // Inserir nova disciplina
            $stmt = $db->prepare("
                INSERT INTO planos_curriculares 
                (curso_id, disciplina, componente, ordem, ativo, 
                 horas_10a, horas_11a, horas_12a, horas_13a, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $success = $stmt->execute([
                $curso_id, $disciplina, $componente, $ordem, $ativo,
                $horas_10a, $horas_11a, $horas_12a, $horas_13a
            ]);
            $message = $success ? 'Disciplina adicionada com sucesso!' : 'Erro ao adicionar disciplina.';
        }
        
        echo json_encode(['success' => $success, 'message' => $message]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
    }
    exit;
}

// ============================================
// ELIMINAR DISCIPLINA
// ============================================

if ($action === 'eliminar') {
    $id = (int)($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID inválido.']);
        exit;
    }
    
    try {
        $stmt = $db->prepare("DELETE FROM planos_curriculares WHERE id = ?");
        $success = $stmt->execute([$id]);
        
        echo json_encode(['success' => $success, 'message' => $success ? 'Disciplina eliminada com sucesso!' : 'Erro ao eliminar disciplina.']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
    }
    exit;
}

// ============================================
// BUSCAR DISCIPLINA PARA EDIÇÃO
// ============================================

if ($action === 'buscar') {
    $id = (int)($_GET['id'] ?? 0);
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID inválido.']);
        exit;
    }
    
    try {
        $stmt = $db->prepare("SELECT * FROM planos_curriculares WHERE id = ?");
        $stmt->execute([$id]);
        $disciplina = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($disciplina) {
            echo json_encode(['success' => true, 'disciplina' => $disciplina]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Disciplina não encontrada.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Ação não reconhecida: ' . $action]);
?>
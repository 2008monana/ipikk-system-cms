<?php
/**
 * processar-escolas-afiliadas.php
 * Processa todas as operações CRUD para as escolas afiliadas
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
// SALVAR ESCOLA (CRIAR/EDITAR)
// ============================================

if ($action === 'salvar') {
    $id = isset($_POST['id']) && $_POST['id'] != '' ? (int)$_POST['id'] : null;
    $nome = trim($_POST['nome'] ?? '');
    $tipo = trim($_POST['tipo'] ?? 'Privado');
    $email = trim($_POST['email'] ?? '');
    $telefone1 = trim($_POST['telefone1'] ?? '');
    $telefone2 = trim($_POST['telefone2'] ?? '');
    $site_url = trim($_POST['site_url'] ?? '');
    $endereco = trim($_POST['endereco'] ?? '');
    $ativo = 1;
    
    if (empty($nome)) {
        echo json_encode(['success' => false, 'message' => 'Nome da escola é obrigatório.']);
        exit;
    }
    
    // Processar upload do logotipo
    $logo_url = null;
        
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['logo'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
        
        if (!in_array($ext, $allowed)) {
            echo json_encode(['success' => false, 'message' => 'Formato de imagem não permitido.']);
            exit;
        }
        
        if ($file['size'] > 5 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'Arquivo muito grande. Máximo 5MB.']);
            exit;
        }
        
        $upload = uploadArquivoNuvem($file, 'escolas');
        if ($upload['success']) {
            $logo_url = $upload['url'];
        } else {
            echo json_encode(['success' => false, 'message' => $upload['message']]);
            exit;
        }
    } elseif ($id) {
        // Manter logotipo existente
        $stmt = $db->prepare("SELECT logo_url FROM escolas_afiliadas WHERE id = ?");
        $stmt->execute([$id]);
        $existente = $stmt->fetch();
        if ($existente) {
            $logo_url = $existente['logo_url'];
        }
    }
    
    // Se não há logo, usar padrão
    if (empty($logo_url)) {
        $logo_url = 'foto/sem_logo.png';
    }
    
    // Buscar a maior ordem atual
    $stmt = $db->prepare("SELECT MAX(ordem) as max_ordem FROM escolas_afiliadas");
    $stmt->execute();
    $max_ordem = $stmt->fetch()['max_ordem'] ?? 0;
    $ordem = $max_ordem + 1;
    
    try {
        if ($id) {
            // Atualizar escola existente (manter ordem)
            $stmt = $db->prepare("UPDATE escolas_afiliadas SET 
                nome = ?, tipo = ?, email = ?, telefone1 = ?, telefone2 = ?, 
                site_url = ?, endereco = ?, logo_url = ?, ativo = ? 
                WHERE id = ?");
            $success = $stmt->execute([$nome, $tipo, $email, $telefone1, $telefone2, $site_url, $endereco, $logo_url, $ativo, $id]);
            $message = $success ? 'Escola atualizada com sucesso!' : 'Erro ao atualizar escola.';
        } else {
            // Inserir nova escola
            $stmt = $db->prepare("INSERT INTO escolas_afiliadas 
                (nome, tipo, email, telefone1, telefone2, site_url, endereco, logo_url, ordem, ativo, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $success = $stmt->execute([$nome, $tipo, $email, $telefone1, $telefone2, $site_url, $endereco, $logo_url, $ordem, $ativo]);
            $message = $success ? 'Escola adicionada com sucesso!' : 'Erro ao adicionar escola.';
        }
        
        echo json_encode(['success' => $success, 'message' => $message]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
    }
    exit;
}

// ============================================
// ELIMINAR ESCOLA
// ============================================

if ($action === 'eliminar') {
    $id = (int)($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID inválido.']);
        exit;
    }
    
    try {
        // Buscar logotipo para deletar se for local
        $stmt = $db->prepare("SELECT logo_url FROM escolas_afiliadas WHERE id = ?");
        $stmt->execute([$id]);
        $escola = $stmt->fetch();
        
        if ($escola && $escola['logo_url'] && $escola['logo_url'] !== 'foto/sem_logo.png') {
            $caminho_logo = dirname(__DIR__) . '/../area-publica/' . $escola['logo_url'];
            if (file_exists($caminho_logo)) {
                unlink($caminho_logo);
            }
        }
        
        $stmt = $db->prepare("DELETE FROM escolas_afiliadas WHERE id = ?");
        $success = $stmt->execute([$id]);
        
        echo json_encode(['success' => $success, 'message' => $success ? 'Escola eliminada com sucesso!' : 'Erro ao eliminar escola.']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
    }
    exit;
}

// ============================================
// SALVAR CONFIGURAÇÕES GERAIS
// ============================================

if ($action === 'salvar_geral') {
    $titulo = trim($_POST['titulo'] ?? 'Escolas Afiliadas');
    $subtitulo = trim($_POST['subtitulo'] ?? '');
    
    try {
        $conteudo = [
            'titulo' => $titulo,
            'subtitulo' => $subtitulo
        ];
        $conteudo_json = json_encode($conteudo, JSON_UNESCAPED_UNICODE);
        
        $stmt = $db->prepare("SELECT id FROM conteudo_paginas WHERE slug = 'escolas-afiliadas'");
        $stmt->execute();
        
        if ($stmt->fetch()) {
            $stmt = $db->prepare("UPDATE conteudo_paginas SET 
                conteudo = ?, 
                ultima_edicao_por = ?, 
                ultima_edicao_em = NOW(),
                status = 'publicado'
                WHERE slug = 'escolas-afiliadas'");
            $success = $stmt->execute([$conteudo_json, $_SESSION['utilizador_id']]);
        } else {
            $stmt = $db->prepare("INSERT INTO conteudo_paginas 
                (slug, titulo, conteudo, status, ultima_edicao_por, ultima_edicao_em, created_at) 
                VALUES ('escolas-afiliadas', 'Escolas Afiliadas', ?, 'publicado', ?, NOW(), NOW())");
            $success = $stmt->execute([$conteudo_json, $_SESSION['utilizador_id']]);
        }
        
        echo json_encode(['success' => $success, 'message' => $success ? 'Configurações guardadas com sucesso!' : 'Erro ao guardar configurações.']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
    }
    exit;
}

// ============================================
// ORDENAR ESCOLAS
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
        $stmt = $db->prepare("UPDATE escolas_afiliadas SET ordem = ? WHERE id = ?");
        
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

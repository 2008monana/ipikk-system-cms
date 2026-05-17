<?php
/**
 * processar-normativos.php
 * Processa todas as operações CRUD para os documentos normativos
 */

error_reporting(0);
ini_set('display_errors', 0);

if (ob_get_level()) ob_clean();

header('Content-Type: application/json');

$base_path = dirname(__DIR__, 2);
require_once $base_path . '/config/index.php';

if (!isset($_SESSION['utilizador_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sessão não iniciada.']);
    exit;
}

$db = getDB();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

function enviarResposta($success, $message, $extra = []) {
    $resposta = array_merge(['success' => $success, 'message' => $message], $extra);
    echo json_encode($resposta);
    exit;
}

// ============================================
// SALVAR DESCRIÇÃO DA PÁGINA
// ============================================

if ($action === 'salvar_descricao') {
    $descricao_pagina = trim($_POST['descricao_pagina'] ?? '');
    
    $conteudo = ['descricao_pagina' => $descricao_pagina];
    $conteudo_json = json_encode($conteudo, JSON_UNESCAPED_UNICODE);
    
    try {
        $stmt = $db->prepare("SELECT id FROM conteudo_paginas WHERE slug = 'normativos'");
        $stmt->execute();
        
        if ($stmt->fetch()) {
            $stmt = $db->prepare("UPDATE conteudo_paginas SET conteudo = ?, ultima_edicao_em = NOW() WHERE slug = 'normativos'");
            $success = $stmt->execute([$conteudo_json]);
        } else {
            $stmt = $db->prepare("INSERT INTO conteudo_paginas (slug, titulo, conteudo, created_at) VALUES ('normativos', 'Normativos', ?, NOW())");
            $success = $stmt->execute([$conteudo_json]);
        }
        
        enviarResposta($success, $success ? 'Descrição atualizada!' : 'Erro ao atualizar.');
    } catch (Exception $e) {
        enviarResposta(false, 'Erro: ' . $e->getMessage());
    }
}

// ============================================
// SALVAR DOCUMENTO (CRIAR/EDITAR)
// ============================================

if ($action === 'salvar' || $action === 'editar') {
    try {
        $id = isset($_POST['id']) && $_POST['id'] != '' ? (int)$_POST['id'] : null;
        $titulo = trim($_POST['titulo'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $categoria = trim($_POST['categoria'] ?? 'normativos');
        $data_publicacao = trim($_POST['data_publicacao'] ?? date('Y-m-d'));
        $ordem = (int)($_POST['ordem'] ?? 0);
        
        if (empty($titulo)) {
            enviarResposta(false, 'Título é obrigatório.');
        }
        
        // Buscar dados existentes
        $pdf_url = null;
        $tamanho_kb = 0;
        $imagem_url = null;
        
        if ($id) {
            $stmt = $db->prepare("SELECT pdf_url, tamanho_kb, imagem_url FROM documentos WHERE id = ?");
            $stmt->execute([$id]);
            $existente = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($existente) {
                $pdf_url = $existente['pdf_url'];
                $tamanho_kb = $existente['tamanho_kb'];
                $imagem_url = $existente['imagem_url'];
            }
        }
        
        // ============================================
        // UPLOAD DA IMAGEM
        // ============================================
        if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['imagem'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (in_array($ext, $allowed) && $file['size'] <= 5 * 1024 * 1024) {
                $upload = uploadArquivoNuvem($file, 'documentos/imagens');
                if ($upload['success']) {
                    $imagem_url = $upload['url'];
                }
            }
        }
        
        // ============================================
        // UPLOAD DO PDF
        // ============================================
        if (isset($_FILES['pdf']) && $_FILES['pdf']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['pdf'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if ($ext === 'pdf' && $file['size'] <= 10 * 1024 * 1024) {
                $upload = uploadArquivoNuvem($file, 'documentos/pdfs');
                if ($upload['success']) {
                    $pdf_url = $upload['url'];
                    $tamanho_kb = round($file['size'] / 1024);
                }
            }
        }
        
        // Para novo documento, PDF é obrigatório
        if (!$id && empty($pdf_url)) {
            enviarResposta(false, 'PDF é obrigatório para novos documentos.');
        }
        
        if ($id) {
            $stmt = $db->prepare("UPDATE documentos SET 
                titulo = ?, descricao = ?, categoria = ?, 
                imagem_url = ?, pdf_url = ?, tamanho_kb = ?, 
                data_publicacao = ?, ordem = ? 
                WHERE id = ?");
            $success = $stmt->execute([$titulo, $descricao, $categoria, $imagem_url, $pdf_url, $tamanho_kb, $data_publicacao, $ordem, $id]);
            $message = $success ? 'Documento atualizado com sucesso!' : 'Erro ao atualizar documento.';
        } else {
            $stmt = $db->prepare("INSERT INTO documentos 
                (titulo, descricao, categoria, imagem_url, pdf_url, tamanho_kb, data_publicacao, ordem, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $success = $stmt->execute([$titulo, $descricao, $categoria, $imagem_url, $pdf_url, $tamanho_kb, $data_publicacao, $ordem]);
            $message = $success ? 'Documento adicionado com sucesso!' : 'Erro ao adicionar documento.';
        }
        
        enviarResposta($success, $message);
    } catch (Exception $e) {
        enviarResposta(false, 'Erro: ' . $e->getMessage());
    }
}

// ============================================
// BUSCAR DOCUMENTO
// ============================================

if ($action === 'buscar') {
    $id = (int)($_GET['id'] ?? 0);
    
    if ($id <= 0) {
        enviarResposta(false, 'ID inválido.');
    }
    
    try {
        $stmt = $db->prepare("SELECT * FROM documentos WHERE id = ?");
        $stmt->execute([$id]);
        $documento = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($documento) {
            enviarResposta(true, 'Documento encontrado', ['documento' => $documento]);
        } else {
            enviarResposta(false, 'Documento não encontrado.');
        }
    } catch (Exception $e) {
        enviarResposta(false, 'Erro: ' . $e->getMessage());
    }
}

// ============================================
// ELIMINAR DOCUMENTO
// ============================================

if ($action === 'eliminar') {
    $id = (int)($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        enviarResposta(false, 'ID inválido.');
    }
    
    try {
        $stmt = $db->prepare("SELECT imagem_url, pdf_url FROM documentos WHERE id = ?");
        $stmt->execute([$id]);
        $documento = $stmt->fetch();
        
        if ($documento) {
            if ($documento['imagem_url'] && file_exists(dirname(__DIR__) . '/../area-publica/' . $documento['imagem_url'])) {
                @unlink(dirname(__DIR__) . '/../area-publica/' . $documento['imagem_url']);
            }
            if ($documento['pdf_url'] && file_exists(dirname(__DIR__) . '/../' . $documento['pdf_url'])) {
                @unlink(dirname(__DIR__) . '/../' . $documento['pdf_url']);
            }
        }
        
        $stmt = $db->prepare("DELETE FROM documentos WHERE id = ?");
        $success = $stmt->execute([$id]);
        
        enviarResposta($success, $success ? 'Documento eliminado com sucesso!' : 'Erro ao eliminar documento.');
    } catch (Exception $e) {
        enviarResposta(false, 'Erro: ' . $e->getMessage());
    }
}

enviarResposta(false, 'Ação não reconhecida: ' . $action);
?>

<?php
/**
 * processar-sobre.php
 * Processa todas as operações CRUD para a página Quem Somos
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

function salvarConteudoPagina($slug, $conteudo) {
    $db = getDB();
    $conteudo_json = json_encode($conteudo, JSON_UNESCAPED_UNICODE);
    
    $stmt = $db->prepare("SELECT id FROM conteudo_paginas WHERE slug = ?");
    $stmt->execute([$slug]);
    
    if ($stmt->fetch()) {
        $stmt = $db->prepare("UPDATE conteudo_paginas SET 
            conteudo = ?, 
            ultima_edicao_por = ?, 
            ultima_edicao_em = NOW(),
            status = 'publicado'
            WHERE slug = ?");
        return $stmt->execute([$conteudo_json, $_SESSION['utilizador_id'], $slug]);
    } else {
        $stmt = $db->prepare("INSERT INTO conteudo_paginas 
            (slug, titulo, conteudo, status, ultima_edicao_por, ultima_edicao_em, created_at) 
            VALUES (?, ?, ?, 'publicado', ?, NOW(), NOW())");
        return $stmt->execute([$slug, ucfirst($slug), $conteudo_json, $_SESSION['utilizador_id']]);
    }
}

// ============================================
// SALVAR EVENTO DA LINHA DO TEMPO
// ============================================

if ($action === 'salvar_evento') {
    $id = isset($_POST['id']) && $_POST['id'] != '' ? (int)$_POST['id'] : null;
    $ano = trim($_POST['ano'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $ativo = 1;
    
    if (empty($ano) || empty($descricao)) {
        echo json_encode(['success' => false, 'message' => 'Ano e descrição são obrigatórios.']);
        exit;
    }
    
    try {
        if ($id) {
            $stmt = $db->prepare("UPDATE linha_tempo SET ano = ?, descricao = ?, ativo = ? WHERE id = ?");
            $success = $stmt->execute([$ano, $descricao, $ativo, $id]);
            $message = 'Evento atualizado com sucesso!';
        } else {
            $stmt = $db->prepare("SELECT MAX(ordem) as max_ordem FROM linha_tempo");
            $stmt->execute();
            $ordem = ($stmt->fetch()['max_ordem'] ?? 0) + 1;
            
            $stmt = $db->prepare("INSERT INTO linha_tempo (ano, descricao, ativo, ordem, created_at) VALUES (?, ?, ?, ?, NOW())");
            $success = $stmt->execute([$ano, $descricao, $ativo, $ordem]);
            $message = 'Evento adicionado com sucesso!';
        }
        
        echo json_encode(['success' => $success, 'message' => $message]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
    }
    exit;
}

// ============================================
// ELIMINAR EVENTO
// ============================================

if ($action === 'eliminar_evento') {
    $id = (int)($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID inválido.']);
        exit;
    }
    
    try {
        $stmt = $db->prepare("DELETE FROM linha_tempo WHERE id = ?");
        $success = $stmt->execute([$id]);
        echo json_encode(['success' => $success, 'message' => $success ? 'Evento eliminado com sucesso!' : 'Erro ao eliminar evento.']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
    }
    exit;
}

// ============================================
// SALVAR CONFIGURAÇÕES GERAIS
// ============================================

if ($action === 'salvar_geral') {
    $hero_titulo = trim($_POST['hero_titulo'] ?? '');
    $hero_subtitulo = trim($_POST['hero_subtitulo'] ?? '');
    $historia_titulo = trim($_POST['historia_titulo'] ?? '');
    $historia_legenda = trim($_POST['historia_legenda'] ?? '');
    $historia_conteudo = $_POST['historia_conteudo'] ?? '';
    $missao = trim($_POST['missao'] ?? '');
    $visao = trim($_POST['visao'] ?? '');
    $valores = trim($_POST['valores'] ?? '');
    $lema = trim($_POST['lema'] ?? '');
    $lema_descricao = trim($_POST['lema_descricao'] ?? '');
    
    // Processar imagem da história
    $historia_imagem = null;
        
    if (isset($_FILES['historia_imagem']) && $_FILES['historia_imagem']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['historia_imagem'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($ext, $allowed) && $file['size'] <= 5 * 1024 * 1024) {
            $upload = uploadArquivoNuvem($file, 'sobre');
            if ($upload['success']) {
                $historia_imagem = $upload['url'];
            }
        }
    }
    
    // Buscar dados atuais para manter imagem se não houver nova
    $pagina_atual = getPagina('sobre');
    if (empty($historia_imagem) && isset($pagina_atual['historia']['imagem'])) {
        $historia_imagem = $pagina_atual['historia']['imagem'];
    } elseif (empty($historia_imagem)) {
        $historia_imagem = 'foto/img_construct_5.jpg';
    }
    
    $conteudo = [
        'hero' => [
            'titulo' => $hero_titulo,
            'subtitulo' => $hero_subtitulo
        ],
        'historia' => [
            'titulo' => $historia_titulo,
            'conteudo' => $historia_conteudo,
            'imagem' => $historia_imagem,
            'legenda' => $historia_legenda
        ],
        'missao' => $missao,
        'visao' => $visao,
        'valores' => $valores,
        'lema' => $lema,
        'lema_descricao' => $lema_descricao
    ];
    
    if (salvarConteudoPagina('sobre', $conteudo)) {
        echo json_encode(['success' => true, 'message' => 'Configurações guardadas com sucesso!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao guardar configurações.']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Ação não reconhecida: ' . $action]);
?>

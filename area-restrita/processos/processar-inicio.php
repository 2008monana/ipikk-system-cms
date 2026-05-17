<?php
/**
 * processar-inicio.php
 * Processa todas as operações CRUD para a página inicial
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
// FUNÇÃO AUXILIAR PARA SALVAR CONTEÚDO
// ============================================

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
// CARREGAR DADOS ATUAIS
// ============================================

$pagina_atual = getPagina('inicio');
$slider = isset($pagina_atual['slider']) && is_array($pagina_atual['slider']) ? $pagina_atual['slider'] : [];
$parceiros = isset($pagina_atual['parceiros']) && is_array($pagina_atual['parceiros']) ? $pagina_atual['parceiros'] : [];
$mensagem_director = isset($pagina_atual['mensagem_director']) && is_array($pagina_atual['mensagem_director']) 
    ? $pagina_atual['mensagem_director'] 
    : [];
$matricula = isset($pagina_atual['matricula']) && is_array($pagina_atual['matricula']) 
    ? $pagina_atual['matricula'] 
    : [];

// ============================================
// SALVAR MENSAGEM DO DIRECTOR (NOVA AÇÃO)
// ============================================

if ($action === 'salvar_director') {
    $nome = trim($_POST['nome'] ?? '');
    $cargo = trim($_POST['cargo'] ?? '');
    $mensagem = trim($_POST['mensagem'] ?? '');
    $assinatura = trim($_POST['assinatura'] ?? '');
    
    // Processar upload da foto
    $foto_url = $mensagem_director['foto'] ?? 'foto/perfil-do-director.jpg';
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['foto'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($ext, $allowed) && $file['size'] <= 5 * 1024 * 1024) {
            $upload = uploadArquivoNuvem($file, 'inicio/director');
            if ($upload['success']) {
                $foto_url = $upload['url'];
            }
        }
    }
    
    $pagina_atual['mensagem_director'] = [
        'nome' => $nome,
        'cargo' => $cargo,
        'mensagem' => $mensagem,
        'assinatura' => $assinatura,
        'foto' => $foto_url
    ];
    
    if (salvarConteudoPagina('inicio', $pagina_atual)) {
        echo json_encode(['success' => true, 'message' => 'Mensagem do Director salva com sucesso!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao salvar mensagem.']);
    }
    exit;
}

// ============================================
// SALVAR MATRÍCULA (NOVA AÇÃO)
// ============================================

if ($action === 'salvar_matricula') {
    $titulo = trim($_POST['titulo'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    
    // Processar upload da imagem
    $imagem_url = $matricula['imagem'] ?? 'foto/matricula.jpg';
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['imagem'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($ext, $allowed) && $file['size'] <= 5 * 1024 * 1024) {
            $upload = uploadArquivoNuvem($file, 'inicio/matricula');
            if ($upload['success']) {
                $imagem_url = $upload['url'];
            }
        }
    }
    
    $pagina_atual['matricula'] = [
        'titulo' => $titulo,
        'descricao' => $descricao,
        'imagem' => $imagem_url
    ];
    
    if (salvarConteudoPagina('inicio', $pagina_atual)) {
        echo json_encode(['success' => true, 'message' => 'Secção de Matrícula salva com sucesso!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao salvar matrícula.']);
    }
    exit;
}

if ($action === 'remover_foto_director') {
    if (!isset($pagina_atual['mensagem_director']) || !is_array($pagina_atual['mensagem_director'])) {
        $pagina_atual['mensagem_director'] = [];
    }
    $pagina_atual['mensagem_director']['foto'] = 'foto/perfil-do-director.jpg';
    if (salvarConteudoPagina('inicio', $pagina_atual)) {
        echo json_encode(['success' => true, 'message' => 'Foto do Director removida com sucesso!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao remover foto do Director.']);
    }
    exit;
}

if ($action === 'remover_imagem_matricula') {
    if (!isset($pagina_atual['matricula']) || !is_array($pagina_atual['matricula'])) {
        $pagina_atual['matricula'] = [];
    }
    $pagina_atual['matricula']['imagem'] = 'foto/matricula.jpg';
    if (salvarConteudoPagina('inicio', $pagina_atual)) {
        echo json_encode(['success' => true, 'message' => 'Imagem de matrícula removida com sucesso!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao remover imagem de matrícula.']);
    }
    exit;
}

// ============================================
// SALVAR SLIDE (CRIAR/EDITAR)
// ============================================

if ($action === 'salvar_slide') {
    $index = isset($_POST['index']) && $_POST['index'] !== '' ? (int)$_POST['index'] : null;
    $titulo = trim($_POST['titulo'] ?? '');
    $subtitulo = trim($_POST['subtitulo'] ?? '');
    $botao = trim($_POST['botao'] ?? 'Saiba mais');
    $link = trim($_POST['link'] ?? '#');
    
    if (empty($titulo)) {
        echo json_encode(['success' => false, 'message' => 'Título do slide é obrigatório.']);
        exit;
    }
    
    // Processar upload da imagem
    $imagem_url = null;
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['imagem'];
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
        
        $upload = uploadArquivoNuvem($file, 'inicio/slider');
        if ($upload['success']) {
            $imagem_url = $upload['url'];
        } else {
            echo json_encode(['success' => false, 'message' => $upload['message']]);
            exit;
        }
    } elseif ($index !== null && isset($slider[$index]['imagem'])) {
        $imagem_url = $slider[$index]['imagem'];
    }
    
    $novo_slide = [
        'titulo' => $titulo,
        'subtitulo' => $subtitulo,
        'botao' => $botao,
        'link' => $link,
        'imagem' => $imagem_url
    ];
    
    if ($index !== null && isset($slider[$index])) {
        $slider[$index] = $novo_slide;
    } else {
        $slider[] = $novo_slide;
    }
    
    $pagina_atual['slider'] = $slider;
    
    if (salvarConteudoPagina('inicio', $pagina_atual)) {
        echo json_encode(['success' => true, 'message' => 'Slide salvo com sucesso!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao salvar slide.']);
    }
    exit;
}

// ============================================
// ELIMINAR SLIDE
// ============================================

if ($action === 'eliminar_slide') {
    $index = (int)($_POST['index'] ?? -1);
    
    if ($index >= 0 && isset($slider[$index])) {
        array_splice($slider, $index, 1);
        $pagina_atual['slider'] = $slider;
        
        if (salvarConteudoPagina('inicio', $pagina_atual)) {
            echo json_encode(['success' => true, 'message' => 'Slide eliminado com sucesso!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao eliminar slide.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Slide não encontrado.']);
    }
    exit;
}

// ============================================
// SALVAR PARCEIRO (CRIAR/EDITAR)
// ============================================

if ($action === 'salvar_parceiro') {
    $index = isset($_POST['index']) && $_POST['index'] !== '' ? (int)$_POST['index'] : null;
    $nome = trim($_POST['nome'] ?? '');
    $link = trim($_POST['link'] ?? '#');
    
    if (empty($nome)) {
        echo json_encode(['success' => false, 'message' => 'Nome do parceiro é obrigatório.']);
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
        
        $upload = uploadArquivoNuvem($file, 'inicio/parceiros');
        if ($upload['success']) {
            $logo_url = $upload['url'];
        } else {
            echo json_encode(['success' => false, 'message' => $upload['message']]);
            exit;
        }
    } elseif ($index !== null && isset($parceiros[$index]['logo'])) {
        $logo_url = $parceiros[$index]['logo'];
    }
    
    $novo_parceiro = [
        'nome' => $nome,
        'link' => $link,
        'logo' => $logo_url
    ];
    
    if ($index !== null && isset($parceiros[$index])) {
        $parceiros[$index] = $novo_parceiro;
    } else {
        $parceiros[] = $novo_parceiro;
    }
    
    $pagina_atual['parceiros'] = $parceiros;
    
    if (salvarConteudoPagina('inicio', $pagina_atual)) {
        echo json_encode(['success' => true, 'message' => 'Parceiro salvo com sucesso!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao salvar parceiro.']);
    }
    exit;
}

// ============================================
// ELIMINAR PARCEIRO
// ============================================

if ($action === 'eliminar_parceiro') {
    $index = (int)($_POST['index'] ?? -1);
    
    if ($index >= 0 && isset($parceiros[$index])) {
        array_splice($parceiros, $index, 1);
        $pagina_atual['parceiros'] = $parceiros;
        
        if (salvarConteudoPagina('inicio', $pagina_atual)) {
            echo json_encode(['success' => true, 'message' => 'Parceiro eliminado com sucesso!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao eliminar parceiro.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Parceiro não encontrado.']);
    }
    exit;
}

// ============================================
// SALVAR CONFIGURAÇÕES GERAIS (LEGADO)
// ============================================

if ($action === 'salvar_geral') {
    // Processar foto do director
    $director_foto = $mensagem_director['foto'] ?? 'foto/perfil-do-director.jpg';
    if (isset($_FILES['director_foto']) && $_FILES['director_foto']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['director_foto'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($ext, $allowed) && $file['size'] <= 5 * 1024 * 1024) {
            $upload = uploadArquivoNuvem($file, 'inicio/director');
            if ($upload['success']) {
                $director_foto = $upload['url'];
            }
        }
    }
    
    // Processar imagem da matrícula
    $matricula_imagem = $matricula['imagem'] ?? 'foto/matricula.jpg';
    if (isset($_FILES['matricula_imagem']) && $_FILES['matricula_imagem']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['matricula_imagem'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($ext, $allowed) && $file['size'] <= 5 * 1024 * 1024) {
            $upload = uploadArquivoNuvem($file, 'inicio/matricula');
            if ($upload['success']) {
                $matricula_imagem = $upload['url'];
            }
        }
    }
    
    // Actualizar mensagem do director
    $pagina_atual['mensagem_director'] = [
        'nome' => trim($_POST['director_nome'] ?? ''),
        'cargo' => trim($_POST['director_cargo'] ?? ''),
        'mensagem' => trim($_POST['director_mensagem'] ?? ''),
        'assinatura' => trim($_POST['director_assinatura'] ?? ''),
        'foto' => $director_foto
    ];
    
    // Actualizar matrícula
    $pagina_atual['matricula'] = [
        'titulo' => trim($_POST['matricula_titulo'] ?? ''),
        'descricao' => trim($_POST['matricula_descricao'] ?? ''),
        'imagem' => $matricula_imagem
    ];
    
    if (salvarConteudoPagina('inicio', $pagina_atual)) {
        echo json_encode(['success' => true, 'message' => 'Configurações guardadas com sucesso!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao guardar configurações.']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Ação não reconhecida: ' . $action]);
?>

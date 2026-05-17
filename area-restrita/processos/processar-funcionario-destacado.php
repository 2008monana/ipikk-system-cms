<?php
/**
 * processar-funcionario-destacado.php
 * Processa todas as operações CRUD para os funcionários em destaque
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
// SALVAR FUNCIONÁRIO (CRIAR/EDITAR)
// ============================================

if ($action === 'salvar') {
    $id = isset($_POST['id']) && $_POST['id'] != '' ? (int)$_POST['id'] : null;
    $grupo = (int)($_POST['grupo'] ?? 1);
    $nome = trim($_POST['nome'] ?? '');
    $cargo = trim($_POST['cargo'] ?? '');
    $ativo = 1;
    
    if (empty($nome) || empty($cargo)) {
        echo json_encode(['success' => false, 'message' => 'Nome e cargo são obrigatórios.']);
        exit;
    }
    
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
        
        $upload = uploadArquivoNuvem($file, 'funcionarios');
        if ($upload['success']) {
            $foto_url = $upload['url'];
        } else {
            echo json_encode(['success' => false, 'message' => $upload['message']]);
            exit;
        }
    } elseif ($id) {
        // Manter foto existente
        $stmt = $db->prepare("SELECT foto_url FROM funcionarios_destaque WHERE id = ?");
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
    
    // Buscar a maior ordem atual para o grupo
    $stmt = $db->prepare("SELECT MAX(ordem) as max_ordem FROM funcionarios_destaque WHERE grupo = ?");
    $stmt->execute([$grupo]);
    $max_ordem = $stmt->fetch()['max_ordem'] ?? 0;
    $ordem = $max_ordem + 1;
    
    try {
        if ($id) {
            // Atualizar funcionário existente (manter ordem)
            $stmt = $db->prepare("UPDATE funcionarios_destaque SET 
                nome = ?, cargo = ?, foto_url = ?, ativo = ? 
                WHERE id = ?");
            $success = $stmt->execute([$nome, $cargo, $foto_url, $ativo, $id]);
            $message = $success ? 'Funcionário atualizado com sucesso!' : 'Erro ao atualizar funcionário.';
        } else {
            // Inserir novo funcionário
            $stmt = $db->prepare("INSERT INTO funcionarios_destaque 
                (ano_lectivo, grupo, nome, cargo, foto_url, ordem, ativo, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            $ano_lectivo = date('Y') . '/' . (date('Y') + 1);
            $success = $stmt->execute([$ano_lectivo, $grupo, $nome, $cargo, $foto_url, $ordem, $ativo]);
            $message = $success ? 'Funcionário adicionado com sucesso!' : 'Erro ao adicionar funcionário.';
        }
        
        echo json_encode(['success' => $success, 'message' => $message]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
    }
    exit;
}

// ============================================
// ELIMINAR FUNCIONÁRIO
// ============================================

if ($action === 'eliminar') {
    $id = (int)($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID inválido.']);
        exit;
    }
    
    try {
        // Buscar foto para deletar se for local
        $stmt = $db->prepare("SELECT foto_url FROM funcionarios_destaque WHERE id = ?");
        $stmt->execute([$id]);
        $funcionario = $stmt->fetch();
        
        if ($funcionario && $funcionario['foto_url'] && $funcionario['foto_url'] !== 'foto/sem_foto.png') {
            $caminho_foto = dirname(__DIR__) . '/../area-publica/' . $funcionario['foto_url'];
            if (file_exists($caminho_foto)) {
                unlink($caminho_foto);
            }
        }
        
        $stmt = $db->prepare("DELETE FROM funcionarios_destaque WHERE id = ?");
        $success = $stmt->execute([$id]);
        
        echo json_encode(['success' => $success, 'message' => $success ? 'Funcionário eliminado com sucesso!' : 'Erro ao eliminar funcionário.']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
    }
    exit;
}

// ============================================
// SALVAR CONFIGURAÇÕES GERAIS
// ============================================

if ($action === 'salvar_geral') {
    $ano_lectivo = trim($_POST['ano_lectivo'] ?? '');
    $hero_titulo = trim($_POST['hero_titulo'] ?? 'Funcionários Destacados');
    $hero_subtitulo = trim($_POST['hero_subtitulo'] ?? '');
    $faixa_texto = trim($_POST['faixa_texto'] ?? '');
    
    if (empty($ano_lectivo)) {
        echo json_encode(['success' => false, 'message' => 'Ano lectivo é obrigatório.']);
        exit;
    }
    
    try {
        // Verificar se a coluna existe na tabela configuracoes
        $stmt = $db->query("SHOW COLUMNS FROM configuracoes LIKE 'ano_lectivo_atual'");
        $coluna_existe = $stmt->fetch();
        
        if ($coluna_existe) {
            // Se a coluna existe, atualiza
            $stmt = $db->prepare("UPDATE configuracoes SET ano_lectivo_atual = ? WHERE id = 1");
            $stmt->execute([$ano_lectivo]);
        } else {
            // Se não existe, adicionar a coluna primeiro
            $db->exec("ALTER TABLE configuracoes ADD COLUMN ano_lectivo_atual VARCHAR(20) NULL");
            $stmt = $db->prepare("UPDATE configuracoes SET ano_lectivo_atual = ? WHERE id = 1");
            $stmt->execute([$ano_lectivo]);
        }
        
        // Atualizar conteúdo da página na tabela conteudo_paginas
        $conteudo = [
            'hero_titulo' => $hero_titulo,
            'hero_subtitulo' => $hero_subtitulo,
            'faixa_texto' => $faixa_texto
        ];
        $conteudo_json = json_encode($conteudo, JSON_UNESCAPED_UNICODE);
        
        $stmt = $db->prepare("SELECT id FROM conteudo_paginas WHERE slug = 'funcionarios'");
        $stmt->execute();
        
        if ($stmt->fetch()) {
            $stmt = $db->prepare("UPDATE conteudo_paginas SET 
                conteudo = ?, 
                ultima_edicao_por = ?, 
                ultima_edicao_em = NOW(),
                status = 'publicado'
                WHERE slug = 'funcionarios'");
            $success = $stmt->execute([$conteudo_json, $_SESSION['utilizador_id']]);
        } else {
            $stmt = $db->prepare("INSERT INTO conteudo_paginas 
                (slug, titulo, conteudo, status, ultima_edicao_por, ultima_edicao_em, created_at) 
                VALUES ('funcionarios', 'Funcionários Destacados', ?, 'publicado', ?, NOW(), NOW())");
            $success = $stmt->execute([$conteudo_json, $_SESSION['utilizador_id']]);
        }
        
        echo json_encode(['success' => $success, 'message' => $success ? 'Configurações guardadas com sucesso!' : 'Erro ao guardar configurações.']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Ação não reconhecida: ' . $action]);
?>

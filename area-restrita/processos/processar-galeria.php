<?php
/**
 * processar-galeria.php
 * Processa todas as operações CRUD para a galeria de mídias e categorias
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
// SALVAR MÍDIA (CRIAR/EDITAR)
// ============================================

if ($action === 'salvar') {
    $id = isset($_POST['id']) && $_POST['id'] != '' ? (int)$_POST['id'] : null;
    $categoria_id = (int)($_POST['categoria_id'] ?? 0);
    $tipo = $_POST['tipo'] ?? 'imagem';
    $legenda = trim($_POST['legenda'] ?? '');
    $ordem = (int)($_POST['ordem'] ?? 0);
    $url_externa = trim($_POST['url'] ?? '');
    
    // Validações
    if ($categoria_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Selecione uma categoria.']);
        exit;
    }
    
    $url = null;
    
    // Processar upload de arquivo
    if (isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['arquivo'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if ($tipo === 'imagem') {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $max_size = 5 * 1024 * 1024;
        } else {
            $allowed = ['mp4', 'webm', 'ogg'];
            $max_size = 50 * 1024 * 1024;
        }
        
        if (!in_array($ext, $allowed)) {
            echo json_encode(['success' => false, 'message' => 'Formato de arquivo não permitido.']);
            exit;
        }
        
        if ($file['size'] > $max_size) {
            echo json_encode(['success' => false, 'message' => 'Arquivo muito grande. Máximo: ' . ($max_size / 1024 / 1024) . 'MB']);
            exit;
        }
        
        $upload = uploadArquivoNuvem($file, $tipo === 'imagem' ? 'galeria/imagens' : 'galeria/videos');
        if ($upload['success']) {
            $url = $upload['url'];
        } else {
            echo json_encode(['success' => false, 'message' => $upload['message']]);
            exit;
        }
    } elseif (!empty($url_externa)) {
        $url = $url_externa;
    } elseif ($id) {
        // Manter URL existente ao editar
        $stmt = $db->prepare("SELECT url FROM galeria WHERE id = ?");
        $stmt->execute([$id]);
        $existente = $stmt->fetch();
        if ($existente) {
            $url = $existente['url'];
        }
    }
    
    if (empty($url)) {
        echo json_encode(['success' => false, 'message' => 'Nenhum arquivo ou URL fornecida.']);
        exit;
    }
    
    try {
        if ($id) {
            // Atualizar mídia existente
            $stmt = $db->prepare("UPDATE galeria SET 
                categoria_id = ?, tipo = ?, url = ?, legenda = ?, 
                ordem = ?, updated_at = NOW() 
                WHERE id = ?");
            $success = $stmt->execute([$categoria_id, $tipo, $url, $legenda, $ordem, $id]);
            $message = $success ? 'Mídia atualizada com sucesso!' : 'Erro ao atualizar mídia.';
        } else {
            // Inserir nova mídia
            $stmt = $db->prepare("INSERT INTO galeria 
                (categoria_id, tipo, url, legenda, ordem, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
            $success = $stmt->execute([$categoria_id, $tipo, $url, $legenda, $ordem]);
            $message = $success ? 'Mídia adicionada com sucesso!' : 'Erro ao adicionar mídia.';
        }
        
        echo json_encode(['success' => $success, 'message' => $message]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
    }
    exit;
}

// ============================================
// BUSCAR MÍDIA PARA EDIÇÃO
// ============================================

if ($action === 'buscar') {
    $id = (int)($_GET['id'] ?? 0);
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID inválido.']);
        exit;
    }
    
    try {
        $stmt = $db->prepare("SELECT * FROM galeria WHERE id = ?");
        $stmt->execute([$id]);
        $midia = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($midia) {
            echo json_encode(['success' => true, 'midia' => $midia]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Mídia não encontrada.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
    }
    exit;
}

// ============================================
// ELIMINAR MÍDIA (MOVER PARA LIXEIRA)
// ============================================

if ($action === 'eliminar') {
    $id = (int)($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID inválido.']);
        exit;
    }
    
    try {
        // Buscar dados da mídia antes de eliminar
        $stmt = $db->prepare("SELECT url, tipo FROM galeria WHERE id = ?");
        $stmt->execute([$id]);
        $midia = $stmt->fetch();
        
        if (!$midia) {
            echo json_encode(['success' => false, 'message' => 'Mídia não encontrada.']);
            exit;
        }
        
        // Mover arquivo para lixeira se for arquivo local
        $lixeira_dir = dirname(__DIR__) . '/../uploads/lixeira/';
        if (!is_dir($lixeira_dir)) {
            mkdir($lixeira_dir, 0777, true);
        }
        
        $arquivo_movido = false;
        if (strpos($midia['url'], '/uploads/galeria/') === 0) {
            $caminho_origem = dirname(__DIR__) . '/..' . $midia['url'];
            $nome_arquivo = basename($midia['url']);
            $tamanho = file_exists($caminho_origem) ? filesize($caminho_origem) : 0;
            $nome_unico = date('Y-m-d_H-i-s') . '_' . $nome_arquivo;
            $caminho_destino = $lixeira_dir . $nome_unico;
            
            if (file_exists($caminho_origem)) {
                if (rename($caminho_origem, $caminho_destino)) {
                    $data_expiracao = date('Y-m-d H:i:s', strtotime('+30 days'));
                    $stmt_lixeira = $db->prepare("INSERT INTO lixeira 
                        (tipo, nome_original, caminho_original, caminho_lixeira, tamanho_bytes, data_expiracao) 
                        VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt_lixeira->execute([
                        $midia['tipo'],
                        $nome_arquivo,
                        $midia['url'],
                        '/uploads/lixeira/' . $nome_unico,
                        $tamanho,
                        $data_expiracao
                    ]);
                    $arquivo_movido = true;
                }
            }
        }
        
        // Remover registro do banco
        $stmt = $db->prepare("DELETE FROM galeria WHERE id = ?");
        $stmt->execute([$id]);
        
        $mensagem = 'Mídia eliminada com sucesso!';
        if ($arquivo_movido) {
            $mensagem .= ' Arquivo movido para a lixeira.';
        }
        
        echo json_encode(['success' => true, 'message' => $mensagem]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
    }
    exit;
}

// ============================================
// ORDENAR MÍDIAS
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
        $stmt = $db->prepare("UPDATE galeria SET ordem = ? WHERE id = ?");
        
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

// ============================================
// LISTAR MÍDIAS (com filtros)
// ============================================

if ($action === 'listar') {
    $categoria = (int)($_GET['categoria'] ?? 0);
    $tipo = $_GET['tipo'] ?? '';
    $busca = $_GET['busca'] ?? '';
    
    $sql = "SELECT g.*, c.nome as categoria_nome, c.slug as categoria_slug, c.cor_classe 
            FROM galeria g 
            JOIN categorias_galeria c ON g.categoria_id = c.id 
            WHERE 1=1";
    $params = [];
    
    if ($categoria > 0) {
        $sql .= " AND g.categoria_id = ?";
        $params[] = $categoria;
    }
    if (!empty($tipo)) {
        $sql .= " AND g.tipo = ?";
        $params[] = $tipo;
    }
    if (!empty($busca)) {
        $sql .= " AND (g.legenda LIKE ? OR g.url LIKE ?)";
        $params[] = "%{$busca}%";
        $params[] = "%{$busca}%";
    }
    
    $sql .= " ORDER BY g.ordem, g.created_at DESC";
    
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $midias = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'midias' => $midias]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro ao listar: ' . $e->getMessage()]);
    }
    exit;
}

// ============================================
// CATEGORIAS - LISTAR
// ============================================

if ($action === 'listar_categorias') {
    try {
        $stmt = $db->query("SELECT * FROM categorias_galeria WHERE ativo = 1 ORDER BY ordem");
        $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'categorias' => $categorias]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro ao listar categorias: ' . $e->getMessage()]);
    }
    exit;
}

// ============================================
// CATEGORIAS - BUSCAR UMA
// ============================================

if ($action === 'buscar_categoria') {
    $id = (int)($_GET['id'] ?? 0);
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID inválido.']);
        exit;
    }
    
    try {
        $stmt = $db->prepare("SELECT * FROM categorias_galeria WHERE id = ?");
        $stmt->execute([$id]);
        $categoria = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($categoria) {
            echo json_encode(['success' => true, 'categoria' => $categoria]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Categoria não encontrada.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
    }
    exit;
}

// ============================================
// CATEGORIAS - SALVAR (CRIAR/EDITAR)
// ============================================

if ($action === 'salvar_categoria') {
    $nome = trim($_POST['nome'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $cor_classe = trim($_POST['cor_classe'] ?? '#003072');
    $icone = trim($_POST['icone'] ?? 'fa-tag');
    $ordem = (int)($_POST['ordem'] ?? 0);
    $ativo = 1;
    
    // Validar nome
    if (empty($nome)) {
        echo json_encode(['success' => false, 'message' => 'Nome da categoria é obrigatório.']);
        exit;
    }
    
    // Gerar slug se não fornecido
    if (empty($slug)) {
        $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9-]/', '-', $nome), '-'));
    }
    
    try {
        $stmt = $db->prepare("INSERT INTO categorias_galeria 
            (nome, slug, cor_classe, icone, ordem, ativo, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $success = $stmt->execute([$nome, $slug, $cor_classe, $icone, $ordem, $ativo]);
        $message = $success ? 'Categoria criada com sucesso!' : 'Erro ao criar categoria.';
        
        echo json_encode(['success' => $success, 'message' => $message]);
    } catch (PDOException $e) {
        // Verificar se é erro de duplicação de slug
        if ($e->errorInfo[1] == 1062) {
            echo json_encode(['success' => false, 'message' => 'Já existe uma categoria com este slug.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
        }
    }
    exit;
}

// ============================================
// CATEGORIAS - EDITAR
// ============================================

if ($action === 'editar_categoria') {
    $id = (int)($_POST['id'] ?? 0);
    $nome = trim($_POST['nome'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $cor_classe = trim($_POST['cor_classe'] ?? '#003072');
    $icone = trim($_POST['icone'] ?? 'fa-tag');
    $ordem = (int)($_POST['ordem'] ?? 0);
    $ativo = 1;
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID inválido.']);
        exit;
    }
    
    if (empty($nome)) {
        echo json_encode(['success' => false, 'message' => 'Nome da categoria é obrigatório.']);
        exit;
    }
    
    if (empty($slug)) {
        $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9-]/', '-', $nome), '-'));
    }
    
    try {
        $stmt = $db->prepare("UPDATE categorias_galeria SET 
            nome = ?, slug = ?, cor_classe = ?, icone = ?, ordem = ?, ativo = ? 
            WHERE id = ?");
        $success = $stmt->execute([$nome, $slug, $cor_classe, $icone, $ordem, $ativo, $id]);
        $message = $success ? 'Categoria atualizada com sucesso!' : 'Erro ao atualizar categoria.';
        
        echo json_encode(['success' => $success, 'message' => $message]);
    } catch (PDOException $e) {
        if ($e->errorInfo[1] == 1062) {
            echo json_encode(['success' => false, 'message' => 'Já existe uma categoria com este slug.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
        }
    }
    exit;
}

// ============================================
// CATEGORIAS - ELIMINAR
// ============================================

if ($action === 'eliminar_categoria') {
    $id = (int)($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID inválido.']);
        exit;
    }
    
    try {
        // Verificar se existem mídias associadas a esta categoria
        $stmt = $db->prepare("SELECT COUNT(*) FROM galeria WHERE categoria_id = ?");
        $stmt->execute([$id]);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            echo json_encode(['success' => false, 'message' => "Não é possível eliminar esta categoria pois existem {$count} mídia(s) associada(s). Remova ou reassocie as mídias primeiro."]);
            exit;
        }
        
        $stmt = $db->prepare("DELETE FROM categorias_galeria WHERE id = ?");
        $success = $stmt->execute([$id]);
        
        echo json_encode(['success' => $success, 'message' => $success ? 'Categoria eliminada com sucesso!' : 'Erro ao eliminar categoria.']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Ação não reconhecida: ' . $action]);
?>

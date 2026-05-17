<?php
/**
 * processar-perfil-director.php
 * Processa o salvamento do perfil do director
 */

header('Content-Type: application/json');

// Ajustar o caminho base para ipikk-2
$base_path = dirname(__DIR__, 2);
require_once $base_path . '/config/index.php';

if (!isset($_SESSION['utilizador_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sessão não iniciada.']);
    exit;
}

$db = getDB();
$action = $_POST['action'] ?? '';

if ($action !== 'salvar') {
    echo json_encode(['success' => false, 'message' => 'Ação inválida.']);
    exit;
}

// Coletar dados
$nome = trim($_POST['nome'] ?? '');
$cargo = trim($_POST['cargo'] ?? '');
$data_nascimento = trim($_POST['data_nascimento'] ?? '');
$naturalidade = trim($_POST['naturalidade'] ?? '');
$experiencia = trim($_POST['experiencia'] ?? '');
$inicio_cargo = trim($_POST['inicio_cargo'] ?? '');
$resumo = trim($_POST['resumo'] ?? '');
$citacao = trim($_POST['citacao'] ?? '');
$formacoes = json_decode($_POST['formacoes'] ?? '[]', true);
$experiencias = json_decode($_POST['experiencias'] ?? '[]', true);
$realizacoes = json_decode($_POST['realizacoes'] ?? '[]', true);
$idiomas = json_decode($_POST['idiomas'] ?? '[]', true);

// Validação
if (empty($nome)) {
    echo json_encode(['success' => false, 'message' => 'Nome é obrigatório.']);
    exit;
}

// Processar upload da foto
$foto_url = null;
$upload_dir = dirname(__DIR__) . '/../area-publica/foto/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

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
    
    $upload = uploadArquivoNuvem($file, 'director');
    if ($upload['success']) {
        $foto_url = $upload['url'];
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao fazer upload da foto.']);
        exit;
    }
}

// Buscar dados atuais para manter foto se não houver nova
if (empty($foto_url)) {
    $stmt = $db->prepare("SELECT conteudo FROM conteudo_paginas WHERE slug = 'director'");
    $stmt->execute();
    $atual = $stmt->fetch();
    if ($atual && $atual['conteudo']) {
        $conteudo_atual = json_decode($atual['conteudo'], true);
        $foto_url = $conteudo_atual['foto'] ?? 'foto/perfil-do-director.jpg';
    } else {
        $foto_url = 'foto/perfil-do-director.jpg';
    }
}

// Montar conteúdo
$conteudo = [
    'nome' => $nome,
    'cargo' => $cargo,
    'foto' => $foto_url,
    'data_nascimento' => $data_nascimento,
    'naturalidade' => $naturalidade,
    'experiencia' => $experiencia,
    'inicio_cargo' => $inicio_cargo,
    'resumo' => $resumo,
    'citacao' => $citacao,
    'formacoes' => $formacoes,
    'experiencias' => $experiencias,
    'realizacoes' => $realizacoes,
    'idiomas' => $idiomas
];

$conteudo_json = json_encode($conteudo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

try {
    $stmt = $db->prepare("SELECT id FROM conteudo_paginas WHERE slug = 'director'");
    $stmt->execute();
    $existe = $stmt->fetch();
    
    if ($existe) {
        $stmt = $db->prepare("UPDATE conteudo_paginas SET 
            conteudo = ?, 
            ultima_edicao_por = ?, 
            ultima_edicao_em = NOW(),
            status = 'publicado'
            WHERE slug = 'director'");
        $success = $stmt->execute([$conteudo_json, $_SESSION['utilizador_id']]);
    } else {
        $stmt = $db->prepare("INSERT INTO conteudo_paginas 
            (slug, titulo, conteudo, status, ultima_edicao_por, ultima_edicao_em, created_at) 
            VALUES ('director', 'Perfil do Director', ?, 'publicado', ?, NOW(), NOW())");
        $success = $stmt->execute([$conteudo_json, $_SESSION['utilizador_id']]);
    }
    
    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Perfil do Director atualizado com sucesso!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar perfil.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
}
?>

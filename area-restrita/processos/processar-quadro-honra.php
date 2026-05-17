<?php
/**
 * processar-quadro-honra.php
 * Processa o salvamento do quadro de honra (preserva fotos existentes)
 */

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

$quadro_honra_id = (int)($_POST['quadro_honra_id'] ?? 0);
$ano_lectivo = trim($_POST['ano_lectivo'] ?? '');
$citacao_texto = trim($_POST['citacao_texto'] ?? '');
$citacao_referencia = trim($_POST['citacao_referencia'] ?? '');
$melhores_classe_json = $_POST['melhores_classe'] ?? '[]';
$melhores_classe = json_decode($melhores_classe_json, true);

if (empty($ano_lectivo)) {
    echo json_encode(['success' => false, 'message' => 'Ano lectivo é obrigatório.']);
    exit;
}

if (empty($melhores_classe) || count($melhores_classe) !== 3) {
    echo json_encode(['success' => false, 'message' => 'As 3 classes (10ª, 11ª, 12ª) são obrigatórias.']);
    exit;
}

$upload_dir = dirname(__DIR__) . '/../area-publica/uploads/quadro-honra/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

try {
    $db->beginTransaction();
    
    // Buscar fotos existentes para preservar
    $stmt = $db->prepare("SELECT classe, foto_url FROM quadro_honra_classe WHERE quadro_honra_id = ?");
    $stmt->execute([$quadro_honra_id]);
    $fotos_existentes = [];
    while ($row = $stmt->fetch()) {
        $fotos_existentes[$row['classe']] = $row['foto_url'];
    }
    
    // Atualizar ou criar quadro_honra
    $stmt = $db->prepare("SELECT id FROM quadro_honra WHERE id = ?");
    $stmt->execute([$quadro_honra_id]);
    
    if (!$stmt->fetch()) {
        $stmt = $db->prepare("INSERT INTO quadro_honra (ano_lectivo, ativo) VALUES (?, 1)");
        $stmt->execute([$ano_lectivo]);
        $quadro_honra_id = $db->lastInsertId();
    } else {
        $stmt = $db->prepare("UPDATE quadro_honra SET ano_lectivo = ? WHERE id = ?");
        $stmt->execute([$ano_lectivo, $quadro_honra_id]);
    }
    
    // Eliminar registos antigos
    $stmt = $db->prepare("DELETE FROM quadro_honra_classe WHERE quadro_honra_id = ?");
    $stmt->execute([$quadro_honra_id]);
    
    // Inserir novos registos
    $stmt = $db->prepare("INSERT INTO quadro_honra_classe 
        (quadro_honra_id, classe, nome, media, curso, foto_url, ordem) 
        VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    $ordem = 0;
    foreach ($melhores_classe as $aluno) {
        $classe = (int)($aluno['classe'] ?? 0);
        $nome = trim($aluno['nome'] ?? '');
        $media = trim($aluno['media'] ?? '');
        $curso = trim($aluno['curso'] ?? '');
        
        if (!in_array($classe, [10, 11, 12])) {
            continue;
        }
        
        if (empty($nome) || empty($media) || empty($curso)) {
            continue;
        }
        
        // Processar nova foto se enviada, senão manter a existente
        $foto_url = null;
        $foto_key = 'classe_foto_' . $classe;
        
        if (isset($_FILES[$foto_key]) && $_FILES[$foto_key]['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES[$foto_key];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed) && $file['size'] <= 5 * 1024 * 1024) {
                $upload = uploadArquivoNuvem($file, 'quadro-honra');
                if ($upload['success']) {
                    $foto_url = $upload['url'];
                }
            }
        } elseif (isset($fotos_existentes[$classe]) && !empty($fotos_existentes[$classe])) {
            // Manter foto existente
            $foto_url = $fotos_existentes[$classe];
        }
        
        $stmt->execute([$quadro_honra_id, $classe, $nome, $media, $curso, $foto_url, $ordem]);
        $ordem++;
    }
    
    // Atualizar citação
    $conteudo = ['citacao' => ['texto' => $citacao_texto, 'referencia' => $citacao_referencia]];
    $stmt = $db->prepare("SELECT id FROM conteudo_paginas WHERE slug = 'quadro-honra'");
    $stmt->execute();
    
    if ($stmt->fetch()) {
        $stmt = $db->prepare("UPDATE conteudo_paginas SET conteudo = ?, ultima_edicao_por = ?, ultima_edicao_em = NOW() WHERE slug = 'quadro-honra'");
        $stmt->execute([json_encode($conteudo, JSON_UNESCAPED_UNICODE), $_SESSION['utilizador_id']]);
    } else {
        $stmt = $db->prepare("INSERT INTO conteudo_paginas (slug, titulo, conteudo, status, ultima_edicao_por, ultima_edicao_em, created_at) VALUES ('quadro-honra', 'Quadro de Honra', ?, 'publicado', ?, NOW(), NOW())");
        $stmt->execute([json_encode($conteudo, JSON_UNESCAPED_UNICODE), $_SESSION['utilizador_id']]);
    }
    
    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Quadro de Honra atualizado com sucesso!']);
    
} catch (PDOException $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
}
?>

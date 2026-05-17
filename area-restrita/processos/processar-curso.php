<?php
/**
 * processar-curso.php — VERSÃO FINAL LIMPA
 * Tabelas saidas_profissionais e projetos foram recriadas do zero,
 * por isso não são necessários quaisquer workarounds para id=0.
 */

error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

while (ob_get_level()) ob_end_clean();
ob_start();

header('Content-Type: application/json; charset=utf-8');

function sendResponse($success, $message, $data = null) {
    while (ob_get_level()) ob_end_clean();
    $response = ['success' => $success, 'message' => $message];
    if ($data) $response = array_merge($response, $data);
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    require_once dirname(__DIR__) . '/../config/index.php';

    if (!isset($_SESSION['utilizador_id'])) {
        sendResponse(false, 'Não autorizado');
    }

    require_once dirname(__DIR__) . '/../config/functions.php';

    $db = getDB();
    $db->exec("SET sql_mode = ''");
} catch(Exception $e) {
    sendResponse(false, 'Erro de conexão: ' . $e->getMessage());
}

$action = $_POST['action'] ?? '';

// Garante que a coluna de competências para card exista.
function garantirColunaCompetenciasCard($db) {
    static $ok = false;
    if ($ok) return;
    $stmt = $db->query("SHOW COLUMNS FROM cursos LIKE 'competencias_card'");
    if (!$stmt->fetch()) {
        $db->exec("ALTER TABLE cursos ADD COLUMN competencias_card TEXT NULL AFTER competencias_descricao");
    }
    $ok = true;
}

// ===================================================
// AUXILIAR: Saídas e Projetos
// ===================================================
function processarSaidasProjetos($db, $curso_id, $post) {

    // --- SAÍDAS ---
    $db->prepare("DELETE FROM saidas_profissionais WHERE curso_id = ?")->execute([$curso_id]);

    $saidas = json_decode($post['saidas'] ?? '[]', true);
    if (!is_array($saidas)) $saidas = [];

    if (!empty($saidas)) {
        $stmt = $db->prepare(
            "INSERT INTO saidas_profissionais (curso_id, titulo, descricao, competencias, imagem_url, ordem)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        foreach ($saidas as $i => $s) {
            if (!empty(trim($s['titulo'] ?? ''))) {
                $comp = is_array($s['competencias'] ?? null)
                    ? json_encode($s['competencias'], JSON_UNESCAPED_UNICODE)
                    : json_encode([]);
                $stmt->execute([
                    $curso_id,
                    $s['titulo'],
                    $s['descricao'] ?? '',
                    $comp,
                    !empty($s['imagem_url']) ? $s['imagem_url'] : null,
                    $i
                ]);
            }
        }
    }

    // --- PROJETOS ---
    $db->prepare("DELETE FROM projetos WHERE curso_id = ?")->execute([$curso_id]);

    $projetos = json_decode($post['projetos'] ?? '[]', true);
    if (!is_array($projetos)) $projetos = [];

    if (!empty($projetos)) {
        $stmt = $db->prepare(
            "INSERT INTO projetos (curso_id, titulo, categoria, descricao, imagem_url, ano, autor, ordem)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        foreach ($projetos as $i => $p) {
            if (!empty(trim($p['titulo'] ?? ''))) {
                $stmt->execute([
                    $curso_id,
                    $p['titulo'],
                    $p['categoria'] ?? 'Geral',
                    $p['descricao'] ?? '',
                    !empty($p['imagem_url']) ? $p['imagem_url'] : null,
                    $p['ano'] ?? date('Y'),
                    $p['autor'] ?? 'Aluno IPIKK',
                    $i
                ]);
            }
        }
    }
}

// ===================================================
// AUXILIAR: PDFs Plano Curricular
// ===================================================
function processarPDFs($db, $curso_id, $files, $post) {
    foreach ([0, 10, 11, 12, 13] as $classe) {
        // Remover se solicitado
        if (!empty($post["remover_pdf_$classe"])) {
            $db->prepare("DELETE FROM plano_curricular WHERE curso_id = ? AND classe = ?")
               ->execute([$curso_id, $classe]);
            continue;
        }
        // Upload novo
        if (isset($files["pdf_$classe"]) && $files["pdf_$classe"]['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($files["pdf_$classe"]['name'], PATHINFO_EXTENSION));
            if ($ext !== 'pdf') continue;
            $upload = uploadArquivoNuvem($files["pdf_$classe"], 'planos-curriculares');
            if ($upload['success']) {
                $url = $upload['url'];
                $existe = $db->prepare("SELECT id FROM plano_curricular WHERE curso_id = ? AND classe = ?");
                $existe->execute([$curso_id, $classe]);
                if ($existe->fetch()) {
                    $db->prepare("UPDATE plano_curricular SET pdf_url = ? WHERE curso_id = ? AND classe = ?")
                       ->execute([$url, $curso_id, $classe]);
                } else {
                    $db->prepare("INSERT INTO plano_curricular (curso_id, classe, pdf_url) VALUES (?, ?, ?)")
                       ->execute([$curso_id, $classe, $url]);
                }
            }
        }
    }
}

// ===================================================
// UPLOAD IMAGEM SAÍDA
// ===================================================
if ($action === 'upload_imagem_saida') {
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
        $upload = uploadArquivoNuvem($_FILES['imagem'], 'cursos/saidas');
        if ($upload['success']) {
            sendResponse(true, 'Upload realizado!', ['url' => $upload['url']]);
        }
        sendResponse(false, $upload['message']);
    }
    sendResponse(false, 'Nenhum arquivo enviado');
}

// ===================================================
// UPLOAD IMAGEM PROJETO
// ===================================================
if ($action === 'upload_imagem_projeto') {
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
        $upload = uploadArquivoNuvem($_FILES['imagem'], 'cursos/projetos');
        if ($upload['success']) {
            sendResponse(true, 'Upload realizado!', ['url' => $upload['url']]);
        }
        sendResponse(false, $upload['message']);
    }
    sendResponse(false, 'Nenhum arquivo enviado');
}

// ===================================================
// CRIAR CURSO
// ===================================================
if ($action === 'create_curso') {
    garantirColunaCompetenciasCard($db);
    $nome                   = trim($_POST['nome'] ?? '');
    $area_id                = (int)($_POST['area_id'] ?? 0);
    $duracao                = $_POST['duracao'] ?? '4 anos';
    $estado                 = $_POST['estado'] ?? 'ativo';
    $cor                    = $_POST['cor'] ?? '#003072';
    $descricao_curta        = $_POST['descricao_curta'] ?? '';
    $sobre_descricao        = $_POST['sobre_descricao'] ?? '';
    $objetivo               = $_POST['objetivo'] ?? '';
    $competencias_descricao = $_POST['competencias_descricao'] ?? '';
    $competencias_card      = $_POST['competencias_card'] ?? '';
    $certificacao_descricao = $_POST['certificacao_descricao'] ?? '';
    $destaque               = (int)($_POST['destaque'] ?? 0);
    $icone_classe           = $_POST['icone_classe'] ?? 'fa-graduation-cap';

    if (empty($nome))   sendResponse(false, 'Nome do curso é obrigatório');
    if ($area_id <= 0)  sendResponse(false, 'Selecione uma área válida');
    if (empty($duracao)) sendResponse(false, 'A duração é obrigatória');

    $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9-]+/', '-', $nome), '-'));

    $imagem_hero = null;
    if (isset($_FILES['imagem_hero']) && $_FILES['imagem_hero']['error'] === UPLOAD_ERR_OK) {
        $upload = uploadArquivoNuvem($_FILES['imagem_hero'], 'cursos/hero');
        if ($upload['success']) {
            $imagem_hero = $upload['url'];
        }
    }

    try {
        $db->beginTransaction();

        $db->prepare("
            INSERT INTO cursos
                (nome, slug, area_id, duracao, nivel, estado, cor, descricao_curta,
                 sobre_descricao, objetivo, competencias_descricao, certificacao_descricao,
                 competencias_card, destaque, icone_classe, imagem_hero)
            VALUES (?, ?, ?, ?, 'Tecnico Medio', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ")->execute([
            $nome, $slug, $area_id, $duracao, $estado, $cor,
            $descricao_curta, $sobre_descricao, $objetivo,
            $competencias_descricao, $certificacao_descricao,
            $competencias_card, $destaque, $icone_classe, $imagem_hero
        ]);

        $curso_id = (int)$db->lastInsertId();

        processarPDFs($db, $curso_id, $_FILES, $_POST);
        processarSaidasProjetos($db, $curso_id, $_POST);

        $db->commit();
        sendResponse(true, 'Curso criado com sucesso!', ['id' => $curso_id]);

    } catch (Exception $e) {
        $db->rollBack();
        sendResponse(false, 'Erro ao criar curso: ' . $e->getMessage());
    }
}

// ===================================================
// ATUALIZAR CURSO
// ===================================================
if ($action === 'update_curso') {
    garantirColunaCompetenciasCard($db);
    $curso_id               = (int)($_POST['curso_id'] ?? 0);
    $nome                   = trim($_POST['nome'] ?? '');
    $area_id                = (int)($_POST['area_id'] ?? 0);
    $duracao                = $_POST['duracao'] ?? '4 anos';
    $estado                 = $_POST['estado'] ?? 'ativo';
    $cor                    = $_POST['cor'] ?? '#003072';
    $descricao_curta        = $_POST['descricao_curta'] ?? '';
    $sobre_descricao        = $_POST['sobre_descricao'] ?? '';
    $objetivo               = $_POST['objetivo'] ?? '';
    $competencias_descricao = $_POST['competencias_descricao'] ?? '';
    $competencias_card      = $_POST['competencias_card'] ?? '';
    $certificacao_descricao = $_POST['certificacao_descricao'] ?? '';
    $destaque               = (int)($_POST['destaque'] ?? 0);
    $icone_classe           = $_POST['icone_classe'] ?? 'fa-graduation-cap';

    if ($curso_id <= 0)  sendResponse(false, 'ID do curso inválido');
    if (empty($nome))    sendResponse(false, 'Nome do curso é obrigatório');
    if ($area_id <= 0)   sendResponse(false, 'Selecione uma área válida');

    $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9-]+/', '-', $nome), '-'));

    // Imagem: novo upload ou manter a existente
    $imagem_hero = null;
    if (isset($_FILES['imagem_hero']) && $_FILES['imagem_hero']['error'] === UPLOAD_ERR_OK) {
        $upload = uploadArquivoNuvem($_FILES['imagem_hero'], 'cursos/hero');
        if ($upload['success']) {
            $imagem_hero = $upload['url'];
        }
    } else {
        $r = $db->prepare("SELECT imagem_hero FROM cursos WHERE id = ?");
        $r->execute([$curso_id]);
        $imagem_hero = $r->fetchColumn();
    }

    try {
        $db->beginTransaction();

        $db->prepare("
            UPDATE cursos SET
                nome=?, slug=?, area_id=?, duracao=?, estado=?, cor=?,
                descricao_curta=?, sobre_descricao=?, objetivo=?,
                competencias_descricao=?, certificacao_descricao=?,
                competencias_card=?, destaque=?, icone_classe=?, imagem_hero=?
            WHERE id=?
        ")->execute([
            $nome, $slug, $area_id, $duracao, $estado, $cor,
            $descricao_curta, $sobre_descricao, $objetivo,
            $competencias_descricao, $certificacao_descricao,
            $competencias_card, $destaque, $icone_classe, $imagem_hero,
            $curso_id
        ]);

        processarPDFs($db, $curso_id, $_FILES, $_POST);
        processarSaidasProjetos($db, $curso_id, $_POST);

        $db->commit();
        sendResponse(true, 'Curso atualizado com sucesso!', ['id' => $curso_id]);

    } catch (Exception $e) {
        $db->rollBack();
        sendResponse(false, 'Erro ao atualizar curso: ' . $e->getMessage());
    }
}

// ===================================================
// ELIMINAR CURSO
// ===================================================
if ($action === 'delete_curso') {
    $curso_id = (int)($_POST['curso_id'] ?? 0);
    if ($curso_id <= 0) sendResponse(false, 'ID inválido');

    try {
        $db->beginTransaction();
        $db->prepare("DELETE FROM plano_curricular WHERE curso_id = ?")->execute([$curso_id]);
        $db->prepare("DELETE FROM saidas_profissionais WHERE curso_id = ?")->execute([$curso_id]);
        $db->prepare("DELETE FROM projetos WHERE curso_id = ?")->execute([$curso_id]);
        $db->prepare("DELETE FROM depoimentos WHERE curso_id = ?")->execute([$curso_id]);
        $db->prepare("DELETE FROM cursos WHERE id = ?")->execute([$curso_id]);
        $db->commit();
        sendResponse(true, 'Curso eliminado com sucesso!');
    } catch (Exception $e) {
        $db->rollBack();
        sendResponse(false, 'Erro ao eliminar: ' . $e->getMessage());
    }
}

// ===================================================
// CRIAR ÁREA
// ===================================================
if ($action === 'create_area') {
    $nome               = trim($_POST['nome'] ?? '');
    $descricao_curta    = $_POST['descricao_curta'] ?? '';
    $descricao_completa = $_POST['descricao_completa'] ?? '';
    $cor_primaria       = $_POST['cor_primaria'] ?? '#6c757d';
    $icone_classe       = $_POST['icone_classe'] ?? 'fa-layer-group';
    $ordem              = (int)($_POST['ordem'] ?? 0);
    $ativo              = (int)($_POST['ativo'] ?? 1);

    if (empty($nome)) sendResponse(false, 'Nome da área é obrigatório');

    $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9-]+/', '-', $nome), '-'));

    $imagem_url = null;
    if (isset($_FILES['imagem_url']) && $_FILES['imagem_url']['error'] === UPLOAD_ERR_OK) {
        $upload = uploadArquivoNuvem($_FILES['imagem_url'], 'areas');
        if ($upload['success']) {
            $imagem_url = $upload['url'];
        }
    }

    $stmt = $db->prepare("
        INSERT INTO areas (nome, slug, descricao_curta, descricao_completa, cor_primaria, icone_classe, ordem, ativo, imagem_url)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $result = $stmt->execute([$nome, $slug, $descricao_curta, $descricao_completa, $cor_primaria, $icone_classe, $ordem, $ativo, $imagem_url]);
    sendResponse($result, $result ? 'Área criada com sucesso!' : 'Erro ao criar área');
}

// ===================================================
// ATUALIZAR ÁREA
// ===================================================
if ($action === 'update_area') {
    $area_id            = (int)($_POST['area_id'] ?? 0);
    $nome               = trim($_POST['nome'] ?? '');
    $descricao_curta    = $_POST['descricao_curta'] ?? '';
    $descricao_completa = $_POST['descricao_completa'] ?? '';
    $cor_primaria       = $_POST['cor_primaria'] ?? '#6c757d';
    $icone_classe       = $_POST['icone_classe'] ?? 'fa-layer-group';
    $ordem              = (int)($_POST['ordem'] ?? 0);
    $ativo              = (int)($_POST['ativo'] ?? 1);

    $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9-]+/', '-', $nome), '-'));

    $imagem_url = null;
    if (isset($_FILES['imagem_url']) && $_FILES['imagem_url']['error'] === UPLOAD_ERR_OK) {
        $upload = uploadArquivoNuvem($_FILES['imagem_url'], 'areas');
        if ($upload['success']) {
            $imagem_url = $upload['url'];
        }
    } else {
        $r = $db->prepare("SELECT imagem_url FROM areas WHERE id = ?");
        $r->execute([$area_id]);
        $imagem_url = $r->fetchColumn();
    }

    $stmt = $db->prepare("
        UPDATE areas SET nome=?, slug=?, descricao_curta=?, descricao_completa=?,
        cor_primaria=?, icone_classe=?, ordem=?, ativo=?, imagem_url=?
        WHERE id=?
    ");
    $result = $stmt->execute([$nome, $slug, $descricao_curta, $descricao_completa, $cor_primaria, $icone_classe, $ordem, $ativo, $imagem_url, $area_id]);
    sendResponse($result, $result ? 'Área atualizada com sucesso!' : 'Erro ao atualizar área');
}

// ===================================================
// ELIMINAR ÁREA
// ===================================================
if ($action === 'delete_area') {
    $area_id = (int)($_POST['area_id'] ?? 0);

    $r = $db->prepare("SELECT COUNT(*) FROM cursos WHERE area_id = ?");
    $r->execute([$area_id]);
    if ($r->fetchColumn() > 0) {
        sendResponse(false, 'Esta área possui cursos. Elimine os cursos primeiro.');
    }

    $stmt   = $db->prepare("DELETE FROM areas WHERE id = ?");
    $result = $stmt->execute([$area_id]);
    sendResponse($result, $result ? 'Área eliminada com sucesso!' : 'Erro ao eliminar área');
}

sendResponse(false, 'Ação não reconhecida: ' . $action);
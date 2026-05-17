<?php
/**
 * processar-vagas.php
 * Processa as requisicoes AJAX para salvar as vagas por curso.
 */

$base_path = dirname(__DIR__, 2);
require_once $base_path . '/config/index.php';

session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['utilizador_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sessao nao iniciada.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metodo nao permitido.']);
    exit;
}

$action = $_POST['action'] ?? '';

if ($action !== 'salvar_vagas') {
    echo json_encode(['success' => false, 'message' => 'Acao invalida.']);
    exit;
}

$vagas_json = $_POST['vagas'] ?? '[]';
$ano_lectivo = $_POST['ano_lectivo'] ?? '';

if (empty($ano_lectivo)) {
    $ano_lectivo = date('Y') . '/' . (date('Y') + 1);
}

$vagas = json_decode($vagas_json, true);

if (!is_array($vagas)) {
    echo json_encode(['success' => false, 'message' => 'Dados de vagas invalidos.']);
    exit;
}

$db = getDB();

try {
    $db->beginTransaction();

    $stmt_delete = $db->prepare("DELETE FROM vagas_curso WHERE ano_lectivo = ?");
    $stmt_delete->execute([$ano_lectivo]);

    $stmt_insert = $db->prepare("INSERT INTO vagas_curso (curso_id, ano_lectivo, vagas_disponiveis) VALUES (?, ?, ?)");

    foreach ($vagas as $vaga) {
        $curso_id = (int)($vaga['curso_id'] ?? 0);
        $vagas_qty = (int)($vaga['vagas'] ?? 0);

        if ($curso_id > 0 && $vagas_qty > 0) {
            $stmt_insert->execute([$curso_id, $ano_lectivo, $vagas_qty]);
        }
    }

    $db->commit();

    echo json_encode(['success' => true, 'message' => 'Vagas guardadas com sucesso!']);
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => 'Erro ao guardar vagas: ' . $e->getMessage()]);
}
?>
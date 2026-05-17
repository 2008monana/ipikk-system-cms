<?php
/**
 * Processar resposta a mensagens de contacto - Área Restrita IPIKK
 */

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/../config/index.php';
require_once dirname(__DIR__) . '/includes/verificar-permissao.php';

try {
    if (!isset($_SESSION['utilizador_id'])) {
        echo json_encode(['success' => false, 'message' => 'Não autorizado']);
        exit;
    }

    verificarPermissao('contactos');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Método inválido']);
        exit;
    }

    $db = getDB();
    $mensagem_id = (int)($_POST['message_id'] ?? 0);
    $resposta = trim($_POST['resposta'] ?? '');
    $utilizador_id = (int)$_SESSION['utilizador_id'];

    if ($mensagem_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Mensagem inválida']);
        exit;
    }

    if ($resposta === '') {
        echo json_encode(['success' => false, 'message' => 'Escreva a resposta antes de enviar.']);
        exit;
    }

    $stmt = $db->prepare('SELECT id, nome, email, assunto, mensagem, data_envio FROM mensagens WHERE id = ?');
    $stmt->execute([$mensagem_id]);
    $mensagem = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$mensagem) {
        echo json_encode(['success' => false, 'message' => 'Mensagem não encontrada']);
        exit;
    }

    if (!filter_var($mensagem['email'], FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'O email do remetente é inválido.']);
        exit;
    }

    $assunto_email = 'RE: ' . ($mensagem['assunto'] ?: 'Mensagem de Contacto');
    $resposta_html = nl2br(htmlspecialchars($resposta, ENT_QUOTES, 'UTF-8'));
    $mensagem_original_html = nl2br(htmlspecialchars($mensagem['mensagem'], ENT_QUOTES, 'UTF-8'));
    $nome = htmlspecialchars($mensagem['nome'], ENT_QUOTES, 'UTF-8');
    $assunto_original = htmlspecialchars($mensagem['assunto'], ENT_QUOTES, 'UTF-8');
    $data_original = !empty($mensagem['data_envio']) ? date('d/m/Y H:i', strtotime($mensagem['data_envio'])) : '';

    $corpo = "
    <!DOCTYPE html>
    <html lang='pt'>
    <head>
        <meta charset='UTF-8'>
        <title>{$assunto_email}</title>
    </head>
    <body style='margin:0;padding:0;background:#f5f7fa;font-family:Arial,sans-serif;color:#2c3e50;'>
        <div style='max-width:680px;margin:0 auto;padding:24px;'>
            <div style='background:#003072;color:#fff;padding:22px 26px;border-radius:14px 14px 0 0;'>
                <h1 style='margin:0;font-size:22px;'>Resposta do IPIKK</h1>
            </div>
            <div style='background:#ffffff;border:1px solid #e5e9f0;border-top:none;padding:28px;border-radius:0 0 14px 14px;'>
                <p>Olá <strong>{$nome}</strong>,</p>
                <div style='font-size:15px;line-height:1.7;margin:20px 0;'>{$resposta_html}</div>
                <hr style='border:none;border-top:1px solid #dce3ec;margin:28px 0;'>
                <p style='font-size:13px;color:#64748b;margin-bottom:10px;'><strong>Mensagem original</strong> — {$data_original}</p>
                <div style='background:#f8fafc;border-left:4px solid #0a9396;padding:16px;border-radius:10px;'>
                    <p style='margin:0 0 8px;'><strong>Assunto:</strong> {$assunto_original}</p>
                    <div style='font-size:14px;line-height:1.6;color:#475569;'>{$mensagem_original_html}</div>
                </div>
                <p style='margin-top:26px;'>Atenciosamente,<br><strong>Equipa IPIKK</strong></p>
            </div>
        </div>
    </body>
    </html>";

    $envio = enviarEmail($mensagem['email'], $mensagem['nome'], $assunto_email, $corpo);

    if (!$envio['success']) {
        echo json_encode(['success' => false, 'message' => $envio['message'] ?? 'Erro ao enviar email']);
        exit;
    }

    $stmt = $db->prepare('UPDATE mensagens SET respondida = 1, lida = 1, data_resposta = NOW(), respondido_por = ?, resposta_texto = ? WHERE id = ?');
    $stmt->execute([$utilizador_id, $resposta, $mensagem_id]);

    if (function_exists('registrarLog')) {
        registrarLog('respondeu', 'mensagens', $mensagem_id, 'Resposta enviada por email para ' . $mensagem['email']);
    }

    echo json_encode(['success' => true, 'message' => 'Resposta enviada com sucesso.']);
} catch (Throwable $e) {
    error_log('Erro ao processar resposta de contacto: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro ao enviar resposta. Tente novamente mais tarde.']);
}

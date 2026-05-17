<?php
/**
 * Configuração de Email - IPIKK
 * Usando PHPMailer instalado manualmente
 */

// Carregar autoloader manual
require_once __DIR__ . '/../vendor/autoload.php';
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * Envia um email usando PHPMailer
 */
function enviarEmail($para, $nome, $assunto, $corpo) {
    // Buscar configurações SMTP do banco
    $db = getDB();
    $stmt = $db->query("SELECT smtp_host, smtp_porta, smtp_seguranca, smtp_email, smtp_senha FROM configuracoes WHERE id = 1");
    $config = $stmt->fetch();
    
    // Configurações padrão
    $smtp_host = trim((string)($config['smtp_host'] ?? 'smtp.gmail.com'));
    $smtp_porta = (int)($config['smtp_porta'] ?? 587);
    $smtp_seguranca = strtolower(trim((string)($config['smtp_seguranca'] ?? 'tls')));
    $smtp_email = trim((string)($config['smtp_email'] ?? 'no-reply@ipikk.ao'));
    $smtp_senha = trim((string)($config['smtp_senha'] ?? ''));

    // Gmail usa App Password (16 chars) e frequentemente é salvo com espaços visuais no painel.
    // Exemplo: "abcd efgh ijkl mnop" -> "abcdefghijklmnop"
    if (stripos($smtp_host, 'gmail') !== false) {
        $smtp_senha = str_replace(' ', '', $smtp_senha);
    }

    if ($smtp_email === '' || $smtp_senha === '') {
        return [
            'success' => false,
            'message' => 'SMTP não configurado. Defina email e senha SMTP em Configurações.'
        ];
    }
    
    $mail = new PHPMailer(true);
    
    try {
        // Configurações do servidor
        $mail->isSMTP();
        $mail->Host       = $smtp_host;
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtp_email;
        $mail->Password   = $smtp_senha;
        
        // Configuração de segurança
        if ($smtp_seguranca === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } elseif ($smtp_seguranca === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        }
        $mail->Port       = $smtp_porta;
        
        // Desabilitar verificação SSL (para testes locais)
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        
        // Remetente e destinatário
        $mail->setFrom($smtp_email, 'IPIKK - Instituto Politécnico Industrial');
        $mail->addAddress($para, $nome);
        
        // Conteúdo do email
        $mail->CharSet = 'UTF-8';
        $mail->isHTML(true);
        $mail->Subject = $assunto;
        $mail->Body    = $corpo;
        $mail->AltBody = strip_tags($corpo);
        
        $mail->send();
        return ['success' => true, 'message' => 'Email enviado com sucesso'];
        
    } catch (Exception $e) {
        error_log("Erro ao enviar email (SMTP): " . $mail->ErrorInfo);
        $erro_smtp = $mail->ErrorInfo;

        // Fallback: tentar envio local (mail/sendmail) para não bloquear o fluxo no painel.
        try {
            $mail_fallback = new PHPMailer(true);
            $mail_fallback->isMail();
            $mail_fallback->CharSet = 'UTF-8';
            $mail_fallback->setFrom($smtp_email, 'IPIKK - Instituto Politécnico Industrial');
            $mail_fallback->addAddress($para, $nome);
            $mail_fallback->isHTML(true);
            $mail_fallback->Subject = $assunto;
            $mail_fallback->Body = $corpo;
            $mail_fallback->AltBody = strip_tags($corpo);
            $mail_fallback->send();

            return [
                'success' => true,
                'message' => 'Email enviado via fallback local (mail). Verifique as credenciais SMTP no painel.'
            ];
        } catch (Exception $e2) {
            error_log("Erro ao enviar email (fallback mail): " . $mail_fallback->ErrorInfo);
            $msg = 'Erro ao enviar email: ' . $erro_smtp;
            if (stripos($erro_smtp, 'authenticate') !== false) {
                $msg .= ' | Verifique utilizador/senha SMTP e App Password do provedor.';
            }
            $msg .= ' | Fallback mail também falhou: ' . $mail_fallback->ErrorInfo;
            return ['success' => false, 'message' => $msg];
        }
    }
}

/**
 * Envia email de recuperação de senha
 */
function enviarEmailRecuperacao($email, $nome, $token) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
    $link = $scheme . '://' . $host . $basePath . '/redefinir-senha.php?token=' . urlencode($token);
    
    $corpo = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Recuperação de Senha - IPIKK</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #003072; color: white; padding: 30px 20px; text-align: center; border-radius: 10px 10px 0 0; }
            .logo { max-width: 110px; margin: 0 auto 14px auto; display: block; background: #ffffff; border-radius: 12px; padding: 8px; }
            .header h1 { margin: 0; font-size: 24px; }
            .content { background: #ffffff; padding: 30px; border: 1px solid #e0e0e0; border-top: none; border-radius: 0 0 10px 10px; }
            .btn { display: inline-block; padding: 14px 28px; background: #0a9396; color: white; text-decoration: none; border-radius: 8px; margin: 20px 0; font-weight: bold; }
            .btn:hover { background: #0a7b7e; }
            .footer { font-size: 12px; color: #666; text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e0e0e0; }
            .aviso { background: #f8f9fa; padding: 15px; border-left: 4px solid #ffc107; margin: 20px 0; font-size: 13px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <img src='{$logo}' alt='Logotipo IPIKK' class='logo'>
                <h1>Recuperação de Senha</h1>
            </div>
            <div class='content'>
                <p>Olá <strong>" . htmlspecialchars($nome) . "</strong>,</p>
                <p>Recebemos uma solicitação para redefinir sua senha no site do <strong>IPIKK</strong>.</p>
                <p>Clique no botão abaixo para criar uma nova senha:</p>
                <p style='text-align: center;'>
                    <a href='{$link}' class='btn' style='color: white;'>Redefinir Senha</a>
                </p>
                <p>Se o botão não funcionar, copie e cole o link abaixo no seu navegador:</p>
                <p><a href='{$link}' style='word-break: break-all;'>{$link}</a></p>
                <div class='aviso'>
                    <strong> Importante:</strong>
                    <ul style='margin: 10px 0 0 20px;'>
                        <li>Este link é válido por <strong>1 hora</strong></li>
                        <li>Se você não solicitou esta alteração, ignore este e-mail</li>
                        <li>Por segurança, não compartilhe este link com ninguém</li>
                    </ul>
                </div>
                <p>Atenciosamente,<br>
                <strong>Equipe IPIKK</strong></p>
            </div>
            <div class='footer'>
                <p>Instituto Médio Politécnico Industrial do Kilamba Kiaxi<br>
                933 096 705 | geral@ipikk.ao |  www.ipikk.ao</p>
                <p>© " . date('Y') . " IPIKK - Todos os direitos reservados</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return enviarEmail($email, $nome, 'Recuperação de Senha - IPIKK', $corpo);
}

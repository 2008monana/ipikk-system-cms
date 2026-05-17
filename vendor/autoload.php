<?php
/**
 * Autoloader manual para PHPMailer
 * Sem necessidade de Composer
 */

// Caminho para os arquivos do PHPMailer (compatível com diferentes maiúsculas/minúsculas)
$caminhos_possiveis = [
    __DIR__ . '/PHPMailer/PHPMailer/src/',
    __DIR__ . '/phpmailer/phpmailer/src/',
    __DIR__ . '/PHPMailer/src/',
    __DIR__ . '/phpmailer/src/',
    __DIR__ . '/',
];

$phpmailer_path = null;
foreach ($caminhos_possiveis as $path) {
    if (is_dir($path) && file_exists($path . 'PHPMailer.php') && file_exists($path . 'SMTP.php') && file_exists($path . 'Exception.php')) {
        $phpmailer_path = $path;
        break;
    }
}

if ($phpmailer_path === null) {
    throw new RuntimeException('PHPMailer não encontrado em vendor/. Verifique a estrutura da pasta vendor.');
}

// Incluir os arquivos necessários
require_once $phpmailer_path . 'Exception.php';
require_once $phpmailer_path . 'PHPMailer.php';
require_once $phpmailer_path . 'SMTP.php';

// Usar os namespaces
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

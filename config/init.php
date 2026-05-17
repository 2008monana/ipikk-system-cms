<?php
/**
 * Inicialização da Área Restrita - IPIKK
 */

// Definir caminhos
define('AREA_RESTRITA_PATH', dirname(__DIR__));
define('PUBLIC_PATH', dirname(AREA_RESTRITA_PATH) . '/area-publica');

// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Carregar configurações do sistema
require_once PUBLIC_PATH . '/../config/database.php';
require_once PUBLIC_PATH . '/../config/functions.php';
require_once PUBLIC_PATH . '/../config/constants.php';

// Buscar configurações do site
$db = getDB();
$config = $db->query("SELECT * FROM configuracoes WHERE id = 1")->fetch();

// Verificar se está logado (exceto na página de login)
$pagina_atual = basename($_SERVER['PHP_SELF']);
$paginas_publicas = ['area-restrita.php', 'processar-login.php', 'recuperar-senha.php'];

if (!in_array($pagina_atual, $paginas_publicas)) {
    if (!isset($_SESSION['utilizador_id'])) {
        header('Location: area-restrita.php');
        exit;
    }
    
    // Buscar dados do usuário logado
    $stmt = $db->prepare("SELECT id, nome, email, foto_url, avatar_icone, nivel, permissoes FROM utilizadores WHERE id = ?");
    $stmt->execute([$_SESSION['utilizador_id']]);
    $usuario_logado = $stmt->fetch();
    
    if (!$usuario_logado) {
        session_destroy();
        header('Location: area-restrita.php');
        exit;
    }
    
    // Verificar se a conta está ativa
    $stmt = $db->prepare("SELECT ativo FROM utilizadores WHERE id = ?");
    $stmt->execute([$_SESSION['utilizador_id']]);
    $ativo = $stmt->fetchColumn();
    
    if (!$ativo) {
        session_destroy();
        header('Location: area-restrita.php?erro=conta_desativada');
        exit;
    }
}

// Definir variáveis globais para as páginas
$titulo_pagina = $titulo_pagina ?? 'Dashboard';
$css_especifico = $css_especifico ?? 'admin-dashboard.css';
$pagina_atual = basename($_SERVER['PHP_SELF']);
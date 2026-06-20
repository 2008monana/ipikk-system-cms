<?php
/**
 * Arquivo de inicialização - IPIKK
 * Deve ser incluído no início de todos os arquivos PHP
 */

// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Definir timezone
date_default_timezone_set('Africa/Luanda');

// Carregar constantes
require_once __DIR__ . '/constants.php';

// Carregar funções
require_once __DIR__ . '/functions.php';

// Carregar conexão com banco de dados
require_once __DIR__ . '/database.php';

require_once __DIR__ . '/email.php';
require_once __DIR__ . '/translation.php';

// ============================================
// CONTAGEM DE VISITANTES (APENAS SITE PÚBLICO)
// ============================================

// Verificar se é uma página pública (não é área restrita)
$arquivo_atual = basename($_SERVER['PHP_SELF']);
$excecoes_visitante = ['site-manutencao.php', 'area-restrita.php', 'processar-login.php', 'admin-'];

$is_publica = true;
foreach ($excecoes_visitante as $exc) {
    if (strpos($arquivo_atual, $exc) !== false) {
        $is_publica = false;
        break;
    }
}

// Contar visitante apenas em páginas públicas
if ($is_publica && function_exists('contarVisitante')) {
    contarVisitante();
}

// ============================================
// VERIFICAR MODO DE MANUTENÇÃO
// ============================================

/**
 * Função para verificar se o utilizador logado é administrador
 * @return bool
 */
function isAdminLoggedIn() {
    return isset($_SESSION['utilizador_id']) && 
           isset($_SESSION['utilizador_nivel']) && 
           $_SESSION['utilizador_nivel'] === 'admin';
}

// Páginas que NUNCA devem ser bloqueadas (login, admin, etc.)
$excecoes_manutencao = [
    'site-manutencao.php',      // própria página de manutenção
    'area-restrita.php',        // página de login
    'processar-login.php',      // processamento de login
    'logout.php',               // logout
    'admin-dashboard.php',      // admin pode aceder
    'admin-',                   // qualquer página admin (prefixo)
];

$is_excecao = false;
foreach ($excecoes_manutencao as $exc) {
    if (strpos($arquivo_atual, $exc) !== false) {
        $is_excecao = true;
        break;
    }
}

// Verificar se está em modo manutenção
if (!$is_excecao) {
    try {
        $db = getDB();
        $stmt = $db->query("SELECT modo_manutencao FROM configuracoes WHERE id = 1");
        $config_manutencao = $stmt->fetch();
        
        if ($config_manutencao && $config_manutencao['modo_manutencao'] == 1) {
            // Se NÃO for administrador logado, redirecionar para manutenção
            if (!isAdminLoggedIn()) {
                header('Location: site-manutencao.php');
                exit;
            }
            // Se for administrador, continua normalmente (sem redirecionamento)
            // Opcional: adicionar um aviso no admin que o site está em manutenção
            if (function_exists('setFlash')) {
                // Não mostrar flash em todas as páginas para não poluir
                if (!isset($_SESSION['manutencao_aviso_mostrado'])) {
                    $_SESSION['manutencao_aviso_mostrado'] = true;
                }
            }
        }
    } catch (Exception $e) {
        // Se houver erro na BD, não bloquear o site
        error_log("Erro ao verificar modo manutenção: " . $e->getMessage());
    }
}
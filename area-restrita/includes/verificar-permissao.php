<?php
/**
 * Função para verificar permissões de acesso às páginas
 */

function verificarPermissao($permissao_necessaria) {
    if (!isset($_SESSION['utilizador_id'])) {
        header('Location: area-restrita.php');
        exit;
    }
    
    $nivel = $_SESSION['utilizador_nivel'] ?? 'editor';
    
    // Admin tem acesso a tudo
    if ($nivel === 'admin') {
        return true;
    }
    
    $permissoes = isset($_SESSION['utilizador_permissoes']) 
        ? (is_array($_SESSION['utilizador_permissoes']) 
            ? $_SESSION['utilizador_permissoes'] 
            : json_decode($_SESSION['utilizador_permissoes'], true))
        : [];
    
    if (!is_array($permissoes)) {
        $permissoes = [];
    }
    
    // Se tiver permissão wildcard (*) ou a permissão específica
    if (in_array('*', $permissoes) || in_array($permissao_necessaria, $permissoes)) {
        return true;
    }
    
    // Redirecionar para página de perfil ou dashboard com erro
    if (function_exists('setFlash')) {
        setFlash('error', 'Você não tem permissão para aceder a esta página.');
    }
    
    // Redirecionar para a primeira página permitida ou perfil
    $mapa_permissoes = [
        'noticias' => 'admin-noticias.php',
        'galeria' => 'admin-galeria.php',
        'inscricoes' => 'admin-inscricoes.php',
        'contactos' => 'admin-contactos.php',
        'utilizadores' => 'admin-utilizadores.php',
        'configuracoes' => 'admin-configuracoes.php',
        'logs' => 'admin-logs.php',
        'lixeira' => 'admin-lixeira.php',
        'conteudo_site' => 'admin-inicio.php',
        'oferta_formativa' => 'admin-cursos.php'
    ];
    
    foreach ($mapa_permissoes as $perm => $pagina) {
        if (in_array($perm, $permissoes)) {
            header("Location: $pagina");
            exit;
        }
    }
    
    header('Location: admin-perfil.php');
    exit;
}
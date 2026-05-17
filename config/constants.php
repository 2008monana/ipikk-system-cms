<?php
/**
 * Constantes do Sistema - IPIKK
 */

// ============================================
// CAMINHOS DO SISTEMA
// ============================================

// Definir BASE_PATH primeiro
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

// Definir BASE_URL
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/ipikk');
}

// Definir UPLOAD_PATH usando BASE_PATH já definido
if (!defined('UPLOAD_PATH')) {
    define('UPLOAD_PATH', BASE_PATH . '/uploads');
}

if (!defined('UPLOAD_URL')) {
    define('UPLOAD_URL', BASE_URL . '/uploads');
}

// Subpastas de upload
if (!defined('UPLOAD_GALERIA')) {
    define('UPLOAD_GALERIA', UPLOAD_PATH . '/galeria');
}

if (!defined('UPLOAD_NOTICIAS')) {
    define('UPLOAD_NOTICIAS', UPLOAD_PATH . '/noticias');
}

if (!defined('UPLOAD_CURSOS')) {
    define('UPLOAD_CURSOS', UPLOAD_PATH . '/cursos');
}

if (!defined('UPLOAD_DOCUMENTOS')) {
    define('UPLOAD_DOCUMENTOS', UPLOAD_PATH . '/documentos');
}

if (!defined('UPLOAD_PERFIS')) {
    define('UPLOAD_PERFIS', UPLOAD_PATH . '/perfis');
}

// ============================================
// LIMITES DE ARQUIVOS
// ============================================

if (!defined('MAX_FILE_SIZE_IMAGE')) {
    define('MAX_FILE_SIZE_IMAGE', 5 * 1024 * 1024);
}

if (!defined('MAX_FILE_SIZE_VIDEO')) {
    define('MAX_FILE_SIZE_VIDEO', 50 * 1024 * 1024);
}

if (!defined('MAX_FILE_SIZE_PDF')) {
    define('MAX_FILE_SIZE_PDF', 10 * 1024 * 1024);
}

// ============================================
// TIPOS DE ARQUIVOS PERMITIDOS
// ============================================

if (!isset($tipos_imagem)) {
    $tipos_imagem = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
}

if (!isset($tipos_video)) {
    $tipos_video = ['video/mp4', 'video/webm', 'video/ogg'];
}

if (!isset($tipos_pdf)) {
    $tipos_pdf = ['application/pdf'];
}

// ============================================
// PAGINAÇÃO
// ============================================

if (!defined('ITENS_POR_PAGINA')) {
    define('ITENS_POR_PAGINA', 10);
}

if (!defined('ITENS_POR_PAGINA_ADMIN')) {
    define('ITENS_POR_PAGINA_ADMIN', 25);
}

// ============================================
// PERMISSÕES
// ============================================

if (!defined('PERMISSAO_NOTICIAS')) {
    define('PERMISSAO_NOTICIAS', 1);
}

if (!defined('PERMISSAO_CURSOS')) {
    define('PERMISSAO_CURSOS', 2);
}

if (!defined('PERMISSAO_CONTACTOS')) {
    define('PERMISSAO_CONTACTOS', 3);
}

if (!defined('PERMISSAO_UTILIZADORES')) {
    define('PERMISSAO_UTILIZADORES', 4);
}

if (!defined('PERMISSAO_CONFIGURACOES')) {
    define('PERMISSAO_CONFIGURACOES', 5);
}

if (!defined('PERMISSAO_RELATORIOS')) {
    define('PERMISSAO_RELATORIOS', 6);
}

// ============================================
// ESTADOS
// ============================================

if (!defined('ESTADO_ATIVO')) {
    define('ESTADO_ATIVO', 'ativo');
}

if (!defined('ESTADO_PAUSADO')) {
    define('ESTADO_PAUSADO', 'pausado');
}

if (!defined('ESTADO_ARQUIVADO')) {
    define('ESTADO_ARQUIVADO', 'arquivado');
}

if (!defined('NOTICIA_PUBLICADA')) {
    define('NOTICIA_PUBLICADA', 'publicada');
}

if (!defined('NOTICIA_RASCUNHO')) {
    define('NOTICIA_RASCUNHO', 'rascunho');
}

if (!defined('NOTICIA_ARQUIVADA')) {
    define('NOTICIA_ARQUIVADA', 'arquivada');
}

if (!defined('MENSAGEM_NAO_LIDA')) {
    define('MENSAGEM_NAO_LIDA', 0);
}

if (!defined('MENSAGEM_LIDA')) {
    define('MENSAGEM_LIDA', 1);
}

if (!defined('MENSAGEM_RESPONDIDA')) {
    define('MENSAGEM_RESPONDIDA', 1);
}
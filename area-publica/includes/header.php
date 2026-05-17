<?php
/**
 * Header compartilhado da Área Pública - IPIKK (COM ESPAÇAMENTO CORRIGIDO)
 */

if (!isset($config) || !is_array($config)) {
    $config = getDB()->query("SELECT * FROM configuracoes WHERE id = 1")->fetch();
}

if (!isset($areas) || !is_array($areas)) {
    $areas = getDB()->query("SELECT * FROM areas WHERE ativo = 1 ORDER BY ordem")->fetchAll();
}

if (!isset($todos_cursos) || !is_array($todos_cursos)) {
    $todos_cursos = getDB()->query("SELECT * FROM cursos WHERE estado = 'ativo' ORDER BY nome")->fetchAll();
}

if (!isset($cursos_por_area) || !is_array($cursos_por_area)) {
    $cursos_por_area = [];
    foreach ($todos_cursos as $curso_item) {
        $cursos_por_area[$curso_item['area_id']][] = $curso_item;
    }
}

if (!isset($link_inscricao)) {
    $status_inscricoes = getDB()->query("SELECT status FROM controle_inscricoes WHERE id = 1")->fetch();
    $link_inscricao = ($status_inscricoes && $status_inscricoes['status'] === 'abertas') ? 'inscricoes.php' : 'inscricoes-indisponiveis.php';
}

// ========== DETECTAR PÁGINA ATUAL ==========
$arquivo_atual = basename($_SERVER['PHP_SELF']);
$slug_atual = $_GET['slug'] ?? '';

// Verificar páginas principais
$is_inicio = ($arquivo_atual === 'index.php');
$is_oferta = ($arquivo_atual === 'oferta-formativa.php');
$is_area = ($arquivo_atual === 'area.php');
$is_curso = ($arquivo_atual === 'curso.php');
$is_noticias = ($arquivo_atual === 'noticias.php');
$is_galeria = ($arquivo_atual === 'galeria.php');
$is_contactos = ($arquivo_atual === 'contatos.php');

// Páginas do dropdown "Sobre"
$sobre_pages = [
    'sobre-nos.php', 'diretor.php', 'orgaos-diretivos.php', 
    'ex-diretores.php', 'normativos.php', 'percurso.php', 
    'quadro-honra.php', 'funcionario-destacado.php', 'escolas-afiliadas.php'
];
$is_sobre = in_array($arquivo_atual, $sobre_pages);

// Se estiver em área ou curso, também marca Oferta Formativa como ativo
$is_oferta_active = ($is_oferta || $is_area || $is_curso);
$is_sobre_active = $is_sobre;
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Fontes e Ícones -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <!-- CSS Principal -->
    <link rel="stylesheet" href="css/header-footer.css">
    
    <style>
        /* === VARIÁVEIS CSS === */
        :root {
            --ciano-suave: #008bb5;
            --ciano-claro: #e6f7ff;
            --ciano-escuro-suave: #006d8f;
            --gradiente-suave: linear-gradient(135deg, #008bb5 0%, #006d8f 100%);
            --cinza-claro-bg: #f5f8fa;
            --texto-principal: #2c3e50;
            --texto-secundario: #5a6b7a;
            --branco: #ffffff;
            --transition: all 0.3s ease;
            --sidebar-font-size: 0.93rem;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background: var(--cinza-claro-bg);
            color: var(--texto-principal);
            line-height: 1.6;
        }

        /* ===== BARRA SUPERIOR ===== */
        .barra-superior {
            background: var(--gradiente-suave);
            color: var(--branco);
            padding: 10px 0;
        }

        .conteudo-superior {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .info-instituto {
            font-size: 0.9rem;
            font-weight: 500;
        }

        .social-traducao {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .icones-sociais {
            display: flex;
            gap: 12px;
        }

        .icone-social {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
        }

        .icone-social:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }

        .icone-social i {
            color: var(--branco);
            font-size: 15px;
        }

        .seletor-idioma {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            padding: 6px 16px;
            border-radius: 30px;
            background: rgba(255, 255, 255, 0.15);
            position: relative;
            z-index: 1011;
        }

        .dropdown-idioma {
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 12px;
            background: var(--branco);
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            min-width: 160px;
            overflow: hidden;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 1012;
        }

        .seletor-idioma:hover .dropdown-idioma {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .opcao-idioma {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            color: var(--texto-principal);
            text-decoration: none;
            transition: var(--transition);
        }

        .opcao-idioma:hover {
            background: var(--ciano-claro);
            color: var(--ciano-suave);
        }

        /* ===== CABEÇALHO ===== */
        .cabecalho {
            background: var(--branco);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .conteudo-cabecalho {
            max-width: 1400px;
            margin: 0 auto;
            padding: 8px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            width: 70px;
            height: 70px;
        }

        .logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        /* ===== NAVEGAÇÃO DESKTOP ===== */
        .menu-navegacao {
            display: flex;
            list-style: none;
            gap: 4px;
            align-items: center;
        }

        .item-navegacao {
            position: relative;
        }

        .link-navegacao {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            color: var(--texto-secundario);
            font-weight: 500;
            font-size: 0.95rem;
            border-radius: 8px;
            transition: var(--transition);
        }

        .link-navegacao:hover {
            background: var(--ciano-claro);
            color: var(--ciano-suave);
        }

        .item-navegacao.ativo .link-navegacao {
            background: var(--ciano-claro);
            color: var(--ciano-suave);
            font-weight: 600;
        }

        /* ===== MENUS SUSPENSOS DESKTOP (CORRIGIDO - ESPAÇAMENTO VISÍVEL) ===== */
        .menu-suspenso {
            position: relative;
        }

        .conteudo-suspenso {
            position: absolute;
            top: 100%;
            left: 0;
            min-width: 260px;
            background: var(--branco);
            border-radius: 16px;
            border: 1px solid rgba(0, 139, 181, 0.1);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.05);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-5px);
            transition: var(--transition);
            z-index: 1000;
            padding: 12px;
        }

        .menu-suspenso:hover .conteudo-suspenso {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        /* Espaçamento entre os itens do dropdown */
        .conteudo-suspenso .item-suspenso {
            margin-bottom: 12px !important;
        }

        .conteudo-suspenso .item-suspenso:last-child {
            margin-bottom: 0 !important;
        }

        /* Links diretos no dropdown (que não estão dentro de item-suspenso) */
        .conteudo-suspenso > .link-suspenso {
            margin-bottom: 8px;
        }

        .conteudo-suspenso > .link-suspenso:last-child {
            margin-bottom: 0;
        }

        .link-suspenso {
            display: block;
            padding: 12px 14px;
            color: var(--texto-secundario);
            font-weight: 500;
            border-radius: 10px;
            transition: var(--transition);
            background: #f8fafd;
        }

        .link-suspenso i {
            float: right;
            margin-top: 2px;
            font-size: 0.75rem;
        }

        .link-suspenso:hover {
            background: var(--ciano-claro);
            color: var(--ciano-suave);
        }

        /* Submenu de segundo nível */
        .submenu-suspenso {
            position: absolute;
            left: 100%;
            top: 0;
            min-width: 220px;
            background: var(--branco);
            border-radius: 16px;
            border: 1px solid rgba(0, 139, 181, 0.1);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.05);
            opacity: 0;
            visibility: hidden;
            transform: translateX(-5px);
            transition: var(--transition);
            z-index: 1001;
            padding: 10px;
        }

        .item-suspenso:hover .submenu-suspenso {
            opacity: 1;
            visibility: visible;
            transform: translateX(0);
        }

        .submenu-suspenso a {
            display: block;
            padding: 10px 14px;
            color: var(--texto-secundario);
            transition: var(--transition);
            border-radius: 8px;
            margin-bottom: 6px;
        }

        .submenu-suspenso a:last-child {
            margin-bottom: 0;
        }

        .submenu-suspenso a:hover {
            background: var(--ciano-claro);
            color: var(--ciano-suave);
        }

        /* Botão área restrita */
        .botao-area-restrita {
            background: var(--ciano-claro) !important;
            color: var(--ciano-suave) !important;
            padding: 8px 20px !important;
            border-radius: 30px !important;
            font-weight: 600 !important;
        }

        /* Botão menu mobile */
        .botao-menu-mobile {
            display: none;
            width: 48px;
            height: 48px;
            background: transparent;
            border: none;
            cursor: pointer;
            position: relative;
        }

        .botao-menu-mobile span {
            display: block;
            position: absolute;
            height: 3px;
            width: 26px;
            background: var(--texto-secundario);
            border-radius: 3px;
            left: 50%;
            transform: translateX(-50%);
            transition: all 0.3s ease;
        }

        .botao-menu-mobile span:nth-child(1) { top: 16px; }
        .botao-menu-mobile span:nth-child(2) { top: 22px; }
        .botao-menu-mobile span:nth-child(3) { top: 28px; }

        .botao-menu-mobile.ativo span:nth-child(1) {
            top: 22px;
            transform: translateX(-50%) rotate(45deg);
        }
        .botao-menu-mobile.ativo span:nth-child(2) { opacity: 0; }
        .botao-menu-mobile.ativo span:nth-child(3) {
            top: 22px;
            transform: translateX(-50%) rotate(-45deg);
        }

        /* Overlay e Sidebar */
        .overlay-sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9998;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .overlay-sidebar.visivel {
            opacity: 1;
            visibility: visible;
        }

        .sidebar-mobile {
        position: fixed !important;
        top: 0 !important;
        right: -100% !important;
        left: auto !important;
        width: 85% !important;
        max-width: 380px !important;
        height: 100vh !important;
        background: var(--branco) !important;
        z-index: 9999 !important;
        transition: right 0.4s ease !important;
        display: flex !important;
        flex-direction: column !important;
        overflow-y: auto !important;
        box-shadow: -5px 0 30px rgba(0, 0, 0, 0.15) !important;
    }

    .sidebar-mobile.aberto {
        right: 0 !important;
        left: auto !important;
    }

        .sidebar-cabecalho {
            background: var(--gradiente-suave);
            padding: 30px 20px;
            position: relative;
        }

        .sidebar-logo-linha {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .sidebar-logo-img {
            width: 55px;
            height: 55px;
            object-fit: contain;
        }

        .sidebar-logo-texto .nome {
            font-weight: 700;
            font-size: 1.3rem;
            color: white;
        }

        .sidebar-logo-texto .sub {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.9);
        }

        .botao-fechar-sidebar {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            cursor: pointer;
        }

        /* Navegação Sidebar */
        .sidebar-nav {
            flex: 1;
            padding: 20px 16px;
        }

        .sidebar-item {
            margin-bottom: 4px;
        }

        /* Estilo para links normais do sidebar */
        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: var(--texto-secundario);
            font-size: var(--sidebar-font-size);
            font-weight: 500;
            border-radius: 12px;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .sidebar-link:hover {
            background: var(--ciano-claro);
            color: var(--ciano-suave);
            transform: translateX(3px);
        }

        .sidebar-link.ativo {
            background: var(--ciano-claro);
            color: var(--ciano-suave);
            font-weight: 600;
        }

        .sidebar-icone {
            width: 32px;
            font-size: 1.1rem;
            text-align: center;
        }

        /* Botão de toggle para submenus */
        .toggle-submenu-btn {
            transition: all 0.2s ease;
        }

        .toggle-submenu-btn:hover {
            background: var(--ciano-claro);
            color: var(--ciano-suave);
        }

        /* Links da Oferta Formativa no sidebar */
        .sidebar-oferta-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: var(--texto-secundario);
            font-size: var(--sidebar-font-size);
            font-weight: 500;
            border-radius: 12px 0 0 12px;
            text-decoration: none;
            transition: all 0.2s ease;
            flex: 1;
        }

        .sidebar-oferta-link:hover {
            background: var(--ciano-claro);
            color: var(--ciano-suave);
        }

        .sidebar-oferta-link.ativo {
            background: var(--ciano-claro);
            color: var(--ciano-suave);
            font-weight: 600;
        }

        /* Botão seta da oferta formativa */
        .btn-seta-oferta {
            width: 44px;
            border: none;
            background: transparent;
            border-left: 1px solid rgba(0, 139, 181, 0.12);
            border-radius: 0 12px 12px 0;
            cursor: pointer;
            transition: all 0.2s ease;
            color: var(--texto-secundario);
        }

        .btn-seta-oferta:hover {
            background: var(--ciano-claro);
            color: var(--ciano-suave);
        }

        /* Botão toggle do Sobre (integrado) */
        .btn-sobre-toggle {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            padding: 12px 16px;
            background: transparent;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-family: inherit;
            font-size: var(--sidebar-font-size);
            font-weight: 500;
            color: var(--texto-secundario);
            text-align: left;
            transition: all 0.2s ease;
        }

        .btn-sobre-toggle:hover {
            background: var(--ciano-claro);
            color: var(--ciano-suave);
        }

        .btn-sobre-toggle.ativo {
            background: var(--ciano-claro);
            color: var(--ciano-suave);
            font-weight: 600;
        }

        /* Botão toggle para sub-itens */
        .btn-sub-toggle {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            padding: 10px 12px;
            background: transparent;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-family: inherit;
            font-size: 0.88rem;
            color: var(--texto-secundario);
            text-align: left;
            transition: all 0.2s ease;
        }

        .btn-sub-toggle:hover {
            background: var(--ciano-claro);
            color: var(--ciano-suave);
        }

        .seta-icon {
            transition: transform 0.3s ease;
        }

        .aberto .seta-icon {
            transform: rotate(90deg);
        }

        /* Submenu */
        .sidebar-submenu {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.35s ease;
            margin-left: 48px;
            border-left: 2px solid var(--ciano-claro);
            padding-left: 12px;
        }

        .sidebar-submenu.aberto {
            max-height: 800px;
        }

        .sidebar-sub-link {
            display: block;
            padding: 10px 12px;
            color: var(--texto-secundario);
            font-size: 0.88rem;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .sidebar-sub-link:hover {
            background: var(--ciano-claro);
            color: var(--ciano-suave);
            padding-left: 18px;
        }

        .sidebar-sub-link.ativo {
            background: var(--ciano-claro);
            color: var(--ciano-suave);
            font-weight: 600;
        }

        /* Sub-submenu */
        .sidebar-submenu .sidebar-submenu {
            margin-left: 28px;
        }

        /* Rodapé sidebar */
        .sidebar-rodape {
            padding: 20px;
            border-top: 1px solid rgba(0, 139, 181, 0.1);
        }

        .botao-area-restrita-sidebar {
            width: 100%;
            padding: 12px;
            background: var(--ciano-claro);
            color: var(--ciano-suave);
            border: none;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .botao-area-restrita-sidebar:hover {
            background: #d4ecf5;
            transform: translateY(-2px);
        }

        /* Responsividade */
        @media (max-width: 992px) {
            .menu-navegacao {
                display: none !important;
            }
            .botao-menu-mobile {
                display: flex;
            }
        }

        @media (max-width: 768px) {
            .conteudo-superior {
                flex-direction: column;
                gap: 10px;
                padding: 0 20px;
            }
            .conteudo-cabecalho {
                padding: 8px 20px;
            }
        }

        body.sidebar-open {
            overflow: hidden;
        }
    </style>
    
    <link href="<?= $config['favicon_url'] ?? 'foto/ipikk_new_logo.png' ?>" rel="icon">
    <title>IPIKK - <?= $titulo_pagina ?? 'Instituto Politécnico Industrial do Kilamba Kiaxi' ?></title>
</head>
<body>

<!-- BARRA SUPERIOR -->
<div class="barra-superior">
    <div class="conteudo-superior">
        <div class="info-instituto">
            <?= htmlspecialchars($config['instituicao_nome'] ?? 'Instituto Médio Politécnico Industrial do Kilamba Kiaxi Nº 8050 Nova-vida') ?>
        </div>
        <div class="social-traducao">
            <div class="icones-sociais">
                <?php if($config['rede_social_facebook'] ?? false): ?>
                <a href="<?= $config['rede_social_facebook'] ?>" class="icone-social" target="_blank" rel="noopener noreferrer">
                    <i class="fab fa-facebook-f"></i>
                </a>
                <?php endif; ?>
                
                <?php if($config['rede_social_instagram'] ?? false): ?>
                <a href="<?= $config['rede_social_instagram'] ?>" class="icone-social" target="_blank" rel="noopener noreferrer">
                    <i class="fab fa-instagram"></i>
                </a>
                <?php endif; ?>
                
                <?php if($config['rede_social_linkedin'] ?? false): ?>
                <a href="<?= $config['rede_social_linkedin'] ?>" class="icone-social" target="_blank" rel="noopener noreferrer">
                    <i class="fab fa-linkedin-in"></i>
                </a>
                <?php endif; ?>
                
                <?php if($config['rede_social_youtube'] ?? false): ?>
                <a href="<?= $config['rede_social_youtube'] ?>" class="icone-social" target="_blank" rel="noopener noreferrer">
                    <i class="fab fa-youtube"></i>
                </a>
                <?php endif; ?>
                
                <?php if($config['rede_social_twitter'] ?? false): ?>
                <a href="<?= $config['rede_social_twitter'] ?>" class="icone-social" target="_blank" rel="noopener noreferrer">
                    <i class="fab fa-twitter"></i>
                </a>
                <?php endif; ?>
            </div>
            <div class="seletor-idioma">
                <i class="fas fa-globe"></i>
                <span id="idiomaAtual">Português</span>
                <i class="fas fa-chevron-down"></i>
                <div class="dropdown-idioma">
                    <button type="button" class="opcao-idioma ativo" data-lang="pt">
                        <i class="fas fa-flag-checkered"></i> Português
                    </button>
                    <button type="button" class="opcao-idioma" data-lang="en">
                        <i class="fas fa-flag-usa"></i> English
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="google_translate_element" style="display:none;"></div>

<!-- CABEÇALHO -->
<header class="cabecalho">
    <div class="conteudo-cabecalho">
        <a href="index.php" class="logo">
            <img src="<?= $config['logo_url'] ?? 'foto/ipikk_new_logo.png' ?>" alt="Logo IPIKK">
        </a>
        <nav>
            <ul class="menu-navegacao">
                <li class="item-navegacao <?= $is_inicio ? 'ativo' : '' ?>">
                    <a href="index.php" class="link-navegacao">Início</a>
                </li>
                <li class="item-navegacao menu-suspenso <?= $is_oferta_active ? 'ativo' : '' ?>">
                    <a href="oferta-formativa.php" class="link-navegacao">
                        Oferta Formativa <i class="fas fa-chevron-down"></i>
                    </a>
                    <div class="conteudo-suspenso">
                        <?php foreach($areas as $area_menu): $cursos_area = $cursos_por_area[$area_menu['id']] ?? []; ?>
                        <div class="item-suspenso">
                            <a href="area.php?slug=<?= $area_menu['slug'] ?>" class="link-suspenso">
                                <?= htmlspecialchars($area_menu['nome']) ?> 
                                <i class="fas fa-chevron-right"></i>
                            </a>
                            <?php if(count($cursos_area) > 0): ?>
                            <div class="submenu-suspenso">
                                <?php foreach($cursos_area as $curso_menu): ?>
                                <a href="curso.php?slug=<?= $curso_menu['slug'] ?>">
                                    <?= htmlspecialchars($curso_menu['nome']) ?>
                                </a>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </li>
                <li class="item-navegacao menu-suspenso <?= $is_sobre_active ? 'ativo' : '' ?>">
                    <a href="javascript:void(0)" class="link-navegacao">
                        Sobre <i class="fas fa-chevron-down"></i>
                    </a>
                    <div class="conteudo-suspenso">
                        <a href="sobre-nos.php" class="link-suspenso">Quem Somos</a>
                        <div class="item-suspenso">
                            <a href="javascript:void(0)" class="link-suspenso">
                                Institucional <i class="fas fa-chevron-right"></i>
                            </a>
                            <div class="submenu-suspenso">
                                <a href="diretor.php">Perfil do Director</a>
                                <a href="orgaos-diretivos.php">Órgãos Directivos</a>
                                <a href="ex-diretores.php">Ex-Directores</a>
                            </div>
                        </div>
                        <a href="normativos.php" class="link-suspenso">Normativos</a>
                        <div class="item-suspenso">
                            <a href="javascript:void(0)" class="link-suspenso">
                                Reconhecimentos <i class="fas fa-chevron-right"></i>
                            </a>
                            <div class="submenu-suspenso">
                                <a href="percurso.php">Percurso</a>
                                <a href="quadro-honra.php">Quadro de Honra</a>
                                <a href="funcionario-destacado.php">Funcionário Destacado</a>
                            </div>
                        </div>
                        <a href="escolas-afiliadas.php" class="link-suspenso">Escolas Afiliadas</a>
                    </div>
                </li>
                <li class="item-navegacao <?= $is_noticias ? 'ativo' : '' ?>">
                    <a href="noticias.php" class="link-navegacao">Notícias</a>
                </li>
                <li class="item-navegacao <?= $is_galeria ? 'ativo' : '' ?>">
                    <a href="galeria.php" class="link-navegacao">Galeria</a>
                </li>
                <li class="item-navegacao <?= $is_contactos ? 'ativo' : '' ?>">
                    <a href="contatos.php" class="link-navegacao">Contactos</a>
                </li>
                <li>
                    <a href="area-restrita.php" class="link-navegacao botao-area-restrita">
                        <i class="fas fa-lock"></i> Área Restrita
                    </a>
                </li>
            </ul>
        </nav>
        <button class="botao-menu-mobile" id="botaoMenu">
            <span></span><span></span><span></span>
        </button>
    </div>
</header>

<!-- Overlay e Sidebar -->
<div class="overlay-sidebar" id="overlaySidebar"></div>

<aside class="sidebar-mobile" id="sidebarMobile">
    <div class="sidebar-cabecalho">
        <div class="sidebar-logo-linha">
            <!--<img src="<?= $config['logo_url'] ?? 'foto/ipikk_new_logo.png' ?>" alt="IPIKK" class="sidebar-logo-img">-->
            <div class="sidebar-logo-texto">
                <div class="nome">IPIKK</div>
                <div class="sub">Instituto Politécnico Industrial do Kilamba Kiaxi</div>
            </div>
        </div>
        <button class="botao-fechar-sidebar" id="fecharSidebar"><i class="fas fa-times"></i></button>
    </div>
    
    <nav class="sidebar-nav">
        <!-- INÍCIO -->
        <div class="sidebar-item">
            <a href="index.php" class="sidebar-link <?= $is_inicio ? 'ativo' : '' ?>">
                <span class="sidebar-icone"><i class="fas fa-home"></i></span>
                <span>Início</span>
            </a>
        </div>
        
        <!-- OFERTA FORMATIVA - com seta separada -->
        <div class="sidebar-item">
            <div style="display: flex; align-items: stretch;">
                <a href="oferta-formativa.php" class="sidebar-oferta-link <?= $is_oferta_active ? 'ativo' : '' ?>">
                    <span class="sidebar-icone"><i class="fas fa-graduation-cap"></i></span>
                    <span>Oferta Formativa</span>
                </a>
                <button class="btn-seta-oferta toggle-submenu-btn" data-target="submenu-oferta">
                    <i class="fas fa-chevron-right seta-icon"></i>
                </button>
            </div>
            <div class="sidebar-submenu" id="submenu-oferta">
                <?php foreach($areas as $area_menu): $cursos_area = $cursos_por_area[$area_menu['id']] ?? []; ?>
                <div class="sidebar-item">
                    <div style="display: flex; align-items: stretch;">
                        <a href="area.php?slug=<?= $area_menu['slug'] ?>" class="sidebar-sub-link" style="flex: 1; border-radius: 8px 0 0 8px;">
                            <?= htmlspecialchars($area_menu['nome']) ?>
                        </a>
                        <?php if(count($cursos_area) > 0): ?>
                        <button class="btn-seta-oferta toggle-submenu-btn" data-target="submenu-<?= $area_menu['id'] ?>" style="width: 40px; border-radius: 0 8px 8px 0;">
                            <i class="fas fa-chevron-right seta-icon"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                    <?php if(count($cursos_area) > 0): ?>
                    <div class="sidebar-submenu" id="submenu-<?= $area_menu['id'] ?>">
                        <?php foreach($cursos_area as $curso_menu): ?>
                        <a href="curso.php?slug=<?= $curso_menu['slug'] ?>" class="sidebar-sub-link">
                            <?= htmlspecialchars($curso_menu['nome']) ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- SOBRE - Botão integrado -->
        <div class="sidebar-item">
            <button class="btn-sobre-toggle toggle-submenu-btn" data-target="submenu-sobre">
                <span style="display: flex; align-items: center; gap: 12px;">
                    <span class="sidebar-icone"><i class="fas fa-university"></i></span>
                    <span>Sobre</span>
                </span>
                <i class="fas fa-chevron-right seta-icon"></i>
            </button>
            <div class="sidebar-submenu" id="submenu-sobre">
                <a href="sobre-nos.php" class="sidebar-sub-link <?= $arquivo_atual === 'sobre-nos.php' ? 'ativo' : '' ?>">Quem Somos</a>
                
                <div class="sidebar-item">
                    <button class="btn-sub-toggle toggle-submenu-btn" data-target="submenu-institucional">
                        <span>Institucional</span>
                        <i class="fas fa-chevron-right seta-icon"></i>
                    </button>
                    <div class="sidebar-submenu" id="submenu-institucional">
                        <a href="diretor.php" class="sidebar-sub-link <?= $arquivo_atual === 'diretor.php' ? 'ativo' : '' ?>">Perfil do Director</a>
                        <a href="orgaos-diretivos.php" class="sidebar-sub-link <?= $arquivo_atual === 'orgaos-diretivos.php' ? 'ativo' : '' ?>">Órgãos Directivos</a>
                        <a href="ex-diretores.php" class="sidebar-sub-link <?= $arquivo_atual === 'ex-diretores.php' ? 'ativo' : '' ?>">Ex-Directores</a>
                    </div>
                </div>
                
                <a href="normativos.php" class="sidebar-sub-link <?= $arquivo_atual === 'normativos.php' ? 'ativo' : '' ?>">Normativos</a>
                
                <div class="sidebar-item">
                    <button class="btn-sub-toggle toggle-submenu-btn" data-target="submenu-alumni">
                        <span>Reconhecimentos</span>
                        <i class="fas fa-chevron-right seta-icon"></i>
                    </button>
                    <div class="sidebar-submenu" id="submenu-alumni">
                        <a href="percurso.php" class="sidebar-sub-link <?= $arquivo_atual === 'percurso.php' ? 'ativo' : '' ?>">Percurso</a>
                        <a href="quadro-honra.php" class="sidebar-sub-link <?= $arquivo_atual === 'quadro-honra.php' ? 'ativo' : '' ?>">Quadro de Honra</a>
                        <a href="funcionario-destacado.php" class="sidebar-sub-link <?= $arquivo_atual === 'funcionario-destacado.php' ? 'ativo' : '' ?>">Funcionário Destacado</a>
                    </div>
                </div>
                
                <a href="escolas-afiliadas.php" class="sidebar-sub-link <?= $arquivo_atual === 'escolas-afiliadas.php' ? 'ativo' : '' ?>">Escolas Afiliadas</a>
            </div>
        </div>
        
        <!-- NOTÍCIAS -->
        <div class="sidebar-item">
            <a href="noticias.php" class="sidebar-link <?= $is_noticias ? 'ativo' : '' ?>">
                <span class="sidebar-icone"><i class="fas fa-newspaper"></i></span>
                <span>Notícias</span>
            </a>
        </div>
        
        <!-- GALERIA -->
        <div class="sidebar-item">
            <a href="galeria.php" class="sidebar-link <?= $is_galeria ? 'ativo' : '' ?>">
                <span class="sidebar-icone"><i class="fas fa-images"></i></span>
                <span>Galeria</span>
            </a>
        </div>
        
        <!-- CONTACTOS -->
        <div class="sidebar-item">
            <a href="contatos.php" class="sidebar-link <?= $is_contactos ? 'ativo' : '' ?>">
                <span class="sidebar-icone"><i class="fas fa-envelope"></i></span>
                <span>Contactos</span>
            </a>
        </div>
    </nav>
    
    <div class="sidebar-rodape">
        <button class="botao-area-restrita-sidebar" onclick="window.location.href='area-restrita.php'">
            <i class="fas fa-lock"></i> Área Restrita
        </button>
    </div>
</aside>


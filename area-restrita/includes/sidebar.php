<?php
/**
 * Sidebar da Área Restrita - IPIKK
 */

$pagina_atual = basename($_SERVER['PHP_SELF']);

$permissoes_usuario = isset($_SESSION['utilizador_permissoes']) 
    ? (is_array($_SESSION['utilizador_permissoes']) ? $_SESSION['utilizador_permissoes'] : json_decode($_SESSION['utilizador_permissoes'], true))
    : [];
$nivel_usuario = $_SESSION['utilizador_nivel'] ?? 'editor';
$is_admin = ($nivel_usuario === 'admin');

function podeVerItem($item, $is_admin, $permissoes) {
    if ($is_admin || in_array('*', $permissoes)) return true;
    return in_array($item, $permissoes);
}

function isActivePage($pages) {
    $current = basename($_SERVER['PHP_SELF']);
    if (!is_array($pages)) $pages = [$pages];
    return in_array($current, $pages);
}
?>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="logo-area">
            <img src="../area-publica/foto/ipikk_new_logo.png" alt="IPIKK Logo">
            <h2>IPIKK</h2>
        </div>
        <p class="subtitle">ÁREA RESTRITA</p>
        <button class="sidebar-close" id="sidebarClose">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <nav class="sidebar-nav">
        <ul>
            <!-- DASHBOARD -->
            <?php if (podeVerItem('dashboard', $is_admin, $permissoes_usuario)): ?>
            <li class="<?= $pagina_atual == 'admin-dashboard.php' ? 'active' : '' ?>">
                <a href="admin-dashboard.php">
                    <i class="fas fa-chart-line"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <?php endif; ?>

            <!-- CONTEÚDO DO SITE -->
            <?php if (podeVerItem('conteudo_site', $is_admin, $permissoes_usuario)): ?>
            <li class="menu-item-has-children has-submenu <?= isActivePage(['admin-inicio.php', 'admin-sobre.php', 'admin-perfil-director.php', 'admin-orgaos.php', 'admin-ex-directores.php', 'admin-normativos.php', 'admin-percurso.php', 'admin-quadro-honra.php', 'admin-funcionario-destacado.php', 'admin-escolas-afiliadas.php']) ? 'active open' : '' ?>">
                <a href="javascript:void(0)" class="submenu-toggle menu-link-parent">
                    <i class="fas fa-file-alt"></i>
                    <span>Conteúdo do Site</span>
                    <i class="fas fa-chevron-down arrow menu-arrow"></i>
                </a>
                <ul class="submenu">
                    <li class="<?= $pagina_atual == 'admin-inicio.php' ? 'active' : '' ?>">
                        <a href="admin-inicio.php"><i class="fas fa-home"></i> Início</a>
                    </li>
                    <li class="<?= $pagina_atual == 'admin-sobre.php' ? 'active' : '' ?>">
                        <a href="admin-sobre.php"><i class="fas fa-building"></i> Quem Somos</a>
                    </li>
                    <li class="submenu-item-has-children has-submenu-level2">
                        <a href="javascript:void(0)" class="submenu-toggle-level2 menu-link-parent">
                            <i class="fas fa-university"></i> Institucional
                            <i class="fas fa-chevron-right arrow-level2 submenu-arrow"></i>
                        </a>
                        <ul class="submenu-level2">
                            <li class="<?= $pagina_atual == 'admin-perfil-director.php' ? 'active' : '' ?>">
                                <a href="admin-perfil-director.php"><i class="fas fa-user-tie"></i> Director</a>
                            </li>
                            <li class="<?= $pagina_atual == 'admin-orgaos.php' ? 'active' : '' ?>">
                                <a href="admin-orgaos.php"><i class="fas fa-users-cog"></i> Órgãos</a>
                            </li>
                            <li class="<?= $pagina_atual == 'admin-ex-directores.php' ? 'active' : '' ?>">
                                <a href="admin-ex-diretores.php"><i class="fas fa-history"></i> Ex-Directores</a>
                            </li>
                            <li class="<?= $pagina_atual == 'admin-normativos.php' ? 'active' : '' ?>">
                                <a href="admin-normativos.php"><i class="fas fa-file-pdf"></i> Normativos</a>
                            </li>
                        </ul>
                    </li>
                    <li class="submenu-item-has-children has-submenu-level2">
                        <a href="javascript:void(0)" class="submenu-toggle-level2 menu-link-parent">
                            <i class="fas fa-users"></i> Reconhecimentos
                            <i class="fas fa-chevron-right arrow-level2 submenu-arrow"></i>
                        </a>
                        <ul class="submenu-level2">
                            <li class="<?= $pagina_atual == 'admin-percurso.php' ? 'active' : '' ?>">
                                <a href="admin-percurso.php"><i class="fas fa-chart-line"></i> Sucesso</a>
                            </li>
                            <li class="<?= $pagina_atual == 'admin-quadro-honra.php' ? 'active' : '' ?>">
                                <a href="admin-quadro-honra.php"><i class="fas fa-trophy"></i> Quadro de Honra</a>
                            </li>
                            <li class="<?= $pagina_atual == 'admin-funcionario-destacado.php' ? 'active' : '' ?>">
                                <a href="admin-funcionario-destacado.php"><i class="fas fa-star"></i> Funcionários</a>
                            </li>
                        </ul>
                    </li>
                    <li class="<?= $pagina_atual == 'admin-escolas-afiliadas.php' ? 'active' : '' ?>">
                        <a href="admin-escolas-afiliadas.php"><i class="fas fa-school"></i> Escolas Afiliadas</a>
                    </li>
                </ul>
            </li>
            <?php endif; ?>

            <!-- OFERTA FORMATIVA -->
            <?php if (podeVerItem('oferta_formativa', $is_admin, $permissoes_usuario)): ?>
            <li class="menu-item-has-children has-submenu <?= isActivePage(['admin-cursos.php', 'admin-depoimentos.php', 'admin-planos-curriculares.php']) ? 'active open' : '' ?>">
                <a href="javascript:void(0)" class="submenu-toggle menu-link-parent">
                    <i class="fas fa-graduation-cap"></i>
                    <span>Oferta Formativa</span>
                    <i class="fas fa-chevron-down arrow menu-arrow"></i>
                </a>
                <ul class="submenu">
                    <li class="<?= $pagina_atual == 'admin-cursos.php' ? 'active' : '' ?>">
                        <a href="admin-cursos.php"><i class="fas fa-book-open"></i> Cursos</a>
                    </li>
                    <li class="<?= $pagina_atual == 'admin-planos-curriculares.php' ? 'active' : '' ?>">
                        <a href="admin-planos-curriculares.php"><i class="fas fa-calendar-alt"></i> Planos</a>
                    </li>
                    <li class="<?= $pagina_atual == 'admin-depoimentos.php' ? 'active' : '' ?>">
                        <a href="admin-depoimentos.php"><i class="fas fa-quote-right"></i> Depoimentos</a>
                    </li>
                </ul>
            </li>
            <?php endif; ?>

            <!-- NOTÍCIAS -->
            <?php if (podeVerItem('noticias', $is_admin, $permissoes_usuario)): ?>
            <li class="<?= $pagina_atual == 'admin-noticias.php' ? 'active' : '' ?>">
                <a href="admin-noticias.php">
                    <i class="fas fa-newspaper"></i>
                    <span>Notícias</span>
                </a>
            </li>
            <?php endif; ?>

            <!-- GALERIA -->
            <?php if (podeVerItem('galeria', $is_admin, $permissoes_usuario)): ?>
            <li class="<?= $pagina_atual == 'admin-galeria.php' ? 'active' : '' ?>">
                <a href="admin-galeria.php">
                    <i class="fas fa-images"></i>
                    <span>Galeria</span>
                </a>
            </li>
            <?php endif; ?>

            <!-- INSCRIÇÕES -->
            <?php if (podeVerItem('inscricoes', $is_admin, $permissoes_usuario)): ?>
            <li class="<?= $pagina_atual == 'admin-inscricoes.php' ? 'active' : '' ?>">
                <a href="admin-inscricoes.php">
                    <i class="fas fa-file-signature"></i>
                    <span>Inscrições</span>
                </a>
            </li>
            <?php endif; ?>

            <!-- CONTACTOS -->
            <?php if (podeVerItem('contactos', $is_admin, $permissoes_usuario)): ?>
            <li class="<?= $pagina_atual == 'admin-contactos.php' ? 'active' : '' ?>">
                <a href="admin-contactos.php">
                    <i class="fas fa-envelope"></i>
                    <span>Contactos</span>
                </a>
            </li>
            <?php endif; ?>

            <!-- UTILIZADORES -->
            <?php if (podeVerItem('utilizadores', $is_admin, $permissoes_usuario)): ?>
            <li class="<?= $pagina_atual == 'admin-utilizadores.php' ? 'active' : '' ?>">
                <a href="admin-utilizadores.php">
                    <i class="fas fa-users"></i>
                    <span>Utilizadores</span>
                </a>
            </li>
            <?php endif; ?>

            <!-- CONFIGURAÇÕES -->
            <?php if (podeVerItem('configuracoes', $is_admin, $permissoes_usuario)): ?>
            <li class="<?= $pagina_atual == 'admin-configuracoes.php' ? 'active' : '' ?>">
                <a href="admin-configuracoes.php">
                    <i class="fas fa-cog"></i>
                    <span>Configurações</span>
                </a>
            </li>
            <?php endif; ?>

            <!-- LIXEIRA -->
            <?php if (podeVerItem('lixeira', $is_admin, $permissoes_usuario)): ?>
            <li class="<?= $pagina_atual == 'admin-lixeira.php' ? 'active' : '' ?>">
                <a href="admin-lixeira.php">
                    <i class="fas fa-trash-alt"></i>
                    <span>Lixeira</span>
                </a>
            </li>
            <?php endif; ?>

            <!-- LOGS -->
            <?php if (podeVerItem('logs', $is_admin, $permissoes_usuario)): ?>
            <li class="<?= $pagina_atual == 'admin-logs.php' ? 'active' : '' ?>">
                <a href="admin-logs.php">
                    <i class="fas fa-history"></i>
                    <span>Logs</span>
                </a>
            </li>
            <?php endif; ?>

            <!-- MEU PERFIL -->
            <li class="<?= $pagina_atual == 'admin-perfil.php' ? 'active' : '' ?>">
                <a href="admin-perfil.php">
                    <i class="fas fa-user"></i>
                    <span>Meu Perfil</span>
                </a>
            </li>
        </ul>
    </nav>

    <div class="sidebar-footer">
        <a href="logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            <span>Sair</span>
        </a>
    </div>
</aside>

<div class="sidebar-overlay overlay-sidebar" id="overlaySidebar"></div>

<style>
/* ===== SIDEBAR ESTILOS ===== */
.sidebar {
    position: fixed;
    top: 0;
    left: -280px;
    width: 280px;
    height: 100vh;
    background: linear-gradient(135deg, #008bb5 0%, #006d8f 100%);
    color: white;
    z-index: 1000;
    transition: left 0.3s ease;
    display: flex;
    flex-direction: column;
    box-shadow: 4px 0 20px rgba(0, 0, 0, 0.15);
}

.sidebar.visible {
    left: 0;
}

/* Sidebar Header */
.sidebar-header {
    padding: 30px 20px;
    text-align: center;
    border-bottom: 1px solid rgba(255, 255, 255, 0.15);
    position: relative;
}

.logo-area {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    margin-bottom: 10px;
}

.logo-area img {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: white;
    padding: 5px;
}

.logo-area h2 {
    font-size: 1.3rem;
    margin: 0;
    color: white;
}

.subtitle {
    font-size: 11px;
    opacity: 0.7;
    letter-spacing: 2px;
    margin: 0;
}

.sidebar-close {
    position: absolute;
    top: 15px;
    right: 15px;
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    cursor: pointer;
    display: none;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.sidebar-close:hover {
    background: rgba(255, 255, 255, 0.4);
    transform: rotate(90deg);
}

/* Sidebar Navigation */
.sidebar-nav {
    flex: 1;
    overflow-y: auto;
    padding: 20px 0;
}

.sidebar-nav ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.sidebar-nav li {
    margin: 2px 0;
}

.sidebar-nav a {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 12px 20px;
    color: rgba(255, 255, 255, 0.85);
    text-decoration: none;
    transition: all 0.3s ease;
    font-size: 14px;
    position: relative;
}

.sidebar-nav a:hover {
    background: rgba(255, 255, 255, 0.1);
    color: white;
    padding-left: 25px;
}

.sidebar-nav li.active > a {
    background: rgba(255, 255, 255, 0.15);
    color: white;
    font-weight: 600;
}

.sidebar-nav li.active > a::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    height: 100%;
    width: 4px;
    background: #0a9396;
}

.sidebar-nav i {
    width: 24px;
    text-align: center;
    font-size: 16px;
}

/* Submenus */
.sidebar-nav .has-submenu > a {
    justify-content: space-between;
}

.sidebar-nav .has-submenu > a .arrow {
    transition: transform 0.3s ease;
}

.sidebar-nav .has-submenu.open > a .arrow {
    transform: rotate(-180deg);
}

.sidebar-nav .submenu {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease-out;
    background: rgba(0, 0, 0, 0.2);
    border-radius: 0 0 8px 8px;
}

.sidebar-nav .has-submenu.open .submenu {
    max-height: 500px;
}

.sidebar-nav .submenu a {
    padding: 10px 20px 10px 50px;
    font-size: 13px;
}

.sidebar-nav .submenu i {
    width: 20px;
    font-size: 12px;
}

/* Submenu Nível 2 */
.sidebar-nav .has-submenu-level2 > a {
    justify-content: space-between;
}

.sidebar-nav .has-submenu-level2 > a .arrow-level2 {
    transition: transform 0.3s ease;
    font-size: 11px;
}

.sidebar-nav .has-submenu-level2.open > a .arrow-level2 {
    transform: rotate(90deg);
}

.sidebar-nav .submenu-level2 {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease-out;
    background: rgba(0, 0, 0, 0.3);
    border-radius: 0 0 8px 8px;
}

.sidebar-nav .has-submenu-level2.open .submenu-level2 {
    max-height: 300px;
}

.sidebar-nav .submenu-level2 a {
    padding: 8px 20px 8px 65px;
    font-size: 12px;
}

/* Sidebar Footer */
.sidebar-footer {
    padding: 20px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.logout-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 12px;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 30px;
    color: white;
    text-decoration: none;
    transition: all 0.3s ease;
    font-weight: 600;
}

.logout-btn:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: translateY(-2px);
}

/* Sidebar Overlay */
.sidebar-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(3px);
    z-index: 999;
    display: none;
}

.sidebar-overlay.visible {
    display: block;
}

/* Scrollbar */
.sidebar-nav::-webkit-scrollbar {
    width: 4px;
}

.sidebar-nav::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.1);
}

.sidebar-nav::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.3);
    border-radius: 4px;
}

/* Desktop */
@media (min-width: 769px) {
    .sidebar {
        left: 0;
    }
    .conteudo-principal {
        margin-left: 280px;
    }
    .sidebar-close {
        display: none !important;
    }
    .sidebar-overlay {
        display: none !important;
    }
}

/* Mobile */
@media (max-width: 768px) {
    .sidebar {
        left: -280px;
    }
    .sidebar.visible {
        left: 0;
    }
    .conteudo-principal {
        margin-left: 0;
    }
    .sidebar-close {
        display: flex;
    }
}
</style>

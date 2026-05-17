<?php
/**
 * Topbar da Área Restrita - IPIKK
 */

$usuario_topo = $usuario ?? [];
$nome_topo = $usuario_topo['nome'] ?? 'Utilizador';
$email_topo = $usuario_topo['email'] ?? '';
$foto_topo = $usuario_topo['foto_url'] ?? 'foto/sem_foto.png';

if (empty($foto_topo) || $foto_topo == 'foto/sem_foto.png') {
    $foto_topo = '../area-publica/foto/sem_foto.png';
} elseif (strpos($foto_topo, '../area-publica/') !== 0 && strpos($foto_topo, 'uploads/') === 0) {
    $foto_topo = '../area-publica/' . $foto_topo;
}

$titulo_topo = $titulo_pagina ?? 'Dashboard';
$permissoes_topo = isset($_SESSION['utilizador_permissoes'])
    ? (is_array($_SESSION['utilizador_permissoes']) ? $_SESSION['utilizador_permissoes'] : json_decode($_SESSION['utilizador_permissoes'], true))
    : [];
if (!is_array($permissoes_topo)) {
    $permissoes_topo = [];
}
$pode_ver_logs_topo = ($_SESSION['utilizador_nivel'] ?? 'editor') === 'admin'
    || in_array('*', $permissoes_topo)
    || in_array('logs', $permissoes_topo);
?>

<header class="topbar">
    <div class="topbar-left">
        <button class="botao-menu-mobile" id="menuMobileBtn">
            <i class="fas fa-bars"></i>
        </button>
        <h1 class="page-title"><?= htmlspecialchars($titulo_topo) ?></h1>
    </div>

    <div class="topbar-right">
        <div class="user-dropdown">
            <button class="user-btn" id="userBtn">
                <img src="<?= htmlspecialchars($foto_topo) ?>" alt="<?= htmlspecialchars($nome_topo) ?>">
                <span class="user-name"><?= htmlspecialchars($nome_topo) ?></span>
                <i class="fas fa-chevron-down"></i>
            </button>
            <div class="dropdown-menu" id="dropdownMenu">
                <div class="dropdown-header">
                    <img src="<?= htmlspecialchars($foto_topo) ?>" alt="<?= htmlspecialchars($nome_topo) ?>">
                    <div class="dropdown-info">
                        <strong><?= htmlspecialchars($nome_topo) ?></strong>
                        <small><?= htmlspecialchars($email_topo) ?></small>
                    </div>
                </div>
                <div class="dropdown-divider"></div>
                <a href="admin-perfil.php" class="dropdown-item">
                    <i class="fas fa-user"></i> Meu Perfil
                </a>
                <?php if ($pode_ver_logs_topo): ?>
                <a href="admin-logs.php" class="dropdown-item">
                    <i class="fas fa-history"></i> Logs
                </a>
                <?php endif; ?>
                <div class="dropdown-divider"></div>
                <a href="logout.php" class="dropdown-item logout">
                    <i class="fas fa-sign-out-alt"></i> Sair
                </a>
            </div>
        </div>
        
    </div>
</header>

<style>
/* ===== TOPBAR ESTILOS ===== */
.topbar {
    position: sticky;
    top: 0;
    height: 70px;
    background: white;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 30px;
    z-index: 998;
    border-bottom: 1px solid #eaeff2;
}

.topbar-left {
    display: flex;
    align-items: center;
    gap: 20px;
}

        .botao-menu-mobile {
            display: none; /* escondido no desktop */
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: #f5f7fa;
            color: #008bb5;
            font-size: 18px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            align-items: center;
            justify-content: center;
        }

        .botao-menu-mobile:hover {
            background: #e6f7ff;
            transform: scale(1.05);
        }

        @media (max-width: 768px) {
            .botao-menu-mobile {
                display: flex; /* aparece só no mobile */
            }
        }

.page-title {
    font-size: 22px;
    font-weight: 600;
    color: #003072;
    margin: 0;
}

.topbar-right {
    display: flex;
    align-items: center;
    gap: 15px;
}

/* User Dropdown */
.user-dropdown {
    position: relative;
}

.user-btn {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 6px 15px 6px 6px;
    border-radius: 40px;
    background: #f5f7fa;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
}

.user-btn:hover {
    background: #e6f7ff;
    transform: translateY(-2px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.user-btn img {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid white;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.user-name {
    font-weight: 500;
    color: #2c3e50;
    font-size: 14px;
}

.user-btn i {
    font-size: 12px;
    color: #7f8c8d;
    transition: transform 0.3s ease;
}

.user-btn.active i {
    transform: rotate(180deg);
}

.dropdown-menu {
    position: absolute;
    top: calc(100% + 10px);
    right: 0;
    width: 280px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
    border: 1px solid #eaeff2;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.3s ease;
    z-index: 1001;
}

.dropdown-menu.show {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.dropdown-header {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 20px;
}

.dropdown-header img {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #e6f7ff;
}

.dropdown-info strong {
    display: block;
    font-size: 14px;
    color: #2c3e50;
}

.dropdown-info small {
    font-size: 12px;
    color: #7f8c8d;
}

.dropdown-divider {
    height: 1px;
    background: #eaeff2;
    margin: 5px 0;
}

.dropdown-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 20px;
    color: #2c3e50;
    text-decoration: none;
    transition: all 0.3s ease;
    font-size: 14px;
}

.dropdown-item:hover {
    background: #e6f7ff;
    color: #008bb5;
    padding-left: 25px;
}

.dropdown-item i {
    width: 20px;
    font-size: 14px;
}

.dropdown-item.logout {
    color: #dc3545;
}

.dropdown-item.logout:hover {
    background: #fee2e2;
    color: #dc3545;
}

.mobile-logout {
    display: none;
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: #f5f7fa;
    align-items: center;
    justify-content: center;
    color: #dc3545;
    transition: all 0.3s ease;
    text-decoration: none;
}

.mobile-logout:hover {
    background: #fee2e2;
    transform: scale(1.05);
}

/* Responsividade */
@media (max-width: 768px) {
    .topbar {
        padding: 0 15px;
    }
    
    .menu-toggle {
        display: flex;
    }
    
    .page-title {
        font-size: 18px;
    }
    
    .user-name {
        display: none;
    }
    
    .user-btn {
        padding: 6px;
    }
    
    .mobile-logout {
        display: flex;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ===== DROPDOWN DO PERFIL =====
    const userBtn = document.getElementById('userBtn');
    const dropdownMenu = document.getElementById('dropdownMenu');

    if (userBtn && dropdownMenu) {
        userBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            dropdownMenu.classList.toggle('show');
            userBtn.classList.toggle('active');
        });

        document.addEventListener('click', function(e) {
            if (!userBtn.contains(e.target) && !dropdownMenu.contains(e.target)) {
                dropdownMenu.classList.remove('show');
                userBtn.classList.remove('active');
            }
        });
    }

    // ===== MENU MOBILE =====
    const menuBtn = document.getElementById('menuMobileBtn');
    if (menuBtn) {
        menuBtn.addEventListener('click', function() {
            if (typeof window.openSidebar === 'function') {
                window.openSidebar();
            }
        });
    }

    const mobileLogout = document.getElementById('mobileLogoutBtn');
    if (mobileLogout) {
        mobileLogout.addEventListener('click', function(e) {
            if (!confirm('Deseja realmente sair?')) {
                e.preventDefault();
            }
        });
    }
});
</script>
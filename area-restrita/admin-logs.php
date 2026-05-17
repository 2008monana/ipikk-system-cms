<?php
/**
 * Logs de Atividade - Area Restrita IPIKK
 */

$titulo_pagina = 'Logs de Atividade';
$css_especifico = 'admin-logs.css';

$base_path = dirname(__DIR__);
require_once $base_path . '/config/index.php';

if (!isset($_SESSION['utilizador_id'])) {
    header('Location: area-restrita.php');
    exit;
}
// ===== VERIFICAÇÃO DE PERMISSÃO CORRIGIDA =====
if (isset($_SESSION['utilizador_permissoes'])) {
    if (is_array($_SESSION['utilizador_permissoes'])) {
        $permissoes = $_SESSION['utilizador_permissoes'];
    } else {
        $permissoes = json_decode($_SESSION['utilizador_permissoes'], true);
    }
} else {
    $permissoes = [];
}

if (!is_array($permissoes)) {
    $permissoes = [];
}

$nivel = $_SESSION['utilizador_nivel'] ?? 'editor';

if ($nivel !== 'admin' && !in_array('logs', $permissoes) && !in_array('*', $permissoes)) {
    header('Location: admin-dashboard.php?erro=permissao');
    exit;
}

$db = getDB();

$stmt = $db->prepare("SELECT id, nome, email, foto_url FROM utilizadores WHERE id = ?");
$stmt->execute([$_SESSION['utilizador_id']]);
$usuario_logado = $stmt->fetch();

if (!$usuario_logado) {
    session_destroy();
    header('Location: area-restrita.php');
    exit;
}

$config = $db->query("SELECT * FROM configuracoes WHERE id = 1")->fetch();

$filtro_tipo = $_GET['tipo'] ?? '';
$filtro_data_inicio = $_GET['data_inicio'] ?? '';
$filtro_data_fim = $_GET['data_fim'] ?? '';
$filtro_busca = $_GET['busca'] ?? '';
$pagina_atual = (int)($_GET['pagina'] ?? 1);
$itens_por_pagina = 20;
$offset = ($pagina_atual - 1) * $itens_por_pagina;

$sql = "SELECT l.*, u.nome as utilizador_nome 
        FROM logs l
        LEFT JOIN utilizadores u ON l.utilizador_id = u.id
        WHERE 1=1";
$params = [];

if (!empty($filtro_tipo)) {
    $sql .= " AND l.acao = ?";
    $params[] = $filtro_tipo;
}
if (!empty($filtro_data_inicio)) {
    $sql .= " AND DATE(l.data_hora) >= ?";
    $params[] = $filtro_data_inicio;
}
if (!empty($filtro_data_fim)) {
    $sql .= " AND DATE(l.data_hora) <= ?";
    $params[] = $filtro_data_fim;
}
if (!empty($filtro_busca)) {
    $sql .= " AND (l.detalhes LIKE ? OR u.nome LIKE ? OR l.tabela LIKE ?)";
    $params[] = "%{$filtro_busca}%";
    $params[] = "%{$filtro_busca}%";
    $params[] = "%{$filtro_busca}%";
}

$count_sql = str_replace("l.*", "COUNT(*) as total", $sql);
$stmt = $db->prepare($count_sql);
$stmt->execute($params);
$total_registros = $stmt->fetch()['total'];
$total_paginas = ceil($total_registros / $itens_por_pagina);

$sql .= " ORDER BY l.data_hora DESC LIMIT ? OFFSET ?";
$params[] = $itens_por_pagina;
$params[] = $offset;
$stmt = $db->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

$tipos_acao = [
    'login' => ['nome' => 'Login', 'icone' => 'fa-sign-in-alt', 'cor' => '#10b981'],
    'logout' => ['nome' => 'Logout', 'icone' => 'fa-sign-out-alt', 'cor' => '#ef4444'],
    'criou' => ['nome' => 'Criacao', 'icone' => 'fa-plus-circle', 'cor' => '#0a9396'],
    'editou' => ['nome' => 'Edicao', 'icone' => 'fa-edit', 'cor' => '#f59e0b'],
    'eliminou' => ['nome' => 'Eliminacao', 'icone' => 'fa-trash-alt', 'cor' => '#ef4444'],
    'publicou' => ['nome' => 'Publicacao', 'icone' => 'fa-paper-plane', 'cor' => '#10b981'],
    'arquivou' => ['nome' => 'Arquivo', 'icone' => 'fa-archive', 'cor' => '#6366f1']
];

$stmt = $db->query("SELECT COUNT(*) as total FROM logs");
$total_logs = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM logs WHERE acao = 'login'");
$total_logins = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM logs WHERE data_hora > DATE_SUB(NOW(), INTERVAL 7 DAY)");
$total_semana = $stmt->fetch()['total'];

function obterCorAcao($acao) {
    $cores = [
        'login' => '#10b981',
        'logout' => '#ef4444',
        'criou' => '#0a9396',
        'editou' => '#f59e0b',
        'eliminou' => '#ef4444',
        'publicou' => '#10b981',
        'arquivou' => '#6366f1'
    ];
    return $cores[$acao] ?? '#6c757d';
}

function obterIconeAcao($acao) {
    $icones = [
        'login' => 'fa-sign-in-alt',
        'logout' => 'fa-sign-out-alt',
        'criou' => 'fa-plus-circle',
        'editou' => 'fa-edit',
        'eliminou' => 'fa-trash-alt',
        'publicou' => 'fa-paper-plane',
        'arquivou' => 'fa-archive'
    ];
    return $icones[$acao] ?? 'fa-info-circle';
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Area Restrita - Logs de Atividade</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="../area-publica/foto/ipikk_new_logo.png" rel="icon">
    <link rel="stylesheet" href="css/admin-sidebar-header.css">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        :root {
            --primario: #003072;
            --secundario: #0a9396;
            --claro: #f5f7fa;
            --medio: #e0e4e8;
            --escuro: #2c3e50;
            --branco: #fff;
            --sucesso: #28a745;
            --perigo: #dc3545;
            --aviso: #ffc107;
            --info: #17a2b8;
            --sombra: 0 2px 8px rgba(0,0,0,0.08);
            --transicao: all 0.3s ease;
            --borda: 8px;
            --largura-sidebar: 280px;
            --altura-topo: 70px;
        }
        
        body {
            font-family: 'Montserrat', sans-serif;
            font-size: 15px;
            line-height: 1.5;
            color: var(--escuro);
            background-color: var(--claro);
        }
        
        .btn-sair {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 12px;
            background: rgba(220,53,69,0.15);
            border-radius: var(--borda);
            color: var(--branco);
            text-decoration: none;
            transition: var(--transicao);
        }
        .btn-sair:hover { background: var(--perigo); }
        
        .conteudo-principal { margin-left: var(--largura-sidebar); min-height: 100vh; }
        
        .barra-superior {
            height: var(--altura-topo); background: var(--branco);
            box-shadow: var(--sombra); display: flex; align-items: center;
            justify-content: space-between; padding: 0 30px;
            position: sticky; top: 0; z-index: 999;
        }
        
        .esquerda-barra { display: flex; align-items: center; gap: 20px; }
        .botao-menu-mobile { display: none; background: none; border: none; font-size: 24px; cursor: pointer; color: var(--primario); }
        .titulo-pagina { font-size: 1.35rem; margin: 0; display: flex; align-items: center; gap: 10px; font-weight: 700; color: var(--primario); }
        .titulo-pagina i { color: var(--secundario); }
        
        .direita-barra { display: flex; align-items: center; gap: 20px; }
        
        .container-perfil { position: relative; }
        .botao-perfil {
            display: flex; align-items: center; gap: 10px; background: var(--claro);
            border: none; padding: 6px 12px; border-radius: 40px; cursor: pointer;
        }
        .botao-perfil:hover { background: var(--medio); }
        .botao-perfil img { width: 35px; height: 35px; border-radius: 50%; object-fit: cover; }
        .nome-usuario { font-size: 13px; font-weight: 500; }
        
        .dropdown-perfil {
            position: absolute; top: 55px; right: 0; width: 250px;
            background: var(--branco); border-radius: var(--borda);
            box-shadow: var(--sombra); z-index: 1000; display: none;
        }
        .dropdown-perfil.ativo { display: block; }
        
        .cabecalho-perfil { display: flex; align-items: center; gap: 12px; padding: 15px; border-bottom: 1px solid var(--medio); }
        .cabecalho-perfil img { width: 45px; height: 45px; border-radius: 50%; object-fit: cover; }
        .info-usuario .nome { font-weight: 600; color: var(--primario); font-size: 14px; }
        .info-usuario .email { font-size: 11px; color: #999; }
        .links-perfil a { display: flex; align-items: center; gap: 12px; padding: 10px 15px; text-decoration: none; color: var(--escuro); }
        .links-perfil a:hover { background: var(--claro); }
        .links-perfil a.ativo { background: rgba(10,147,150,0.1); color: var(--secundario); }
        .links-perfil a.sair { color: var(--perigo); }
        .links-perfil hr { margin: 5px 0; border-color: var(--medio); }
        
        .conteudo-pagina { padding: 30px; }
        
        /* Estatisticas */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: var(--branco);
            border-radius: var(--borda);
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: var(--sombra);
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }
        .stat-icon.blue { background: #e8f4fd; color: #008bb5; }
        .stat-icon.green { background: #e8f5e9; color: #2e7d32; }
        .stat-icon.orange { background: #fff3e0; color: #e67e22; }
        
        .stat-info h3 { font-size: 28px; font-weight: 700; margin-bottom: 2px; }
        .stat-info p { font-size: 12px; color: #666; }
        
        /* Filtros */
        .filters-card {
            background: var(--branco);
            border-radius: var(--borda);
            margin-bottom: 25px;
            box-shadow: var(--sombra);
        }
        
        .filters-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            cursor: pointer;
        }
        
        .filters-title {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .filters-title i { color: var(--secundario); }
        .filters-title h3 { font-size: 15px; font-weight: 600; margin: 0; }
        
        .filters-active-badge {
            background: #e8f4fd;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 600;
            color: var(--secundario);
        }
        
        .btn-toggle-filters {
            background: none; border: none; cursor: pointer;
            color: #999; width: 30px; height: 30px;
            border-radius: 6px;
        }
        .btn-toggle-filters:hover { background: var(--claro); }
        .btn-toggle-filters.collapsed i { transform: rotate(-180deg); }
        
        .filters-form {
            padding: 0 20px;
            max-height: 400px;
            overflow: hidden;
            transition: all 0.3s ease;
            border-top: 1px solid var(--medio);
        }
        .filters-form.collapsed {
            max-height: 0;
            padding: 0 20px;
            border-top: none;
        }
        
        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 15px;
            padding: 20px 0;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .filter-group label {
            font-size: 11px;
            font-weight: 600;
            color: #666;
            text-transform: uppercase;
        }
        .filter-group label i { color: var(--secundario); margin-right: 4px; }
        
        .filter-select, .filter-date {
            padding: 10px 12px;
            border: 1px solid var(--medio);
            border-radius: var(--borda);
            font-size: 13px;
            background: var(--branco);
        }
        .filter-select:focus, .filter-date:focus {
            outline: none;
            border-color: var(--secundario);
        }
        
        .search-box { position: relative; }
        .search-box i:first-child {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }
        .search-box input {
            width: 100%;
            padding: 10px 12px 10px 35px;
            border: 1px solid var(--medio);
            border-radius: var(--borda);
            font-size: 13px;
        }
        .search-box input:focus {
            outline: none;
            border-color: var(--secundario);
        }
        .search-clear {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            text-decoration: none;
        }
        .search-clear:hover { color: var(--perigo); }
        
        .filter-actions {
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }
        
        .btn-filter, .btn-clear {
            padding: 10px 20px;
            border-radius: var(--borda);
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-filter {
            background: var(--secundario);
            color: white;
        }
        .btn-filter:hover { background: #008bb5; transform: translateY(-1px); }
        .btn-clear {
            background: var(--claro);
            color: #666;
        }
        .btn-clear:hover { background: var(--medio); }
        
        /* Logs */
        .logs-card {
            background: var(--branco);
            border-radius: var(--borda);
            padding: 20px;
            box-shadow: var(--sombra);
        }
        
        .logs-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--medio);
        }
        .logs-title {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .logs-title i { color: var(--secundario); }
        .logs-title h2 { font-size: 16px; font-weight: 600; margin: 0; }
        
        .logs-count {
            background: var(--claro);
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
        }
        
        /* Timeline */
        .timeline { position: relative; }
        
        .timeline-date {
            display: flex;
            align-items: center;
            gap: 15px;
            margin: 20px 0 15px;
        }
        
        .date-marker {
            background: var(--secundario);
            color: white;
            border-radius: var(--borda);
            padding: 5px 12px;
            min-width: 65px;
            text-align: center;
        }
        .date-day { font-size: 18px; font-weight: 700; line-height: 1; }
        .date-month { font-size: 9px; text-transform: uppercase; }
        
        .date-line {
            flex: 1;
            height: 1px;
            background: var(--medio);
        }
        
        .timeline-item {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            padding-left: 20px;
            position: relative;
        }
        
        .timeline-icon {
            position: absolute;
            left: -8px;
            top: 0;
        }
        
        .icon-circle {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: white;
            border: 2px solid;
        }
        
        .timeline-content {
            flex: 1;
            background: var(--claro);
            padding: 12px 16px;
            border-radius: var(--borda);
        }
        
        .timeline-time {
            font-size: 10px;
            color: #999;
            margin-bottom: 8px;
        }
        
        .timeline-user {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        .user-avatar {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .user-info strong { font-size: 13px; }
        
        .acao-badge {
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 600;
        }
        
        .timeline-details {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }
        .detail-item {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 12px;
        }
        .detail-item i { color: #999; width: 14px; }
        .detail-label { color: #666; }
        .detail-value { color: var(--escuro); }
        
        /* Paginacao */
        .pagination-modern {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid var(--medio);
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .pagination-info {
            font-size: 12px;
            color: #666;
        }
        
        .pagination-controls {
            display: flex;
            gap: 5px;
            align-items: center;
        }
        
        .page-btn, .page-number {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--borda);
            background: var(--branco);
            border: 1px solid var(--medio);
            color: #666;
            text-decoration: none;
            font-size: 13px;
        }
        .page-number { width: auto; min-width: 32px; }
        .page-btn:hover, .page-number:hover {
            background: var(--claro);
            border-color: var(--secundario);
            color: var(--secundario);
        }
        .page-number.active {
            background: var(--secundario);
            border-color: var(--secundario);
            color: white;
        }
        
        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 50px 20px;
        }
        .empty-icon {
            width: 80px;
            height: 80px;
            background: var(--claro);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }
        .empty-icon i { font-size: 36px; color: #bbb; }
        .empty-state h3 { font-size: 18px; margin-bottom: 8px; }
        .empty-state p { color: #666; margin-bottom: 20px; font-size: 13px; }
        
        .btn-clear-state {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 20px;
            background: var(--claro);
            color: #666;
            border-radius: var(--borda);
            text-decoration: none;
            font-size: 13px;
        }
        .btn-clear-state:hover { background: var(--medio); }
        
        @media (max-width: 992px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.visivel,
            .sidebar.visible { transform: translateX(0); }
            .conteudo-principal { margin-left: 0; }
            .botao-menu-mobile { display: block; }
        }
        
        @media (max-width: 768px) {
            .conteudo-pagina { padding: 15px; }
            .stats-grid { grid-template-columns: 1fr; gap: 12px; }
            .filters-grid { grid-template-columns: 1fr; }
            .timeline-item { padding-left: 15px; }
            .timeline-icon { left: -12px; }
            .timeline-details { flex-direction: column; gap: 6px; }
            .pagination-modern { flex-direction: column; align-items: flex-start; }
            .nome-usuario { display: none; }
        }
    </style>
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<main class="conteudo-principal">
    <?php
    $usuario = $usuario_logado;
    $titulo_topo = $titulo_pagina;
    include 'includes/topbar-fallback.php';
    ?>
    
    <div class="conteudo-pagina">
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-database"></i></div>
                <div class="stat-info">
                    <h3><?= number_format($total_logs, 0, ',', '.') ?></h3>
                    <p>Total de Registos</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-sign-in-alt"></i></div>
                <div class="stat-info">
                    <h3><?= number_format($total_logins, 0, ',', '.') ?></h3>
                    <p>Total de Logins</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon orange"><i class="fas fa-calendar-week"></i></div>
                <div class="stat-info">
                    <h3><?= number_format($total_semana, 0, ',', '.') ?></h3>
                    <p>Ultimos 7 dias</p>
                </div>
            </div>
        </div>

        <div class="filters-card">
            <div class="filters-header" id="filtersHeader">
                <div class="filters-title">
                    <i class="fas fa-sliders-h"></i>
                    <h3>Filtros</h3>
                    <?php if(!empty($filtro_tipo) || !empty($filtro_data_inicio) || !empty($filtro_data_fim) || !empty($filtro_busca)): ?>
                        <span class="filters-active-badge">Ativos</span>
                    <?php endif; ?>
                </div>
                <button class="btn-toggle-filters" id="toggleFilters">
                    <i class="fas fa-chevron-down"></i>
                </button>
            </div>
            
            <form method="GET" class="filters-form" id="filtersForm">
                <div class="filters-grid">
                    <div class="filter-group">
                        <label><i class="fas fa-tag"></i> Tipo de Acao</label>
                        <select name="tipo" class="filter-select">
                            <option value="">Todas as acoes</option>
                            <?php foreach($tipos_acao as $key => $info): ?>
                            <option value="<?= $key ?>" <?= $filtro_tipo == $key ? 'selected' : '' ?>><?= $info['nome'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label><i class="fas fa-calendar-alt"></i> Data Inicio</label>
                        <input type="date" name="data_inicio" class="filter-date" value="<?= $filtro_data_inicio ?>">
                    </div>
                    <div class="filter-group">
                        <label><i class="fas fa-calendar-alt"></i> Data Fim</label>
                        <input type="date" name="data_fim" class="filter-date" value="<?= $filtro_data_fim ?>">
                    </div>
                    <div class="filter-group">
                        <label><i class="fas fa-search"></i> Buscar</label>
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" name="busca" placeholder="Buscar por detalhes, utilizador ou tabela..." value="<?= htmlspecialchars($filtro_busca) ?>">
                            <?php if(!empty($filtro_busca)): ?>
                                <a href="admin-logs.php" class="search-clear"><i class="fas fa-times-circle"></i></a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="filter-actions">
                        <button type="submit" class="btn-filter"><i class="fas fa-search"></i> Filtrar</button>
                        <a href="admin-logs.php" class="btn-clear"><i class="fas fa-undo-alt"></i> Limpar</a>
                    </div>
                </div>
            </form>
        </div>

        <div class="logs-card">
            <div class="logs-header">
                <div class="logs-title">
                    <i class="fas fa-list-ul"></i>
                    <h2>Registos de Atividade</h2>
                </div>
                <div class="logs-count">
                    <i class="fas fa-chart-bar"></i>
                    <span><?= $total_registros ?> registro<?= $total_registros != 1 ? 's' : '' ?></span>
                </div>
            </div>

            <?php if(count($logs) > 0): ?>
                <div class="timeline">
                    <?php 
                    $data_atual = '';
                    foreach($logs as $log): 
                        $data_log = date('Y-m-d', strtotime($log['data_hora']));
                        $cor_acao = obterCorAcao($log['acao']);
                        $icone_acao = obterIconeAcao($log['acao']);
                        $nome_acao = $tipos_acao[$log['acao']]['nome'] ?? ucfirst($log['acao']);
                        
                        if($data_atual != $data_log):
                            $data_atual = $data_log;
                    ?>
                        <div class="timeline-date">
                            <div class="date-marker">
                                <span class="date-day"><?= date('d', strtotime($log['data_hora'])) ?></span>
                                <span class="date-month"><?= ucfirst(date('M', strtotime($log['data_hora']))) ?></span>
                            </div>
                            <div class="date-line"></div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="timeline-item">
                        <div class="timeline-icon">
                            <div class="icon-circle" style="background: <?= $cor_acao ?>15; border-color: <?= $cor_acao ?>;">
                                <i class="fas <?= $icone_acao ?>" style="color: <?= $cor_acao ?>;"></i>
                            </div>
                        </div>
                        <div class="timeline-content">
                            <div class="timeline-time">
                                <i class="far fa-clock"></i>
                                <span><?= date('H:i:s', strtotime($log['data_hora'])) ?></span>
                            </div>
                            <div class="timeline-user">
                                <div class="user-avatar" style="background: <?= $cor_acao ?>20;">
                                    <i class="fas fa-user" style="color: <?= $cor_acao ?>;"></i>
                                </div>
                                <div class="user-info">
                                    <strong><?= htmlspecialchars($log['utilizador_nome'] ?? 'Sistema') ?></strong>
                                    <span class="acao-badge" style="background: <?= $cor_acao ?>15; color: <?= $cor_acao ?>;">
                                        <?= $nome_acao ?>
                                    </span>
                                </div>
                            </div>
                            <div class="timeline-details">
                                <div class="detail-item">
                                    <i class="fas fa-table"></i>
                                    <span class="detail-label">Tabela:</span>
                                    <span class="detail-value"><?= ucfirst($log['tabela'] ?? 'N/A') ?></span>
                                </div>
                                <?php if(!empty($log['detalhes'])): ?>
                                <div class="detail-item">
                                    <i class="fas fa-info-circle"></i>
                                    <span class="detail-label">Detalhes:</span>
                                    <span class="detail-value"><?= htmlspecialchars($log['detalhes']) ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if(!empty($log['ip_address'])): ?>
                                <div class="detail-item">
                                    <i class="fas fa-network-wired"></i>
                                    <span class="detail-label">IP:</span>
                                    <span class="detail-value"><?= htmlspecialchars($log['ip_address']) ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <?php if($total_paginas > 1): ?>
                <div class="pagination-modern">
                    <div class="pagination-info">
                        <i class="fas fa-chart-simple"></i>
                        <span>Pagina <?= $pagina_atual ?> de <?= $total_paginas ?></span>
                        <span class="separator">|</span>
                        <span><?= $total_registros ?> registro<?= $total_registros != 1 ? 's' : '' ?></span>
                    </div>
                    <div class="pagination-controls">
                        <?php if($pagina_atual > 1): ?>
                        <a href="?pagina=1&<?= http_build_query(array_filter(['tipo'=>$filtro_tipo, 'data_inicio'=>$filtro_data_inicio, 'data_fim'=>$filtro_data_fim, 'busca'=>$filtro_busca])) ?>" class="page-btn"><i class="fas fa-angle-double-left"></i></a>
                        <a href="?pagina=<?= $pagina_atual-1 ?>&<?= http_build_query(array_filter(['tipo'=>$filtro_tipo, 'data_inicio'=>$filtro_data_inicio, 'data_fim'=>$filtro_data_fim, 'busca'=>$filtro_busca])) ?>" class="page-btn"><i class="fas fa-chevron-left"></i></a>
                        <?php endif; ?>
                        
                        <?php
                        $start = max(1, $pagina_atual - 2);
                        $end = min($total_paginas, $pagina_atual + 2);
                        for($i = $start; $i <= $end; $i++):
                        ?>
                        <a href="?pagina=<?= $i ?>&<?= http_build_query(array_filter(['tipo'=>$filtro_tipo, 'data_inicio'=>$filtro_data_inicio, 'data_fim'=>$filtro_data_fim, 'busca'=>$filtro_busca])) ?>" class="page-number <?= $i == $pagina_atual ? 'active' : '' ?>"><?= $i ?></a>
                        <?php endfor; ?>
                        
                        <?php if($pagina_atual < $total_paginas): ?>
                        <a href="?pagina=<?= $pagina_atual+1 ?>&<?= http_build_query(array_filter(['tipo'=>$filtro_tipo, 'data_inicio'=>$filtro_data_inicio, 'data_fim'=>$filtro_data_fim, 'busca'=>$filtro_busca])) ?>" class="page-btn"><i class="fas fa-chevron-right"></i></a>
                        <a href="?pagina=<?= $total_paginas ?>&<?= http_build_query(array_filter(['tipo'=>$filtro_tipo, 'data_inicio'=>$filtro_data_inicio, 'data_fim'=>$filtro_data_fim, 'busca'=>$filtro_busca])) ?>" class="page-btn"><i class="fas fa-angle-double-right"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="fas fa-inbox"></i></div>
                    <h3>Nenhum registo encontrado</h3>
                    <p>Tente ajustar os filtros de busca.</p>
                    <a href="admin-logs.php" class="btn-clear-state"><i class="fas fa-undo-alt"></i> Limpar filtros</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<script src="js/admin-sidebar-header.js"></script>
<script>
    const toggleBtn = document.getElementById('toggleFilters');
    const filtersForm = document.getElementById('filtersForm');
    
    if (toggleBtn && filtersForm) {
        toggleBtn.addEventListener('click', () => {
            filtersForm.classList.toggle('collapsed');
            toggleBtn.classList.toggle('collapsed');
        });
        
        const hasFilters = <?php echo !empty($filtro_tipo) || !empty($filtro_data_inicio) || !empty($filtro_data_fim) || !empty($filtro_busca) ? 'true' : 'false'; ?>;
        if (!hasFilters) {
            filtersForm.classList.add('collapsed');
        }
    }
    
    document.getElementById('botaoPerfil')?.addEventListener('click', function() {
        document.getElementById('dropdownPerfil').classList.toggle('ativo');
    });
    
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.container-perfil')) {
            document.getElementById('dropdownPerfil')?.classList.remove('ativo');
        }
    });
    
</script>
</body>
</html>
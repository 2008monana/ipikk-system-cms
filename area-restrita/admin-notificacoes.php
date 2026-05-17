<?php
/**
 * Notificações - Área Restrita IPIKK
 * Visualização e gestão das notificações geradas automaticamente pelo sistema
 */

$titulo_pagina = 'Notificações';
$css_especifico = 'admin-notificacoes.css';

require_once dirname(__DIR__) . '/config/index.php';

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

if ($nivel !== 'admin' && !in_array('galeria', $permissoes) && !in_array('*', $permissoes)) {
    header('Location: admin-dashboard.php?erro=permissao');
    exit;
}

$db = getDB();

$stmt = $db->prepare("SELECT id, nome, email, foto_url, nivel, permissoes FROM utilizadores WHERE id = ?");
$stmt->execute([$_SESSION['utilizador_id']]);
$usuario_logado = $stmt->fetch();

if (!$usuario_logado) {
    session_destroy();
    header('Location: area-restrita.php');
    exit;
}

$config = $db->query("SELECT * FROM configuracoes WHERE id = 1")->fetch();

// ============================================
// BUSCAR NOTIFICAÇÕES (geradas automaticamente)
// ============================================

// Filtrar por utilizador (admin vê todas; editor vê apenas as suas e as globais)
if ($usuario_logado['nivel'] === 'admin') {
    $stmt = $db->prepare("
        SELECT * FROM notificacoes 
        ORDER BY data_criacao DESC
    ");
    $stmt->execute();
} else {
    $stmt = $db->prepare("
        SELECT * FROM notificacoes 
        WHERE para_utilizador_id IS NULL OR para_utilizador_id = ? 
        ORDER BY data_criacao DESC
    ");
    $stmt->execute([$_SESSION['utilizador_id']]);
}
$notificacoes = $stmt->fetchAll();

// ============================================
// ESTATÍSTICAS
// ============================================
$total_notificacoes = count($notificacoes);
$nao_lidas = count(array_filter($notificacoes, fn($n) => $n['lida'] == 0));
$lidas = $total_notificacoes - $nao_lidas;

// Tipos de notificação com ícones e cores
$tipos = [
    'sistema' => ['icone' => 'fa-shield-alt', 'cor' => '#6366f1', 'label' => 'Sistema'],
    'contacto' => ['icone' => 'fa-envelope', 'cor' => '#10b981', 'label' => 'Contacto'],
    'noticia' => ['icone' => 'fa-newspaper', 'cor' => '#f59e0b', 'label' => 'Notícia'],
    'curso' => ['icone' => 'fa-graduation-cap', 'cor' => '#3b82f6', 'label' => 'Curso'],
    'usuario' => ['icone' => 'fa-user', 'cor' => '#8b5cf6', 'label' => 'Utilizador'],
    'inscricao' => ['icone' => 'fa-file-signature', 'cor' => '#ef4444', 'label' => 'Inscrição']
];

$prioridades = [
    'alta' => ['cor' => '#ef4444', 'label' => 'Alta'],
    'media' => ['cor' => '#f59e0b', 'label' => 'Média'],
    'baixa' => ['cor' => '#10b981', 'label' => 'Baixa']
];

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<main class="conteudo-principal">
    <header class="barra-superior">
        <div class="esquerda-barra">
            <button class="botao-menu-mobile" id="botaoMenuMobile">
                <i class="fas fa-bars"></i>
            </button>
            <h1 class="titulo-pagina">
                <i class="fas fa-bell"></i> Notificações
            </h1>
        </div>
        <div class="direita-barra">
            <span class="info-sistema">
                <i class="fas fa-info-circle"></i> Notificações geradas automaticamente pelo sistema
            </span>
        </div>
    </header>

    <div class="conteudo-pagina">

        <!-- CARDS ESTATÍSTICAS -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon purple"><i class="fas fa-bell"></i></div>
                <div class="stat-info">
                    <h3><?= $total_notificacoes ?></h3>
                    <p>Total de Notificações</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon warning"><i class="fas fa-circle"></i></div>
                <div class="stat-info">
                    <h3><?= $nao_lidas ?></h3>
                    <p>Não Lidas</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon success"><i class="fas fa-check-circle"></i></div>
                <div class="stat-info">
                    <h3><?= $lidas ?></h3>
                    <p>Lidas</p>
                </div>
            </div>
        </div>

        <!-- FILTROS -->
        <div class="filtros-card">
            <div class="filtros-header">
                <div class="filtros-title">
                    <i class="fas fa-filter"></i>
                    <h3>Filtros</h3>
                </div>
                <button class="btn-toggle-filtros" id="toggleFiltros">
                    <i class="fas fa-chevron-down"></i>
                </button>
            </div>
            <div class="filtros-form" id="filtrosForm">
                <div class="filtros-grid">
                    <div class="filter-group">
                        <label><i class="fas fa-tag"></i> Tipo</label>
                        <select id="filtroTipo" class="filter-select">
                            <option value="">Todos os tipos</option>
                            <?php foreach ($tipos as $key => $tipo): ?>
                            <option value="<?= $key ?>"><?= $tipo['label'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label><i class="fas fa-flag"></i> Prioridade</label>
                        <select id="filtroPrioridade" class="filter-select">
                            <option value="">Todas</option>
                            <?php foreach ($prioridades as $key => $prioridade): ?>
                            <option value="<?= $key ?>"><?= $prioridade['label'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label><i class="fas fa-circle"></i> Estado</label>
                        <select id="filtroEstado" class="filter-select">
                            <option value="">Todas</option>
                            <option value="0">Não Lidas</option>
                            <option value="1">Lidas</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label><i class="fas fa-search"></i> Buscar</label>
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" id="campoBusca" placeholder="Buscar por título ou mensagem...">
                        </div>
                    </div>
                    <div class="filter-actions">
                        <button class="btn-filter" id="btnAplicarFiltros"><i class="fas fa-search"></i> Aplicar</button>
                        <button class="btn-clear" id="btnLimparFiltros"><i class="fas fa-undo"></i> Limpar</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- AÇÕES EM MASSA -->
        <div class="acoes-massa">
            <button class="btn-acao" id="btnMarcarTodasLidas">
                <i class="fas fa-check-double"></i> Marcar todas como lidas
            </button>
            <button class="btn-acao" id="btnMarcarSelecionadasLidas" disabled>
                <i class="fas fa-check-circle"></i> Marcar selecionadas como lidas
            </button>
            <button class="btn-acao btn-perigo" id="btnEliminarSelecionadas" disabled>
                <i class="fas fa-trash"></i> Eliminar selecionadas
            </button>
        </div>

        <!-- LISTA DE NOTIFICAÇÕES -->
        <div class="notificacoes-container">
            <?php if (empty($notificacoes)): ?>
            <div class="empty-state">
                <i class="fas fa-bell-slash"></i>
                <h3>Nenhuma notificação</h3>
                <p>As notificações aparecerão aqui automaticamente quando ocorrerem eventos importantes no sistema.</p>
            </div>
            <?php else: ?>
                <div class="lista-notificacoes" id="listaNotificacoes">
                    <?php foreach ($notificacoes as $notif): 
                        $tipo = $tipos[$notif['tipo']] ?? $tipos['sistema'];
                        $prioridade = $prioridades[$notif['prioridade']] ?? $prioridades['media'];
                        $tempo_relativo = tempoRelativo($notif['data_criacao']);
                    ?>
                    <div class="notificacao-item <?= $notif['lida'] ? '' : 'nao-lida' ?>" data-id="<?= $notif['id'] ?>">
                        <div class="notificacao-checkbox">
                            <input type="checkbox" class="checkbox-notificacao" data-id="<?= $notif['id'] ?>">
                        </div>
                        <div class="notificacao-icone" style="background: <?= $tipo['cor'] ?>20; color: <?= $tipo['cor'] ?>;">
                            <i class="fas <?= $tipo['icone'] ?>"></i>
                        </div>
                        <div class="notificacao-conteudo">
                            <div class="notificacao-cabecalho">
                                <div class="notificacao-titulo">
                                    <?= htmlspecialchars($notif['titulo']) ?>
                                    <span class="badge-prioridade" style="background: <?= $prioridade['cor'] ?>20; color: <?= $prioridade['cor'] ?>;">
                                        <i class="fas fa-flag"></i> <?= $prioridade['label'] ?>
                                    </span>
                                    <span class="badge-tipo" style="background: <?= $tipo['cor'] ?>20; color: <?= $tipo['cor'] ?>;">
                                        <i class="fas <?= $tipo['icone'] ?>"></i> <?= $tipo['label'] ?>
                                    </span>
                                </div>
                                <div class="notificacao-data">
                                    <i class="far fa-clock"></i> <?= $tempo_relativo ?>
                                </div>
                            </div>
                            <div class="notificacao-mensagem">
                                <?= nl2br(htmlspecialchars($notif['mensagem'])) ?>
                            </div>
                            <?php if (!empty($notif['referencia_tabela']) && !empty($notif['referencia_id'])): ?>
                            <div class="notificacao-referencia">
                                <i class="fas fa-link"></i>
                                Referência: <?= ucfirst($notif['referencia_tabela']) ?> #<?= $notif['referencia_id'] ?>
                                <?php if (!empty($notif['acao_link'])): ?>
                                <a href="<?= htmlspecialchars($notif['acao_link']) ?>" class="btn-link">
                                    Ver detalhes <i class="fas fa-arrow-right"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="notificacao-acoes">
                            <?php if (!$notif['lida']): ?>
                            <button class="btn-marcar-lida" title="Marcar como lida" data-id="<?= $notif['id'] ?>">
                                <i class="fas fa-check"></i>
                            </button>
                            <?php endif; ?>
                            <button class="btn-eliminar" title="Eliminar" data-id="<?= $notif['id'] ?>">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- MODAL CONFIRMAÇÃO -->
<div id="modalConfirmacao" class="modal-confirmacao">
    <div class="modal-confirmacao-caixa">
        <div class="modal-confirmacao-icone">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h3 id="confirmacaoTitulo">Confirmar ação</h3>
        <p id="confirmacaoTexto">Tem certeza que deseja continuar?</p>
        <div class="modal-confirmacao-botoes">
            <button class="botao-cancelar-modal" id="btnCancelarConfirmacao">Cancelar</button>
            <button class="botao-confirmar-modal" id="btnConfirmarAcao">Confirmar</button>
        </div>
    </div>
</div>

<style>
/* ===== ESTILOS ADMIN NOTIFICAÇÕES ===== */

.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    border-radius: 16px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.stat-icon {
    width: 55px;
    height: 55px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}

.stat-icon.purple { background: #e8eaff; color: #6366f1; }
.stat-icon.warning { background: #fff3e0; color: #f59e0b; }
.stat-icon.success { background: #e8f5e9; color: #2e7d32; }

.stat-info h3 {
    font-size: 28px;
    font-weight: 700;
    margin: 0;
}

.stat-info p {
    font-size: 13px;
    color: #6c757d;
    margin: 5px 0 0;
}

.info-sistema {
    font-size: 12px;
    color: #6c757d;
    background: #f0f0f0;
    padding: 6px 12px;
    border-radius: 20px;
}

.info-sistema i {
    margin-right: 5px;
    color: #0a9396;
}

/* Filtros */
.filtros-card {
    background: white;
    border-radius: 16px;
    margin-bottom: 30px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.filtros-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    cursor: pointer;
}

.filtros-title {
    display: flex;
    align-items: center;
    gap: 10px;
}

.filtros-title i { color: #0a9396; }
.filtros-title h3 { font-size: 16px; margin: 0; }

.btn-toggle-filtros {
    background: none;
    border: none;
    cursor: pointer;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    transition: all 0.3s;
}

.btn-toggle-filtros:hover { background: #f0f0f0; }
.btn-toggle-filtros.collapsed i { transform: rotate(-180deg); }

.filtros-form {
    padding: 0 20px;
    max-height: 300px;
    overflow: hidden;
    transition: all 0.3s ease;
    border-top: 1px solid #eef2f6;
}

.filtros-form.collapsed {
    max-height: 0;
    padding: 0 20px;
    border-top: none;
}

.filtros-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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

.filter-select {
    padding: 10px 12px;
    border: 1px solid #e0e4e8;
    border-radius: 10px;
    font-size: 13px;
    background: white;
}

.search-box {
    position: relative;
}

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
    border: 1px solid #e0e4e8;
    border-radius: 10px;
    font-size: 13px;
}

.filter-actions {
    display: flex;
    gap: 10px;
    align-items: flex-end;
}

.btn-filter, .btn-clear {
    padding: 10px 20px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 13px;
    cursor: pointer;
    border: none;
}

.btn-filter {
    background: #0a9396;
    color: white;
}

.btn-clear {
    background: #f0f0f0;
    color: #666;
}

/* Ações em massa */
.acoes-massa {
    display: flex;
    gap: 15px;
    margin-bottom: 25px;
    flex-wrap: wrap;
}

.btn-acao {
    background: #f0f0f0;
    border: none;
    padding: 10px 18px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-acao:hover:not(:disabled) {
    background: #0a9396;
    color: white;
}

.btn-acao.btn-perigo:hover:not(:disabled) {
    background: #dc3545;
    color: white;
}

.btn-acao:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Lista de notificações */
.notificacoes-container {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.lista-notificacoes {
    display: flex;
    flex-direction: column;
}

.notificacao-item {
    display: flex;
    align-items: flex-start;
    gap: 15px;
    padding: 20px 25px;
    border-bottom: 1px solid #eef2f6;
    transition: all 0.2s;
    position: relative;
}

.notificacao-item:hover {
    background: #fafcfc;
}

.notificacao-item.nao-lida {
    background: rgba(10, 147, 150, 0.03);
    border-left: 3px solid #0a9396;
}

.notificacao-checkbox {
    padding-top: 2px;
}

.checkbox-notificacao {
    width: 18px;
    height: 18px;
    cursor: pointer;
    accent-color: #0a9396;
}

.notificacao-icone {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    flex-shrink: 0;
}

.notificacao-conteudo {
    flex: 1;
}

.notificacao-cabecalho {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 8px;
}

.notificacao-titulo {
    font-weight: 700;
    font-size: 15px;
    color: #1a2c3e;
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.notificacao-data {
    font-size: 12px;
    color: #888;
}

.badge-prioridade, .badge-tipo {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 8px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 600;
}

.notificacao-mensagem {
    font-size: 13px;
    color: #4a5a6e;
    line-height: 1.6;
    margin-bottom: 8px;
}

.notificacao-referencia {
    font-size: 11px;
    color: #888;
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.btn-link {
    color: #0a9396;
    text-decoration: none;
    font-size: 11px;
    font-weight: 600;
}

.btn-link:hover {
    text-decoration: underline;
}

.notificacao-acoes {
    display: flex;
    gap: 5px;
    opacity: 0;
    transition: opacity 0.2s;
}

.notificacao-item:hover .notificacao-acoes {
    opacity: 1;
}

.btn-marcar-lida, .btn-eliminar {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-marcar-lida {
    background: #e8f5e9;
    color: #2e7d32;
}

.btn-marcar-lida:hover {
    background: #2e7d32;
    color: white;
}

.btn-eliminar {
    background: #fee2e2;
    color: #dc3545;
}

.btn-eliminar:hover {
    background: #dc3545;
    color: white;
}

.empty-state {
    text-align: center;
    padding: 60px;
}

.empty-state i {
    font-size: 64px;
    color: #cbd5e1;
    margin-bottom: 20px;
    display: block;
}

.empty-state h3 {
    font-size: 20px;
    margin-bottom: 10px;
}

.empty-state p {
    color: #6c757d;
    margin-bottom: 20px;
}

.modal-confirmacao {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.7);
    z-index: 10001;
    align-items: center;
    justify-content: center;
}

.modal-confirmacao.ativo { display: flex; }

.modal-confirmacao-caixa {
    background: white;
    border-radius: 20px;
    padding: 30px;
    max-width: 400px;
    text-align: center;
}

.modal-confirmacao-icone {
    width: 60px;
    height: 60px;
    margin: 0 auto 15px;
    background: #fee2e2;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: #dc3545;
}

.modal-confirmacao-botoes {
    display: flex;
    gap: 15px;
    justify-content: center;
    margin-top: 25px;
}

.botao-cancelar-modal, .botao-confirmar-modal {
    padding: 10px 20px;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    border: none;
}

.botao-cancelar-modal {
    background: #f0f0f0;
    color: #666;
}

.botao-confirmar-modal {
    background: #dc3545;
    color: white;
}

.notificacao-flutuante {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 14px 24px;
    color: white;
    border-radius: 12px;
    z-index: 99999;
    font-weight: 600;
    animation: slideIn 0.3s;
}

.notificacao-flutuante.sucesso { background: linear-gradient(135deg, #28a745, #1e7e34); }
.notificacao-flutuante.erro { background: linear-gradient(135deg, #dc3545, #c82333); }

@keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

/* Responsividade */
@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .filtros-grid {
        grid-template-columns: 1fr;
    }
    
    .acoes-massa {
        flex-direction: column;
    }
    
    .btn-acao {
        width: 100%;
        justify-content: center;
    }
    
    .notificacao-item {
        flex-wrap: wrap;
        padding: 15px;
    }
    
    .notificacao-acoes {
        width: 100%;
        justify-content: flex-end;
        opacity: 1;
    }
}
</style>

<script>
let notificacoesSelecionadas = [];

function mostrarNotificacao(mensagem, tipo = 'sucesso') {
    const notif = document.createElement('div');
    notif.className = `notificacao-flutuante ${tipo}`;
    notif.innerHTML = `<i class="fas fa-${tipo === 'sucesso' ? 'check-circle' : 'exclamation-circle'}"></i> ${mensagem}`;
    document.body.appendChild(notif);
    setTimeout(() => notif.remove(), 3000);
}

function toggleFiltros() {
    const form = document.getElementById('filtrosForm');
    const btn = document.getElementById('toggleFiltros');
    form.classList.toggle('collapsed');
    btn.classList.toggle('collapsed');
}

function aplicarFiltros() {
    const tipo = document.getElementById('filtroTipo').value;
    const prioridade = document.getElementById('filtroPrioridade').value;
    const estado = document.getElementById('filtroEstado').value;
    const busca = document.getElementById('campoBusca').value.toLowerCase();
    
    const itens = document.querySelectorAll('.notificacao-item');
    let visiveis = 0;
    
    itens.forEach(item => {
        let mostrar = true;
        
        if (tipo) {
            const tipoIcone = item.querySelector('.notificacao-icone i')?.className || '';
            const mapTipo = {
                'fa-shield-alt': 'sistema',
                'fa-envelope': 'contacto',
                'fa-newspaper': 'noticia',
                'fa-graduation-cap': 'curso',
                'fa-user': 'usuario',
                'fa-file-signature': 'inscricao'
            };
            const itemTipo = Object.entries(mapTipo).find(([icone]) => tipoIcone.includes(icone))?.[1] || '';
            if (itemTipo !== tipo) mostrar = false;
        }
        
        if (prioridade) {
            const badgePrioridade = item.querySelector('.badge-prioridade')?.textContent || '';
            const prioridadesMap = { 'Alta': 'alta', 'Média': 'media', 'Baixa': 'baixa' };
            const itemPrioridade = prioridadesMap[badgePrioridade.trim()] || '';
            if (itemPrioridade !== prioridade) mostrar = false;
        }
        
        if (estado !== '') {
            const isLida = !item.classList.contains('nao-lida');
            if (estado === '1' && !isLida) mostrar = false;
            if (estado === '0' && isLida) mostrar = false;
        }
        
        if (busca) {
            const titulo = item.querySelector('.notificacao-titulo')?.textContent.toLowerCase() || '';
            const mensagem = item.querySelector('.notificacao-mensagem')?.textContent.toLowerCase() || '';
            if (!titulo.includes(busca) && !mensagem.includes(busca)) mostrar = false;
        }
        
        item.style.display = mostrar ? '' : 'none';
        if (mostrar) visiveis++;
    });
    
    if (visiveis === 0 && document.querySelector('.lista-notificacoes') && !document.querySelector('.no-results')) {
        const noResults = document.createElement('div');
        noResults.className = 'empty-state no-results';
        noResults.innerHTML = '<i class="fas fa-search"></i><h3>Nenhum resultado encontrado</h3><p>Tente ajustar os filtros de busca.</p>';
        document.querySelector('.lista-notificacoes').appendChild(noResults);
    } else {
        document.querySelector('.no-results')?.remove();
    }
}

function limparFiltros() {
    document.getElementById('filtroTipo').value = '';
    document.getElementById('filtroPrioridade').value = '';
    document.getElementById('filtroEstado').value = '';
    document.getElementById('campoBusca').value = '';
    aplicarFiltros();
}

function atualizarEstadoBotoesMassa() {
    const temSelecao = notificacoesSelecionadas.length > 0;
    document.getElementById('btnMarcarSelecionadasLidas').disabled = !temSelecao;
    document.getElementById('btnEliminarSelecionadas').disabled = !temSelecao;
}

function atualizarCheckboxes() {
    const checkboxes = document.querySelectorAll('.checkbox-notificacao');
    notificacoesSelecionadas = [];
    checkboxes.forEach(cb => {
        const id = parseInt(cb.dataset.id);
        if (cb.checked && id) notificacoesSelecionadas.push(id);
    });
    atualizarEstadoBotoesMassa();
}

async function marcarComoLida(id) {
    try {
        const response = await fetch(window.location.href, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ action: 'marcar_lida', id: id })
        });
        const data = await response.json();
        if (data.success) {
            const item = document.querySelector(`.notificacao-item[data-id="${id}"]`);
            if (item) {
                item.classList.remove('nao-lida');
                const btnMarcar = item.querySelector('.btn-marcar-lida');
                if (btnMarcar) btnMarcar.remove();
                
                const totalNaoLidas = document.querySelectorAll('.notificacao-item.nao-lida').length;
                document.querySelector('.stat-card.warning .stat-info h3').textContent = totalNaoLidas;
                document.querySelector('.stat-card.success .stat-info h3').textContent = 
                    document.querySelectorAll('.notificacao-item').length - totalNaoLidas;
            }
            mostrarNotificacao(data.message, 'sucesso');
        }
    } catch (error) {
        mostrarNotificacao('Erro ao marcar como lida', 'erro');
    }
}

async function marcarTodasLidas() {
    try {
        const response = await fetch(window.location.href, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ action: 'marcar_todas_lidas' })
        });
        const data = await response.json();
        if (data.success) {
            document.querySelectorAll('.notificacao-item.nao-lida').forEach(item => {
                item.classList.remove('nao-lida');
                const btn = item.querySelector('.btn-marcar-lida');
                if (btn) btn.remove();
            });
            document.querySelector('.stat-card.warning .stat-info h3').textContent = '0';
            document.querySelector('.stat-card.success .stat-info h3').textContent = 
                document.querySelectorAll('.notificacao-item').length;
            mostrarNotificacao(data.message, 'sucesso');
        }
    } catch (error) {
        mostrarNotificacao('Erro ao marcar notificações', 'erro');
    }
}

async function marcarSelecionadasLidas() {
    if (notificacoesSelecionadas.length === 0) {
        mostrarNotificacao('Selecione pelo menos uma notificação', 'aviso');
        return;
    }
    
    for (const id of notificacoesSelecionadas) {
        await marcarComoLida(id);
    }
    notificacoesSelecionadas = [];
    document.querySelectorAll('.checkbox-notificacao').forEach(cb => cb.checked = false);
    atualizarEstadoBotoesMassa();
}

async function eliminarNotificacao(id) {
    const modal = document.getElementById('modalConfirmacao');
    document.getElementById('confirmacaoTitulo').textContent = 'Eliminar Notificação';
    document.getElementById('confirmacaoTexto').textContent = 'Tem certeza que deseja eliminar esta notificação permanentemente?';
    
    const confirmar = document.getElementById('btnConfirmarAcao');
    const novoConfirmar = confirmar.cloneNode(true);
    confirmar.parentNode.replaceChild(novoConfirmar, confirmar);
    
    novoConfirmar.onclick = async () => {
        try {
            const response = await fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ action: 'eliminar_notificacao', id: id })
            });
            const data = await response.json();
            if (data.success) {
                const item = document.querySelector(`.notificacao-item[data-id="${id}"]`);
                if (item) item.remove();
                mostrarNotificacao(data.message, 'sucesso');
                
                const total = document.querySelectorAll('.notificacao-item').length;
                const naoLidas = document.querySelectorAll('.notificacao-item.nao-lida').length;
                document.querySelector('.stat-card.purple .stat-info h3').textContent = total;
                document.querySelector('.stat-card.warning .stat-info h3').textContent = naoLidas;
                document.querySelector('.stat-card.success .stat-info h3').textContent = total - naoLidas;
                
                if (total === 0) {
                    location.reload();
                }
            }
        } catch (error) {
            mostrarNotificacao('Erro ao eliminar', 'erro');
        }
        modal.classList.remove('ativo');
    };
    modal.classList.add('ativo');
}

async function eliminarSelecionadas() {
    if (notificacoesSelecionadas.length === 0) {
        mostrarNotificacao('Selecione pelo menos uma notificação', 'aviso');
        return;
    }
    
    const modal = document.getElementById('modalConfirmacao');
    document.getElementById('confirmacaoTitulo').textContent = 'Eliminar Notificações';
    document.getElementById('confirmacaoTexto').textContent = `Tem certeza que deseja eliminar ${notificacoesSelecionadas.length} notificação(ões) permanentemente?`;
    
    const confirmar = document.getElementById('btnConfirmarAcao');
    const novoConfirmar = confirmar.cloneNode(true);
    confirmar.parentNode.replaceChild(novoConfirmar, confirmar);
    
    novoConfirmar.onclick = async () => {
        let sucesso = 0;
        for (const id of notificacoesSelecionadas) {
            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ action: 'eliminar_notificacao', id: id })
                });
                const data = await response.json();
                if (data.success) {
                    sucesso++;
                    const item = document.querySelector(`.notificacao-item[data-id="${id}"]`);
                    if (item) item.remove();
                }
            } catch (error) {}
        }
        
        mostrarNotificacao(`${sucesso} notificação(ões) eliminada(s)`, 'sucesso');
        notificacoesSelecionadas = [];
        document.querySelectorAll('.checkbox-notificacao').forEach(cb => cb.checked = false);
        atualizarEstadoBotoesMassa();
        
        const total = document.querySelectorAll('.notificacao-item').length;
        const naoLidas = document.querySelectorAll('.notificacao-item.nao-lida').length;
        document.querySelector('.stat-card.purple .stat-info h3').textContent = total;
        document.querySelector('.stat-card.warning .stat-info h3').textContent = naoLidas;
        document.querySelector('.stat-card.success .stat-info h3').textContent = total - naoLidas;
        
        if (total === 0) location.reload();
        modal.classList.remove('ativo');
    };
    modal.classList.add('ativo');
}

function fecharModalConfirmacao() {
    document.getElementById('modalConfirmacao').classList.remove('ativo');
}

document.getElementById('toggleFiltros')?.addEventListener('click', toggleFiltros);
document.getElementById('btnAplicarFiltros')?.addEventListener('click', aplicarFiltros);
document.getElementById('btnLimparFiltros')?.addEventListener('click', limparFiltros);
document.getElementById('btnMarcarTodasLidas')?.addEventListener('click', marcarTodasLidas);
document.getElementById('btnMarcarSelecionadasLidas')?.addEventListener('click', marcarSelecionadasLidas);
document.getElementById('btnEliminarSelecionadas')?.addEventListener('click', eliminarSelecionadas);
document.getElementById('btnCancelarConfirmacao')?.addEventListener('click', fecharModalConfirmacao);

document.querySelectorAll('.btn-marcar-lida')?.forEach(btn => {
    btn.addEventListener('click', (e) => {
        e.stopPropagation();
        const id = parseInt(btn.dataset.id);
        if (id) marcarComoLida(id);
    });
});

document.querySelectorAll('.btn-eliminar')?.forEach(btn => {
    btn.addEventListener('click', (e) => {
        e.stopPropagation();
        const id = parseInt(btn.dataset.id);
        if (id) eliminarNotificacao(id);
    });
});

document.querySelectorAll('.checkbox-notificacao')?.forEach(cb => {
    cb.addEventListener('change', atualizarCheckboxes);
});

document.getElementById('modalConfirmacao')?.addEventListener('click', (e) => {
    if (e.target === document.getElementById('modalConfirmacao')) fecharModalConfirmacao();
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') fecharModalConfirmacao();
});

document.getElementById('campoBusca')?.addEventListener('input', aplicarFiltros);
document.getElementById('filtroTipo')?.addEventListener('change', aplicarFiltros);
document.getElementById('filtroPrioridade')?.addEventListener('change', aplicarFiltros);
document.getElementById('filtroEstado')?.addEventListener('change', aplicarFiltros);

aplicarFiltros();
</script>

<?php include 'includes/footer.php'; ?>
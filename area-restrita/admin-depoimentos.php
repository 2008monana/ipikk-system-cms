<?php
/**
 * Depoimentos - Área Restrita IPIKK
 * Gestão completa de depoimentos de alumni
 */

$titulo_pagina = 'Depoimentos';
$css_especifico = 'admin-depoimentos.css';

$base_path = dirname(__DIR__);
require_once $base_path . '/config/index.php';

if (!isset($_SESSION['utilizador_id'])) {
    header('Location: area-restrita.php');
    exit;
}

require_once __DIR__ . '/includes/verificar-permissao.php';

// ===== VERIFICAR PERMISSÃO ESPECÍFICA =====
verificarPermissao('oferta_formativa');

$db = getDB();

$stmt = $db->prepare("SELECT id, nome, email, foto_url, nivel, permissoes FROM utilizadores WHERE id = ?");
$stmt->execute([$_SESSION['utilizador_id']]);
$usuario_logado = $stmt->fetch();

if (!$usuario_logado) {
    session_destroy();
    header('Location: area-restrita.php');
    exit;
}

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

$config = $db->query("SELECT * FROM configuracoes WHERE id = 1")->fetch();

// Buscar cursos ativos
$cursos = $db->query("SELECT id, nome, area_id FROM cursos WHERE estado = 'ativo' ORDER BY nome")->fetchAll();

// Buscar depoimentos
$depoimentos = $db->query("
    SELECT d.*, c.nome as curso_nome 
    FROM depoimentos d
    JOIN cursos c ON c.id = d.curso_id
    ORDER BY d.destaque DESC, d.ordem ASC, d.created_at DESC
")->fetchAll();

// Estatísticas
$total_depoimentos = count($depoimentos);
$total_destaques = count(array_filter($depoimentos, fn($d) => $d['destaque'] == 1));
$total_empresas = count(array_unique(array_column($depoimentos, 'empresa')));

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<main class="conteudo-principal">
    <header class="barra-superior">
        <div class="esquerda-barra">
            <button class="botao-menu-mobile" id="botaoMenuMobile" onclick="window.openSidebar && window.openSidebar()">
            <i class="fas fa-bars"></i>
            </button>
            <h1 class="titulo-pagina">
                <i class="fas fa-quote-right"></i> Depoimentos
            </h1>
        </div>
        <div class="direita-barra">
            <button class="btn-primario" id="btnNovoDepoimento">
                <i class="fas fa-plus"></i>
                <span>Novo Depoimento</span>
            </button>
        </div>
    </header>

    <div class="conteudo-pagina">

        <!-- CARDS ESTATÍSTICAS -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon purple"><i class="fas fa-quote-right"></i></div>
                <div class="stat-info">
                    <h3><?= $total_depoimentos ?></h3>
                    <p>Total de Depoimentos</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon gold"><i class="fas fa-star"></i></div>
                <div class="stat-info">
                    <h3><?= $total_destaques ?></h3>
                    <p>Em Destaque</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-building"></i></div>
                <div class="stat-info">
                    <h3><?= $total_empresas ?></h3>
                    <p>Empresas</p>
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
                        <label><i class="fas fa-search"></i> Buscar</label>
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" id="campoBusca" placeholder="Buscar por nome, curso ou empresa...">
                        </div>
                    </div>
                    <div class="filter-group">
                        <label><i class="fas fa-star"></i> Destaque</label>
                        <select id="filtroDestaque" class="filter-select">
                            <option value="">Todos</option>
                            <option value="1">Em Destaque</option>
                            <option value="0">Normais</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label><i class="fas fa-graduation-cap"></i> Curso</label>
                        <select id="filtroCurso" class="filter-select">
                            <option value="">Todos os cursos</option>
                            <?php foreach ($cursos as $curso): ?>
                            <option value="<?= $curso['id'] ?>"><?= htmlspecialchars($curso['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label><i class="fas fa-user-tag"></i> Tipo</label>
                        <select id="filtroTipo" class="filter-select">
                            <option value="">Todos</option>
                            <option value="ex_aluno">Ex-Alunos</option>
                            <option value="atual">Alunos Atuais</option>
                        </select>
                    </div>
                    <div class="filter-actions">
                        <button class="btn-filter" id="btnAplicarFiltros"><i class="fas fa-search"></i> Aplicar</button>
                        <button class="btn-clear" id="btnLimparFiltros"><i class="fas fa-undo"></i> Limpar</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- LISTA DE DEPOIMENTOS -->
        <div class="secao-conteudo">
            <div class="secao-header">
                <h2 class="secao-titulo">
                    <i class="fas fa-list"></i> Todos os Depoimentos
                    <span class="contador" id="contadorDepoimentos">(<?= $total_depoimentos ?> registos)</span>
                </h2>
            </div>

            <div class="tabela-responsiva">
                <table class="tabela-dados" id="tabelaDepoimentos">
                    <thead>
                        <tr>
                            <th width="60">Foto</th>
                            <th>Nome</th>
                            <th>Curso / Turma</th>
                            <th>Tipo</th>
                            <th>Empresa</th>
                            <th width="100">Destaque</th>
                            <th width="120">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="corpoTabelaDepoimentos">
                        <?php if (empty($depoimentos)): ?>
                        <tr>
                            <td colspan="6" class="empty-table">Nenhum depoimento cadastrado. Clique em "Novo Depoimento" para começar.</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($depoimentos as $depoimento): ?>
                            <tr data-id="<?= $depoimento['id'] ?>" data-destaque="<?= $depoimento['destaque'] ?>" data-curso="<?= $depoimento['curso_id'] ?>" data-tipo="<?= $depoimento['tipo_depoimento'] ?? 'ex_aluno' ?>">
                                <td class="foto-coluna">
                                    <img src="<?= htmlspecialchars(normalizarUrlMidia($depoimento['foto_url'] ?? 'foto/sem_foto.png', '..')) ?>" 
                                         class="foto-miniatura" 
                                         alt="<?= htmlspecialchars($depoimento['nome']) ?>"
                                         onerror="this.src='foto/sem_foto.png'">
                                </td>
                                <td class="nome-coluna">
                                    <strong><?= htmlspecialchars($depoimento['nome']) ?></strong>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($depoimento['curso_nome']) ?></strong><br>
                                    <small>Turma: <?= htmlspecialchars($depoimento['turma']) ?></small>
                                </td>
                                <td>
                                    <?php $tipo = $depoimento['tipo_depoimento'] ?? 'ex_aluno'; ?>
                                    <span class="<?= $tipo === 'atual' ? 'badge-tipo-atual' : 'badge-tipo-ex' ?>"><?= $tipo === 'atual' ? 'Aluno Atual' : 'Ex-Aluno' ?></span>
                                </td>
                                <td><?= htmlspecialchars($depoimento['empresa']) ?></td>
                                <td class="destaque-coluna">
                                    <?php if ($depoimento['destaque']): ?>
                                    <span class="badge-destaque"><i class="fas fa-star"></i> Destaque</span>
                                    <?php else: ?>
                                    <span class="badge-normal"><i class="far fa-star"></i> Normal</span>
                                    <?php endif; ?>
                                </td>
                                <td class="acoes-coluna">
                                    <button class="btn-editar" onclick="editarDepoimento(<?= $depoimento['id'] ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn-eliminar" onclick="eliminarDepoimento(<?= $depoimento['id'] ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- MODAL DEPOIMENTO -->
<div id="modalDepoimento" class="modal">
    <div class="modal-conteudo">
        <div class="modal-cabecalho">
            <h2 id="modalTitulo"><i class="fas fa-plus"></i> Novo Depoimento</h2>
            <button class="modal-fechar" onclick="fecharModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-corpo">
            <form id="formDepoimento" onsubmit="return salvarDepoimento(event)">
                <input type="hidden" id="depoimentoId">

                <div class="linha-form">
                    <div class="grupo-form">
                        <label><i class="fas fa-user"></i> Nome *</label>
                        <input type="text" id="campoNome" class="campo-form" required>
                    </div>
                    <div class="grupo-form">
                        <label><i class="fas fa-graduation-cap"></i> Curso *</label>
                        <select id="campoCursoId" class="campo-form" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($cursos as $curso): ?>
                            <option value="<?= $curso['id'] ?>"><?= htmlspecialchars($curso['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="linha-form">
                    <div class="grupo-form">
                        <label><i class="fas fa-user-tag"></i> Tipo de Depoente *</label>
                        <select id="campoTipoDepoimento" class="campo-form" required onchange="atualizarCamposTipoDepoimento()">
                            <option value="ex_aluno">Ex-Aluno</option>
                            <option value="atual">Aluno Atual</option>
                        </select>
                    </div>
                </div>
                <div class="linha-form">
                    <div class="grupo-form">
                        <label id="labelTurma"><i class="fas fa-calendar"></i> Ano de Conclusão *</label>
                        <input type="text" id="campoTurma" class="campo-form" required>
                    </div>
                    <div class="grupo-form" id="grupoAnoAtual" style="display:none;">
                        <label><i class="fas fa-calendar-alt"></i> Ano Atual *</label>
                        <input type="text" id="campoAnoAtual" class="campo-form" placeholder="Ex: 2026">
                    </div>
                    <div class="grupo-form" id="grupoEmpresa">
                        <label><i class="fas fa-building"></i> Empresa *</label>
                        <input type="text" id="campoEmpresa" class="campo-form">
                    </div>
                </div>

                <div class="grupo-form">
                    <label><i class="fas fa-quote-left"></i> Depoimento *</label>
                    <textarea id="campoTexto" class="campo-form area-texto" rows="5" required></textarea>
                </div>

                <div class="linha-form">
                    <div class="grupo-form">
                        <label><i class="fas fa-image"></i> Foto</label>
                        <div class="area-upload" onclick="document.getElementById('inputFoto').click()">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>Clique para selecionar a foto</p>
                            <small>JPG, PNG, GIF | 200x200px | Máx 5MB</small>
                        </div>
                        <input type="file" id="inputFoto" accept="image/*" style="display: none;">
                        <div class="preview-foto" id="previewFoto" style="display: none;">
                            <img id="previewImg" src="" alt="Preview">
                            <button type="button" class="btn-remover-foto" onclick="removerFoto()">
                                <i class="fas fa-trash"></i> Remover
                            </button>
                        </div>
                    </div>
                    <div class="grupo-form">
                        <label><i class="fas fa-star"></i> Destaque</label>
                        <div class="toggle-group">
                            <label class="toggle-switch">
                                <input type="checkbox" id="campoDestaque">
                                <span class="toggle-slider"></span>
                            </label>
                            <span>Mostrar na página inicial</span>
                        </div>
                    </div>
                </div>

                <div class="modal-acoes">
                    <button type="button" class="btn-cancelar" onclick="fecharModal()">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn-salvar">
                        <i class="fas fa-save"></i> Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

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

<style>/* Botão menu mobile - comportamento correto */
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

/* ===== ESTILOS ADMIN DEPOIMENTOS ===== */
/* ===== BOTÃO NOVA DISCIPLINA ===== */

.btn-primario {
    background: linear-gradient(135deg, #003072 0%, #0a5e6b 100%);
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 40px;
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    box-shadow: 0 2px 6px rgba(0, 48, 114, 0.2);
}

.btn-primario i {
    font-size: 0.9rem;
    transition: transform 0.2s ease;
}

.btn-primario span {
    letter-spacing: 0.3px;
}

.btn-primario:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 14px rgba(0, 48, 114, 0.3);
}

.btn-primario:hover i {
    transform: rotate(90deg);
}

.btn-primario:active {
    transform: translateY(0);
}

.btn-primario:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

/* Variante para botões menores */
.btn-primario-sm {
    padding: 8px 18px;
    font-size: 0.75rem;
}

.btn-primario-sm i {
    font-size: 0.8rem;
}

/* Variante para botões maiores */
.btn-primario-lg {
    padding: 14px 32px;
    font-size: 0.95rem;
}

.btn-primario-lg i {
    font-size: 1rem;
}

/* Variante sem texto (apenas ícone) */
.btn-primario-icon {
    padding: 12px;
    border-radius: 50%;
    justify-content: center;
}

.btn-primario-icon span {
    display: none;
}

.btn-primario-icon i {
    margin: 0;
}
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
.stat-icon.gold { background: #fff8e1; color: #f39c12; }
.stat-icon.green { background: #e8f5e9; color: #2e7d32; }

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

.filter-select {
    padding: 10px 12px;
    border: 1px solid #e0e4e8;
    border-radius: 10px;
    font-size: 13px;
    background: white;
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

/* Seção conteúdo */
.secao-conteudo {
    background: white;
    border-radius: 20px;
    padding: 25px;
    margin-bottom: 30px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.secao-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #eef2f6;
}

.secao-titulo {
    font-size: 18px;
    font-weight: 600;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.secao-titulo .contador {
    font-size: 13px;
    font-weight: normal;
    color: #6c757d;
}

/* Tabela */
.tabela-responsiva {
    overflow-x: auto;
}

.tabela-dados {
    width: 100%;
    border-collapse: collapse;
}

.tabela-dados th {
    text-align: left;
    padding: 12px 15px;
    background: #f8f9fa;
    font-weight: 600;
    font-size: 13px;
    color: #003072;
    border-bottom: 2px solid #eef2f6;
}

.tabela-dados td {
    padding: 12px 15px;
    border-bottom: 1px solid #eef2f6;
    vertical-align: middle;
}

.tabela-dados tr:hover {
    background: #f8f9fa;
}

.empty-table {
    text-align: center;
    padding: 40px;
    color: #999;
}

.foto-coluna {
    width: 60px;
}

.foto-miniatura {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    object-fit: cover;
}

.nome-coluna strong {
    font-weight: 600;
}

.badge-destaque, .badge-normal {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}

.badge-destaque {
    background: #e8f5e9;
    color: #2e7d32;
}

.badge-normal {
    background: #f0f0f0;
    color: #666;
}
.badge-tipo-ex, .badge-tipo-atual {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
}
.badge-tipo-ex { background: #e0f2fe; color: #0369a1; }
.badge-tipo-atual { background: #dcfce7; color: #15803d; }

.acoes-coluna {
    white-space: nowrap;
}

.btn-editar, .btn-eliminar {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
    margin: 0 2px;
}

.btn-editar {
    background: #e3f2fd;
    color: #0288d1;
}

.btn-editar:hover {
    background: #0288d1;
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

/* Modal */
.modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.7);
    z-index: 10000;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.modal.ativo { display: flex; }

.modal-conteudo {
    background: white;
    border-radius: 20px;
    max-width: 650px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-cabecalho {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 25px;
    border-bottom: 1px solid #eef2f6;
    background: linear-gradient(135deg, #003072, #0a9396);
    border-radius: 20px 20px 0 0;
}

.modal-cabecalho h2 {
    color: white;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.modal-fechar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: rgba(255,255,255,0.2);
    border: none;
    color: white;
    cursor: pointer;
}

.modal-fechar:hover {
    background: rgba(255,255,255,0.4);
    transform: rotate(90deg);
}

.modal-corpo {
    padding: 25px;
}

.linha-form {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
    margin-bottom: 15px;
}

.grupo-form {
    margin-bottom: 15px;
}

.grupo-form label {
    display: block;
    font-weight: 600;
    margin-bottom: 6px;
    font-size: 12px;
}

.campo-form {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid #e0e4e8;
    border-radius: 10px;
    font-size: 14px;
}

.campo-form:focus {
    outline: none;
    border-color: #0a9396;
}

.area-texto {
    resize: vertical;
    min-height: 120px;
}

.area-upload {
    border: 2px dashed #cbd5e1;
    border-radius: 12px;
    padding: 20px;
    text-align: center;
    cursor: pointer;
}

.area-upload i {
    font-size: 32px;
    color: #0a9396;
}

.preview-foto {
    margin-top: 15px;
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 10px;
    background: #f0fdfa;
    border-radius: 10px;
}

.preview-foto img {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    object-fit: cover;
}

.btn-remover-foto {
    background: #fee2e2;
    border: none;
    padding: 6px 12px;
    border-radius: 8px;
    cursor: pointer;
    color: #dc3545;
}

.toggle-group {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-top: 10px;
}

.toggle-switch {
    position: relative;
    width: 44px;
    height: 24px;
    display: inline-block;
}

.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: 0.3s;
    border-radius: 24px;
}

.toggle-slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: 0.3s;
    border-radius: 50%;
}

input:checked + .toggle-slider {
    background-color: #0a9396;
}

input:checked + .toggle-slider:before {
    transform: translateX(20px);
}

.modal-acoes {
    display: flex;
    justify-content: flex-end;
    gap: 15px;
    margin-top: 25px;
    padding-top: 20px;
    border-top: 1px solid #eef2f6;
}

.btn-cancelar, .btn-salvar {
    padding: 12px 24px;
    border-radius: 12px;
    font-weight: 600;
    cursor: pointer;
    border: none;
}

.btn-cancelar {
    background: #f0f0f0;
    color: #666;
}

.btn-salvar {
    background: #0a9396;
    color: white;
}

.btn-salvar:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(10,147,150,0.3);
}

/* Modal Confirmação */
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

/* Notificação */
.notificacao {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 14px 24px;
    background: linear-gradient(135deg, #28a745, #1e7e34);
    color: white;
    border-radius: 12px;
    z-index: 99999;
    font-weight: 600;
    animation: slideIn 0.3s;
}

.notificacao.erro {
    background: linear-gradient(135deg, #dc3545, #c82333);
}

@keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

/* Responsividade */
@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .linha-form {
        grid-template-columns: 1fr;
    }
    
    .filtros-grid {
        grid-template-columns: 1fr;
    }
    
    .modal-conteudo {
        max-width: 95%;
    }
}
</style>

<script>
let fotoFile = null;
let depoimentosData = <?= json_encode($depoimentos) ?>;

function mostrarNotificacao(mensagem, tipo = 'sucesso') {
    const notif = document.createElement('div');
    notif.className = `notificacao ${tipo}`;
    notif.innerHTML = `<i class="fas fa-${tipo === 'sucesso' ? 'check-circle' : 'exclamation-circle'}"></i> ${mensagem}`;
    document.body.appendChild(notif);
    setTimeout(() => notif.remove(), 3000);
}

function previewFoto(input) {
    const file = input.files[0];
    if (!file) return;
    
    if (file.size > 5 * 1024 * 1024) {
        mostrarNotificacao('Arquivo muito grande. Máximo 5MB', 'erro');
        input.value = '';
        return;
    }
    
    fotoFile = file;
    const reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById('previewImg').src = e.target.result;
        document.getElementById('previewFoto').style.display = 'flex';
    };
    reader.readAsDataURL(file);
}

function removerFoto() {
    document.getElementById('inputFoto').value = '';
    fotoFile = null;
    document.getElementById('previewFoto').style.display = 'none';
}

function toggleFiltros() {
    const form = document.getElementById('filtrosForm');
    const btn = document.getElementById('toggleFiltros');
    form.classList.toggle('collapsed');
    btn.classList.toggle('collapsed');
}

function aplicarFiltros() {
    const busca = document.getElementById('campoBusca').value.toLowerCase();
    const destaque = document.getElementById('filtroDestaque').value;
    const cursoId = document.getElementById('filtroCurso').value;
    const tipo = document.getElementById('filtroTipo').value;
    
    const rows = document.querySelectorAll('#corpoTabelaDepoimentos tr');
    let visiveis = 0;
    
    rows.forEach(row => {
        const nome = row.querySelector('.nome-coluna')?.textContent.toLowerCase() || '';
        const curso = row.cells[2]?.querySelector('strong')?.textContent.toLowerCase() || '';
        const empresa = row.cells[4]?.textContent.toLowerCase() || '';
        const rowDestaque = row.dataset.destaque;
        const rowCurso = row.dataset.curso;
        const rowTipo = row.dataset.tipo || 'ex_aluno';
        
        let mostrar = true;
        if (busca && !nome.includes(busca) && !curso.includes(busca) && !empresa.includes(busca)) {
            mostrar = false;
        }
        if (destaque !== '' && rowDestaque != destaque) {
            mostrar = false;
        }
        if (cursoId !== '' && rowCurso != cursoId) {
            mostrar = false;
        }
        if (tipo !== '' && rowTipo !== tipo) {
            mostrar = false;
        }
        
        row.style.display = mostrar ? '' : 'none';
        if (mostrar) visiveis++;
    });
    
    document.getElementById('contadorDepoimentos').innerHTML = `(${visiveis} registos)`;
}

function limparFiltros() {
    document.getElementById('campoBusca').value = '';
    document.getElementById('filtroDestaque').value = '';
    document.getElementById('filtroCurso').value = '';
    document.getElementById('filtroTipo').value = '';
    aplicarFiltros();
}

function abrirModal(depoimento = null) {
    const modal = document.getElementById('modalDepoimento');
    const titulo = document.getElementById('modalTitulo');
    
    document.getElementById('formDepoimento').reset();
    document.getElementById('depoimentoId').value = '';
    document.getElementById('previewFoto').style.display = 'none';
    document.getElementById('campoDestaque').checked = false;
    fotoFile = null;
    
    if (depoimento) {
        titulo.innerHTML = '<i class="fas fa-edit"></i> Editar Depoimento';
        document.getElementById('depoimentoId').value = depoimento.id;
        document.getElementById('campoNome').value = depoimento.nome;
        document.getElementById('campoCursoId').value = depoimento.curso_id;
        document.getElementById('campoTurma').value = depoimento.turma;
        document.getElementById('campoAnoAtual').value = depoimento.ano_atual || '';
        document.getElementById('campoEmpresa').value = depoimento.empresa;
        document.getElementById('campoTipoDepoimento').value = depoimento.tipo_depoimento || 'ex_aluno';
        document.getElementById('campoTexto').value = depoimento.texto;
        document.getElementById('campoDestaque').checked = depoimento.destaque == 1;
        
        if (depoimento.foto_url && depoimento.foto_url !== 'foto/sem_foto.png') {
            document.getElementById('previewImg').src = (depoimento.foto_url.startsWith('http') ? depoimento.foto_url : '../area-publica/' + depoimento.foto_url);
            document.getElementById('previewFoto').style.display = 'flex';
        }
    } else {
        titulo.innerHTML = '<i class="fas fa-plus"></i> Novo Depoimento';
    }
    atualizarCamposTipoDepoimento();
    
    modal.classList.add('ativo');
}

function atualizarCamposTipoDepoimento() {
    const tipo = document.getElementById('campoTipoDepoimento')?.value || 'ex_aluno';
    const labelTurma = document.getElementById('labelTurma');
    const grupoEmpresa = document.getElementById('grupoEmpresa');
    const campoEmpresa = document.getElementById('campoEmpresa');
    const grupoAnoAtual = document.getElementById('grupoAnoAtual');
    const campoAnoAtual = document.getElementById('campoAnoAtual');
    if (labelTurma) {
        labelTurma.innerHTML = tipo === 'atual'
            ? '<i class="fas fa-calendar"></i> Classe Atual *'
            : '<i class="fas fa-calendar"></i> Ano de Conclusão *';
    }
    if (grupoEmpresa && campoEmpresa) {
        grupoEmpresa.style.display = tipo === 'atual' ? 'none' : '';
        campoEmpresa.required = tipo !== 'atual';
        if (tipo === 'atual') campoEmpresa.value = 'Estudante';
    }
    if (grupoAnoAtual && campoAnoAtual) {
        grupoAnoAtual.style.display = tipo === 'atual' ? '' : 'none';
        campoAnoAtual.required = tipo === 'atual';
        if (tipo !== 'atual') campoAnoAtual.value = '';
    }
}

function fecharModal() {
    const modal = document.getElementById('modalDepoimento');
    modal.classList.remove('ativo');
}

function editarDepoimento(id) {
    const depoimento = depoimentosData.find(d => d.id == id);
    if (depoimento) {
        abrirModal(depoimento);
    }
}

async function salvarDepoimento(event) {
    event.preventDefault();
    
    const id = document.getElementById('depoimentoId').value;
    const nome = document.getElementById('campoNome').value;
    const curso_id = document.getElementById('campoCursoId').value;
    const turma = document.getElementById('campoTurma').value;
    const empresa = document.getElementById('campoEmpresa').value;
    const ano_atual = document.getElementById('campoAnoAtual').value;
    const tipo_depoimento = document.getElementById('campoTipoDepoimento').value;
    const texto = document.getElementById('campoTexto').value;
    const destaque = document.getElementById('campoDestaque').checked ? 1 : 0;
    
    if (!nome || !curso_id || !turma || !texto || (tipo_depoimento === 'ex_aluno' && !empresa) || (tipo_depoimento === 'atual' && !ano_atual)) {
        mostrarNotificacao('Preencha todos os campos obrigatórios', 'erro');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', id ? 'editar' : 'salvar');
    if (id) formData.append('id', id);
    formData.append('nome', nome);
    formData.append('curso_id', curso_id);
    formData.append('turma', turma);
    formData.append('empresa', empresa);
    formData.append('ano_atual', ano_atual);
    formData.append('tipo_depoimento', tipo_depoimento);
    formData.append('texto', texto);
    formData.append('destaque', destaque);
    if (fotoFile) formData.append('foto', fotoFile);
    
    try {
        const response = await fetch('processos/processar-depoimentos.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            mostrarNotificacao(data.message, 'sucesso');
            fecharModal();
            setTimeout(() => location.reload(), 1500);
        } else {
            mostrarNotificacao(data.message, 'erro');
        }
    } catch (error) {
        mostrarNotificacao('Erro ao salvar', 'erro');
    }
}

function eliminarDepoimento(id) {
    const modal = document.getElementById('modalConfirmacao');
    document.getElementById('confirmacaoTitulo').textContent = 'Eliminar Depoimento';
    document.getElementById('confirmacaoTexto').textContent = 'Tem certeza que deseja eliminar este depoimento permanentemente?';
    
    const confirmar = document.getElementById('btnConfirmarAcao');
    const novoConfirmar = confirmar.cloneNode(true);
    confirmar.parentNode.replaceChild(novoConfirmar, confirmar);
    
    novoConfirmar.onclick = async () => {
        try {
            const response = await fetch('processos/processar-depoimentos.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ action: 'eliminar', id: id })
            });
            const data = await response.json();
            if (data.success) {
                mostrarNotificacao(data.message, 'sucesso');
                location.reload();
            } else {
                mostrarNotificacao(data.message, 'erro');
            }
        } catch (error) {
            mostrarNotificacao('Erro ao eliminar', 'erro');
        }
        modal.classList.remove('ativo');
    };
    
    modal.classList.add('ativo');
}

document.getElementById('btnNovoDepoimento')?.addEventListener('click', () => abrirModal());
document.getElementById('toggleFiltros')?.addEventListener('click', toggleFiltros);
document.getElementById('btnAplicarFiltros')?.addEventListener('click', aplicarFiltros);
document.getElementById('btnLimparFiltros')?.addEventListener('click', limparFiltros);
document.getElementById('btnCancelarConfirmacao')?.addEventListener('click', () => {
    document.getElementById('modalConfirmacao').classList.remove('ativo');
});
document.getElementById('inputFoto')?.addEventListener('change', (e) => previewFoto(e.target));

document.getElementById('modalDepoimento')?.addEventListener('click', (e) => {
    if (e.target === document.getElementById('modalDepoimento')) fecharModal();
});
document.getElementById('modalConfirmacao')?.addEventListener('click', (e) => {
    if (e.target === document.getElementById('modalConfirmacao')) {
        document.getElementById('modalConfirmacao').classList.remove('ativo');
    }
});
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        fecharModal();
        document.getElementById('modalConfirmacao').classList.remove('ativo');
    }
});

document.getElementById('campoBusca')?.addEventListener('input', aplicarFiltros);
document.getElementById('filtroDestaque')?.addEventListener('change', aplicarFiltros);
document.getElementById('filtroCurso')?.addEventListener('change', aplicarFiltros);
document.getElementById('filtroTipo')?.addEventListener('change', aplicarFiltros);
document.getElementById('campoTipoDepoimento')?.addEventListener('change', atualizarCamposTipoDepoimento);

aplicarFiltros();
</script>

<?php include 'includes/footer.php'; ?>
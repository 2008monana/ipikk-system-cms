<?php
/**
 * Percurso (Histórias de Sucesso) - Área Restrita IPIKK
 * Gestão completa dos alumni
 */

$titulo_pagina = 'Percurso - Histórias de Sucesso';
$css_especifico = 'admin-percurso.css';

require_once dirname(__DIR__) . '/config/index.php';

if (!isset($_SESSION['utilizador_id'])) {
    header('Location: area-restrita.php');
    exit;
}

require_once __DIR__ . '/includes/verificar-permissao.php';

// ===== VERIFICAR PERMISSÃO ESPECÍFICA =====
verificarPermissao('conteudo_site');

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
// BUSCAR ALUMNI DO BANCO DE DADOS COM JOIN
// ============================================
$alumni = $db->query("
    SELECT a.*, c.nome as curso_nome 
    FROM alumni a
    LEFT JOIN cursos c ON a.curso_id = c.id
    WHERE a.ativo = 1 
    ORDER BY a.destaque DESC, a.ordem, a.ano_conclusao DESC
")->fetchAll();

// Buscar cursos para o select
$cursos = $db->query("SELECT id, nome FROM cursos WHERE estado = 'ativo' ORDER BY nome")->fetchAll();

// Estatísticas
$total_alumni = count($alumni);
$total_destaque = count(array_filter($alumni, fn($a) => $a['destaque'] == 1));
$total_empresas = count(array_unique(array_column($alumni, 'empresa')));

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
                <i class="fas fa-chart-line"></i> Histórias de Sucesso
            </h1>
        </div>
        <div class="direita-barra">
            <button class="btn-primario" id="btnNovoAlumni" onclick="abrirModalAlumni()">
                <i class="fas fa-plus"></i>
                <span>Novo Alumni</span>
            </button>
        </div>
    </header>

    <div class="conteudo-pagina">

        <!-- CARDS ESTATÍSTICAS -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon purple"><i class="fas fa-users"></i></div>
                <div class="stat-info">
                    <h3><?= $total_alumni ?></h3>
                    <p>Total de Alumni</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon gold"><i class="fas fa-star"></i></div>
                <div class="stat-info">
                    <h3><?= $total_destaque ?></h3>
                    <p>Em Destaque</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-building"></i></div>
                <div class="stat-info">
                    <h3><?= $total_empresas ?></h3>
                    <p>Empresas Parceiras</p>
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
                    <div class="filter-actions">
                        <button class="btn-filter" id="btnAplicarFiltros"><i class="fas fa-search"></i> Aplicar</button>
                        <button class="btn-clear" id="btnLimparFiltros"><i class="fas fa-undo"></i> Limpar</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- LISTA DE ALUMNI -->
        <div class="secao-conteudo">
            <div class="secao-header">
                <h2 class="secao-titulo">
                    <i class="fas fa-list"></i> Alumni Cadastrados
                    <span class="contador" id="contadorAlumni">(<?= $total_alumni ?> registos)</span>
                </h2>
                <button class="btn-adicionar" onclick="abrirModalAlumni()">
                    <i class="fas fa-plus"></i> Adicionar
                </button>
            </div>

            <div class="grid-alumni" id="gridAlumni">
                <?php if (empty($alumni)): ?>
                <div class="empty-state">
                    <i class="fas fa-users"></i>
                    <h3>Nenhum alumni cadastrado</h3>
                    <p>Clique em "Novo Alumni" para começar a adicionar histórias de sucesso.</p>
                    <button class="btn-primario" onclick="abrirModalAlumni()">
                        <i class="fas fa-plus"></i> Adicionar Primeiro
                    </button>
                </div>
                <?php else: ?>
                    <?php foreach ($alumni as $aluno): ?>
                    <div class="card-alumni" data-id="<?= $aluno['id'] ?>" data-destaque="<?= $aluno['destaque'] ?>" data-curso="<?= $aluno['curso_id'] ?>">
                        <div class="card-acoes">
                            <button class="btn-editar" onclick="editarAlumni(<?= $aluno['id'] ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn-eliminar" onclick="eliminarAlumni(<?= $aluno['id'] ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                        <div class="card-foto">
                            <img src="<?= htmlspecialchars(normalizarUrlMidia($aluno['foto_url'] ?? 'foto/sem_foto.png', '..')) ?>" 
                                 alt="<?= htmlspecialchars($aluno['nome']) ?>"
                                 onerror="this.src='foto/sem_foto.png'">
                            <?php if ($aluno['destaque']): ?>
                            <span class="destaque-badge"><i class="fas fa-star"></i> Destaque</span>
                            <?php endif; ?>
                        </div>
                        <div class="card-info">
                            <h3><?= htmlspecialchars($aluno['nome']) ?></h3>
                            <p class="curso"><?= htmlspecialchars($aluno['curso_nome'] ?? 'Curso não informado') ?></p>
                            <p class="ano"><?= htmlspecialchars($aluno['ano_conclusao']) ?></p>
                            <p class="empresa"><i class="fas fa-building"></i> <?= htmlspecialchars($aluno['empresa']) ?></p>
                            <p class="cargo"><i class="fas fa-briefcase"></i> <?= htmlspecialchars($aluno['cargo_atual'] ?? 'N/A') ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<!-- MODAL PARA ALUMNI -->
<div id="modalAlumni" class="modal">
    <div class="modal-conteudo modal-grande">
        <div class="modal-cabecalho">
            <h2 id="modalTitulo"><i class="fas fa-plus"></i> Novo Alumni</h2>
            <button class="modal-fechar" onclick="fecharModalAlumni()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-corpo">
            <form id="formAlumni" onsubmit="return salvarAlumni(event)">
                <input type="hidden" id="alumniId">

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
                        <label><i class="fas fa-calendar"></i> Ano de Conclusão *</label>
                        <input type="text" id="campoAnoConclusao" class="campo-form" placeholder="Ex: 2012" required>
                    </div>
                    <div class="grupo-form">
                        <label><i class="fas fa-building"></i> Empresa *</label>
                        <input type="text" id="campoEmpresa" class="campo-form" required>
                    </div>
                </div>

                <div class="linha-form">
                    <div class="grupo-form">
                        <label><i class="fas fa-briefcase"></i> Cargo Atual</label>
                        <input type="text" id="campoCargoAtual" class="campo-form">
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

                <div class="grupo-form">
                    <label><i class="fas fa-align-left"></i> Percurso Profissional *</label>
                    <textarea id="campoPercurso" class="campo-form area-texto" rows="5" required placeholder="Descreva a trajetória profissional do alumni..."></textarea>
                </div>

                <div class="grupo-form">
                    <label><i class="fas fa-image"></i> Foto do Alumni</label>
                    <div class="area-upload" onclick="document.getElementById('fotoInput').click()">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>Clique para fazer upload da foto</p>
                        <small>Formatos: JPG, PNG | 200x200px recomendado</small>
                    </div>
                    <input type="file" id="fotoInput" accept="image/*" style="display: none;" onchange="previewFoto(this)">
                    <div class="preview-foto" id="previewFoto" style="display: none;">
                        <img id="previewImg" src="" alt="Preview">
                        <button type="button" class="btn-remover" onclick="removerFoto()">
                            <i class="fas fa-trash"></i> Remover
                        </button>
                    </div>
                </div>

                <div class="grupo-form">
                    <label><i class="fas fa-sort-numeric-down"></i> Ordem de Exibição</label>
                    <input type="number" id="campoOrdem" class="campo-form" value="0" min="0">
                </div>

                <div class="modal-acoes">
                    <button type="button" class="btn-cancelar" onclick="fecharModalAlumni()">
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

<style>
/* Botão menu mobile - comportamento correto */
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
/* ===== ESTILOS ADMIN PERCURSO ===== */

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-bottom: 32px;
}

.stat-card {
    background: white;
    border-radius: 20px;
    padding: 22px 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
    transition: all 0.3s ease;
    border: 1px solid #f0f2f5;
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
    border-color: #e2e8f0;
}

.stat-icon {
    width: 56px;
    height: 56px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    transition: all 0.3s ease;
}

.stat-card:hover .stat-icon {
    transform: scale(1.05);
}

.stat-icon.purple { 
    background: linear-gradient(135deg, #e8eaff 0%, #ddd6fe 100%);
    color: #6366f1; 
}
.stat-icon.gold { 
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    color: #d97706; 
}
.stat-icon.blue { 
    background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%);
    color: #0284c7; 
}

.stat-info h3 {
    font-size: 28px;
    font-weight: 700;
    margin: 0;
    color: #1e293b;
    line-height: 1.2;
}

.stat-info p {
    font-size: 12px;
    font-weight: 500;
    color: #64748b;
    margin: 6px 0 0;
    letter-spacing: 0.3px;
}

/* Filtros Card */
.filtros-card {
    background: white;
    border-radius: 20px;
    margin-bottom: 32px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
    border: 1px solid #f0f2f5;
    transition: all 0.3s ease;
}

.filtros-card:hover {
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
    border-color: #e2e8f0;
}

.filtros-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 24px;
    cursor: pointer;
    transition: background 0.2s ease;
}

.filtros-header:hover {
    background: #fafbfc;
}

.filtros-title {
    display: flex;
    align-items: center;
    gap: 12px;
}

.filtros-title i {
    color: #0a9396;
    font-size: 1.1rem;
}

.filtros-title h3 {
    font-size: 0.9rem;
    font-weight: 600;
    margin: 0;
    color: #1e293b;
}

.btn-toggle-filtros {
    background: #f1f5f9;
    border: none;
    cursor: pointer;
    width: 34px;
    height: 34px;
    border-radius: 10px;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-toggle-filtros:hover {
    background: #e2e8f0;
}

.btn-toggle-filtros i {
    transition: transform 0.2s ease;
    color: #64748b;
}

.btn-toggle-filtros.collapsed i {
    transform: rotate(180deg);
}

.filtros-form {
    padding: 0 24px;
    max-height: 300px;
    overflow: hidden;
    transition: all 0.3s ease;
    border-top: 1px solid #eef2f8;
}

.filtros-form.collapsed {
    max-height: 0;
    padding: 0 24px;
    border-top: none;
}

.filtros-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 16px;
    padding: 20px 0;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.filter-group label {
    font-size: 0.65rem;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.search-box {
    position: relative;
}

.search-box i:first-child {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 0.8rem;
}

.search-box input {
    width: 100%;
    padding: 10px 12px 10px 36px;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    font-size: 0.85rem;
    transition: all 0.2s ease;
}

.search-box input:focus {
    outline: none;
    border-color: #0a9396;
    box-shadow: 0 0 0 3px rgba(10, 147, 150, 0.1);
}

.filter-select {
    padding: 10px 12px;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    font-size: 0.85rem;
    background: white;
    transition: all 0.2s ease;
    cursor: pointer;
}

.filter-select:focus {
    outline: none;
    border-color: #0a9396;
}

.filter-actions {
    display: flex;
    gap: 10px;
    align-items: flex-end;
}

.btn-filter, .btn-clear {
    padding: 10px 20px;
    border-radius: 40px;
    font-weight: 600;
    font-size: 0.75rem;
    cursor: pointer;
    border: none;
    transition: all 0.2s ease;
}

.btn-filter {
    background: linear-gradient(135deg, #0a9396 0%, #008bb5 100%);
    color: white;
}

.btn-filter:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(10, 147, 150, 0.3);
}

.btn-clear {
    background: #f1f5f9;
    color: #64748b;
    border: 1px solid #e2e8f0;
}

.btn-clear:hover {
    background: #e2e8f0;
    transform: translateY(-1px);
}

/* Secção principal */
.secao-conteudo {
    background: white;
    border-radius: 24px;
    padding: 28px;
    margin-bottom: 32px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
    border: 1px solid #f0f2f5;
    transition: all 0.3s ease;
}

.secao-conteudo:hover {
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
    border-color: #e2e8f0;
}

.secao-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 2px solid #f1f5f9;
    flex-wrap: wrap;
    gap: 12px;
}

.secao-titulo {
    font-size: 1.1rem;
    font-weight: 700;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 12px;
    color: #0f172a;
}

.secao-titulo i {
    color: #0a9396;
    font-size: 1.2rem;
    width: 34px;
    height: 34px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(10, 147, 150, 0.1);
    border-radius: 12px;
}

.secao-titulo .contador {
    font-size: 0.7rem;
    font-weight: 500;
    color: #64748b;
    background: #f1f5f9;
    padding: 3px 12px;
    border-radius: 30px;
    letter-spacing: normal;
}

/* Botão Adicionar */
.btn-adicionar {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border: 1px solid #e2e8f0;
    padding: 10px 20px;
    border-radius: 40px;
    font-size: 0.75rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    color: #475569;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    letter-spacing: 0.3px;
}

.btn-adicionar i {
    font-size: 0.8rem;
    transition: transform 0.2s ease;
}

.btn-adicionar:hover {
    background: #0a9396;
    border-color: #0a9396;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(10, 147, 150, 0.3);
}

.btn-adicionar:hover i {
    transform: rotate(90deg);
}

/* Botão Principal (Novo Alumni) */
.btn-primario {
    background: linear-gradient(135deg, #003072 0%, #0a5e6b 100%);
    color: white;
    border: none;
    padding: 10px 24px;
    border-radius: 40px;
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 10px;
}

.btn-primario i {
    transition: transform 0.2s ease;
}

.btn-primario:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 14px rgba(0, 48, 114, 0.3);
}

.btn-primario:hover i {
    transform: rotate(90deg);
}

/* Grid de Alumni */
.grid-alumni {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: 28px;
}

/* Card de Alumni */
.card-alumni {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    border: 1px solid #eef2f8;
    transition: all 0.3s ease;
    position: relative;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
}

.card-alumni:hover {
    transform: translateY(-6px);
    box-shadow: 0 16px 32px rgba(0, 0, 0, 0.1);
    border-color: #cbd5e1;
}

/* Ações do card */
.card-acoes {
    position: absolute;
    top: 12px;
    right: 12px;
    display: flex;
    gap: 8px;
    opacity: 0;
    transition: all 0.2s ease;
    z-index: 10;
}

.card-alumni:hover .card-acoes {
    opacity: 1;
}

/* Foto do card */
.card-foto {
    position: relative;
    height: 220px;
    overflow: hidden;
    background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
}

.card-foto img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.4s ease;
}

.card-alumni:hover .card-foto img {
    transform: scale(1.05);
}

/* Badge de destaque */
.destaque-badge {
    position: absolute;
    top: 12px;
    left: 12px;
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: white;
    padding: 5px 12px;
    border-radius: 30px;
    font-size: 0.7rem;
    font-weight: 600;
    letter-spacing: 0.5px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    z-index: 5;
}

.destaque-badge i {
    margin-right: 6px;
    font-size: 0.65rem;
}

/* Informações do card */
.card-info {
    padding: 18px;
}

.card-info h3 {
    font-size: 1rem;
    font-weight: 700;
    margin-bottom: 6px;
    color: #1e293b;
    line-height: 1.3;
}

.card-info .curso {
    font-size: 0.7rem;
    font-weight: 600;
    color: #0a9396;
    margin-bottom: 10px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.card-info .ano {
    font-size: 0.65rem;
    color: #64748b;
    margin-bottom: 12px;
    display: inline-block;
    background: #f1f5f9;
    padding: 3px 10px;
    border-radius: 30px;
}

.card-info .empresa, 
.card-info .cargo {
    font-size: 0.75rem;
    color: #475569;
    margin-top: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.card-info .empresa i, 
.card-info .cargo i {
    width: 20px;
    color: #0a9396;
    font-size: 0.7rem;
}

/* Botões de ação */
.btn-editar, .btn-eliminar {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    border: none;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
}

.btn-editar {
    background: #e0f2fe;
    color: #0284c7;
}

.btn-editar:hover {
    background: #0284c7;
    color: white;
    transform: scale(1.05);
}

.btn-eliminar {
    background: #fee2e2;
    color: #dc2626;
}

.btn-eliminar:hover {
    background: #dc2626;
    color: white;
    transform: scale(1.05);
}

/* Empty State */
.empty-state {
    grid-column: 1 / -1;
    text-align: center;
    padding: 60px 40px;
    background: linear-gradient(135deg, #fafbfc 0%, #f8fafc 100%);
    border-radius: 32px;
    border: 1px dashed #cbd5e1;
}

.empty-state i {
    font-size: 64px;
    color: #cbd5e1;
    margin-bottom: 20px;
    display: block;
}

.empty-state h3 {
    font-size: 1.2rem;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 8px;
}

.empty-state p {
    color: #64748b;
    font-size: 0.85rem;
    margin-bottom: 24px;
}

.empty-state .btn-primario {
    background: linear-gradient(135deg, #0a9396 0%, #008bb5 100%);
}

/* ===== MODAL ===== */
.modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    z-index: 10000;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.modal.ativo {
    display: flex;
}

.modal-conteudo {
    background: white;
    border-radius: 28px;
    max-width: 720px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    animation: modalFadeIn 0.3s ease;
}

@keyframes modalFadeIn {
    from {
        opacity: 0;
        transform: scale(0.95);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

.modal-grande {
    max-width: 720px;
}

.modal-cabecalho {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 22px 28px;
    border-bottom: 1px solid #eef2f8;
    position: sticky;
    top: 0;
    background: white;
    z-index: 5;
}

.modal-cabecalho h2 {
    font-size: 1.25rem;
    font-weight: 700;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 12px;
    color: #1e293b;
}

.modal-cabecalho h2 i {
    color: #0a9396;
    font-size: 1.2rem;
}

.modal-fechar {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: #f1f5f9;
    border: none;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    color: #64748b;
}

.modal-fechar:hover {
    background: #fee2e2;
    color: #dc2626;
    transform: rotate(90deg);
}

.modal-corpo {
    padding: 28px;
}

/* Formulário */
.linha-form {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 18px;
    margin-bottom: 18px;
}

.grupo-form {
    margin-bottom: 20px;
}

.grupo-form label {
    display: block;
    font-weight: 600;
    margin-bottom: 8px;
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    color: #64748b;
}

.grupo-form label i {
    margin-right: 6px;
    color: #0a9396;
}

.campo-form {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    font-size: 0.9rem;
    transition: all 0.2s ease;
    background: white;
    font-family: inherit;
}

.campo-form:focus {
    outline: none;
    border-color: #0a9396;
    box-shadow: 0 0 0 3px rgba(10, 147, 150, 0.1);
}

.campo-form::placeholder {
    color: #cbd5e1;
    font-size: 0.85rem;
}

select.campo-form {
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748b'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 12px center;
    background-size: 18px;
}

.area-texto {
    resize: vertical;
    min-height: 120px;
    line-height: 1.5;
}

/* Upload de imagem */
.area-upload {
    border: 2px dashed #cbd5e1;
    border-radius: 16px;
    padding: 28px 20px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s ease;
    background: #fafbfc;
}

.area-upload:hover {
    border-color: #0a9396;
    background: #f0fdfa;
}

.area-upload i {
    font-size: 2rem;
    color: #0a9396;
    margin-bottom: 10px;
    display: block;
}

.area-upload p {
    color: #475569;
    font-size: 0.85rem;
    margin-bottom: 4px;
}

.area-upload small {
    color: #94a3b8;
    font-size: 0.7rem;
}

.preview-foto {
    margin-top: 16px;
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 12px 16px;
    background: #f0fdfa;
    border-radius: 14px;
    border: 1px solid #ccfbf1;
}

.preview-foto img {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #0a9396;
}

.btn-remover {
    background: #fee2e2;
    border: none;
    border-radius: 30px;
    padding: 6px 14px;
    cursor: pointer;
    color: #dc2626;
    font-size: 0.7rem;
    font-weight: 600;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.btn-remover:hover {
    background: #dc2626;
    color: white;
}

/* Toggle Switch */
.toggle-group {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-top: 8px;
}

.toggle-switch {
    position: relative;
    width: 48px;
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
    background-color: #cbd5e1;
    transition: 0.3s;
    border-radius: 34px;
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
    background: linear-gradient(135deg, #0a9396 0%, #008bb5 100%);
}

input:checked + .toggle-slider:before {
    transform: translateX(24px);
}

.toggle-group span {
    font-size: 0.75rem;
    color: #64748b;
}

/* Modal Actions */
.modal-acoes {
    display: flex;
    justify-content: flex-end;
    gap: 16px;
    margin-top: 28px;
    padding-top: 20px;
    border-top: 1px solid #eef2f8;
}

.btn-cancelar, .btn-salvar {
    padding: 12px 28px;
    border-radius: 40px;
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
    border: none;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 10px;
}

.btn-cancelar {
    background: #f1f5f9;
    color: #64748b;
}

.btn-cancelar:hover {
    background: #e2e8f0;
    transform: translateY(-1px);
}

.btn-salvar {
    background: linear-gradient(135deg, #0a9396 0%, #008bb5 100%);
    color: white;
}

.btn-salvar:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(10, 147, 150, 0.3);
}

/* Modal Confirmação */
.modal-confirmacao {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(4px);
    z-index: 10001;
    align-items: center;
    justify-content: center;
}

.modal-confirmacao.ativo {
    display: flex;
}

.modal-confirmacao-caixa {
    background: white;
    border-radius: 28px;
    padding: 32px;
    max-width: 400px;
    width: 90%;
    text-align: center;
    animation: zoomIn 0.2s ease;
    box-shadow: 0 20px 40px -12px rgba(0, 0, 0, 0.2);
}

@keyframes zoomIn {
    from {
        opacity: 0;
        transform: scale(0.95);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

.modal-confirmacao-caixa h3 {
    margin: 0 0 8px 0;
    font-size: 1.2rem;
    color: #1e293b;
}

.modal-confirmacao-caixa p {
    color: #64748b;
    font-size: 0.85rem;
    margin-bottom: 24px;
}

.modal-confirmacao-icone {
    width: 60px;
    height: 60px;
    margin: 0 auto 16px;
    background: #fee2e2;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: #dc2626;
}

.modal-confirmacao-botoes {
    display: flex;
    gap: 12px;
    justify-content: center;
}

.botao-cancelar-modal, .botao-confirmar-modal {
    padding: 10px 24px;
    border-radius: 40px;
    font-weight: 600;
    font-size: 0.8rem;
    cursor: pointer;
    border: none;
    transition: all 0.2s ease;
}

.botao-cancelar-modal {
    background: #f1f5f9;
    color: #64748b;
}

.botao-cancelar-modal:hover {
    background: #e2e8f0;
}

.botao-confirmar-modal {
    background: #dc2626;
    color: white;
}

.botao-confirmar-modal:hover {
    background: #b91c1c;
    transform: scale(1.02);
}

/* Notificação */
.notificacao {
    position: fixed;
    top: 24px;
    right: 24px;
    padding: 14px 24px;
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    border-radius: 50px;
    z-index: 99999;
    font-weight: 600;
    font-size: 0.85rem;
    display: flex;
    align-items: center;
    gap: 10px;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
    animation: slideInRight 0.3s ease;
}

@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

/* Responsividade */
@media (max-width: 1024px) {
    .grid-alumni {
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
    }
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
        gap: 12px;
    }
    
    .secao-conteudo {
        padding: 20px;
    }
    
    .secao-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .grid-alumni {
        grid-template-columns: 1fr;
    }
    
    .linha-form {
        grid-template-columns: 1fr;
        gap: 0;
    }
    
    .filtros-grid {
        grid-template-columns: 1fr;
    }
    
    .filter-actions {
        flex-direction: column;
    }
    
    .btn-filter, .btn-clear {
        width: 100%;
        justify-content: center;
    }
    
    .modal-conteudo {
        max-width: 95%;
    }
    
    .modal-cabecalho {
        padding: 16px 20px;
    }
    
    .modal-corpo {
        padding: 20px;
    }
    
    .modal-acoes {
        flex-direction: column;
    }
    
    .btn-cancelar, .btn-salvar {
        width: 100%;
        justify-content: center;
    }
    
    .notificacao {
        left: 16px;
        right: 16px;
        top: 16px;
        justify-content: center;
    }
    
    .card-acoes {
        opacity: 1;
    }
}

@media (max-width: 480px) {
    .stat-card {
        padding: 16px;
    }
    
    .stat-info h3 {
        font-size: 24px;
    }
    
    .secao-titulo {
        font-size: 1rem;
    }
    
    .secao-titulo i {
        width: 28px;
        height: 28px;
        font-size: 0.9rem;
    }
    
    .btn-primario span {
        display: none;
    }
    
    .btn-primario {
        padding: 10px 14px;
    }
    
    .btn-primario i {
        margin: 0;
    }
    
    .card-foto {
        height: 180px;
    }
    
    .empty-state {
        padding: 40px 20px;
    }
    
    .empty-state i {
        font-size: 48px;
    }
    
    .empty-state h3 {
        font-size: 1rem;
    }
}

/* Scrollbar personalizada para modais */
.modal-conteudo::-webkit-scrollbar {
    width: 6px;
}

.modal-conteudo::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 10px;
}

.modal-conteudo::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}

.modal-conteudo::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
</style>

<script>
let fotoFile = null;

function mostrarNotificacao(mensagem, tipo = 'sucesso') {
    const notif = document.createElement('div');
    notif.className = 'notificacao';
    notif.innerHTML = `<i class="fas fa-${tipo === 'sucesso' ? 'check-circle' : 'exclamation-circle'}"></i> ${mensagem}`;
    notif.style.cssText = 'position:fixed;top:20px;right:20px;padding:14px 24px;background:linear-gradient(135deg,#28a745,#1e7e34);color:white;border-radius:12px;z-index:99999;font-weight:600;animation:slideIn 0.3s;';
    if (tipo === 'erro') notif.style.background = 'linear-gradient(135deg,#dc3545,#c82333)';
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
    document.getElementById('fotoInput').value = '';
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
    
    const cards = document.querySelectorAll('.card-alumni');
    let visiveis = 0;
    
    cards.forEach(card => {
        let mostrar = true;
        const nome = card.querySelector('h3')?.textContent.toLowerCase() || '';
        const curso = card.querySelector('.curso')?.textContent.toLowerCase() || '';
        const empresa = card.querySelector('.empresa')?.textContent.toLowerCase() || '';
        
        if (busca && !nome.includes(busca) && !curso.includes(busca) && !empresa.includes(busca)) {
            mostrar = false;
        }
        if (destaque !== '' && card.dataset.destaque != destaque) {
            mostrar = false;
        }
        if (cursoId !== '' && card.dataset.curso != cursoId) {
            mostrar = false;
        }
        
        card.style.display = mostrar ? '' : 'none';
        if (mostrar) visiveis++;
    });
    
    document.getElementById('contadorAlumni').innerHTML = `(${visiveis} registos)`;
}

function limparFiltros() {
    document.getElementById('campoBusca').value = '';
    document.getElementById('filtroDestaque').value = '';
    document.getElementById('filtroCurso').value = '';
    aplicarFiltros();
}

async function salvarAlumni(event) {
    event.preventDefault();
    
    const id = document.getElementById('alumniId').value;
    const nome = document.getElementById('campoNome').value;
    const curso_id = document.getElementById('campoCursoId').value;
    const ano_conclusao = document.getElementById('campoAnoConclusao').value;
    const empresa = document.getElementById('campoEmpresa').value;
    const cargo_atual = document.getElementById('campoCargoAtual').value;
    const percurso_texto = document.getElementById('campoPercurso').value;
    const destaque = document.getElementById('campoDestaque').checked ? 1 : 0;
    const ordem = document.getElementById('campoOrdem').value;
    
    if (!nome || !curso_id || !ano_conclusao || !empresa || !percurso_texto) {
        mostrarNotificacao('Preencha todos os campos obrigatórios', 'erro');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', id ? 'editar' : 'salvar');
    if (id) formData.append('id', id);
    formData.append('nome', nome);
    formData.append('curso_id', curso_id);
    formData.append('ano_conclusao', ano_conclusao);
    formData.append('empresa', empresa);
    formData.append('cargo_atual', cargo_atual);
    formData.append('percurso_texto', percurso_texto);
    formData.append('destaque', destaque);
    formData.append('ordem', ordem);
    if (fotoFile) formData.append('foto', fotoFile);
    
    try {
        const response = await fetch('processos/processar-percurso.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            mostrarNotificacao(data.message, 'sucesso');
            fecharModalAlumni();
            setTimeout(() => location.reload(), 1500);
        } else {
            mostrarNotificacao(data.message, 'erro');
        }
    } catch (error) {
        mostrarNotificacao('Erro ao salvar', 'erro');
    }
}

function abrirModalAlumni(id = null) {
    const modal = document.getElementById('modalAlumni');
    const titulo = document.getElementById('modalTitulo');
    
    document.getElementById('formAlumni').reset();
    document.getElementById('alumniId').value = '';
    document.getElementById('previewFoto').style.display = 'none';
    document.getElementById('campoDestaque').checked = false;
    document.getElementById('campoOrdem').value = '0';
    fotoFile = null;
    
    if (id) {
        titulo.innerHTML = '<i class="fas fa-edit"></i> Editar Alumni';
        editarAlumni(id);
    } else {
        titulo.innerHTML = '<i class="fas fa-plus"></i> Novo Alumni';
        modal.classList.add('ativo');
    }
}

function fecharModalAlumni() {
    document.getElementById('modalAlumni').classList.remove('ativo');
}

function editarAlumni(id) {
    fetch(`processos/processar-percurso.php?action=buscar&id=${id}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const a = data.alumni;
                document.getElementById('alumniId').value = a.id;
                document.getElementById('campoNome').value = a.nome;
                document.getElementById('campoCursoId').value = a.curso_id;
                document.getElementById('campoAnoConclusao').value = a.ano_conclusao;
                document.getElementById('campoEmpresa').value = a.empresa;
                document.getElementById('campoCargoAtual').value = a.cargo_atual || '';
                document.getElementById('campoPercurso').value = a.percurso_texto;
                document.getElementById('campoDestaque').checked = a.destaque == 1;
                document.getElementById('campoOrdem').value = a.ordem || 0;
                
                if (a.foto_url && a.foto_url !== 'foto/sem_foto.png') {
                    document.getElementById('previewImg').src = '../area-publica/' + a.foto_url;
                    document.getElementById('previewFoto').style.display = 'flex';
                }
                
                document.getElementById('modalAlumni').classList.add('ativo');
            }
        });
}

function eliminarAlumni(id) {
    const modal = document.getElementById('modalConfirmacao');
    document.getElementById('confirmacaoTitulo').textContent = 'Eliminar Alumni';
    document.getElementById('confirmacaoTexto').textContent = 'Tem certeza que deseja eliminar este alumni permanentemente?';
    
    const confirmar = document.getElementById('btnConfirmarAcao');
    const novoConfirmar = confirmar.cloneNode(true);
    confirmar.parentNode.replaceChild(novoConfirmar, confirmar);
    
    novoConfirmar.onclick = async () => {
        try {
            const response = await fetch('processos/processar-percurso.php', {
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

document.getElementById('btnNovoAlumni')?.addEventListener('click', () => abrirModalAlumni());
document.getElementById('toggleFiltros')?.addEventListener('click', toggleFiltros);
document.getElementById('btnAplicarFiltros')?.addEventListener('click', aplicarFiltros);
document.getElementById('btnLimparFiltros')?.addEventListener('click', limparFiltros);
document.getElementById('btnCancelarConfirmacao')?.addEventListener('click', () => {
    document.getElementById('modalConfirmacao').classList.remove('ativo');
});

document.getElementById('modalAlumni')?.addEventListener('click', (e) => {
    if (e.target === document.getElementById('modalAlumni')) fecharModalAlumni();
});

document.getElementById('modalConfirmacao')?.addEventListener('click', (e) => {
    if (e.target === document.getElementById('modalConfirmacao')) {
        document.getElementById('modalConfirmacao').classList.remove('ativo');
    }
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        fecharModalAlumni();
        document.getElementById('modalConfirmacao').classList.remove('ativo');
    }
});

// Inicializar filtros
document.getElementById('campoBusca')?.addEventListener('input', aplicarFiltros);
document.getElementById('filtroDestaque')?.addEventListener('change', aplicarFiltros);
document.getElementById('filtroCurso')?.addEventListener('change', aplicarFiltros);
</script>

<?php include 'includes/footer.php'; ?>
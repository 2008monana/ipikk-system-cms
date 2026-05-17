<?php
/**
 * Plano Curricular - Área Restrita IPIKK
 * Gestão completa do plano curricular por curso
 */

$titulo_pagina = 'Plano Curricular';
$css_especifico = 'admin-plano-curricular.css';

require_once dirname(__DIR__) . '/config/index.php';

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

$config = $db->query("SELECT * FROM configuracoes WHERE id = 1")->fetch();

// Buscar cursos ativos
$cursos = $db->query("SELECT id, nome FROM cursos WHERE estado = 'ativo' ORDER BY nome")->fetchAll();

// Curso selecionado (padrão: primeiro curso)
$curso_id = isset($_GET['curso_id']) ? (int)$_GET['curso_id'] : ($cursos[0]['id'] ?? 0);
$curso_nome = '';
$area_nome = '';

foreach ($cursos as $c) {
    if ($c['id'] == $curso_id) {
        $curso_nome = addslashes($c['nome']);
        break;
    }
}

// Buscar área do curso
$stmt = $db->prepare("
    SELECT a.nome as area_nome 
    FROM cursos c 
    JOIN areas a ON c.area_id = a.id 
    WHERE c.id = ?
");
$stmt->execute([$curso_id]);
$area = $stmt->fetch();
$area_nome = $area ? addslashes($area['area_nome']) : '';

// Buscar disciplinas do plano curricular
$disciplinas = $db->prepare("
    SELECT * FROM planos_curriculares 
    WHERE curso_id = ? AND ativo = 1 
    ORDER BY ordem
");
$disciplinas->execute([$curso_id]);
$disciplinas = $disciplinas->fetchAll();

// Preparar dados para o JavaScript
$disciplinas_json = json_encode($disciplinas);
$cursos_json = json_encode($cursos);

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
                <i class="fas fa-calendar-alt"></i> Plano Curricular
            </h1>
        </div>
        <div class="direita-barra">
            <button class="btn-primario" id="btnNovaDisciplina" onclick="abrirModalDisciplina()">
                <i class="fas fa-plus"></i>
                <span>Nova Disciplina</span>
            </button>
        </div>
    </header>

    <div class="conteudo-pagina">

        <!-- Seletor de Curso -->
        <div class="seletor-curso">
            <label><i class="fas fa-graduation-cap"></i> Curso:</label>
            <select id="seletorCurso" class="selecao-curso" onchange="mudarCurso()">
                <?php foreach ($cursos as $curso): ?>
                <option value="<?= $curso['id'] ?>" <?= $curso_id == $curso['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($curso['nome']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Cards de Estatísticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon purple"><i class="fas fa-book"></i></div>
                <div class="stat-info">
                    <h3><?= count($disciplinas) ?></h3>
                    <p>Disciplinas</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-layer-group"></i></div>
                <div class="stat-info">
                    <h3>4</h3>
                    <p>Anos/Classes</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-chalkboard"></i></div>
                <div class="stat-info">
                    <h3 id="totalHoras">0</h3>
                    <p>Carga Horária Total</p>
                </div>
            </div>
        </div>

        <!-- Visualizador do Plano Curricular com Abas -->
        <div class="plano-container">
            <div class="tabs-header">
                <button class="tab-btn ativo" data-tab="tab10">10ª Classe</button>
                <button class="tab-btn" data-tab="tab11">11ª Classe</button>
                <button class="tab-btn" data-tab="tab12">12ª Classe</button>
                <button class="tab-btn" data-tab="tab13">13ª Classe</button>
                <button class="tab-btn" data-tab="tabGeral">Geral (Completo)</button>
            </div>

            <div class="tabs-content">
                <!-- 10ª Classe -->
                <div id="tab10" class="tab-pane ativo">
                    <div class="tab-header">
                        <h3>Plano Curricular - 10ª Classe</h3>
                        <button class="btn-pdf" onclick="gerarPDF(10)">
                            <i class="fas fa-file-pdf"></i> Gerar PDF
                        </button>
                    </div>
                    <div class="tabela-wrapper">
                        <table class="tabela-plano" id="tabela10">
                            <thead><tr><th>Disciplinas</th><th>Horas Semanais</th><th width="100">Ações</th></tr></thead>
                            <tbody id="tbody10"></tbody>
                        </table>
                    </div>
                </div>

                <!-- 11ª Classe -->
                <div id="tab11" class="tab-pane">
                    <div class="tab-header">
                        <h3>Plano Curricular - 11ª Classe</h3>
                        <button class="btn-pdf" onclick="gerarPDF(11)">
                            <i class="fas fa-file-pdf"></i> Gerar PDF
                        </button>
                    </div>
                    <div class="tabela-wrapper">
                        <table class="tabela-plano" id="tabela11">
                            <thead><tr><th>Disciplinas</th><th>Horas Semanais</th><th width="100">Ações</th></tr></thead>
                            <tbody id="tbody11"></tbody>
                        </table>
                    </div>
                </div>

                <!-- 12ª Classe -->
                <div id="tab12" class="tab-pane">
                    <div class="tab-header">
                        <h3>Plano Curricular - 12ª Classe</h3>
                        <button class="btn-pdf" onclick="gerarPDF(12)">
                            <i class="fas fa-file-pdf"></i> Gerar PDF
                        </button>
                    </div>
                    <div class="tabela-wrapper">
                        <table class="tabela-plano" id="tabela12">
                            <thead><tr><th>Disciplinas</th><th>Horas Semanais</th><th width="100">Ações</th></tr></thead>
                            <tbody id="tbody12"></tbody>
                        </table>
                    </div>
                </div>

                <!-- 13ª Classe -->
                <div id="tab13" class="tab-pane">
                    <div class="tab-header">
                        <h3>Plano Curricular - 13ª Classe</h3>
                        <button class="btn-pdf" onclick="gerarPDF(13)">
                            <i class="fas fa-file-pdf"></i> Gerar PDF
                        </button>
                    </div>
                    <div class="tabela-wrapper">
                        <table class="tabela-plano" id="tabela13">
                            <thead><tr><th>Disciplinas</th><th>Horas Semanais</th><th width="100">Ações</th></tr></thead>
                            <tbody id="tbody13"></tbody>
                        </table>
                    </div>
                </div>

                <!-- Geral (Completo) - Estilo Word -->
                <div id="tabGeral" class="tab-pane">
                    <div class="tab-header">
                        <h3>Plano Curricular Completo - 10ª à 13ª Classe</h3>
                        <button class="btn-pdf" onclick="gerarPDF(0)">
                            <i class="fas fa-file-pdf"></i> Gerar PDF Completo
                        </button>
                    </div>
                    <div class="tabela-wrapper" id="tabelaGeralContainer">
                        <div class="word-table-container">
                            <table class="word-table" id="tabelaGeral">
                                <thead>
                                    <tr>
                                        <th rowspan="2">Disciplinas</th>
                                        <th colspan="4">Horas Curriculares Semanais</th>
                                        <th rowspan="2" width="100">Ações</th>
                                    </tr>
                                    <tr>
                                        <th>10ª Classe</th>
                                        <th>11ª Classe</th>
                                        <th>12ª Classe</th>
                                        <th>13ª Classe</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyGeral"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Editor de Disciplina (Modal) -->
        <div id="modalDisciplina" class="modal">
            <div class="modal-conteudo modal-grande">
                <div class="modal-cabecalho">
                    <h2 id="modalTitulo"><i class="fas fa-plus"></i> Nova Disciplina</h2>
                    <button class="modal-fechar" onclick="fecharModalDisciplina()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-corpo">
                    <form id="formDisciplina" onsubmit="return salvarDisciplina(event)">
                        <input type="hidden" id="disciplinaId">

                        <div class="linha-form">
                            <div class="grupo-form">
                                <label><i class="fas fa-heading"></i> Disciplina *</label>
                                <input type="text" id="disciplinaNome" class="campo-form" required>
                            </div>
                            <div class="grupo-form">
                                <label><i class="fas fa-layer-group"></i> Componente</label>
                                <select id="disciplinaComponente" class="campo-form">
                                    <option value="sociocultural">Sociocultural</option>
                                    <option value="cientifica">Científica</option>
                                    <option value="tecnica">Técnica, Tecnológica e Prática</option>
                                </select>
                            </div>
                        </div>

                        <div class="linha-form">
                            <div class="grupo-form">
                                <label><i class="fas fa-sort-numeric-down"></i> Ordem</label>
                                <input type="number" id="disciplinaOrdem" class="campo-form" value="0" min="0">
                            </div>
                            <div class="grupo-form">
                                <label><i class="fas fa-check-circle"></i> Ativo</label>
                                <div class="wrapper-toggle">
                                    <label class="toggle-switch">
                                        <input type="checkbox" id="disciplinaAtivo" checked>
                                        <span class="toggle-slider"></span>
                                    </label>
                                    <span>Visível no plano</span>
                                </div>
                            </div>
                        </div>

                        <div class="secao-horarios">
                            <h4><i class="fas fa-clock"></i> Carga Horária Semanal</h4>
                            <div class="linha-form">
                                <div class="grupo-form">
                                    <label>10ª Classe</label>
                                    <input type="number" id="horas_10a" class="campo-form" value="0" min="0" step="1">
                                </div>
                                <div class="grupo-form">
                                    <label>11ª Classe</label>
                                    <input type="number" id="horas_11a" class="campo-form" value="0" min="0" step="1">
                                </div>
                            </div>
                            <div class="linha-form">
                                <div class="grupo-form">
                                    <label>12ª Classe</label>
                                    <input type="number" id="horas_12a" class="campo-form" value="0" min="0" step="1">
                                </div>
                                <div class="grupo-form">
                                    <label>13ª Classe</label>
                                    <input type="number" id="horas_13a" class="campo-form" value="0" min="0" step="1">
                                </div>
                            </div>
                        </div>

                        <div class="modal-acoes">
                            <button type="button" class="btn-cancelar" onclick="fecharModalDisciplina()">
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
/* ===== ESTILOS ADMIN PLANO CURRICULAR ===== */
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

.seletor-curso {
    background: white;
    border-radius: 16px;
    padding: 24px 28px;
    margin-bottom: 30px;
    display: flex;
    align-items: center;
    gap: 20px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
    border: 1px solid #f0f2f5;
    flex-wrap: wrap;
}

.seletor-curso label {
    font-weight: 700;
    font-size: 0.85rem;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 8px;
    letter-spacing: 0.3px;
}

.seletor-curso label i {
    color: #0a9396;
}

.selecao-curso {
    padding: 12px 18px;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    font-size: 0.9rem;
    min-width: 280px;
    background: white;
    cursor: pointer;
    transition: all 0.2s ease;
    font-family: inherit;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748b'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 16px center;
    background-size: 18px;
    padding-right: 45px;
}

.selecao-curso:hover {
    border-color: #cbd5e1;
}

.selecao-curso:focus {
    outline: none;
    border-color: #0a9396;
    box-shadow: 0 0 0 3px rgba(10, 147, 150, 0.1);
}

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
.stat-icon.blue { 
    background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%);
    color: #0284c7; 
}
.stat-icon.green { 
    background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
    color: #16a34a; 
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

/* Tabs */
.plano-container {
    background: white;
    border-radius: 24px;
    overflow: hidden;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
    border: 1px solid #f0f2f5;
}

.tabs-header {
    display: flex;
    background: #f8fafc;
    border-bottom: 1px solid #eef2f8;
    flex-wrap: wrap;
    padding: 0 8px;
}

.tab-btn {
    padding: 16px 28px;
    background: none;
    border: none;
    font-size: 0.85rem;
    font-weight: 600;
    color: #64748b;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
}

.tab-btn:hover {
    color: #0a9396;
}

.tab-btn.ativo {
    color: #0a9396;
}

.tab-btn.ativo::after {
    content: '';
    position: absolute;
    bottom: -1px;
    left: 0;
    width: 100%;
    height: 3px;
    background: #0a9396;
}

.tabs-content {
    padding: 28px;
}

.tab-pane {
    display: none;
    animation: fadeIn 0.3s ease;
}

.tab-pane.ativo {
    display: block;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Header da Tab */
.tab-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 16px;
    padding-bottom: 16px;
    border-bottom: 2px solid #f1f5f9;
}

.tab-header h3 {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 700;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 10px;
}

.tab-header h3 i {
    color: #0a9396;
    font-size: 1.2rem;
}

/* Botão PDF */
.btn-pdf {
    background: #dc2626;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 40px;
    font-weight: 600;
    font-size: 0.75rem;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-pdf:hover {
    background: #b91c1c;
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(220, 38, 38, 0.3);
}

/* Botão Adicionar Disciplina - CORRIGIDO */
.btn-adicionar-disciplina {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border: 1px solid #e2e8f0;
    padding: 12px 24px;
    border-radius: 40px;
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    color: #475569;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    letter-spacing: 0.3px;
    margin-bottom: 20px;
}

.btn-adicionar-disciplina i {
    font-size: 0.85rem;
    transition: transform 0.2s ease;
}

.btn-adicionar-disciplina:hover {
    background: #0a9396;
    border-color: #0a9396;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(10, 147, 150, 0.3);
}

.btn-adicionar-disciplina:hover i {
    transform: rotate(90deg);
}

/* Tabela wrapper */
.tabela-wrapper {
    overflow-x: auto;
    border-radius: 12px;
    border: 1px solid #eef2f8;
}

/* Estilo para tabelas de classe individual */
.tabela-plano {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.8rem;
    background: white;
}

.tabela-plano th,
.tabela-plano td {
    border: 1px solid #e2e8f0;
    padding: 12px 10px;
    text-align: center;
}

.tabela-plano th {
    background: #f8fafc;
    font-weight: 700;
    color: #1e293b;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.tabela-plano td.left,
.tabela-plano th.left {
    text-align: left;
}

.tabela-plano .section-row {
    background: #f1f5f9;
    font-weight: 700;
    color: #1e293b;
}

.tabela-plano .subtotal-row {
    background: #f8fafc;
    font-weight: 600;
}

.tabela-plano .total-row {
    background: #e0f2fe;
    font-weight: 700;
    color: #0a9396;
}

/* Botões de ação na tabela */
.btn-acao-editar, .btn-acao-eliminar {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    border: none;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin: 0 4px;
}

.btn-acao-editar {
    background: #e0f2fe;
    color: #0284c7;
}

.btn-acao-editar:hover {
    background: #0284c7;
    color: white;
    transform: scale(1.05);
}

.btn-acao-eliminar {
    background: #fee2e2;
    color: #dc2626;
}

.btn-acao-eliminar:hover {
    background: #dc2626;
    color: white;
    transform: scale(1.05);
}

/* Estilo para tabela GERAL - Estilo Word */
.word-table-container {
    background: white;
    padding: 20px 0;
    overflow-x: auto;
}

.word-table {
    width: 100%;
    border-collapse: collapse;
    font-family: 'Poppins', Arial, sans-serif;
    font-size: 0.8rem;
    background: white;
}

.word-table th,
.word-table td {
    border: 1px solid #cbd5e1;
    padding: 12px 10px;
    text-align: center;
}

.word-table th {
    background-color: #f1f5f9;
    font-weight: 700;
    color: #1e293b;
    font-size: 0.75rem;
    text-transform: uppercase;
}

.word-table td:first-child,
.word-table th:first-child {
    text-align: left;
    font-weight: 600;
}

.word-table .section-row {
    background-color: #f1f5f9;
    font-weight: 700;
}

.word-table .section-row td {
    text-align: left;
    font-weight: 700;
    color: #1e293b;
}

.word-table .highlight {
    background-color: #fef3c7;
    font-weight: 700;
}

.word-table .subtotal-row {
    font-weight: 700;
    background-color: #f8fafc;
}

.word-table .total-row {
    font-weight: 700;
    background-color: #e0f2fe;
    color: #0a9396;
}

.word-table .text-center {
    text-align: center;
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
    border-radius: 24px;
    max-width: 650px;
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
    max-width: 700px;
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
    font-size: 1.2rem;
    font-weight: 700;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 12px;
    color: #1e293b;
}

.modal-cabecalho h2 i {
    color: #0a9396;
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
    margin-bottom: 0;
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

select.campo-form {
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748b'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 14px center;
    background-size: 18px;
    padding-right: 45px;
}

/* Secção de horários */
.secao-horarios {
    background: #f8fafc;
    border-radius: 16px;
    padding: 20px;
    margin-top: 20px;
}

.secao-horarios h4 {
    font-size: 0.85rem;
    font-weight: 700;
    margin: 0 0 15px 0;
    color: #0a9396;
    display: flex;
    align-items: center;
    gap: 8px;
}

.secao-horarios .linha-form {
    margin-bottom: 0;
}

/* Toggle Switch */
.wrapper-toggle {
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

.notificacao.erro {
    background: linear-gradient(135deg, #ef4444, #dc2626);
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

/* Scrollbar personalizada */
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

/* Responsividade */
@media (max-width: 1024px) {
    .seletor-curso {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .selecao-curso {
        width: 100%;
        min-width: auto;
    }
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
        gap: 12px;
    }
    
    .tabs-header {
        flex-wrap: wrap;
    }
    
    .tab-btn {
        flex: 1;
        text-align: center;
        padding: 12px 16px;
        font-size: 0.75rem;
    }
    
    .tabs-content {
        padding: 20px;
    }
    
    .linha-form {
        grid-template-columns: 1fr;
        gap: 12px;
    }
    
    .tab-header {
        flex-direction: column;
        align-items: flex-start;
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
    
    .btn-adicionar-disciplina {
        width: 100%;
        justify-content: center;
    }
    
    .notificacao {
        left: 16px;
        right: 16px;
        top: 16px;
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .stat-card {
        padding: 16px;
    }
    
    .stat-info h3 {
        font-size: 24px;
    }
    
    .tabela-plano th,
    .tabela-plano td {
        padding: 8px 6px;
        font-size: 0.7rem;
    }
    
    .btn-acao-editar, .btn-acao-eliminar {
        width: 28px;
        height: 28px;
    }
}
</style>

<script>
// Dados do PHP para JavaScript
const disciplinasData = <?php echo $disciplinas_json ?: '[]'; ?>;
const cursoAtual = <?php echo $curso_id; ?>;
const cursoNome = '<?php echo addslashes($curso_nome); ?>';
const areaNome = '<?php echo addslashes($area_nome); ?>';

function mostrarNotificacao(mensagem, tipo = 'sucesso') {
    const notif = document.createElement('div');
    notif.className = `notificacao ${tipo}`;
    notif.innerHTML = `<i class="fas fa-${tipo === 'sucesso' ? 'check-circle' : 'exclamation-circle'}"></i> ${mensagem}`;
    document.body.appendChild(notif);
    setTimeout(() => notif.remove(), 3000);
}

function mudarCurso() {
    const novoCurso = document.getElementById('seletorCurso').value;
    if (novoCurso) {
        window.location.href = `admin-planos-curriculares.php?curso_id=${novoCurso}`;
    }
}

function carregarDisciplinas() {
    fetch(`processos/processar-plano-curricular.php?action=listar&curso_id=${cursoAtual}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                disciplinasData.length = 0;
                disciplinasData.push(...data.disciplinas);
                renderizarTabelas();
                calcularTotalHoras();
            }
        })
        .catch(error => console.error('Erro:', error));
}

function renderizarTabelas() {
    // Agrupar por componente
    const sociocultural = disciplinasData.filter(d => d.componente === 'sociocultural');
    const cientifica = disciplinasData.filter(d => d.componente === 'cientifica');
    const tecnica = disciplinasData.filter(d => d.componente === 'tecnica');

    // Renderizar cada aba
    renderizarTabelaClasse(10, 'tbody10', sociocultural, cientifica, tecnica);
    renderizarTabelaClasse(11, 'tbody11', sociocultural, cientifica, tecnica);
    renderizarTabelaClasse(12, 'tbody12', sociocultural, cientifica, tecnica);
    renderizarTabelaClasse(13, 'tbody13', sociocultural, cientifica, tecnica);
    renderizarTabelaGeralWord('tbodyGeral', sociocultural, cientifica, tecnica);
}

function renderizarTabelaClasse(classe, tbodyId, sociocultural, cientifica, tecnica) {
    const tbody = document.getElementById(tbodyId);
    if (!tbody) return;

    let html = '';
    let total = 0;

    // Componente Sociocultural
    html += '<tr class="section-row"><td colspan="3"><strong>Componente Sociocultural</strong></td></tr>';
    sociocultural.forEach(disp => {
        const id = disp.id;
        const horas = disp[`horas_${classe}a`] || 0;
        total += horas;
        html += `<tr>
            <td class="left">${escapeHtml(disp.disciplina)}</td>
            <td class="text-center">${horas}</td>
            <td class="text-center">
                <button class="btn-acao-editar" onclick="editarDisciplina(${id})" title="Editar"><i class="fas fa-edit"></i></button>
                <button class="btn-acao-eliminar" onclick="eliminarDisciplina(${id}, '${escapeHtml(disp.disciplina)}')" title="Eliminar"><i class="fas fa-trash"></i></button>
            </td>
        </tr>`;
    });
    html += `<tr class="subtotal-row"><td class="left"><strong>Subtotal</strong></td><td class="text-center"><strong>${total}</strong></td><td></td></tr>`;

    // Componente Científica
    html += '<tr class="section-row"><td colspan="3"><strong>Componente Científica</strong></td></tr>';
    cientifica.forEach(disp => {
        const id = disp.id;
        const horas = disp[`horas_${classe}a`] || 0;
        total += horas;
        html += `<tr>
            <td class="left">${escapeHtml(disp.disciplina)}</td>
            <td class="text-center">${horas}</td>
            <td class="text-center">
                <button class="btn-acao-editar" onclick="editarDisciplina(${id})" title="Editar"><i class="fas fa-edit"></i></button>
                <button class="btn-acao-eliminar" onclick="eliminarDisciplina(${id}, '${escapeHtml(disp.disciplina)}')" title="Eliminar"><i class="fas fa-trash"></i></button>
            </td>
        </tr>`;
    });
    html += `<tr class="subtotal-row"><td class="left"><strong>Subtotal</strong></td><td class="text-center"><strong>${total}</strong></td><td></td></tr>`;

    // Componente Técnica
    html += '<tr class="section-row"><td colspan="3"><strong>Componente Técnica, Tecnológica e Prática</strong></td></tr>';
    tecnica.forEach(disp => {
        const id = disp.id;
        const horas = disp[`horas_${classe}a`] || 0;
        total += horas;
        html += `<tr>
            <td class="left">${escapeHtml(disp.disciplina)}</td>
            <td class="text-center">${horas}</td>
            <td class="text-center">
                <button class="btn-acao-editar" onclick="editarDisciplina(${id})" title="Editar"><i class="fas fa-edit"></i></button>
                <button class="btn-acao-eliminar" onclick="eliminarDisciplina(${id}, '${escapeHtml(disp.disciplina)}')" title="Eliminar"><i class="fas fa-trash"></i></button>
            </td>
        </tr>`;
    });
    html += `<tr class="subtotal-row"><td class="left"><strong>Subtotal</strong></td><td class="text-center"><strong>${total}</strong></td><td></td></tr>`;
    html += `<tr class="total-row"><td class="left"><strong>TOTAL</strong></td><td class="text-center"><strong>${total}</strong></td><td></td></tr>`;

    tbody.innerHTML = html;
}

function renderizarTabelaGeralWord(tbodyId, sociocultural, cientifica, tecnica) {
    const tbody = document.getElementById(tbodyId);
    if (!tbody) return;

    let html = '';

    // Componente Sociocultural
    html += '<tr class="section-row"><td colspan="6"><strong>Componente Sociocultural</strong></td></tr>';
    sociocultural.forEach(disp => {
        const id = disp.id;
        html += `<tr>
            <td class="left">${escapeHtml(disp.disciplina)}</td>
            <td class="text-center ${disp.horas_10a > 4 ? 'highlight' : ''}">${disp.horas_10a || '-'}</td>
            <td class="text-center ${disp.horas_11a > 4 ? 'highlight' : ''}">${disp.horas_11a || '-'}</td>
            <td class="text-center ${disp.horas_12a > 4 ? 'highlight' : ''}">${disp.horas_12a || '-'}</td>
            <td class="text-center ${disp.horas_13a > 4 ? 'highlight' : ''}">${disp.horas_13a || '-'}</td>
            <td class="text-center">
                <button class="btn-acao-editar" onclick="editarDisciplina(${id})" title="Editar"><i class="fas fa-edit"></i></button>
                <button class="btn-acao-eliminar" onclick="eliminarDisciplina(${id}, '${escapeHtml(disp.disciplina)}')" title="Eliminar"><i class="fas fa-trash"></i></button>
            </td>
        </tr>`;
    });

    // Componente Científica
    html += '<tr class="section-row"><td colspan="6"><strong>Componente Científica</strong></tr>';
    cientifica.forEach(disp => {
        const id = disp.id;
        html += `<tr>
            <td class="left">${escapeHtml(disp.disciplina)}</td>
            <td class="text-center">${disp.horas_10a || '-'}</td>
            <td class="text-center">${disp.horas_11a || '-'}</td>
            <td class="text-center">${disp.horas_12a || '-'}</td>
            <td class="text-center">${disp.horas_13a || '-'}</td>
            <td class="text-center">
                <button class="btn-acao-editar" onclick="editarDisciplina(${id})" title="Editar"><i class="fas fa-edit"></i></button>
                <button class="btn-acao-eliminar" onclick="eliminarDisciplina(${id}, '${escapeHtml(disp.disciplina)}')" title="Eliminar"><i class="fas fa-trash"></i></button>
            </td>
        </tr>`;
    });

    // Componente Técnica
    html += '<tr class="section-row"><td colspan="6"><strong>Componente Técnica, Tecnológica e Prática</strong></tr>';
    tecnica.forEach(disp => {
        const id = disp.id;
        html += `<tr>
            <td class="left">${escapeHtml(disp.disciplina)}</td>
            <td class="text-center">${disp.horas_10a || '-'}</td>
            <td class="text-center">${disp.horas_11a || '-'}</td>
            <td class="text-center">${disp.horas_12a || '-'}</td>
            <td class="text-center">${disp.horas_13a || '-'}</td>
            <td class="text-center">
                <button class="btn-acao-editar" onclick="editarDisciplina(${id})" title="Editar"><i class="fas fa-edit"></i></button>
                <button class="btn-acao-eliminar" onclick="eliminarDisciplina(${id}, '${escapeHtml(disp.disciplina)}')" title="Eliminar"><i class="fas fa-trash"></i></button>
            </td>
        </tr>`;
    });

    // Estágio (fixo para 13ª classe)
    html += `<tr class="section-row">
        <td class="left">Estágio Curricular Supervisionado</td>
        <td class="text-center">-</td>
        <td class="text-center">-</td>
        <td class="text-center">-</td>
        <td class="text-center">20</td>
        <td class="text-center">
            <button class="btn-acao-editar" onclick="editarEstagio()" title="Editar Estágio"><i class="fas fa-edit"></i></button>
        </td>
    </tr>`;

    // Calcular totais
    let total10a = 0, total11a = 0, total12a = 0, total13a = 0;
    disciplinasData.forEach(disp => {
        total10a += disp.horas_10a || 0;
        total11a += disp.horas_11a || 0;
        total12a += disp.horas_12a || 0;
        total13a += disp.horas_13a || 0;
    });
    total13a += 20; // Adicionar estágio

    html += `<tr class="subtotal-row">
        <td class="left"><strong>Subtotal</strong></td>
        <td class="text-center"><strong>${total10a}</strong></td>
        <td class="text-center"><strong>${total11a}</strong></td>
        <td class="text-center"><strong>${total12a}</strong></td>
        <td class="text-center"><strong>${total13a}</strong></td>
        <td></td>
    </tr>`;

    html += `<tr class="total-row">
        <td class="left"><strong>TOTAL</strong></td>
        <td class="text-center"><strong>${total10a}</strong></td>
        <td class="text-center"><strong>${total11a}</strong></td>
        <td class="text-center"><strong>${total12a}</strong></td>
        <td class="text-center"><strong>${total13a}</strong></td>
        <td></td>
    </tr>`;

    tbody.innerHTML = html;
}

function editarEstagio() {
    // Encontrar ou criar disciplina de estágio
    let estagio = disciplinasData.find(d => d.disciplina === 'Estágio Curricular Supervisionado');
    
    if (!estagio) {
        // Criar um objeto temporário para o estágio
        estagio = {
            id: null,
            disciplina: 'Estágio Curricular Supervisionado',
            componente: 'tecnica',
            ordem: 999,
            ativo: 1,
            horas_10a: 0,
            horas_11a: 0,
            horas_12a: 0,
            horas_13a: 20
        };
    }
    
    abrirModalDisciplina(estagio.id, estagio);
}

function calcularTotalHoras() {
    let total = 0;
    disciplinasData.forEach(disp => {
        total += (disp.horas_10a || 0) + (disp.horas_11a || 0) + (disp.horas_12a || 0) + (disp.horas_13a || 0);
    });
    document.getElementById('totalHoras').textContent = total;
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function abrirModalDisciplina(id = null, dadosPreenchidos = null) {
    const modal = document.getElementById('modalDisciplina');
    const titulo = document.getElementById('modalTitulo');

    document.getElementById('formDisciplina').reset();
    document.getElementById('disciplinaId').value = '';
    document.getElementById('disciplinaAtivo').checked = true;
    document.getElementById('disciplinaOrdem').value = '0';
    document.getElementById('horas_10a').value = 0;
    document.getElementById('horas_11a').value = 0;
    document.getElementById('horas_12a').value = 0;
    document.getElementById('horas_13a').value = 0;

    if (id) {
        const disp = dadosPreenchidos || disciplinasData.find(d => d.id == id);
        if (disp) {
            titulo.innerHTML = '<i class="fas fa-edit"></i> Editar Disciplina';
            document.getElementById('disciplinaId').value = disp.id || '';
            document.getElementById('disciplinaNome').value = disp.disciplina;
            document.getElementById('disciplinaComponente').value = disp.componente;
            document.getElementById('disciplinaOrdem').value = disp.ordem;
            document.getElementById('disciplinaAtivo').checked = disp.ativo == 1;
            document.getElementById('horas_10a').value = disp.horas_10a || 0;
            document.getElementById('horas_11a').value = disp.horas_11a || 0;
            document.getElementById('horas_12a').value = disp.horas_12a || 0;
            document.getElementById('horas_13a').value = disp.horas_13a || 0;
        }
    } else {
        titulo.innerHTML = '<i class="fas fa-plus"></i> Nova Disciplina';
    }

    modal.classList.add('ativo');
}

function editarDisciplina(id) {
    abrirModalDisciplina(id);
}

function fecharModalDisciplina() {
    document.getElementById('modalDisciplina').classList.remove('ativo');
}

async function salvarDisciplina(event) {
    event.preventDefault();

    const id = document.getElementById('disciplinaId').value;
    const disciplina = document.getElementById('disciplinaNome').value.trim();
    const componente = document.getElementById('disciplinaComponente').value;
    const ordem = document.getElementById('disciplinaOrdem').value;
    const ativo = document.getElementById('disciplinaAtivo').checked ? 1 : 0;
    const horas_10a = parseInt(document.getElementById('horas_10a').value) || 0;
    const horas_11a = parseInt(document.getElementById('horas_11a').value) || 0;
    const horas_12a = parseInt(document.getElementById('horas_12a').value) || 0;
    const horas_13a = parseInt(document.getElementById('horas_13a').value) || 0;

    if (!disciplina) {
        mostrarNotificacao('Nome da disciplina é obrigatório', 'erro');
        return;
    }

    const formData = new FormData();
    formData.append('action', id ? 'editar' : 'salvar');
    if (id) formData.append('id', id);
    formData.append('curso_id', cursoAtual);
    formData.append('disciplina', disciplina);
    formData.append('componente', componente);
    formData.append('ordem', ordem);
    formData.append('ativo', ativo);
    formData.append('horas_10a', horas_10a);
    formData.append('horas_11a', horas_11a);
    formData.append('horas_12a', horas_12a);
    formData.append('horas_13a', horas_13a);

    try {
        const response = await fetch('processos/processar-plano-curricular.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        if (data.success) {
            mostrarNotificacao(data.message, 'sucesso');
            fecharModalDisciplina();
            carregarDisciplinas();
        } else {
            mostrarNotificacao(data.message, 'erro');
        }
    } catch (error) {
        mostrarNotificacao('Erro ao salvar', 'erro');
    }
}

function eliminarDisciplina(id, nome) {
    const modal = document.getElementById('modalConfirmacao');
    document.getElementById('confirmacaoTitulo').textContent = 'Eliminar Disciplina';
    document.getElementById('confirmacaoTexto').textContent = `Tem certeza que deseja eliminar "${nome}" permanentemente?`;

    const confirmar = document.getElementById('btnConfirmarAcao');
    const novoConfirmar = confirmar.cloneNode(true);
    confirmar.parentNode.replaceChild(novoConfirmar, confirmar);

    novoConfirmar.onclick = async () => {
        try {
            const response = await fetch('processos/processar-plano-curricular.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ action: 'eliminar', id: id })
            });
            const data = await response.json();
            if (data.success) {
                mostrarNotificacao(data.message, 'sucesso');
                carregarDisciplinas();
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

function gerarPDF(classe) {
    const sociocultural = disciplinasData.filter(d => d.componente === 'sociocultural');
    const cientifica = disciplinasData.filter(d => d.componente === 'cientifica');
    const tecnica = disciplinasData.filter(d => d.componente === 'tecnica');

    let html = `<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Plano Curricular - ${cursoNome}</title>
        <style>
            body { font-family: Arial, sans-serif; background: white; padding: 20px; margin: 0; }
            .container { max-width: 1100px; margin: auto; background: white; }
            h2, h3 { margin: 5px 0; }
            table { width: 100%; border-collapse: collapse; margin-top: 15px; }
            th, td { border: 1px solid #999; padding: 8px; text-align: center; font-size: 12px; }
            th { background-color: #e0e0e0; }
            .left { text-align: left; }
            .section { background-color: #d9d9d9; font-weight: bold; text-align: left; }
            .highlight { background-color: #fff2cc; font-weight: bold; }
            .subtotal { font-weight: bold; background-color: #f0f0f0; }
            .total { font-weight: bold; background-color: #cfe2f3; }
            .text-center { text-align: center; }
            @media print {
                body { padding: 0; }
                .container { margin: 0; }
                .no-print { display: none; }
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h2>Curso: ${cursoNome} (Formação Média Técnica)</h2>
            <h3>Área de Formação: ${areaNome}</h3>`;

    if (classe === 0) {
        // Geral - Estilo Word
        html += `<table>
            <thead>
                <tr><th rowspan="2">Disciplinas</th><th colspan="4">Horas Curriculares Semanais</th></tr>
                <tr><th>10ª Classe</th><th>11ª Classe</th><th>12ª Classe</th><th>13ª Classe</th></tr>
            </thead>
            <tbody>`;
        
        // Sociocultural
        html += `<tr class="section"><td colspan="5">Componente Sociocultural</td></tr>`;
        sociocultural.forEach(d => {
            html += `<tr>
                <td class="left">${d.disciplina}</td>
                <td>${d.horas_10a || '-'}</td>
                <td>${d.horas_11a || '-'}</td>
                <td>${d.horas_12a || '-'}</td>
                <td>${d.horas_13a || '-'}</td>
            </tr>`;
        });
        
        // Científica
        html += `<tr class="section"><td colspan="5">Componente Científica</td></tr>`;
        cientifica.forEach(d => {
            html += `<tr>
                <td class="left">${d.disciplina}</td>
                <td>${d.horas_10a || '-'}</td>
                <td>${d.horas_11a || '-'}</td>
                <td>${d.horas_12a || '-'}</td>
                <td>${d.horas_13a || '-'}</td>
            </tr>`;
        });
        
        // Técnica
        html += `<tr class="section"><td colspan="5">Componente Técnica, Tecnológica e Prática</td></tr>`;
        tecnica.forEach(d => {
            html += `<tr>
                <td class="left">${d.disciplina}</td>
                <td>${d.horas_10a || '-'}</td>
                <td>${d.horas_11a || '-'}</td>
                <td>${d.horas_12a || '-'}</td>
                <td>${d.horas_13a || '-'}</td>
            </tr>`;
        });
        
        html += `<tr class="section">
            <td class="left">Estágio Curricular Supervisionado</td>
            <td>-</td><td>-</td><td>-</td><td>20</td>
        </tr>`;
        
        // Totais
        let total10a = 0, total11a = 0, total12a = 0, total13a = 0;
        disciplinasData.forEach(d => {
            total10a += d.horas_10a || 0;
            total11a += d.horas_11a || 0;
            total12a += d.horas_12a || 0;
            total13a += d.horas_13a || 0;
        });
        total13a += 20;
        
        html += `<tr class="subtotal">
            <td class="left">Subtotal</td>
            <td>${total10a}</td><td>${total11a}</td><td>${total12a}</td><td>${total13a}</td>
        </tr>
        <tr class="total">
            <td class="left">TOTAL</td>
            <td>${total10a}</td><td>${total11a}</td><td>${total12a}</td><td>${total13a}</td>
        </tr>`;
        
        html += `</tbody></table>`;
    } else {
        // Classe específica
        const classeTexto = {10: '10ª', 11: '11ª', 12: '12ª', 13: '13ª'}[classe];
        html += `<h3>Plano Curricular - ${classeTexto} Classe</h3>
        <table>
            <thead><tr><th>Disciplinas</th><th>Horas Semanais</th></tr></thead>
            <tbody>`;
        
        let total = 0;
        
        html += `<tr class="section"><td colspan="2">Componente Sociocultural</td></tr>`;
        sociocultural.forEach(d => {
            const horas = d[`horas_${classe}a`] || 0;
            total += horas;
            html += `<tr><td class="left">${d.disciplina}</td><td>${horas}</td></tr>`;
        });
        
        html += `<tr class="section"><td colspan="2">Componente Científica</td></tr>`;
        cientifica.forEach(d => {
            const horas = d[`horas_${classe}a`] || 0;
            total += horas;
            html += `<tr><td class="left">${d.disciplina}</td><td>${horas}</td></tr>`;
        });
        
        html += `<tr class="section"><td colspan="2">Componente Técnica, Tecnológica e Prática</td></tr>`;
        tecnica.forEach(d => {
            const horas = d[`horas_${classe}a`] || 0;
            total += horas;
            html += `<tr><td class="left">${d.disciplina}</td><td>${horas}</td></tr>`;
        });
        
        html += `<tr class="total"><td class="left">TOTAL</td><td>${total}</td></tr>`;
        html += `</tbody></table>`;
    }
    
    html += `</div></body></html>`;
    
    const win = window.open();
    win.document.write(html);
    win.document.close();
    win.print();
}

// Tabs
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const tabId = this.dataset.tab;
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('ativo'));
        document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('ativo'));
        this.classList.add('ativo');
        document.getElementById(tabId).classList.add('ativo');
    });
});

document.getElementById('btnCancelarConfirmacao')?.addEventListener('click', () => {
    document.getElementById('modalConfirmacao').classList.remove('ativo');
});

document.getElementById('modalDisciplina')?.addEventListener('click', (e) => {
    if (e.target === document.getElementById('modalDisciplina')) fecharModalDisciplina();
});

document.getElementById('modalConfirmacao')?.addEventListener('click', (e) => {
    if (e.target === document.getElementById('modalConfirmacao')) {
        document.getElementById('modalConfirmacao').classList.remove('ativo');
    }
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        fecharModalDisciplina();
        document.getElementById('modalConfirmacao').classList.remove('ativo');
    }
});

// Inicializar
carregarDisciplinas();
</script>

<?php include 'includes/footer.php'; ?>
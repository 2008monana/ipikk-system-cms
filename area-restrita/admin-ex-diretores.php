<?php
/**
 * Ex-Directores - Área Restrita IPIKK
 * Gestão completa dos ex-directores do instituto
 */

$titulo_pagina = 'Ex-Directores';
$css_especifico = 'admin-ex-directores.css';

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
// BUSCAR EX-DIRECTORES DO BANCO DE DADOS
// ============================================
$ex_diretores = $db->query("
    SELECT * FROM ex_diretores 
    WHERE ativo = 1 
    ORDER BY ordem, periodo_inicio DESC
")->fetchAll();

// Estatísticas
$total_ex_diretores = count($ex_diretores);

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
                <i class="fas fa-history"></i> Ex-Directores
            </h1>
        </div>
        <div class="direita-barra">
            <button class="btn-primario" id="btnNovoExDirector" onclick="abrirModalExDirector()">
                <i class="fas fa-plus"></i>
                <span>Novo Ex-Director</span>
            </button>
        </div>
    </header>

    <div class="conteudo-pagina">

        <!-- CARDS ESTATÍSTICAS -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon purple"><i class="fas fa-history"></i></div>
                <div class="stat-info">
                    <h3><?= $total_ex_diretores ?></h3>
                    <p>Total de Ex-Directores</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-calendar"></i></div>
                <div class="stat-info">
                    <h3><?= date('Y') ?></h3>
                    <p>Ano Corrente</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-trophy"></i></div>
                <div class="stat-info">
                    <h3><?= count(array_filter($ex_diretores, fn($d) => $d['periodo_fim'] ?? null)) ?></h3>
                    <p>Gestões Completas</p>
                </div>
            </div>
        </div>

        <!-- LISTA DE EX-DIRECTORES -->
        <div class="secao-conteudo">
            <div class="secao-header">
                <h2 class="secao-titulo">
                    <i class="fas fa-users"></i> Galeria de Ex-Directores
                    <span class="contador">(<?= $total_ex_diretores ?> registos)</span>
                </h2>
                <button class="btn-adicionar" onclick="abrirModalExDirector()">
                    <i class="fas fa-plus"></i> Adicionar
                </button>
            </div>

            <div class="grid-ex-directores" id="gridExDirectores">
                <?php if (empty($ex_diretores)): ?>
                <div class="empty-state">
                    <i class="fas fa-history"></i>
                    <h3>Nenhum ex-director cadastrado</h3>
                    <p>Clique em "Novo Ex-Director" para começar a adicionar.</p>
                    <button class="btn-primario" onclick="abrirModalExDirector()">
                        <i class="fas fa-plus"></i> Adicionar Primeiro
                    </button>
                </div>
                <?php else: ?>
                    <?php foreach ($ex_diretores as $ex): ?>
                    <div class="card-ex-director" data-id="<?= $ex['id'] ?>">
                        <div class="card-acoes">
                            <button class="btn-editar" onclick="editarExDirector(<?= $ex['id'] ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn-eliminar" onclick="eliminarExDirector(<?= $ex['id'] ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                        <div class="card-foto">
                            <img src="<?= htmlspecialchars(normalizarUrlMidia($ex['foto_url'] ?? 'foto/sem_foto.png', '..')) ?>" 
                                 alt="<?= htmlspecialchars($ex['nome']) ?>"
                                 onerror="this.src='foto/sem_foto.png'">
                        </div>
                        <h3><?= htmlspecialchars($ex['nome']) ?></h3>
                        <p class="cargo"><?= htmlspecialchars($ex['cargo'] ?? 'Director Geral') ?></p>
                        <span class="periodo">
                            <i class="fas fa-calendar-alt"></i>
                            <?= htmlspecialchars($ex['periodo_inicio'] ?? '?') ?> - <?= htmlspecialchars($ex['periodo_fim'] ?? 'Presente') ?>
                        </span>
                        <?php if (!empty($ex['biografia'])): ?>
                        <p class="biografia-resumo"><?= htmlspecialchars(mb_substr($ex['biografia'], 0, 100)) ?>...</p>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<!-- MODAL PARA EX-DIRECTOR -->
<div id="modalExDirector" class="modal">
    <div class="modal-conteudo">
        <div class="modal-cabecalho">
            <h2 id="modalTitulo"><i class="fas fa-plus"></i> Novo Ex-Director</h2>
            <button class="modal-fechar" onclick="fecharModalExDirector()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-corpo">
            <form id="formExDirector" onsubmit="return salvarExDirector(event)">
                <input type="hidden" id="exDirectorId">

                <div class="grupo-form">
                    <label><i class="fas fa-user"></i> Nome *</label>
                    <input type="text" id="campoNome" class="campo-form" required>
                </div>

                <div class="grupo-form">
                    <label><i class="fas fa-briefcase"></i> Cargo</label>
                    <input type="text" id="campoCargo" class="campo-form" value="Director Geral">
                </div>

                <div class="linha-form">
                    <div class="grupo-form">
                        <label><i class="fas fa-calendar-alt"></i> Início do Mandato *</label>
                        <input type="text" id="campoPeriodoInicio" class="campo-form" placeholder="Ex: 2009" required>
                    </div>
                    <div class="grupo-form">
                        <label><i class="fas fa-calendar-alt"></i> Fim do Mandato</label>
                        <input type="text" id="campoPeriodoFim" class="campo-form" placeholder="Ex: 2012 (deixar em branco se atual)">
                    </div>
                </div>

                <div class="grupo-form">
                    <label><i class="fas fa-align-left"></i> Biografia / Contribuições</label>
                    <textarea id="campoBiografia" class="campo-form area-texto" rows="4" placeholder="Principais contribuições e realizações durante o mandato..."></textarea>
                </div>

                <div class="grupo-form">
                    <label><i class="fas fa-image"></i> Foto</label>
                    <div class="area-upload" onclick="document.getElementById('fotoInput').click()">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>Clique para fazer upload da foto</p>
                        <small>Formatos: JPG, PNG | 200x200px</small>
                    </div>
                    <input type="file" id="fotoInput" accept="image/*" style="display: none;" onchange="previewFoto(this)">
                    <div class="preview-foto" id="previewFoto" style="display: none;">
                        <img id="previewImg" src="" alt="Preview">
                        <button type="button" class="btn-remover" onclick="removerFoto()">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>

                <div class="grupo-form">
                    <label><i class="fas fa-sort-numeric-down"></i> Ordem de Exibição</label>
                    <input type="number" id="campoOrdem" class="campo-form" value="0" min="0">
                </div>

                <div class="modal-acoes">
                    <button type="button" class="btn-cancelar" onclick="fecharModalExDirector()">
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
/* ===== ESTILOS ADMIN EX-DIRECTORES ===== */
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
    gap: 18px;
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

/* Botão Adicionar (dentro da secção) */
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

/* Botão Principal (Novo Ex-Director) */
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

/* Grid de ex-directores */
.grid-ex-directores {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 28px;
}

/* Card de ex-director */
.card-ex-director {
    background: white;
    border-radius: 24px;
    padding: 24px 20px;
    text-align: center;
    border: 1px solid #eef2f8;
    transition: all 0.3s ease;
    position: relative;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
}

.card-ex-director:hover {
    transform: translateY(-6px);
    box-shadow: 0 16px 32px rgba(0, 0, 0, 0.1);
    border-color: #cbd5e1;
}

/* Ações do card */
.card-ex-director .card-acoes {
    position: absolute;
    top: 16px;
    right: 16px;
    display: flex;
    gap: 8px;
    opacity: 0;
    transition: all 0.2s ease;
}

.card-ex-director:hover .card-acoes {
    opacity: 1;
}

/* Foto do card */
.card-ex-director .card-foto {
    width: 130px;
    height: 130px;
    margin: 0 auto 18px;
    border-radius: 50%;
    overflow: hidden;
    border: 3px solid #0a9396;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.card-ex-director:hover .card-foto {
    transform: scale(1.02);
    border-color: #008bb5;
}

.card-ex-director .card-foto img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* Nome e cargo */
.card-ex-director h3 {
    font-size: 1rem;
    font-weight: 700;
    margin-bottom: 6px;
    color: #1e293b;
    line-height: 1.3;
}

.card-ex-director .cargo {
    font-size: 0.7rem;
    font-weight: 600;
    color: #0a9396;
    margin-bottom: 10px;
    letter-spacing: 0.5px;
    text-transform: uppercase;
}

/* Período */
.card-ex-director .periodo {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.7rem;
    color: #64748b;
    background: #f1f5f9;
    padding: 5px 14px;
    border-radius: 30px;
    margin-bottom: 12px;
}

.card-ex-director .periodo i {
    font-size: 0.65rem;
}

/* Biografia resumo */
.card-ex-director .biografia-resumo {
    font-size: 0.75rem;
    color: #475569;
    line-height: 1.5;
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px solid #eef2f8;
}

/* Botões de edição/eliminação */
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
    max-width: 620px;
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
.grupo-form {
    margin-bottom: 22px;
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

.linha-form {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 18px;
    margin-bottom: 0;
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

.area-texto {
    resize: vertical;
    min-height: 100px;
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
    width: 60px;
    height: 60px;
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

/* Modal Ações */
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
    .stats-grid {
        gap: 16px;
    }
    
    .grid-ex-directores {
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
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
    
    .grid-ex-directores {
        grid-template-columns: 1fr;
    }
    
    .card-ex-director .card-acoes {
        opacity: 1;
    }
    
    .linha-form {
        grid-template-columns: 1fr;
        gap: 0;
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
    
    .card-ex-director {
        padding: 18px;
    }
    
    .card-ex-director .card-foto {
        width: 100px;
        height: 100px;
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
</style>

<script>
let fotoAtual = null;

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
    
    const reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById('previewImg').src = e.target.result;
        document.getElementById('previewFoto').style.display = 'flex';
    };
    reader.readAsDataURL(file);
}

function removerFoto() {
    document.getElementById('fotoInput').value = '';
    document.getElementById('previewFoto').style.display = 'none';
    fotoAtual = null;
}

async function salvarExDirector(event) {
    event.preventDefault();
    
    const id = document.getElementById('exDirectorId').value;
    const nome = document.getElementById('campoNome').value;
    const cargo = document.getElementById('campoCargo').value;
    const periodoInicio = document.getElementById('campoPeriodoInicio').value;
    const periodoFim = document.getElementById('campoPeriodoFim').value;
    const biografia = document.getElementById('campoBiografia').value;
    const ordem = document.getElementById('campoOrdem').value;
    const fotoInput = document.getElementById('fotoInput').files[0];
    
    if (!nome || !periodoInicio) {
        mostrarNotificacao('Preencha o nome e o período de início', 'erro');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', id ? 'editar' : 'salvar');
    if (id) formData.append('id', id);
    formData.append('nome', nome);
    formData.append('cargo', cargo);
    formData.append('periodo_inicio', periodoInicio);
    formData.append('periodo_fim', periodoFim);
    formData.append('biografia', biografia);
    formData.append('ordem', ordem);
    if (fotoInput) formData.append('foto', fotoInput);
    
    try {
        const response = await fetch('processos/processar-ex-directores.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            mostrarNotificacao(data.message, 'sucesso');
            fecharModalExDirector();
            setTimeout(() => location.reload(), 1000);
        } else {
            mostrarNotificacao(data.message, 'erro');
        }
    } catch (error) {
        mostrarNotificacao('Erro ao salvar', 'erro');
    }
}

function abrirModalExDirector(id = null) {
    const modal = document.getElementById('modalExDirector');
    const form = document.getElementById('formExDirector');
    const titulo = document.getElementById('modalTitulo');
    
    form.reset();
    document.getElementById('exDirectorId').value = '';
    document.getElementById('previewFoto').style.display = 'none';
    document.getElementById('campoOrdem').value = '0';
    document.getElementById('campoCargo').value = 'Director Geral';
    
    if (id) {
        titulo.innerHTML = '<i class="fas fa-edit"></i> Editar Ex-Director';
        editarExDirector(id);
    } else {
        titulo.innerHTML = '<i class="fas fa-plus"></i> Novo Ex-Director';
        modal.classList.add('ativo');
    }
}

function fecharModalExDirector() {
    document.getElementById('modalExDirector').classList.remove('ativo');
}

function editarExDirector(id) {
    fetch(`processos/processar-ex-directores.php?action=buscar&id=${id}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const ex = data.ex_director;
                document.getElementById('exDirectorId').value = ex.id;
                document.getElementById('campoNome').value = ex.nome;
                document.getElementById('campoCargo').value = ex.cargo || 'Director Geral';
                document.getElementById('campoPeriodoInicio').value = ex.periodo_inicio || '';
                document.getElementById('campoPeriodoFim').value = ex.periodo_fim || '';
                document.getElementById('campoBiografia').value = ex.biografia || '';
                document.getElementById('campoOrdem').value = ex.ordem || 0;
                
                if (ex.foto_url && ex.foto_url !== 'foto/sem_foto.png') {
                    document.getElementById('previewImg').src = '../area-publica/' + ex.foto_url;
                    document.getElementById('previewFoto').style.display = 'flex';
                }
                
                document.getElementById('modalExDirector').classList.add('ativo');
            }
        });
}

function eliminarExDirector(id) {
    const modal = document.getElementById('modalConfirmacao');
    document.getElementById('confirmacaoTitulo').textContent = 'Eliminar Ex-Director';
    document.getElementById('confirmacaoTexto').textContent = 'Tem certeza que deseja eliminar este ex-director permanentemente?';
    
    const confirmar = document.getElementById('btnConfirmarAcao');
    const novoConfirmar = confirmar.cloneNode(true);
    confirmar.parentNode.replaceChild(novoConfirmar, confirmar);
    
    novoConfirmar.onclick = async () => {
        try {
            const response = await fetch('processos/processar-ex-directores.php', {
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

document.getElementById('btnNovoExDirector')?.addEventListener('click', () => abrirModalExDirector());
document.getElementById('btnCancelarConfirmacao')?.addEventListener('click', () => {
    document.getElementById('modalConfirmacao').classList.remove('ativo');
});

document.getElementById('modalExDirector')?.addEventListener('click', (e) => {
    if (e.target === document.getElementById('modalExDirector')) fecharModalExDirector();
});

document.getElementById('modalConfirmacao')?.addEventListener('click', (e) => {
    if (e.target === document.getElementById('modalConfirmacao')) {
        document.getElementById('modalConfirmacao').classList.remove('ativo');
    }
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        fecharModalExDirector();
        document.getElementById('modalConfirmacao').classList.remove('ativo');
    }
});
</script>

<?php include 'includes/footer.php'; ?>
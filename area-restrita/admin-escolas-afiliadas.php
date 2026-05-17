<?php
/**
 * Escolas Afiliadas - Área Restrita IPIKK
 * Gestão completa das escolas parceiras
 */

$titulo_pagina = 'Escolas Afiliadas';
$css_especifico = 'admin-escolas-afiliadas.css';

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
// BUSCAR ESCOLAS AFILIADAS
// ============================================
$escolas = $db->query("
    SELECT * FROM escolas_afiliadas 
    WHERE ativo = 1 
    ORDER BY ordem
")->fetchAll();

// Buscar conteúdo da página para textos
$pagina = getPagina('escolas-afiliadas');
$titulo = $pagina['titulo'] ?? 'Escolas Afiliadas';
$subtitulo = $pagina['subtitulo'] ?? 'Lista das instituições de ensino parceiras e seus respectivos contactos.';

// Estatísticas
$total_escolas = count($escolas);

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
                <i class="fas fa-school"></i> Escolas Afiliadas
            </h1>
        </div>
    </header>

    <div class="conteudo-pagina">

        <!-- CARDS ESTATÍSTICAS -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon purple"><i class="fas fa-school"></i></div>
                <div class="stat-info">
                    <h3><?= $total_escolas ?></h3>
                    <p>Total de Escolas</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon gold"><i class="fas fa-building"></i></div>
                <div class="stat-info">
                    <h3><?= count(array_filter($escolas, fn($e) => ($e['tipo'] ?? 'Privado') == 'Privado')) ?></h3>
                    <p>Escolas Privadas</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-landmark"></i></div>
                <div class="stat-info">
                    <h3><?= count(array_filter($escolas, fn($e) => ($e['tipo'] ?? '') == 'Publico')) ?></h3>
                    <p>Escolas Públicas</p>
                </div>
            </div>
        </div>

        <!-- INFORMAÇÕES GERAIS -->
        <div class="secao-conteudo">
            <h2 class="secao-titulo">
                <i class="fas fa-edit"></i> Informações da Página
            </h2>
            <div class="linha-form">
                <div class="grupo-form">
                    <label><i class="fas fa-heading"></i> Título da Página</label>
                    <input type="text" id="paginaTitulo" class="campo-form" value="<?= htmlspecialchars($titulo) ?>">
                </div>
                <div class="grupo-form">
                    <label><i class="fas fa-align-left"></i> Subtítulo</label>
                    <input type="text" id="paginaSubtitulo" class="campo-form" value="<?= htmlspecialchars($subtitulo) ?>">
                </div>
            </div>
        </div>

        <!-- LISTA DE ESCOLAS -->
        <div class="secao-conteudo">
            <div class="secao-header">
                <h2 class="secao-titulo">
                    <i class="fas fa-list"></i> Escolas Afiliadas
                    <span class="contador">(<?= $total_escolas ?> escolas)</span>
                </h2>
                <button type="button" class="btn-adicionar" onclick="adicionarEscola()">
                    <i class="fas fa-plus"></i> Adicionar Escola
                </button>
            </div>

            <div id="listaEscolas" class="lista-escolas">
                <?php if (empty($escolas)): ?>
                <div class="empty-state" id="emptyEscolas">
                    <i class="fas fa-school"></i>
                    <p>Nenhuma escola afiliada cadastrada. Clique em "Adicionar Escola" para começar.</p>
                </div>
                <?php else: ?>
                    <?php foreach ($escolas as $index => $escola): ?>
                    <div class="item-escola" data-id="<?= $escola['id'] ?>">
                        <div class="item-header">
                            <span class="item-numero"><?= $index + 1 ?></span>
                            <div class="item-acoes">
                                <button type="button" class="btn-editar" onclick="editarEscola(this)"><i class="fas fa-edit"></i></button>
                                <button type="button" class="btn-eliminar" onclick="eliminarEscola(this)"><i class="fas fa-trash"></i></button>
                            </div>
                        </div>
                        <div class="item-preview">
                            <div class="preview-logo">
                                <img src="<?= htmlspecialchars(normalizarUrlMidia($escola['logo_url'] ?? 'foto/sem_logo.png', '..')) ?>" class="escola-logo-preview" onerror="this.src='../area-publica/foto/sem_logo.png'">
                            </div>
                            <div class="preview-info">
                                <strong><?= htmlspecialchars($escola['nome']) ?></strong>
                                <span class="preview-tipo <?= ($escola['tipo'] ?? 'Privado') == 'Privado' ? 'tipo-privado' : 'tipo-publico' ?>">
                                    <?= htmlspecialchars($escola['tipo'] ?? 'Privado') ?>
                                </span>
                                <span class="preview-endereco"><?= htmlspecialchars(mb_substr($escola['endereco'] ?? '', 0, 60)) ?></span>
                            </div>
                        </div>
                        <div class="item-form" style="display: none;">
                            <div class="linha-form">
                                <div class="grupo-form">
                                    <label>Nome da Escola *</label>
                                    <input type="text" class="campo-form nome-input" value="<?= htmlspecialchars($escola['nome']) ?>">
                                </div>
                                <div class="grupo-form">
                                    <label>Tipo</label>
                                    <select class="campo-form tipo-select">
                                        <option value="Privado" <?= ($escola['tipo'] ?? 'Privado') == 'Privado' ? 'selected' : '' ?>>Privado</option>
                                        <option value="Publico" <?= ($escola['tipo'] ?? '') == 'Publico' ? 'selected' : '' ?>>Público</option>
                                    </select>
                                </div>
                            </div>
                            <div class="linha-form">
                                <div class="grupo-form">
                                    <label>Email</label>
                                    <input type="email" class="campo-form email-input" value="<?= htmlspecialchars($escola['email'] ?? '') ?>">
                                </div>
                                <div class="grupo-form">
                                    <label>Telefone 1</label>
                                    <input type="text" class="campo-form telefone1-input" value="<?= htmlspecialchars($escola['telefone1'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="linha-form">
                                <div class="grupo-form">
                                    <label>Telefone 2</label>
                                    <input type="text" class="campo-form telefone2-input" value="<?= htmlspecialchars($escola['telefone2'] ?? '') ?>">
                                </div>
                                <div class="grupo-form">
                                    <label>Website</label>
                                    <input type="url" class="campo-form site-input" value="<?= htmlspecialchars($escola['site_url'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="grupo-form">
                                <label>Endereço</label>
                                <textarea class="campo-form endereco-input" rows="2"><?= htmlspecialchars($escola['endereco'] ?? '') ?></textarea>
                            </div>
                            <div class="grupo-form">
                                <label>Logotipo</label>
                                <div class="area-upload-pequena" onclick="document.getElementById('fotoEscola_<?= $index ?>').click()">
                                    <i class="fas fa-cloud-upload-alt"></i> Alterar Logotipo
                                </div>
                                <input type="file" id="fotoEscola_<?= $index ?>" accept="image/*" style="display: none;" data-index="<?= $index ?>" onchange="previewEscolaLogo(this)">
                                <div class="preview-logo-pequena" id="previewEscola_<?= $index ?>">
                                    <img src="<?= htmlspecialchars(normalizarUrlMidia($escola['logo_url'] ?? 'foto/sem_logo.png', '..')) ?>" class="escola-logo-preview-pequena">
                                </div>
                            </div>
                            <div class="item-actions">
                                <button type="button" class="btn-salvar-item" onclick="salvarEscola(this)">Salvar</button>
                                <button type="button" class="btn-cancelar-item" onclick="cancelarEdicao(this)">Cancelar</button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- AÇÕES -->
        <div class="secao-acoes">
            <button class="btn-primario btn-grande" onclick="salvarTodas()">
                <i class="fas fa-save"></i> Guardar Todas as Alterações
            </button>
            <button class="btn-secundario" onclick="previewPagina()">
                <i class="fas fa-eye"></i> Pré-visualizar
            </button>
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
/* ===== ESTILOS ADMIN ESCOLAS AFILIADAS ===== */
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

/* Secções principais */
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

/* Botão Principal (Guardar Alterações) */
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
    transform: rotate(-10deg);
}

/* Grid de formulário */
.linha-form {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
    margin-bottom: 20px;
}

/* Grupos de formulário */
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

/* Campos de input */
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

textarea.campo-form {
    resize: vertical;
    line-height: 1.5;
}

/* Lista de escolas */
.lista-escolas {
    display: flex;
    flex-direction: column;
    gap: 18px;
}

/* Item escola */
.item-escola {
    background: linear-gradient(135deg, #fafbfc 0%, #ffffff 100%);
    border-radius: 20px;
    padding: 22px;
    border: 1px solid #eef2f8;
    transition: all 0.3s ease;
}

.item-escola:hover {
    border-color: #cbd5e1;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.05);
}

/* Header do item */
.item-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 1px solid #eef2f8;
}

.item-numero {
    font-weight: 700;
    font-size: 0.75rem;
    color: #0a9396;
    background: rgba(10, 147, 150, 0.1);
    padding: 4px 14px;
    border-radius: 30px;
}

/* Ações do item */
.item-acoes {
    display: flex;
    gap: 8px;
}

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

/* Preview do item */
.item-preview {
    display: flex;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
}

.preview-logo {
    width: 75px;
    height: 75px;
    border-radius: 50%;
    overflow: hidden;
    background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
    border: 2px solid #0a9396;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.escola-logo-preview {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.preview-info {
    flex: 1;
}

.preview-info strong {
    display: block;
    font-size: 1rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 6px;
}

/* Badges de tipo */
.preview-tipo {
    display: inline-block;
    font-size: 0.65rem;
    font-weight: 700;
    padding: 3px 12px;
    border-radius: 30px;
    margin-bottom: 6px;
    letter-spacing: 0.5px;
}

.tipo-privado {
    background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%);
    color: #0369a1;
}

.tipo-publico {
    background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
    color: #15803d;
}

.preview-endereco {
    font-size: 0.7rem;
    color: #64748b;
    display: block;
    margin-top: 4px;
    line-height: 1.4;
}

/* Formulário do item */
.item-form {
    margin-top: 18px;
    padding-top: 18px;
    border-top: 1px solid #eef2f8;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Upload de imagem pequeno */
.area-upload-pequena {
    border: 1px dashed #cbd5e1;
    border-radius: 12px;
    padding: 10px 16px;
    text-align: center;
    cursor: pointer;
    font-size: 0.75rem;
    transition: all 0.2s ease;
    background: #fafbfc;
    color: #475569;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.area-upload-pequena:hover {
    border-color: #0a9396;
    background: #f0fdfa;
    color: #0a9396;
}

.area-upload-pequena i {
    font-size: 0.85rem;
}

/* Preview de logo pequena */
.preview-logo-pequena {
    margin-top: 10px;
}

.escola-logo-preview-pequena {
    width: 65px;
    height: 65px;
    border-radius: 12px;
    object-fit: cover;
    border: 1px solid #e2e8f0;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

/* Ações do item */
.item-actions {
    display: flex;
    gap: 12px;
    margin-top: 16px;
    justify-content: flex-end;
}

.btn-salvar-item, .btn-cancelar-item {
    padding: 8px 20px;
    border-radius: 40px;
    font-weight: 600;
    font-size: 0.75rem;
    cursor: pointer;
    border: none;
    transition: all 0.2s ease;
}

.btn-salvar-item {
    background: linear-gradient(135deg, #0a9396 0%, #008bb5 100%);
    color: white;
}

.btn-salvar-item:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(10, 147, 150, 0.3);
}

.btn-cancelar-item {
    background: #f1f5f9;
    color: #64748b;
    border: 1px solid #e2e8f0;
}

.btn-cancelar-item:hover {
    background: #e2e8f0;
    transform: translateY(-1px);
}

/* Empty state */
.empty-state {
    text-align: center;
    padding: 48px 24px;
    background: linear-gradient(135deg, #fafbfc 0%, #f8fafc 100%);
    border-radius: 20px;
    border: 1px dashed #cbd5e1;
}

.empty-state i {
    font-size: 48px;
    color: #cbd5e1;
    margin-bottom: 16px;
    display: block;
}

.empty-state p {
    color: #94a3b8;
    font-size: 0.85rem;
    margin-bottom: 0;
}

/* Ações principais */
.secao-acoes {
    display: flex;
    justify-content: flex-end;
    gap: 16px;
    margin-top: 24px;
    padding-top: 8px;
}

.btn-grande {
    padding: 12px 32px;
    font-size: 0.9rem;
}

.btn-secundario {
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    padding: 12px 28px;
    border-radius: 40px;
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
    color: #475569;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 10px;
}

.btn-secundario:hover {
    background: #e2e8f0;
    transform: translateY(-1px);
}

.btn-secundario i {
    transition: transform 0.2s ease;
}

.btn-secundario:hover i {
    transform: translateX(3px);
}

/* Modal de confirmação */
.modal-confirmacao {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.6);
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

/* Responsividade */
@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
        gap: 12px;
    }
    
    .secao-conteudo {
        padding: 20px;
        margin-bottom: 20px;
    }
    
    .secao-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .linha-form {
        grid-template-columns: 1fr;
        gap: 16px;
    }
    
    .item-preview {
        flex-direction: column;
        text-align: center;
    }
    
    .preview-logo {
        margin: 0 auto;
    }
    
    .secao-acoes {
        flex-direction: column;
    }
    
    .btn-grande, .btn-secundario {
        width: 100%;
        justify-content: center;
    }
    
    .item-actions {
        flex-direction: column;
    }
    
    .btn-salvar-item, .btn-cancelar-item {
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
        padding: 10px 16px;
    }
    
    .btn-primario i {
        margin: 0;
    }
    
    .preview-logo {
        width: 60px;
        height: 60px;
    }
    
    .preview-info strong {
        font-size: 0.9rem;
    }
    
    .empty-state {
        padding: 32px 16px;
    }
    
    .empty-state i {
        font-size: 40px;
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
</style>

<script>
let novoItemCounter = 0;
let pendingLogoFiles = {};

function mostrarNotificacao(mensagem, tipo = 'sucesso') {
    const notif = document.createElement('div');
    notif.className = `notificacao ${tipo}`;
    notif.innerHTML = `<i class="fas fa-${tipo === 'sucesso' ? 'check-circle' : 'exclamation-circle'}"></i> ${mensagem}`;
    document.body.appendChild(notif);
    setTimeout(() => notif.remove(), 3000);
}

function previewEscolaLogo(input) {
    const file = input.files[0];
    if (!file) return;
    
    if (file.size > 5 * 1024 * 1024) {
        mostrarNotificacao('Arquivo muito grande. Máximo 5MB', 'erro');
        input.value = '';
        return;
    }
    
    const index = input.dataset.index;
    const reader = new FileReader();
    
    reader.onload = function(e) {
        const previewDiv = document.getElementById(`previewEscola_${index}`);
        if (previewDiv) {
            previewDiv.innerHTML = `<img src="${e.target.result}" class="escola-logo-preview-pequena">`;
        }
        pendingLogoFiles[index] = file;
    };
    reader.readAsDataURL(file);
}

function adicionarEscola() {
    const container = document.getElementById('listaEscolas');
    const emptyState = document.getElementById('emptyEscolas');
    if (emptyState) emptyState.style.display = 'none';
    
    const novoId = `novo_${Date.now()}_${novoItemCounter++}`;
    const div = document.createElement('div');
    div.className = 'item-escola';
    div.setAttribute('data-novo', 'true');
    div.setAttribute('data-temp-id', novoId);
    
    div.innerHTML = `
        <div class="item-header">
            <span class="item-numero">Novo</span>
            <div class="item-acoes">
                <button type="button" class="btn-eliminar" onclick="removerNovoItem(this)"><i class="fas fa-trash"></i></button>
            </div>
        </div>
        <div class="item-form" style="display: block;">
            <div class="linha-form">
                <div class="grupo-form">
                    <label>Nome da Escola *</label>
                    <input type="text" class="campo-form nome-input" placeholder="Digite o nome">
                </div>
                <div class="grupo-form">
                    <label>Tipo</label>
                    <select class="campo-form tipo-select">
                        <option value="Privado">Privado</option>
                        <option value="Publico">Público</option>
                    </select>
                </div>
            </div>
            <div class="linha-form">
                <div class="grupo-form">
                    <label>Email</label>
                    <input type="email" class="campo-form email-input" placeholder="email@exemplo.com">
                </div>
                <div class="grupo-form">
                    <label>Telefone 1</label>
                    <input type="text" class="campo-form telefone1-input" placeholder="(244) 999 999 999">
                </div>
            </div>
            <div class="linha-form">
                <div class="grupo-form">
                    <label>Telefone 2</label>
                    <input type="text" class="campo-form telefone2-input" placeholder="(244) 999 999 999">
                </div>
                <div class="grupo-form">
                    <label>Website</label>
                    <input type="url" class="campo-form site-input" placeholder="https://www.exemplo.ao">
                </div>
            </div>
            <div class="grupo-form">
                <label>Endereço</label>
                <textarea class="campo-form endereco-input" rows="2" placeholder="Endereço completo da escola"></textarea>
            </div>
            <div class="grupo-form">
                <label>Logotipo</label>
                <div class="area-upload-pequena" onclick="document.getElementById('fotoNovo_${novoId}').click()">
                    <i class="fas fa-cloud-upload-alt"></i> Selecionar Logotipo
                </div>
                <input type="file" id="fotoNovo_${novoId}" accept="image/*" style="display: none;" data-temp-id="${novoId}" onchange="previewNovoEscolaLogo(this)">
                <div class="preview-logo-pequena" id="previewNovo_${novoId}"></div>
            </div>
            <div class="item-actions">
                <button type="button" class="btn-salvar-item" onclick="salvarNovaEscola(this)">Adicionar</button>
                <button type="button" class="btn-cancelar-item" onclick="removerNovoItem(this)">Cancelar</button>
            </div>
        </div>
    `;
    
    container.appendChild(div);
}

function previewNovoEscolaLogo(input) {
    const file = input.files[0];
    if (!file) return;
    
    if (file.size > 5 * 1024 * 1024) {
        mostrarNotificacao('Arquivo muito grande. Máximo 5MB', 'erro');
        input.value = '';
        return;
    }
    
    const tempId = input.dataset.tempId;
    const reader = new FileReader();
    
    reader.onload = function(e) {
        const previewDiv = document.getElementById(`previewNovo_${tempId}`);
        if (previewDiv) {
            previewDiv.innerHTML = `<img src="${e.target.result}" class="escola-logo-preview-pequena">`;
        }
        pendingLogoFiles[tempId] = file;
    };
    reader.readAsDataURL(file);
}

function removerNovoItem(btn) {
    const item = btn.closest('.item-escola');
    if (item) {
        const tempId = item.dataset.tempId;
        if (tempId) delete pendingLogoFiles[tempId];
        item.remove();
        const container = item.parentElement;
        if (container.children.length === 0) {
            const emptyState = document.getElementById('emptyEscolas');
            if (emptyState) emptyState.style.display = 'block';
        }
    }
}

function editarEscola(btn) {
    const item = btn.closest('.item-escola');
    const previewDiv = item.querySelector('.item-preview');
    const formDiv = item.querySelector('.item-form');
    
    if (previewDiv) previewDiv.style.display = 'none';
    if (formDiv) formDiv.style.display = 'block';
}

function cancelarEdicao(btn) {
    const item = btn.closest('.item-escola');
    const previewDiv = item.querySelector('.item-preview');
    const formDiv = item.querySelector('.item-form');
    
    if (previewDiv) previewDiv.style.display = 'flex';
    if (formDiv) formDiv.style.display = 'none';
}

function salvarEscola(btn) {
    const item = btn.closest('.item-escola');
    const id = item.dataset.id;
    const nomeInput = item.querySelector('.nome-input');
    const tipoSelect = item.querySelector('.tipo-select');
    const emailInput = item.querySelector('.email-input');
    const telefone1Input = item.querySelector('.telefone1-input');
    const telefone2Input = item.querySelector('.telefone2-input');
    const siteInput = item.querySelector('.site-input');
    const enderecoInput = item.querySelector('.endereco-input');
    const fotoInput = item.querySelector('input[type="file"]');
    
    const nome = nomeInput?.value.trim();
    const tipo = tipoSelect?.value;
    const email = emailInput?.value;
    const telefone1 = telefone1Input?.value;
    const telefone2 = telefone2Input?.value;
    const site_url = siteInput?.value;
    const endereco = enderecoInput?.value;
    const fotoFile = fotoInput?.files[0];
    
    if (!nome) {
        mostrarNotificacao('Nome da escola é obrigatório', 'erro');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'salvar');
    formData.append('id', id);
    formData.append('nome', nome);
    formData.append('tipo', tipo);
    formData.append('email', email);
    formData.append('telefone1', telefone1);
    formData.append('telefone2', telefone2);
    formData.append('site_url', site_url);
    formData.append('endereco', endereco);
    if (fotoFile) formData.append('logo', fotoFile);
    
    fetch('processos/processar-escolas-afiliadas.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarNotificacao(data.message, 'sucesso');
            setTimeout(() => location.reload(), 1000);
        } else {
            mostrarNotificacao(data.message, 'erro');
        }
    })
    .catch(error => {
        mostrarNotificacao('Erro ao salvar', 'erro');
    });
}

function salvarNovaEscola(btn) {
    const item = btn.closest('.item-escola');
    const tempId = item.dataset.tempId;
    const nomeInput = item.querySelector('.nome-input');
    const tipoSelect = item.querySelector('.tipo-select');
    const emailInput = item.querySelector('.email-input');
    const telefone1Input = item.querySelector('.telefone1-input');
    const telefone2Input = item.querySelector('.telefone2-input');
    const siteInput = item.querySelector('.site-input');
    const enderecoInput = item.querySelector('.endereco-input');
    
    const nome = nomeInput?.value.trim();
    const tipo = tipoSelect?.value;
    const email = emailInput?.value;
    const telefone1 = telefone1Input?.value;
    const telefone2 = telefone2Input?.value;
    const site_url = siteInput?.value;
    const endereco = enderecoInput?.value;
    const logoFile = pendingLogoFiles[tempId];
    
    if (!nome) {
        mostrarNotificacao('Nome da escola é obrigatório', 'erro');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'salvar');
    formData.append('nome', nome);
    formData.append('tipo', tipo);
    formData.append('email', email);
    formData.append('telefone1', telefone1);
    formData.append('telefone2', telefone2);
    formData.append('site_url', site_url);
    formData.append('endereco', endereco);
    if (logoFile) formData.append('logo', logoFile);
    
    fetch('processos/processar-escolas-afiliadas.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarNotificacao(data.message, 'sucesso');
            setTimeout(() => location.reload(), 1000);
        } else {
            mostrarNotificacao(data.message, 'erro');
        }
    })
    .catch(error => {
        mostrarNotificacao('Erro ao adicionar', 'erro');
    });
}

function eliminarEscola(btn) {
    const modal = document.getElementById('modalConfirmacao');
    const item = btn.closest('.item-escola');
    const id = item.dataset.id;
    const nome = item.querySelector('.preview-info strong')?.textContent || 'esta escola';
    
    document.getElementById('confirmacaoTitulo').textContent = 'Eliminar Escola';
    document.getElementById('confirmacaoTexto').textContent = `Tem certeza que deseja eliminar "${nome}" permanentemente?`;
    
    const confirmar = document.getElementById('btnConfirmarAcao');
    const novoConfirmar = confirmar.cloneNode(true);
    confirmar.parentNode.replaceChild(novoConfirmar, confirmar);
    
    novoConfirmar.onclick = async () => {
        try {
            const response = await fetch('processos/processar-escolas-afiliadas.php', {
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

function salvarTodas() {
    const titulo = document.getElementById('paginaTitulo').value;
    const subtitulo = document.getElementById('paginaSubtitulo').value;
    
    const formData = new FormData();
    formData.append('action', 'salvar_geral');
    formData.append('titulo', titulo);
    formData.append('subtitulo', subtitulo);
    
    fetch('processos/processar-escolas-afiliadas.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarNotificacao(data.message, 'sucesso');
        } else {
            mostrarNotificacao(data.message, 'erro');
        }
    })
    .catch(error => {
        mostrarNotificacao('Erro ao guardar configurações', 'erro');
    });
}

function previewPagina() {
    window.open('../area-publica/escolas-afiliadas.php?preview=1', '_blank');
}

document.getElementById('btnCancelarConfirmacao')?.addEventListener('click', () => {
    document.getElementById('modalConfirmacao').classList.remove('ativo');
});

document.getElementById('modalConfirmacao')?.addEventListener('click', (e) => {
    if (e.target === document.getElementById('modalConfirmacao')) {
        document.getElementById('modalConfirmacao').classList.remove('ativo');
    }
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        document.getElementById('modalConfirmacao').classList.remove('ativo');
    }
});
</script>

<?php include 'includes/footer.php'; ?>
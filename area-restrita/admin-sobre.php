<?php
/**
 * Quem Somos - Área Restrita IPIKK
 * Gestão completa do conteúdo da página Quem Somos
 */

$titulo_pagina = 'Quem Somos';
$css_especifico = 'admin-sobre.css';

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
// BUSCAR DADOS DA PÁGINA SOBRE
// ============================================
$pagina = getPagina('sobre');

// Hero
$hero = isset($pagina['hero']) && is_array($pagina['hero']) ? $pagina['hero'] : [
    'titulo' => 'Quem Somos?',
    'subtitulo' => 'Conheça a história, missão e valores do Instituto Politécnico Industrial do Kilamba Kiaxi nº 8056 "Nova Vida"'
];

// História
$historia = isset($pagina['historia']) && is_array($pagina['historia']) ? $pagina['historia'] : [
    'titulo' => 'Nossa História',
    'conteudo' => '',
    'imagem' => 'foto/img_construct_5.jpg',
    'legenda' => 'IPIKK — Símbolo de excelência no ensino técnico-profissional angolano'
];

// Missão, Visão, Valores
$missao = $pagina['missao'] ?? '';
$visao = $pagina['visao'] ?? '';
$valores = $pagina['valores'] ?? '';

// Lema
$lema = $pagina['lema'] ?? '"Um diferencial para a sua formação"';
$lema_descricao = $pagina['lema_descricao'] ?? 'Mais do que uma frase, nosso compromisso diário com cada estudante';

// Linha do tempo
$linha_tempo = $db->query("SELECT * FROM linha_tempo WHERE ativo = 1 ORDER BY ordem")->fetchAll();

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
                <i class="fas fa-building"></i> Quem Somos
            </h1>
        </div>
    </header>

    <div class="conteudo-pagina">

        <!-- ===== HERO ===== -->
        <div class="secao-conteudo">
            <h2 class="secao-titulo">
                <i class="fas fa-star"></i> Cabeçalho da Página
            </h2>
            <div class="linha-form">
                <div class="grupo-form">
                    <label>Título</label>
                    <input type="text" id="heroTitulo" class="campo-form" value="<?= htmlspecialchars($hero['titulo'] ?? '') ?>">
                </div>
                <div class="grupo-form">
                    <label>Subtítulo</label>
                    <input type="text" id="heroSubtitulo" class="campo-form" value="<?= htmlspecialchars($hero['subtitulo'] ?? '') ?>">
                </div>
            </div>
        </div>

        <!-- ===== HISTÓRIA ===== -->
        <div class="secao-conteudo">
            <h2 class="secao-titulo">
                <i class="fas fa-history"></i> História da Instituição
            </h2>
            <div class="linha-form">
                <div class="grupo-form">
                    <label>Título da História</label>
                    <input type="text" id="historiaTitulo" class="campo-form" value="<?= htmlspecialchars($historia['titulo'] ?? '') ?>">
                </div>
                <div class="grupo-form">
                    <label>Legenda da Imagem</label>
                    <input type="text" id="historiaLegenda" class="campo-form" value="<?= htmlspecialchars($historia['legenda'] ?? '') ?>">
                </div>
            </div>
            <div class="grupo-form">
                <label>Conteúdo da História</label>
                <textarea id="historiaConteudo" class="campo-form area-texto" rows="8"><?= htmlspecialchars($historia['conteudo'] ?? '') ?></textarea>
                <small class="info-texto">Use HTML para formatação (p, strong, em, etc.)</small>
            </div>
            <div class="grupo-form">
                <label>Imagem Ilustrativa</label>
                <div class="area-upload-pequena" onclick="document.getElementById('historiaImagemInput').click()">
                    <i class="fas fa-cloud-upload-alt"></i> Alterar Imagem
                </div>
                <input type="file" id="historiaImagemInput" accept="image/*" style="display: none;" onchange="previewHistoriaImagem(this)">
                <div class="preview-foto-pequena" id="previewHistoriaImagem">
                    <img src="<?= htmlspecialchars(normalizarUrlMidia($historia['imagem'] ?? 'foto/img_construct_5.jpg', '..')) ?>" class="historia-img-preview" onerror="this.src='../area-publica/foto/img_construct_5.jpg'">
                </div>
            </div>
        </div>

        <!-- ===== LINHA DO TEMPO ===== -->
        <div class="secao-conteudo">
            <div class="secao-header">
                <h2 class="secao-titulo">
                    <i class="fas fa-timeline"></i> Linha do Tempo
                    <span class="contador" id="contadorEventos">(<?= count($linha_tempo) ?> eventos)</span>
                </h2>
                <button type="button" class="btn-adicionar" onclick="adicionarEventoTempo()">
                    <i class="fas fa-plus"></i> Adicionar Evento
                </button>
            </div>

            <div id="listaEventos" class="lista-eventos">
                <?php if (empty($linha_tempo)): ?>
                <div class="empty-state" id="emptyEventos">
                    <i class="fas fa-timeline"></i>
                    <p>Nenhum evento cadastrado. Clique em "Adicionar Evento" para começar.</p>
                </div>
                <?php else: ?>
                    <?php foreach ($linha_tempo as $index => $evento): ?>
                    <div class="item-evento" data-id="<?= $evento['id'] ?>">
                        <div class="item-header">
                            <span class="item-numero">Evento <?= $index + 1 ?></span>
                            <div class="item-acoes">
                                <button type="button" class="btn-editar" onclick="editarEvento(this)"><i class="fas fa-edit"></i></button>
                                <button type="button" class="btn-eliminar" onclick="eliminarEvento(this, <?= $evento['id'] ?>)"><i class="fas fa-trash"></i></button>
                            </div>
                        </div>
                        <div class="item-preview">
                            <div class="preview-info">
                                <strong><?= htmlspecialchars($evento['ano']) ?></strong>
                                <span><?= htmlspecialchars($evento['descricao']) ?></span>
                            </div>
                        </div>
                        <div class="item-form" style="display: none;">
                            <div class="linha-form">
                                <div class="grupo-form">
                                    <label>Ano</label>
                                    <input type="text" class="campo-form ano-input" value="<?= htmlspecialchars($evento['ano']) ?>">
                                </div>
                                <div class="grupo-form">
                                    <label>Descrição</label>
                                    <input type="text" class="campo-form descricao-input" value="<?= htmlspecialchars($evento['descricao']) ?>">
                                </div>
                            </div>
                            <div class="item-actions">
                                <button type="button" class="btn-salvar-item" onclick="salvarEvento(this)">Salvar</button>
                                <button type="button" class="btn-cancelar-item" onclick="cancelarEdicaoEvento(this)">Cancelar</button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- ===== MISSÃO, VISÃO, VALORES ===== -->
        <div class="secao-conteudo">
            <h2 class="secao-titulo">
                <i class="fas fa-bullseye"></i> Missão, Visão e Valores
            </h2>
            <div class="grupo-form">
                <label>Missão</label>
                <textarea id="missao" class="campo-form area-texto" rows="3"><?= htmlspecialchars($missao) ?></textarea>
            </div>
            <div class="grupo-form">
                <label>Visão</label>
                <textarea id="visao" class="campo-form area-texto" rows="3"><?= htmlspecialchars($visao) ?></textarea>
            </div>
            <div class="grupo-form">
                <label>Valores</label>
                <textarea id="valores" class="campo-form area-texto" rows="4"><?= htmlspecialchars($valores) ?></textarea>
                <small class="info-texto">Separe os valores por vírgula ou escreva em parágrafos.</small>
            </div>
        </div>

        <!-- ===== LEMA ===== -->
        <div class="secao-conteudo">
            <h2 class="secao-titulo">
                <i class="fas fa-quote-right"></i> Lema
            </h2>
            <div class="linha-form">
                <div class="grupo-form">
                    <label>Frase do Lema</label>
                    <input type="text" id="lema" class="campo-form" value="<?= htmlspecialchars($lema) ?>">
                </div>
                <div class="grupo-form">
                    <label>Descrição do Lema</label>
                    <input type="text" id="lemaDescricao" class="campo-form" value="<?= htmlspecialchars($lema_descricao) ?>">
                </div>
            </div>
        </div>

        <!-- ===== AÇÕES ===== -->
        <div class="secao-acoes">
            <button class="btn-primario btn-grande" onclick="salvarTudo()">
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
/* ===== ESTILOS ADMIN SOBRE ===== */
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
.conteudo-pagina {
    max-width: 1200px;
    margin: 0 auto;
}

/* Secções principais */
.secao-conteudo {
    background: white;
    border-radius: 24px;
    padding: 28px;
    margin-bottom: 28px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
    border: 1px solid #f0f2f5;
}

.secao-conteudo:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    border-color: #e8ecef;
}

/* Cabeçalho da secção */
.secao-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 2px solid #f0f4f8;
    flex-wrap: wrap;
    gap: 12px;
}

/* Títulos das secções */
.secao-titulo {
    font-size: 1.1rem;
    font-weight: 600;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
    color: #1a2c3e;
    letter-spacing: -0.2px;
}

.secao-titulo i {
    color: #0a9396;
    font-size: 1.2rem;
    width: 28px;
    height: 28px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(10,147,150,0.1);
    border-radius: 10px;
}

/* Contador estilizado */
.secao-titulo .contador {
    background: #eef2f6;
    color: #4a5568;
    font-size: 0.7rem;
    font-weight: 500;
    padding: 3px 10px;
    border-radius: 30px;
    margin-left: 8px;
    letter-spacing: normal;
}

/* Grid de formulário */
.linha-form {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
    margin-bottom: 20px;
}

@media (max-width: 768px) {
    .linha-form {
        grid-template-columns: 1fr;
        gap: 16px;
    }
}

/* Grupos de formulário */
.grupo-form {
    margin-bottom: 20px;
}

.grupo-form:last-child {
    margin-bottom: 0;
}

.grupo-form label {
    display: block;
    font-weight: 600;
    margin-bottom: 8px;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #5a6e7c;
}

.grupo-form label i {
    margin-right: 6px;
    color: #0a9396;
    font-size: 0.7rem;
}

/* Campos de input */
.campo-form {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    font-size: 0.9rem;
    transition: all 0.2s ease;
    background: #fafbfc;
    font-family: inherit;
}

.campo-form:focus {
    outline: none;
    border-color: #0a9396;
    background: white;
    box-shadow: 0 0 0 3px rgba(10,147,150,0.1);
}

.campo-form::placeholder {
    color: #a0aec0;
    font-size: 0.85rem;
}

/* Textareas */
.area-texto {
    resize: vertical;
    line-height: 1.6;
    min-height: 100px;
}

/* Botão adicionar */
.btn-adicionar {
    background: #f0fdf4;
    border: 1px solid #d1fae5;
    padding: 8px 18px;
    border-radius: 40px;
    font-size: 0.75rem;
    font-weight: 600;
    cursor: pointer;
    color: #2e7d32;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-adicionar:hover {
    background: #dcfce7;
    border-color: #86efac;
    transform: translateY(-1px);
}

.btn-adicionar i {
    font-size: 0.7rem;
}

/* Upload de imagens */
.area-upload-pequena {
    border: 1px dashed #cbd5e1;
    border-radius: 12px;
    padding: 10px 16px;
    text-align: center;
    cursor: pointer;
    font-size: 0.75rem;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #fafbfc;
    transition: all 0.2s ease;
    color: #4a5568;
}

.area-upload-pequena:hover {
    border-color: #0a9396;
    background: #f0fdfa;
    color: #0a9396;
}

.area-upload-pequena i {
    font-size: 0.85rem;
}

/* Previews de imagens */
.preview-foto-pequena {
    margin-top: 12px;
}

.historia-img-preview {
    width: 120px;
    height: 90px;
    border-radius: 12px;
    object-fit: cover;
    border: 1px solid #e2e8f0;
    box-shadow: 0 2px 6px rgba(0,0,0,0.05);
}

/* Lista de eventos */
.lista-eventos {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

/* Item de evento */
.item-evento {
    background: #fafbfc;
    border-radius: 16px;
    padding: 20px;
    border: 1px solid #edf2f7;
    transition: all 0.2s ease;
}

.item-evento:hover {
    border-color: #cbd5e1;
    background: white;
}

/* Cabeçalho do item */
.item-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 14px;
    padding-bottom: 12px;
    border-bottom: 1px solid #edf2f7;
}

.item-numero {
    font-weight: 600;
    color: #0a9396;
    font-size: 0.75rem;
    background: rgba(10,147,150,0.08);
    padding: 4px 12px;
    border-radius: 30px;
    letter-spacing: 0.3px;
}

/* Ações dos itens */
.item-acoes {
    display: flex;
    gap: 8px;
}

.btn-editar, .btn-eliminar {
    width: 32px;
    height: 32px;
    border-radius: 10px;
    border: none;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
}

.btn-editar {
    background: #e0f2fe;
    color: #0288d1;
}

.btn-editar:hover {
    background: #0288d1;
    color: white;
    transform: scale(1.05);
}

.btn-eliminar {
    background: #fee2e2;
    color: #dc3545;
}

.btn-eliminar:hover {
    background: #dc3545;
    color: white;
    transform: scale(1.05);
}

/* Preview do item */
.item-preview {
    display: flex;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
}

.preview-info strong {
    display: block;
    font-size: 1rem;
    color: #1a2c3e;
    margin-bottom: 6px;
    font-weight: 600;
}

.preview-info span {
    font-size: 0.85rem;
    color: #5a6e7c;
    line-height: 1.5;
}

/* Formulário do item */
.item-form {
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid #edf2f7;
    animation: fadeIn 0.25s ease;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-8px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
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
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.75rem;
    cursor: pointer;
    border: none;
    transition: all 0.2s ease;
}

.btn-salvar-item {
    background: #0a9396;
    color: white;
}

.btn-salvar-item:hover {
    background: #008bb5;
    transform: translateY(-1px);
}

.btn-cancelar-item {
    background: #f1f3f5;
    color: #5a6e7c;
}

.btn-cancelar-item:hover {
    background: #e9ecef;
    transform: translateY(-1px);
}

/* Empty state */
.empty-state {
    text-align: center;
    padding: 48px 24px;
    background: #fafbfc;
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
}

/* Info text */
.info-texto {
    font-size: 0.7rem;
    color: #94a3b8;
    margin-top: 6px;
    display: block;
}

/* Ações principais */
.secao-acoes {
    display: flex;
    justify-content: flex-end;
    gap: 16px;
    margin-top: 16px;
    padding-top: 16px;
}

.btn-grande {
    padding: 12px 28px;
    font-size: 0.85rem;
    font-weight: 600;
}

.btn-primario {
    background: linear-gradient(135deg, #003072 0%, #0a5e6b 100%);
    color: white;
    border: none;
    border-radius: 40px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 10px;
}

.btn-primario:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 14px rgba(0,48,114,0.25);
}

.btn-secundario {
    background: white;
    border: 1px solid #e2e8f0;
    padding: 12px 24px;
    border-radius: 40px;
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
    color: #4a5568;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-secundario:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
    transform: translateY(-1px);
}

/* Modal de confirmação */
.modal-confirmacao {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.6);
    backdrop-filter: blur(3px);
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
    box-shadow: 0 20px 35px -10px rgba(0,0,0,0.2);
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
    color: #1a2c3e;
}

.modal-confirmacao-caixa p {
    color: #5a6e7c;
    font-size: 0.85rem;
    margin-bottom: 24px;
}

.modal-confirmacao-icone {
    width: 56px;
    height: 56px;
    margin: 0 auto 16px;
    background: #fee2e2;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: #dc3545;
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
    background: #f1f3f5;
    color: #5a6e7c;
}

.botao-cancelar-modal:hover {
    background: #e9ecef;
}

.botao-confirmar-modal {
    background: #dc3545;
    color: white;
}

.botao-confirmar-modal:hover {
    background: #c82333;
    transform: scale(1.02);
}

/* Notificação */
.notificacao {
    position: fixed;
    top: 24px;
    right: 24px;
    padding: 14px 24px;
    background: linear-gradient(135deg, #28a745, #1e7e34);
    color: white;
    border-radius: 50px;
    z-index: 99999;
    font-weight: 600;
    font-size: 0.85rem;
    display: flex;
    align-items: center;
    gap: 10px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    animation: slideInRight 0.3s ease;
}

.notificacao.erro {
    background: linear-gradient(135deg, #dc3545, #b02a37);
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
    .secao-conteudo {
        padding: 20px;
        margin-bottom: 20px;
    }
    
    .secao-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .secao-acoes {
        flex-direction: column;
    }
    
    .btn-grande, .btn-secundario {
        width: 100%;
        justify-content: center;
    }
    
    .item-preview {
        flex-direction: column;
        align-items: flex-start;
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
    .secao-titulo {
        font-size: 1rem;
    }
    
    .secao-titulo i {
        width: 24px;
        height: 24px;
        font-size: 0.9rem;
    }
    
    .campo-form {
        font-size: 0.85rem;
        padding: 10px 14px;
    }
    
    .modal-confirmacao-caixa {
        padding: 24px;
    }
}
</style>

<script>
let pendingHistoriaImagem = null;
let eventoCounter = <?= count($linha_tempo) ?>;

function mostrarNotificacao(mensagem, tipo = 'sucesso') {
    const notif = document.createElement('div');
    notif.className = `notificacao ${tipo}`;
    notif.innerHTML = `<i class="fas fa-${tipo === 'sucesso' ? 'check-circle' : 'exclamation-circle'}"></i> ${mensagem}`;
    document.body.appendChild(notif);
    setTimeout(() => notif.remove(), 3000);
}

function previewHistoriaImagem(input) {
    const file = input.files[0];
    if (!file) return;
    if (file.size > 5 * 1024 * 1024) {
        mostrarNotificacao('Arquivo muito grande. Máximo 5MB', 'erro');
        input.value = '';
        return;
    }
    pendingHistoriaImagem = file;
    const reader = new FileReader();
    reader.onload = function(e) {
        const previewDiv = document.getElementById('previewHistoriaImagem');
        previewDiv.innerHTML = `<img src="${e.target.result}" class="historia-img-preview">`;
    };
    reader.readAsDataURL(file);
}

function adicionarEventoTempo() {
    const container = document.getElementById('listaEventos');
    const emptyState = document.getElementById('emptyEventos');
    if (emptyState) emptyState.style.display = 'none';
    
    const div = document.createElement('div');
    div.className = 'item-evento';
    div.setAttribute('data-novo', 'true');
    
    div.innerHTML = `
        <div class="item-header">
            <span class="item-numero">Novo Evento</span>
            <div class="item-acoes">
                <button type="button" class="btn-eliminar" onclick="removerNovoEvento(this)"><i class="fas fa-trash"></i></button>
            </div>
        </div>
        <div class="item-form" style="display: block;">
            <div class="linha-form">
                <div class="grupo-form">
                    <label>Ano</label>
                    <input type="text" class="campo-form ano-input" placeholder="Ex: 2008">
                </div>
                <div class="grupo-form">
                    <label>Descrição</label>
                    <input type="text" class="campo-form descricao-input" placeholder="Descrição do evento">
                </div>
            </div>
            <div class="item-actions">
                <button type="button" class="btn-salvar-item" onclick="salvarNovoEvento(this)">Adicionar</button>
                <button type="button" class="btn-cancelar-item" onclick="removerNovoEvento(this)">Cancelar</button>
            </div>
        </div>
    `;
    container.appendChild(div);
}

function removerNovoEvento(btn) {
    const item = btn.closest('.item-evento');
    if (item) {
        item.remove();
        const container = document.getElementById('listaEventos');
        if (container.children.length === 0) {
            const emptyState = document.getElementById('emptyEventos');
            if (emptyState) emptyState.style.display = 'block';
        }
    }
}

function editarEvento(btn) {
    const item = btn.closest('.item-evento');
    const previewDiv = item.querySelector('.item-preview');
    const formDiv = item.querySelector('.item-form');
    if (previewDiv) previewDiv.style.display = 'none';
    if (formDiv) formDiv.style.display = 'block';
}

function cancelarEdicaoEvento(btn) {
    const item = btn.closest('.item-evento');
    const previewDiv = item.querySelector('.item-preview');
    const formDiv = item.querySelector('.item-form');
    if (previewDiv) previewDiv.style.display = 'flex';
    if (formDiv) formDiv.style.display = 'none';
}

function salvarEvento(btn) {
    const item = btn.closest('.item-evento');
    const id = item.dataset.id;
    const ano = item.querySelector('.ano-input')?.value;
    const descricao = item.querySelector('.descricao-input')?.value;
    
    if (!ano || !descricao) {
        mostrarNotificacao('Ano e descrição são obrigatórios', 'erro');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'salvar_evento');
    formData.append('id', id);
    formData.append('ano', ano);
    formData.append('descricao', descricao);
    
    fetch('processos/processar-sobre.php', {
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
    });
}

function salvarNovoEvento(btn) {
    const item = btn.closest('.item-evento');
    const ano = item.querySelector('.ano-input')?.value;
    const descricao = item.querySelector('.descricao-input')?.value;
    
    if (!ano || !descricao) {
        mostrarNotificacao('Ano e descrição são obrigatórios', 'erro');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'salvar_evento');
    formData.append('ano', ano);
    formData.append('descricao', descricao);
    
    fetch('processos/processar-sobre.php', {
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
    });
}

function eliminarEvento(btn, id) {
    const modal = document.getElementById('modalConfirmacao');
    
    document.getElementById('confirmacaoTitulo').textContent = 'Eliminar Evento';
    document.getElementById('confirmacaoTexto').textContent = 'Tem certeza que deseja eliminar este evento permanentemente?';
    
    const confirmar = document.getElementById('btnConfirmarAcao');
    const novoConfirmar = confirmar.cloneNode(true);
    confirmar.parentNode.replaceChild(novoConfirmar, confirmar);
    
    novoConfirmar.onclick = async () => {
        try {
            const response = await fetch('processos/processar-sobre.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ action: 'eliminar_evento', id: id })
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

function salvarTudo() {
    const formData = new FormData();
    formData.append('action', 'salvar_geral');
    formData.append('hero_titulo', document.getElementById('heroTitulo').value);
    formData.append('hero_subtitulo', document.getElementById('heroSubtitulo').value);
    formData.append('historia_titulo', document.getElementById('historiaTitulo').value);
    formData.append('historia_legenda', document.getElementById('historiaLegenda').value);
    formData.append('historia_conteudo', document.getElementById('historiaConteudo').value);
    formData.append('missao', document.getElementById('missao').value);
    formData.append('visao', document.getElementById('visao').value);
    formData.append('valores', document.getElementById('valores').value);
    formData.append('lema', document.getElementById('lema').value);
    formData.append('lema_descricao', document.getElementById('lemaDescricao').value);
    
    if (pendingHistoriaImagem) formData.append('historia_imagem', pendingHistoriaImagem);
    
    fetch('processos/processar-sobre.php', {
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
    window.open('../area-publica/sobre-nos.php?preview=1', '_blank');
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
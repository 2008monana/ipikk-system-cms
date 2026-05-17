<?php
/**
 * Normativos - Área Restrita IPIKK
 * Gestão completa dos documentos normativos (PDFs)
 */

$titulo_pagina = 'Normativos';
$css_especifico = 'admin-normativos.css';

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
// BUSCAR CONTEÚDO DA PÁGINA (JSON)
// ============================================
$pagina = getConteudoPagina('normativos');
$descricao_pagina = $pagina['descricao_pagina'] ?? 'Aqui encontra todos os documentos normativos e institucionais do IPIKK, incluindo regulamentos, projetos educativos, leis de base e outros documentos oficiais para consulta e download.';

// ============================================
// BUSCAR DOCUMENTOS NORMATIVOS
// ============================================
$documentos = $db->query("
    SELECT * FROM documentos 
    WHERE categoria = 'normativos' 
    ORDER BY ordem
")->fetchAll();

// Estatísticas
$total_documentos = count($documentos);
$total_downloads = array_sum(array_column($documentos, 'downloads'));

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
                <i class="fas fa-file-alt"></i> Normativos
            </h1>
        </div>
        <div class="direita-barra">
            <button class="btn-primario" id="btnNovoDocumento" onclick="abrirModalDocumento()">
                <i class="fas fa-plus"></i>
                <span>Novo Documento</span>
            </button>
        </div>
    </header>

    <div class="conteudo-pagina">

        <!-- DESCRIÇÃO DA PÁGINA -->
        <div class="secao-descricao">
            <div class="descricao-header">
                <h3><i class="fas fa-align-left"></i> Descrição da Página</h3>
                <button class="btn-editar-descricao" onclick="abrirModalDescricao()">
                    <i class="fas fa-edit"></i> Editar
                </button>
            </div>
            <p class="descricao-texto"><?= htmlspecialchars($descricao_pagina) ?></p>
        </div>

        <!-- CARDS ESTATÍSTICAS -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon purple"><i class="fas fa-file-alt"></i></div>
                <div class="stat-info">
                    <h3><?= $total_documentos ?></h3>
                    <p>Total de Documentos</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-download"></i></div>
                <div class="stat-info">
                    <h3><?= number_format($total_downloads, 0, ',', '.') ?></h3>
                    <p>Downloads Totais</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-file-pdf"></i></div>
                <div class="stat-info">
                    <h3><?= $total_documentos ?></h3>
                    <p>PDFs Disponíveis</p>
                </div>
            </div>
        </div>

        <!-- LISTA DE DOCUMENTOS -->
        <div class="secao-conteudo">
            <div class="secao-header">
                <h2 class="secao-titulo">
                    <i class="fas fa-list"></i> Documentos Normativos
                    <span class="contador">(<?= $total_documentos ?> documentos)</span>
                </h2>
                <button class="btn-adicionar" onclick="abrirModalDocumento()">
                    <i class="fas fa-plus"></i> Adicionar
                </button>
            </div>

            <div class="tabela-container">
                <?php if (empty($documentos)): ?>
                <div class="empty-state">
                    <i class="fas fa-file-alt"></i>
                    <h3>Nenhum documento cadastrado</h3>
                    <p>Clique em "Novo Documento" para começar a adicionar.</p>
                    <button class="btn-primario" onclick="abrirModalDocumento()">
                        <i class="fas fa-plus"></i> Adicionar Primeiro
                    </button>
                </div>
                <?php else: ?>
                <table class="tabela-documentos">
                    <thead>
                        <tr>
                            <th width="60">ID</th>
                            <th>Título</th>
                            <th>Descrição</th>
                            <th>Imagem</th>
                            <th width="100">Downloads</th>
                            <th width="120">Data</th>
                            <th width="140">Ações</th>
                        </thead>
                    <tbody>
                        <?php foreach ($documentos as $doc): ?>
                        <tr data-id="<?= $doc['id'] ?>">
                            <td><?= $doc['id'] ?></td>
                            <td class="titulo-coluna">
                                <strong><?= htmlspecialchars($doc['titulo']) ?></strong>
                                <?php if ($doc['tamanho_kb']): ?>
                                <span class="tamanho">(<?= round($doc['tamanho_kb'] / 1024, 2) ?> MB)</span>
                                <?php endif; ?>
                            </td>
                            <td class="descricao-coluna">
                                <?= htmlspecialchars(mb_substr($doc['descricao'] ?? '', 0, 60)) ?>
                                <?php if (strlen($doc['descricao'] ?? '') > 60): ?>...<?php endif; ?>
                            </td>
                            <td class="imagem-coluna">
                                <?php if (!empty($doc['imagem_url'])): ?>
                                    <?php if (strpos($doc['imagem_url'], 'http') === 0): ?>
                                        <img src="<?= $doc['imagem_url'] ?>" class="miniatura-imagem" alt="Imagem">
                                    <?php else: ?>
                                        <img src="../area-publica/<?= $doc['imagem_url'] ?>" class="miniatura-imagem" alt="Imagem" onerror="this.style.display='none'">
                                    <?php endif; ?>
                                <?php else: ?>
                                <span class="sem-imagem">Sem imagem</span>
                                <?php endif; ?>
                            </td>
                            <td class="downloads-coluna"><?= number_format($doc['downloads'] ?? 0, 0, ',', '.') ?></td>
                            <td class="data-coluna"><?= $doc['data_publicacao'] ? date('d/m/Y', strtotime($doc['data_publicacao'])) : '-' ?></td>
                            <td class="acoes-coluna">
                                <div class="acoes-botoes">
                                    <button class="btn-editar" onclick="editarDocumento(<?= $doc['id'] ?>)" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn-eliminar" onclick="eliminarDocumento(<?= $doc['id'] ?>)" title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                             </td>
                         </tr>
                        <?php endforeach; ?>
                    </tbody>
                 </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<!-- MODAL PARA DESCRIÇÃO DA PÁGINA -->
<div id="modalDescricao" class="modal">
    <div class="modal-conteudo modal-pequeno">
        <div class="modal-cabecalho">
            <h2><i class="fas fa-align-left"></i> Editar Descrição da Página</h2>
            <button class="modal-fechar" onclick="fecharModalDescricao()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-corpo">
            <form id="formDescricao" onsubmit="return salvarDescricao(event)">
                <div class="grupo-form">
                    <label><i class="fas fa-align-left"></i> Descrição da Página Normativos</label>
                    <textarea id="campoDescricaoPagina" class="campo-form area-texto" rows="5"><?= htmlspecialchars($descricao_pagina) ?></textarea>
                    <small class="info-texto">Esta descrição será exibida no topo da página pública de Normativos.</small>
                </div>
                <div class="modal-acoes">
                    <button type="button" class="btn-cancelar" onclick="fecharModalDescricao()">
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

<!-- MODAL PARA DOCUMENTO -->
<div id="modalDocumento" class="modal">
    <div class="modal-conteudo modal-medio">
        <div class="modal-cabecalho">
            <h2 id="modalTitulo"><i class="fas fa-plus"></i> Novo Documento</h2>
            <button class="modal-fechar" onclick="fecharModalDocumento()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-corpo">
            <form id="formDocumento" onsubmit="return salvarDocumento(event)">
                <input type="hidden" id="documentoId">

                <div class="grupo-form">
                    <label><i class="fas fa-heading"></i> Título *</label>
                    <input type="text" id="campoTitulo" class="campo-form" required>
                </div>

                <div class="grupo-form">
                    <label><i class="fas fa-align-left"></i> Descrição</label>
                    <textarea id="campoDescricao" class="campo-form area-texto" rows="3" placeholder="Breve descrição do documento..."></textarea>
                </div>

                <div class="grupo-form">
                    <label><i class="fas fa-image"></i> Imagem de Capa</label>
                    <div class="area-upload" onclick="document.getElementById('imagemInput').click()">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>Clique para fazer upload da imagem</p>
                        <small>Formatos: JPG, PNG | 400x200px recomendado</small>
                    </div>
                    <input type="file" id="imagemInput" accept="image/*" style="display: none;" onchange="previewImagem(this)">
                    <div class="preview-imagem" id="previewImagem" style="display: none;">
                        <img id="previewImg" src="" alt="Preview">
                        <button type="button" class="btn-remover" onclick="removerImagem()">
                            <i class="fas fa-trash"></i> Remover
                        </button>
                    </div>
                    <div id="imagemExistente" style="display: none;" class="info-existente">
                        <i class="fas fa-check-circle"></i> Imagem atual mantida
                    </div>
                </div>

                <div class="grupo-form">
                    <label><i class="fas fa-file-pdf"></i> Arquivo PDF</label>
                    <div class="area-upload" onclick="document.getElementById('pdfInput').click()">
                        <i class="fas fa-file-pdf"></i>
                        <p>Clique para selecionar o arquivo PDF</p>
                        <small>Formatos: PDF | Máx: 10MB | Deixe em branco para manter o PDF atual</small>
                    </div>
                    <input type="file" id="pdfInput" accept=".pdf" style="display: none;" onchange="previewPDF(this)">
                    <div class="preview-pdf" id="previewPDF" style="display: none;">
                        <div>
                            <i class="fas fa-file-pdf"></i>
                            <span id="previewNome"></span>
                            <span id="previewTamanho" class="tamanho"></span>
                        </div>
                        <button type="button" class="btn-remover" onclick="removerPDF()">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    <div id="pdfExistente" style="display: none;" class="info-existente">
                        <i class="fas fa-check-circle"></i> PDF atual mantido
                    </div>
                </div>

                <div class="linha-form">
                    <div class="grupo-form">
                        <label><i class="fas fa-calendar-alt"></i> Data de Publicação</label>
                        <input type="date" id="campoData" class="campo-form">
                    </div>
                    <div class="grupo-form">
                        <label><i class="fas fa-sort-numeric-down"></i> Ordem de Exibição</label>
                        <input type="number" id="campoOrdem" class="campo-form" value="0" min="0">
                    </div>
                </div>

                <div class="modal-acoes">
                    <button type="button" class="btn-cancelar" onclick="fecharModalDocumento()">
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
/* ===== ESTILOS ADMIN NORMATIVOS ===== */
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
/* Descrição da Página */
.secao-descricao {
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    border-radius: 20px;
    padding: 24px 28px;
    margin-bottom: 28px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
    border: 1px solid #eef2f8;
    transition: all 0.3s ease;
}

.secao-descricao:hover {
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
    border-color: #e2e8f0;
}

.descricao-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 14px;
    flex-wrap: wrap;
    gap: 12px;
}

.descricao-header h3 {
    font-size: 0.9rem;
    font-weight: 700;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
    color: #1e293b;
    letter-spacing: 0.3px;
}

.descricao-header h3 i {
    color: #0a9396;
    font-size: 1rem;
}

.btn-editar-descricao {
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    color: #475569;
    cursor: pointer;
    font-size: 0.75rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border-radius: 40px;
    transition: all 0.2s ease;
}

.btn-editar-descricao:hover {
    background: #0a9396;
    border-color: #0a9396;
    color: white;
    transform: translateY(-1px);
}

.descricao-texto {
    color: #475569;
    line-height: 1.7;
    margin: 0;
    font-size: 0.85rem;
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

/* Botão Principal (Novo Documento) */
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

/* Tabela de Documentos */
.tabela-container {
    overflow-x: auto;
    border-radius: 16px;
}

.tabela-documentos {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.85rem;
}

.tabela-documentos th {
    text-align: left;
    padding: 14px 16px;
    background: #f8fafc;
    font-weight: 700;
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #64748b;
    border-bottom: 2px solid #eef2f8;
}

.tabela-documentos td {
    padding: 14px 16px;
    border-bottom: 1px solid #eef2f8;
    vertical-align: middle;
    color: #334155;
}

.tabela-documentos tr {
    transition: background 0.2s ease;
}

.tabela-documentos tr:hover {
    background: #f8fafc;
}

.titulo-coluna strong {
    font-size: 0.85rem;
    font-weight: 600;
    color: #1e293b;
}

.tamanho {
    font-size: 0.65rem;
    color: #94a3b8;
    margin-left: 6px;
}

.descricao-coluna {
    color: #64748b;
    font-size: 0.75rem;
    max-width: 250px;
    line-height: 1.4;
}

.imagem-coluna {
    text-align: center;
}

.miniatura-imagem {
    width: 45px;
    height: 45px;
    border-radius: 10px;
    object-fit: cover;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    transition: transform 0.2s ease;
}

.miniatura-imagem:hover {
    transform: scale(1.5);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    z-index: 1;
}

.sem-imagem {
    font-size: 0.65rem;
    color: #94a3b8;
    background: #f8fafc;
    padding: 4px 8px;
    border-radius: 20px;
}

.downloads-coluna {
    font-weight: 700;
    color: #0a9396;
    text-align: center;
    font-size: 0.85rem;
}

.data-coluna {
    font-size: 0.7rem;
    color: #64748b;
    white-space: nowrap;
}

/* Ações da tabela */
.acoes-coluna .acoes-botoes {
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

.modal-pequeno {
    max-width: 550px;
}

.modal-medio {
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

/* Preview de imagem */
.preview-imagem {
    margin-top: 16px;
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 12px 16px;
    background: #f0fdfa;
    border-radius: 14px;
    border: 1px solid #ccfbf1;
}

.preview-imagem img {
    width: 70px;
    height: 70px;
    border-radius: 12px;
    object-fit: cover;
    border: 1px solid #e2e8f0;
}

/* Preview de PDF */
.preview-pdf {
    margin-top: 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    background: #f0fdfa;
    border-radius: 14px;
    border: 1px solid #ccfbf1;
}

.preview-pdf div {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.preview-pdf i {
    font-size: 1.5rem;
    color: #dc2626;
}

.preview-pdf .tamanho {
    font-size: 0.65rem;
    color: #64748b;
}

.info-existente {
    margin-top: 10px;
    padding: 8px 14px;
    background: #e8f5e9;
    border-radius: 10px;
    font-size: 0.7rem;
    color: #2e7d32;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.info-existente i {
    font-size: 0.7rem;
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

.btn-salvar:disabled {
    opacity: 0.7;
    cursor: not-allowed;
    transform: none;
}

.info-texto {
    display: block;
    font-size: 0.65rem;
    color: #94a3b8;
    margin-top: 6px;
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
    
    .tabela-documentos {
        font-size: 0.75rem;
    }
    
    .tabela-documentos th,
    .tabela-documentos td {
        padding: 10px 12px;
    }
    
    .descricao-coluna {
        max-width: 150px;
    }
    
    .modal-medio, .modal-pequeno {
        max-width: 95%;
    }
    
    .linha-form {
        grid-template-columns: 1fr;
        gap: 0;
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
    
    .preview-pdf div {
        flex-direction: column;
        align-items: flex-start;
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
    
    .tabela-documentos {
        font-size: 0.7rem;
    }
    
    .tabela-documentos th,
    .tabela-documentos td {
        padding: 8px 10px;
    }
    
    .btn-editar, .btn-eliminar {
        width: 30px;
        height: 30px;
        font-size: 0.75rem;
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
let imagemFile = null;
let pdfFile = null;

function mostrarNotificacao(mensagem, tipo = 'sucesso') {
    const notif = document.createElement('div');
    notif.className = 'notificacao';
    notif.innerHTML = `<i class="fas fa-${tipo === 'sucesso' ? 'check-circle' : 'exclamation-circle'}"></i> ${mensagem}`;
    notif.style.cssText = 'position:fixed;top:20px;right:20px;padding:14px 24px;background:linear-gradient(135deg,#28a745,#1e7e34);color:white;border-radius:12px;z-index:99999;font-weight:600;animation:slideIn 0.3s;';
    if (tipo === 'erro') notif.style.background = 'linear-gradient(135deg,#dc3545,#c82333)';
    document.body.appendChild(notif);
    setTimeout(() => notif.remove(), 3000);
}

function previewImagem(input) {
    const file = input.files[0];
    if (!file) return;
    
    if (file.size > 5 * 1024 * 1024) {
        mostrarNotificacao('Imagem muito grande. Máximo 5MB', 'erro');
        input.value = '';
        return;
    }
    
    imagemFile = file;
    const reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById('previewImg').src = e.target.result;
        document.getElementById('previewImagem').style.display = 'flex';
        document.getElementById('imagemExistente').style.display = 'none';
    };
    reader.readAsDataURL(file);
}

function removerImagem() {
    document.getElementById('imagemInput').value = '';
    document.getElementById('previewImagem').style.display = 'none';
    imagemFile = null;
}

function previewPDF(input) {
    const file = input.files[0];
    if (!file) return;
    
    if (file.type !== 'application/pdf') {
        mostrarNotificacao('Formato inválido. Selecione um arquivo PDF.', 'erro');
        input.value = '';
        return;
    }
    
    if (file.size > 10 * 1024 * 1024) {
        mostrarNotificacao('Arquivo muito grande. Máximo 10MB', 'erro');
        input.value = '';
        return;
    }
    
    pdfFile = file;
    document.getElementById('previewNome').textContent = file.name;
    document.getElementById('previewTamanho').textContent = `(${(file.size / 1024 / 1024).toFixed(2)} MB)`;
    document.getElementById('previewPDF').style.display = 'flex';
    document.getElementById('pdfExistente').style.display = 'none';
}

function removerPDF() {
    document.getElementById('pdfInput').value = '';
    document.getElementById('previewPDF').style.display = 'none';
    pdfFile = null;
}

// ============================================
// DESCRIÇÃO DA PÁGINA
// ============================================

function abrirModalDescricao() {
    document.getElementById('modalDescricao').classList.add('ativo');
}

function fecharModalDescricao() {
    document.getElementById('modalDescricao').classList.remove('ativo');
}

async function salvarDescricao(event) {
    event.preventDefault();
    
    const descricao = document.getElementById('campoDescricaoPagina').value;
    
    try {
        const response = await fetch('processos/processar-normativos.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'salvar_descricao',
                descricao_pagina: descricao
            })
        });
        const data = await response.json();
        
        if (data.success) {
            mostrarNotificacao(data.message, 'sucesso');
            setTimeout(() => location.reload(), 1000);
        } else {
            mostrarNotificacao(data.message, 'erro');
        }
    } catch (error) {
        mostrarNotificacao('Erro ao salvar descrição', 'erro');
    }
}

// ============================================
// DOCUMENTOS
// ============================================

function abrirModalDocumento(id = null) {
    const modal = document.getElementById('modalDocumento');
    const titulo = document.getElementById('modalTitulo');
    
    document.getElementById('formDocumento').reset();
    document.getElementById('documentoId').value = '';
    document.getElementById('previewImagem').style.display = 'none';
    document.getElementById('previewPDF').style.display = 'none';
    document.getElementById('imagemExistente').style.display = 'none';
    document.getElementById('pdfExistente').style.display = 'none';
    document.getElementById('campoOrdem').value = '0';
    imagemFile = null;
    pdfFile = null;
    
    if (id) {
        titulo.innerHTML = '<i class="fas fa-edit"></i> Editar Documento';
        editarDocumento(id);
    } else {
        titulo.innerHTML = '<i class="fas fa-plus"></i> Novo Documento';
        modal.classList.add('ativo');
    }
}

function fecharModalDocumento() {
    document.getElementById('modalDocumento').classList.remove('ativo');
}

function editarDocumento(id) {
    fetch(`processos/processar-normativos.php?action=buscar&id=${id}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const doc = data.documento;
                document.getElementById('documentoId').value = doc.id;
                document.getElementById('campoTitulo').value = doc.titulo;
                document.getElementById('campoDescricao').value = doc.descricao || '';
                document.getElementById('campoData').value = doc.data_publicacao || '';
                document.getElementById('campoOrdem').value = doc.ordem || 0;
                
                if (doc.imagem_url) {
                    document.getElementById('previewImg').src = '../area-publica/' + doc.imagem_url;
                    document.getElementById('previewImagem').style.display = 'flex';
                    document.getElementById('imagemExistente').style.display = 'block';
                }
                
                if (doc.pdf_url) {
                    document.getElementById('previewNome').textContent = doc.pdf_url.split('/').pop();
                    document.getElementById('previewPDF').style.display = 'flex';
                    document.getElementById('pdfExistente').style.display = 'block';
                }
                
                document.getElementById('modalDocumento').classList.add('ativo');
            }
        });
}

async function salvarDocumento(event) {
    event.preventDefault();
    
    const id = document.getElementById('documentoId').value;
    const titulo = document.getElementById('campoTitulo').value;
    const descricao = document.getElementById('campoDescricao').value;
    const data_publicacao = document.getElementById('campoData').value;
    const ordem = document.getElementById('campoOrdem').value;
    
    if (!titulo) {
        mostrarNotificacao('Título é obrigatório', 'erro');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', id ? 'editar' : 'salvar');
    if (id) formData.append('id', id);
    formData.append('titulo', titulo);
    formData.append('descricao', descricao);
    formData.append('categoria', 'normativos');
    formData.append('data_publicacao', data_publicacao);
    formData.append('ordem', ordem);
    
    if (imagemFile) formData.append('imagem', imagemFile);
    if (pdfFile) formData.append('pdf', pdfFile);
    
    const btnSalvar = document.querySelector('#modalDocumento .btn-salvar');
    const textoOriginal = btnSalvar.innerHTML;
    btnSalvar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';
    btnSalvar.disabled = true;
    
    try {
        const response = await fetch('processos/processar-normativos.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            mostrarNotificacao(data.message, 'sucesso');
            setTimeout(() => location.reload(), 1000);
        } else {
            mostrarNotificacao(data.message, 'erro');
            btnSalvar.innerHTML = textoOriginal;
            btnSalvar.disabled = false;
        }
    } catch (error) {
        console.error('Erro:', error);
        mostrarNotificacao('Erro ao salvar: ' + error.message, 'erro');
        btnSalvar.innerHTML = textoOriginal;
        btnSalvar.disabled = false;
    }
}

function eliminarDocumento(id) {
    const modal = document.getElementById('modalConfirmacao');
    document.getElementById('confirmacaoTitulo').textContent = 'Eliminar Documento';
    document.getElementById('confirmacaoTexto').textContent = 'Tem certeza que deseja eliminar este documento permanentemente?';
    
    const confirmar = document.getElementById('btnConfirmarAcao');
    const novoConfirmar = confirmar.cloneNode(true);
    confirmar.parentNode.replaceChild(novoConfirmar, confirmar);
    
    novoConfirmar.onclick = async () => {
        try {
            const response = await fetch('processos/processar-normativos.php', {
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

// Event listeners
document.getElementById('btnNovoDocumento')?.addEventListener('click', () => abrirModalDocumento());
document.getElementById('btnCancelarConfirmacao')?.addEventListener('click', () => {
    document.getElementById('modalConfirmacao').classList.remove('ativo');
});

document.getElementById('modalDescricao')?.addEventListener('click', (e) => {
    if (e.target === document.getElementById('modalDescricao')) fecharModalDescricao();
});

document.getElementById('modalDocumento')?.addEventListener('click', (e) => {
    if (e.target === document.getElementById('modalDocumento')) fecharModalDocumento();
});

document.getElementById('modalConfirmacao')?.addEventListener('click', (e) => {
    if (e.target === document.getElementById('modalConfirmacao')) {
        document.getElementById('modalConfirmacao').classList.remove('ativo');
    }
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        fecharModalDescricao();
        fecharModalDocumento();
        document.getElementById('modalConfirmacao').classList.remove('ativo');
    }
});
</script>

<?php include 'includes/footer.php'; ?>
<?php
/**
 * Galeria - Área Restrita IPIKK
 * Gestão completa da galeria de mídias e categorias
 */

$titulo_pagina = 'Galeria';
$css_especifico = 'admin-galeria.css';

require_once dirname(__DIR__) . '/config/index.php';

if (!isset($_SESSION['utilizador_id'])) {
    header('Location: area-restrita.php');
    exit;
}


require_once __DIR__ . '/includes/verificar-permissao.php';

// ===== VERIFICAR PERMISSÃO ESPECÍFICA =====
verificarPermissao('galeria');
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
// BUSCAR CATEGORIAS DA GALERIA
// ============================================
$categorias = $db->query("
    SELECT * FROM categorias_galeria 
    WHERE ativo = 1 
    ORDER BY ordem
")->fetchAll();

// ============================================
// BUSCAR MÍDIAS DA GALERIA
// ============================================
$midias = $db->query("
    SELECT g.*, c.nome as categoria_nome, c.slug as categoria_slug, c.cor_classe 
    FROM galeria g 
    JOIN categorias_galeria c ON g.categoria_id = c.id 
    WHERE g.ativo = 1 
    ORDER BY g.ordem, g.created_at DESC
")->fetchAll();

// Estatísticas
$total_midias = count($midias);
$total_imagens = count(array_filter($midias, fn($m) => $m['tipo'] === 'imagem'));
$total_videos = count(array_filter($midias, fn($m) => $m['tipo'] === 'video'));

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
                <i class="fas fa-images"></i> Galeria
            </h1>
        </div>
        <div class="direita-barra">
            <button class="btn-primario" id="btnNovaMidia">
                <i class="fas fa-plus"></i>
                <span>Nova Mídia</span>
            </button>
        </div>
    </header>

    <div class="conteudo-pagina">

        <!-- CARDS ESTATÍSTICAS -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon purple"><i class="fas fa-images"></i></div>
                <div class="stat-info">
                    <h3><?= $total_midias ?></h3>
                    <p>Total de Mídias</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-image"></i></div>
                <div class="stat-info">
                    <h3><?= $total_imagens ?></h3>
                    <p>Imagens</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-video"></i></div>
                <div class="stat-info">
                    <h3><?= $total_videos ?></h3>
                    <p>Vídeos</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon orange"><i class="fas fa-tags"></i></div>
                <div class="stat-info">
                    <h3><?= count($categorias) ?></h3>
                    <p>Categorias</p>
                </div>
            </div>
        </div>

        <!-- CATEGORIAS (com toggle e sem slug) -->
        <div class="secao-categorias">
            <div class="secao-header">
                <div class="secao-header-left">
                    <i class="fas fa-tags"></i>
                    <h2 class="secao-titulo">
                        Categorias da Galeria
                        <span class="contador">(<?= count($categorias) ?> categorias)</span>
                    </h2>
                </div>
                <div class="secao-header-acoes">
                    <button class="btn-toggle-categorias" id="btnToggleCategorias">
                        <i class="fas fa-chevron-up"></i>
                    </button>
                    <button class="btn-adicionar" onclick="abrirModalCategoria()">
                        <i class="fas fa-plus"></i> Nova Categoria
                    </button>
                </div>
            </div>
            <div class="categorias-container" id="categoriasContainer">
                <div class="grid-categorias" id="gridCategorias">
                    <?php foreach ($categorias as $cat): ?>
                    <div class="card-categoria" data-id="<?= $cat['id'] ?>" style="border-left-color: <?= htmlspecialchars($cat['cor_classe'] ?? '#003072') ?>;">
                        <div class="card-categoria-icon" style="background: <?= htmlspecialchars($cat['cor_classe'] ?? '#003072') ?>20;">
                            <i class="fas <?= htmlspecialchars($cat['icone'] ?? 'fa-tag') ?>"></i>
                        </div>
                        <div class="card-categoria-info">
                            <h3><?= htmlspecialchars($cat['nome']) ?></h3>
                        </div>
                        <div class="card-categoria-cor">
                            <span class="cor-preview" style="background: <?= htmlspecialchars($cat['cor_classe'] ?? '#003072') ?>;"></span>
                        </div>
                        <div class="card-categoria-acoes">
                            <button class="btn-editar" onclick="editarCategoria(<?= $cat['id'] ?>)" title="Editar categoria">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn-eliminar" onclick="eliminarCategoria(<?= $cat['id'] ?>)" title="Eliminar categoria">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- FILTROS (com toggle) -->
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
                        <label><i class="fas fa-tag"></i> Categoria</label>
                        <select id="filtroCategoria" class="filter-select">
                            <option value="">Todas as categorias</option>
                            <?php foreach ($categorias as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label><i class="fas fa-play-circle"></i> Tipo</label>
                        <select id="filtroTipo" class="filter-select">
                            <option value="">Todos</option>
                            <option value="imagem">Imagens</option>
                            <option value="video">Vídeos</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label><i class="fas fa-search"></i> Buscar</label>
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" id="campoBusca" placeholder="Buscar por legenda...">
                        </div>
                    </div>
                    <div class="filter-actions">
                        <button class="btn-filter" id="btnAplicarFiltros"><i class="fas fa-search"></i> Aplicar</button>
                        <button class="btn-clear" id="btnLimparFiltros"><i class="fas fa-undo"></i> Limpar</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- GRADE DA GALERIA -->
        <div class="galeria-container">
            <div class="galeria-header">
                <h2 class="galeria-titulo">
                    <i class="fas fa-th-large"></i> Todas as Mídias
                    <span class="contador" id="contadorMidias">(<?= $total_midias ?> itens)</span>
                </h2>
                <div class="galeria-acoes">
                    <button class="btn-acao" id="btnOrdenar" title="Ordenar por arrasto">
                        <i class="fas fa-arrows-alt"></i> Ordenar
                    </button>
                    <button class="btn-acao" id="btnGuardarOrdem" style="display: none;">
                        <i class="fas fa-save"></i> Guardar Ordem
                    </button>
                    <button class="btn-acao" id="btnCancelarOrdem" style="display: none;">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                </div>
            </div>

            <div class="galeria-grid" id="galeriaGrid">
                <?php if (empty($midias)): ?>
                <div class="empty-state">
                    <i class="fas fa-images"></i>
                    <h3>Nenhuma mídia na galeria</h3>
                    <p>Clique em "Nova Mídia" para começar a adicionar imagens e vídeos.</p>
                    <button class="btn-primario" onclick="abrirModalMidia()">
                        <i class="fas fa-plus"></i> Adicionar Primeira Mídia
                    </button>
                </div>
                <?php else: ?>
                    <?php foreach ($midias as $midia): 
                        // CORREÇÃO: Caminho correto da imagem/vídeo
                        $url_caminho = $midia['url'];
                        if (strpos($url_caminho, '/uploads/') === 0) {
                            $url_caminho = '..' . $url_caminho;
                        } elseif (strpos($url_caminho, 'http') !== 0 && strpos($url_caminho, '../') !== 0) {
                            $url_caminho = '../' . $url_caminho;
                        }
                    ?>
                    <div class="galeria-card" data-id="<?= $midia['id'] ?>" data-categoria="<?= $midia['categoria_id'] ?>" data-tipo="<?= $midia['tipo'] ?>">
                        <div class="card-preview">
                            <?php if ($midia['tipo'] === 'imagem'): ?>
                                <img src="<?= $url_caminho ?>" alt="<?= htmlspecialchars($midia['legenda']) ?>" onerror="this.src='https://via.placeholder.com/400x300?text=Erro+ao+carregar'">
                            <?php else: ?>
                                <video src="<?= $url_caminho ?>" muted preload="metadata"></video>
                                <div class="video-overlay"><i class="fas fa-play"></i></div>
                            <?php endif; ?>
                        </div>
                        <div class="card-info">
                            <div class="card-categoria" style="background: <?= $midia['cor_classe'] ?? '#003072' ?>20; color: <?= $midia['cor_classe'] ?? '#003072' ?>;">
                                <i class="fas <?= $midia['tipo'] === 'imagem' ? 'fa-image' : 'fa-video' ?>"></i>
                                <?= htmlspecialchars($midia['categoria_nome']) ?>
                            </div>
                            <p class="card-legenda"><?= htmlspecialchars($midia['legenda'] ?? 'Sem legenda') ?></p>
                            <div class="card-acoes">
                                <button class="btn-editar" onclick="editarMidia(<?= $midia['id'] ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn-eliminar" onclick="eliminarMidia(<?= $midia['id'] ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-handle" style="display: none;">
                            <i class="fas fa-grip-vertical"></i>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<!-- MODAL PARA MÍDIA -->
<div id="modalMidia" class="modal">
    <div class="modal-conteudo">
        <div class="modal-cabecalho">
            <h2 id="modalTituloMidia"><i class="fas fa-plus"></i> Nova Mídia</h2>
            <button class="modal-fechar" onclick="fecharModalMidia()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-corpo">
            <form id="formMidia" onsubmit="return salvarMidia(event)">
                <input type="hidden" id="midiaId">
                
                <div class="grupo-form">
                    <label><i class="fas fa-tag"></i> Categoria *</label>
                    <select id="midiaCategoria" class="campo-form" required>
                        <option value="">Selecione...</option>
                        <?php foreach ($categorias as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="grupo-form">
                    <label><i class="fas fa-play-circle"></i> Tipo de Mídia *</label>
                    <div class="tipo-opcoes">
                        <label class="tipo-opcao selecionado">
                            <input type="radio" name="tipo_midia" value="imagem" checked onchange="toggleTipoMidia()">
                            <i class="fas fa-image"></i> Imagem
                        </label>
                        <label class="tipo-opcao">
                            <input type="radio" name="tipo_midia" value="video" onchange="toggleTipoMidia()">
                            <i class="fas fa-video"></i> Vídeo
                        </label>
                    </div>
                </div>

                <div id="secaoUpload">
                    <div class="grupo-form">
                        <label><i class="fas fa-cloud-upload-alt"></i> Upload de Arquivo</label>
                        <div class="area-upload" onclick="document.getElementById('arquivoInput').click()">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p><strong>Clique para selecionar o arquivo</strong></p>
                            <small id="uploadInfo">Formatos: JPG, PNG, GIF | Máx: 5MB</small>
                        </div>
                        <input type="file" id="arquivoInput" accept="image/*" style="display: none;" onchange="previewArquivo(this)">
                        <div class="preview-arquivo" id="previewArquivo" style="display: none;">
                            <div>
                                <i class="fas fa-image" id="previewIcone"></i>
                                <span id="previewNome"></span>
                                <span id="previewTamanho" class="tamanho"></span>
                            </div>
                            <button type="button" class="btn-remover" onclick="removerArquivo()">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>

                    <div class="grupo-form" id="secaoUrlExterna" style="display: none;">
                        <label><i class="fas fa-link"></i> Ou URL Externa</label>
                        <input type="text" id="urlExterna" class="campo-form" placeholder="https://exemplo.com/imagem.jpg">
                        <small class="info-texto">Para vídeos do YouTube, use o link de compartilhamento ou URL direta do MP4</small>
                    </div>
                </div>

                <div class="grupo-form">
                    <label><i class="fas fa-quote-left"></i> Legenda</label>
                    <input type="text" id="midiaLegenda" class="campo-form" placeholder="Breve descrição da mídia">
                </div>

                <div class="grupo-form">
                    <label><i class="fas fa-sort-numeric-down"></i> Ordem de Exibição</label>
                    <input type="number" id="midiaOrdem" class="campo-form" value="0" min="0">
                </div>

                <div class="modal-acoes">
                    <button type="button" class="btn-cancelar" onclick="fecharModalMidia()">
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

<!-- MODAL PARA CATEGORIA (sem slug) -->
<div id="modalCategoria" class="modal">
    <div class="modal-conteudo modal-categoria">
        <div class="modal-cabecalho">
            <h2 id="modalTituloCategoria"><i class="fas fa-plus"></i> Nova Categoria</h2>
            <button class="modal-fechar" onclick="fecharModalCategoria()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-corpo">
            <form id="formCategoria" onsubmit="return salvarCategoria(event)">
                <input type="hidden" id="categoriaId">
                
                <div class="grupo-form">
                    <label><i class="fas fa-heading"></i> Nome da Categoria *</label>
                    <input type="text" id="categoriaNome" class="campo-form" required>
                </div>

                <div class="linha-form">
                    <div class="grupo-form">
                        <label><i class="fas fa-palette"></i> Cor da Categoria</label>
                        <input type="color" id="categoriaCor" class="campo-form" value="#003072">
                        <div class="cor-preview" id="categoriaCorPreview" style="background: #003072; margin-top: 8px; height: 30px; border-radius: 8px;"></div>
                    </div>
                    <div class="grupo-form">
                        <label><i class="fas fa-icons"></i> Ícone (Font Awesome)</label>
                        <input type="text" id="categoriaIcone" class="campo-form" value="fa-tag" placeholder="fa-tag, fa-image, fa-video">
                        <div class="icone-preview" style="margin-top: 8px; padding: 8px; background: #f5f7fa; border-radius: 8px; text-align: center;">
                            <i class="fas fa-tag" id="iconePreviewCategoria"></i> <span id="iconePreviewText">fa-tag</span>
                        </div>
                    </div>
                </div>

                <div class="grupo-form">
                    <label><i class="fas fa-sort-numeric-down"></i> Ordem de Exibição</label>
                    <input type="number" id="categoriaOrdem" class="campo-form" value="0" min="0">
                </div>

                <div class="modal-acoes">
                    <button type="button" class="btn-cancelar" onclick="fecharModalCategoria()">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn-salvar">
                        <i class="fas fa-save"></i> Salvar Categoria
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
/* ===== ESTILOS DA GALERIA ADMIN ===== */
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
    grid-template-columns: repeat(4, 1fr);
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
    transition: transform 0.2s;
}

.stat-card:hover { transform: translateY(-3px); }

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
.stat-icon.blue { background: #e3f2fd; color: #0288d1; }
.stat-icon.green { background: #e8f5e9; color: #2e7d32; }
.stat-icon.orange { background: #fff3e0; color: #ed6c02; }

.stat-info h3 { font-size: 28px; font-weight: 700; margin: 0; }
.stat-info p { font-size: 13px; color: #6c757d; margin: 5px 0 0; }

/* Seção Categorias */
.secao-categorias {
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

.secao-header-left {
    display: flex;
    align-items: center;
    gap: 10px;
}

.secao-header-left i { color: #0a9396; font-size: 20px; }

.secao-header-acoes {
    display: flex;
    align-items: center;
    gap: 10px;
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

.btn-toggle-categorias {
    background: #f0f0f0;
    border: none;
    width: 36px;
    height: 36px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-toggle-categorias:hover { background: #e0e0e0; }
.btn-toggle-categorias.collapsed i { transform: rotate(180deg); }

.categorias-container {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.4s ease-in-out;
}

.categorias-container.expanded {
    max-height: 2000px; /* Altura suficiente para qualquer número de cards */
}

.btn-adicionar {
    background: #e8f5e9;
    border: none;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    color: #2e7d32;
    transition: all 0.2s;
}

.btn-adicionar:hover {
    background: #c8e6c9;
    transform: translateY(-2px);
}

.grid-categorias {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 15px;
}

.card-categoria {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 15px;
    display: flex;
    align-items: center;
    gap: 12px;
    border-left: 4px solid;
    transition: all 0.2s;
}

.card-categoria:hover {
    transform: translateX(3px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.card-categoria-icon {
    width: 45px;
    height: 45px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
}

.card-categoria-info { flex: 1; }
.card-categoria-info h3 { font-size: 14px; font-weight: 600; margin: 0; }

.card-categoria-cor .cor-preview {
    width: 30px;
    height: 30px;
    border-radius: 8px;
    display: block;
}

.card-categoria-acoes {
    display: flex;
    gap: 8px;
    opacity: 0.6;
    transition: opacity 0.2s;
}

.card-categoria:hover .card-categoria-acoes { opacity: 1; }

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

.filtros-title { display: flex; align-items: center; gap: 10px; }
.filtros-title i { color: #0a9396; }
.filtros-title h3 { font-size: 16px; margin: 0; }

.btn-toggle-filtros {
    background: none;
    border: none;
    cursor: pointer;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    transition: all 0.2s;
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

.btn-filter { background: #0a9396; color: white; }
.btn-clear { background: #f0f0f0; color: #666; }

/* Galeria Grid */
.galeria-container {
    background: white;
    border-radius: 20px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.galeria-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #eef2f6;
}

.galeria-titulo {
    font-size: 18px;
    font-weight: 600;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.galeria-titulo .contador {
    font-size: 13px;
    font-weight: normal;
    color: #6c757d;
}

.galeria-acoes { display: flex; gap: 10px; }

.btn-acao {
    padding: 8px 16px;
    background: #f0f0f0;
    border: none;
    border-radius: 8px;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-acao:hover { background: #0a9396; color: white; }

.galeria-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
}

.galeria-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    border: 1px solid #eef2f6;
    transition: all 0.3s;
    position: relative;
}

.galeria-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.1);
}

.card-preview {
    height: 180px;
    overflow: hidden;
    position: relative;
    background: #f0f0f0;
}

.card-preview img, .card-preview video {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s;
}

.galeria-card:hover .card-preview img,
.galeria-card:hover .card-preview video { transform: scale(1.05); }

.video-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,0.3);
    display: flex;
    align-items: center;
    justify-content: center;
}

.video-overlay i { font-size: 48px; color: white; }

.card-info { padding: 15px; }

.card-categoria {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    margin-bottom: 10px;
}

.card-legenda {
    font-size: 13px;
    color: #4a5a6e;
    margin-bottom: 12px;
    line-height: 1.5;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.card-acoes {
    display: flex;
    gap: 8px;
}

.btn-editar, .btn-eliminar {
    padding: 6px 12px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 12px;
    transition: all 0.2s;
}

.btn-editar { background: #e3f2fd; color: #0288d1; }
.btn-editar:hover { background: #0288d1; color: white; }

.btn-eliminar { background: #fee2e2; color: #dc3545; }
.btn-eliminar:hover { background: #dc3545; color: white; }

.card-handle {
    position: absolute;
    top: 10px;
    left: 10px;
    width: 32px;
    height: 32px;
    background: rgba(0,0,0,0.5);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: grab;
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
    max-width: 600px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-categoria { max-width: 550px; }

.modal-cabecalho {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 25px;
    border-bottom: 1px solid #eef2f6;
}

.modal-cabecalho h2 { margin: 0; font-size: 20px; display: flex; align-items: center; gap: 10px; }

.modal-fechar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: none;
    border: none;
    cursor: pointer;
    font-size: 18px;
    transition: all 0.2s;
}

.modal-fechar:hover { background: #f0f0f0; transform: rotate(90deg); }

.modal-corpo { padding: 25px; }

.grupo-form { margin-bottom: 20px; }
.grupo-form label { display: block; font-weight: 600; margin-bottom: 8px; font-size: 13px; }

.linha-form {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
    margin-bottom: 15px;
}

.campo-form {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid #e0e4e8;
    border-radius: 12px;
    font-size: 14px;
    transition: all 0.2s;
}

.campo-form:focus { outline: none; border-color: #0a9396; }

.area-upload {
    border: 2px dashed #cbd5e1;
    border-radius: 16px;
    padding: 30px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
}

.area-upload:hover { border-color: #0a9396; background: #f0fdfa; }
.area-upload i { font-size: 48px; color: #0a9396; margin-bottom: 10px; }

.preview-arquivo {
    margin-top: 15px;
    padding: 12px 15px;
    background: #f0fdfa;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.tipo-opcoes {
    display: flex;
    gap: 15px;
}

.tipo-opcao {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px;
    border: 2px solid #e0e4e8;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s;
}

.tipo-opcao.selecionado {
    border-color: #0a9396;
    background: rgba(10,147,150,0.05);
    color: #0a9396;
}

.tipo-opcao input { display: none; }

.cor-preview {
    height: 30px;
    border-radius: 8px;
    margin-top: 8px;
}

.icone-preview {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
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

.btn-cancelar { background: #f0f0f0; color: #666; }
.btn-salvar { background: #0a9396; color: white; }
.btn-salvar:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(10,147,150,0.3); }

.info-texto { font-size: 11px; color: #6c757d; margin-top: 5px; display: block; }

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

.botao-cancelar-modal { background: #f0f0f0; color: #666; }
.botao-confirmar-modal { background: #dc3545; color: white; }

.empty-state {
    grid-column: 1 / -1;
    text-align: center;
    padding: 60px;
}

.empty-state i { font-size: 64px; color: #cbd5e1; margin-bottom: 20px; display: block; }
.empty-state h3 { font-size: 20px; margin-bottom: 10px; }
.empty-state p { color: #6c757d; margin-bottom: 20px; }

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

.notificacao.erro { background: linear-gradient(135deg, #dc3545, #c82333); }

@keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0%); opacity: 1; }
}

@media (max-width: 992px) {
    .stats-grid { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 768px) {
    .stats-grid { grid-template-columns: 1fr; }
    .galeria-grid { grid-template-columns: 1fr; }
    .grid-categorias { grid-template-columns: 1fr; }
    .linha-form { grid-template-columns: 1fr; }
    .filtros-grid { grid-template-columns: 1fr; }
}
</style>

<script>
// ===================================================
// VARIÁVEIS GLOBAIS
// ===================================================
let modoOrdenacao = false;
let draggingItem = null;
let arquivoFile = null;

// ===================================================
// NOTIFICAÇÕES
// ===================================================
function mostrarNotificacao(mensagem, tipo = 'sucesso') {
    const notif = document.createElement('div');
    notif.className = `notificacao ${tipo}`;
    const icone = tipo === 'sucesso' ? 'fa-check-circle' : tipo === 'erro' ? 'fa-exclamation-circle' : 'fa-info-circle';
    notif.innerHTML = `<i class="fas ${icone}"></i> ${mensagem}`;
    document.body.appendChild(notif);
    setTimeout(() => notif.remove(), 3000);
}

// ===================================================
// TOGGLE CATEGORIAS (cresce conforme o conteúdo)
// ===================================================
document.getElementById('btnToggleCategorias')?.addEventListener('click', function() {
    const container = document.getElementById('categoriasContainer');
    container.classList.toggle('expanded');
    this.classList.toggle('collapsed');
});

// Inicializar categorias container como expandido
document.getElementById('categoriasContainer')?.classList.add('expanded');

// ===================================================
// FUNÇÕES DE UPLOAD
// ===================================================
function toggleTipoMidia() {
    const tipoSelecionado = document.querySelector('input[name="tipo_midia"]:checked')?.value;
    const uploadInfo = document.getElementById('uploadInfo');
    const urlExternaDiv = document.getElementById('secaoUrlExterna');
    const arquivoInput = document.getElementById('arquivoInput');
    
    if (tipoSelecionado === 'video') {
        uploadInfo.innerHTML = 'Formatos: MP4, WebM, OGG | Máx: 50MB';
        arquivoInput.accept = 'video/*';
        urlExternaDiv.style.display = 'block';
    } else {
        uploadInfo.innerHTML = 'Formatos: JPG, PNG, GIF, WEBP | Máx: 5MB';
        arquivoInput.accept = 'image/*';
        urlExternaDiv.style.display = 'block';
    }
}

function previewArquivo(input) {
    const file = input.files[0];
    if (!file) return;
    
    const maxSize = file.type.startsWith('video/') ? 50 * 1024 * 1024 : 5 * 1024 * 1024;
    if (file.size > maxSize) {
        mostrarNotificacao(`Arquivo excede o limite de ${maxSize / 1024 / 1024}MB`, 'erro');
        input.value = '';
        return;
    }
    
    arquivoFile = file;
    const preview = document.getElementById('previewArquivo');
    const previewNome = document.getElementById('previewNome');
    const previewTamanho = document.getElementById('previewTamanho');
    const previewIcone = document.getElementById('previewIcone');
    
    previewNome.textContent = file.name;
    previewTamanho.textContent = `(${(file.size / 1024 / 1024).toFixed(1)} MB)`;
    previewIcone.className = file.type.startsWith('video/') ? 'fas fa-video' : 'fas fa-image';
    preview.style.display = 'flex';
}

function removerArquivo() {
    document.getElementById('arquivoInput').value = '';
    document.getElementById('previewArquivo').style.display = 'none';
    arquivoFile = null;
}

// ===================================================
// FUNÇÃO PARA OBTER URL CORRETA DA MÍDIA
// ===================================================
function obterUrlMidia(url) {
    if (!url) return '';
    if (url.startsWith('http://') || url.startsWith('https://')) {
        return url;
    }
    if (url.startsWith('/uploads/')) {
        return '..' + url;
    }
    if (url.startsWith('../uploads/')) {
        return url;
    }
    return '../' + url;
}

// ===================================================
// CRUD MÍDIAS
// ===================================================
async function salvarMidia(event) {
    event.preventDefault();
    
    const id = document.getElementById('midiaId').value;
    const categoria = document.getElementById('midiaCategoria').value;
    const tipo = document.querySelector('input[name="tipo_midia"]:checked')?.value;
    const legenda = document.getElementById('midiaLegenda').value;
    const ordem = document.getElementById('midiaOrdem').value;
    const urlExterna = document.getElementById('urlExterna').value;
    
    if (!categoria) {
        mostrarNotificacao('Selecione uma categoria', 'erro');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'salvar');
    if (id) formData.append('id', id);
    formData.append('categoria_id', categoria);
    formData.append('tipo', tipo);
    formData.append('legenda', legenda);
    formData.append('ordem', ordem);
    
    if (arquivoFile) {
        formData.append('arquivo', arquivoFile);
    } else if (urlExterna) {
        formData.append('url', urlExterna);
    } else if (!id) {
        mostrarNotificacao('Selecione um arquivo ou informe uma URL', 'erro');
        return;
    }
    
    try {
        const response = await fetch('processos/processar-galeria.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            mostrarNotificacao(data.message, 'sucesso');
            fecharModalMidia();
            setTimeout(() => location.reload(), 1000);
        } else {
            mostrarNotificacao(data.message, 'erro');
        }
    } catch (error) {
        mostrarNotificacao('Erro ao salvar', 'erro');
    }
}

function editarMidia(id) {
    fetch(`processos/processar-galeria.php?action=buscar&id=${id}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const m = data.midia;
                document.getElementById('modalTituloMidia').innerHTML = '<i class="fas fa-edit"></i> Editar Mídia';
                document.getElementById('midiaId').value = m.id;
                document.getElementById('midiaCategoria').value = m.categoria_id;
                document.querySelector(`input[name="tipo_midia"][value="${m.tipo}"]`).checked = true;
                document.getElementById('midiaLegenda').value = m.legenda || '';
                document.getElementById('midiaOrdem').value = m.ordem || 0;
                toggleTipoMidia();
                document.getElementById('modalMidia').classList.add('ativo');
            }
        });
}

function eliminarMidia(id) {
    const modal = document.getElementById('modalConfirmacao');
    document.getElementById('confirmacaoTitulo').textContent = 'Eliminar Mídia';
    document.getElementById('confirmacaoTexto').textContent = 'Tem certeza que deseja eliminar esta mídia permanentemente?';
    
    const confirmar = document.getElementById('btnConfirmarAcao');
    const novoConfirmar = confirmar.cloneNode(true);
    confirmar.parentNode.replaceChild(novoConfirmar, confirmar);
    
    novoConfirmar.onclick = async () => {
        try {
            const response = await fetch('processos/processar-galeria.php', {
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

function abrirModalMidia() {
    document.getElementById('modalTituloMidia').innerHTML = '<i class="fas fa-plus"></i> Nova Mídia';
    document.getElementById('formMidia').reset();
    document.getElementById('midiaId').value = '';
    document.getElementById('previewArquivo').style.display = 'none';
    document.getElementById('urlExterna').value = '';
    document.querySelector('input[name="tipo_midia"][value="imagem"]').checked = true;
    document.querySelectorAll('.tipo-opcao').forEach(opt => opt.classList.remove('selecionado'));
    document.querySelector('.tipo-opcao:first-child').classList.add('selecionado');
    toggleTipoMidia();
    arquivoFile = null;
    document.getElementById('modalMidia').classList.add('ativo');
}

function fecharModalMidia() {
    document.getElementById('modalMidia').classList.remove('ativo');
}

// ===================================================
// CRUD CATEGORIAS (sem slug)
// ===================================================
function gerarSlug(texto) {
    return texto
        .toLowerCase()
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-|-$/g, '');
}

document.getElementById('categoriaCor')?.addEventListener('input', function() {
    document.getElementById('categoriaCorPreview').style.background = this.value;
});

document.getElementById('categoriaIcone')?.addEventListener('input', function() {
    const icone = this.value.trim();
    document.getElementById('iconePreviewCategoria').className = `fas ${icone}`;
    document.getElementById('iconePreviewText').textContent = icone;
});

async function salvarCategoria(event) {
    event.preventDefault();
    
    const id = document.getElementById('categoriaId').value;
    const nome = document.getElementById('categoriaNome').value;
    const cor = document.getElementById('categoriaCor').value;
    const icone = document.getElementById('categoriaIcone').value;
    const ordem = document.getElementById('categoriaOrdem').value;
    
    if (!nome) {
        mostrarNotificacao('Nome da categoria é obrigatório', 'erro');
        return;
    }
    
    const slug = gerarSlug(nome);
    
    const formData = new URLSearchParams();
    formData.append('action', id ? 'editar_categoria' : 'salvar_categoria');
    if (id) formData.append('id', id);
    formData.append('nome', nome);
    formData.append('slug', slug);
    formData.append('cor_classe', cor);
    formData.append('icone', icone);
    formData.append('ordem', ordem);
    
    try {
        const response = await fetch('processos/processar-galeria.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            mostrarNotificacao(data.message, 'sucesso');
            fecharModalCategoria();
            setTimeout(() => location.reload(), 1000);
        } else {
            mostrarNotificacao(data.message, 'erro');
        }
    } catch (error) {
        mostrarNotificacao('Erro ao salvar categoria', 'erro');
    }
}

function abrirModalCategoria(id = null) {
    const modal = document.getElementById('modalCategoria');
    const titulo = document.getElementById('modalTituloCategoria');
    
    document.getElementById('formCategoria').reset();
    document.getElementById('categoriaId').value = '';
    document.getElementById('categoriaCor').value = '#003072';
    document.getElementById('categoriaCorPreview').style.background = '#003072';
    document.getElementById('categoriaIcone').value = 'fa-tag';
    document.getElementById('iconePreviewCategoria').className = 'fas fa-tag';
    document.getElementById('iconePreviewText').textContent = 'fa-tag';
    document.getElementById('categoriaOrdem').value = '0';
    
    if (id) {
        titulo.innerHTML = '<i class="fas fa-edit"></i> Editar Categoria';
        fetch(`processos/processar-galeria.php?action=buscar_categoria&id=${id}`)
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const cat = data.categoria;
                    document.getElementById('categoriaId').value = cat.id;
                    document.getElementById('categoriaNome').value = cat.nome;
                    document.getElementById('categoriaCor').value = cat.cor_classe || '#003072';
                    document.getElementById('categoriaCorPreview').style.background = cat.cor_classe || '#003072';
                    document.getElementById('categoriaIcone').value = cat.icone || 'fa-tag';
                    document.getElementById('iconePreviewCategoria').className = `fas ${cat.icone || 'fa-tag'}`;
                    document.getElementById('iconePreviewText').textContent = cat.icone || 'fa-tag';
                    document.getElementById('categoriaOrdem').value = cat.ordem || 0;
                    modal.classList.add('ativo');
                }
            });
    } else {
        titulo.innerHTML = '<i class="fas fa-plus"></i> Nova Categoria';
        modal.classList.add('ativo');
    }
}

function editarCategoria(id) {
    abrirModalCategoria(id);
}

function eliminarCategoria(id) {
    const modal = document.getElementById('modalConfirmacao');
    document.getElementById('confirmacaoTitulo').textContent = 'Eliminar Categoria';
    document.getElementById('confirmacaoTexto').textContent = 'Tem certeza que deseja eliminar esta categoria? As mídias associadas ficarão sem categoria.';
    
    const confirmar = document.getElementById('btnConfirmarAcao');
    const novoConfirmar = confirmar.cloneNode(true);
    confirmar.parentNode.replaceChild(novoConfirmar, confirmar);
    
    novoConfirmar.onclick = async () => {
        try {
            const response = await fetch('processos/processar-galeria.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ action: 'eliminar_categoria', id: id })
            });
            const data = await response.json();
            if (data.success) {
                mostrarNotificacao(data.message, 'sucesso');
                location.reload();
            } else {
                mostrarNotificacao(data.message, 'erro');
            }
        } catch (error) {
            mostrarNotificacao('Erro ao eliminar categoria', 'erro');
        }
        modal.classList.remove('ativo');
    };
    modal.classList.add('ativo');
}

function fecharModalCategoria() {
    document.getElementById('modalCategoria').classList.remove('ativo');
}

// ===================================================
// FILTROS
// ===================================================
function toggleFiltros() {
    const form = document.getElementById('filtrosForm');
    const btn = document.getElementById('toggleFiltros');
    form.classList.toggle('collapsed');
    btn.classList.toggle('collapsed');
}

function aplicarFiltros() {
    const categoria = document.getElementById('filtroCategoria').value;
    const tipo = document.getElementById('filtroTipo').value;
    const busca = document.getElementById('campoBusca').value.toLowerCase();
    
    const cards = document.querySelectorAll('.galeria-card');
    let visiveis = 0;
    
    cards.forEach(card => {
        let mostrar = true;
        if (categoria && card.dataset.categoria !== categoria) mostrar = false;
        if (tipo && card.dataset.tipo !== tipo) mostrar = false;
        if (busca) {
            const legenda = card.querySelector('.card-legenda')?.textContent.toLowerCase() || '';
            if (!legenda.includes(busca)) mostrar = false;
        }
        card.style.display = mostrar ? '' : 'none';
        if (mostrar) visiveis++;
    });
    
    document.getElementById('contadorMidias').innerHTML = `(${visiveis} itens)`;
}

function limparFiltros() {
    document.getElementById('filtroCategoria').value = '';
    document.getElementById('filtroTipo').value = '';
    document.getElementById('campoBusca').value = '';
    aplicarFiltros();
}

// ===================================================
// ORDENAÇÃO
// ===================================================
function ordenarGaleria() {
    modoOrdenacao = !modoOrdenacao;
    const cards = document.querySelectorAll('.galeria-card');
    const btnOrdenar = document.getElementById('btnOrdenar');
    const btnGuardar = document.getElementById('btnGuardarOrdem');
    const btnCancelar = document.getElementById('btnCancelarOrdem');
    
    if (modoOrdenacao) {
        btnOrdenar.style.display = 'none';
        btnGuardar.style.display = 'flex';
        btnCancelar.style.display = 'flex';
        cards.forEach(card => {
            card.style.cursor = 'grab';
            const handle = card.querySelector('.card-handle');
            if (handle) handle.style.display = 'flex';
            card.setAttribute('draggable', 'true');
        });
        iniciarDragDrop();
    } else {
        btnOrdenar.style.display = 'flex';
        btnGuardar.style.display = 'none';
        btnCancelar.style.display = 'none';
        cards.forEach(card => {
            card.style.cursor = '';
            const handle = card.querySelector('.card-handle');
            if (handle) handle.style.display = 'none';
            card.setAttribute('draggable', 'false');
        });
    }
}

function cancelarOrdenacao() {
    modoOrdenacao = false;
    document.getElementById('btnOrdenar').style.display = 'flex';
    document.getElementById('btnGuardarOrdem').style.display = 'none';
    document.getElementById('btnCancelarOrdem').style.display = 'none';
    document.querySelectorAll('.galeria-card').forEach(card => {
        card.style.cursor = '';
        card.setAttribute('draggable', 'false');
        const handle = card.querySelector('.card-handle');
        if (handle) handle.style.display = 'none';
    });
}

function iniciarDragDrop() {
    const cards = document.querySelectorAll('.galeria-card');
    
    cards.forEach(card => {
        card.addEventListener('dragstart', (e) => {
            draggingItem = card;
            card.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', card.dataset.id);
        });
        
        card.addEventListener('dragend', () => {
            draggingItem = null;
            card.classList.remove('dragging');
            document.querySelectorAll('.galeria-card').forEach(c => c.classList.remove('drag-over'));
        });
        
        card.addEventListener('dragover', (e) => {
            e.preventDefault();
            if (!draggingItem || draggingItem === card) return;
            card.classList.add('drag-over');
        });
        
        card.addEventListener('dragleave', () => {
            card.classList.remove('drag-over');
        });
        
        card.addEventListener('drop', (e) => {
            e.preventDefault();
            if (!draggingItem || draggingItem === card) return;
            const grid = document.getElementById('galeriaGrid');
            const rect = card.getBoundingClientRect();
            const mouseY = e.clientY;
            const cardMiddle = rect.top + rect.height / 2;
            
            if (mouseY > cardMiddle) {
                grid.insertBefore(draggingItem, card.nextSibling);
            } else {
                grid.insertBefore(draggingItem, card);
            }
            card.classList.remove('drag-over');
        });
    });
}

async function guardarOrdem() {
    const cards = document.querySelectorAll('.galeria-card');
    const ordem = [];
    cards.forEach((card, index) => {
        ordem.push({ id: card.dataset.id, ordem: index + 1 });
    });
    
    try {
        const response = await fetch('processos/processar-galeria.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'ordenar', ordem: ordem })
        });
        const data = await response.json();
        if (data.success) {
            mostrarNotificacao('Ordem salva com sucesso!', 'sucesso');
            cancelarOrdenacao();
        } else {
            mostrarNotificacao(data.message, 'erro');
        }
    } catch (error) {
        mostrarNotificacao('Erro ao salvar ordem', 'erro');
    }
}

// ===================================================
// EVENTOS E INICIALIZAÇÃO
// ===================================================
document.getElementById('btnNovaMidia')?.addEventListener('click', abrirModalMidia);
document.getElementById('toggleFiltros')?.addEventListener('click', toggleFiltros);
document.getElementById('btnAplicarFiltros')?.addEventListener('click', aplicarFiltros);
document.getElementById('btnLimparFiltros')?.addEventListener('click', limparFiltros);
document.getElementById('btnOrdenar')?.addEventListener('click', ordenarGaleria);
document.getElementById('btnGuardarOrdem')?.addEventListener('click', guardarOrdem);
document.getElementById('btnCancelarOrdem')?.addEventListener('click', cancelarOrdenacao);
document.getElementById('btnCancelarConfirmacao')?.addEventListener('click', () => {
    document.getElementById('modalConfirmacao').classList.remove('ativo');
});

document.getElementById('modalMidia')?.addEventListener('click', (e) => {
    if (e.target === document.getElementById('modalMidia')) fecharModalMidia();
});
document.getElementById('modalCategoria')?.addEventListener('click', (e) => {
    if (e.target === document.getElementById('modalCategoria')) fecharModalCategoria();
});
document.getElementById('modalConfirmacao')?.addEventListener('click', (e) => {
    if (e.target === document.getElementById('modalConfirmacao')) {
        document.getElementById('modalConfirmacao').classList.remove('ativo');
    }
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        fecharModalMidia();
        fecharModalCategoria();
        document.getElementById('modalConfirmacao').classList.remove('ativo');
    }
});

document.querySelectorAll('.tipo-opcao').forEach(opt => {
    opt.addEventListener('click', function() {
        document.querySelectorAll('.tipo-opcao').forEach(o => o.classList.remove('selecionado'));
        this.classList.add('selecionado');
        this.querySelector('input').checked = true;
        toggleTipoMidia();
    });
});

// Inicializar
document.getElementById('campoBusca')?.addEventListener('input', aplicarFiltros);
document.getElementById('filtroCategoria')?.addEventListener('change', aplicarFiltros);
document.getElementById('filtroTipo')?.addEventListener('change', aplicarFiltros);
</script>

<?php include 'includes/footer.php'; ?>
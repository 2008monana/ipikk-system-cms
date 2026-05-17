<?php
/**
 * Notícias - Área Restrita IPIKK
 */

$titulo_pagina = 'Notícias';
$css_especifico = 'admin-noticias.css';

// Caminho correto - sobe um nível para acessar a pasta config
require_once dirname(__DIR__) . '/config/index.php';

// Verificar se está logado (o init.php já faz isso)
if (!isset($_SESSION['utilizador_id'])) {
    header('Location: area-restrita.php');
    exit;
}

require_once __DIR__ . '/includes/verificar-permissao.php';

// ===== VERIFICAR PERMISSÃO ESPECÍFICA =====
verificarPermissao('noticias');

$db = getDB();

// Buscar dados do usuário para o perfil
$stmt = $db->prepare("SELECT id, nome, email, foto_url FROM utilizadores WHERE id = ?");
$stmt->execute([$_SESSION['utilizador_id']]);
$usuario_logado = $stmt->fetch();

if (!$usuario_logado) {
    session_destroy();
    header('Location: area-restrita.php');
    exit;
}

// Buscar configurações
$config = $db->query("SELECT * FROM configuracoes WHERE id = 1")->fetch();

// ===== BUSCAR NOTÍCIAS DO BANCO =====
$stmt = $db->query("
    SELECT id, titulo, resumo, conteudo, categoria, tipo_midia, 
           imagem_url, video_file, alt_text, autor, tags, 
           data_publicacao, estado, destaque_principal, visualizacoes
    FROM noticias 
    ORDER BY created_at DESC
");
$noticias = $stmt->fetchAll();

// Incluir header e sidebar
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<main class="conteudo-principal">
    <header class="barra-superior" id="barraSuperior">
        <div class="esquerda-barra">
            <button class="botao-menu-mobile" id="botaoMenuMobile" onclick="window.openSidebar && window.openSidebar()">
            <i class="fas fa-bars"></i>
        </button>
            <h1 class="titulo-pagina" style="color: #003072;">Notícias</h1>
        </div>
        
        <div class="direita-barra">
            
            <button class="botao-primario" id="botaoNovaNoticia">
                <i class="fas fa-plus"></i>
                <span>Nova Notícia</span>
            </button>
        </div>
    </header>

    <div class="conteudo-pagina">
        <!-- FILTROS -->
        <section class="secao-filtros">
            <h2 class="titulo-secao"><i class="fas fa-search"></i> Buscar & Filtrar</h2>
            <div class="grade-filtros">
                <div class="item-filtro largura-total">
                    <div class="caixa-busca">
                        <i class="fas fa-search"></i>
                        <input type="text" id="campoBusca" placeholder="Buscar por título...">
                    </div>
                </div>
                
                <div class="item-filtro">
                    <label>Estado</label>
                    <select id="filtroEstado" class="selecao-form">
                        <option value="">Todos</option>
                        <option value="publicada">Publicadas</option>
                        <option value="rascunho">Rascunhos</option>
                        <option value="arquivada">Arquivadas</option>
                    </select>
                </div>
                
                <div class="item-filtro">
                    <label>Categoria</label>
                    <select id="filtroCategoria" class="selecao-form">
                        <option value="">Todas</option>
                        <option value="DESTAQUE">Destaque</option>
                        <option value="CURSOS">Cursos</option>
                        <option value="EVENTOS">Eventos</option>
                        <option value="PARCERIA">Parceria</option>
                        <option value="INSTITUCIONAL">Institucional</option>
                    </select>
                </div>
                
                <div class="item-filtro">
                    <label>Data Inicial</label>
                    <input type="date" id="filtroDataInicio" class="entrada-form">
                </div>
                
                <div class="item-filtro">
                    <label>Data Final</label>
                    <input type="date" id="filtroDataFim" class="entrada-form">
                </div>
                
                <div class="item-filtro">
                    <label>Ordenar por</label>
                    <select id="filtroOrdenar" class="selecao-form">
                        <option value="data-desc">Data (Mais recentes)</option>
                        <option value="data-asc">Data (Mais antigas)</option>
                        <option value="titulo-asc">Título (A-Z)</option>
                        <option value="titulo-desc">Título (Z-A)</option>
                    </select>
                </div>
            </div>
        </section>

        <!-- LISTA DE NOTÍCIAS -->
        <section class="secao-conteudo">
            <div class="cabecalho-secao">
                <h2 class="titulo-secao"><i class="fas fa-list"></i> Lista de Notícias</h2>
            </div>
            
            <div class="tabela-responsiva">
                <table class="tabela-dados" id="tabelaNoticias">
                    <thead>
                        <tr>
                            <th width="40"><input type="checkbox" id="selecionarTodos" class="checkbox-linha"></th>
                            <th>TÍTULO</th>
                            <th width="120">DATA</th>
                            <th width="120">ESTADO</th>
                            <th width="150">AÇÕES</th>
                        </thead>
                    <tbody id="corpoTabelaNoticias">
                        <?php foreach($noticias as $noticia): ?>
                        <tr data-id="<?php echo $noticia['id']; ?>" data-estado="<?php echo $noticia['estado']; ?>" data-categoria="<?php echo $noticia['categoria']; ?>">
                            <td><input type="checkbox" class="checkbox-linha" data-id="<?php echo $noticia['id']; ?>"></td>
                            <td>
                                <?php if($noticia['destaque_principal']): ?>
                                <span class="badge-star"><i class="fas fa-star"></i></span>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($noticia['titulo']); ?>
                            </td>
                            <td><?php echo formatarData($noticia['data_publicacao']); ?></td>
                            <td>
                                <?php
                                $classeBadge = 'badge-success';
                                $iconeBadge = 'fa-check-circle';
                                $textoBadge = 'Publicada';
                                
                                if($noticia['estado'] == 'rascunho') {
                                    $classeBadge = 'badge-warning';
                                    $iconeBadge = 'fa-clock';
                                    $textoBadge = 'Rascunho';
                                } elseif($noticia['estado'] == 'arquivada') {
                                    $classeBadge = 'badge-info';
                                    $iconeBadge = 'fa-archive';
                                    $textoBadge = 'Arquivada';
                                }
                                ?>
                                <span class="badge <?php echo $classeBadge; ?>"><i class="fas <?php echo $iconeBadge; ?>"></i> <?php echo $textoBadge; ?></span>
                            </td>
                            <td>
                                <div class="botoes-acao">
                                    <button class="botao-icone" onclick="visualizarNoticia(<?php echo $noticia['id']; ?>)" title="Visualizar"><i class="fas fa-eye"></i></button>
                                    <button class="botao-icone" onclick="editarNoticia(<?php echo $noticia['id']; ?>)" title="Editar"><i class="fas fa-edit"></i></button>
                                    <button class="botao-icone botao-perigo" onclick="eliminarNoticia(<?php echo $noticia['id']; ?>)" title="Eliminar"><i class="fas fa-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- PAGINAÇÃO -->
            <div class="paginacao">
                <div class="informacoes-pagina">
                    <button class="botao-pagina" id="botaoAnterior" disabled>
                        <i class="fas fa-chevron-left"></i> Anterior
                    </button>
                    <span class="texto-pagina">Página <strong id="paginaAtual">1</strong> de <strong id="totalPaginas">1</strong></span>
                    <button class="botao-pagina" id="botaoProximo" disabled>
                        Próximo <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
                <div class="seletor-pagina">
                    <select id="itensPorPagina" class="selecao-form">
                        <option value="10">10 por página</option>
                        <option value="25">25 por página</option>
                        <option value="50">50 por página</option>
                    </select>
                </div>
            </div>
        </section>

        <!-- RESUMO DE NOTÍCIAS -->
        <section class="secao-conteudo">
            <h2 class="titulo-secao"><i class="fas fa-chart-pie"></i> Resumo de Notícias</h2>
            <div class="grade-resumo">
                <div class="item-resumo">
                    <span class="rotulo-resumo">Total:</span>
                    <span class="valor-resumo" id="resumoTotal"><?php echo count($noticias); ?></span>
                </div>
                <div class="item-resumo">
                    <span class="rotulo-resumo">Publicadas:</span>
                    <span class="valor-resumo" id="resumoPublicadas"><?php echo count(array_filter($noticias, function($n) { return $n['estado'] == 'publicada'; })); ?></span>
                </div>
                <div class="item-resumo">
                    <span class="rotulo-resumo">Rascunhos:</span>
                    <span class="valor-resumo" id="resumoRascunhos"><?php echo count(array_filter($noticias, function($n) { return $n['estado'] == 'rascunho'; })); ?></span>
                </div>
                <div class="item-resumo">
                    <span class="rotulo-resumo">Arquivadas:</span>
                    <span class="valor-resumo" id="resumoArquivadas"><?php echo count(array_filter($noticias, function($n) { return $n['estado'] == 'arquivada'; })); ?></span>
                </div>
            </div>
        </section>

        <!-- AÇÕES EM MASSA -->
        <section class="secao-conteudo">
            <h2 class="titulo-secao"><i class="fas fa-bolt"></i> Ações em Massa</h2>
            <div class="acoes-massa">
                <button class="botao-acao-massa" id="botaoSelecionarTodas">
                    <i class="fas fa-check-square"></i> Selecionar Todas
                </button>
                <button class="botao-acao-massa botao-perigo" id="botaoEliminarSelecionadas" disabled>
                    <i class="fas fa-trash"></i> Eliminar Selecionadas
                </button>
                <button class="botao-acao-massa" id="botaoExportarCSV" disabled>
                    <i class="fas fa-file-export"></i> Exportar CSV
                </button>
                <button class="botao-acao-massa" id="botaoPublicarSelecionadas" disabled>
                    <i class="fas fa-paper-plane"></i> Publicar Selecionadas
                </button>
                
                <button class="botao-acao-massa" id="botaoImportarCSV">
                    <i class="fas fa-file-import"></i> Importar CSV
                </button>
            </div>
        </section>
    </div>
</main>

<!-- MODAL DE IMPORTAÇÃO CSV -->
<div id="modalImportarCSV" class="modal-importar">
    <div class="modal-importar-conteudo">
        <div class="modal-importar-cabecalho">
            <h3><i class="fas fa-file-import"></i> Importar Notícias (CSV)</h3>
            <button class="modal-importar-fechar" onclick="fecharModalImportar()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-importar-corpo">
            <div class="aviso-importacao">
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    <strong>Atenção!</strong><br>
                    A importação irá <strong>adicionar novas notícias</strong> sem apagar as existentes.<br>
                    Para evitar duplicatas, recomenda-se exportar antes e verificar os dados.
                </div>
            </div>
            
            <div class="grupo-form">
                <label><i class="fas fa-file-csv"></i> Selecione o arquivo CSV</label>
                <div class="area-upload-csv" onclick="document.getElementById('inputCSV').click()">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <p><strong>Clique para selecionar o arquivo CSV</strong></p>
                    <small>Formatos: .csv | Máximo: 5MB</small>
                </div>
                <input type="file" id="inputCSV" accept=".csv" style="display: none;">
                <div class="previa-arquivo" id="previaArquivo" style="display: none;">
                    <div>
                        <strong id="nomeArquivoCSV"></strong><br>
                        <small id="tamanhoArquivoCSV"></small>
                    </div>
                    <button type="button" class="botao-icone" onclick="removerArquivoCSV()">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            
            <div class="opcoes-importacao">
                <label class="opcao-importacao">
                    <input type="checkbox" id="ignorarDuplicatas" checked>
                    <span>Ignorar notícias duplicadas (por título)</span>
                </label>
                <label class="opcao-importacao">
                    <input type="checkbox" id="atualizarExistentes">
                    <span>Atualizar notícias existentes (se título igual)</span>
                </label>
            </div>
            
            <div class="preview-csv" id="previewCSV" style="display: none;">
                <h4><i class="fas fa-list"></i> Pré-visualização dos dados</h4>
                <div class="tabela-preview" id="tabelaPreview"></div>
                <div class="resumo-importacao" id="resumoImportacao"></div>
            </div>
        </div>
        <div class="modal-importar-rodape">
            <button class="botao-cancelar" onclick="fecharModalImportar()">
                <i class="fas fa-times"></i> Cancelar
            </button>
            <button class="botao-importar" id="botaoConfirmarImportar" disabled>
                <i class="fas fa-file-import"></i> Importar Notícias
            </button>
        </div>
    </div>
</div>

<!-- MODAL DE VISUALIZAÇÃO RÁPIDA -->
<div id="modalVisualizarNoticia" class="modal-visualizar">
    <div class="modal-visualizar-conteudo">
        <div class="modal-visualizar-cabecalho">
            <h3><i class="fas fa-eye"></i> Visualizar Notícia</h3>
            <button class="modal-visualizar-fechar" onclick="fecharModalVisualizar()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-visualizar-corpo" id="visualizarCorpo">
            <div class="loading-visualizar">
                <i class="fas fa-spinner fa-spin"></i> Carregando...
            </div>
        </div>
        <div class="modal-visualizar-rodape">
            <button class="botao-cancelar" onclick="fecharModalVisualizar()">
                <i class="fas fa-times"></i> Fechar
            </button>
        </div>
    </div>
</div>

<!-- MODAL DE CONFIRMAÇÃO -->
<div id="modalConfirmacao" class="modal-confirmacao">
    <div class="modal-confirmacao-caixa">
        <div class="modal-confirmacao-icone" id="modalConfirmacaoIconeWrapper">
            <i class="fas fa-paper-plane" id="modalConfirmacaoIcone"></i>
        </div>
        <h3 id="modalConfirmacaoTitulo">Deseja eliminar a notícia?</h3>
        <p id="modalConfirmacaoTexto">Esta ação moverá a notícia para a lixeira.</p>
        <div class="modal-confirmacao-botoes">
            <button type="button" class="botao-cancelar" id="botaoCancelarConfirmacao">
                <i class="fas fa-times"></i> Cancelar
            </button>
            <button type="button" class="botao-perigo-confirmacao" id="botaoConfirmarAcao">
                <i class="fas fa-paper-plane" id="modalConfirmacaoBotaoIcone"></i> <span id="modalConfirmacaoBotaoTexto">Publicar</span>
            </button>
        </div>
    </div>
</div>

<!-- MODAL NOVA/EDITAR NOTÍCIA -->
<div id="modalNoticia" class="modal">
    <div class="conteudo-modal">
        <div class="cabecalho-modal">
            <div class="esquerda-cabecalho">
                <div class="icone-cabecalho"><i class="fas fa-newspaper"></i></div>
                <div class="texto-cabecalho">
                    <h2 id="modalTitulo">Nova Notícia</h2>
                    <p id="modalSubtitulo">Preencha os dados da notícia</p>
                </div>
            </div>
            <button class="botao-fechar" onclick="fecharModalNoticia()"><i class="fas fa-times"></i></button>
        </div>

        <div class="corpo-modal">
            <form id="formNoticia" method="POST" enctype="multipart/form-data">
                <input type="hidden" id="noticiaId" name="id">

                <!-- INFORMAÇÕES BÁSICAS -->
                <div class="secao-form">
                    <h3><i class="fas fa-info-circle"></i> Informações Básicas</h3>
                    
                    <div class="grupo-form">
                        <label><i class="fas fa-heading"></i> Título da Notícia *</label>
                        <input type="text" id="noticiaTitulo" name="titulo" class="campo-form" required placeholder="Ex: IPIKK abre inscrições para 2024">
                    </div>

                    <div class="linha-form">
                        <div class="grupo-form">
                            <label><i class="fas fa-tag"></i> Categoria *</label>
                            <select id="noticiaCategoria" name="categoria" class="campo-form" required>
                                <option value="">Selecione...</option>
                                <option value="DESTAQUE">Destaque</option>
                                <option value="CURSOS">Cursos</option>
                                <option value="EVENTOS">Eventos</option>
                                <option value="PARCERIA">Parceria</option>
                                <option value="INSTITUCIONAL">Institucional</option>
                            </select>
                        </div>

                        <div class="grupo-form">
                            <label><i class="fas fa-calendar"></i> Data de Publicação *</label>
                            <input type="date" id="noticiaData" name="data_publicacao" class="campo-form" required>
                        </div>
                    </div>

                    <div class="grupo-form">
                        <label><i class="fas fa-align-left"></i> Resumo (para listagens) *</label>
                        <textarea id="noticiaResumo" name="resumo" class="campo-form" rows="2" maxlength="200" required placeholder="Resumo curto que aparece no slider..."></textarea>
                        <div class="contador-caracteres" id="contadorResumo">0 / 200</div>
                    </div>

                    <div class="grupo-form">
                        <label><i class="fas fa-align-justify"></i> Conteúdo Completo *</label>
                        <textarea id="noticiaConteudo" name="conteudo" class="campo-form campo-alto" rows="6" required placeholder="Texto completo da notícia..."></textarea>
                    </div>
                </div>

                <!-- MÍDIA -->
                <div class="secao-form">
                    <h3><i class="fas fa-image"></i> Mídia</h3>

                    <div class="linha-form">
                        <div class="grupo-form">
                            <label><i class="fas fa-play-circle"></i> Tipo de Mídia</label>
                            <select id="noticiaTipoMidia" name="tipo_midia" class="campo-form">
                                <option value="imagem">📷 Imagem (Upload)</option>
                                <option value="video">🎥 Vídeo (Upload)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Upload de Imagem -->
                    <div id="secaoImagem">
                        <div class="grupo-form">
                            <label><i class="fas fa-cloud-upload-alt"></i> Upload de Imagem</label>
                            <div class="area-upload" onclick="document.getElementById('inputImagem').click()">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <p><strong>Clique para fazer upload da imagem</strong></p>
                                <small>Formatos: JPG, PNG, GIF, WebP | Tamanho máximo: 5MB</small>
                            </div>
                            <input type="file" id="inputImagem" name="imagem" accept="image/*" style="display: none;">
                            <div class="previa-imagem" id="previaImagem">
                                <div>
                                    <strong id="nomeImagem"></strong><br>
                                    <img id="miniaturaImagem" alt="Preview" style="max-width: 150px; max-height: 100px;">
                                </div>
                                <button type="button" class="botao-icone" onclick="removerImagemNoticia()"><i class="fas fa-trash"></i></button>
                            </div>
                        </div>
                    </div>

                    <!-- Upload de Vídeo -->
                    <div id="secaoVideo" style="display: none;">
                        <div class="grupo-form">
                            <label><i class="fas fa-cloud-upload-alt"></i> Upload de Vídeo</label>
                            <div class="area-upload" onclick="document.getElementById('inputVideo').click()">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <p><strong>Clique para fazer upload do vídeo</strong></p>
                                <small>Formatos: MP4, WebM, OGG | Tamanho máximo: 50MB</small>
                            </div>
                            <input type="file" id="inputVideo" name="video" accept="video/*" style="display: none;">
                            <div class="previa-imagem" id="previaVideo">
                                <div>
                                    <strong id="nomeVideo"></strong><br>
                                    <video id="miniaturaVideo" controls style="max-width: 200px; max-height: 120px;"></video>
                                </div>
                                <button type="button" class="botao-icone" onclick="removerVideoNoticia()"><i class="fas fa-trash"></i></button>
                            </div>
                        </div>

                        <div class="grupo-form">
                            <label><i class="fas fa-image"></i> Imagem Representativa do Vídeo (opcional)</label>
                            <div class="area-upload" onclick="document.getElementById('inputImagemCapaVideo').click()">
                                <i class="fas fa-photo-video"></i>
                                <p><strong>Clique para fazer upload da capa do vídeo</strong></p>
                                <small>Esta imagem será usada nos cards do Index e Notícias</small>
                            </div>
                            <input type="file" id="inputImagemCapaVideo" name="imagem_capa" accept="image/*" style="display: none;">
                            <div class="previa-imagem" id="previaImagemCapaVideo">
                                <div>
                                    <strong id="nomeImagemCapaVideo"></strong><br>
                                    <img id="miniaturaImagemCapaVideo" alt="Preview capa vídeo" style="max-width: 150px; max-height: 100px;">
                                </div>
                                <button type="button" class="botao-icone" onclick="removerImagemCapaVideo()"><i class="fas fa-trash"></i></button>
                            </div>
                        </div>
                    </div>

                    <div class="grupo-form">
                        <label><i class="fas fa-file-image"></i> Texto Alternativo (alt)</label>
                        <input type="text" id="noticiaAlt" name="alt_text" class="campo-form" placeholder="Descrição da mídia para acessibilidade">
                    </div>
                </div>

                <!-- AUTORIA -->
                <div class="secao-form">
                    <h3><i class="fas fa-user"></i> Autoria</h3>
                    <div class="grupo-form">
                        <label><i class="fas fa-user-circle"></i> Autor *</label>
                        <input type="text" id="noticiaAutor" name="autor" class="campo-form" required placeholder="Ex: Gabinete de Comunicação">
                    </div>
                </div>

                <!-- TAGS -->
                <div class="secao-form">
                    <h3><i class="fas fa-hashtag"></i> Tags</h3>
                    <div class="grupo-form">
                        <label>Tags (separadas por vírgula)</label>
                        <input type="text" id="noticiaTags" name="tags" class="campo-form" placeholder="Ex: Inscrições, Cursos, 2024">
                    </div>
                </div>

                <!-- CONFIGURAÇÕES -->
                <div class="secao-form">
                    <h3><i class="fas fa-cog"></i> Configurações</h3>
                    <div class="linha-form-3">
                        <div class="grupo-form">
                            <label><i class="fas fa-toggle-on"></i> Estado</label>
                            <select id="noticiaEstado" name="estado" class="campo-form">
                                <option value="publicada">Publicada</option>
                                <option value="rascunho">Rascunho</option>
                                <option value="arquivada">Arquivada</option>
                            </select>
                        </div>
                        <div class="grupo-form">
                            <label><i class="fas fa-star"></i> Destaque Principal</label>
                            <div class="container-toggle">
                                <label class="toggle">
                                    <input type="checkbox" id="noticiaDestaquePrincipal" name="destaque_principal" value="1">
                                    <span class="slider"></span>
                                </label>
                                <span style="font-size: 13px;">Destacar</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- AÇÕES - BOTÕES -->
                <div class="acoes-form">
                    <button type="button" class="botao-cancelar" onclick="fecharModalNoticia()">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="botao-salvar">
                        <i class="fas fa-save"></i> Salvar Notícia
                    </button>
                </div>

            </form>
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
    /* ===== ESTILOS DO FORMULÁRIO ===== */
    .acoes-form {
        display: flex;
        justify-content: flex-end;
        gap: 15px;
        margin-top: 30px;
        padding-top: 20px;
        border-top: 2px solid #e0e4e8;
    }
    
    .botao-salvar, .botao-cancelar {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 28px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.3s ease;
        border: none;
    }
    
    .botao-salvar {
        background: linear-gradient(135deg, #28a745, #218838);
        color: white;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    
    .botao-salvar:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
    }
    
    .botao-cancelar {
        background: #f8f9fa;
        color: #495057;
        border: 1px solid #dee2e6;
    }
    
    .botao-cancelar:hover {
        background: #e9ecef;
    }
    /* ===== MODAL DE VISUALIZAÇÃO RÁPIDA ===== */
    .modal-visualizar {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.85);
        backdrop-filter: blur(5px);
        z-index: 10001;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .modal-visualizar.ativo {
        display: flex;
    }

    .modal-visualizar-conteudo {
        background: white;
        border-radius: 16px;
        max-width: 800px;
        width: 100%;
        max-height: 90vh;
        display: flex;
        flex-direction: column;
        animation: slideInModal 0.3s ease;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
    }

    .modal-visualizar-cabecalho {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px 25px;
        border-bottom: 2px solid #e0e4e8;
        background: linear-gradient(135deg, #f8f9fa, #ffffff);
        border-radius: 16px 16px 0 0;
    }

    .modal-visualizar-cabecalho h3 {
        margin: 0;
        font-size: 1.3rem;
        color: #003072;
    }

    .modal-visualizar-cabecalho h3 i {
        color: #0a9396;
        margin-right: 10px;
    }

    .modal-visualizar-fechar {
        width: 35px;
        height: 35px;
        border-radius: 50%;
        border: none;
        background: #e9ecef;
        color: #6c757d;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .modal-visualizar-fechar:hover {
        background: #dc3545;
        color: white;
        transform: rotate(90deg);
    }

    .modal-visualizar-corpo {
        flex: 1;
        overflow-y: auto;
        padding: 25px;
        background: #fff;
    }

    .modal-visualizar-rodape {
        padding: 15px 25px;
        border-top: 1px solid #e0e4e8;
        display: flex;
        justify-content: flex-end;
        background: #f8f9fa;
        border-radius: 0 0 16px 16px;
    }

    .loading-visualizar {
        text-align: center;
        padding: 50px;
        color: #6c757d;
    }

    .loading-visualizar i {
        font-size: 2rem;
        margin-bottom: 10px;
        display: block;
    }

    .visualizar-noticia {
        line-height: 1.6;
    }

    .visualizar-noticia h2 {
        color: #003072;
        margin-bottom: 15px;
        font-size: 1.5rem;
    }

    .visualizar-noticia .meta {
        color: #6c757d;
        font-size: 0.85rem;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 1px solid #e0e4e8;
    }

    .visualizar-noticia .meta span {
        margin-right: 20px;
    }

    .visualizar-noticia .meta i {
        margin-right: 5px;
        color: #0a9396;
    }

    .visualizar-noticia .imagem {
        margin: 20px 0;
        text-align: center;
    }

    .visualizar-noticia .imagem img {
        max-width: 100%;
        max-height: 300px;
        border-radius: 8px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }

    .visualizar-noticia .imagem video {
        max-width: 100%;
        max-height: 300px;
        border-radius: 8px;
    }

    .visualizar-noticia .conteudo {
        margin-top: 20px;
        font-size: 0.95rem;
        line-height: 1.7;
        color: #2c3e50;
    }

    .visualizar-noticia .tags {
        margin-top: 20px;
        padding-top: 15px;
        border-top: 1px solid #e0e4e8;
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .visualizar-noticia .tag {
        background: #e9ecef;
        padding: 4px 12px;
        border-radius: 15px;
        font-size: 0.75rem;
        color: #495057;
    }

    .visualizar-noticia .badge-categoria {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 600;
        margin-bottom: 15px;
    }

    .visualizar-noticia .badge-categoria.DESTAQUE { background: #ffc107; color: #856404; }
    .visualizar-noticia .badge-categoria.CURSOS { background: #17a2b8; color: white; }
    .visualizar-noticia .badge-categoria.EVENTOS { background: #28a745; color: white; }
    .visualizar-noticia .badge-categoria.PARCERIA { background: #6f42c1; color: white; }
    .visualizar-noticia .badge-categoria.INSTITUCIONAL { background: #6c757d; color: white; }
    /* ===== ESTILOS PARA O MODAL ===== */
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.75);
        backdrop-filter: blur(4px);
        z-index: 10000;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }
    
    .modal.ativo {
        display: flex;
    }
    
    .conteudo-modal {
        background: white;
        border-radius: 16px;
        max-width: 800px;
        width: 100%;
        max-height: 92vh;
        overflow-y: auto;
        animation: slideInModal 0.3s ease;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
    }
    
    @keyframes slideInModal {
        from {
            opacity: 0;
            transform: translateY(-30px) scale(0.95);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }
    
    .secao-form {
        background: #f8fafc;
        border-radius: 12px;
        padding: 20px 25px;
        margin-bottom: 20px;
        border-left: 4px solid #0a9396;
    }
    
    .grupo-form {
        margin-bottom: 15px;
    }
    
    .grupo-form label {
        display: block;
        font-size: 12px;
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 6px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .campo-form {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid #e0e4e8;
        border-radius: 8px;
        font-size: 14px;
        transition: all 0.3s;
    }
    
    .campo-form:focus {
        outline: none;
        border-color: #0a9396;
        box-shadow: 0 0 0 3px rgba(10, 147, 150, 0.1);
    }
    
    .campo-form.campo-alto {
        min-height: 150px;
        resize: vertical;
    }
    
    .linha-form {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
        margin-bottom: 15px;
    }
    
    .linha-form-3 {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 15px;
        margin-bottom: 15px;
    }
    
    .area-upload {
        border: 2px dashed #cbd5e0;
        border-radius: 12px;
        padding: 25px;
        text-align: center;
        background: white;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .area-upload:hover {
        border-color: #0a9396;
        background: #f0fdfa;
    }
    
    .area-upload i {
        font-size: 32px;
        color: #0a9396;
        margin-bottom: 10px;
    }
    
    .previa-imagem {
        margin-top: 15px;
        padding: 15px;
        background: #f1f5f9;
        border-radius: 8px;
        display: none;
        align-items: center;
        justify-content: space-between;
    }
    
    .previa-imagem.ativa {
        display: flex;
    }
    
    .container-toggle {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-top: 10px;
    }
    
    .toggle {
        position: relative;
        width: 50px;
        height: 24px;
        display: inline-block;
    }
    
    .toggle input {
        opacity: 0;
        width: 0;
        height: 0;
    }
    
    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #cbd5e0;
        transition: 0.3s;
        border-radius: 24px;
    }
    
    .slider:before {
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
    
    input:checked + .slider {
        background-color: #0a9396;
    }
    
    input:checked + .slider:before {
        transform: translateX(26px);
    }
    
    .contador-caracteres {
        font-size: 11px;
        color: #6c757d;
        text-align: right;
        margin-top: 4px;
    }

    /* ===== MODAL DE IMPORTAÇÃO CSV ===== */
.modal-importar {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.85);
    backdrop-filter: blur(5px);
    z-index: 10002;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.modal-importar.ativo {
    display: flex;
}

.modal-importar-conteudo {
    background: white;
    border-radius: 16px;
    max-width: 800px;
    width: 100%;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    animation: slideInModal 0.3s ease;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
}

.modal-importar-cabecalho {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 25px;
    border-bottom: 2px solid #e0e4e8;
    background: linear-gradient(135deg, #f8f9fa, #ffffff);
    border-radius: 16px 16px 0 0;
}

.modal-importar-cabecalho h3 {
    margin: 0;
    font-size: 1.3rem;
    color: #003072;
}

.modal-importar-cabecalho h3 i {
    color: #0a9396;
    margin-right: 10px;
}

.modal-importar-fechar {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    border: none;
    background: #e9ecef;
    color: #6c757d;
    cursor: pointer;
    transition: all 0.3s ease;
}

.modal-importar-fechar:hover {
    background: #dc3545;
    color: white;
    transform: rotate(90deg);
}

.modal-importar-corpo {
    flex: 1;
    overflow-y: auto;
    padding: 25px;
}

.modal-importar-rodape {
    padding: 15px 25px;
    border-top: 1px solid #e0e4e8;
    display: flex;
    justify-content: flex-end;
    gap: 15px;
    background: #f8f9fa;
    border-radius: 0 0 16px 16px;
}

.botao-importar {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 24px;
    background: linear-gradient(135deg, #28a745, #218838);
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.botao-importar:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
}

.botao-importar:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.aviso-importacao {
    background: #fff3cd;
    border-left: 4px solid #ffc107;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    gap: 12px;
    align-items: flex-start;
}

.aviso-importacao i {
    font-size: 1.5rem;
    color: #ffc107;
}

.area-upload-csv {
    border: 2px dashed #cbd5e0;
    border-radius: 12px;
    padding: 25px;
    text-align: center;
    background: #f8fafc;
    cursor: pointer;
    transition: all 0.3s;
}

.area-upload-csv:hover {
    border-color: #0a9396;
    background: #f0fdfa;
}

.area-upload-csv i {
    font-size: 32px;
    color: #0a9396;
    margin-bottom: 10px;
}

.previa-arquivo {
    margin-top: 15px;
    padding: 12px 15px;
    background: #e9ecef;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.opcoes-importacao {
    margin: 20px 0;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
}

.opcao-importacao {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
    cursor: pointer;
}

.opcao-importacao:last-child {
    margin-bottom: 0;
}

.opcao-importacao input {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.preview-csv {
    margin-top: 20px;
    border-top: 1px solid #e0e4e8;
    padding-top: 20px;
}

.preview-csv h4 {
    margin-bottom: 15px;
    color: #003072;
}

.tabela-preview {
    max-height: 250px;
    overflow-y: auto;
    border: 1px solid #e0e4e8;
    border-radius: 8px;
}

.tabela-preview table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
}

.tabela-preview th,
.tabela-preview td {
    padding: 8px 10px;
    text-align: left;
    border-bottom: 1px solid #e0e4e8;
}

.tabela-preview th {
    background: #f8f9fa;
    font-weight: 600;
    position: sticky;
    top: 0;
}

    .resumo-importacao {
  margin-top: 15px;
  padding: 10px;
  background: #e7f3ff;
  border-radius: 8px;
  font-size: 13px;
}

/* ===== MODAL DE CONFIRMAÇÃO ===== */
.modal-confirmacao {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 10020;
    align-items: center;
    justify-content: center;
    padding: 20px;
    background: linear-gradient(135deg, rgba(5, 19, 43, 0.82), rgba(0, 0, 0, 0.86));
    backdrop-filter: blur(3px);
}

.modal-confirmacao.ativo {
    display: flex;
}

.modal-confirmacao-caixa {
    width: 100%;
    max-width: 460px;
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 24px 48px rgba(0, 0, 0, 0.35);
    padding: 26px;
    text-align: center;
    animation: slideInModal 0.25s ease;
}

.modal-confirmacao-icone {
    width: 62px;
    height: 62px;
    margin: 0 auto 14px;
    border-radius: 50%;
    background: linear-gradient(135deg, #dbeafe, #eff6ff);
    color: #2563eb;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
}

.modal-confirmacao-caixa h3 {
    margin: 0 0 10px;
    color: #1f2937;
    font-size: 1.3rem;
}

.modal-confirmacao-caixa p {
    margin: 0;
    color: #6b7280;
    line-height: 1.45;
}

.modal-confirmacao-botoes {
    margin-top: 22px;
    display: flex;
    gap: 12px;
    justify-content: center;
    flex-wrap: wrap;
}

.botao-perigo-confirmacao {
    border: none;
    border-radius: 8px;
    padding: 12px 22px;
    color: #fff;
    cursor: pointer;
    font-weight: 600;
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    box-shadow: 0 8px 18px rgba(37, 99, 235, 0.25);
}

.botao-perigo-confirmacao:hover {
    transform: translateY(-1px);
}
    
    @media (max-width: 768px) {
        .linha-form, .linha-form-3 {
            grid-template-columns: 1fr;
        }
        .acoes-form {
            flex-direction: column;
        }
        .botao-salvar, .botao-cancelar {
            justify-content: center;
        }
    }
    
</style>

<script>
    // Dados das notícias do PHP para JavaScript
    const noticias = <?php echo json_encode($noticias); ?>;
    
    let paginaAtual = 1;
    let itensPorPagina = 10;
    let noticiaEditandoId = null;
    let linhasSelecionadas = [];
    let imagemFile = null;
    let imagemCapaVideoFile = null;
    let videoFile = null;
    let acaoPendenteConfirmacao = null;
    let noticiaVisualizandoId = null;

    // ============== FUNÇÕES DE RENDERIZAÇÃO ===================
    function limparTextoTag(tag) {
        return String(tag || '')
            .replace(/\\/g, '')
            .replace(/^\[+|\]+$/g, '')
            .replace(/^"+|"+$/g, '')
            .replace(/^'+|'+$/g, '')
            .trim();
    }

    function obterListaTags(valor) {
        if (!valor) return [];
        if (Array.isArray(valor)) {
            return valor.map(limparTextoTag).filter(Boolean);
        }

        const texto = String(valor).trim();
        if (!texto) return [];

        try {
            const parsed = JSON.parse(texto);
            if (Array.isArray(parsed)) {
                return parsed.map(limparTextoTag).filter(Boolean);
            }
        } catch (e) {}

        return texto
            .split(',')
            .map(limparTextoTag)
            .filter(Boolean);
    }

    function formatarTagsParaInput(valor) {
        return obterListaTags(valor).join(', ');
    }

    function renderizarTabelaNoticias() {
        const tbody = document.getElementById('corpoTabelaNoticias');
        if (!tbody) return;
        
        tbody.innerHTML = '';
        
        let noticiasFiltradas = aplicarFiltros();
        const totalItens = noticiasFiltradas.length;
        const totalPaginas = Math.ceil(totalItens / itensPorPagina);
        const indiceInicio = (paginaAtual - 1) * itensPorPagina;
        const indiceFim = indiceInicio + itensPorPagina;
        const noticiasPagina = noticiasFiltradas.slice(indiceInicio, indiceFim);
        
        noticiasPagina.forEach(noticia => {
            const linha = document.createElement('tr');
            linha.dataset.id = noticia.id;
            linha.dataset.estado = noticia.estado;
            linha.dataset.categoria = noticia.categoria;
            
            const dataObj = new Date(noticia.data_publicacao);
            const dataExibicao = dataObj.toLocaleDateString('pt-PT');
            
            let classeBadge = 'badge-success';
            let iconeBadge = 'fa-check-circle';
            let textoBadge = 'Publicada';
            
            if (noticia.estado === 'rascunho') {
                classeBadge = 'badge-warning';
                iconeBadge = 'fa-clock';
                textoBadge = 'Rascunho';
            } else if (noticia.estado === 'arquivada') {
                classeBadge = 'badge-info';
                iconeBadge = 'fa-archive';
                textoBadge = 'Arquivada';
            }
            
            linha.innerHTML = `
                <td><input type="checkbox" class="checkbox-linha" data-id="${noticia.id}"></td>
                <td>
                    ${noticia.destaque_principal ? '<span class="badge-star"><i class="fas fa-star"></i></span>' : ''}
                    ${escapeHtml(noticia.titulo)}
                </td>
                <td>${dataExibicao}</td>
                <td><span class="badge ${classeBadge}"><i class="fas ${iconeBadge}"></i> ${textoBadge}</span></td>
                <td>
                    <div class="botoes-acao">
                        <button class="botao-icone" onclick="visualizarNoticia(${noticia.id})" title="Visualizar"><i class="fas fa-eye"></i></button>
                        <button class="botao-icone" onclick="editarNoticia(${noticia.id})" title="Editar"><i class="fas fa-edit"></i></button>
                        <button class="botao-icone botao-perigo" onclick="eliminarNoticia(${noticia.id})" title="Eliminar"><i class="fas fa-trash"></i></button>
                    </div>
                </td>
            `;
            
            tbody.appendChild(linha);
        });
        
        document.getElementById('resumoTotal').textContent = noticias.length;
        document.getElementById('resumoPublicadas').textContent = noticias.filter(n => n.estado === 'publicada').length;
        document.getElementById('resumoRascunhos').textContent = noticias.filter(n => n.estado === 'rascunho').length;
        document.getElementById('resumoArquivadas').textContent = noticias.filter(n => n.estado === 'arquivada').length;
        
        document.getElementById('paginaAtual').textContent = paginaAtual;
        document.getElementById('totalPaginas').textContent = totalPaginas;
        document.getElementById('botaoAnterior').disabled = paginaAtual === 1;
        document.getElementById('botaoProximo').disabled = paginaAtual === totalPaginas;
        
        atribuirEventosCheckboxes();
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function normalizarUrlMidiaNoticia(url) {
        if (!url) return '';
        if (/^https?:\/\//i.test(url)) return url;
        return '../' + String(url).replace(/^\/+/, '');
    }
    
    function atribuirEventosCheckboxes() {
        const checkboxes = document.querySelectorAll('#corpoTabelaNoticias .checkbox-linha');
        checkboxes.forEach(cb => {
            cb.removeEventListener('change', handleCheckboxChange);
            cb.addEventListener('change', handleCheckboxChange);
        });
    }
    
    function handleCheckboxChange(e) {
        const id = parseInt(this.dataset.id);
        if (!Number.isInteger(id)) return;

        if (this.checked) {
            if (!linhasSelecionadas.includes(id)) linhasSelecionadas.push(id);
        } else {
            linhasSelecionadas = linhasSelecionadas.filter(i => i !== id);
        }
        const selecionarTodos = document.getElementById('selecionarTodos');
        if (selecionarTodos) {
            const checkboxesTabela = document.querySelectorAll('#corpoTabelaNoticias .checkbox-linha');
            selecionarTodos.checked = checkboxesTabela.length > 0 && Array.from(checkboxesTabela).every(cb => cb.checked);
        }
        atualizarEstadoBotoesMassa();
    }
    
    // ============= FILTROS =================
    function aplicarFiltros() {
        const termo = document.getElementById('campoBusca')?.value.toLowerCase() || '';
        const estado = document.getElementById('filtroEstado')?.value || '';
        const categoria = document.getElementById('filtroCategoria')?.value || '';
        const dataInicio = document.getElementById('filtroDataInicio')?.value;
        const dataFim = document.getElementById('filtroDataFim')?.value;
        const ordenar = document.getElementById('filtroOrdenar')?.value || 'data-desc';
        
        let filtradas = [...noticias];
        
        if (termo) {
            filtradas = filtradas.filter(n => n.titulo.toLowerCase().includes(termo) || (n.resumo && n.resumo.toLowerCase().includes(termo)));
        }
        if (estado) filtradas = filtradas.filter(n => n.estado === estado);
        if (categoria) filtradas = filtradas.filter(n => n.categoria === categoria);
        if (dataInicio) filtradas = filtradas.filter(n => n.data_publicacao >= dataInicio);
        if (dataFim) filtradas = filtradas.filter(n => n.data_publicacao <= dataFim);
        
        filtradas.sort((a, b) => {
            switch(ordenar) {
                case 'data-desc': return new Date(b.data_publicacao) - new Date(a.data_publicacao);
                case 'data-asc': return new Date(a.data_publicacao) - new Date(b.data_publicacao);
                case 'titulo-asc': return a.titulo.localeCompare(b.titulo);
                case 'titulo-desc': return b.titulo.localeCompare(a.titulo);
                default: return 0;
            }
        });
        
        return filtradas;
    }
    
    // =============== FUNÇÕES DO MODAL ==============
    function abrirModalNoticia(id = null, estadoRestaurado = null) {
        const modal = document.getElementById('modalNoticia');
        const form = document.getElementById('formNoticia');
        const titulo = document.getElementById('modalTitulo');
        const subtitulo = document.getElementById('modalSubtitulo');
        
        form.reset();
        removerImagemNoticia();
        removerImagemCapaVideo();
        removerVideoNoticia();
        
        document.getElementById('secaoImagem').style.display = 'block';
        document.getElementById('secaoVideo').style.display = 'none';
        document.getElementById('contadorResumo').textContent = '0 / 200';
        
        if (id) {
            const noticia = noticias.find(n => n.id === id);
            if (noticia) {
                titulo.innerHTML = 'Editar Notícia';
                subtitulo.innerHTML = 'Altere os dados da notícia';
                
                document.getElementById('noticiaId').value = noticia.id;
                document.getElementById('noticiaTitulo').value = noticia.titulo;
                document.getElementById('noticiaCategoria').value = noticia.categoria;
                document.getElementById('noticiaData').value = noticia.data_publicacao;
                document.getElementById('noticiaResumo').value = noticia.resumo || '';
                document.getElementById('noticiaConteudo').value = noticia.conteudo;
                document.getElementById('noticiaTipoMidia').value = noticia.tipo_midia || 'imagem';
                document.getElementById('noticiaAlt').value = noticia.alt_text || '';
                document.getElementById('noticiaAutor').value = noticia.autor;
                document.getElementById('noticiaTags').value = formatarTagsParaInput(noticia.tags);
                document.getElementById('noticiaEstado').value = noticia.estado;
                document.getElementById('noticiaDestaquePrincipal').checked = noticia.destaque_principal == 1;
                
                document.getElementById('contadorResumo').textContent = (noticia.resumo?.length || 0) + ' / 200';
                
                if (noticia.tipo_midia === 'video' && noticia.video_file) {
                    document.getElementById('secaoImagem').style.display = 'none';
                    document.getElementById('secaoVideo').style.display = 'block';
                    const videoElement = document.getElementById('miniaturaVideo');
                    videoElement.src = noticia.video_file;
                    document.getElementById('previaVideo').classList.add('ativa');
                    document.getElementById('nomeVideo').textContent = noticia.video_file.split('/').pop();
                }

                if (noticia.tipo_midia === 'video' && noticia.imagem_url) {
                    document.getElementById('miniaturaImagemCapaVideo').src = noticia.imagem_url;
                    document.getElementById('previaImagemCapaVideo').classList.add('ativa');
                    document.getElementById('nomeImagemCapaVideo').textContent = noticia.imagem_url.split('/').pop();
                }
                
                if (noticia.tipo_midia === 'imagem' && noticia.imagem_url) {
                    document.getElementById('miniaturaImagem').src = noticia.imagem_url;
                    document.getElementById('previaImagem').classList.add('ativa');
                    document.getElementById('nomeImagem').textContent = noticia.imagem_url.split('/').pop();
                }
                
                noticiaEditandoId = id;
            }
        } else {
            titulo.innerHTML = 'Nova Notícia';
            subtitulo.innerHTML = 'Preencha os dados para criar uma nova notícia';
            document.getElementById('noticiaId').value = '';
            document.getElementById('noticiaData').value = new Date().toISOString().split('T')[0];
            noticiaEditandoId = null;
        }
        
        modal.classList.add('ativo');
        document.body.style.overflow = 'hidden';
        emitirSom('modal');

        if (estadoRestaurado && estadoRestaurado.form) {
            const f = estadoRestaurado.form;
            document.getElementById('noticiaTitulo').value = f.titulo || '';
            document.getElementById('noticiaCategoria').value = f.categoria || '';
            document.getElementById('noticiaData').value = f.data || '';
            document.getElementById('noticiaResumo').value = f.resumo || '';
            document.getElementById('noticiaConteudo').value = f.conteudo || '';
            document.getElementById('noticiaTipoMidia').value = f.tipoMidia || 'imagem';
            document.getElementById('noticiaAlt').value = f.altText || '';
            document.getElementById('noticiaAutor').value = f.autor || '';
            document.getElementById('noticiaTags').value = formatarTagsParaInput(f.tags || '');
            document.getElementById('noticiaEstado').value = f.estado || 'rascunho';
            document.getElementById('noticiaDestaquePrincipal').checked = !!f.destaque;
            document.getElementById('contadorResumo').textContent = `${(f.resumo || '').length} / 200`;
            document.getElementById('noticiaTipoMidia').dispatchEvent(new Event('change'));
        }

        salvarEstadoModalNoticia();
    }
    
    function fecharModalNoticia() {
        document.getElementById('modalNoticia').classList.remove('ativo');
        document.body.style.overflow = '';
        limparEstadoModalNoticia();
    }
    
    // ===== EVENTOS DE UPLOAD =====
    document.getElementById('noticiaTipoMidia')?.addEventListener('change', function() {
        if (this.value === 'imagem') {
            document.getElementById('secaoImagem').style.display = 'block';
            document.getElementById('secaoVideo').style.display = 'none';
        } else {
            document.getElementById('secaoImagem').style.display = 'none';
            document.getElementById('secaoVideo').style.display = 'block';
        }
        salvarEstadoModalNoticia();
    });
    
    document.getElementById('inputImagem')?.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            imagemFile = file;
            document.getElementById('nomeImagem').textContent = file.name;
            
            if (file.size > 5 * 1024 * 1024) {
                mostrarNotificacao('Imagem muito grande. Máximo 5MB.', 'error');
                this.value = '';
                imagemFile = null;
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('miniaturaImagem').src = e.target.result;
                document.getElementById('previaImagem').classList.add('ativa');
                salvarEstadoModalNoticia();
            };
            reader.readAsDataURL(file);
        }
    });
    
    document.getElementById('inputVideo')?.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            videoFile = file;
            document.getElementById('nomeVideo').textContent = file.name;
            
            if (file.size > 50 * 1024 * 1024) {
                mostrarNotificacao('Vídeo muito grande. Máximo 50MB.', 'error');
                this.value = '';
                videoFile = null;
                return;
            }
            
            const url = URL.createObjectURL(file);
            const videoElement = document.getElementById('miniaturaVideo');
            videoElement.src = url;
            document.getElementById('previaVideo').classList.add('ativa');
            salvarEstadoModalNoticia();
        }
    });

    document.getElementById('inputImagemCapaVideo')?.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            imagemCapaVideoFile = file;
            document.getElementById('nomeImagemCapaVideo').textContent = file.name;

            if (file.size > 5 * 1024 * 1024) {
                mostrarNotificacao('Imagem de capa muito grande. Máximo 5MB.', 'error');
                this.value = '';
                imagemCapaVideoFile = null;
                return;
            }

            const reader = new FileReader();
            reader.onload = function(ev) {
                document.getElementById('miniaturaImagemCapaVideo').src = ev.target.result;
                document.getElementById('previaImagemCapaVideo').classList.add('ativa');
                salvarEstadoModalNoticia();
            };
            reader.readAsDataURL(file);
        }
    });
    
    function removerImagemNoticia() {
        document.getElementById('inputImagem').value = '';
        document.getElementById('previaImagem').classList.remove('ativa');
        imagemFile = null;
        salvarEstadoModalNoticia();
    }
    
    function removerVideoNoticia() {
        document.getElementById('inputVideo').value = '';
        document.getElementById('previaVideo').classList.remove('ativa');
        if (videoFile) {
            URL.revokeObjectURL(document.getElementById('miniaturaVideo').src);
            videoFile = null;
        }
        salvarEstadoModalNoticia();
    }

    function removerImagemCapaVideo() {
        document.getElementById('inputImagemCapaVideo').value = '';
        document.getElementById('previaImagemCapaVideo').classList.remove('ativa');
        imagemCapaVideoFile = null;
        salvarEstadoModalNoticia();
    }
    
    document.getElementById('noticiaResumo')?.addEventListener('input', function() {
        document.getElementById('contadorResumo').textContent = this.value.length + ' / 200';
        salvarEstadoModalNoticia();
    });
    
    // ================== CRUD OPERATIONS =======================
    function salvarNoticia(event) {
        event.preventDefault();
        
        const formData = new FormData();
        
        const id = document.getElementById('noticiaId').value;
        formData.append('acao', id ? 'editar' : 'salvar');
        formData.append('id', id);
        formData.append('titulo', document.getElementById('noticiaTitulo').value);
        formData.append('categoria', document.getElementById('noticiaCategoria').value);
        formData.append('data_publicacao', document.getElementById('noticiaData').value);
        formData.append('resumo', document.getElementById('noticiaResumo').value);
        formData.append('conteudo', document.getElementById('noticiaConteudo').value);
        formData.append('tipo_midia', document.getElementById('noticiaTipoMidia').value);
        formData.append('alt_text', document.getElementById('noticiaAlt').value);
        formData.append('autor', document.getElementById('noticiaAutor').value);
        formData.append('tags', document.getElementById('noticiaTags').value);
        formData.append('estado', document.getElementById('noticiaEstado').value);
        formData.append('destaque_principal', document.getElementById('noticiaDestaquePrincipal').checked ? '1' : '0');
        
        if (imagemFile) {
            formData.append('imagem', imagemFile);
        }
        if (imagemCapaVideoFile) {
            formData.append('imagem_capa', imagemCapaVideoFile);
        }
        if (videoFile) {
            formData.append('video', videoFile);
        }
        
        const btnSalvar = document.querySelector('.botao-salvar');
        const textoOriginal = btnSalvar.innerHTML;
        btnSalvar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';
        btnSalvar.disabled = true;
        
        fetch('processos/processar-noticia.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                limparEstadoModalNoticia();
                mostrarNotificacao(data.message, 'success');
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                mostrarNotificacao(data.message, 'error');
                btnSalvar.innerHTML = textoOriginal;
                btnSalvar.disabled = false;
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            mostrarNotificacao('Erro ao salvar notícia: ' + error.message, 'error');
            btnSalvar.innerHTML = textoOriginal;
            btnSalvar.disabled = false;
        });
    }
    
    function editarNoticia(id) {
        abrirModalNoticia(id);
    }
    
function visualizarNoticia(id) {
    const noticia = noticias.find(n => n.id === id);
    if (!noticia) return;
    
    const modal = document.getElementById('modalVisualizarNoticia');
    const corpo = document.getElementById('visualizarCorpo');
    
    // Mostrar loading
    corpo.innerHTML = '<div class="loading-visualizar"><i class="fas fa-spinner fa-spin"></i> Carregando notícia...</div>';
    
    // Abrir modal
    modal.classList.add('ativo');
    document.body.style.overflow = 'hidden';
    noticiaVisualizandoId = id;
    emitirSom('modal');
    salvarEstadoModalNoticia();
    
    // Construir conteúdo da notícia para visualização
    const dataObj = new Date(noticia.data_publicacao);
    const dataFormatada = dataObj.toLocaleDateString('pt-PT');
    
    // Processar tags
    let tagsHtml = '';
    if (noticia.tags) {
        try {
            const tags = obterListaTags(noticia.tags);
            tagsHtml = tags.map(tag => `<span class="tag"><i class="fas fa-hashtag"></i> ${escapeHtml(tag)}</span>`).join('');
        } catch(e) {
            const tagsArray = obterListaTags(noticia.tags);
            tagsHtml = tagsArray.map(tag => `<span class="tag"><i class="fas fa-hashtag"></i> ${escapeHtml(tag)}</span>`).join('');
        }
    }
    
    // Determinar caminho correto da imagem
    let midiaHtml = '';
    if (noticia.tipo_midia === 'video' && noticia.video_file) {
        midiaHtml = `<div class="imagem"><video src="${normalizarUrlMidiaNoticia(noticia.video_file)}" controls style="max-width:100%; max-height:300px;"></video></div>`;
    } else if (noticia.imagem_url) {
        midiaHtml = `<div class="imagem"><img src="${normalizarUrlMidiaNoticia(noticia.imagem_url)}" alt="${escapeHtml(noticia.titulo)}"></div>`;
    }
    
    // Construir HTML completo
    const html = `
        <div class="visualizar-noticia">
            <div class="badge-categoria ${noticia.categoria}">
                <i class="fas ${getIconeCategoria(noticia.categoria)}"></i> ${noticia.categoria}
            </div>
            <h2>${escapeHtml(noticia.titulo)}</h2>
            <div class="meta">
                <span><i class="fas fa-calendar-alt"></i> ${dataFormatada}</span>
                <span><i class="fas fa-user-circle"></i> ${escapeHtml(noticia.autor || 'Gabinete de Comunicação')}</span>
                <span><i class="fas fa-eye"></i> ${noticia.visualizacoes || 0} visualizações</span>
                <span><i class="fas fa-${noticia.estado === 'publicada' ? 'check-circle' : (noticia.estado === 'rascunho' ? 'clock' : 'archive')}"></i> 
                    ${noticia.estado === 'publicada' ? 'Publicada' : (noticia.estado === 'rascunho' ? 'Rascunho' : 'Arquivada')}
                </span>
            </div>
            ${midiaHtml}
            ${noticia.alt_text ? `<div class="legenda"><i class="fas fa-camera"></i> ${escapeHtml(noticia.alt_text)}</div>` : ''}
            <div class="conteudo">
                ${escapeHtml(noticia.conteudo).replace(/\n/g, '<br>')}
            </div>
            ${tagsHtml ? `<div class="tags">${tagsHtml}</div>` : ''}
        </div>
    `;
    
    corpo.innerHTML = html;
}

function getIconeCategoria(categoria) {
    const icones = {
        'DESTAQUE': 'fa-star',
        'CURSOS': 'fa-graduation-cap',
        'EVENTOS': 'fa-calendar-alt',
        'PARCERIA': 'fa-handshake',
        'INSTITUCIONAL': 'fa-building'
    };
    return icones[categoria] || 'fa-newspaper';
}

    function fecharModalVisualizar() {
        const modal = document.getElementById('modalVisualizarNoticia');
        modal.classList.remove('ativo');
        document.body.style.overflow = '';
        noticiaVisualizandoId = null;
        limparEstadoModalNoticia();
    }

    function abrirModalConfirmacao(titulo, texto, callbackConfirmar, opcoes = {}) {
        const modal = document.getElementById('modalConfirmacao');
        const tituloEl = document.getElementById('modalConfirmacaoTitulo');
        const textoEl = document.getElementById('modalConfirmacaoTexto');
        const iconeEl = document.getElementById('modalConfirmacaoIcone');
        const botaoIconeEl = document.getElementById('modalConfirmacaoBotaoIcone');
        const botaoTextoEl = document.getElementById('modalConfirmacaoBotaoTexto');
        const iconeWrapper = document.getElementById('modalConfirmacaoIconeWrapper');
        const botaoConfirmar = document.getElementById('botaoConfirmarAcao');

        tituloEl.textContent = titulo;
        textoEl.textContent = texto;
        const iconeClasse = opcoes.iconeClasse || 'fa-paper-plane';
        const botaoTexto = opcoes.botaoTexto || 'Publicar';
        const corPrimaria = opcoes.corPrimaria || '#2563eb';
        const corSecundaria = opcoes.corSecundaria || '#1d4ed8';

        if (iconeEl) iconeEl.className = `fas ${iconeClasse}`;
        if (botaoIconeEl) botaoIconeEl.className = `fas ${iconeClasse}`;
        if (botaoTextoEl) botaoTextoEl.textContent = botaoTexto;
        if (iconeWrapper) {
            iconeWrapper.style.background = 'linear-gradient(135deg, #dbeafe, #eff6ff)';
            iconeWrapper.style.color = corPrimaria;
        }
        if (botaoConfirmar) {
            botaoConfirmar.style.background = `linear-gradient(135deg, ${corPrimaria}, ${corSecundaria})`;
            botaoConfirmar.style.boxShadow = `0 8px 18px ${corPrimaria}55`;
        }
        acaoPendenteConfirmacao = callbackConfirmar;

        modal.classList.add('ativo');
        document.body.style.overflow = 'hidden';
        emitirSom('modal');
    }

    function fecharModalConfirmacao() {
        const modal = document.getElementById('modalConfirmacao');
        modal.classList.remove('ativo');
        acaoPendenteConfirmacao = null;
        document.body.style.overflow = '';
    }

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

    const CHAVE_ESTADO_MODAL = 'adminNoticiasModalState:v1';

    function emitirSom(tipo = 'modal') {
        try {
            const AudioContextRef = window.AudioContext || window.webkitAudioContext;
            if (!AudioContextRef) return;

            const ctx = new AudioContextRef();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();

            osc.type = 'sine';
            osc.frequency.value = tipo === 'error' ? 220 : (tipo === 'success' ? 660 : 460);
            gain.gain.value = 0.0001;
            osc.connect(gain);
            gain.connect(ctx.destination);

            const now = ctx.currentTime;
            gain.gain.exponentialRampToValueAtTime(0.08, now + 0.01);
            gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.2);

            osc.start(now);
            osc.stop(now + 0.22);
        } catch (e) {}
    }

    function salvarEstadoModalNoticia() {
        const modalNoticia = document.getElementById('modalNoticia');
        const modalVisualizar = document.getElementById('modalVisualizarNoticia');
        const estado = {};

        if (modalNoticia?.classList.contains('ativo')) {
            estado.modal = 'noticia';
            estado.noticiaId = document.getElementById('noticiaId').value || null;
            estado.form = {
                titulo: document.getElementById('noticiaTitulo').value,
                categoria: document.getElementById('noticiaCategoria').value,
                data: document.getElementById('noticiaData').value,
                resumo: document.getElementById('noticiaResumo').value,
                conteudo: document.getElementById('noticiaConteudo').value,
                tipoMidia: document.getElementById('noticiaTipoMidia').value,
                altText: document.getElementById('noticiaAlt').value,
                autor: document.getElementById('noticiaAutor').value,
                tags: document.getElementById('noticiaTags').value,
                estado: document.getElementById('noticiaEstado').value,
                destaque: document.getElementById('noticiaDestaquePrincipal').checked
            };
        } else if (modalVisualizar?.classList.contains('ativo') && noticiaVisualizandoId) {
            estado.modal = 'visualizar';
            estado.visualizarId = noticiaVisualizandoId;
        } else {
            sessionStorage.removeItem(CHAVE_ESTADO_MODAL);
            return;
        }

        sessionStorage.setItem(CHAVE_ESTADO_MODAL, JSON.stringify(estado));
    }

    function limparEstadoModalNoticia() {
        sessionStorage.removeItem(CHAVE_ESTADO_MODAL);
    }
    
    function eliminarNoticia(id) {
        abrirModalConfirmacao(
            'Deseja eliminar a notícia?',
            'Esta notícia e seus arquivos serão movidos para a lixeira.',
            () => {
            const formData = new FormData();
            formData.append('acao', 'eliminar');
            formData.append('id', id);
            
            fetch('processos/processar-noticia.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mostrarNotificacao(data.message, 'success');
                    location.reload();
                } else {
                    mostrarNotificacao(data.message, 'error');
                }
            });
            },
            { iconeClasse: 'fa-trash', botaoTexto: 'Eliminar', corPrimaria: '#dc3545', corSecundaria: '#b62030' }
        );
    }
    
    // ============ AÇÕES EM MASSA =================
    document.getElementById('selecionarTodos')?.addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('#corpoTabelaNoticias .checkbox-linha');
        checkboxes.forEach(cb => {
            cb.checked = this.checked;
            const id = parseInt(cb.dataset.id);
            if (!Number.isInteger(id)) return;
            if (this.checked) {
                if (!linhasSelecionadas.includes(id)) linhasSelecionadas.push(id);
            } else {
                linhasSelecionadas = linhasSelecionadas.filter(i => i !== id);
            }
        });
        linhasSelecionadas = linhasSelecionadas.filter(id => Number.isInteger(id));
        atualizarEstadoBotoesMassa();
    });
    
    document.getElementById('botaoSelecionarTodas')?.addEventListener('click', function() {
        const checkboxes = document.querySelectorAll('#corpoTabelaNoticias .checkbox-linha');
        checkboxes.forEach(cb => {
            cb.checked = true;
            const id = parseInt(cb.dataset.id);
            if (!Number.isInteger(id)) return;
            if (!linhasSelecionadas.includes(id)) linhasSelecionadas.push(id);
        });
        document.getElementById('selecionarTodos').checked = true;
        linhasSelecionadas = linhasSelecionadas.filter(id => Number.isInteger(id));
        atualizarEstadoBotoesMassa();
    });
    
    document.getElementById('botaoEliminarSelecionadas')?.addEventListener('click', function() {
        if (linhasSelecionadas.length === 0) {
            mostrarNotificacao('Selecione pelo menos uma notícia.', 'error');
            return;
        }
        
        abrirModalConfirmacao(
            'Deseja eliminar as notícias selecionadas?',
            `Serão eliminadas ${linhasSelecionadas.length} notícia(s) e os ficheiros irão para a lixeira.`,
            () => {
            const formData = new FormData();
            formData.append('acao', 'eliminar_massa');
            formData.append('ids', linhasSelecionadas.join(','));
            
            fetch('processos/processar-noticia.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mostrarNotificacao(data.message, 'success');
                    location.reload();
                } else {
                    mostrarNotificacao(data.message, 'error');
                }
            });
            },
            { iconeClasse: 'fa-trash', botaoTexto: 'Eliminar', corPrimaria: '#dc3545', corSecundaria: '#b62030' }
        );
    });
    
    document.getElementById('botaoPublicarSelecionadas')?.addEventListener('click', function() {
        if (linhasSelecionadas.length === 0) {
            mostrarNotificacao('Selecione pelo menos uma notícia.', 'error');
            return;
        }
        
        const noticiasSelecionadas = noticias.filter(n => linhasSelecionadas.includes(parseInt(n.id)));
        const idsPublicaveis = noticiasSelecionadas
            .filter(n => n.estado === 'rascunho' || n.estado === 'arquivada')
            .map(n => parseInt(n.id))
            .filter(id => Number.isInteger(id));

        if (idsPublicaveis.length === 0) {
            mostrarNotificacao('Nenhuma notícia publicável na seleção (apenas rascunho/arquivada).', 'error');
            return;
        }

        abrirModalConfirmacao(
            'Publicar notícias selecionadas?',
            `Deseja publicar ${idsPublicaveis.length} notícia(s) (rascunho/arquivada)?`,
            () => {
            const formData = new FormData();
            formData.append('acao', 'publicar_massa');
            formData.append('ids', idsPublicaveis.join(','));
            
            fetch('processos/processar-noticia.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (noticiasSelecionadas.length > idsPublicaveis.length) {
                        mostrarNotificacao('Notícias já publicadas foram ignoradas. ' + data.message, 'success');
                    } else {
                        mostrarNotificacao(data.message, 'success');
                    }
                    location.reload();
                } else {
                    mostrarNotificacao(data.message, 'error');
                }
            });
            },
            { iconeClasse: 'fa-paper-plane', botaoTexto: 'Publicar', corPrimaria: '#2563eb', corSecundaria: '#1d4ed8' }
        );
    });

    function atualizarEstadoBotoesMassa() {
        const temSelecao = linhasSelecionadas.length > 0;
        const noticiasSelecionadas = noticias.filter(n => linhasSelecionadas.includes(parseInt(n.id)));
        const temPublicavel = noticiasSelecionadas.some(n => n.estado === 'rascunho' || n.estado === 'arquivada');
        document.getElementById('botaoEliminarSelecionadas').disabled = !temSelecao;
        document.getElementById('botaoExportarCSV').disabled = !temSelecao;
        document.getElementById('botaoPublicarSelecionadas').disabled = !temPublicavel;
    }

    //============== EXPORTAÇÃO CSV ==================

    function exportarParaCSV() {
    if (linhasSelecionadas.length === 0) {
        mostrarNotificacao('Selecione pelo menos uma notícia para exportar.', 'error');
        return;
    }

    const noticiasSelecionadas = noticias.filter(n => linhasSelecionadas.includes(parseInt(n.id)));
    if (noticiasSelecionadas.length === 0) {
        mostrarNotificacao('Não foi possível encontrar as notícias selecionadas para exportação.', 'error');
        return;
    }

    // Exportar no formato adequado para reimportação (incluindo imagens)
    const csv = [['titulo', 'resumo', 'conteudo', 'categoria', 'autor', 'tags', 'data_publicacao', 'estado', 'imagem_url', 'video_file'].join(',')];
    const escaparCSV = (valor) => {
        const texto = String(valor ?? '')
            .replace(/\r\n/g, '\n')
            .replace(/\r/g, '\n')
            .replace(/"/g, '""');
        return `"${texto}"`;
    };
    
    noticiasSelecionadas.forEach(n => {
        // Tratar tags
        let tags = '';
        if (n.tags) {
            try {
                const tagsArray = JSON.parse(n.tags);
                tags = tagsArray.join(',');
            } catch(e) {
                tags = n.tags;
            }
        }
        
        csv.push([
            escaparCSV(n.titulo),
            escaparCSV(n.resumo || ''),
            escaparCSV(n.conteudo || ''),
            escaparCSV(n.categoria),
            escaparCSV(n.autor || ''),
            escaparCSV(tags),
            escaparCSV(n.data_publicacao),
            escaparCSV(n.estado),
            escaparCSV(n.imagem_url || ''),
            escaparCSV(n.video_file || '')
        ].join(','));
    });
    
    const csvContent = csv.join('\n');
    const blob = new Blob(["\uFEFF" + csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    
    link.setAttribute('href', url);
    link.setAttribute('download', `noticias_backup_${new Date().getTime()}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    mostrarNotificacao(`Exportação concluída: ${noticiasSelecionadas.length} notícia(s).`, 'success');
}
    
    // =============== FILTROS =====================
    ['campoBusca', 'filtroEstado', 'filtroCategoria', 'filtroDataInicio', 'filtroDataFim', 'filtroOrdenar'].forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('change', function() {
                paginaAtual = 1;
                renderizarTabelaNoticias();
            });
            if (id === 'campoBusca') {
                el.addEventListener('input', function() {
                    paginaAtual = 1;
                    renderizarTabelaNoticias();
                });
            }
        }
    });
    
    // ================ IMPORTAÇÃO CSV ================================
let arquivoCSV = null;
let dadosCSV = [];

// Abrir modal de importação
document.getElementById('botaoImportarCSV')?.addEventListener('click', () => {
    abrirModalImportar();
});

function abrirModalImportar() {
    const modal = document.getElementById('modalImportarCSV');
    modal.classList.add('ativo');
    document.body.style.overflow = 'hidden';
    emitirSom('modal');
    
    // Resetar campos
    document.getElementById('inputCSV').value = '';
    document.getElementById('previaArquivo').style.display = 'none';
    document.getElementById('previewCSV').style.display = 'none';
    document.getElementById('botaoConfirmarImportar').disabled = true;
    arquivoCSV = null;
    dadosCSV = [];
}

function fecharModalImportar() {
    const modal = document.getElementById('modalImportarCSV');
    modal.classList.remove('ativo');
    document.body.style.overflow = '';
}

// Processar upload do CSV
document.getElementById('inputCSV')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) return;
    
    // Validar tipo
    if (!file.name.endsWith('.csv')) {
        mostrarNotificacao('Por favor, selecione um arquivo CSV válido.', 'error');
        this.value = '';
        return;
    }
    
    // Validar tamanho (5MB)
    if (file.size > 5 * 1024 * 1024) {
        mostrarNotificacao('Arquivo muito grande. Máximo 5MB.', 'error');
        this.value = '';
        return;
    }
    
    arquivoCSV = file;
    
    // Mostrar preview
    document.getElementById('nomeArquivoCSV').textContent = file.name;
    document.getElementById('tamanhoArquivoCSV').textContent = 
        (file.size / 1024).toFixed(2) + ' KB';
    document.getElementById('previaArquivo').style.display = 'flex';
    
    // Ler e processar CSV
    const reader = new FileReader();
    reader.onload = function(event) {
        processarCSV(event.target.result);
    };
    reader.readAsText(file, 'UTF-8');
});

function removerArquivoCSV() {
    document.getElementById('inputCSV').value = '';
    document.getElementById('previaArquivo').style.display = 'none';
    document.getElementById('previewCSV').style.display = 'none';
    document.getElementById('botaoConfirmarImportar').disabled = true;
    arquivoCSV = null;
    dadosCSV = [];
}

function processarCSV(csvText) {
    const linhas = parseCSVCompleto(csvText);

    if (linhas.length < 2) {
        mostrarNotificacao('Arquivo CSV vazio ou inválido.', 'error');
        return;
    }
    
    // Pegar cabeçalhos
    const headers = linhas[0].map(h => String(h || '').replace(/\uFEFF/g, '').trim());
    
    // Verificar cabeçalhos obrigatórios
    const obrigatorios = ['titulo', 'conteudo', 'autor'];
    const faltantes = obrigatorios.filter(o => !headers.includes(o));
    
    if (faltantes.length > 0) {
        mostrarNotificacao(`Campos obrigatórios faltando: ${faltantes.join(', ')}`, 'error');
        return;
    }
    
    // Processar dados
    dadosCSV = [];
    for (let i = 1; i < linhas.length; i++) {
        const valores = linhas[i];
        if (!valores || valores.every(v => String(v || '').trim() === '')) continue;

        const registro = {};
        headers.forEach((header, idx) => {
            registro[header] = (valores[idx] ?? '').toString().trim();
        });
        dadosCSV.push(registro);
    }
    
    if (dadosCSV.length === 0) {
        mostrarNotificacao('Nenhum dado válido encontrado no CSV.', 'error');
        return;
    }
    
    // Mostrar preview
    mostrarPreviewCSV(dadosCSV);
    document.getElementById('botaoConfirmarImportar').disabled = false;
}

function parseCSVCompleto(texto) {
    const resultado = [];
    let linha = [];
    let campo = '';
    let dentroAspas = false;

    for (let i = 0; i < texto.length; i++) {
        const char = texto[i];
        const prox = texto[i + 1];

        if (char === '"') {
            if (dentroAspas && prox === '"') {
                campo += '"';
                i++;
            } else {
                dentroAspas = !dentroAspas;
            }
            continue;
        }

        if (char === ',' && !dentroAspas) {
            linha.push(campo);
            campo = '';
            continue;
        }

        if ((char === '\n' || char === '\r') && !dentroAspas) {
            if (char === '\r' && prox === '\n') i++;
            linha.push(campo);
            campo = '';

            if (linha.some(v => String(v || '').trim() !== '')) {
                resultado.push(linha);
            }
            linha = [];
            continue;
        }

        campo += char;
    }

    if (campo.length > 0 || linha.length > 0) {
        linha.push(campo);
        if (linha.some(v => String(v || '').trim() !== '')) {
            resultado.push(linha);
        }
    }

    return resultado;
}

function mostrarPreviewCSV(dados) {
    const previewDiv = document.getElementById('previewCSV');
    const tabelaDiv = document.getElementById('tabelaPreview');
    const resumoDiv = document.getElementById('resumoImportacao');
    
    // Gerar tabela preview (apenas primeiras 5 linhas)
    const amostra = dados.slice(0, 5);
    const headers = Object.keys(dados[0]);
    
    let tabelaHtml = '<table><thead><tr>';
    headers.forEach(h => {
        tabelaHtml += `<th>${escapeHtml(h)}</th>`;
    });
    tabelaHtml += '</tr></thead><tbody>';
    
    amostra.forEach(linha => {
        tabelaHtml += '<tr>';
        headers.forEach(h => {
            let valor = linha[h] || '';
            if (valor.length > 30) valor = valor.substring(0, 30) + '...';
            tabelaHtml += `<td>${escapeHtml(valor)}</td>`;
        });
        tabelaHtml += '</tr>';
    });
    tabelaHtml += '</tbody></table>';
    
    tabelaDiv.innerHTML = tabelaHtml;
    
    // Resumo
    resumoDiv.innerHTML = `
        <i class="fas fa-info-circle"></i> 
        Total de registros encontrados: <strong>${dados.length}</strong><br>
        <small>Os primeiros 5 registros são mostrados acima como pré-visualização.</small>
    `;
    
    previewDiv.style.display = 'block';
}

// Confirmar importação
document.getElementById('botaoConfirmarImportar')?.addEventListener('click', function() {
    if (!arquivoCSV || dadosCSV.length === 0) {
        mostrarNotificacao('Nenhum arquivo válido para importar.', 'error');
        return;
    }
    
    const ignorarDuplicatas = document.getElementById('ignorarDuplicatas').checked;
    const atualizarExistentes = document.getElementById('atualizarExistentes').checked;
    
    const formData = new FormData();
    formData.append('acao', 'importar_csv');
    formData.append('ignorar_duplicatas', ignorarDuplicatas ? '1' : '0');
    formData.append('atualizar_existentes', atualizarExistentes ? '1' : '0');
    formData.append('dados', JSON.stringify(dadosCSV));
    
    const btn = this;
    const textoOriginal = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Importando...';
    btn.disabled = true;
    
    fetch('processos/processar-noticia.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarNotificacao(data.message, 'success');
            setTimeout(() => {
                location.reload();
            }, 2000);
        } else {
            mostrarNotificacao(data.message, 'error');
            btn.innerHTML = textoOriginal;
            btn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        mostrarNotificacao('Erro ao importar: ' + error.message, 'error');
        btn.innerHTML = textoOriginal;
        btn.disabled = false;
    });
});

    // ================= NOTIFICAÇÕES ======================
    function mostrarNotificacao(mensagem, tipo = 'success') {
        const notif = document.createElement('div');
        const bgColor = tipo === 'success' ? '#28a745' : tipo === 'error' ? '#dc3545' : '#17a2b8';
        
        notif.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 16px 28px;
            background: linear-gradient(135deg, ${bgColor}, ${bgColor}dd);
            color: #fff;
            border-radius: 12px;
            box-shadow: 0 8px 25px ${bgColor}66;
            z-index: 99999;
            font-weight: 600;
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s ease;
        `;
        
    const icon = tipo === 'success' ? 'fa-check-circle' : tipo === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle';
    notif.innerHTML = `<i class="fas ${icon}"></i> ${mensagem}`;
    
    document.body.appendChild(notif);
    emitirSom(tipo);
        
        setTimeout(() => {
            notif.remove();
        }, 3000);
    }
    
    // =============== INICIALIZAÇÃO ================
    document.addEventListener('DOMContentLoaded', function() {
        renderizarTabelaNoticias();

        document.querySelectorAll('#formNoticia input, #formNoticia textarea, #formNoticia select').forEach(el => {
            el.addEventListener('input', salvarEstadoModalNoticia);
            el.addEventListener('change', salvarEstadoModalNoticia);
        });
        
        document.getElementById('botaoNovaNoticia').addEventListener('click', () => abrirModalNoticia());
        document.getElementById('formNoticia').addEventListener('submit', salvarNoticia);
        
        document.getElementById('modalNoticia').addEventListener('click', function(e) {
            if (e.target === this) fecharModalNoticia();
        });

        document.getElementById('botaoCancelarConfirmacao')?.addEventListener('click', fecharModalConfirmacao);
        document.getElementById('botaoConfirmarAcao')?.addEventListener('click', function() {
            if (typeof acaoPendenteConfirmacao === 'function') {
                acaoPendenteConfirmacao();
            }
            fecharModalConfirmacao();
        });
        document.getElementById('modalConfirmacao')?.addEventListener('click', function(e) {
            if (e.target === this) fecharModalConfirmacao();
        });
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && document.getElementById('modalNoticia').classList.contains('ativo')) {
                fecharModalNoticia();
            }
            if (e.key === 'Escape' && document.getElementById('modalConfirmacao').classList.contains('ativo')) {
                fecharModalConfirmacao();
            }
        });
                // Evento de exportação
        document.getElementById('botaoExportarCSV')?.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Botão Exportar CSV clicado'); // Para debug
            exportarParaCSV();
        });
        // Perfil dropdown
        const botaoPerfil = document.getElementById('botaoPerfil');
        const dropdownPerfil = document.getElementById('dropdownPerfil');
        
        if (botaoPerfil && dropdownPerfil) {
            botaoPerfil.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                dropdownPerfil.classList.toggle('visivel');
            });
            
            document.addEventListener('click', function(e) {
                if (!botaoPerfil.contains(e.target) && !dropdownPerfil.contains(e.target)) {
                    dropdownPerfil.classList.remove('visivel');
                }
            });
        }
        // Fechar modal de visualização com ESC
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    const modalVisualizar = document.getElementById('modalVisualizarNoticia');
                    if (modalVisualizar.classList.contains('ativo')) {
                        fecharModalVisualizar();
                    }
                }
            });

            // Fechar modal ao clicar fora
            document.getElementById('modalVisualizarNoticia')?.addEventListener('click', function(e) {
                if (e.target === this) fecharModalVisualizar();
            });

            // Menu mobile - CORRETO
            const botaoMenuMobile = document.getElementById('botaoMenuMobile');
            if (botaoMenuMobile) {
                botaoMenuMobile.addEventListener('click', function() {
                    if (typeof window.openSidebar === 'function') {
                        window.openSidebar();
                    }
                });
            }

        const estadoSalvoRaw = sessionStorage.getItem(CHAVE_ESTADO_MODAL);
        if (estadoSalvoRaw) {
            try {
                const estadoSalvo = JSON.parse(estadoSalvoRaw);
                if (estadoSalvo.modal === 'noticia') {
                    abrirModalNoticia(estadoSalvo.noticiaId ? parseInt(estadoSalvo.noticiaId) : null, estadoSalvo);
                } else if (estadoSalvo.modal === 'visualizar' && estadoSalvo.visualizarId) {
                    visualizarNoticia(parseInt(estadoSalvo.visualizarId));
                }
            } catch (e) {
                limparEstadoModalNoticia();
            }
        }
    });
</script>

<?php include 'includes/footer.php'; ?>
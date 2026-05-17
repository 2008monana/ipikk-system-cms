<?php
/**
 * Página Inicial - Área Restrita IPIKK
 * Gestão completa do conteúdo da página inicial (slider, mensagem do director, parceiros)
 */

$titulo_pagina = 'Página Inicial';

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
// LISTAR PÁGINAS PÚBLICAS PARA O SELECT DE LINKS
// ============================================

$paginas_publicas = [
    ['url' => 'index.php', 'nome' => 'Página Inicial'],
    ['url' => 'sobre-nos.php', 'nome' => 'Quem Somos'],
    ['url' => 'diretor.php', 'nome' => 'Perfil do Director'],
    ['url' => 'orgaos-diretivos.php', 'nome' => 'Órgãos Directivos'],
    ['url' => 'ex-diretores.php', 'nome' => 'Ex-Directores'],
    ['url' => 'normativos.php', 'nome' => 'Normativos'],
    ['url' => 'percurso.php', 'nome' => 'Histórias de Sucesso'],
    ['url' => 'quadro-honra.php', 'nome' => 'Quadro de Honra'],
    ['url' => 'funcionario-destacado.php', 'nome' => 'Funcionários Destacados'],
    ['url' => 'escolas-afiliadas.php', 'nome' => 'Escolas Afiliadas'],
    ['url' => 'oferta-formativa.php', 'nome' => 'Oferta Formativa'],
    ['url' => 'noticias.php', 'nome' => 'Notícias'],
    ['url' => 'galeria.php', 'nome' => 'Galeria'],
    ['url' => 'contatos.php', 'nome' => 'Contactos'],
    ['url' => 'inscricoes.php', 'nome' => 'Inscrições (abertas)'],
    ['url' => 'inscricoes-indisponiveis.php', 'nome' => 'Inscrições (indisponíveis)']
];

$areas_para_links = $db->query("SELECT id, nome, slug FROM areas WHERE ativo = 1 ORDER BY nome")->fetchAll();
$cursos_para_links = $db->query("SELECT id, nome, slug FROM cursos WHERE estado = 'ativo' ORDER BY nome")->fetchAll();

$opcoes_links = [
    'Páginas Principais' => $paginas_publicas,
    'Áreas de Formação' => array_map(function($area) {
        return ['url' => 'area.php?slug=' . $area['slug'], 'nome' => ' ' . $area['nome']];
    }, $areas_para_links),
    'Cursos' => array_map(function($curso) {
        return ['url' => 'curso.php?slug=' . $curso['slug'], 'nome' => ' ' . $curso['nome']];
    }, $cursos_para_links)
];

// ============================================
// BUSCAR DADOS DA PÁGINA INICIAL
// ============================================
$pagina = getPagina('inicio');

$slider = isset($pagina['slider']) && is_array($pagina['slider']) ? $pagina['slider'] : [];

$mensagem_director = isset($pagina['mensagem_director']) && is_array($pagina['mensagem_director']) 
    ? $pagina['mensagem_director'] 
    : [
        'nome' => 'Ferreira Manuel Fragoso',
        'cargo' => 'Director do IPIKK',
        'mensagem' => '',
        'foto' => 'foto/perfil-do-director.jpg',
        'assinatura' => 'Ferreira Manuel Fragoso'
    ];

$matricula = isset($pagina['matricula']) && is_array($pagina['matricula'])
    ? $pagina['matricula']
    : [
        'titulo' => 'Faça a sua matrícula no IPIKK',
        'descricao' => 'Junte-se a nós, usufrua do que temos a oferecer para a sua capacitação profissional.',
        'imagem' => 'foto/matricula.jpg'
    ];

$parceiros = isset($pagina['parceiros']) && is_array($pagina['parceiros']) ? $pagina['parceiros'] : [];

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
                <i class="fas fa-home"></i> Página Inicial
            </h1>
        </div>
    </header>

    <div class="conteudo-pagina">

        <!-- ===== SLIDER PRINCIPAL ===== -->
<div class="secao-conteudo">
    <div class="secao-header">
        <h2 class="secao-titulo">
            <i class="fas fa-images"></i> Slider Principal
            <span class="contador" id="contadorSlides">(<?= count($slider) ?> slides)</span>
        </h2>
        <button type="button" class="btn-adicionar" onclick="adicionarSlide()">
            <i class="fas fa-plus"></i> Adicionar Slide
        </button>
    </div>

    <div id="listaSlides" class="lista-slides">
        <?php if (empty($slider)): ?>
        <div class="empty-state" id="emptySlides">
            <i class="fas fa-images"></i>
            <p>Nenhum slide cadastrado. Clique em "Adicionar Slide" para começar.</p>
        </div>
        <?php else: ?>
            <?php foreach ($slider as $index => $slide): ?>
            <div class="item-slide" data-index="<?= $index ?>">
                <div class="item-header">
                    <span class="item-numero">Slide <?= $index + 1 ?></span>
                    <div class="item-acoes">
                        <button type="button" class="btn-editar" onclick="editarSlide(this)"><i class="fas fa-edit"></i></button>
                        <button type="button" class="btn-eliminar" onclick="eliminarSlide(this)"><i class="fas fa-trash"></i></button>
                    </div>
                </div>

                <!-- PREVIEW DO SLIDE -->
                <div class="item-preview">
                    <div class="preview-imagem">
                        <?php 
                        $img_src = $slide['imagem'] ?? '';
                        if (!empty($img_src) && strpos($img_src, 'http') !== 0) {
                            $img_src = normalizarUrlMidia($img_src, '..');
                        } elseif (empty($img_src)) {
                            $img_src = 'https://via.placeholder.com/1600x900/2c3e50/ffffff?text=Slide';
                        }
                        ?>
                        <img src="<?= htmlspecialchars($img_src) ?>" class="slide-preview-img" onerror="this.src='https://via.placeholder.com/1600x900/2c3e50/ffffff?text=Slide'">
                    </div>
                    <div class="preview-info">
                        <strong><?= htmlspecialchars($slide['titulo'] ?? 'Sem título') ?></strong>
                        <span><?= htmlspecialchars($slide['subtitulo'] ?? '') ?></span>
                        <span class="preview-botao">Botão: <?= htmlspecialchars($slide['botao'] ?? 'Saiba mais') ?></span>
                    </div>
                </div>

                <!-- FORMULÁRIO DE EDIÇÃO (oculto) -->
                <div class="item-form" style="display: none;">
                    <div class="linha-form">
                        <div class="grupo-form">
                            <label>Título *</label>
                            <input type="text" class="campo-form titulo-input" value="<?= htmlspecialchars($slide['titulo'] ?? '') ?>">
                        </div>
                        <div class="grupo-form">
                            <label>Subtítulo</label>
                            <input type="text" class="campo-form subtitulo-input" value="<?= htmlspecialchars($slide['subtitulo'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="linha-form">
                        <div class="grupo-form">
                            <label>Texto do Botão</label>
                            <input type="text" class="campo-form botao-input" value="<?= htmlspecialchars($slide['botao'] ?? 'Saiba mais') ?>">
                        </div>
                        <div class="grupo-form">
                            <label>Link</label>
                            <div class="grupo-link">
                                <select class="campo-form link-select">
                                    <option value="">Selecione uma página...</option>
                                    <?php foreach ($opcoes_links as $grupo => $opcoes): ?>
                                    <optgroup label="<?= htmlspecialchars($grupo) ?>">
                                        <?php foreach ($opcoes as $opcao): ?>
                                        <option value="<?= htmlspecialchars($opcao['url']) ?>" <?= (($slide['link'] ?? '') == $opcao['url']) ? 'selected' : '' ?>><?= htmlspecialchars($opcao['nome']) ?></option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                    <?php endforeach; ?>
                                </select>
                                <small class="info-texto">Ou digite um link personalizado</small>
                                <input type="text" class="campo-form link-input mt-2" placeholder="Link personalizado" value="<?= htmlspecialchars($slide['link'] ?? '') ?>" style="margin-top: 8px;">
                            </div>
                        </div>
                    </div>
                    <div class="grupo-form">
                        <label>Imagem de Fundo</label>
                        <div class="area-upload-pequena" onclick="document.getElementById('slideImg_<?= $index ?>').click()">
                            <i class="fas fa-cloud-upload-alt"></i> Alterar Imagem
                        </div>
                        <input type="file" id="slideImg_<?= $index ?>" accept="image/*" style="display: none;" data-index="<?= $index ?>" onchange="previewSlideImagem(this)">
                        <div class="preview-imagem-pequena" id="previewSlide_<?= $index ?>">
                            <?php 
                            $thumb_src = $slide['imagem'] ?? '';
                            if (!empty($thumb_src) && strpos($thumb_src, 'http') !== 0) {
                                $thumb_src = normalizarUrlMidia($thumb_src, '..');
                            } elseif (empty($thumb_src)) {
                                $thumb_src = 'https://via.placeholder.com/1600x900/2c3e50/ffffff?text=Slide';
                            }
                            ?>
                            <img src="<?= htmlspecialchars($thumb_src) ?>" class="slide-preview-pequena">
                        </div>
                    </div>
                    <div class="item-actions">
                        <button type="button" class="btn-salvar-item" onclick="salvarSlide(this)">Salvar</button>
                        <button type="button" class="btn-cancelar-item" onclick="cancelarEdicaoSlide(this)">Cancelar</button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

        <!-- ===== MENSAGEM DO DIRECTOR (com botão salvar individual) ===== -->
        <div class="secao-conteudo">
            <div class="secao-header">
                <h2 class="secao-titulo">
                    <i class="fas fa-user-tie"></i> Mensagem do Director
                </h2>
                <button type="button" class="btn-salvar-secao" onclick="salvarMensagemDirector()">
                    <i class="fas fa-save"></i> Salvar Mensagem
                </button>
            </div>
            <div class="linha-form">
                <div class="grupo-form">
                    <label><i class="fas fa-user"></i> Nome do Director</label>
                    <input type="text" id="directorNome" class="campo-form" value="<?= htmlspecialchars($mensagem_director['nome'] ?? '') ?>">
                </div>
                <div class="grupo-form">
                    <label><i class="fas fa-briefcase"></i> Cargo</label>
                    <input type="text" id="directorCargo" class="campo-form" value="<?= htmlspecialchars($mensagem_director['cargo'] ?? '') ?>">
                </div>
            </div>
            <div class="grupo-form">
                <label><i class="fas fa-align-left"></i> Mensagem</label>
                <textarea id="directorMensagem" class="campo-form area-texto" rows="8"><?= htmlspecialchars($mensagem_director['mensagem'] ?? '') ?></textarea>
                <small class="info-texto">Use parágrafos separados por linhas em branco.</small>
            </div>
            <div class="linha-form">
                <div class="grupo-form">
                    <label><i class="fas fa-signature"></i> Assinatura</label>
                    <input type="text" id="directorAssinatura" class="campo-form" value="<?= htmlspecialchars($mensagem_director['assinatura'] ?? '') ?>">
                </div>
                <div class="grupo-form">
                    <label><i class="fas fa-image"></i> Foto do Director</label>
                    <div class="area-upload-pequena" onclick="document.getElementById('directorFotoInput').click()">
                        <i class="fas fa-cloud-upload-alt"></i> Alterar Foto
                    </div>
                    <input type="file" id="directorFotoInput" accept="image/*" style="display: none;" onchange="previewDirectorFoto(this)">
                    <div class="preview-foto-pequena" id="previewDirectorFoto">
                        <img src="<?= htmlspecialchars(normalizarUrlMidia($mensagem_director['foto'] ?? 'foto/perfil-do-director.jpg', '..')) ?>" class="director-foto-preview">
                    </div>
                    <button type="button" class="btn-remover-foto" id="btnRemoverFotoDirector" onclick="confirmarRemoverFotoDirector()" style="<?= (($mensagem_director['foto'] ?? 'foto/perfil-do-director.jpg') === 'foto/perfil-do-director.jpg') ? 'display:none;' : '' ?>">
                        <i class="fas fa-trash"></i> Remover Foto
                    </button>
                </div>
            </div>
        </div>

        <!-- ===== MATRÍCULA (com botão salvar individual e textarea maior) ===== -->
        <div class="secao-conteudo">
            <div class="secao-header">
                <h2 class="secao-titulo">
                    <i class="fas fa-file-signature"></i> Secção de Matrícula
                </h2>
                <button type="button" class="btn-salvar-secao" onclick="salvarMatricula()">
                    <i class="fas fa-save"></i> Salvar Matrícula
                </button>
            </div>
            <div class="grupo-form">
                <label><i class="fas fa-heading"></i> Título</label>
                <input type="text" id="matriculaTitulo" class="campo-form" value="<?= htmlspecialchars($matricula['titulo'] ?? '') ?>">
            </div>
            <div class="grupo-form">
                <label><i class="fas fa-align-left"></i> Descrição</label>
                <textarea id="matriculaDescricao" class="campo-form area-texto" rows="5"><?= htmlspecialchars($matricula['descricao'] ?? '') ?></textarea>
                <small class="info-texto">Descreva o processo de matrícula e os benefícios.</small>
            </div>
            <div class="grupo-form">
                <label><i class="fas fa-image"></i> Imagem</label>
                <div class="area-upload-pequena" onclick="document.getElementById('matriculaImgInput').click()">
                    <i class="fas fa-cloud-upload-alt"></i> Alterar Imagem
                </div>
                <input type="file" id="matriculaImgInput" accept="image/*" style="display: none;" onchange="previewMatriculaImagem(this)">
                <div class="preview-foto-pequena" id="previewMatriculaImg">
                    <img src="<?= htmlspecialchars(normalizarUrlMidia($matricula['imagem'] ?? 'foto/matricula.jpg', '..')) ?>" class="matricula-img-preview">
                </div>
                <button type="button" class="btn-remover-foto" id="btnRemoverFotoMatricula" onclick="confirmarRemoverFotoMatricula()" style="<?= (($matricula['imagem'] ?? 'foto/matricula.jpg') === 'foto/matricula.jpg') ? 'display:none;' : '' ?>">
                    <i class="fas fa-trash"></i> Remover Foto
                </button>
            </div>
        </div>

        <!-- ===== PARCEIROS (corrigido - SEM DUPLICAÇÃO) ===== -->
        <div class="secao-conteudo">
            <div class="secao-header">
                <h2 class="secao-titulo">
                    <i class="fas fa-handshake"></i> Parceiros
                    <span class="contador" id="contadorParceiros">(<?= count($parceiros) ?> parceiros)</span>
                </h2>
                <button type="button" class="btn-adicionar" onclick="adicionarParceiro()">
                    <i class="fas fa-plus"></i> Adicionar Parceiro
                </button>
            </div>

            <div id="listaParceiros" class="lista-parceiros">
                <?php if (empty($parceiros)): ?>
                <div class="empty-state" id="emptyParceiros">
                    <i class="fas fa-handshake"></i>
                    <p>Nenhum parceiro cadastrado. Clique em "Adicionar Parceiro" para começar.</p>
                </div>
                <?php else: ?>
                    <?php foreach ($parceiros as $index => $parceiro): ?>
                    <div class="item-parceiro" data-index="<?= $index ?>">
                        <div class="item-header">
                            <span class="item-numero"><?= $index + 1 ?></span>
                            <div class="item-acoes">
                                <button type="button" class="btn-editar" onclick="editarParceiro(this)"><i class="fas fa-edit"></i></button>
                                <button type="button" class="btn-eliminar" onclick="eliminarParceiro(this)"><i class="fas fa-trash"></i></button>
                            </div>
                        </div>
                        <div class="item-preview">
                        <div class="preview-logo">
                            <?php 
                            $logo_src = $parceiro['logo'] ?? '';
                            if (!empty($logo_src) && strpos($logo_src, 'http') !== 0) {
                                $logo_src = normalizarUrlMidia($logo_src, '..');
                            } elseif (empty($logo_src)) {
                                $logo_src = 'https://via.placeholder.com/150x60/555/fff?text=Parceiro';
                            }
                            ?>
                            <img src="<?= htmlspecialchars($logo_src) ?>" class="parceiro-logo-preview" onerror="this.src='https://via.placeholder.com/150x60/555/fff?text=Parceiro'">
                        </div>
                        </div>
                        <div class="item-form" style="display: none;">
                            <div class="linha-form">
                                <div class="grupo-form">
                                    <label>Nome do Parceiro</label>
                                    <input type="text" class="campo-form nome-input" value="<?= htmlspecialchars($parceiro['nome'] ?? '') ?>">
                                </div>
                                <div class="grupo-form">
                                    <label>Link do Site</label>
                                    <input type="url" class="campo-form link-input" value="<?= htmlspecialchars($parceiro['link'] ?? '#') ?>">
                                </div>
                            </div>
                            <div class="grupo-form">
                                <label>Logotipo</label>
                                <div class="area-upload-pequena" onclick="document.getElementById('parceiroLogo_<?= $index ?>').click()">
                                    <i class="fas fa-cloud-upload-alt"></i> Alterar Logotipo
                                </div>
                                <input type="file" id="parceiroLogo_<?= $index ?>" accept="image/*" style="display: none;" data-index="<?= $index ?>" onchange="previewParceiroLogo(this)">
                                <div class="preview-logo-pequena" id="previewParceiro_<?= $index ?>">
                                    <img src="<?= htmlspecialchars($parceiro['logo'] ?? 'https://via.placeholder.com/150x60/555/fff?text=Parceiro') ?>" class="parceiro-logo-preview-pequena">
                                </div>
                            </div>
                            <div class="item-actions">
                                <button type="button" class="btn-salvar-item" onclick="salvarParceiro(this)">Salvar</button>
                                <button type="button" class="btn-cancelar-item" onclick="cancelarEdicaoParceiro(this)">Cancelar</button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
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
        <div class="modal-confirmacao-icone"><i class="fas fa-exclamation-triangle"></i></div>
        <h3 id="confirmacaoTitulo">Confirmar ação</h3>
        <p id="confirmacaoTexto">Tem certeza que deseja continuar?</p>
        <div class="modal-confirmacao-botoes">
            <button class="botao-cancelar-modal" id="btnCancelarConfirmacao">Cancelar</button>
            <button class="botao-confirmar-modal" id="btnConfirmarAcao">Confirmar</button>
        </div>
    </div>
</div>

<style>
/* ===== ESTILOS ADMIN INICIO ===== */

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
    max-width: 1400px; 
    margin: 0 auto; 
}

/* Secções */
.secao-conteudo { 
    background: white; 
    border-radius: 20px; 
    padding: 25px; 
    margin-bottom: 30px; 
    box-shadow: 0 2px 8px rgba(0,0,0,0.05); 
    transition: box-shadow 0.3s ease; 
}

.secao-conteudo:hover { 
    box-shadow: 0 4px 16px rgba(0,0,0,0.1); 
}

/* Cabeçalho das secções */
.secao-header { 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    margin-bottom: 20px; 
    padding-bottom: 15px; 
    border-bottom: 2px solid #eef2f6; 
    flex-wrap: wrap; 
    gap: 15px; 
}

.secao-titulo { 
    font-size: 18px; 
    font-weight: 600; 
    margin: 0; 
    display: flex; 
    align-items: center; 
    gap: 12px; 
    color: #003072; 
}

.secao-titulo i { 
    color: #0a9396; 
    font-size: 20px; 
}

/* ===== CONTADOR ESTILIZADO ===== */
.secao-titulo .contador {
    background: linear-gradient(135deg, #0a9396 0%, #008bb5 100%);
    color: white;
    font-size: 12px;
    font-weight: 600;
    padding: 4px 12px;
    border-radius: 30px;
    letter-spacing: 0.5px;
    box-shadow: 0 2px 4px rgba(10,147,150,0.2);
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.secao-titulo .contador::before {
    content: '\f0a4';
    font-family: 'Font Awesome 6 Free';
    font-weight: 900;
    font-size: 10px;
}

/* Contador específico para parceiros */
.secao-titulo .contador-parceiros {
    background: linear-gradient(135deg, #2e7d32 0%, #1b5e20 100%);
}

.secao-titulo .contador-parceiros::before {
    content: '\f2b5';
}

/* Botões */
.btn-adicionar, .btn-salvar-secao { 
    background: #e8f5e9; 
    border: none; 
    padding: 8px 18px; 
    border-radius: 10px; 
    font-size: 13px; 
    font-weight: 600; 
    cursor: pointer; 
    color: #2e7d32; 
    transition: all 0.2s ease; 
    display: inline-flex; 
    align-items: center; 
    gap: 8px; 
}

.btn-adicionar:hover, .btn-salvar-secao:hover { 
    background: #c8e6c9; 
    transform: translateY(-1px); 
}

.btn-salvar-secao { 
    background: #e3f2fd; 
    color: #0288d1; 
}

.btn-salvar-secao:hover { 
    background: #0288d1; 
    color: white; 
}

/* Listas */
.lista-slides, .lista-parceiros { 
    display: flex; 
    flex-direction: column; 
    gap: 20px; 
}

/* Itens */
.item-slide, .item-parceiro { 
    background: #f8f9fa; 
    border-radius: 16px; 
    padding: 20px; 
    border: 1px solid #eef2f6; 
    transition: all 0.3s ease; 
}

.item-slide:hover, .item-parceiro:hover { 
    border-color: #0a9396; 
    box-shadow: 0 4px 12px rgba(10,147,150,0.1); 
}

/* Cabeçalho dos itens */
.item-header { 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    margin-bottom: 15px; 
    padding-bottom: 10px; 
    border-bottom: 1px solid #eef2f6; 
}

.item-numero { 
    font-weight: 700; 
    color: #0a9396; 
    font-size: 13px; 
    background: rgba(10,147,150,0.1); 
    padding: 4px 14px; 
    border-radius: 30px; 
    letter-spacing: 0.3px;
}

/* Ações dos itens */
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
}

.btn-editar { 
    background: #e3f2fd; 
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

/* Preview dos itens */
.item-preview { 
    display: flex; 
    align-items: center; 
    gap: 20px; 
    flex-wrap: wrap; 
}

.preview-imagem { 
    width: 120px; 
    height: 80px; 
    border-radius: 12px; 
    overflow: hidden; 
    background: #e0e4e8; 
    flex-shrink: 0; 
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}

.slide-preview-img { 
    width: 100%; 
    height: 100%; 
    object-fit: cover; 
}

.preview-logo { 
    width: 80px; 
    height: 60px; 
    border-radius: 12px; 
    overflow: hidden; 
    background: white; 
    display: flex; 
    align-items: center; 
    justify-content: center; 
    flex-shrink: 0; 
    border: 1px solid #eef2f6; 
    padding: 8px;
}

.parceiro-logo-preview { 
    max-width: 100%; 
    max-height: 100%; 
    object-fit: contain; 
}

.preview-info { 
    flex: 1; 
}

.preview-info strong { 
    display: block; 
    font-size: 15px; 
    color: #003072; 
    margin-bottom: 6px; 
}

.preview-info span { 
    font-size: 13px; 
    color: #666; 
    display: block; 
    margin-bottom: 4px; 
}

.preview-botao { 
    font-size: 11px; 
    color: #0a9396; 
    margin-top: 6px; 
    display: inline-block; 
    background: rgba(10,147,150,0.1); 
    padding: 3px 10px; 
    border-radius: 20px; 
}

/* Formulários */
.item-form { 
    margin-top: 15px; 
    padding-top: 15px; 
    border-top: 1px solid #eef2f6; 
    animation: fadeIn 0.3s ease; 
}

@keyframes fadeIn { 
    from { opacity: 0; transform: translateY(-10px); } 
    to { opacity: 1; transform: translateY(0); } 
}

.linha-form { 
    display: grid; 
    grid-template-columns: repeat(2, 1fr); 
    gap: 15px; 
    margin-bottom: 15px; 
}

@media (max-width: 768px) { 
    .linha-form { grid-template-columns: 1fr; } 
}

.grupo-form { 
    margin-bottom: 15px; 
}

.grupo-form label { 
    display: block; 
    font-weight: 600; 
    margin-bottom: 6px; 
    font-size: 12px; 
    color: #2c3e50; 
}

.grupo-form label i { 
    color: #0a9396; 
    margin-right: 6px; 
}

.campo-form { 
    width: 100%; 
    padding: 10px 14px; 
    border: 1px solid #e0e4e8; 
    border-radius: 10px; 
    font-size: 14px; 
    transition: all 0.2s ease; 
    background: white; 
}

.campo-form:focus { 
    outline: none; 
    border-color: #0a9396; 
    box-shadow: 0 0 0 3px rgba(10,147,150,0.1); 
}

.area-texto { 
    resize: vertical; 
    font-family: inherit; 
}

/* Grupo de link */
.grupo-link { 
    display: flex; 
    flex-direction: column; 
    gap: 8px; 
}

.grupo-link select, .grupo-link input { 
    width: 100%; 
}

.grupo-link select optgroup { 
    font-weight: 600; 
    color: #003072; 
}

.mt-2 { 
    margin-top: 8px; 
}

.info-texto { 
    font-size: 11px; 
    color: #6c757d; 
    margin-top: 4px; 
    display: block; 
}

/* Upload de imagens */
.area-upload-pequena { 
    border: 1px dashed #cbd5e1; 
    border-radius: 10px; 
    padding: 10px 12px; 
    text-align: center; 
    cursor: pointer; 
    font-size: 12px; 
    display: inline-block; 
    background: white; 
    transition: all 0.2s ease; 
}

.area-upload-pequena:hover { 
    border-color: #0a9396; 
    background: #f0fdfa; 
}

.area-upload-pequena i { 
    margin-right: 6px; 
    color: #0a9396; 
}

/* Previews de imagens */
.preview-imagem-pequena, 
.preview-foto-pequena, 
.preview-logo-pequena { 
    margin-top: 10px; 
}

.slide-preview-pequena { 
    width: 100px; 
    height: 60px; 
    border-radius: 10px; 
    object-fit: cover; 
    border: 1px solid #eef2f6; 
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.director-foto-preview { 
    width: 80px; 
    height: 80px; 
    border-radius: 50%; 
    object-fit: cover; 
    border: 3px solid #0a9396; 
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.matricula-img-preview { 
    width: 100px; 
    height: 100px; 
    border-radius: 12px; 
    object-fit: cover; 
    border: 2px solid #0a9396; 
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}
.btn-remover-foto {
    margin-top: 10px;
    border: 1px solid #ef4444;
    background: #fee2e2;
    color: #b91c1c;
    border-radius: 8px;
    padding: 8px 12px;
    font-weight: 600;
    cursor: pointer;
}
.btn-remover-foto:hover { background:#fecaca; }


.parceiro-logo-preview-pequena { 
    width: 70px; 
    height: 70px; 
    border-radius: 10px; 
    object-fit: contain; 
    background: white; 
    border: 1px solid #eef2f6; 
    padding: 6px;
}

/* Ações dos itens */
.item-actions { 
    display: flex; 
    gap: 10px; 
    margin-top: 15px; 
    justify-content: flex-end; 
}

.btn-salvar-item, .btn-cancelar-item { 
    padding: 8px 22px; 
    border-radius: 10px; 
    font-weight: 600; 
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
    background: #f0f0f0; 
    color: #666; 
}

.btn-cancelar-item:hover { 
    background: #e0e0e0; 
    transform: translateY(-1px); 
}

/* Empty state */
.empty-state { 
    text-align: center; 
    padding: 50px 20px; 
    color: #999; 
    background: #fafcfc; 
    border-radius: 16px; 
}

.empty-state i { 
    font-size: 48px; 
    color: #cbd5e1; 
    margin-bottom: 15px; 
    display: block; 
}

.empty-state p { 
    font-size: 14px; 
    margin-bottom: 15px; 
}

/* Ações principais */
.secao-acoes { 
    display: flex; 
    justify-content: flex-end; 
    gap: 15px; 
    margin-top: 20px; 
    padding-top: 20px; 
    border-top: 2px solid #eef2f6; 
}

.btn-grande { 
    padding: 12px 28px; 
    font-size: 14px; 
    font-weight: 600; 
}

.btn-primario {
    background: linear-gradient(135deg, #003072 0%, #0a9396 100%);
    color: white;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-primario:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,48,114,0.3);
}

.btn-secundario { 
    background: #f0f0f0; 
    border: none; 
    padding: 12px 24px; 
    border-radius: 12px; 
    font-weight: 600; 
    cursor: pointer; 
    color: #666; 
    transition: all 0.2s ease; 
}

.btn-secundario:hover { 
    background: #e0e0e0; 
    transform: translateY(-1px); 
}

/* Modal de confirmação */
.modal-confirmacao { 
    display: none; 
    position: fixed; 
    inset: 0; 
    background: rgba(0,0,0,0.7); 
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
    border-radius: 24px; 
    padding: 30px; 
    max-width: 400px; 
    width: 90%; 
    text-align: center; 
    animation: zoomIn 0.2s ease; 
}

@keyframes zoomIn { 
    from { opacity: 0; transform: scale(0.9); } 
    to { opacity: 1; transform: scale(1); } 
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

.modal-confirmacao-caixa h3 {
    margin-bottom: 10px;
    color: #2c3e50;
}

.modal-confirmacao-caixa p {
    color: #666;
    margin-bottom: 20px;
}

.modal-confirmacao-botoes { 
    display: flex; 
    gap: 15px; 
    justify-content: center; 
    margin-top: 25px; 
}

.botao-cancelar-modal, 
.botao-confirmar-modal { 
    padding: 10px 24px; 
    border-radius: 10px; 
    font-weight: 600; 
    cursor: pointer; 
    border: none; 
    transition: all 0.2s ease; 
}

.botao-cancelar-modal { 
    background: #f0f0f0; 
    color: #666; 
}

.botao-cancelar-modal:hover { 
    background: #e0e0e0; 
}

.botao-confirmar-modal { 
    background: #dc3545; 
    color: white; 
}

.botao-confirmar-modal:hover { 
    background: #c82333; 
    transform: scale(1.02); 
}

/* Notificação flutuante */
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
    font-size: 14px; 
    display: flex; 
    align-items: center; 
    gap: 10px; 
    box-shadow: 0 4px 15px rgba(0,0,0,0.2); 
    animation: slideInRight 0.3s ease; 
}

.notificacao.erro { 
    background: linear-gradient(135deg, #dc3545, #c82333); 
}

@keyframes slideInRight { 
    from { transform: translateX(100%); opacity: 0; } 
    to { transform: translateX(0); opacity: 1; } 
}

/* ===== RESPONSIVIDADE ===== */
@media (max-width: 768px) { 
    .secao-conteudo { 
        padding: 20px; 
    }
    
    .item-preview { 
        flex-direction: column; 
        text-align: center; 
    }
    
    .preview-imagem, 
    .preview-logo { 
        margin: 0 auto; 
    }
    
    .secao-acoes { 
        flex-direction: column; 
    }
    
    .btn-grande, 
    .btn-secundario { 
        width: 100%; 
        justify-content: center; 
    }
    
    .item-actions { 
        flex-direction: column; 
    }
    
    .btn-salvar-item, 
    .btn-cancelar-item { 
        width: 100%; 
        justify-content: center; 
    }
    
    .secao-header {
        flex-direction: column;
        align-items: flex-start;
    }
}

@media (max-width: 480px) { 
    .secao-titulo { 
        font-size: 16px; 
        flex-wrap: wrap;
    }
    
    .secao-titulo .contador {
        font-size: 10px;
        padding: 3px 10px;
    }
    
    .item-numero { 
        font-size: 11px; 
    }
    
    .campo-form { 
        font-size: 13px; 
    }
}
</style>

<script>
let slideCounter = <?= count($slider) ?>;
let parceiroCounter = <?= count($parceiros) ?>;
let pendingSlideImages = {};
let pendingParceiroLogos = {};
let pendingDirectorFoto = null;
let pendingMatriculaImg = null;

function mostrarNotificacao(mensagem, tipo = 'sucesso') {
    const notif = document.createElement('div');
    notif.className = `notificacao ${tipo}`;
    notif.innerHTML = `<i class="fas fa-${tipo === 'sucesso' ? 'check-circle' : 'exclamation-circle'}"></i> ${mensagem}`;
    document.body.appendChild(notif);
    setTimeout(() => notif.remove(), 3000);
}

function initLinkSelect(item) {
    const select = item.querySelector('.link-select');
    const input = item.querySelector('.link-input');
    if (!select || !input) return;
    select.addEventListener('change', function() { if (this.value) input.value = this.value; });
    input.addEventListener('input', function() {
        const option = Array.from(select.options).find(opt => opt.value === this.value);
        if (option) select.value = this.value;
        else select.value = '';
    });
    if (input.value && input.value !== '#') {
        const option = Array.from(select.options).find(opt => opt.value === input.value);
        if (option) select.value = input.value;
    }
}

function previewSlideImagem(input) {
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
        const previewDiv = document.getElementById(`previewSlide_${index}`);
        if (previewDiv) previewDiv.innerHTML = `<img src="${e.target.result}" class="slide-preview-pequena">`;
        pendingSlideImages[index] = file;
    };
    reader.readAsDataURL(file);
}

function previewDirectorFoto(input) {
    const file = input.files[0];
    if (!file) return;
    if (file.size > 5 * 1024 * 1024) {
        mostrarNotificacao('Arquivo muito grande. Máximo 5MB', 'erro');
        input.value = '';
        return;
    }
    pendingDirectorFoto = file;
    const reader = new FileReader();
    reader.onload = function(e) {
        const previewDiv = document.getElementById('previewDirectorFoto');
        previewDiv.innerHTML = `<img src="${e.target.result}" class="director-foto-preview">`;
    };
    reader.readAsDataURL(file);
}

function previewMatriculaImagem(input) {
    const file = input.files[0];
    if (!file) return;
    if (file.size > 5 * 1024 * 1024) {
        mostrarNotificacao('Arquivo muito grande. Máximo 5MB', 'erro');
        input.value = '';
        return;
    }
    pendingMatriculaImg = file;
    const reader = new FileReader();
    reader.onload = function(e) {
        const previewDiv = document.getElementById('previewMatriculaImg');
        previewDiv.innerHTML = `<img src="${e.target.result}" class="matricula-img-preview">`;
    };
    reader.readAsDataURL(file);
}

function previewParceiroLogo(input) {
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
        const previewDiv = document.getElementById(`previewParceiro_${index}`);
        if (previewDiv) previewDiv.innerHTML = `<img src="${e.target.result}" class="parceiro-logo-preview-pequena">`;
        pendingParceiroLogos[index] = file;
    };
    reader.readAsDataURL(file);
}

function adicionarSlide() {
    const container = document.getElementById('listaSlides');
    const emptyState = document.getElementById('emptySlides');
    if (emptyState) emptyState.style.display = 'none';
    const novoId = slideCounter++;
    const div = document.createElement('div');
    div.className = 'item-slide';
    div.setAttribute('data-novo', 'true');
    div.setAttribute('data-temp-id', novoId);
    div.innerHTML = `
        <div class="item-header"><span class="item-numero">Novo Slide</span><div class="item-acoes"><button type="button" class="btn-eliminar" onclick="removerNovoSlide(this)"><i class="fas fa-trash"></i></button></div></div>
        <div class="item-form" style="display: block;">
            <div class="linha-form"><div class="grupo-form"><label>Título *</label><input type="text" class="campo-form titulo-input" placeholder="Título"></div><div class="grupo-form"><label>Subtítulo</label><input type="text" class="campo-form subtitulo-input" placeholder="Subtítulo"></div></div>
            <div class="linha-form"><div class="grupo-form"><label>Texto do Botão</label><input type="text" class="campo-form botao-input" value="Saiba mais"></div><div class="grupo-form"><label>Link</label><div class="grupo-link"><select class="campo-form link-select"><option value="">Selecione uma página...</option><?php foreach ($opcoes_links as $grupo => $opcoes): ?><optgroup label="<?= htmlspecialchars($grupo) ?>"><?php foreach ($opcoes as $opcao): ?><option value="<?= htmlspecialchars($opcao['url']) ?>"><?= htmlspecialchars($opcao['nome']) ?></option><?php endforeach; ?></optgroup><?php endforeach; ?></select><small class="info-texto">Ou digite um link personalizado</small><input type="text" class="campo-form link-input mt-2" placeholder="Link personalizado" style="margin-top: 8px;"></div></div></div>
            <div class="grupo-form"><label>Imagem</label><div class="area-upload-pequena" onclick="document.getElementById('slideImgNovo_${novoId}').click()"><i class="fas fa-cloud-upload-alt"></i> Selecionar Imagem</div><input type="file" id="slideImgNovo_${novoId}" accept="image/*" style="display: none;" data-temp-id="${novoId}" onchange="previewNovoSlideImagem(this)"><div class="preview-imagem-pequena" id="previewNovoSlide_${novoId}"></div></div>
            <div class="item-actions"><button type="button" class="btn-salvar-item" onclick="salvarNovoSlide(this)">Adicionar</button><button type="button" class="btn-cancelar-item" onclick="removerNovoSlide(this)">Cancelar</button></div>
        </div>
    `;
    container.appendChild(div);
    initLinkSelect(div);
}

function previewNovoSlideImagem(input) {
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
        const previewDiv = document.getElementById(`previewNovoSlide_${tempId}`);
        if (previewDiv) previewDiv.innerHTML = `<img src="${e.target.result}" class="slide-preview-pequena">`;
        pendingSlideImages[tempId] = file;
    };
    reader.readAsDataURL(file);
}

function removerNovoSlide(btn) {
    const item = btn.closest('.item-slide');
    if (item) {
        const tempId = item.dataset.tempId;
        if (tempId) delete pendingSlideImages[tempId];
        item.remove();
        const container = document.getElementById('listaSlides');
        if (container.children.length === 0) {
            const emptyState = document.getElementById('emptySlides');
            if (emptyState) emptyState.style.display = 'block';
        }
    }
}

function editarSlide(btn) {
    const item = btn.closest('.item-slide');
    const previewDiv = item.querySelector('.item-preview');
    const formDiv = item.querySelector('.item-form');
    if (previewDiv) previewDiv.style.display = 'none';
    if (formDiv) formDiv.style.display = 'block';
    initLinkSelect(item);
}

function cancelarEdicaoSlide(btn) {
    const item = btn.closest('.item-slide');
    const previewDiv = item.querySelector('.item-preview');
    const formDiv = item.querySelector('.item-form');
    if (previewDiv) previewDiv.style.display = 'flex';
    if (formDiv) formDiv.style.display = 'none';
}

function salvarSlide(btn) {
    const item = btn.closest('.item-slide');
    const index = item.dataset.index;
    const titulo = item.querySelector('.titulo-input')?.value;
    const subtitulo = item.querySelector('.subtitulo-input')?.value;
    const botao = item.querySelector('.botao-input')?.value;
    const link = item.querySelector('.link-input')?.value;
    const imagemFile = pendingSlideImages[index];
    if (!titulo) { mostrarNotificacao('Título do slide é obrigatório', 'erro'); return; }
    const formData = new FormData();
    formData.append('action', 'salvar_slide');
    formData.append('index', index);
    formData.append('titulo', titulo);
    formData.append('subtitulo', subtitulo);
    formData.append('botao', botao);
    formData.append('link', link || '#');
    if (imagemFile) formData.append('imagem', imagemFile);
    fetch('processos/processar-inicio.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => { if (data.success) { mostrarNotificacao(data.message, 'sucesso'); setTimeout(() => location.reload(), 1000); } else { mostrarNotificacao(data.message, 'erro'); } });
}

function salvarNovoSlide(btn) {
    const item = btn.closest('.item-slide');
    const tempId = item.dataset.tempId;
    const titulo = item.querySelector('.titulo-input')?.value;
    const subtitulo = item.querySelector('.subtitulo-input')?.value;
    const botao = item.querySelector('.botao-input')?.value;
    const link = item.querySelector('.link-input')?.value;
    const imagemFile = pendingSlideImages[tempId];
    if (!titulo) { mostrarNotificacao('Título do slide é obrigatório', 'erro'); return; }
    const formData = new FormData();
    formData.append('action', 'salvar_slide');
    formData.append('titulo', titulo);
    formData.append('subtitulo', subtitulo);
    formData.append('botao', botao);
    formData.append('link', link || '#');
    if (imagemFile) formData.append('imagem', imagemFile);
    fetch('processos/processar-inicio.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => { if (data.success) { mostrarNotificacao(data.message, 'sucesso'); setTimeout(() => location.reload(), 1000); } else { mostrarNotificacao(data.message, 'erro'); } });
}

function eliminarSlide(btn) {
    const modal = document.getElementById('modalConfirmacao');
    const item = btn.closest('.item-slide');
    const index = item.dataset.index;
    document.getElementById('confirmacaoTitulo').textContent = 'Eliminar Slide';
    document.getElementById('confirmacaoTexto').textContent = 'Tem certeza que deseja eliminar este slide permanentemente?';
    const confirmar = document.getElementById('btnConfirmarAcao');
    const novoConfirmar = confirmar.cloneNode(true);
    confirmar.parentNode.replaceChild(novoConfirmar, confirmar);
    novoConfirmar.onclick = async () => {
        try {
            const response = await fetch('processos/processar-inicio.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: new URLSearchParams({ action: 'eliminar_slide', index: index }) });
            const data = await response.json();
            if (data.success) { mostrarNotificacao(data.message, 'sucesso'); location.reload(); } else { mostrarNotificacao(data.message, 'erro'); }
        } catch (error) { mostrarNotificacao('Erro ao eliminar', 'erro'); }
        modal.classList.remove('ativo');
    };
    modal.classList.add('ativo');
}

function adicionarParceiro() {
    const container = document.getElementById('listaParceiros');
    const emptyState = document.getElementById('emptyParceiros');
    if (emptyState) emptyState.style.display = 'none';
    const novoId = parceiroCounter++;
    const div = document.createElement('div');
    div.className = 'item-parceiro';
    div.setAttribute('data-novo', 'true');
    div.setAttribute('data-temp-id', novoId);
    div.innerHTML = `
        <div class="item-header"><span class="item-numero">Novo</span><div class="item-acoes"><button type="button" class="btn-eliminar" onclick="removerNovoParceiro(this)"><i class="fas fa-trash"></i></button></div></div>
        <div class="item-form" style="display: block;">
            <div class="linha-form"><div class="grupo-form"><label>Nome</label><input type="text" class="campo-form nome-input" placeholder="Nome do parceiro"></div><div class="grupo-form"><label>Link</label><input type="url" class="campo-form link-input" value="#"></div></div>
            <div class="grupo-form"><label>Logotipo</label><div class="area-upload-pequena" onclick="document.getElementById('parceiroLogoNovo_${novoId}').click()"><i class="fas fa-cloud-upload-alt"></i> Selecionar Logotipo</div><input type="file" id="parceiroLogoNovo_${novoId}" accept="image/*" style="display: none;" data-temp-id="${novoId}" onchange="previewNovoParceiroLogo(this)"><div class="preview-logo-pequena" id="previewNovoParceiro_${novoId}"></div></div>
            <div class="item-actions"><button type="button" class="btn-salvar-item" onclick="salvarNovoParceiro(this)">Adicionar</button><button type="button" class="btn-cancelar-item" onclick="removerNovoParceiro(this)">Cancelar</button></div>
        </div>
    `;
    container.appendChild(div);
}

function previewNovoParceiroLogo(input) {
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
        const previewDiv = document.getElementById(`previewNovoParceiro_${tempId}`);
        if (previewDiv) previewDiv.innerHTML = `<img src="${e.target.result}" class="parceiro-logo-preview-pequena">`;
        pendingParceiroLogos[tempId] = file;
    };
    reader.readAsDataURL(file);
}

function removerNovoParceiro(btn) {
    const item = btn.closest('.item-parceiro');
    if (item) {
        const tempId = item.dataset.tempId;
        if (tempId) delete pendingParceiroLogos[tempId];
        item.remove();
        const container = document.getElementById('listaParceiros');
        if (container.children.length === 0) {
            const emptyState = document.getElementById('emptyParceiros');
            if (emptyState) emptyState.style.display = 'block';
        }
    }
}

function editarParceiro(btn) {
    const item = btn.closest('.item-parceiro');
    const previewDiv = item.querySelector('.item-preview');
    const formDiv = item.querySelector('.item-form');
    if (previewDiv) previewDiv.style.display = 'none';
    if (formDiv) formDiv.style.display = 'block';
}

function cancelarEdicaoParceiro(btn) {
    const item = btn.closest('.item-parceiro');
    const previewDiv = item.querySelector('.item-preview');
    const formDiv = item.querySelector('.item-form');
    if (previewDiv) previewDiv.style.display = 'flex';
    if (formDiv) formDiv.style.display = 'none';
}

function salvarParceiro(btn) {
    const item = btn.closest('.item-parceiro');
    const index = item.dataset.index;
    const nome = item.querySelector('.nome-input')?.value;
    const link = item.querySelector('.link-input')?.value;
    const logoFile = pendingParceiroLogos[index];
    if (!nome) { mostrarNotificacao('Nome do parceiro é obrigatório', 'erro'); return; }
    const formData = new FormData();
    formData.append('action', 'salvar_parceiro');
    formData.append('index', index);
    formData.append('nome', nome);
    formData.append('link', link);
    if (logoFile) formData.append('logo', logoFile);
    fetch('processos/processar-inicio.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => { if (data.success) { mostrarNotificacao(data.message, 'sucesso'); setTimeout(() => location.reload(), 1000); } else { mostrarNotificacao(data.message, 'erro'); } });
}

function salvarNovoParceiro(btn) {
    const item = btn.closest('.item-parceiro');
    const tempId = item.dataset.tempId;
    const nome = item.querySelector('.nome-input')?.value;
    const link = item.querySelector('.link-input')?.value;
    const logoFile = pendingParceiroLogos[tempId];
    if (!nome) { mostrarNotificacao('Nome do parceiro é obrigatório', 'erro'); return; }
    const formData = new FormData();
    formData.append('action', 'salvar_parceiro');
    formData.append('nome', nome);
    formData.append('link', link);
    if (logoFile) formData.append('logo', logoFile);
    fetch('processos/processar-inicio.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => { if (data.success) { mostrarNotificacao(data.message, 'sucesso'); setTimeout(() => location.reload(), 1000); } else { mostrarNotificacao(data.message, 'erro'); } });
}

function eliminarParceiro(btn) {
    const modal = document.getElementById('modalConfirmacao');
    const item = btn.closest('.item-parceiro');
    const index = item.dataset.index;
    document.getElementById('confirmacaoTitulo').textContent = 'Eliminar Parceiro';
    document.getElementById('confirmacaoTexto').textContent = 'Tem certeza que deseja eliminar este parceiro permanentemente?';
    const confirmar = document.getElementById('btnConfirmarAcao');
    const novoConfirmar = confirmar.cloneNode(true);
    confirmar.parentNode.replaceChild(novoConfirmar, confirmar);
    novoConfirmar.onclick = async () => {
        try {
            const response = await fetch('processos/processar-inicio.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: new URLSearchParams({ action: 'eliminar_parceiro', index: index }) });
            const data = await response.json();
            if (data.success) { mostrarNotificacao(data.message, 'sucesso'); location.reload(); } else { mostrarNotificacao(data.message, 'erro'); }
        } catch (error) { mostrarNotificacao('Erro ao eliminar', 'erro'); }
        modal.classList.remove('ativo');
    };
    modal.classList.add('ativo');
}


function abrirModalConfirmacao(titulo, texto, callback, tipoAcao = 'eliminar') {
    const modal = document.getElementById('modalConfirmacao');
    const confirmar = document.getElementById('btnConfirmarAcao');
    document.getElementById('confirmacaoTitulo').textContent = titulo;
    document.getElementById('confirmacaoTexto').textContent = texto;
    confirmar.classList.toggle('acao-publicar', tipoAcao === 'publicar');
    const novoConfirmar = confirmar.cloneNode(true);
    confirmar.parentNode.replaceChild(novoConfirmar, confirmar);
    novoConfirmar.onclick = async () => { await callback(); modal.classList.remove('ativo'); };
    modal.classList.add('ativo');
}

function confirmarRemoverFotoDirector() {
    abrirModalConfirmacao('Remover foto do Director', 'Tem certeza que deseja remover a foto atual?', async () => {
        const formData = new FormData();
        formData.append('action', 'remover_foto_director');
        const response = await fetch('processos/processar-inicio.php', { method: 'POST', body: formData });
        const data = await response.json();
        if (data.success) { mostrarNotificacao(data.message, 'sucesso'); setTimeout(() => location.reload(), 700); }
        else { mostrarNotificacao(data.message, 'erro'); }
    }, 'eliminar');
}

function confirmarRemoverFotoMatricula() {
    abrirModalConfirmacao('Remover foto de Matrícula', 'Tem certeza que deseja remover a imagem atual?', async () => {
        const formData = new FormData();
        formData.append('action', 'remover_imagem_matricula');
        const response = await fetch('processos/processar-inicio.php', { method: 'POST', body: formData });
        const data = await response.json();
        if (data.success) { mostrarNotificacao(data.message, 'sucesso'); setTimeout(() => location.reload(), 700); }
        else { mostrarNotificacao(data.message, 'erro'); }
    }, 'eliminar');
}

function salvarMensagemDirector() {
    const formData = new FormData();
    formData.append('action', 'salvar_director');
    formData.append('nome', document.getElementById('directorNome').value);
    formData.append('cargo', document.getElementById('directorCargo').value);
    formData.append('mensagem', document.getElementById('directorMensagem').value);
    formData.append('assinatura', document.getElementById('directorAssinatura').value);
    if (pendingDirectorFoto) formData.append('foto', pendingDirectorFoto);
    fetch('processos/processar-inicio.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => { if (data.success) { mostrarNotificacao(data.message, 'sucesso'); setTimeout(() => location.reload(), 1000); } else { mostrarNotificacao(data.message, 'erro'); } });
}

function salvarMatricula() {
    const formData = new FormData();
    formData.append('action', 'salvar_matricula');
    formData.append('titulo', document.getElementById('matriculaTitulo').value);
    formData.append('descricao', document.getElementById('matriculaDescricao').value);
    if (pendingMatriculaImg) formData.append('imagem', pendingMatriculaImg);
    fetch('processos/processar-inicio.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => { if (data.success) { mostrarNotificacao(data.message, 'sucesso'); setTimeout(() => location.reload(), 1000); } else { mostrarNotificacao(data.message, 'erro'); } });
}

function salvarTudo() {
    salvarMensagemDirector();
    salvarMatricula();
    setTimeout(() => { mostrarNotificacao('Todas as alterações foram guardadas!', 'sucesso'); }, 500);
}

function previewPagina() { window.open('../area-publica/index.php?preview=1', '_blank'); }

document.getElementById('btnCancelarConfirmacao')?.addEventListener('click', () => { document.getElementById('modalConfirmacao').classList.remove('ativo'); });
document.getElementById('modalConfirmacao')?.addEventListener('click', (e) => { if (e.target === document.getElementById('modalConfirmacao')) document.getElementById('modalConfirmacao').classList.remove('ativo'); });
document.addEventListener('keydown', (e) => { if (e.key === 'Escape') document.getElementById('modalConfirmacao').classList.remove('ativo'); });
document.querySelectorAll('.item-slide').forEach(item => { initLinkSelect(item); });
</script>

<?php include 'includes/footer.php'; ?>
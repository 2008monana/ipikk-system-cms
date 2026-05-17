<?php
/**
 * Perfil do Director - Área Restrita IPIKK
 * Gestão completa do perfil do director
 */

$titulo_pagina = 'Perfil do Director';
$css_especifico = 'admin-perfil-director.css';

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
// BUSCAR DADOS DO DIRECTOR (da tabela conteudo_paginas)
// ============================================
$pagina = getPagina('director');

// Dados do director com fallbacks
$director = [
    'nome' => $pagina['nome'] ?? 'Ferreira Manuel Fragoso',
    'cargo' => $pagina['cargo'] ?? 'Director Geral do IPIKK',
    'foto' => $pagina['foto'] ?? 'foto/perfil-do-director.jpg',
    'data_nascimento' => $pagina['data_nascimento'] ?? '27 de Julho de 1971',
    'naturalidade' => $pagina['naturalidade'] ?? 'Cacongo, Cabinda',
    'experiencia' => $pagina['experiencia'] ?? '30+ Anos na Educação',
    'inicio_cargo' => $pagina['inicio_cargo'] ?? '17 de Outubro de 2018',
    'resumo' => $pagina['resumo'] ?? '',
    'citacao' => $pagina['citacao'] ?? 'A educação é a base para a construção de uma sociedade sólida e o pilar para o desenvolvimento de cada indivíduo.',
    'formacoes' => $pagina['formacoes'] ?? [],
    'experiencias' => $pagina['experiencias'] ?? [],
    'realizacoes' => $pagina['realizacoes'] ?? [],
    'idiomas' => $pagina['idiomas'] ?? ['Português (Nativo)', 'Espanhol (Intermédio)', 'Francês (Noções)']
];

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
            <i class="fas fa-user-tie"></i> Perfil do Director
        </h1>
    </div>
    <div class="direita-barra">
        <button class="btn-primario" onclick="abrirModalDirector()">
            <i class="fas fa-edit"></i>
            <span>Editar Perfil</span>
        </button>
    </div>
</header>
    <div class="conteudo-pagina">

        <!-- VISUALIZAÇÃO DO PERFIL (modo leitura) -->
        <div class="perfil-container">
            
            <!-- Card Principal -->
            <div class="bio-card">
                <div class="bio-foto">
                    <img src="<?= htmlspecialchars(normalizarUrlMidia($director['foto'] ?? 'foto/sem_foto.png', '..')) ?>" alt="<?= htmlspecialchars($director['nome']) ?>" onerror="this.src='../area-publica/foto/sem_foto.png'">
                </div>
                <div class="bio-info">
                    <h2 class="nome-director"><?= htmlspecialchars($director['nome']) ?></h2>
                    <span class="cargo-badge"><?= htmlspecialchars($director['cargo']) ?></span>
                    <p class="resumo-profissional"><?= nl2br(htmlspecialchars($director['resumo'])) ?></p>
                    <div class="grid-stats">
                        <div class="stat-box">
                            <span class="stat-label">Data de Nascimento</span>
                            <span class="stat-value"><?= htmlspecialchars($director['data_nascimento']) ?></span>
                        </div>
                        <div class="stat-box">
                            <span class="stat-label">Naturalidade</span>
                            <span class="stat-value"><?= htmlspecialchars($director['naturalidade']) ?></span>
                        </div>
                        <div class="stat-box">
                            <span class="stat-label">Experiência</span>
                            <span class="stat-value"><?= htmlspecialchars($director['experiencia']) ?></span>
                        </div>
                        <div class="stat-box">
                            <span class="stat-label">Início no Cargo</span>
                            <span class="stat-value"><?= htmlspecialchars($director['inicio_cargo']) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Formação Académica -->
            <div class="secao-conteudo">
                <div class="secao-header">
                    <h2 class="secao-titulo"><i class="fas fa-graduation-cap"></i> Formação Académica</h2>
                    <span class="contador">(<?= count($director['formacoes']) ?>)</span>
                </div>
                <div class="lista-formacoes">
                    <?php foreach ($director['formacoes'] as $formacao): ?>
                    <div class="item-lista">
                        <div class="icon-wrapper"><i class="fas fa-book"></i></div>
                        <div class="info-wrapper">
                            <h4><?= htmlspecialchars($formacao['curso']) ?></h4>
                            <span class="instituicao"><?= htmlspecialchars($formacao['instituicao']) ?></span>
                            <span class="detalhe"><?= htmlspecialchars($formacao['detalhe'] ?? $formacao['periodo'] ?? '') ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($director['formacoes'])): ?>
                    <div class="empty-placeholder">Nenhuma formação cadastrada</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Experiência Profissional -->
            <div class="secao-conteudo">
                <div class="secao-header">
                    <h2 class="secao-titulo"><i class="fas fa-briefcase"></i> Experiência Profissional</h2>
                    <span class="contador">(<?= count($director['experiencias']) ?>)</span>
                </div>
                <div class="lista-experiencias">
                    <?php foreach ($director['experiencias'] as $exp): ?>
                    <div class="timeline-item">
                        <div class="time-date"><?= htmlspecialchars($exp['periodo']) ?></div>
                        <div class="time-content">
                            <h4><?= htmlspecialchars($exp['cargo']) ?></h4>
                            <p><?= htmlspecialchars($exp['local']) ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($director['experiencias'])): ?>
                    <div class="empty-placeholder">Nenhuma experiência cadastrada</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Realizações -->
            <div class="secao-conteudo">
                <div class="secao-header">
                    <h2 class="secao-titulo"><i class="fas fa-trophy"></i> Realizações e Publicações</h2>
                    <span class="contador">(<?= count($director['realizacoes']) ?>)</span>
                </div>
                <ul class="lista-realizacoes">
                    <?php foreach ($director['realizacoes'] as $realizacao): ?>
                    <li><?= htmlspecialchars($realizacao) ?></li>
                    <?php endforeach; ?>
                    <?php if (empty($director['realizacoes'])): ?>
                    <li class="empty-placeholder">Nenhuma realização cadastrada</li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Idiomas e Citação -->
            <div class="secao-conteudo">
                <div class="secao-header">
                    <h2 class="secao-titulo"><i class="fas fa-language"></i> Idiomas</h2>
                </div>
                <div class="skills-grid">
                    <?php foreach ($director['idiomas'] as $idioma): ?>
                    <span class="skill-tag"><?= htmlspecialchars($idioma) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="quote-container">
                <p class="quote-text">"<?= htmlspecialchars($director['citacao']) ?>"</p>
                <p class="quote-author"><?= htmlspecialchars($director['nome']) ?></p>
            </div>
        </div>
    </div>
</main>

<!-- MODAL PARA EDITAR PERFIL DO DIRECTOR -->
<div id="modalDirector" class="modal">
    <div class="modal-conteudo modal-grande">
        <div class="modal-cabecalho">
            <h2><i class="fas fa-edit"></i> Editar Perfil do Director</h2>
            <button class="modal-fechar" onclick="fecharModalDirector()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-corpo">
            <form id="formDirector" onsubmit="return salvarDirector(event)">
                
                <!-- Informações Pessoais -->
                <div class="secao-form">
                    <h3><i class="fas fa-user"></i> Informações Pessoais</h3>
                    <div class="linha-form">
                        <div class="grupo-form">
                            <label>Nome *</label>
                            <input type="text" id="campoNome" class="campo-form" value="<?= htmlspecialchars($director['nome']) ?>" required>
                        </div>
                        <div class="grupo-form">
                            <label>Cargo</label>
                            <input type="text" id="campoCargo" class="campo-form" value="<?= htmlspecialchars($director['cargo']) ?>">
                        </div>
                    </div>
                    <div class="linha-form">
                        <div class="grupo-form">
                            <label>Data de Nascimento</label>
                            <input type="text" id="campoDataNascimento" class="campo-form" value="<?= htmlspecialchars($director['data_nascimento']) ?>">
                        </div>
                        <div class="grupo-form">
                            <label>Naturalidade</label>
                            <input type="text" id="campoNaturalidade" class="campo-form" value="<?= htmlspecialchars($director['naturalidade']) ?>">
                        </div>
                    </div>
                    <div class="linha-form">
                        <div class="grupo-form">
                            <label>Experiência (anos)</label>
                            <input type="text" id="campoExperiencia" class="campo-form" value="<?= htmlspecialchars($director['experiencia']) ?>">
                        </div>
                        <div class="grupo-form">
                            <label>Início no Cargo</label>
                            <input type="text" id="campoInicioCargo" class="campo-form" value="<?= htmlspecialchars($director['inicio_cargo']) ?>">
                        </div>
                    </div>
                    <div class="grupo-form">
                        <label>Resumo / Biografia</label>
                        <textarea id="campoResumo" class="campo-form area-texto" rows="4"><?= htmlspecialchars($director['resumo']) ?></textarea>
                    </div>
                    <div class="grupo-form">
                        <label>Citação</label>
                        <textarea id="campoCitacao" class="campo-form" rows="2"><?= htmlspecialchars($director['citacao']) ?></textarea>
                    </div>
                </div>

                <!-- Foto -->
                <div class="secao-form">
                    <h3><i class="fas fa-image"></i> Foto do Director</h3>
                    <div class="area-upload" onclick="document.getElementById('fotoInput').click()">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>Clique para fazer upload da foto</p>
                        <small>Formatos: JPG, PNG | 300x350px recomendado</small>
                    </div>
                    <input type="file" id="fotoInput" accept="image/*" style="display: none;" onchange="previewFoto(this)">
                    <div class="preview-foto" id="previewFoto">
                        <img id="previewImg" src="<?= htmlspecialchars(normalizarUrlMidia($director['foto'] ?? 'foto/perfil-do-director.jpg', '..')) ?>" alt="Preview">
                        <button type="button" class="btn-remover" onclick="removerFoto()">
                            <i class="fas fa-trash"></i> Remover
                        </button>
                    </div>
                </div>

                <!-- Formações (dinâmico) -->
                <div class="secao-form">
                    <div class="secao-header-inline">
                        <h3><i class="fas fa-graduation-cap"></i> Formações Académicas</h3>
                        <button type="button" class="btn-adicionar" onclick="adicionarFormacao()">
                            <i class="fas fa-plus"></i> Adicionar
                        </button>
                    </div>
                    <div id="listaFormacoes">
                        <?php foreach ($director['formacoes'] as $index => $formacao): ?>
                        <div class="item-dinamico" data-index="<?= $index ?>">
                            <div class="item-header">
                                <span class="item-numero"><?= $index + 1 ?></span>
                                <button type="button" class="btn-remover-item" onclick="removerItem(this)">×</button>
                            </div>
                            <div class="linha-form">
                                <div class="grupo-form">
                                    <label>Curso</label>
                                    <input type="text" class="campo-form formacao-curso" value="<?= htmlspecialchars($formacao['curso'] ?? '') ?>">
                                </div>
                                <div class="grupo-form">
                                    <label>Instituição</label>
                                    <input type="text" class="campo-form formacao-instituicao" value="<?= htmlspecialchars($formacao['instituicao'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="linha-form">
                                <div class="grupo-form">
                                    <label>Período</label>
                                    <input type="text" class="campo-form formacao-periodo" value="<?= htmlspecialchars($formacao['periodo'] ?? '') ?>">
                                </div>
                                <div class="grupo-form">
                                    <label>Detalhe</label>
                                    <input type="text" class="campo-form formacao-detalhe" value="<?= htmlspecialchars($formacao['detalhe'] ?? '') ?>">
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (empty($director['formacoes'])): ?>
                    <div class="empty-dinamico" id="emptyFormacoes">Nenhuma formação cadastrada. Clique em "Adicionar" para começar.</div>
                    <?php endif; ?>
                </div>

                <!-- Experiências (dinâmico) -->
                <div class="secao-form">
                    <div class="secao-header-inline">
                        <h3><i class="fas fa-briefcase"></i> Experiências Profissionais</h3>
                        <button type="button" class="btn-adicionar" onclick="adicionarExperiencia()">
                            <i class="fas fa-plus"></i> Adicionar
                        </button>
                    </div>
                    <div id="listaExperiencias">
                        <?php foreach ($director['experiencias'] as $index => $exp): ?>
                        <div class="item-dinamico" data-index="<?= $index ?>">
                            <div class="item-header">
                                <span class="item-numero"><?= $index + 1 ?></span>
                                <button type="button" class="btn-remover-item" onclick="removerItem(this)">×</button>
                            </div>
                            <div class="linha-form">
                                <div class="grupo-form">
                                    <label>Período</label>
                                    <input type="text" class="campo-form experiencia-periodo" value="<?= htmlspecialchars($exp['periodo'] ?? '') ?>">
                                </div>
                                <div class="grupo-form">
                                    <label>Cargo</label>
                                    <input type="text" class="campo-form experiencia-cargo" value="<?= htmlspecialchars($exp['cargo'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="grupo-form">
                                <label>Local / Empresa</label>
                                <input type="text" class="campo-form experiencia-local" value="<?= htmlspecialchars($exp['local'] ?? '') ?>">
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (empty($director['experiencias'])): ?>
                    <div class="empty-dinamico" id="emptyExperiencias">Nenhuma experiência cadastrada. Clique em "Adicionar" para começar.</div>
                    <?php endif; ?>
                </div>

                <!-- Realizações (dinâmico) -->
                <div class="secao-form">
                    <div class="secao-header-inline">
                        <h3><i class="fas fa-trophy"></i> Realizações e Publicações</h3>
                        <button type="button" class="btn-adicionar" onclick="adicionarRealizacao()">
                            <i class="fas fa-plus"></i> Adicionar
                        </button>
                    </div>
                    <div id="listaRealizacoes">
                        <?php foreach ($director['realizacoes'] as $index => $realizacao): ?>
                        <div class="item-simples" data-index="<?= $index ?>">
                            <div class="item-simples-header">
                                <span class="item-numero"><?= $index + 1 ?></span>
                                <input type="text" class="campo-form realizacao-texto" value="<?= htmlspecialchars($realizacao) ?>">
                                <button type="button" class="btn-remover-item" onclick="removerItem(this)">×</button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (empty($director['realizacoes'])): ?>
                    <div class="empty-dinamico" id="emptyRealizacoes">Nenhuma realização cadastrada. Clique em "Adicionar" para começar.</div>
                    <?php endif; ?>
                </div>

                <!-- Idiomas (dinâmico) -->
                <div class="secao-form">
                    <div class="secao-header-inline">
                        <h3><i class="fas fa-language"></i> Idiomas</h3>
                        <button type="button" class="btn-adicionar" onclick="adicionarIdioma()">
                            <i class="fas fa-plus"></i> Adicionar
                        </button>
                    </div>
                    <div id="listaIdiomas">
                        <?php foreach ($director['idiomas'] as $index => $idioma): ?>
                        <div class="item-simples" data-index="<?= $index ?>">
                            <div class="item-simples-header">
                                <span class="item-numero"><?= $index + 1 ?></span>
                                <input type="text" class="campo-form idioma-texto" value="<?= htmlspecialchars($idioma) ?>">
                                <button type="button" class="btn-remover-item" onclick="removerItem(this)">×</button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (empty($director['idiomas'])): ?>
                    <div class="empty-dinamico" id="emptyIdiomas">Nenhum idioma cadastrado. Clique em "Adicionar" para começar.</div>
                    <?php endif; ?>
                </div>

                <div class="modal-acoes">
                    <button type="button" class="btn-cancelar" onclick="fecharModalDirector()">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn-salvar">
                        <i class="fas fa-save"></i> Salvar Perfil
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
/* ===== ESTILOS ADMIN PERFIL DIRECTOR ===== */

.perfil-container {
    max-width: 1000px;
    margin: 0 auto;
}

/* Card Principal */
.bio-card {
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    border-radius: 24px;
    padding: 32px;
    display: flex;
    gap: 32px;
    margin-bottom: 32px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
    border: 1px solid rgba(10, 147, 150, 0.15);
    transition: all 0.3s ease;
}

.bio-card:hover {
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
}

.bio-foto {
    width: 260px;
    height: 320px;
    border-radius: 16px;
    overflow: hidden;
    flex-shrink: 0;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
    border: 3px solid white;
}

.bio-foto img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.bio-foto img:hover {
    transform: scale(1.02);
}

.bio-info {
    flex: 1;
}

.nome-director {
    font-size: 2rem;
    font-weight: 700;
    background: linear-gradient(135deg, #003072 0%, #0a5e6b 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 8px;
}

.cargo-badge {
    display: inline-block;
    background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
    color: #1b5e20;
    padding: 6px 18px;
    border-radius: 30px;
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.5px;
    margin-bottom: 20px;
    border: 1px solid rgba(46, 125, 50, 0.2);
}

.resumo-profissional {
    color: #475569;
    line-height: 1.7;
    margin-bottom: 24px;
    font-size: 0.9rem;
    text-align: justify;
}

/* Grid de estatísticas */
.grid-stats {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
    background: #f1f5f9;
    padding: 16px;
    border-radius: 16px;
}

.stat-box {
    background: white;
    border-radius: 12px;
    padding: 12px 16px;
    border-left: 3px solid #0a9396;
    transition: all 0.2s ease;
}

.stat-box:hover {
    transform: translateX(4px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.stat-label {
    font-size: 0.7rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    display: block;
    margin-bottom: 4px;
}

.stat-value {
    font-size: 0.9rem;
    font-weight: 500;
    color: #1e293b;
}

/* Secções de conteúdo */
.secao-conteudo {
    background: white;
    border-radius: 20px;
    padding: 24px;
    margin-bottom: 28px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
    border: 1px solid #eef2f8;
    transition: all 0.3s ease;
}

.secao-conteudo:hover {
    border-color: #cbd5e1;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
}

.secao-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 14px;
    border-bottom: 2px solid #f0f4f8;
}

.secao-titulo {
    font-size: 1.1rem;
    font-weight: 600;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
    color: #1e293b;
}

.secao-titulo i {
    color: #0a9396;
    font-size: 1.2rem;
    width: 30px;
    height: 30px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(10, 147, 150, 0.1);
    border-radius: 10px;
}

.contador {
    font-size: 0.7rem;
    font-weight: 500;
    color: #64748b;
    background: #f1f5f9;
    padding: 2px 10px;
    border-radius: 30px;
}

/* Lista de formações */
.lista-formacoes {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.item-lista {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    padding: 16px;
    background: #f8fafc;
    border-radius: 14px;
    transition: all 0.2s ease;
    border: 1px solid #eef2f8;
}

.item-lista:hover {
    background: #f1f5f9;
    transform: translateX(4px);
}

.icon-wrapper {
    width: 44px;
    height: 44px;
    background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #2e7d32;
    font-size: 1.2rem;
}

.info-wrapper h4 {
    font-size: 0.95rem;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 4px;
}

.info-wrapper .instituicao {
    font-size: 0.8rem;
    color: #64748b;
    display: block;
    margin-bottom: 2px;
}

.info-wrapper .detalhe {
    font-size: 0.7rem;
    color: #94a3b8;
}

/* Timeline de experiências */
.lista-experiencias {
    display: flex;
    flex-direction: column;
}

.timeline-item {
    display: flex;
    gap: 24px;
    padding: 16px 0;
    border-bottom: 1px solid #eef2f8;
    transition: all 0.2s ease;
}

.timeline-item:last-child {
    border-bottom: none;
}

.timeline-item:hover {
    background: #f8fafc;
    margin: 0 -16px;
    padding: 16px;
    border-radius: 12px;
}

.time-date {
    min-width: 110px;
    font-weight: 700;
    color: #0a9396;
    font-size: 0.85rem;
    letter-spacing: -0.3px;
}

.time-content h4 {
    font-size: 0.95rem;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 4px;
}

.time-content p {
    font-size: 0.8rem;
    color: #64748b;
}

/* Lista de realizações */
.lista-realizacoes {
    list-style: none;
    padding: 0;
    margin: 0;
}

.lista-realizacoes li {
    padding: 12px 0 12px 24px;
    position: relative;
    border-bottom: 1px solid #f0f4f8;
    color: #475569;
    font-size: 0.85rem;
    line-height: 1.5;
    transition: all 0.2s ease;
}

.lista-realizacoes li:last-child {
    border-bottom: none;
}

.lista-realizacoes li:before {
    content: "";
    width: 6px;
    height: 6px;
    background: #0a9396;
    border-radius: 50%;
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
}

.lista-realizacoes li:hover {
    transform: translateX(4px);
    color: #1e293b;
}

/* Skills (Idiomas) */
.skills-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.skill-tag {
    background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
    padding: 8px 18px;
    border-radius: 30px;
    font-size: 0.8rem;
    font-weight: 500;
    color: #334155;
    transition: all 0.2s ease;
    border: 1px solid #cbd5e1;
}

.skill-tag:hover {
    background: #0a9396;
    color: white;
    border-color: #0a9396;
    transform: translateY(-2px);
}

/* Citação */
.quote-container {
    text-align: center;
    padding: 36px;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border-radius: 20px;
    border-left: 4px solid #0a9396;
    border-right: 4px solid #0a9396;
}

.quote-text {
    font-style: italic;
    font-size: 1.1rem;
    color: #334155;
    margin-bottom: 16px;
    line-height: 1.6;
}

.quote-text:before {
    content: "\f10d";
    font-family: "Font Awesome 6 Free";
    font-weight: 900;
    margin-right: 10px;
    color: #0a9396;
    font-size: 0.9rem;
}

.quote-text:after {
    content: "\f10e";
    font-family: "Font Awesome 6 Free";
    font-weight: 900;
    margin-left: 10px;
    color: #0a9396;
    font-size: 0.9rem;
}

.quote-author {
    font-weight: 600;
    color: #0a9396;
    font-size: 0.9rem;
    letter-spacing: 0.5px;
}

.empty-placeholder {
    text-align: center;
    padding: 40px;
    color: #94a3b8;
    font-size: 0.85rem;
    background: #f8fafc;
    border-radius: 14px;
}

/* Botão Editar Perfil */
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

.btn-primario:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 14px rgba(0, 48, 114, 0.3);
}

/* MODAL */
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
    max-width: 950px;
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
    padding: 24px 28px;
    border-bottom: 1px solid #eef2f8;
    position: sticky;
    top: 0;
    background: white;
    z-index: 10;
}

.modal-cabecalho h2 {
    font-size: 1.3rem;
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
    font-size: 1.1rem;
    color: #64748b;
}

.modal-fechar:hover {
    background: #fee2e2;
    color: #dc3545;
    transform: rotate(90deg);
}

.modal-corpo {
    padding: 28px;
}

/* Formulário dentro do modal */
.secao-form {
    background: #f8fafc;
    border-radius: 20px;
    padding: 24px;
    margin-bottom: 24px;
    border: 1px solid #eef2f8;
}

.secao-header-inline {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 1px solid #e2e8f0;
}

.secao-header-inline h3 {
    font-size: 1rem;
    font-weight: 600;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
    color: #1e293b;
}

.secao-header-inline h3 i {
    color: #0a9396;
}

/* Campos de formulário */
.linha-form {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 18px;
    margin-bottom: 18px;
}

.grupo-form {
    margin-bottom: 18px;
}

.grupo-form:last-child {
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
    border-radius: 12px;
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
}

.area-texto {
    resize: vertical;
    line-height: 1.6;
    min-height: 100px;
}

/* Upload de imagem */
.area-upload {
    border: 2px dashed #cbd5e1;
    border-radius: 16px;
    padding: 24px;
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
    margin-bottom: 8px;
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
}

.preview-foto img {
    width: 80px;
    height: 80px;
    border-radius: 16px;
    object-fit: cover;
    border: 2px solid #e2e8f0;
}

.btn-remover {
    background: #fee2e2;
    border: none;
    padding: 8px 16px;
    border-radius: 30px;
    cursor: pointer;
    color: #dc3545;
    font-size: 0.75rem;
    font-weight: 600;
    transition: all 0.2s ease;
}

.btn-remover:hover {
    background: #dc3545;
    color: white;
}

/* Botão adicionar */
.btn-adicionar {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    padding: 8px 18px;
    border-radius: 30px;
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

/* Itens dinâmicos */
.item-dinamico,
.item-simples {
    background: white;
    border-radius: 16px;
    padding: 18px;
    margin-bottom: 16px;
    border: 1px solid #e2e8f0;
    transition: all 0.2s ease;
}

.item-dinamico:hover,
.item-simples:hover {
    border-color: #cbd5e1;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.item-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
}

.item-numero {
    background: #0a9396;
    color: white;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 700;
}

.btn-remover-item {
    background: none;
    border: none;
    font-size: 1.2rem;
    cursor: pointer;
    color: #dc3545;
    transition: all 0.2s ease;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-remover-item:hover {
    background: #fee2e2;
    transform: scale(1.1);
}

.item-simples-header {
    display: flex;
    align-items: center;
    gap: 14px;
}

.item-simples-header input {
    flex: 1;
}

/* Empty dinâmico */
.empty-dinamico {
    text-align: center;
    padding: 40px;
    color: #94a3b8;
    font-size: 0.85rem;
    background: #f8fafc;
    border-radius: 14px;
}

/* Modal actions */
.modal-acoes {
    display: flex;
    justify-content: flex-end;
    gap: 16px;
    margin-top: 24px;
    padding-top: 20px;
    border-top: 1px solid #eef2f8;
}

.btn-cancelar,
.btn-salvar {
    padding: 12px 28px;
    border-radius: 40px;
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
    border: none;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
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

/* Modal de confirmação */
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
    color: #dc3545;
}

.modal-confirmacao-botoes {
    display: flex;
    gap: 12px;
    justify-content: center;
}

.botao-cancelar-modal,
.botao-confirmar-modal {
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
@media (max-width: 768px) {
    .bio-card {
        flex-direction: column;
        align-items: center;
        text-align: center;
        padding: 24px;
    }
    
    .bio-foto {
        width: 200px;
        height: 250px;
    }
    
    .grid-stats {
        grid-template-columns: 1fr;
    }
    
    .timeline-item {
        flex-direction: column;
        gap: 8px;
    }
    
    .time-date {
        min-width: auto;
    }
    
    .linha-form {
        grid-template-columns: 1fr;
        gap: 12px;
    }
    
    .secao-form {
        padding: 18px;
    }
    
    .modal-cabecalho {
        padding: 18px 20px;
    }
    
    .modal-corpo {
        padding: 20px;
    }
    
    .notificacao {
        left: 16px;
        right: 16px;
        top: 16px;
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .nome-director {
        font-size: 1.5rem;
    }
    
    .secao-titulo {
        font-size: 1rem;
    }
    
    .quote-text {
        font-size: 0.9rem;
    }
    
    .modal-acoes {
        flex-direction: column;
    }
    
    .btn-cancelar,
    .btn-salvar {
        width: 100%;
        justify-content: center;
    }
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
    };
    reader.readAsDataURL(file);
}

function removerFoto() {
    document.getElementById('fotoInput').value = '';
    fotoFile = null;
    document.getElementById('previewImg').src = '<?= htmlspecialchars(normalizarUrlMidia($director['foto'] ?? 'foto/perfil-do-director.jpg', '..')) ?>';
}

function abrirModalDirector() {
    document.getElementById('modalDirector').classList.add('ativo');
}

function fecharModalDirector() {
    document.getElementById('modalDirector').classList.remove('ativo');
}

function removerItem(btn) {
    btn.closest('.item-dinamico, .item-simples')?.remove();
    atualizarNumeracao();
}

function atualizarNumeracao() {
    document.querySelectorAll('#listaFormacoes .item-dinamico').forEach((item, i) => {
        item.querySelector('.item-numero').textContent = i + 1;
    });
    document.querySelectorAll('#listaExperiencias .item-dinamico').forEach((item, i) => {
        item.querySelector('.item-numero').textContent = i + 1;
    });
    document.querySelectorAll('#listaRealizacoes .item-simples').forEach((item, i) => {
        item.querySelector('.item-numero').textContent = i + 1;
    });
    document.querySelectorAll('#listaIdiomas .item-simples').forEach((item, i) => {
        item.querySelector('.item-numero').textContent = i + 1;
    });
    
    const emptyFormacoes = document.getElementById('emptyFormacoes');
    if (emptyFormacoes) {
        emptyFormacoes.style.display = document.querySelectorAll('#listaFormacoes .item-dinamico').length ? 'none' : 'block';
    }
    const emptyExperiencias = document.getElementById('emptyExperiencias');
    if (emptyExperiencias) {
        emptyExperiencias.style.display = document.querySelectorAll('#listaExperiencias .item-dinamico').length ? 'none' : 'block';
    }
    const emptyRealizacoes = document.getElementById('emptyRealizacoes');
    if (emptyRealizacoes) {
        emptyRealizacoes.style.display = document.querySelectorAll('#listaRealizacoes .item-simples').length ? 'none' : 'block';
    }
    const emptyIdiomas = document.getElementById('emptyIdiomas');
    if (emptyIdiomas) {
        emptyIdiomas.style.display = document.querySelectorAll('#listaIdiomas .item-simples').length ? 'none' : 'block';
    }
}

function adicionarFormacao() {
    const container = document.getElementById('listaFormacoes');
    const emptyDiv = document.getElementById('emptyFormacoes');
    if (emptyDiv) emptyDiv.style.display = 'none';
    
    const div = document.createElement('div');
    div.className = 'item-dinamico';
    const index = container.children.length + 1;
    div.innerHTML = `
        <div class="item-header">
            <span class="item-numero">${index}</span>
            <button type="button" class="btn-remover-item" onclick="removerItem(this)">×</button>
        </div>
        <div class="linha-form">
            <div class="grupo-form">
                <label>Curso</label>
                <input type="text" class="campo-form formacao-curso">
            </div>
            <div class="grupo-form">
                <label>Instituição</label>
                <input type="text" class="campo-form formacao-instituicao">
            </div>
        </div>
        <div class="linha-form">
            <div class="grupo-form">
                <label>Período</label>
                <input type="text" class="campo-form formacao-periodo">
            </div>
            <div class="grupo-form">
                <label>Detalhe</label>
                <input type="text" class="campo-form formacao-detalhe">
            </div>
        </div>
    `;
    container.appendChild(div);
    atualizarNumeracao();
}

function adicionarExperiencia() {
    const container = document.getElementById('listaExperiencias');
    const emptyDiv = document.getElementById('emptyExperiencias');
    if (emptyDiv) emptyDiv.style.display = 'none';
    
    const div = document.createElement('div');
    div.className = 'item-dinamico';
    const index = container.children.length + 1;
    div.innerHTML = `
        <div class="item-header">
            <span class="item-numero">${index}</span>
            <button type="button" class="btn-remover-item" onclick="removerItem(this)">×</button>
        </div>
        <div class="linha-form">
            <div class="grupo-form">
                <label>Período</label>
                <input type="text" class="campo-form experiencia-periodo">
            </div>
            <div class="grupo-form">
                <label>Cargo</label>
                <input type="text" class="campo-form experiencia-cargo">
            </div>
        </div>
        <div class="grupo-form">
            <label>Local / Empresa</label>
            <input type="text" class="campo-form experiencia-local">
        </div>
    `;
    container.appendChild(div);
    atualizarNumeracao();
}

function adicionarRealizacao() {
    const container = document.getElementById('listaRealizacoes');
    const emptyDiv = document.getElementById('emptyRealizacoes');
    if (emptyDiv) emptyDiv.style.display = 'none';
    
    const div = document.createElement('div');
    div.className = 'item-simples';
    const index = container.children.length + 1;
    div.innerHTML = `
        <div class="item-simples-header">
            <span class="item-numero">${index}</span>
            <input type="text" class="campo-form realizacao-texto" placeholder="Digite a realização ou publicação...">
            <button type="button" class="btn-remover-item" onclick="removerItem(this)">×</button>
        </div>
    `;
    container.appendChild(div);
    atualizarNumeracao();
}

function adicionarIdioma() {
    const container = document.getElementById('listaIdiomas');
    const emptyDiv = document.getElementById('emptyIdiomas');
    if (emptyDiv) emptyDiv.style.display = 'none';
    
    const div = document.createElement('div');
    div.className = 'item-simples';
    const index = container.children.length + 1;
    div.innerHTML = `
        <div class="item-simples-header">
            <span class="item-numero">${index}</span>
            <input type="text" class="campo-form idioma-texto" placeholder="Ex: Português (Nativo)">
            <button type="button" class="btn-remover-item" onclick="removerItem(this)">×</button>
        </div>
    `;
    container.appendChild(div);
    atualizarNumeracao();
}

function coletarFormacoes() {
    const formacoes = [];
    document.querySelectorAll('#listaFormacoes .item-dinamico').forEach(item => {
        const curso = item.querySelector('.formacao-curso')?.value || '';
        if (curso) {
            formacoes.push({
                curso: curso,
                instituicao: item.querySelector('.formacao-instituicao')?.value || '',
                periodo: item.querySelector('.formacao-periodo')?.value || '',
                detalhe: item.querySelector('.formacao-detalhe')?.value || ''
            });
        }
    });
    return formacoes;
}

function coletarExperiencias() {
    const experiencias = [];
    document.querySelectorAll('#listaExperiencias .item-dinamico').forEach(item => {
        const periodo = item.querySelector('.experiencia-periodo')?.value || '';
        const cargo = item.querySelector('.experiencia-cargo')?.value || '';
        if (periodo || cargo) {
            experiencias.push({
                periodo: periodo,
                cargo: cargo,
                local: item.querySelector('.experiencia-local')?.value || ''
            });
        }
    });
    return experiencias;
}

function coletarRealizacoes() {
    const realizacoes = [];
    document.querySelectorAll('#listaRealizacoes .item-simples .realizacao-texto').forEach(input => {
        const texto = input.value.trim();
        if (texto) realizacoes.push(texto);
    });
    return realizacoes;
}

function coletarIdiomas() {
    const idiomas = [];
    document.querySelectorAll('#listaIdiomas .item-simples .idioma-texto').forEach(input => {
        const texto = input.value.trim();
        if (texto) idiomas.push(texto);
    });
    return idiomas;
}

async function salvarDirector(event) {
    event.preventDefault();
    
    const formData = new FormData();
    formData.append('action', 'salvar');
    formData.append('nome', document.getElementById('campoNome').value);
    formData.append('cargo', document.getElementById('campoCargo').value);
    formData.append('data_nascimento', document.getElementById('campoDataNascimento').value);
    formData.append('naturalidade', document.getElementById('campoNaturalidade').value);
    formData.append('experiencia', document.getElementById('campoExperiencia').value);
    formData.append('inicio_cargo', document.getElementById('campoInicioCargo').value);
    formData.append('resumo', document.getElementById('campoResumo').value);
    formData.append('citacao', document.getElementById('campoCitacao').value);
    formData.append('formacoes', JSON.stringify(coletarFormacoes()));
    formData.append('experiencias', JSON.stringify(coletarExperiencias()));
    formData.append('realizacoes', JSON.stringify(coletarRealizacoes()));
    formData.append('idiomas', JSON.stringify(coletarIdiomas()));
    
    if (fotoFile) {
        formData.append('foto', fotoFile);
    }
    
    try {
        const response = await fetch('processos/processar-perfil-director.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            mostrarNotificacao(data.message, 'sucesso');
            setTimeout(() => location.reload(), 1500);
        } else {
            mostrarNotificacao(data.message, 'erro');
        }
    } catch (error) {
        mostrarNotificacao('Erro ao salvar perfil', 'erro');
    }
}

document.getElementById('btnCancelarConfirmacao')?.addEventListener('click', () => {
    document.getElementById('modalConfirmacao').classList.remove('ativo');
});

document.getElementById('modalDirector')?.addEventListener('click', (e) => {
    if (e.target === document.getElementById('modalDirector')) fecharModalDirector();
});

document.getElementById('modalConfirmacao')?.addEventListener('click', (e) => {
    if (e.target === document.getElementById('modalConfirmacao')) {
        document.getElementById('modalConfirmacao').classList.remove('ativo');
    }
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        fecharModalDirector();
        document.getElementById('modalConfirmacao').classList.remove('ativo');
    }
});
</script>

<?php include 'includes/footer.php'; ?>
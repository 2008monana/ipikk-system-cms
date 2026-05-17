<?php
/**
 * Órgãos Directivos - Área Restrita IPIKK
 * Gestão completa da equipa (direção, coordenadores, chefes de área)
 */

$titulo_pagina = 'Órgãos Directivos';
$css_especifico = 'admin-orgaos.css';

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
// BUSCAR MEMBROS DA EQUIPE
// ============================================

// Direção Executiva (tipo_card: grande)
$direcao_executiva = $db->query("
    SELECT * FROM equipe 
    WHERE categoria = 'direcao_executiva' 
    ORDER BY ordem
")->fetchAll();

// Coordenadores de Curso
$coordenadores_curso = $db->query("
    SELECT * FROM equipe 
    WHERE categoria = 'coordenador_curso' 
    ORDER BY ordem
")->fetchAll();

// Coordenadores de Disciplina
$coordenadores_disciplina = $db->query("
    SELECT * FROM equipe 
    WHERE categoria = 'coordenador_disciplina' 
    ORDER BY ordem
")->fetchAll();

// Chefes de Área
$chefes_area = $db->query("
    SELECT * FROM equipe 
    WHERE categoria = 'chefe_area' 
    ORDER BY ordem
")->fetchAll();

// Outros Coordenadores
$outros_coordenadores = $db->query("
    SELECT * FROM equipe 
    WHERE categoria = 'outros' 
    ORDER BY ordem
")->fetchAll();

// Estatísticas
$total_membros = count($direcao_executiva) + count($coordenadores_curso) + 
                 count($coordenadores_disciplina) + count($chefes_area) + 
                 count($outros_coordenadores);

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
                <i class="fas fa-users-cog"></i> Órgãos Directivos
            </h1>
        </div>
        <div class="direita-barra">
            <button class="btn-primario" id="btnNovoMembro" onclick="abrirModalMembro()">
                <i class="fas fa-plus"></i>
                <span>Novo Membro</span>
            </button>
        </div>
    </header>

    <div class="conteudo-pagina">

        <!-- CARDS ESTATÍSTICAS -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon purple"><i class="fas fa-users"></i></div>
                <div class="stat-info">
                    <h3><?= $total_membros ?></h3>
                    <p>Total de Membros</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-crown"></i></div>
                <div class="stat-info">
                    <h3><?= count($direcao_executiva) ?></h3>
                    <p>Direção Executiva</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-chalkboard-user"></i></div>
                <div class="stat-info">
                    <h3><?= count($coordenadores_curso) + count($coordenadores_disciplina) ?></h3>
                    <p>Coordenadores</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon orange"><i class="fas fa-briefcase"></i></div>
                <div class="stat-info">
                    <h3><?= count($chefes_area) + count($outros_coordenadores) ?></h3>
                    <p>Chefes e Outros</p>
                </div>
            </div>
        </div>

        <!-- ===== DIREÇÃO EXECUTIVA ===== -->
        <div class="secao-conteudo">
            <div class="secao-header">
                <h2 class="secao-titulo">
                    <i class="fas fa-crown"></i> Direção Executiva
                    <span class="contador">(<?= count($direcao_executiva) ?>)</span>
                </h2>
                <button class="btn-adicionar" onclick="abrirModalMembro('direcao_executiva', 'grande')">
                    <i class="fas fa-plus"></i> Adicionar
                </button>
            </div>
            <div class="grid-grandes" id="gridDirecao">
                <?php foreach ($direcao_executiva as $membro): ?>
                <div class="card-grande" data-id="<?= $membro['id'] ?>">
                    <div class="card-acoes">
                        <button class="btn-editar" onclick="editarMembro(<?= $membro['id'] ?>)">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-eliminar" onclick="eliminarMembro(<?= $membro['id'] ?>)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    <div class="card-foto">
                        <img src="<?= htmlspecialchars(normalizarUrlMidia($membro['foto_url'] ?? 'foto/sem_foto.png', '..')) ?>" 
                             alt="<?= htmlspecialchars($membro['nome']) ?>"
                             onerror="this.src='foto/sem_foto.png'">
                    </div>
                    <h3><?= htmlspecialchars($membro['nome']) ?></h3>
                    <p><?= htmlspecialchars($membro['cargo']) ?></p>
                </div>
                <?php endforeach; ?>
                <?php if (empty($direcao_executiva)): ?>
                <div class="empty-placeholder">
                    <i class="fas fa-users"></i>
                    <p>Nenhum membro na Direção Executiva</p>
                    <button class="btn-link" onclick="abrirModalMembro('direcao_executiva', 'grande')">Adicionar</button>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ===== COORDENADORES DE CURSO ===== -->
        <div class="secao-conteudo">
            <div class="secao-header">
                <h2 class="secao-titulo">
                    <i class="fas fa-graduation-cap"></i> Coordenadores de Curso
                    <span class="contador">(<?= count($coordenadores_curso) ?>)</span>
                </h2>
                <button class="btn-adicionar" onclick="abrirModalMembro('coordenador_curso', 'pequeno')">
                    <i class="fas fa-plus"></i> Adicionar
                </button>
            </div>
            <div class="grid-pequenos" id="gridCoordenadoresCurso">
                <?php foreach ($coordenadores_curso as $membro): ?>
                <div class="card-pequeno" data-id="<?= $membro['id'] ?>">
                    <div class="card-foto">
                        <img src="<?= htmlspecialchars(normalizarUrlMidia($membro['foto_url'] ?? 'foto/sem_foto.png', '..')) ?>" 
                             alt="<?= htmlspecialchars($membro['nome']) ?>"
                             onerror="this.src='foto/sem_foto.png'">
                    </div>
                    <div class="card-info">
                        <h4><?= htmlspecialchars($membro['nome']) ?></h4>
                        <p><?= htmlspecialchars($membro['cargo']) ?></p>
                    </div>
                    <div class="card-acoes">
                        <button class="btn-editar" onclick="editarMembro(<?= $membro['id'] ?>)">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-eliminar" onclick="eliminarMembro(<?= $membro['id'] ?>)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($coordenadores_curso)): ?>
                <div class="empty-placeholder">
                    <i class="fas fa-graduation-cap"></i>
                    <p>Nenhum coordenador de curso cadastrado</p>
                    <button class="btn-link" onclick="abrirModalMembro('coordenador_curso', 'pequeno')">Adicionar</button>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ===== COORDENADORES DE DISCIPLINA ===== -->
        <div class="secao-conteudo">
            <div class="secao-header">
                <h2 class="secao-titulo">
                    <i class="fas fa-book"></i> Coordenadores de Disciplina
                    <span class="contador">(<?= count($coordenadores_disciplina) ?>)</span>
                </h2>
                <button class="btn-adicionar" onclick="abrirModalMembro('coordenador_disciplina', 'pequeno')">
                    <i class="fas fa-plus"></i> Adicionar
                </button>
            </div>
            <div class="grid-pequenos" id="gridCoordenadoresDisciplina">
                <?php foreach ($coordenadores_disciplina as $membro): ?>
                <div class="card-pequeno" data-id="<?= $membro['id'] ?>">
                    <div class="card-foto">
                        <img src="<?= htmlspecialchars(normalizarUrlMidia($membro['foto_url'] ?? 'foto/sem_foto.png', '..')) ?>" 
                             alt="<?= htmlspecialchars($membro['nome']) ?>"
                             onerror="this.src='foto/sem_foto.png'">
                    </div>
                    <div class="card-info">
                        <h4><?= htmlspecialchars($membro['nome']) ?></h4>
                        <p><?= htmlspecialchars($membro['cargo']) ?></p>
                    </div>
                    <div class="card-acoes">
                        <button class="btn-editar" onclick="editarMembro(<?= $membro['id'] ?>)">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-eliminar" onclick="eliminarMembro(<?= $membro['id'] ?>)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($coordenadores_disciplina)): ?>
                <div class="empty-placeholder">
                    <i class="fas fa-book"></i>
                    <p>Nenhum coordenador de disciplina cadastrado</p>
                    <button class="btn-link" onclick="abrirModalMembro('coordenador_disciplina', 'pequeno')">Adicionar</button>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ===== CHEFES DE ÁREA ===== -->
        <div class="secao-conteudo">
            <div class="secao-header">
                <h2 class="secao-titulo">
                    <i class="fas fa-building"></i> Chefes de Área
                    <span class="contador">(<?= count($chefes_area) ?>)</span>
                </h2>
                <button class="btn-adicionar" onclick="abrirModalMembro('chefe_area', 'pequeno')">
                    <i class="fas fa-plus"></i> Adicionar
                </button>
            </div>
            <div class="grid-pequenos" id="gridChefesArea">
                <?php foreach ($chefes_area as $membro): ?>
                <div class="card-pequeno" data-id="<?= $membro['id'] ?>">
                    <div class="card-foto">
                        <img src="<?= htmlspecialchars(normalizarUrlMidia($membro['foto_url'] ?? 'foto/sem_foto.png', '..')) ?>" 
                             alt="<?= htmlspecialchars($membro['nome']) ?>"
                             onerror="this.src='foto/sem_foto.png'">
                    </div>
                    <div class="card-info">
                        <h4><?= htmlspecialchars($membro['nome']) ?></h4>
                        <p><?= htmlspecialchars($membro['cargo']) ?></p>
                    </div>
                    <div class="card-acoes">
                        <button class="btn-editar" onclick="editarMembro(<?= $membro['id'] ?>)">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-eliminar" onclick="eliminarMembro(<?= $membro['id'] ?>)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($chefes_area)): ?>
                <div class="empty-placeholder">
                    <i class="fas fa-building"></i>
                    <p>Nenhum chefe de área cadastrado</p>
                    <button class="btn-link" onclick="abrirModalMembro('chefe_area', 'pequeno')">Adicionar</button>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ===== OUTROS COORDENADORES ===== -->
        <div class="secao-conteudo">
            <div class="secao-header">
                <h2 class="secao-titulo">
                    <i class="fas fa-users"></i> Outros Coordenadores
                    <span class="contador">(<?= count($outros_coordenadores) ?>)</span>
                </h2>
                <button class="btn-adicionar" onclick="abrirModalMembro('outros', 'pequeno')">
                    <i class="fas fa-plus"></i> Adicionar
                </button>
            </div>
            <div class="grid-pequenos" id="gridOutros">
                <?php foreach ($outros_coordenadores as $membro): ?>
                <div class="card-pequeno" data-id="<?= $membro['id'] ?>">
                    <div class="card-foto">
                        <img src="<?= htmlspecialchars(normalizarUrlMidia($membro['foto_url'] ?? 'foto/sem_foto.png', '..')) ?>" 
                             alt="<?= htmlspecialchars($membro['nome']) ?>"
                             onerror="this.src='foto/sem_foto.png'">
                    </div>
                    <div class="card-info">
                        <h4><?= htmlspecialchars($membro['nome']) ?></h4>
                        <p><?= htmlspecialchars($membro['cargo']) ?></p>
                    </div>
                    <div class="card-acoes">
                        <button class="btn-editar" onclick="editarMembro(<?= $membro['id'] ?>)">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-eliminar" onclick="eliminarMembro(<?= $membro['id'] ?>)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($outros_coordenadores)): ?>
                <div class="empty-placeholder">
                    <i class="fas fa-users"></i>
                    <p>Nenhum outro coordenador cadastrado</p>
                    <button class="btn-link" onclick="abrirModalMembro('outros', 'pequeno')">Adicionar</button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<!-- MODAL PARA MEMBRO -->
<div id="modalMembro" class="modal">
    <div class="modal-conteudo">
        <div class="modal-cabecalho">
            <h2 id="modalTitulo"><i class="fas fa-user-plus"></i> Novo Membro</h2>
            <button class="modal-fechar" onclick="fecharModalMembro()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-corpo">
            <form id="formMembro" onsubmit="return salvarMembro(event)">
                <input type="hidden" id="membroId">
                <input type="hidden" id="categoriaAtual">
                <input type="hidden" id="tipoCardAtual">

                <div class="grupo-form">
                    <label><i class="fas fa-user"></i> Nome *</label>
                    <input type="text" id="campoNome" class="campo-form" required>
                </div>

                <div class="grupo-form">
                    <label><i class="fas fa-briefcase"></i> Cargo *</label>
                    <input type="text" id="campoCargo" class="campo-form" required>
                </div>

                <div class="grupo-form">
                    <label><i class="fas fa-tag"></i> Categoria</label>
                    <select id="campoCategoria" class="campo-form" required>
                        <option value="direcao_executiva">Direção Executiva</option>
                        <option value="coordenador_curso">Coordenador de Curso</option>
                        <option value="coordenador_disciplina">Coordenador de Disciplina</option>
                        <option value="chefe_area">Chefe de Área</option>
                        <option value="outros">Outros Coordenadores</option>
                    </select>
                </div>

                <div class="grupo-form" id="grupoTipoCard" style="display: none;">
                    <label><i class="fas fa-border-all"></i> Tipo de Card</label>
                    <select id="campoTipoCard" class="campo-form">
                        <option value="grande">Card Grande (Foto 120px)</option>
                        <option value="pequeno">Card Pequeno (Foto 70px)</option>
                    </select>
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
                    <button type="button" class="btn-cancelar" onclick="fecharModalMembro()">
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
/* ===== ESTILOS ADMIN ORGAOS ===== */

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 32px;
}

.stat-card {
    background: white;
    border-radius: 20px;
    padding: 22px 18px;
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
.stat-icon.orange { 
    background: linear-gradient(135deg, #fff3e0 0%, #fed7aa 100%);
    color: #ea580c; 
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

/* Secções de conteúdo */
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
    gap: 10px;
    color: #0f172a;
}

.secao-titulo i {
    color: #0a9396;
    font-size: 1.2rem;
    width: 32px;
    height: 32px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(10, 147, 150, 0.1);
    border-radius: 10px;
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

/* Botão Adicionar - Estilizado */
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

/* Botão Principal (Novo Membro) */
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

/* Grid para cards grandes (Direção Executiva) */
.grid-grandes {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 28px;
}

.card-grande {
    background: white;
    border-radius: 20px;
    padding: 24px 20px;
    text-align: center;
    border: 1px solid #eef2f8;
    transition: all 0.3s ease;
    position: relative;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
}

.card-grande:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 28px rgba(0, 0, 0, 0.1);
    border-color: #cbd5e1;
}

.card-grande .card-acoes {
    position: absolute;
    top: 16px;
    right: 16px;
    display: flex;
    gap: 8px;
    opacity: 0;
    transition: all 0.2s ease;
}

.card-grande:hover .card-acoes {
    opacity: 1;
}

.card-grande .card-foto {
    width: 130px;
    height: 130px;
    margin: 0 auto 18px;
    border-radius: 50%;
    overflow: hidden;
    border: 3px solid #0a9396;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.card-grande:hover .card-foto {
    transform: scale(1.02);
    border-color: #008bb5;
}

.card-grande .card-foto img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.card-grande h3 {
    font-size: 1rem;
    font-weight: 700;
    margin-bottom: 6px;
    color: #1e293b;
}

.card-grande p {
    font-size: 0.75rem;
    color: #64748b;
    line-height: 1.4;
}

/* Grid para cards pequenos */
.grid-pequenos {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 16px;
}

.card-pequeno {
    background: #fafbfc;
    border-radius: 16px;
    padding: 16px 18px;
    display: flex;
    align-items: center;
    gap: 16px;
    transition: all 0.3s ease;
    position: relative;
    border: 1px solid #edf2f7;
}

.card-pequeno:hover {
    background: white;
    transform: translateX(4px);
    border-color: #cbd5e1;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.card-pequeno .card-foto {
    width: 65px;
    height: 65px;
    border-radius: 50%;
    overflow: hidden;
    flex-shrink: 0;
    border: 2px solid #0a9396;
    transition: all 0.2s ease;
}

.card-pequeno:hover .card-foto {
    transform: scale(1.02);
    border-color: #008bb5;
}

.card-pequeno .card-foto img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.card-pequeno .card-info {
    flex: 1;
}

.card-pequeno .card-info h4 {
    font-size: 0.9rem;
    font-weight: 700;
    margin-bottom: 4px;
    color: #1e293b;
}

.card-pequeno .card-info p {
    font-size: 0.7rem;
    color: #64748b;
    line-height: 1.3;
}

.card-pequeno .card-acoes {
    display: flex;
    gap: 8px;
    opacity: 0;
    transition: all 0.2s ease;
}

.card-pequeno:hover .card-acoes {
    opacity: 1;
}

/* Botões de edição e eliminação */
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

/* Empty placeholder */
.empty-placeholder {
    text-align: center;
    padding: 48px 24px;
    background: #fafbfc;
    border-radius: 20px;
    border: 1px dashed #cbd5e1;
}

.empty-placeholder i {
    font-size: 48px;
    color: #cbd5e1;
    margin-bottom: 16px;
    display: block;
}

.empty-placeholder p {
    color: #94a3b8;
    font-size: 0.85rem;
    margin-bottom: 12px;
}

.btn-link {
    background: none;
    border: none;
    color: #0a9396;
    cursor: pointer;
    font-size: 0.8rem;
    font-weight: 500;
    transition: all 0.2s ease;
}

.btn-link:hover {
    color: #008bb5;
    text-decoration: underline;
}

/* Modal */
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
    max-width: 580px;
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

.campo-form:disabled {
    background: #f8fafc;
    color: #94a3b8;
}

select.campo-form {
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748b'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 12px center;
    background-size: 18px;
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

/* Modal actions */
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

/* Responsividade */
@media (max-width: 1024px) {
    .stats-grid {
        gap: 16px;
    }
    
    .grid-grandes {
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
        gap: 20px;
    }
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }
    
    .grid-grandes, .grid-pequenos {
        grid-template-columns: 1fr;
    }
    
    .secao-conteudo {
        padding: 20px;
    }
    
    .secao-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .card-grande .card-acoes {
        opacity: 1;
    }
    
    .card-pequeno .card-acoes {
        opacity: 1;
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
}

@media (max-width: 480px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .stat-card {
        padding: 16px;
    }
    
    .secao-titulo {
        font-size: 1rem;
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
    
    .card-pequeno {
        flex-wrap: wrap;
        text-align: center;
        justify-content: center;
    }
    
    .card-pequeno .card-acoes {
        width: 100%;
        justify-content: center;
        margin-top: 8px;
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

async function salvarMembro(event) {
    event.preventDefault();
    
    const id = document.getElementById('membroId').value;
    const nome = document.getElementById('campoNome').value;
    const cargo = document.getElementById('campoCargo').value;
    const categoria = document.getElementById('campoCategoria').value;
    const tipoCard = document.getElementById('campoTipoCard').value;
    const ordem = document.getElementById('campoOrdem').value;
    const fotoInput = document.getElementById('fotoInput').files[0];
    
    if (!nome || !cargo || !categoria) {
        mostrarNotificacao('Preencha os campos obrigatórios', 'erro');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', id ? 'editar' : 'salvar');
    if (id) formData.append('id', id);
    formData.append('nome', nome);
    formData.append('cargo', cargo);
    formData.append('categoria', categoria);
    formData.append('tipo_card', tipoCard || 'pequeno');
    formData.append('ordem', ordem);
    if (fotoInput) formData.append('foto', fotoInput);
    
    try {
        const response = await fetch('processos/processar-orgaos.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            mostrarNotificacao(data.message, 'sucesso');
            fecharModalMembro();
            setTimeout(() => location.reload(), 1000);
        } else {
            mostrarNotificacao(data.message, 'erro');
        }
    } catch (error) {
        mostrarNotificacao('Erro ao salvar', 'erro');
    }
}

function abrirModalMembro(categoria = null, tipoCard = null) {
    document.getElementById('formMembro').reset();
    document.getElementById('membroId').value = '';
    document.getElementById('previewFoto').style.display = 'none';
    document.getElementById('campoOrdem').value = '0';
    
    if (categoria) {
        document.getElementById('campoCategoria').value = categoria;
        document.getElementById('grupoTipoCard').style.display = 'block';
        if (tipoCard) document.getElementById('campoTipoCard').value = tipoCard;
    } else {
        document.getElementById('grupoTipoCard').style.display = 'block';
    }
    
    document.getElementById('modalMembro').classList.add('ativo');
}

function fecharModalMembro() {
    document.getElementById('modalMembro').classList.remove('ativo');
}

function editarMembro(id) {
    fetch(`processos/processar-orgaos.php?action=buscar&id=${id}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const m = data.membro;
                document.getElementById('membroId').value = m.id;
                document.getElementById('campoNome').value = m.nome;
                document.getElementById('campoCargo').value = m.cargo;
                document.getElementById('campoCategoria').value = m.categoria;
                document.getElementById('campoOrdem').value = m.ordem;
                document.getElementById('grupoTipoCard').style.display = 'block';
                document.getElementById('campoTipoCard').value = m.tipo_card || 'pequeno';
                
                if (m.foto_url && m.foto_url !== 'foto/sem_foto.png') {
                    document.getElementById('previewImg').src = '../area-publica/' + m.foto_url;
                    document.getElementById('previewFoto').style.display = 'flex';
                }
                
                document.getElementById('modalMembro').classList.add('ativo');
            }
        });
}

function eliminarMembro(id) {
    const modal = document.getElementById('modalConfirmacao');
    document.getElementById('confirmacaoTitulo').textContent = 'Eliminar Membro';
    document.getElementById('confirmacaoTexto').textContent = 'Tem certeza que deseja eliminar este membro permanentemente?';
    
    const confirmar = document.getElementById('btnConfirmarAcao');
    const novoConfirmar = confirmar.cloneNode(true);
    confirmar.parentNode.replaceChild(novoConfirmar, confirmar);
    
    novoConfirmar.onclick = async () => {
        try {
            const response = await fetch('processos/processar-orgaos.php', {
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

document.getElementById('btnCancelarConfirmacao')?.addEventListener('click', () => {
    document.getElementById('modalConfirmacao').classList.remove('ativo');
});

document.getElementById('modalMembro')?.addEventListener('click', (e) => {
    if (e.target === document.getElementById('modalMembro')) fecharModalMembro();
});

document.getElementById('modalConfirmacao')?.addEventListener('click', (e) => {
    if (e.target === document.getElementById('modalConfirmacao')) {
        document.getElementById('modalConfirmacao').classList.remove('ativo');
    }
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        fecharModalMembro();
        document.getElementById('modalConfirmacao').classList.remove('ativo');
    }
});
</script>

<?php include 'includes/footer.php'; ?>
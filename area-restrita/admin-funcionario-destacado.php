<?php
/**
 * Funcionário Destacado - Área Restrita IPIKK
 * Gestão completa dos funcionários em destaque (duas linhas)
 */

$titulo_pagina = 'Funcionários Destacados';
$css_especifico = 'admin-funcionario-destacado.css';

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
// BUSCAR DADOS
// ============================================

// Ano lectivo atual (do config)
$ano_lectivo = $config['ano_lectivo_atual'] ?? date('Y') . '/' . (date('Y') + 1);

// Funcionários do Grupo 1 (primeira linha)
$funcionarios_grupo1 = $db->prepare("
    SELECT * FROM funcionarios_destaque 
    WHERE ativo = 1 AND grupo = 1 
    ORDER BY ordem
");
$funcionarios_grupo1->execute();
$funcionarios_grupo1 = $funcionarios_grupo1->fetchAll();

// Funcionários do Grupo 2 (segunda linha)
$funcionarios_grupo2 = $db->prepare("
    SELECT * FROM funcionarios_destaque 
    WHERE ativo = 1 AND grupo = 2 
    ORDER BY ordem
");
$funcionarios_grupo2->execute();
$funcionarios_grupo2 = $funcionarios_grupo2->fetchAll();

// Buscar conteúdo da página (JSON da tabela conteudo_paginas) para textos
$pagina = getPagina('funcionarios');
$hero_titulo = $pagina['hero_titulo'] ?? 'Funcionários Destacados';
$hero_subtitulo = $pagina['hero_subtitulo'] ?? 'Reconhecimento pela dedicação, excelência e contribuição ao ensino técnico-profissional';
$faixa_texto = $pagina['faixa_texto'] ?? 'O IPIKK reconhece e valoriza todos os seus colaboradores pelo empenho e dedicação no desenvolvimento do ensino técnico-profissional em Angola.';

// Estatísticas
$total_funcionarios = count($funcionarios_grupo1) + count($funcionarios_grupo2);

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
                <i class="fas fa-star"></i> Funcionários Destacados
            </h1>
        </div>
    </header>

    <div class="conteudo-pagina">

        <!-- CARDS ESTATÍSTICAS -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon purple"><i class="fas fa-users"></i></div>
                <div class="stat-info">
                    <h3><?= $total_funcionarios ?></h3>
                    <p>Total de Funcionários</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon gold"><i class="fas fa-star"></i></div>
                <div class="stat-info">
                    <h3><?= count($funcionarios_grupo1) ?></h3>
                    <p>Primeira Linha</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-star-half-alt"></i></div>
                <div class="stat-info">
                    <h3><?= count($funcionarios_grupo2) ?></h3>
                    <p>Segunda Linha</p>
                </div>
            </div>
        </div>

        <!-- INFORMAÇÕES GERAIS -->
        <div class="secao-conteudo">
            <h2 class="secao-titulo">
                <i class="fas fa-calendar-alt"></i> Informações Gerais
            </h2>
            <div class="linha-form">
                <div class="grupo-form">
                    <label><i class="fas fa-calendar"></i> Ano Lectivo *</label>
                    <input type="text" id="anoLectivo" class="campo-form" value="<?= htmlspecialchars($ano_lectivo) ?>" placeholder="Ex: 2024/2025">
                </div>
                <div class="grupo-form">
                    <label><i class="fas fa-heading"></i> Título da Página</label>
                    <input type="text" id="heroTitulo" class="campo-form" value="<?= htmlspecialchars($hero_titulo) ?>">
                </div>
            </div>
            <div class="linha-form">
                <div class="grupo-form">
                    <label><i class="fas fa-align-left"></i> Subtítulo</label>
                    <input type="text" id="heroSubtitulo" class="campo-form" value="<?= htmlspecialchars($hero_subtitulo) ?>">
                </div>
                <div class="grupo-form">
                    <label><i class="fas fa-quote-left"></i> Texto da Faixa de Reconhecimento</label>
                    <input type="text" id="faixaTexto" class="campo-form" value="<?= htmlspecialchars($faixa_texto) ?>">
                </div>
            </div>
        </div>

        <!-- PRIMEIRA LINHA -->
        <div class="secao-conteudo">
            <div class="secao-header">
                <h2 class="secao-titulo">
                    <i class="fas fa-star"></i> Primeira Linha
                    <span class="contador">(<?= count($funcionarios_grupo1) ?> funcionários)</span>
                </h2>
                <button type="button" class="btn-adicionar" onclick="adicionarFuncionario(1)">
                    <i class="fas fa-plus"></i> Adicionar
                </button>
            </div>
            <div id="listaGrupo1" class="lista-funcionarios">
                <?php foreach ($funcionarios_grupo1 as $index => $func): ?>
                <div class="item-funcionario" data-grupo="1" data-id="<?= $func['id'] ?>">
                    <div class="item-header">
                        <span class="item-numero"><?= $index + 1 ?></span>
                        <div class="item-acoes">
                            <button type="button" class="btn-editar" onclick="editarFuncionario(this)"><i class="fas fa-edit"></i></button>
                            <button type="button" class="btn-eliminar" onclick="eliminarFuncionario(this)"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                    <div class="item-preview">
                        <div class="preview-foto">
                            <img src="<?= htmlspecialchars(normalizarUrlMidia($func['foto_url'] ?? 'foto/sem_foto.png', '..')) ?>" class="func-foto-preview" onerror="this.src='foto/sem_foto.png'">
                        </div>
                        <div class="preview-info">
                            <strong><?= htmlspecialchars($func['nome']) ?></strong>
                            <span><?= htmlspecialchars($func['cargo']) ?></span>
                        </div>
                    </div>
                    <div class="item-form" style="display: none;">
                        <div class="linha-form">
                            <div class="grupo-form">
                                <label>Nome</label>
                                <input type="text" class="campo-form nome-input" value="<?= htmlspecialchars($func['nome']) ?>">
                            </div>
                            <div class="grupo-form">
                                <label>Cargo</label>
                                <input type="text" class="campo-form cargo-input" value="<?= htmlspecialchars($func['cargo']) ?>">
                            </div>
                        </div>
                        <div class="grupo-form">
                            <label>Foto</label>
                            <div class="area-upload-pequena" onclick="document.getElementById('fotoGrupo1_<?= $index ?>').click()">
                                <i class="fas fa-cloud-upload-alt"></i> Alterar Foto
                            </div>
                            <input type="file" id="fotoGrupo1_<?= $index ?>" accept="image/*" style="display: none;" data-index="<?= $index ?>" data-grupo="1" onchange="previewFuncionarioFoto(this)">
                            <div class="preview-foto-pequena" id="previewGrupo1_<?= $index ?>">
                                <img src="<?= htmlspecialchars(normalizarUrlMidia($func['foto_url'] ?? 'foto/sem_foto.png', '..')) ?>" class="func-foto-preview-pequena">
                            </div>
                        </div>
                        <div class="item-actions">
                            <button type="button" class="btn-salvar-item" onclick="salvarFuncionario(this)">Salvar</button>
                            <button type="button" class="btn-cancelar-item" onclick="cancelarEdicao(this)">Cancelar</button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($funcionarios_grupo1)): ?>
                <div class="empty-state" id="emptyGrupo1">
                    <i class="fas fa-users"></i>
                    <p>Nenhum funcionário na primeira linha. Clique em "Adicionar" para começar.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- SEGUNDA LINHA -->
        <div class="secao-conteudo">
            <div class="secao-header">
                <h2 class="secao-titulo">
                    <i class="fas fa-star-half-alt"></i> Segunda Linha
                    <span class="contador">(<?= count($funcionarios_grupo2) ?> funcionários)</span>
                </h2>
                <button type="button" class="btn-adicionar" onclick="adicionarFuncionario(2)">
                    <i class="fas fa-plus"></i> Adicionar
                </button>
            </div>
            <div id="listaGrupo2" class="lista-funcionarios">
                <?php foreach ($funcionarios_grupo2 as $index => $func): ?>
                <div class="item-funcionario" data-grupo="2" data-id="<?= $func['id'] ?>">
                    <div class="item-header">
                        <span class="item-numero"><?= $index + 1 ?></span>
                        <div class="item-acoes">
                            <button type="button" class="btn-editar" onclick="editarFuncionario(this)"><i class="fas fa-edit"></i></button>
                            <button type="button" class="btn-eliminar" onclick="eliminarFuncionario(this)"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                    <div class="item-preview">
                        <div class="preview-foto">
                            <img src="<?= htmlspecialchars(normalizarUrlMidia($func['foto_url'] ?? 'foto/sem_foto.png', '..')) ?>" class="func-foto-preview" onerror="this.src='foto/sem_foto.png'">
                        </div>
                        <div class="preview-info">
                            <strong><?= htmlspecialchars($func['nome']) ?></strong>
                            <span><?= htmlspecialchars($func['cargo']) ?></span>
                        </div>
                    </div>
                    <div class="item-form" style="display: none;">
                        <div class="linha-form">
                            <div class="grupo-form">
                                <label>Nome</label>
                                <input type="text" class="campo-form nome-input" value="<?= htmlspecialchars($func['nome']) ?>">
                            </div>
                            <div class="grupo-form">
                                <label>Cargo</label>
                                <input type="text" class="campo-form cargo-input" value="<?= htmlspecialchars($func['cargo']) ?>">
                            </div>
                        </div>
                        <div class="grupo-form">
                            <label>Foto</label>
                            <div class="area-upload-pequena" onclick="document.getElementById('fotoGrupo2_<?= $index ?>').click()">
                                <i class="fas fa-cloud-upload-alt"></i> Alterar Foto
                            </div>
                            <input type="file" id="fotoGrupo2_<?= $index ?>" accept="image/*" style="display: none;" data-index="<?= $index ?>" data-grupo="2" onchange="previewFuncionarioFoto(this)">
                            <div class="preview-foto-pequena" id="previewGrupo2_<?= $index ?>">
                                <img src="<?= htmlspecialchars(normalizarUrlMidia($func['foto_url'] ?? 'foto/sem_foto.png', '..')) ?>" class="func-foto-preview-pequena">
                            </div>
                        </div>
                        <div class="item-actions">
                            <button type="button" class="btn-salvar-item" onclick="salvarFuncionario(this)">Salvar</button>
                            <button type="button" class="btn-cancelar-item" onclick="cancelarEdicao(this)">Cancelar</button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($funcionarios_grupo2)): ?>
                <div class="empty-state" id="emptyGrupo2">
                    <i class="fas fa-users"></i>
                    <p>Nenhum funcionário na segunda linha. Clique em "Adicionar" para começar.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- AÇÕES -->
        <div class="secao-acoes">
            <button class="btn-primario btn-grande" onclick="salvarTodos()">
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
/* ===== ESTILOS ADMIN FUNCIONÁRIO DESTACADO ===== */
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

/* Lista de funcionários */
.lista-funcionarios {
    display: flex;
    flex-direction: column;
    gap: 18px;
}

/* Item funcionário */
.item-funcionario {
    background: linear-gradient(135deg, #fafbfc 0%, #ffffff 100%);
    border-radius: 20px;
    padding: 22px;
    border: 1px solid #eef2f8;
    transition: all 0.3s ease;
}

.item-funcionario:hover {
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

.preview-foto {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    overflow: hidden;
    background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
    border: 2px solid #0a9396;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.func-foto-preview {
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

.preview-info span {
    font-size: 0.8rem;
    color: #0a9396;
    font-weight: 500;
    background: rgba(10, 147, 150, 0.1);
    padding: 3px 12px;
    border-radius: 30px;
    display: inline-block;
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

/* Preview de foto pequena */
.preview-foto-pequena {
    margin-top: 10px;
}

.func-foto-preview-pequena {
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
    
    .preview-foto {
        margin: 0 auto;
    }
    
    .preview-info span {
        display: inline-block;
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
    
    .preview-foto {
        width: 65px;
        height: 65px;
    }
    
    .preview-info strong {
        font-size: 0.9rem;
    }
    
    .preview-info span {
        font-size: 0.7rem;
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
let pendingChanges = [];

function mostrarNotificacao(mensagem, tipo = 'sucesso') {
    const notif = document.createElement('div');
    notif.className = `notificacao ${tipo}`;
    notif.innerHTML = `<i class="fas fa-${tipo === 'sucesso' ? 'check-circle' : 'exclamation-circle'}"></i> ${mensagem}`;
    document.body.appendChild(notif);
    setTimeout(() => notif.remove(), 3000);
}

function previewFuncionarioFoto(input) {
    const file = input.files[0];
    if (!file) return;
    
    if (file.size > 5 * 1024 * 1024) {
        mostrarNotificacao('Arquivo muito grande. Máximo 5MB', 'erro');
        input.value = '';
        return;
    }
    
    const grupo = input.dataset.grupo;
    const index = input.dataset.index;
    const reader = new FileReader();
    
    reader.onload = function(e) {
        const previewDiv = document.getElementById(`previewGrupo${grupo}_${index}`);
        if (previewDiv) {
            previewDiv.innerHTML = `<img src="${e.target.result}" class="func-foto-preview-pequena">`;
        }
    };
    reader.readAsDataURL(file);
}

function adicionarFuncionario(grupo) {
    const container = document.getElementById(`listaGrupo${grupo}`);
    const emptyState = document.getElementById(`emptyGrupo${grupo}`);
    if (emptyState) emptyState.style.display = 'none';
    
    const novoId = `novo_${Date.now()}_${novoItemCounter++}`;
    const div = document.createElement('div');
    div.className = 'item-funcionario';
    div.setAttribute('data-grupo', grupo);
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
                    <label>Nome *</label>
                    <input type="text" class="campo-form nome-input" placeholder="Digite o nome">
                </div>
                <div class="grupo-form">
                    <label>Cargo *</label>
                    <input type="text" class="campo-form cargo-input" placeholder="Digite o cargo">
                </div>
            </div>
            <div class="grupo-form">
                <label>Foto</label>
                <div class="area-upload-pequena" onclick="document.getElementById('fotoNovo_${novoId}').click()">
                    <i class="fas fa-cloud-upload-alt"></i> Selecionar Foto
                </div>
                <input type="file" id="fotoNovo_${novoId}" accept="image/*" style="display: none;" data-temp-id="${novoId}" onchange="previewNovoFuncionarioFoto(this)">
                <div class="preview-foto-pequena" id="previewNovo_${novoId}"></div>
            </div>
            <div class="item-actions">
                <button type="button" class="btn-salvar-item" onclick="salvarNovoFuncionario(this)">Adicionar</button>
                <button type="button" class="btn-cancelar-item" onclick="removerNovoItem(this)">Cancelar</button>
            </div>
        </div>
    `;
    
    container.appendChild(div);
}

function previewNovoFuncionarioFoto(input) {
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
            previewDiv.innerHTML = `<img src="${e.target.result}" class="func-foto-preview-pequena">`;
        }
    };
    reader.readAsDataURL(file);
}

function removerNovoItem(btn) {
    const item = btn.closest('.item-funcionario');
    if (item) {
        item.remove();
        const container = item.parentElement;
        if (container.children.length === 0) {
            const grupo = container.id === 'listaGrupo1' ? 1 : 2;
            const emptyId = `emptyGrupo${grupo}`;
            const emptyState = document.getElementById(emptyId);
            if (emptyState) emptyState.style.display = 'block';
        }
    }
}

function editarFuncionario(btn) {
    const item = btn.closest('.item-funcionario');
    const previewDiv = item.querySelector('.item-preview');
    const formDiv = item.querySelector('.item-form');
    
    if (previewDiv) previewDiv.style.display = 'none';
    if (formDiv) formDiv.style.display = 'block';
}

function cancelarEdicao(btn) {
    const item = btn.closest('.item-funcionario');
    const previewDiv = item.querySelector('.item-preview');
    const formDiv = item.querySelector('.item-form');
    
    if (previewDiv) previewDiv.style.display = 'flex';
    if (formDiv) formDiv.style.display = 'none';
}

function salvarFuncionario(btn) {
    const item = btn.closest('.item-funcionario');
    const grupo = item.dataset.grupo;
    const id = item.dataset.id;
    const nomeInput = item.querySelector('.nome-input');
    const cargoInput = item.querySelector('.cargo-input');
    const fotoInput = item.querySelector('input[type="file"]');
    
    const nome = nomeInput?.value.trim();
    const cargo = cargoInput?.value.trim();
    const fotoFile = fotoInput?.files[0];
    
    if (!nome || !cargo) {
        mostrarNotificacao('Nome e cargo são obrigatórios', 'erro');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'salvar');
    formData.append('id', id);
    formData.append('grupo', grupo);
    formData.append('nome', nome);
    formData.append('cargo', cargo);
    if (fotoFile) formData.append('foto', fotoFile);
    
    fetch('processos/processar-funcionario-destacado.php', {
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

function salvarNovoFuncionario(btn) {
    const item = btn.closest('.item-funcionario');
    const grupo = item.dataset.grupo;
    const nomeInput = item.querySelector('.nome-input');
    const cargoInput = item.querySelector('.cargo-input');
    const fotoInput = item.querySelector('input[type="file"]');
    
    const nome = nomeInput?.value.trim();
    const cargo = cargoInput?.value.trim();
    const fotoFile = fotoInput?.files[0];
    
    if (!nome || !cargo) {
        mostrarNotificacao('Nome e cargo são obrigatórios', 'erro');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'salvar');
    formData.append('grupo', grupo);
    formData.append('nome', nome);
    formData.append('cargo', cargo);
    if (fotoFile) formData.append('foto', fotoFile);
    
    fetch('processos/processar-funcionario-destacado.php', {
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

function eliminarFuncionario(btn) {
    const modal = document.getElementById('modalConfirmacao');
    const item = btn.closest('.item-funcionario');
    const id = item.dataset.id;
    const nome = item.querySelector('.preview-info strong')?.textContent || 'este funcionário';
    
    document.getElementById('confirmacaoTitulo').textContent = 'Eliminar Funcionário';
    document.getElementById('confirmacaoTexto').textContent = `Tem certeza que deseja eliminar "${nome}" permanentemente?`;
    
    const confirmar = document.getElementById('btnConfirmarAcao');
    const novoConfirmar = confirmar.cloneNode(true);
    confirmar.parentNode.replaceChild(novoConfirmar, confirmar);
    
    novoConfirmar.onclick = async () => {
        try {
            const response = await fetch('processos/processar-funcionario-destacado.php', {
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

function salvarTodos() {
    const anoLectivo = document.getElementById('anoLectivo').value;
    const heroTitulo = document.getElementById('heroTitulo').value;
    const heroSubtitulo = document.getElementById('heroSubtitulo').value;
    const faixaTexto = document.getElementById('faixaTexto').value;
    
    if (!anoLectivo) {
        mostrarNotificacao('Ano lectivo é obrigatório', 'erro');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'salvar_geral');
    formData.append('ano_lectivo', anoLectivo);
    formData.append('hero_titulo', heroTitulo);
    formData.append('hero_subtitulo', heroSubtitulo);
    formData.append('faixa_texto', faixaTexto);
    
    fetch('processos/processar-funcionario-destacado.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarNotificacao(data.message, 'sucesso');
            // Atualizar também o ano exibido no topo da página
            const anoElement = document.querySelector('.fd-etiqueta');
            if (anoElement) {
                anoElement.innerHTML = `<i class="fas fa-award"></i> IPIKK · Ano Lectivo ${anoLectivo}`;
            }
        } else {
            mostrarNotificacao(data.message, 'erro');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        mostrarNotificacao('Erro ao guardar configurações', 'erro');
    });
}
function previewPagina() {
    window.open('../area-publica/funcionario-destacado.php?preview=1', '_blank');
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
<?php
/**
 * Lixeira - Área Restrita IPIKK
 * Gerencia arquivos movidos para lixeira
 */

$titulo_pagina = 'Lixeira';
$css_especifico = 'admin-lixeira.css';

require_once dirname(__DIR__) . '/config/index.php';

// Verificar login
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

// Buscar estatísticas da lixeira
$stmt = $db->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN tipo = 'imagem' THEN 1 ELSE 0 END) as total_imagens,
        SUM(CASE WHEN tipo = 'video' THEN 1 ELSE 0 END) as total_videos,
        SUM(CASE WHEN data_expiracao < NOW() THEN 1 ELSE 0 END) as total_expirados,
        SUM(tamanho_bytes) as espaco_total
    FROM lixeira 
    WHERE restaurado = 0
");
$estatisticas = $stmt->fetch();

// Buscar arquivos na lixeira
$stmt = $db->query("
    SELECT * FROM lixeira 
    WHERE restaurado = 0 
    ORDER BY data_movimento DESC
");
$arquivos_lixeira = $stmt->fetchAll();

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<main class="conteudo-principal">
    <header class="barra-superior">
        <div class="esquerda-barra">
            <button class="botao-menu-mobile" id="botaoMenuMobile">
                <i class="fas fa-bars"></i>
            </button>
            <div class="titulo-pagina">
                <div class="icone-titulo">
                    <i class="fas fa-trash-alt"></i>
                </div>
                <div>
                    <h1>Lixeira</h1>
                    <p>Gerencie os arquivos removidos das notícias</p>
                </div>
            </div>
        </div>
    </header>

    <div class="conteudo-pagina">
        
        <!-- CARDS DE ESTATÍSTICAS -->
        <div class="cards-estatisticas">
            <div class="card-estatistica">
                <div class="card-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="card-info">
                    <span class="card-valor"><?= number_format($estatisticas['total'] ?? 0, 0, ',', '.') ?></span>
                    <span class="card-rotulo">Total de arquivos</span>
                </div>
            </div>
            <div class="card-estatistica">
                <div class="card-icon imagem">
                    <i class="fas fa-image"></i>
                </div>
                <div class="card-info">
                    <span class="card-valor"><?= number_format($estatisticas['total_imagens'] ?? 0, 0, ',', '.') ?></span>
                    <span class="card-rotulo">Imagens</span>
                </div>
            </div>
            <div class="card-estatistica">
                <div class="card-icon video">
                    <i class="fas fa-video"></i>
                </div>
                <div class="card-info">
                    <span class="card-valor"><?= number_format($estatisticas['total_videos'] ?? 0, 0, ',', '.') ?></span>
                    <span class="card-rotulo">Vídeos</span>
                </div>
            </div>
            <div class="card-estatistica">
                <div class="card-icon expirado">
                    <i class="fas fa-hourglass-end"></i>
                </div>
                <div class="card-info">
                    <span class="card-valor"><?= number_format($estatisticas['total_expirados'] ?? 0, 0, ',', '.') ?></span>
                    <span class="card-rotulo">Expirados</span>
                </div>
            </div>
            <div class="card-estatistica">
                <div class="card-icon espaco">
                    <i class="fas fa-hdd"></i>
                </div>
                <div class="card-info">
                    <span class="card-valor"><?= round(($estatisticas['espaco_total'] ?? 0) / 1024 / 1024, 2) ?> <small>MB</small></span>
                    <span class="card-rotulo">Espaço ocupado</span>
                </div>
            </div>
        </div>

        <!-- BARRA DE AÇÕES -->
        <div class="barra-acoes">
            <div class="acoes-principais">
                <button class="btn-acao" onclick="limparLixeira('expirados')" <?= ($estatisticas['total_expirados'] ?? 0) == 0 ? 'disabled' : '' ?>>
                    <i class="fas fa-clock"></i>
                    <span>Limpar Expirados</span>
                    <?php if (($estatisticas['total_expirados'] ?? 0) > 0): ?>
                    <span class="badge"><?= $estatisticas['total_expirados'] ?></span>
                    <?php endif; ?>
                </button>
                <button class="btn-acao" onclick="limparLixeira('imagem')" <?= ($estatisticas['total_imagens'] ?? 0) == 0 ? 'disabled' : '' ?>>
                    <i class="fas fa-image"></i>
                    <span>Limpar Imagens</span>
                </button>
                <button class="btn-acao" onclick="limparLixeira('video')" <?= ($estatisticas['total_videos'] ?? 0) == 0 ? 'disabled' : '' ?>>
                    <i class="fas fa-video"></i>
                    <span>Limpar Vídeos</span>
                </button>
            </div>
            <div class="acoes-perigo">
                <button class="btn-acao btn-danger" onclick="limparLixeira('tudo')" <?= ($estatisticas['total'] ?? 0) == 0 ? 'disabled' : '' ?>>
                    <i class="fas fa-trash-alt"></i>
                    <span>Esvaziar Lixeira</span>
                </button>
            </div>
        </div>

        <!-- TABELA DE ARQUIVOS -->
        <div class="tabela-container">
            <div class="tabela-header">
                <h2 class="tabela-titulo">
                    <i class="fas fa-list-ul"></i>
                    Arquivos na Lixeira
                </h2>
                <?php if (count($arquivos_lixeira) > 0): ?>
                <div class="tabela-info">
                    <i class="fas fa-info-circle"></i>
                    <span>Os arquivos expiram automaticamente após 30 dias</span>
                </div>
                <?php endif; ?>
            </div>

            <?php if (count($arquivos_lixeira) > 0): ?>
            <div class="secao-filtros-lixeira">
                <div class="caixa-busca-lixeira">
                    <i class="fas fa-search"></i>
                    <input type="text" id="campoBuscaLixeira" placeholder="Buscar por notícia ou nome do arquivo...">
                </div>
                <select id="filtroTipoLixeira" class="selecao-filtro-lixeira">
                    <option value="">Todos os tipos</option>
                    <option value="imagem">Imagens</option>
                    <option value="video">Vídeos</option>
                </select>
                <button class="btn-acao btn-restaurar-massa" id="botaoRestaurarSelecionados" disabled>
                    <i class="fas fa-undo-alt"></i>
                    <span>Recuperar Selecionados</span>
                </button>
            </div>
            <?php endif; ?>

            <?php if (count($arquivos_lixeira) > 0): ?>
            <div class="tabela-responsiva">
                <table class="tabela-lixeira">
                    <thead>
                        <tr>
                            <th width="40"><input type="checkbox" id="selecionarTodosLixeira"></th>
                            <th>Tipo</th>
                            <th>Notícia</th>
                            <th>Arquivo</th>
                            <th>Data de remoção</th>
                            <th>Expira em</th>
                            <th>Tamanho</th>
                            <th class="acoes-coluna">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($arquivos_lixeira as $arquivo): 
                            $data_expiracao = new DateTime($arquivo['data_expiracao']);
                            $hoje = new DateTime();
                            $dias_restantes = $hoje->diff($data_expiracao)->days;
                            $expirado = $data_expiracao < $hoje;
                            
                            $tamanho_mb = round($arquivo['tamanho_bytes'] / 1024 / 1024, 2);
                            $tamanho_texto = $tamanho_mb < 0.01 ? '< 0.01 MB' : $tamanho_mb . ' MB';
                            
                            $classe_expiracao = '';
                            $texto_expiracao = '';
                            if ($expirado) {
                                $classe_expiracao = 'expirado';
                                $texto_expiracao = 'Expirado';
                            } elseif ($dias_restantes <= 7) {
                                $classe_expiracao = 'proximo';
                                $texto_expiracao = $dias_restantes . ' dias';
                            } else {
                                $texto_expiracao = $dias_restantes . ' dias';
                            }
                        ?>
                        <tr class="<?= $classe_expiracao ?>" data-id="<?= $arquivo['id'] ?>" data-tipo="<?= $arquivo['tipo'] ?>" data-noticia="<?= htmlspecialchars(strtolower($arquivo['noticia_titulo'] ?? ''), ENT_QUOTES) ?>" data-arquivo="<?= htmlspecialchars(strtolower($arquivo['nome_original'] ?? ''), ENT_QUOTES) ?>">
                            <td><input type="checkbox" class="checkbox-lixeira" data-id="<?= $arquivo['id'] ?>" <?= $expirado ? 'disabled' : '' ?>></td>
                            <td class="tipo-coluna">
                                <?php if ($arquivo['tipo'] == 'imagem'): ?>
                                    <span class="tipo-badge tipo-imagem">
                                        <i class="fas fa-image"></i> Imagem
                                    </span>
                                <?php else: ?>
                                    <span class="tipo-badge tipo-video">
                                        <i class="fas fa-video"></i> Vídeo
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="noticia-coluna">
                                <div class="noticia-info">
                                    <span class="noticia-titulo"><?= htmlspecialchars($arquivo['noticia_titulo'] ?? 'Notícia removida') ?></span>
                                    <span class="noticia-id">ID: <?= $arquivo['noticia_id'] ?></span>
                                </div>
                            </td>
                            <td class="arquivo-coluna">
                                <div class="arquivo-info">
                                    <i class="fas fa-file-alt"></i>
                                    <span class="arquivo-nome" title="<?= htmlspecialchars($arquivo['nome_original']) ?>">
                                        <?= htmlspecialchars($arquivo['nome_original']) ?>
                                    </span>
                                </div>
                            </td>
                            <td class="data-coluna">
                                <div class="data-info">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span><?= date('d/m/Y H:i', strtotime($arquivo['data_movimento'])) ?></span>
                                </div>
                            </td>
                            <td class="expiracao-coluna">
                                <div class="expiracao-info <?= $classe_expiracao ?>">
                                    <i class="fas fa-hourglass-half"></i>
                                    <?php if ($expirado): ?>
                                        <span class="expirado-texto">Expirado</span>
                                    <?php else: ?>
                                        <span><?= $texto_expiracao ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="tamanho-coluna">
                                <div class="tamanho-info">
                                    <i class="fas fa-weight-hanging"></i>
                                    <span><?= $tamanho_texto ?></span>
                                </div>
                            </td>
                            <td class="acoes-coluna">
                                <div class="acoes-botoes">
                                    <?php if (!$expirado): ?>
                                    <button class="btn-restaurar" onclick="restaurarArquivo(<?= $arquivo['id'] ?>)" title="Restaurar arquivo">
                                        <i class="fas fa-undo-alt"></i>
                                        <span>Restaurar</span>
                                    </button>
                                    <?php endif; ?>
                                    <button class="btn-excluir" onclick="excluirArquivoLixeira(<?= $arquivo['id'] ?>)" title="Excluir permanentemente">
                                        <i class="fas fa-trash-alt"></i>
                                        <span>Excluir</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-trash-alt"></i>
                </div>
                <h3>Lixeira vazia</h3>
                <p>Não há arquivos na lixeira. Quando você excluir notícias, os arquivos aparecerão aqui.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<div id="modalConfirmacaoLixeira" class="modal-confirmacao">
    <div class="modal-confirmacao-caixa">
        <div class="modal-confirmacao-icone" id="modalConfirmacaoLixeiraIconeBox">
            <i class="fas fa-exclamation-triangle" id="modalConfirmacaoLixeiraIcone"></i>
        </div>
        <h3 id="modalConfirmacaoLixeiraTitulo">Confirmar ação</h3>
        <p id="modalConfirmacaoLixeiraTexto">Tem certeza que deseja continuar?</p>
        <div class="modal-confirmacao-botoes">
            <button type="button" class="botao-cancelar-modal" id="botaoCancelarLixeira">Cancelar</button>
            <button type="button" class="botao-confirmar-modal" id="botaoConfirmarLixeira"><i class="fas fa-check"></i> Confirmar</button>
        </div>
    </div>
</div>

<style>
/* ===== ESTILOS DA LIXEIRA ===== */

/* Cards de estatísticas */
.cards-estatisticas {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.card-estatistica {
    background: white;
    border-radius: 16px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
    border: 1px solid #eef2f6;
}

.card-estatistica:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
}

.card-icon {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    background: #eef2ff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: #003072;
}

.card-icon.imagem {
    background: #e8f5e9;
    color: #2e7d32;
}

.card-icon.video {
    background: #fff3e0;
    color: #ed6c02;
}

.card-icon.expirado {
    background: #ffebee;
    color: #d32f2f;
}

.card-icon.espaco {
    background: #e3f2fd;
    color: #0288d1;
}

.card-info {
    flex: 1;
}

.card-valor {
    display: block;
    font-size: 28px;
    font-weight: 700;
    color: #1a2c3e;
    line-height: 1.2;
}

.card-valor small {
    font-size: 14px;
    font-weight: 500;
    color: #6c757d;
}

.card-rotulo {
    display: block;
    font-size: 13px;
    color: #6c757d;
    margin-top: 4px;
}

/* Barra de ações */
.barra-acoes {
    background: white;
    border-radius: 16px;
    padding: 16px 20px;
    margin-bottom: 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    border: 1px solid #eef2f6;
}

.acoes-principais {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.acoes-perigo {
    display: flex;
    gap: 12px;
}

.btn-acao {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border: none;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    background: #f5f7fa;
    color: #2c3e50;
}

.btn-acao i {
    font-size: 14px;
}

.btn-acao:hover:not(:disabled) {
    background: #e9ecef;
    transform: translateY(-1px);
}

.btn-acao:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.btn-acao .badge {
    background: #dc3545;
    color: white;
    border-radius: 20px;
    padding: 2px 8px;
    font-size: 11px;
    margin-left: 4px;
}

.btn-danger {
    background: #fee2e2;
    color: #dc3545;
}

.btn-danger:hover:not(:disabled) {
    background: #fecaca;
}

/* Tabela */
.tabela-container {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 10px 28px rgba(15, 23, 42, 0.08);
    border: 1px solid #dbe4ef;
    padding: 18px;
}

.tabela-header {
    padding: 8px 6px 16px;
    border-bottom: 1px solid #eef2f6;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.tabela-titulo {
    font-size: 18px;
    font-weight: 600;
    color: #1a2c3e;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.tabela-titulo i {
    color: #0a9396;
    font-size: 18px;
}

.tabela-info {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: #6c757d;
    background: #f8f9fa;
    padding: 6px 12px;
    border-radius: 20px;
}

.tabela-info i {
    color: #0a9396;
}

.tabela-responsiva {
    overflow-x: auto;
}

.secao-filtros-lixeira {
    display: grid;
    grid-template-columns: 1.4fr 180px auto;
    gap: 12px;
    margin-bottom: 16px;
}

.caixa-busca-lixeira {
    position: relative;
}

.caixa-busca-lixeira i {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #64748b;
}

.caixa-busca-lixeira input,
.selecao-filtro-lixeira {
    width: 100%;
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    font-size: 14px;
}

.caixa-busca-lixeira input {
    padding: 11px 12px 11px 36px;
}

.selecao-filtro-lixeira {
    padding: 11px 12px;
}

.btn-restaurar-massa {
    background: linear-gradient(135deg, #16a34a, #15803d);
    color: #fff;
}

.btn-restaurar-massa:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.tabela-lixeira {
    width: 100%;
    border-collapse: collapse;
}

.tabela-lixeira th {
    padding: 16px 20px;
    text-align: left;
    font-size: 13px;
    font-weight: 600;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    background: #fafcfc;
    border-bottom: 1px solid #eef2f6;
}

.tabela-lixeira td {
    padding: 16px 20px;
    border-bottom: 1px solid #f0f2f5;
    vertical-align: middle;
}

.tabela-lixeira tr:hover {
    background: #fafcfc;
}

.tabela-lixeira tr.expirado {
    background: #fff9f9;
}

.tabela-lixeira tr.expirado:hover {
    background: #fff5f5;
}

/* Colunas estilizadas */
.tipo-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 12px;
    border-radius: 30px;
    font-size: 12px;
    font-weight: 500;
}

.tipo-imagem {
    background: #e8f5e9;
    color: #2e7d32;
}

.tipo-video {
    background: #fff3e0;
    color: #ed6c02;
}

.noticia-info {
    display: flex;
    flex-direction: column;
}

.noticia-titulo {
    font-weight: 500;
    color: #2c3e50;
    font-size: 14px;
}

.noticia-id {
    font-size: 11px;
    color: #9aa6b5;
    margin-top: 2px;
}

.arquivo-info {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: #4a5a6e;
}

.arquivo-info i {
    color: #9aa6b5;
    font-size: 14px;
}

.arquivo-nome {
    max-width: 200px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.data-info {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: #5a6a7e;
}

.data-info i {
    color: #9aa6b5;
    font-size: 12px;
}

.expiracao-info {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    padding: 4px 12px;
    border-radius: 20px;
    background: #f5f7fa;
}

.expiracao-info.expirado {
    background: #fee2e2;
    color: #dc3545;
}

.expiracao-info.proximo {
    background: #fff3e0;
    color: #ed6c02;
}

.expirado-texto {
    font-weight: 500;
}

.tamanho-info {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    font-family: monospace;
    color: #5a6a7e;
}

.tamanho-info i {
    color: #9aa6b5;
}

.acoes-botoes {
    display: flex;
    gap: 8px;
}

.btn-restaurar, .btn-excluir {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border: none;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-restaurar {
    background: #e8f5e9;
    color: #2e7d32;
}

.btn-restaurar:hover {
    background: #c8e6c9;
    transform: translateY(-1px);
}

.btn-excluir {
    background: #fee2e2;
    color: #dc3545;
}

.btn-excluir:hover {
    background: #fecaca;
    transform: translateY(-1px);
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 60px 20px;
}

.empty-state-icon {
    font-size: 64px;
    color: #cbd5e1;
    margin-bottom: 20px;
}

.empty-state h3 {
    font-size: 20px;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 8px;
}

.empty-state p {
    font-size: 14px;
    color: #6c757d;
    margin: 0;
}

/* Título da página */
.titulo-pagina {
    display: flex;
    align-items: center;
    gap: 15px;
}

.icone-titulo {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, #fee2e2, #fff5f5);
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.icone-titulo i {
    font-size: 24px;
    color: #dc3545;
}

.titulo-pagina h1 {
    font-size: 24px;
    font-weight: 600;
    color: #1a2c3e;
    margin: 0;
}

.titulo-pagina p {
    font-size: 13px;
    color: #6c757d;
    margin: 4px 0 0;
}

/* Responsividade */
@media (max-width: 1024px) {
    .cards-estatisticas {
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    }
}

@media (max-width: 768px) {
    .cards-estatisticas {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .barra-acoes {
        flex-direction: column;
        align-items: stretch;
    }
    
    .acoes-principais, .acoes-perigo {
        justify-content: center;
    }
    
    .tabela-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .acoes-botoes {
        flex-direction: column;
    }
    
    .btn-restaurar, .btn-excluir {
        justify-content: center;
    }

    .secao-filtros-lixeira {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 640px) {
    .cards-estatisticas {
        grid-template-columns: 1fr;
    }
}

/* Modal de confirmação estilizado */
.modal-confirmacao {
    position: fixed;
    inset: 0;
    background: radial-gradient(circle at top, rgba(12, 20, 35, 0.58), rgba(5, 10, 20, 0.88));
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 120000;
    padding: 20px;
    backdrop-filter: blur(2px);
}

.modal-confirmacao.ativo {
    display: flex;
}

.modal-confirmacao-caixa {
    width: min(100%, 460px);
    background: linear-gradient(180deg, #ffffff, #f8fafc);
    border-radius: 18px;
    padding: 24px;
    text-align: center;
    box-shadow: 0 24px 70px rgba(0, 0, 0, 0.32);
}

.modal-confirmacao-icone {
    width: 66px;
    height: 66px;
    border-radius: 999px;
    margin: 0 auto 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #fff5f5;
    color: #dc3545;
    font-size: 28px;
}

.modal-confirmacao.acao-restaurar .modal-confirmacao-icone {
    background: #ecfdf3;
    color: #16a34a;
}

.modal-confirmacao.acao-restaurar .botao-confirmar-modal {
    background: linear-gradient(135deg, #16a34a, #15803d);
    color: #fff;
}

.modal-confirmacao.acao-eliminar .modal-confirmacao-icone {
    background: #fff5f5;
    color: #dc2626;
}

.modal-confirmacao.acao-eliminar .botao-confirmar-modal {
    background: #dc2626;
    color: #fff;
}

.modal-confirmacao-caixa h3 {
    margin: 0 0 8px;
    color: #1f2937;
}

.modal-confirmacao-caixa p {
    margin: 0;
    color: #64748b;
}

.modal-confirmacao-botoes {
    margin-top: 18px;
    display: flex;
    justify-content: center;
    gap: 10px;
}

.botao-cancelar-modal,
.botao-confirmar-modal {
    border: none;
    border-radius: 10px;
    padding: 10px 16px;
    font-weight: 600;
    cursor: pointer;
}

.botao-cancelar-modal {
    background: #e2e8f0;
    color: #334155;
}

.botao-confirmar-modal {
    background: #dc2626;
    color: #fff;
}
</style>

<script>
let acaoPendenteLixeira = null;
let itensSelecionadosLixeira = [];

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

function abrirModalConfirmacaoLixeira(titulo, texto, callbackConfirmar, tipoAcao = 'eliminar') {
    const modal = document.getElementById('modalConfirmacaoLixeira');
    const icone = document.getElementById('modalConfirmacaoLixeiraIcone');
    const botaoConfirmar = document.getElementById('botaoConfirmarLixeira');

    document.getElementById('modalConfirmacaoLixeiraTitulo').textContent = titulo;
    document.getElementById('modalConfirmacaoLixeiraTexto').textContent = texto;
    acaoPendenteLixeira = callbackConfirmar;

    modal.classList.remove('acao-restaurar', 'acao-eliminar');
    if (tipoAcao === 'restaurar') {
        modal.classList.add('acao-restaurar');
        if (icone) icone.className = 'fas fa-undo-alt';
        if (botaoConfirmar) botaoConfirmar.innerHTML = '<i class="fas fa-undo-alt"></i> Recuperar';
    } else {
        modal.classList.add('acao-eliminar');
        if (icone) icone.className = 'fas fa-trash-alt';
        if (botaoConfirmar) botaoConfirmar.innerHTML = '<i class="fas fa-trash-alt"></i> Eliminar';
    }

    modal.classList.add('ativo');
    document.body.style.overflow = 'hidden';
    emitirSom('modal');
}

function fecharModalConfirmacaoLixeira() {
    const modal = document.getElementById('modalConfirmacaoLixeira');
    modal.classList.remove('ativo', 'acao-restaurar', 'acao-eliminar');
    acaoPendenteLixeira = null;
    document.body.style.overflow = '';
}

function restaurarArquivo(id) {
    abrirModalConfirmacaoLixeira(
        'Restaurar arquivo',
        'Deseja restaurar este arquivo para a pasta de notícias?',
        () => {
        const formData = new FormData();
        formData.append('acao', 'restaurar_lixeira');
        formData.append('id', id);
        
        fetch('processos/processar-noticia.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarNotificacao(data.message, 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                mostrarNotificacao(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            mostrarNotificacao('Erro ao restaurar arquivo', 'error');
        });
        },
        'restaurar'
    );
}

function atualizarEstadoRecuperacaoMassa() {
    const btn = document.getElementById('botaoRestaurarSelecionados');
    if (btn) btn.disabled = itensSelecionadosLixeira.length === 0;
}

function restaurarSelecionadosLixeira() {
    if (itensSelecionadosLixeira.length === 0) {
        mostrarNotificacao('Selecione pelo menos um arquivo para recuperar.', 'error');
        return;
    }

    abrirModalConfirmacaoLixeira(
        'Recuperar arquivos selecionados',
        `Deseja recuperar ${itensSelecionadosLixeira.length} arquivo(s) selecionado(s)?`,
        async () => {
            try {
                const requisicoes = itensSelecionadosLixeira.map((id) => {
                    const formData = new FormData();
                    formData.append('acao', 'restaurar_lixeira');
                    formData.append('id', id);
                    return fetch('processos/processar-noticia.php', { method: 'POST', body: formData }).then(r => r.json());
                });

                const resultados = await Promise.all(requisicoes);
                const totalSucesso = resultados.filter(r => r.success).length;
                const totalErro = resultados.length - totalSucesso;

                if (totalSucesso > 0) {
                    mostrarNotificacao(`${totalSucesso} arquivo(s) recuperado(s) com sucesso.${totalErro > 0 ? ` ${totalErro} falharam.` : ''}`, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    mostrarNotificacao('Não foi possível recuperar os arquivos selecionados.', 'error');
                }
            } catch (e) {
                mostrarNotificacao('Erro ao recuperar arquivos selecionados.', 'error');
            }
        },
        'restaurar'
    );
}

function aplicarFiltrosLixeira() {
    const termo = (document.getElementById('campoBuscaLixeira')?.value || '').toLowerCase().trim();
    const tipo = document.getElementById('filtroTipoLixeira')?.value || '';
    const linhas = document.querySelectorAll('.tabela-lixeira tbody tr');

    linhas.forEach(linha => {
        const tipoLinha = linha.dataset.tipo || '';
        const noticia = linha.dataset.noticia || '';
        const arquivo = linha.dataset.arquivo || '';
        const atendeBusca = !termo || noticia.includes(termo) || arquivo.includes(termo);
        const atendeTipo = !tipo || tipoLinha === tipo;
        linha.style.display = (atendeBusca && atendeTipo) ? '' : 'none';
    });
}

function excluirArquivoLixeira(id) {
    abrirModalConfirmacaoLixeira(
        'Excluir permanentemente',
        'Esta ação irá excluir permanentemente este arquivo. Esta operação não pode ser desfeita.',
        () => {
        const formData = new FormData();
        formData.append('acao', 'excluir_item_lixeira');
        formData.append('id', id);
        
        fetch('processos/processar-noticia.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarNotificacao(data.message, 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                mostrarNotificacao(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            mostrarNotificacao('Erro ao excluir arquivo', 'error');
        });
        },
        'eliminar'
    );
}

function limparLixeira(tipo) {
    let mensagem = '';
    let titulo = '';
    
    switch(tipo) {
        case 'expirados':
            titulo = 'Limpar arquivos expirados';
            mensagem = 'Tem certeza que deseja limpar apenas os arquivos expirados (30+ dias)? Eles serão removidos permanentemente.';
            break;
        case 'imagem':
            titulo = 'Limpar imagens';
            mensagem = 'Tem certeza que deseja limpar apenas as imagens da lixeira? Esta ação não pode ser desfeita.';
            break;
        case 'video':
            titulo = 'Limpar vídeos';
            mensagem = 'Tem certeza que deseja limpar apenas os vídeos da lixeira? Esta ação não pode ser desfeita.';
            break;
        default:
            titulo = 'Esvaziar lixeira';
            mensagem = 'ATENÇÃO! Isso irá apagar TODOS os arquivos da lixeira permanentemente. Esta ação não pode ser desfeita. Continuar?';
    }
    
    abrirModalConfirmacaoLixeira(titulo, mensagem, () => {
        const formData = new FormData();
        formData.append('acao', 'limpar_lixeira');
        formData.append('tipo', tipo);
        
        fetch('processos/processar-noticia.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarNotificacao(data.message, 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                mostrarNotificacao(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            mostrarNotificacao('Erro ao limpar lixeira', 'error');
        });
    }, 'eliminar');
}

function mostrarNotificacao(mensagem, tipo) {
    const notif = document.createElement('div');
    const bgColor = tipo === 'success' ? '#28a745' : '#dc3545';
    
    notif.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 14px 24px;
        background: ${bgColor};
        color: #fff;
        border-radius: 12px;
        z-index: 99999;
        font-weight: 500;
        font-size: 14px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        animation: slideIn 0.3s ease;
    `;
    
    const icon = tipo === 'success' ? '✓' : '✗';
    notif.innerHTML = `${icon} ${mensagem}`;
    document.body.appendChild(notif);
    emitirSom(tipo);
    
    setTimeout(() => {
        notif.style.opacity = '0';
        notif.style.transition = 'opacity 0.3s';
        setTimeout(() => notif.remove(), 300);
    }, 3000);
}

// Animação slideIn
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
`;
document.head.appendChild(style);

document.getElementById('botaoCancelarLixeira')?.addEventListener('click', fecharModalConfirmacaoLixeira);
document.getElementById('botaoConfirmarLixeira')?.addEventListener('click', function() {
    if (typeof acaoPendenteLixeira === 'function') {
        acaoPendenteLixeira();
    }
    fecharModalConfirmacaoLixeira();
});
document.getElementById('modalConfirmacaoLixeira')?.addEventListener('click', function(e) {
    if (e.target === this) {
        fecharModalConfirmacaoLixeira();
    }
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && document.getElementById('modalConfirmacaoLixeira')?.classList.contains('ativo')) {
        fecharModalConfirmacaoLixeira();
    }
});

document.getElementById('selecionarTodosLixeira')?.addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.checkbox-lixeira:not(:disabled)');
    itensSelecionadosLixeira = [];

    checkboxes.forEach(cb => {
        cb.checked = this.checked;
        if (this.checked) {
            const id = parseInt(cb.dataset.id);
            if (Number.isInteger(id)) itensSelecionadosLixeira.push(id);
        }
    });
    atualizarEstadoRecuperacaoMassa();
});

document.querySelectorAll('.checkbox-lixeira').forEach(cb => {
    cb.addEventListener('change', function() {
        const id = parseInt(this.dataset.id);
        if (!Number.isInteger(id)) return;

        if (this.checked) {
            if (!itensSelecionadosLixeira.includes(id)) itensSelecionadosLixeira.push(id);
        } else {
            itensSelecionadosLixeira = itensSelecionadosLixeira.filter(item => item !== id);
        }
        atualizarEstadoRecuperacaoMassa();
    });
});

document.getElementById('botaoRestaurarSelecionados')?.addEventListener('click', restaurarSelecionadosLixeira);
document.getElementById('campoBuscaLixeira')?.addEventListener('input', aplicarFiltrosLixeira);
document.getElementById('filtroTipoLixeira')?.addEventListener('change', aplicarFiltrosLixeira);
</script>

<?php include 'includes/footer.php'; ?>
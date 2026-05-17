<?php
/**
 * Contactos - Área Restrita IPIKK
 * Funcionalidades: Visualizar e Excluir mensagens
 */

$titulo_pagina = 'Contactos';
$css_especifico = 'admin-contactos.css';

require_once dirname(__DIR__) . '/config/index.php';

if (!isset($_SESSION['utilizador_id'])) {
    header('Location: area-restrita.php');
    exit;
}


require_once __DIR__ . '/includes/verificar-permissao.php';

// ===== VERIFICAR PERMISSÃO ESPECÍFICA =====
verificarPermissao('contactos');

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
// PROCESSAR AÇÕES
// ============================================

$feedback = '';
$feedback_tipo = 'success';

// Buscar mensagem via AJAX (para o modal)
if (isset($_GET['action']) && $_GET['action'] === 'buscar' && isset($_GET['id'])) {
    header('Content-Type: application/json');
    $id = (int)$_GET['id'];

    $stmt = $db->prepare("SELECT * FROM mensagens WHERE id = ?");
    $stmt->execute([$id]);
    $msg = $stmt->fetch();

    if ($msg) {
        // Marcar como lida automaticamente ao visualizar
        $stmt_upd = $db->prepare("UPDATE mensagens SET lida = 1 WHERE id = ?");
        $stmt_upd->execute([$id]);

        echo json_encode(['success' => true, 'mensagem' => $msg]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Mensagem não encontrada']);
    }
    exit;
}

// Processar exclusão
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = trim($_POST['acao'] ?? '');

    try {
        if ($acao === 'excluir' && isset($_POST['message_id'])) {
            $message_id = (int)$_POST['message_id'];
            $stmt = $db->prepare("DELETE FROM mensagens WHERE id = ?");
            $stmt->execute([$message_id]);
            $feedback = 'Mensagem excluída permanentemente.';
            $feedback_tipo = 'success';

        } elseif ($acao === 'excluir_multiplas' && isset($_POST['message_ids'])) {
            $ids = array_map('intval', $_POST['message_ids']);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $db->prepare("DELETE FROM mensagens WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            $feedback = count($ids) . ' mensagem(ns) excluída(s) permanentemente.';
            $feedback_tipo = 'success';

        } elseif ($acao === 'marcar_respondida' && isset($_POST['message_id'])) {
            $message_id = (int)$_POST['message_id'];
            $stmt = $db->prepare("UPDATE mensagens SET respondida = 1, lida = 1, data_resposta = COALESCE(data_resposta, NOW()), respondido_por = COALESCE(respondido_por, ?) WHERE id = ?");
            $stmt->execute([$_SESSION['utilizador_id'], $message_id]);
            $feedback = 'Mensagem marcada como respondida.';
            $feedback_tipo = 'success';

        } else {
            throw new Exception('Ação inválida.');
        }

    } catch (Exception $e) {
        $feedback = $e->getMessage();
        $feedback_tipo = 'error';
    }

    // Se for AJAX, retorna JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => $feedback_tipo === 'success', 'message' => $feedback]);
        exit;
    }
}

// ============================================
// LISTAGEM DE MENSAGENS
// ============================================

$filtro_busca = trim($_GET['busca'] ?? '');
$status = $_GET['status'] ?? 'todas';
$pagina_atual = (int)($_GET['pagina'] ?? 1);
$itens_por_pagina = 20;
$offset = ($pagina_atual - 1) * $itens_por_pagina;

$where = [];
$params = [];

if (!empty($filtro_busca)) {
    $where[] = '(nome LIKE ? OR email LIKE ? OR assunto LIKE ? OR mensagem LIKE ?)';
    $like = '%' . $filtro_busca . '%';
    array_push($params, $like, $like, $like, $like);
}

if ($status === 'nao_lidas') {
    $where[] = 'lida = 0';
} elseif ($status === 'respondidas') {
    $where[] = 'respondida = 1';
} elseif ($status === 'nao_respondidas') {
    $where[] = 'respondida = 0';
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Contar total
$count_sql = "SELECT COUNT(*) as total FROM mensagens $where_sql";
$stmt = $db->prepare($count_sql);
$stmt->execute($params);
$total_registros = $stmt->fetch()['total'];
$total_paginas = ceil($total_registros / $itens_por_pagina);

// Buscar mensagens
$sql = "
    SELECT *,
           CASE
               WHEN respondida = 1 THEN 'respondida'
               WHEN lida = 1 THEN 'lida'
               ELSE 'nao_lida'
           END as estado
    FROM mensagens
    $where_sql
    ORDER BY
        CASE WHEN lida = 0 THEN 0 ELSE 1 END,
        data_envio DESC
    LIMIT $itens_por_pagina OFFSET $offset
";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$mensagens = $stmt->fetchAll();

// Resumo estatístico
$resumo = $db->query("
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN lida = 0 THEN 1 ELSE 0 END) AS nao_lidas,
        SUM(CASE WHEN respondida = 1 THEN 1 ELSE 0 END) AS respondidas,
        SUM(CASE WHEN respondida = 0 THEN 1 ELSE 0 END) AS nao_respondidas
    FROM mensagens
")->fetch();

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<main class="conteudo-principal">
    <header class="barra-topo">
        <div class="esquerda-barra-topo">
            <button class="botao-menu-mobile" id="botaoMenuMobile">
                <i class="fas fa-bars"></i>
            </button>
            <h1 class="titulo-pagina">
                <i class="fas fa-envelope-open-text"></i> Gestão de Contactos
            </h1>
        </div>
        <div class="direita-barra-topo">
            <a href="admin-contactos.php" class="btn-secundario">
                <i class="fas fa-sync-alt"></i> Actualizar
            </a>
        </div>
    </header>

    <div class="conteudo-pagina">

        <?php if (!empty($feedback)): ?>
        <div class="alert alert-<?= $feedback_tipo === 'error' ? 'danger' : 'success' ?> alert-dismissible">
            <i class="fas fa-<?= $feedback_tipo === 'error' ? 'exclamation-circle' : 'check-circle' ?>"></i>
            <?= htmlspecialchars($feedback) ?>
            <button type="button" class="close-alert">&times;</button>
        </div>
        <?php endif; ?>

        <!-- Cards de Resumo -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-inbox"></i></div>
                <div class="stat-info">
                    <h3>Total</h3>
                    <p class="stat-number"><?= $resumo['total'] ?></p>
                </div>
            </div>
            <div class="stat-card warning">
                <div class="stat-icon"><i class="fas fa-envelope"></i></div>
                <div class="stat-info">
                    <h3>Não Lidas</h3>
                    <p class="stat-number"><?= $resumo['nao_lidas'] ?></p>
                </div>
            </div>
            <div class="stat-card info">
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                <div class="stat-info">
                    <h3>Não Respondidas</h3>
                    <p class="stat-number"><?= $resumo['nao_respondidas'] ?></p>
                </div>
            </div>
            <div class="stat-card success">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="stat-info">
                    <h3>Respondidas</h3>
                    <p class="stat-number"><?= $resumo['respondidas'] ?></p>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filtros-container">
            <div class="filtros-tabs">
                <a href="?status=todas" class="filtro-tab <?= $status === 'todas' ? 'active' : '' ?>">
                    <i class="fas fa-inbox"></i> Todas
                </a>
                <a href="?status=nao_lidas" class="filtro-tab <?= $status === 'nao_lidas' ? 'active' : '' ?>">
                    <i class="fas fa-envelope"></i> Não Lidas
                    <?php if($resumo['nao_lidas'] > 0): ?>
                    <span class="badge"><?= $resumo['nao_lidas'] ?></span>
                    <?php endif; ?>
                </a>
                <a href="?status=nao_respondidas" class="filtro-tab <?= $status === 'nao_respondidas' ? 'active' : '' ?>">
                    <i class="fas fa-reply"></i> Não Respondidas
                    <?php if($resumo['nao_respondidas'] > 0): ?>
                    <span class="badge"><?= $resumo['nao_respondidas'] ?></span>
                    <?php endif; ?>
                </a>
                <a href="?status=respondidas" class="filtro-tab <?= $status === 'respondidas' ? 'active' : '' ?>">
                    <i class="fas fa-check-circle"></i> Respondidas
                </a>
            </div>

            <div class="filtros-busca">
                <form method="GET" class="form-busca">
                    <?php if ($status !== 'todas'): ?>
                    <input type="hidden" name="status" value="<?= htmlspecialchars($status) ?>">
                    <?php endif; ?>
                    <div class="input-group">
                        <i class="fas fa-search"></i>
                        <input type="text" name="busca" value="<?= htmlspecialchars($filtro_busca) ?>" placeholder="Buscar por nome, email, assunto...">
                        <?php if (!empty($filtro_busca)): ?>
                        <a href="?status=<?= $status ?>" class="clear-search">
                            <i class="fas fa-times-circle"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                    <button type="submit" class="btn-buscar">Buscar</button>
                </form>
            </div>
        </div>

        <!-- Lista de Mensagens -->
        <div class="mensagens-lista">
            <?php if (empty($mensagens)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox fa-3x"></i>
                <h3>Nenhuma mensagem encontrada</h3>
                <p>Não há mensagens para exibir nesta categoria.</p>
            </div>
            <?php else: ?>
                <div class="acoes-em-massa">
                    <label class="checkbox-label">
                        <input type="checkbox" id="selecionarTodos"> Selecionar todos
                    </label>
                    <button type="button" class="btn-excluir-massa" id="excluirSelecionados" disabled>
                        <i class="fas fa-trash-alt"></i> Excluir selecionados
                    </button>
                </div>

                <?php foreach ($mensagens as $msg): ?>
                <div class="mensagem-card <?= $msg['estado'] ?>" data-id="<?= $msg['id'] ?>">
                    <div class="mensagem-checkbox">
                        <input type="checkbox" class="selecionar-msg" value="<?= $msg['id'] ?>">
                    </div>
                    <div class="mensagem-conteudo">
                        <div class="mensagem-header">
                            <div class="remetente-info">
                                <div class="status-indicador">
                                    <?php if ($msg['estado'] === 'nao_lida'): ?>
                                    <span class="status-badge nao-lida" title="Não lida">
                                        <i class="fas fa-circle"></i> Não lida
                                    </span>
                                    <?php elseif ($msg['estado'] === 'lida'): ?>
                                    <span class="status-badge lida" title="Lida">
                                        <i class="fas fa-envelope-open"></i> Lida
                                    </span>
                                    <?php else: ?>
                                    <span class="status-badge respondida" title="Respondida">
                                        <i class="fas fa-check-circle"></i> Respondida
                                    </span>
                                    <?php endif; ?>
                                </div>
                                <div class="remetente-detalhes">
                                    <strong><?= htmlspecialchars($msg['nome']) ?></strong>
                                    <span class="email">&lt;<?= htmlspecialchars($msg['email']) ?>&gt;</span>
                                </div>
                            </div>
                            <div class="data-hora">
                                <i class="far fa-calendar-alt"></i>
                                <?= date('d/m/Y H:i', strtotime($msg['data_envio'])) ?>
                            </div>
                        </div>

                        <div class="mensagem-assunto">
                            <strong>Assunto:</strong> <?= htmlspecialchars($msg['assunto']) ?>
                        </div>

                        <div class="mensagem-preview">
                            <?= htmlspecialchars(substr($msg['mensagem'], 0, 200)) ?>...
                        </div>

                        <div class="mensagem-acoes">
                            <button type="button" class="btn-ver" onclick="verMensagem(<?= $msg['id'] ?>)">
                                <i class="fas fa-eye"></i> Ver detalhes
                            </button>

                            <?php if ((int)$msg['respondida'] !== 1): ?>
                            <button type="button" class="btn-responder" onclick="abrirModalRespostaPorId(<?= $msg['id'] ?>)">
                                <i class="fas fa-reply"></i> Responder
                            </button>
                            <button type="button" class="btn-ver" onclick="marcarComoRespondida(<?= $msg['id'] ?>)">
                                <i class="fas fa-check"></i> Marcar respondida
                            </button>
                            <?php endif; ?>
                            <button type="button" class="btn-excluir" onclick="excluirMensagem(<?= $msg['id'] ?>)">
                                <i class="fas fa-trash-alt"></i> Excluir
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>

                <!-- Paginação -->
                <?php if ($total_paginas > 1): ?>
                <div class="paginacao">
                    <div class="paginacao-info">
                        Mostrando <strong><?= count($mensagens) ?></strong> de <strong><?= $total_registros ?></strong> mensagens
                    </div>
                    <div class="paginacao-links">
                        <?php if ($pagina_atual > 1): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['pagina' => $pagina_atual - 1])) ?>" class="pagina-link">
                            <i class="fas fa-chevron-left"></i> Anterior
                        </a>
                        <?php endif; ?>

                        <?php
                        $start = max(1, $pagina_atual - 2);
                        $end = min($total_paginas, $pagina_atual + 2);
                        for ($i = $start; $i <= $end; $i++):
                        ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['pagina' => $i])) ?>" class="pagina-link <?= $i === $pagina_atual ? 'active' : '' ?>">
                            <?= $i ?>
                        </a>
                        <?php endfor; ?>

                        <?php if ($pagina_atual < $total_paginas): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['pagina' => $pagina_atual + 1])) ?>" class="pagina-link">
                            Próximo <i class="fas fa-chevron-right"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- MODAL VISUALIZAR MENSAGEM -->
<div id="modalVisualizar" class="modal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-envelope-open-text"></i> Detalhes da Mensagem
                </h5>
                <button type="button" class="close-modal" onclick="fecharModalVisualizar()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="visualizarConteudo">
                <div class="loading-spinner">
                    <i class="fas fa-spinner fa-spin"></i> Carregando...
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-responder-modal" id="btnAbrirResposta" style="display:none;" onclick="abrirModalRespostaAtual()">
                    <i class="fas fa-reply"></i> Responder
                </button>
                <button type="button" class="btn-secondary" onclick="fecharModalVisualizar()">
                    <i class="fas fa-times"></i> Fechar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL RESPONDER MENSAGEM -->
<div id="modalResposta" class="modal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-reply"></i> Responder Mensagem
                </h5>
                <button type="button" class="close-modal" onclick="fecharModalResposta()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="formResposta" onsubmit="enviarResposta(event)">
                <div class="modal-body">
                    <input type="hidden" id="respostaMensagemId">
                    <div class="resposta-destino" id="respostaDestino"></div>
                    <label for="respostaTexto" class="resposta-label">Resposta</label>
                    <textarea id="respostaTexto" class="resposta-textarea" rows="8" placeholder="Escreva a resposta que será enviada por email..." required></textarea>
                    <small class="resposta-ajuda">O email incluirá automaticamente a mensagem original citada abaixo da sua resposta.</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="fecharModalResposta()">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn-enviar-resposta" id="btnEnviarResposta">
                        <i class="fas fa-paper-plane"></i> Enviar Resposta
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* ===== ESTILOS COMPLETOS ===== */

.conteudo-pagina {
    max-width: 1240px;
    margin: 0 auto;
    padding: 0 6px 30px;
}

/* Alertas */
.alert {
    padding: 15px 20px;
    border-radius: 12px;
    margin-bottom: 25px;
    display: flex;
    align-items: center;
    gap: 12px;
    position: relative;
}

.alert-success {
    background: #e8f5e9;
    color: #2e7d32;
    border-left: 4px solid #28a745;
}

.alert-danger {
    background: #fee2e2;
    color: #c62828;
    border-left: 4px solid #dc3545;
}

.close-alert {
    margin-left: auto;
    background: none;
    border: none;
    font-size: 20px;
    cursor: pointer;
    color: inherit;
}

/* Cards de estatísticas */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 22px;
    margin-bottom: 30px;
}

.stat-card {
    background: linear-gradient(145deg, #ffffff 0%, #f8fbff 100%);
    border: 1px solid rgba(0, 48, 114, 0.08);
    border-radius: 18px;
    padding: 24px;
    min-height: 122px;
    display: flex;
    align-items: center;
    gap: 18px;
    box-shadow: 0 12px 28px rgba(15, 23, 42, 0.07);
    overflow: hidden;
    position: relative;
    transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
}

.stat-card::before {
    content: '';
    position: absolute;
    inset: 0 auto 0 0;
    width: 5px;
    background: linear-gradient(180deg, #003072, #0a9396);
}

.stat-card:hover {
    border-color: rgba(10, 147, 150, 0.22);
    transform: translateY(-4px);
    box-shadow: 0 18px 36px rgba(15, 23, 42, 0.12);
}

.stat-icon {
    width: 62px;
    height: 62px;
    min-width: 62px;
    background: linear-gradient(135deg, #003072 0%, #0a9396 100%);
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 25px;
    color: white;
    box-shadow: 0 10px 22px rgba(0, 48, 114, 0.22);
}

.stat-card.warning::before {
    background: linear-gradient(180deg, #ffb020, #f97316);
}

.stat-card.warning .stat-icon {
    background: linear-gradient(135deg, #ffb020, #f97316);
}

.stat-card.info::before {
    background: linear-gradient(180deg, #3498db, #2563eb);
}

.stat-card.info .stat-icon {
    background: linear-gradient(135deg, #3498db, #2563eb);
}

.stat-card.success::before {
    background: linear-gradient(180deg, #28a745, #15803d);
}

.stat-card.success .stat-icon {
    background: linear-gradient(135deg, #28a745, #15803d);
}

.stat-info h3 {
    font-size: 12px;
    color: #64748b;
    letter-spacing: 0.08em;
    margin: 0 0 8px 0;
    text-transform: uppercase;
}

.stat-number {
    font-size: 32px;
    font-weight: 800;
    line-height: 1;
    margin: 0;
    color: #0f172a;
}

/* Filtros */
.filtros-container {
    background: rgba(255, 255, 255, 0.94);
    border: 1px solid rgba(0, 48, 114, 0.08);
    border-radius: 18px;
    padding: 22px;
    margin-bottom: 30px;
    box-shadow: 0 12px 28px rgba(15, 23, 42, 0.07);
}

.filtros-tabs {
    display: flex;
    gap: 5px;
    margin-bottom: 20px;
    border-bottom: 1px solid #eef2f6;
    padding-bottom: 15px;
    flex-wrap: wrap;
}

.filtro-tab {
    padding: 10px 20px;
    text-decoration: none;
    color: #666;
    border-radius: 10px;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.filtro-tab:hover {
    background: #f8f9fa;
    color: #003072;
}

.filtro-tab.active {
    background: linear-gradient(135deg, #003072, #0a9396);
    color: white;
    box-shadow: 0 10px 22px rgba(0, 48, 114, 0.18);
}

.filtro-tab .badge {
    background: rgba(0,0,0,0.1);
    padding: 2px 8px;
    border-radius: 20px;
    font-size: 11px;
}

.filtro-tab.active .badge {
    background: rgba(255,255,255,0.2);
}

.form-busca {
    display: flex;
    gap: 10px;
}

.input-group {
    flex: 1;
    position: relative;
}

.input-group i {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #999;
}

.input-group input {
    width: 100%;
    padding: 12px 40px 12px 40px;
    border: 1px solid #e0e4e8;
    border-radius: 12px;
    font-size: 14px;
    transition: all 0.3s;
}

.input-group input:focus {
    outline: none;
    border-color: #003072;
    box-shadow: 0 0 0 3px rgba(0,48,114,0.1);
}

.clear-search {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #999;
    text-decoration: none;
}

.clear-search:hover {
    color: #dc3545;
}

.btn-buscar {
    padding: 12px 25px;
    background: #003072;
    color: white;
    border: none;
    border-radius: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-buscar:hover {
    background: #001a40;
    transform: translateY(-2px);
}

/* Ações em massa */
.acoes-em-massa {
    background: linear-gradient(135deg, #f8fafc, #eef7ff);
    border: 1px solid rgba(0, 48, 114, 0.08);
    padding: 15px 20px;
    border-radius: 14px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    font-weight: 500;
}

.btn-excluir-massa {
    padding: 8px 20px;
    background: #dc3545;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-excluir-massa:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.btn-excluir-massa:not(:disabled):hover {
    background: #c82333;
    transform: translateY(-2px);
}

/* Lista de mensagens */
.mensagens-lista {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.mensagem-card {
    background: #ffffff;
    border: 1px solid rgba(0, 48, 114, 0.08);
    border-radius: 18px;
    display: flex;
    gap: 16px;
    overflow: hidden;
    padding: 20px;
    position: relative;
    transition: transform 0.22s ease, box-shadow 0.22s ease, border-color 0.22s ease;
    box-shadow: 0 10px 26px rgba(15, 23, 42, 0.06);
}

.mensagem-card::before {
    content: '';
    position: absolute;
    inset: 0 auto 0 0;
    width: 5px;
    background: #94a3b8;
}

.mensagem-card.nao_lida {
    background: linear-gradient(135deg, #fffdf7 0%, #ffffff 62%);
}

.mensagem-card.nao_lida::before {
    background: linear-gradient(180deg, #ffb020, #f97316);
}

.mensagem-card.lida::before {
    background: linear-gradient(180deg, #3498db, #2563eb);
}

.mensagem-card.respondida::before {
    background: linear-gradient(180deg, #28a745, #15803d);
}

.mensagem-card:hover {
    border-color: rgba(10, 147, 150, 0.18);
    box-shadow: 0 16px 34px rgba(15, 23, 42, 0.11);
    transform: translateY(-2px);
}

.mensagem-checkbox {
    padding-top: 5px;
}

.selecionar-msg {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.mensagem-conteudo {
    flex: 1;
}

.mensagem-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
    flex-wrap: wrap;
    gap: 10px;
}

.remetente-info {
    display: flex;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}

.status-badge.nao-lida {
    background: #fff3e0;
    color: #e65100;
}

.status-badge.lida {
    background: #e3f2fd;
    color: #1565c0;
}

.status-badge.respondida {
    background: #e8f5e9;
    color: #2e7d32;
}

.remetente-detalhes strong {
    font-size: 16px;
    color: #2c3e50;
}

.remetente-detalhes .email {
    font-size: 13px;
    color: #666;
    margin-left: 8px;
}

.data-hora {
    font-size: 12px;
    color: #999;
}

.mensagem-assunto {
    margin-bottom: 8px;
    font-size: 14px;
}

.mensagem-preview {
    color: #666;
    font-size: 13px;
    margin-bottom: 12px;
    line-height: 1.5;
}

.mensagem-acoes {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.mensagem-acoes button {
    padding: 6px 16px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 500;
    transition: all 0.2s;
}

.btn-ver {
    background: #e3f2fd;
    color: #1565c0;
}

.btn-ver:hover {
    background: #1565c0;
    color: white;
}

.btn-responder,
.btn-responder-modal,
.btn-enviar-resposta {
    background: #e8f5e9;
    color: #1e7e34;
}

.btn-responder:hover,
.btn-responder-modal:hover,
.btn-enviar-resposta:hover {
    background: #28a745;
    color: white;
}

.btn-responder-modal,
.btn-enviar-resposta {
    border: none;
    border-radius: 10px;
    cursor: pointer;
    font-weight: 600;
    padding: 10px 20px;
    transition: all 0.2s;
}

.btn-enviar-resposta:disabled {
    opacity: 0.65;
    cursor: not-allowed;
}

.btn-excluir {
    background: #fee2e2;
    color: #c62828;
}

.btn-excluir:hover {
    background: #c62828;
    color: white;
}

/* Empty state */
.empty-state {
    text-align: center;
    padding: 60px;
    background: #f8f9fa;
    border-radius: 16px;
}

.empty-state i {
    color: #ccc;
    margin-bottom: 15px;
}

.empty-state h3 {
    color: #666;
    margin-bottom: 10px;
}

.empty-state p {
    color: #999;
}

/* Paginação */
.paginacao {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 25px;
    padding-top: 20px;
    border-top: 1px solid #eef2f6;
    flex-wrap: wrap;
    gap: 15px;
}

.paginacao-info {
    font-size: 13px;
    color: #666;
}

.paginacao-links {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.pagina-link {
    padding: 8px 14px;
    background: #f0f0f0;
    border-radius: 8px;
    text-decoration: none;
    color: #555;
    font-size: 13px;
    transition: all 0.2s;
}

.pagina-link:hover {
    background: #003072;
    color: white;
}

.pagina-link.active {
    background: #003072;
    color: white;
}

/* Modal */
.modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.7);
    backdrop-filter: blur(4px);
    z-index: 10000;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.modal.active {
    display: flex;
}

.modal-dialog {
    width: 100%;
    max-width: 800px;
}

.modal-lg {
    max-width: 800px;
}

.modal-content {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    animation: modalSlideIn 0.3s ease;
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: translateY(-50px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 25px;
    background: #f8f9fa;
    border-bottom: 1px solid #eef2f6;
}

.modal-title {
    margin: 0;
    font-size: 20px;
    font-weight: 600;
    color: #003072;
}

.close-modal {
    background: none;
    border: none;
    font-size: 20px;
    cursor: pointer;
    color: #999;
    transition: color 0.2s;
}

.close-modal:hover {
    color: #dc3545;
}

.modal-body {
    padding: 25px;
    max-height: 60vh;
    overflow-y: auto;
}

.modal-footer {
    padding: 15px 25px;
    background: #f8f9fa;
    border-top: 1px solid #eef2f6;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}

.loading-spinner {
    text-align: center;
    padding: 40px;
    color: #999;
}

.btn-secondary {
    padding: 10px 20px;
    background: #f0f0f0;
    color: #666;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.2s;
}

.btn-secondary:hover {
    background: #e0e0e0;
}

/* Visualização da mensagem no modal */
.visualizar-mensagem .msg-header {
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #eef2f6;
}

.visualizar-mensagem .msg-header h3 {
    color: #003072;
    margin: 0 0 10px 0;
}

.visualizar-mensagem .msg-meta {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 12px;
    margin-bottom: 20px;
}

.visualizar-mensagem .msg-meta div {
    margin-bottom: 8px;
}

.visualizar-mensagem .msg-meta div:last-child {
    margin-bottom: 0;
}

.visualizar-mensagem .msg-meta i {
    width: 25px;
    color: #003072;
}

.visualizar-mensagem .msg-conteudo {
    margin-bottom: 20px;
}

.visualizar-mensagem .msg-conteudo h4 {
    color: #2c3e50;
    margin-bottom: 10px;
}

.visualizar-mensagem .conteudo-texto {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 12px;
    line-height: 1.6;
    white-space: pre-wrap;
    word-wrap: break-word;
}

.resposta-destino {
    background: #f8f9fa;
    border-left: 4px solid #0a9396;
    border-radius: 12px;
    color: #2c3e50;
    line-height: 1.6;
    margin-bottom: 18px;
    padding: 14px 16px;
}

.resposta-label {
    color: #003072;
    display: block;
    font-weight: 600;
    margin-bottom: 8px;
}

.resposta-textarea {
    border: 1px solid #d9e2ec;
    border-radius: 12px;
    font-family: inherit;
    font-size: 14px;
    line-height: 1.6;
    outline: none;
    padding: 14px 16px;
    resize: vertical;
    transition: border-color 0.2s, box-shadow 0.2s;
    width: 100%;
}

.resposta-textarea:focus {
    border-color: #0a9396;
    box-shadow: 0 0 0 3px rgba(10,147,150,0.12);
}

.resposta-ajuda {
    color: #64748b;
    display: block;
    margin-top: 8px;
}

/* Responsividade */
@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }

    .filtros-tabs {
        flex-wrap: wrap;
    }

    .filtro-tab {
        flex: 1;
        justify-content: center;
    }

    .form-busca {
        flex-direction: column;
    }

    .mensagem-header {
        flex-direction: column;
        align-items: flex-start;
    }

    .remetente-info {
        flex-direction: column;
        align-items: flex-start;
    }

    .mensagem-acoes {
        flex-direction: column;
    }

    .mensagem-acoes button {
        width: 100%;
    }

    .paginacao {
        flex-direction: column;
        align-items: center;
    }

    .modal-dialog {
        margin: 10px;
    }

    .modal-header, .modal-footer {
        padding: 15px;
    }

    .modal-body {
        padding: 15px;
    }
}

@media (max-width: 480px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }

    .acoes-em-massa {
        flex-direction: column;
        align-items: stretch;
    }

    .btn-excluir-massa {
        width: 100%;
    }
}
</style>

<script>
let itensSelecionados = [];
let mensagemAtual = null;

function confirmarAcao(titulo, texto, callbackConfirmar, tipoAcao = 'eliminar') {
    if (typeof window.abrirModalConfirmacao === 'function') {
        window.abrirModalConfirmacao(titulo, texto, callbackConfirmar, tipoAcao);
        return;
    }

    const overlay = document.createElement('div');
    overlay.className = 'modal active';
    overlay.innerHTML = `
        <div class="modal-dialog" style="max-width:450px;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-triangle-exclamation"></i> ${escapeHtml(titulo)}</h5>
                    <button type="button" class="close-modal" data-confirm-cancel><i class="fas fa-times"></i></button>
                </div>
                <div class="modal-body"><p style="margin:0;line-height:1.7;color:#475569;">${escapeHtml(texto)}</p></div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" data-confirm-cancel>Cancelar</button>
                    <button type="button" class="btn-excluir" data-confirm-ok>Eliminar</button>
                </div>
            </div>
        </div>
    `;
    const fechar = () => overlay.remove();
    overlay.addEventListener('click', (event) => {
        if (event.target === overlay || event.target.closest('[data-confirm-cancel]')) fechar();
        if (event.target.closest('[data-confirm-ok]')) {
            fechar();
            callbackConfirmar();
        }
    });
    document.body.appendChild(overlay);
}

// Fechar alertas
document.querySelectorAll('.close-alert').forEach(btn => {
    btn.addEventListener('click', function() {
        this.parentElement.remove();
    });
});

// Ver mensagem no modal
function verMensagem(id) {
    const modal = document.getElementById('modalVisualizar');
    const conteudo = document.getElementById('visualizarConteudo');

    conteudo.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Carregando...</div>';
    modal.classList.add('active');

    fetch(`admin-contactos.php?action=buscar&id=${id}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const m = data.mensagem;
                mensagemAtual = m;
                const btnAbrirResposta = document.getElementById('btnAbrirResposta');
                if (btnAbrirResposta) btnAbrirResposta.style.display = Number(m.respondida) === 1 ? 'none' : 'inline-flex';
                const estadoTexto = m.respondida ? 'Respondida' : (m.lida ? 'Lida' : 'Não lida');

                conteudo.innerHTML = `
                    <div class="visualizar-mensagem">
                        <div class="msg-header">
                            <h3>${escapeHtml(m.assunto)}</h3>
                        </div>
                        <div class="msg-meta">
                            <div><i class="fas fa-user"></i> <strong>De:</strong> ${escapeHtml(m.nome)} &lt;${escapeHtml(m.email)}&gt;</div>
                            <div><i class="fas fa-calendar"></i> <strong>Data:</strong> ${new Date(m.data_envio).toLocaleString('pt-PT')}</div>
                            <div><i class="fas ${m.respondida ? 'fa-check-circle' : (m.lida ? 'fa-envelope-open' : 'fa-circle')}"></i> <strong>Status:</strong> ${estadoTexto}</div>
                        </div>
                        <div class="msg-conteudo">
                            <h4>Mensagem:</h4>
                            <div class="conteudo-texto">${escapeHtml(m.mensagem).replace(/\n/g, '<br>')}</div>
                        </div>
                    </div>
                `;
            } else {
                conteudo.innerHTML = `<div class="loading-spinner">Erro ao carregar mensagem.</div>`;
            }
        })
        .catch(() => {
            conteudo.innerHTML = `<div class="loading-spinner">Erro ao carregar mensagem.</div>`;
        });
}

function fecharModalVisualizar() {
    document.getElementById('modalVisualizar').classList.remove('active');
}

function preencherModalResposta(mensagem) {
    if (!mensagem) return;
    document.getElementById('respostaMensagemId').value = mensagem.id;
    document.getElementById('respostaTexto').value = '';
    document.getElementById('respostaDestino').innerHTML = `
        <strong>Para:</strong> ${escapeHtml(mensagem.nome)} &lt;${escapeHtml(mensagem.email)}&gt;<br>
        <strong>Assunto:</strong> RE: ${escapeHtml(mensagem.assunto || 'Mensagem de Contacto')}
    `;
    document.getElementById('modalResposta').classList.add('active');
    setTimeout(() => document.getElementById('respostaTexto')?.focus(), 80);
}

function abrirModalRespostaAtual() {
    preencherModalResposta(mensagemAtual);
}

function abrirModalRespostaPorId(id) {
    fetch(`admin-contactos.php?action=buscar&id=${id}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                mensagemAtual = data.mensagem;
                preencherModalResposta(data.mensagem);
            } else {
                mostrarNotificacao(data.message || 'Erro ao carregar mensagem.', 'error');
            }
        })
        .catch(() => mostrarNotificacao('Erro ao carregar mensagem.', 'error'));
}

function fecharModalResposta() {
    document.getElementById('modalResposta').classList.remove('active');
}

function enviarResposta(event) {
    event.preventDefault();
    const btn = document.getElementById('btnEnviarResposta');
    const formData = new URLSearchParams();
    formData.append('message_id', document.getElementById('respostaMensagemId').value);
    formData.append('resposta', document.getElementById('respostaTexto').value.trim());

    btn.disabled = true;
    const textoOriginal = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';

    fetch('processos/processar-resposta.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        mostrarNotificacao(data.message, data.success ? 'success' : 'error');
        if (data.success) {
            fecharModalResposta();
            fecharModalVisualizar();
            setTimeout(() => location.reload(), 1200);
        }
    })
    .catch(() => mostrarNotificacao('Erro ao enviar resposta.', 'error'))
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = textoOriginal;
    });
}

function marcarComoRespondida(id) {
    const formData = new URLSearchParams();
    formData.append('acao', 'marcar_respondida');
    formData.append('message_id', id);
    fetch(window.location.href, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    }).then(() => {
        mostrarNotificacao('Mensagem marcada como respondida.', 'success');
        setTimeout(() => location.reload(), 900);
    });
}

// Excluir mensagem individual
function excluirMensagem(id) {
    confirmarAcao(
        'Confirmar eliminação',
        'Excluir esta mensagem permanentemente? Esta ação não pode ser desfeita.',
        () => {
            const formData = new URLSearchParams();
            formData.append('acao', 'excluir');
            formData.append('message_id', id);

            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                mostrarNotificacao(data.message, data.success ? 'success' : 'error');
                if (data.success) {
                    setTimeout(() => location.reload(), 1500);
                }
            });
        },
        'eliminar'
    );
}

// Excluir múltiplas mensagens
function excluirMultiplas(ids) {
    const formData = new URLSearchParams();
    formData.append('acao', 'excluir_multiplas');
    ids.forEach(id => formData.append('message_ids[]', id));

    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        mostrarNotificacao(data.message, data.success ? 'success' : 'error');
        if (data.success) {
            setTimeout(() => location.reload(), 1500);
        }
    });
}

// Mostrar notificação
function mostrarNotificacao(mensagem, tipo) {
    const alert = document.createElement('div');
    alert.className = `alert alert-${tipo === 'success' ? 'success' : 'danger'}`;
    alert.innerHTML = `
        <i class="fas fa-${tipo === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        ${mensagem}
        <button type="button" class="close-alert">&times;</button>
    `;

    const container = document.querySelector('.conteudo-pagina');
    container.insertBefore(alert, container.firstChild);

    alert.querySelector('.close-alert').addEventListener('click', () => alert.remove());
    setTimeout(() => alert.remove(), 5000);
}

// Seleção em massa
const selectAllCheckbox = document.getElementById('selecionarTodos');
const excluirSelecionadosBtn = document.getElementById('excluirSelecionados');

function atualizarSelecao() {
    const checkboxes = document.querySelectorAll('.selecionar-msg');
    itensSelecionados = [];
    checkboxes.forEach(cb => {
        if (cb.checked) {
            itensSelecionados.push(parseInt(cb.value));
        }
    });

    if (excluirSelecionadosBtn) {
        excluirSelecionadosBtn.disabled = itensSelecionados.length === 0;
    }

    if (selectAllCheckbox && checkboxes.length > 0) {
        const todosChecked = Array.from(checkboxes).every(cb => cb.checked);
        selectAllCheckbox.checked = todosChecked;
    }
}

if (selectAllCheckbox) {
    selectAllCheckbox.addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.selecionar-msg');
        checkboxes.forEach(cb => {
            cb.checked = this.checked;
        });
        atualizarSelecao();
    });
}

document.querySelectorAll('.selecionar-msg').forEach(cb => {
    cb.addEventListener('change', atualizarSelecao);
});

if (excluirSelecionadosBtn) {
    excluirSelecionadosBtn.addEventListener('click', () => {
        if (itensSelecionados.length === 0) return;
        confirmarAcao(
            'Confirmar eliminação em massa',
            `Excluir ${itensSelecionados.length} mensagem(ns) permanentemente? Esta ação não pode ser desfeita.`,
            () => excluirMultiplas(itensSelecionados),
            'eliminar'
        );
    });
}

// Fechar modal clicando fora
document.getElementById('modalVisualizar')?.addEventListener('click', (e) => {
    if (e.target === document.getElementById('modalVisualizar')) fecharModalVisualizar();
});

document.getElementById('modalResposta')?.addEventListener('click', (e) => {
    if (e.target === document.getElementById('modalResposta')) fecharModalResposta();
});

// Escape key fecha modal
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        fecharModalVisualizar();
        fecharModalResposta();
    }
});

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Inicializar seleção
atualizarSelecao();
</script>

<?php include 'includes/footer.php'; ?>

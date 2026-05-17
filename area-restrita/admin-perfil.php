<?php
/**
 * Meu Perfil - Área Restrita IPIKK
 */

$titulo_pagina = 'Meu Perfil';
$css_especifico = 'admin-perfil.css';

$base_path = dirname(__DIR__);
require_once $base_path . '/config/index.php';

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

$stmt = $db->prepare("SELECT * FROM utilizadores WHERE id = ?");
$stmt->execute([$_SESSION['utilizador_id']]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    session_destroy();
    header('Location: area-restrita.php');
    exit;
}

$config = $db->query("SELECT * FROM configuracoes WHERE id = 1")->fetch();

// Estatísticas
$stmt = $db->prepare("SELECT COUNT(*) FROM noticias WHERE autor = ?");
$stmt->execute([$usuario['nome']]);
$stats['noticias'] = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM mensagens WHERE respondido_por = ?");
$stmt->execute([$_SESSION['utilizador_id']]);
$stats['mensagens'] = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM logs WHERE utilizador_id = ? AND (acao = 'login' OR acao = 'logout')");
$stmt->execute([$_SESSION['utilizador_id']]);
$stats['logins'] = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT data_hora, ip_address, user_agent FROM logs WHERE utilizador_id = ? AND acao = 'login' ORDER BY data_hora DESC LIMIT 5");
$stmt->execute([$_SESSION['utilizador_id']]);
$ultimos_acessos = $stmt->fetchAll();

$data_senha = new DateTime($usuario['updated_at'] ?? $usuario['created_at']);
$agora = new DateTime();
$dias_senha = $data_senha->diff($agora)->days;
$nivel_acesso = $usuario['nivel'] === 'admin' ? 'Administrador' : 'Editor';
$icone_nivel = $usuario['nivel'] === 'admin' ? 'fa-crown' : 'fa-edit';

// Processamento POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'atualizar_perfil') {
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telefone = trim($_POST['telefone'] ?? '');
        $departamento = trim($_POST['departamento'] ?? '');
        $cargo = trim($_POST['cargo'] ?? '');
        $biografia = trim($_POST['biografia'] ?? '');
        
        $notificacoes_contacto = isset($_POST['notificacoes_contacto']) ? 1 : 0;
        $notificacoes_sistema = isset($_POST['notificacoes_sistema']) ? 1 : 0;
        $notificacoes_relatorios = isset($_POST['notificacoes_relatorios']) ? 1 : 0;
        $notificacoes_mensagens = isset($_POST['notificacoes_mensagens']) ? 1 : 0;
        $notificacoes_comentarios = isset($_POST['notificacoes_comentarios']) ? 1 : 0;
        
        if (empty($nome) || empty($email)) {
            echo json_encode(['success' => false, 'message' => 'Nome e email são obrigatórios.']);
            exit;
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Email inválido.']);
            exit;
        }
        
        $stmt = $db->prepare("SELECT id FROM utilizadores WHERE email = ? AND id != ?");
        $stmt->execute([$email, $_SESSION['utilizador_id']]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Este email já está em uso.']);
            exit;
        }
        
        $stmt = $db->prepare("UPDATE utilizadores SET 
            nome = ?, email = ?, telefone = ?, departamento = ?, cargo = ?, 
            biografia = ?, notificacoes_contacto = ?, notificacoes_sistema = ?,
            notificacoes_relatorios = ?, notificacoes_mensagens = ?, notificacoes_comentarios = ? 
            WHERE id = ?");
        
        $success = $stmt->execute([
            $nome, $email, $telefone, $departamento, $cargo, $biografia,
            $notificacoes_contacto, $notificacoes_sistema, $notificacoes_relatorios,
            $notificacoes_mensagens, $notificacoes_comentarios, $_SESSION['utilizador_id']
        ]);
        
        if ($success) {
            $_SESSION['utilizador_nome'] = $nome;
            $_SESSION['utilizador_email'] = $email;
            registrarLog('editou_perfil', 'utilizadores', $_SESSION['utilizador_id'], 'Atualizou o próprio perfil');
            echo json_encode(['success' => true, 'message' => 'Perfil atualizado com sucesso!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao atualizar perfil.']);
        }
        exit;
    }
    
    if ($action === 'alterar_senha') {
        $senha_atual = $_POST['senha_atual'] ?? '';
        $nova_senha = $_POST['nova_senha'] ?? '';
        $confirmar_senha = $_POST['confirmar_senha'] ?? '';
        $encerrar_sessoes = isset($_POST['encerrar_sessoes']) ? 1 : 0;
        
        if (empty($senha_atual) || empty($nova_senha) || empty($confirmar_senha)) {
            echo json_encode(['success' => false, 'message' => 'Preencha todos os campos.']);
            exit;
        }
        
        if (!password_verify($senha_atual, $usuario['senha'])) {
            echo json_encode(['success' => false, 'message' => 'Senha atual incorreta.']);
            exit;
        }
        
        if ($nova_senha !== $confirmar_senha) {
            echo json_encode(['success' => false, 'message' => 'As senhas não coincidem.']);
            exit;
        }
        
        if (strlen($nova_senha) < 6) {
            echo json_encode(['success' => false, 'message' => 'A nova senha deve ter pelo menos 6 caracteres.']);
            exit;
        }
        
        $nova_senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE utilizadores SET senha = ?, updated_at = NOW() WHERE id = ?");
        $success = $stmt->execute([$nova_senha_hash, $_SESSION['utilizador_id']]);
        
        if ($success) {
            if ($encerrar_sessoes) {
                $stmt = $db->prepare("DELETE FROM sessoes WHERE utilizador_id = ?");
                $stmt->execute([$_SESSION['utilizador_id']]);
            }
            registrarLog('alterou_senha', 'utilizadores', $_SESSION['utilizador_id'], 'Alterou a própria senha');
            echo json_encode(['success' => true, 'message' => 'Senha alterada com sucesso!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao alterar senha.']);
        }
        exit;
    }
    
    if ($action === 'upload_foto') {
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (!in_array($ext, $allowed)) {
                echo json_encode(['success' => false, 'message' => 'Formato não permitido.']);
                exit;
            }
            
            if ($_FILES['foto']['size'] > 5 * 1024 * 1024) {
                echo json_encode(['success' => false, 'message' => 'Arquivo muito grande. Máximo 5MB.']);
                exit;
            }
            
            $upload = uploadArquivoNuvem($_FILES['foto'], 'perfis');
            if ($upload['success']) {
                $stmt = $db->prepare("UPDATE utilizadores SET foto_url = ? WHERE id = ?");
                $stmt->execute([$upload['url'], $_SESSION['utilizador_id']]);
                registrarLog('upload_foto', 'utilizadores', $_SESSION['utilizador_id'], 'Atualizou a foto de perfil');
                echo json_encode(['success' => true, 'foto_url' => $upload['url']]);
                exit;
            }
        }
        echo json_encode(['success' => false, 'message' => 'Erro ao fazer upload.']);
        exit;
    }
    
    if ($action === 'remover_foto') {
        if ($usuario['foto_url'] && $usuario['foto_url'] != 'foto/sem_foto.png') {
            $foto_path = dirname(__DIR__) . '/area-publica/' . $usuario['foto_url'];
            if (file_exists($foto_path)) unlink($foto_path);
        }
        $stmt = $db->prepare("UPDATE utilizadores SET foto_url = 'foto/sem_foto.png' WHERE id = ?");
        $success = $stmt->execute([$_SESSION['utilizador_id']]);
        echo json_encode(['success' => $success, 'message' => $success ? 'Foto removida!' : 'Erro ao remover.']);
        exit;
    }
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<!-- HTML e JavaScript do admin-perfil.php (igual ao que você já tem) -->
<main class="conteudo-principal">
    <header class="barra-superior">
        <div class="esquerda-barra">
            <button class="botao-menu-mobile" id="botaoMenuMobile">
                <i class="fas fa-bars"></i>
            </button>
            <h1 class="titulo-pagina">
                <i class="fas fa-user-circle"></i> Meu Perfil
            </h1>
        </div>
    </header>

    <div class="conteudo-pagina">
        <div class="perfil-header">
            <div class="perfil-foto">
                <div class="foto-container" id="fotoContainer">
                    <?php if (!empty($usuario['foto_url']) && $usuario['foto_url'] != 'foto/sem_foto.png'): ?>
                        <img src="<?= htmlspecialchars(normalizarUrlMidia($usuario['foto_url'], '..')) ?>" alt="Foto de Perfil" id="fotoPerfil">
                    <?php else: ?>
                        <div class="avatar-padrao" id="avatarPadrao">
                            <i class="fas <?= $usuario['avatar_icone'] ?? ($usuario['nivel'] === 'admin' ? 'fa-crown' : 'fa-user') ?>"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="foto-acoes">
                    <button class="btn-icone" onclick="document.getElementById('uploadFoto').click()" title="Alterar foto">
                        <i class="fas fa-camera"></i>
                    </button>
                    <button class="btn-icone btn-remover-foto" onclick="removerFoto()" title="Remover foto">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
                <input type="file" id="uploadFoto" accept="image/*" style="display: none;">
            </div>
            
            <div class="perfil-info">
                <h2>
                    <?= htmlspecialchars($usuario['nome']) ?>
                    <span class="badge-ativo <?= $usuario['ativo'] ? '' : 'badge-inativo' ?>">
                        <i class="fas <?= $usuario['ativo'] ? 'fa-check-circle' : 'fa-pause-circle' ?>"></i>
                        <?= $usuario['ativo'] ? 'Ativo' : 'Inativo' ?>
                    </span>
                </h2>
                <p class="email"><i class="fas fa-envelope"></i> <?= htmlspecialchars($usuario['email']) ?></p>
                <div class="nivel"><i class="fas <?= $icone_nivel ?>"></i> <?= $nivel_acesso ?></div>
                <div class="perfil-stats">
                    <div class="perfil-stat"><span class="stat-number"><?= number_format($stats['noticias'], 0, ',', '.') ?></span><span class="stat-label">Notícias</span></div>
                    <div class="perfil-stat"><span class="stat-number"><?= number_format($stats['mensagens'], 0, ',', '.') ?></span><span class="stat-label">Respostas</span></div>
                    <div class="perfil-stat"><span class="stat-number"><?= number_format($stats['logins'], 0, ',', '.') ?></span><span class="stat-label">Acessos</span></div>
                </div>
            </div>
        </div>

        <div class="secao-conteudo">
            <h2 class="secao-titulo"><i class="fas fa-id-card"></i> Informações Pessoais</h2>
            <div class="linha-form">
                <div class="grupo-form"><label><i class="fas fa-user"></i> Nome Completo *</label><input type="text" id="campoNome" class="campo-form" value="<?= htmlspecialchars($usuario['nome']) ?>"></div>
                <div class="grupo-form"><label><i class="fas fa-envelope"></i> Email *</label><input type="email" id="campoEmail" class="campo-form" value="<?= htmlspecialchars($usuario['email']) ?>"></div>
            </div>
            <div class="linha-form">
                <div class="grupo-form"><label><i class="fas fa-phone"></i> Telefone</label><input type="tel" id="campoTelefone" class="campo-form" value="<?= htmlspecialchars($usuario['telefone'] ?? '') ?>"></div>
                <div class="grupo-form"><label><i class="fas fa-building"></i> Departamento</label><input type="text" id="campoDepartamento" class="campo-form" value="<?= htmlspecialchars($usuario['departamento'] ?? '') ?>"></div>
            </div>
            <div class="linha-form largura-total">
                <div class="grupo-form"><label><i class="fas fa-briefcase"></i> Cargo / Função</label><input type="text" id="campoCargo" class="campo-form" value="<?= htmlspecialchars($usuario['cargo'] ?? '') ?>"></div>
            </div>
            <div class="linha-form largura-total">
                <div class="grupo-form"><label><i class="fas fa-pen"></i> Biografia</label><textarea id="campoBiografia" class="campo-form area-texto" rows="3" maxlength="500"><?= htmlspecialchars($usuario['biografia'] ?? '') ?></textarea><div class="contador-caracteres">Max. 500 | <span id="contadorBio"><?= strlen($usuario['biografia'] ?? '') ?></span></div></div>
            </div>
        </div>

        <div class="secao-conteudo">
            <h2 class="secao-titulo"><i class="fas fa-shield-alt"></i> Segurança</h2>
            <div class="info-seguranca">
                <div class="info-item"><span class="info-label"><i class="fas fa-clock"></i> Última alteração:</span><span class="info-value"><?= date('d/m/Y', strtotime($usuario['updated_at'] ?? $usuario['created_at'])) ?> (há <?= $dias_senha ?> dias)</span></div>
                <div class="info-item"><span class="info-label"><i class="fas fa-check-circle"></i> Estado:</span><span class="info-value <?= $usuario['ativo'] ? 'ativo' : 'inativo' ?>"><?= $usuario['ativo'] ? 'Ativa' : 'Inativa' ?></span></div>
                <div class="info-item"><span class="info-label"><i class="fas fa-calendar"></i> Criada em:</span><span class="info-value"><?= date('d/m/Y H:i', strtotime($usuario['created_at'])) ?></span></div>
                <div class="info-item"><span class="info-label"><i class="fas fa-sign-in-alt"></i> Último login:</span><span class="info-value"><?= $usuario['ultimo_login'] ? date('d/m/Y H:i', strtotime($usuario['ultimo_login'])) : 'Nunca' ?></span></div>
            </div>
            <button class="btn-primario" onclick="abrirModalSenha()"><i class="fas fa-key"></i> Alterar Senha</button>
        </div>

        <div class="secao-conteudo">
            <h2 class="secao-titulo"><i class="fas fa-chart-pie"></i> Minhas Estatísticas</h2>
            <div class="stats-pessoais">
                <div class="stat-card"><i class="fas fa-newspaper"></i><div><h3><?= number_format($stats['noticias'], 0, ',', '.') ?></h3><p>Notícias</p></div></div>
                <div class="stat-card"><i class="fas fa-reply-all"></i><div><h3><?= number_format($stats['mensagens'], 0, ',', '.') ?></h3><p>Respostas</p></div></div>
                <div class="stat-card"><i class="fas fa-sign-in-alt"></i><div><h3><?= number_format($stats['logins'], 0, ',', '.') ?></h3><p>Acessos</p></div></div>
            </div>
        </div>

        <div class="secao-acoes">
            <button class="btn-cancelar" onclick="descartarAlteracoes()"><i class="fas fa-undo-alt"></i> Descartar</button>
            <button class="btn-salvar" onclick="salvarPerfil()"><i class="fas fa-save"></i> Guardar</button>
        </div>
    </div>
</main>

<div id="modalSenha" class="modal">
    <div class="modal-conteudo">
        <div class="modal-cabecalho"><h2><i class="fas fa-key"></i> Alterar Senha</h2><button class="modal-fechar" onclick="fecharModalSenha()"><i class="fas fa-times"></i></button></div>
        <div class="modal-corpo">
            <form id="formSenha" onsubmit="return false;">
                <div class="grupo-form"><label><i class="fas fa-lock"></i> Senha Atual *</label><input type="password" id="senhaAtual" class="campo-form" required></div>
                <div class="grupo-form"><label><i class="fas fa-key"></i> Nova Senha *</label><input type="password" id="novaSenha" class="campo-form" required oninput="verificarForcaSenha()"><div id="forcaSenha" class="forca-senha" style="display:none;"><div class="barra-forca"><div class="preenchimento-forca" id="preenchimentoForca"></div></div><div class="rotulo-forca" id="rotuloForca"></div></div></div>
                <div class="grupo-form"><label><i class="fas fa-check-circle"></i> Confirmar *</label><input type="password" id="confirmarSenha" class="campo-form" required></div>
                <div class="requisitos-senha"><h4>Requisitos:</h4><div class="requisito" id="reqComprimento"><i class="fas fa-circle"></i> Mínimo 6 caracteres</div><div class="requisito" id="reqMaiuscula"><i class="fas fa-circle"></i> Letra maiúscula</div><div class="requisito" id="reqMinuscula"><i class="fas fa-circle"></i> Letra minúscula</div><div class="requisito" id="reqNumero"><i class="fas fa-circle"></i> Número</div><div class="requisito" id="reqEspecial"><i class="fas fa-circle"></i> Caractere especial</div></div>
                <div class="linha-form"><div class="grupo-form"><label class="checkbox-label"><input type="checkbox" id="encerrarSessoes" checked><span>Encerrar todas as outras sessões</span></label></div></div>
                <div class="modal-acoes"><button type="button" class="btn-cancelar" onclick="fecharModalSenha()"><i class="fas fa-times"></i> Cancelar</button><button type="button" class="btn-salvar" onclick="alterarSenha()"><i class="fas fa-save"></i> Alterar</button></div>
            </form>
        </div>
    </div>
</div>
<style>
/* ===== VARIÁVEIS GLOBAIS ===== */
:root {
    --primary: #0a9396;
    --primary-dark: #0b7b7e;
    --primary-light: #94d2bd;
    --secondary: #003072;
    --secondary-dark: #002255;
    --success: #28a745;
    --danger: #dc3545;
    --warning: #ffc107;
    --info: #17a2b8;
    --dark: #2c3e50;
    --gray: #6c757d;
    --light-gray: #e9ecef;
    --white: #ffffff;
    --shadow-sm: 0 2px 4px rgba(0,0,0,0.05);
    --shadow-md: 0 4px 6px rgba(0,0,0,0.07);
    --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.1);
    --shadow-xl: 0 20px 25px -5px rgba(0,0,0,0.1);
    --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* ===== ANIMAÇÕES ===== */
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

@keyframes fadeInUp {
    from {
        transform: translateY(20px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

@keyframes pulse {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
}

@keyframes shimmer {
    0% {
        background-position: -1000px 0;
    }
    100% {
        background-position: 1000px 0;
    }
}

/* ===== SEÇÕES PRINCIPAIS ===== */
.secao-conteudo {
    background: var(--white);
    border-radius: 24px;
    padding: 30px;
    margin-bottom: 30px;
    box-shadow: var(--shadow-md);
    transition: var(--transition);
    animation: fadeInUp 0.5s ease-out;
    border: 1px solid rgba(0,0,0,0.03);
}

.secao-conteudo:hover {
    box-shadow: var(--shadow-lg);
    transform: translateY(-2px);
}

.secao-titulo {
    font-size: 1.35rem;
    font-weight: 700;
    margin-bottom: 25px;
    display: flex;
    align-items: center;
    gap: 12px;
    padding-bottom: 15px;
    border-bottom: 2px solid var(--light-gray);
    color: var(--secondary);
    position: relative;
}

.secao-titulo i {
    color: var(--primary);
    font-size: 1.5rem;
}

.secao-titulo::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    width: 50px;
    height: 2px;
    background: linear-gradient(90deg, var(--primary), var(--primary-light));
    border-radius: 2px;
}

/* ===== PERFIL HEADER ===== */
.perfil-header {
    display: flex;
    align-items: center;
    gap: 40px;
    flex-wrap: wrap;
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border-radius: 24px;
    padding: 30px;
    position: relative;
    overflow: hidden;
}

.perfil-header::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 200px;
    height: 200px;
    background: radial-gradient(circle, rgba(10,147,150,0.05) 0%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
}

.perfil-foto {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
}

.foto-container {
    position: relative;
    width: 130px;
    height: 130px;
    border-radius: 50%;
    overflow: hidden;
    background: linear-gradient(135deg, #e0e4e8, #f0f2f5);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
}

.foto-container:hover {
    transform: scale(1.02);
}

.foto-container img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.avatar-padrao {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 52px;
    background: linear-gradient(135deg, #0a9396, #003072);
    color: white;
}

.foto-acoes {
    display: flex;
    gap: 8px;
    margin-top: 5px;
}

.btn-icone {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: white;
    border: 1px solid #e0e4e8;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6c757d;
}

.btn-icone:hover {
    background: #0a9396;
    border-color: #0a9396;
    color: white;
    transform: translateY(-2px);
}

.btn-remover-foto:hover {
    background: #dc3545;
    border-color: #dc3545;
}

.perfil-info {
    flex: 1;
}

.perfil-info h2 {
    font-size: 28px;
    font-weight: 700;
    color: #003072;
    margin: 0 0 8px 0;
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.badge-ativo {
    display: inline-block;
    padding: 4px 12px;
    background: #e8f5e9;
    color: #2e7d32;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.badge-inativo {
    background: #fee2e2;
    color: #c62828;
}

.perfil-info .email {
    color: #6c757d;
    margin-bottom: 12px;
    font-size: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.perfil-info .email i {
    color: #0a9396;
    width: 20px;
}

.perfil-info .nivel {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 16px;
    background: linear-gradient(135deg, #0a9396, #003072);
    color: white;
    border-radius: 30px;
    font-size: 13px;
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(10,147,150,0.3);
}

.perfil-info .nivel i {
    font-size: 14px;
}

.perfil-stats {
    display: flex;
    gap: 30px;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #eef2f6;
}

.perfil-stat {
    text-align: center;
}

.perfil-stat .stat-number {
    font-size: 22px;
    font-weight: 700;
    color: #003072;
    display: block;
}

.perfil-stat .stat-label {
    font-size: 12px;
    color: #6c757d;
}

@media (max-width: 768px) {
    .perfil-header {
        flex-direction: column;
        text-align: center;
        padding: 25px;
    }
    
    .perfil-info h2 {
        justify-content: center;
    }
    
    .perfil-info .email {
        justify-content: center;
    }
    
    .perfil-stats {
        justify-content: center;
    }
}
/* ===== FORMULÁRIOS ===== */
.linha-form {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 25px;
    margin-bottom: 25px;
}

.grupo-form {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.grupo-form label {
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--dark);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.grupo-form label i {
    margin-right: 8px;
    color: var(--primary);
}

.campo-form {
    padding: 12px 16px;
    border: 2px solid var(--light-gray);
    border-radius: 12px;
    font-size: 0.95rem;
    transition: var(--transition);
    background: var(--white);
}

.campo-form:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(10, 147, 150, 0.1);
}

.campo-form:hover:not(:focus) {
    border-color: var(--primary-light);
}

.area-texto {
    resize: vertical;
    min-height: 100px;
    font-family: inherit;
}

.contador-caracteres {
    font-size: 0.75rem;
    color: var(--gray);
    text-align: right;
    margin-top: 6px;
}

/* ===== INFORMAÇÕES DE SEGURANÇA ===== */
.info-seguranca {
    background: linear-gradient(135deg, #f8f9fa, #ffffff);
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 25px;
    border: 1px solid var(--light-gray);
}

.info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid var(--light-gray);
    transition: var(--transition);
}

.info-item:last-child {
    border-bottom: none;
}

.info-item:hover {
    transform: translateX(5px);
}

.info-label {
    font-weight: 700;
    color: var(--dark);
    display: flex;
    align-items: center;
    gap: 10px;
}

.info-label i {
    color: var(--primary);
    width: 20px;
}

.info-value {
    font-weight: 500;
    padding: 4px 12px;
    border-radius: 20px;
}

.info-value.ativo {
    background: #d4edda;
    color: var(--success);
}

.info-value.inativo {
    background: #f8d7da;
    color: var(--danger);
}

/* ===== BOTÕES ===== */
.btn-primario {
    padding: 12px 28px;
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    color: var(--white);
    border: none;
    border-radius: 50px;
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition);
    display: inline-flex;
    align-items: center;
    gap: 10px;
    font-size: 0.9rem;
}

.btn-primario:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
    background: linear-gradient(135deg, var(--primary-dark), var(--primary));
}

.btn-cancelar, .btn-salvar {
    padding: 12px 32px;
    border-radius: 50px;
    font-weight: 600;
    cursor: pointer;
    border: none;
    transition: var(--transition);
    font-size: 0.9rem;
    display: inline-flex;
    align-items: center;
    gap: 10px;
}

.btn-cancelar {
    background: var(--light-gray);
    color: var(--gray);
}

.btn-cancelar:hover {
    background: #dee2e6;
    transform: translateY(-2px);
}

.btn-salvar {
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    color: var(--white);
}

.btn-salvar:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
}

/* ===== NOTIFICAÇÕES TOGGLE ===== */
.notificacoes-grid {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.toggle-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 18px;
    background: linear-gradient(135deg, #f8f9fa, #ffffff);
    border-radius: 16px;
    cursor: pointer;
    transition: var(--transition);
    border: 1px solid var(--light-gray);
}

.toggle-item:hover {
    transform: translateX(8px);
    box-shadow: var(--shadow-sm);
    border-color: var(--primary-light);
}

.toggle-info {
    display: flex;
    align-items: center;
    gap: 18px;
}

.toggle-info i {
    font-size: 24px;
    color: var(--primary);
    transition: var(--transition);
}

.toggle-item:hover .toggle-info i {
    transform: scale(1.1);
}

.toggle-info strong {
    display: block;
    font-size: 0.95rem;
    color: var(--dark);
    margin-bottom: 4px;
}

.toggle-info span {
    font-size: 0.8rem;
    color: var(--gray);
}

.toggle-switch {
    position: relative;
    width: 52px;
    height: 28px;
    display: inline-block;
}

.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: var(--gray);
    transition: 0.3s;
    border-radius: 34px;
}

.toggle-slider:before {
    position: absolute;
    content: "";
    height: 22px;
    width: 22px;
    left: 3px;
    bottom: 3px;
    background-color: var(--white);
    transition: 0.3s;
    border-radius: 50%;
    box-shadow: var(--shadow-sm);
}

input:checked + .toggle-slider {
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
}

input:checked + .toggle-slider:before {
    transform: translateX(24px);
}

/* ===== ESTATÍSTICAS ===== */
.stats-pessoais {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 25px;
}

.stat-card {
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 25px;
    background: linear-gradient(135deg, var(--white), #f8f9fa);
    border-radius: 16px;
    transition: var(--transition);
    border: 1px solid var(--light-gray);
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--primary), var(--primary-light));
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-lg);
}

.stat-card i {
    font-size: 40px;
    color: var(--primary);
    transition: var(--transition);
}

.stat-card:hover i {
    transform: scale(1.1);
}

.stat-card h3 {
    font-size: 28px;
    font-weight: 800;
    margin: 0;
    color: var(--secondary);
    line-height: 1;
}

.stat-card p {
    font-size: 0.85rem;
    color: var(--gray);
    margin: 5px 0 0 0;
}

/* ===== TABELA DE ACESSOS ===== */
.tabela-acessos {
    overflow-x: auto;
    border-radius: 16px;
}

.tabela-dados {
    width: 100%;
    border-collapse: collapse;
}

.tabela-dados th {
    text-align: left;
    padding: 15px;
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    font-weight: 700;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--dark);
}

.tabela-dados td {
    padding: 12px 15px;
    border-bottom: 1px solid var(--light-gray);
    font-size: 0.9rem;
    transition: var(--transition);
}

.tabela-dados tr:hover td {
    background: #f8f9fa;
    transform: scale(1.01);
}

.empty-state {
    text-align: center;
    padding: 60px;
    color: var(--gray);
}

.empty-state i {
    font-size: 64px;
    margin-bottom: 20px;
    display: block;
    color: var(--primary-light);
}

.empty-state p {
    font-size: 1rem;
}

/* ===== SEÇÃO DE AÇÕES ===== */
.secao-acoes {
    display: flex;
    justify-content: flex-end;
    gap: 20px;
    margin-top: 20px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 16px;
}

/* ===== MODAL ===== */
.modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.8);
    backdrop-filter: blur(4px);
    z-index: 10000;
    align-items: center;
    justify-content: center;
    padding: 20px;
    animation: fadeInUp 0.3s ease-out;
}

.modal.ativo { display: flex; }

.modal-conteudo {
    background: var(--white);
    border-radius: 24px;
    max-width: 600px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: var(--shadow-xl);
    animation: slideIn 0.3s ease-out;
}

.modal-cabecalho {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 25px 30px;
    border-bottom: 2px solid var(--light-gray);
    background: linear-gradient(135deg, #f8f9fa, var(--white));
}

.modal-cabecalho h2 {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--secondary);
    display: flex;
    align-items: center;
    gap: 10px;
}

.modal-cabecalho h2 i {
    color: var(--primary);
}

.modal-fechar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--white);
    border: 1px solid var(--light-gray);
    cursor: pointer;
    transition: var(--transition);
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-fechar:hover {
    background: var(--danger);
    color: var(--white);
    transform: rotate(90deg);
}

.modal-corpo {
    padding: 30px;
}

.modal-acoes {
    display: flex;
    justify-content: flex-end;
    gap: 15px;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid var(--light-gray);
}

/* ===== FORÇA DA SENHA ===== */
.forca-senha {
    margin-top: 10px;
}

.barra-forca {
    height: 6px;
    background: var(--light-gray);
    border-radius: 3px;
    overflow: hidden;
}

.preenchimento-forca {
    height: 100%;
    width: 0;
    transition: width 0.3s ease;
}

.rotulo-forca {
    font-size: 0.75rem;
    margin-top: 8px;
    font-weight: 600;
}

.requisitos-senha {
    background: linear-gradient(135deg, #f8f9fa, var(--white));
    border-radius: 16px;
    padding: 20px;
    margin: 20px 0;
    border: 1px solid var(--light-gray);
}

.requisitos-senha h4 {
    font-size: 0.85rem;
    margin-bottom: 12px;
    color: var(--dark);
    font-weight: 700;
}

.requisito {
    font-size: 0.8rem;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 10px;
    color: var(--gray);
    transition: var(--transition);
}

.requisito.atendido {
    color: var(--success);
}

.requisito i {
    font-size: 0.75rem;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    font-weight: normal;
    padding: 8px;
    border-radius: 8px;
    transition: var(--transition);
}

.checkbox-label:hover {
    background: var(--light-gray);
}

/* ===== NOTIFICAÇÃO FLUTUANTE ===== */
.notificacao {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 14px 24px;
    border-radius: 12px;
    z-index: 99999;
    font-weight: 600;
    animation: slideIn 0.3s ease-out;
    color: white;
    display: flex;
    align-items: center;
    gap: 12px;
    box-shadow: var(--shadow-lg);
}

.notificacao i {
    font-size: 1.2rem;
}

/* ===== RESPONSIVIDADE ===== */
@media (max-width: 1024px) {
    .secao-conteudo {
        padding: 25px;
    }
    
    .stats-pessoais {
        gap: 20px;
    }
}

@media (max-width: 768px) {
    .linha-form {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .stats-pessoais {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .perfil-header {
        flex-direction: column;
        text-align: center;
        padding: 20px;
    }
    
    .secao-acoes {
        flex-direction: column;
    }
    
    .btn-cancelar, .btn-salvar {
        width: 100%;
        justify-content: center;
    }
    
    .modal-conteudo {
        margin: 20px;
        max-width: calc(100% - 40px);
    }
    
    .toggle-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .toggle-info {
        width: 100%;
    }
    
    .info-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
}

@media (max-width: 480px) {
    .secao-conteudo {
        padding: 20px;
    }
    
    .perfil-info h2 {
        font-size: 24px;
    }
    
    .foto-container {
        width: 100px;
        height: 100px;
    }
    
    .stat-card {
        padding: 20px;
    }
    
    .stat-card i {
        font-size: 32px;
    }
    
    .stat-card h3 {
        font-size: 24px;
    }
    
    .modal-cabecalho {
        padding: 20px;
    }
    
    .modal-corpo {
        padding: 20px;
    }
}

/* ===== SCROLLBAR PERSONALIZADA ===== */
::-webkit-scrollbar {
    width: 10px;
    height: 10px;
}

::-webkit-scrollbar-track {
    background: var(--light-gray);
    border-radius: 10px;
}

::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    border-radius: 10px;
}

::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(135deg, var(--primary-dark), var(--secondary));
}

/* ===== LOADING STATES ===== */
.carregando {
    position: relative;
    pointer-events: none;
    opacity: 0.7;
}

.carregando::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 30px;
    height: 30px;
    margin: -15px 0 0 -15px;
    border: 3px solid var(--light-gray);
    border-top-color: var(--primary);
    border-radius: 50%;
    animation: spin 0.6s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>
<script>
let fotoFile = null;

document.getElementById('campoBiografia')?.addEventListener('input', function() {
    document.getElementById('contadorBio').textContent = this.value.length;
});

document.getElementById('uploadFoto')?.addEventListener('change', async function(e) {
    const file = e.target.files[0];
    if (!file) return;
    if (file.size > 5 * 1024 * 1024) { mostrarNotificacao('Máximo 5MB', 'erro'); this.value = ''; return; }
    const formData = new FormData();
    formData.append('action', 'upload_foto');
    formData.append('foto', file);
    try {
        const response = await fetch(window.location.href, { method: 'POST', body: formData });
        const data = await response.json();
        if (data.success) {
            document.getElementById('fotoContainer').innerHTML = `<img src="${normalizarUrlMidiaAdmin(data.foto_url || 'foto/sem_foto.png')}" alt="Foto">`;
            mostrarNotificacao('Foto atualizada!', 'sucesso');
        } else { mostrarNotificacao(data.message, 'erro'); }
    } catch(e) { mostrarNotificacao('Erro ao fazer upload', 'erro'); }
});

async function removerFoto() {
    if (!confirm('Remover foto de perfil?')) return;
    try {
        const response = await fetch(window.location.href, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ action: 'remover_foto' })
        });
        const data = await response.json();
        if (data.success) {
            document.getElementById('fotoContainer').innerHTML = `<div class="avatar-padrao"><i class="fas fa-user"></i></div>`;
            mostrarNotificacao(data.message, 'sucesso');
        } else { mostrarNotificacao(data.message, 'erro'); }
    } catch(e) { mostrarNotificacao('Erro ao remover', 'erro'); }
}

async function salvarPerfil() {
    const nome = document.getElementById('campoNome').value.trim();
    const email = document.getElementById('campoEmail').value.trim();
    if (!nome || !email) { mostrarNotificacao('Nome e email obrigatórios', 'erro'); return; }
    const formData = new URLSearchParams();
    formData.append('action', 'atualizar_perfil');
    formData.append('nome', nome);
    formData.append('email', email);
    formData.append('telefone', document.getElementById('campoTelefone').value);
    formData.append('departamento', document.getElementById('campoDepartamento').value);
    formData.append('cargo', document.getElementById('campoCargo').value);
    formData.append('biografia', document.getElementById('campoBiografia').value);
    formData.append('notificacoes_contacto', document.getElementById('notifContacto').checked ? '1' : '0');
    formData.append('notificacoes_sistema', document.getElementById('notifSistema').checked ? '1' : '0');
    formData.append('notificacoes_relatorios', document.getElementById('notifRelatorios').checked ? '1' : '0');
    formData.append('notificacoes_mensagens', '0');
    formData.append('notificacoes_comentarios', '0');
    try {
        const response = await fetch(window.location.href, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: formData });
        const data = await response.json();
        mostrarNotificacao(data.message, data.success ? 'sucesso' : 'erro');
    } catch(e) { mostrarNotificacao('Erro ao salvar', 'erro'); }
}

function descartarAlteracoes() { if (confirm('Descartar alterações?')) location.reload(); }

function abrirModalSenha() {
    document.getElementById('modalSenha').classList.add('ativo');
    document.getElementById('senhaAtual').value = '';
    document.getElementById('novaSenha').value = '';
    document.getElementById('confirmarSenha').value = '';
    document.getElementById('forcaSenha').style.display = 'none';
    document.querySelectorAll('.requisito').forEach(req => { req.classList.remove('atendido'); req.querySelector('i').className = 'fas fa-circle'; });
}

function fecharModalSenha() { document.getElementById('modalSenha').classList.remove('ativo'); }

function verificarForcaSenha() {
    const senha = document.getElementById('novaSenha').value;
    const forcaDiv = document.getElementById('forcaSenha');
    if (senha.length === 0) { forcaDiv.style.display = 'none'; return; }
    forcaDiv.style.display = 'block';
    
    const comprimentoOk = senha.length >= 6;
    const maiusculaOk = /[A-Z]/.test(senha);
    const minusculaOk = /[a-z]/.test(senha);
    const numeroOk = /[0-9]/.test(senha);
    const especialOk = /[!@#$%*]/.test(senha);
    
    const reqs = [
        { elem: document.getElementById('reqComprimento'), ok: comprimentoOk },
        { elem: document.getElementById('reqMaiuscula'), ok: maiusculaOk },
        { elem: document.getElementById('reqMinuscula'), ok: minusculaOk },
        { elem: document.getElementById('reqNumero'), ok: numeroOk },
        { elem: document.getElementById('reqEspecial'), ok: especialOk }
    ];
    reqs.forEach(r => {
        if (r.ok) { r.elem.classList.add('atendido'); r.elem.querySelector('i').className = 'fas fa-check-circle'; }
        else { r.elem.classList.remove('atendido'); r.elem.querySelector('i').className = 'fas fa-circle'; }
    });
    
    let forca = [comprimentoOk, maiusculaOk, minusculaOk, numeroOk, especialOk].filter(Boolean).length;
    const percentual = (forca / 5) * 100;
    document.getElementById('preenchimentoForca').style.width = percentual + '%';
    let cor, texto;
    if (forca <= 2) { cor = '#dc3545'; texto = 'Fraca'; }
    else if (forca <= 3) { cor = '#ffc107'; texto = 'Média'; }
    else { cor = '#28a745'; texto = 'Forte'; }
    document.getElementById('preenchimentoForca').style.background = cor;
    document.getElementById('rotuloForca').textContent = texto;
    document.getElementById('rotuloForca').style.color = cor;
}

async function alterarSenha() {
    const senhaAtual = document.getElementById('senhaAtual').value;
    const novaSenha = document.getElementById('novaSenha').value;
    const confirmarSenha = document.getElementById('confirmarSenha').value;
    if (!senhaAtual || !novaSenha || !confirmarSenha) { mostrarNotificacao('Preencha todos os campos', 'erro'); return; }
    if (novaSenha !== confirmarSenha) { mostrarNotificacao('Senhas não coincidem', 'erro'); return; }
    if (novaSenha.length < 6) { mostrarNotificacao('Mínimo 6 caracteres', 'erro'); return; }
    const formData = new URLSearchParams();
    formData.append('action', 'alterar_senha');
    formData.append('senha_atual', senhaAtual);
    formData.append('nova_senha', novaSenha);
    formData.append('confirmar_senha', confirmarSenha);
    formData.append('encerrar_sessoes', document.getElementById('encerrarSessoes').checked ? '1' : '0');
    try {
        const response = await fetch(window.location.href, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: formData });
        const data = await response.json();
        if (data.success) { mostrarNotificacao(data.message, 'sucesso'); fecharModalSenha(); }
        else { mostrarNotificacao(data.message, 'erro'); }
    } catch(e) { mostrarNotificacao('Erro ao alterar senha', 'erro'); }
}

function mostrarNotificacao(mensagem, tipo) {
    const notif = document.createElement('div');
    notif.className = 'notificacao';
    notif.innerHTML = `<i class="fas fa-${tipo === 'sucesso' ? 'check-circle' : 'exclamation-circle'}"></i> ${mensagem}`;
    notif.style.cssText = 'position:fixed;top:20px;right:20px;padding:12px 20px;border-radius:8px;z-index:99999;background:' + (tipo === 'sucesso' ? '#28a745' : '#dc3545') + ';color:white;font-weight:500;';
    document.body.appendChild(notif);
    setTimeout(() => notif.remove(), 3000);
}

document.getElementById('modalSenha')?.addEventListener('click', (e) => { if (e.target === document.getElementById('modalSenha')) fecharModalSenha(); });
document.addEventListener('keydown', (e) => { if (e.key === 'Escape') fecharModalSenha(); });
</script>

<?php include 'includes/footer.php'; ?>


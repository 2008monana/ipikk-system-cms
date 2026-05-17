<?php
/**
 * Utilizadores - Area Restrita IPIKK
 * Gestao completa de utilizadores do sistema
 */

$titulo_pagina = 'Utilizadores';
$css_especifico = 'admin-utilizadores.css';

$base_path = dirname(__DIR__);
require_once $base_path . '/config/index.php';
require_once $base_path . '/config/email.php';

if (!isset($_SESSION['utilizador_id'])) {
    header('Location: area-restrita.php');
    exit;
}

require_once __DIR__ . '/includes/verificar-permissao.php';

// ===== VERIFICAR PERMISSÃO ESPECÍFICA =====
verificarPermissao('utilizadores');

$db = getDB();

$stmt = $db->prepare("SELECT id, nome, email, foto_url FROM utilizadores WHERE id = ?");
$stmt->execute([$_SESSION['utilizador_id']]);
$usuario_logado = $stmt->fetch();

if (!$usuario_logado) {
    session_destroy();
    header('Location: area-restrita.php');
    exit;
}

$config = $db->query("SELECT * FROM configuracoes WHERE id = 1")->fetch();

$utilizadores = $db->query("SELECT * FROM utilizadores ORDER BY nome ASC")->fetchAll();

$total_utilizadores = count($utilizadores);
$total_ativos = count(array_filter($utilizadores, fn($u) => $u['ativo'] == 1));
$total_inativos = $total_utilizadores - $total_ativos;
$total_admin = count(array_filter($utilizadores, fn($u) => $u['nivel'] === 'admin'));
$total_editor = count(array_filter($utilizadores, fn($u) => $u['nivel'] === 'editor'));

// Lista de permissões disponíveis para o admin marcar
$permissoes_disponiveis = [
    ['valor' => 'dashboard', 'icone' => 'fa-chart-line', 'nome' => 'Dashboard', 'desc' => 'Página inicial da área restrita'],
    ['valor' => 'conteudo_site', 'icone' => 'fa-file-alt', 'nome' => 'Conteúdo do Site', 'desc' => 'Páginas: Início, Quem Somos, Institucional, Alumni, Escolas'],
    ['valor' => 'oferta_formativa', 'icone' => 'fa-graduation-cap', 'nome' => 'Oferta Formativa', 'desc' => 'Cursos e Depoimentos'],
    ['valor' => 'noticias', 'icone' => 'fa-newspaper', 'nome' => 'Notícias', 'desc' => 'Criar, editar e publicar notícias'],
    ['valor' => 'galeria', 'icone' => 'fa-images', 'nome' => 'Galeria', 'desc' => 'Gerir imagens e vídeos'],
    ['valor' => 'inscricoes', 'icone' => 'fa-file-signature', 'nome' => 'Inscrições', 'desc' => 'Gerir período de inscrições e vagas'],
    ['valor' => 'contactos', 'icone' => 'fa-envelope', 'nome' => 'Contactos', 'desc' => 'Ler mensagens recebidas'],
    ['valor' => 'utilizadores', 'icone' => 'fa-users', 'nome' => 'Utilizadores', 'desc' => 'Criar e gerir contas (recomendado apenas para admin)'],
    ['valor' => 'configuracoes', 'icone' => 'fa-cog', 'nome' => 'Configurações', 'desc' => 'Alterar configurações do site'],
    ['valor' => 'logs', 'icone' => 'fa-history', 'nome' => 'Logs', 'desc' => 'Consultar registos e atividade do sistema']
];
$permissoes_validas = array_column($permissoes_disponiveis, 'valor');


$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'upload_foto') {
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (!in_array($ext, $allowed)) {
                echo json_encode(['success' => false, 'message' => 'Formato nao permitido. Use JPG, PNG, GIF ou WEBP.']);
                exit;
            }
            $upload = uploadArquivoNuvem($_FILES['foto'], 'utilizadores');
            if ($upload['success']) {
                echo json_encode(['success' => true, 'foto_url' => $upload['url']]);
                exit;
            }
        }
        echo json_encode(['success' => false, 'message' => 'Erro ao fazer upload da foto.']);
        exit;
    }

    if ($action === 'salvar_utilizador') {
    if (isset($_POST['id']) && $_POST['id'] != '' && $_POST['id'] != '0' && is_numeric($_POST['id']) && $_POST['id'] > 0) {
        $id = (int)$_POST['id'];
    } else {
        $id = null;
    }
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $departamento = trim($_POST['departamento'] ?? '');
    $cargo = trim($_POST['cargo'] ?? '');
    $nivel = $_POST['nivel'] ?? 'editor';
    $avatar_icone = $_POST['avatar_icone'] ?? 'fa-user';
    $foto_url = $_POST['foto_url'] ?? 'foto/sem_foto.png';
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    $forcar_alteracao_senha = isset($_POST['forcar_alteracao_senha']) ? 1 : 0;
    $senha = $_POST['senha'] ?? '';
    
    // ============================================
    // CORREÇÃO: Processar as permissões corretamente
    // ============================================
    $permissoes = [];
    
    // Verificar se vieram permissões no POST
    if (isset($_POST['permissoes']) && is_array($_POST['permissoes'])) {
        $permissoes = $_POST['permissoes'];
    } elseif (isset($_POST['permissoes']) && is_string($_POST['permissoes'])) {
        // Se veio como string JSON, decodificar
        $decoded = json_decode($_POST['permissoes'], true);
        if (is_array($decoded)) {
            $permissoes = $decoded;
        }
    }
    
    // Garantir que é um array e aceitar apenas permissões configuráveis.
    if (!is_array($permissoes)) {
        $permissoes = [];
    }
    $permissoes = array_values(array_intersect($permissoes, $permissoes_validas));
    
    // Converter para JSON
    $permissoes_json = json_encode($permissoes, JSON_UNESCAPED_UNICODE);
    
    // VALIDAR SE O EMAIL JÁ EXISTE
    $stmt_check = $db->prepare("SELECT id FROM utilizadores WHERE email = ? AND id != ?");
    $stmt_check->execute([$email, $id ?? 0]);
    if ($stmt_check->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Este email já está em uso.']);
        exit;
    }
    
    try {
        if ($id) {
            // EDITAR UTILIZADOR EXISTENTE
            if (!empty($senha)) {
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE utilizadores SET 
                    nome = ?, email = ?, telefone = ?, departamento = ?, 
                    cargo = ?, nivel = ?, avatar_icone = ?, foto_url = ?, 
                    ativo = ?, forcar_alteracao_senha = ?, senha = ?, 
                    permissoes = ?, updated_at = NOW() 
                    WHERE id = ?");
                $success = $stmt->execute([
                    $nome, $email, $telefone, $departamento, $cargo, 
                    $nivel, $avatar_icone, $foto_url, $ativo, 
                    $forcar_alteracao_senha, $senha_hash, $permissoes_json, $id
                ]);
                $message = $success ? 'Utilizador atualizado com sucesso! Senha alterada.' : 'Erro ao atualizar.';
            } else {
                $stmt = $db->prepare("UPDATE utilizadores SET 
                    nome = ?, email = ?, telefone = ?, departamento = ?, 
                    cargo = ?, nivel = ?, avatar_icone = ?, foto_url = ?, 
                    ativo = ?, forcar_alteracao_senha = ?, permissoes = ?, 
                    updated_at = NOW() 
                    WHERE id = ?");
                $success = $stmt->execute([
                    $nome, $email, $telefone, $departamento, $cargo, 
                    $nivel, $avatar_icone, $foto_url, $ativo, 
                    $forcar_alteracao_senha, $permissoes_json, $id
                ]);
                $message = $success ? 'Utilizador atualizado com sucesso! Senha mantida.' : 'Erro ao atualizar.';
            }
            echo json_encode(['success' => $success, 'message' => $message]);
        } else {
            // CRIAR NOVO UTILIZADOR
            if (empty($senha)) {
                $senha = substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789'), 0, 10);
            }
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            
            $stmt = $db->prepare("INSERT INTO utilizadores 
                (nome, email, telefone, departamento, cargo, nivel, 
                 avatar_icone, foto_url, ativo, forcar_alteracao_senha, 
                 senha, permissoes, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
            $success = $stmt->execute([
                $nome, $email, $telefone, $departamento, $cargo, 
                $nivel, $avatar_icone, $foto_url, $ativo, 
                $forcar_alteracao_senha, $senha_hash, $permissoes_json
            ]);
            
            if ($success) {
                $assunto = 'Credenciais de Acesso - Área Restrita IPIKK';
                $corpo = '<p>Olá <strong>' . htmlspecialchars($nome) . '</strong>,</p>'
                    . '<p>A sua conta foi criada com sucesso.</p>'
                    . '<p><strong>Email:</strong> ' . htmlspecialchars($email) . '<br>'
                    . '<strong>Senha inicial:</strong> ' . htmlspecialchars($senha) . '</p>'
                    . '<p>Por segurança, altere a senha no primeiro acesso.</p>';
                enviarEmail($email, $nome, $assunto, $corpo);
                echo json_encode(['success' => true, 'message' => 'Utilizador "' . $nome . '" criado com sucesso! As credenciais foram enviadas para o email do utilizador.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erro ao criar utilizador.']);
            }
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
    }
    exit;
}
    
    if ($action === 'alterar_estado') {
        $id = (int)($_POST['id'] ?? 0);
        $ativo = (int)($_POST['ativo'] ?? 0);
        if ($id == $_SESSION['utilizador_id'] && $ativo == 0) {
            echo json_encode(['success' => false, 'message' => 'Nao pode desativar a sua propria conta.']);
            exit;
        }
        $stmt = $db->prepare("UPDATE utilizadores SET ativo = ? WHERE id = ?");
        $success = $stmt->execute([$ativo, $id]);
        $estado_texto = $ativo ? 'ativado' : 'desativado';
        if ($success && function_exists('registrarLog')) {
            registrarLog('editou', 'utilizadores', $id, "{$estado_texto} o utilizador ID {$id}");
        }
        echo json_encode(['success' => $success, 'message' => $success ? 'Estado atualizado com sucesso!' : 'Erro ao atualizar estado.']);
        exit;
    }

    if ($action === 'reenviar_credenciais') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $db->prepare("SELECT nome, email FROM utilizadores WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $nova_senha = substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789!@#$%'), 0, 12);
            $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
            $stmt_update = $db->prepare("UPDATE utilizadores SET senha = ?, forcar_alteracao_senha = 1 WHERE id = ?");
            $stmt_update->execute([$senha_hash, $id]);
            if (function_exists('registrarLog')) {
                registrarLog('reenviou_credenciais', 'utilizadores', $id, "Reenviou credenciais para o utilizador: {$user['nome']}");
            }
            echo json_encode(['success' => true, 'message' => "Credenciais geradas! Copie a senha: {$nova_senha}", 'senha' => $nova_senha]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Utilizador nao encontrado.']);
        }
        exit;
    }

    if ($action === 'forcar_mudanca_senha') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $db->prepare("UPDATE utilizadores SET forcar_alteracao_senha = 1 WHERE id = ?");
        $success = $stmt->execute([$id]);
        if ($success && function_exists('registrarLog')) {
            registrarLog('forcou_mudanca_senha', 'utilizadores', $id, "Forcou mudanca de senha para utilizador ID {$id}");
        }
        echo json_encode(['success' => $success, 'message' => $success ? 'Pedido de alteracao de senha enviado!' : 'Erro ao processar pedido.']);
        exit;
    }

    if ($action === 'buscar_utilizador') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $db->prepare("SELECT * FROM utilizadores WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $user['permissoes'] = json_decode($user['permissoes'] ?? '[]', true);
            if (!is_array($user['permissoes'])) {
                $user['permissoes'] = [];
            }
            $user['permissoes'] = array_values(array_intersect($user['permissoes'], $permissoes_validas));
            echo json_encode(['success' => true, 'utilizador' => $user]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Utilizador nao encontrado.']);
        }
        exit;
    }

    if ($action === 'eliminar_utilizador') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0 || $id == $_SESSION['utilizador_id']) {
            echo json_encode(['success' => false, 'message' => 'Não é possível eliminar este utilizador.']);
            exit;
        }
        try {
            $stmt = $db->prepare("DELETE FROM utilizadores WHERE id = ?");
            $success = $stmt->execute([$id]);
            echo json_encode(['success' => $success, 'message' => $success ? 'Utilizador eliminado com sucesso!' : 'Erro ao eliminar utilizador.']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Erro ao eliminar utilizador: ' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'importar_utilizadores') {
        $dados_json = $_POST['dados'] ?? '';
        $dados = json_decode($dados_json, true);
        if (!is_array($dados) || empty($dados)) {
            echo json_encode(['success' => false, 'message' => 'Dados invalidos para importacao.']);
            exit;
        }
        $importados = 0;
        $erros = [];
        foreach ($dados as $linha) {
            $nome = trim($linha['nome'] ?? $linha['Nome'] ?? '');
            $email = trim($linha['email'] ?? $linha['Email'] ?? '');
            if (empty($nome) || empty($email)) continue;
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) continue;
            $stmt_check = $db->prepare("SELECT id FROM utilizadores WHERE email = ?");
            $stmt_check->execute([$email]);
            if ($stmt_check->fetch()) continue;
            $senha = substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789!@#$%'), 0, 10);
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO utilizadores (nome, email, senha, nivel, ativo, forcar_alteracao_senha, permissoes) VALUES (?, ?, ?, 'editor', 1, 1, '[]')");
            if ($stmt->execute([$nome, $email, $senha_hash])) {
                $importados++;
            } else {
                $erros[] = $email;
            }
        }
        if (function_exists('registrarLog')) {
            registrarLog('importou', 'utilizadores', 0, "Importou {$importados} utilizadores via CSV");
        }
        echo json_encode(['success' => true, 'message' => "Importados {$importados} utilizadores.", 'erros' => $erros]);
        exit;
    }
}

$dados_js = [
    'utilizadores' => $utilizadores,
    'usuario_logado_id' => $_SESSION['utilizador_id'],
    'permissoes_disponiveis' => $permissoes_disponiveis
];
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Area Restrita - Utilizadores</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="../area-publica/foto/ipikk_new_logo.png" rel="icon">
    <link rel="stylesheet" href="css/admin-sidebar-header.css">
    <style>
        /* ===== ESTILOS ADMIN UTILIZADORES ===== */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

:root {
    --azul-primario: #003072;
    --azul-secundario: #0a4da8;
    --verde-acento: #0a9396;
    --cinza-claro: #f8fafc;
    --cinza-medio: #e2e8f0;
    --cinza-escuro: #334155;
    --cor-texto: #1e293b;
    --branco: #ffffff;
    --sucesso: #10b981;
    --aviso: #f59e0b;
    --perigo: #ef4444;
    --info: #3b82f6;
    --sombra-sm: 0 1px 3px rgba(0, 0, 0, 0.05);
    --sombra-md: 0 4px 12px rgba(0, 0, 0, 0.08);
    --sombra-lg: 0 8px 24px rgba(0, 0, 0, 0.12);
    --transicao: all 0.3s ease;
    --borda-arredondada: 12px;
    --largura-sidebar: 280px;
    --altura-topo: 70px;
}

/* Tipografia */
body {
    font-family: 'Montserrat', sans-serif;
    font-size: 14px;
    line-height: 1.6;
    color: var(--cor-texto);
    background-color: #f1f5f9;
}

h1, h2, h3, h4 {
    font-family: 'Poppins', sans-serif;
    font-weight: 600;
    color: var(--azul-primario);
}

/* Layout principal */
.conteudo-principal {
    margin-left: var(--largura-sidebar);
    min-height: 100vh;
}

/* Barra superior */
.barra-topo {
    height: var(--altura-topo);
    background: var(--branco);
    box-shadow: var(--sombra-md);
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 32px;
    position: sticky;
    top: 0;
    z-index: 999;
    border-bottom: 1px solid var(--cinza-medio);
}

.barra-topo h1 {
    font-size: 1.4rem;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 12px;
}

.barra-topo h1 i {
    color: var(--verde-acento);
}

.direita-barra-topo {
    display: flex;
    align-items: center;
    gap: 20px;
}

/* Botão menu mobile */
.botao-menu-mobile {
    display: none;
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: var(--azul-primario);
    padding: 8px;
}

/* Botão primário atualizado */
.btn-primario {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 10px 24px;
    background: linear-gradient(135deg, var(--verde-acento), var(--azul-primario));
    color: var(--branco);
    border: none;
    border-radius: 40px;
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
    transition: var(--transicao);
    box-shadow: var(--sombra-sm);
}

.btn-primario:hover {
    transform: translateY(-2px);
    box-shadow: var(--sombra-lg);
}

.btn-primario:active {
    transform: translateY(0);
}

/* Wrapper conteúdo */
.wrapper-conteudo {
    padding: 32px;
}

/* Secções */
.secao-conteudo {
    background: var(--branco);
    border-radius: 20px;
    padding: 28px;
    margin-bottom: 28px;
    box-shadow: var(--sombra-sm);
    border: 1px solid var(--cinza-medio);
    transition: var(--transicao);
}

.secao-conteudo:hover {
    box-shadow: var(--sombra-md);
}

.titulo-secao {
    font-size: 1.1rem;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    padding-bottom: 12px;
    border-bottom: 2px solid var(--cinza-medio);
}

.titulo-secao i {
    color: var(--verde-acento);
}

/* Cards estatísticos */
.grade-visao-geral {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.item-visao-geral {
    padding: 20px;
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    border-radius: 16px;
    border-left: 4px solid var(--verde-acento);
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: var(--transicao);
    box-shadow: var(--sombra-sm);
}

.item-visao-geral:hover {
    transform: translateY(-3px);
    box-shadow: var(--sombra-md);
}

.item-visao-geral i {
    font-size: 32px;
    color: var(--azul-primario);
    opacity: 0.8;
}

.item-visao-geral div {
    text-align: right;
}

.item-visao-geral strong {
    font-size: 28px;
    font-weight: 700;
    color: var(--azul-primario);
    display: block;
    line-height: 1.2;
}

.item-visao-geral span {
    font-size: 12px;
    color: #64748b;
}

/* Filtros */
.grade-filtros {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.item-filtro {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.item-filtro.largura-total {
    grid-column: 1 / -1;
}

.item-filtro label {
    font-size: 0.7rem;
    font-weight: 700;
    color: var(--cinza-escuro);
    text-transform: uppercase;
    letter-spacing: 0.8px;
}

/* Busca */
.caixa-busca {
    position: relative;
}

.caixa-busca i {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 0.9rem;
}

.caixa-busca input {
    width: 100%;
    padding: 12px 16px 12px 44px;
    border: 1px solid var(--cinza-medio);
    border-radius: 12px;
    font-size: 0.85rem;
    transition: var(--transicao);
    background: var(--branco);
}

.caixa-busca input:focus {
    outline: none;
    border-color: var(--verde-acento);
    box-shadow: 0 0 0 3px rgba(10, 147, 150, 0.1);
}

/* Selects */
.selecao-form,
.campo-form {
    padding: 12px 16px;
    border: 1px solid var(--cinza-medio);
    border-radius: 12px;
    font-size: 0.85rem;
    transition: var(--transicao);
    width: 100%;
    font-family: inherit;
    background: var(--branco);
}

.selecao-form:focus,
.campo-form:focus {
    outline: none;
    border-color: var(--verde-acento);
    box-shadow: 0 0 0 3px rgba(10, 147, 150, 0.1);
}

/* Tabela */
.tabela-responsiva {
    overflow-x: auto;
    border-radius: 12px;
    border: 1px solid var(--cinza-medio);
}

.tabela-dados {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.85rem;
}

.tabela-dados th {
    background: #f8fafc;
    color: var(--azul-primario);
    padding: 14px 16px;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 2px solid var(--cinza-medio);
}

.tabela-dados td {
    padding: 14px 16px;
    border-bottom: 1px solid var(--cinza-medio);
    vertical-align: middle;
}

.tabela-dados tbody tr {
    transition: var(--transicao);
    cursor: pointer;
}

.tabela-dados tbody tr:hover {
    background: #f8fafc;
}
/* Botão Ativar/Desativar */
.btn-toggle-estado {
    background: #e0e0e0;
    color: #666;
}

.btn-toggle-estado:hover {
    background: #f59e0b;
    color: white;
    transform: scale(1.05);
}

/* Checkbox */
.checkbox-coluna {
    width: 40px;
    text-align: center;
}

.checkbox-linha,
.checkbox-selecionar-todos {
    width: 18px;
    height: 18px;
    cursor: pointer;
    accent-color: var(--verde-acento);
}

/* Avatar */
.foto-usuario {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid var(--cinza-medio);
}

.avatar-usuario {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}

.avatar-usuario.admin {
    background: linear-gradient(135deg, #fef3c7, #fde68a);
    color: #d97706;
}

.avatar-usuario.editor {
    background: linear-gradient(135deg, #dcfce7, #bbf7d0);
    color: #15803d;
}

/* Badges */
.badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    border-radius: 30px;
    font-size: 0.7rem;
    font-weight: 600;
}

.badge-sucesso {
    background: #dcfce7;
    color: #15803d;
}

.badge-aviso {
    background: #fef3c7;
    color: #b45309;
}

/* Ações da tabela */
.acoes-coluna {
    white-space: nowrap;
}

.btn-acao-tabela {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    border: none;
    cursor: pointer;
    transition: var(--transicao);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin: 0 4px;
}

.btn-acao-tabela.btn-editar {
    background: #e0f2fe;
    color: #0284c7;
}

.btn-acao-tabela.btn-editar:hover {
    background: #0284c7;
    color: white;
    transform: scale(1.05);
}

.btn-acao-tabela.btn-eliminar {
    background: #fee2e2;
    color: #dc2626;
}

.btn-acao-tabela.btn-eliminar:hover {
    background: #dc2626;
    color: white;
    transform: scale(1.05);
}

.btn-acao-tabela.btn-disabled {
    opacity: 0.5;
    cursor: not-allowed;
    pointer-events: none;
}

/* Ações rápidas */
.acoes-rapidas {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid var(--cinza-medio);
}

.btn-acao {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: #f8fafc;
    border: 1px solid var(--cinza-medio);
    border-radius: 40px;
    cursor: pointer;
    transition: var(--transicao);
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--cinza-escuro);
}

.btn-acao:hover:not(:disabled) {
    background: var(--verde-acento);
    border-color: var(--verde-acento);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(10, 147, 150, 0.2);
}

.btn-acao:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    pointer-events: none;
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

.conteudo-modal {
    background: var(--branco);
    border-radius: 24px;
    max-width: 950px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    animation: modalFadeIn 0.3s ease;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
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

/* Cabeçalho do modal */
.cabecalho-modal {
    background: linear-gradient(135deg, var(--azul-primario) 0%, var(--azul-secundario) 60%, var(--verde-acento) 100%);
    padding: 24px 28px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: sticky;
    top: 0;
    z-index: 10;
}

.esquerda-cabecalho {
    display: flex;
    align-items: center;
    gap: 14px;
}

.icone-cabecalho {
    width: 52px;
    height: 52px;
    background: rgba(255, 255, 255, 0.15);
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: white;
}

.texto-cabecalho h2 {
    color: white;
    font-size: 1.25rem;
    margin: 0 0 4px 0;
    font-weight: 700;
}

.texto-cabecalho p {
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.75rem;
    margin: 0;
}

.btn-fechar {
    width: 38px;
    height: 38px;
    background: rgba(255, 255, 255, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 50%;
    cursor: pointer;
    transition: var(--transicao);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    color: white;
}

.btn-fechar:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: rotate(90deg);
}

/* Navegação de passos */
.navegacao-passos {
    display: flex;
    align-items: center;
    padding: 20px 28px;
    background: #f8fafc;
    border-bottom: 1px solid var(--cinza-medio);
}

.item-passo {
    display: flex;
    align-items: center;
    gap: 10px;
    flex: 1;
    cursor: pointer;
}

.bolha-passo {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    border: 2px solid var(--cinza-medio);
    background: white;
    color: #94a3b8;
    font-weight: 700;
    font-size: 0.8rem;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: var(--transicao);
}

.rotulo-passo {
    font-size: 0.7rem;
    font-weight: 600;
    color: #94a3b8;
    letter-spacing: 0.3px;
}

.linha-passo {
    flex: 1;
    height: 2px;
    background: var(--cinza-medio);
    margin: 0 12px;
}

.item-passo.ativo .bolha-passo {
    background: var(--verde-acento);
    border-color: var(--verde-acento);
    color: white;
    box-shadow: 0 0 0 3px rgba(10, 147, 150, 0.2);
}

.item-passo.ativo .rotulo-passo {
    color: var(--verde-acento);
}

.item-passo.concluido .bolha-passo {
    background: var(--sucesso);
    border-color: var(--sucesso);
    color: white;
}

.item-passo.concluido .rotulo-passo {
    color: var(--sucesso);
}

/* Corpo do modal */
.corpo-modal {
    padding: 28px;
}

.painel-passo {
    display: none;
    animation: fadeInUp 0.3s ease;
}

.painel-passo.ativo {
    display: block;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(15px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Cards de seção */
.cartao-secao {
    background: #f8fafc;
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 24px;
    border: 1px solid var(--cinza-medio);
    transition: var(--transicao);
}

.cartao-secao:hover {
    border-color: #cbd5e1;
}

.titulo-cartao {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 0.9rem;
    font-weight: 700;
    color: var(--azul-primario);
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 1px solid var(--cinza-medio);
}

.titulo-cartao i {
    color: var(--verde-acento);
}

/* Formulário */
.linha-form {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
    margin-bottom: 20px;
}

.linha-form.largura-total {
    grid-template-columns: 1fr;
}

.grupo-form {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.grupo-form label {
    font-size: 0.7rem;
    font-weight: 700;
    color: var(--cinza-escuro);
    text-transform: uppercase;
    letter-spacing: 0.8px;
}

/* Seletor de avatar */
.seletor-avatar {
    display: flex;
    align-items: center;
    gap: 24px;
    flex-wrap: wrap;
    margin-bottom: 20px;
}

.circulo-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--verde-acento), var(--azul-primario));
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    color: white;
    cursor: pointer;
    transition: var(--transicao);
    box-shadow: var(--sombra-sm);
}

.circulo-avatar:hover {
    transform: scale(1.02);
}

.opcoes-avatar {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.opcao-avatar {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    font-size: 1.1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    border: 2px solid var(--cinza-medio);
    background: white;
    transition: var(--transicao);
    color: #64748b;
}

.opcao-avatar:hover {
    border-color: var(--verde-acento);
    transform: scale(1.1);
}

.opcao-avatar.selecionado {
    border-color: var(--verde-acento);
    background: rgba(10, 147, 150, 0.1);
    color: var(--verde-acento);
}

/* Upload de foto */
.area-upload-foto {
    border: 2px dashed #cbd5e1;
    border-radius: 16px;
    padding: 24px;
    text-align: center;
    cursor: pointer;
    transition: var(--transicao);
    background: white;
    margin-top: 10px;
}

.area-upload-foto:hover {
    border-color: var(--verde-acento);
    background: #f0fdfa;
}

.area-upload-foto i {
    font-size: 2rem;
    color: var(--verde-acento);
}

.area-upload-foto p {
    margin-top: 8px;
    font-size: 0.85rem;
    color: var(--cinza-escuro);
}

.area-upload-foto small {
    font-size: 0.7rem;
    color: #94a3b8;
}

.preview-foto {
    margin-top: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 16px;
}

.preview-foto img {
    width: 75px;
    height: 75px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid var(--verde-acento);
    box-shadow: var(--sombra-sm);
}

.preview-foto button {
    background: var(--perigo);
    color: white;
    border: none;
    border-radius: 50%;
    width: 34px;
    height: 34px;
    cursor: pointer;
    transition: var(--transicao);
    display: flex;
    align-items: center;
    justify-content: center;
}

.preview-foto button:hover {
    transform: scale(1.1);
}

/* Cards de nível */
.cards-nivel {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
}

.card-nivel {
    border: 2px solid var(--cinza-medio);
    border-radius: 16px;
    padding: 16px;
    text-align: center;
    cursor: pointer;
    transition: var(--transicao);
    background: white;
}

.card-nivel:hover {
    border-color: var(--verde-acento);
    transform: translateY(-2px);
    box-shadow: var(--sombra-sm);
}

.card-nivel.selecionado {
    border-color: var(--verde-acento);
    background: rgba(10, 147, 150, 0.05);
}

.card-nivel i {
    font-size: 2rem;
    display: block;
    margin-bottom: 10px;
    color: var(--verde-acento);
}

.titulo-card-nivel {
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--cor-texto);
}

.desc-card-nivel {
    font-size: 0.65rem;
    color: #94a3b8;
    margin-top: 4px;
}

.card-nivel input {
    display: none;
}

/* Permissões */
.grade-permissoes {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
}

.item-permissao {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    background: white;
    border: 1px solid var(--cinza-medio);
    border-radius: 12px;
    cursor: pointer;
    transition: var(--transicao);
}

.item-permissao:hover {
    border-color: var(--verde-acento);
    background: #f8fafc;
}

.item-permissao.selecionado {
    border-color: var(--verde-acento);
    background: rgba(10, 147, 150, 0.05);
}

.item-permissao input {
    width: 18px;
    height: 18px;
    cursor: pointer;
    accent-color: var(--verde-acento);
}

.item-permissao i {
    font-size: 1.1rem;
    color: var(--verde-acento);
    width: 24px;
}

.texto-permissao strong {
    display: block;
    font-size: 0.8rem;
    font-weight: 700;
    color: var(--cor-texto);
}

.texto-permissao span {
    font-size: 0.65rem;
    color: #94a3b8;
}

/* Senha */
.wrapper-senha {
    position: relative;
}

.wrapper-senha input {
    padding-right: 100px;
}

.btn-gerar-senha {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    padding: 6px 14px;
    background: linear-gradient(135deg, var(--info), #2563eb);
    color: white;
    border: none;
    border-radius: 30px;
    cursor: pointer;
    font-size: 0.7rem;
    font-weight: 600;
    transition: var(--transicao);
}

.btn-gerar-senha:hover {
    transform: translateY(-1px);
    box-shadow: var(--sombra-sm);
}

/* Força da senha */
.forca-senha {
    margin-top: 8px;
}

.barra-forca {
    height: 4px;
    border-radius: 2px;
    background: var(--cinza-medio);
    overflow: hidden;
}

.preenchimento-forca {
    height: 100%;
    border-radius: 2px;
    transition: width 0.3s ease;
}

.rotulo-forca {
    font-size: 0.65rem;
    margin-top: 4px;
    font-weight: 600;
}

/* Toggle */
.linha-toggle {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 18px;
    background: white;
    border: 1px solid var(--cinza-medio);
    border-radius: 12px;
    margin-bottom: 12px;
    cursor: pointer;
    transition: var(--transicao);
}

.linha-toggle:hover {
    border-color: var(--verde-acento);
    background: #f8fafc;
}

.esquerda-toggle {
    display: flex;
    align-items: center;
    gap: 12px;
}

.esquerda-toggle i {
    font-size: 1.1rem;
    color: var(--verde-acento);
    width: 24px;
}

.texto-toggle strong {
    display: block;
    font-size: 0.8rem;
    font-weight: 700;
    color: var(--cor-texto);
}

.texto-toggle span {
    font-size: 0.65rem;
    color: #94a3b8;
}

.toggle-switch {
    position: relative;
    width: 46px;
    height: 24px;
    flex-shrink: 0;
}

.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider {
    position: absolute;
    inset: 0;
    background: #cbd5e1;
    border-radius: 24px;
    cursor: pointer;
    transition: var(--transicao);
}

.toggle-slider:before {
    content: '';
    position: absolute;
    width: 18px;
    height: 18px;
    left: 3px;
    bottom: 3px;
    background: white;
    border-radius: 50%;
    transition: var(--transicao);
}

input:checked + .toggle-slider {
    background: var(--verde-acento);
}

input:checked + .toggle-slider:before {
    transform: translateX(22px);
}

/* Aviso de edição */
.aviso-edicao-senha {
    background: #fef3c7;
    border-left: 4px solid #f59e0b;
    border-radius: 10px;
    padding: 10px 14px;
    margin-top: 10px;
    font-size: 0.75rem;
    color: #92400e;
}

/* Revisão */
.avatar-revisao {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--verde-acento), var(--azul-primario));
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    color: white;
    margin: 0 auto 20px;
}

.foto-revisao {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    object-fit: cover;
    margin: 0 auto 20px;
    display: block;
    border: 3px solid var(--verde-acento);
}

.sumario-revisao {
    background: #f8fafc;
    border-radius: 16px;
    padding: 20px;
}

.linha-revisao {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid var(--cinza-medio);
    font-size: 0.85rem;
}

.linha-revisao:last-child {
    border-bottom: none;
}

.rotulo-revisao {
    color: #64748b;
    font-size: 0.7rem;
    text-transform: uppercase;
    font-weight: 600;
}

.rotulo-revisao i {
    margin-right: 6px;
    color: var(--verde-acento);
}

.valor-revisao {
    font-weight: 600;
    color: var(--cor-texto);
}

/* Rodapé do modal */
.rodape-modal {
    padding: 20px 28px;
    border-top: 1px solid var(--cinza-medio);
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f8fafc;
    border-radius: 0 0 24px 24px;
}

.info-passo-rodape {
    font-size: 0.75rem;
    color: #94a3b8;
}

.botoes-rodape {
    display: flex;
    gap: 12px;
}

.btn-voltar,
.btn-avancar,
.btn-enviar {
    padding: 10px 24px;
    border-radius: 40px;
    font-weight: 600;
    font-size: 0.8rem;
    cursor: pointer;
    transition: var(--transicao);
    display: inline-flex;
    align-items: center;
    gap: 10px;
}

.btn-voltar {
    background: white;
    border: 1px solid var(--cinza-medio);
    color: var(--cinza-escuro);
}

.btn-voltar:hover {
    border-color: #cbd5e1;
    background: #f8fafc;
}

.btn-avancar {
    background: linear-gradient(135deg, var(--verde-acento), var(--azul-primario));
    color: white;
    border: none;
}

.btn-avancar:hover {
    transform: translateY(-2px);
    box-shadow: var(--sombra-md);
}

.btn-enviar {
    background: linear-gradient(135deg, var(--sucesso), #059669);
    color: white;
    border: none;
}

.btn-enviar:hover {
    transform: translateY(-2px);
    box-shadow: var(--sombra-md);
}

/* Notificações */
.notificacao {
    position: fixed;
    bottom: 30px;
    right: 30px;
    padding: 14px 24px;
    border-radius: 50px;
    color: white;
    font-weight: 600;
    font-size: 0.85rem;
    z-index: 10001;
    animation: slideInRight 0.3s ease;
    box-shadow: var(--sombra-lg);
    display: flex;
    align-items: center;
    gap: 10px;
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

.notificacao.sucesso {
    background: linear-gradient(135deg, var(--sucesso), #059669);
}

.notificacao.erro {
    background: linear-gradient(135deg, var(--perigo), #dc2626);
}

.notificacao.aviso {
    background: linear-gradient(135deg, var(--aviso), #d97706);
}

.notificacao.info {
    background: linear-gradient(135deg, var(--info), #2563eb);
}

/* Scrollbar personalizada */
.conteudo-modal::-webkit-scrollbar {
    width: 6px;
}

.conteudo-modal::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 10px;
}

.conteudo-modal::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}

.conteudo-modal::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Responsividade */
@media (max-width: 1024px) {
    .grade-permissoes {
        grid-template-columns: 1fr;
    }
    
    .cards-nivel {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .conteudo-principal {
        margin-left: 0;
    }
    
    .botao-menu-mobile {
        display: block;
    }
    
    .wrapper-conteudo {
        padding: 20px;
    }
    
    .secao-conteudo {
        padding: 20px;
    }
    
    .grade-visao-geral {
        grid-template-columns: 1fr;
        gap: 12px;
    }
    
    .grade-filtros {
        grid-template-columns: 1fr;
    }
    
    .linha-form {
        grid-template-columns: 1fr;
        gap: 16px;
    }
    
    .navegacao-passos {
        flex-wrap: wrap;
        gap: 12px;
        justify-content: center;
    }
    
    .item-passo {
        flex-direction: column;
        text-align: center;
        gap: 6px;
    }
    
    .rotulo-passo {
        font-size: 0.6rem;
    }
    
    .linha-passo {
        display: none;
    }
    
    .tabela-dados th,
    .tabela-dados td {
        padding: 10px 12px;
        font-size: 0.75rem;
    }
    
    .acoes-rapidas {
        flex-direction: column;
    }
    
    .btn-acao {
        justify-content: center;
    }
    
    .rodape-modal {
        flex-direction: column;
        gap: 15px;
    }
    
    .botoes-rodape {
        width: 100%;
    }
    
    .btn-voltar,
    .btn-avancar,
    .btn-enviar {
        flex: 1;
        justify-content: center;
    }
    
    .conteudo-modal {
        max-width: 95%;
    }
    
    .notificacao {
        left: 20px;
        right: 20px;
        bottom: 20px;
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .barra-topo {
        padding: 0 16px;
    }
    
    .barra-topo h1 {
        font-size: 1.1rem;
    }
    
    .btn-primario span {
        display: none;
    }
    
    .btn-primario {
        padding: 10px 14px;
    }
    
    .item-visao-geral strong {
        font-size: 20px;
    }
    
    .item-visao-geral i {
        font-size: 24px;
    }
    
    .cabecalho-modal {
        padding: 16px 20px;
    }
    
    .esquerda-cabecalho {
        gap: 10px;
    }
    
    .icone-cabecalho {
        width: 40px;
        height: 40px;
        font-size: 1.2rem;
    }
    
    .texto-cabecalho h2 {
        font-size: 1rem;
    }
    
    .corpo-modal {
        padding: 20px;
    }
    
    .cartao-secao {
        padding: 16px;
    }
    
    .foto-usuario,
    .avatar-usuario {
        width: 36px;
        height: 36px;
        font-size: 1rem;
    }
    
    .btn-acao-tabela {
        width: 30px;
        height: 30px;
    }
}
    </style>
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<main class="conteudo-principal">
    <header class="barra-superior">
        <div class="esquerda-barra">
            <button class="botao-menu-mobile" id="botaoMenuMobile"><i class="fas fa-bars"></i></button>
            <h1 class="titulo-pagina"><i class="fas fa-users"></i> Utilizadores</h1>
        </div>
        <div class="direita-barra">
            <button class="btn-primario" id="btnNovoUtilizador">
                <i class="fas fa-plus"></i><span>Novo Utilizador</span>
            </button>
        </div>
    </header>

    <div class="conteudo-pagina">
        <section class="secao-conteudo">
            <h2 class="titulo-secao"><i class="fas fa-chart-bar"></i> Visao Geral</h2>
            <div class="grade-visao-geral">
                <div class="item-visao-geral"><i class="fas fa-users"></i><div><strong><?= $total_utilizadores ?></strong><span> Total</span></div></div>
                <div class="item-visao-geral"><i class="fas fa-check-circle"></i><div><strong><?= $total_ativos ?></strong><span> Ativos</span></div></div>
                <div class="item-visao-geral"><i class="fas fa-pause-circle"></i><div><strong><?= $total_inativos ?></strong><span> Inativos</span></div></div>
                <div class="item-visao-geral"><i class="fas fa-crown"></i><div><strong><?= $total_admin ?></strong><span> Administradores</span></div></div>
                <div class="item-visao-geral"><i class="fas fa-edit"></i><div><strong><?= $total_editor ?></strong><span> Editores</span></div></div>
            </div>
        </section>

        <section class="secao-conteudo">
            <h2 class="titulo-secao"><i class="fas fa-filter"></i> Filtros & Busca</h2>
            <div class="grade-filtros">
                <div class="item-filtro largura-total"><div class="caixa-busca"><i class="fas fa-search"></i><input type="text" id="campoBusca" placeholder="Buscar por nome ou email..."></div></div>
                <div class="item-filtro"><label>Cargo</label><select class="selecao-form" id="filtroCargo"><option value="">Todos</option><option value="admin">Administrador</option><option value="editor">Editor</option></select></div>
                <div class="item-filtro"><label>Estado</label><select class="selecao-form" id="filtroEstado"><option value="">Todos</option><option value="1">Ativos</option><option value="0">Inativos</option></select></div>
                <div class="item-filtro"><label>Ordenar por</label><select class="selecao-form" id="filtroOrdenar"><option value="nome">Nome (A-Z)</option><option value="data">Ultimo login</option></select></div>
            </div>
        </section>

        <section class="secao-conteudo">
            <h2 class="titulo-secao"><i class="fas fa-list"></i> Lista de Utilizadores <span style="color: var(--verde-acento);" id="contadorUtilizadores">(<?= $total_utilizadores ?> utilizadores)</span></h2>
            <div class="tabela-responsiva">
                <table class="tabela-dados">
    <thead>
        <tr>
            <th class="checkbox-coluna"><input type="checkbox" id="selecionarTodos" class="checkbox-selecionar-todos" title="Selecionar todos"></th>
            <th>FOTO</th>
            <th>NOME</th>
            <th>EMAIL</th>
            <th>CARGO</th>
            <th>ESTADO</th>
            <th>AÇÕES</th>
        </tr>
    </thead>
    <tbody id="corpoTabelaUtilizadores">
        <?php foreach ($utilizadores as $user): ?>
        <tr data-id="<?= $user['id'] ?>" data-cargo="<?= $user['nivel'] ?>" data-estado="<?= $user['ativo'] ?>">
            <td class="checkbox-coluna" onclick="event.stopPropagation()">
                <input type="checkbox" class="checkbox-linha" data-id="<?= $user['id'] ?>" value="<?= $user['id'] ?>">
            </td>
            <td>
                <?php if (!empty($user['foto_url']) && $user['foto_url'] != 'foto/sem_foto.png'): ?>
                    <img src="<?= htmlspecialchars(normalizarUrlMidia($user['foto_url'], '..')) ?>" class="foto-usuario" alt="<?= htmlspecialchars($user['nome']) ?>">
                <?php else: ?>
                    <div class="avatar-usuario"><i class="fas <?= $user['avatar_icone'] ?? 'fa-user' ?>"></i></div>
                <?php endif; ?>
            </td>
            <td><strong><?= htmlspecialchars($user['nome']) ?></strong></td>
            <td><?= htmlspecialchars($user['email']) ?></td>
            <td><?= $user['nivel'] === 'admin' ? 'Administrador' : 'Editor' ?></td>
            <td>
                <span class="badge <?= $user['ativo'] ? 'badge-sucesso' : 'badge-aviso' ?>">
                    <i class="fas <?= $user['ativo'] ? 'fa-check-circle' : 'fa-pause-circle' ?>"></i> 
                    <?= $user['ativo'] ? 'Ativo' : 'Inativo' ?>
                </span>
            </td>
            <td class="acoes-coluna" onclick="event.stopPropagation()">
                <!-- Botão Ativar/Desativar -->
                <button class="btn-acao-tabela btn-toggle-estado" onclick="toggleEstadoUtilizador(<?= $user['id'] ?>, <?= $user['ativo'] ?>)" title="<?= $user['ativo'] ? 'Desativar' : 'Ativar' ?>">
                    <i class="fas <?= $user['ativo'] ? 'fa-pause-circle' : 'fa-play-circle' ?>"></i>
                </button>
                
                <!-- Botão Editar -->
                <button class="btn-acao-tabela btn-editar" onclick="abrirEdicao(<?= $user['id'] ?>)" title="Editar">
                    <i class="fas fa-edit"></i>
                </button>
                
                <!-- Botão Eliminar (protegido para própria conta) -->
                <?php if ($user['id'] != $_SESSION['utilizador_id']): ?>
                <button class="btn-acao-tabela btn-eliminar" onclick="eliminarUtilizador(<?= $user['id'] ?>)" title="Eliminar">
                    <i class="fas fa-trash-alt"></i>
                </button>
                <?php else: ?>
                <button class="btn-acao-tabela btn-disabled" disabled title="Não pode eliminar a própria conta">
                    <i class="fas fa-trash-alt"></i>
                </button>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
            </div>
        </section>

        <section class="secao-conteudo">
            <h2 class="titulo-secao"><i class="fas fa-bolt"></i> Acoes Rapidas</h2>
            <p style="font-size:13px; color:#888; margin-bottom:8px;"><i class="fas fa-info-circle"></i> Selecione utilizadores na tabela para ativar as acoes abaixo.</p>
            <div class="acoes-rapidas">
                <!--<button class="btn-acao" id="btnReenviarCredenciais" disabled><i class="fas fa-envelope"></i> Reenviar credenciais</button>
                <button class="btn-acao" id="btnForcarMudancaSenha" disabled><i class="fas fa-lock"></i> Forcar mudanca de senha</button>-->
                <button class="btn-acao" id="btnExportarLista"><i class="fas fa-file-export"></i> Exportar lista</button>
                <!--<button class="btn-acao" id="btnImportarLista"><i class="fas fa-file-import"></i> Importar lista</button>-->
            </div>
        </section>
    </div>
</main>

<div id="modalNovoUtilizador" class="modal">
    <div class="conteudo-modal">
        <div class="cabecalho-modal">
            <div class="esquerda-cabecalho"><div class="icone-cabecalho"><i class="fas fa-user-plus"></i></div><div class="texto-cabecalho"><h2 id="modalTitulo">Novo Utilizador</h2><p id="modalSubtitulo">Preencha os dados para criar uma conta de acesso</p></div></div>
            <button class="btn-fechar" id="btnFecharModal"><i class="fas fa-times"></i></button>
        </div>
        <div class="navegacao-passos">
            <div class="item-passo ativo" data-passo="1"><div class="bolha-passo">1</div><span class="rotulo-passo">Dados Pessoais</span></div>
            <div class="linha-passo"></div>
            <div class="item-passo" data-passo="2"><div class="bolha-passo">2</div><span class="rotulo-passo">Permissoes</span></div>
            <div class="linha-passo"></div>
            <div class="item-passo" data-passo="3"><div class="bolha-passo">3</div><span class="rotulo-passo">Credenciais</span></div>
            <div class="linha-passo"></div>
            <div class="item-passo" data-passo="4"><div class="bolha-passo">4</div><span class="rotulo-passo">Revisao</span></div>
        </div>
        <div class="corpo-modal">
            <form id="formularioNovoUtilizador" onsubmit="return false;">
                <input type="hidden" id="utilizadorId">
                <input type="hidden" id="isEditMode" value="0">

                <div class="painel-passo ativo" id="passo1">
                    <div class="cartao-secao">
                        <div class="titulo-cartao"><i class="fas fa-image"></i> Foto de Perfil</div>
                        <div class="seletor-avatar"><div class="circulo-avatar" id="previewAvatar"><i class="fas fa-user"></i></div>
                            <div><p style="font-size:12px; color:#666; margin-bottom:8px;">Escolha um avatar ou faca upload de uma foto</p>
                                <div class="opcoes-avatar" id="opcoesAvatar"></div></div>
                        </div>
                        <div class="area-upload-foto" onclick="document.getElementById('uploadFotoInput').click()"><i class="fas fa-cloud-upload-alt" style="font-size: 24px;"></i><p>Clique para fazer upload de uma foto real</p><small>Formatos: JPG, PNG</small></div>
                        <input type="file" id="uploadFotoInput" accept="image/*" style="display: none;">
                        <div class="preview-foto" id="previewFotoContainer" style="display: none;"><img id="previewFotoImg" src="" alt="Preview"><button type="button" onclick="removerFotoUpload()"><i class="fas fa-trash"></i></button></div>
                    </div>
                    <div class="cartao-secao">
                        <div class="titulo-cartao"><i class="fas fa-id-card"></i> Dados Pessoais</div>
                        <div class="linha-form"><div class="grupo-form"><label>Nome Completo *</label><input type="text" class="campo-form" id="campoNome" placeholder="Ex: Maria da Silva"></div>
                        <div class="grupo-form"><label>Email Institucional *</label><input type="email" class="campo-form" id="campoEmail" placeholder="utilizador@ipikk.ao"></div></div>
                        <div class="linha-form"><div class="grupo-form"><label>Telefone</label><input type="tel" class="campo-form" id="campoTelefone" placeholder="+244 900 000 000"></div>
                        <div class="grupo-form"><label>Departamento</label><input type="text" class="campo-form" id="campoDepartamento" placeholder="Ex: Secretaria"></div></div>
                        <div class="linha-form largura-total"><div class="grupo-form"><label>Cargo / Funcao</label><input type="text" class="campo-form" id="campoCargo" placeholder="Ex: Editor de Conteudo"></div></div>
                    </div>
                </div>

                <div class="painel-passo" id="passo2">
                    <div class="cartao-secao">
                        <div class="titulo-cartao"><i class="fas fa-bullseye"></i> Nivel de Acesso</div>
                        <div class="cards-nivel"><label class="card-nivel" id="cardAdmin"><input type="radio" name="nivel" value="admin"><i class="fas fa-crown"></i><div class="titulo-card-nivel">Administrador</div><div class="desc-card-nivel">Acesso total ao sistema</div></label>
                        <label class="card-nivel selecionado" id="cardEditor"><input type="radio" name="nivel" value="editor" checked><i class="fas fa-edit"></i><div class="titulo-card-nivel">Editor</div><div class="desc-card-nivel">Cria e edita conteudo (permissoes configuráveis)</div></label></div>
                    </div>
                    <div class="cartao-secao">
                        <div class="titulo-cartao"><i class="fas fa-key"></i> Permissoes Especificas</div>
                        <p style="font-size:12px; color:#666; margin-bottom:15px;"><i class="fas fa-info-circle"></i> Marque apenas as paginas que este utilizador pode acessar</p>
                        <div class="grade-permissoes" id="gradePermissoes"></div>
                    </div>
                </div>

                <div class="painel-passo" id="passo3">
                    <div class="cartao-secao">
                        <div class="titulo-cartao"><i class="fas fa-lock"></i> Senha de Acesso</div>
                        <div class="linha-form largura-total"><div class="grupo-form"><label id="senhaLabel">Senha Inicial</label><div class="wrapper-senha"><input type="text" class="campo-form" id="senhaGerada" placeholder="Clique em Gerar..."><button type="button" class="btn-gerar-senha" id="btnGerarSenha"><i class="fas fa-sync-alt"></i> Gerar</button></div></div></div>
                        <p style="font-size:11px; color:#888;" id="infoSenha"><i class="fas fa-info-circle"></i> A senha sera mostrada ao criar/utilizador.</p>
                        <div id="avisoEdicaoSenha" class="aviso-edicao-senha" style="display: none;"><i class="fas fa-info-circle"></i> <strong>Apenas preencha a senha se quiser alterá-la.</strong> Deixe em branco para manter a senha atual.</div>
                    </div>
                    <div class="cartao-secao">
                        <div class="titulo-cartao"><i class="fas fa-shield-alt"></i> Opcoes de Seguranca</div>
                        <div class="linha-toggle"><div class="esquerda-toggle"><i class="fas fa-sync-alt"></i><div><strong>Obrigar alteracao de senha</strong><span>Utilizador devera criar nova senha no 1º login</span></div></div><label class="toggle-switch"><input type="checkbox" id="toggleForcarSenha" checked><span class="toggle-slider"></span></label></div>
                        <div class="linha-toggle"><div class="esquerda-toggle"><i class="fas fa-check-circle"></i><div><strong>Conta ativa imediatamente</strong><span>Desative para criar a conta sem acesso imediato</span></div></div><label class="toggle-switch"><input type="checkbox" id="toggleAtivo" checked><span class="toggle-slider"></span></label></div>
                    </div>
                </div>

                <div class="painel-passo" id="passo4">
                    <div class="cartao-secao">
                        <div class="titulo-cartao"><i class="fas fa-check-double"></i> Confirmar Dados</div>
                        <div id="revisaoAvatar" class="avatar-revisao"><i class="fas fa-user"></i></div>
                        <div class="sumario-revisao">
                            <div class="linha-revisao"><span class="rotulo-revisao"><i class="fas fa-user"></i> Nome</span><span class="valor-revisao" id="revNome">—</span></div>
                            <div class="linha-revisao"><span class="rotulo-revisao"><i class="fas fa-envelope"></i> Email</span><span class="valor-revisao" id="revEmail">—</span></div>
                            <div class="linha-revisao"><span class="rotulo-revisao"><i class="fas fa-briefcase"></i> Cargo</span><span class="valor-revisao" id="revCargo">—</span></div>
                            <div class="linha-revisao"><span class="rotulo-revisao"><i class="fas fa-bullseye"></i> Nivel</span><span class="valor-revisao" id="revNivel">—</span></div>
                            <div class="linha-revisao"><span class="rotulo-revisao"><i class="fas fa-key"></i> Permissoes</span><span class="valor-revisao" id="revPerms">—</span></div>
                            <div class="linha-revisao"><span class="rotulo-revisao"><i class="fas fa-lock"></i> Senha</span><span class="valor-revisao" id="revSenha">—</span></div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="rodape-modal">
            <span class="info-passo-rodape" id="infoRodape">Passo 1 de 4</span>
            <div class="botoes-rodape"><button class="btn-voltar" id="btnVoltar" style="display:none;"><i class="fas fa-arrow-left"></i> Anterior</button>
            <button class="btn-avancar" id="btnAvancar">Proximo <i class="fas fa-arrow-right"></i></button>
            <button class="btn-enviar" id="btnEnviar" style="display:none;"><i class="fas fa-check-circle"></i> <span id="textoBtnEnviar">Criar Utilizador</span></button></div>
        </div>
    </div>
</div>

<script src="js/admin-sidebar-header.js"></script>
<script>
// Dados do PHP
const utilizadoresData = <?php echo json_encode($utilizadores, JSON_UNESCAPED_UNICODE); ?>;
const usuarioLogadoId = <?php echo (int)$_SESSION['utilizador_id']; ?>;
const permissoesDisponiveis = <?php echo json_encode($permissoes_disponiveis, JSON_UNESCAPED_UNICODE); ?>;

let passoAtual = 1;
const totalPassos = 4;
let editandoId = null;
let isEditMode = false;
let avatarSelecionado = 'fa-user';
let fotoUploadUrl = '';
let utilizadoresSelecionados = [];

// ============================================
// NOTIFICAÇÕES
// ============================================
function mostrarNotificacao(mensagem, tipo = 'sucesso') {
    const notif = document.createElement('div');
    notif.className = `notificacao ${tipo}`;
    const icone = tipo === 'sucesso' ? 'fa-check-circle' : tipo === 'erro' ? 'fa-exclamation-circle' : 'fa-info-circle';
    notif.innerHTML = `<i class="fas ${icone}"></i> ${mensagem}`;
    document.body.appendChild(notif);
    setTimeout(() => notif.remove(), 3500);
}

// ============================================
// GERAR SENHA
// ============================================
function gerarSenha() {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789!@#$%';
    let senha = '';
    for (let i = 0; i < 12; i++) senha += chars[Math.floor(Math.random() * chars.length)];
    document.getElementById('senhaGerada').value = senha;
}

// ============================================
// PREENCHER REVISÃO (INCLUINDO FOTO)
// ============================================
function preencherRevisao() {
    document.getElementById('revNome').textContent = document.getElementById('campoNome').value || '—';
    document.getElementById('revEmail').textContent = document.getElementById('campoEmail').value || '—';
    document.getElementById('revCargo').textContent = document.getElementById('campoCargo').value || '—';
    
    const nivel = document.querySelector('input[name="nivel"]:checked')?.value;
    document.getElementById('revNivel').innerHTML = nivel === 'admin' 
        ? '<i class="fas fa-crown"></i> Administrador' 
        : '<i class="fas fa-edit"></i> Editor';
    
    const permsSelecionadas = Array.from(document.querySelectorAll('#gradePermissoes .item-permissao.selecionado .texto-permissao strong')).map(el => el.textContent);
    document.getElementById('revPerms').textContent = permsSelecionadas.length ? permsSelecionadas.join(', ') : 'Nenhuma';
    
    const senha = document.getElementById('senhaGerada').value;
    document.getElementById('revSenha').textContent = senha ? '••••••••' : '—';
    
    // CORREÇÃO: Foto na revisão
    const revisaoDiv = document.getElementById('revisaoAvatar');
    if (fotoUploadUrl && fotoUploadUrl !== 'foto/sem_foto.png') {
        revisaoDiv.innerHTML = `<img src="../area-publica/${fotoUploadUrl}" style="width:100px;height:100px;border-radius:50%;object-fit:cover;border:3px solid var(--verde-acento);">`;
        revisaoDiv.className = '';
    } else {
        revisaoDiv.innerHTML = `<i class="fas ${avatarSelecionado}"></i>`;
        revisaoDiv.className = 'avatar-revisao';
    }
}

// ============================================
// NAVEGAÇÃO DE PASSOS
// ============================================
function irParaPasso(n) {
    document.getElementById('passo' + passoAtual).classList.remove('ativo');
    passoAtual = n;
    document.getElementById('passo' + passoAtual).classList.add('ativo');
    
    document.querySelectorAll('.item-passo').forEach(el => {
        const s = parseInt(el.dataset.passo);
        el.classList.remove('ativo', 'concluido');
        if (s < passoAtual) el.classList.add('concluido');
        if (s === passoAtual) el.classList.add('ativo');
    });
    
    document.getElementById('btnVoltar').style.display = passoAtual > 1 ? 'flex' : 'none';
    document.getElementById('btnAvancar').style.display = passoAtual < totalPassos ? 'flex' : 'none';
    document.getElementById('btnEnviar').style.display = passoAtual === totalPassos ? 'flex' : 'none';
    document.getElementById('infoRodape').textContent = `Passo ${passoAtual} de ${totalPassos}`;
    
    if (passoAtual === totalPassos) preencherRevisao();
}

// ============================================
// UPLOAD DE FOTO
// ============================================
async function uploadFoto(file) {
    const formData = new FormData();
    formData.append('action', 'upload_foto');
    formData.append('foto', file);
    try {
        const response = await fetch(window.location.href, { method: 'POST', body: formData });
        const data = await response.json();
        if (data.success) {
            fotoUploadUrl = data.foto_url;
            document.getElementById('previewFotoImg').src = '../area-publica/' + fotoUploadUrl;
            document.getElementById('previewFotoContainer').style.display = 'flex';
            document.getElementById('previewAvatar').innerHTML = `<img src="../area-publica/${fotoUploadUrl}" style="width:70px;height:70px;border-radius:50%;object-fit:cover;">`;
            mostrarNotificacao('Foto carregada!', 'sucesso');
        } else { 
            mostrarNotificacao(data.message, 'erro'); 
        }
    } catch(e) { 
        mostrarNotificacao('Erro ao fazer upload', 'erro'); 
    }
}

function removerFotoUpload() {
    fotoUploadUrl = '';
    document.getElementById('previewFotoContainer').style.display = 'none';
    document.getElementById('uploadFotoInput').value = '';
    document.getElementById('previewAvatar').innerHTML = `<i class="fas ${avatarSelecionado}"></i>`;
}

// ============================================
// RESETAR MODAL
// ============================================
function resetarModal() {
    editandoId = null;
    isEditMode = false;
    fotoUploadUrl = '';
    avatarSelecionado = 'fa-user';
    
    document.getElementById('utilizadorId').value = '';
    document.getElementById('isEditMode').value = '0';
    document.getElementById('campoNome').value = '';
    document.getElementById('campoEmail').value = '';
    document.getElementById('campoTelefone').value = '';
    document.getElementById('campoDepartamento').value = '';
    document.getElementById('campoCargo').value = '';
    
    document.getElementById('modalTitulo').textContent = 'Novo Utilizador';
    document.getElementById('modalSubtitulo').textContent = 'Preencha os dados para criar uma conta de acesso';
    document.getElementById('textoBtnEnviar').textContent = 'Criar Utilizador';
    
    document.getElementById('senhaLabel').textContent = 'Senha Inicial';
    document.getElementById('infoSenha').innerHTML = '<i class="fas fa-info-circle"></i> A senha será mostrada ao criar/utilizador.';
    document.getElementById('avisoEdicaoSenha').style.display = 'none';
    
    document.getElementById('previewAvatar').innerHTML = '<i class="fas fa-user"></i>';
    document.getElementById('previewFotoContainer').style.display = 'none';
    
    document.querySelectorAll('.card-nivel').forEach(c => c.classList.remove('selecionado'));
    document.getElementById('cardEditor').classList.add('selecionado');
    document.querySelector('input[name="nivel"][value="editor"]').checked = true;
    
    document.querySelectorAll('#gradePermissoes .item-permissao').forEach(item => {
        item.classList.remove('selecionado');
        item.querySelector('input').checked = false;
    });
    
    document.getElementById('toggleForcarSenha').checked = true;
    document.getElementById('toggleAtivo').checked = true;
    document.getElementById('senhaGerada').value = '';
    
    gerarSenha();
}

// ============================================
// CARREGAR GRADE DE PERMISSÕES
// ============================================
function carregarGradePermissoes(permissoesSelecionadas = []) {
    const container = document.getElementById('gradePermissoes');
    if (!container) return;
    
    container.innerHTML = '';
    
    permissoesDisponiveis.forEach(perm => {
        const isSelected = permissoesSelecionadas.includes(perm.valor);
        
        const div = document.createElement('div');
        div.className = `item-permissao ${isSelected ? 'selecionado' : ''}`;
        div.setAttribute('data-permissao', perm.valor);
        
        div.innerHTML = `
            <input type="checkbox" value="${perm.valor}" ${isSelected ? 'checked' : ''}>
            <i class="fas ${perm.icone}"></i>
            <div class="texto-permissao">
                <strong>${perm.nome}</strong>
                <span>${perm.desc}</span>
            </div>
        `;
        
        // Obter referências dos elementos
        const checkbox = div.querySelector('input');
        
        // Função para atualizar o estado visual
        function atualizarEstado(checked) {
            if (checked) {
                div.classList.add('selecionado');
                checkbox.checked = true;
            } else {
                div.classList.remove('selecionado');
                checkbox.checked = false;
            }
        }
        
        // Evento do checkbox
        checkbox.addEventListener('change', function(e) {
            e.stopPropagation();
            atualizarEstado(this.checked);
        });
        
        // Evento do card inteiro (clicar em qualquer lugar do item-permissao)
        div.addEventListener('click', function(e) {
            // Se clicou diretamente no checkbox, não fazer nada (já foi tratado)
            if (e.target.tagName === 'INPUT') return;
            
            // Inverter o estado
            const novoEstado = !checkbox.checked;
            atualizarEstado(novoEstado);
        });
        
        container.appendChild(div);
    });
}

// ============================================
// ABRIR MODAL NOVO
// ============================================
function abrirModalNovo() {
    resetarModal();
    carregarGradePermissoes([]);
    irParaPasso(1);
    document.getElementById('modalNovoUtilizador').classList.add('ativo');
}

// ============================================
// ABRIR EDIÇÃO
// ============================================
function abrirEdicao(id) {
    const user = utilizadoresData.find(u => u.id == id);
    if (!user) return;
    
    editandoId = id;
    isEditMode = true;
    document.getElementById('isEditMode').value = '1';
    document.getElementById('utilizadorId').value = user.id;
    document.getElementById('campoNome').value = user.nome;
    document.getElementById('campoEmail').value = user.email;
    document.getElementById('campoTelefone').value = user.telefone || '';
    document.getElementById('campoDepartamento').value = user.departamento || '';
    document.getElementById('campoCargo').value = user.cargo || '';
    
    document.getElementById('modalTitulo').textContent = 'Editar Utilizador';
    document.getElementById('modalSubtitulo').textContent = 'Altere os dados do utilizador';
    document.getElementById('textoBtnEnviar').textContent = 'Guardar Alteracoes';
    
    document.getElementById('senhaLabel').textContent = 'Nova Senha (opcional)';
    document.getElementById('infoSenha').innerHTML = '<i class="fas fa-info-circle"></i> Deixe em branco para manter a senha atual.';
    document.getElementById('avisoEdicaoSenha').style.display = 'block';
    
    // Carregar permissões do usuário
    let permissoes = [];
    try { 
        permissoes = typeof user.permissoes === 'string' ? JSON.parse(user.permissoes) : (user.permissoes || []); 
    } catch(e) { 
        permissoes = []; 
    }
    carregarGradePermissoes(permissoes);
    
    // Carregar foto
    if (user.foto_url && user.foto_url !== 'foto/sem_foto.png') {
        fotoUploadUrl = user.foto_url;
        document.getElementById('previewFotoImg').src = '../area-publica/' + fotoUploadUrl;
        document.getElementById('previewFotoContainer').style.display = 'flex';
        document.getElementById('previewAvatar').innerHTML = `<img src="../area-publica/${fotoUploadUrl}" style="width:70px;height:70px;border-radius:50%;object-fit:cover;">`;
    } else {
        fotoUploadUrl = '';
        const avatar = user.avatar_icone || 'fa-user';
        avatarSelecionado = avatar;
        document.getElementById('previewAvatar').innerHTML = `<i class="fas ${avatar}"></i>`;
        document.getElementById('previewFotoContainer').style.display = 'none';
    }
    
    // Carregar nível
    document.querySelectorAll('.card-nivel').forEach(c => c.classList.remove('selecionado'));
    if (user.nivel === 'admin') {
        document.getElementById('cardAdmin').classList.add('selecionado');
    } else {
        document.getElementById('cardEditor').classList.add('selecionado');
    }
    document.querySelector(`input[name="nivel"][value="${user.nivel}"]`).checked = true;
    
    document.getElementById('toggleAtivo').checked = user.ativo == 1;
    document.getElementById('senhaGerada').value = '';
    
    irParaPasso(1);
    document.getElementById('modalNovoUtilizador').classList.add('ativo');
}

function fecharModal() { 
    document.getElementById('modalNovoUtilizador').classList.remove('ativo'); 
}

// ============================================
// SALVAR UTILIZADOR (CORRIGIDO)
// ============================================
async function salvarUtilizador() {
    const id = document.getElementById('utilizadorId').value;
    const nome = document.getElementById('campoNome').value.trim();
    const email = document.getElementById('campoEmail').value.trim();
    const senha = document.getElementById('senhaGerada').value;
    
    console.log('=== SALVANDO UTILIZADOR ===');
    console.log('ID:', id || 'novo');
    console.log('Nome:', nome);
    console.log('Email:', email);
    console.log('Senha:', senha ? 'definida' : 'vazia');
    
    if (!nome || !email) { 
        mostrarNotificacao('Preencha nome e email.', 'erro'); 
        return; 
    }
    
    if (!id && !senha) { 
        mostrarNotificacao('A senha é obrigatória para novos utilizadores.', 'erro'); 
        return; 
    }
    
    const telefone = document.getElementById('campoTelefone').value;
    const departamento = document.getElementById('campoDepartamento').value;
    const cargo = document.getElementById('campoCargo').value;
    const nivel = document.querySelector('input[name="nivel"]:checked')?.value || 'editor';
    const ativo = document.getElementById('toggleAtivo').checked ? 1 : 0;
    const forcarSenha = document.getElementById('toggleForcarSenha').checked ? 1 : 0;
    const permissoes = Array.from(document.querySelectorAll('#gradePermissoes .item-permissao.selecionado input')).map(cb => cb.value);
    const fotoUrl = fotoUploadUrl || 'foto/sem_foto.png';
    
    console.log('Nível:', nivel);
    console.log('Permissões:', permissoes);
    console.log('Foto URL:', fotoUrl);
    
    const formData = new URLSearchParams();
    formData.append('action', 'salvar_utilizador');
    
    // ===== CORREÇÃO: Só enviar ID se for válido =====
    if (id && id !== '' && id !== '0' && id !== 'null') {
        formData.append('id', id);
    }
    
    formData.append('nome', nome);
    formData.append('email', email);
    formData.append('telefone', telefone);
    formData.append('departamento', departamento);
    formData.append('cargo', cargo);
    formData.append('nivel', nivel);
    formData.append('avatar_icone', avatarSelecionado);
    formData.append('foto_url', fotoUrl);
    formData.append('ativo', ativo);
    formData.append('forcar_alteracao_senha', forcarSenha);
    formData.append('senha', senha);
    formData.append('permissoes', JSON.stringify(permissoes));
    
    try {
        const response = await fetch(window.location.href, { 
            method: 'POST', 
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: formData 
        });
        const data = await response.json();
        console.log('Resposta:', data);
        mostrarNotificacao(data.message, data.success ? 'sucesso' : 'erro');
        if (data.success) setTimeout(() => location.reload(), 1500);
    } catch(e) { 
        console.error('Erro:', e);
        mostrarNotificacao('Erro ao salvar: ' + e.message, 'erro'); 
    }
}
// ============================================
// ELIMINAR UTILIZADOR
// ============================================
async function eliminarUtilizador(id) {
    if (!confirm('Eliminar este utilizador permanentemente?')) return;
    
    const formData = new URLSearchParams();
    formData.append('action', 'eliminar_utilizador');
    formData.append('id', id);
    
    try {
        const response = await fetch(window.location.href, { 
            method: 'POST', 
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: formData 
        });
        const data = await response.json();
        mostrarNotificacao(data.message, data.success ? 'sucesso' : 'erro');
        if (data.success) setTimeout(() => location.reload(), 1200);
    } catch(e) { 
        mostrarNotificacao('Erro ao eliminar', 'erro'); 
    }
}

// ============================================
// REENVIAR CREDENCIAIS
// ============================================
async function reenviarCredenciais(id) {
    const formData = new URLSearchParams();
    formData.append('action', 'reenviar_credenciais');
    formData.append('id', id);
    
    try {
        const response = await fetch(window.location.href, { 
            method: 'POST', 
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: formData 
        });
        const data = await response.json();
        if (data.success && data.senha) {
            mostrarNotificacao(`${data.message} Senha: ${data.senha}`, 'sucesso');
        } else { 
            mostrarNotificacao(data.message, 'erro'); 
        }
    } catch(e) { 
        mostrarNotificacao('Erro ao reenviar', 'erro'); 
    }
}
// ============================================
// ALTERNAR ESTADO DO UTILIZADOR (ATIVAR/DESATIVAR)
// ============================================
async function toggleEstadoUtilizador(id, estadoAtual) {
    const novoEstado = estadoAtual == 1 ? 0 : 1;
    const acao = novoEstado == 1 ? 'ativar' : 'desativar';
    
    if (!confirm(`Tem certeza que deseja ${acao} este utilizador?`)) return;
    
    const formData = new URLSearchParams();
    formData.append('action', 'alterar_estado');
    formData.append('id', id);
    formData.append('ativo', novoEstado);
    
    try {
        const response = await fetch(window.location.href, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: formData
        });
        const data = await response.json();
        mostrarNotificacao(data.message, data.success ? 'sucesso' : 'erro');
        if (data.success) setTimeout(() => location.reload(), 1200);
    } catch(e) {
        mostrarNotificacao('Erro ao alterar estado', 'erro');
    }
}
// ============================================
// FORCAR MUDANÇA DE SENHA
// ============================================
async function forcarMudancaSenha(id) {
    const formData = new URLSearchParams();
    formData.append('action', 'forcar_mudanca_senha');
    formData.append('id', id);
    
    try {
        const response = await fetch(window.location.href, { 
            method: 'POST', 
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: formData 
        });
        const data = await response.json();
        mostrarNotificacao(data.message, data.success ? 'sucesso' : 'erro');
    } catch(e) { 
        mostrarNotificacao('Erro ao processar', 'erro'); 
    }
}

// ============================================
// FILTROS E BUSCA
// ============================================
function aplicarFiltros() {
    const busca = document.getElementById('campoBusca').value.toLowerCase();
    const cargo = document.getElementById('filtroCargo').value;
    const estado = document.getElementById('filtroEstado').value;
    let visiveis = 0;
    
    document.querySelectorAll('#corpoTabelaUtilizadores tr').forEach(row => {
        const nome = (row.cells[2]?.textContent || '').toLowerCase();
        const email = (row.cells[3]?.textContent || '').toLowerCase();
        const rowCargo = row.dataset.cargo;
        const rowEstado = row.dataset.estado;
        
        const ok = (!busca || nome.includes(busca) || email.includes(busca)) 
                && (!cargo || rowCargo === cargo) 
                && (!estado || rowEstado === estado);
        
        row.style.display = ok ? '' : 'none';
        if (ok) visiveis++;
    });
    
    document.getElementById('contadorUtilizadores').textContent = `(${visiveis} utilizadores)`;
    
    utilizadoresSelecionados = [];
    document.querySelectorAll('#corpoTabelaUtilizadores .checkbox-linha').forEach(cb => cb.checked = false);
    document.getElementById('selecionarTodos').checked = false;
    atualizarSelecaoMassa();
}

function atualizarSelecaoMassa() {
    const temSelecao = utilizadoresSelecionados.length > 0;
    document.getElementById('btnReenviarCredenciais').disabled = !temSelecao;
    document.getElementById('btnForcarMudancaSenha').disabled = !temSelecao;
}

// ============================================
// EVENT LISTENERS
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    // Selecionar todos
    document.getElementById('selecionarTodos')?.addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('#corpoTabelaUtilizadores .checkbox-linha');
        utilizadoresSelecionados = [];
        checkboxes.forEach(cb => {
            const row = cb.closest('tr');
            if (row && row.style.display !== 'none') {
                cb.checked = this.checked;
                if (this.checked) utilizadoresSelecionados.push(parseInt(cb.dataset.id));
            }
        });
        atualizarSelecaoMassa();
    });
    
    // Checkbox individual
    document.getElementById('corpoTabelaUtilizadores')?.addEventListener('change', function(e) {
        if (!e.target.classList.contains('checkbox-linha')) return;
        const id = parseInt(e.target.dataset.id);
        if (e.target.checked) { 
            if (!utilizadoresSelecionados.includes(id)) utilizadoresSelecionados.push(id); 
        } else { 
            utilizadoresSelecionados = utilizadoresSelecionados.filter(i => i !== id); 
            document.getElementById('selecionarTodos').checked = false; 
        }
        atualizarSelecaoMassa();
    });
    
    // Clique na linha para editar
    document.getElementById('corpoTabelaUtilizadores')?.addEventListener('click', function(e) {
        if (e.target.type === 'checkbox') return;
        if (e.target.closest('.checkbox-coluna')) return;
        if (e.target.closest('.acoes-coluna')) return;
        const row = e.target.closest('tr');
        if (row && row.dataset.id) abrirEdicao(row.dataset.id);
    });
    
    // Botões de ação em massa
    document.getElementById('btnReenviarCredenciais')?.addEventListener('click', async function() {
        if (utilizadoresSelecionados.length === 0) { 
            mostrarNotificacao('Selecione pelo menos um utilizador.', 'aviso'); 
            return; 
        }
        const ids = utilizadoresSelecionados.filter(id => id !== usuarioLogadoId);
        if (ids.length === 0) { 
            mostrarNotificacao('Não pode reenviar para sua própria conta.', 'aviso'); 
            return; 
        }
        if (!confirm(`Reenviar credenciais para ${ids.length} utilizador(es)?`)) return;
        for (const id of ids) await reenviarCredenciais(id);
    });
    
    document.getElementById('btnForcarMudancaSenha')?.addEventListener('click', async function() {
        if (utilizadoresSelecionados.length === 0) { 
            mostrarNotificacao('Selecione pelo menos um utilizador.', 'aviso'); 
            return; 
        }
        if (!confirm(`Forçar mudança de senha para ${utilizadoresSelecionados.length} utilizador(es)?`)) return;
        for (const id of utilizadoresSelecionados) await forcarMudancaSenha(id);
    });
    
    // Exportar
    document.getElementById('btnExportarLista')?.addEventListener('click', function() {
        const lista = [];
        document.querySelectorAll('#corpoTabelaUtilizadores tr').forEach(row => {
            if (row.style.display !== 'none') {
                lista.push({
                    nome: row.cells[2]?.textContent || '',
                    email: row.cells[3]?.textContent || '',
                    nivel: row.cells[4]?.textContent || '',
                    estado: row.cells[5]?.textContent || ''
                });
            }
        });
        const cabecalho = ['Nome', 'Email', 'Cargo', 'Estado'];
        const linhas = [cabecalho, ...lista.map(u => [u.nome, u.email, u.nivel, u.estado])];
        const csv = linhas.map(r => r.map(v => `"${String(v).replace(/"/g, '""')}"`).join(',')).join('\n');
        const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8' });
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = `utilizadores_${new Date().toISOString().slice(0,10)}.csv`;
        a.click();
        mostrarNotificacao('Lista exportada!', 'sucesso');
    });
    
    // Importar
    document.getElementById('btnImportarLista')?.addEventListener('click', function() {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = '.csv';
        input.onchange = async (e) => {
            const file = e.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = async (evt) => {
                const linhas = evt.target.result.split('\n');
                const headers = linhas[0].split(',').map(h => h.replace(/"/g, '').trim());
                const nomeIdx = headers.findIndex(h => h.toLowerCase() === 'nome');
                const emailIdx = headers.findIndex(h => h.toLowerCase() === 'email');
                if (nomeIdx === -1 || emailIdx === -1) { 
                    mostrarNotificacao('CSV inválido. Necessário colunas: Nome, Email', 'erro'); 
                    return; 
                }
                const dadosImportar = [];
                for (let i = 1; i < linhas.length; i++) {
                    const vals = linhas[i].split(',').map(v => v.replace(/"/g, '').trim());
                    const nome = vals[nomeIdx];
                    const email = vals[emailIdx];
                    if (!nome || !email) continue;
                    if (utilizadoresData.some(u => u.email === email)) continue;
                    dadosImportar.push({ nome, email });
                }
                if (dadosImportar.length === 0) { 
                    mostrarNotificacao('Nenhum utilizador novo para importar.', 'info'); 
                    return; 
                }
                const formData = new URLSearchParams();
                formData.append('action', 'importar_utilizadores');
                formData.append('dados', JSON.stringify(dadosImportar));
                try {
                    const response = await fetch(window.location.href, { 
                        method: 'POST', 
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: formData 
                    });
                    const data = await response.json();
                    mostrarNotificacao(data.message, data.success ? 'sucesso' : 'erro');
                    if (data.success) setTimeout(() => location.reload(), 1500);
                } catch(err) { 
                    mostrarNotificacao('Erro ao importar', 'erro'); 
                }
            };
            reader.readAsText(file, 'UTF-8');
        };
        input.click();
    });
    
    // Filtros
    document.getElementById('campoBusca')?.addEventListener('input', aplicarFiltros);
    document.getElementById('filtroCargo')?.addEventListener('change', aplicarFiltros);
    document.getElementById('filtroEstado')?.addEventListener('change', aplicarFiltros);
    document.getElementById('filtroOrdenar')?.addEventListener('change', aplicarFiltros);
    
    // Avatares
    const opcoesAvatarContainer = document.getElementById('opcoesAvatar');
    const iconesAvatar = ['fa-user', 'fa-user-tie', 'fa-user-graduate', 'fa-user-edit', 'fa-user-cog', 'fa-user-shield'];
    iconesAvatar.forEach(icone => {
        const div = document.createElement('div');
        div.className = `opcao-avatar ${icone === 'fa-user' ? 'selecionado' : ''}`;
        div.dataset.icone = icone;
        div.innerHTML = `<i class="fas ${icone}"></i>`;
        div.addEventListener('click', function() {
            document.querySelectorAll('.opcao-avatar').forEach(o => o.classList.remove('selecionado'));
            this.classList.add('selecionado');
            avatarSelecionado = this.dataset.icone;
            if (!fotoUploadUrl) document.getElementById('previewAvatar').innerHTML = `<i class="fas ${avatarSelecionado}"></i>`;
        });
        opcoesAvatarContainer.appendChild(div);
    });
    
    // Cards de nível
    document.querySelectorAll('.card-nivel').forEach(card => {
        card.addEventListener('click', function() {
            document.querySelectorAll('.card-nivel').forEach(c => c.classList.remove('selecionado'));
            this.classList.add('selecionado');
            this.querySelector('input').checked = true;
        });
    });
    
    // Botões do modal
    document.getElementById('btnNovoUtilizador')?.addEventListener('click', abrirModalNovo);
    document.getElementById('btnFecharModal')?.addEventListener('click', fecharModal);
    document.getElementById('btnVoltar')?.addEventListener('click', () => { if (passoAtual > 1) irParaPasso(passoAtual - 1); });
    document.getElementById('btnAvancar')?.addEventListener('click', () => { if (passoAtual < totalPassos) irParaPasso(passoAtual + 1); });
    document.getElementById('btnEnviar')?.addEventListener('click', salvarUtilizador);
    document.getElementById('btnGerarSenha')?.addEventListener('click', gerarSenha);
    document.getElementById('uploadFotoInput')?.addEventListener('change', function(e) { if (e.target.files.length) uploadFoto(e.target.files[0]); });
    
    // Navegação por passos
    document.querySelectorAll('.item-passo').forEach(step => { 
        step.addEventListener('click', () => irParaPasso(parseInt(step.dataset.passo))); 
    });
    
    // Fechar modal com clique fora
    document.getElementById('modalNovoUtilizador')?.addEventListener('click', e => { 
        if (e.target === document.getElementById('modalNovoUtilizador')) fecharModal(); 
    });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') fecharModal(); });
    
    // Inicializar
    aplicarFiltros();
    atualizarSelecaoMassa();
});
</script>
</body>
</html>
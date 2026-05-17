<?php
/**
 * Inscrições - Área Restrita IPIKK
 * Gestão completa de inscrições e matrículas
 */

$titulo_pagina = 'Gestão de Inscrições';
$css_especifico = 'admin-inscricoes.css';

$base_path = dirname(__DIR__);
require_once $base_path . '/config/index.php';

// Verificar autenticação
if (!isset($_SESSION['utilizador_id'])) {
    header('Location: area-restrita.php');
    exit;
}


require_once __DIR__ . '/includes/verificar-permissao.php';

// ===== VERIFICAR PERMISSÃO ESPECÍFICA =====
verificarPermissao('inscricoes');

$db = getDB();

// Buscar dados do usuário logado
$stmt = $db->prepare("SELECT id, nome, email, foto_url FROM utilizadores WHERE id = ?");
$stmt->execute([$_SESSION['utilizador_id']]);
$usuario_logado = $stmt->fetch();

if (!$usuario_logado) {
    session_destroy();
    header('Location: area-restrita.php');
    exit;
}

// Buscar configurações do site
$config = $db->query("SELECT * FROM configuracoes WHERE id = 1")->fetch();

// ============================================
// BUSCAR DADOS DAS TABELAS DE INSCRIÇÕES
// ============================================

// Tabela: controle_inscricoes
$stmt = $db->query("SELECT * FROM controle_inscricoes WHERE id = 1");
$controle = $stmt->fetch();

if (!$controle) {
    $db->exec("INSERT INTO controle_inscricoes (id, status, modo, data_abertura, data_encerramento) VALUES (1, 'fechadas', 'manual', NULL, NULL)");
    $stmt = $db->query("SELECT * FROM controle_inscricoes WHERE id = 1");
    $controle = $stmt->fetch();
}

// Tabela: conteudo_inscricoes
$stmt = $db->query("SELECT * FROM conteudo_inscricoes WHERE id = 1");
$conteudo = $stmt->fetch();

if (!$conteudo) {
    $db->exec("INSERT INTO conteudo_inscricoes (id) VALUES (1)");
    $stmt = $db->query("SELECT * FROM conteudo_inscricoes WHERE id = 1");
    $conteudo = $stmt->fetch();
}

// Decodificar JSONs
$documentos = json_decode($conteudo['documentos'] ?? '[]', true);
$passos_inscricao = json_decode($conteudo['passos_inscricao'] ?? '[]', true);
$passos_matricula = json_decode($conteudo['passos_matricula'] ?? '[]', true);
$cards_matricula = json_decode($conteudo['cards_matricula'] ?? '[]', true);
$info_importantes = json_decode($conteudo['info_importantes'] ?? '[]', true);
$vagas_curso = json_decode($conteudo['vagas_curso'] ?? '[]', true);

// Buscar cursos para o seletor de vagas
$cursos = $db->query("SELECT id, nome FROM cursos WHERE estado = 'ativo' ORDER BY nome")->fetchAll();

// Buscar vagas atuais
$ano_lectivo_atual = $config['ano_lectivo_atual'] ?? (date('Y') . '/' . (date('Y') + 1));
$stmt = $db->prepare("
    SELECT v.*, c.nome as curso_nome 
    FROM vagas_curso v
    JOIN cursos c ON c.id = v.curso_id
    WHERE v.ano_lectivo = ?
    ORDER BY c.nome
");
$stmt->execute([$ano_lectivo_atual]);
$vagas_atual = $stmt->fetchAll();

// ============================================
// PROCESSAMENTO DE FORMULÁRIOS (AJAX)
// ============================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    header('Content-Type: application/json');
    
    if ($action === 'salvar_controle') {
        $status = $_POST['status'] ?? 'fechadas';
        $modo = $_POST['modo'] ?? 'manual';
        $data_abertura = $_POST['data_abertura'] ?: null;
        $data_encerramento = $_POST['data_encerramento'] ?: null;
        $ano_lectivo = trim($_POST['ano_lectivo'] ?? '');
        if ($ano_lectivo === '') {
            $ano_lectivo = date('Y') . '/' . (date('Y') + 1);
        }
        
        $stmt = $db->prepare("UPDATE controle_inscricoes SET status = ?, modo = ?, data_abertura = ?, data_encerramento = ? WHERE id = 1");
        if ($stmt->execute([$status, $modo, $data_abertura, $data_encerramento])) {
            $stmtConfig = $db->prepare("UPDATE configuracoes SET ano_lectivo_atual = ? WHERE id = 1");
            $stmtConfig->execute([$ano_lectivo]);
            echo json_encode(['success' => true, 'message' => 'Configurações de controle salvas com sucesso!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao salvar configurações.']);
        }
        exit;
    }
    
    if ($action === 'salvar_conteudo') {
        $titulo_disponivel = $_POST['titulo_disponivel'] ?? '';
        $msg_abertura = $_POST['msg_abertura'] ?? '';
        $documentos = json_decode($_POST['documentos'] ?? '[]', true);
        $passos_inscricao = json_decode($_POST['passos_inscricao'] ?? '[]', true);
        $titulo_indisponivel = $_POST['titulo_indisponivel'] ?? '';
        $msg_indisponivel = $_POST['msg_indisponivel'] ?? '';
        $texto_info_indisponivel = $_POST['texto_info_indisponivel'] ?? '';
        $proximo_periodo = $_POST['proximo_periodo'] ?? '';
        $titulo_matricula = $_POST['titulo_matricula'] ?? '';
        $descricao_matricula = $_POST['descricao_matricula'] ?? '';
        $passos_matricula = json_decode($_POST['passos_matricula'] ?? '[]', true);
        $cards_matricula = json_decode($_POST['cards_matricula'] ?? '[]', true);
        $info_importantes = json_decode($_POST['info_importantes'] ?? '[]', true);
        $texto_cartao_estudante = $_POST['texto_cartao_estudante'] ?? '';
        $mensagem_cartao_destaque = $_POST['mensagem_cartao_destaque'] ?? '';
        $contacto_telefone = $_POST['contacto_telefone'] ?? '';
        $contacto_email = $_POST['contacto_email'] ?? '';
        $contacto_horario = $_POST['contacto_horario'] ?? '';
        $contacto_endereco = $_POST['contacto_endereco'] ?? '';
        
        $stmt = $db->prepare("UPDATE conteudo_inscricoes SET 
            titulo_disponivel = ?, msg_abertura = ?, documentos = ?, passos_inscricao = ?,
            titulo_indisponivel = ?,
            msg_indisponivel = ?, texto_info_indisponivel = ?, proximo_periodo = ?,
            titulo_matricula = ?, descricao_matricula = ?, passos_matricula = ?,
            cards_matricula = ?, info_importantes = ?, texto_cartao_estudante = ?,
            mensagem_cartao_destaque = ?, contacto_telefone = ?, contacto_email = ?,
            contacto_horario = ?, contacto_endereco = ?
            WHERE id = 1");
        
        $success = $stmt->execute([
            $titulo_disponivel, $msg_abertura, json_encode($documentos, JSON_UNESCAPED_UNICODE),
            json_encode($passos_inscricao, JSON_UNESCAPED_UNICODE), $titulo_indisponivel, $msg_indisponivel, $texto_info_indisponivel,
            $proximo_periodo, $titulo_matricula, $descricao_matricula,
            json_encode($passos_matricula, JSON_UNESCAPED_UNICODE),
            json_encode($cards_matricula, JSON_UNESCAPED_UNICODE),
            json_encode($info_importantes, JSON_UNESCAPED_UNICODE),
            $texto_cartao_estudante, $mensagem_cartao_destaque, $contacto_telefone, $contacto_email,
            $contacto_horario, $contacto_endereco
        ]);
        
        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Conteúdo salvo com sucesso!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao salvar conteúdo.']);
        }
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Área Restrita - Gestão de Inscrições</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="<?= $config['favicon_url'] ?? '../area-publica/foto/ipikk_new_logo.png' ?>" rel="icon">
    <link rel="stylesheet" href="css/admin-sidebar-header.css">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --azul-primario: #003072;
            --verde-acento: #0a9396;
            --cinza-claro: #f5f7fa;
            --cinza-medio: #e0e4e8;
            --cinza-escuro: #2c3e50;
            --branco: #fff;
            --sucesso: #28a745;
            --perigo: #dc3545;
            --aviso: #ffc107;
            --info: #17a2b8;
            --sombra: 0 2px 10px rgba(0,0,0,0.1);
            --sombra-forte: 0 5px 20px rgba(0,0,0,0.15);
            --transicao: all 0.3s ease;
            --borda-arredondada: 12px;
            --largura-sidebar: 280px;
            --altura-topo: 70px;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            font-size: 16px;
            line-height: 1.6;
            color: var(--cinza-escuro);
            background-color: var(--cinza-claro);
        }

        .btn-sair {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 12px;
            background: rgba(220,53,69,0.2);
            border: 1px solid rgba(220,53,69,0.4);
            border-radius: var(--borda-arredondada);
            color: var(--branco);
            text-decoration: none;
            transition: var(--transicao);
        }

        .btn-sair:hover { background: var(--perigo); transform: translateY(-2px); }

        .conteudo-principal { margin-left: var(--largura-sidebar); min-height: 100vh; }
        
        .barra-topo {
            height: var(--altura-topo); background: var(--branco);
            box-shadow: var(--sombra); display: flex; align-items: center;
            justify-content: space-between; padding: 0 30px;
            position: sticky; top: 0; z-index: 999;
        }
        
        .barra-topo h1 { font-size: 24px; color: var(--azul-primario); margin: 0; }
        
        .botao-menu-mobile {
            display: none;
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--azul-primario);
            padding: 10px;
        }
        
        .btn-primario {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: linear-gradient(135deg, var(--verde-acento), var(--azul-primario));
            color: var(--branco);
            border: none;
            border-radius: var(--borda-arredondada);
            font-weight: 600;
            cursor: pointer;
        }

        .btn-guardar {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 25px;
            background: linear-gradient(135deg, var(--verde-acento), var(--azul-primario));
            color: var(--branco);
            border: none;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transicao);
            box-shadow: var(--sombra);
        }

        .btn-guardar:hover {
            transform: translateY(-2px);
            box-shadow: var(--sombra-forte);
        }

        .conteudo { padding: 30px; }

        .card {
            background: var(--branco);
            border-radius: var(--borda-arredondada);
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: var(--sombra);
        }

        .card h3 {
            font-size: 1.2rem;
            margin-bottom: 20px;
            color: var(--azul-primario);
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 12px;
            border-bottom: 2px solid var(--cinza-medio);
        }

        .status-indicador {
            text-align: center;
            padding: 20px;
            border-radius: var(--borda-arredondada);
            font-size: 1.2rem;
            font-weight: 600;
        }

        .status-abertas {
            background: #e8f5e9;
            color: var(--sucesso);
            border-left: 5px solid var(--sucesso);
        }

        .status-fechadas {
            background: #fff0f0;
            color: var(--perigo);
            border-left: 5px solid var(--perigo);
        }

        .status-agendadas {
            background: #fff3cd;
            color: #d39e00;
            border-left: 5px solid #ffc107;
        }

        .opcoes-modo {
            display: flex;
            gap: 30px;
            margin-bottom: 25px;
            padding: 15px;
            background: var(--cinza-claro);
            border-radius: var(--borda-arredondada);
        }

        .opcoes-modo label {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            font-weight: 500;
        }

        .secao-manual {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 15px;
        }

        .btn-abrir, .btn-fechar {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 30px;
            border: none;
            border-radius: 40px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transicao);
        }

        .btn-abrir {
            background: linear-gradient(135deg, var(--sucesso), #1e7e34);
            color: var(--branco);
        }

        .btn-fechar {
            background: linear-gradient(135deg, var(--perigo), #c82333);
            color: var(--branco);
        }

        .secao-agendado {
            display: none;
            margin-top: 15px;
        }

        .secao-agendado.ativo {
            display: block;
        }

        .linha-form {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 15px;
        }

        .grupo-form {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 15px;
        }

        .grupo-form label {
            font-size: 12px;
            font-weight: 700;
            color: var(--cinza-escuro);
            text-transform: uppercase;
        }

        .campo-form, .selecao-form, .area-texto {
            width: 100%;
            padding: 12px 14px;
            border: 2px solid var(--cinza-medio);
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            transition: var(--transicao);
            background: var(--branco);
        }

        .campo-form:focus, .selecao-form:focus, .area-texto:focus {
            outline: none;
            border-color: var(--verde-acento);
        }

        textarea.campo-form {
            resize: vertical;
            min-height: 100px;
        }

        .info-agendamento {
            background: var(--cinza-claro);
            padding: 12px;
            border-radius: 8px;
            font-size: 0.9rem;
            margin-top: 15px;
        }

        .abas-editor {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid var(--cinza-medio);
            flex-wrap: wrap;
        }

        .aba-edit {
            background: none;
            border: none;
            padding: 12px 24px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transicao);
            color: var(--cinza-escuro);
            border-radius: 8px 8px 0 0;
        }

        .aba-edit:hover {
            background: var(--cinza-claro);
            color: var(--verde-acento);
        }

        .aba-edit.ativa {
            background: var(--verde-acento);
            color: var(--branco);
        }

        .conteudo-edit {
            display: none;
        }

        .conteudo-edit.ativo {
            display: block;
        }

        .item-dinamico {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            background: var(--cinza-claro);
            border-radius: 8px;
            margin-bottom: 8px;
        }

        .item-dinamico input {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid var(--cinza-medio);
            border-radius: 6px;
            font-size: 14px;
        }

        .btn-remover {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--perigo);
            color: var(--branco);
            border: none;
            cursor: pointer;
        }

        .btn-adicionar {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: none;
            border: 2px dashed var(--verde-acento);
            border-radius: 8px;
            color: var(--verde-acento);
            font-weight: 600;
            cursor: pointer;
            margin-top: 5px;
        }

        .btn-preview {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 25px;
            background: var(--azul-claro);
            color: var(--branco);
            border: none;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-preview:hover {
            background: var(--azul-primario);
            transform: translateY(-2px);
        }

        .notificacao {
            position: fixed;
            bottom: 30px;
            right: 30px;
            padding: 15px 25px;
            border-radius: 12px;
            color: white;
            font-weight: 600;
            z-index: 10001;
            animation: slideInRight 0.3s ease;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        }

        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        .notificacao.sucesso { background: linear-gradient(135deg, #28a745, #1e7e34); }
        .notificacao.erro { background: linear-gradient(135deg, #dc3545, #c82333); }

        .info-vagas-status {
            background: var(--cinza-claro);
            padding: 10px;
            border-radius: 8px;
            font-size: 0.85rem;
            border-left: 4px solid var(--verde-acento);
            margin-bottom: 15px;
        }

        /* Modal de pré-visualização com iframe */
        .modal-preview-iframe {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,30,70,0.85);
            backdrop-filter: blur(6px);
            z-index: 10001;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal-preview-iframe.ativo {
            display: flex;
        }

        .modal-preview-iframe .conteudo-modal {
            background: var(--branco);
            border-radius: 16px;
            max-width: 1200px;
            width: 95%;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            box-shadow: 0 25px 60px rgba(0,30,70,0.35);
        }

        .modal-preview-iframe .cabecalho-modal {
            background: linear-gradient(135deg, var(--azul-primario) 0%, #0a4da8 50%, var(--verde-acento) 100%);
            padding: 25px 30px;
            border-radius: 16px 16px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-preview-iframe .esquerda-cabecalho {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .modal-preview-iframe .icone-cabecalho {
            width: 48px;
            height: 48px;
            background: rgba(255,255,255,0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .modal-preview-iframe .texto-cabecalho h2 {
            color: var(--branco);
            font-size: 20px;
            margin: 0;
        }

        .modal-preview-iframe .texto-cabecalho p {
            color: rgba(255,255,255,0.75);
            font-size: 12px;
            margin: 0;
        }

        .modal-preview-iframe .btn-fechar {
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.3);
            color: var(--branco);
            width: 36px;
            height: 36px;
            border-radius: 50%;
            cursor: pointer;
        }

        .modal-preview-iframe .corpo-modal {
            padding: 0;
            height: calc(90vh - 80px);
            overflow: hidden;
        }

        .modal-preview-iframe iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

        .modal-preview-iframe .acoes-form {
            display: flex;
            justify-content: center;
            gap: 15px;
            padding: 15px;
            border-top: 1px solid var(--cinza-medio);
        }

        @media (max-width: 768px) {
            .conteudo-principal { margin-left: 0; }
            .botao-menu-mobile { display: block; }
            .linha-form { grid-template-columns: 1fr; }
            .opcoes-modo { flex-direction: column; gap: 10px; }
            .secao-manual { flex-direction: column; }
            .btn-abrir, .btn-fechar { justify-content: center; }
        }
    </style>
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<main class="conteudo-principal">
    <header class="barra-superior">
        <div class="esquerda-barra">
            <button class="botao-menu-mobile" id="botaoMenuMobile">
                <i class="fas fa-bars"></i>
            </button>
            <h1 class="titulo-pagina"><i class="fas fa-file-signature"></i> Inscrições</h1>
        </div>
        <div class="direita-barra">
        <button class="btn-guardar" onclick="guardarTudo()">
            <i class="fas fa-save"></i> Guardar Alterações
        </button>
        </div>
    </header>

    <div class="conteudo-pagina">
        <div class="card">
            <h3><i class="fas fa-info-circle"></i> Status das Inscrições</h3>
            <div class="status-indicador" id="statusIndicador"></div>
        </div>

        <div class="card">
            <h3><i class="fas fa-cog"></i> Modo de Controle</h3>
            
            <div class="opcoes-modo">
                <label>
                    <input type="radio" name="modo" value="manual" id="modoManual"> 
                    <i class="fas fa-hand-pointer"></i> Manual
                </label>
                <label>
                    <input type="radio" name="modo" value="agendado" id="modoAgendado"> 
                    <i class="fas fa-calendar-alt"></i> Agendado
                </label>
            </div>

            <div id="controleManual" class="secao-manual">
                <button class="btn-abrir" onclick="abrirInscricoesManual()">
                    <i class="fas fa-calendar-plus"></i> Abrir Inscrições
                </button>
                <button class="btn-fechar" onclick="fecharInscricoesManual()">
                    <i class="fas fa-calendar-times"></i> Fechar Inscrições
                </button>
            </div>

            <div id="controleAgendado" class="secao-agendado">
                <div class="linha-form">
                    <div class="grupo-form">
                        <label><i class="fas fa-calendar-check"></i> Data de Abertura</label>
                        <input type="datetime-local" id="dataAbertura" class="campo-form">
                    </div>
                    <div class="grupo-form">
                        <label><i class="fas fa-calendar-times"></i> Data de Encerramento</label>
                        <input type="datetime-local" id="dataEncerramento" class="campo-form">
                    </div>
                </div>
                <div class="info-agendamento" id="infoAgendamento"></div>
            </div>
        </div>

        <div class="card">
            <h3><i class="fas fa-edit"></i> Conteúdo das Páginas</h3>
            
            <div class="abas-editor" id="abasEditor">
                <button class="aba-edit ativa" data-aba="disponivel">
                    <i class="fas fa-check-circle"></i> Página Disponível
                </button>
                <button class="aba-edit" data-aba="indisponivel">
                    <i class="fas fa-ban"></i> Página Indisponível
                </button>
                <button class="aba-edit" data-aba="matricula">
                    <i class="fas fa-user-plus"></i> Matrícula
                </button>
            </div>

            <!-- ABA DISPONÍVEL -->
            <div id="conteudoDisponivel" class="conteudo-edit ativo">
                <div class="grupo-form">
                    <label><i class="fas fa-heading"></i> Título da Página</label>
                    <input type="text" id="tituloDisponivel" class="campo-form" value="<?= htmlspecialchars($conteudo['titulo_disponivel'] ?? 'Processo de Inscrição') ?>">
                </div>
                <div class="grupo-form">
                    <label><i class="fas fa-info-circle"></i> Mensagem de Abertura</label>
                    <textarea id="msgAbertura" class="campo-form" rows="3"><?= htmlspecialchars($conteudo['msg_abertura'] ?? '') ?></textarea>
                </div>
                <div class="grupo-form">
                    <label><i class="fas fa-file-alt"></i> Documentos Necessários</label>
                    <div id="listaDocumentos" class="lista-dinamica"></div>
                    <button type="button" class="btn-adicionar" onclick="adicionarDocumento()">
                        <i class="fas fa-plus"></i> Adicionar Documento
                    </button>
                </div>
                <div class="grupo-form">
                    <label><i class="fas fa-stairs"></i> Passo a Passo da Inscrição</label>
                    <div id="listaPassos" class="lista-dinamica"></div>
                    <button type="button" class="btn-adicionar" onclick="adicionarPasso()">
                        <i class="fas fa-plus"></i> Adicionar Passo
                    </button>
                </div>
                <div class="grupo-form">
                    <label><i class="fas fa-graduation-cap"></i> Vagas por Curso (Ano lectivo)</label>
                    <div class="linha-form">
                        <input type="text" id="anoLectivoVagas" class="campo-form" value="<?= htmlspecialchars($ano_lectivo_atual) ?>" placeholder="Ex: 2026/2027">
                    </div>
                    <div class="info-vagas-status" id="infoVagasStatus"></div>
                    <div id="listaVagasCurso" class="lista-dinamica"></div>
                    <button type="button" class="btn-adicionar" onclick="adicionarVagaCurso()">
                        <i class="fas fa-plus"></i> Adicionar Vaga por Curso
                    </button>
                </div>
            </div>

            <!-- ABA INDISPONÍVEL -->
            <div id="conteudoIndisponivel" class="conteudo-edit">
                <div class="grupo-form">
                    <label><i class="fas fa-heading"></i> Título da Página</label>
                    <input type="text" id="tituloIndisponivel" class="campo-form" value="<?= htmlspecialchars($conteudo['titulo_indisponivel'] ?? 'Inscrições Indisponíveis') ?>">
                </div>
                <div class="grupo-form">
                    <label><i class="fas fa-info-circle"></i> Mensagem Principal</label>
                    <textarea id="msgIndisponivel" class="campo-form" rows="3"><?= htmlspecialchars($conteudo['msg_indisponivel'] ?? '') ?></textarea>
                </div>
                <div class="grupo-form">
                    <label><i class="fas fa-info-circle"></i> Texto Informativo</label>
                    <textarea id="textoInfoIndisponivel" class="campo-form" rows="6"><?= htmlspecialchars($conteudo['texto_info_indisponivel'] ?? '') ?></textarea>
                </div>
                <div class="grupo-form">
                    <label><i class="fas fa-calendar-alt"></i> Próximo Período (Texto)</label>
                    <input type="text" id="proximoPeriodo" class="campo-form" value="<?= htmlspecialchars($conteudo['proximo_periodo'] ?? '') ?>">
                </div>
            </div>

            <!-- ABA MATRÍCULA -->
            <div id="conteudoMatricula" class="conteudo-edit">
                <div class="grupo-form">
                    <label><i class="fas fa-heading"></i> Título da Página</label>
                    <input type="text" id="tituloMatricula" class="campo-form" value="<?= htmlspecialchars($conteudo['titulo_matricula'] ?? 'Processo de Matrícula') ?>">
                </div>
                <div class="grupo-form">
                    <label><i class="fas fa-align-left"></i> Descrição</label>
                    <textarea id="descricaoMatricula" class="campo-form" rows="3"><?= htmlspecialchars($conteudo['descricao_matricula'] ?? '') ?></textarea>
                </div>
                <div class="grupo-form">
                    <label><i class="fas fa-stairs"></i> Passo a Passo da Matrícula</label>
                    <div id="listaPassosMatricula" class="lista-dinamica"></div>
                    <button type="button" class="btn-adicionar" onclick="adicionarPassoMatricula()">
                        <i class="fas fa-plus"></i> Adicionar Passo
                    </button>
                </div>
                <div class="grupo-form">
                    <label><i class="fas fa-layer-group"></i> Cards de Matrícula</label>
                    <div id="listaCardsMatricula" class="lista-dinamica"></div>
                    <button type="button" class="btn-adicionar" onclick="adicionarCardMatricula()">
                        <i class="fas fa-plus"></i> Adicionar Card
                    </button>
                </div>
                <div class="grupo-form">
                    <label><i class="fas fa-info-circle"></i> Informações Importantes</label>
                    <div id="listaInfoImportantes" class="lista-dinamica"></div>
                    <button type="button" class="btn-adicionar" onclick="adicionarInfoImportante()">
                        <i class="fas fa-plus"></i> Adicionar Informação
                    </button>
                </div>
                <div class="grupo-form">
                    <label><i class="fas fa-id-card"></i> Texto do Cartão de Estudante</label>
                    <textarea id="textoCartaoEstudante" class="campo-form" rows="4"><?= htmlspecialchars($conteudo['texto_cartao_estudante'] ?? '') ?></textarea>
                </div>
                <div class="grupo-form">
                    <label><i class="fas fa-star"></i> Mensagem do Cartão em Destaque</label>
                    <textarea id="mensagemCartaoDestaque" class="campo-form" rows="3"><?= htmlspecialchars($conteudo['mensagem_cartao_destaque'] ?? '') ?></textarea>
                </div>
            </div>

        </div>

        <div class="card">
            <h3><i class="fas fa-address-card"></i> Contactos da Secretaria</h3>
            <div class="linha-form">
                <div class="grupo-form">
                    <label><i class="fas fa-phone"></i> Telefone</label>
                    <input type="text" id="contactoTelefone" class="campo-form" value="<?= htmlspecialchars($conteudo['contacto_telefone'] ?? $config['telefone'] ?? '') ?>">
                </div>
                <div class="grupo-form">
                    <label><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" id="contactoEmail" class="campo-form" value="<?= htmlspecialchars($conteudo['contacto_email'] ?? $config['email_geral'] ?? '') ?>">
                </div>
            </div>
            <div class="linha-form">
                <div class="grupo-form">
                    <label><i class="fas fa-clock"></i> Horário de Atendimento</label>
                    <input type="text" id="contactoHorario" class="campo-form" value="<?= htmlspecialchars($conteudo['contacto_horario'] ?? $config['horario_funcionamento'] ?? '') ?>">
                </div>
                <div class="grupo-form">
                    <label><i class="fas fa-map-marker-alt"></i> Endereço</label>
                    <input type="text" id="contactoEndereco" class="campo-form" value="<?= htmlspecialchars($conteudo['contacto_endereco'] ?? $config['endereco_completo'] ?? '') ?>">
                </div>
            </div>
        </div>

       
    </div>
</main>

<!-- MODAL DE PRÉ-VISUALIZAÇÃO COM IFRAME -->
<div id="modalPreviewIframe" class="modal-preview-iframe">
    <div class="conteudo-modal">
        <div class="cabecalho-modal">
            <div class="esquerda-cabecalho">
                <div class="icone-cabecalho"><i class="fas fa-eye"></i></div>
                <div class="texto-cabecalho">
                    <h2 id="previewIframeTitulo">Pré-visualização</h2>
                    <p id="previewIframeSubtitulo">Página pública do site</p>
                </div>
            </div>
            <button class="btn-fechar" onclick="fecharPreviewIframe()"><i class="fas fa-times"></i></button>
        </div>
        <div class="corpo-modal">
            <iframe id="previewIframe" src="about:blank" title="Pré-visualização"></iframe>
        </div>
        <div class="acoes-form">
            <button class="btn btn-secundario" onclick="fecharPreviewIframe()">Fechar</button>
            <button class="btn btn-primario" id="btnAbrirNovaAbaPreview" onclick="abrirPreviewNovaAba()">
                <i class="fas fa-external-link-alt"></i> Abrir em nova aba
            </button>
        </div>
    </div>
</div>

<script src="js/admin-sidebar-header.js"></script>
<script>
    // Dados carregados do PHP
    const documentosIniciais = <?php echo json_encode($documentos, JSON_UNESCAPED_UNICODE); ?>;
    const passosIniciais = <?php echo json_encode($passos_inscricao, JSON_UNESCAPED_UNICODE); ?>;
    const passosMatriculaIniciais = <?php echo json_encode($passos_matricula, JSON_UNESCAPED_UNICODE); ?>;
    const cardsMatriculaIniciais = <?php echo json_encode($cards_matricula, JSON_UNESCAPED_UNICODE); ?>;
    const infoImportantesIniciais = <?php echo json_encode($info_importantes, JSON_UNESCAPED_UNICODE); ?>;
    const vagasIniciais = <?php echo json_encode($vagas_atual, JSON_UNESCAPED_UNICODE); ?>;
    const cursosList = <?php echo json_encode($cursos, JSON_UNESCAPED_UNICODE); ?>;
    
    let configInscricoes = {
        status: '<?= $controle['status'] ?? 'fechadas' ?>',
        modo: '<?= $controle['modo'] ?? 'manual' ?>',
        dataAbertura: '<?= $controle['data_abertura'] ?? '' ?>',
        dataEncerramento: '<?= $controle['data_encerramento'] ?? '' ?>',
        conteudoDisponivel: {
            documentos: documentosIniciais,
            passos: passosIniciais
        },
        conteudoMatricula: {
            passos: passosMatriculaIniciais,
            cards: cardsMatriculaIniciais,
            infoImportantes: infoImportantesIniciais
        },
        conteudoResultados: {
            vagasCurso: vagasIniciais
        }
    };
    
    let urlPreviewAtual = '';
    
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    function mostrarNotificacao(mensagem, tipo) {
        const notif = document.createElement('div');
        notif.className = `notificacao ${tipo}`;
        const icon = tipo === 'sucesso' ? 'fa-check-circle' : 'fa-exclamation-circle';
        notif.innerHTML = `<i class="fas ${icon}"></i> ${mensagem}`;
        document.body.appendChild(notif);
        setTimeout(() => notif.remove(), 3000);
    }
    
    function atualizarInterface() {
        const statusIndicador = document.getElementById('statusIndicador');
        const statusMap = {
            'abertas': '<i class="fas fa-check-circle"></i> INSCRIÇÕES ABERTAS',
            'fechadas': '<i class="fas fa-ban"></i> INSCRIÇÕES FECHADAS',
            'agendadas': '<i class="fas fa-calendar-alt"></i> INSCRIÇÕES AGENDADAS'
        };
        statusIndicador.innerHTML = statusMap[configInscricoes.status] || statusMap.fechadas;
        statusIndicador.className = `status-indicador status-${configInscricoes.status}`;
        
        document.getElementById('modoManual').checked = configInscricoes.modo === 'manual';
        document.getElementById('modoAgendado').checked = configInscricoes.modo === 'agendado';
        
        document.getElementById('controleManual').style.display = configInscricoes.modo === 'manual' ? 'flex' : 'none';
        const controleAgendado = document.getElementById('controleAgendado');
        if (configInscricoes.modo === 'agendado') {
            controleAgendado.classList.add('ativo');
        } else {
            controleAgendado.classList.remove('ativo');
        }
        
        document.getElementById('dataAbertura').value = configInscricoes.dataAbertura || '';
        document.getElementById('dataEncerramento').value = configInscricoes.dataEncerramento || '';
        
        renderizarLista('listaDocumentos', configInscricoes.conteudoDisponivel.documentos, 'documento');
        renderizarLista('listaPassos', configInscricoes.conteudoDisponivel.passos, 'passo');
        renderizarLista('listaPassosMatricula', configInscricoes.conteudoMatricula.passos, 'passoMatricula');
        renderizarLista('listaCardsMatricula', configInscricoes.conteudoMatricula.cards, 'cardMatricula');
        renderizarLista('listaInfoImportantes', configInscricoes.conteudoMatricula.infoImportantes, 'infoImportante');
        renderizarListaVagasCurso('listaVagasCurso', configInscricoes.conteudoResultados.vagasCurso);
        
        atualizarInfoVagasStatus();
    }
    
    function atualizarInfoVagasStatus() {
        const infoDiv = document.getElementById('infoVagasStatus');
        if (configInscricoes.status === 'abertas') {
            infoDiv.innerHTML = '<i class="fas fa-check-circle"></i>  INSCRIÇÕES ABERTAS - As vagas definidas abaixo serão exibidas no site público.';
            infoDiv.className = 'info-vagas-status';
        } else {
            infoDiv.innerHTML = '<i class="fas fa-lock"></i>  INSCRIÇÕES FECHADAS - As vagas não são exibidas no site público. Altere o status das inscrições para ABERTAS para divulgar as vagas.';
            infoDiv.className = 'info-vagas-status';
        }
    }
    
    function renderizarLista(containerId, itens, tipo) {
        const container = document.getElementById(containerId);
        if (!container) return;
        
        if (!itens || itens.length === 0) {
            container.innerHTML = '<p style="color: #999; padding: 10px;">Nenhum item adicionado.</p>';
            return;
        }
        
        container.innerHTML = itens.map((item, index) => `
            <div class="item-dinamico" data-index="${index}">
                <input type="text" value="${escapeHtml(typeof item === 'object' ? (item.titulo || item.icone || item) : item)}" data-tipo="${tipo}" data-index="${index}" onchange="atualizarItemLista(this, '${tipo}')">
                <button class="btn-remover" onclick="removerItemLista(${index}, '${tipo}')"><i class="fas fa-trash"></i></button>
            </div>
        `).join('');
    }
    
    function renderizarListaVagasCurso(containerId, vagas) {
        const container = document.getElementById(containerId);
        if (!container) return;
        
        if (!vagas || vagas.length === 0) {
            container.innerHTML = '<p style="color: #999; padding: 10px;">Nenhuma vaga definida.</p>';
            return;
        }
        
        container.innerHTML = vagas.map((vaga, index) => `
            <div class="item-dinamico" data-index="${index}">
                <select data-tipo="vagaCurso" data-index="${index}" onchange="atualizarVagaCurso(this, ${index})" style="flex:1; padding:8px 12px; border:1px solid #e0e4e8; border-radius:6px;">
                    <option value="">Selecione um curso</option>
                    ${cursosList.map(curso => `<option value="${curso.id}" ${vaga.curso_id == curso.id ? 'selected' : ''}>${escapeHtml(curso.nome)}</option>`).join('')}
                </select>
                <input type="number" style="width:100px;" placeholder="Vagas" value="${vaga.vagas_disponiveis || 0}" data-tipo="vagaCursoQtd" data-index="${index}" onchange="atualizarVagaCursoQtd(this, ${index})">
                <button class="btn-remover" onclick="removerVagaCurso(${index})"><i class="fas fa-trash"></i></button>
            </div>
        `).join('');
    }
    
    function atualizarItemLista(input, tipo) {
        const index = parseInt(input.dataset.index);
        const novoValor = input.value;
        
        if (tipo === 'documento' && configInscricoes.conteudoDisponivel.documentos[index]) {
            configInscricoes.conteudoDisponivel.documentos[index] = novoValor;
        } else if (tipo === 'passo' && configInscricoes.conteudoDisponivel.passos[index]) {
            configInscricoes.conteudoDisponivel.passos[index] = novoValor;
        } else if (tipo === 'passoMatricula' && configInscricoes.conteudoMatricula.passos[index]) {
            configInscricoes.conteudoMatricula.passos[index] = typeof configInscricoes.conteudoMatricula.passos[index] === 'object' 
                ? { ...configInscricoes.conteudoMatricula.passos[index], titulo: novoValor }
                : novoValor;
        } else if (tipo === 'cardMatricula' && configInscricoes.conteudoMatricula.cards[index]) {
            configInscricoes.conteudoMatricula.cards[index] = typeof configInscricoes.conteudoMatricula.cards[index] === 'object'
                ? { ...configInscricoes.conteudoMatricula.cards[index], titulo: novoValor }
                : novoValor;
        } else if (tipo === 'infoImportante' && configInscricoes.conteudoMatricula.infoImportantes[index]) {
            configInscricoes.conteudoMatricula.infoImportantes[index] = novoValor;
        }
    }
    
    function atualizarVagaCurso(select, index) {
        configInscricoes.conteudoResultados.vagasCurso[index].curso_id = select.value;
    }
    
    function atualizarVagaCursoQtd(input, index) {
        configInscricoes.conteudoResultados.vagasCurso[index].vagas_disponiveis = parseInt(input.value) || 0;
    }
    
    function adicionarDocumento() {
        configInscricoes.conteudoDisponivel.documentos.push('Novo Documento');
        renderizarLista('listaDocumentos', configInscricoes.conteudoDisponivel.documentos, 'documento');
    }
    
    function adicionarPasso() {
        configInscricoes.conteudoDisponivel.passos.push('Novo Passo');
        renderizarLista('listaPassos', configInscricoes.conteudoDisponivel.passos, 'passo');
    }
    
    function adicionarPassoMatricula() {
        configInscricoes.conteudoMatricula.passos.push({ titulo: 'Novo Passo', descricao: '' });
        renderizarLista('listaPassosMatricula', configInscricoes.conteudoMatricula.passos, 'passoMatricula');
    }
    
    function adicionarCardMatricula() {
        configInscricoes.conteudoMatricula.cards.push({ icone: 'fa-book', titulo: 'Novo Card', descricao: '' });
        renderizarLista('listaCardsMatricula', configInscricoes.conteudoMatricula.cards, 'cardMatricula');
    }
    
    function adicionarInfoImportante() {
        configInscricoes.conteudoMatricula.infoImportantes.push('Nova Informação Importante');
        renderizarLista('listaInfoImportantes', configInscricoes.conteudoMatricula.infoImportantes, 'infoImportante');
    }
    
    function adicionarVagaCurso() {
        configInscricoes.conteudoResultados.vagasCurso.push({ curso_id: '', vagas_disponiveis: 0 });
        renderizarListaVagasCurso('listaVagasCurso', configInscricoes.conteudoResultados.vagasCurso);
    }
    
    function removerItemLista(index, tipo) {
        if (tipo === 'documento') {
            configInscricoes.conteudoDisponivel.documentos.splice(index, 1);
            renderizarLista('listaDocumentos', configInscricoes.conteudoDisponivel.documentos, 'documento');
        } else if (tipo === 'passo') {
            configInscricoes.conteudoDisponivel.passos.splice(index, 1);
            renderizarLista('listaPassos', configInscricoes.conteudoDisponivel.passos, 'passo');
        } else if (tipo === 'passoMatricula') {
            configInscricoes.conteudoMatricula.passos.splice(index, 1);
            renderizarLista('listaPassosMatricula', configInscricoes.conteudoMatricula.passos, 'passoMatricula');
        } else if (tipo === 'cardMatricula') {
            configInscricoes.conteudoMatricula.cards.splice(index, 1);
            renderizarLista('listaCardsMatricula', configInscricoes.conteudoMatricula.cards, 'cardMatricula');
        } else if (tipo === 'infoImportante') {
            configInscricoes.conteudoMatricula.infoImportantes.splice(index, 1);
            renderizarLista('listaInfoImportantes', configInscricoes.conteudoMatricula.infoImportantes, 'infoImportante');
        }
    }
    
    function removerVagaCurso(index) {
        configInscricoes.conteudoResultados.vagasCurso.splice(index, 1);
        renderizarListaVagasCurso('listaVagasCurso', configInscricoes.conteudoResultados.vagasCurso);
    }
    
    async function guardarControle() {
        try {
            const response = await fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'salvar_controle',
                    status: configInscricoes.status,
                    modo: configInscricoes.modo,
                    data_abertura: configInscricoes.dataAbertura || '',
                    data_encerramento: configInscricoes.dataEncerramento || '',
                    ano_lectivo: (document.getElementById('anoLectivoVagas')?.value || '<?= $ano_lectivo_atual ?>').trim()
                })
            });
            const data = await response.json();
            mostrarNotificacao(data.message, data.success ? 'sucesso' : 'erro');
            if (data.success) atualizarInfoVagasStatus();
        } catch (error) {
            mostrarNotificacao('Erro ao guardar controle', 'erro');
        }
    }
    
    async function guardarConteudo() {
        const documentos = configInscricoes.conteudoDisponivel.documentos;
        const passos = configInscricoes.conteudoDisponivel.passos;
        const passosMatricula = configInscricoes.conteudoMatricula.passos;
        const cardsMatricula = configInscricoes.conteudoMatricula.cards;
        const infoImportantes = configInscricoes.conteudoMatricula.infoImportantes;
        
        const formData = new URLSearchParams();
        formData.append('action', 'salvar_conteudo');
        formData.append('titulo_disponivel', document.getElementById('tituloDisponivel')?.value || '');
        formData.append('msg_abertura', document.getElementById('msgAbertura')?.value || '');
        formData.append('documentos', JSON.stringify(documentos));
        formData.append('passos_inscricao', JSON.stringify(passos));
        formData.append('titulo_indisponivel', document.getElementById('tituloIndisponivel')?.value || '');
        formData.append('msg_indisponivel', document.getElementById('msgIndisponivel')?.value || '');
        formData.append('texto_info_indisponivel', document.getElementById('textoInfoIndisponivel')?.value || '');
        formData.append('proximo_periodo', document.getElementById('proximoPeriodo')?.value || '');
        formData.append('titulo_matricula', document.getElementById('tituloMatricula')?.value || '');
        formData.append('descricao_matricula', document.getElementById('descricaoMatricula')?.value || '');
        formData.append('passos_matricula', JSON.stringify(passosMatricula));
        formData.append('cards_matricula', JSON.stringify(cardsMatricula));
        formData.append('info_importantes', JSON.stringify(infoImportantes));
        formData.append('texto_cartao_estudante', document.getElementById('textoCartaoEstudante')?.value || '');
        formData.append('mensagem_cartao_destaque', document.getElementById('mensagemCartaoDestaque')?.value || '');
        formData.append('contacto_telefone', document.getElementById('contactoTelefone')?.value || '');
        formData.append('contacto_email', document.getElementById('contactoEmail')?.value || '');
        formData.append('contacto_horario', document.getElementById('contactoHorario')?.value || '');
        formData.append('contacto_endereco', document.getElementById('contactoEndereco')?.value || '');
        
        try {
            const response = await fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData
            });
            const data = await response.json();
            mostrarNotificacao(data.message, data.success ? 'sucesso' : 'erro');
        } catch (error) {
            mostrarNotificacao('Erro ao guardar conteúdo', 'erro');
        }
    }
    
    async function guardarVagas() {
        const vagasParaSalvar = configInscricoes.conteudoResultados.vagasCurso
            .filter(vaga => vaga.curso_id)
            .map(vaga => ({ curso_id: vaga.curso_id, vagas: vaga.vagas_disponiveis || 0 }));
        
        try {
            const response = await fetch('processos/processar-vagas.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'salvar_vagas',
                    vagas: JSON.stringify(vagasParaSalvar),
                    ano_lectivo: (document.getElementById('anoLectivoVagas')?.value || '<?= $ano_lectivo_atual ?>').trim()
                })
            });
            const data = await response.json();
            mostrarNotificacao(data.message, data.success ? 'sucesso' : 'erro');
        } catch (error) {
            mostrarNotificacao('Erro ao guardar vagas', 'erro');
        }
    }
    
    function guardarTudo() {
        guardarControle();
        guardarConteudo();
        guardarVagas();
    }
    
    function abrirInscricoesManual() {
        configInscricoes.status = 'abertas';
        configInscricoes.modo = 'manual';
        atualizarInterface();
        mostrarNotificacao('Inscrições abertas manualmente!', 'sucesso');
        guardarControle();
    }
    
    function fecharInscricoesManual() {
        configInscricoes.status = 'fechadas';
        configInscricoes.modo = 'manual';
        atualizarInterface();
        mostrarNotificacao('Inscrições fechadas manualmente!', 'sucesso');
        guardarControle();
    }
    
    // ========== PRÉ-VISUALIZAÇÃO EM MODAL ==========
    function previewPaginaModal(tipo, titulo) {
        let url;
        if (tipo === 'disponivel') {
            url = '../area-publica/inscricoes.php?preview=1';
        } else if (tipo === 'indisponivel') {
            url = '../area-publica/inscricoes-indisponiveis.php?preview=1';
        } else if (tipo === 'matricula') {
            url = '../area-publica/inscricoes.php?tab=matricula&preview=1';
        } else {
            url = '../area-publica/inscricoes.php?preview=1';
        }
        
        urlPreviewAtual = url;
        
        const modal = document.getElementById('modalPreviewIframe');
        const iframe = document.getElementById('previewIframe');
        const tituloEl = document.getElementById('previewIframeTitulo');
        
        tituloEl.textContent = titulo;
        iframe.src = url;
        
        modal.classList.add('ativo');
        document.body.style.overflow = 'hidden';
    }
    
    function fecharPreviewIframe() {
        const modal = document.getElementById('modalPreviewIframe');
        const iframe = document.getElementById('previewIframe');
        iframe.src = 'about:blank';
        modal.classList.remove('ativo');
        document.body.style.overflow = '';
        urlPreviewAtual = '';
    }
    
    function abrirPreviewNovaAba() {
        if (urlPreviewAtual) {
            window.open(urlPreviewAtual, '_blank');
        }
    }
    
    // Event Listeners
    document.getElementById('modoManual')?.addEventListener('change', function() {
        if (this.checked) {
            configInscricoes.modo = 'manual';
            atualizarInterface();
        }
    });
    
    document.getElementById('modoAgendado')?.addEventListener('change', function() {
        if (this.checked) {
            configInscricoes.modo = 'agendado';
            atualizarInterface();
        }
    });
    
    document.getElementById('dataAbertura')?.addEventListener('change', function() {
        if (configInscricoes.modo === 'agendado') {
            configInscricoes.dataAbertura = this.value;
            guardarControle();
        }
    });
    
    document.getElementById('dataEncerramento')?.addEventListener('change', function() {
        if (configInscricoes.modo === 'agendado') {
            configInscricoes.dataEncerramento = this.value;
            guardarControle();
        }
    });
    
    // Abas
    document.querySelectorAll('.aba-edit').forEach(aba => {
        aba.addEventListener('click', function() {
            document.querySelectorAll('.aba-edit').forEach(a => a.classList.remove('ativa'));
            document.querySelectorAll('.conteudo-edit').forEach(c => c.classList.remove('ativo'));
            this.classList.add('ativa');
            const abaId = this.dataset.aba;
            document.getElementById(`conteudo${abaId.charAt(0).toUpperCase() + abaId.slice(1)}`).classList.add('ativo');
        });
    });
    
    // Inicializar
    document.addEventListener('DOMContentLoaded', () => {
        atualizarInterface();
        
        // Verificação automática para modo agendado a cada minuto
        setInterval(() => {
            if (configInscricoes.modo === 'agendado' && configInscricoes.dataAbertura && configInscricoes.dataEncerramento) {
                const agora = new Date();
                const dataAbertura = new Date(configInscricoes.dataAbertura);
                const dataEncerramento = new Date(configInscricoes.dataEncerramento);
                
                if (agora >= dataAbertura && agora <= dataEncerramento && configInscricoes.status !== 'abertas') {
                    configInscricoes.status = 'abertas';
                    atualizarInterface();
                    guardarControle();
                    mostrarNotificacao('As inscrições foram abertas automaticamente!', 'info');
                } else if (agora > dataEncerramento && configInscricoes.status !== 'fechadas') {
                    configInscricoes.status = 'fechadas';
                    atualizarInterface();
                    guardarControle();
                    mostrarNotificacao('As inscrições foram encerradas automaticamente.', 'info');
                }
            }
        }, 60000);
    });
</script>
</body>
</html>
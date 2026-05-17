<?php
/**
 * Página Redefinir Senha - IPIKK
 * Recebe token via URL e permite definir nova senha
 */

require_once '../config/index.php';

// Buscar configurações do site
$config = getDB()->query("SELECT * FROM configuracoes WHERE id = 1")->fetch();

// Buscar áreas para o menu
$areas = getDB()->query("SELECT * FROM areas WHERE ativo = 1 ORDER BY ordem")->fetchAll();

// Buscar todos os cursos para os submenus
$todos_cursos = getDB()->query("SELECT * FROM cursos WHERE estado = 'ativo' ORDER BY nome")->fetchAll();
$cursos_por_area = [];
foreach ($todos_cursos as $curso_item) {
    $cursos_por_area[$curso_item['area_id']][] = $curso_item;
}

// Verificar status das inscrições
$status_inscricoes = getDB()->query("SELECT status FROM controle_inscricoes WHERE id = 1")->fetch();
$link_inscricao = ($status_inscricoes && $status_inscricoes['status'] === 'abertas') ? 'inscricoes.php' : 'inscricoes-indisponiveis.php';

// Buscar token da URL
$token = $_GET['token'] ?? '';

$token_valido = false;
$token_data = null;
$mensagem_erro = '';

// Verificar token
if (!empty($token)) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT t.*, u.id as utilizador_id, u.nome, u.email, u.nivel 
        FROM tokens_recuperacao t
        JOIN utilizadores u ON t.utilizador_id = u.id
        WHERE t.token = ? AND t.usado = 0 AND t.expiracao > NOW()
    ");
    $stmt->execute([$token]);
    $token_data = $stmt->fetch();
    
    if ($token_data) {
        $token_valido = true;
    } else {
        // Verificar se token existe mas está expirado ou já usado
        $stmt = $db->prepare("SELECT * FROM tokens_recuperacao WHERE token = ?");
        $stmt->execute([$token]);
        $token_existe = $stmt->fetch();
        
        if ($token_existe) {
            if ($token_existe['usado']) {
                $mensagem_erro = 'Este link de recuperação já foi utilizado. Solicite um novo link.';
            } else {
                $mensagem_erro = 'Este link de recuperação expirou. Solicite um novo link.';
            }
        } else {
            $mensagem_erro = 'Link de recuperação inválido. Verifique o link ou solicite um novo.';
        }
    }
}

// Processar formulário
$processar = isset($_GET['processar']) ? true : false;
$resposta = ['success' => false, 'message' => '', 'redirect' => false];

if ($processar && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDB();
    $input = json_decode(file_get_contents('php://input'), true);
    $token_post = trim($input['token'] ?? '');
    $nova_senha = $input['nova_senha'] ?? '';
    $confirmar_senha = $input['confirmar_senha'] ?? '';
    
    // Validar token novamente
    if (!empty($token_post)) {
        $stmt = $db->prepare("
            SELECT t.*, u.id as utilizador_id, u.nome, u.email, u.nivel 
            FROM tokens_recuperacao t
            JOIN utilizadores u ON t.utilizador_id = u.id
            WHERE t.token = ? AND t.usado = 0 AND t.expiracao > NOW()
        ");
        $stmt->execute([$token_post]);
        $token_verify = $stmt->fetch();
        
        if ($token_verify) {
            // Validar senha
            $erros = [];
            if (strlen($nova_senha) < 6) {
                $erros[] = 'A senha deve ter pelo menos 6 caracteres.';
            }
            if ($nova_senha !== $confirmar_senha) {
                $erros[] = 'As senhas não coincidem.';
            }
            
            if (empty($erros)) {
                try {
                    $db->beginTransaction();

                    // Atualizar senha
                    $nova_senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
                    $stmt = $db->prepare("UPDATE utilizadores SET senha = ?, forcar_alteracao_senha = 0 WHERE id = ?");
                    $stmt->execute([$nova_senha_hash, $token_verify['utilizador_id']]);
                    
                    // Marcar todos os tokens do utilizador como usados (anti-reutilização)
                    $stmt = $db->prepare("UPDATE tokens_recuperacao SET usado = 1 WHERE utilizador_id = ?");
                    $stmt->execute([$token_verify['utilizador_id']]);
                    
                    // Registrar log
                    $stmt = $db->prepare("INSERT INTO logs (utilizador_id, acao, tabela, detalhes) VALUES (?, 'redefiniu senha', 'utilizadores', 'Senha redefinida via link de recuperação')");
                    $stmt->execute([$token_verify['utilizador_id']]);

                    $db->commit();

                    $resposta = [
                        'success' => true, 
                        'message' => 'Senha redefinida com sucesso! Faça login com a sua nova senha.',
                        'redirect' => true
                    ];
                } catch (PDOException $e) {
                    if ($db->inTransaction()) {
                        $db->rollBack();
                    }
                    error_log("Erro ao redefinir senha: " . $e->getMessage());
                    $resposta = ['success' => false, 'message' => 'Erro ao redefinir senha. Tente novamente.'];
                }
            } else {
                $resposta = ['success' => false, 'message' => implode(' ', $erros)];
            }
        } else {
            $resposta = ['success' => false, 'message' => 'Link de recuperação inválido ou expirado.'];
        }
    } else {
        $resposta = ['success' => false, 'message' => 'Token inválido.'];
    }
    
    header('Content-Type: application/json');
    echo json_encode($resposta);
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IPIKK - Redefinir Senha</title>
    
    <!-- Fontes e Ícones -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <!-- Favicon -->
    <link href="<?= $config['favicon_url'] ?? 'foto/ipikk_new_logo.png' ?>" rel="icon">
    
    <!-- CSS Header/Footer Padrão -->
    <link rel="stylesheet" href="css/header-footer.css">
    
    <style>
        /* === VARIÁVEIS === */
        :root {
            --azul-principal: <?= $config['cor_primaria'] ?? '#003072' ?>;
            --azul-claro: <?= $config['cor_azul_claro'] ?? '#2e86c1' ?>;
            --azul-escuro: <?= $config['cor_azul_escuro'] ?? '#001a40' ?>;
            --verde-acento: <?= $config['cor_verde_acento'] ?? '#0a9396' ?>;
            --branco: #ffffff;
            --cinza-claro: #f8f9fa;
            --cinza: #6c757d;
            --cinza-escuro: #212529;
            --verde-sucesso: #38b000;
            --vermelho-erro: #e63946;
            --borda-raio: 12px;
            --sombra: 0 5px 20px rgba(0, 48, 114, 0.1);
            --transicao: all 0.3s ease;
        }

        /* === ESTILO PRINCIPAL PARA CENTRALIZAR === */
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .pagina-redefinir {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        /* === CONTENTOR PRINCIPAL === */
        .contentor-redefinir {
            max-width: 520px;
            width: 100%;
            background: var(--branco);
            border-radius: var(--borda-raio);
            padding: 50px 40px;
            box-shadow: var(--sombra);
            position: relative;
            overflow: hidden;
            animation: entradaSuave 0.6s ease forwards;
            opacity: 0;
        }

        .contentor-redefinir::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 150px;
            height: 150px;
            background: linear-gradient(135deg, rgba(0, 48, 114, 0.03), rgba(10, 147, 150, 0.03));
            border-radius: 0 0 0 150px;
            z-index: 0;
        }

        /* === CABEÇALHO === */
        .cabecalho-redefinir {
            text-align: center;
            margin-bottom: 35px;
            position: relative;
            z-index: 1;
        }

        .icone-senha {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--azul-principal), var(--verde-acento));
            border-radius: 50%;
            margin-bottom: 20px;
            box-shadow: 0 6px 20px rgba(0, 48, 114, 0.2);
        }

        .icone-senha i {
            font-size: 2.5rem;
            color: var(--branco);
        }

        .titulo-redefinir {
            font-size: 1.8rem;
            background: linear-gradient(135deg, var(--azul-principal), var(--verde-acento));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 15px;
            position: relative;
            display: inline-block;
        }

        .titulo-redefinir::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: linear-gradient(90deg, var(--azul-principal), var(--verde-acento));
            border-radius: 2px;
            animation: expandirLargura 1s ease-out forwards;
        }

        .subtitulo-redefinir {
            font-size: 0.95rem;
            color: var(--cinza);
            margin-top: 25px;
            line-height: 1.6;
        }

        /* === FORMULÁRIO === */
        form {
            position: relative;
            z-index: 1;
        }

        .grupo-campo {
            position: relative;
            margin-bottom: 25px;
        }

        .grupo-campo i {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--azul-principal);
            font-size: 1.1rem;
            z-index: 2;
        }

        input[type="password"] {
            width: 100%;
            padding: 18px 20px 18px 55px;
            font-size: 1rem;
            border: 2px solid rgba(0, 48, 114, 0.1);
            border-radius: var(--borda-raio);
            background: var(--cinza-claro);
            color: var(--cinza-escuro);
            transition: var(--transicao);
            outline: none;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }

        input[type="password"]:focus {
            border-color: var(--verde-acento);
            box-shadow: 0 0 0 3px rgba(10, 147, 150, 0.1);
            background: var(--branco);
        }

        input[type="password"]::placeholder {
            color: var(--cinza);
        }

        /* === FORÇA DA SENHA === */
        .forca-senha {
            margin-top: 8px;
            margin-bottom: 20px;
        }

        .barra-forca {
            height: 4px;
            background: var(--cinza-claro);
            border-radius: 2px;
            overflow: hidden;
            margin-bottom: 5px;
        }

        .preenchimento-forca {
            width: 0%;
            height: 100%;
            transition: width 0.3s ease;
        }

        .rotulo-forca {
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* === REQUISITOS DA SENHA === */
        .requisitos-senha {
            margin: 15px 0 25px;
            padding: 12px 15px;
            background: var(--cinza-claro);
            border-radius: var(--borda-raio);
        }

        .requisitos-senha h4 {
            font-size: 0.85rem;
            margin-bottom: 10px;
            color: var(--cinza-escuro);
        }

        .requisito {
            font-size: 0.75rem;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--cinza);
        }

        .requisito i {
            width: 14px;
            font-size: 0.7rem;
        }

        .requisito.atendido {
            color: var(--verde-sucesso);
        }

        .requisito.nao-atendido {
            color: var(--cinza);
        }

        /* === BOTÃO === */
        .botao-redefinir {
            width: 100%;
            background: linear-gradient(135deg, var(--azul-principal), var(--verde-acento));
            color: var(--branco);
            border: none;
            padding: 18px 30px;
            border-radius: var(--borda-raio);
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transicao);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            box-shadow: 0 8px 25px rgba(0, 48, 114, 0.2);
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .botao-redefinir::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, var(--verde-acento), var(--azul-principal));
            transition: var(--transicao);
            z-index: -1;
        }

        .botao-redefinir:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(0, 48, 114, 0.3);
        }

        .botao-redefinir:hover::before {
            left: 0;
        }

        /* === ALERTAS === */
        .alerta-sucesso {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 15px 20px;
            border-radius: var(--borda-raio);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-left: 4px solid #2e7d32;
            animation: entradaSuave 0.4s ease;
        }

        .alerta-erro {
            background: #ffebee;
            color: var(--vermelho-erro);
            padding: 15px 20px;
            border-radius: var(--borda-raio);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-left: 4px solid var(--vermelho-erro);
            animation: entradaSuave 0.4s ease;
        }

        .alerta-info {
            background: #e3f2fd;
            color: #0a5c8e;
            padding: 15px 20px;
            border-radius: var(--borda-raio);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-left: 4px solid #0a5c8e;
            animation: entradaSuave 0.4s ease;
        }

        /* === RODAPÉ === */
        .rodape-redefinir {
            text-align: center;
            margin-top: 30px;
            padding-top: 25px;
            border-top: 1px solid rgba(0, 48, 114, 0.1);
            color: var(--cinza);
            font-size: 0.95rem;
        }

        .rodape-redefinir a {
            color: var(--azul-principal);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transicao);
        }

        .rodape-redefinir a:hover {
            color: var(--verde-acento);
            text-decoration: underline;
        }

        /* === ANIMAÇÕES === */
        @keyframes entradaSuave {
            from { opacity: 0; transform: translateY(30px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        @keyframes expandirLargura {
            from { width: 0; }
            to   { width: 60px; }
        }

        @keyframes girar {
            to { transform: rotate(360deg); }
        }

        /* === ESTADO DE CARREGAMENTO === */
        .botao-redefinir.carregando {
            pointer-events: none;
        }

        .botao-redefinir.carregando span {
            display: none;
        }

        .botao-redefinir.carregando::after {
            content: '';
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: girar 1s linear infinite;
        }

        /* === VALIDAÇÃO === */
        .grupo-campo.valido i {
            color: var(--verde-sucesso);
        }

        .grupo-campo.invalido i {
            color: var(--vermelho-erro);
        }

        .grupo-campo.valido input {
            border-color: var(--verde-sucesso);
        }

        .grupo-campo.invalido input {
            border-color: var(--vermelho-erro);
        }

        .mensagem-erro {
            color: var(--vermelho-erro);
            font-size: 0.85rem;
            margin-top: 8px;
            display: none;
            animation: entradaSuave 0.3s ease;
        }

        .grupo-campo.invalido .mensagem-erro {
            display: block;
        }

        /* === RESPONSIVIDADE === */
        @media (max-width: 768px) {
            .contentor-redefinir {
                padding: 40px 30px;
                margin: 40px 20px;
            }
            .titulo-redefinir {
                font-size: 1.6rem;
            }
        }

        @media (max-width: 480px) {
            .contentor-redefinir {
                padding: 35px 25px;
            }
            .titulo-redefinir {
                font-size: 1.4rem;
            }
            .icone-senha {
                width: 65px;
                height: 65px;
            }
            .icone-senha i {
                font-size: 2rem;
            }
            input[type="password"] {
                padding: 16px 18px 16px 50px;
            }
        }
    </style>
</head>
<body>

<?php include __DIR__ . '/includes/header.php'; ?>

    <!-- ===== CONTEÚDO PRINCIPAL - REDEFINIR SENHA ===== -->
    <div class="pagina-redefinir">
        <div class="contentor-redefinir">
            
            <!-- Cabeçalho -->
            <div class="cabecalho-redefinir">
                <!--<div class="icone-senha">
                    <i class="fas fa-key"></i>
                </div>-->
                <h1 class="titulo-redefinir">Redefinir Senha</h1>
                <p class="subtitulo-redefinir">
                    Crie uma nova senha para a sua conta.
                </p>
            </div>

            <?php if (!$token_valido && !empty($token)): ?>
                <!-- Token inválido ou expirado -->
                <div class="alerta-info">
                    <i class="fas fa-info-circle"></i>
                    <?= htmlspecialchars($mensagem_erro) ?>
                </div>
                <div class="rodape-redefinir">
                    <p><i class="fas fa-arrow-left"></i> <a href="recuperar-senha.php">Solicitar novo link de recuperação</a></p>
                    <p style="margin-top: 12px;"><a href="area-restrita.php">Voltar para o login</a></p>
                </div>
            <?php elseif (empty($token)): ?>
                <!-- Token não fornecido -->
                <div class="alerta-info">
                    <i class="fas fa-info-circle"></i>
                    Link de recuperação inválido. Solicite um novo link.
                </div>
                <div class="rodape-redefinir">
                    <p><i class="fas fa-arrow-left"></i> <a href="recuperar-senha.php">Solicitar novo link de recuperação</a></p>
                    <p style="margin-top: 12px;"><a href="area-restrita.php">Voltar para o login</a></p>
                </div>
            <?php else: ?>
                <!-- Token válido - mostrar formulário -->
                <div id="mensagemFeedback"></div>

                <form id="formularioRedefinir" method="POST" action="redefinir-senha.php?processar=1" novalidate>
                    <input type="hidden" name="token" id="token" value="<?= htmlspecialchars($token) ?>">
                    
                    <div class="grupo-campo" id="grupoCampoNovaSenha">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="novaSenha" name="nova_senha" placeholder="Nova senha" required minlength="6">
                        <div class="mensagem-erro" id="erroNovaSenha">A senha deve ter pelo menos 6 caracteres.</div>
                    </div>

                    <div class="grupo-campo" id="grupoCampoConfirmarSenha">
                        <i class="fas fa-check-circle"></i>
                        <input type="password" id="confirmarSenha" name="confirmar_senha" placeholder="Confirmar senha" required>
                        <div class="mensagem-erro" id="erroConfirmarSenha">As senhas não coincidem.</div>
                    </div>

                    <!-- Força da senha -->
                    <div class="forca-senha" id="forcaSenha" style="display: none;">
                        <div class="barra-forca">
                            <div class="preenchimento-forca" id="preenchimentoForca"></div>
                        </div>
                        <div class="rotulo-forca" id="rotuloForca"></div>
                    </div>

                    <!-- Requisitos da senha -->
                    <div class="requisitos-senha">
                        <h4><i class="fas fa-shield-alt"></i> Requisitos da senha:</h4>
                        <div class="requisito" id="reqComprimento">
                            <i class="fas fa-circle"></i> Pelo menos 6 caracteres
                        </div>
                        <div class="requisito" id="reqMaiuscula">
                            <i class="fas fa-circle"></i> Letra maiúscula
                        </div>
                        <div class="requisito" id="reqMinuscula">
                            <i class="fas fa-circle"></i> Letra minúscula
                        </div>
                        <div class="requisito" id="reqNumero">
                            <i class="fas fa-circle"></i> Pelo menos 1 número
                        </div>
                        <div class="requisito" id="reqEspecial">
                            <i class="fas fa-circle"></i> Caractere especial (!@#$%*)
                        </div>
                    </div>

                    <button type="submit" class="botao-redefinir" id="botaoRedefinir">
                        <i class="fas fa-save"></i>
                        <span>Redefinir Senha</span>
                    </button>
                </form>

                <div class="rodape-redefinir">
                    <p><i class="fas fa-arrow-left"></i> <a href="area-restrita.php">Voltar para o login</a></p>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <!-- ===== BOTÕES FLUTUANTES ===== -->
    <div class="botoes-flutuantes">
        <button class="botao-flutuante" id="botaoTopo" title="Voltar ao topo">
            <i class="fas fa-chevron-up"></i>
        </button>
        <?php if($config['whatsapp_numero']): ?>
        <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $config['whatsapp_numero']) ?>" class="botao-flutuante whatsapp" target="_blank" rel="noopener" title="WhatsApp">
            <i class="fab fa-whatsapp"></i>
        </a>
        <?php endif; ?>
    </div>

<?php include __DIR__ . '/includes/footer.php'; ?>

    <script src="js/header-footer.js"></script>
    <script>
        // ===== VALIDAÇÃO E FORÇA DA SENHA =====
        const novaSenha = document.getElementById('novaSenha');
        const confirmarSenha = document.getElementById('confirmarSenha');
        const grupoNovaSenha = document.getElementById('grupoCampoNovaSenha');
        const grupoConfirmarSenha = document.getElementById('grupoCampoConfirmarSenha');
        const erroNovaSenha = document.getElementById('erroNovaSenha');
        const erroConfirmarSenha = document.getElementById('erroConfirmarSenha');
        const forcaSenhaDiv = document.getElementById('forcaSenha');
        const preenchimentoForca = document.getElementById('preenchimentoForca');
        const rotuloForca = document.getElementById('rotuloForca');
        
        // Elementos dos requisitos
        const reqComprimento = document.getElementById('reqComprimento');
        const reqMaiuscula = document.getElementById('reqMaiuscula');
        const reqMinuscula = document.getElementById('reqMinuscula');
        const reqNumero = document.getElementById('reqNumero');
        const reqEspecial = document.getElementById('reqEspecial');
        
        function verificarForcaSenha() {
            const senha = novaSenha.value;
            
            if (senha.length === 0) {
                forcaSenhaDiv.style.display = 'none';
                return;
            }
            
            forcaSenhaDiv.style.display = 'block';
            
            // Verificar requisitos
            const comprimentoOk = senha.length >= 6;
            const maiusculaOk = /[A-Z]/.test(senha);
            const minusculaOk = /[a-z]/.test(senha);
            const numeroOk = /[0-9]/.test(senha);
            const especialOk = /[!@#$%*]/.test(senha);
            
            // Atualizar ícones dos requisitos
            atualizarRequisito(reqComprimento, comprimentoOk);
            atualizarRequisito(reqMaiuscula, maiusculaOk);
            atualizarRequisito(reqMinuscula, minusculaOk);
            atualizarRequisito(reqNumero, numeroOk);
            atualizarRequisito(reqEspecial, especialOk);
            
            // Calcular força (0-5)
            let forca = 0;
            if (comprimentoOk) forca++;
            if (maiusculaOk) forca++;
            if (minusculaOk) forca++;
            if (numeroOk) forca++;
            if (especialOk) forca++;
            
            const percentual = (forca / 5) * 100;
            preenchimentoForca.style.width = percentual + '%';
            
            let cor, texto;
            if (forca <= 2) {
                cor = '#dc3545';
                texto = '<i class="fas fa-exclamation-circle"></i> Senha fraca';
            } else if (forca <= 3) {
                cor = '#ffc107';
                texto = '<i class="fas fa-exclamation-triangle"></i> Senha média';
            } else {
                cor = '#28a745';
                texto = '<i class="fas fa-check-circle"></i> Senha forte';
            }
            
            preenchimentoForca.style.background = cor;
            rotuloForca.innerHTML = texto;
            rotuloForca.style.color = cor;
        }
        
        function atualizarRequisito(elemento, atendido) {
            if (atendido) {
                elemento.classList.add('atendido');
                elemento.classList.remove('nao-atendido');
                elemento.querySelector('i').className = 'fas fa-check-circle';
            } else {
                elemento.classList.add('nao-atendido');
                elemento.classList.remove('atendido');
                elemento.querySelector('i').className = 'fas fa-circle';
            }
        }
        
        function validarSenhas() {
            const senha = novaSenha.value;
            const confirmacao = confirmarSenha.value;
            let valido = true;
            
            // Validar nova senha
            if (senha === '') {
                grupoNovaSenha.classList.remove('valido', 'invalido');
            } else if (senha.length >= 6) {
                grupoNovaSenha.classList.add('valido');
                grupoNovaSenha.classList.remove('invalido');
            } else {
                grupoNovaSenha.classList.add('invalido');
                grupoNovaSenha.classList.remove('valido');
                valido = false;
            }
            
            // Validar confirmação
            if (confirmacao === '') {
                grupoConfirmarSenha.classList.remove('valido', 'invalido');
            } else if (senha === confirmacao && senha.length >= 6) {
                grupoConfirmarSenha.classList.add('valido');
                grupoConfirmarSenha.classList.remove('invalido');
            } else {
                grupoConfirmarSenha.classList.add('invalido');
                grupoConfirmarSenha.classList.remove('valido');
                valido = false;
            }
            
            return valido;
        }
        
        novaSenha?.addEventListener('input', () => {
            verificarForcaSenha();
            validarSenhas();
        });
        
        confirmarSenha?.addEventListener('input', validarSenhas);
        
        // ===== SUBMISSÃO DO FORMULÁRIO =====
        const formulario = document.getElementById('formularioRedefinir');
        const botaoRedefinir = document.getElementById('botaoRedefinir');
        const feedback = document.getElementById('mensagemFeedback');
        
        formulario?.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            if (!validarSenhas()) {
                return;
            }
            
            const senha = novaSenha.value;
            const token = document.getElementById('token').value;
            
            // Estado de carregamento
            botaoRedefinir.classList.add('carregando');
            botaoRedefinir.disabled = true;
            
            try {
                const response = await fetch('redefinir-senha.php?processar=1', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ 
                        token: token,
                        nova_senha: senha,
                        confirmar_senha: confirmarSenha.value
                    })
                });
                
                const resultado = await response.json();
                
                if (resultado.success) {
                    feedback.innerHTML = `
                        <div class="alerta-sucesso">
                            <i class="fas fa-check-circle"></i>
                            ${resultado.message}
                        </div>
                    `;
                    formulario.reset();
                    
                    if (resultado.redirect) {
                        setTimeout(() => {
                            window.location.href = 'area-restrita.php';
                        }, 3000);
                    }
                } else {
                    feedback.innerHTML = `
                        <div class="alerta-erro">
                            <i class="fas fa-exclamation-triangle"></i>
                            ${resultado.message}
                        </div>
                    `;
                }
            } catch (error) {
                feedback.innerHTML = `
                    <div class="alerta-erro">
                        <i class="fas fa-exclamation-triangle"></i>
                        Erro ao processar solicitação. Tente novamente mais tarde.
                    </div>
                `;
            } finally {
                botaoRedefinir.classList.remove('carregando');
                botaoRedefinir.disabled = false;
            }
        });
    </script>
</body>
</html>

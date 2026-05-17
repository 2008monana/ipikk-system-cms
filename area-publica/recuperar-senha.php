<?php
/**
 * Página Recuperar Senha - IPIKK
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

// Processar formulário via AJAX
$processar = isset($_GET['processar']) ? true : false;
$resposta = ['success' => false, 'message' => ''];

if ($processar && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // LER O CORPO DA REQUISIÇÃO COMO JSON
    $input = json_decode(file_get_contents('php://input'), true);
    
    $email = trim($input['email'] ?? '');
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $resposta = ['success' => false, 'message' => '<i class="fas fa-exclamation-circle"></i> Por favor, insira um e-mail válido.'];
    } else {
        try {
            $db = getDB();
            
            // Verificar se email existe
            $stmt = $db->prepare("SELECT id, nome, email FROM utilizadores WHERE email = ? AND ativo = 1");
            $stmt->execute([$email]);
            $utilizador = $stmt->fetch();
                        
                        // Dentro do if ($utilizador) { ... }
            if ($utilizador) {
                // Gerar token único
                $token = bin2hex(random_bytes(32));
                $expiracao = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // Invalida tokens antigos e salva novo token
                $db->beginTransaction();
                $stmt = $db->prepare("UPDATE tokens_recuperacao SET usado = 1 WHERE utilizador_id = ? AND usado = 0");
                $stmt->execute([$utilizador['id']]);
                $stmt = $db->prepare("INSERT INTO tokens_recuperacao (utilizador_id, token, expiracao, usado) VALUES (?, ?, ?, 0)");
                $stmt->execute([$utilizador['id'], $token, $expiracao]);
                
                // Enviar email usando PHPMailer (REAL!)
                $email_enviado = enviarEmailRecuperacao($email, $utilizador['nome'], $token);
                $db->commit();
                
                if ($email_enviado['success']) {
                    $resposta = [
                        'success' => true, 
                        'message' => '<i class="fas fa-check-circle"></i> Link de recuperação enviado! Verifique sua caixa de entrada.'
                    ];
                } else {
                    // Email não enviado (erro SMTP)
                    error_log("Erro ao enviar email para: " . $email . " - " . $email_enviado['message']);
                    $resposta = [
                        'success' => true, 
                        'message' => '<i class="fas fa-envelope"></i> Se o e-mail estiver registado, você receberá um link para redefinir sua senha em breve.'
                    ];
                }
            } else {
                // Resposta neutra para não expor se o e-mail existe
                $resposta = [
                    'success' => true,
                    'message' => '<i class="fas fa-envelope"></i> Se o e-mail estiver registado, você receberá um link para redefinir sua senha em breve.'
                ];
            }
        } catch (PDOException $e) {
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            error_log("Erro ao processar recuperação de senha: " . $e->getMessage());
            // Erro servidor - notificação VERMELHA
            $resposta = [
                'success' => false, 
                'message' => '<i class="fas fa-exclamation-circle"></i> Erro ao processar solicitação. Tente novamente mais tarde.'
            ];
        }
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
    <title>IPIKK - Recuperar Senha</title>
    
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

        .pagina-recuperar {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        /* === CONTENTOR PRINCIPAL === */
        .contentor-recuperar {
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

        .contentor-recuperar::before {
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
        .cabecalho-recuperar {
            text-align: center;
            margin-bottom: 35px;
            position: relative;
            z-index: 1;
        }

        .icone-email {
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

        .icone-email i {
            font-size: 2.5rem;
            color: var(--branco);
        }

        .titulo-recuperar {
            font-size: 1.8rem;
            background: linear-gradient(135deg, var(--azul-principal), var(--verde-acento));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 15px;
            position: relative;
            display: inline-block;
        }

        .titulo-recuperar::after {
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

        .subtitulo-recuperar {
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

        input[type="email"] {
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

        input[type="email"]:focus {
            border-color: var(--verde-acento);
            box-shadow: 0 0 0 3px rgba(10, 147, 150, 0.1);
            background: var(--branco);
        }

        input[type="email"]::placeholder {
            color: var(--cinza);
        }

        /* === BOTÃO === */
        .botao-enviar {
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

        .botao-enviar::before {
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

        .botao-enviar:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(0, 48, 114, 0.3);
        }

        .botao-enviar:hover::before {
            left: 0;
        }

        .botao-enviar:active {
            transform: translateY(-1px);
        }

        /* === NOTIFICAÇÃO FLUTUANTE === */
        .notificacao-flutuante {
            position: fixed;
            top: 80px;
            left: 50%;
            transform: translateX(-50%);
            padding: 15px 30px;
            border-radius: 50px;
            background: white;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            display: flex;
            align-items: center;
            gap: 12px;
            z-index: 9999;
            font-weight: 600;
            animation: slideDown 0.4s ease, fadeOut 0.4s ease 4.6s forwards;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            max-width: 90%;
        }

        .notificacao-flutuante.sucesso {
            background: linear-gradient(135deg, #28a745, #1e7e34);
            color: white;
        }

        .notificacao-flutuante.erro {
            background: linear-gradient(135deg, #dc3545, #b02a37);
            color: white;
        }

        .notificacao-flutuante i {
            font-size: 1.2rem;
            margin-right: 8px;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateX(-50%) translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(-50%) translateY(0);
            }
        }

        @keyframes fadeOut {
            from {
                opacity: 1;
                transform: translateX(-50%) translateY(0);
            }
            to {
                opacity: 0;
                transform: translateX(-50%) translateY(-20px);
                visibility: hidden;
            }
        }

        /* === RODAPÉ === */
        .rodape-recuperar {
            text-align: center;
            margin-top: 30px;
            padding-top: 25px;
            border-top: 1px solid rgba(0, 48, 114, 0.1);
            color: var(--cinza);
            font-size: 0.95rem;
        }

        .rodape-recuperar a {
            color: var(--azul-principal);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transicao);
        }

        .rodape-recuperar a:hover {
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
        .botao-enviar.carregando {
            pointer-events: none;
        }

        .botao-enviar.carregando span {
            display: none;
        }

        .botao-enviar.carregando::after {
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
            .contentor-recuperar {
                padding: 40px 30px;
                margin: 40px 20px;
            }
            .titulo-recuperar {
                font-size: 1.6rem;
            }
        }

        @media (max-width: 480px) {
            .contentor-recuperar {
                padding: 35px 25px;
            }
            .titulo-recuperar {
                font-size: 1.4rem;
            }
            .icone-email {
                width: 65px;
                height: 65px;
            }
            .icone-email i {
                font-size: 2rem;
            }
            input[type="email"] {
                padding: 16px 18px 16px 50px;
            }
        }
    </style>
</head>
<body>


<?php include __DIR__ . '/includes/header.php'; ?>

    <!-- ===== CONTEÚDO PRINCIPAL - RECUPERAR SENHA ===== -->
    <div class="pagina-recuperar">
        <div class="contentor-recuperar">
            
            <!-- Cabeçalho -->
            <div class="cabecalho-recuperar">
                <h1 class="titulo-recuperar">Recuperar Senha</h1>
                <p class="subtitulo-recuperar">
                    Esqueceu sua senha? Sem problemas. Apenas informe seu endereço de e-mail<br>
                    que enviaremos um link que permitirá definir uma nova senha.
                </p>
            </div>

            <!-- Formulário -->
            <form id="formularioRecuperar" method="POST" novalidate>
                <div class="grupo-campo" id="grupoCampoEmail">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="campoEmail" name="email" placeholder="Seu e-mail institucional" required autocomplete="email">
                    <div class="mensagem-erro" id="erroEmail">Por favor, insira um e-mail válido.</div>
                </div>

                <button type="submit" class="botao-enviar" id="botaoEnviar">
                    <i class="fas fa-paper-plane"></i>
                    <span>Enviar link para redefinir senha</span>
                </button>
            </form>

            <!-- Rodapé -->
            <div class="rodape-recuperar">
                <p><i class="fas fa-arrow-left"></i> <a href="area-restrita.php">Voltar para o login</a></p>
                <p style="margin-top: 12px; font-size: 0.8rem;">
                    <i class="fas fa-shield-alt"></i> Suas informações são protegidas com criptografia SSL
                </p>
            </div>

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
        // ===== FUNÇÃO PARA MOSTRAR NOTIFICAÇÃO FLUTUANTE =====
        function mostrarNotificacao(mensagem, tipo) {
            // Remover notificação existente se houver
            const notificacaoExistente = document.querySelector('.notificacao-flutuante');
            if (notificacaoExistente) {
                notificacaoExistente.remove();
            }
            
            // Criar elemento da notificação
            const notificacao = document.createElement('div');
            notificacao.className = `notificacao-flutuante ${tipo}`;
            notificacao.innerHTML = mensagem;
            
            // Adicionar ao body
            document.body.appendChild(notificacao);
            
            // Remover após 5 segundos
            setTimeout(() => {
                if (notificacao.parentNode) {
                    notificacao.remove();
                }
            }, 5000);
        }
        
        // ===== FORMULÁRIO DE RECUPERAÇÃO =====
        const campoEmail = document.getElementById('campoEmail');
        const grupoEmail = document.getElementById('grupoCampoEmail');
        const formulario = document.getElementById('formularioRecuperar');
        const botaoEnviar = document.getElementById('botaoEnviar');

        function validarEmail(email) {
            const regex = /^[^\s@]+@([^\s@]+\.)+[^\s@]+$/;
            return regex.test(email);
        }

        campoEmail?.addEventListener('input', () => {
            const email = campoEmail.value.trim();
            if (email === '') {
                grupoEmail.classList.remove('valido', 'invalido');
            } else if (validarEmail(email)) {
                grupoEmail.classList.add('valido');
                grupoEmail.classList.remove('invalido');
            } else {
                grupoEmail.classList.add('invalido');
                grupoEmail.classList.remove('valido');
            }
        });

        formulario?.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const email = campoEmail.value.trim();
            
            if (!validarEmail(email)) {
                grupoEmail.classList.add('invalido');
                return;
            }
            
            // Estado de carregamento
            botaoEnviar.classList.add('carregando');
            botaoEnviar.disabled = true;
            
            try {
                const response = await fetch('recuperar-senha.php?processar=1', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ email: email })
                });
                
                let resultado = null;
                const conteudo = await response.text();
                try { resultado = JSON.parse(conteudo); } catch (_) { resultado = null; }

                if (!response.ok || !resultado || typeof resultado.success === 'undefined') {
                    throw new Error('Resposta inválida do servidor');
                }

                if (resultado.success) {
                    // Sucesso - notificação VERDE
                    mostrarNotificacao(resultado.message, 'sucesso');
                    campoEmail.value = '';
                    grupoEmail.classList.remove('valido', 'invalido');
                } else {
                    // Erro - notificação VERMELHA
                    mostrarNotificacao(resultado.message, 'erro');
                }
            } catch (error) {
                // Erro de rede - notificação VERMELHA
                mostrarNotificacao('<i class="fas fa-exclamation-circle"></i> Erro ao processar solicitação. Tente novamente mais tarde.', 'erro');
            } finally {
                botaoEnviar.classList.remove('carregando');
                botaoEnviar.disabled = false;
            }
        });
    </script>
</body>
</html>
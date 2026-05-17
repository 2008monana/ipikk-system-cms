<?php
/**
 * Página Login - Área Restrita IPIKK
 * Este arquivo está em: C:\xampp\htdocs\ipikk\area-publica\area-restrita.php
 */

require_once dirname(__DIR__) . '/config/index.php';

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

// Verificar se já está logado (redireciona para a área restrita)
if (isset($_SESSION['utilizador_id'])) {
    header('Location: ../area-restrita/admin-dashboard.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Área restrita do site IPIKK - Acesso administrativo">
    <title>IPIKK - Login</title>
    
    <!-- Fontes e Ícones -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <!-- Favicon -->
    <link href="<?= $config['favicon_url'] ?? 'foto/ipikk_new_logo.png' ?>" rel="icon">
    
    <!-- CSS Header/Footer Padrão -->
    <link rel="stylesheet" href="css/header-footer.css">
    
    <style>
        /* === VARIÁVEIS  === */
        :root {
            --azul-principal:  <?= $config['cor_primaria'] ?? '#003072' ?>;
            --azul-claro:      <?= $config['cor_azul_claro'] ?? '#2e86c1' ?>;
            --azul-escuro:     <?= $config['cor_azul_escuro'] ?? '#001a40' ?>;
            --verde-acento:    <?= $config['cor_verde_acento'] ?? '#0a9396' ?>;
            --verde-sucesso:   #38b000;
            --vermelho-erro:   #e63946;
            --branco:          #ffffff;
            --cinza-claro:     #f8f9fa;
            --cinza:           #6c757d;
            --cinza-escuro:    #212529;
            --borda-raio:      12px;
            --sombra:          0 5px 20px rgba(0, 48, 114, 0.1);
            --transicao:       all 0.3s ease;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Montserrat', sans-serif;
            background: var(--cinza-claro);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .pagina-login {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        .contentor-login {
            max-width: 480px;
            width: 100%;
            background: var(--branco);
            border-radius: var(--borda-raio);
            padding: 50px 40px;
            box-shadow: var(--sombra);
            position: relative;
            animation: fadeInUp 0.6s ease;
        }

        .cabecalho-login { text-align: center; margin-bottom: 40px; }
        .titulo-login {
            font-size: 2.2rem;
            background: linear-gradient(135deg, var(--azul-principal), var(--verde-acento));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 15px;
        }
        .titulo-login::after {
            content: '';
            display: block;
            width: 80px;
            height: 4px;
            background: linear-gradient(90deg, var(--azul-principal), var(--verde-acento));
            margin: 15px auto 0;
            border-radius: 2px;
        }
        .subtitulo-login { color: var(--cinza); font-size: 1rem; }

        .grupo-campo {
            position: relative;
            margin-bottom: 25px;
        }
        .grupo-campo > i {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--azul-principal);
            font-size: 1rem;
            z-index: 2;
        }
        input {
            width: 100%;
            padding: 16px 20px 16px 50px;
            font-size: 1rem;
            border: 2px solid #e0e0e0;
            border-radius: var(--borda-raio);
            background: var(--cinza-claro);
            transition: var(--transicao);
            outline: none;
        }
        .grupo-campo.senha input {
            padding-right: 56px;
        }
        .botao-visualizar-senha {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: transparent;
            color: var(--cinza);
            width: 34px;
            height: 34px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transicao);
            z-index: 3;
        }
        .botao-visualizar-senha:hover {
            background: rgba(10, 147, 150, 0.1);
            color: var(--azul-principal);
        }
        .botao-visualizar-senha:focus-visible {
            outline: 2px solid var(--verde-acento);
            outline-offset: 2px;
        }
        input:focus {
            border-color: var(--verde-acento);
            box-shadow: 0 0 0 3px rgba(10, 147, 150, 0.1);
            background: var(--branco);
        }
        .grupo-campo.valido i { color: var(--verde-sucesso); }
        .grupo-campo.invalido i { color: var(--vermelho-erro); }
        .grupo-campo.valido input { border-color: var(--verde-sucesso); }
        .grupo-campo.invalido input { border-color: var(--vermelho-erro); }
        .mensagem-erro {
            color: var(--vermelho-erro);
            font-size: 0.75rem;
            margin-top: 5px;
            display: none;
        }
        .grupo-campo.invalido .mensagem-erro { display: block; }

        .opcoes-formulario {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }
        .contentor-checkbox {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-size: 0.9rem;
            color: var(--cinza);
        }
        .contentor-checkbox input { width: 18px; height: 18px; margin: 0; padding: 0; }
        .link-esqueceu-senha {
            color: var(--azul-principal);
            text-decoration: none;
            font-size: 0.9rem;
        }
        .link-esqueceu-senha:hover { color: var(--verde-acento); text-decoration: underline; }

        .botao-entrar {
            width: 100%;
            background: linear-gradient(135deg, var(--azul-principal), var(--verde-acento));
            color: var(--branco);
            border: none;
            padding: 16px;
            border-radius: var(--borda-raio);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transicao);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .botao-entrar:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,48,114,0.2); }
        .botao-entrar.carregando { pointer-events: none; opacity: 0.8; }
        .botao-entrar.carregando span { display: none; }
        .botao-entrar.carregando::after {
            content: '';
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        .rodape-login { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e0e0e0; color: var(--cinza); font-size: 0.8rem; }
        .rodape-login a { color: var(--azul-principal); text-decoration: none; }

        /* OVERLAY DE PROCESSAMENTO */
        .overlay-processamento {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(8px);
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        .overlay-processamento.ativo {
            opacity: 1;
            visibility: visible;
        }
        .feedback-box {
            text-align: center;
            background: white;
            padding: 40px 50px;
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            animation: zoomIn 0.4s ease;
        }
        .feedback-icone {
            width: 100px;
            height: 100px;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .feedback-icone i { font-size: 4rem; }
        .spinner {
            width: 60px;
            height: 60px;
            border: 5px solid #e0e0e0;
            border-top-color: var(--verde-acento);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        .feedback-texto {
            font-size: 1.2rem;
            font-weight: 500;
            color: var(--cinza-escuro);
        }
        .feedback-subtexto {
            font-size: 0.9rem;
            color: var(--cinza);
            margin-top: 8px;
        }

        @keyframes spin { to { transform: rotate(360deg); } }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes zoomIn {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }
    </style>
</head>
<body>

<?php include __DIR__ . '/includes/header.php'; ?>

    <!-- ===== OVERLAY DE PROCESSAMENTO ===== -->
    <div id="overlayProcessamento" class="overlay-processamento">
        <div class="feedback-box">
            <div class="feedback-icone" id="feedbackIcone">
                <div class="spinner"></div>
            </div>
            <div class="feedback-texto" id="feedbackTexto">Autenticando...</div>
            <div class="feedback-subtexto" id="feedbackSubtexto">Por favor, aguarde</div>
        </div>
    </div>

    <!-- ===== CONTEÚDO PRINCIPAL - LOGIN ===== -->
    <div class="pagina-login">
        <div class="contentor-login">
            <div class="cabecalho-login">
                <h1 class="titulo-login">Login</h1>
                <p class="subtitulo-login">Área Restrita - Acesso Administrativo</p>
            </div>

            <form id="formularioLogin" method="POST" action="processar-login.php" novalidate>
                <div class="grupo-campo" id="grupoCampoEmail">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" id="campoEmail" placeholder="Email" required autocomplete="email">
                    <div class="mensagem-erro" id="erroEmail">Por favor, insira um email válido.</div>
                </div>

                <div class="grupo-campo senha" id="grupoCampoSenha">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="senha" id="campoSenha" placeholder="Senha" required autocomplete="current-password">
                    <button type="button" class="botao-visualizar-senha" id="botaoVisualizarSenha" aria-label="Mostrar palavra-passe" aria-pressed="false">
                        <i class="fas fa-eye-slash" id="iconeVisualizarSenha"></i>
                    </button>
                    <div class="mensagem-erro" id="erroSenha">A senha deve ter pelo menos 6 caracteres.</div>
                </div>

                <div class="opcoes-formulario">
                    <label class="contentor-checkbox">
                        <input type="checkbox" name="manter_conectado" id="manterConectado">
                        <span>Manter conectado</span>
                    </label>
                    <a href="recuperar-senha.php" class="link-esqueceu-senha">Esqueceu sua senha?</a>
                </div>

                <button type="submit" class="botao-entrar" id="botaoSubmeter">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>ENTRAR</span>
                </button>
            </form>

            <div class="rodape-login">
                <p><i class="fas fa-shield-alt"></i> Credenciais protegidas com criptografia SSL</p>
            </div>
        </div>
    </div>

<?php include __DIR__ . '/includes/footer.php'; ?>

    <script src="js/header-footer.js"></script>
<script>
    // ===== TESTE DE CARREGAMENTO =====
    console.log('=== PÁGINA DE LOGIN CARREGADA ===');
    
    // Aguardar o DOM carregar completamente
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM completamente carregado');
        
        // ===== ELEMENTOS - BUSCAR NOVAMENTE APÓS DOM CARREGAR =====
        const campoEmail = document.getElementById('campoEmail');
        const campoSenha = document.getElementById('campoSenha');
        const grupoEmail = document.getElementById('grupoCampoEmail');
        const grupoSenha = document.getElementById('grupoCampoSenha');
        const formulario = document.getElementById('formularioLogin');
        const botaoSubmit = document.getElementById('botaoSubmeter');
        const overlay = document.getElementById('overlayProcessamento');
        const feedbackIcone = document.getElementById('feedbackIcone');
        const feedbackTexto = document.getElementById('feedbackTexto');
        const feedbackSubtexto = document.getElementById('feedbackSubtexto');
        const manterConectado = document.getElementById('manterConectado');
        const botaoVisualizarSenha = document.getElementById('botaoVisualizarSenha');
        const iconeVisualizarSenha = document.getElementById('iconeVisualizarSenha');

        // ===== VERIFICAR SE OS ELEMENTOS EXISTEM =====
        console.log('campoEmail:', campoEmail);
        console.log('campoSenha:', campoSenha);
        console.log('formulario:', formulario);
        console.log('botaoSubmit:', botaoSubmit);
        
        if (!campoEmail || !campoSenha || !formulario) {
            console.error('Elementos do formulário não encontrados!');
            return;
        }
        
        console.log(' Todos os elementos encontrados!');

        // ===== AUDIO CONTEXT (para sons) =====
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();

        function playBeep(frequency, duration, type = 'sine') {
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();
            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);
            oscillator.frequency.value = frequency;
            oscillator.type = type;
            gainNode.gain.value = 0.3;
            oscillator.start();
            gainNode.gain.exponentialRampToValueAtTime(0.00001, audioContext.currentTime + duration);
            oscillator.stop(audioContext.currentTime + duration);
        }

        function playSuccess() {
            playBeep(523.25, 0.15);
            setTimeout(() => playBeep(659.25, 0.2), 150);
        }

        function playError() {
            playBeep(440, 0.1);
            setTimeout(() => playBeep(349.23, 0.2), 100);
        }

        function validarEmail(email) {
            return /^[^\s@]+@([^\s@]+\.)+[^\s@]+$/.test(email);
        }

        function validarSenha(senha) {
            return senha.length >= 6;
        }

        if (botaoVisualizarSenha && iconeVisualizarSenha) {
            botaoVisualizarSenha.addEventListener('click', () => {
                const senhaVisivel = campoSenha.type === 'text';
                campoSenha.type = senhaVisivel ? 'password' : 'text';
                iconeVisualizarSenha.className = senhaVisivel ? 'fas fa-eye-slash' : 'fas fa-eye';
                botaoVisualizarSenha.setAttribute('aria-pressed', String(!senhaVisivel));
                botaoVisualizarSenha.setAttribute('aria-label', senhaVisivel ? 'Mostrar palavra-passe' : 'Ocultar palavra-passe');
            });
        }

        // Validação em tempo real
        campoEmail.addEventListener('input', () => {
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

        campoSenha.addEventListener('input', () => {
            const senha = campoSenha.value;
            if (senha === '') {
                grupoSenha.classList.remove('valido', 'invalido');
            } else if (validarSenha(senha)) {
                grupoSenha.classList.add('valido');
                grupoSenha.classList.remove('invalido');
            } else {
                grupoSenha.classList.add('invalido');
                grupoSenha.classList.remove('valido');
            }
        });

        function mostrarFeedback(estado, mensagem, subtexto, cor) {
            if (!feedbackIcone) return;
            feedbackIcone.innerHTML = '';
            if (estado === 'loading') {
                feedbackIcone.innerHTML = '<div class="spinner"></div>';
            } else if (estado === 'success') {
                feedbackIcone.innerHTML = '<i class="fas fa-check-circle" style="font-size: 4rem; color: #38b000;"></i>';
                playSuccess();
            } else if (estado === 'error') {
                feedbackIcone.innerHTML = '<i class="fas fa-times-circle" style="font-size: 4rem; color: #e63946;"></i>';
                playError();
            }
            if (feedbackTexto) feedbackTexto.textContent = mensagem;
            if (feedbackSubtexto) feedbackSubtexto.textContent = subtexto || '';
            if (cor && feedbackTexto) feedbackTexto.style.color = cor;
        }

        // Envio do formulário
        formulario.addEventListener('submit', async (e) => {
            e.preventDefault();
            console.log('Formulário submetido!');
            
            // PEGAR OS VALORES DIRETAMENTE DOS CAMPOS
            const email = campoEmail.value.trim();
            const senha = campoSenha.value;
            const manter = manterConectado ? manterConectado.checked : false;
            
            console.log(' Email:', email);
            console.log('Senha:', senha ? '******' : 'vazia');
            console.log(' Manter conectado:', manter);
            
            // Validação
            let isValid = true;
            if (!validarEmail(email)) {
                grupoEmail.classList.add('invalido');
                isValid = false;
                console.log('Email inválido');
            }
            if (!validarSenha(senha)) {
                grupoSenha.classList.add('invalido');
                isValid = false;
                console.log(' Senha inválida (menos de 6 caracteres)');
            }
            
            if (!isValid) {
                console.log('Validação falhou');
                mostrarFeedback('error', 'Preencha todos os campos corretamente!', 'Email válido e senha com 6+ caracteres', '#c62828');
                setTimeout(() => {
                    if (overlay) overlay.classList.remove('ativo');
                }, 2000);
                return;
            }
            
            // Mostrar loading
            if (overlay) overlay.classList.add('ativo');
            mostrarFeedback('loading', 'Autenticando...', 'Aguarde 5 segundos', '#666');
            
            // Ativar áudio
            if (audioContext.state === 'suspended') {
                await audioContext.resume();
            }

            await new Promise(resolve => setTimeout(resolve, 5000));
            
            try {
                console.log('Enviando requisição para processar-login.php');
                const response = await fetch('processar-login.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, senha, manter_conectado: manter })
                });
                
                console.log('Status da resposta:', response.status);
                const resultado = await response.json();
                console.log('Resultado:', resultado);
                
                if (resultado.success) {
                    mostrarFeedback('success', `Bem-vindo de volta, ${resultado.nome}!`, 'Redirecionando...', '#2e7d32');
                    setTimeout(() => {
                        const redirectUrl = resultado.redirect_url || '../area-restrita/admin-dashboard.php';
                        window.location.href = redirectUrl;
                    }, 2000);
                } else {
                    mostrarFeedback('error', resultado.message || 'Credenciais inválidas!', 'Verifique seu email e senha', '#c62828');
                    setTimeout(() => {
                        if (overlay) overlay.classList.remove('ativo');
                        if (botaoSubmit) {
                            botaoSubmit.classList.remove('carregando');
                            botaoSubmit.disabled = false;
                        }
                        campoEmail.value = '';
                        campoSenha.value = '';
                        campoEmail.focus();
                    }, 2000);
                }
            } catch (error) {
                console.error('Erro na requisição:', error);
                mostrarFeedback('error', 'Erro de conexão!', 'Tente novamente mais tarde.', '#c62828');
                setTimeout(() => {
                    if (overlay) overlay.classList.remove('ativo');
                    if (botaoSubmit) {
                        botaoSubmit.classList.remove('carregando');
                        botaoSubmit.disabled = false;
                    }
                }, 2000);
            }
        });
    });
    
    // Ativar áudio no primeiro clique
    document.body.addEventListener('click', () => {
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        if (audioContext.state === 'suspended') {
            audioContext.resume();
        }
    }, { once: true });
</script>

</body>
</html>

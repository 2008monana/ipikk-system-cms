<?php
/**
 * Logout - Área Restrita IPIKK
 * Modal de confirmação que aparece sobre a página atual
 */

// Carregar configurações
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/functions.php';
require_once dirname(__DIR__) . '/config/constants.php';

session_start();

// Verificar se o usuário está logado
if (!isset($_SESSION['utilizador_id'])) {
    header('Location: ../area-publica/area-restrita.php');
    exit;
}

$usuario_nome = $_SESSION['utilizador_nome'] ?? 'Utilizador';
$usuario_id = $_SESSION['utilizador_id'];
$usuario_email = $_SESSION['utilizador_email'] ?? '';

// Buscar a foto do perfil do utilizador na base de dados
$foto_url = '../area-publica/foto/sem_foto.png';
$tem_foto_perfil = false;
try {
    $db = getDB();
    $stmt = $db->prepare("SELECT foto_url FROM utilizadores WHERE id = ?");
    $stmt->execute([$usuario_id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    $foto_bd = trim($usuario['foto_url'] ?? '');

    if ($foto_bd !== '' && $foto_bd !== 'foto/sem_foto.png') {
        $foto_url = normalizarUrlMidia($foto_bd, '..');
        $tem_foto_perfil = true;
    }
} catch (PDOException $e) {
    $foto_url = '../area-publica/foto/sem_foto.png';
    $tem_foto_perfil = false;
}

// Processar logout via POST (confirmação)
$confirmado = isset($_POST['confirmar']) && $_POST['confirmar'] === 'sim';

if ($confirmado) {
    // Registrar a ação de logout no banco de dados
    try {
        $db = getDB();
        
        // Registrar no log de atividades
        $stmt = $db->prepare("
            INSERT INTO logs (utilizador_id, acao, tabela, detalhes, ip_address, user_agent) 
            VALUES (?, 'logout', 'utilizadores', ?, ?, ?)
        ");
        $stmt->execute([
            $usuario_id,
            "Utilizador {$usuario_nome} encerrou a sessão",
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
        
        // Marcar sessão como encerrada na tabela sessoes
        $stmt = $db->prepare("
            UPDATE sessoes 
            SET data_expiracao = NOW() 
            WHERE utilizador_id = ? AND token = ?
        ");
        $stmt->execute([$usuario_id, session_id()]);
        
    } catch (PDOException $e) {
        error_log("Erro ao registrar logout: " . $e->getMessage());
    }
    
    // Destruir todas as variáveis de sessão
    $_SESSION = array();
    
    // Destruir o cookie de sessão
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // Destruir a sessão
    session_destroy();
    
    // Retornar resposta JSON para o AJAX
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'nome' => $usuario_nome,
        'message' => "Até mais, {$usuario_nome}!"
    ]);
    exit;
}

// Se for GET, mostrar apenas a página de logout com modal
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Encerrar Sessão - IPIKK</title>
    
    <!-- Fontes e Ícones -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(8px);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        /* Card de confirmação - estilo semelhante ao formulário de recuperação */
        .card-logout {
            max-width: 480px;
            width: 100%;
            background: #ffffff;
            border-radius: 12px;
            padding: 50px 40px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.5s ease;
        }

        .card-logout::before {
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

        /* Cabeçalho do card */
        .cabecalho-logout {
            text-align: center;
            margin-bottom: 35px;
            position: relative;
            z-index: 1;
        }

        .foto-perfil {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin: 0 auto 15px;
            overflow: hidden;
            border: 3px solid rgba(10, 147, 150, 0.3);
            background: linear-gradient(135deg, #003072, #0a9396);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .foto-perfil img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .foto-perfil i {
            font-size: 2.5rem;
            color: #ffffff;
        }

        .titulo-logout {
            font-size: 1.8rem;
            background: linear-gradient(135deg, #003072, #0a9396);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 8px;
            position: relative;
            display: inline-block;
        }

        .titulo-logout::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: linear-gradient(90deg, #003072, #0a9396);
            border-radius: 2px;
            animation: expandirLargura 1s ease-out forwards;
        }

        .subtitulo-logout {
            font-size: 1rem;
            color: #6c757d;
            margin-top: 20px;
            line-height: 1.6;
        }

        /* Informações do usuário */
        .usuario-info {
            background: #f8f9fa;
            padding: 15px 20px;
            border-radius: 30px;
            display: inline-block;
            margin: 15px auto 0;
            text-align: center;
        }

        .usuario-nome {
            font-weight: 700;
            color: #003072;
            font-size: 1.1rem;
            display: block;
        }

        .usuario-email {
            font-size: 0.8rem;
            color: #6c757d;
            display: block;
            margin-top: 4px;
        }

        /* Botões */
        .botoes {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 25px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 28px;
            border-radius: 40px;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            text-decoration: none;
        }

        /* Botão Sim - estilo do botão de login */
        .btn-sim {
            background: linear-gradient(135deg, #003072, #0a9396);
            color: #ffffff;
            box-shadow: 0 8px 25px rgba(0, 48, 114, 0.2);
        }

        .btn-sim:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(0, 48, 114, 0.3);
        }

        /* Botão Não - estilo secundário */
        .btn-nao {
            background: #6c757d;
            color: #ffffff;
        }

        .btn-nao:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        /* Overlay de Processamento */
        .overlay-processamento {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.95);
            backdrop-filter: blur(8px);
            z-index: 10001;
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
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            animation: zoomIn 0.4s ease;
            min-width: 320px;
        }

        .feedback-icone {
            width: 100px;
            height: 100px;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .spinner {
            width: 60px;
            height: 60px;
            border: 5px solid #e0e0e0;
            border-top-color: #0a9396;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        .feedback-texto {
            font-size: 1.2rem;
            font-weight: 500;
            color: #2c3e50;
        }

        .feedback-subtexto {
            font-size: 0.9rem;
            color: #6c757d;
            margin-top: 8px;
        }

        .icone-sucesso {
            font-size: 4rem;
            color: #28a745;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes zoomIn {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        @keyframes expandirLargura {
            from { width: 0; }
            to { width: 60px; }
        }

        @media (max-width: 768px) {
            .card-logout {
                padding: 40px 30px;
                margin: 20px;
            }
            .titulo-logout {
                font-size: 1.6rem;
            }
        }

        @media (max-width: 480px) {
            .card-logout {
                padding: 35px 25px;
            }
            .titulo-logout {
                font-size: 1.4rem;
            }
            .foto-perfil {
                width: 65px;
                height: 65px;
            }
            .foto-perfil i {
                font-size: 2rem;
            }
            .btn {
                padding: 12px 24px;
                font-size: 0.9rem;
            }
            .botoes {
                gap: 10px;
            }
        }
    </style>
</head>
<body>

<div class="card-logout">
    <div class="cabecalho-logout">
        <div class="foto-perfil">
            <?php if ($tem_foto_perfil): ?>
                <img src="<?= htmlspecialchars($foto_url) ?>" alt="<?= htmlspecialchars($usuario_nome) ?>" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                <i class="fas fa-user-circle" style="display:none;"></i>
            <?php else: ?>
                <i class="fas fa-user-circle"></i>
            <?php endif; ?>
        </div>
        <h1 class="titulo-logout">Encerrar Sessão</h1>
        <p class="subtitulo-logout">
            Tem certeza que deseja sair da área restrita?
        </p>
        <div class="usuario-info">
            <span class="usuario-nome">
                <i class="fas fa-user"></i> <?= htmlspecialchars($usuario_nome) ?>
            </span>
            <?php if (!empty($usuario_email)): ?>
            <span class="usuario-email">
                <i class="fas fa-envelope"></i> <?= htmlspecialchars($usuario_email) ?>
            </span>
            <?php endif; ?>
        </div>
    </div>

    <div class="botoes">
        <button class="btn btn-sim" id="btnSim">
            <i class="fas fa-check-circle"></i> Sim, encerrar
        </button>
        <button class="btn btn-nao" id="btnNao">
            <i class="fas fa-times-circle"></i> Não, cancelar
        </button>
    </div>
</div>

<!-- Overlay de Processamento -->
<div id="overlayProcessamento" class="overlay-processamento">
    <div class="feedback-box">
        <div class="feedback-icone" id="feedbackIcone">
            <div class="spinner"></div>
        </div>
        <div class="feedback-texto" id="feedbackTexto">Encerrando sessão...</div>
        <div class="feedback-subtexto" id="feedbackSubtexto">Por favor, aguarde</div>
    </div>
</div>

<script>
    const btnSim = document.getElementById('btnSim');
    const btnNao = document.getElementById('btnNao');
    const overlay = document.getElementById('overlayProcessamento');
    const feedbackIcone = document.getElementById('feedbackIcone');
    const feedbackTexto = document.getElementById('feedbackTexto');
    const feedbackSubtexto = document.getElementById('feedbackSubtexto');
    const audioContext = new (window.AudioContext || window.webkitAudioContext)();

    function emitirSom(tipo = 'modal') {
        if (!audioContext) return;

        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();

        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);

        if (tipo === 'modal') {
            oscillator.frequency.setValueAtTime(660, audioContext.currentTime);
            oscillator.frequency.exponentialRampToValueAtTime(820, audioContext.currentTime + 0.12);
            gainNode.gain.setValueAtTime(0.0001, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.08, audioContext.currentTime + 0.01);
            gainNode.gain.exponentialRampToValueAtTime(0.0001, audioContext.currentTime + 0.18);
            oscillator.type = 'sine';
            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.18);
        }
    }
    
    function mostrarFeedback(estado, mensagem, subtexto) {
        feedbackIcone.innerHTML = '';
        if (estado === 'loading') {
            feedbackIcone.innerHTML = '<div class="spinner"></div>';
        } else if (estado === 'success') {
            feedbackIcone.innerHTML = '<i class="fas fa-check-circle icone-sucesso"></i>';
        }
        feedbackTexto.textContent = mensagem;
        feedbackSubtexto.textContent = subtexto || '';
    }
    
    btnSim.addEventListener('click', async () => {
        if (audioContext.state === 'suspended') {
            await audioContext.resume();
        }
        emitirSom('modal');
        overlay.classList.add('ativo');
        mostrarFeedback('loading', 'Encerrando sessão...', 'Aguarde 5 segundos');
        
        await new Promise(resolve => setTimeout(resolve, 5000));
        
        try {
            const response = await fetch('logout.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'confirmar=sim'
            });
            
            const resultado = await response.json();
            
            if (resultado.success) {
                mostrarFeedback('success', resultado.message, 'Sessão encerrada com sucesso');
                setTimeout(() => {
                    window.location.href = '../area-publica/area-restrita.php';
                }, 2000);
            } else {
                mostrarFeedback('error', 'Erro ao encerrar sessão', 'Tente novamente');
                setTimeout(() => {
                    overlay.classList.remove('ativo');
                }, 2000);
            }
        } catch (error) {
            console.error('Erro:', error);
            mostrarFeedback('error', 'Erro de conexão', 'Tente novamente mais tarde');
            setTimeout(() => {
                overlay.classList.remove('ativo');
            }, 2000);
        }
    });
    
    btnNao.addEventListener('click', () => {
        window.location.href = 'admin-dashboard.php';
    });

    document.body.addEventListener('click', () => {
        if (audioContext.state === 'suspended') {
            audioContext.resume();
        }
    }, { once: true });
</script>

</body>
</html>
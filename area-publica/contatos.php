<?php
/**
 * Página Contactos - IPIKK (Versão Simplificada)
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

// Processar formulário
$processar = isset($_GET['processar']) ? true : false;
$resposta = ['success' => false, 'message' => ''];

if ($processar && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $assunto = trim($_POST['assunto'] ?? '');
    $mensagem = trim($_POST['mensagem'] ?? '');
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    // Validação simplificada
    $erros = [];
    if (empty($nome)) $erros[] = 'Nome é obrigatório';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $erros[] = 'Email inválido';
    if (empty($assunto)) $erros[] = 'Assunto é obrigatório';
    if (empty($mensagem)) $erros[] = 'Mensagem é obrigatória';

    if (empty($erros)) {
        try {
            $db = getDB();

            $stmt_limite = $db->prepare("
                SELECT id
                FROM mensagens
                WHERE email = ?
                  AND data_envio >= CURDATE()
                  AND data_envio < (CURDATE() + INTERVAL 1 DAY)
                LIMIT 1
            ");
            $stmt_limite->execute([$email]);

            if ($stmt_limite->fetch()) {
                $resposta = ['success' => false, 'message' => 'Já enviou uma mensagem nas últimas 24 horas. Aguarde até amanhã.'];
            } else {
                $stmt = $db->prepare("INSERT INTO mensagens (nome, email, assunto, mensagem, ip_address, user_agent)
                                       VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$nome, $email, $assunto, $mensagem, $ip, $user_agent]);

                $resposta = ['success' => true, 'message' => 'Mensagem enviada com sucesso! Entraremos em contacto em breve.'];
            }
        } catch (PDOException $e) {
            $resposta = ['success' => false, 'message' => 'Erro ao enviar mensagem. Tente novamente mais tarde.'];
            error_log("Erro ao salvar mensagem: " . $e->getMessage());
        }
    } else {
        $resposta = ['success' => false, 'message' => implode(', ', $erros)];
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
    <title>IPIKK - Contactos</title>

    <!-- Fontes e Ícones -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Favicon -->
    <link href="<?= $config['favicon_url'] ?? 'foto/ipikk_new_logo.png' ?>" rel="icon">

    <!-- CSS Header/Footer Padrão -->
    <link rel="stylesheet" href="css/header-footer.css">

    <style>
        /*======Variáveis==========*/
        :root {
            --cor-primaria-contato: <?= $config['cor_primaria'] ?? '#0a3cff' ?>;
            --cor-secundaria-contato: <?= $config['cor_azul_claro'] ?? '#00c6ff' ?>;
            --texto-escuro: #1a1a1a;
            --texto-claro: #4a4a4a;
        }

        /* ===== CABEÇALHO DA PÁGINA ===== */
        .cabecalho-pagina {
            max-width: 1200px;
            margin: 40px auto 0;
            padding: 0 20px;
            text-align: center;
        }

        .titulo-pagina {
            font-family: 'Poppins', sans-serif;
            font-size: 2.5rem;
            color: #003072;
            margin-bottom: 15px;
        }

        .subtitulo-pagina {
            font-size: 1.1rem;
            color: var(--cinza);
            max-width: 700px;
            margin: 0 auto 20px;
        }

        .linha-decorativa-titulo {
            width: 80px;
            height: 4px;
            background: linear-gradient(160deg, #003072 0%, #0a9396 100%);
            margin: 0 auto 30px;
            border-radius: 2px;
        }

        /*====== Layout principal ===========*/
        .container-contatos {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px 80px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 40px;
        }

        /*========= Cartões ==============*/
        .cartao {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 24px;
            padding: 40px;
            backdrop-filter: blur(14px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            transition: transform 0.4s ease;
        }

        .cartao:hover {
            transform: translateY(-8px);
        }

        .cartao h2 {
            font-size: 1.8rem;
            margin-bottom: 20px;
            color: var(--cor-primaria-contato);
            font-family: 'Poppins', sans-serif;
        }

        /*========== Informações de contacto ==========*/
        .item-informacao {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            align-items: center;
        }

        .icone-info {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--cor-secundaria-contato), var(--cor-primaria-contato));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            color: white;
            flex-shrink: 0;
        }

        .texto-info {
            color: var(--texto-escuro);
            font-weight: 500;
            line-height: 1.6;
        }

        .divisor {
            width: 80%;
            opacity: 0.3;
            margin: 10px 0;
            border: 0;
            border-top: 1px solid var(--texto-claro);
        }

        /*========= Formulário ================*/
        .formulario-contato input,
        .formulario-contato select,
        .formulario-contato textarea {
            width: 100%;
            padding: 14px 18px;
            margin-bottom: 18px;
            border-radius: 14px;
            border: none;
            background: rgba(0, 0, 0, 0.08);
            color: var(--texto-escuro);
            font-size: 1rem;
            outline: none;
            font-family: 'Montserrat', sans-serif;
        }

        .formulario-contato select {
            cursor: pointer;
        }

        .formulario-contato input::placeholder,
        .formulario-contato select::placeholder,
        .formulario-contato textarea::placeholder {
            color: var(--texto-claro);
        }

        .formulario-contato textarea {
            resize: vertical;
            min-height: 140px;
        }

        .botao-enviar {
            width: 100%;
            padding: 16px;
            border-radius: 16px;
            border: none;
            font-size: 1.1rem;
            font-weight: 600;
            color: #fff;
            background: linear-gradient(135deg, var(--cor-secundaria-contato), var(--cor-primaria-contato));
            cursor: pointer;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            font-family: 'Poppins', sans-serif;
        }

        .botao-enviar:hover {
            transform: scale(1.01);
            box-shadow: 0 10px 30px rgba(0, 198, 255, 0.3);
        }

        .botao-enviar.enviando {
            opacity: 0.7;
            cursor: not-allowed;
        }

        /*======== Mapa ==============*/
        .mapa-container {
            max-width: 1200px;
            margin: 0 auto 80px;
            padding: 0 20px;
        }

        .mapa-container .cartao {
            padding: 20px;
        }

        .mapa {
            width: 100%;
            height: 500px;
            border-radius: 16px;
            border: none;
        }

        .visually-hidden {
            position: absolute;
            width: 1px;
            height: 1px;
            margin: -1px;
            padding: 0;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            border: 0;
        }

        /* ===== NOTIFICAÇÃO FLUTUANTE ===== */
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

        /*========= Responsividade ===========*/
        @media (max-width: 768px) {
            .cabecalho-pagina h1 {
                font-size: 2.2rem;
            }

            .cartao {
                padding: 30px;
            }

            .mapa {
                height: 350px;
            }
        }

        @media (max-width: 480px) {
            .container-contatos {
                grid-template-columns: 1fr;
                padding: 20px 20px 60px;
            }

            .cabecalho-pagina h1 {
                font-size: 1.8rem;
            }

            .cartao h2 {
                font-size: 1.5rem;
            }

            .notificacao-flutuante {
                top: 70px;
                padding: 12px 20px;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>

<?php include __DIR__ . '/includes/header.php'; ?>

    <main>
        <section class="cabecalho-pagina">
        <h1 class="titulo-pagina">Entre em Contacto</h1>
        <p class="subtitulo-pagina">Estamos aqui para responder às suas questões</p>
        <div class="linha-decorativa-titulo"></div>
        </section>

        <section class="container-contatos">
            <article class="cartao">
                <h2><i class="fas fa-building"></i> Informações</h2>

                <div class="item-informacao">
                    <div class="icone-info"><i class="fas fa-location-dot"></i></div>
                    <address class="texto-info">
                        <?= nl2br(htmlspecialchars($config['endereco_completo'] ?? 'Distrito Urbano da Nova-Vida, Rua 130,<br>Município do Kilamba Kiaxi, Província de Luanda - Angola.')) ?>
                    </address>
                </div>

                <div class="item-informacao">
                    <div class="icone-info"><i class="fas fa-phone"></i></div>
                    <div class="texto-info">
                        <a href="tel:<?= preg_replace('/[^0-9]/', '', $config['telefone'] ?? '933096705') ?>">
                            <?= htmlspecialchars($config['telefone'] ?? '+244 933 096 705') ?>
                        </a>
                    </div>
                </div>

                <div class="item-informacao">
                    <div class="icone-info"><i class="fas fa-envelope"></i></div>
                    <div class="texto-info">
                        <a href="mailto:<?= $config['email_geral'] ?? 'geral@ipikk.ao' ?>">
                            <?= htmlspecialchars($config['email_geral'] ?? 'geral@ipikk.ao') ?>
                        </a>
                    </div>
                </div>

                <div class="item-informacao">
                    <div class="icone-info"><i class="fas fa-clock"></i></div>
                    <div class="texto-info">
                        <?= htmlspecialchars($config['horario_funcionamento'] ?? 'Segunda a Sexta · 08h00 – 17h00') ?>
                    </div>
                </div>
            </article>

            <article class="cartao">
                <h2><i class="fas fa-paper-plane"></i> Envie Mensagem</h2>

                <form class="formulario-contato" id="formularioContato" novalidate>
                    <input type="text" id="nome" name="nome" placeholder="Nome completo" required>
                    <input type="email" id="email" name="email" placeholder="Email" required>
                    <input type="text" id="assunto" name="assunto" placeholder="Assunto" required>
                    <textarea id="mensagem" name="mensagem" placeholder="Escreva a sua mensagem..." required></textarea>
                    <button type="submit" class="botao-enviar" id="botaoEnviar">Enviar Mensagem</button>
                </form>
            </article>
        </section>

        <section class="mapa-container">
            <article class="cartao">
                <h2><i class="fas fa-map-marker-alt"></i> Nossa Localização</h2>
                <iframe
                    class="mapa"
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3941.775737508126!2d13.221684474058835!3d-8.900430191155815!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x1a51f585cb9b5811%3A0x339cabe0139f60da!2sInstituto%20M%C3%A9dio%20Polit%C3%A9cnico%20do%20nova%20vida!5e0!3m2!1spt-PT!2sao!4v1770320133365!5m2!1spt-PT!2sao"
                    title="Mapa de localização do IPIKK"
                    allowfullscreen=""
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </article>
        </section>
    </main>

    <div class="botoes-flutuantes">
        <button class="botao-flutuante" id="botaoTopo" title="Voltar ao topo">
            <i class="fas fa-chevron-up"></i>
        </button>
        <?php if($config['whatsapp_numero']): ?>
        <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $config['whatsapp_numero']) ?>" class="botao-flutuante whatsapp" target="_blank" rel="noopener">
            <i class="fab fa-whatsapp"></i>
        </a>
        <?php endif; ?>
    </div>

<?php include __DIR__ . '/includes/footer.php'; ?>

    <script src="js/header-footer.js"></script>
    <script>
        function mostrarNotificacao(mensagem, tipo) {
            const notifExistente = document.querySelector('.notificacao-flutuante');
            if (notifExistente) notifExistente.remove();

            const notificacao = document.createElement('div');
            notificacao.className = `notificacao-flutuante ${tipo}`;
            const icone = tipo === 'sucesso' ? 'fa-check-circle' : 'fa-exclamation-triangle';
            notificacao.innerHTML = `<i class="fas ${icone}"></i><span>${mensagem}</span>`;
            document.body.appendChild(notificacao);
            setTimeout(() => notificacao.remove(), 5000);
        }

        const formulario = document.getElementById('formularioContato');
        const botaoEnviar = document.getElementById('botaoEnviar');

        formulario.addEventListener('submit', async function(e) {
            e.preventDefault();

            botaoEnviar.disabled = true;
            botaoEnviar.classList.add('enviando');
            botaoEnviar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';

            const formData = new FormData(formulario);

            try {
                const response = await fetch('contatos.php?processar=1', {
                    method: 'POST',
                    body: formData
                });
                const resultado = await response.json();

                if (resultado.success) {
                    mostrarNotificacao(resultado.message, 'sucesso');
                    formulario.reset();
                } else {
                    mostrarNotificacao(resultado.message, 'erro');
                }
            } catch (error) {
                mostrarNotificacao('Erro ao enviar mensagem. Tente novamente.', 'erro');
            } finally {
                botaoEnviar.disabled = false;
                botaoEnviar.classList.remove('enviando');
                botaoEnviar.innerHTML = 'Enviar Mensagem';
            }
        });
    </script>
</body>
</html>

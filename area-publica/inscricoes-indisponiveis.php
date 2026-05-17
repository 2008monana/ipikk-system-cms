<?php
/**
 * Página Inscrições Indisponíveis - IPIKK
 * Exibida quando as inscrições estão fechadas
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

// Verificar status das inscrições (se estiverem abertas, redirecionar)
$status_inscricoes = getDB()->query("SELECT status FROM controle_inscricoes WHERE id = 1")->fetch();
if ($status_inscricoes && $status_inscricoes['status'] === 'abertas') {
    header('Location: inscricoes.php');
    exit;
}

// Buscar conteúdo das inscrições (para textos dinâmicos)
$conteudo = getDB()->query("SELECT * FROM conteudo_inscricoes WHERE id = 1")->fetch();

// Se não houver dados, usar fallback
if (!$conteudo) {
    $conteudo = [
        'titulo_indisponivel' => 'Inscrições Indisponíveis',
        'msg_indisponivel' => 'O período de inscrições ainda não foi aberto ou já foi encerrado.',
        'texto_info_indisponivel' => 'As inscrições para o Instituto Médio Politécnico Industrial do Kilamba Kiaxi ocorrem em períodos específicos do ano letivo.

Como se inscrever quando estiver disponível: Dirija-se à secretaria da escola com os documentos necessários, escolha o curso pretendido e aguarde a data do teste de admissão.

Documentos necessários: Bilhete de Identidade, Certificado de Habilitações, 2 fotos tipo passe, Declaração de residência e Atestado Médico.

Fique atento às nossas redes sociais e comunicados oficiais para saber quando as inscrições serão abertas.',
        'proximo_periodo' => 'A ser divulgado em breve',
        'contacto_telefone' => $config['telefone'] ?? '+244 933 096 705',
        'contacto_email' => $config['email_geral'] ?? 'geral@ipikk.ao',
        'contacto_horario' => $config['horario_inscricoes'] ?? 'Segunda a Sexta, 8h às 16h',
        'contacto_endereco' => $config['endereco_completo'] ?? 'Nova Vida, Kilamba Kiaxi, Luanda - Angola'
    ];
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscrições Indisponíveis - IPIKK</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="<?= $config['favicon_url'] ?? 'foto/ipikk_new_logo.png' ?>" rel="icon">
    <link rel="stylesheet" href="css/header-footer.css">
    
    <style>

        /* ===== VARIÁVEIS ===== */
        :root {
            --ciano-suave: #008bb5;
            --ciano-claro: #e6f7ff;
            --ciano-escuro-suave: #006d8f;
            --gradiente-suave: linear-gradient(135deg, #008bb5 0%, #006d8f 100%);
            --cinza-claro-bg: #f5f8fa;
            --texto-principal: #2c3e50;
            --texto-secundario: #5a6b7a;
            --branco: #ffffff;
            --transition: all 0.3s ease;
            --verde-sucesso: #28a745;
            --verde-claro: #e8f5e9;
            --laranja-destaque: #ff6b35;
            --laranja-claro: #fff3e6;
            --roxo-matricula: #6f42c1;
            --roxo-claro: #f3e8ff;
            --amarelo-vagas: #f39c12;
            --amarelo-claro: #fff8e7;
            --azul-info: #3498db;
            --cinza-claro-bg: #f5f8fa;
            --texto-principal: #2c3e50;
            --texto-secundario: #5a6b7a;
            --branco: #ffffff;
            --transition: all 0.3s ease;
            --vermelho-alerta: #dc3545;
            --vermelho-claro: #fff0f0;
        }

                /* ===== CONTEÚDO PRINCIPAL - INSCRIÇÕES INDISPONÍVEIS ===== */
        .main-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 60px 20px;
        }

        .card-indisponivel {
            max-width: 700px;
            width: 100%;
            background: var(--branco);
            border-radius: 40px;
            padding: 50px 45px;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(0, 139, 181, 0.1);
            animation: fadeInUp 0.6s ease;
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

        .icone-status {
            width: 120px;
            height: 120px;
            background: var(--vermelho-claro);
            border-radius: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
        }

        .icone-status i {
            font-size: 4rem;
            color: var(--vermelho-alerta);
        }

        h1 {
            font-family: 'Poppins', sans-serif;
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 15px;
            color: var(--texto-principal);
        }

        .subtitulo {
            font-size: 1.1rem;
            color: var(--texto-secundario);
            margin-bottom: 25px;
        }

        .data-periodo {
            background: var(--vermelho-claro);
            padding: 15px 20px;
            border-radius: 20px;
            margin: 20px 0;
            display: inline-block;
        }

        .data-periodo i {
            color: var(--vermelho-alerta);
            margin-right: 10px;
        }

        .data-periodo span {
            color: var(--vermelho-alerta);
            font-weight: 600;
        }

        .mensagem-info {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 24px;
            margin: 25px 0;
            text-align: left;
            border-left: 4px solid var(--ciano-suave);
        }

        .mensagem-info p {
            margin-bottom: 15px;
            color: var(--texto-secundario);
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .mensagem-info p:last-child {
            margin-bottom: 0;
        }

        .mensagem-info i {
            width: 24px;
            color: var(--ciano-suave);
            margin-top: 3px;
        }

        .mensagem-info strong {
            color: var(--texto-principal);
        }

        .contato-destaque {
            background: #e8f5e8;
            padding: 20px;
            border-radius: 20px;
            margin: 25px 0;
        }

        .contato-destaque p {
            color: #2e7d32;
            font-weight: 500;
            margin-bottom: 10px;
        }

        .contato-destaque p:last-child {
            margin-bottom: 0;
        }

        .contato-destaque i {
            margin-right: 10px;
        }

        .botoes {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 30px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 28px;
            border-radius: 40px;
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition);
            font-size: 0.95rem;
            border: none;
            cursor: pointer;
        }

        .btn-primario {
            background: var(--gradiente-suave);
            color: var(--branco);
        }

        .btn-primario:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 139, 181, 0.3);
        }

        .btn-whatsapp {
            background: #25D366;
            color: var(--branco);
        }

        .btn-whatsapp:hover {
            background: #128C7E;
            transform: translateY(-3px);
        }

        .btn-secundario {
            background: var(--ciano-claro);
            color: var(--ciano-suave);
        }

        .btn-secundario:hover {
            background: #d4ecf5;
            transform: translateY(-3px);
        }

        .contatos-footer {
            margin-top: 30px;
            padding-top: 25px;
            border-top: 1px solid #eef2f6;
            display: flex;
            justify-content: center;
            gap: 25px;
            flex-wrap: wrap;
        }

        .contatos-footer a {
            color: var(--texto-secundario);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
            font-size: 0.9rem;
        }

        .contatos-footer a:hover {
            color: var(--ciano-suave);
        }


        /* ===== RESPONSIVIDADE ===== */
        @media (max-width: 1200px) {
            .conteudo-rodape {
                grid-template-columns: 1fr 1fr;
                gap: 30px;
            }
        }

        @media (max-width: 992px) {
            .menu-navegacao {
                display: none;
            }
            .botao-menu-mobile {
                display: flex;
            }
            .card-indisponivel {
                padding: 40px 30px;
            }
            h1 {
                font-size: 1.8rem;
            }
        }

        @media (max-width: 768px) {
            .conteudo-superior {
                flex-direction: column;
                text-align: center;
                padding: 0 20px;
            }
            .conteudo-rodape {
                grid-template-columns: 1fr;
                gap: 30px;
            }
            .icone-status {
                width: 90px;
                height: 90px;
            }
            .icone-status i {
                font-size: 3rem;
            }
            .botoes {
                flex-direction: column;
            }
            .btn {
                justify-content: center;
            }
            .card-indisponivel {
                padding: 35px 25px;
            }
        }

        @media (max-width: 480px) {
            .sidebar-mobile {
                width: 280px;
            }
            h1 {
                font-size: 1.5rem;
            }
            .subtitulo {
                font-size: 1rem;
            }
            .contatos-footer {
                flex-direction: column;
                align-items: center;
                gap: 12px;
            }
        }
    </style>
</head>
<body>


<?php include __DIR__ . '/includes/header.php'; ?>

    <!-- CONTEÚDO PRINCIPAL - INSCRIÇÕES INDISPONÍVEIS -->
    <main class="main-content">
        <div class="card-indisponivel">
            <div class="icone-status">
                <i class="fas fa-calendar-times"></i>
            </div>
            
            <h1><?= htmlspecialchars($conteudo['titulo_indisponivel'] ?? 'Inscrições Indisponíveis') ?></h1>
            <p class="subtitulo"><?= htmlspecialchars($conteudo['msg_indisponivel'] ?? 'O período de inscrições ainda não foi aberto ou já foi encerrado.') ?></p>

            <div class="data-periodo">
                <i class="fas fa-calendar-alt"></i>
                <span>Próximo período de inscrições:</span> <?= htmlspecialchars($conteudo['proximo_periodo'] ?? 'A ser divulgado em breve') ?>
            </div>

            <div class="mensagem-info">
                <?php 
                $paragrafos = explode("\n\n", $conteudo['texto_info_indisponivel'] ?? '');
                foreach($paragrafos as $paragrafo): 
                    if(trim($paragrafo) != ''):
                ?>
                <p><i class="fas fa-info-circle"></i> <?= nl2br(htmlspecialchars(trim($paragrafo))) ?></p>
                <?php 
                    endif;
                endforeach; 
                ?>
            </div>

            <div class="contato-destaque">
                <p><i class="fas fa-phone-alt"></i> <strong>Secretaria do IPIKK</strong></p>
                <p><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($conteudo['contacto_endereco'] ?? 'Nova Vida, Kilamba Kiaxi, Luanda - Angola') ?></p>
                <p><i class="fas fa-clock"></i> Atendimento: <?= htmlspecialchars($conteudo['contacto_horario'] ?? 'Segunda a Sexta, 8h às 16h') ?></p>
                <p><i class="fas fa-phone"></i> Telefone: <?= htmlspecialchars($conteudo['contacto_telefone'] ?? '+244 933 096 705') ?></p>
                <p><i class="fas fa-envelope"></i> Email: <?= htmlspecialchars($conteudo['contacto_email'] ?? 'geral@ipikk.ao') ?></p>
            </div>

            <div class="botoes">
                <a href="contatos.php" class="btn btn-secundario">
                    <i class="fas fa-envelope"></i> Página de Contactos
                </a>
            </div>

            <div class="contatos-footer">
                <a href="<?= $config['rede_social_facebook'] ?? '#' ?>" target="_blank"><i class="fab fa-facebook"></i> Facebook</a>
                <a href="<?= $config['rede_social_instagram'] ?? '#' ?>" target="_blank"><i class="fab fa-instagram"></i> Instagram</a>
                <a href="mailto:<?= $config['email_geral'] ?? 'geral@ipikk.ao' ?>"><i class="fas fa-envelope"></i> <?= $config['email_geral'] ?? 'geral@ipikk.ao' ?></a>
            </div>
        </div>
    </main>

<?php include __DIR__ . '/includes/footer.php'; ?>
    <script src="js/header-footer.js"></script>
</body>
</html>
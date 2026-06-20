<?php
/**
 * Página Escolas Afiliadas - IPIKK
 */

require_once '../config/index.php';

// Buscar configurações do site
$config = getDB()->query("SELECT * FROM configuracoes WHERE id = 1")->fetch();

// Buscar conteúdo da página (JSON da tabela conteudo_paginas)
$pagina = getPagina('escolas-afiliadas');

// ============================================
// BUSCAR ESCOLAS AFILIADAS DO BANCO DE DADOS
// ============================================
$escolas = getDB()->query("
    SELECT * FROM escolas_afiliadas
    WHERE ativo = 1
    ORDER BY ordem
")->fetchAll();

// Extrair dados da página
$titulo = $pagina['titulo'] ?? 'Escolas Afiliadas';
$subtitulo = $pagina['subtitulo'] ?? 'Lista das instituições de ensino parceiras e seus respectivos contactos.';

// Verificar status das inscrições para o botão de matrícula
$status_inscricoes = getDB()->query("SELECT status FROM controle_inscricoes WHERE id = 1")->fetch();
$link_inscricao = ($status_inscricoes && $status_inscricoes['status'] === 'abertas') ? 'inscricoes' : 'inscricoes-indisponiveis';
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IPIKK - Escolas Afiliadas</title>

    <!-- Fontes e Ícones -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Favicon -->
    <link href="<?= $config['favicon_url'] ?? 'foto/ipikk_new_logo.png' ?>" rel="icon">

    <!-- CSS Header/Footer Padrão -->
    <link rel="stylesheet" href="css/header-footer.css">

    <style>
        :root {
            --azul-principal: #003072;
            --azul-escuro: #001a40;
            --verde-acento: #0a9396;
            --branco: #ffffff;
            --cinza-claro: #f8f9fa;
            --cinza: #6c757d;
            --texto-principal: #2c3e50;
            --borda-raio: 18px;
            --sombra-card: 0 18px 45px rgba(0, 48, 114, 0.12);
            --transicao: all 0.3s ease;
        }

        body {
            background: var(--cinza-claro);
        }

        .container-escolas {
            max-width: 1200px;
            margin: 50px auto 70px;
            padding: 0 20px;
        }

        .intro-secao {
            text-align: center;
            margin-bottom: 42px;
        }

        .intro-secao h2 {
            color: var(--azul-principal);
            font-family: 'Poppins', sans-serif;
            font-size: 2.5rem;
            margin-bottom: 12px;
            font-weight: 700;
        }

        .linha-decorativa {
            width: 80px;
            height: 4px;
            background: linear-gradient(90deg, var(--azul-principal), var(--verde-acento));
            margin: 0 auto 18px;
            border-radius: 999px;
        }

        .intro-secao p {
            color: var(--cinza);
            font-size: 1.05rem;
            max-width: 740px;
            margin: 0 auto;
            line-height: 1.7;
        }

        .grade-escolas {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 28px;
        }

        .card-escola {
            background: var(--branco);
            border-radius: var(--borda-raio);
            padding: 34px 26px 28px;
            text-align: center;
            box-shadow: var(--sombra-card);
            border: 1px solid rgba(0, 48, 114, 0.08);
            position: relative;
            overflow: hidden;
            transition: var(--transicao);
        }

        .card-escola:hover {
            transform: translateY(-8px);
            box-shadow: 0 24px 55px rgba(0, 48, 114, 0.18);
        }

        .foto-escola {
            width: 132px;
            height: 132px;
            border-radius: 50%;
            margin: 0 auto 22px;
            padding: 5px;
            background: linear-gradient(155deg, var(--azul-principal) 0%, var(--verde-acento) 100%);
            box-shadow: 0 12px 28px rgba(0, 48, 114, 0.18);
        }

        .foto-escola img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            background: var(--branco);
            border: 4px solid var(--branco);
        }

        .card-escola h3 {
            font-family: 'Poppins', sans-serif;
            color: var(--azul-principal);
            font-size: 1.22rem;
            line-height: 1.35;
            margin-bottom: 12px;
        }

        .badge-escola {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            padding: 7px 16px;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .badge-privado {
            background: rgba(0, 48, 114, 0.08);
            color: var(--azul-principal);
        }

        .badge-publico {
            background: rgba(10, 147, 150, 0.12);
            color: #08787a;
        }

        .info-escola {
            display: flex;
            flex-direction: column;
            gap: 13px;
            text-align: left;
        }

        .info-item {
            display: grid;
            grid-template-columns: 34px 1fr;
            gap: 12px;
            align-items: flex-start;
            color: var(--texto-principal);
        }

        .info-item i {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(10, 147, 150, 0.11);
            color: var(--verde-acento);
            font-size: 0.95rem;
        }

        .info-item strong {
            display: block;
            color: var(--azul-principal);
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin-bottom: 2px;
        }

        .info-item span,
        .info-item a {
            color: #55606b;
            font-size: 0.95rem;
            line-height: 1.5;
            text-decoration: none;
            word-break: break-word;
        }

        .info-item a:hover {
            color: var(--verde-acento);
        }

        .link-site-escola {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 24px;
            padding: 11px 20px;
            border-radius: 999px;
            color: var(--branco);
            background: linear-gradient(155deg, var(--azul-principal) 0%, var(--verde-acento) 100%);
            font-weight: 700;
            text-decoration: none;
            transition: var(--transicao);
        }

        .link-site-escola:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(0, 48, 114, 0.2);
        }

        .mensagem-vazia {
            max-width: 760px;
            margin: 34px auto 60px;
            padding: 34px 28px;
            text-align: center;
            color: #6c757d;
            background: #fff;
            border: 1px solid rgba(0, 48, 114, 0.08);
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07);
            font-size: 1rem;
            line-height: 1.7;
        }

        .mensagem-vazia i {
            display: block;
            color: var(--verde-acento);
            font-size: 2rem;
            margin-bottom: 12px;
        }

        @media (max-width: 600px) {
            .container-escolas {
                margin-top: 34px;
            }

            .intro-secao h2 {
                font-size: 2rem;
            }

            .card-escola {
                padding: 30px 22px 24px;
            }

            .foto-escola {
                width: 116px;
                height: 116px;
            }
        }
    </style>
</head>
<body>

<?php include __DIR__ . '/includes/header.php'; ?>

    <!-- ===== CONTEÚDO PRINCIPAL ===== -->
    <section class="container-escolas">
        <div class="intro-secao">
            <h2><?= htmlspecialchars($titulo) ?></h2>
            <div class="linha-decorativa"></div>
            <p><?= htmlspecialchars($subtitulo) ?></p>
        </div>

        <?php if (empty($escolas)): ?>
        <div class="mensagem-vazia">
            <i class="fas fa-info-circle"></i>
            Sem informações de escolas afiliadas no momento.
        </div>
        <?php else: ?>
        <div class="grade-escolas">
            <?php foreach($escolas as $index => $escola):
                $tipo_escola = $escola['tipo'] ?? 'Privado';
                $badge_class = $tipo_escola === 'Privado' ? 'badge-privado' : 'badge-publico';
                $logo_url = !empty($escola['logo_url'])
                    ? $escola['logo_url']
                    : 'foto/sem_logo.png';
            ?>
            <article class="card-escola" data-id="<?= $escola['id'] ?? $index ?>">
                <div class="foto-escola">
                    <img src="<?= htmlspecialchars($logo_url) ?>" alt="<?= htmlspecialchars($escola['nome']) ?>" onerror="this.src='foto/sem_logo.png'">
                </div>

                <h3><?= htmlspecialchars($escola['nome']) ?></h3>
                <span class="badge-escola <?= $badge_class ?>">
                    <i class="fas fa-school"></i>
                    <?= htmlspecialchars($tipo_escola) ?>
                </span>

                <div class="info-escola">
                    <?php if(!empty($escola['email'])): ?>
                    <div class="info-item">
                        <i class="fas fa-envelope"></i>
                        <div>
                            <strong>Email</strong>
                            <a href="mailto:<?= htmlspecialchars($escola['email']) ?>"><?= htmlspecialchars($escola['email']) ?></a>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if(!empty($escola['telefone1']) || !empty($escola['telefone2'])): ?>
                    <div class="info-item">
                        <i class="fas fa-phone"></i>
                        <div>
                            <strong>Telefone</strong>
                            <span>
                                <?= htmlspecialchars($escola['telefone1'] ?? '') ?>
                                <?php if(!empty($escola['telefone2'])): ?>
                                / <?= htmlspecialchars($escola['telefone2']) ?>
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if(!empty($escola['endereco'])): ?>
                    <div class="info-item">
                        <i class="fas fa-location-dot"></i>
                        <div>
                            <strong>Endereço</strong>
                            <span><?= nl2br(htmlspecialchars($escola['endereco'])) ?></span>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if(!empty($escola['site_url'])): ?>
                <a class="link-site-escola" href="<?= htmlspecialchars($escola['site_url']) ?>" target="_blank" rel="noopener noreferrer">
                    <i class="fas fa-arrow-up-right-from-square"></i> Visitar site
                </a>
                <?php endif; ?>
            </article>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </section>

    <!-- ===== BOTÕES FLUTUANTES ===== -->
    <div class="botoes-flutuantes">
        <button class="botao-flutuante" id="botaoTopo" title="Voltar ao topo">
            <i class="fas fa-chevron-up"></i>
        </button>
        <?php include __DIR__ . '/includes/botao-whatsapp.php'; ?>
    </div>

<?php include __DIR__ . '/includes/footer.php'; ?>

    <script src="js/header-footer.js"></script>
</body>
</html>

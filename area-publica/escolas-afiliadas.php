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
$link_inscricao = ($status_inscricoes && $status_inscricoes['status'] === 'abertas') ? 'inscricoes.php' : 'inscricoes-indisponiveis.php';
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
        /* ========= CONTAINER DA SEÇÃO ================ */
        .container-escolas {
            max-width: 800px;
            margin: 50px auto;
            padding: 0 20px;
        }

        /* Introdução (Título e Texto) */
        .intro-secao {
            text-align: center;
            margin-bottom: 40px;
        }

        .intro-secao h2 {
            color: #003366;
            font-size: 28px;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .linha-decorativa {
            width: 60px;
            height: 4px;
            background-color: #008080;
            margin: 0 auto 20px auto;
            border-radius: 2px;
        }

        .intro-secao p {
            color: #888;
            font-size: 14px;
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.6;
        }

        /* ====== ESTRUTURA DO ACORDEÃO ============== */
        .escola_afiliada {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .escola_afiliada-item {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            overflow: hidden;
            transition: box-shadow 0.3s ease, transform 0.3s ease;
        }

        /* ======== CABEÇALHO (PARTE CLICÁVEL) ========== */
        .escola_afiliada-cabecalho {
            padding: 20px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            background-color: #fff;
            user-select: none;
            transition: background 0.4s ease, color 0.4s ease;
        }

        .titulo-escola {
            font-weight: 500;
            color: #003366;
            font-size: 16px;
            flex: 1;
            margin-right: 15px;
            transition: color 0.4s ease;
        }

        .meta-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        /* Badge "Privado" */
        .badge-privado {
            background-color: #eef2f5;
            color: #556b7f;
            font-size: 12px;
            font-weight: 700;
            padding: 6px 14px;
            border-radius: 50px;
            transition: all 0.4s ease;
        }

        .badge-publico {
            background-color: #e8f5e9;
            color: #2e7d32;
            font-size: 12px;
            font-weight: 700;
            padding: 6px 14px;
            border-radius: 50px;
            transition: all 0.4s ease;
        }

        /* Seta (Círculo com ícone) */
        .escola_afiliada-seta {
            width: 32px;
            height: 32px;
            border: 2px solid #ddd;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #888;
            transition: all 0.4s cubic-bezier(0.25, 1, 0.5, 1);
        }

        .escola_afiliada-seta svg {
            width: 16px;
            height: 16px;
        }

        /* ========= CONTEÚDO (ANIMAÇÃO SUAVE) ============= */
        .escola_afiliada-conteudo {
            max-height: 0;
            opacity: 0;
            overflow: hidden;
            padding: 0 25px;
            background-color: #fff;
            transition: max-height 0.5s cubic-bezier(0.25, 1, 0.5, 1),
                        opacity 0.4s ease-in-out,
                        padding 0.5s cubic-bezier(0.25, 1, 0.5, 1);
            will-change: max-height, opacity, padding;
        }

        /* Estilização das linhas de informação */
        .info-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px solid #f0f0f0;
            position: relative;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        /* Container esquerdo com ícone e texto */
        .info-row-left {
            display: flex;
            align-items: flex-start;
            flex: 1;
        }

        .icon-box {
            color: #008080;
            margin-right: 15px;
            min-width: 24px;
            margin-top: 2px;
        }

        .icon-box svg {
            width: 20px;
            height: 20px;
        }

        .info-text {
            display: flex;
            flex-direction: column;
        }

        .info-text strong {
            color: #006680;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }

        .info-text span {
            color: #555;
            font-size: 15px;
            line-height: 1.5;
        }

        /* ===== CÍRCULO COM LOGO DA ESCOLA ===== */
        .circulo-logo {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            overflow: hidden;
            background: linear-gradient(135deg, #003366, #008080);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-left: 15px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border: 2px solid rgba(255,255,255,0.5);
        }

        .circulo-logo:hover {
            transform: scale(1.08);
            box-shadow: 0 4px 12px rgba(0,51,102,0.3);
            border-color: #ffc107;
        }

        .circulo-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Para as outras info-rows (email, telefone, endereço) não terem o círculo */
        .info-row:not(:first-child) .circulo-logo {
            display: none;
        }

        /* ============= ESTADO ATIVO (QUANDO ABERTO) ====================== */
        .escola_afiliada-item.active {
            box-shadow: 0 15px 30px rgba(0, 51, 102, 0.15);
            transform: translateY(-2px);
        }

        .escola_afiliada-item.active .escola_afiliada-cabecalho {
            background: linear-gradient(135deg, #003366 0%, #006680 100%);
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .escola_afiliada-item.active .titulo-escola {
            color: #ffffff;
        }

        .escola_afiliada-item.active .badge-privado,
        .escola_afiliada-item.active .badge-publico {
            background-color: rgba(255, 255, 255, 0.2);
            color: #ffffff;
        }

        .escola_afiliada-item.active .escola_afiliada-seta {
            transform: rotate(180deg);
            border-color: rgba(255,255,255,0.5);
            color: #ffffff;
            background-color: rgba(255,255,255,0.1);
        }

        .escola_afiliada-item.active .escola_afiliada-conteudo {
            opacity: 1;
            padding-top: 20px;
            padding-bottom: 25px;
        }

        /* ================== RESPONSIVIDADE ======================= */
        @media (max-width: 600px) {
            .escola_afiliada-cabecalho {
                flex-wrap: wrap;
            }
            .titulo-escola {
                width: 100%;
                margin-bottom: 10px;
                margin-right: 0;
            }
            .meta-info {
                width: 100%;
                justify-content: space-between;
            }
            .circulo-logo {
                width: 40px;
                height: 40px;
            }
            .info-row {
                flex-wrap: wrap;
            }
            .info-row-left {
                flex: 1;
            }
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
            color: #0a9396;
            font-size: 2rem;
            margin-bottom: 12px;
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
        <div class="escola_afiliada">
            <?php foreach($escolas as $index => $escola):
                $badge_class = ($escola['tipo'] ?? 'Privado') == 'Privado' ? 'badge-privado' : 'badge-publico';
                $logo_url = !empty($escola['logo_url'])
                    ? $escola['logo_url']
                    : 'foto/sem_logo.png';
            ?>
            <div class="escola_afiliada-item" data-id="<?= $escola['id'] ?? $index ?>">
                <div class="escola_afiliada-cabecalho">
                    <span class="titulo-escola"><?= htmlspecialchars($escola['nome']) ?></span>
                    <div class="meta-info">
                        <span class="<?= $badge_class ?>"><?= htmlspecialchars($escola['tipo'] ?? 'Privado') ?></span>
                        <span class="escola_afiliada-seta">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </span>
                    </div>
                </div>
                <div class="escola_afiliada-conteudo">

                    <!-- Nome (com círculo) -->
                    <div class="info-row">
                        <div class="info-row-left">
                            <div class="icon-box">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M5 21V7l8-4 8 4v14M8 21v-4h8v4"/></svg>
                            </div>
                            <div class="info-text">
                                <strong>Nome da Escola</strong>
                                <span><?= htmlspecialchars($escola['nome']) ?></span>
                            </div>
                        </div>
                        <div class="circulo-logo">
                            <img src="<?= htmlspecialchars($logo_url) ?>" alt="<?= htmlspecialchars($escola['nome']) ?>" onerror="this.src='foto/sem_logo.png'">
                        </div>
                    </div>

                    <!-- Email -->
                    <?php if(!empty($escola['email'])): ?>
                    <div class="info-row">
                        <div class="info-row-left">
                            <div class="icon-box">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                            </div>
                            <div class="info-text">
                                <strong>Email</strong>
                                <span><?= htmlspecialchars($escola['email']) ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Telefone(s) -->
                    <?php if(!empty($escola['telefone1']) || !empty($escola['telefone2'])): ?>
                    <div class="info-row">
                        <div class="info-row-left">
                            <div class="icon-box">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                            </div>
                            <div class="info-text">
                                <strong>Telefone</strong>
                                <span>
                                    <?= htmlspecialchars($escola['telefone1']) ?>
                                    <?php if(!empty($escola['telefone2'])): ?>
                                    / <?= htmlspecialchars($escola['telefone2']) ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Endereço -->
                    <?php if(!empty($escola['endereco'])): ?>
                    <div class="info-row">
                        <div class="info-row-left">
                            <div class="icon-box">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                            </div>
                            <div class="info-text">
                                <strong>Endereço</strong>
                                <span><?= nl2br(htmlspecialchars($escola['endereco'])) ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </section>

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
        // ===== ACORDEÃO - FUNCIONALIDADE =====
        document.addEventListener("DOMContentLoaded", function() {
            const items = document.querySelectorAll(".escola_afiliada-item");

            items.forEach(item => {
                const header = item.querySelector(".escola_afiliada-cabecalho");
                const content = item.querySelector(".escola_afiliada-conteudo");

                header.addEventListener("click", () => {
                    const isOpen = item.classList.contains("active");

                    // Fechar todos os outros itens
                    items.forEach(otherItem => {
                        if (otherItem !== item && otherItem.classList.contains("active")) {
                            otherItem.classList.remove("active");
                            const otherContent = otherItem.querySelector(".escola_afiliada-conteudo");
                            otherContent.style.maxHeight = null;
                        }
                    });

                    // Abrir/fechar o item atual
                    if (isOpen) {
                        item.classList.remove("active");
                        content.style.maxHeight = null;
                    } else {
                        item.classList.add("active");
                        content.style.maxHeight = content.scrollHeight + 50 + "px";
                    }
                });
            });
        });
    </script>
</body>
</html>

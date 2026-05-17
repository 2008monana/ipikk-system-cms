<?php
/**
 * Página Funcionário Destacado - IPIKK
 */

require_once '../config/index.php';

// Buscar configurações do site
$config = getDB()->query("SELECT * FROM configuracoes WHERE id = 1")->fetch();

// Buscar conteúdo da página (JSON da tabela conteudo_paginas)
$pagina = getPagina('funcionarios');

// Buscar ano lectivo atual
$ano_lectivo = $config['ano_lectivo_atual'] ?? date('Y') . '/' . (date('Y') + 1);

// ============================================
// BUSCAR FUNCIONÁRIOS EM DESTAQUE DO BANCO DE DADOS
// ============================================

// Funcionários do Grupo 1 (primeira linha)
$funcionarios_grupo1 = getDB()->prepare("
    SELECT * FROM funcionarios_destaque
    WHERE ativo = 1 AND grupo = 1
    ORDER BY ordem
");
$funcionarios_grupo1->execute();
$funcionarios_grupo1 = $funcionarios_grupo1->fetchAll();

// Funcionários do Grupo 2 (segunda linha)
$funcionarios_grupo2 = getDB()->prepare("
    SELECT * FROM funcionarios_destaque
    WHERE ativo = 1 AND grupo = 2
    ORDER BY ordem
");
$funcionarios_grupo2->execute();
$funcionarios_grupo2 = $funcionarios_grupo2->fetchAll();

// ============================================
// CONTEÚDO EDITÁVEL DA PÁGINA
// ============================================

// Extrair dados da página
$hero_titulo = $pagina['hero_titulo'] ?? 'Funcionários Destacados';
$hero_subtitulo = $pagina['hero_subtitulo'] ?? 'Reconhecimento pela dedicação, excelência e contribuição ao ensino técnico-profissional';
$faixa_texto = $pagina['faixa_texto'] ?? 'O IPIKK reconhece e valoriza todos os seus colaboradores pelo empenho e dedicação no desenvolvimento do ensino técnico-profissional em Angola.';

$tem_funcionarios = !empty($funcionarios_grupo1) || !empty($funcionarios_grupo2);

// Verificar status das inscrições para o botão de matrícula
$status_inscricoes = getDB()->query("SELECT status FROM controle_inscricoes WHERE id = 1")->fetch();
$link_inscricao = ($status_inscricoes && $status_inscricoes['status'] === 'abertas') ? 'inscricoes.php' : 'inscricoes-indisponiveis.php';
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IPIKK - Funcionário Destacado</title>

    <!-- Fontes e Ícones -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Favicon -->
    <link href="<?= $config['favicon_url'] ?? 'foto/ipikk_new_logo.png' ?>" rel="icon">

    <!-- CSS Header/Footer Padrão -->
    <link rel="stylesheet" href="css/header-footer.css">

    <style>
        /* ===== VARIÁVEIS ===== */
            :root {
                --azul-principal: #003072;
                --azul-claro: #2e86c1;
                --azul-escuro: #001a40;
                --verde-acento: #0a9396;
                --verde-claro: #94d2bd;
                --branco: #ffffff;
                --cinza-claro: #f8f9fa;
                --cinza: #6c757d;
                --cinza-escuro: #212529;
                --ouro: #b8923a;
                --ouro-claro: #e8b84b;
                --raio: 12px;
                --sombra-cartao: 0 4px 20px rgba(0, 48, 114, 0.09);
                --sombra-hover: 0 12px 36px rgba(0, 48, 114, 0.16);
                --transicao: all 0.3s ease;
            }

            /* ===== HEROI ===== */
            .fd-heroi {
                background: linear-gradient(155deg, var(--azul-principal) 0%, var(--verde-acento) 100%);
                padding: 64px 24px 52px;
                text-align: center;
                position: relative;
                overflow: hidden;
            }

            .fd-heroi::before {
                content: '';
                position: absolute;
                inset: 0;
                background-image: repeating-linear-gradient(
                    -45deg,
                    transparent,
                    transparent 52px,
                    rgba(255,255,255,0.016) 52px,
                    rgba(255,255,255,0.016) 53px
                );
                pointer-events: none;
            }

            .fd-heroi::after {
                content: '';
                position: absolute;
                bottom: 0;
                left: 0;
                right: 0;
                height: 3px;
                background: linear-gradient(90deg, transparent 0%, var(--verde-acento) 25%, var(--ouro-claro) 50%, var(--verde-acento) 75%, transparent 100%);
            }

            .fd-heroi__inner {
                max-width: 720px;
                margin: 0 auto;
                position: relative;
                z-index: 1;
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 16px;
            }

            .fd-etiqueta {
                display: inline-flex;
                align-items: center;
                gap: 7px;
                background: rgba(255,255,255,0.09);
                border: 1px solid rgba(255,255,255,0.18);
                color: var(--verde-claro);
                font-size: 0.7rem;
                font-weight: 700;
                letter-spacing: 2px;
                text-transform: uppercase;
                padding: 6px 18px;
                border-radius: 30px;
            }

            .fd-etiqueta i {
                font-size: 0.8rem;
                color: var(--ouro-claro);
            }

            .fd-heroi__titulo {
                font-family: 'Poppins', sans-serif;
                font-size: clamp(2rem, 5vw, 3.2rem);
                font-weight: 900;
                color: var(--branco);
                letter-spacing: 1px;
                text-transform: uppercase;
                line-height: 1.15;
                margin: 0;
            }

            .fd-heroi__titulo em {
                font-style: normal;
                color: var(--ouro-claro);
            }

            .fd-heroi__divisor {
                display: flex;
                align-items: center;
                gap: 10px;
                color: var(--ouro-claro);
                font-size: 0.85rem;
            }

            .fd-heroi__linha {
                display: block;
                width: 120px;
                height: 1px;
                background: linear-gradient(90deg, transparent, var(--ouro-claro), transparent);
            }

            .fd-heroi__subtitulo {
                font-size: 0.95rem;
                color: rgba(255,255,255,0.58);
                font-style: italic;
                max-width: 520px;
                line-height: 1.7;
                margin: 0;
            }

            /* ===== GRADES - CARDS EM LINHA ===== */
            .fd-grade-wrapper {
                padding: 60px 24px;
            }

            .fd-grade-wrapper--azul {
                background: linear-gradient(135deg, #f5f7fa 0%, #ffffff 100%);
            }

            .fd-grade-wrapper--claro {
                background: #f8fafd;
            }

            .fd-grade {
                max-width: 1100px;
                margin: 0 auto;
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
                gap: 28px;
            }

            .fd-grade--4 {
                justify-content: center;
            }

            .fd-grade--5 {
                justify-content: center;
            }

            /* ===== CARTÃO DE FUNCIONÁRIO ===== */
            .fd-cartao {
                background: var(--branco);
                border-radius: var(--raio);
                overflow: hidden;
                box-shadow: var(--sombra-cartao);
                border: 1px solid rgba(0, 48, 114, 0.07);
                width: 210px;
                display: flex;
                flex-direction: column;
                transition: var(--transicao);
            }

            .fd-cartao:hover {
                transform: translateY(-6px);
                box-shadow: var(--sombra-hover);
            }

            .fd-cartao__foto-area {
                background: linear-gradient(160deg, #d0d9e8, #b8c4d6);
                display: flex;
                align-items: flex-end;
                justify-content: center;
                padding: 18px 14px 0;
                position: relative;
                min-height: 170px;
            }

            .fd-cartao__foto {
                width: 100%;
                max-width: 120px;
                height: 148px;
                object-fit: cover;
                object-position: top;
                border-radius: 8px 8px 0 0;
                border: 3px solid var(--branco);
                border-bottom: none;
                display: block;
                box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.08);
                background: #c8d2e0;
            }

            .fd-cartao__foto.fd-sem-foto {
                display: none;
            }

            .fd-cartao__foto-icon {
                display: none;
            }

            .fd-cartao__foto.fd-sem-foto ~ .fd-cartao__foto-icon {
                display: flex;
                align-items: center;
                justify-content: center;
                width: 100%;
                max-width: 120px;
                height: 148px;
                background: linear-gradient(160deg, #d0d9e8, #b8c4d6);
                border-radius: 8px 8px 0 0;
                border: 3px solid var(--branco);
                border-bottom: none;
                font-size: 3.4rem;
                color: rgba(0, 48, 114, 0.25);
            }

            .fd-cartao__info {
                padding: 14px 14px 16px;
                text-align: center;
                display: flex;
                flex-direction: column;
                gap: 5px;
                flex: 1;
            }

            .fd-cartao__nome {
                font-family: 'Poppins', sans-serif;
                font-size: 0.85rem;
                font-weight: 700;
                color: var(--azul-principal);
                text-transform: uppercase;
                letter-spacing: 0.3px;
                line-height: 1.3;
                display: block;
            }

            .fd-cartao__cargo {
                font-size: 0.73rem;
                color: var(--cinza);
                line-height: 1.5;
            }

            .fd-cartao__linha {
                width: 28px;
                height: 3px;
                background: linear-gradient(90deg, var(--azul-principal), var(--azul-claro));
                border-radius: 2px;
                margin: 5px auto 0;
            }

            .fd-cartao__linha--teal {
                background: linear-gradient(90deg, var(--verde-acento), var(--verde-claro));
            }

            /* ===== FAIXA DE RECONHECIMENTO ===== */
            .fd-reconhecimento {
                background: linear-gradient(135deg, var(--azul-principal) 0%, #004a99 50%, var(--azul-principal) 100%);
                padding: 50px 24px;
                text-align: center;
                position: relative;
                overflow: hidden;
            }

            .fd-reconhecimento::before {
                content: '★★★';
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                font-size: 12rem;
                color: rgba(255, 255, 255, 0.03);
                pointer-events: none;
                letter-spacing: 3rem;
                white-space: nowrap;
            }

            .fd-reconhecimento__inner {
                max-width: 640px;
                margin: 0 auto;
                position: relative;
                z-index: 1;
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 16px;
            }

            .fd-reconhecimento__icone {
                font-size: 2.2rem;
                color: var(--ouro-claro);
            }

            .fd-reconhecimento__texto {
                font-family: 'Poppins', sans-serif;
                font-size: clamp(0.9rem, 1.8vw, 1.05rem);
                font-style: italic;
                font-weight: 500;
                color: rgba(255, 255, 255, 0.82);
                line-height: 1.8;
            }

            .mensagem-vazia {
                max-width: 760px;
                margin: 40px auto 70px;
                padding: 34px 28px;
                text-align: center;
                color: var(--cinza);
                background: var(--branco);
                border: 1px solid rgba(0, 48, 114, 0.08);
                border-radius: var(--raio);
                box-shadow: var(--sombra-cartao);
                font-size: 1rem;
            }

            .mensagem-vazia i {
                display: block;
                color: var(--verde-acento);
                font-size: 2rem;
                margin-bottom: 12px;
            }

            /* ===== RESPONSIVIDADE ===== */
            @media (max-width: 900px) {
                .fd-grade {
                    gap: 20px;
                }

                .fd-cartao {
                    width: calc(33.33% - 20px);
                    min-width: 180px;
                }
            }

            @media (max-width: 700px) {
                .fd-cartao {
                    width: calc(50% - 15px);
                    min-width: 160px;
                }
            }

            @media (max-width: 500px) {
                .fd-cartao {
                    width: calc(50% - 10px);
                    min-width: 140px;
                }

                .fd-cartao__nome {
                    font-size: 0.75rem;
                }

                .fd-cartao__cargo {
                    font-size: 0.65rem;
                }
            }

            @media (max-width: 400px) {
                .fd-cartao {
                    width: 100%;
                    max-width: 220px;
                }
            }
    </style>

</head>
<body>


<?php include __DIR__ . '/includes/header.php'; ?>

    <!-- ===== CONTEÚDO PRINCIPAL ===== -->
    <section class="fd-heroi">
        <div class="fd-heroi__inner">
            <span class="fd-etiqueta">
                <i class="fas fa-award"></i> IPIKK · Ano Lectivo <?= htmlspecialchars($ano_lectivo) ?>
            </span>
            <h1 class="fd-heroi__titulo">
                <?= htmlspecialchars($hero_titulo) ?> <em></em>
            </h1>
            <div class="fd-heroi__divisor">
                <span class="fd-heroi__linha"></span>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <span class="fd-heroi__linha"></span>
            </div>
            <p class="fd-heroi__subtitulo">
                <?= htmlspecialchars($hero_subtitulo) ?>
            </p>
        </div>
    </section>

    <?php if (!$tem_funcionarios): ?>
    <div class="mensagem-vazia">
        <i class="fas fa-info-circle"></i>
        Os funcionários destacados serão divulgados em breve.
    </div>
    <?php else: ?>
    <!-- PRIMEIRA LINHA — Funcionários do Grupo 1 -->
    <?php if (!empty($funcionarios_grupo1)): ?>
    <section class="fd-grade-wrapper">
        <div class="fd-grade fd-grade--<?= count($funcionarios_grupo1) ?>">
            <?php foreach($funcionarios_grupo1 as $func): ?>
            <div class="fd-cartao">
                <div class="fd-cartao__foto-area">
                    <img src="<?= htmlspecialchars($func['foto_url'] ?? 'foto/sem_foto.png') ?>"
                         alt="<?= htmlspecialchars($func['nome']) ?>"
                         class="fd-cartao__foto"
                         onerror="this.src=''; this.classList.add('fd-sem-foto'); this.parentElement.querySelector('.fd-cartao__foto-icon').style.display='flex'">
                    <i class="fas fa-user fd-cartao__foto-icon" aria-hidden="true" style="display: none;"></i>
                </div>
                <div class="fd-cartao__info">
                    <strong class="fd-cartao__nome"><?= htmlspecialchars($func['nome']) ?></strong>
                    <span class="fd-cartao__cargo"><?= htmlspecialchars($func['cargo']) ?></span>
                    <div class="fd-cartao__linha"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- SEGUNDA LINHA — Funcionários do Grupo 2 -->
    <?php if (!empty($funcionarios_grupo2)): ?>
    <section style="margin-bottom: 5%;">
        <div class="fd-grade fd-grade--<?= count($funcionarios_grupo2) ?>">
            <?php foreach($funcionarios_grupo2 as $func): ?>
            <div class="fd-cartao">
                <div class="fd-cartao__foto-area">
                    <img src="<?= htmlspecialchars($func['foto_url'] ?? 'foto/sem_foto.png') ?>"
                         alt="<?= htmlspecialchars($func['nome']) ?>"
                         class="fd-cartao__foto"
                         onerror="this.src=''; this.classList.add('fd-sem-foto'); this.parentElement.querySelector('.fd-cartao__foto-icon').style.display='flex'">
                    <i class="fas fa-user fd-cartao__foto-icon" aria-hidden="true" style="display: none;"></i>
                </div>
                <div class="fd-cartao__info">
                    <strong class="fd-cartao__nome"><?= htmlspecialchars($func['nome']) ?></strong>
                    <span class="fd-cartao__cargo"><?= htmlspecialchars($func['cargo']) ?></span>
                    <div class="fd-cartao__linha fd-cartao__linha--teal"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
    <?php endif; ?>

    <!-- FAIXA DE RECONHECIMENTO -->
    <section class="fd-reconhecimento">
        <div class="fd-reconhecimento__inner">
            <i class="fas fa-medal fd-reconhecimento__icone"></i>
            <p class="fd-reconhecimento__texto">
                <?= htmlspecialchars($faixa_texto) ?>
            </p>
        </div>
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
</body>
</html>

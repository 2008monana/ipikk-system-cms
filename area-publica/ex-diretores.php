<?php
/**
 * Página Ex-Directores - IPIKK
 */

require_once '../config/index.php';

// Buscar configurações do site
$config = getDB()->query("SELECT * FROM configuracoes WHERE id = 1")->fetch();

// Buscar conteúdo da página (JSON da tabela conteudo_paginas)
$pagina = getPagina('ex-directores');

// ============================================
// BUSCAR EX-DIRECTORES DO BANCO DE DADOS
// ============================================
$ex_diretores = getDB()->query("
    SELECT * FROM ex_diretores
    WHERE ativo = 1
    ORDER BY ordem, periodo_inicio DESC
")->fetchAll();

// Verificar status das inscrições para o botão de matrícula
$status_inscricoes = getDB()->query("SELECT status FROM controle_inscricoes WHERE id = 1")->fetch();
$link_inscricao = ($status_inscricoes && $status_inscricoes['status'] === 'abertas') ? 'inscricoes.php' : 'inscricoes-indisponiveis.php';
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IPIKK - Ex-Directores</title>

    <!-- Fontes e Ícones -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Favicon -->
    <link href="<?= $config['favicon_url'] ?? 'foto/ipikk_new_logo.png' ?>" rel="icon">

    <!-- CSS Header/Footer Padrão -->
    <link rel="stylesheet" href="css/header-footer.css">

    <style>
        /* ========== VARIÁVEIS ========== */
:root {
    --azul: #003072;
    --azul-escuro: #001a40;
    --teal: #0a9396;
    --teal-claro: #94d2bd;
    --branco: #ffffff;
    --fundo-pagina: #f0f4f8;
    --fundo-secao: #f8f9fa;
    --cinza: #6c757d;
    --cinza-claro: #dee2e6;
    --texto: #2d2d2d;
    --sombra: 0 2px 12px rgba(0,0,0,0.07);
    --sombra-hover: 0 6px 22px rgba(0,0,0,0.13);
    --transicao: all 0.3s ease;
    --raio: 10px;
}

/* ============== BASE ================= */
*, *::before, *::after {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

html {
    scroll-behavior: smooth;
}

body {
    font-family: 'Montserrat', sans-serif;
    color: var(--texto);
    background: var(--fundo-pagina);
    line-height: 1.6;
}

a {
    text-decoration: none;
    color: inherit;
    transition: var(--transicao);
}

img {
    display: block;
    max-width: 100%;
}

/* ======= PÁGINA — wrapper geral ======== */
.pagina {
    max-width: 1200px;
    margin: 0 auto;
    padding: 60px 24px 80px;
}

/* =========== TÍTULO PRINCIPAL ================== */
.titulo-principal {
    font-family: 'Poppins', sans-serif;
    font-size: 2.4rem;
    font-weight: 700;
    color: var(--azul);
    text-align: center;
    margin-bottom: 12px;
    line-height: 1.2;
}

.titulo-principal::after {
    content: '';
    display: block;
    width: 60px;
    height: 4px;
    background: var(--teal);
    border-radius: 2px;
    margin: 10px auto 0;
}

/* ======== TÍTULOS DE SEÇÃO =================== */
.titulo-secao {
    font-family: 'Poppins', sans-serif;
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--azul);
    text-align: center;
    margin-bottom: 10px;
}

.titulo-secao::after {
    content: '';
    display: block;
    width: 44px;
    height: 3px;
    background: var(--teal);
    border-radius: 2px;
    margin: 8px auto 32px;
}

/* ======= SEÇÕES =========== */
section {
    margin-top: 60px;
}

/* ============= DIREÇÃO EXECUTIVA — CARTÕES GRANDES ================ */
.cartoes-diretoria {
    display: flex;
    justify-content: center;
    gap: 28px;
    flex-wrap: wrap;
}

.cartao.grande {
    background: var(--branco);
    border-radius: var(--raio);
    border: 1px solid var(--cinza-claro);
    border-top: 3px solid var(--teal);
    box-shadow: var(--sombra);
    padding: 36px 24px 28px;
    width: 250px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 14px;
    text-align: center;
    transition: var(--transicao);
}

.cartao.grande:hover {
    transform: translateY(-5px);
    box-shadow: var(--sombra-hover);
}

/* Container da imagem - cartão grande */
.cartao.grande .container-imagem-grande {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    overflow: hidden;
    border: 3px solid var(--teal-claro);
    background: var(--fundo-secao);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 10px;
}

.cartao.grande .container-imagem-grande img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
}

.cartao.grande h3 {
    font-family: 'Poppins', sans-serif;
    font-size: 1rem;
    font-weight: 700;
    color: var(--azul);
    line-height: 1.4;
    margin: 0;
}

.cartao.grande span {
    font-size: 0.85rem;
    font-weight: 500;
    color: var(--teal);
    line-height: 1.4;
}

/* Período de gestão */
.periodo-gestao {
    display: inline-block;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--cinza);
    background: var(--fundo-secao);
    padding: 4px 12px;
    border-radius: 20px;
    margin-top: 8px;
}

/* =========== RESPONSIVIDADE =================== */
@media (max-width: 1024px) {
    .cartoes-diretoria {
        gap: 20px;
    }
}

@media (max-width: 768px) {
    .pagina {
        padding: 40px 20px 60px;
    }

    .titulo-principal {
        font-size: 2rem;
    }

    .cartoes-diretoria {
        gap: 20px;
    }

    .cartao.grande {
        width: 220px;
        padding: 30px 20px 24px;
    }

    .cartao.grande .container-imagem-grande {
        width: 100px;
        height: 100px;
    }
}

@media (max-width: 600px) {
    .cartoes-diretoria {
        flex-direction: column;
        align-items: center;
    }

    .cartao.grande {
        width: 100%;
        max-width: 280px;
    }
}

@media (max-width: 480px) {
    .pagina {
        padding: 30px 15px 50px;
    }

    .titulo-principal {
        font-size: 1.8rem;
    }

    .titulo-secao {
        font-size: 1.3rem;
    }

    .botoes-flutuantes {
        bottom: 20px;
        right: 20px;
    }

    .botao-flutuante {
        width: 44px;
        height: 44px;
        font-size: 1rem;
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
    <div class="pagina">
        <h1 class="titulo-principal">Ex-Directores</h1>

        <section>
            <?php if (empty($ex_diretores)): ?>
            <div class="mensagem-vazia">
                <i class="fas fa-info-circle"></i>
                Sem informações de ex-directores no momento.
            </div>
            <?php else: ?>
            <div class="cartoes-diretoria">
                <?php foreach($ex_diretores as $ex): ?>
                <div class="cartao grande">
                    <div class="container-imagem-grande">
                        <img src="<?= htmlspecialchars($ex['foto_url'] ?? 'foto/sem_foto.png') ?>" alt="<?= htmlspecialchars($ex['nome']) ?>" onerror="this.src='foto/sem_foto.png'">
                    </div>
                    <h3><?= htmlspecialchars($ex['nome']) ?></h3>
                    <span><?= htmlspecialchars($ex['cargo'] ?? 'Director Geral') ?></span>
                    <?php if(isset($ex['periodo_inicio'])): ?>
                    <span class="periodo-gestao">
                        <?= htmlspecialchars($ex['periodo_inicio']) ?> - <?= htmlspecialchars($ex['periodo_fim'] ?? 'Presente') ?>
                    </span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </section>
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

    <!-- JavaScript -->
    <script src="js/header-footer.js"></script>
</body>
</html>

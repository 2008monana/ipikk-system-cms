<?php
/**
 * Página Órgãos Directivos - IPIKK
 */

require_once '../config/index.php';

// Buscar configurações do site
$config = getDB()->query("SELECT * FROM configuracoes WHERE id = 1")->fetch();

// Buscar conteúdo da página (JSON da tabela conteudo_paginas)
$pagina = getPagina('orgaos');

// ============================================
// BUSCAR MEMBROS DA EQUIPE DO BANCO DE DADOS
// ============================================

// Direção Executiva (categoria: direcao_executiva, tipo_card: grande)
$direcao_executiva = getDB()->query("
    SELECT * FROM equipe
    WHERE categoria = 'direcao_executiva' AND ativo = 1
    ORDER BY ordem
")->fetchAll();

// Coordenadores de Curso
$coordenadores_curso = getDB()->query("
    SELECT * FROM equipe
    WHERE categoria = 'coordenador_curso' AND ativo = 1
    ORDER BY ordem
")->fetchAll();

// Coordenadores de Disciplina
$coordenadores_disciplina = getDB()->query("
    SELECT * FROM equipe
    WHERE categoria = 'coordenador_disciplina' AND ativo = 1
    ORDER BY ordem
")->fetchAll();

// Chefes de Área
$chefes_area = getDB()->query("
    SELECT * FROM equipe
    WHERE categoria = 'chefe_area' AND ativo = 1
    ORDER BY ordem
")->fetchAll();

// Outros Coordenadores
$outros_coordenadores = getDB()->query("
    SELECT * FROM equipe
    WHERE categoria = 'outros' AND ativo = 1
    ORDER BY ordem
")->fetchAll();

$tem_orgaos = !empty($direcao_executiva) || !empty($coordenadores_curso) || !empty($coordenadores_disciplina) || !empty($chefes_area) || !empty($outros_coordenadores);

// Verificar status das inscrições para o botão de matrícula
$status_inscricoes = getDB()->query("SELECT status FROM controle_inscricoes WHERE id = 1")->fetch();
$link_inscricao = ($status_inscricoes && $status_inscricoes['status'] === 'abertas') ? 'inscricoes.php' : 'inscricoes-indisponiveis.php';
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IPIKK - Órgãos Directivos</title>

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

/* ========= CARTÕES PEQUENOS — todas as outras seções =================== */
.cartoes-pequenos {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
}

.cartao.pequeno {
    background: var(--branco);
    border-radius: var(--raio);
    border: 1px solid var(--cinza-claro);
    border-left: 4px solid var(--teal);
    box-shadow: var(--sombra);
    padding: 16px;
    display: flex;
    flex-direction: row;
    align-items: center;
    gap: 15px;
    transition: var(--transicao);
    min-height: 110px;
    width: 100%;
}

.cartao.pequeno:hover {
    transform: translateY(-3px);
    box-shadow: var(--sombra-hover);
    border-left-color: var(--azul);
}

/* Container da imagem - cartão pequeno */
.cartao.pequeno .container-imagem-pequena {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    overflow: hidden;
    border: 2px solid var(--teal-claro);
    background: var(--fundo-secao);
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}

.cartao.pequeno .container-imagem-pequena img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
}

/* Informações do membro */
.cartao.pequeno .info-membro {
    display: flex;
    flex-direction: column;
    justify-content: center;
    flex: 1;
    min-width: 0;
}

.cartao.pequeno h4 {
    font-family: 'Poppins', sans-serif;
    font-size: 0.9rem;
    font-weight: 700;
    color: var(--azul);
    line-height: 1.4;
    margin-bottom: 5px;
    word-wrap: break-word;
}

.cartao.pequeno p {
    font-size: 0.8rem;
    color: var(--cinza);
    line-height: 1.4;
    word-wrap: break-word;
}

/* Classe para centralizar cartões */
.cartoes-pequenos.centralizado {
    display: flex;
    justify-content: center;
    width: 100%;
}

/* =========== RESPONSIVIDADE =================== */
@media (max-width: 1024px) {
    .cartoes-pequenos {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 768px) {
    .pagina {
        padding: 40px 20px 60px;
    }

    .titulo-principal {
        font-size: 2.4rem;
    }

    .cartoes-pequenos {
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
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

    .conteudo-rodape {
        grid-template-columns: 1fr 1fr;
        gap: 30px;
    }

    .container-rodape {
        padding: 0 20px;
    }

    .rodape-inferior {
        padding: 20px;
    }
}

@media (max-width: 600px) {
    .cartoes-pequenos {
        grid-template-columns: 1fr;
    }

    .cartao.pequeno {
        max-width: 100%;
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

    .cartoes-diretoria {
        flex-direction: column;
        align-items: center;
    }

    .cartao.grande {
        width: 100%;
        max-width: 280px;
    }

    .conteudo-rodape {
        grid-template-columns: 1fr;
        gap: 25px;
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
        <h1 class="titulo-principal">Órgãos Directivos</h1>

        <!-- ── DIREÇÃO EXECUTIVA ── -->
        <?php if (!$tem_orgaos): ?>
        <div class="mensagem-vazia">
            <i class="fas fa-info-circle"></i>
            Informações sobre órgãos diretivos estão indisponíveis no momento.
        </div>
        <?php else: ?>
        <?php if (!empty($direcao_executiva)): ?>
        <section>
            <h2 class="titulo-secao">Direção Executiva</h2>
            <div class="cartoes-diretoria">
                <?php foreach($direcao_executiva as $membro): ?>
                <div class="cartao grande">
                    <div class="container-imagem-grande">
                        <img src="<?= htmlspecialchars($membro['foto_url'] ?? 'foto/sem_foto.png') ?>" alt="<?= htmlspecialchars($membro['nome']) ?>" onerror="this.src='foto/sem_foto.png'">
                    </div>
                    <h3><?= htmlspecialchars($membro['nome']) ?></h3>
                    <span><?= htmlspecialchars($membro['cargo']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- ── COORDENADORES DE CURSO ── -->
        <?php if (!empty($coordenadores_curso)): ?>
        <section>
            <h2 class="titulo-secao">Coordenadores de Curso</h2>
            <div class="cartoes-pequenos">
                <?php foreach($coordenadores_curso as $membro): ?>
                <div class="cartao pequeno">
                    <div class="container-imagem-pequena">
                        <img src="<?= htmlspecialchars($membro['foto_url'] ?? 'foto/sem_foto.png') ?>" alt="<?= htmlspecialchars($membro['nome']) ?>" onerror="this.src='foto/sem_foto.png'">
                    </div>
                    <div class="info-membro">
                        <h4><?= htmlspecialchars($membro['nome']) ?></h4>
                        <p><?= htmlspecialchars($membro['cargo']) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- ── COORDENADORES DE DISCIPLINA ── -->
        <?php if (!empty($coordenadores_disciplina)): ?>
        <section>
            <h2 class="titulo-secao">Coordenadores de Disciplina</h2>
            <div class="cartoes-pequenos">
                <?php foreach($coordenadores_disciplina as $membro): ?>
                <div class="cartao pequeno">
                    <div class="container-imagem-pequena">
                        <img src="<?= htmlspecialchars($membro['foto_url'] ?? 'foto/sem_foto.png') ?>" alt="<?= htmlspecialchars($membro['nome']) ?>" onerror="this.src='foto/sem_foto.png'">
                    </div>
                    <div class="info-membro">
                        <h4><?= htmlspecialchars($membro['nome']) ?></h4>
                        <p><?= htmlspecialchars($membro['cargo']) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- ── CHEFES DE ÁREA ── -->
        <?php if (!empty($chefes_area)): ?>
        <section>
            <h2 class="titulo-secao">Chefes de Área</h2>
            <div class="cartoes-pequenos">
                <?php foreach($chefes_area as $membro): ?>
                <div class="cartao pequeno">
                    <div class="container-imagem-pequena">
                        <img src="<?= htmlspecialchars($membro['foto_url'] ?? 'foto/sem_foto.png') ?>" alt="<?= htmlspecialchars($membro['nome']) ?>" onerror="this.src='foto/sem_foto.png'">
                    </div>
                    <div class="info-membro">
                        <h4><?= htmlspecialchars($membro['nome']) ?></h4>
                        <p><?= htmlspecialchars($membro['cargo']) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- ── OUTROS COORDENADORES ── -->
        <?php if (!empty($outros_coordenadores)): ?>
        <section>
            <h2 class="titulo-secao">Outros Coordenadores</h2>
            <div class="cartoes-pequenos">
                <?php foreach($outros_coordenadores as $membro): ?>
                <div class="cartao pequeno">
                    <div class="container-imagem-pequena">
                        <img src="<?= htmlspecialchars($membro['foto_url'] ?? 'foto/sem_foto.png') ?>" alt="<?= htmlspecialchars($membro['nome']) ?>" onerror="this.src='foto/sem_foto.png'">
                    </div>
                    <div class="info-membro">
                        <h4><?= htmlspecialchars($membro['nome']) ?></h4>
                        <p><?= htmlspecialchars($membro['cargo']) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
        <?php endif; ?>
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

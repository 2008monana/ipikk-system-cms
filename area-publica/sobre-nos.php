<?php
/**
 * Página Quem Somos - IPIKK
 */

require_once '../config/index.php';

// Buscar configurações do site
$config = getDB()->query("SELECT * FROM configuracoes WHERE id = 1")->fetch();

// Buscar conteúdo da página (JSON da tabela conteudo_paginas)
$pagina = getPagina('sobre');

// Extrair dados (com fallbacks)
$hero = $pagina['hero'] ?? [
    'titulo' => 'Quem Somos?',
    'subtitulo' => 'Conheça a história, missão e valores do Instituto Politécnico Industrial do Kilamba Kiaxi nº 8056 "Nova Vida"'
];

$historia = $pagina['historia'] ?? [
    'titulo' => 'Nossa História',
    'conteudo' => '',
    'imagem' => 'foto/img_construct_5.jpg',
    'legenda' => 'IPIKK — Símbolo de excelência no ensino técnico-profissional angolano'
];

// Buscar linha do tempo da base de dados (tabela linha_tempo)
$linha_tempo = getDB()->query("SELECT * FROM linha_tempo WHERE ativo = 1 ORDER BY ordem")->fetchAll();

// Se não houver linha do tempo, usar fallback
if (empty($linha_tempo)) {
    $linha_tempo = [
        ['ano' => '2008', 'descricao' => 'Criação do instituto pelo despacho nº 328/08', 'ativo' => 0],
        ['ano' => '2009', 'descricao' => 'Inauguração oficial pelo Ministro da Educação', 'ativo' => 0],
        ['ano' => 'Atual', 'descricao' => 'Referência em ensino técnico-profissional em Angola', 'ativo' => 0]
    ];
}

// Extrair missão, visão, valores (do JSON)
$missao = $pagina['missao'] ?? '';
$visao = $pagina['visao'] ?? '';
$valores = $pagina['valores'] ?? '';
$lema = $pagina['lema'] ?? '" Um diferencial para a sua formação "';
$lema_descricao = $pagina['lema_descricao'] ?? 'Mais do que uma frase, nosso compromisso diário com cada estudante';

// Fallback para missão/visão/valores se não existirem no JSON
if (empty($missao)) {
    $missao = 'Educar e formar cidadãos autónomos, responsáveis, críticos e criativos, preparando-os para os desafios do mercado de trabalho e para contribuir activamente no desenvolvimento nacional.';
}
if (empty($visao)) {
    $visao = 'Ser referência nacional no ensino técnico-profissional, reconhecida pela excelência na formação, inovação pedagógica e contribuição para o desenvolvimento industrial sustentável de Angola.';
}
if (empty($valores)) {
    $valores = 'Autonomia, Excelência, Disciplina, Inovação, Respeito, Solidariedade, Competência, Organização, Pontualidade e Compromisso com a qualidade educacional.';
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IPIKK - Quem Somos</title>
    
    <!-- Fontes e Ícones -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <!-- Favicon -->
    <link href="<?= $config['favicon_url'] ?? 'foto/ipikk_new_logo.png' ?>" rel="icon">
    
    <!-- CSS Header/Footer Padrão -->
    <link rel="stylesheet" href="css/header-footer.css">
    
    <style>
        /* =========== VARIÁVEIS ============ */
:root {
    --azul: #003072;
    --azul-escuro: #001a40;
    --teal: #0a9396;
    --teal-claro: #94d2bd;
    --branco: #ffffff;
    --fundo-claro: #f8f9fa;
    --fundo-secao: #eef2f7;
    --cinzento: #6c757d;
    --texto: #2d2d2d;
    --borda: #dde3ea;
    --sombra: 0 4px 18px rgba(0,0,0,0.08);
    --sombra-hover: 0 8px 28px rgba(0,0,0,0.13);
    --transicao: all 0.3s ease;
    --raio: 10px;
}

/* ============= BASE ================= */
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
    background: var(--branco);
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

/* ========= HERO — "Quem Somos?" ============== */
.secao-hero-quem-somos {
    background-color: var(--fundo-secao);
    padding: 76px 24px 60px;
    text-align: center;
}

.container-hero {
    max-width: 780px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 20px;
}

.titulo-hero {
    font-family: 'Poppins', sans-serif;
    font-size: 2.6rem;
    font-weight: 700;
    color: var(--azul);
    line-height: 1.2;
}

.linha-titulo {
    display: block;
    width: 56px;
    height: 4px;
    background: var(--teal);
    border-radius: 2px;
}

/* Caixa subtítulo — bloco branco com borda cinzenta leve */
.caixa-subtitulo {
    background: var(--branco);
    border: 1px solid var(--borda);
    border-radius: var(--raio);
    padding: 20px 30px;
    max-width: 560px;
    box-shadow: var(--sombra);
    text-align: left;
}

.subtitulo-hero {
    font-size: 0.97rem;
    color: var(--cinzento);
    line-height: 1.75;
}

/* =========== SECÇÃO HISTÓRIA ================ */
.secao-historia {
    background: var(--branco);
    padding: 80px 24px;
}

.container-historia {
    max-width: 1080px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 1fr 310px;
    gap: 64px;
    align-items: start;
}

/* — Cabeçalho — */
.cabecalho-historia {
    margin-bottom: 20px;
}

.titulo-historia {
    font-family: 'Poppins', sans-serif;
    font-size: 1.45rem;
    font-weight: 700;
    color: var(--azul);
    margin-bottom: 8px;
}

.linha-historia {
    display: block;
    width: 38px;
    height: 3px;
    background: var(--teal);
    border-radius: 2px;
}

/* — Parágrafos — */
.paragrafo-historia {
    font-size: 0.92rem;
    color: var(--texto);
    line-height: 1.8;
    margin-bottom: 18px;
}

/* — Linha do tempo — */
.linha-do-tempo {
    margin: 22px 0 26px;
    display: flex;
    flex-direction: column;
}

.evento-tempo {
    display: flex;
    gap: 14px;
    align-items: flex-start;
}

.marcador-tempo {
    display: flex;
    flex-direction: column;
    align-items: center;
    flex-shrink: 0;
    padding-top: 3px;
}

.bolinha-tempo {
    width: 14px;
    height: 14px;
    border-radius: 50%;
    background: var(--teal);
    border: 2px solid var(--branco);
    outline: 2px solid var(--teal);
    flex-shrink: 0;
    z-index: 1;
}

.bolinha-ativa {
    width: 15px;
    height: 15px;
    outline: 3px solid rgba(10,147,150,0.28);
}

.linha-vertical {
    width: 2px;
    min-height: 30px;
    background: var(--teal-claro);
    flex: 1;
    margin: 3px 0;
}

.conteudo-evento {
    padding-bottom: 16px;
}

.ano-evento {
    font-family: 'Poppins', sans-serif;
    font-size: 0.9rem;
    font-weight: 700;
    color: var(--azul);
    display: block;
    margin-bottom: 2px;
}

.descricao-evento {
    font-size: 0.82rem;
    color: var(--cinzento);
    line-height: 1.5;
}

/* — Bloco compromisso — */
.bloco-compromisso {
    background: var(--fundo-claro);
    border-left: 3px solid var(--teal);
    border-radius: 0 var(--raio) var(--raio) 0;
    padding: 16px 20px;
    margin: 18px 0;
}

.titulo-compromisso {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 10px;
}

.estrela-compromisso {
    color: #f6ad55;
    font-size: 1.1rem;
    line-height: 1;
}

.texto-compromisso {
    font-family: 'Poppins', sans-serif;
    font-size: 0.97rem;
    font-weight: 700;
    color: var(--azul);
}

/* — Coluna imagem — */
.coluna-imagem-historia {
    display: flex;
    flex-direction: column;
    gap: 10px;
    position: sticky;
    top: 100px;
}

.cartao-imagem {
    border-radius: var(--raio);
    overflow: hidden;
    box-shadow: var(--sombra);
    position: relative;
}

.icone-localizacao {
    position: absolute;
    top: 10px;
    right: 10px;
    width: 28px;
    height: 28px;
    background: rgba(255,255,255,0.92);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--teal);
    font-size: 0.82rem;
    box-shadow: 0 2px 6px rgba(0,0,0,0.18);
    z-index: 2;
}

.foto-instituto {
    width: 100%;
    height: 210px;
    object-fit: cover;
    display: block;
}

.legenda-imagem {
    display: flex;
    align-items: flex-start;
    gap: 5px;
    justify-content: center;
    font-size: 0.75rem;
    color: var(--cinzento);
    line-height: 1.5;
    text-align: center;
    padding: 0 4px;
}

.legenda-imagem i {
    color: var(--teal);
    font-size: 0.75rem;
    margin-top: 2px;
    flex-shrink: 0;
}

/* ======== SECÇÃO PRINCÍPIOS =========== */
.secao-principios {
    background: var(--fundo-secao);
    padding: 80px 24px 80px;
}

.container-principios {
    max-width: 980px;
    margin: 0 auto;
}

.cabecalho-principios {
    text-align: center;
    margin-bottom: 48px;
}

.titulo-principios {
    font-family: 'Poppins', sans-serif;
    font-size: 2rem;
    font-weight: 600;
    color: var(--azul);
    margin-bottom: 12px;
}

.linha-principios {
    display: block;
    width: 50px;
    height: 3px;
    background: var(--teal);
    border-radius: 2px;
    margin: 0 auto;
}

/* — Grade dos 3 cartões — */
.grade-principios {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 22px;
    margin-bottom: 52px;
}

@media (max-width: 960px) {
    .grade-principios {
        grid-template-columns: 1fr;
        max-width: 360px;
        margin: 0 auto 48px;
    }
}

.cartao-principio {
    background: var(--branco);
    border-radius: var(--raio);
    padding: 36px 22px 28px;
    text-align: center;
    box-shadow: var(--sombra);
    border: 1px solid var(--borda);
    transition: var(--transicao);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 14px;
}

.cartao-principio:hover {
    transform: translateY(-5px);
    box-shadow: var(--sombra-hover);
}

/* Círculo azul escuro com ícone branco */
.circulo-icone {
    width: 62px;
    height: 62px;
    background: var(--azul);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.circulo-icone i {
    color: var(--branco);
    font-size: 1.3rem;
}

.titulo-principio {
    font-family: 'Poppins', sans-serif;
    font-size: 1rem;
    font-weight: 600;
    color: var(--teal);
}

.texto-principio {
    font-size: 0.86rem;
    color: var(--cinzento);
    line-height: 1.75;
}

/* — Bloco Lema — */
.bloco-lema {
    text-align: center;
}

.rotulo-lema {
    display: block;
    font-family: 'Poppins', sans-serif;
    font-size: 0.78rem;
    font-weight: 600;
    color: var(--teal);
    text-transform: uppercase;
    letter-spacing: 2.5px;
    margin-bottom: 16px;
}

.caixa-lema {
    display: block;
    max-width: 620px;
    margin: 0 auto;
    border-top: 3px solid var(--teal);
    border-bottom: 3px solid var(--teal);
    background: rgba(10,147,150,0.04);
    padding: 24px 40px;
    border-radius: 2px;
}

.frase-lema {
    font-family: 'Poppins', sans-serif;
    font-size: 1.2rem;
    font-weight: 700;
    font-style: italic;
    color: var(--azul);
    margin-bottom: 8px;
}

.descricao-lema {
    font-size: 0.83rem;
    color: var(--cinzento);
}


/* ============ RESPONSIVIDADE ================= */
@media (max-width: 960px) {
    .container-historia {
        grid-template-columns: 1fr;
        gap: 40px;
    }
    
    .coluna-imagem-historia {
        position: static;
        max-width: 460px;
        margin: 0 auto;
        width: 100%;
    }
}

@media (max-width: 600px) {
    .titulo-hero {
        font-size: 1.9rem;
    }
    
    .titulo-historia {
        font-size: 1.25rem;
    }
    
    .titulo-principios {
        font-size: 1.5rem;
    }
    
    .caixa-lema {
        padding: 18px 20px;
    }
    
    .frase-lema {
        font-size: 1rem;
    }
    
    .secao-hero-quem-somos {
        padding: 50px 16px 40px;
    }
    
    .secao-historia {
        padding: 50px 16px;
    }
    
    .secao-principios {
        padding: 50px 16px;
    }
}
    </style>
</head>
<body>


<?php include __DIR__ . '/includes/header.php'; ?>

    <!-- ===== CONTEÚDO PRINCIPAL ===== -->
    <main>
        <!-- HERO -->
        <section class="secao-hero-quem-somos">
            <div class="container-hero">
                <h1 class="titulo-hero"><?= htmlspecialchars($hero['titulo']) ?></h1>
                <span class="linha-titulo"></span>
                <div class="caixa-subtitulo">
                    <p class="subtitulo-hero"><?= htmlspecialchars($hero['subtitulo']) ?></p>
                </div>
            </div>
        </section>

        <!-- HISTÓRIA -->
        <section class="secao-historia">
            <div class="container-historia">
                <div class="coluna-texto-historia">
                    <div class="cabecalho-historia">
                        <h2 class="titulo-historia"><?= htmlspecialchars($historia['titulo']) ?></h2>
                        <span class="linha-historia"></span>
                    </div>

                    <?= $historia['conteudo'] ?>

                    <div class="linha-do-tempo">
                        <?php foreach($linha_tempo as $evento): ?>
                        <div class="evento-tempo">
                            <div class="marcador-tempo">
                                <span class="bolinha-tempo <?= $evento['ativo'] ? 'bolinha-ativa' : '' ?>"></span>
                                <?php if(!$evento['ativo']): ?>
                                <span class="linha-vertical"></span>
                                <?php endif; ?>
                            </div>
                            <div class="conteudo-evento">
                                <strong class="ano-evento"><?= htmlspecialchars($evento['ano']) ?></strong>
                                <p class="descricao-evento"><?= htmlspecialchars($evento['descricao']) ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="bloco-compromisso">
                        <div class="titulo-compromisso">
                            <span class="estrela-compromisso"></span>
                            <h3 class="texto-compromisso">Compromisso com a Excelência</h3>
                        </div>
                        <p class="paragrafo-historia" style="margin-bottom:0;">
                            Ao longo dos anos, o IPIKK tem se destacado pela qualidade do ensino técnico-profissional,
                            formando profissionais altamente qualificados que contribuem significativamente para o
                            desenvolvimento industrial e tecnológico de Angola.
                        </p>
                    </div>
                </div>

                <div class="coluna-imagem-historia">
                    <div class="cartao-imagem">
                        <span class="icone-localizacao"><i class="fas fa-map-marker-alt"></i></span>
                        <img src="<?= htmlspecialchars($historia['imagem']) ?>" alt="Instituto IPIKK — Nova Vida" class="foto-instituto" onerror="this.src='foto/img_construct_5.jpg'"/>
                    </div>
                    <div class="legenda-imagem">
                        <i class="fas fa-map-marker-alt"></i>
                        <span><?= htmlspecialchars($historia['legenda']) ?></span>
                    </div>
                </div>
            </div>
        </section>

        <!-- PRINCÍPIOS -->
        <section class="secao-principios">
            <div class="container-principios">
                <div class="cabecalho-principios">
                    <h2 class="titulo-principios">Nossos Princípios</h2>
                    <span class="linha-principios"></span>
                </div>

                <div class="grade-principios">
                    <div class="cartao-principio">
                        <div class="circulo-icone">
                            <i class="fas fa-flag"></i>
                        </div>
                        <h3 class="titulo-principio">Missão</h3>
                        <p class="texto-principio"><?= nl2br(htmlspecialchars($missao)) ?></p>
                    </div>

                    <div class="cartao-principio">
                        <div class="circulo-icone">
                            <i class="fas fa-eye"></i>
                        </div>
                        <h3 class="titulo-principio">Visão</h3>
                        <p class="texto-principio"><?= nl2br(htmlspecialchars($visao)) ?></p>
                    </div>

                    <div class="cartao-principio">
                        <div class="circulo-icone">
                            <i class="fas fa-heart"></i>
                        </div>
                        <h3 class="titulo-principio">Valores</h3>
                        <p class="texto-principio"><?= nl2br(htmlspecialchars($valores)) ?></p>
                    </div>
                </div>

                <div class="bloco-lema">
                    <span class="rotulo-lema">Lema</span>
                    <div class="caixa-lema">
                        <p class="frase-lema"><?= htmlspecialchars($lema) ?></p>
                        <p class="descricao-lema"><?= htmlspecialchars($lema_descricao) ?></p>
                    </div>
                </div>
            </div>
        </section>
    </main>

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

    <!-- JavaScript Header/Footer Padrão -->
    <script src="js/header-footer.js"></script>
</body>
</html>
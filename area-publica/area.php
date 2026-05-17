<?php
/**
 * Página de Área de Formação - IPIKK
 * UMA página para TODAS as áreas
 * Ex: area.php?slug=construcao-civil
 */

require_once '../config/index.php';

$config = getDB()->query("SELECT * FROM configuracoes WHERE id = 1")->fetch();

$area_slug = $_GET['slug'] ?? null;

if (!$area_slug) {
    header('Location: oferta-formativa.php');
    exit;
}

// Buscar a área
$stmt = getDB()->prepare("SELECT * FROM areas WHERE slug = ? AND ativo = 1");
$stmt->execute([$area_slug]);
$area = $stmt->fetch();

if (isset($area) && $area['id']) {
    incrementarVisualizacaoArea($area['id']);
} 

if (!$area) {
    header('HTTP/1.0 404 Not Found');
    echo "<h1>Área não encontrada</h1>";
    exit;
}

// Buscar cursos desta área
$stmt = getDB()->prepare("SELECT * FROM cursos WHERE area_id = ? AND estado = 'ativo' ORDER BY ordem, id");
$stmt->execute([$area['id']]);
$cursos = $stmt->fetchAll();

// Buscar outras áreas (excluindo a atual)
$stmt = getDB()->prepare("SELECT * FROM areas WHERE id != ? AND ativo = 1 ORDER BY ordem");
$stmt->execute([$area['id']]);
$outras_areas = $stmt->fetchAll();

// Buscar todas as áreas para o menu
$stmt = getDB()->query("SELECT * FROM areas WHERE ativo = 1 ORDER BY ordem");
$todas_areas = $stmt->fetchAll();

// Buscar todos os cursos para submenus
$todos_cursos = getDB()->query("SELECT * FROM cursos WHERE estado = 'ativo' ORDER BY nome")->fetchAll();
$cursos_por_area = [];
foreach ($todos_cursos as $curso_item) {
    $cursos_por_area[$curso_item['area_id']][] = $curso_item;
}

// ============================================
// COR DA ÁREA (vinda do banco de dados)
// ============================================
$cor_area = !empty($area['cor_primaria']) ? $area['cor_primaria'] : '#6c757d';

// Função para escurecer uma cor (para overlay e hover)
function escurecerCor($cor_hex, $percent = 0.7) {
    $cor_hex = ltrim($cor_hex, '#');
    $r = hexdec(substr($cor_hex, 0, 2));
    $g = hexdec(substr($cor_hex, 2, 2));
    $b = hexdec(substr($cor_hex, 4, 2));
    
    $r = max(0, min(255, $r * $percent));
    $g = max(0, min(255, $g * $percent));
    $b = max(0, min(255, $b * $percent));
    
    return sprintf("#%02x%02x%02x", round($r), round($g), round($b));
}

/**
 * Converte HEX para RGBA
 */
function hexParaRgba($cor_hex, $alpha = 1) {
    $cor_hex = ltrim((string)$cor_hex, '#');
    if (!preg_match('/^[0-9a-fA-F]{6}$/', $cor_hex)) {
        $cor_hex = '6c757d';
    }
    $r = hexdec(substr($cor_hex, 0, 2));
    $g = hexdec(substr($cor_hex, 2, 2));
    $b = hexdec(substr($cor_hex, 4, 2));

    $alpha = max(0, min(1, (float)$alpha));
    return "rgba({$r}, {$g}, {$b}, {$alpha})";
}

/**
 * Normaliza cor HEX com fallback
 */
function normalizarCorHex($cor_hex, $fallback = '#6c757d') {
    $cor_hex = trim((string)$cor_hex);
    if (preg_match('/^#[0-9a-fA-F]{6}$/', $cor_hex)) {
        return strtolower($cor_hex);
    }
    if (preg_match('/^[0-9a-fA-F]{6}$/', $cor_hex)) {
        return '#' . strtolower($cor_hex);
    }
    return $fallback;
}

$is_informatica = ($area_slug === 'informatica');

// Imagens padrão por área
$imagens_padrao = [
    'construcao-civil' => 'https://images.unsplash.com/photo-1504307651254-35680f356dfd?w=800&q=80',
    'eletricidade' => 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=800&q=80',
    'mecanica' => 'https://images.unsplash.com/photo-1581092160607-ee22621dd758?w=800&q=80',
    'informatica' => 'https://images.unsplash.com/photo-1518770660439-4636190af475?w=800&q=80',
    'tecnologia-moveis' => 'https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?w=800&q=80',
    'alfaiataria' => 'https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?w=800&q=80'
];

$link_inscricao = 'inscricoes.php';
$controle_inscricao = getDB()->query("SELECT * FROM controle_inscricoes WHERE id = 1")->fetch();
if ($controle_inscricao && $controle_inscricao['status'] === 'abertas') {
    $link_inscricao = 'inscricoes.php';
} else {
    $link_inscricao = 'inscricoes-indisponiveis.php';
}

$titulo_pagina = "IPIKK - " . htmlspecialchars($area['nome']);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titulo_pagina ?></title>
    
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="<?= $config['favicon_url'] ?? 'foto/ipikk_new_logo.png' ?>" rel="icon">
    <link rel="stylesheet" href="css/header-footer.css">
    
    <style>
        /* ===== VARIÁVEIS ===== */
        :root {
            --primary-blue: #003072;
            --blue-light: #2e86c1;
            --blue-dark: #001a40;
            --accent-teal: #0a9396;
            --teal-light: #94d2bd;
            --neutral-white: #ffffff;
            --neutral-light: #f8f9fa;
            --neutral-gray: #6c757d;
            --neutral-dark: #212529;
            --amarelo: #e6a817;
            --amarelo-escuro: #c9920e;
            --laranja: #e07b2a;
            --laranja-escuro: #c95a0e;
            --verde-escuro: #2d7a3a;
            --verde-mais-escuro: #1e5527;
            --vermelho: #c0392b;
            --vermelho-escuro: #a83224;
            --azul-bebe: #2e86c1;
            --azul-bebe-escuro: #1f5a8a;
            --transition: all 0.3s ease;
            --sombra-suave: 0 4px 24px rgba(0,0,0,0.09);
            --sombra-hover: 0 12px 40px rgba(0,0,0,0.16);
            --radius: 14px;
            --cor-tema: <?= $cor_area ?>;
            --cor-tema-escura: <?= escurecerCor($cor_area, 0.6) ?>;
        }

        /* ===== ESTILOS BASE ===== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background: #f5f8fa;
            color: #2c3e50;
        }

        img {
            display: block;
            max-width: 100%;
        }

        /* ===== SEÇÃO ÁREA DE FORMAÇÃO ===== */
        .secao-area-formacao {
            padding: 80px 24px 72px;
            max-width: 1100px;
            margin: 0 auto;
        }

        .cabecalho-area {
            text-align: center;
            margin-bottom: 56px;
        }

        .titulo-area {
            font-size: clamp(1.6rem, 3.5vw, 2.4rem);
            color: var(--primary-blue);
            margin-bottom: 14px;
            letter-spacing: -0.3px;
        }

        .descricao-area {
            font-size: 0.97rem;
            color: var(--neutral-gray);
            max-width: 520px;
            margin: 0 auto 20px;
            line-height: 1.75;
        }

        .linha-area {
            display: block;
            width: 56px;
            height: 4px;
            background: var(--cor-tema);
            border-radius: 4px;
            margin: 0 auto;
        }

        /* ===== GRADE DE CURSOS ===== */
        .grade-cursos {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 28px;
        }

        @media (max-width: 768px) {
            .grade-cursos {
                grid-template-columns: 1fr;
            }
        }

        /* ===== CARTÃO DE CURSO ===== */
        .cartao-curso {
            background: var(--neutral-white);
            border-radius: var(--radius);
            box-shadow: var(--sombra-suave);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: var(--transition);
        }

        .cartao-curso:hover {
            transform: translateY(-4px);
            box-shadow: var(--sombra-hover);
        }

        .capa-curso {
            position: relative;
            height: 200px;
            overflow: hidden;
        }

        .imagem-capa {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .cartao-curso:hover .imagem-capa {
            transform: scale(1.04);
        }

        .overlay-capa {
            position: absolute;
            inset: 0;
        }

        .info-capa {
            position: absolute;
            bottom: 20px;
            left: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            z-index: 2;
        }

        .icone-curso {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: rgba(255,255,255,0.18);
            border: 1.5px solid rgba(255,255,255,0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--neutral-white);
            flex-shrink: 0;
            backdrop-filter: blur(4px);
        }

        .texto-capa {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .nome-curso {
            font-family: 'Poppins', sans-serif;
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--neutral-white);
            line-height: 1.2;
        }

        .area-curso {
            font-size: 0.78rem;
            color: rgba(255,255,255,0.80);
            font-weight: 400;
        }

        .corpo-cartao {
            padding: 28px 28px 32px;
            display: flex;
            flex-direction: column;
            gap: 24px;
            flex: 1;
        }

        .lista-competencias {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .item-competencia {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.91rem;
            color: var(--neutral-dark);
            font-weight: 500;
        }

        .icone-check {
            font-weight: 700;
            font-size: 1rem;
            flex-shrink: 0;
            line-height: 1;
        }

        .cartao-curso .icone-check {
            color: var(--curso-cor, var(--cor-tema));
        }

        /* ===== BOTÕES ===== */
        .botao-detalhes {
            display: inline-block;
            padding: 13px 24px;
            border-radius: 8px;
            font-family: 'Montserrat', sans-serif;
            font-size: 0.88rem;
            font-weight: 700;
            letter-spacing: 0.02em;
            transition: var(--transition);
            width: fit-content;
            text-decoration: none;
        }

        .botao-detalhes {
            background: var(--botao-cor, var(--cor-tema));
            color: var(--neutral-white);
        }
        .botao-detalhes:hover {
            filter: brightness(0.88);
            transform: translateX(4px);
            color: var(--neutral-white);
        }

        /* ===== SEÇÃO OUTRAS ÁREAS ===== */
        .secao-outras-areas {
            padding: 64px 24px 80px;
            background: var(--neutral-light);
        }

        .container-outras-areas {
            max-width: 1100px;
            margin: 0 auto;
            background: var(--neutral-white);
            border-radius: 20px;
            padding: 56px 48px;
            box-shadow: var(--sombra-suave);
        }

        .titulo-outras-areas {
            font-size: clamp(1.4rem, 3vw, 2rem);
            color: var(--primary-blue);
            text-align: center;
            margin-bottom: 40px;
        }

        .grade-outras-areas {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }

        @media (max-width: 992px) {
            .grade-outras-areas {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 576px) {
            .grade-outras-areas {
                grid-template-columns: 1fr;
            }
        }

        .cartao-area {
            border-radius: var(--radius);
            padding: 24px 20px;
            color: var(--neutral-white);
            display: flex;
            flex-direction: column;
            gap: 14px;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            background: var(--area-cor);
        }

        .cartao-area:hover {
            transform: translateY(-4px);
            filter: brightness(1.08);
            box-shadow: var(--sombra-hover);
        }

        .topo-cartao-area {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .icone-area {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .icone-area i {
            color: var(--neutral-white);
            font-size: 1.2rem;
        }

        .nome-area {
            font-family: 'Poppins', sans-serif;
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--neutral-white);
            line-height: 1.25;
        }

        .descricao-area-card {
            font-size: 0.80rem;
            color: rgba(255,255,255,0.88);
            line-height: 1.6;
            font-weight: 400;
            margin: 0;
        }

        /* ===== ANIMAÇÕES ===== */
        .cartao-curso,
        .cartao-area {
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 0.6s ease forwards;
        }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 992px) {
            .menu-navegacao { display: none !important; }
            .botao-menu-mobile { display: flex; }
        }
    </style>
</head>
<body>


<?php include __DIR__ . '/includes/header.php'; ?>

    <!-- ===== CONTEÚDO PRINCIPAL ===== -->
    <main>
        <section class="secao-area-formacao" id="<?= $area_slug ?>">
            <div class="cabecalho-area">
                <h2 class="titulo-area">Área de Formação: <?= htmlspecialchars($area['nome']) ?></h2>
                <p class="descricao-area">
                    <?= nl2br(htmlspecialchars($area['descricao_completa'] ?? $area['descricao_curta'] ?? '')) ?>
                </p>
                <span class="linha-area"></span>
            </div>

            <div class="grade-cursos">
                <?php foreach($cursos as $curso): 
                    $cor_curso = normalizarCorHex($curso['cor'] ?? '', $cor_area);
                    $cor_curso_escura = escurecerCor($cor_curso, 0.65);
                    $overlay_style = 'linear-gradient(to bottom, ' . hexParaRgba($cor_curso, 0.30) . ' 0%, ' . hexParaRgba($cor_curso_escura, 0.92) . ' 100%)';
                    
                    // Imagem do curso ou padrão da área
                    $imagem_curso = !empty($curso['imagem_hero']) 
                        ? (strpos($curso['imagem_hero'], 'http') === 0 ? $curso['imagem_hero'] : '../uploads/cursos/' . $curso['imagem_hero'])
                        : ($imagens_padrao[$area_slug] ?? 'https://images.unsplash.com/photo-1518770660439-4636190af475?w=800&q=80');
                ?>
                <article class="cartao-curso" data-curso-id="<?= $curso['id'] ?>" style="--curso-cor: <?= htmlspecialchars($cor_curso) ?>;">
                    <div class="capa-curso">
                        <img src="<?= $imagem_curso ?>" alt="<?= htmlspecialchars($curso['nome']) ?>" class="imagem-capa" />
                        <div class="overlay-capa" style="background: <?= $overlay_style ?>;"></div>
                        <div class="info-capa">
                            <div class="icone-curso">
                                <i class="fas <?= $curso['icone_classe'] ?? 'fa-graduation-cap' ?>"></i>
                            </div>
                            <div class="texto-capa">
                                <h3 class="nome-curso"><?= htmlspecialchars($curso['nome']) ?></h3>
                                <span class="area-curso">Área de <?= htmlspecialchars($area['nome']) ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="corpo-cartao">
                        <ul class="lista-competencias">
                            <?php if(!empty($curso['competencias'])): ?>
                                <?php 
                                $competencias = is_array($curso['competencias']) ? $curso['competencias'] : json_decode($curso['competencias'], true);
                                if(is_array($competencias)):
                                    foreach($competencias as $competencia): 
                                ?>
                                <li class="item-competencia">
                                    <span class="icone-check">✓</span>
                                    <?= htmlspecialchars($competencia) ?>
                                </li>
                                <?php 
                                    endforeach;
                                endif;
                                ?>
                            <?php else: ?>
                                <!-- COMPETÊNCIAS PADRÃO POR CURSO -->
                                <?php if($curso['id'] == 1): // Técnico de Obras ?>
                                <li class="item-competencia"><span class="icone-check">✓</span> Planeamento e gestão de obras</li>
                                <li class="item-competencia"><span class="icone-check">✓</span> Fiscalização e controle de qualidade</li>
                                <li class="item-competencia"><span class="icone-check">✓</span> Leitura e interpretação de projetos</li>
                                <li class="item-competencia"><span class="icone-check">✓</span> Gestão de materiais e equipamentos</li>
                                
                                <?php elseif($curso['id'] == 2): // Desenhador Projectista ?>
                                <li class="item-competencia"><span class="icone-check">✓</span> Desenho técnico arquitetônico</li>
                                <li class="item-competencia"><span class="icone-check">✓</span> Projetos estruturais e de instalações</li>
                                <li class="item-competencia"><span class="icone-check">✓</span> Softwares CAD e BIM</li>
                                <li class="item-competencia"><span class="icone-check">✓</span> Modelagem 3D e representação gráfica</li>
                                
                                <?php elseif($curso['id'] == 3): // Energia e Instalações ?>
                                <li class="item-competencia"><span class="icone-check">✓</span> Projetos de instalações elétricas</li>
                                <li class="item-competencia"><span class="icone-check">✓</span> Manutenção de sistemas elétricos</li>
                                <li class="item-competencia"><span class="icone-check">✓</span> Energias renováveis e eficiência energética</li>
                                <li class="item-competencia"><span class="icone-check">✓</span> Automação e comandos elétricos</li>
                                
                                <?php elseif($curso['id'] == 4): // Frio e Climatização ?>
                                <li class="item-competencia"><span class="icone-check">✓</span> Instalação de sistemas de refrigeração</li>
                                <li class="item-competencia"><span class="icone-check">✓</span> Manutenção de equipamentos de climatização</li>
                                <li class="item-competencia"><span class="icone-check">✓</span> Dimensionamento de sistemas HVAC</li>
                                <li class="item-competencia"><span class="icone-check">✓</span> Diagnóstico de falhas em sistemas frigoríficos</li>
                                
                                <?php elseif($curso['id'] == 5): // Gestão de Sistemas ?>
                                <li class="item-competencia"><span class="icone-check">✓</span> Administração de servidores e redes</li>
                                <li class="item-competencia"><span class="icone-check">✓</span> Gestão de bases de dados</li>
                                <li class="item-competencia"><span class="icone-check">✓</span> Segurança informática</li>
                                <li class="item-competencia"><span class="icone-check">✓</span> Desenvolvimento de software</li>
                                
                                <?php elseif($curso['id'] == 6): // Técnico de Informática ?>
                                <li class="item-competencia"><span class="icone-check">✓</span> Montagem e manutenção de computadores</li>
                                <li class="item-competencia"><span class="icone-check">✓</span> Instalação e configuração de sistemas operativos</li>
                                <li class="item-competencia"><span class="icone-check">✓</span> Redes de computadores e conectividade</li>
                                <li class="item-competencia"><span class="icone-check">✓</span> Suporte técnico a utilizadores</li>
                                
                                <?php elseif($curso['id'] == 8): // Tecnologias de Móveis ?>
                                <li class="item-competencia"><span class="icone-check">✓</span> Design de móveis e modelagem 3D</li>
                                <li class="item-competencia"><span class="icone-check">✓</span> Processos de produção moveleira</li>
                                <li class="item-competencia"><span class="icone-check">✓</span> Gestão da indústria moveleira</li>
                                <li class="item-competencia"><span class="icone-check">✓</span> Softwares CAD específicos para móveis</li>
                                
                                <?php else: ?>
                                <?php
                                    $competencias_card = [];
                                    if (!empty($curso['competencias_card'])) {
                                        $competencias_card = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $curso['competencias_card']))));
                                    }
                                    if (empty($competencias_card)) {
                                        $competencias_card = [
                                            'Formação técnica especializada',
                                            'Prática em laboratórios modernos',
                                            'Preparação para o mercado de trabalho'
                                        ];
                                    }
                                    foreach ($competencias_card as $item):
                                ?>
                                <li class="item-competencia"><span class="icone-check">✓</span> <?= htmlspecialchars($item) ?></li>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            <?php endif; ?>
                        </ul>
                        <a href="curso.php?slug=<?= $curso['slug'] ?>" class="botao-detalhes" style="--botao-cor: <?= htmlspecialchars($cor_curso) ?>;">
                            Ver detalhes do curso →
                        </a>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- SEÇÃO OUTRAS ÁREAS -->
        <section class="secao-outras-areas" id="outras-areas">
            <div class="container-outras-areas">
                <h2 class="titulo-outras-areas">Outras Áreas de Formação</h2>

                <div class="grade-outras-areas">
                    <?php foreach($outras_areas as $outra_area): ?>
                    <a href="area.php?slug=<?= $outra_area['slug'] ?>" class="cartao-area" style="--area-cor: <?= $outra_area['cor_primaria'] ?? '#6c757d' ?>;">
                        <div class="topo-cartao-area">
                            <div class="icone-area">
                                <i class="fas <?= $outra_area['icone_classe'] ?? 'fa-graduation-cap' ?>"></i>
                            </div>
                            <h3 class="nome-area"><?= htmlspecialchars($outra_area['nome']) ?></h3>
                        </div>
                        <p class="descricao-area-card">
                            <?= htmlspecialchars($outra_area['descricao_curta'] ?? 'Formação técnica especializada') ?>
                        </p>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    </main>

    <!-- ===== BOTÕES FLUTUANTES ===== -->
    <div class="botoes-flutuantes">
        <button class="botao-flutuante" id="botaoTopo"><i class="fas fa-chevron-up"></i></button>
        <?php if($config['whatsapp_numero']): ?>
        <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $config['whatsapp_numero']) ?>" class="botao-flutuante whatsapp" target="_blank"><i class="fab fa-whatsapp"></i></a>
        <?php endif; ?>
    </div>

<?php include __DIR__ . '/includes/footer.php'; ?>

    <script src="js/header-footer.js"></script>
</body>
</html>

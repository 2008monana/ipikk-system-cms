<?php
/**
 * Página de Oferta Formativa - IPIKK
 * Estilo dinâmico para TODAS as áreas (existentes e novas)
 */

require_once '../config/index.php';

// Buscar configurações do site
$config = getDB()->query("SELECT * FROM configuracoes WHERE id = 1")->fetch();

// Buscar todas as áreas de formação ativas
$areas = getDB()->query("
    SELECT * FROM areas 
    WHERE ativo = 1 
    ORDER BY ordem
")->fetchAll();

// Buscar todos os cursos (para menu e contagem)
$todos_cursos = getDB()->query("
    SELECT * FROM cursos WHERE estado = 'ativo' ORDER BY nome
")->fetchAll();

// Agrupar cursos por área_id
$cursos_por_area = [];
foreach ($todos_cursos as $curso) {
    $cursos_por_area[$curso['area_id']][] = $curso;
}

// Mapeamento de imagens padrão por slug (fallback para áreas sem imagem)
$imagens_padrao = [
    'construcao-civil' => 'https://images.unsplash.com/photo-1504307651254-35680f356dfd?w=800&q=80',
    'eletricidade' => 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=800&q=80',
    'mecanica' => 'https://images.unsplash.com/photo-1581092160607-ee22621dd758?w=800&q=80',
    'informatica' => 'https://images.unsplash.com/photo-1518770660439-4636190af475?w=800&q=80',
    'tecnologia-moveis' => 'https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?w=800&q=80'
];

// Cores padrão para áreas existentes (fallback)
$cores_padrao = [
    'construcao-civil' => '#6c757d',
    'eletricidade' => '#2e86c1',
    'mecanica' => '#E67E22',
    'informatica' => '#1F7A4D',
    'tecnologia-moveis' => '#e01a1a'
];

// Função para criar overlay gradiente baseado na cor da área
function criarOverlayGradiente($cor_hex) {
    $cor_hex = ltrim($cor_hex, '#');
    $r = hexdec(substr($cor_hex, 0, 2));
    $g = hexdec(substr($cor_hex, 2, 2));
    $b = hexdec(substr($cor_hex, 4, 2));
    
    $cor_clara = "rgba($r, $g, $b, 0.25)";
    $cor_escura = "rgba(" . round($r * 0.4) . ", " . round($g * 0.4) . ", " . round($b * 0.4) . ", 0.85)";
    
    return "linear-gradient(to bottom, $cor_clara, $cor_escura)";
}

// Função para obter a URL correta da imagem
function getImagemArea($area) {
    global $imagens_padrao;
    
    // Se não tem imagem definida, usar padrão
    if (empty($area['imagem_url'])) {
        return $imagens_padrao[$area['slug']] ?? 'https://images.unsplash.com/photo-1518770660439-4636190af475?w=800&q=80';
    }
    
    // Se já é uma URL externa (http ou https)
    if (strpos($area['imagem_url'], 'http') === 0) {
        return $area['imagem_url'];
    }
    
    // Se já tem o caminho completo começando com ../
    if (strpos($area['imagem_url'], '../') === 0) {
        return $area['imagem_url'];
    }
    
    // Se já tem o caminho uploads/ (sem a barra no início)
    if (strpos($area['imagem_url'], 'uploads/') === 0) {
        return normalizarUrlMidia($area['imagem_url'], '..');
    }
    
    // Se for apenas o nome do arquivo (ex: 69cc66ecaba9e.png)
    return '../uploads/areas/' . $area['imagem_url'];
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IPIKK - Oferta Formativa</title>
    
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="<?= $config['favicon_url'] ?? 'foto/ipikk_new_logo.png' ?>" rel="icon">
    <link rel="stylesheet" href="css/header-footer.css">
    <link rel="stylesheet" href="css/oferta-formativa.css">
    
    <style>
        /* ===== VARIÁVEIS ===== */
        :root {
            --primary-blue: #003072;
            --blue-light: #2e86c1;
            --blue-dark: #001a40;
            --accent-teal: #0a9396;
            --teal-light: #94d2bd;
            --neutral-white: #ffffff;
            --neutral-light: #f0f4f9;
            --neutral-gray: #6c757d;
            --neutral-dark: #212529;
            --transition: all 0.3s ease;
            --sombra-suave: 0 4px 24px rgba(0, 0, 0, 0.08);
            --sombra-hover: 0 14px 44px rgba(0, 0, 0, 0.15);
            --radius: 14px;
        }

        /* ===== HERO ===== */
        .secao-hero-oferta {
            background: linear-gradient(135deg, var(--primary-blue), var(--accent-teal));
            padding: 80px 24px 72px;
            text-align: center;
        }

        .container-hero-oferta {
            max-width: 700px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 18px;
        }

        .rotulo-hero {
            display: inline-block;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.25);
            color: var(--teal-light);
            font-size: 0.78rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            padding: 7px 18px;
            border-radius: 999px;
        }

        .titulo-hero-oferta {
            font-size: clamp(2.2rem, 5vw, 3.2rem);
            color: var(--neutral-white);
            letter-spacing: -0.5px;
            line-height: 1.15;
        }

        .linha-hero {
            display: block;
            width: 56px;
            height: 4px;
            background: linear-gradient(90deg, var(--accent-teal), var(--teal-light));
            border-radius: 4px;
        }

        .subtitulo-hero-oferta {
            font-size: 0.97rem;
            color: rgba(255, 255, 255, 0.75);
            max-width: 560px;
            line-height: 1.75;
        }

        /* ===== GRADE DE ÁREAS ===== */
        .secao-grade-areas {
            padding: 60px 24px 80px;
        }

        .container-grade-areas {
            max-width: 1100px;
            margin: 0 auto;
            display: flex;
            flex-wrap: wrap;
            gap: 26px;
        }

        /* Cartão de área - estilo base para TODAS as áreas */
        .cartao-area {
            display: flex;
            flex-direction: column;
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--sombra-suave);
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            flex: 1 1 300px;
            max-width: calc(33.333% - 18px);
            transition: var(--transition);
        }

        .cartao-area:hover {
            transform: translateY(-5px);
            box-shadow: var(--sombra-hover);
        }

        .capa-area {
            position: relative;
            height: 220px;
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: flex-end;
            padding: 24px 22px;
        }

        .overlay-area {
            position: absolute;
            inset: 0;
            transition: var(--transition);
        }

        .conteudo-capa-area {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .icone-area-formativa {
            width: 46px;
            height: 46px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.15);
            border: 1.5px solid rgba(255, 255, 255, 0.30);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--neutral-white);
            margin-bottom: 4px;
            backdrop-filter: blur(4px);
            transition: var(--transition);
        }

        .cartao-area:hover .icone-area-formativa {
            background: var(--area-cor) !important;
            transform: scale(1.05);
        }

        .titulo-area-formativa {
            font-size: 1.25rem;
            color: var(--neutral-white);
            font-weight: 700;
            line-height: 1.2;
        }

        .descricao-area-formativa {
            font-size: 0.80rem;
            color: rgba(255, 255, 255, 0.78);
            line-height: 1.5;
        }

        /* Rodapé do cartão */
        .rodape-area-formativa {
            background: var(--neutral-white);
            padding: 16px 22px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .contagem-cursos {
            font-size: 0.78rem;
            color: var(--neutral-gray);
            font-weight: 500;
        }

        .botao-ver-area {
            font-size: 0.82rem;
            font-weight: 700;
            color: var(--area-cor, var(--primary-blue));
            border: 2px solid var(--area-cor, var(--primary-blue));
            padding: 7px 16px;
            border-radius: 999px;
            white-space: nowrap;
            transition: var(--transition);
        }

        .cartao-area:hover .botao-ver-area {
            background: var(--area-cor, var(--primary-blue)) !important;
            color: var(--neutral-white) !important;
            border-color: var(--area-cor, var(--primary-blue)) !important;
        }

        /* ===== BOTÕES FLUTUANTES ===== */
        .botoes-flutuantes {
            position: fixed;
            bottom: 30px;
            right: 30px;
            display: flex;
            flex-direction: column-reverse;
            gap: 12px;
            z-index: 999;
        }

        .botao-flutuante {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            background: var(--neutral-white);
            color: var(--neutral-gray);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
            cursor: pointer;
            transition: var(--transition);
            border: 1px solid rgba(0, 139, 181, 0.1);
        }

        .botao-flutuante:hover {
            background: var(--accent-teal);
            color: var(--neutral-white);
            transform: scale(1.1) translateY(-3px);
            box-shadow: 0 12px 25px rgba(10, 147, 150, 0.3);
        }

        .botao-flutuante.whatsapp {
            background: #e8f5e8;
            color: #2e7d32;
        }

        .botao-flutuante.whatsapp:hover {
            background: #d0ebd0;
            color: #1b5e20;
        }

        #botaoTopo {
            display: none;
        }

        /* ===== RESPONSIVIDADE ===== */
        @media (max-width: 900px) {
            .cartao-area {
                max-width: calc(50% - 13px);
            }
        }

        @media (max-width: 580px) {
            .cartao-area {
                max-width: 100%;
            }
            
            .secao-grade-areas {
                padding: 40px 16px 60px;
            }
            
            .botoes-flutuantes {
                bottom: 20px;
                right: 20px;
            }
        }
    </style>
</head>
<body>

<?php include __DIR__ . '/includes/header.php'; ?>

    <!-- ===== CONTEÚDO PRINCIPAL ===== -->
    <main>
        <section class="secao-hero-oferta">
            <div class="container-hero-oferta">
                <span class="rotulo-hero"><?= htmlspecialchars($config['instituicao_acronimo'] ?? 'IPIKK') ?> — Instituto Politécnico Industrial</span>
                <h1 class="titulo-hero-oferta">Oferta Formativa</h1>
                <span class="linha-hero"></span>
                <p class="subtitulo-hero-oferta">
                    Conheça as áreas de formação técnico-profissional disponíveis no Instituto
                    Politécnico Industrial do Kilamba Kiaxi nº 8050 "Nova Vida"
                </p>
            </div>
        </section>

        <section class="secao-grade-areas">
            <div class="container-grade-areas">
                <?php foreach($areas as $area): 
                    $total_cursos = count($cursos_por_area[$area['id']] ?? []);
                    
                    // Definir cor da área (usar a do banco ou fallback)
                    $cor_area = !empty($area['cor_primaria']) 
                        ? $area['cor_primaria'] 
                        : ($cores_padrao[$area['slug']] ?? '#003072');
                    
                    // CORREÇÃO: Usar a função para obter a URL correta da imagem
                    $imagem_url = getImagemArea($area);
                    
                    // Criar overlay gradiente com a cor da área
                    $overlay_gradiente = criarOverlayGradiente($cor_area);
                ?>
                <a href="area.php?slug=<?= $area['slug'] ?>" class="cartao-area" style="--area-cor: <?= $cor_area ?>;">
                    <div class="capa-area" style="background-image: url('<?= $imagem_url ?>')">
                        <div class="overlay-area" style="background: <?= $overlay_gradiente ?>;"></div>
                        <div class="conteudo-capa-area">
                            <div class="icone-area-formativa">
                                <i class="fas <?= $area['icone_classe'] ?? 'fa-graduation-cap' ?>"></i>
                            </div>
                            <h2 class="titulo-area-formativa"><?= htmlspecialchars($area['nome']) ?></h2>
                            <p class="descricao-area-formativa"><?= htmlspecialchars($area['descricao_curta'] ?? 'Formação técnica especializada') ?></p>
                        </div>
                    </div>
                    <div class="rodape-area-formativa">
                        <span class="contagem-cursos"><?= $total_cursos ?> curso<?= $total_cursos != 1 ? 's' : '' ?> disponível<?= $total_cursos != 1 ? 'is' : '' ?></span>
                        <span class="botao-ver-area">Explorar área →</span>
                    </div>
                </a>
                <?php endforeach; ?>
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
    <script src="js/oferta-formativa.js"></script>
</body>
</html>
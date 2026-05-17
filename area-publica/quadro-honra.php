<?php
/**
 * Página Quadro de Honra - IPIKK
 */

require_once '../config/index.php';

$config = getDB()->query("SELECT * FROM configuracoes WHERE id = 1")->fetch();

// Buscar quadro de honra ativo
$qh = getDB()->query("SELECT * FROM quadro_honra WHERE ativo = 1 ORDER BY id DESC LIMIT 1")->fetch();
$ano_lectivo = $qh['ano_lectivo'] ?? date('Y') . '/' . (date('Y') + 1);

// Buscar alunos por classe
$alunos = [];
$alunos_db = [];
if ($qh) {
    $stmt = getDB()->prepare("SELECT * FROM quadro_honra_classe WHERE quadro_honra_id = ? ORDER BY classe");
    $stmt->execute([$qh['id']]);
    $alunos_db = $stmt->fetchAll();
}

foreach ($alunos_db as $aluno) {
    $alunos[$aluno['classe']] = $aluno;
}

// Cores padrão para cursos (mapeamento completo)
$cores_padrao = [
    'Energia e Instalações Eléctricas' => '#2e86c1',
    'Energia e Instalações Elétricas' => '#2e86c1',
    'Informática' => '#1F7A4D',
    'Técnico de Informática' => '#2d7a3a',
    'Gestão de Sistemas' => '#1a5a8c',
    'Técnico de Obras' => '#6c757d',
    'Construção Civil' => '#6c757d',
    'Obras de Construção Civil' => '#6c757d',
    'Desenhador Projectista' => '#b46e00',
    'Frio e Climatização' => '#e07b2a',
    'Tecnologias de Móveis' => '#c0392b',
    'Tecnologia de Móveis' => '#c0392b',
    'Alfaiataria' => '#a63cc3',
    'costura' => '#ff055d'
];

function getCorCurso($curso) {
    global $cores_padrao;
    return $cores_padrao[$curso] ?? '#003072';
}

// Determinar melhor aluno geral (maior média)
$melhor_aluno = null;
$maior_media = -1;
foreach ($alunos_db as $aluno) {
    // Extrair valor numérico da média (ex: "17 Valores" -> 17)
    $media_num = filter_var($aluno['media'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    if ($media_num !== false && (float)$media_num > $maior_media) {
        $maior_media = (float)$media_num;
        $melhor_aluno = $aluno;
    }
}

// Buscar citação
$pagina = getPagina('quadro-honra');
$citacao = $pagina['citacao'] ?? ['texto' => '', 'referencia' => ''];
$lema = $config['instituicao_slogan'] ?? 'Por amor, primemos por uma educação de qualidade';
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IPIKK - Quadro de Honra</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="<?= $config['favicon_url'] ?? 'foto/ipikk_new_logo.png' ?>" rel="icon">
    <link rel="stylesheet" href="css/header-footer.css">

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Montserrat', sans-serif; background: #f0f4f8; }

        /* Hero */
        .hero {
            background: linear-gradient(160deg, #003072 0%, #0a9396 100%);
            padding: 60px 24px 50px;
            text-align: center;
            position: relative;
        }
        .hero .ano {
            position: absolute;
            top: 20px; right: 24px;
            background: rgba(255,255,255,0.1);
            border-radius: 8px;
            padding: 8px 16px;
            color: #e8b84b;
            font-weight: 800;
        }
        .hero h1 { font-size: clamp(2rem, 5vw, 3rem); color: white; margin-bottom: 10px; }
        .hero h1 em { color: #e8b84b; font-style: normal; }
        .hero p { color: rgba(255,255,255,0.6); font-style: italic; }

        /* Melhor Aluno */
        .melhor-aluno {
            background: white;
            padding: 60px 24px;
            text-align: center;
        }
        .melhor-aluno .badge {
            display: inline-block;
            background: linear-gradient(135deg, #003072, #2e86c1);
            color: white;
            padding: 6px 18px;
            border-radius: 30px;
            font-size: 12px;
            margin-bottom: 20px;
        }
        .melhor-aluno h2 { font-size: 28px; color: #003072; margin-bottom: 30px; }

        .card-melhor {
            max-width: 400px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            overflow: hidden;
        }
        /* Topo com a cor do curso do melhor aluno */
        .card-melhor .topo {
            height: 100px;
            position: relative;
        }
        .card-melhor .medalha {
            position: absolute;
            top: 12px;
            right: 14px;
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #b8923a, #e8b84b);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            z-index: 2;
        }
        .card-melhor .foto {
            margin-top: -50px;
            text-align: center;
            position: relative;
            z-index: 2;
        }
        .card-melhor .foto img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
            background: white;
        }
        .card-melhor .info {
            padding: 20px 24px 30px;
            text-align: left;
        }
        .card-melhor .nome {
            font-size: 20px;
            font-weight: 800;
            color: #003072;
            margin-bottom: 20px;
            text-align: center;
            border-bottom: 2px solid #eef2f6;
            padding-bottom: 15px;
        }
        .card-melhor .dados {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .card-melhor .dado {
            display: flex;
            align-items: baseline;
            gap: 10px;
            font-size: 14px;
            flex-wrap: wrap;
        }
        .card-melhor .dado .rotulo {
            font-weight: 700;
            color: #6c757d;
            text-transform: uppercase;
            font-size: 11px;
            min-width: 55px;
            letter-spacing: 0.5px;
        }
        .card-melhor .dado .valor {
            color: #2c3e50;
            font-weight: 500;
        }
        .card-melhor .dado .valor-media {
            font-weight: 800;
            color: #0a9396;
            font-size: 16px;
        }

        /* Cards por classe */
        .por-classe {
            background: #f8f9fa;
            padding: 60px 24px;
        }
        .por-classe h2 {
            text-align: center;
            font-size: 28px;
            color: #003072;
            margin-bottom: 40px;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
            max-width: 900px;
            margin: 0 auto;
        }
        .card-classe {
            background: white;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 6px 20px rgba(0,0,0,0.08);
            transition: transform 0.3s;
        }
        .card-classe:hover {
            transform: translateY(-5px);
        }
        .card-classe .topo {
            text-align: center;
            padding: 12px;
            color: white;
            font-weight: 700;
            font-size: 14px;
        }
        .card-classe .foto {
            background: #eef1f6;
            display: flex;
            justify-content: center;
            padding: 20px 16px 0;
        }
        .card-classe .foto img {
            width: 96px;
            height: 96px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid white;
            box-shadow: 0 4px 14px rgba(0,0,0,0.12);
            background: white;
        }
        .card-classe .info {
            padding: 16px;
            text-align: center;
        }
        .card-classe .nome {
            font-weight: 700;
            color: #003072;
            font-size: 14px;
            margin-bottom: 5px;
        }
        .card-classe .media {
            font-weight: 800;
            color: #0a9396;
            font-size: 16px;
            margin-bottom: 5px;
        }
        .card-classe .curso {
            font-size: 12px;
            color: #6c757d;
        }

        /* Citação */
        .citacao {
            background: #fdf6e3;
            text-align: center;
            padding: 50px 24px;
            border-top: 4px solid #e8b84b;
            border-bottom: 4px solid #e8b84b;
        }
        .citacao .texto {
            font-size: 18px;
            font-style: italic;
            color: #003072;
            max-width: 600px;
            margin: 0 auto 15px;
        }
        .citacao .ref {
            color: #b8923a;
            font-weight: 700;
            letter-spacing: 2px;
        }

        @media (max-width: 768px) {
            .grid {
                grid-template-columns: 1fr;
                max-width: 280px;
                margin: 0 auto;
            }
            .card-melhor .dado {
                flex-direction: column;
                gap: 4px;
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

<!-- Hero -->
<section class="hero">
    <div class="ano"><strong><?= htmlspecialchars($ano_lectivo) ?></strong></div>
    <h1>Quadro de <em>Honra</em></h1>
    <p><?= htmlspecialchars($lema) ?></p>
</section>

<?php if (empty($alunos_db)): ?>
<section class="por-classe">
    <div class="mensagem-vazia">
        <i class="fas fa-info-circle"></i>
        Os estudantes do quadro de honra serão divulgados em breve.
    </div>
</section>
<?php else: ?>
<!-- Melhor Aluno Geral -->
<?php if ($melhor_aluno):
    $cor_melhor = getCorCurso($melhor_aluno['curso']);
?>
<section class="melhor-aluno">
    <span class="badge"><i class="fas fa-trophy"></i> Melhor Aluno Geral</span>
    <h2>Destaque do Ano Lectivo</h2>
    <div class="card-melhor">
        <div class="topo" style="background: <?= $cor_melhor ?>;">
            <div class="medalha"><i class="fas fa-trophy"></i></div>
        </div>
        <div class="foto">
            <img src="<?= htmlspecialchars(normalizarUrlMidia($melhor_aluno['foto_url'] ?? 'foto/sem_foto.png', '..')) ?>"
                 onerror="this.src='foto/sem_foto.png'">
        </div>
        <div class="info">
            <div class="nome"><?= htmlspecialchars($melhor_aluno['nome']) ?></div>
            <div class="dados">
                <div class="dado">
                    <span class="rotulo">Média</span>
                    <span class="valor valor-media"><?= htmlspecialchars($melhor_aluno['media']) ?></span>
                </div>
                <div class="dado">
                    <span class="rotulo">Curso</span>
                    <span class="valor"><?= htmlspecialchars($melhor_aluno['curso']) ?></span>
                </div>
                <div class="dado">
                    <span class="rotulo">Classe</span>
                    <span class="valor"><?= $melhor_aluno['classe'] ?>ª Classe</span>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Melhor Aluno por Classe -->
<section class="por-classe">
    <h2>Melhor Aluno por Classe</h2>
    <div class="grid">
        <?php for($classe = 10; $classe <= 12; $classe++):
            $aluno = $alunos[$classe] ?? null;
            $cor = getCorCurso($aluno['curso'] ?? '');
            $nome = $aluno ? htmlspecialchars($aluno['nome']) : 'A ser divulgado';
            $media = $aluno ? htmlspecialchars($aluno['media']) : '--';
            $curso = $aluno ? htmlspecialchars($aluno['curso']) : '--';
            $foto = $aluno && !empty($aluno['foto_url']) ? normalizarUrlMidia($aluno['foto_url'], '..') : 'foto/sem_foto.png';
        ?>
        <div class="card-classe">
            <div class="topo" style="background: <?= $cor ?>;"><?= $classe ?>ª Classe</div>
            <div class="foto">
                <img src="<?= $foto ?>" onerror="this.src='foto/sem_foto.png'">
            </div>
            <div class="info">
                <div class="nome"><?= $nome ?></div>
                <div class="media"><?= $media ?></div>
                <div class="curso"><?= $curso ?></div>
            </div>
        </div>
        <?php endfor; ?>
    </div>
</section>
<?php endif; ?>

<!-- Citação -->
<?php if (!empty($citacao['texto'])): ?>
<section class="citacao">
    <div class="texto">"<?= htmlspecialchars($citacao['texto']) ?>"</div>
    <div class="ref"><?= htmlspecialchars($citacao['referencia']) ?></div>
</section>
<?php endif; ?>

<!-- Botões flutuantes -->
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
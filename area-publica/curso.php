<?php
/**
 * Pagina de Curso - IPIKK
 * UMA pagina para TODOS os cursos
 * O conteudo e filtrado pelo parametro 'slug' na URL
 * Ex: curso.php?slug=construcao-civil-obras
 */

require_once '../config/index.php';

$config = getDB()->query("SELECT * FROM configuracoes WHERE id = 1")->fetch();

$curso_slug = $_GET['slug'] ?? null;

if (!$curso_slug) {
    header('Location: oferta-formativa.php');
    exit;
}

$stmt = getDB()->prepare("
    SELECT c.*, a.nome as area_nome, a.slug as area_slug, a.cor_primaria as area_cor, a.icone_classe as area_icone
    FROM cursos c
    JOIN areas a ON c.area_id = a.id
    WHERE c.slug = ? AND c.estado = 'ativo'
");
$stmt->execute([$curso_slug]);
$curso = $stmt->fetch();

if (isset($curso) && $curso['id']) {
    incrementarVisualizacaoCurso($curso['id']);
} 

if (!$curso) {
    header('HTTP/1.0 404 Not Found');
    echo "<h1>Curso nao encontrado</h1>";
    exit;
}

// ============================================
// COR E ICONE DO CURSO - VINDO DA BASE DE DADOS
// ============================================

// Cor do tema: usa a cor do curso, ou a cor da area, ou cor padrao
$cor_tema = !empty($curso['cor']) ? $curso['cor'] : (!empty($curso['area_cor']) ? $curso['area_cor'] : '#003072');

// Icone do curso: usa o icone do curso, ou o icone da area, ou icone padrao
$icone_curso = !empty($curso['icone_classe']) ? $curso['icone_classe'] : (!empty($curso['area_icone']) ? $curso['area_icone'] : 'fa-graduation-cap');

// Slug do curso para CSS
$curso_slug_css = $curso['slug'];

// Imagem de fundo do heroi (usa imagem do curso ou fallback)
$heroi_imagem = !empty($curso['imagem_hero']) ? normalizarUrlMidia($curso['imagem_hero'], '..') : 'https://images.unsplash.com/photo-1541888946425-d81bb19240f5?w=1470&q=80';

// Gradiente baseado na cor do tema
$gradiente_heroi = "linear-gradient(to bottom, " . hex2rgba($cor_tema, 0.3) . " 0%, " . hex2rgba($cor_tema, 0.85) . " 100%)";

// Funcao auxiliar para converter hex para rgba
function hex2rgba($hex, $alpha = 1) {
    $hex = str_replace('#', '', $hex);
    if (strlen($hex) == 3) {
        $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
        $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
        $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
    } else {
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
    }
    return "rgba($r, $g, $b, $alpha)";
}

// ============================================
// BUSCAR DADOS RELACIONADOS (INCLUINDO CLASSE 0 - RESUMO GERAL)
// ============================================

// Buscar PDFs do plano curricular (classes 0, 10, 11, 12, 13)
$stmt = getDB()->prepare("SELECT * FROM plano_curricular WHERE curso_id = ? AND classe IN (0, 10, 11, 12, 13) ORDER BY classe");
$stmt->execute([$curso['id']]);
$plano_curricular = $stmt->fetchAll();

$pdfs = [];
foreach ($plano_curricular as $pc) {
    $pdfs[$pc['classe']] = $pc;
}

// Buscar saidas profissionais
$stmt = getDB()->prepare("SELECT * FROM saidas_profissionais WHERE curso_id = ? ORDER BY ordem");
$stmt->execute([$curso['id']]);
$saidas = $stmt->fetchAll();

foreach ($saidas as &$saida) {
    $saida['competencias'] = json_decode($saida['competencias'], true) ?? [];
}
unset($saida);

// Buscar projetos
$stmt = getDB()->prepare("SELECT * FROM projetos WHERE curso_id = ? ORDER BY ordem");
$stmt->execute([$curso['id']]);
$projetos = $stmt->fetchAll();

// Buscar depoimentos
$stmt = getDB()->prepare("SELECT * FROM depoimentos WHERE curso_id = ? AND ativo = 1 ORDER BY destaque DESC, ordem ASC");
$stmt->execute([$curso['id']]);
$depoimentos = $stmt->fetchAll();

// Buscar areas para o menu
$stmt = getDB()->query("SELECT * FROM areas WHERE ativo = 1 ORDER BY ordem");
$todas_areas = $stmt->fetchAll();

$todos_cursos = getDB()->query("SELECT * FROM cursos WHERE estado = 'ativo' ORDER BY nome")->fetchAll();
$cursos_por_area = [];
foreach ($todos_cursos as $curso_item) {
    $cursos_por_area[$curso_item['area_id']][] = $curso_item;
}

$titulo_pagina = "IPIKK - " . htmlspecialchars($curso['nome']);
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
        /* ... (manter o mesmo estilo do seu curso.php original) ... */
        :root {
            --cor-institucional: #1A3A5F;
            --cor-institucional-clara: #2A5C8B;
            --cor-institucional-escura: #0F2842;
            --cor-texto: #333333;
            --cor-texto-clara: #666666;
            --cor-texto-mais-clara: #6c757d;
            --cor-fundo: #FFFFFF;
            --cor-fundo-alt: #F8F9FA;
            --cor-fundo-secao: #F0F4F8;
            --cor-borda: #E0E6ED;
            --cor-tema: <?= $cor_tema ?>;
            --cor-tema-suave: <?= hex2rgba($cor_tema, 0.08) ?>;
            --fonte-titulo: 'Georgia', 'Times New Roman', serif;
            --fonte-corpo: 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
            --espaco-xs: 0.5rem;
            --espaco-sm: 1rem;
            --espaco-md: 2rem;
            --espaco-lg: 3rem;
            --espaco-xl: 4rem;
            --espaco-xxl: 6rem;
            --raio-borda: 8px;
            --sombra-suave: 0 4px 12px rgba(0, 0, 0, 0.05);
            --sombra-cartao: 0 6px 20px rgba(0, 0, 0, 0.08);
            --transicao: all 0.3s ease;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        
        body {
            font-family: var(--fonte-corpo);
            color: var(--cor-texto);
            line-height: 1.6;
            background-color: var(--cor-fundo);
            overflow-x: hidden;
        }

        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 var(--espaco-md);
        }

        .secao { padding: var(--espaco-xl) 0; }
        .secao-alt { background-color: var(--cor-fundo-alt); }
        
        .titulo-secao {
            font-family: var(--fonte-titulo);
            font-size: 2.2rem;
            color: var(--cor-institucional);
            text-align: center;
            margin-bottom: var(--espaco-lg);
            position: relative;
            font-weight: 600;
        }
        
        .titulo-secao::after {
            content: '';
            display: block;
            width: 60px;
            height: 4px;
            background-color: var(--cor-tema);
            margin: var(--espaco-sm) auto;
            border-radius: 2px;
        }
        
        .subtitulo-secao {
            text-align: center;
            color: var(--cor-texto-clara);
            font-size: 1.1rem;
            max-width: 700px;
            margin: 0 auto var(--espaco-xl);
            line-height: 1.7;
        }

        /* SECAO HEROI */
        .heroi-curso {
            color: white;
            padding: var(--espaco-xxl) 0;
            position: relative;
            background: <?= $gradiente_heroi ?>, url('<?= $heroi_imagem ?>');
            background-size: cover;
            background-position: center;
        }

        .conteudo-heroi {
            max-width: 800px;
            text-align: center;
            margin: 0 auto;
        }

        .emblema-heroi {
            width: 80px;
            height: 80px;
            background-color: rgba(255, 255, 255, 0.15);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto var(--espaco-lg);
            backdrop-filter: blur(5px);
            border: 2px solid rgba(255, 255, 255, 0.2);
        }

        .emblema-heroi i { font-size: 2.5rem; color: white; }
        .titulo-heroi { font-family: var(--fonte-titulo); font-size: 3rem; margin-bottom: var(--espaco-sm); line-height: 1.2; font-weight: 700; }
        .subtitulo-heroi { font-size: 1.3rem; opacity: 0.9; margin-bottom: var(--espaco-xl); line-height: 1.6; max-width: 700px; margin-left: auto; margin-right: auto; }

        .metadados-heroi {
            display: flex;
            justify-content: center;
            gap: var(--espaco-lg);
            flex-wrap: wrap;
            margin-top: var(--espaco-xl);
        }

        .cartao-metadado {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: var(--raio-borda);
            padding: var(--espaco-md);
            min-width: 160px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.15);
            transition: var(--transicao);
        }

        .cartao-metadado:hover { transform: translateY(-5px); background: rgba(255, 255, 255, 0.15); }
        .icone-metadado { font-size: 1.8rem; margin-bottom: var(--espaco-sm); color: white; }
        .rotulo-metadado { font-size: 0.9rem; opacity: 0.8; margin-bottom: var(--espaco-xs); text-transform: uppercase; letter-spacing: 1px; }
        .valor-metadado { font-size: 1.2rem; font-weight: 600; }

        /* SOBRE O CURSO */
        .grade-sobre {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: var(--espaco-lg);
        }

        .cartao-sobre {
            background: var(--cor-fundo);
            padding: var(--espaco-lg);
            border-radius: var(--raio-borda);
            box-shadow: var(--sombra-cartao);
            transition: var(--transicao);
            border-top: 4px solid var(--cor-tema);
        }

        .cartao-sobre:hover { transform: translateY(-8px); box-shadow: 0 12px 30px rgba(0, 0, 0, 0.1); }
        .icone-sobre {
            width: 60px;
            height: 60px;
            background-color: var(--cor-tema-suave);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: var(--espaco-md);
            color: var(--cor-tema);
            font-size: 1.5rem;
        }
        .cartao-sobre h3 { font-family: var(--fonte-titulo); font-size: 1.4rem; color: var(--cor-institucional); margin-bottom: var(--espaco-sm); }
        .cartao-sobre p { color: var(--cor-texto-clara); line-height: 1.7; }

        /* PLANO CURRICULAR (COM ABA GERAL) */
        .abas-plano {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: var(--espaco-sm);
            margin-bottom: var(--espaco-lg);
            border-bottom: 1px solid var(--cor-borda);
            padding-bottom: var(--espaco-md);
        }

        .botao-aba {
            padding: var(--espaco-sm) var(--espaco-lg);
            background: transparent;
            border: 2px solid var(--cor-borda);
            border-radius: var(--raio-borda);
            font-family: var(--fonte-corpo);
            font-size: 1rem;
            font-weight: 500;
            color: var(--cor-texto);
            cursor: pointer;
            transition: var(--transicao);
        }

        .botao-aba:hover { background-color: var(--cor-tema-suave); color: var(--cor-tema); border-color: var(--cor-tema); }
        .botao-aba.ativo { background-color: var(--cor-tema); color: white; font-weight: 600; border-color: var(--cor-tema); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }

        .conteudo-plano {
            background: var(--cor-fundo);
            border-radius: var(--raio-borda);
            overflow: hidden;
            box-shadow: var(--sombra-suave);
            padding: var(--espaco-lg);
            min-height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .bloco-documento-pdf {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: var(--espaco-md);
            padding: var(--espaco-md) var(--espaco-lg);
            background: linear-gradient(135deg, var(--cor-fundo-alt), var(--cor-fundo));
            border: 1px solid var(--cor-borda);
            border-left: 4px solid var(--cor-tema);
            border-radius: var(--raio-borda);
            width: 100%;
            transition: var(--transicao);
        }

        .bloco-documento-pdf:hover { transform: translateY(-2px); box-shadow: var(--sombra-cartao); }
        .info-pdf { display: flex; align-items: center; gap: var(--espaco-md); }
        .icone-pdf { font-size: 2.5rem; color: #e53e3e; flex-shrink: 0; }
        .textos-pdf { display: flex; flex-direction: column; gap: 4px; }
        .rotulo-pdf { font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; color: var(--cor-texto-mais-clara); font-weight: 600; }
        .titulo-pdf { font-family: var(--fonte-titulo); font-size: 1.1rem; color: var(--cor-institucional); font-weight: 600; line-height: 1.3; }
        .descricao-pdf { font-size: 0.88rem; color: var(--cor-texto-clara); }

        .botao-ver-documento {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background-color: var(--cor-tema);
            color: white;
            border: none;
            border-radius: var(--raio-borda);
            font-family: var(--fonte-corpo);
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: var(--transicao);
            white-space: nowrap;
            flex-shrink: 0;
        }

        .botao-ver-documento:hover { background-color: var(--cor-tema); filter: brightness(1.1); transform: translateY(-2px); box-shadow: 0 6px 18px rgba(0,0,0,0.18); }

        /* SAIDAS PROFISSIONAIS */
        .grade-saidas {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }
        .cartao-aviso-vazio {
            grid-column: 1 / -1;
            text-align: center;
            padding: 2rem 1.5rem;
            border: 1px dashed var(--cor-borda);
            border-radius: 12px;
            background: var(--cor-fundo);
            color: var(--cor-texto-claro);
        }
        .cartao-aviso-vazio i {
            font-size: 2rem;
            color: var(--cor-tema);
            margin-bottom: .6rem;
        }
        .cartao-aviso-vazio p { margin: 0; font-weight: 500; }

        .cartao-saida {
            background: var(--cor-fundo);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 6px 20px rgba(0,0,0,0.08);
            transition: var(--transicao);
            border: 1px solid var(--cor-borda);
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .cartao-saida:hover { transform: translateY(-8px); box-shadow: 0 15px 35px rgba(0,0,0,0.15); }

        .imagem-saida {
            width: 100%;
            height: 180px;
            overflow: hidden;
            position: relative;
        }

        .imagem-saida img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .cartao-saida:hover .imagem-saida img { transform: scale(1.05); }

        .imagem-saida-fallback {
            width: 100%;
            height: 180px;
            background: linear-gradient(135deg, var(--cor-tema-suave), <?= hex2rgba($cor_tema, 0.2) ?>);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            color: var(--cor-tema);
        }

        .cabecalho-saida {
            background-color: var(--cor-tema);
            color: white;
            padding: 16px 20px;
            text-align: center;
            flex-shrink: 0;
        }

        .titulo-saida {
            font-family: var(--fonte-titulo);
            font-size: 1.2rem;
            font-weight: 600;
            margin: 0;
        }

        .corpo-saida {
            padding: 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .descricao-saida {
            color: var(--cor-texto-clara);
            line-height: 1.7;
            margin-bottom: 16px;
            flex-grow: 1;
        }

        .competencias-saida {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .etiqueta-competencia {
            background-color: var(--cor-tema-suave);
            color: var(--cor-tema);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        /* PROJECTOS */
        .secao-projectos { background-color: var(--cor-fundo-secao); }
        
        .grade-projectos {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: var(--espaco-lg);
        }

        .cartao-projecto {
            background: var(--cor-fundo);
            border-radius: var(--raio-borda);
            overflow: hidden;
            box-shadow: var(--sombra-cartao);
            transition: var(--transicao);
            border: 1px solid var(--cor-borda);
        }

        .cartao-projecto:hover { transform: translateY(-8px); box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1); }
        .imagem-projecto { width: 100%; height: 220px; object-fit: cover; display: block; }
        .imagem-projecto-fallback {
            width: 100%;
            height: 220px;
            background: linear-gradient(135deg, var(--cor-tema-suave), <?= hex2rgba($cor_tema, 0.2) ?>);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            color: var(--cor-tema);
        }
        .conteudo-projecto { padding: var(--espaco-lg); }
        
        .categoria-projecto {
            display: inline-block;
            background-color: var(--cor-tema-suave);
            color: var(--cor-tema);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: var(--espaco-sm);
        }
        
        .titulo-projecto { font-family: var(--fonte-titulo); font-size: 1.4rem; color: var(--cor-institucional); margin-bottom: var(--espaco-sm); line-height: 1.3; }
        .descricao-projecto { color: var(--cor-texto-clara); line-height: 1.7; margin-bottom: var(--espaco-md); }
        
        .metadados-projecto {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: var(--espaco-sm);
            border-top: 1px solid var(--cor-borda);
            color: var(--cor-texto-mais-clara);
            font-size: 0.9rem;
        }
        
        .ano-projecto, .autor-projecto { display: flex; align-items: center; gap: 5px; }

        /* DEPOIMENTOS */
        .secao-depoimentos {
            background-color: var(--cor-fundo-secao);
            padding: var(--espaco-xl) 0;
        }

        .cabecalho-secao {
            text-align: center;
            margin-bottom: var(--espaco-xl);
            padding: 0 var(--espaco-md);
        }

        .subtitulo {
            color: var(--cor-texto-clara);
            font-size: 1.05rem;
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.7;
        }

        .area-slider {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 var(--espaco-md);
            position: relative;
        }

        .card-depoimento {
            display: flex;
            background: var(--cor-fundo);
            border-radius: var(--raio-borda);
            box-shadow: var(--sombra-cartao);
            overflow: hidden;
            margin-bottom: var(--espaco-lg);
        }

        .barra-lateral { width: 6px; background: var(--cor-tema); flex-shrink: 0; }
        .conteudo-cartao { padding: var(--espaco-lg); flex: 1; }
        
        .cabecalho-cartao {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: var(--espaco-lg);
            flex-wrap: wrap;
            gap: var(--espaco-sm);
        }

        .bloco-perfil { display: flex; align-items: center; gap: var(--espaco-md); }
        
        .avatar-wrapper {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            overflow: hidden;
            border: 3px solid var(--cor-tema-suave);
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--cor-fundo-alt);
            flex-shrink: 0;
        }
        
        .avatar-wrapper img { width: 100%; height: 100%; object-fit: cover; }
        .avatar-fallback { font-size: 3rem; color: var(--cor-texto-clara); display: flex; align-items: center; justify-content: center; }
        
        .info-perfil { display: flex; flex-direction: column; gap: 3px; }
        .nome-alumni { font-family: var(--fonte-titulo); font-size: 1.1rem; color: var(--cor-institucional); font-weight: 600; }
        .curso-alumni { font-size: 0.85rem; color: var(--cor-tema); font-weight: 500; }
        .empresa-alumni { display: flex; align-items: center; gap: 5px; font-size: 0.82rem; color: var(--cor-texto-mais-clara); }

        .bloco-texto { position: relative; }
        .aspas-decorativas { position: absolute; top: -8px; right: 0; font-size: 2rem; color: var(--cor-tema); opacity: 0.15; }
        .texto-depoimento { font-size: 1rem; font-style: italic; color: var(--cor-texto); line-height: 1.8; padding-right: var(--espaco-xl); }

        .navegacao-slider {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: var(--espaco-lg);
        }

        .botao-seta {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            border: 2px solid var(--cor-borda);
            background: var(--cor-fundo);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transicao);
            color: var(--cor-institucional);
            flex-shrink: 0;
        }

        .botao-seta:hover { background: var(--cor-tema); border-color: var(--cor-tema); color: white; }
        .botao-seta svg { width: 20px; height: 20px; }

        .pontos-indicadores { display: flex; gap: 8px; align-items: center; }
        .ponto {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: var(--cor-borda);
            cursor: pointer;
            transition: var(--transicao);
            border: none;
            padding: 0;
        }
        .ponto.ativo { background: var(--cor-tema); transform: scale(1.3); }

        @media (max-width: 768px) {
            .titulo-heroi { font-size: 2rem; }
            .subtitulo-heroi { font-size: 1.1rem; }
            .metadados-heroi { gap: var(--espaco-sm); }
            .cartao-metadado { min-width: 130px; padding: var(--espaco-sm); }
            .grade-sobre, .grade-saidas, .grade-projectos { grid-template-columns: 1fr; }
            .abas-plano { flex-direction: column; align-items: center; }
            .botao-aba { width: 100%; max-width: 300px; }
            .bloco-documento-pdf { flex-direction: column; align-items: flex-start; }
            .botao-ver-documento { width: 100%; justify-content: center; }
            .card-depoimento { flex-direction: column; }
            .barra-lateral { width: 100%; height: 5px; }
            .cabecalho-cartao { flex-direction: column; }
        }

        @media (max-width: 480px) {
            .titulo-heroi { font-size: 1.6rem; }
            .titulo-secao { font-size: 1.6rem; }
        }
    </style>
</head>
<body data-tema="<?= $curso_slug_css ?>">


    <!-- ===== CABECALHO ===== -->

<?php include __DIR__ . '/includes/header.php'; ?>

    <!-- ===== CONTEUDO PRINCIPAL ===== -->
    <main>
        <!-- SECAO HEROI -->
        <section class="heroi-curso">
            <div class="container">
                <div class="conteudo-heroi">
                    <div class="emblema-heroi">
                        <i class="fas <?= $icone_curso ?>"></i>
                    </div>
                    <h1 class="titulo-heroi"><?= htmlspecialchars($curso['nome']) ?></h1>
                    <p class="subtitulo-heroi">
                        <?= htmlspecialchars($curso['subtitulo_hero'] ?? $curso['descricao_curta'] ?? 'Formacao tecnica especializada') ?>
                    </p>

                    <div class="metadados-heroi">
                        <div class="cartao-metadado">
                            <div class="icone-metadado"><i class="far fa-clock"></i></div>
                            <div class="rotulo-metadado">Duracao</div>
                            <div class="valor-metadado"><?= htmlspecialchars($curso['duracao'] ?? '4 Anos') ?></div>
                        </div>
                        <div class="cartao-metadado">
                            <div class="icone-metadado"><i class="fas fa-graduation-cap"></i></div>
                            <div class="rotulo-metadado">Nivel</div>
                            <div class="valor-metadado"><?= htmlspecialchars($curso['nivel'] ?? 'Tecnico Medio') ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- SOBRE O CURSO -->
        <section class="secao" id="sobre">
            <div class="container">
                <h2 class="titulo-secao">Sobre o Curso</h2>
                <p class="subtitulo-secao">
                    <?= htmlspecialchars($curso['sobre_descricao'] ?? 'Formacao completa para o mercado de trabalho') ?>
                </p>

                <div class="grade-sobre">
                    <div class="cartao-sobre">
                        <div class="icone-sobre"><i class="fas fa-bullseye"></i></div>
                        <h3>Objectivo</h3>
                        <p><?= nl2br(htmlspecialchars($curso['objetivo'] ?? 'Formar profissionais capacitados para actuar no mercado de trabalho.')) ?></p>
                    </div>
                    <div class="cartao-sobre">
                        <div class="icone-sobre"><i class="fas fa-tools"></i></div>
                        <h3>Competencias</h3>
                        <p><?= nl2br(htmlspecialchars($curso['competencias_descricao'] ?? 'Desenvolver competencias tecnicas e praticas na area.')) ?></p>
                    </div>
                    <div class="cartao-sobre">
                        <div class="icone-sobre"><i class="fas fa-certificate"></i></div>
                        <h3>Certificacao</h3>
                        <p><?= nl2br(htmlspecialchars($curso['certificacao_descricao'] ?? 'Diploma de Tecnico Medio reconhecido pelo Ministerio da Educacao.')) ?></p>
                    </div>
                </div>
            </div>
        </section>

        <!-- PLANO CURRICULAR (COM ABA GERAL) -->
        <section class="secao secao-alt" id="plano">
            <div class="container">
                <h2 class="titulo-secao">Plano Curricular</h2>
                <p class="subtitulo-secao">Selecione a classe para aceder ao documento oficial do plano curricular em PDF.</p>
                
                <div class="abas-plano" id="abasPlano">
                    <button class="botao-aba ativo" data-classe="10">10ª Classe</button>
                    <button class="botao-aba" data-classe="11">11ª Classe</button>
                    <button class="botao-aba" data-classe="12">12ª Classe</button>
                    <button class="botao-aba" data-classe="13">13ª Classe</button>
                    <button class="botao-aba" data-classe="0">Geral (Completo)</button>
                </div>

                <div class="conteudo-plano" id="conteudoPlano"></div>
            </div>
        </section>

        <!-- SAIDAS PROFISSIONAIS -->
        <section class="secao" id="saidas">
            <div class="container">
                <h2 class="titulo-secao">Saidas Profissionais</h2>
                <p class="subtitulo-secao">Conheca as oportunidades que o curso oferece no mercado de trabalho.</p>

                <div class="grade-saidas" id="gradeSaidas">
                    <?php if(count($saidas) > 0): ?>
                        <?php foreach($saidas as $saida): ?>
                        <div class="cartao-saida">
                            <div class="imagem-saida">
                                <?php if(!empty($saida['imagem_url'])): ?>
                                    <img src="<?= htmlspecialchars(normalizarUrlMidia($saida['imagem_url'], '..')) ?>" alt="<?= htmlspecialchars($saida['titulo']) ?>">
                                <?php else: ?>
                                    <div class="imagem-saida-fallback">
                                        <i class="fas fa-briefcase"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="cabecalho-saida">
                                <h3 class="titulo-saida"><?= htmlspecialchars($saida['titulo']) ?></h3>
                            </div>
                            <div class="corpo-saida">
                                <p class="descricao-saida"><?= htmlspecialchars($saida['descricao']) ?></p>
                                <div class="competencias-saida">
                                    <?php foreach($saida['competencias'] as $competencia): ?>
                                    <span class="etiqueta-competencia"><?= htmlspecialchars($competencia) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="cartao-aviso-vazio">
                            <i class="fas fa-briefcase"></i>
                            <p>As saídas profissionais serão divulgadas brevemente.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- PROJECTOS REALIZADOS -->
        <section class="secao secao-projectos" id="projectos">
            <div class="container">
                <h2 class="titulo-secao">Projectos Realizados</h2>
                <p class="subtitulo-secao">Conheca alguns dos projectos desenvolvidos pelos nossos alunos.</p>

                <div class="grade-projectos">
                    <?php if(count($projetos) > 0): ?>
                        <?php foreach($projetos as $projeto): ?>
                        <div class="cartao-projecto">
                            <?php if(!empty($projeto['imagem_url'])): ?>
                                <img src="<?= htmlspecialchars(normalizarUrlMidia($projeto['imagem_url'], '..')) ?>" alt="<?= htmlspecialchars($projeto['titulo']) ?>" class="imagem-projecto">
                            <?php else: ?>
                                <div class="imagem-projecto-fallback">
                                    <i class="fas fa-project-diagram"></i>
                                </div>
                            <?php endif; ?>
                            <div class="conteudo-projecto">
                                <span class="categoria-projecto"><?= htmlspecialchars($projeto['categoria'] ?? 'Projecto') ?></span>
                                <h3 class="titulo-projecto"><?= htmlspecialchars($projeto['titulo']) ?></h3>
                                <p class="descricao-projecto"><?= htmlspecialchars($projeto['descricao']) ?></p>
                                <div class="metadados-projecto">
                                    <div class="ano-projecto"><i class="far fa-calendar"></i><span><?= $projeto['ano'] ?? date('Y') ?></span></div>
                                    <div class="autor-projecto"><i class="fas fa-user-graduate"></i><span><?= htmlspecialchars($projeto['autor'] ?? 'Aluno IPIKK') ?></span></div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="cartao-aviso-vazio">
                            <i class="fas fa-project-diagram"></i>
                            <p>Os projectos serão divulgados brevemente.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- DEPOIMENTOS -->
        <section class="secao-depoimentos" id="depoimentos">
            <div class="cabecalho-secao">
                <h2 class="titulo-secao">Depoimentos de Alumni</h2>
                <p class="subtitulo">O que dizem os nossos antigos estudantes sobre a sua experiencia no <strong>IPIKK</strong></p>
            </div>

            <?php if(count($depoimentos) > 0): ?>
            <div class="area-slider">
                <div class="card-depoimento" id="cartao-principal">
                    <div class="barra-lateral"></div>
                    <div class="conteudo-cartao">
                        <div class="cabecalho-cartao">
                            <div class="bloco-perfil">
                                <div class="avatar-wrapper" id="avatar-wrapper">
                                    <div class="avatar-fallback"><i class="far fa-user-circle"></i></div>
                                </div>
                                <div class="info-perfil">
                                    <h3 class="nome-alumni" id="nome-alumni"></h3>
                                    <p class="curso-alumni" id="curso-alumni"></p>
                                    <p id="tipo-depoente-curso" style="margin:4px 0 0;"></p>
                                    <p class="empresa-alumni"><i class="far fa-building"></i><span id="empresa-alumni"></span></p>
                                </div>
                            </div>
                        </div>
                        <div class="bloco-texto">
                            <div class="aspas-decorativas"><i class="fas fa-quote-right"></i></div>
                            <p class="texto-depoimento" id="texto-depoimento"></p>
                        </div>
                    </div>
                </div>

                <div class="navegacao-slider">
                    <button class="botao-seta" id="botao-anterior" aria-label="Depoimento anterior">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
                    </button>
                    <div class="pontos-indicadores" id="pontos-indicadores"></div>
                    <button class="botao-seta" id="botao-proximo" aria-label="Proximo depoimento">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
                    </button>
                </div>
            </div>
            <?php else: ?>
            <div class="container" style="max-width: 900px;">
                <div class="cartao-aviso-vazio">
                    <i class="fas fa-comments"></i>
                    <p>Os depoimentos serão divulgados brevemente.</p>
                </div>
            </div>
            <?php endif; ?>
        </section>
    </main>

    <!-- BOTOES FLUTUANTES -->
    <div class="botoes-flutuantes">
        <button class="botao-flutuante" id="botaoTopo" title="Voltar ao topo"><i class="fas fa-chevron-up"></i></button>
        <?php if($config['whatsapp_numero']): ?>
        <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $config['whatsapp_numero']) ?>" class="botao-flutuante whatsapp" target="_blank" rel="noopener" title="WhatsApp"><i class="fab fa-whatsapp"></i></a>
        <?php endif; ?>
    </div>

    <!-- RODAPE -->

<?php include __DIR__ . '/includes/footer.php'; ?>

    <script src="js/header-footer.js"></script>
    <script>
        // Dados dos PDFs (incluindo classe 0 para o Resumo Geral)
        const dadosPdf = {
            <?php for($classe = 10; $classe <= 13; $classe++): 
                $pdf = $pdfs[$classe] ?? null;
                $pdf_url = $pdf && $pdf['pdf_url'] ? normalizarUrlMidia($pdf['pdf_url'], '..') : '#';
            ?>
            <?= $classe ?>: {
                titulo: 'Plano Curricular — <?= $classe ?>ª Classe',
                descricao: '<?= addslashes($curso['nome']) ?> · <?= $classe ?>ª Classe',
                url: '<?= $pdf_url ?>',
                disponivel: <?= $pdf && $pdf['pdf_url'] ? 'true' : 'false' ?>
            }<?= $classe < 13 ? ',' : '' ?>
            <?php endfor; ?>,
            0: {
                titulo: 'Plano Curricular - Resumo Geral',
                descricao: '<?= addslashes($curso['nome']) ?> · Visão Geral de Todas as Classes',
                url: '<?= !empty($pdfs[0]['pdf_url']) ? normalizarUrlMidia($pdfs[0]['pdf_url'], '..') : '#' ?>',
                disponivel: <?= !empty($pdfs[0]['pdf_url']) ? 'true' : 'false' ?>
            }
        };

        let classeActiva = '10';

        function atualizarPdf(classe) {
            const dados = dadosPdf[classe];
            const container = document.getElementById('conteudoPlano');
            if (!container || !dados) return;
            
            container.style.opacity = '0';
            setTimeout(() => {
                if (dados.disponivel && dados.url !== '#') {
                    container.innerHTML = `
                        <div class="bloco-documento-pdf">
                            <div class="info-pdf">
                                <div class="icone-pdf"><i class="fas fa-file-pdf"></i></div>
                                <div class="textos-pdf">
                                    <span class="rotulo-pdf">Documento Oficial</span>
                                    <span class="titulo-pdf">${dados.titulo}</span>
                                    <span class="descricao-pdf">${dados.descricao}</span>
                                </div>
                            </div>
                            <a href="${dados.url}" class="botao-ver-documento" target="_blank">
                                <i class="fas fa-eye"></i> Ver Documento
                            </a>
                        </div>
                    `;
                } else {
                    container.innerHTML = `
                        <div class="bloco-documento-pdf">
                            <div class="info-pdf">
                                <div class="icone-pdf"><i class="fas fa-file-pdf"></i></div>
                                <div class="textos-pdf">
                                    <span class="rotulo-pdf">Documento Oficial</span>
                                    <span class="titulo-pdf">${dados.titulo}</span>
                                    <span class="descricao-pdf">${dados.descricao}</span>
                                </div>
                            </div>
                            <span class="botao-ver-documento disabled" style="background: #adb5bd; cursor: default;">
                                <i class="fas fa-file-pdf"></i> PDF não disponível
                            </span>
                        </div>
                    `;
                }
                container.style.opacity = '1';
            }, 180);
        }

        function inicializarAbasPlano() {
            const botoes = document.querySelectorAll('.botao-aba');
            botoes.forEach(btn => {
                btn.addEventListener('click', function() {
                    const classe = this.dataset.classe;
                    if (classe === classeActiva) return;
                    botoes.forEach(b => b.classList.remove('ativo'));
                    this.classList.add('ativo');
                    classeActiva = classe;
                    atualizarPdf(classe);
                });
            });
        }

        // Dados dos depoimentos
        const depoimentosData = <?= json_encode($depoimentos) ?>;

        function inicializarDepoimentos() {
            if (!depoimentosData.length) return;
            
            const nomeEl = document.getElementById('nome-alumni');
            const cursoEl = document.getElementById('curso-alumni');
            const empresaEl = document.getElementById('empresa-alumni');
            const textoEl = document.getElementById('texto-depoimento');
            const pontosContainer = document.getElementById('pontos-indicadores');
            const avatarWrapper = document.getElementById('avatar-wrapper');
            
            if (!nomeEl || !pontosContainer) return;
            
            let indiceAtual = 0, intervalo;
            
            function obterFotoUrl(depoimento) {
                let fotoUrl = 'foto/sem_foto.png';
                if (depoimento.foto_url && depoimento.foto_url !== 'foto/sem_foto.png') {
                    if (depoimento.foto_url.startsWith('uploads/')) {
                        fotoUrl = (depoimento.foto_url.startsWith('http') ? depoimento.foto_url : '../area-publica/' + depoimento.foto_url);
                    } 
                    else if (depoimento.foto_url.startsWith('../area-publica/')) {
                        fotoUrl = depoimento.foto_url;
                    }
                    else {
                        fotoUrl = (depoimento.foto_url.startsWith('http') ? depoimento.foto_url : '../area-publica/' + depoimento.foto_url);
                    }
                }
                return fotoUrl;
            }
            
            function mostrarDepoimento(i) {
                const dep = depoimentosData[i];
                nomeEl.textContent = dep.nome;
                const infoTurma = (dep.tipo_depoimento === 'atual')
                    ? `Ano ${dep.ano_atual || '-'} • Turma ${dep.turma || '-'}`
                    : `Turma ${dep.turma || '-'}`;
                cursoEl.textContent = dep.curso_nome + ' (' + infoTurma + ')';
                empresaEl.textContent = dep.empresa;
                const tipoEl = document.getElementById('tipo-depoente-curso');
                if (tipoEl) {
                    const atual = (dep.tipo_depoimento || 'ex_aluno') === 'atual';
                    tipoEl.innerHTML = `<span style="display:inline-block;padding:4px 10px;border-radius:999px;font-size:.75rem;font-weight:700;background:${atual ? '#dcfce7' : '#e0f2fe'};color:${atual ? '#15803d' : '#0369a1'};">${atual ? 'Aluno Atual' : 'Ex-Aluno'}</span>`;
                }
                textoEl.textContent = dep.texto;
                
                if (avatarWrapper) {
                    const fotoUrl = obterFotoUrl(dep);
                    if (fotoUrl && fotoUrl !== 'foto/sem_foto.png') {
                        avatarWrapper.innerHTML = `<img src="${fotoUrl}" alt="${dep.nome}" onerror="this.parentElement.innerHTML='<div class=avatar-fallback><i class=far fa-user-circle></i></div>'">`;
                    } else {
                        avatarWrapper.innerHTML = '<div class="avatar-fallback"><i class="far fa-user-circle"></i></div>';
                    }
                }
                
                document.querySelectorAll('.ponto').forEach((p, j) => {
                    p.classList.toggle('ativo', j === i);
                });
            }
            
            function iniciarLoop() {
                if (intervalo) clearInterval(intervalo);
                intervalo = setInterval(() => {
                    indiceAtual = (indiceAtual + 1) % depoimentosData.length;
                    mostrarDepoimento(indiceAtual);
                }, 5000);
            }
            
            pontosContainer.innerHTML = '';
            depoimentosData.forEach((_, i) => {
                const p = document.createElement('span');
                p.className = 'ponto' + (i === 0 ? ' ativo' : '');
                p.addEventListener('click', () => {
                    indiceAtual = i;
                    mostrarDepoimento(i);
                    clearInterval(intervalo);
                    iniciarLoop();
                });
                pontosContainer.appendChild(p);
            });
            
            document.getElementById('botao-anterior')?.addEventListener('click', () => {
                indiceAtual = (indiceAtual - 1 + depoimentosData.length) % depoimentosData.length;
                mostrarDepoimento(indiceAtual);
                clearInterval(intervalo);
                iniciarLoop();
            });
            
            document.getElementById('botao-proximo')?.addEventListener('click', () => {
                indiceAtual = (indiceAtual + 1) % depoimentosData.length;
                mostrarDepoimento(indiceAtual);
                clearInterval(intervalo);
                iniciarLoop();
            });
            
            mostrarDepoimento(0);
            iniciarLoop();
        }

        document.addEventListener('DOMContentLoaded', function() {
            if (document.getElementById('conteudoPlano')) {
                atualizarPdf(10);
                inicializarAbasPlano();
            }
            if (document.getElementById('nome-alumni')) inicializarDepoimentos();
        });
    </script>
</body>
</html>
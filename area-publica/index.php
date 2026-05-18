<?php
/**
 * Página Inicial - IPIKK
 */

require_once '../config/index.php';
// Buscar configurações do site
$config = getDB()->query("SELECT * FROM configuracoes WHERE id = 1")->fetch();

// Buscar conteúdo da página inicial (JSON da tabela conteudo_paginas)
$pagina_inicial = getPagina('inicio');

// Buscar todas as áreas de formação (para os cards "Cursos Ministrados")
$areas = getDB()->query("
    SELECT * FROM areas 
    WHERE ativo = 1 
    ORDER BY ordem
")->fetchAll();

// ============================================
// MENSAGEM DO DIRECTOR (vinda do JSON)
// ============================================
// Verifica se existe e se tem dados minimamente preenchidos
$tem_mensagem_valida = isset($pagina_inicial['mensagem_director']) 
    && is_array($pagina_inicial['mensagem_director'])
    && !empty($pagina_inicial['mensagem_director']['nome']);  // ← CRUCIAL: verifica se tem nome

if ($tem_mensagem_valida) {
    $mensagem_director = $pagina_inicial['mensagem_director'];
} else {
    // Fallback com dados reais
    $mensagem_director = [
        'foto' => 'foto/perfil-do-director.jpg',
        'nome' => 'Ferreira Manuel Fragoso',
        'cargo' => 'Director do IPIKK',
        'mensagem' => 'Bem-vindos ao Instituto Médio Politécnico Industrial do Kilamba Kiaxi. A nossa missão é formar profissionais qualificados e cidadãos preparados para os desafios do mercado de trabalho.',
        'assinatura' => 'Ferreira Manuel Fragoso'
    ];
}

// ============================================
// DADOS DO SLIDER (vindos do JSON)
// ============================================
$tem_slider_valido = isset($pagina_inicial['slider']) 
    && is_array($pagina_inicial['slider']) 
    && count($pagina_inicial['slider']) > 0
    && !empty($pagina_inicial['slider'][0]['titulo']);  // Verifica se o primeiro slide tem título

if ($tem_slider_valido) {
    $slider = $pagina_inicial['slider'];
} else {
    $slider = [
        ['titulo' => 'Bem-vindo ao IPIKK', 'subtitulo' => '', 'botao' => 'Saiba mais', 'link' => '#', 'imagem' => 'https://via.placeholder.com/1600x900/2c3e50/ffffff?text=IPIKK'],
        ['titulo' => 'Formando profissionais técnicos', 'subtitulo' => '', 'botao' => 'Saiba mais', 'link' => '#', 'imagem' => 'https://via.placeholder.com/1600x900/34495e/ffffff?text=Formação'],
        ['titulo' => 'Educação moderna para o futuro', 'subtitulo' => '', 'botao' => 'Saiba mais', 'link' => '#', 'imagem' => 'https://via.placeholder.com/1600x900/5d6d7e/ffffff?text=Educação']
    ];
}

// ============================================
// DADOS DA MATRÍCULA (vindos do JSON)
// ============================================
$tem_matricula_valida = isset($pagina_inicial['matricula']) 
    && is_array($pagina_inicial['matricula'])
    && !empty($pagina_inicial['matricula']['titulo']);

if ($tem_matricula_valida) {
    $matricula = $pagina_inicial['matricula'];
} else {
    $matricula = [
        'titulo' => 'Faça a sua matrícula no IPIKK',
        'descricao' => 'Junte-se a nós, usufrua do que temos a oferecer para a sua capacitação profissional e desenvolvimento técnico. Transforme o seu futuro com educação de qualidade.',
        'imagem' => 'foto/matricula.jpg'
    ];
}

// ============================================
// PARCEIROS (vindos do JSON)
// ============================================
$tem_parceiros_validos = isset($pagina_inicial['parceiros']) 
    && is_array($pagina_inicial['parceiros']) 
    && count($pagina_inicial['parceiros']) > 0;

if ($tem_parceiros_validos) {
    $parceiros = array_values(array_filter($pagina_inicial['parceiros'], function($parceiro) {
        return !empty(trim($parceiro['logo'] ?? $parceiro['logo_url'] ?? ''));
    }));
} else {
    // Fallback da tabela escolas_afiliadas: exibir apenas parceiros com logo registado.
    $parceiros = getDB()->query("
        SELECT nome, logo_url as logo, site_url as link
        FROM escolas_afiliadas
        WHERE ativo = 1
          AND logo_url IS NOT NULL
          AND TRIM(logo_url) <> ''
        ORDER BY ordem
        LIMIT 10
    ")->fetchAll();
}

// ============================================
// DEPOIMENTOS EM DESTAQUE (da tabela depoimentos)
// ============================================
 $sem_areas = empty($areas);

$depoimentos = getDB()->query("
    SELECT d.*, c.nome as curso_nome 
    FROM depoimentos d
    JOIN cursos c ON d.curso_id = c.id
    WHERE d.destaque = 1 AND d.ativo = 1
    ORDER BY d.ordem
    LIMIT 4
")->fetchAll();
$tipos_depoimentos = array_unique(array_map(fn($d) => $d['tipo_depoimento'] ?? 'ex_aluno', $depoimentos));
$titulo_depoimentos = 'O que dizem os nossos alunos';
if (count($tipos_depoimentos) === 1) {
    $titulo_depoimentos = $tipos_depoimentos[0] === 'atual' ? 'Depoimentos de Alunos Atuais' : 'Depoimentos de Ex-Alunos';
}

// ============================================
// NOTÍCIAS EM DESTAQUE (da tabela noticias)
// ============================================
$noticias = getDB()->query("
    SELECT * FROM noticias 
    WHERE estado = 'publicada' 
    ORDER BY data_publicacao DESC, created_at DESC 
    LIMIT 3
")->fetchAll();

// ============================================
// CURSOS EM DESTAQUE (vindos do JSON)
// ============================================
$cursos_destaque = isset($pagina_inicial['cursos_destaque']) && is_array($pagina_inicial['cursos_destaque'])
    ? $pagina_inicial['cursos_destaque']
    : [];

// Verificar status das inscrições para o botão de matrícula
$status_inscricoes = getDB()->query("SELECT status FROM controle_inscricoes WHERE id = 1")->fetch();
$link_inscricao = ($status_inscricoes && $status_inscricoes['status'] === 'abertas') ? 'inscricoes.php' : 'inscricoes-indisponiveis.php';
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= htmlspecialchars($config['seo_descricao'] ?? 'Bem-vindo ao site oficial do Instituto Politécnico Industrial do Kilamba Kiaxi Nº 8050 "Nova Vida" (IPIKK-NV).') ?>">
    <meta name="author" content="Equipa de Desenvolvimento Web, Curso de Gestão de Sistemas Informáticos, IPIKK-NV">
    <meta name="keywords" content="<?= htmlspecialchars($config['seo_keywords'] ?? 'IPIKK, IPIKK-NV, Instituto Politécnico Industrial do Kilamba Kiaxi') ?>">

    <title>IPIKK - Inicio</title>
    
    <!-- Fontes e Ícones -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Montserrat:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <!-- Favicon -->
    <link href="<?= $config['favicon_url'] ?? 'foto/ipikk_new_logo.png' ?>" rel="icon">
    
    <!-- CSS Header/Footer Padrão -->
    <link rel="stylesheet" href="css/header-footer.css">
    
    <style>
        /* ========== VARIÁVEIS CSS ============= */
        :root {
            --azul-principal:  #003072;
            --azul-claro:      #2e86c1;
            --azul-escuro:     #001a40;
            --verde-acento:    #0a9396;
            --verde-claro:     #94d2bd;
            --branco:          #ffffff;
            --cinza-claro:     #f8f9fa;
            --cinza:           #6c757d;
            --cinza-escuro:    #212529;
            --amarelo:         #ffc107;
            --sombra:          0 10px 30px rgba(0, 48, 114, 0.1);
            --borda-raio:      12px;
            --transicao:       all 0.3s ease;
            /* Cores dos cursos */
            --cor-construcao:  #9FA3A7;
            --cor-eletricidade:#3A7BC0;
            --cor-mecanica:    #E67E22;
            --cor-informatica: #1F7A4D;
            --cor-moveis:      #e01a1a;
        }
        
        /* =============== SLIDER ========================= */
        .container-slider {
            position: relative; height: 100vh; min-height: 600px; overflow: hidden; z-index: 1;
        }
        .slider { height: 100%; position: relative; }
        .slide {
            position: absolute; inset: 0;
            opacity: 0; transform: scale(1.1);
            transition: opacity 1s ease, transform 1s ease;
            background-size: cover; background-position: center; z-index: 1;
        }
        .slide.ativo { opacity: 1; transform: scale(1); z-index: 2; }
        .gradiente {
            position: absolute; inset: 0;
            background: linear-gradient(90deg, rgba(0,48,114,.8), rgba(10,147,150,.6)); z-index: 2;
        }
        .conteudo-texto {
            position: absolute; top: 50%; left: 10%;
            transform: translateY(-50%); color: white; z-index: 3; max-width: 700px;
        }
        .conteudo-texto h1 { font-size: 3.5rem; margin-bottom: 20px; line-height: 1.2; }
        .conteudo-texto h1 span { color: var(--verde-claro); border-right: 3px solid var(--verde-claro); display: inline-block; }
        .botao {
            display: inline-flex; align-items: center; gap: 10px;
            padding: 14px 35px; background: var(--verde-acento);
            border: none; border-radius: 50px; color: white;
            font-size: 1rem; font-weight: 600; cursor: pointer; transition: var(--transicao);
        }
        .botao:hover { background: var(--azul-principal); gap: 15px; transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,.2); }
        .controles {
            position: absolute; bottom: 30px; left: 50%; transform: translateX(-50%);
            z-index: 3; display: flex; gap: 10px;
        }
        .ponto {
            width: 14px; height: 14px; background: white; opacity: .4;
            border-radius: 50%; cursor: pointer; transition: var(--transicao);
        }
        .ponto.ativo { opacity: 1; transform: scale(1.2); background: var(--verde-claro); }

        /* ================ SEÇÃO CURSOS ==================== */
        .secao-cursos {
            max-width: 1200px; margin: 80px auto; padding: 40px;
            background: var(--branco); border-radius: var(--borda-raio);
            box-shadow: var(--sombra);
        }
        .titulo-secao { text-align: center; font-size: 2.5rem; color: var(--azul-principal); margin-bottom: 12px; }
        .linha-decorativa {
            width: 120px; height: 5px;
            background: linear-gradient(to right, var(--azul-principal), var(--verde-acento));
            margin: 0 auto 60px; border-radius: 50px;
        }
        .grid-cursos {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
            justify-content: flex-start;
        }
        .card-curso {
            position: relative; background: white; border-radius: 18px;
            box-shadow: 0 15px 35px rgba(0,0,0,.08); overflow: hidden;
            width: calc(50% - 15px);
            min-width: 400px;
            transition: var(--transicao);
            flex: 0 0 auto;
        }
        @media (max-width: 900px) {
            .card-curso { min-width: 300px; }
        }
        @media (max-width: 700px) {
            .card-curso { width: 100%; min-width: auto; }
        }
        .card-curso:hover { transform: translateY(-10px); box-shadow: 0 20px 40px rgba(0,48,114,.15); }
        .barra-lateral {
            position: absolute; left: 0; top: 0; width: 8px; height: 100%; transition: var(--transicao);
        }
        .card-curso:hover .barra-lateral { width: 12px; }
        .card-curso .barra-lateral { background: var(--area-cor, #6c757d); }
        .conteudo-card { display: flex; gap: 25px; padding: 35px; }
        .icone {
            width: 70px; height: 70px; background: var(--cinza-claro);
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            font-size: 28px; color: var(--cinza); flex-shrink: 0; transition: var(--transicao);
        }
        .texto-card h3 { font-size: 1.5rem; color: var(--azul-principal); margin-bottom: 10px; }
        .texto-card p  { color: var(--cinza); line-height: 1.6; margin-bottom: 18px; }
        .texto-card .botao {
            display: inline-flex; align-items: center; gap: 8px; padding: 10px 22px;
            background: var(--cinza-claro); border-radius: 50px;
            color: var(--azul-principal); font-weight: 600; font-size: .9rem;
        }
        .texto-card .botao:hover { gap: 12px; }
        .card-curso:hover .icone { background: var(--area-cor, #6c757d); color: var(--branco); }
        .texto-card .botao:hover { background: var(--area-cor, #6c757d); color: white; }

        /* ================= SEÇÃO MENSAGEM DO DIRECTOR ==================== */
        .secao-mensagem { padding: 80px 20px; background: rgba(0,48,114,.03); position: relative; z-index: 10; }
        .container-mensagem {
            max-width: 1100px; margin: auto; display: flex; flex-wrap: wrap;
            border-radius: 18px; overflow: hidden;
            box-shadow: 0 20px 45px rgba(0,0,0,.12); background: white;
        }
        .coluna-esquerda {
            background: linear-gradient(to bottom, var(--azul-principal), var(--azul-escuro));
            color: white; display: flex; flex-direction: column;
            justify-content: center; align-items: center;
            padding: 50px 25px; text-align: center; width: 35%; min-width: 300px;
        }
        .foto-director {
            width: 200px; height: 200px; border-radius: 50%;
            padding: 6px; background: rgba(255,255,255,.3); margin-bottom: 25px;
            overflow: hidden;
        }
        .foto-director img { 
            width: 100%; height: 100%; border-radius: 50%; 
            object-fit: cover; transition: var(--transicao);
        }
        .foto-director:hover img { transform: scale(1.05); }
        .nome-director { font-size: 1.6rem; margin-bottom: 6px; }
        .cargo-director { font-size: 1rem; opacity: .85; }
        .coluna-direita { padding: 50px; flex: 1; min-width: 300px; }
        .topo-texto { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .topo-texto h2 { font-size: 2.3rem; color: var(--azul-principal); }
        .icone-aspas { font-size: 3rem; color: var(--verde-acento); opacity: .9; }
        .conteudo-texto-director { max-width: 700px; }
        .conteudo-texto-director p { color: var(--cinza); line-height: 1.8; margin-bottom: 20px; }
        .assinatura { margin-top: 40px; }
        .linha-assinatura { width: 200px; height: 1px; background: #aaa; margin-bottom: 10px; }
        .assinatura strong { color: var(--azul-principal); font-size: 1.1rem; }

        /* ======================= SEÇÃO DEPOIMENTOS ============================== */
        .secao-depoimentos { padding: 80px 20px; background: var(--branco); position: relative; z-index: 10; }
        .cabecalho-secao  { text-align: center; margin-bottom: 50px; }
        .subtitulo { font-size: 1.2rem; color: var(--cinza); max-width: 700px; margin: 0 auto; }
        .area-slider { max-width: 900px; margin: 0 auto; }
        .card-depoimento {
            position: relative; background: white; border-radius: 18px;
            box-shadow: 0 15px 35px rgba(0,0,0,.08); overflow: hidden;
            margin-bottom: 30px; border: 1px solid rgba(0,0,0,.05);
        }
        .card-depoimento .barra-lateral {
            position: absolute; left: 0; top: 0; width: 5px; height: 100%;
            background: linear-gradient(to bottom, var(--azul-principal), var(--verde-acento));
        }
        .conteudo-cartao { padding: 30px; }
        .cabecalho-cartao { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 25px; flex-wrap: wrap; gap: 20px; }
        .bloco-perfil { display: flex; align-items: center; gap: 20px; }
        .avatar-wrapper {
            width: 70px; height: 70px; border-radius: 50%; overflow: hidden;
            border: 3px solid var(--cinza-claro);
            background: #e0e0e0;
            display:flex; align-items:center; justify-content:center;
        }
        .avatar-fallback { color:#7a8796; font-size: 2rem; line-height:1; display:flex; align-items:center; justify-content:center; width:100%; height:100%; }
        .avatar-wrapper img {
            width: 100%; height: 100%; object-fit: cover;
        }
        .info-perfil h3 { font-size: 1.3rem; color: var(--azul-principal); margin-bottom: 5px; }
        .curso-alumni { font-size: .9rem; color: var(--cinza); margin-bottom: 8px; }
        .meta-depoente { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
        .empresa-alumni { font-size: .9rem; color: var(--verde-acento); display: flex; align-items: center; gap: 6px; margin:0; }
        .bloco-estrelas { display: flex; gap: 5px; color: #ff9e00; }
        .bloco-texto { position: relative; padding-left: 40px; }
        .aspas-decorativas { position: absolute; left: 0; top: 0; font-size: 1.8rem; color: var(--verde-claro); opacity: .7; }
        .texto-depoimento { font-size: 1.05rem; color: var(--cinza-escuro); font-style: italic; line-height: 1.7; }
        .navegacao-slider { display: flex; justify-content: center; align-items: center; gap: 30px; margin-top: 30px; }
        .botao-seta {
            width: 50px; height: 50px; border-radius: 50%; background: var(--branco);
            border: 2px solid var(--cinza-claro); color: var(--azul-principal);
            cursor: pointer; display: flex; align-items: center; justify-content: center; transition: var(--transicao);
        }
        .botao-seta:hover { background: var(--azul-principal); color: var(--branco); border-color: var(--azul-principal); }
        .botao-seta svg { width: 20px; height: 20px; }
        .pontos-indicadores { display: flex; gap: 15px; }
        .ponto-indicador { width: 12px; height: 12px; border-radius: 50%; background: var(--cinza-claro); cursor: pointer; transition: var(--transicao); }
        .ponto-indicador.ativo { background: var(--azul-principal); transform: scale(1.2); }
        .ponto-indicador:hover { background: var(--verde-acento); }

        /* =================== SEÇÃO NOTÍCIAS ========================= */
        .secao-noticias { padding: 80px 20px; background: rgba(0,48,114,.03); position: relative; z-index: 10; }
        .cabecalho-noticias { text-align: center; margin-bottom: 50px; }
        .titulo-noticias { font-size: 2.5rem; color: var(--azul-principal); margin-bottom: 12px; }
        .linha-titulo {
            width: 120px; height: 5px;
            background: linear-gradient(to right, var(--azul-principal), var(--verde-acento));
            margin: 0 auto; border-radius: 50px;
        }
        .grade-noticias {
            max-width: 1200px; margin: 0 auto;
            display: flex; flex-wrap: wrap; gap: 30px; justify-content: center;
        }
        .cartao-noticia {
            background: white; border-radius: 18px; overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,.08);
            width: calc(33.333% - 20px); min-width: 300px; transition: var(--transicao);
        }
        .cartao-noticia:hover { transform: translateY(-10px); box-shadow: 0 20px 40px rgba(0,48,114,.15); }
        .cartao-imagem {
            height: 200px; overflow: hidden; position: relative;
            background: linear-gradient(135deg, var(--azul-principal), var(--azul-escuro));
        }
        .cartao-imagem img {
            width: 100%; height: 100%; object-fit: cover;
            transition: transform .5s ease;
        }
        .cartao-noticia:hover .cartao-imagem img { transform: scale(1.08); }
        .cartao-conteudo { padding: 25px; }
        .badge-data {
            display: inline-block; padding: 5px 15px; background: var(--verde-acento);
            color: white; border-radius: 20px; font-size: .8rem; margin-bottom: 15px;
        }
        .badge-data i { margin-right: 5px; }
        .cartao-titulo { font-size: 1.3rem; color: var(--azul-principal); margin-bottom: 10px; }
        .cartao-descricao { color: var(--cinza); margin-bottom: 20px; line-height: 1.6; }
        .link-ler-mais {
            display: inline-flex; align-items: center; gap: 8px;
            color: var(--azul-principal); font-weight: 600; cursor: pointer;
            background: none; border: none; font-family: inherit; font-size: inherit;
            transition: var(--transicao);
        }
        .link-ler-mais:hover { gap: 12px; color: var(--verde-acento); }
        .area-botao { text-align: center; margin-top: 50px; }
        .botao-ver-mais {
            display: inline-flex; align-items: center; gap: 10px; padding: 15px 40px;
            background: var(--azul-principal); color: white; border-radius: 50px;
            font-weight: 600; transition: var(--transicao);
        }
        .botao-ver-mais:hover { background: var(--verde-acento); gap: 15px; transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,48,114,.2); }

        /* ================== MODAL DE NOTÍCIA ========================= */
        .modal-fundo {
            display: none;
            position: fixed; inset: 0;
            background: rgba(0,0,0,.9);
            backdrop-filter: blur(8px);
            z-index: 99999;
            align-items: center; justify-content: center;
            padding: 20px;
            animation: fadeInFundo .3s ease;
        }
        .modal-fundo.visivel { display: flex; }
        @keyframes fadeInFundo {
            from { opacity: 0; }
            to   { opacity: 1; }
        }
        .modal-conteudo {
            background: var(--branco);
            border-radius: 18px;
            max-width: 950px; width: 100%;
            max-height: 92vh; overflow-y: auto;
            position: relative;
            box-shadow: 0 25px 60px rgba(0,0,0,.5);
            animation: modalSobir .4s cubic-bezier(.68,-.55,.265,1.55);
        }
        @keyframes modalSobir {
            from { opacity: 0; transform: translateY(60px) scale(.9); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }
        .modal-botao-fechar {
            position: absolute; top: 18px; right: 18px;
            width: 42px; height: 42px; border-radius: 50%;
            background: rgba(0,0,0,.65); color: var(--branco);
            border: none; cursor: pointer; font-size: 1.4rem;
            display: flex; align-items: center; justify-content: center;
            z-index: 10; transition: var(--transicao);
        }
        .modal-botao-fechar:hover { background: #dc3545; transform: rotate(90deg) scale(1.1); }
        .modal-midia {
            width: 100%; height: 460px;
            background: #000;
            border-radius: 18px 18px 0 0;
            overflow: hidden; position: relative;
        }
        .modal-midia img,
        .modal-midia video {
            width: 100%; height: 100%; object-fit: cover;
        }
        .modal-midia-overlay {
            position: absolute; bottom: 0; left: 0; right: 0;
            height: 50%;
            background: linear-gradient(to top, rgba(0,0,0,.7), transparent);
            pointer-events: none;
        }
        .modal-badge-midia {
            position: absolute; top: 20px; left: 20px;
            background: var(--amarelo); color: var(--cinza-escuro);
            padding: 7px 18px; border-radius: 25px;
            font-size: .78rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;
            display: flex; align-items: center; gap: 6px;
        }
        .modal-corpo { padding: 35px; }
        .modal-titulo {
            font-size: 2rem; color: var(--azul-principal);
            margin-bottom: 18px; line-height: 1.3;
        }
        .modal-legenda {
            font-size: .85rem; color: var(--cinza); font-style: italic;
            padding: 10px 16px;
            background: var(--cinza-claro); border-radius: 8px;
            margin-bottom: 22px;
            display: flex; align-items: center; gap: 8px;
        }
        .modal-legenda i { color: var(--verde-acento); flex-shrink: 0; }
        .modal-meta {
            display: flex; gap: 28px; flex-wrap: wrap;
            padding-bottom: 22px;
            border-bottom: 2px solid var(--cinza-claro);
            margin-bottom: 28px;
            color: var(--cinza); font-size: .9rem;
        }
        .modal-meta-item { display: flex; align-items: center; gap: 8px; }
        .modal-meta-item i { color: var(--verde-acento); font-size: 1rem; }
        .modal-descricao {
            color: var(--cinza-escuro); font-size: 1.05rem;
            line-height: 1.9; margin-bottom: 28px;
        }
        .modal-tags {
            display: flex; gap: 10px; flex-wrap: wrap;
            padding-top: 22px; border-top: 2px solid var(--cinza-claro);
        }
        .tag-item {
            background: var(--cinza-claro); color: var(--azul-principal);
            padding: 7px 16px; border-radius: 25px; font-size: .88rem;
            font-weight: 600; cursor: default; transition: var(--transicao);
            display: flex; align-items: center; gap: 5px;
        }
        .tag-item i { font-size: .75rem; }
        .tag-item:hover { background: var(--azul-principal); color: var(--branco); transform: translateY(-2px); }

            /* ===================== SEÇÃO MATRÍCULA =========================== */
            .secao-matricula { padding: 80px 20px; background: var(--branco); position: relative; z-index: 10; }
            .container-matricula {
                max-width: 1200px; margin: 0 auto;
                display: flex;
                flex-wrap: wrap;
                background: linear-gradient(135deg, var(--azul-principal), var(--azul-escuro));
                border-radius: 20px;
                overflow: hidden;
                box-shadow: 0 20px 40px rgba(0,48,114,.2);
            }

            
            .coluna-esquerda-matricula { 
                flex: 1; 
                min-width: 280px; 
                padding: 50px 40px; 
                color: white; 
                background: transparent;
            }

            .titulo-matricula { 
                font-size: 2.2rem; 
                margin-bottom: 20px; 
                font-weight: 700;
                line-height: 1.3;
            }
            .descricao-matricula { 
                font-size: 1rem; 
                margin-bottom: 30px; 
                opacity: 0.9; 
                line-height: 1.7; 
            }
            .botao-matricula {
                display: inline-flex; 
                align-items: center; 
                gap: 10px;
                padding: 14px 32px; 
                background: white;
                color: var(--azul-principal); 
                border-radius: 50px; 
                font-weight: 600; 
                transition: var(--transicao);
                text-decoration: none;
            }
            .botao-matricula:hover { 
                background: var(--verde-acento); 
                color: white; 
                gap: 15px; 
                transform: translateY(-3px); 
            }

            .coluna-direita-matricula { 
                flex: 1;
                min-width: 280px;
                position: relative;
                overflow: hidden;
                background: linear-gradient(135deg, rgba(0,0,0,0.1), rgba(0,0,0,0.2));
            }

            .coluna-direita-matricula img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                display: block;
                transition: transform 0.5s ease;
            }

            .coluna-direita-matricula:hover img {
                transform: scale(1.05);
            }

            /* Responsividade */
            @media (max-width: 768px) {
                .container-matricula {
                    flex-direction: column;
                }
                .coluna-esquerda-matricula {
                    padding: 40px 30px;
                    text-align: center;
                }
                .coluna-direita-matricula {
                    min-height: 250px;
                }
                .titulo-matricula {
                    font-size: 1.8rem;
                }
            }

            @media (max-width: 480px) {
                .coluna-esquerda-matricula {
                    padding: 30px 20px;
                }
                .titulo-matricula {
                    font-size: 1.5rem;
                }
                .descricao-matricula {
                    font-size: 0.9rem;
                }
                .botao-matricula {
                    padding: 12px 24px;
                    font-size: 0.9rem;
                }
            }
        /* ====================== SEÇÃO PARCEIROS ======================== */
        .secao-parceiros { padding: 80px 20px; background: var(--cinza-claro); overflow: hidden; position: relative; z-index: 10; }
        .titulo-parceiros { text-align: center; font-size: 2.5rem; color: var(--azul-principal); margin-bottom: 12px; }
        .linha-parceiros { width: 120px; height: 5px; background: linear-gradient(to right, var(--azul-principal), var(--verde-acento)); margin: 0 auto 60px; border-radius: 50px; }
        .area-slider-parceiros { max-width: 1200px; margin: 0 auto; overflow: hidden; -webkit-mask-image: linear-gradient(to right, transparent 0%, #000 9%, #000 91%, transparent 100%); mask-image: linear-gradient(to right, transparent 0%, #000 9%, #000 91%, transparent 100%); }
        .slider-parceiros { display: flex; width: 100%; justify-content: center; will-change: transform; transform: translate3d(0,0,0); }
        .slider-parceiros.animado { justify-content: flex-start; width: max-content; animation: fluxoParceiros var(--fluxo-parceiros-duracao, 16s) linear infinite; }
        .slider-grupo { display: flex; gap: 36px; padding-right: 0; flex-shrink: 0; }
        .slider-parceiros.animado .slider-grupo { padding-right: 36px; }
        .area-slider-parceiros:hover .slider-parceiros { animation-play-state: paused; }
        @keyframes fluxoParceiros { from { transform: translate3d(0,0,0); } to { transform: translate3d(calc(-1 * var(--fluxo-parceiros-distancia, 0px)),0,0); } }
        .card-parceiro {
            background: white; border-radius: 12px; padding: 30px;
            display: flex; align-items: center; justify-content: center;
            min-width: 200px; height: 120px;
            box-shadow: 0 10px 30px rgba(0,0,0,.08); flex-shrink: 0; transition: var(--transicao);
        }
        .card-parceiro:hover { transform: translateY(-5px); box-shadow: 0 15px 35px rgba(0,48,114,.15); }
        .card-parceiro img { max-width: 150px; max-height: 60px; filter: grayscale(100%); transition: var(--transicao); }
        .card-parceiro:hover img { filter: grayscale(0%); transform: scale(1.1); }

        /* =================== RESPONSIVIDADE ========================= */
        @media (max-width: 992px) {
            .conteudo-texto h1 { font-size: 2.5rem; }
            .titulo-secao,.titulo-noticias,.titulo-parceiros,.titulo-matricula { font-size: 2rem; }
            .topo-texto h2 { font-size: 2rem; }
            .coluna-esquerda { width: 100%; }
            .cartao-noticia { width: calc(50% - 15px); }
        }
        @media (max-width: 768px) {
            .conteudo-texto { left: 5%; right: 5%; }
            .conteudo-texto h1 { font-size: 2rem; }
            .grid-cursos { flex-direction: row; }
            .card-curso { width: 100%; min-width: auto; }
            .conteudo-card { flex-direction: column; align-items: flex-start; }
            .cartao-noticia { width: 100%; }
            .modal-midia { height: 320px; }
            .modal-titulo { font-size: 1.6rem; }
            .coluna-esquerda-matricula,.coluna-direita-matricula { padding: 40px; width: 100%; }
        }
        @media (max-width: 480px) {
            .conteudo-texto h1 { font-size: 1.8rem; }
            .modal-corpo { padding: 22px; }
        }
    </style>
</head>
<body>

<?php include __DIR__ . '/includes/header.php'; ?>

    <!-- ===== SLIDER ===== -->
    <section class="container-slider">
        <div class="slider">
            <?php foreach($slider as $index => $slide): ?>
            <div class="slide <?= $index === 0 ? 'ativo' : '' ?>" style="background-image:url('<?= htmlspecialchars($slide['imagem'] ?? 'https://via.placeholder.com/1600x900/2c3e50/ffffff?text=Slide') ?>')">
                <div class="gradiente"></div>
                <div class="conteudo-texto">
                    <h1><span id="textoDigitado<?= $index + 1 ?>"><?= htmlspecialchars($slide['titulo'] ?? 'Bem-vindo ao IPIKK') ?></span></h1>
                            <a href="<?= htmlspecialchars($slide['link'] ?? '#') ?>" class="botao" style="text-decoration: none;">
            <?= htmlspecialchars($slide['botao'] ?? 'Saiba mais') ?> <i class="fas fa-arrow-right"></i>
        </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="controles">
            <?php for($i = 0; $i < max(1, count($slider)); $i++): ?>
            <span class="ponto <?= $i === 0 ? 'ativo' : '' ?>" data-indice="<?= $i ?>"></span>
            <?php endfor; ?>
        </div>
    </section>

    <!-- ===== CURSOS MINISTRADOS (ÁREAS) ===== -->
    <section class="secao-cursos">
        <h2 class="titulo-secao">Cursos Ministrados</h2>
        <div class="linha-decorativa"></div>
        <?php if ($sem_areas): ?>
        <div style="text-align:center; background:#fff; padding:60px 20px; border-radius:14px;">
            <i class="fas fa-graduation-cap" style="font-size:2.8rem; color:var(--cinza);"></i>
            <p style="margin-top:12px; font-weight:600; color:var(--azul-principal);">Nenhum curso inserido no momento</p>
        </div>
        <?php else: ?>
        <div class="grid-cursos">
            <?php foreach($areas as $area): $cor_area_card = (!empty($area['cor_primaria']) && preg_match('/^#[0-9a-fA-F]{6}$/', $area['cor_primaria'])) ? $area['cor_primaria'] : '#6c757d'; ?>
            <article class="card-curso" style="--area-cor: <?= htmlspecialchars($cor_area_card) ?>;">
                <div class="barra-lateral"></div>
                <div class="conteudo-card">
                    <div class="icone"><i class="fas <?= htmlspecialchars($area['icone_classe'] ?? 'fa-graduation-cap') ?>"></i></div>
                    <div class="texto-card">
                        <h3><?= htmlspecialchars($area['nome']) ?></h3>
                        <p><?= htmlspecialchars($area['descricao_curta'] ?? 'Formação técnica especializada') ?></p>
                        <a href="area.php?slug=<?= $area['slug'] ?>" class="botao">Ver Detalhes <i class="fa-solid fa-arrow-right"></i></a>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </section>

    <!-- ===== MENSAGEM DO DIRECTOR ===== -->
    <section class="secao-mensagem">
        <div class="container-mensagem">
            <div class="coluna-esquerda">
                <div class="foto-director">
                    <img src="<?= htmlspecialchars($mensagem_director['foto'] ?? 'foto/perfil-do-director.jpg') ?>" alt="Director do IPIKK" onerror="this.src='foto/sem_foto.png'">
                </div>
                <h3 class="nome-director"><?= htmlspecialchars($mensagem_director['nome'] ?? 'Director') ?></h3>
                <span class="cargo-director"><?= htmlspecialchars($mensagem_director['cargo'] ?? 'Director do IPIKK') ?></span>
            </div>
            <div class="coluna-direita">
                <div class="topo-texto">
                    <h2>Mensagem do Director</h2>
                    <i class="fa-solid fa-quote-right icone-aspas"></i>
                </div>
                <div class="conteudo-texto-director">
        <?php 
        // Processar a mensagem do director (dividir em parágrafos)
        $mensagem_paragrafos = !empty($mensagem_director['mensagem']) 
            ? explode("\n\n", $mensagem_director['mensagem']) 
            : ['Bem-vindos ao IPIKK'];
        ?>
        <?php foreach($mensagem_paragrafos as $paragrafo): ?>
            <?php if(trim($paragrafo) != ''): ?>
            <p><?= nl2br(htmlspecialchars(trim($paragrafo))) ?></p>
            <?php endif; ?>
        <?php endforeach; ?>
        <div class="assinatura">
            <div class="linha-assinatura"></div>
            <strong><?= htmlspecialchars($mensagem_director['assinatura']) ?></strong>
        </div>
    </div>
            </div>
        </div>
    </section>

    <!-- ===== DEPOIMENTOS ===== -->
    <section class="secao-depoimentos" id="depoimentos">
        <div class="cabecalho-secao">
            <h2 class="titulo-secao"><?= htmlspecialchars($titulo_depoimentos) ?></h2>
            <div class="linha-decorativa"></div>
            <p class="subtitulo">O que dizem os nossos alunos atuais e antigos sobre a experiência no <strong>IPIKK</strong></p>
        </div>
        <?php if (empty($depoimentos)): ?>
        <div style="text-align:center; background:#fff; padding:60px 20px; border-radius:14px; max-width:900px; margin:0 auto;">
            <i class="fas fa-comment-slash" style="font-size:2.8rem; color:var(--cinza);"></i>
            <p style="margin-top:12px; font-weight:600; color:var(--azul-principal);">Sem depoimentos no momento</p>
        </div>
        <?php else: ?>
        <div class="area-slider">
            <div class="card-depoimento" id="cartao-principal">
                <div class="barra-lateral"></div>
                <div class="conteudo-cartao">
                    <div class="cabecalho-cartao">
                        <div class="bloco-perfil">
                            <div class="avatar-wrapper">
                                <div class="avatar-fallback"><i class="far fa-user-circle"></i></div>
                            </div>
                            <div class="info-perfil">
                                <h3 id="nome-alumni"></h3>
                <p class="curso-alumni" id="curso-alumni"></p>
                                <div class="meta-depoente">
                                    <p id="tipo-depoente" style="margin:0;"></p>
                                    <p class="empresa-alumni"><i class="far fa-building"></i><span id="empresa-alumni"></span></p>
                                </div>
                            </div>
                        </div>
                        <div class="bloco-estrelas" id="bloco-estrelas"></div>
                    </div>
                    <div class="bloco-texto">
                        <div class="aspas-decorativas"><i class="fas fa-quote-right"></i></div>
                        <p class="texto-depoimento" id="texto-depoimento"></p>
                    </div>
                </div>
            </div>
            <div class="navegacao-slider">
                <button class="botao-seta" id="botao-anterior"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg></button>
                <div class="pontos-indicadores" id="pontos-indicadores"></div>
                <button class="botao-seta" id="botao-proximo"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg></button>
            </div>
        </div>
        <?php endif; ?>
    </section>

    <!-- ===== NOTÍCIAS ===== -->
    <section class="secao-noticias" id="noticias-eventos">
        <div class="cabecalho-noticias">
            <h2 class="titulo-noticias">Notícias e Eventos</h2>
            <div class="linha-titulo"></div>
        </div>
        <?php if (empty($noticias)): ?>
        <div style="text-align:center; background:#fff; padding:60px 20px; border-radius:14px;">
            <i class="fas fa-newspaper" style="font-size:2.8rem; color:var(--cinza);"></i>
            <p style="margin-top:12px; font-weight:600; color:var(--azul-principal);">Sem notícias no momento</p>
        </div>
        <?php else: ?>
        <div class="grade-noticias" id="gradeNoticias">
            <?php foreach($noticias as $noticia): 
                if (!empty($noticia['imagem_url'])) {
                    $imagem_noticia = preg_match('/^https?:\/\//i', $noticia['imagem_url'])
                        ? $noticia['imagem_url']
                        : '../' . ltrim($noticia['imagem_url'], '/');
                } else {
                    $imagem_noticia = 'foto/ipikk_new_logo.png';
                }
            ?>
            <article class="cartao-noticia" onclick="abrirModalNoticia(<?= $noticia['id'] ?>)">
                <div class="cartao-imagem">
                    <img src="<?= $imagem_noticia ?>" alt="<?= htmlspecialchars($noticia['titulo']) ?>">
                    <?php if (($noticia['tipo_midia'] ?? '') === 'video'): ?>
                    <span style="position:absolute;top:12px;right:12px;background:rgba(0,0,0,.65);color:#fff;border-radius:20px;padding:6px 10px;font-size:.75rem;font-weight:700;">
                        <i class="fas fa-play"></i> Vídeo
                    </span>
                    <?php endif; ?>
                </div>
                <div class="cartao-conteudo">
                    <span class="badge-data"><i class="fa-regular fa-calendar-days"></i> <?= formatarData($noticia['data_publicacao']) ?></span>
                    <h3 class="cartao-titulo"><?= htmlspecialchars($noticia['titulo']) ?></h3>
                    <p class="cartao-descricao"><?= htmlspecialchars($noticia['resumo'] ?? limitarTexto($noticia['conteudo'], 100)) ?></p>
                    <button class="link-ler-mais" onclick="abrirModalNoticia(<?= $noticia['id'] ?>)">
                        Ler Mais <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <div class="area-botao">
            <a href="noticias.php" class="botao-ver-mais">
                Ver Mais Notícias <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
    </section>

    <!-- ===== MODAL DE NOTÍCIA ===== -->
    <div class="modal-fundo" id="modalNoticia" role="dialog" aria-modal="true" aria-labelledby="modalTitulo">
        <div class="modal-conteudo">
            <button class="modal-botao-fechar" id="modalBotaoFechar"><i class="fas fa-times"></i></button>
            <div class="modal-midia" id="modalMidia"></div>
            <div class="modal-corpo">
                <h2 class="modal-titulo" id="modalTitulo"></h2>
                <div class="modal-legenda" id="modalLegenda"><i class="fas fa-camera"></i><span id="modalLegendaTexto"></span></div>
                <div class="modal-meta">
                    <span class="modal-meta-item"><i class="fas fa-calendar-alt"></i><span id="modalData"></span></span>
                    <span class="modal-meta-item"><i class="fas fa-user-circle"></i><span id="modalAutor"></span></span>
                    <span class="modal-meta-item"><i class="fas fa-eye"></i><span id="modalVisualizacoes"></span></span>
                </div>
                <div class="modal-descricao" id="modalDescricao"></div>
                <div class="modal-tags" id="modalTags"></div>
            </div>
        </div>
    </div>

    <!-- ===== MATRÍCULA ===== -->
        <section class="secao-matricula">
            <div class="container-matricula">
                <div class="coluna-esquerda-matricula">
                    <h2 class="titulo-matricula"><?= htmlspecialchars($matricula['titulo'] ?? 'Faça a sua matrícula no IPIKK') ?></h2>
                    <p class="descricao-matricula"><?= htmlspecialchars($matricula['descricao'] ?? 'Junte-se a nós para a sua capacitação profissional.') ?></p>
                    <a href="<?= $link_inscricao ?>" class="botao-matricula">Inscreva-se Agora <i class="fa-solid fa-arrow-right"></i></a>
                </div>
                <div class="coluna-direita-matricula">
                    <img src="<?= htmlspecialchars($matricula['imagem'] ?? 'foto/matricula.jpg') ?>" alt="Aluno IPIKK" onerror="this.src='foto/sem_foto.png'">
                </div>
            </div>
        </section>

    <!-- ===== PARCEIROS ===== -->
    <section class="secao-parceiros">
        <h2 class="titulo-parceiros">Nossos Parceiros</h2>
        <div class="linha-parceiros"></div>
        <div class="area-slider-parceiros">
            <div class="slider-parceiros" id="sliderParceiros">
                <?php if (!empty($parceiros)): ?>
                    <div class="slider-grupo" id="grupoParceirosOriginal">
                        <?php foreach($parceiros as $parceiro): ?>
                        <?php $logoParceiro = trim($parceiro['logo'] ?? $parceiro['logo_url'] ?? ''); ?>
                        <?php if ($logoParceiro !== ''): ?>
                        <div class="card-parceiro">
                            <img src="<?= htmlspecialchars($logoParceiro) ?>" alt="<?= htmlspecialchars($parceiro['nome'] ?? 'Parceiro') ?>">
                        </div>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="text-align:center; width:100%; background:#fff; border-radius:14px; padding:40px 20px;">
                        <i class="fas fa-handshake-slash" style="font-size:2.5rem; color:var(--cinza);"></i>
                        <p style="margin-top:10px; font-weight:600; color:var(--azul-principal);">Sem parceiros no momento</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- ===== BOTÕES FLUTUANTES ===== -->
    <div class="botoes-flutuantes">
        <button class="botao-flutuante" id="botaoTopo"><i class="fas fa-chevron-up"></i></button>
        <?php if($config['whatsapp_numero']): ?>
        <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $config['whatsapp_numero']) ?>" class="botao-flutuante whatsapp" target="_blank"><i class="fab fa-whatsapp"></i></a>
        <?php endif; ?>
    </div>

<?php include __DIR__ . '/includes/footer.php'; ?>

    <script src="js/header-footer.js"></script>
    <script>
    // Dados das notícias para o modal
    const noticiasData = <?= json_encode($noticias) ?>;
    
    // Dados dos depoimentos
    const depoimentosData = <?= json_encode($depoimentos) ?>;
    
    function normalizarTagsNoticia(tagsRaw) {
        if (!tagsRaw) return [];
        let tags = [];
        if (Array.isArray(tagsRaw)) {
            tags = tagsRaw;
        } else {
            try {
                const parsed = JSON.parse(tagsRaw);
                tags = Array.isArray(parsed) ? parsed : [tagsRaw];
            } catch (e) {
                tags = String(tagsRaw).split(',');
            }
        }
        return tags
            .map(tag => String(tag)
                .replace(/\\/g, '')
                .replace(/^\[+|\]+$/g, '')
                .replace(/^"+|"+$/g, '')
                .replace(/^'+|'+$/g, '')
                .trim()
            )
            .filter(Boolean);
    }

    function normalizarUrlMidia(url, fallback = '') {
        if (!url) return fallback;
        if (/^https?:\/\//i.test(url)) return url;
        return '../' + String(url).replace(/^\/+/, '');
    }

    function abrirModalNoticia(id) {
        const noticia = noticiasData.find(n => n.id === id);
        if (!noticia) return;
        
        const modal = document.getElementById('modalNoticia');
        const midiaContainer = document.getElementById('modalMidia');
        
        if (noticia.tipo_midia === 'video') {
            const videoSrc = normalizarUrlMidia(noticia.video_file, '');
            const posterSrc = normalizarUrlMidia(noticia.imagem_url, '');
            midiaContainer.innerHTML = '<video src="' + videoSrc + '" controls autoplay ' + (posterSrc ? 'poster="' + posterSrc + '"' : '') + ' style="width:100%; max-height:500px;"></video>';
        } else {
            const imgSrc = normalizarUrlMidia(noticia.imagem_url, 'https://via.placeholder.com/800x450/003072/ffffff?text=Noticia');
            midiaContainer.innerHTML = '<img src="' + imgSrc + '" alt="' + noticia.titulo + '" style="width:100%; max-height:500px; object-fit:cover;">';
        }
        
        document.getElementById('modalTitulo').textContent = noticia.titulo;
        document.getElementById('modalLegendaTexto').textContent = noticia.alt_text || noticia.titulo;
        document.getElementById('modalData').textContent = noticia.data_publicacao;
        document.getElementById('modalAutor').textContent = noticia.autor || 'Gabinete de Comunicação';
        document.getElementById('modalVisualizacoes').textContent = (noticia.visualizacoes || 0) + ' visualizações';
        document.getElementById('modalDescricao').innerHTML = noticia.conteudo;
        
        if (noticia.tags) {
            try {
                const tags = normalizarTagsNoticia(noticia.tags);
                const tagsHtml = tags.map(tag => '<span class="tag-item"><i class="fas fa-hashtag"></i>' + tag + '</span>').join('');
                document.getElementById('modalTags').innerHTML = tagsHtml;
            } catch(e) {
                document.getElementById('modalTags').innerHTML = '';
            }
        }
        
        modal.classList.add('visivel');
        document.body.style.overflow = 'hidden';
    }
    
    function fecharModalNoticia() {
        const modal = document.getElementById('modalNoticia');
        modal.classList.remove('visivel');
        document.body.style.overflow = '';
    }
    
    document.getElementById('modalBotaoFechar')?.addEventListener('click', fecharModalNoticia);
    document.getElementById('modalNoticia')?.addEventListener('click', function(e) {
        if (e.target === this) fecharModalNoticia();
    });
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') fecharModalNoticia();
    });
    
    // Depoimentos
    if (depoimentosData.length > 0) {
        let indiceAtual = 0;
        let intervalo;
        
        function obterFotoUrlDepoimento(depoimento) {
            let fotoUrl = 'foto/sem_foto.png';
            if (depoimento.foto_url && depoimento.foto_url !== 'foto/sem_foto.png') {
                if (/^https?:\/\//i.test(depoimento.foto_url)) {
                    fotoUrl = depoimento.foto_url;
                } else if (depoimento.foto_url.startsWith('uploads/')) {
                    fotoUrl = (depoimento.foto_url.startsWith('http') ? depoimento.foto_url : '../area-publica/' + depoimento.foto_url);
                } else if (depoimento.foto_url.startsWith('../area-publica/')) {
                    fotoUrl = depoimento.foto_url;
                } else {
                    fotoUrl = (depoimento.foto_url.startsWith('http') ? depoimento.foto_url : '../area-publica/' + depoimento.foto_url);
                }
            }
            return fotoUrl;
        }
        
        function mostrarDepoimento(indice) {
            const dep = depoimentosData[indice];
            document.getElementById('nome-alumni').textContent = dep.nome;
            const infoTurma = (dep.tipo_depoimento === 'atual')
                ? `Ano ${dep.ano_atual || '-'} • Turma ${dep.turma || '-'}`
                : `Turma ${dep.turma || '-'}`;
            document.getElementById('curso-alumni').textContent = dep.curso_nome + ' (' + infoTurma + ')';
            document.getElementById('empresa-alumni').textContent = dep.empresa;
            const tipoEl = document.getElementById('tipo-depoente');
            if (tipoEl) {
                const atual = (dep.tipo_depoimento || 'ex_aluno') === 'atual';
                tipoEl.innerHTML = `<span style="display:inline-block;padding:4px 10px;border-radius:999px;font-size:.75rem;font-weight:700;background:${atual ? '#dcfce7' : '#e0f2fe'};color:${atual ? '#15803d' : '#0369a1'};">${atual ? 'Aluno Atual' : 'Ex-Aluno'}</span>`;
            }
            document.getElementById('texto-depoimento').textContent = dep.texto;
            
            const avatarWrapper = document.querySelector('.avatar-wrapper');
            if (avatarWrapper) {
                const fotoUrl = obterFotoUrlDepoimento(dep);
                if (fotoUrl && fotoUrl !== 'foto/sem_foto.png') {
                    avatarWrapper.innerHTML = '<img src="' + fotoUrl + '" alt="' + dep.nome + '" onerror="this.parentElement.innerHTML=\'<div class=avatar-fallback><i class=far fa-user-circle></i></div>\'">';
                } else {
                    avatarWrapper.innerHTML = '<div class="avatar-fallback"><i class="far fa-user-circle"></i></div>';
                }
            }
            
            document.querySelectorAll('.ponto-indicador').forEach((p, i) => {
                p.classList.toggle('ativo', i === indice);
            });
        }
        
        function proximoDepoimento() {
            indiceAtual = (indiceAtual + 1) % depoimentosData.length;
            mostrarDepoimento(indiceAtual);
        }
        
        const pontosContainer = document.getElementById('pontos-indicadores');
        depoimentosData.forEach((_, i) => {
            const ponto = document.createElement('span');
            ponto.className = 'ponto-indicador' + (i === 0 ? ' ativo' : '');
            ponto.addEventListener('click', () => {
                indiceAtual = i;
                mostrarDepoimento(indiceAtual);
                clearInterval(intervalo);
                intervalo = setInterval(proximoDepoimento, 5000);
            });
            pontosContainer.appendChild(ponto);
        });
        
        document.getElementById('botao-anterior')?.addEventListener('click', () => {
            indiceAtual = (indiceAtual - 1 + depoimentosData.length) % depoimentosData.length;
            mostrarDepoimento(indiceAtual);
            clearInterval(intervalo);
            intervalo = setInterval(proximoDepoimento, 5000);
        });
        
        document.getElementById('botao-proximo')?.addEventListener('click', () => {
            indiceAtual = (indiceAtual + 1) % depoimentosData.length;
            mostrarDepoimento(indiceAtual);
            clearInterval(intervalo);
            intervalo = setInterval(proximoDepoimento, 5000);
        });
        
        mostrarDepoimento(0);
        intervalo = setInterval(proximoDepoimento, 5000);
        
        const areaSlider = document.querySelector('.area-slider');
        let pausado = false;
        areaSlider?.addEventListener('mouseenter', () => { pausado = true; clearInterval(intervalo); });
        areaSlider?.addEventListener('mouseleave', () => { if (!pausado) intervalo = setInterval(proximoDepoimento, 5000); pausado = false; });
    }
    
    // Slider de parceiros: mostra apenas parceiros reais e só duplica tecnicamente quando há overflow.
    const areaParceiros = document.querySelector('.area-slider-parceiros');
    const sliderParceiros = document.getElementById('sliderParceiros');
    const grupoParceirosOriginal = document.getElementById('grupoParceirosOriginal');

    function limparClonesParceiros() {
        sliderParceiros?.querySelectorAll('.slider-grupo-clone').forEach(clone => clone.remove());
    }

    function calibrarSliderParceiros() {
        if (!areaParceiros || !sliderParceiros || !grupoParceirosOriginal) return;
        limparClonesParceiros();
        sliderParceiros.classList.remove('animado');
        sliderParceiros.style.removeProperty('--fluxo-parceiros-distancia');
        sliderParceiros.style.removeProperty('--fluxo-parceiros-duracao');

        const larguraGrupo = grupoParceirosOriginal.scrollWidth;
        const larguraArea = areaParceiros.clientWidth;
        if (!larguraGrupo || larguraGrupo <= larguraArea) return;

        const clone = grupoParceirosOriginal.cloneNode(true);
        clone.id = '';
        clone.classList.add('slider-grupo-clone');
        clone.setAttribute('aria-hidden', 'true');
        sliderParceiros.appendChild(clone);

        const duracao = Math.max(10, Math.round(larguraGrupo / 65));
        sliderParceiros.style.setProperty('--fluxo-parceiros-distancia', `${larguraGrupo + 36}px`);
        sliderParceiros.style.setProperty('--fluxo-parceiros-duracao', `${duracao}s`);
        sliderParceiros.classList.add('animado');
    }

    calibrarSliderParceiros();
    window.addEventListener('resize', calibrarSliderParceiros);

    // Slider hero com animação de digitação
    const slides = document.querySelectorAll('.slide');
    const pontos = document.querySelectorAll('.ponto');
    if (slides.length > 0) {
        let slideAtual = 0;
        let timeoutDigitar;
        let timeoutProximo;
        
        const textosCompletos = Array.from(slides).map(slide => slide.querySelector('h1 span')?.textContent || '');
        
        function digitarTexto(elemento, texto, callback) {
            if (!elemento) return;
            let i = 0;
            elemento.textContent = '';
            function digitar() {
                if (i < texto.length) {
                    elemento.textContent += texto.charAt(i);
                    i++;
                    timeoutDigitar = setTimeout(digitar, 100);
                } else if (callback) callback();
            }
            digitar();
        }
        
        function mostrarSlide(indice) {
            slides.forEach(s => s.classList.remove('ativo'));
            pontos.forEach(p => p.classList.remove('ativo'));
            slides[indice].classList.add('ativo');
            pontos[indice].classList.add('ativo');
            slideAtual = indice;
            
            if (timeoutDigitar) clearTimeout(timeoutDigitar);
            if (timeoutProximo) clearTimeout(timeoutProximo);
            
            const elementoAtual = slides[indice].querySelector('h1 span');
            digitarTexto(elementoAtual, textosCompletos[indice], () => {
                timeoutProximo = setTimeout(() => {
                    mostrarSlide((slideAtual + 1) % slides.length);
                }, 2000);
            });
        }
        
        pontos.forEach((ponto, i) => {
            ponto.addEventListener('click', () => {
                if (i !== slideAtual) {
                    if (timeoutDigitar) clearTimeout(timeoutDigitar);
                    if (timeoutProximo) clearTimeout(timeoutProximo);
                    mostrarSlide(i);
                }
            });
        });
        
        mostrarSlide(0);
    }
    </script>
</body>
</html>
<?php
/**
 * Politica de Privacidade - IPIKK
 * Pagina publica que exibe a politica de privacidade do site
 */

require_once '../config/index.php';

$db = getDB();

$config = $db->query("SELECT * FROM configuracoes WHERE id = 1")->fetch();

// Buscar areas para o menu
$areas = $db->query("SELECT * FROM areas WHERE ativo = 1 ORDER BY ordem")->fetchAll();

$todos_cursos = $db->query("SELECT * FROM cursos WHERE estado = 'ativo' ORDER BY nome")->fetchAll();
$cursos_por_area = [];
foreach ($todos_cursos as $curso) {
    $cursos_por_area[$curso['area_id']][] = $curso;
}

$status_inscricoes = $db->query("SELECT status FROM controle_inscricoes WHERE id = 1")->fetch();
$link_inscricao = ($status_inscricoes && $status_inscricoes['status'] === 'abertas') ? 'inscricoes.php' : 'inscricoes-indisponiveis.php';

$titulo_pagina = "IPIKK - Politica de Privacidade";
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titulo_pagina ?></title>
    
    <meta name="description" content="Politica de Privacidade e Protecao de Dados Pessoais do IPIKK - Instituto Medio Politecnico Industrial do Kilamba Kiaxi">
    <meta name="keywords" content="IPIKK, politica de privacidade, protecao de dados, cookies, termos de uso">
    
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <link href="<?= $config['favicon_url'] ?? 'foto/ipikk_new_logo.png' ?>" rel="icon">
    
    <link rel="stylesheet" href="css/header-footer.css">
    
    <style>
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
            --sombra: 0 10px 30px rgba(0, 48, 114, 0.1);
            --borda-raio: 12px;
            --transicao: all 0.3s ease;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; scroll-padding-top: 100px; }
        body {
            font-family: 'Montserrat', sans-serif;
            background: var(--cinza-claro);
            color: var(--cinza-escuro);
            line-height: 1.6;
            overflow-x: hidden;
        }
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            line-height: 1.2;
        }
        a { text-decoration: none; color: inherit; transition: var(--transicao); }

        .cabecalho-pagina {
            background: var(--branco);
            padding: 55px 20px 40px;
            text-align: center;
            position: relative;
            border-bottom: 1px solid rgba(0,48,114,0.08);
        }
        .titulo-pagina {
            font-size: 2.6rem;
            color: var(--azul-principal);
            margin-bottom: 14px;
            animation: deslizarCima 0.6s ease both;
        }
        .titulo-pagina span { color: var(--verde-acento); }
        .linha-decorativa-titulo {
            width: 80px; height: 4px;
            background: linear-gradient(to right, var(--verde-acento), var(--verde-claro));
            margin: 0 auto;
            border-radius: 50px;
            animation: expandir 0.8s ease 0.3s both;
        }
        @keyframes deslizarCima {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes expandir {
            from { width: 0; }
            to { width: 80px; }
        }

        .corpo-politica {
            max-width: 900px;
            margin: 50px auto 60px;
            padding: 0 20px;
        }

        .documento-container {
            background: var(--branco);
            border-radius: 18px;
            padding: 50px 55px;
            box-shadow: var(--sombra);
            animation: deslizarCima 0.6s ease 0.1s both;
        }

        .documento-container > p:first-child {
            color: var(--cinza);
            line-height: 1.8;
            margin-bottom: 36px;
            font-size: 0.97rem;
            border-left: 4px solid var(--verde-acento);
            padding-left: 18px;
        }

        .titulo-seccao {
            display: flex;
            align-items: center;
            gap: 14px;
            font-size: 1.25rem;
            color: var(--azul-principal);
            margin: 36px 0 14px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--cinza-claro);
        }

        .icone-seccao {
            width: 42px; height: 42px;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--azul-principal), var(--verde-acento));
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .icone-seccao i { color: var(--branco); font-size: 1rem; }

        .documento-container p {
            color: var(--cinza);
            line-height: 1.8;
            margin-bottom: 14px;
            font-size: 0.96rem;
        }

        .lista-politica {
            list-style: none;
            padding: 0;
            margin: 12px 0 20px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .lista-politica li {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            background: var(--cinza-claro);
            border-radius: 10px;
            padding: 12px 16px;
            color: var(--cinza-escuro);
            font-size: 0.95rem;
            line-height: 1.6;
        }

        .numero-item {
            min-width: 28px; height: 28px;
            border-radius: 50%;
            background: var(--azul-principal);
            color: var(--branco);
            display: flex; align-items: center; justify-content: center;
            font-size: 0.8rem; font-weight: 700;
            flex-shrink: 0;
            margin-top: 1px;
        }

        .separador {
            height: 1px;
            background: linear-gradient(to right, transparent, var(--cinza-claro), transparent);
            margin: 28px 0;
        }

        .caixa-destaque-alerta {
            background: rgba(10, 147, 150, 0.07);
            border: 1.5px solid rgba(10, 147, 150, 0.35);
            border-left: 5px solid var(--verde-acento);
            border-radius: 10px;
            padding: 18px 22px;
            margin: 22px 0;
            display: flex;
            align-items: flex-start;
            gap: 14px;
        }
        .caixa-destaque-alerta .icone-alerta {
            color: var(--verde-acento);
            font-size: 1.25rem;
            margin-top: 2px;
            flex-shrink: 0;
        }
        .caixa-destaque-alerta p {
            color: var(--azul-principal) !important;
            font-weight: 500;
            font-size: 0.94rem !important;
            margin: 0 !important;
            line-height: 1.7;
        }

        .caixa-assinatura {
            background: var(--cinza-claro);
            border-radius: 12px;
            padding: 26px 30px;
            margin: 22px 0;
            border-left: 5px solid var(--azul-principal);
        }
        .caixa-assinatura .data-assinatura {
            font-style: italic;
            color: var(--cinza);
            font-size: 0.92rem;
            margin-bottom: 8px;
        }
        .caixa-assinatura .organizacao-assinatura {
            font-style: italic;
            color: var(--azul-claro);
            font-size: 0.95rem;
            margin-bottom: 6px;
        }
        .caixa-assinatura .departamento-assinatura {
            font-weight: 700;
            color: var(--azul-principal);
            font-size: 0.93rem;
        }

        .ultima-atualizacao {
            text-align: right;
            font-size: 0.8rem;
            color: var(--cinza);
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid var(--cinza-claro);
        }

        @media (max-width: 992px) {
            .menu-navegacao { display: none; }
            .botao-menu-mobile { display: block; }
        }
        @media (max-width: 768px) {
            .conteudo-superior { flex-direction: column; gap: 10px; text-align: center; }
            .conteudo-cabecalho { padding: 10px 20px; }
            .documento-container { padding: 30px 22px; }
            .titulo-pagina { font-size: 2rem; }
            .container-rodape { padding: 0 20px; }
        }
        @media (max-width: 480px) {
            .titulo-pagina { font-size: 1.7rem; }
            .botoes-flutuantes { bottom: 20px; right: 20px; }
        }
    </style>
</head>
<body>


    <!-- ===== CABECALHO ===== -->

<?php include __DIR__ . '/includes/header.php'; ?>
    
    <!-- ===== TITULO DA PAGINA ===== -->
    <section class="cabecalho-pagina">
        <h1 class="titulo-pagina"> <span>Politicas de Privacidade</span></h1>
        <div class="linha-decorativa-titulo"></div>
    </section>

    <!-- ===== CONTEUDO PRINCIPAL ===== -->
    <main class="corpo-politica">
        <div class="documento-container">

            <p>A Politica de Privacidade e Protecao de Dados Pessoais, complementares aos Termos e Condicoes de Utilizacao, destina-se a regular o processo de tratamento de dados pessoais a realizar pelo IPIKK por conta da utilizacao deste website.</p>

            <h2 class="titulo-seccao">
                <span class="icone-seccao"><i class="fas fa-clipboard"></i></span>
                Dados Recolhidos
            </h2>
            <p>Constituem dados recolhidos, os seguintes:</p>
            <ul class="lista-politica">
                <li><span class="numero-item">1</span>Dados fornecidos diretamente pelo titular</li>
                <li><span class="numero-item">2</span>Dados recolhidos no âmbito de relacao constituida com o titular desses dados</li>
                <li><span class="numero-item">3</span>Dados pessoais solicitados ao titular tratados com o seu consentimento</li>
            </ul>

            <h2 class="titulo-seccao">
                <span class="icone-seccao"><i class="fas fa-globe"></i></span>
                Finalidades
            </h2>
            <p>A utilizacao dos dados recolhidos deve ter como finalidades:</p>
            <ul class="lista-politica">
                <li><span class="numero-item">1</span>A prestacao de servicos solicitados pelo titular</li>
                <li><span class="numero-item">2</span>Fornecer informacao sobre produtos, servicos, atividades de marketing, campanhas, promocoes, fins estatisticos e conteudos personalizados, mediante o consentimento previo para o efeito</li>
            </ul>

            <div class="separador"></div>

            <div class="caixa-destaque-alerta">
                <i class="fas fa-triangle-exclamation icone-alerta"></i>
                <p>Além das obrigacoes referidas na Lei aplicavel a protecao de dados ou a salvaguarda e protecao dos seus proprios interesses, o IPIKK nao partilhara quaisquer dados pessoais com entidades terceiras.</p>
            </div>

            <p>E proibido o uso deste site para quaisquer fins ilegais, abusivos, difamatorios ou que ameacem a transmissao de qualquer virus ou outro tipo de codigo informatico, ficheiros ou programas desenhados para interromper, destruir ou danificar intencionalmente hardware ou software ou que interfira no funcionamento normal do site.</p>

            <div class="separador"></div>

            <h2 class="titulo-seccao">
                <span class="icone-seccao"><i class="fas fa-cookie-bite"></i></span>
                Cookies
            </h2>
            <p>O IPIKK podera utilizar cookies no seu website com o objetivo de melhorar a qualidade do servico, as funcionalidades disponibilizadas e a experiencia do utilizador, nao sendo utilizadas para definicao de perfis.</p>
            <p>Se nao permitir a utilizacao de cookies alguns servicos e/ou funcionalidades poderao nao corresponder ao nivel de servico esperado.</p>
            <p>Caso pretenda remover cookies deve consultar a secao "Ajuda" do seu navegador de internet.</p>

            <div class="separador"></div>

            <h2 class="titulo-seccao">
                <span class="icone-seccao"><i class="fas fa-balance-scale"></i></span>
                Autoridade de Controlo
            </h2>
            <p>A autoridade de controlo e a Agencia de Protecao de Dados (APD), a quem compete velhar pelo cumprimento da legislacao sobre protecao de dados pessoais.</p>

            <h2 class="titulo-seccao">
                <span class="icone-seccao"><i class="fas fa-edit"></i></span>
                Alteracao
            </h2>
            <p>As disposicoes do presente termo de uso e privacidade pode ser alterada, sempre que se justificar ou sempre que se registe qualquer alteracao na legislacao em vigor sobre a materia.</p>

            <div class="caixa-assinatura">
                <div class="data-assinatura"><i>Luanda, 22 de Abril de 2022</i></div>
                <div class="organizacao-assinatura"><em>Instituto Nacional de Fomento da Sociedade de Informacao</em></div>
                <div class="departamento-assinatura">Departamento de Ciberseguranca, Chaves Publicas e Carimbo do Tempo</div>
            </div>

            <div class="separador"></div>

            <h2 class="titulo-seccao">
                <span class="icone-seccao"><i class="fas fa-triangle-exclamation"></i></span>
                Limitacoes de Tratamento
            </h2>
            <p>O IPIKK nao efectuara o tratamento de dados pessoais que revelem a origem racial ou etnica, as opinioes politicas, as conviccoes religiosas ou filosoficas, ou a filiacao sindical, bem como o tratamento de dados geneticos, dados biometricos para identificar uma pessoa de forma inequivoca, dados relativos a saude ou genero.</p>
            <p>Os dados pessoais apenas poderao ser recolhidos caso:</p>
            <ul class="lista-politica">
                <li><span class="numero-item">1</span>O utilizador efectue registo no website</li>
                <li><span class="numero-item">2</span>O titular dos dados solicite um envio, respondendo a um inquerito, ou mais informacoes sobre um programa atraves de formulario e/ou outro meio de comunicacao eletronica com o IPIKK</li>
            </ul>
            <p>A receita de informacao pessoal esta limitada aos visitantes que se registam voluntariamente.</p>

            <h2 class="titulo-seccao">
                <span class="icone-seccao"><i class="fas fa-envelope-open-text"></i></span>
                Consentimento para a Recepcao de Informacoes
            </h2>
            <p>O IPIKK sera o seu consentimento previo relativamente a rececao de comunicacoes comerciais para fins de marketing, sendo-lhe conferida a faculdade de oposicao, a todo o tempo, mediante comunicacao dirigida ao IPIKK.</p>
            <p>As referidas informacoes comerciais, poderao ser enviadas pelo IPIKK atraves de correio eletronico, telefone, SMS ou qualquer outro meio de comunicacao electronica, websites de redes sociais, Web 2.0, qualquer canal de telemovel ou metodo.</p>

            <div class="ultima-atualizacao">
                <i class="fas fa-clock"></i> Ultima atualizacao: <?= date('d/m/Y') ?>
            </div>
        </div>
    </main>

    <!-- ===== BOTOES FLUTUANTES ===== -->
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

    <!-- ===== RODAPE ===== -->

<?php include __DIR__ . '/includes/footer.php'; ?>

    <script src="js/header-footer.js"></script>
</body>
</html>
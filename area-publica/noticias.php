<?php
/**
 * Página Notícias - IPIKK
 */

require_once '../config/index.php';

// Buscar configurações do site
$config = getDB()->query("SELECT * FROM configuracoes WHERE id = 1")->fetch();

// Buscar áreas para o menu
$areas = getDB()->query("SELECT * FROM areas WHERE ativo = 1 ORDER BY ordem")->fetchAll();

// Buscar todos os cursos para os submenus
$todos_cursos = getDB()->query("SELECT * FROM cursos WHERE estado = 'ativo' ORDER BY nome")->fetchAll();
$cursos_por_area = [];
foreach ($todos_cursos as $curso_item) {
    $cursos_por_area[$curso_item['area_id']][] = $curso_item;
}

// Verificar status das inscrições
$status_inscricoes = getDB()->query("SELECT status FROM controle_inscricoes WHERE id = 1")->fetch();
$link_inscricao = ($status_inscricoes && $status_inscricoes['status'] === 'abertas') ? 'inscricoes.php' : 'inscricoes-indisponiveis.php';

// Buscar conteúdo da página (JSON)
$pagina = getPagina('noticias');

// Extrair dados da página
$titulo_secao = $pagina['titulo'] ?? 'Últimas Notícias do Instituto';
$subtitulo_secao = $pagina['subtitulo'] ?? 'Fique por dentro de tudo que acontece no IPIKK: eventos, conquistas, inovações e oportunidades para os nossos alunos';

// Buscar notícias publicadas
$todas_noticias = getDB()->query("
    SELECT * FROM noticias 
    WHERE estado = 'publicada' 
    ORDER BY data_publicacao DESC, created_at DESC
")->fetchAll();

$sem_noticias = empty($todas_noticias);

// Separar notícia destaque (primeira) das demais
$noticia_destaque = !empty($todas_noticias) ? $todas_noticias[0] : null;
$outras_noticias = array_slice($todas_noticias, 1);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IPIKK - Notícias</title>
    
    <!-- Fontes e Ícones -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <!-- Favicon -->
    <link href="<?= $config['favicon_url'] ?? 'foto/ipikk_new_logo.png' ?>" rel="icon">
    
    <!-- CSS Header/Footer Padrão -->
    <link rel="stylesheet" href="css/header-footer.css">

        <style>
        /* ======= VARIÁVEIS ========== */
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
            --amarelo-destaque: #ffc107;
            --sombra: 0 10px 30px rgba(0, 48, 114, 0.1);
            --borda-raio: 12px;
            --transicao: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background: var(--cinza-claro);
            color: var(--cinza-escuro);
            line-height: 1.6;
        }

        h1, h2, h3, h4 {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
        }

        /* ===== CONTAINER PRINCIPAL ===== */
        .container-pagina {
            max-width: 1400px;
            margin: 40px auto;
            padding: 0 20px;
        }

        /* ===== CABEÇALHO TOPO ===== */
        .cabecalho-topo {
            background: linear-gradient(135deg, var(--azul-principal), var(--azul-escuro));
            padding: 25px 30px;
            border-radius: var(--borda-raio);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--sombra);
        }

        .titulo-pagina {
            color: var(--branco);
            font-size: 2rem;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .titulo-pagina i {
            color: var(--amarelo-destaque);
            font-size: 2.2rem;
        }

        .badge-ao-vivo {
            background: #dc3545;
            color: var(--branco);
            padding: 8px 20px;
            border-radius: 25px;
            font-size: 0.9rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: pulsar 2s infinite;
        }

        @keyframes pulsar {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.8; transform: scale(1.05); }
        }

        /* ===== LAYOUT PRINCIPAL: DIV SUPERIOR + 2 DIVS ABAIXO ===== */
        .layout-noticias {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }

        /* DIV SUPERIOR */
        .secao-destaque-superior {
            background: linear-gradient(155deg, var(--azul-principal) 0%, var(--verde-acento) 100%);
            padding: 64px 24px 52px;
            text-align: center;
            position: relative;
            overflow: hidden;
            color: var(--branco);
            box-shadow: var(--sombra);
        }

        .secao-destaque-superior h2 {
            font-size: 2.5rem;
            margin-bottom: 15px;
            color: var(--branco);
        }

        .secao-destaque-superior p {
            font-size: 1.1rem;
            opacity: 0.95;
            max-width: 800px;
            margin: 0 auto;
        }

        /* GRADE DE 2 COLUNAS */
        .grade-principal {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 30px;
        }

        /* ===== NOTÍCIA PRINCIPAL (ESQUERDA) ===== */
        .noticia-destaque {
            background: var(--branco);
            border-radius: var(--borda-raio);
            overflow: hidden;
            box-shadow: var(--sombra);
            position: relative;
            height: 650px;
            cursor: pointer;
            transition: var(--transicao);
        }

        .noticia-destaque:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 48, 114, 0.2);
        }

        .container-midia {
            width: 100%;
            height: 100%;
            position: relative;
            overflow: hidden;
        }

        .container-midia img,
        .container-midia video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .noticia-destaque:hover .container-midia img,
        .noticia-destaque:hover .container-midia video {
            transform: scale(1.08);
        }

        .overlay-escuro {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 60%;
            background: linear-gradient(to top, rgba(0,0,0,0.95), transparent);
            pointer-events: none;
        }

        .info-destaque {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 35px;
            color: var(--branco);
            z-index: 2;
        }

        .badge-categoria {
            display: inline-block;
            background: var(--amarelo-destaque);
            color: var(--cinza-escuro);
            padding: 8px 20px;
            border-radius: 25px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 15px;
            letter-spacing: 1px;
        }

        .badge-categoria i {
            margin-right: 5px;
        }

        .titulo-destaque {
            font-size: 2rem;
            margin-bottom: 15px;
            line-height: 1.3;
            text-shadow: 2px 2px 6px rgba(0,0,0,0.7);
        }

        .meta-informacao {
            display: flex;
            gap: 25px;
            font-size: 0.9rem;
            opacity: 0.95;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* ===== SLIDER VERTICAL (DIREITA) ===== */
        .container-slider-vertical {
            background: var(--branco);
            border-radius: var(--borda-raio);
            box-shadow: var(--sombra);
            overflow: hidden;
            height: 650px;
            position: relative;
        }

        .cabecalho-slider {
            background: linear-gradient(135deg, var(--azul-principal), var(--verde-acento));
            padding: 20px 25px;
            color: var(--branco);
        }

        .cabecalho-slider h3 {
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 12px;
            color: var(--branco);
        }

        .area-slider {
            height: calc(650px - 70px);
            overflow: hidden;
            position: relative;
            cursor: pointer;
        }

        .lista-slider {
            display: flex;
            flex-direction: column;
            transition: transform 0.7s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }

        .item-slider {
            min-height: 193px;
            padding: 20px;
            border-bottom: 2px solid var(--cinza-claro);
            cursor: pointer;
            transition: var(--transicao);
            display: flex;
            gap: 18px;
        }

        .item-slider:hover {
            background: rgba(10, 147, 150, 0.05);
            transform: translateX(8px);
        }

        .miniatura-slider {
            width: 140px;
            height: 105px;
            border-radius: 10px;
            overflow: hidden;
            flex-shrink: 0;
            position: relative;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .miniatura-slider img,
        .miniatura-slider video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: var(--transicao);
        }

        .item-slider:hover .miniatura-slider img,
        .item-slider:hover .miniatura-slider video {
            transform: scale(1.1);
        }

        .conteudo-slider {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .data-slider {
            color: var(--verde-acento);
            font-size: 0.75rem;
            font-weight: 700;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
            text-transform: uppercase;
        }

        .titulo-slider {
            font-size: 1rem;
            color: var(--azul-principal);
            margin-bottom: 8px;
            font-weight: 600;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .resumo-slider {
            font-size: 0.85rem;
            color: var(--cinza);
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* ===== MODAL DE DETALHES ===== */
        .modal-fundo {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            z-index: 99999;
            align-items: center;
            justify-content: center;
            padding: 20px;
            backdrop-filter: blur(8px);
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-fundo.visivel {
            display: flex;
        }

        .modal-conteudo {
            background: var(--branco);
            border-radius: var(--borda-raio);
            max-width: 950px;
            width: 100%;
            max-height: 92vh;
            overflow-y: auto;
            position: relative;
            animation: modalSlideUp 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            box-shadow: 0 25px 60px rgba(0,0,0,0.5);
        }

        @keyframes modalSlideUp {
            from {
                opacity: 0;
                transform: translateY(60px) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .btn-fechar-modal {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: rgba(0,0,0,0.7);
            color: var(--branco);
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            z-index: 10;
            transition: var(--transicao);
        }

        .btn-fechar-modal:hover {
            background: #dc3545;
            transform: rotate(90deg) scale(1.1);
        }

        .modal-midia {
            width: 100%;
            height: 520px;
            background: #000;
            position: relative;
            border-radius: var(--borda-raio) var(--borda-raio) 0 0;
            overflow: hidden;
        }

        .modal-midia img,
        .modal-midia video {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .modal-corpo {
            padding: 35px;
        }

        .modal-badge {
            background: var(--amarelo-destaque);
            color: var(--cinza-escuro);
            padding: 8px 18px;
            border-radius: 25px;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            display: inline-block;
            margin-bottom: 18px;
        }

        .modal-badge i {
            margin-right: 5px;
        }

        .modal-titulo {
            font-size: 2.2rem;
            color: var(--azul-principal);
            margin-bottom: 18px;
            line-height: 1.3;
        }

        .modal-meta {
            display: flex;
            gap: 30px;
            padding-bottom: 22px;
            border-bottom: 2px solid var(--cinza-claro);
            margin-bottom: 28px;
            color: var(--cinza);
            font-size: 0.95rem;
            flex-wrap: wrap;
        }

        .modal-meta-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-meta-item i {
            color: var(--verde-acento);
            font-size: 1.1rem;
        }

        .modal-descricao {
            color: var(--cinza-escuro);
            font-size: 1.1rem;
            line-height: 1.9;
            margin-bottom: 25px;
        }

        .modal-tags {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            padding-top: 22px;
            border-top: 2px solid var(--cinza-claro);
        }

        .tag-item {
            background: var(--cinza-claro);
            color: var(--azul-principal);
            padding: 8px 18px;
            border-radius: 25px;
            font-size: 0.9rem;
            font-weight: 600;
            transition: var(--transicao);
            cursor: default;
        }

        .tag-item i {
            margin-right: 5px;
            font-size: 0.8rem;
        }

        .tag-item:hover {
            background: var(--azul-principal);
            color: var(--branco);
            transform: translateY(-2px);
        }

        /* ===== RESPONSIVIDADE ===== */
        @media (max-width: 1200px) {
            .grade-principal {
                grid-template-columns: 1fr;
            }

            .noticia-destaque,
            .container-slider-vertical {
                height: 550px;
            }

            .area-slider {
                height: calc(550px - 70px);
            }

            .item-slider {
                min-height: 160px;
            }
        }

        @media (max-width: 768px) {
            .cabecalho-topo {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .titulo-pagina {
                font-size: 1.6rem;
            }

            .secao-destaque-superior h2 {
                font-size: 1.8rem;
            }

            .modal-midia {
                height: 350px;
            }

            .modal-titulo {
                font-size: 1.7rem;
            }

            .modal-meta {
                gap: 15px;
            }

            .item-slider {
                flex-direction: column;
            }

            .miniatura-slider {
                width: 100%;
                height: 180px;
            }
        }

        @media (max-width: 480px) {
            .titulo-destaque {
                font-size: 1.5rem;
            }

            .modal-corpo {
                padding: 25px;
            }
        }
    
        </style>        

</head>
<body>

<?php include __DIR__ . '/includes/header.php'; ?>

<!-- DIV SUPERIOR -->
            <div class="secao-destaque-superior">
                <h2><i class="fas fa-bullhorn"></i> <?= htmlspecialchars($titulo_secao) ?></h2>
                <p><?= htmlspecialchars($subtitulo_secao) ?></p>
            </div>
            
<!-- ===== CONTEÚDO PRINCIPAL ===== -->
    <div class="container-pagina">
        
        <div class="layout-noticias">
            <?php if ($sem_noticias): ?>
                <div class="estado-vazio" style="grid-column:1 / -1; text-align:center; background:#fff; border-radius:12px; padding:70px 20px; box-shadow: var(--sombra);">
                    <i class="fas fa-newspaper" style="font-size:3rem; color:var(--cinza);"></i>
                    <h3 style="margin-top:15px; color:var(--azul-principal);">Sem notícias no momento</h3>
                </div>
            <?php endif; ?>
            <!-- GRADE DE 2 COLUNAS -->
            <div class="grade-principal">
                
                <!-- NOTÍCIA DESTAQUE (ESQUERDA) -->
                <?php if(!$sem_noticias && $noticia_destaque): ?>
                <div class="noticia-destaque" id="noticiaDestaque" onclick="abrirModalNoticiaDestaque()">
                    <div class="container-midia">
                        <?php if($noticia_destaque['tipo_midia'] === 'video' && (!empty($noticia_destaque['imagem_url']) || !empty($noticia_destaque['video_file']))): ?>
                            <?php if (!empty($noticia_destaque['imagem_url'])): ?>
                                <img src="<?= htmlspecialchars(normalizarUrlMidia($noticia_destaque['imagem_url'] ?? '', '..')) ?>" alt="<?= htmlspecialchars($noticia_destaque['titulo']) ?>">
                            <?php else: ?>
                                <video src="<?= htmlspecialchars(normalizarUrlMidia($noticia_destaque['video_file'], '..')) ?>" muted></video>
                            <?php endif; ?>
                        <?php else: ?>
                            <img src="<?= htmlspecialchars(normalizarUrlMidia($noticia_destaque['imagem_url'] ?? '', '..')) ?>" alt="<?= htmlspecialchars($noticia_destaque['titulo']) ?>">
                        <?php endif; ?>
                        <div class="overlay-escuro"></div>
                    </div>
                    <div class="info-destaque">
                        <span class="badge-categoria" id="destaqueCategoria"><i class="fas fa-trophy"></i> <?= htmlspecialchars($noticia_destaque['categoria']) ?></span>
                        <h2 class="titulo-destaque" id="destaqueTitulo"><?= htmlspecialchars($noticia_destaque['titulo']) ?></h2>
                        <div class="meta-informacao">
                            <span class="meta-item">
                                <i class="fas fa-calendar"></i>
                                <span id="destaqueData"><?= formatarData($noticia_destaque['data_publicacao'], true) ?></span>
                            </span>
                            <span class="meta-item">
                                <i class="fas fa-clock"></i>
                                <span id="destaqueTempo"><?= tempoRelativo($noticia_destaque['data_publicacao']) ?></span>
                            </span>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- SLIDER VERTICAL (DIREITA) -->
                <div class="container-slider-vertical">
                    <div class="cabecalho-slider">
                        <h3>
                            <i class="fas fa-newspaper"></i>
                            Notícias em Destaque
                        </h3>
                    </div>
                    <div class="area-slider" id="areaSlider">
                        <div class="lista-slider" id="listaSlider"></div>
                    </div>
                </div>

            </div>

        </div>
    </div>

    <!-- MODAL DE DETALHES -->
    <div class="modal-fundo" id="modalDetalhes">
        <div class="modal-conteudo">
            <button class="btn-fechar-modal" onclick="fecharModalNoticia()">
                <i class="fas fa-times"></i>
            </button>
            <div class="modal-midia" id="modalMidia"></div>
            <div class="modal-corpo">
                <span class="modal-badge" id="modalCategoria"></span>
                <h2 class="modal-titulo" id="modalTitulo"></h2>
                <div class="modal-meta">
                    <span class="modal-meta-item">
                        <i class="fas fa-calendar-alt"></i>
                        <span id="modalData"></span>
                    </span>
                    <span class="modal-meta-item">
                        <i class="fas fa-user-circle"></i>
                        <span id="modalAutor"></span>
                    </span>
                    <span class="modal-meta-item">
                        <i class="fas fa-eye"></i>
                        <span id="modalVisualizacoes"></span>
                    </span>
                </div>
                <div class="modal-descricao" id="modalDescricao"></div>
                <div class="modal-tags" id="modalTags"></div>
            </div>
        </div>
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

    <!-- ===== RODAPÉ ===== -->
    <?php include __DIR__ . '/includes/footer.php'; ?>


    <script src="js/header-footer.js"></script>
    <div id="noticiasPageData" hidden data-noticias='<?= htmlspecialchars(json_encode($todas_noticias), ENT_QUOTES, "UTF-8") ?>'></div>

    <script>
        // ===== DADOS DAS NOTÍCIAS =====
const pageDataEl = document.getElementById('noticiasPageData');
function normalizarUrlNoticia(url){ if(!url) return ''; const u=String(url).trim(); if(/^https?:\/\//i.test(u)) return u; return '..'+(u.startsWith('/')?u:'/'+u); }

const noticiasData = JSON.parse(pageDataEl?.dataset.noticias || '[]');


function incrementarVisualizacaoNoticia(id) {
    const noticiaId = Number(id || 0);
    if (!noticiaId) return;
    const chaveSessao = `noticia_vista_${noticiaId}`;
    if (sessionStorage.getItem(chaveSessao)) return;
    sessionStorage.setItem(chaveSessao, '1');

    fetch(`../incrementar-visualizacao.php?tipo=noticia&id=${noticiaId}`)
        .then(() => {
            const noticia = noticiasData.find(n => Number(n.id) === noticiaId);
            if (noticia) {
                noticia.visualizacoes = Number(noticia.visualizacoes || 0) + 1;
                const campo = document.getElementById('modalVisualizacoes');
                if (campo) campo.textContent = `${noticia.visualizacoes} visualizações`;
            }
        })
        .catch(() => {});
}

function initNotificacoesNoticiasPagina() {
    if (!noticiasData.length) return;
    const noticiaRecente = [...noticiasData].sort((a,b)=>Number(b.id)-Number(a.id))[0];
    const pref = localStorage.getItem('ipikk_news_notif_enabled');
    if (pref !== '1') return;
    const seen = Number(localStorage.getItem('ipikk_news_last_seen_id') || 0);
    if (!noticiaRecente || Number(noticiaRecente.id) <= seen) return;

    const resumo = String(noticiaRecente.resumo || noticiaRecente.conteudo || '').replace(/<[^>]*>/g, '').slice(0, 140);
    const overlay = document.createElement('div');
    overlay.className = 'modal-fundo visivel';
    overlay.style.zIndex = '100000';
    overlay.innerHTML = `<div class="modal-conteudo" style="max-width:560px;"><button class="btn-fechar-modal" data-close="1"><i class="fas fa-times"></i></button><div class="modal-corpo"><h2 class="modal-titulo" style="font-size:1.6rem;">📰 Novidade do IPIKK</h2><p><strong>${escapeHtml(noticiaRecente.titulo || 'Nova notícia')}</strong></p><p>${escapeHtml(resumo)}${resumo.length>=140?'...':''}</p><div style="display:flex;gap:10px;"><button id="irNoticia" class="botao-filtro" style="background:#003072;color:#fff;">Ver notícia</button><button id="fecharNotif" class="botao-filtro">Fechar</button></div></div></div>`;
    document.body.appendChild(overlay);
    document.body.style.overflow='hidden';
    const marcar = ()=> localStorage.setItem('ipikk_news_last_seen_id', String(noticiaRecente.id));
    const fechar = ()=>{overlay.remove(); document.body.style.overflow='';};
    overlay.querySelector('#irNoticia')?.addEventListener('click', ()=>{ marcar(); fechar(); abrirModalNoticia(noticiaRecente.id); });
    overlay.querySelector('#fecharNotif')?.addEventListener('click', ()=>{ marcar(); fechar(); });
    overlay.querySelector('[data-close]')?.addEventListener('click', ()=>{ marcar(); });
    overlay.addEventListener('click', e=>{ if(e.target===overlay){ marcar(); fechar(); }});
}

function normalizarDataNoticia(noticia) {
    const referencia = noticia.created_at || noticia.data_publicacao;
    const d = new Date(referencia);
    return isNaN(d.getTime()) ? new Date() : d;
}

function formatarDataSlider(data) {
    const d = new Date(data);
    const meses = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];
    return `${d.getDate()} ${meses[d.getMonth()]} ${d.getFullYear()}`;
}

function formatarDataCompleta(data) {
    const d = new Date(data);
    const meses = ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
    const hh = String(d.getHours()).padStart(2, '0');
    const mm = String(d.getMinutes()).padStart(2, '0');
    return `${d.getDate()} de ${meses[d.getMonth()]} de ${d.getFullYear()} às ${hh}:${mm}`;
}

function tempoRelativo(data) {
    const agora = new Date();
    const d = new Date(data);
    const diffSeg = Math.max(0, Math.floor((agora - d) / 1000));

    if (diffSeg < 60) return 'agora mesmo';
    const min = Math.floor(diffSeg / 60);
    if (min < 60) return min === 1 ? 'há 1 minuto' : `há ${min} minutos`;
    const h = Math.floor(min / 60);
    if (h < 24) return h === 1 ? 'há 1 hora' : `há ${h} horas`;
    const dias = Math.floor(h / 24);
    if (dias < 30) return dias === 1 ? 'há 1 dia' : `há ${dias} dias`;
    const meses = Math.floor(dias / 30);
    if (meses < 12) return meses === 1 ? 'há 1 mês' : `há ${meses} meses`;
    const anos = Math.floor(meses / 12);
    return anos === 1 ? 'há 1 ano' : `há ${anos} anos`;
}

function obterThumbNoticia(noticia) {
    if (noticia.imagem_url) return normalizarUrlNoticia(noticia.imagem_url);
    return '';
}

function normalizarUrlMidiaJs(url) {
    if (!url) return '';
    if (/^https?:\/\//i.test(url)) return url;
    return '..' + String(url).replace(/^\/+/, '');
}

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

const noticiaController = {
    filaNoticias: [...noticiasData].sort((a, b) => normalizarDataNoticia(b) - normalizarDataNoticia(a)),
    intervaloAutomatico: null,
    pausado: false,
    emAnimacao: false,

    init() {
        this.renderizarDestaque();
        this.renderizarSlider();
        this.iniciarAutoPlay();
        this.configurarEventos();
        this.configurarModalEventos();
    },

    get noticiaAtual() {
        return this.filaNoticias[0] || null;
    },

    renderizarDestaque() {
        const noticia = this.noticiaAtual;
        if (!noticia) return;

        const container = document.getElementById('noticiaDestaque');
        const midia = container?.querySelector('.container-midia');
        const categoria = document.getElementById('destaqueCategoria');
        const titulo = document.getElementById('destaqueTitulo');
        const data = document.getElementById('destaqueData');
        const tempo = document.getElementById('destaqueTempo');
        if (!container || !midia || !categoria || !titulo || !data || !tempo) return;

        container.dataset.id = noticia.id;
        categoria.innerHTML = `<i class="fas fa-trophy"></i> ${escapeHtml(noticia.categoria || 'NOTÍCIA')}`;
        titulo.textContent = noticia.titulo || '';

        const dataRef = normalizarDataNoticia(noticia);
        data.textContent = formatarDataCompleta(dataRef);
        tempo.textContent = tempoRelativo(dataRef);

        const midiaHtml = (noticia.tipo_midia === 'video')
            ? `<img src="${obterThumbNoticia(noticia)}" alt="${escapeHtml(noticia.titulo || 'Vídeo')}">`
            : `<img src="${obterThumbNoticia(noticia)}" alt="${escapeHtml(noticia.titulo || 'Notícia')}">`;

        midia.innerHTML = `${midiaHtml}<div class="overlay-escuro"></div>`;
    },

    renderizarSlider() {
        const lista = document.getElementById('listaSlider');
        if (!lista) return;
        const noticiasSlider = this.filaNoticias.slice(1);

        lista.style.transition = 'none';
        lista.style.transform = 'translateY(0)';
        lista.innerHTML = noticiasSlider.map(noticia => {
            const dataRef = normalizarDataNoticia(noticia);
            return `
                <div class="item-slider" onclick="abrirModalNoticia(${noticia.id})">
                    <div class="miniatura-slider">
                        <img src="${obterThumbNoticia(noticia)}" alt="${escapeHtml(noticia.titulo)}">
                    </div>
                    <div class="conteudo-slider">
                        <div class="data-slider">
                            <i class="fas fa-clock"></i>
                            ${formatarDataSlider(dataRef)}
                        </div>
                        <h4 class="titulo-slider">${escapeHtml(noticia.titulo)}</h4>
                        <p class="resumo-slider">${escapeHtml(noticia.resumo || limitarTexto(noticia.conteudo, 80))}</p>
                    </div>
                </div>
            `;
        }).join('');
    },

    iniciarAutoPlay() {
        if (this.intervaloAutomatico) clearInterval(this.intervaloAutomatico);
        this.intervaloAutomatico = setInterval(() => {
            if (!this.pausado) this.proximaNoticia();
        }, 5000);
    },

    proximaNoticia() {
        if (this.filaNoticias.length <= 1 || this.emAnimacao) return;

        const lista = document.getElementById('listaSlider');
        const primeiro = lista?.querySelector('.item-slider');
        const altura = primeiro ? primeiro.offsetHeight : 193;

        this.emAnimacao = true;
        if (lista) {
            lista.style.transition = 'transform 0.6s ease';
            lista.style.transform = `translateY(-${altura}px)`;
        }

        setTimeout(() => {
            const primeira = this.filaNoticias.shift();
            this.filaNoticias.push(primeira);
            this.renderizarDestaque();
            this.renderizarSlider();
            this.emAnimacao = false;
        }, 620);
    },

    abrirModal(id) {
        incrementarVisualizacaoNoticia(id);
        const noticia = this.filaNoticias.find(n => Number(n.id) === Number(id));
        if (!noticia) return;

        const modal = document.getElementById('modalDetalhes');
        const midiaContainer = document.getElementById('modalMidia');
        if (!modal || !midiaContainer) return;

        if (noticia.tipo_midia === 'video' && noticia.video_file) {
            const videoSrc = normalizarUrlMidiaJs(noticia.video_file);
            midiaContainer.innerHTML = `<video src="${videoSrc}" controls autoplay poster="${obterThumbNoticia(noticia)}"></video>`;
        } else {
            midiaContainer.innerHTML = `<img src="${obterThumbNoticia(noticia)}" alt="${escapeHtml(noticia.titulo)}">`;
        }

        document.getElementById('modalCategoria').innerHTML = `<i class="fas fa-tag"></i> ${escapeHtml(noticia.categoria || 'NOTÍCIA')}`;
        document.getElementById('modalTitulo').textContent = noticia.titulo || '';
        document.getElementById('modalData').textContent = formatarDataCompleta(normalizarDataNoticia(noticia));
        document.getElementById('modalAutor').textContent = noticia.autor || 'Gabinete de Comunicação';
        document.getElementById('modalVisualizacoes').textContent = `${noticia.visualizacoes || 0} visualizações`;
        document.getElementById('modalDescricao').innerHTML = noticia.conteudo || '';

        const tagsEl = document.getElementById('modalTags');
        const tags = normalizarTagsNoticia(noticia.tags);
        tagsEl.innerHTML = tags.map(tag => `<span class="tag-item"><i class="fas fa-hashtag"></i>${escapeHtml(tag)}</span>`).join('');

        modal.classList.add('visivel');
        document.body.style.overflow = 'hidden';
        this.pausado = true;
    },

    abrirModalDestaque() {
        if (this.noticiaAtual) {
            this.abrirModal(this.noticiaAtual.id);
        }
    },

    fecharModal() {
        const modal = document.getElementById('modalDetalhes');
        if (!modal) return;
        modal.classList.remove('visivel');
        document.body.style.overflow = '';
        document.querySelectorAll('.modal-midia video').forEach(v => v.pause());
        setTimeout(() => { this.pausado = false; }, 100);
    },

    configurarEventos() {
        const areaSlider = document.getElementById('areaSlider');
        areaSlider?.addEventListener('mouseenter', () => { this.pausado = true; });
        areaSlider?.addEventListener('mouseleave', () => { this.pausado = false; });
    },

    configurarModalEventos() {
        const modal = document.getElementById('modalDetalhes');
        const btnFechar = document.querySelector('.btn-fechar-modal');
        btnFechar?.addEventListener('click', () => this.fecharModal());
        modal?.addEventListener('click', (e) => { if (e.target === modal) this.fecharModal(); });
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') this.fecharModal(); });
    }
};

// Limitar texto
function limitarTexto(texto, limite) {
    if (!texto) return '';
    if (texto.length <= limite) return texto;
    return texto.substring(0, limite).trim() + '...';
}

// Escapar HTML
function escapeHtml(texto) {
    if (!texto) return '';
    const div = document.createElement('div');
    div.textContent = texto;
    return div.innerHTML;
}

function abrirModalNoticia(id) {
    noticiaController.abrirModal(id);
}

function abrirModalNoticiaDestaque() {
    noticiaController.abrirModalDestaque();
}

function fecharModalNoticia() {
    noticiaController.fecharModal();
}

// ===== INICIALIZAÇÃO =====
document.addEventListener('DOMContentLoaded', function() {
    noticiaController.init();
    initNotificacoesNoticiasPagina();
});
    

    </script>
</body>
</html>
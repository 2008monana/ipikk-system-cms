<?php
/**
 * Página Galeria - IPIKK
 * Com categorias da tabela categorias_galeria
 */

require_once '../config/index.php';

// Buscar configurações do site
$config = getDB()->query("SELECT * FROM configuracoes WHERE id = 1")->fetch();

// Buscar conteúdo da página (JSON da tabela conteudo_paginas)
$pagina = getPagina('galeria');

// ============================================
// BUSCAR CATEGORIAS DA GALERIA
// ============================================

// Buscar categorias da tabela categorias_galeria
$categorias = getDB()->query("
    SELECT id, nome, slug, cor_classe, icone, ordem 
    FROM categorias_galeria 
    WHERE ativo = 1 
    ORDER BY ordem
")->fetchAll();

// ============================================
// BUSCAR MÍDIAS DA GALERIA
// ============================================
$midias = getDB()->query("
    SELECT g.*, c.nome as categoria_nome, c.slug as categoria_slug, c.cor_classe 
    FROM galeria g 
    JOIN categorias_galeria c ON g.categoria_id = c.id 
    WHERE g.ativo = 1 
    ORDER BY g.ordem, g.created_at DESC
")->fetchAll();

// Não usar placeholders quando não há conteúdos
$sem_midias = empty($midias);

// Extrair dados da página
$titulo_pagina = $pagina['titulo'] ?? 'Galeria';
$subtitulo_pagina = $pagina['subtitulo'] ?? 'Conheça as imagens e vídeos do nosso instituto';

// Verificar status das inscrições para o botão de matrícula
$status_inscricoes = getDB()->query("SELECT status FROM controle_inscricoes WHERE id = 1")->fetch();
$link_inscricao = ($status_inscricoes && $status_inscricoes['status'] === 'abertas') ? 'inscricoes.php' : 'inscricoes-indisponiveis.php';

// Mapeamento de cores das categorias (para uso no JavaScript)
$cores_categorias_json = [];
foreach ($categorias as $cat) {
    $cores_categorias_json[$cat['slug']] = $cat['cor_classe'] ?? '#003072';
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IPIKK - <?= htmlspecialchars($titulo_pagina) ?></title>
    
    <!-- Fontes e Ícones -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <!-- Favicon -->
    <link href="<?= $config['favicon_url'] ?? 'foto/ipikk_new_logo.png' ?>" rel="icon">
    
    <!-- CSS Header/Footer Padrão -->
    <link rel="stylesheet" href="css/header-footer.css">
    
    <style>
        /* ===== VARIÁVEIS ===== */
        :root {
            --azul-principal: <?= $config['cor_primaria'] ?? '#003072' ?>;
            --azul-claro: <?= $config['cor_azul_claro'] ?? '#2e86c1' ?>;
            --azul-escuro: <?= $config['cor_azul_escuro'] ?? '#001a40' ?>;
            --verde-acento: <?= $config['cor_verde_acento'] ?? '#0a9396' ?>;
            --branco: #ffffff;
            --cinza-claro: #f8f9fa;
            --cinza: #6c757d;
            --texto-principal: #2c3e50;
            --transicao: all 0.3s ease;
            --borda-raio: 12px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background: var(--cinza-claro);
            color: var(--texto-principal);
            line-height: 1.6;
        }

        /* ===== CABEÇALHO DA PÁGINA ===== */
        .cabecalho-pagina {
            max-width: 1200px;
            margin: 40px auto 0;
            padding: 0 20px;
            text-align: center;
        }

        .titulo-pagina {
            font-family: 'Poppins', sans-serif;
            font-size: 2.5rem;
            color: var(--azul-principal);
            margin-bottom: 15px;
        }

        .subtitulo-pagina {
            font-size: 1.1rem;
            color: var(--cinza);
            max-width: 700px;
            margin: 0 auto 20px;
        }

        .linha-decorativa-titulo {
            width: 80px;
            height: 4px;
            background: linear-gradient(90deg, var(--azul-principal), var(--verde-acento));
            margin: 0 auto 30px;
            border-radius: 2px;
        }

        /* ===== FILTRO COM CSS MELHORADO ===== */
        .container-filtro {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
            display: flex;
            justify-content: flex-end;
        }

        .filtro-select {
            padding: 14px 28px;
            padding-right: 50px;
            border: 2px solid #e0e6ed;
            border-radius: 40px;
            font-family: 'Montserrat', sans-serif;
            font-size: 0.9rem;
            background: var(--branco);
            cursor: pointer;
            transition: var(--transicao);
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%230a9396' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 20px center;
        }

        .filtro-select:hover {
            border-color: var(--verde-acento);
            background-color: #f8fafc;
        }

        .filtro-select:focus {
            outline: none;
            border-color: var(--verde-acento);
            box-shadow: 0 0 0 3px rgba(10, 147, 150, 0.15);
        }

        /* ===== GALERIA ===== */
        .galeria {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 25px;
        }

        .item-galeria {
            background: var(--branco);
            border-radius: var(--borda-raio);
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: var(--transicao);
            cursor: pointer;
        }

        .item-galeria:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }

        .envoltorio-midia {
            position: relative;
            height: 220px;
            overflow: hidden;
        }

        .envoltorio-midia img,
        .envoltorio-midia video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .item-galeria:hover .envoltorio-midia img,
        .item-galeria:hover .envoltorio-midia video {
            transform: scale(1.08);
        }

        .sobreposicao-midia {
            position: absolute;
            inset: 0;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            opacity: 0;
            transition: var(--transicao);
        }

        .item-galeria:hover .sobreposicao-midia {
            opacity: 1;
        }

        .botao-acao {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: var(--branco);
            border: none;
            cursor: pointer;
            font-size: 1.2rem;
            transition: var(--transicao);
        }

        .botao-acao:hover {
            background: var(--verde-acento);
            color: var(--branco);
            transform: scale(1.1);
        }

        .indicador-video {
            position: absolute;
            bottom: 15px;
            right: 15px;
            width: 40px;
            height: 40px;
            background: rgba(0,0,0,0.7);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--branco);
            font-size: 1rem;
        }

        .badge-categoria {
            position: absolute;
            top: 15px;
            left: 15px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--branco);
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .legenda-midia {
            padding: 12px 15px;
            font-size: 0.85rem;
            color: var(--cinza);
            text-align: center;
        }

        /* ===== LIGHTBOX ===== */
        .lightbox {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.95);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }

        .lightbox.ativo {
            display: flex;
        }

        .conteudo-lightbox {
            position: relative;
            max-width: 90%;
            max-height: 90%;
        }

        .fechar-lightbox {
            position: absolute;
            top: -50px;
            right: 0;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--branco);
            border: none;
            cursor: pointer;
            font-size: 1.2rem;
        }

        .seta-lightbox {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            border: none;
            cursor: pointer;
            color: var(--branco);
            font-size: 1.3rem;
        }

        .seta-esquerda { left: -60px; }
        .seta-direita { right: -60px; }

        .conteudo-midia-lightbox img,
        .conteudo-midia-lightbox video {
            max-width: 100%;
            max-height: 85vh;
            border-radius: 8px;
        }

        .legenda-lightbox {
            position: absolute;
            bottom: -40px;
            left: 0;
            right: 0;
            text-align: center;
            color: var(--branco);
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .galeria { grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); }
            .seta-esquerda { left: -45px; }
            .seta-direita { right: -45px; }
            .filtro-select {
                padding: 12px 20px;
                padding-right: 45px;
                background-position: right 16px center;
            }
        }

        @media (max-width: 576px) {
            .galeria { grid-template-columns: 1fr; }
            .seta-esquerda { left: 5px; }
            .seta-direita { right: 5px; }
            .fechar-lightbox { top: -45px; right: 5px; }
            .container-filtro { justify-content: center; }
            .filtro-select { width: 100%; max-width: 280px; }
        }
    </style>
</head>
<body>

<?php include __DIR__ . '/includes/header.php'; ?>

    <!-- ===== CONTEÚDO PRINCIPAL ===== -->
    <section class="cabecalho-pagina">
        <h1 class="titulo-pagina"><?= htmlspecialchars($titulo_pagina) ?></h1>
        <p class="subtitulo-pagina"><?= htmlspecialchars($subtitulo_pagina) ?></p>
        <div class="linha-decorativa-titulo"></div>
    </section>

    <!-- FILTRO -->
    <?php if (!$sem_midias): ?>
    <div class="container-filtro">
        <select class="filtro-select" id="filtroSelect">
            <option value="todos">Todas as Mídias</option>
            <?php foreach($categorias as $cat): ?>
            <option value="<?= $cat['slug'] ?>"><?= htmlspecialchars($cat['nome']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>

    <!-- GRADE DA GALERIA -->
    <?php if ($sem_midias): ?>
    <div class="galeria" id="galeria" style="display:grid;">
        <div style="grid-column:1/-1; text-align:center; background:#fff; padding:70px 20px; border-radius:12px;">
            <i class="fas fa-images" style="font-size:3rem; color:var(--cinza);"></i>
            <h3 style="margin-top:15px; color:var(--azul-principal);">Sem fotos na galeria no momento</h3>
        </div>
    </div>
    <?php else: ?>
    <div class="galeria" id="galeria"></div>
    <?php endif; ?>

    <!-- LIGHTBOX -->
    <div class="lightbox" id="lightbox">
        <div class="conteudo-lightbox">
            <button class="fechar-lightbox" id="fecharLightbox"><i class="fas fa-times"></i></button>
            <button class="seta-lightbox seta-esquerda" id="setaAnterior"><i class="fas fa-chevron-left"></i></button>
            <div class="conteudo-midia-lightbox" id="conteudoLightbox"></div>
            <button class="seta-lightbox seta-direita" id="setaProximo"><i class="fas fa-chevron-right"></i></button>
            <div class="legenda-lightbox" id="legendaLightbox"></div>
        </div>
    </div>

    <!-- ===== BOTÕES FLUTUANTES ===== -->
    <div class="botoes-flutuantes">
        <button class="botao-flutuante" id="botaoTopo" title="Voltar ao topo">
            <i class="fas fa-chevron-up"></i>
        </button>
        <?php if($config['whatsapp_numero']): ?>
        <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $config['whatsapp_numero']) ?>" class="botao-flutuante whatsapp" target="_blank"><i class="fab fa-whatsapp"></i></a>
        <?php endif; ?>
    </div>

<?php include __DIR__ . '/includes/footer.php'; ?>

    <script src="js/header-footer.js"></script>
    <script>
        // Dados da galeria com caminhos corrigidos
        const galeriaItensRaw = <?= json_encode($midias) ?>;
        
        // Cores das categorias (vindas do PHP)
        const coresCategorias = <?= json_encode($cores_categorias_json) ?>;
        
        // Mapeamento de ícones para categorias
        const iconesCategorias = {
            'fotos-gerais': 'fa-images',
            'tecnico-obras': 'fa-helmet-safety',
            'desenhador-projectista': 'fa-edit',
            'energia-instalacoes': 'fa-bolt',
            'frio-climatizacao': 'fa-snowflake',
            'gestao-sistemas': 'fa-server',
            'tecnico-informatica': 'fa-laptop-code',
            'tecnologias-moveis': 'fa-couch'
        };
        
        // Corrigir caminhos das URLs para arquivos locais
        const galeriaItens = galeriaItensRaw.map(item => {
            let urlCorrigida = item.url;
            if (urlCorrigida && !urlCorrigida.startsWith('http://') && !urlCorrigida.startsWith('https://')) {
                urlCorrigida = '../' + urlCorrigida.replace(/^\/+/, '');
            }
            return { ...item, url: urlCorrigida };
        });
        
        // ===== CONTROLLER DA GALERIA =====
        let itensVisiveis = [];
        let indiceAtual = 0;
        
        function getCorCategoria(categoriaSlug) {
            return coresCategorias[categoriaSlug] || '#003072';
        }
        
        function getIconeCategoria(categoriaSlug) {
            return iconesCategorias[categoriaSlug] || 'fa-tag';
        }
        
        function renderizarGaleria(categoria) {
            const container = document.getElementById('galeria');
            if (!container) return;
            
            itensVisiveis = categoria === 'todos' 
                ? galeriaItens 
                : galeriaItens.filter(item => item.categoria_slug === categoria);
            
            if (itensVisiveis.length === 0) {
                container.innerHTML = `
                    <div style="grid-column:1/-1; text-align:center; padding:60px; background:#fff; border-radius:12px;">
                        <i class="fas fa-images" style="font-size:3rem; color:#ccc;"></i>
                        <p style="margin-top:15px;">Nenhuma mídia encontrada nesta categoria.</p>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = itensVisiveis.map((item, index) => {
                const corCategoria = getCorCategoria(item.categoria_slug);
                const corMaisEscura = corCategoria === '#003072' ? '#001a40' : corCategoria;
                
                return `
                <div class="item-galeria" data-index="${index}" data-tipo="${item.tipo}" data-url="${item.url}" data-legenda="${escapeHtml(item.legenda)}">
                    <div class="envoltorio-midia">
                        ${item.tipo === 'imagem' 
                            ? `<img src="${item.url}" alt="${escapeHtml(item.legenda)}" loading="lazy">`
                            : `<video src="${item.url}" loading="lazy" muted></video>`
                        }
                        <div class="sobreposicao-midia">
                            <button class="botao-acao botao-expandir" title="Ver em tamanho completo"><i class="fas fa-expand"></i></button>
                            ${item.tipo === 'imagem' ? `<button class="botao-acao botao-baixar" title="Baixar foto"><i class="fas fa-download"></i></button>` : ''}
                        </div>
                        ${item.tipo === 'video' ? '<div class="indicador-video"><i class="fas fa-play"></i></div>' : ''}
                        <span class="badge-categoria" style="background: linear-gradient(135deg, ${corCategoria}, ${corMaisEscura});">
                            <i class="fas ${getIconeCategoria(item.categoria_slug)}"></i> ${escapeHtml(item.categoria_nome)}
                        </span>
                    </div>
                    <div class="legenda-midia">${escapeHtml(item.legenda)}</div>
                </div>`;
            }).join('');
            
            configurarBotoesAcao();
        }
        
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function configurarBotoesAcao() {
            document.querySelectorAll('.botao-expandir').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const item = btn.closest('.item-galeria');
                    if (item) {
                        indiceAtual = parseInt(item.dataset.index);
                        abrirLightbox();
                    }
                });
            });
            
            document.querySelectorAll('.botao-baixar').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const item = btn.closest('.item-galeria');
                    if (item) {
                        const url = item.dataset.url;
                        const legenda = item.dataset.legenda;
                        fetch(url)
                            .then(response => response.blob())
                            .then(blob => {
                                const link = document.createElement('a');
                                link.href = URL.createObjectURL(blob);
                                link.download = legenda.replace(/[^a-z0-9]/gi, '_').toLowerCase() + '.jpg';
                                link.click();
                                URL.revokeObjectURL(link.href);
                            })
                            .catch(() => window.open(url, '_blank'));
                    }
                });
            });
            
            document.querySelectorAll('.item-galeria').forEach(item => {
                const wrapper = item.querySelector('.envoltorio-midia');
                if (wrapper) {
                    wrapper.addEventListener('click', (e) => {
                        if (e.target.closest('.botao-acao')) return;
                        indiceAtual = parseInt(item.dataset.index);
                        abrirLightbox();
                    });
                }
            });
        }
        
        function abrirLightbox() {
            const lightbox = document.getElementById('lightbox');
            const conteudo = document.getElementById('conteudoLightbox');
            const legenda = document.getElementById('legendaLightbox');
            const item = itensVisiveis[indiceAtual];
            
            if (!item) return;
            
            if (item.tipo === 'imagem') {
                conteudo.innerHTML = `<img src="${item.url}" alt="${escapeHtml(item.legenda)}">`;
            } else {
                conteudo.innerHTML = `<video src="${item.url}" controls autoplay></video>`;
            }
            
            legenda.textContent = item.legenda;
            lightbox.classList.add('ativo');
            document.body.style.overflow = 'hidden';
        }
        
        function fecharLightbox() {
            const lightbox = document.getElementById('lightbox');
            lightbox.classList.remove('ativo');
            document.body.style.overflow = '';
            const video = document.querySelector('#conteudoLightbox video');
            if (video) video.pause();
        }
        
        function navegarLightbox(direcao) {
            if (itensVisiveis.length === 0) return;
            if (direcao === 'anterior') {
                indiceAtual = (indiceAtual - 1 + itensVisiveis.length) % itensVisiveis.length;
            } else {
                indiceAtual = (indiceAtual + 1) % itensVisiveis.length;
            }
            const item = itensVisiveis[indiceAtual];
            const conteudo = document.getElementById('conteudoLightbox');
            const legenda = document.getElementById('legendaLightbox');
            
            if (item.tipo === 'imagem') {
                conteudo.innerHTML = `<img src="${item.url}" alt="${escapeHtml(item.legenda)}">`;
            } else {
                conteudo.innerHTML = `<video src="${item.url}" controls autoplay></video>`;
            }
            legenda.textContent = item.legenda;
        }
        
        // Eventos
        document.getElementById('filtroSelect')?.addEventListener('change', function(e) {
            renderizarGaleria(e.target.value);
        });
        
        document.getElementById('fecharLightbox')?.addEventListener('click', fecharLightbox);
        document.getElementById('setaAnterior')?.addEventListener('click', () => navegarLightbox('anterior'));
        document.getElementById('setaProximo')?.addEventListener('click', () => navegarLightbox('proximo'));
        document.getElementById('lightbox')?.addEventListener('click', function(e) {
            if (e.target === this) fecharLightbox();
        });
        document.addEventListener('keydown', function(e) {
            if (!document.getElementById('lightbox').classList.contains('ativo')) return;
            if (e.key === 'Escape') fecharLightbox();
            if (e.key === 'ArrowLeft') navegarLightbox('anterior');
            if (e.key === 'ArrowRight') navegarLightbox('proximo');
        });
        
        // Inicializar
        document.addEventListener('DOMContentLoaded', function() {
            renderizarGaleria('todos');
        });
    </script>
</body>
</html>
<?php
/**
 * Página Perfil do Director - IPIKK
 */

require_once '../config/index.php';

// Buscar configurações do site
$config = getDB()->query("SELECT * FROM configuracoes WHERE id = 1")->fetch();

// Buscar conteúdo da página (JSON da tabela conteudo_paginas)
$pagina = getPagina('director');

// Extrair dados do Director (com fallbacks)
$director = [
    'nome' => $pagina['nome'] ?? 'Ferreira Manuel Fragoso',
    'cargo' => $pagina['cargo'] ?? 'Director Geral do IPIKK',
    'foto' => $pagina['foto'] ?? 'foto/perfil-do-director.jpg',
    'data_nascimento' => $pagina['data_nascimento'] ?? '27 de Julho de 1971',
    'naturalidade' => $pagina['naturalidade'] ?? 'Cacongo, Cabinda',
    'experiencia' => $pagina['experiencia'] ?? '30+ Anos na Educação',
    'inicio_cargo' => $pagina['inicio_cargo'] ?? '17 de Outubro de 2018',
    'resumo' => $pagina['resumo'] ?? 'Profissional com vasta experiência na gestão educacional e coordenação pedagógica. Com um histórico sólido em liderança institucional, desde o ensino de base ao superior, dedica-se ao desenvolvimento de infraestruturas e capacitação de recursos humanos para elevar a qualidade do ensino em Angola.',
    'citacao' => $pagina['citacao'] ?? 'A educação é a base para a construção de uma sociedade sólida e o pilar para o desenvolvimento de cada indivíduo.',
    'formacoes' => $pagina['formacoes'] ?? [],
    'experiencias' => $pagina['experiencias'] ?? [],
    'realizacoes' => $pagina['realizacoes'] ?? [],
    'idiomas' => $pagina['idiomas'] ?? ['Português (Nativo)', 'Espanhol (Intermédio)', 'Francês (Noções)']
];

// Fallback para formações se não existirem no JSON
if (empty($director['formacoes'])) {
    $director['formacoes'] = [
        ['curso' => 'Doutorando em Sociologia', 'instituicao' => '"ESCOL SOCIETY & MINEDUC" no ISCED – Luanda', 'periodo' => 'Em curso', 'detalhe' => 'Em curso'],
        ['curso' => 'Pós-Graduação em Pedagogia (Gestão e Organização)', 'instituicao' => 'Instituto de Ciências da Educação – ISCED Lubango', 'periodo' => '2014 - 2016', 'detalhe' => 'Concluído'],
        ['curso' => 'Licenciatura em Ciências da Educação', 'instituicao' => 'Universidade Federal do Maranhão - Brasil', 'periodo' => '2014', 'detalhe' => 'Concluído em 2014'],
        ['curso' => 'Bacharel em Ciências Pedagógicas', 'instituicao' => 'Universidade de Ciências Pedagógicas de Havana, Cuba (Enafa)', 'periodo' => '2001', 'detalhe' => 'Concluído em 2001 (Classificação: 18 valores)']
    ];
}

// Fallback para experiências se não existirem no JSON
if (empty($director['experiencias'])) {
    $director['experiencias'] = [
        ['periodo' => '2018 - Pres.', 'cargo' => 'Director Geral', 'local' => 'Instituto Politécnico Industrial do Kilamba Kiaxi (Governo Provincial de Luanda)'],
        ['periodo' => '2017', 'cargo' => 'Secretário Geral', 'local' => 'Direcção de Coordenação Institucional e Integração Ministerial (MESCTI)'],
        ['periodo' => '2016', 'cargo' => 'Chefe de Gabinete', 'local' => 'Coordenação Institucional e Integração Ministerial (MESCTI)'],
        ['periodo' => '2013 - 2016', 'cargo' => 'Chefe de Departamento', 'local' => 'Recursos Humanos, Técnicos e Infra-estruturas da Direcção Nacional do Ensino Superior'],
        ['periodo' => '2011 - 2012', 'cargo' => 'Professor Auxiliar', 'local' => 'Orientações Educacional no ISCED – Lubango'],
        ['periodo' => '2008 - 2011', 'cargo' => 'Director Escolar', 'local' => 'Escola de Formação de Professores (EMED/ESCOPH) e Escola Primária nº 2028 (Huambo)']
    ];
}

// Fallback para realizações se não existirem no JSON
if (empty($director['realizacoes'])) {
    $director['realizacoes'] = [
        'Autor Aprovado (2008): Manuais de Administração Escolar e Ética no ISCED – Luanda.',
        'Vice-Decano (2008–2010): ISCED-Huambo. Assessor Técnico da Cátedra UNESCO da UAN.',
        'Revisor Técnico (2008): Planos de Estudo dos Cursos de Engenharia (Informática).',
        'Membro SADC (2007): Comissão de Recursos Humanos, Ciência e Tecnologia.',
        'Educação para Todos (2004): Membro da Comissão do Plano Integrado (Aliança Estratégica).',
        'Certificação (2006): Titular da Carteira de Professor do Ensino Secundário (Reconvertido em Público).'
    ];
}

// Verificar status das inscrições para o botão de matrícula
$status_inscricoes = getDB()->query("SELECT status FROM controle_inscricoes WHERE id = 1")->fetch();
$link_inscricao = ($status_inscricoes && $status_inscricoes['status'] === 'abertas') ? 'inscricoes.php' : 'inscricoes-indisponiveis.php';
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IPIKK - Perfil do Diretor</title>
    
    <!-- Fontes e Ícones -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <!-- Favicon -->
    <link href="<?= $config['favicon_url'] ?? 'foto/ipikk_new_logo.png' ?>" rel="icon">
    
    <!-- CSS Header/Footer Padrão -->
    <link rel="stylesheet" href="css/header-footer.css">
    
    <style>
        /* Resets e Configuração Base */
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    font-family: 'Roboto', sans-serif;
    background-color: #f0f4f8; 
    color: #333;
    line-height: 1.6;
}

.perfil-section {
    max-width: 900px;
    margin: 40px auto;
    padding: 0 20px;
}

/* --- Cabeçalho --- */
.header-container {
    text-align: center;
    margin-bottom: 40px;
}

.header-container h1 {
    color: #003366; 
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 10px;
}

.linha-destaque {
    width: 80px;
    height: 4px;
    background-color: #008080; 
    margin: 0 auto 15px auto;
    border-radius: 2px;
}

.subtitulo {
    color: #666;
    max-width: 600px;
    margin: 0 auto;
    font-size: 1rem;
}

/* --- Card Principal (BIO) --- */
.bio-card {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
    display: flex;
    flex-wrap: wrap;
    padding: 30px;
    gap: 30px;
    margin-bottom: 40px;
    align-items: flex-start;
    border-top: 5px solid #008080; 
}

.bio-foto {
    flex: 0 0 250px;
    height: 300px;
    border-radius: 12px;
    overflow: hidden;
    background-color: #ddd;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}

.bio-foto img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.bio-info {
    flex: 1;
    min-width: 300px;
}

.nome-director {
    color: #003366;
    font-size: 1.8rem;
    margin-bottom: 5px;
}

.cargo-badge {
    display: inline-block;
    background-color: #e0f2f1;
    color: #00695c;
    padding: 5px 12px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.9rem;
    margin-bottom: 20px;
}

.resumo-profissional {
    color: #555;
    margin-bottom: 30px;
    font-size: 1rem;
}

/* Grid de Estatísticas */
.grid-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 15px;
}

.stat-box {
    border-left: 3px solid #003366;
    padding-left: 15px;
}

.stat-label {
    display: block;
    font-size: 0.75rem;
    text-transform: uppercase;
    color: #888;
    letter-spacing: 0.5px;
    margin-bottom: 4px;
}

.stat-value {
    display: block;
    font-size: 0.95rem;
    font-weight: 500;
    color: #222;
}

/* --- Sistema de Abas (Tabs) --- */
.tabs-container {
    display: flex;
    gap: 15px;
    margin-bottom: 20px;
    overflow-x: auto;
    padding-bottom: 5px;
}

.tab-btn {
    background: #fff;
    border: 1px solid #ddd;
    padding: 12px 25px;
    border-radius: 8px;
    cursor: pointer;
    font-family: inherit;
    font-weight: 600;
    color: #555;
    display: flex;
    align-items: center;
    gap: 10px;
    transition: all 0.3s ease;
    white-space: nowrap;
}

.tab-btn svg {
    width: 18px;
    height: 18px;
}

.tab-btn:hover {
    background-color: #f9f9f9;
    border-color: #bbb;
}

.tab-btn.active {
    background-color: #003366;
    color: #fff;
    border-color: #003366;
    box-shadow: 0 4px 10px rgba(0, 51, 102, 0.3);
}

/* --- Conteúdo das Abas --- */
.content-container {
    background: #fff;
    border-radius: 16px;
    padding: 30px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
    min-height: 400px;
}

.tab-content {
    display: none; 
    opacity: 0;
    transition: opacity 0.4s ease-in;
}

/* Classe auxiliar para animação JS */
.active-content {
    display: block;
    opacity: 1;
}

.tab-title {
    color: #003366;
    border-bottom: 2px solid #f0f0f0;
    padding-bottom: 10px;
    margin-bottom: 25px;
}

.mt-4 { margin-top: 1.5rem; }

/* Estilo Item de Lista (Formação) */
.item-lista {
    display: flex;
    align-items: center;
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 15px;
    border-left: 4px solid #008080; 
    transition: transform 0.2s;
}

.item-lista:hover {
    transform: translateX(5px);
    background: #fff;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.icon-wrapper {
    background: #e0f2f1;
    color: #008080;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
    flex-shrink: 0;
}

.icon-wrapper svg { width: 20px; height: 20px; }

.info-wrapper h4 {
    font-size: 1rem;
    color: #333;
    margin-bottom: 3px;
}

.instituicao {
    display: block;
    font-size: 0.9rem;
    color: #666;
}

.detalhe {
    font-size: 0.8rem;
    color: #999;
    font-weight: 500;
}

/* Estilo Timeline (Experiência) */
.timeline-item {
    display: flex;
    margin-bottom: 20px;
    border-bottom: 1px solid #eee;
    padding-bottom: 15px;
}

.timeline-item:last-child { border-bottom: none; }

.time-date {
    min-width: 100px;
    font-weight: 700;
    color: #008080;
    font-size: 0.9rem;
    padding-top: 2px;
}

.time-content h4 {
    font-size: 1rem;
    color: #222;
}

.time-content p {
    font-size: 0.9rem;
    color: #666;
    margin-top: 2px;
}

/* Estilo Lista Simples & Tags */
.lista-realizacoes {
    list-style: none;
    padding-left: 10px;
}

.lista-realizacoes li {
    margin-bottom: 12px;
    position: relative;
    padding-left: 20px;
    font-size: 0.95rem;
    color: #444;
}

.lista-realizacoes li::before {
    content: "•";
    color: #008080;
    font-weight: bold;
    position: absolute;
    left: 0;
    top: 0;
    font-size: 1.2rem;
    line-height: 1.2;
}

.skills-grid {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.skill-tag {
    background: #eee;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.85rem;
    color: #555;
    font-weight: 500;
}

/* --- Citação Final --- */
.quote-container {
    text-align: center;
    margin-top: 60px;
    margin-bottom: 40px;
    padding: 0 40px;
}

.quote-text {
    font-family: 'Playfair Display', serif;
    font-style: italic;
    font-size: 1.2rem;
    color: #555;
    margin-bottom: 15px;
}

.quote-author {
    font-family: 'Dancing Script', cursive;
    font-size: 1.5rem;
    color: #008080;
}

/* --- Responsividade --- */
@media (max-width: 768px) {
    .bio-card {
        flex-direction: column;
        align-items: center;
        text-align: center;
    }

    .bio-foto {
        width: 100%;
        max-width: 200px;
        height: 200px;
    }

    .stat-box {
        border-left: none;
        border-bottom: 1px solid #eee;
        padding-bottom: 10px;
    }
    
    .timeline-item {
        flex-direction: column;
    }
    
    .time-date {
        margin-bottom: 5px;
    }
    
    .header-container h1 {
        font-size: 1.8rem;
    }
}
    </style>
    
</head>
<body>

<?php include __DIR__ . '/includes/header.php'; ?>

    <!-- ===== CONTEÚDO PRINCIPAL ===== -->
    <section class="perfil-section">
        
        <!-- Cabeçalho da Página -->
        <div class="header-container">
            <h1>Perfil do Director Geral</h1>
            <div class="linha-destaque"></div>
            <p class="subtitulo">Conheça a trajetória, qualificações e visão de liderança comprometida com a excelência educacional.</p>
        </div>

        <!-- CARTÃO PRINCIPAL (BIO) -->
        <div class="bio-card">
            <div class="bio-foto">
                <img src="<?= htmlspecialchars($director['foto']) ?>" alt="<?= htmlspecialchars($director['nome']) ?>" onerror="this.src='https://via.placeholder.com/300x350?text=Foto+Director'">
            </div>
            
            <div class="bio-info">
                <h2 class="nome-director"><?= htmlspecialchars($director['nome']) ?></h2>
                <span class="cargo-badge"><?= htmlspecialchars($director['cargo']) ?></span>
                
                <p class="resumo-profissional"><?= nl2br(htmlspecialchars($director['resumo'])) ?></p>

                <div class="grid-stats">
                    <div class="stat-box">
                        <span class="stat-label">Data de Nascimento</span>
                        <span class="stat-value"><?= htmlspecialchars($director['data_nascimento']) ?></span>
                    </div>
                    <div class="stat-box">
                        <span class="stat-label">Naturalidade</span>
                        <span class="stat-value"><?= htmlspecialchars($director['naturalidade']) ?></span>
                    </div>
                    <div class="stat-box">
                        <span class="stat-label">Experiência</span>
                        <span class="stat-value"><?= htmlspecialchars($director['experiencia']) ?></span>
                    </div>
                    <div class="stat-box">
                        <span class="stat-label">Início no Cargo</span>
                        <span class="stat-value"><?= htmlspecialchars($director['inicio_cargo']) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- NAVEGAÇÃO DE ABAS (TABS) -->
        <div class="tabs-container">
            <button class="tab-btn active" onclick="openTab(event, 'qualificacoes')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                Formação Académica
            </button>
            <button class="tab-btn" onclick="openTab(event, 'experiencia')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                Experiência Profissional
            </button>
            <button class="tab-btn" onclick="openTab(event, 'realizacoes')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="7"/><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/></svg>
                Realizações & Outros
            </button>
        </div>

        <!-- CONTEÚDO DAS ABAS -->
        <div class="content-container">

            <!-- ABA 1: QUALIFICAÇÕES -->
            <div id="qualificacoes" class="tab-content active-content">
                <h3 class="tab-title">Formação Académica</h3>
                
                <?php foreach($director['formacoes'] as $formacao): ?>
                <div class="item-lista">
                    <div class="icon-wrapper">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/></svg>
                    </div>
                    <div class="info-wrapper">
                        <h4><?= htmlspecialchars($formacao['curso']) ?></h4>
                        <span class="instituicao"><?= htmlspecialchars($formacao['instituicao']) ?></span>
                        <span class="detalhe"><?= htmlspecialchars($formacao['detalhe'] ?? $formacao['periodo']) ?></span>
                    </div>
                </div>
                <?php endforeach; ?>

                <h3 class="tab-title mt-4">Idiomas</h3>
                <div class="skills-grid">
                    <?php foreach($director['idiomas'] as $idioma): ?>
                    <span class="skill-tag"><?= htmlspecialchars($idioma) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- ABA 2: EXPERIÊNCIA -->
            <div id="experiencia" class="tab-content">
                <h3 class="tab-title">Trajectória Profissional</h3>
                
                <?php foreach($director['experiencias'] as $exp): ?>
                <div class="timeline-item">
                    <div class="time-date"><?= htmlspecialchars($exp['periodo']) ?></div>
                    <div class="time-content">
                        <h4><?= htmlspecialchars($exp['cargo']) ?></h4>
                        <p><?= htmlspecialchars($exp['local']) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- ABA 3: REALIZAÇÕES -->
            <div id="realizacoes" class="tab-content">
                <h3 class="tab-title">Outras Informações e Publicações</h3>
                
                <ul class="lista-realizacoes">
                    <?php foreach($director['realizacoes'] as $realizacao): ?>
                    <li><?= htmlspecialchars($realizacao) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>

        </div>

        <!-- CITAÇÃO FINAL -->
        <div class="quote-container">
            <p class="quote-text">"<?= htmlspecialchars($director['citacao']) ?>"</p>
            <p class="quote-author"><?= htmlspecialchars($director['nome']) ?></p>
        </div>

    </section>

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
    <script>
        function openTab(evt, tabName) {
            var i, tabContent, tabBtn;
            
            // Esconder todo conteúdo
            tabContent = document.getElementsByClassName("tab-content");
            for (i = 0; i < tabContent.length; i++) {
                tabContent[i].style.display = "none";
                tabContent[i].classList.remove("active-content");
            }
            
            // Remover classe ativa dos botões
            tabBtn = document.getElementsByClassName("tab-btn");
            for (i = 0; i < tabBtn.length; i++) {
                tabBtn[i].className = tabBtn[i].className.replace(" active", "");
            }
            
            // Mostrar o atual
            document.getElementById(tabName).style.display = "block";
            
            // Pequeno delay para animação de opacidade funcionar
            setTimeout(() => {
                document.getElementById(tabName).classList.add("active-content");
            }, 10);
            
            evt.currentTarget.className += " active";
        }
        
        // Inicializar a primeira aba visível
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('qualificacoes').style.display = 'block';
        });
    </script>
</body>
</html>
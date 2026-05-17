<?php
/**
 * Página Percurso (Histórias de Sucesso) - IPIKK
 * Exibe histórias de alumni com modal de detalhes
 */

require_once '../config/index.php';

// Buscar configurações do site
$config = getDB()->query("SELECT * FROM configuracoes WHERE id = 1")->fetch();

// Buscar conteúdo da página (JSON da tabela conteudo_paginas)
$pagina = getPagina('percurso');

// ============================================
// BUSCAR ALUMNI ATIVOS DO BANCO DE DADOS COM JOIN
// ============================================
$alumni = getDB()->query("
    SELECT a.*, c.nome as curso_nome
    FROM alumni a
    LEFT JOIN cursos c ON a.curso_id = c.id
    WHERE a.ativo = 1
    ORDER BY a.destaque DESC, a.ordem, a.ano_conclusao DESC
")->fetchAll();

// Extrair dados da página (com fallbacks)
$hero = $pagina['hero'] ?? [
    'titulo' => 'Histórias de Sucesso',
    'descricao' => '<p><strong>Transformando vidas desde 2009.</strong> O compromisso do IPIKK-NV vai além da sala de aula: formamos profissionais qualificados e cidadãos preparados para impulsionar o desenvolvimento sustentável de Angola.</p><p>Orgulhamo-nos dos nossos antigos alunos que hoje ocupam posições de destaque em grandes empresas e instituições públicas.</p>'
];

// Verificar status das inscrições para o botão de matrícula
$status_inscricoes = getDB()->query("SELECT status FROM controle_inscricoes WHERE id = 1")->fetch();
$link_inscricao = ($status_inscricoes && $status_inscricoes['status'] === 'abertas') ? 'inscricoes.php' : 'inscricoes-indisponiveis.php';
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IPIKK - Percurso</title>

    <!-- Fontes e Ícones -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Favicon -->
    <link href="<?= $config['favicon_url'] ?? 'foto/ipikk_new_logo.png' ?>" rel="icon">

    <!-- CSS Header/Footer Padrão -->
    <link rel="stylesheet" href="css/header-footer.css">

    <style>
        /*========= SECÇÃO PERCURSO ============*/
.container-percursos {
    max-width: 1000px;
    margin: 40px auto;
    padding: 0 20px;
}

/* --- Cabeçalho da Página --- */
.header-section { text-align: center; margin-bottom: 50px; }
.header-section h1 { color: #003366; font-size: 2.5rem; margin-bottom: 15px; }
.linha-destaque {
    width: 60px;
    height: 4px;
    background-color: #008080;
    margin: 0 auto 20px auto;
}
.intro-texto { max-width: 700px; margin: 0 auto; line-height: 1.6; color: #555; }
.intro-texto p { margin-bottom: 15px; }

/* --- Grid de Cartões (Botões de Abrir) --- */
.grid-alunos {
    display: flex;
    justify-content: center;
    gap: 30px;
    flex-wrap: wrap;
}

.card-aluno {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    padding: 20px;
    text-align: center;
    width: 280px;
    cursor: pointer;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border-top: 4px solid #008080;
}

.card-aluno:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}

.foto-wrapper img {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    object-fit: cover;
    margin-bottom: 15px;
    border: 3px solid #f0f0f0;
}

.info-resumo h3 { color: #003366; font-size: 1.1rem; margin-bottom: 5px; }
.curso-resumo { font-size: 0.9rem; color: #777; margin-bottom: 15px; }
.btn-ver-mais {
    background: transparent;
    border: 1px solid #008080;
    color: #008080;
    padding: 8px 20px;
    border-radius: 20px;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.3s;
}
.card-aluno:hover .btn-ver-mais { background: #008080; color: #fff; }

/* ============= ESTILOS DO MODAL (POP-UP) - BASEADO NA IMAGEM ============== */

/* Fundo Escuro (Backdrop) */
.modal-overlay {
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background-color: rgba(0, 0, 0, 0.6);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000;

    /* Estado inicial: Escondido */
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

/* Quando ativo (Via JS) */
.modal-overlay.active {
    opacity: 1;
    visibility: visible;
}

/* O Cartão Branco do Modal */
.modal-card {
    background: #fff;
    width: 90%;
    max-width: 500px;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 20px 50px rgba(0,0,0,0.3);
    transform: translateY(20px);
    transition: transform 0.3s ease;
    position: relative;
}

.modal-overlay.active .modal-card {
    transform: translateY(0);
}

/* Cabeçalho Gradiente  */
.modal-header {
    background: linear-gradient(135deg, #003366 0%, #008080 100%);
    padding: 20px 20px 60px 20px;
    color: #fff;
    position: relative;
    text-align: left;
}

.header-content h2 {
    font-size: 1.5rem;
    margin-bottom: 5px;
    font-weight: 700;
}

.subtitulo-modal {
    font-size: 0.9rem;
    opacity: 0.9;
    font-weight: 400;
    display: block;
}

/* Botão Fechar Circular (Estilo "X" em bolinha) */
.btn-fechar-circular {
    position: absolute;
    top: 20px;
    right: 20px;

    /* Tamanho do círculo */
    width: 32px;
    height: 32px;
    border-radius: 50%;
    border: none;

    /* Cor de fundo: Branco com 20% de opacidade (Efeito Vidro) */
    background-color: rgba(255, 255, 255, 0.2);

    /* Centralizar o X dentro da bolinha */
    display: flex;
    align-items: center;
    justify-content: center;

    cursor: pointer;
    transition: all 0.3s ease;
    z-index: 20;
}

.btn-fechar-circular:hover {
    background-color: rgba(255, 255, 255, 0.5);
    transform: rotate(90deg);
}

/* Ajuste do SVG interno para garantir cor */
.btn-fechar-circular svg path {
    stroke: #fff;
}

/* Corpo do Modal */
.modal-body {
    padding: 0 30px 30px 30px;
}

/* Foto sobreposta (Círculo) */
.modal-avatar {
    text-align: center;
    position: relative;
    margin-top: -50px;
    margin-bottom: 20px;
    z-index: 10;
}

.modal-avatar img {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    border: 5px solid #fff;
    box-shadow: 0 4px 10px rgba(0,0,0,0.2);
    background: #fff;
    object-fit: cover;
}

/* Lista de Informações */
.detalhes-lista {
    font-size: 0.95rem;
}

.detalhe-row {
    display: flex;
    margin-bottom: 15px;
    border-bottom: 1px solid #f0f0f0;
    padding-bottom: 10px;
}

.detalhe-row:last-child { border-bottom: none; }

.detalhe-row.descricao {
    flex-direction: column;
    align-items: flex-start;
}

.label {
    color: #003366;
    font-weight: 700;
    min-width: 140px;
}

.valor {
    color: #555;
    flex: 1;
}

.texto-corrido {
    margin-top: 5px;
    line-height: 1.6;
    text-align: justify;
    color: #444;
}

/* =========== RESPONSIVIDADE =================== */
@media (max-width: 500px) {
    .detalhe-row { flex-direction: column; }
    .label { margin-bottom: 4px; color: #008080; }
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

    <!-- ===== CONTEÚDO PRINCIPAL ===== -->
    <section class="container-percursos">

        <!-- Título e Introdução -->
        <div class="header-section">
            <h1><?= htmlspecialchars($hero['titulo']) ?></h1>
            <div class="linha-destaque"></div>
            <div class="intro-texto">
                <?= $hero['descricao'] ?>
            </div>
        </div>

        <!-- Grelha de Alunos -->
        <?php if (empty($alumni)): ?>
        <div class="mensagem-vazia">
            <i class="fas fa-info-circle"></i>
            Sem informação de histórias de sucesso no momento.
        </div>
        <?php else: ?>
        <div class="grid-alunos">
            <?php foreach($alumni as $aluno): ?>
            <div class="card-aluno" onclick="abrirModal('modal-<?= $aluno['id'] ?>')">
                <div class="foto-wrapper">
                    <img src="<?= htmlspecialchars($aluno['foto_url'] ?? 'foto/sem_foto.png') ?>" alt="<?= htmlspecialchars($aluno['nome']) ?>" onerror="this.src='foto/sem_foto.png'">
                </div>
                <div class="info-resumo">
                    <h3><?= htmlspecialchars($aluno['nome']) ?></h3>
                    <p class="curso-resumo"><?= htmlspecialchars($aluno['curso_nome'] ?? 'Curso não informado') ?></p>
                    <button class="btn-ver-mais">Ver Percurso</button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </section>

    <!-- Modals (gerados dinamicamente) -->
    <?php foreach($alumni as $aluno): ?>
    <div id="modal-<?= $aluno['id'] ?>" class="modal-overlay" onclick="fecharModal(event, 'modal-<?= $aluno['id'] ?>')">
        <div class="modal-card">
            <div class="modal-header">
                <div class="header-content">
                    <h2><?= htmlspecialchars($aluno['nome']) ?></h2>
                    <span class="subtitulo-modal"><?= htmlspecialchars($aluno['curso_nome'] ?? 'Curso não informado') ?></span>
                </div>
                <button class="btn-fechar-circular" onclick="fecharModalbotao('modal-<?= $aluno['id'] ?>')">
                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M13 1L1 13M1 1L13 13" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <div class="modal-avatar">
                    <img src="<?= htmlspecialchars($aluno['foto_url'] ?? 'foto/sem_foto.png') ?>" alt="<?= htmlspecialchars($aluno['nome']) ?>" onerror="this.src='foto/sem_foto.png'">
                </div>
                <div class="detalhes-lista">
                    <div class="detalhe-row">
                        <span class="label">Nome:</span>
                        <span class="valor"><?= htmlspecialchars($aluno['nome']) ?></span>
                    </div>
                    <div class="detalhe-row">
                        <span class="label">Curso:</span>
                        <span class="valor"><?= htmlspecialchars($aluno['curso_nome'] ?? 'Curso não informado') ?></span>
                    </div>
                    <div class="detalhe-row">
                        <span class="label">Ano de Conclusão:</span>
                        <span class="valor"><?= htmlspecialchars($aluno['ano_conclusao']) ?></span>
                    </div>
                    <?php if(!empty($aluno['cargo_atual'])): ?>
                    <div class="detalhe-row">
                        <span class="label">Cargo Atual:</span>
                        <span class="valor"><?= htmlspecialchars($aluno['cargo_atual']) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if(!empty($aluno['empresa'])): ?>
                    <div class="detalhe-row">
                        <span class="label">Empresa:</span>
                        <span class="valor"><?= htmlspecialchars($aluno['empresa']) ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="detalhe-row descricao">
                        <span class="label">Percurso Profissional:</span>
                        <p class="valor texto-corrido"><?= nl2br(htmlspecialchars($aluno['percurso_texto'])) ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

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
        // Função para abrir o modal
        function abrirModal(modalID) {
            const modal = document.getElementById(modalID);
            if (modal) {
                modal.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        }

        // Função para fechar pelo botão X
        function fecharModalbotao(modalID) {
            const modal = document.getElementById(modalID);
            if (modal) {
                modal.classList.remove('active');
                document.body.style.overflow = '';
            }
        }

        // Função para fechar clicando fora do cartão
        function fecharModal(event, modalID) {
            if (event.target.classList.contains('modal-overlay')) {
                fecharModalbotao(modalID);
            }
        }
    </script>
</body>
</html>

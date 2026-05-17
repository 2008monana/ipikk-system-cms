<?php
/**
 * Página Normativos - IPIKK
 * Exibe documentos institucionais (PDFs) para consulta
 */

require_once '../config/index.php';

// Buscar configurações do site
$config = getDB()->query("SELECT * FROM configuracoes WHERE id = 1")->fetch();

// Buscar conteúdo da página
$pagina = getPagina('normativos');
$descricao_pagina = $pagina['descricao_pagina'] ?? 'Aqui encontra todos os documentos normativos e institucionais do IPIKK.';

// Buscar documentos
$documentos = getDB()->query("
    SELECT * FROM documentos 
    WHERE categoria = 'normativos' AND ativo = 1 
    ORDER BY ordem
")->fetchAll();

// Função para obter URL correta da imagem
function getImagemUrl($imagem_url) {
    if (empty($imagem_url)) {
        return '../area-publica/foto/documento_padrao.jpg';
    }
    
    // URL externa
    if (strpos($imagem_url, 'http://') === 0 || strpos($imagem_url, 'https://') === 0) {
        return $imagem_url;
    }
    
    // Se já começa com area-publica/
    if (strpos($imagem_url, 'area-publica/') === 0) {
        return normalizarUrlMidia($imagem_url, '..');
    }
    
    // Se começa com uploads/documentos/
    if (strpos($imagem_url, 'uploads/documentos/') === 0) {
        return '../area-publica/' . $imagem_url;
    }
    
    // Se começa com uploads/
    if (strpos($imagem_url, 'uploads/') === 0) {
        return '../area-publica/' . $imagem_url;
    }
    
    return '../area-publica/foto/documento_padrao.jpg';
}

// Função para obter URL correta do PDF
function getPdfUrl($pdf_url) {
    if (empty($pdf_url)) {
        return '#';
    }
    
    if (strpos($pdf_url, 'http://') === 0 || strpos($pdf_url, 'https://') === 0) {
        return ajustarCloudinaryPdfUrl($pdf_url);
    }
    
    if (strpos($pdf_url, 'uploads/documentos/') === 0) {
        return normalizarUrlMidia($pdf_url, '..');
    }
    
    if (strpos($pdf_url, '../') === 0) {
        return ajustarCloudinaryPdfUrl($pdf_url);
    }
    
    return normalizarUrlMidia($pdf_url, '..');
}

$status_inscricoes = getDB()->query("SELECT status FROM controle_inscricoes WHERE id = 1")->fetch();
$link_inscricao = ($status_inscricoes && $status_inscricoes['status'] === 'abertas') ? 'inscricoes.php' : 'inscricoes-indisponiveis.php';
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IPIKK - Normativos</title>
    
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="<?= $config['favicon_url'] ?? 'foto/ipikk_new_logo.png' ?>" rel="icon">
    <link rel="stylesheet" href="css/header-footer.css">
    
    <style>
        .normativos-section {
            background: #f0f4f8;
            padding: 60px 24px 80px;
        }
        .normativos-wrapper {
            max-width: 1200px;
            margin: 0 auto;
        }
        .normativos-title {
            font-family: 'Poppins', sans-serif;
            font-size: 2.5rem;
            font-weight: 700;
            color: #003072;
            text-align: center;
            margin-bottom: 16px;
        }
        .normativos-divider {
            width: 70px;
            height: 4px;
            background: linear-gradient(90deg, #003072, #0a9396);
            margin: 0 auto 40px;
            border-radius: 2px;
        }
        .normativos-descricao {
            text-align: center;
            max-width: 700px;
            margin: 0 auto 40px;
            font-size: 1rem;
            color: #6c757d;
        }
        .normativos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 30px;
        }
        .normativos-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        .normativos-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 6px 22px rgba(0,0,0,0.13);
        }
        .card-imagem {
            position: relative;
            height: 180px;
            overflow: hidden;
            background: linear-gradient(135deg, #003072, #001a40);
        }
        .card-imagem img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        .normativos-card:hover .card-imagem img {
            transform: scale(1.05);
        }
        .pdf-icon-overlay {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(220,53,69,0.9);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }
        .card-conteudo {
            padding: 20px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .card-titulo {
            font-family: 'Poppins', sans-serif;
            font-size: 1.1rem;
            font-weight: 600;
            color: #003072;
            margin-bottom: 12px;
        }
        .card-descricao {
            font-size: 0.85rem;
            color: #6c757d;
            line-height: 1.6;
            margin-bottom: 20px;
            flex: 1;
        }
        .card-botao {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: linear-gradient(135deg, #0a9396, #003072);
            color: white;
            padding: 12px 20px;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            margin-top: 10px;
            width: 100%;
        }
        .card-botao:hover {
            transform: translateY(-2px);
            gap: 12px;
            box-shadow: 0 8px 20px rgba(10,147,150,0.3);
        }
        @media (max-width: 768px) {
            .normativos-grid { grid-template-columns: 1fr; }
            .normativos-title { font-size: 2rem; }
        }
    </style>
</head>
<body>

<?php include __DIR__ . '/includes/header.php'; ?>

<section class="normativos-section">
    <div class="normativos-wrapper">
        <h2 class="normativos-title">Normativos</h2>
        <div class="normativos-divider"></div>
        <p class="normativos-descricao"><?= htmlspecialchars($descricao_pagina) ?></p>
        
        <div class="normativos-grid">
            <?php foreach($documentos as $doc): 
                $imagem_url = getImagemUrl($doc['imagem_url'] ?? '');
                $pdf_url = getPdfUrl($doc['pdf_url'] ?? '');
            ?>
            <div class="normativos-card">
                <div class="card-imagem">
                    <img src="<?= htmlspecialchars($imagem_url) ?>" 
                         alt="<?= htmlspecialchars($doc['titulo']) ?>" 
                         onerror="this.src='../area-publica/foto/documento_padrao.jpg'">
                    <div class="pdf-icon-overlay">
                        <i class="fas fa-file-pdf"></i>
                    </div>
                </div>
                <div class="card-conteudo">
                    <h3 class="card-titulo"><?= htmlspecialchars($doc['titulo']) ?></h3>
                    <p class="card-descricao"><?= htmlspecialchars($doc['descricao'] ?? 'Documento normativo do IPIKK para consulta.') ?></p>
                    <a href="<?= htmlspecialchars($pdf_url) ?>" class="card-botao" target="_blank">
                        <i class="fas fa-file-pdf"></i> Visualizar Documento
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

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
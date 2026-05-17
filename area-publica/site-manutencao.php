<?php
/**
 * Página de Manutenção - IPIKK
 * Exibida quando o site está em modo de manutenção
 */

require_once '../config/index.php';

// Buscar configurações do site
$config = getDB()->query("SELECT * FROM configuracoes WHERE id = 1")->fetch();

// Buscar dados da manutenção
$manutencao_titulo = $config['manutencao_titulo'] ?? 'Site em Manutenção';
$manutencao_mensagem = $config['manutencao_mensagem_principal'] ?? 'Estamos realizando melhorias para lhe servir melhor.';
$manutencao_detalhes = $config['manutencao_detalhes'] ?? "O site estará disponível em breve.\nEstamos atualizando nossos sistemas.\nAgradecemos pela paciência.";
$manutencao_previsao = $config['manutencao_previsao'] ?? 'em breve';
$manutencao_telefone = $config['manutencao_telefone'] ?? $config['telefone'] ?? '';
$manutencao_whatsapp = $config['manutencao_whatsapp'] ?? $config['whatsapp_numero'] ?? '';
$manutencao_email = $config['manutencao_email'] ?? $config['email_geral'] ?? '';

// Converter detalhes em array de parágrafos
$detalhes_array = explode("\n", $manutencao_detalhes);
$detalhes_array = array_filter($detalhes_array, function($line) {
    return trim($line) !== '';
});
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($manutencao_titulo) ?> - IPIKK</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="<?= $config['favicon_url'] ?? 'foto/ipikk_new_logo.png' ?>" rel="icon">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background: #f5f8fa;
            color: #2c3e50;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        :root {
            --ciano-suave: #008bb5;
            --ciano-claro: #e6f7ff;
            --ciano-escuro-suave: #006d8f;
            --gradiente-suave: linear-gradient(135deg, #008bb5 0%, #006d8f 100%);
            --cinza-claro-bg: #f5f8fa;
            --texto-principal: #2c3e50;
            --texto-secundario: #5a6b7a;
            --branco: #ffffff;
            --transition: all 0.3s ease;
        }

        .barra-superior {
            background: var(--gradiente-suave);
            color: var(--branco);
            padding: 10px 0;
        }

        .conteudo-superior {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .info-instituto {
            font-size: 0.9rem;
            font-weight: 500;
            opacity: 0.95;
        }

        .logo-simples {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo-simples img {
            width: 50px;
            height: 50px;
            object-fit: contain;
        }

        .logo-simples span {
            font-weight: 600;
            font-size: 1.2rem;
            color: var(--branco);
        }

        .main-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 60px 20px;
        }

        .card-indisponivel {
            max-width: 650px;
            width: 100%;
            background: var(--branco);
            border-radius: 40px;
            padding: 50px 40px;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(0, 139, 181, 0.1);
            animation: fadeInUp 0.6s ease;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .icone-status {
            width: 120px;
            height: 120px;
            background: var(--ciano-claro);
            border-radius: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
        }

        .icone-status i {
            font-size: 4rem;
            color: var(--ciano-suave);
        }

        .icone-status.manutencao i {
            color: #ff6b35;
        }

        h1 {
            font-family: 'Poppins', sans-serif;
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 20px;
            color: var(--texto-principal);
        }

        .mensagem-principal {
            font-size: 1.2rem;
            color: var(--texto-secundario);
            margin-bottom: 25px;
        }

        .mensagem-detalhe {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 20px;
            margin: 25px 0;
            text-align: left;
            border-left: 4px solid var(--ciano-suave);
        }

        .mensagem-detalhe p {
            margin-bottom: 12px;
            color: var(--texto-secundario);
        }

        .mensagem-detalhe p:last-child {
            margin-bottom: 0;
        }

        .mensagem-detalhe i {
            color: var(--ciano-suave);
            margin-right: 10px;
            width: 24px;
        }

        .data-info {
            background: var(--ciano-claro);
            padding: 15px 20px;
            border-radius: 16px;
            margin: 20px 0;
            font-weight: 500;
            color: var(--ciano-suave);
        }

        .data-info i {
            margin-right: 10px;
        }

        .botoes {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 30px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 28px;
            border-radius: 40px;
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition);
            font-size: 0.95rem;
        }

        .btn-primario {
            background: var(--gradiente-suave);
            color: var(--branco);
            border: none;
        }

        .btn-primario:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 139, 181, 0.3);
        }

        .btn-secundario {
            background: var(--ciano-claro);
            color: var(--ciano-suave);
        }

        .btn-secundario:hover {
            background: #d4ecf5;
            transform: translateY(-3px);
        }

        .btn-contato {
            background: #e8f5e8;
            color: #2e7d32;
        }

        .btn-contato:hover {
            background: #d0ebd0;
            transform: translateY(-3px);
        }

        .contatos {
            margin-top: 35px;
            padding-top: 25px;
            border-top: 1px solid #eef2f6;
            display: flex;
            justify-content: center;
            gap: 25px;
            flex-wrap: wrap;
        }

        .contatos a {
            color: var(--texto-secundario);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
            font-size: 0.9rem;
        }

        .contatos a:hover {
            color: var(--ciano-suave);
        }

        .rodape-simples {
            background: var(--gradiente-suave);
            color: white;
            padding: 25px 0;
            text-align: center;
            margin-top: auto;
        }

        .rodape-simples p {
            font-size: 0.85rem;
            opacity: 0.9;
        }

        @media (max-width: 768px) {
            .conteudo-superior {
                flex-direction: column;
                text-align: center;
                padding: 0 20px;
            }

            .card-indisponivel {
                padding: 35px 25px;
            }

            h1 {
                font-size: 1.8rem;
            }

            .mensagem-principal {
                font-size: 1rem;
            }

            .icone-status {
                width: 90px;
                height: 90px;
            }

            .icone-status i {
                font-size: 3rem;
            }
        }

        @media (max-width: 480px) {
            .botoes {
                flex-direction: column;
                width: 100%;
            }

            .btn {
                justify-content: center;
            }

            .contatos {
                flex-direction: column;
                align-items: center;
                gap: 12px;
            }
        }
    </style>
</head>
<body>

    <div class="barra-superior">
        <div class="conteudo-superior">
            <div class="info-instituto">
                <?= htmlspecialchars($config['instituicao_nome'] ?? 'Instituto Médio Politécnico Industrial do Kilamba Kiaxi Nº 8050 Nova-vida') ?>
            </div>
            <div class="logo-simples">
                <img src="<?= $config['logo_url'] ?? 'foto/ipikk_new_logo.png' ?>" alt="IPIKK" onerror="this.style.display='none'">
                <span>IPIKK</span>
            </div>
        </div>
    </div>

    <main class="main-content">
        <div class="card-indisponivel">
            <div class="icone-status manutencao">
                <i class="fas fa-tools"></i>
            </div>
            <h1><?= htmlspecialchars($manutencao_titulo) ?></h1>
            <p class="mensagem-principal"><?= htmlspecialchars($manutencao_mensagem) ?></p>
            
            <div class="mensagem-detalhe">
                <?php foreach ($detalhes_array as $detalhe): ?>
                <p><i class="fas fa-info-circle"></i> <?= htmlspecialchars(trim($detalhe)) ?></p>
                <?php endforeach; ?>
            </div>
            
            <div class="data-info">
                <i class="fas fa-calendar-alt"></i> Previsão de retorno: <?= htmlspecialchars($manutencao_previsao) ?>
            </div>
            
            <div class="botoes">
                <a href="javascript:void(0)" class="btn btn-primario" id="btnAtualizar">
                    <i class="fas fa-sync-alt"></i> Tentar novamente
                </a>
                <?php if (!empty($manutencao_whatsapp)): ?>
                <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $manutencao_whatsapp) ?>" class="btn btn-contato" target="_blank">
                    <i class="fab fa-whatsapp"></i> Contactar via WhatsApp
                </a>
                <?php endif; ?>
            </div>

            <?php if (!empty($manutencao_telefone) || !empty($manutencao_email)): ?>
            <div class="contatos">
                <?php if (!empty($manutencao_telefone)): ?>
                <a href="tel:<?= preg_replace('/[^0-9]/', '', $manutencao_telefone) ?>">
                    <i class="fas fa-phone"></i> <?= htmlspecialchars($manutencao_telefone) ?>
                </a>
                <?php endif; ?>
                <?php if (!empty($manutencao_email)): ?>
                <a href="mailto:<?= htmlspecialchars($manutencao_email) ?>">
                    <i class="fas fa-envelope"></i> <?= htmlspecialchars($manutencao_email) ?>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <footer class="rodape-simples">
        <div class="conteudo-superior" style="justify-content: center;">
            <p>IPIKK <?= date('Y') ?> © Todos os direitos reservados | Instituto Médio Politécnico Industrial do Kilamba Kiaxi</p>
        </div>
    </footer>

    <script>
        const btnAtualizar = document.getElementById('btnAtualizar');
        if (btnAtualizar) {
            btnAtualizar.addEventListener('click', () => {
                location.reload();
            });
        }

        // Detectar se veio de um redirecionamento e tentar recarregar após 30 segundos
        setTimeout(() => {
            location.reload();
        }, 30000);
    </script>
</body>
</html>
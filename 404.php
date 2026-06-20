<?php
require_once __DIR__ . '/config/index.php';
http_response_code(404);
$config = [];
try {
    $config = getDB()->query("SELECT instituicao_nome, instituicao_acronimo, instituicao_slogan, logo_url, favicon_url FROM configuracoes WHERE id = 1")->fetch() ?: [];
} catch (Throwable $e) {
    $config = [];
}
$logo = $config['logo_url'] ?? 'area-publica/foto/ipikk_new_logo.png';
$acronimo = $config['instituicao_acronimo'] ?? 'IPIKK';
$nome = $config['instituicao_nome'] ?? 'Instituto Médio Politécnico Industrial do Kilamba Kiaxi';
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Página não encontrada - <?= htmlspecialchars($acronimo) ?></title>
    <link rel="icon" href="<?= htmlspecialchars($config['favicon_url'] ?? $logo) ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root { --azul:#003072; --azul-escuro:#001a40; --verde:#0a9396; --fundo:#f4f7fb; }
        * { box-sizing: border-box; }
        body { margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center; font-family: Poppins, Arial, sans-serif; background: radial-gradient(circle at top left, #e6f4ff, var(--fundo)); color:#1f2937; padding:24px; }
        .card-404 { width:min(760px, 100%); background:#fff; border-radius:28px; padding:48px; text-align:center; box-shadow:0 24px 70px rgba(0, 48, 114, .16); border:1px solid rgba(0,48,114,.08); }
        .logo { max-width:110px; max-height:110px; object-fit:contain; margin-bottom:18px; }
        .badge { display:inline-flex; align-items:center; gap:8px; color:var(--verde); font-weight:700; background:rgba(10,147,150,.1); border-radius:999px; padding:8px 16px; margin-bottom:18px; }
        h1 { font-size: clamp(42px, 9vw, 94px); line-height:1; color:var(--azul); margin:0 0 10px; letter-spacing:-3px; }
        h2 { font-size: clamp(24px, 4vw, 34px); margin:0 0 12px; color:var(--azul-escuro); }
        p { font-size:17px; line-height:1.7; margin:0 auto 28px; max-width:560px; color:#4b5563; }
        .btn { display:inline-flex; align-items:center; gap:10px; border-radius:14px; background:linear-gradient(135deg, var(--azul), var(--verde)); color:#fff; padding:14px 22px; text-decoration:none; font-weight:700; box-shadow:0 12px 28px rgba(0,48,114,.25); }
        .institution { margin-top:24px; color:#6b7280; font-size:14px; }
    </style>
</head>
<body>
    <main class="card-404" role="main">
        <img class="logo" src="<?= htmlspecialchars($logo) ?>" alt="Logo <?= htmlspecialchars($acronimo) ?>">
        <div class="badge"><i class="fa-solid fa-circle-info"></i> Erro 404</div>
        <h1>404</h1>
        <h2>Página não encontrada</h2>
        <p>A página que procura pode ter sido movida, removida ou o endereço foi escrito incorretamente. Continue a navegar a partir da página inicial do <?= htmlspecialchars($acronimo) ?>.</p>
        <a class="btn" href="/area-publica/"><i class="fa-solid fa-house"></i> Voltar ao início</a>
        <div class="institution"><?= htmlspecialchars($nome) ?></div>
    </main>
</body>
</html>

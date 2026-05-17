<?php
require_once __DIR__ . '/../config/index.php';

$db = getDB();

$filas = $db->query("SELECT f.id, f.noticia_id, n.titulo, n.resumo, n.imagem_url, n.slug
                    FROM push_fila f
                    JOIN noticias n ON n.id = f.noticia_id
                    WHERE f.status = 'pendente'
                    ORDER BY f.id ASC
                    LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);

if (!$filas) { echo "Sem notificações pendentes\n"; exit(0); }

if (!class_exists('Minishlink\\WebPush\\WebPush')) {
    echo "Biblioteca web-push não instalada. Mantendo fila pendente.\n";
    exit(0);
}

$vapid = [
    'VAPID' => [
        'subject' => getenv('PUSH_VAPID_SUBJECT') ?: 'mailto:admin@ipikk.local',
        'publicKey' => getenv('PUSH_VAPID_PUBLIC_KEY') ?: '',
        'privateKey' => getenv('PUSH_VAPID_PRIVATE_KEY') ?: '',
    ],
];

$webPush = new \Minishlink\WebPush\WebPush($vapid);
$subs = $db->query("SELECT endpoint, p256dh, auth FROM push_subscricoes WHERE ativo = 1")->fetchAll(PDO::FETCH_ASSOC);

foreach ($filas as $f) {
    foreach ($subs as $s) {
        $payload = json_encode([
            'title' => 'Nova notícia: ' . $f['titulo'],
            'body' => mb_substr(strip_tags($f['resumo'] ?? ''), 0, 120),
            'image' => $f['imagem_url'] ?? null,
            'url' => '/area-publica/noticia.php?slug=' . urlencode($f['slug'])
        ], JSON_UNESCAPED_UNICODE);

        $sub = \Minishlink\WebPush\Subscription::create([
            'endpoint' => $s['endpoint'],
            'keys' => ['p256dh' => $s['p256dh'], 'auth' => $s['auth']]
        ]);

        $webPush->queueNotification($sub, $payload);
    }

    $ok = true;
    foreach ($webPush->flush() as $report) {
        if (!$report->isSuccess()) { $ok = false; }
    }

    $stmt = $db->prepare("UPDATE push_fila SET status = ?, tentativas = tentativas + 1, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$ok ? 'enviado' : 'erro', $f['id']]);
}

echo "Processamento concluído\n";
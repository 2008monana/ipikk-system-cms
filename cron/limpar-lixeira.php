<?php
require_once dirname(__DIR__) . '/config/index.php';
$db = getDB();
$lixeira_dir = dirname(__DIR__) . '/uploads/lixeira/';
$stmt = $db->prepare("SELECT id, caminho_lixeira, tamanho_bytes, nome_original FROM lixeira WHERE restaurado = 0 AND data_expiracao < NOW()");
$stmt->execute();
$expirados = $stmt->fetchAll();
$total_limpos = 0; $total_espaco = 0;
foreach ($expirados as $arquivo) {
    $caminho_completo = dirname(__DIR__) . '/..' . $arquivo['caminho_lixeira'];
    if (file_exists($caminho_completo)) { $total_espaco += (int)$arquivo['tamanho_bytes']; @unlink($caminho_completo); }
    $db->prepare('DELETE FROM lixeira WHERE id = ?')->execute([$arquivo['id']]);
    $total_limpos++;
}
$backupDir = dirname(__DIR__) . '/backups';
$backupManter = (int)($db->query("SELECT backup_manter FROM configuracoes WHERE id = 1")->fetchColumn() ?: 4);
$backupManter = max(1, $backupManter);
$backups = glob($backupDir . '/*.zip') ?: [];
usort($backups, fn($a,$b)=>filemtime($b)-filemtime($a));
$remover = array_slice($backups, $backupManter);
$agora = time();
$backupsRemovidos = 0;
foreach ($remover as $file) {
    if (($agora - filemtime($file)) >= 30*86400) { $total_espaco += filesize($file); @unlink($file); $backupsRemovidos++; }
}
$espaco_mb = round($total_espaco / 1024 / 1024, 2);
if (function_exists('registrarLog')) {
    registrarLog('removeu', 'limpeza_automatica', 0, "Lixeira: {$total_limpos}; backups removidos: {$backupsRemovidos}; espaço: {$espaco_mb}MB");
}
echo "Lixeira limpa: $total_limpos | Backups removidos: $backupsRemovidos | Espaço: {$espaco_mb}MB\n";
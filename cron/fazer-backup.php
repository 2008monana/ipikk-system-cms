<?php
require_once dirname(__DIR__) . '/config/index.php';
$db = getDB();
if (!class_exists('ZipArchive')) {
    echo "Erro: extensão ZIP do PHP não está ativa (ZipArchive).\n";
    exit(1);
}
$config = $db->query("SELECT backup_frequencia, backup_horario, backup_manter FROM configuracoes WHERE id = 1")->fetch(PDO::FETCH_ASSOC) ?: [];
$manter = max(1, (int)($config['backup_manter'] ?? 4));
$backupDir = dirname(__DIR__) . '/backups';
if (!is_dir($backupDir)) mkdir($backupDir, 0777, true);
$nome = 'backup_automatico_' . date('Y-m-d_H-i-s') . '.zip';
$zipPath = $backupDir . '/' . $nome;
$zip = new ZipArchive();
if ($zip->open($zipPath, ZipArchive::CREATE) !== true) { exit("Erro ao criar ZIP\n"); }
$sql = "-- Backup automático\nSET FOREIGN_KEY_CHECKS=0;\n";
$tables = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $table) {
    $create = $db->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
    $sql .= "DROP TABLE IF EXISTS `$table`;\n" . $create['Create Table'] . ";\n";
    $rows = $db->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
        $cols = '`' . implode('`,`', array_keys($row)) . '`';
        $vals = implode(',', array_map([$db, 'quote'], array_values($row)));
        $sql .= "INSERT INTO `$table` ($cols) VALUES ($vals);\n";
    }
}
$sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
$zip->addFromString('database.sql', $sql);
foreach (glob(dirname(__DIR__) . '/config/*.php') as $cfg) $zip->addFile($cfg, 'config/' . basename($cfg));
foreach (['.env','.htaccess','composer.json','composer.lock'] as $extra) {
    $full = dirname(__DIR__) . '/' . $extra;
    if (file_exists($full)) $zip->addFile($full, $extra);
}
$zip->close();
$files = glob($backupDir . '/backup_automatico_*.zip');
usort($files, fn($a,$b)=>filemtime($b)-filemtime($a));
foreach (array_slice($files, $manter) as $old) @unlink($old);
if (function_exists('registrarLog')) registrarLog('criou', 'backup', 0, 'Backup automático criado: ' . $nome);
echo "Backup automático criado: $nome\n";

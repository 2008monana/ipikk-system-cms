<?php
/**
 * Configuracoes - Area Restrita IPIKK
 * Gestao completa das configuracoes do sistema
 */

$titulo_pagina = 'Configuracoes';
$css_especifico = 'admin-configuracoes.css';

$base_path = dirname(__DIR__);
require_once $base_path . '/config/index.php';

if (!isset($_SESSION['utilizador_id'])) {
    header('Location: area-restrita.php');
    exit;
}

if (isset($_SESSION['utilizador_permissoes'])) {
    if (is_array($_SESSION['utilizador_permissoes'])) {
        $permissoes = $_SESSION['utilizador_permissoes'];
    } else {
        $permissoes = json_decode($_SESSION['utilizador_permissoes'], true);
    }
} else {
    $permissoes = [];
}

if (!is_array($permissoes)) {
    $permissoes = [];
}

$nivel = $_SESSION['utilizador_nivel'] ?? 'editor';

if ($nivel !== 'admin' && !in_array('galeria', $permissoes) && !in_array('*', $permissoes)) {
    header('Location: admin-dashboard.php?erro=permissao');
    exit;
}


$db = getDB();

$stmt = $db->prepare("SELECT id, nome, email, foto_url FROM utilizadores WHERE id = ?");
$stmt->execute([$_SESSION['utilizador_id']]);
$usuario_logado = $stmt->fetch();

if (!$usuario_logado) {
    session_destroy();
    header('Location: area-restrita.php');
    exit;
}

// Buscar configuracoes atuais
$config = $db->query("SELECT * FROM configuracoes WHERE id = 1")->fetch();

if (!$config) {
    $db->exec("INSERT INTO configuracoes (id) VALUES (1)");
    $config = $db->query("SELECT * FROM configuracoes WHERE id = 1")->fetch();
}

// Buscar backups disponiveis
$backup_dir = dirname(__DIR__) . '/backups/';
if (!is_dir($backup_dir)) {
    mkdir($backup_dir, 0777, true);
}
$backups = [];
$files = glob($backup_dir . '*.zip');
foreach ($files as $file) {
    $backups[] = [
        'nome' => basename($file),
        'tamanho' => round(filesize($file) / 1048576, 2),
        'data' => date('d/m/Y H:i:s', filemtime($file)),
        'timestamp' => filemtime($file)
    ];
}
usort($backups, fn($a, $b) => $b['timestamp'] - $a['timestamp']);

// Processar POST
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    $action = $_POST['action'] ?? '';
    
    if ($action === 'salvar_escola') {
        $db->exec("ALTER TABLE configuracoes ADD COLUMN IF NOT EXISTS rodape_links_ipikk TEXT NULL");
        $db->exec("ALTER TABLE configuracoes ADD COLUMN IF NOT EXISTS rodape_links_rapidos TEXT NULL");
        $stmt = $db->prepare("UPDATE configuracoes SET 
            instituicao_nome = ?, instituicao_acronimo = ?, instituicao_slogan = ?,
            endereco_completo = ?, cidade = ?, provincia = ?, telefone = ?,
            email_geral = ?, whatsapp_numero = ?, horario_funcionamento = ?,
            rodape_links_ipikk = ?, rodape_links_rapidos = ?
            WHERE id = 1");
        
        $success = $stmt->execute([
            $_POST['instituicao_nome'] ?? '',
            $_POST['instituicao_acronimo'] ?? '',
            $_POST['instituicao_slogan'] ?? '',
            $_POST['endereco_completo'] ?? '',
            $_POST['cidade'] ?? '',
            $_POST['provincia'] ?? '',
            $_POST['telefone'] ?? '',
            $_POST['email_geral'] ?? '',
            $_POST['whatsapp_numero'] ?? '',
            $_POST['horario_funcionamento'] ?? '',
            $_POST['rodape_links_ipikk'] ?? '',
            $_POST['rodape_links_rapidos'] ?? ''
        ]);
        
        echo json_encode(['success' => $success, 'message' => $success ? 'Configuracoes guardadas!' : 'Erro ao guardar']);
        exit;
    }
    
    if ($action === 'salvar_redes') {
        $stmt = $db->prepare("UPDATE configuracoes SET 
            rede_social_facebook = ?, rede_social_instagram = ?, rede_social_linkedin = ?,
            mostrar_social_header = ?, mostrar_social_footer = ?, social_nova_janela = ?
            WHERE id = 1");
        
        $success = $stmt->execute([
            $_POST['facebook'] ?? '',
            $_POST['instagram'] ?? '',
            $_POST['linkedin'] ?? '',
            isset($_POST['mostrar_header']) ? 1 : 0,
            isset($_POST['mostrar_footer']) ? 1 : 0,
            isset($_POST['nova_janela']) ? 1 : 0
        ]);
        
        echo json_encode(['success' => $success, 'message' => $success ? 'Redes sociais guardadas!' : 'Erro ao guardar']);
        exit;
    }
    
    if ($action === 'salvar_tecnico') {
        $stmt = $db->prepare("UPDATE configuracoes SET 
            smtp_host = ?, smtp_porta = ?, smtp_seguranca = ?, smtp_email = ?, smtp_senha = ?,
            seo_titulo = ?, seo_descricao = ?, seo_keywords = ?, seo_url = ?
            WHERE id = 1");
        
        $success = $stmt->execute([
            $_POST['smtp_host'] ?? '',
            $_POST['smtp_porta'] ?? 587,
            $_POST['smtp_seguranca'] ?? 'tls',
            $_POST['smtp_email'] ?? '',
            $_POST['smtp_senha'] ?? '',
            $_POST['seo_titulo'] ?? '',
            $_POST['seo_descricao'] ?? '',
            $_POST['seo_keywords'] ?? '',
            $_POST['seo_url'] ?? ''
        ]);
        
        echo json_encode(['success' => $success, 'message' => $success ? 'Configuracoes tecnicas guardadas!' : 'Erro ao guardar']);
        exit;
    }
    
    if ($action === 'salvar_manutencao') {
    // CORREÇÃO: Converter corretamente o valor do modo de manutenção
    $modo_manutencao = 0;
    if (isset($_POST['modo_manutencao'])) {
        $modo_manutencao = (int)$_POST['modo_manutencao'];
    }
    
    $stmt = $db->prepare("UPDATE configuracoes SET 
        modo_manutencao = ?, 
        manutencao_titulo = ?, 
        manutencao_mensagem_principal = ?,
        manutencao_detalhes = ?, 
        manutencao_previsao = ?, 
        manutencao_telefone = ?,
        manutencao_whatsapp = ?, 
        manutencao_email = ?, 
        manutencao_inicio = ?, 
        manutencao_fim = ?
        WHERE id = 1");
    
    $success = $stmt->execute([
        $modo_manutencao,
        trim($_POST['manutencao_titulo'] ?? 'Site em Manutencao'),
        trim($_POST['manutencao_mensagem_principal'] ?? 'Estamos realizando melhorias para lhe servir melhor.'),
        trim($_POST['manutencao_detalhes'] ?? ''),
        trim($_POST['manutencao_previsao'] ?? 'em breve'),
        trim($_POST['manutencao_telefone'] ?? ''),
        trim($_POST['manutencao_whatsapp'] ?? ''),
        trim($_POST['manutencao_email'] ?? ''),
        !empty($_POST['manutencao_inicio']) ? $_POST['manutencao_inicio'] : null,
        !empty($_POST['manutencao_fim']) ? $_POST['manutencao_fim'] : null
    ]);
    
    if ($success) {
        // Log da ação
        if (function_exists('registrarLog')) {
            registrarLog('editou', 'configuracoes', 1, 
                $modo_manutencao ? 'Ativou modo de manutenção' : 'Desativou modo de manutenção');
        }
    }
    
    echo json_encode(['success' => $success, 'message' => $success ? 'Configuracoes de manutencao guardadas!' : 'Erro ao guardar']);
    exit;
}
    
    if ($action === 'upload_logo') {
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (!in_array($ext, $allowed)) {
                echo json_encode(['success' => false, 'message' => 'Formato nao permitido.']);
                exit;
            }
            
            $upload = uploadArquivoNuvem($_FILES['logo'], 'configuracoes');
            if ($upload['success']) {
                $stmt = $db->prepare("UPDATE configuracoes SET logo_url = ?, logo_rodape_url = ? WHERE id = 1");
                $stmt->execute([$upload['url'], $upload['url']]);
                echo json_encode(['success' => true, 'message' => 'Logo atualizada!']);
                exit;
            }
        }
        echo json_encode(['success' => false, 'message' => 'Erro ao fazer upload.']);
        exit;
    }
    
    if ($action === 'upload_favicon') {
        if (isset($_FILES['favicon']) && $_FILES['favicon']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['favicon']['name'], PATHINFO_EXTENSION));
            $allowed = ['ico', 'png'];
            
            if (!in_array($ext, $allowed)) {
                echo json_encode(['success' => false, 'message' => 'Formato nao permitido. Use ICO ou PNG.']);
                exit;
            }
            
            $upload = uploadArquivoNuvem($_FILES['favicon'], 'configuracoes');
            if ($upload['success']) {
                $stmt = $db->prepare("UPDATE configuracoes SET favicon_url = ? WHERE id = 1");
                $stmt->execute([$upload['url']]);
                echo json_encode(['success' => true, 'message' => 'Favicon atualizado!']);
                exit;
            }
        }
        echo json_encode(['success' => false, 'message' => 'Erro ao fazer upload.']);
        exit;
    }
    
    if ($action === 'salvar_backup') {
        $stmt = $db->prepare("UPDATE configuracoes SET 
            backup_frequencia = ?, backup_horario = ?, backup_manter = ?
            WHERE id = 1");
        
        $success = $stmt->execute([
            $_POST['backup_frequencia'] ?? 'semanal',
            $_POST['backup_horario'] ?? '02:00:00',
            $_POST['backup_manter'] ?? 4
        ]);
        
        echo json_encode(['success' => $success, 'message' => $success ? 'Configuracoes de backup guardadas!' : 'Erro ao guardar']);
        exit;
    }
    
    if ($action === 'executar_backup') {
        if (!class_exists('ZipArchive')) {
            echo json_encode(['success' => false, 'message' => 'Extensão ZIP do PHP não está ativa no servidor (ZipArchive). Ative a extensão php-zip e tente novamente.']);
            exit;
        }
        $zip = null;
        $zip_path = null;

        try {
            $tipo = $_POST['tipo'] ?? 'completo';
            $nome = $_POST['nome'] ?? 'backup_' . date('Y-m-d_H-i-s') . '.zip';

            $backup_dir = dirname(__DIR__) . '/backups/';
            if (!is_dir($backup_dir) && !mkdir($backup_dir, 0777, true)) {
                throw new RuntimeException('Não foi possível criar a pasta de backups.');
            }

            $zip = new ZipArchive();
            $zip_path = $backup_dir . $nome;

            if ($zip->open($zip_path, ZipArchive::CREATE) !== true) {
                throw new RuntimeException('Erro ao criar arquivo ZIP.');
            }

            if ($tipo === 'completo' || $tipo === 'dados') {
                $stmt = $db->query("SHOW TABLES");
                $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

                $sql = "-- Backup gerado em " . date('Y-m-d H:i:s') . "\n";
                $sql .= "-- Sistema: IPIKK\n\n";
                $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

                foreach ($tables as $table) {
                    $safeTable = str_replace('`', '``', (string)$table);
                    $stmt_tbl = $db->query("SHOW CREATE TABLE `{$safeTable}`");
                    $row = $stmt_tbl ? $stmt_tbl->fetch(PDO::FETCH_ASSOC) : null;
                    if (!$row || !isset($row['Create Table'])) {
                        throw new RuntimeException('Falha ao exportar estrutura da tabela: ' . $table);
                    }

                    $sql .= "\n\n-- Estrutura da tabela: $table\n";
                    $sql .= "DROP TABLE IF EXISTS `{$safeTable}`;\n";
                    $sql .= $row['Create Table'] . ";\n\n";

                    $stmt_data = $db->query("SELECT * FROM `{$safeTable}`");
                    while ($row_data = $stmt_data->fetch(PDO::FETCH_ASSOC)) {
                        $columns = array_map(
                            fn($col) => str_replace('`', '``', (string)$col),
                            array_keys($row_data)
                        );
                        $values = array_map(fn($val) => $db->quote($val), array_values($row_data));
                        $sql .= "INSERT INTO `{$safeTable}` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(',', $values) . ");\n";
                    }
                    $sql .= "\n";
                }

                $sql .= "\nSET FOREIGN_KEY_CHECKS=1;\n";
                $zip->addFromString('database.sql', $sql);
            }

            if ($tipo === 'completo' || $tipo === 'ficheiros') {
                $baseProjeto = dirname(__DIR__);
                $configDir = $baseProjeto . '/config/';
                if (is_dir($configDir)) {
                    foreach (glob($configDir . '*.php') as $fileCfg) {
                        $zip->addFile($fileCfg, 'config/' . basename($fileCfg));
                    }
                }
                foreach (['.env', '.htaccess', 'composer.json', 'composer.lock'] as $extraFile) {
                    $full = $baseProjeto . '/' . $extraFile;
                    if (file_exists($full)) {
                        $zip->addFile($full, $extraFile);
                    }
                }
            }

            $zip->close();
            echo json_encode(['success' => true, 'message' => 'Backup criado com sucesso!', 'nome' => $nome]);
        } catch (Throwable $e) {
            if ($zip instanceof ZipArchive) {
                $zip->close();
            }
            if ($zip_path && file_exists($zip_path)) {
                @unlink($zip_path);
            }
            error_log('Erro ao criar backup: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erro ao criar backup: ' . $e->getMessage()]);
        }
        exit;
    }
    
    if ($action === 'baixar_backup') {
        $nome = $_POST['nome'] ?? ($_GET['nome'] ?? '');
        $backup_path = dirname(__DIR__) . '/backups/' . $nome;
        
        if (file_exists($backup_path)) {
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $nome . '"');
            header('Content-Length: ' . filesize($backup_path));
            readfile($backup_path);
            exit;
        }
        echo json_encode(['success' => false, 'message' => 'Backup nao encontrado.']);
        exit;
    }
    
    if ($action === 'eliminar_backup') {
        $nome = $_POST['nome'] ?? ($_GET['nome'] ?? '');
        $backup_path = dirname(__DIR__) . '/backups/' . $nome;
        
        if (file_exists($backup_path)) {
            unlink($backup_path);
            echo json_encode(['success' => true, 'message' => 'Backup eliminado!']);
            exit;
        }
        echo json_encode(['success' => false, 'message' => 'Backup nao encontrado.']);
        exit;
    }
    
    // ========== NOVA AÇÃO: RESTAURAR BACKUP ==========
    if ($action === 'restaurar_backup') {
        if (!class_exists('ZipArchive')) {
            echo json_encode(['success' => false, 'message' => 'Extensão ZIP do PHP não está ativa no servidor (ZipArchive).']);
            exit;
        }
        $nome = $_POST['nome'] ?? ($_GET['nome'] ?? '');
        $backup_path = dirname(__DIR__) . '/backups/' . $nome;
        $restore_dir = dirname(__DIR__) . '/backups/restore_temp/';
        
        if (!file_exists($backup_path)) {
            echo json_encode(['success' => false, 'message' => 'Backup nao encontrado.']);
            exit;
        }
        
        // Criar diretório temporário para restauração
        if (!is_dir($restore_dir)) {
            mkdir($restore_dir, 0777, true);
        }
        
        $zip = new ZipArchive();
        if ($zip->open($backup_path) !== true) {
            echo json_encode(['success' => false, 'message' => 'Erro ao abrir o arquivo de backup.']);
            exit;
        }
        
        // Extrair para diretório temporário
        $zip->extractTo($restore_dir);
        $zip->close();
        
        $erros = [];
        
        // Restaurar banco de dados
        $sql_file = $restore_dir . 'database.sql';
        if (file_exists($sql_file)) {
            try {
                $sql_content = file_get_contents($sql_file);
                
                // Remover comentários e quebrar em comandos
                $sql_content = preg_replace('/--[^\n]*\n/', '', $sql_content);
                $sql_content = preg_replace('/SET FOREIGN_KEY_CHECKS=0;\s*/', '', $sql_content);
                $sql_content = preg_replace('/SET FOREIGN_KEY_CHECKS=1;\s*/', '', $sql_content);
                
                $commands = explode(';', $sql_content);
                
                foreach ($commands as $command) {
                    $command = trim($command);
                    if (!empty($command)) {
                        try {
                            $db->exec($command);
                        } catch (PDOException $e) {
                            error_log("Erro no comando SQL: " . $e->getMessage());
                        }
                    }
                }
            } catch (Exception $e) {
                $erros[] = 'Erro ao restaurar banco de dados: ' . $e->getMessage();
            }
        }
        
        // Restaurar ficheiros de configuração (não afeta uploads/Cloudinary)
        $configRestoreDir = $restore_dir . 'config/';
        $configDestinoDir = dirname(__DIR__) . '/config/';
        if (is_dir($configRestoreDir)) {
            foreach (glob($configRestoreDir . '*.php') as $cfgFile) {
                $destinoCfg = $configDestinoDir . basename($cfgFile);
                if (!copy($cfgFile, $destinoCfg)) {
                    $erros[] = 'Erro ao restaurar configuração: ' . basename($cfgFile);
                }
            }
        }
        foreach (['.env', '.htaccess', 'composer.json', 'composer.lock'] as $extra) {
            $origem = $restore_dir . $extra;
            $destino = dirname(__DIR__) . '/' . $extra;
            if (file_exists($origem) && !copy($origem, $destino)) {
                $erros[] = 'Erro ao restaurar ficheiro: ' . $extra;
            }
        }
        
        // Limpar diretório temporário (arquivos e subpastas)
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($restore_dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $file) {
            if ($file->isFile()) {
                @unlink($file->getRealPath());
            } elseif ($file->isDir()) {
                @rmdir($file->getRealPath());
            }
        }
        @rmdir($restore_dir);
        
        if (empty($erros)) {
            echo json_encode(['success' => true, 'message' => 'Backup restaurado com sucesso! O site foi restaurado ao estado do backup.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erros durante a restauração: ' . implode(', ', $erros)]);
        }
        exit;
    }
    
    if ($action === 'testar_email') {
        require_once $base_path . '/config/email.php';
        $resultado = enviarEmail(
            $_POST['email_teste'] ?? $config['smtp_email'] ?? '',
            'Teste IPIKK',
            'Teste de Configuracao de Email',
            '<p>Este e um email de teste do sistema IPIKK.</p><p>Se recebeu esta mensagem, as configuracoes de email estao corretas!</p>'
        );
        echo json_encode($resultado);
        exit;
    }
}

$dados_js = [
    'config' => $config,
    'backups' => $backups
];
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Area Restrita - Configuracoes</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="../area-publica/foto/ipikk_new_logo.png" rel="icon">
    <link rel="stylesheet" href="css/admin-sidebar-header.css">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --azul-primario: #003072;
            --verde-acento: #0a9396;
            --cinza-claro: #f5f7fa;
            --cinza-medio: #e0e4e8;
            --cinza-escuro: #2c3e50;
            --branco: #fff;
            --sucesso: #28a745;
            --perigo: #dc3545;
            --aviso: #ffc107;
            --info: #17a2b8;
            --sombra: 0 2px 10px rgba(0,0,0,0.1);
            --sombra-forte: 0 5px 20px rgba(0,0,0,0.15);
            --transicao: all 0.3s ease;
            --borda-arredondada: 12px;
            --largura-sidebar: 280px;
            --altura-topo: 70px;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            font-size: 16px;
            line-height: 1.6;
            color: var(--cinza-escuro);
            background-color: var(--cinza-claro);
        }
        .btn-sair {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 12px;
            background: rgba(220,53,69,0.2);
            border: 1px solid rgba(220,53,69,0.4);
            border-radius: var(--borda-arredondada);
            color: var(--branco);
            text-decoration: none;
            transition: var(--transicao);
        }

        .btn-sair:hover { background: var(--perigo); transform: translateY(-2px); }

        .conteudo-principal { margin-left: var(--largura-sidebar); min-height: 100vh; }
        
        .barra-topo {
            height: var(--altura-topo); background: var(--branco);
            box-shadow: var(--sombra); display: flex; align-items: center;
            justify-content: space-between; padding: 0 30px;
            position: sticky; top: 0; z-index: 999;
        }
        
        .barra-topo h1 { font-size: 24px; color: var(--azul-primario); }
        
        .botao-menu-mobile {
            display: none;
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--azul-primario);
            padding: 10px;
        }
        
        .btn-guardar-tudo {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: linear-gradient(135deg, var(--verde-acento), var(--azul-primario));
            color: var(--branco);
            border: none;
            border-radius: var(--borda-arredondada);
            font-weight: 600;
            cursor: pointer;
        }

        .conteudo { padding: 30px; }

        .abas {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            border-bottom: 2px solid var(--cinza-medio);
            overflow-x: auto;
        }
        
        .aba {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            background: transparent;
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            font-weight: 600;
            color: var(--cinza-escuro);
            transition: var(--transicao);
            white-space: nowrap;
        }
        
        .aba:hover { background: var(--cinza-claro); }
        .aba.ativa { border-bottom-color: var(--verde-acento); color: var(--verde-acento); }
        
        .conteudo-aba { display: none; animation: fadeIn 0.3s; }
        .conteudo-aba.ativa { display: block; }
        
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        .secao {
            background: var(--branco);
            border-radius: var(--borda-arredondada);
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: var(--sombra);
        }
        
        .titulo-secao {
            font-size: 18px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--cinza-medio);
            color: var(--azul-primario);
        }

        .grupo-form { margin-bottom: 20px; }
        .grupo-form label { display: block; font-weight: 600; margin-bottom: 8px; font-size: 13px; }
        
        .campo-form, .selecao-form, .area-texto {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--cinza-medio);
            border-radius: var(--borda-arredondada);
            font-size: 14px;
            font-family: inherit;
        }
        
        .campo-form:focus, .selecao-form:focus, .area-texto:focus {
            outline: none;
            border-color: var(--verde-acento);
        }
        
        .linha-form { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; }
        .area-texto { min-height: 100px; resize: vertical; }

        .upload-arquivo {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            padding: 15px;
            background: var(--cinza-claro);
            border-radius: var(--borda-arredondada);
        }
        
        .preview-arquivo { font-size: 14px; color: var(--cinza-escuro); }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border: none;
            border-radius: var(--borda-arredondada);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transicao);
        }
        
        .btn-primario { background: var(--verde-acento); color: var(--branco); }
        .btn-perigo { background: var(--perigo); color: var(--branco); }
        .btn-secundario { background: var(--cinza-medio); color: var(--cinza-escuro); }
        .btn:hover { transform: translateY(-2px); box-shadow: var(--sombra-forte); }

        .rodape-acoes {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            padding-top: 20px;
            border-top: 2px solid var(--cinza-medio);
            margin-top: 30px;
        }

        .grupo-checkbox { display: flex; align-items: center; gap: 10px; margin-top: 10px; }
        .grupo-checkbox input { width: 18px; height: 18px; cursor: pointer; }

        .status-badge { display: inline-block; padding: 8px 20px; border-radius: 30px; font-weight: 600; }
        .status-ativo { background: #fff0f0; color: var(--perigo); border-left: 4px solid var(--perigo); }
        .status-inativo { background: #e8f5e9; color: var(--sucesso); border-left: 4px solid var(--sucesso); }
        .status-agendado { background: #fff3cd; color: #d39e00; border-left: 4px solid #ffc107; }

        .card-status, .card-controle, .card-agendamento, .card-mensagem, .card-contactos, .card-preview {
            background: var(--branco);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 25px;
            border: 1px solid var(--cinza-medio);
        }

        /* ESTILOS DA SECÇÃO BACKUP */
        .lista-backup {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 20px;
        }
        
        .item-backup {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px;
            background: var(--cinza-claro);
            border-radius: var(--borda-arredondada);
            transition: var(--transicao);
        }
        
        .item-backup:hover {
            background: var(--cinza-medio);
        }
        
        .info-backup {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .info-backup strong {
            font-size: 14px;
            color: var(--azul-primario);
        }
        
        .info-backup p {
            margin: 0;
            font-size: 12px;
            color: var(--cinza-escuro);
        }
        
        .acoes-backup {
            display: flex;
            gap: 10px;
        }
        
        .btn-backup {
            padding: 8px 15px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transicao);
            border: none;
        }
        
        .btn-backup-download {
            background: var(--info);
            color: white;
        }
        
        .btn-backup-download:hover {
            background: #138496;
        }
        
        .btn-backup-restaurar {
            background: var(--aviso);
            color: #856404;
        }
        
        .btn-backup-restaurar:hover {
            background: #e0a800;
        }
        
        .btn-backup-eliminar {
            background: var(--perigo);
            color: white;
        }
        
        .btn-backup-eliminar:hover {
            background: #c82333;
        }
        
        .info-backup-total {
            background: var(--cinza-claro);
            padding: 15px;
            border-radius: var(--borda-arredondada);
            margin-top: 15px;
            text-align: center;
            font-size: 13px;
            color: var(--cinza-escuro);
        }
        
        .empty-backup {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        
        /* Modal de confirmação */
        .modal-confirmacao {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.7);
            backdrop-filter: blur(4px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 10002;
            padding: 20px;
        }
        
        .modal-confirmacao.ativo {
            display: flex;
        }
        
        .modal-confirmacao-caixa {
            background: white;
            border-radius: 16px;
            max-width: 450px;
            width: 100%;
            padding: 30px;
            text-align: center;
        }
        
        .modal-confirmacao-icone {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
        }
        
        .modal-confirmacao-caixa h3 {
            margin-bottom: 10px;
            color: var(--cinza-escuro);
        }
        
        .modal-confirmacao-caixa p {
            margin-bottom: 25px;
            color: #666;
        }
        
        .modal-confirmacao-botoes {
            display: flex;
            gap: 10px;
            justify-content: center;
        }
        
        .botao-cancelar-modal {
            padding: 10px 20px;
            background: var(--cinza-claro);
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }
        
        .botao-confirmar-modal {
            padding: 10px 20px;
            background: var(--perigo);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }

        .notificacao {
            position: fixed;
            bottom: 30px;
            right: 30px;
            padding: 15px 25px;
            border-radius: var(--borda-arredondada);
            color: white;
            font-weight: 600;
            z-index: 10001;
            animation: slideInRight 0.3s ease;
            box-shadow: var(--sombra-forte);
        }
        @keyframes slideInRight { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        .notificacao.sucesso { background: linear-gradient(135deg, #28a745, #1e7e34); }
        .notificacao.erro { background: linear-gradient(135deg, #dc3545, #c82333); }
        .notificacao.info { background: linear-gradient(135deg, #17a2b8, #138496); }

        @media (max-width: 768px) {
            .conteudo-principal { margin-left: 0; }
            .botao-menu-mobile { display: block; }
            .abas { flex-wrap: wrap; }
            .linha-form { grid-template-columns: 1fr; }
            .acoes-backup { flex-direction: column; }
            .btn-backup { text-align: center; }
        }

        .links-builder .link-row{display:grid;grid-template-columns:1fr 1fr auto;gap:10px;margin-bottom:10px;}
        .backup-progress{position:fixed;right:20px;bottom:20px;background:#fff;padding:16px;border-radius:12px;box-shadow:0 10px 30px rgba(0,0,0,.2);z-index:9999;min-width:320px;}
        .progress-bar-wrap{height:10px;background:#e9ecef;border-radius:999px;overflow:hidden;margin:8px 0;}
        .progress-bar-fill{height:100%;background:#0a9396;width:0%;transition:width .3s;}

    </style>
</head>
<body>

<?php include 'includes/sidebar.php'; ?>


<main class="conteudo-principal">
    <header class="barra-superior">
        <div class="esquerda-barra">
            <button class="botao-menu-mobile" id="botaoMenuMobile" onclick="window.openSidebar && window.openSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <h1 class="titulo-pagina"><i class="fas fa-cog"></i> Configurações</h1>
        </div>
        <button class="btn-guardar-tudo" onclick="guardarTodasConfiguracoes()">
            <i class="fas fa-save"></i> Guardar Todas
        </button>
    </header>

    <div class="conteudo-pagina">
        <div class="abas">
            <button class="aba ativa" onclick="mudarAba(0)"><i class="fas fa-school"></i> Escola</button>
            <button class="aba" onclick="mudarAba(1)"><i class="fas fa-share-alt"></i> Redes Sociais</button>
            <button class="aba" onclick="mudarAba(2)"><i class="fas fa-cogs"></i> Tecnico</button>
            <button class="aba" onclick="mudarAba(3)"><i class="fas fa-database"></i> Backup</button>
            <button class="aba" onclick="mudarAba(4)"><i class="fas fa-tools"></i> Manutencao</button>
        </div>

        <!-- ABA 1: ESCOLA -->
        <div class="conteudo-aba ativa" id="abaEscola">
            <div class="secao">
                <h3 class="titulo-secao"><i class="fas fa-info-circle"></i> Informacoes da Escola</h3>
                <div class="grupo-form">
                    <label>Nome da Instituicao</label>
                    <input type="text" class="campo-form" id="escola_nome" value="<?= htmlspecialchars($config['instituicao_nome'] ?? 'Instituto Medio Politecnico Industrial do Kilamba Kiaxi') ?>">
                </div>
                <div class="linha-form">
                    <div class="grupo-form"><label>Acronimo</label><input type="text" class="campo-form" id="escola_acronimo" value="<?= htmlspecialchars($config['instituicao_acronimo'] ?? 'IPIKK') ?>"></div>
                    <div class="grupo-form"><label>Slogan/Lema</label><input type="text" class="campo-form" id="escola_slogan" value="<?= htmlspecialchars($config['instituicao_slogan'] ?? 'Um diferencial para a sua formacao') ?>"></div>
                </div>
                <div class="grupo-form"><label>Endereco Completo</label><input type="text" class="campo-form" id="escola_endereco" value="<?= htmlspecialchars($config['endereco_completo'] ?? 'Distrito Urbano da Nova-Vida, Rua 130, Kilamba Kiaxi') ?>"></div>
                <div class="linha-form">
                    <div class="grupo-form"><label>Cidade</label><input type="text" class="campo-form" id="escola_cidade" value="<?= htmlspecialchars($config['cidade'] ?? 'Luanda') ?>"></div>
                    <div class="grupo-form"><label>Provincia</label><input type="text" class="campo-form" id="escola_provincia" value="<?= htmlspecialchars($config['provincia'] ?? 'Luanda') ?>"></div>
                </div>
                <div class="linha-form">
                    <div class="grupo-form"><label>Telefone</label><input type="text" class="campo-form" id="escola_telefone" value="<?= htmlspecialchars($config['telefone'] ?? '933 096 705') ?>"></div>
                    <div class="grupo-form"><label>Email</label><input type="email" class="campo-form" id="escola_email" value="<?= htmlspecialchars($config['email_geral'] ?? 'geral@ipikk.ao') ?>"></div>
                    <div class="grupo-form"><label>WhatsApp</label><input type="text" class="campo-form" id="escola_whatsapp" value="<?= htmlspecialchars($config['whatsapp_numero'] ?? '933 096 705') ?>"></div>
                </div>
                <div class="grupo-form"><label>Horario de Funcionamento</label><input type="text" class="campo-form" id="escola_horario" value="<?= htmlspecialchars($config['horario_funcionamento'] ?? 'Segunda a Sexta: 7:00 - 17:40') ?>"></div>
            </div>

            <div class="secao">
                <h3 class="titulo-secao"><i class="fas fa-link"></i> Links do Rodapé (IPIKK / Links Rápidos)</h3>
                <p style="margin-bottom: 10px; font-size: 13px; color: #666;">Defina o nome do link (esquerda) e selecione a página de destino (direita).</p>
                <div id="builder_ipikk" class="links-builder" data-target="rodape_links_ipikk"></div>
                <button type="button" class="btn btn-secundario" onclick="adicionarLinhaLink('builder_ipikk')"><i class="fas fa-plus"></i> Adicionar Link IPIKK</button>
                <hr style="margin: 20px 0; border: 1px solid #eee;">
                <div id="builder_rapidos" class="links-builder" data-target="rodape_links_rapidos"></div>
                <p style="margin:8px 0; font-size:12px; color:#666;">Nos Links Rápidos o destino é digitado manualmente (URL livre).</p>
                <button type="button" class="btn btn-secundario" onclick="adicionarLinhaLink('builder_rapidos')"><i class="fas fa-plus"></i> Adicionar Link Rápido</button>
                <textarea class="area-texto" rows="5" id="rodape_links_ipikk" style="display:none;"><?= htmlspecialchars($config['rodape_links_ipikk'] ?? "Sobre Nós|sobre-nos.php
Inscrição|inscricoes.php
Contactos|contatos.php
Área Restrita|area-restrita.php
Políticas de Privacidade|politica-privacidade.php") ?></textarea>
                <textarea class="area-texto" rows="5" id="rodape_links_rapidos" style="display:none;"><?= htmlspecialchars($config['rodape_links_rapidos'] ?? "Governo de Angola|https://governo.gov.ao/
Governo Provincial de Luanda|https://luanda.gov.ao/
Ministério da Educação|https://med.gov.ao/
Instituto de Telecomunicações|https://itel.gov.ao/
Webmail IPIKK|https://webmail.ipikk.ao/") ?></textarea>
            </div>

            <div class="secao">
                <h3 class="titulo-secao"><i class="fas fa-image"></i> Logotipo e Imagens</h3>
                <div class="grupo-form">
                    <label>Logotipo Atual</label>
                    <div class="upload-arquivo">
                        <span class="preview-arquivo" id="preview_logo"><?= basename($config['logo_url'] ?? 'ipikk_new_logo.png') ?></span>
                        <button class="btn btn-secundario" onclick="document.getElementById('input_logo').click()"><i class="fas fa-upload"></i> Alterar</button>
                        <input type="file" id="input_logo" accept="image/*" style="display: none;">
                    </div>
                </div>
                <div class="grupo-form">
                    <label>Favicon</label>
                    <div class="upload-arquivo">
                        <span class="preview-arquivo" id="preview_favicon"><?= basename($config['favicon_url'] ?? 'ipikk_new_logo.png') ?></span>
                        <button class="btn btn-secundario" onclick="document.getElementById('input_favicon').click()"><i class="fas fa-upload"></i> Alterar</button>
                        <input type="file" id="input_favicon" accept=".ico,.png" style="display: none;">
                    </div>
                </div>
            </div>

            <div class="rodape-acoes">
                <button class="btn btn-primario" onclick="guardarEscola()"><i class="fas fa-save"></i> Guardar Alteracoes</button>
            </div>
        </div>

        <!-- ABA 2: REDES SOCIAIS -->
        <div class="conteudo-aba" id="abaRedes">
            <div class="secao">
                <h3 class="titulo-secao"><i class="fas fa-share-alt"></i> Redes Sociais</h3>
                <div class="grupo-form"><label><i class="fab fa-facebook"></i> Facebook</label><input type="url" class="campo-form" id="social_facebook" value="<?= htmlspecialchars($config['rede_social_facebook'] ?? '') ?>"></div>
                <div class="grupo-form"><label><i class="fab fa-instagram"></i> Instagram</label><input type="url" class="campo-form" id="social_instagram" value="<?= htmlspecialchars($config['rede_social_instagram'] ?? '') ?>"></div>
                <div class="grupo-form"><label><i class="fab fa-linkedin"></i> LinkedIn</label><input type="url" class="campo-form" id="social_linkedin" value="<?= htmlspecialchars($config['rede_social_linkedin'] ?? '') ?>"></div>
                
                <h4 style="margin: 25px 0 15px;"><i class="fas fa-cog"></i> Configuracoes</h4>
                <div class="grupo-checkbox"><input type="checkbox" id="mostrarCabecalho" <?= $config['mostrar_social_header'] ? 'checked' : '' ?>> <label>Mostrar icones no cabecalho</label></div>
                <div class="grupo-checkbox"><input type="checkbox" id="mostrarRodape" <?= $config['mostrar_social_footer'] ? 'checked' : '' ?>> <label>Mostrar icones no rodape</label></div>
                <div class="grupo-checkbox"><input type="checkbox" id="novaJanela" <?= $config['social_nova_janela'] ? 'checked' : '' ?>> <label>Abrir links em nova janela</label></div>
            </div>
            <div class="rodape-acoes"><button class="btn btn-primario" onclick="guardarRedesSociais()"><i class="fas fa-save"></i> Guardar Alteracoes</button></div>
        </div>

        <!-- ABA 3: TECNICO -->
        <div class="conteudo-aba" id="abaTecnico">
            <div class="secao">
                <h3 class="titulo-secao"><i class="fas fa-envelope"></i> Configuracoes de Email</h3>
                <div class="linha-form">
                    <div class="grupo-form"><label>SMTP Host</label><input type="text" class="campo-form" id="smtp_host" value="<?= htmlspecialchars($config['smtp_host'] ?? 'smtp.gmail.com') ?>"></div>
                    <div class="grupo-form"><label>SMTP Porta</label><input type="number" class="campo-form" id="smtp_porta" value="<?= $config['smtp_porta'] ?? 587 ?>"></div>
                    <div class="grupo-form"><label>Seguranca</label><select class="selecao-form" id="smtp_seguranca"><option value="tls" <?= ($config['smtp_seguranca'] ?? 'tls') == 'tls' ? 'selected' : '' ?>>TLS</option><option value="ssl">SSL</option></select></div>
                </div>
                <div class="grupo-form"><label>Email de Envio</label><input type="email" class="campo-form" id="smtp_email" value="<?= htmlspecialchars($config['smtp_email'] ?? '') ?>"></div>
                <div class="grupo-form"><label>Senha</label><input type="password" class="campo-form" id="smtp_senha" value="<?= htmlspecialchars($config['smtp_senha'] ?? '') ?>"></div>
                <button class="btn btn-secundario" onclick="testarEmail()"><i class="fas fa-paper-plane"></i> Enviar Email de Teste</button>
            </div>

            <div class="secao">
                <h3 class="titulo-secao"><i class="fas fa-search"></i> Configuracoes SEO</h3>
                <div class="grupo-form"><label>Titulo Padrao</label><input type="text" class="campo-form" id="seo_titulo" value="<?= htmlspecialchars($config['seo_titulo'] ?? 'IPIKK - Instituto Politecnico Industrial') ?>"></div>
                <div class="grupo-form"><label>Meta Descricao</label><textarea class="area-texto" rows="3" id="seo_descricao"><?= htmlspecialchars($config['seo_descricao'] ?? 'Formacao tecnica especializada') ?></textarea></div>
                <div class="grupo-form"><label>Palavras-chave</label><input type="text" class="campo-form" id="seo_keywords" value="<?= htmlspecialchars($config['seo_keywords'] ?? 'IPIKK, formacao tecnica') ?>"></div>
                <div class="grupo-form"><label>URL do Site</label><input type="url" class="campo-form" id="seo_url" value="<?= htmlspecialchars($config['seo_url'] ?? 'https://www.ipikk.ao') ?>"></div>
            </div>

            <div class="rodape-acoes"><button class="btn btn-primario" onclick="guardarTecnico()"><i class="fas fa-save"></i> Guardar Alteracoes</button></div>
        </div>

        <!-- ABA 4: BACKUP (MELHORADA) -->
        <div class="conteudo-aba" id="abaBackup">
            <div class="secao">
                <h3 class="titulo-secao"><i class="fas fa-clock"></i> Backup Automatico</h3>
                <div class="linha-form">
                    <div class="grupo-form"><label>Frequencia</label><select class="selecao-form" id="backup_frequencia"><option value="diario">Diario</option><option value="semanal" <?= ($config['backup_frequencia'] ?? 'semanal') == 'semanal' ? 'selected' : '' ?>>Semanal</option><option value="mensal">Mensal</option></select></div>
                    <div class="grupo-form"><label>Horario</label><input type="time" class="campo-form" id="backup_horario" value="<?= $config['backup_horario'] ?? '02:00' ?>"></div>
                    <div class="grupo-form"><label>Manter ultimos</label><select class="selecao-form" id="backup_manter"><option value="2">2 backups</option><option value="4" <?= ($config['backup_manter'] ?? 4) == 4 ? 'selected' : '' ?>>4 backups</option><option value="10">10 backups</option></select></div>
                </div>
                <div class="linha-form" style="margin-top: 15px;">
                    <div class="grupo-form">
                        <label>Tipo de Backup</label>
                        <select id="backup_tipo" class="selecao-form">
                            <option value="completo">Completo (Base de dados + Configurações)</option>
                            <option value="dados">Apenas Base de dados</option>
                            <option value="ficheiros">Apenas Ficheiros de configuração</option>
                        </select>
                    </div>
                </div>
                <button class="btn btn-primario" onclick="executarBackupAgora(this)"><i class="fas fa-sync"></i> Executar Backup Agora</button>
            </div>

            <div class="secao">
                <h3 class="titulo-secao"><i class="fas fa-list"></i> Backups Disponiveis</h3>
                <div class="lista-backup" id="lista_backup">
                    <?php foreach ($backups as $backup): ?>
                    <div class="item-backup" data-nome="<?= htmlspecialchars($backup['nome']) ?>">
                        <div class="info-backup">
                            <strong><?= htmlspecialchars($backup['nome']) ?></strong>
                            <p><?= $backup['tamanho'] ?>MB - <?= $backup['data'] ?></p>
                        </div>
                        <div class="acoes-backup">
                            <button class="btn-backup btn-backup-download" onclick="baixarBackup('<?= htmlspecialchars($backup['nome']) ?>')" title="Baixar">
                                <i class="fas fa-download"></i> Baixar
                            </button>
                            <button class="btn-backup btn-backup-restaurar" onclick="restaurarBackup('<?= htmlspecialchars($backup['nome']) ?>')" title="Restaurar">
                                <i class="fas fa-undo-alt"></i> Restaurar
                            </button>
                            <button class="btn-backup btn-backup-eliminar" onclick="eliminarBackup('<?= htmlspecialchars($backup['nome']) ?>')" title="Eliminar">
                                <i class="fas fa-trash-alt"></i> Eliminar
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($backups)): ?>
                    <div class="empty-backup">
                        <i class="fas fa-database" style="font-size: 48px; margin-bottom: 15px; display: block;"></i>
                        <p>Nenhum backup encontrado.</p>
                        <p style="font-size: 12px; margin-top: 8px;">Clique em "Executar Backup Agora" para criar o primeiro backup.</p>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="info-backup-total">
                    <i class="fas fa-info-circle"></i> Total: <?= count($backups) ?> backup(s) | Espaço total: <?= array_sum(array_column($backups, 'tamanho')) ?> MB
                </div>
            </div>

            <div class="rodape-acoes"><button class="btn btn-primario" onclick="guardarConfigBackup()"><i class="fas fa-save"></i> Guardar Configuracoes</button></div>
        </div>

        <!-- ABA 5: MANUTENCAO -->
        <div class="conteudo-aba" id="abaManutencao">
            <div class="secao">
                <h3 class="titulo-secao"><i class="fas fa-tools"></i> Modo de Manutencao</h3>
                
                <div class="card-status">
                    <h4>Status do Site</h4>
                    <div id="statusManutencao" class="status-badge status-inativo"><i class="fas fa-check-circle"></i> SITE OPERACIONAL</div>
                    <div id="contadorManutencao" style="margin-top: 10px;"></div>
                </div>

                <div class="card-controle">
                    <h4><i class="fas fa-hand-pointer"></i> Controle Manual</h4>
                    <div style="display: flex; gap: 15px; margin-top: 15px; flex-wrap: wrap;">
                        <button class="btn btn-perigo" onclick="ativarManutencao()"><i class="fas fa-toggle-on"></i> Ativar Modo Manutencao</button>
                        <button class="btn btn-primario" onclick="desativarManutencao()"><i class="fas fa-toggle-off"></i> Desativar Modo Manutencao</button>
                    </div>
                </div>

                <div class="card-agendamento">
                    <h4><i class="fas fa-calendar-alt"></i> Agendamento Automatico</h4>
                    <div class="linha-form">
                        <div class="grupo-form"><label>Data e Hora de Inicio</label><input type="datetime-local" id="manutencaoInicio" class="campo-form"></div>
                        <div class="grupo-form"><label>Data e Hora de Termino</label><input type="datetime-local" id="manutencaoFim" class="campo-form"></div>
                    </div>
                    <button class="btn btn-secundario" onclick="agendarManutencao()"><i class="fas fa-save"></i> Agendar Manutencao</button>
                </div>

                <div class="card-mensagem">
                    <h4><i class="fas fa-edit"></i> Mensagem Personalizada</h4>
                    <div class="grupo-form"><label>Titulo da Pagina</label><input type="text" id="manutencaoTitulo" class="campo-form" value="<?= htmlspecialchars($config['manutencao_titulo'] ?? 'Site em Manutencao') ?>"></div>
                    <div class="grupo-form"><label>Mensagem Principal</label><input type="text" id="manutencaoMsgPrincipal" class="campo-form" value="<?= htmlspecialchars($config['manutencao_mensagem_principal'] ?? 'Estamos realizando melhorias para lhe servir melhor.') ?>"></div>
                    <div class="grupo-form"><label>Detalhes (cada linha sera um paragrafo)</label><textarea id="manutencaoDetalhes" class="area-texto" rows="4"><?= htmlspecialchars($config['manutencao_detalhes'] ?? 'O site estara disponivel em breve.\nEstamos atualizando nossos sistemas.\nAgradecemos pela paciencia.') ?></textarea></div>
                    <div class="grupo-form"><label>Previsao de Retorno</label><input type="text" id="manutencaoPrevisao" class="campo-form" value="<?= htmlspecialchars($config['manutencao_previsao'] ?? 'em breve') ?>"></div>
                </div>

                <div class="card-contactos">
                    <h4><i class="fas fa-phone-alt"></i> Contactos para Emergencia</h4>
                    <div class="linha-form">
                        <div class="grupo-form"><label>Telefone</label><input type="text" id="manutencaoTelefone" class="campo-form" value="<?= htmlspecialchars($config['manutencao_telefone'] ?? $config['telefone'] ?? '') ?>"></div>
                        <div class="grupo-form"><label>WhatsApp</label><input type="text" id="manutencaoWhatsapp" class="campo-form" value="<?= htmlspecialchars($config['manutencao_whatsapp'] ?? $config['whatsapp_numero'] ?? '') ?>"></div>
                    </div>
                    <div class="grupo-form"><label>Email</label><input type="email" id="manutencaoEmail" class="campo-form" value="<?= htmlspecialchars($config['manutencao_email'] ?? $config['email_geral'] ?? '') ?>"></div>
                </div>

                <div class="card-preview">
                    <h4><i class="fas fa-eye"></i> Pre-visualizacao</h4>
                    <button class="btn btn-info" onclick="previewManutencao()"><i class="fas fa-external-link-alt"></i> Ver Como Fica a Pagina</button>
                </div>

                <div class="rodape-acoes">
                    <button class="btn btn-primario" onclick="guardarConfigManutencao()"><i class="fas fa-save"></i> Guardar Configuracoes</button>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- MODAL DE CONFIRMAÇÃO PARA RESTAURAÇÃO -->
<div id="modalConfirmacaoBackup" class="modal-confirmacao">
    <div class="modal-confirmacao-caixa">
        <div class="modal-confirmacao-icone" id="modalIcone">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h3 id="modalTitulo">Confirmar restauração</h3>
        <p id="modalMensagem">Tem certeza que deseja restaurar este backup? Esta ação irá substituir todos os dados atuais.</p>
        <div class="modal-confirmacao-botoes">
            <button class="botao-cancelar-modal" onclick="fecharModalConfirmacaoBackup()">Cancelar</button>
            <button class="botao-confirmar-modal" id="btnConfirmarAcaoBackup">Confirmar</button>
        </div>
    </div>
</div>

<script src="js/admin-sidebar-header.js"></script>
<script>
    const configData = <?php echo json_encode($config, JSON_UNESCAPED_UNICODE); ?>;
    let abaAtual = 0;
    let acaoBackupPendente = null;

    function mostrarNotificacao(mensagem, tipo = 'sucesso') {
        const notif = document.createElement('div');
        notif.className = `notificacao ${tipo}`;
        notif.innerHTML = `<i class="fas ${tipo === 'sucesso' ? 'fa-check-circle' : tipo === 'erro' ? 'fa-exclamation-circle' : 'fa-info-circle'}"></i> ${mensagem}`;
        document.body.appendChild(notif);
        setTimeout(() => notif.remove(), 3000);
    }

    function mudarAba(indice) {
        const abas = document.querySelectorAll('.aba');
        const conteudos = document.querySelectorAll('.conteudo-aba');
        if (indice < 0 || indice >= abas.length) indice = 0;
        abas.forEach(aba => aba.classList.remove('ativa'));
        conteudos.forEach(conteudo => conteudo.classList.remove('ativa'));
        abas[indice].classList.add('ativa');
        conteudos[indice].classList.add('ativa');
        abaAtual = indice;
        try {
            localStorage.setItem('admin_configuracoes_aba', String(indice));
        } catch (e) {}
        if (window.location.hash !== `#aba=${indice}`) {
            history.replaceState(null, '', `#aba=${indice}`);
        }
    }

    async function guardarTodasConfiguracoes() {
        await guardarEscola();
        await guardarRedesSociais();
        await guardarTecnico();
        await guardarConfigBackup();
        await guardarConfigManutencao();
        mostrarNotificacao('Todas as configuracoes foram guardadas!', 'sucesso');
    }

    // ESCOLA
    async function guardarEscola() {
        const formData = new URLSearchParams();
        formData.append('action', 'salvar_escola');
        formData.append('instituicao_nome', document.getElementById('escola_nome').value);
        formData.append('instituicao_acronimo', document.getElementById('escola_acronimo').value);
        formData.append('instituicao_slogan', document.getElementById('escola_slogan').value);
        formData.append('endereco_completo', document.getElementById('escola_endereco').value);
        formData.append('cidade', document.getElementById('escola_cidade').value);
        formData.append('provincia', document.getElementById('escola_provincia').value);
        formData.append('telefone', document.getElementById('escola_telefone').value);
        formData.append('email_geral', document.getElementById('escola_email').value);
        formData.append('whatsapp_numero', document.getElementById('escola_whatsapp').value);
        formData.append('horario_funcionamento', document.getElementById('escola_horario').value);
        formData.append('rodape_links_ipikk', document.getElementById('rodape_links_ipikk').value);
        formData.append('rodape_links_rapidos', document.getElementById('rodape_links_rapidos').value);
        
        const response = await fetch(window.location.href, { method: 'POST', body: formData });
        const data = await response.json();
        mostrarNotificacao(data.message, data.success ? 'sucesso' : 'erro');
    }

    // REDES SOCIAIS
    async function guardarRedesSociais() {
        const formData = new URLSearchParams();
        formData.append('action', 'salvar_redes');
        formData.append('facebook', document.getElementById('social_facebook').value);
        formData.append('instagram', document.getElementById('social_instagram').value);
        formData.append('linkedin', document.getElementById('social_linkedin').value);
        formData.append('mostrar_header', document.getElementById('mostrarCabecalho').checked ? '1' : '0');
        formData.append('mostrar_footer', document.getElementById('mostrarRodape').checked ? '1' : '0');
        formData.append('nova_janela', document.getElementById('novaJanela').checked ? '1' : '0');
        
        const response = await fetch(window.location.href, { method: 'POST', body: formData });
        const data = await response.json();
        mostrarNotificacao(data.message, data.success ? 'sucesso' : 'erro');
    }

    // TECNICO
    async function guardarTecnico() {
        const formData = new URLSearchParams();
        formData.append('action', 'salvar_tecnico');
        formData.append('smtp_host', document.getElementById('smtp_host').value);
        formData.append('smtp_porta', document.getElementById('smtp_porta').value);
        formData.append('smtp_seguranca', document.getElementById('smtp_seguranca').value);
        formData.append('smtp_email', document.getElementById('smtp_email').value);
        formData.append('smtp_senha', document.getElementById('smtp_senha').value);
        formData.append('seo_titulo', document.getElementById('seo_titulo').value);
        formData.append('seo_descricao', document.getElementById('seo_descricao').value);
        formData.append('seo_keywords', document.getElementById('seo_keywords').value);
        formData.append('seo_url', document.getElementById('seo_url').value);
        
        const response = await fetch(window.location.href, { method: 'POST', body: formData });
        const data = await response.json();
        mostrarNotificacao(data.message, data.success ? 'sucesso' : 'erro');
    }

    async function testarEmail() {
        const email = prompt('Digite o email para receber o teste:', '<?= $usuario_logado['email'] ?>');
        if (!email) return;
        
        const formData = new URLSearchParams();
        formData.append('action', 'testar_email');
        formData.append('email_teste', email);
        
        const response = await fetch(window.location.href, { method: 'POST', body: formData });
        const data = await response.json();
        mostrarNotificacao(data.message, data.success ? 'sucesso' : 'erro');
    }

    // BACKUP
    async function guardarConfigBackup() {
        const formData = new URLSearchParams();
        formData.append('action', 'salvar_backup');
        formData.append('backup_frequencia', document.getElementById('backup_frequencia').value);
        formData.append('backup_horario', document.getElementById('backup_horario').value);
        formData.append('backup_manter', document.getElementById('backup_manter').value);
        
        const response = await fetch(window.location.href, { method: 'POST', body: formData });
        const data = await response.json();
        mostrarNotificacao(data.message, data.success ? 'sucesso' : 'erro');
    }


    const paginasDisponiveis = ['sobre-nos.php','inscricoes.php','contatos.php','area-restrita.php','politica-privacidade.php','cursos.php','noticias.php','index.php'];
    function adicionarLinhaLink(builderId, nome = '', url = '') {
        const builder = document.getElementById(builderId);
        const row = document.createElement('div');
        row.className = 'link-row';
        const isRapidos = builderId === 'builder_rapidos';
        const campoDestino = isRapidos
            ? `<input class="campo-form link-url" placeholder="URL de destino" value="${url}">`
            : `<select class="selecao-form link-url">${paginasDisponiveis.map(p=>`<option value="${p}" ${p===url?'selected':''}>${p}</option>`).join('')}</select>`;
        row.innerHTML = `<input class="campo-form link-nome" placeholder="Nome do link" value="${nome}">${campoDestino}
            <button type="button" class="btn btn-perigo" onclick="this.closest('.link-row').remove();sincronizarTextareasLinks();"><i class="fas fa-trash"></i></button>`;
        builder.appendChild(row);
    }
    function carregarBuilderLinks(builderId){const b=document.getElementById(builderId);const target=document.getElementById(b.dataset.target);target.value.split('\n').filter(Boolean).forEach(l=>{const [n,u]=l.split('|');adicionarLinhaLink(builderId,n||'',u||'');});if(!b.children.length)adicionarLinhaLink(builderId);b.addEventListener('input',sincronizarTextareasLinks);b.addEventListener('change',sincronizarTextareasLinks);}
    function sincronizarTextareasLinks(){['builder_ipikk','builder_rapidos'].forEach(id=>{const b=document.getElementById(id);const linhas=[...b.querySelectorAll('.link-row')].map(r=>`${r.querySelector('.link-nome').value.trim()}|${r.querySelector('.link-url').value}`).filter(v=>v.split('|')[0]);document.getElementById(b.dataset.target).value=linhas.join('\n');});}
    function renderProgressoBackup(titulo, percent, etapa){let box=document.getElementById('backupProgressBox');if(!box){box=document.createElement('div');box.id='backupProgressBox';box.className='backup-progress';document.body.appendChild(box);}box.innerHTML=`<strong>${titulo}</strong><div class="progress-bar-wrap"><div class="progress-bar-fill" style="width:${percent}%"></div></div><div>${percent}% completo</div><small>${etapa}</small>`;if(percent>=100){setTimeout(()=>box.remove(),3000);}}

    async function executarBackupAgora(botao = null) {
        const tipo = document.getElementById('backup_tipo')?.value || 'completo';
        const nome = `backup_${tipo}_${new Date().toISOString().slice(0,19).replace(/:/g, '-')}.zip`;
        
        const formData = new URLSearchParams();
        formData.append('action', 'executar_backup');
        formData.append('tipo', tipo);
        formData.append('nome', nome);
        
        const btn = botao || document.querySelector('[onclick="executarBackupAgora(this)"]');
        if (btn) btn.disabled = true;
        const etapas = [[10,'A conectar à base de dados...'],[20,'A exportar estrutura das tabelas...'],[40,'A exportar registos da base de dados...'],[60,'A processar ficheiros de configuração...'],[80,'A criar ficheiro ZIP...'],[95,'A finalizar backup...']];
        etapas.forEach((e,i)=>setTimeout(()=>renderProgressoBackup('⏳ A CRIAR BACKUP...', e[0], e[1]), i*350));
        try {
            const response = await fetch(window.location.href, { method: 'POST', body: formData });
            const data = await response.json();

            if (data.success) {
            renderProgressoBackup('✅ BACKUP CRIADO COM SUCESSO!', 100, data.nome || 'Concluído');
            mostrarNotificacao(data.message, 'sucesso');
            location.reload();
            } else {
                renderProgressoBackup('❌ ERRO AO CRIAR BACKUP', 100, data.message || 'Falha ao criar backup.');
                mostrarNotificacao(data.message || 'Falha ao criar backup.', 'erro');
            }
        } catch (error) {
            renderProgressoBackup('❌ ERRO AO CRIAR BACKUP', 100, 'Falha de comunicação com o servidor');
            mostrarNotificacao('Erro de comunicação ao criar backup.', 'erro');
            console.error(error);
        } finally {
            if (btn) btn.disabled = false;
        }
        if (btn) btn.disabled = false;
    }

    function baixarBackup(nome) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = window.location.href;
        form.style.display = 'none';

        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'baixar_backup';

        const nomeInput = document.createElement('input');
        nomeInput.type = 'hidden';
        nomeInput.name = 'nome';
        nomeInput.value = nome;

        form.appendChild(actionInput);
        form.appendChild(nomeInput);
        document.body.appendChild(form);
        form.submit();
        form.remove();
    }

    async function eliminarBackup(nome) {
        if (!confirm('Eliminar este backup permanentemente?')) return;
        
        const formData = new URLSearchParams();
        formData.append('action', 'eliminar_backup');
        formData.append('nome', nome);
        
        const response = await fetch(window.location.href, { method: 'POST', body: formData });
        const data = await response.json();
        mostrarNotificacao(data.message, data.success ? 'sucesso' : 'erro');
        if (data.success) location.reload();
    }

    // NOVA FUNÇÃO: RESTAURAR BACKUP COM CONFIRMAÇÃO
    function restaurarBackup(nome) {
        const modal = document.getElementById('modalConfirmacaoBackup');
        const tituloEl = document.getElementById('modalTitulo');
        const mensagemEl = document.getElementById('modalMensagem');
        const iconeEl = document.getElementById('modalIcone');
        
        tituloEl.textContent = 'Restaurar Backup';
        mensagemEl.innerHTML = `Tem certeza que deseja restaurar o backup <strong>${nome}</strong>?<br><br>
        <strong style="color: #dc3545;">ATENÇÃO:</strong> Esta ação irá substituir dados e ficheiros de configuração atuais pelo estado do backup. Uploads na Cloudinary não são afetados.<br>
        Recomenda-se fazer um backup atual antes de prosseguir.`;
        iconeEl.innerHTML = '<i class="fas fa-exclamation-triangle"></i>';
        iconeEl.style.background = '#fff3cd';
        iconeEl.style.color = '#856404';
        
        acaoBackupPendente = async () => {
            const formData = new URLSearchParams();
            formData.append('action', 'restaurar_backup');
            formData.append('nome', nome);
            
            mostrarNotificacao('A restaurar backup...', 'info');
            
            try {
                const response = await fetch(window.location.href, { method: 'POST', body: formData });
                const data = await response.json();
                
                if (data.success) {
                    mostrarNotificacao(data.message, 'sucesso');
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    mostrarNotificacao(data.message, 'erro');
                }
            } catch (error) {
                mostrarNotificacao('Erro ao restaurar backup', 'erro');
            }
            fecharModalConfirmacaoBackup();
        };
        
        modal.classList.add('ativo');
        document.body.style.overflow = 'hidden';
    }

    function fecharModalConfirmacaoBackup() {
        const modal = document.getElementById('modalConfirmacaoBackup');
        modal.classList.remove('ativo');
        acaoBackupPendente = null;
        document.body.style.overflow = '';
    }

    // UPLOAD LOGO
    document.getElementById('input_logo')?.addEventListener('change', async function(e) {
        if (!e.target.files.length) return;
        const formData = new FormData();
        formData.append('action', 'upload_logo');
        formData.append('logo', e.target.files[0]);
        
        const response = await fetch(window.location.href, { method: 'POST', body: formData });
        const data = await response.json();
        mostrarNotificacao(data.message, data.success ? 'sucesso' : 'erro');
        if (data.success) document.getElementById('preview_logo').textContent = e.target.files[0].name;
    });

    document.getElementById('input_favicon')?.addEventListener('change', async function(e) {
        if (!e.target.files.length) return;
        const formData = new FormData();
        formData.append('action', 'upload_favicon');
        formData.append('favicon', e.target.files[0]);
        
        const response = await fetch(window.location.href, { method: 'POST', body: formData });
        const data = await response.json();
        mostrarNotificacao(data.message, data.success ? 'sucesso' : 'erro');
        if (data.success) document.getElementById('preview_favicon').textContent = e.target.files[0].name;
    });

    // MANUTENCAO
let configManutencao = {
    ativo: <?= (int)($config['modo_manutencao'] ?? 0) ?>,
    modo: 'manual',
    dataInicio: '<?= $config['manutencao_inicio'] ?? '' ?>',
    dataFim: '<?= $config['manutencao_fim'] ?? '' ?>',
    conteudo: {
        titulo: '<?= addslashes($config['manutencao_titulo'] ?? 'Site em Manutencao') ?>',
        mensagemPrincipal: '<?= addslashes($config['manutencao_mensagem_principal'] ?? 'Estamos realizando melhorias para lhe servir melhor.') ?>',
        detalhes: <?= json_encode(explode("\n", $config['manutencao_detalhes'] ?? "O site estara disponivel em breve.\nEstamos atualizando nossos sistemas.\nAgradecemos pela paciencia.")) ?>,
        previsaoRetorno: '<?= addslashes($config['manutencao_previsao'] ?? 'em breve') ?>'
    },
    contactos: {
        telefone: '<?= addslashes($config['manutencao_telefone'] ?? $config['telefone'] ?? '') ?>',
        whatsapp: '<?= addslashes($config['manutencao_whatsapp'] ?? $config['whatsapp_numero'] ?? '') ?>',
        email: '<?= addslashes($config['manutencao_email'] ?? $config['email_geral'] ?? '') ?>'
    }
};

function atualizarInterfaceManutencao() {
    const statusDiv = document.getElementById('statusManutencao');
    const contadorDiv = document.getElementById('contadorManutencao');
    
    // CORREÇÃO: Forçar recarga do status do banco
    if (configManutencao.ativo === true || configManutencao.ativo === 1) {
        statusDiv.innerHTML = '<i class="fas fa-tools"></i> EM MANUTENCAO - Site indisponivel para visitantes';
        statusDiv.className = 'status-badge status-ativo';
        contadorDiv.innerHTML = '<i class="fas fa-clock"></i> Manutencao ativa (apenas administradores veem o site)';
    } else {
        statusDiv.innerHTML = '<i class="fas fa-check-circle"></i> SITE OPERACIONAL';
        statusDiv.className = 'status-badge status-inativo';
        contadorDiv.innerHTML = '<i class="fas fa-globe"></i> Site funcionando normalmente';
    }
}

function ativarManutencao() { 
    if (confirm('ATENÇÃO: Ao ativar o modo de manutenção, todos os visitantes verão apenas a página de manutenção. Apenas administradores poderão aceder ao site normalmente. Deseja continuar?')) {
        configManutencao.ativo = true; 
        guardarConfigManutencao(); 
        mostrarNotificacao('Modo de manutencao ATIVADO! Visitantes verão a página de manutenção.', 'sucesso'); 
    }
}

function desativarManutencao() { 
    if (confirm('Desativar o modo de manutenção? O site voltará a funcionar normalmente para todos os visitantes.')) {
        configManutencao.ativo = false; 
        guardarConfigManutencao(); 
        mostrarNotificacao('Modo de manutencao DESATIVADO! Site acessível a todos.', 'sucesso'); 
    }
}

function agendarManutencao() {
    const inicio = document.getElementById('manutencaoInicio').value;
    if (!inicio) { 
        mostrarNotificacao('Defina a data de inicio', 'erro'); 
        return; 
    }
    configManutencao.dataInicio = inicio;
    configManutencao.dataFim = document.getElementById('manutencaoFim').value;
    configManutencao.modo = 'agendado';
    // Também ativar o modo de manutenção quando agendado
    configManutencao.ativo = true;
    guardarConfigManutencao();
    atualizarInterfaceManutencao();
    mostrarNotificacao('Manutencao agendada! O site entrará em manutenção na data definida.', 'sucesso');
}

async function guardarConfigManutencao() {
    const titulo = document.getElementById('manutencaoTitulo').value;
    const msgPrincipal = document.getElementById('manutencaoMsgPrincipal').value;
    const detalhes = document.getElementById('manutencaoDetalhes').value;
    const previsao = document.getElementById('manutencaoPrevisao').value;
    const telefone = document.getElementById('manutencaoTelefone').value;
    const whatsapp = document.getElementById('manutencaoWhatsapp').value;
    const email = document.getElementById('manutencaoEmail').value;
    const inicio = document.getElementById('manutencaoInicio').value;
    const fim = document.getElementById('manutencaoFim').value;

    const modoAtivo = configManutencao.ativo ? 1 : 0;

    const formData = new URLSearchParams();
    formData.append('action', 'salvar_manutencao');
    formData.append('modo_manutencao', modoAtivo);
    formData.append('manutencao_titulo', titulo);
    formData.append('manutencao_mensagem_principal', msgPrincipal);
    formData.append('manutencao_detalhes', detalhes);
    formData.append('manutencao_previsao', previsao);
    formData.append('manutencao_telefone', telefone);
    formData.append('manutencao_whatsapp', whatsapp);
    formData.append('manutencao_email', email);
    formData.append('manutencao_inicio', inicio);
    formData.append('manutencao_fim', fim);

    try {
        const response = await fetch(window.location.href, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: formData
        });
        const data = await response.json();

        if (data.success) {
            mostrarNotificacao(data.message, 'sucesso');
            atualizarInterfaceManutencao();
        } else {
            renderProgressoBackup('❌ ERRO AO CRIAR BACKUP', 100, data.message);
            mostrarNotificacao(data.message, 'erro');
        }
        if (btn) btn.disabled = false;
    } catch (error) {
        console.error('Erro:', error);
        mostrarNotificacao('Erro ao guardar configurações de manutenção', 'erro');
    }
}

function previewManutencao() {
    guardarConfigManutencao();
    window.open('../area-publica/site-manutencao.php?preview=1', '_blank');
}

// Carregar valores atuais no DOM
document.getElementById('manutencaoTitulo').value = configManutencao.conteudo.titulo;
document.getElementById('manutencaoMsgPrincipal').value = configManutencao.conteudo.mensagemPrincipal;
document.getElementById('manutencaoDetalhes').value = configManutencao.conteudo.detalhes.join('\n');
document.getElementById('manutencaoPrevisao').value = configManutencao.conteudo.previsaoRetorno;
document.getElementById('manutencaoTelefone').value = configManutencao.contactos.telefone;
document.getElementById('manutencaoWhatsapp').value = configManutencao.contactos.whatsapp;
document.getElementById('manutencaoEmail').value = configManutencao.contactos.email;
if (configManutencao.dataInicio) document.getElementById('manutencaoInicio').value = configManutencao.dataInicio;
if (configManutencao.dataFim) document.getElementById('manutencaoFim').value = configManutencao.dataFim;
atualizarInterfaceManutencao();

    // Eventos do modal de confirmação
    document.getElementById('btnConfirmarAcaoBackup')?.addEventListener('click', function() {
        if (typeof acaoBackupPendente === 'function') {
            acaoBackupPendente();
        }
        fecharModalConfirmacaoBackup();
    });
    
    document.getElementById('modalConfirmacaoBackup')?.addEventListener('click', function(e) {
        if (e.target === this) {
            fecharModalConfirmacaoBackup();
        }
    });
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && document.getElementById('modalConfirmacaoBackup')?.classList.contains('ativo')) {
            fecharModalConfirmacaoBackup();
        }
    });

    carregarBuilderLinks('builder_ipikk');
    carregarBuilderLinks('builder_rapidos');
    // INICIALIZACAO
    const hashMatch = window.location.hash.match(/#aba=(\d+)/);
    let abaInicial = 0;
    if (hashMatch) {
        abaInicial = parseInt(hashMatch[1], 10) || 0;
    } else {
        try {
            abaInicial = parseInt(localStorage.getItem('admin_configuracoes_aba') || '0', 10) || 0;
        } catch (e) {
            abaInicial = 0;
        }
    }
    mudarAba(abaInicial);

</script>
</body>
</html>

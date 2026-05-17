<?php
/**
 * Processar Notícias - Área Restrita IPIKK
 * Caminho: area-restrita/processos/processar-noticia.php
 */

// Ativar exibição de erros para debug
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Definir cabeçalho JSON primeiro
header('Content-Type: application/json');


function enfileirarNotificacaoPushNoticia($db, $noticiaId) {
    $stmt = $db->prepare("INSERT IGNORE INTO push_noticias_enviadas (noticia_id, created_at) VALUES (?, NOW())");
    $ok = $stmt->execute([(int)$noticiaId]);
    if ($ok && $stmt->rowCount() > 0) {
        $db->prepare("INSERT INTO push_fila (noticia_id, status, tentativas, created_at, updated_at) VALUES (?, 'pendente', 0, NOW(), NOW())")
           ->execute([(int)$noticiaId]);
        return true;
    }
    return false;
}

try {
    // Carregar configurações
    require_once dirname(__DIR__) . '/../config/index.php';
    
    // Verificar login
    if (!isset($_SESSION['utilizador_id'])) {
        echo json_encode(['success' => false, 'message' => 'Não autorizado']);
        exit;
    }
    // Carregar funções para ter acesso a registrarLog
    require_once dirname(__DIR__) . '/../config/functions.php';
    $db = getDB();
    $acao = $_POST['acao'] ?? '';
    
    // Configurações de upload
    $upload_dir = dirname(__DIR__) . '/../uploads/noticias/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Criar pasta de lixeira se não existir
    $lixeira_dir = dirname(__DIR__) . '/../uploads/lixeira/';
    if (!is_dir($lixeira_dir)) {
        mkdir($lixeira_dir, 0777, true);
    }
    
    // ========== FUNÇÃO PARA LIMPAR LIXEIRA AUTOMATICAMENTE ==========
    function limparLixeiraAutomatico($db, $lixeira_dir) {
        // Buscar arquivos expirados (mais de 30 dias)
        $stmt = $db->prepare("
            SELECT id, caminho_lixeira, tipo, nome_original, tamanho_bytes 
            FROM lixeira 
            WHERE restaurado = 0 AND data_expiracao < NOW()
        ");
        $stmt->execute();
        $expirados = $stmt->fetchAll();
        
        $total_limpos = 0;
        $total_espaco = 0;
        
        foreach ($expirados as $arquivo) {
            $caminho_completo = dirname(__DIR__) . '/..' . $arquivo['caminho_lixeira'];
            
            // Apagar arquivo físico
            if (file_exists($caminho_completo)) {
                $total_espaco += $arquivo['tamanho_bytes'];
                unlink($caminho_completo);
            }
            
            // Remover registro do banco
            $stmt_del = $db->prepare("DELETE FROM lixeira WHERE id = ?");
            $stmt_del->execute([$arquivo['id']]);
            $total_limpos++;
        }
        
        if ($total_limpos > 0) {
            $espaco_mb = round($total_espaco / 1024 / 1024, 2);
            error_log("Lixeira limpa automaticamente: $total_limpos arquivo(s) removidos, liberados {$espaco_mb}MB");
        }
        
        return ['limpos' => $total_limpos, 'espaco' => $total_espaco];
    }
    
    // Executar limpeza automática em todas as requisições
    limparLixeiraAutomatico($db, $lixeira_dir);

    /**
     * Restaura automaticamente um arquivo da lixeira para uploads/noticias
     * quando o caminho original é encontrado no CSV importado.
     */
    function restaurarArquivoDaLixeira($db, $caminho_original) {
        if (empty($caminho_original)) {
            return $caminho_original;
        }

        $caminho_destino = dirname(__DIR__) . '/..' . $caminho_original;
        if (file_exists($caminho_destino)) {
            return $caminho_original;
        }

        $stmt_lixeira = $db->prepare("
            SELECT id, caminho_lixeira
            FROM lixeira
            WHERE caminho_original = ? AND restaurado = 0
            ORDER BY id DESC
            LIMIT 1
        ");
        $stmt_lixeira->execute([$caminho_original]);
        $arquivo_lixeira = $stmt_lixeira->fetch();

        if (!$arquivo_lixeira) {
            return $caminho_original;
        }

        $caminho_lixeira = dirname(__DIR__) . '/..' . $arquivo_lixeira['caminho_lixeira'];
        if (!file_exists($caminho_lixeira)) {
            return $caminho_original;
        }

        $pasta_destino = dirname($caminho_destino);
        if (!is_dir($pasta_destino)) {
            mkdir($pasta_destino, 0777, true);
        }

        if (rename($caminho_lixeira, $caminho_destino)) {
            $stmt_update = $db->prepare("UPDATE lixeira SET restaurado = 1 WHERE id = ?");
            $stmt_update->execute([$arquivo_lixeira['id']]);
        }

        return $caminho_original;
    }
    
    // Função para fazer upload de arquivo
    function fazerUpload($file, $upload_dir, $extensoes, $tamanho_maximo) {
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }
        
        $extensao = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extensao, $extensoes)) {
            return ['error' => 'Extensão não permitida. Use: ' . implode(', ', $extensoes)];
        }
        
        if ($file['size'] > $tamanho_maximo) {
            return ['error' => 'Arquivo muito grande. Máximo: ' . ($tamanho_maximo / 1024 / 1024) . 'MB'];
        }
        
        $upload = uploadArquivoNuvem($file, 'noticias');
        if ($upload['success']) {
            return ['success' => true, 'url' => $upload['url']];
        }
        
        return ['error' => 'Erro ao salvar arquivo'];
    }

    function limparTagTexto($tag) {
        $tag = trim((string)$tag);
        $tag = trim($tag, " \t\n\r\0\x0B\"'");
        $tag = str_replace('\\', '', $tag);
        $tag = preg_replace('/^\[+|\]+$/u', '', $tag);
        $tag = preg_replace('/\s+/u', ' ', $tag);
        return trim($tag);
    }

    function normalizarTagsEntrada($tags_raw) {
        if (empty($tags_raw)) return null;

        $tags = [];

        if (is_array($tags_raw)) {
            $tags = $tags_raw;
        } else {
            $texto = trim((string)$tags_raw);
            if ($texto === '') return null;

            $decoded = json_decode($texto, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $tags = $decoded;
            } else {
                $tags = preg_split('/[,;]+/u', $texto);
            }
        }

        $tags = array_map('limparTagTexto', $tags);
        $tags = array_values(array_filter($tags));

        return !empty($tags)
            ? json_encode($tags, JSON_UNESCAPED_UNICODE)
            : null;
    }
    
    // ========== SALVAR NOTÍCIA ==========
    if ($acao === 'salvar' || $acao === 'editar') {
        $id = $_POST['id'] ?? null;
        $titulo = trim($_POST['titulo'] ?? '');
        $categoria = $_POST['categoria'] ?? '';
        $data_publicacao = $_POST['data_publicacao'] ?? date('Y-m-d');
        $resumo = trim($_POST['resumo'] ?? '');
        $conteudo = $_POST['conteudo'] ?? '';
        $tipo_midia = $_POST['tipo_midia'] ?? 'imagem';
        $alt_text = $_POST['alt_text'] ?? '';
        $autor = trim($_POST['autor'] ?? $_SESSION['utilizador_nome']);
        
        // ===== PROCESSAR TAGS =====
        $tags_raw = $_POST['tags'] ?? '';
        $tags_raw = trim($tags_raw);
        
        $tags = normalizarTagsEntrada($tags_raw);
        
        $estado = $_POST['estado'] ?? 'rascunho';
        $destaque_principal = isset($_POST['destaque_principal']) ? 1 : 0;
        
        // Validação
        $erros = [];
        if (empty($titulo)) $erros[] = 'Título é obrigatório';
        if (empty($categoria)) $erros[] = 'Categoria é obrigatória';
        if (empty($resumo)) $erros[] = 'Resumo é obrigatório';
        if (empty($conteudo)) $erros[] = 'Conteúdo é obrigatório';
        if (empty($autor)) $erros[] = 'Autor é obrigatório';
        
        if (!empty($erros)) {
            echo json_encode(['success' => false, 'message' => implode(', ', $erros)]);
            exit;
        }
        
        // Processar uploads
        $imagem_url = null;
        $video_file = null;
        
        if ($tipo_midia === 'imagem' && isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
            $resultado = fazerUpload(
                $_FILES['imagem'],
                $upload_dir,
                ['jpg', 'jpeg', 'png', 'gif', 'webp'],
                5 * 1024 * 1024
            );
            if (isset($resultado['error'])) {
                echo json_encode(['success' => false, 'message' => 'Erro na imagem: ' . $resultado['error']]);
                exit;
            }
            if (isset($resultado['success'])) {
                $imagem_url = $resultado['url'];
            }
        }

        if ($tipo_midia === 'video' && isset($_FILES['imagem_capa']) && $_FILES['imagem_capa']['error'] === UPLOAD_ERR_OK) {
            $resultado = fazerUpload(
                $_FILES['imagem_capa'],
                $upload_dir,
                ['jpg', 'jpeg', 'png', 'gif', 'webp'],
                5 * 1024 * 1024
            );
            if (isset($resultado['error'])) {
                echo json_encode(['success' => false, 'message' => 'Erro na imagem representativa: ' . $resultado['error']]);
                exit;
            }
            if (isset($resultado['success'])) {
                $imagem_url = $resultado['url'];
            }
        }

        if ($tipo_midia === 'video' && isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
            $resultado = fazerUpload(
                $_FILES['video'],
                $upload_dir,
                ['mp4', 'webm', 'ogg'],
                50 * 1024 * 1024
            );
            if (isset($resultado['error'])) {
                echo json_encode(['success' => false, 'message' => 'Erro no vídeo: ' . $resultado['error']]);
                exit;
            }
            if (isset($resultado['success'])) {
                $video_file = $resultado['url'];
            }
        }
        
        // Gerar slug
        $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($titulo));
        $slug = trim($slug, '-');
        
        try {
            if ($id && $id > 0) {
                // Buscar dados antigos
                $stmt = $db->prepare("SELECT imagem_url, video_file FROM noticias WHERE id = ?");
                $stmt->execute([$id]);
                $antigo = $stmt->fetch();
                
                if (!$imagem_url && $antigo['imagem_url']) {
                    $imagem_url = $antigo['imagem_url'];
                }
                if (!$video_file && $antigo['video_file']) {
                    $video_file = $antigo['video_file'];
                }
                
                // Atualizar
                $sql = "UPDATE noticias SET 
                            titulo = ?, slug = ?, resumo = ?, conteudo = ?, 
                            categoria = ?, tipo_midia = ?, imagem_url = ?, 
                            video_file = ?, alt_text = ?, autor = ?, 
                            tags = ?, data_publicacao = ?, estado = ?, 
                            destaque_principal = ?, updated_at = NOW()
                        WHERE id = ?";
                $params = [
                    $titulo, $slug, $resumo, $conteudo, $categoria, $tipo_midia,
                    $imagem_url, $video_file, $alt_text, $autor, $tags,
                    $data_publicacao, $estado, $destaque_principal, $id
                ];
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                $mensagem = 'Notícia atualizada com sucesso!';
            } else {
                // Verificar slug único
                $stmt = $db->prepare("SELECT id FROM noticias WHERE slug = ?");
                $stmt->execute([$slug]);
                if ($stmt->fetch()) {
                    $slug = $slug . '-' . uniqid();
                }
                
                // Inserir nova
                $sql = "INSERT INTO noticias (titulo, slug, resumo, conteudo, categoria, tipo_midia, 
                        imagem_url, video_file, alt_text, autor, tags, data_publicacao, estado, 
                        destaque_principal, visualizacoes, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NOW())";
                $params = [
                    $titulo, $slug, $resumo, $conteudo, $categoria, $tipo_midia,
                    $imagem_url, $video_file, $alt_text, $autor, $tags,
                    $data_publicacao, $estado, $destaque_principal
                ];
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                $id = $db->lastInsertId();
                $mensagem = 'Notícia criada com sucesso!';
            }
            
            if ($id) {
                // Atualização
                registrarLog('editou', 'noticias', $id, "Editou a notícia: {$titulo}");
            } else {
                // Criação
                $novo_id = $db->lastInsertId();
                registrarLog('criou', 'noticias', $novo_id, "Criou a notícia: {$titulo}");
            }
            if ($estado === 'publicada' && !empty($id)) { enfileirarNotificacaoPushNoticia($db, (int)$id); }
            echo json_encode(['success' => true, 'message' => $mensagem, 'id' => $id]);
            
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
        }
        exit;
    }
    
    // ========== ELIMINAR NOTÍCIA (MOVER PARA LIXEIRA COM EXPIRAÇÃO) ==========
    if ($acao === 'eliminar') {
        $id = $_POST['id'] ?? 0;
        
        if ($id) {
            // Buscar dados da notícia antes de eliminar
            $stmt = $db->prepare("SELECT titulo, imagem_url, video_file FROM noticias WHERE id = ?");
            $stmt->execute([$id]);
            $noticia = $stmt->fetch();
            
            if ($noticia) {
                $arquivos_movidos = [];
                $data_expiracao = date('Y-m-d H:i:s', strtotime('+30 days')); // Expira em 30 dias
                
                // Mover imagem para lixeira
                if ($noticia['imagem_url']) {
                    $caminho_origem = dirname(__DIR__) . '/..' . $noticia['imagem_url'];
                    $nome_arquivo = basename($noticia['imagem_url']);
                    $tamanho = file_exists($caminho_origem) ? filesize($caminho_origem) : 0;
                    $nome_unico = date('Y-m-d_H-i-s') . '_' . $nome_arquivo;
                    $caminho_destino = $lixeira_dir . $nome_unico;
                    
                    if (file_exists($caminho_origem)) {
                        if (rename($caminho_origem, $caminho_destino)) {
                            // Registrar na tabela lixeira com data de expiração
                            $stmt_lixeira = $db->prepare("
                                INSERT INTO lixeira (tipo, nome_original, caminho_original, caminho_lixeira, noticia_id, noticia_titulo, tamanho_bytes, data_expiracao)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                            ");
                            $stmt_lixeira->execute([
                                'imagem',
                                $nome_arquivo,
                                $noticia['imagem_url'],
                                '/uploads/lixeira/' . $nome_unico,
                                $id,
                                $noticia['titulo'],
                                $tamanho,
                                $data_expiracao
                            ]);
                            $arquivos_movidos[] = $nome_arquivo;
                        }
                    }
                }
                
                // Mover vídeo para lixeira
                if ($noticia['video_file']) {
                    $caminho_origem = dirname(__DIR__) . '/..' . $noticia['video_file'];
                    $nome_arquivo = basename($noticia['video_file']);
                    $tamanho = file_exists($caminho_origem) ? filesize($caminho_origem) : 0;
                    $nome_unico = date('Y-m-d_H-i-s') . '_' . $nome_arquivo;
                    $caminho_destino = $lixeira_dir . $nome_unico;
                    
                    if (file_exists($caminho_origem)) {
                        if (rename($caminho_origem, $caminho_destino)) {
                            $stmt_lixeira = $db->prepare("
                                INSERT INTO lixeira (tipo, nome_original, caminho_original, caminho_lixeira, noticia_id, noticia_titulo, tamanho_bytes, data_expiracao)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                            ");
                            $stmt_lixeira->execute([
                                'video',
                                $nome_arquivo,
                                $noticia['video_file'],
                                '/uploads/lixeira/' . $nome_unico,
                                $id,
                                $noticia['titulo'],
                                $tamanho,
                                $data_expiracao
                            ]);
                            $arquivos_movidos[] = $nome_arquivo;
                        }
                    }
                }
                
                registrarLog('eliminou', 'noticias', $id, "Eliminou a notícia: {$noticia['titulo']} (movida para lixeira)");
                // Remover registro do banco
                $stmt = $db->prepare("DELETE FROM noticias WHERE id = ?");
                $stmt->execute([$id]);
                
                $mensagem = 'Notícia eliminada com sucesso!';
                if (!empty($arquivos_movidos)) {
                    $mensagem .= ' Arquivos movidos para a lixeira (expirarão em 30 dias).';
                }
                
                echo json_encode(['success' => true, 'message' => $mensagem]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Notícia não encontrada']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'ID não informado']);
        }
        exit;
    }
    
    // ========== ELIMINAR EM MASSA (MOVER PARA LIXEIRA) ==========
    if ($acao === 'eliminar_massa') {
        $ids = explode(',', $_POST['ids'] ?? '');
        $ids = array_filter($ids, 'is_numeric');
        
        if (count($ids) > 0) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            
            // Buscar notícias com seus arquivos
            $stmt = $db->prepare("SELECT id, titulo, imagem_url, video_file FROM noticias WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            $noticias = $stmt->fetchAll();
            $total_arquivos = 0;
            $data_expiracao = date('Y-m-d H:i:s', strtotime('+30 days'));
            
            foreach ($noticias as $noticia) {
                // Mover imagem
                if ($noticia['imagem_url']) {
                    $caminho_origem = dirname(__DIR__) . '/..' . $noticia['imagem_url'];
                    $nome_arquivo = basename($noticia['imagem_url']);
                    $tamanho = file_exists($caminho_origem) ? filesize($caminho_origem) : 0;
                    $nome_unico = date('Y-m-d_H-i-s') . '_' . $nome_arquivo;
                    $caminho_destino = $lixeira_dir . $nome_unico;
                    
                    if (file_exists($caminho_origem)) {
                        if (rename($caminho_origem, $caminho_destino)) {
                            $stmt_lixeira = $db->prepare("
                                INSERT INTO lixeira (tipo, nome_original, caminho_original, caminho_lixeira, noticia_id, noticia_titulo, tamanho_bytes, data_expiracao)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                            ");
                            $stmt_lixeira->execute([
                                'imagem',
                                $nome_arquivo,
                                $noticia['imagem_url'],
                                '/uploads/lixeira/' . $nome_unico,
                                $noticia['id'],
                                $noticia['titulo'],
                                $tamanho,
                                $data_expiracao
                            ]);
                            $total_arquivos++;
                        }
                    }
                }
                
                // Mover vídeo
                if ($noticia['video_file']) {
                    $caminho_origem = dirname(__DIR__) . '/..' . $noticia['video_file'];
                    $nome_arquivo = basename($noticia['video_file']);
                    $tamanho = file_exists($caminho_origem) ? filesize($caminho_origem) : 0;
                    $nome_unico = date('Y-m-d_H-i-s') . '_' . $nome_arquivo;
                    $caminho_destino = $lixeira_dir . $nome_unico;
                    
                    if (file_exists($caminho_origem)) {
                        if (rename($caminho_origem, $caminho_destino)) {
                            $stmt_lixeira = $db->prepare("
                                INSERT INTO lixeira (tipo, nome_original, caminho_original, caminho_lixeira, noticia_id, noticia_titulo, tamanho_bytes, data_expiracao)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                            ");
                            $stmt_lixeira->execute([
                                'video',
                                $nome_arquivo,
                                $noticia['video_file'],
                                '/uploads/lixeira/' . $nome_unico,
                                $noticia['id'],
                                $noticia['titulo'],
                                $tamanho,
                                $data_expiracao
                            ]);
                            $total_arquivos++;
                        }
                    }
                }
            }
                foreach ($noticias as $noticia) {
                    registrarLog('eliminou', 'noticias', $noticia['id'], "Eliminou a notícia: {$noticia['titulo']} (movida para lixeira)");
                }
            // Deletar notícias
            $stmt = $db->prepare("DELETE FROM noticias WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            
            $mensagem = count($ids) . ' notícias eliminadas com sucesso!';
            if ($total_arquivos > 0) {
                $mensagem .= " $total_arquivos arquivo(s) movido(s) para a lixeira (expirarão em 30 dias).";
            }
            
            echo json_encode(['success' => true, 'message' => $mensagem]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Nenhuma notícia selecionada']);
        }
        exit;
    }
    
    // ========== PUBLICAR EM MASSA ==========
    if ($acao === 'publicar_massa') {
        $ids = explode(',', $_POST['ids'] ?? '');
        $ids = array_filter($ids, 'is_numeric');
        
        registrarLog('publicou', 'noticias', 0, "Publicou {$stmt->rowCount()} notícias em massa");
        if (count($ids) > 0) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $db->prepare("UPDATE noticias SET estado = 'publicada' WHERE id IN ($placeholders) AND estado IN ('rascunho', 'arquivada')");
            $stmt->execute($ids);
            foreach ($ids as $nid) { enfileirarNotificacaoPushNoticia($db, (int)$nid); }
            echo json_encode(['success' => true, 'message' => $stmt->rowCount() . ' notícias publicadas com sucesso!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Nenhuma notícia selecionada']);
        }
        exit;
    }

    // ========== IMPORTAR CSV ==========
    if ($acao === 'importar_csv') {
        $dados_json = $_POST['dados'] ?? '';
        $ignorar_duplicatas = isset($_POST['ignorar_duplicatas']) && $_POST['ignorar_duplicatas'] == '1';
        $atualizar_existentes = isset($_POST['atualizar_existentes']) && $_POST['atualizar_existentes'] == '1';
        
        if (empty($dados_json)) {
            echo json_encode(['success' => false, 'message' => 'Nenhum dado para importar.']);
            exit;
        }
        
        $dados = json_decode($dados_json, true);
        if (!is_array($dados) || count($dados) === 0) {
            echo json_encode(['success' => false, 'message' => 'Dados inválidos para importação.']);
            exit;
        }
        
        $importados = 0;
        $atualizados = 0;
        $ignorados = 0;
        $erros = [];
        
        foreach ($dados as $linha) {
            // Mapear campos
            $titulo = $linha['titulo'] ?? $linha['Título'] ?? $linha['Titulo'] ?? null;
            $conteudo = $linha['conteudo'] ?? $linha['Conteudo'] ?? $linha['Conteúdo'] ?? null;
            $autor = $linha['autor'] ?? $linha['Autor'] ?? null;
            $resumo = $linha['resumo'] ?? $linha['Resumo'] ?? null;
            $categoria = $linha['categoria'] ?? $linha['Categoria'] ?? 'INSTITUCIONAL';
            $tags_raw = $linha['tags'] ?? $linha['Tags'] ?? null;
            $data_publicacao = $linha['data_publicacao'] ?? $linha['Data'] ?? $linha['Data_Publicacao'] ?? date('Y-m-d');
            $estado = 'rascunho';
            $imagem_url = $linha['imagem_url'] ?? $linha['Imagem_URL'] ?? null;
            $video_file = $linha['video_file'] ?? $linha['Video_File'] ?? null;

            // Ao importar, tenta restaurar automaticamente mídia da lixeira
            if (!empty($imagem_url)) {
                $imagem_url = restaurarArquivoDaLixeira($db, $imagem_url);
            }
            if (!empty($video_file)) {
                $video_file = restaurarArquivoDaLixeira($db, $video_file);
            }
            
            if (empty($conteudo) && !empty($resumo)) {
                $conteudo = $resumo;
            }
            
            if (empty($titulo) || empty($conteudo) || empty($autor)) {
                $ignorados++;
                continue;
            }
            
            $stmt = $db->prepare("SELECT id, imagem_url, video_file FROM noticias WHERE titulo = ?");
            $stmt->execute([$titulo]);
            $existente = $stmt->fetch();
            
            $tags = normalizarTagsEntrada($tags_raw);
            
            $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($titulo));
            $slug = trim($slug, '-');
            
            if ($existente) {
                if ($atualizar_existentes) {
                    $stmt = $db->prepare("SELECT id FROM noticias WHERE slug = ? AND id != ?");
                    $stmt->execute([$slug, $existente['id']]);
                    if ($stmt->fetch()) {
                        $slug = $slug . '-' . uniqid();
                    }
                    
                    if (empty($imagem_url) && $existente['imagem_url']) {
                        $imagem_url = $existente['imagem_url'];
                    }
                    if (empty($video_file) && $existente['video_file']) {
                        $video_file = $existente['video_file'];
                    }
                    
                    $stmt = $db->prepare("
                        UPDATE noticias SET 
                            titulo = ?, slug = ?, resumo = ?, conteudo = ?, 
                            categoria = ?, autor = ?, tags = ?, 
                            data_publicacao = ?, estado = ?,
                            imagem_url = ?, video_file = ?,
                            updated_at = NOW()
                        WHERE id = ?
                    ");
                    
                    $result = $stmt->execute([
                        $titulo, $slug, $resumo, $conteudo, $categoria,
                        $autor, $tags, $data_publicacao, $estado,
                        $imagem_url, $video_file, $existente['id']
                    ]);
                    
                    if ($result) $atualizados++;
                    else $erros[] = $titulo;
                    
                } else if ($ignorar_duplicatas) {
                    $ignorados++;
                    continue;
                }
            } else {
                $stmt = $db->prepare("SELECT id FROM noticias WHERE slug = ?");
                $stmt->execute([$slug]);
                if ($stmt->fetch()) {
                    $slug = $slug . '-' . uniqid();
                }
                
                $stmt = $db->prepare("
                    INSERT INTO noticias (titulo, slug, resumo, conteudo, categoria, autor, tags, data_publicacao, estado, imagem_url, video_file, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                
                $result = $stmt->execute([
                    $titulo, $slug, $resumo, $conteudo, $categoria,
                    $autor, $tags, $data_publicacao, $estado,
                    $imagem_url, $video_file
                ]);
                
                if ($result) $importados++;
                else $erros[] = $titulo;
            }
        }
        
        $mensagem = "Importação concluída!<br>";
        $mensagem .= "Novas notícias: $importados<br>";
        $mensagem .= "Atualizadas: $atualizados<br>";
        $mensagem .= "Ignoradas: $ignorados";
        
        if (!empty($erros)) {
            $mensagem .= "<br> Erros em: " . implode(', ', array_slice($erros, 0, 5));
            if (count($erros) > 5) $mensagem .= " e mais " . (count($erros) - 5);
        }
        registrarLog('importou', 'noticias', 0, "Importou {$importados} notícias do CSV");
        
        echo json_encode(['success' => true, 'message' => $mensagem]);
        exit;
    }
    
    // ========== RESTAURAR DA LIXEIRA ==========
    if ($acao === 'restaurar_lixeira') {
        $id_lixeira = $_POST['id'] ?? 0;
        
        if ($id_lixeira) {
            $stmt = $db->prepare("SELECT * FROM lixeira WHERE id = ? AND restaurado = 0");
            $stmt->execute([$id_lixeira]);
            $arquivo = $stmt->fetch();
            
            if ($arquivo) {
                $caminho_origem = dirname(__DIR__) . '/..' . $arquivo['caminho_lixeira'];
                $caminho_destino = dirname(__DIR__) . '/..' . $arquivo['caminho_original'];
                
                if (file_exists($caminho_origem)) {
                    if (rename($caminho_origem, $caminho_destino)) {
                        $stmt = $db->prepare("UPDATE lixeira SET restaurado = 1 WHERE id = ?");
                        $stmt->execute([$id_lixeira]);
                        
                        echo json_encode(['success' => true, 'message' => 'Arquivo restaurado com sucesso!']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Erro ao restaurar arquivo']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Arquivo não encontrado na lixeira']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Arquivo não encontrado ou já restaurado']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'ID não informado']);
        }
        exit;
    }

    if ($acao === 'excluir_item_lixeira') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID inválido para exclusão.']);
            exit;
        }

        $stmt = $db->prepare("SELECT id, caminho_lixeira, tamanho_bytes FROM lixeira WHERE id = ? AND restaurado = 0 LIMIT 1");
        $stmt->execute([$id]);
        $arquivo = $stmt->fetch();

        if (!$arquivo) {
            echo json_encode(['success' => false, 'message' => 'Arquivo não encontrado na lixeira.']);
            exit;
        }

        $caminho_completo = dirname(__DIR__) . '/..' . $arquivo['caminho_lixeira'];
        $total_espaco = 0;

        if (file_exists($caminho_completo)) {
            $total_espaco = (int)($arquivo['tamanho_bytes'] ?? 0);
            @unlink($caminho_completo);
        }

        $stmt_del = $db->prepare("DELETE FROM lixeira WHERE id = ?");
        $stmt_del->execute([$id]);

        $espaco_mb = round($total_espaco / 1024 / 1024, 2);
        echo json_encode([
            'success' => true,
            'message' => "Arquivo removido permanentemente. Espaço liberado: {$espaco_mb}MB."
        ]);
        exit;
    }
    
    // ========== LIMPAR LIXEIRA MANUALMENTE ==========
    if ($acao === 'limpar_lixeira') {
        $tipo = $_POST['tipo'] ?? 'expirados'; // 'tudo', 'imagem', 'video', 'expirados'
        
        $sql = "SELECT id, caminho_lixeira, tamanho_bytes FROM lixeira WHERE restaurado = 0";
        $params = [];
        
        if ($tipo === 'unico') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'ID inválido para exclusão.']);
                exit;
            }
            $sql .= " AND id = ?";
            $params[] = $id;
        } elseif ($tipo === 'expirados') {
            $sql .= " AND data_expiracao < NOW()";
        } elseif ($tipo !== 'tudo') {
            $sql .= " AND tipo = ?";
            $params[] = $tipo;
        }
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $arquivos = $stmt->fetchAll();
        
        $total_limpos = 0;
        $total_espaco = 0;
        
        foreach ($arquivos as $arquivo) {
            $caminho_completo = dirname(__DIR__) . '/..' . $arquivo['caminho_lixeira'];
            
            if (file_exists($caminho_completo)) {
                $total_espaco += $arquivo['tamanho_bytes'];
                unlink($caminho_completo);
            }
            
            $stmt_del = $db->prepare("DELETE FROM lixeira WHERE id = ?");
            $stmt_del->execute([$arquivo['id']]);
            $total_limpos++;
        }
        
        $espaco_mb = round($total_espaco / 1024 / 1024, 2);
        $mensagem = "Lixeira limpa! $total_limpos arquivo(s) removidos, liberados {$espaco_mb}MB.";
        
        echo json_encode(['success' => true, 'message' => $mensagem, 'limpos' => $total_limpos, 'espaco' => $espaco_mb]);
        exit;
    }
    
    echo json_encode(['success' => false, 'message' => 'Ação inválida: ' . $acao]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
}
?>
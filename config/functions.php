<?php
/**
 * Funções Auxiliares - IPIKK
 * 
 * Este arquivo contém funções utilitárias para todo o sistema.
 */

// ============================================
// FUNÇÕES DE FORMATAÇÃO
// ============================================

/**
 * Formata uma data para o padrão brasileiro/português
 * 
 * @param string $data Data no formato Y-m-d ou timestamp
 * @param bool $completo Se true, exibe mês por extenso
 * @return string Data formatada
 */
function formatarData($data, $completo = false) {
    if (empty($data)) return '';
    
    if (is_string($data) && strpos($data, '-') !== false) {
        $timestamp = strtotime($data);
    } else {
        $timestamp = $data;
    }
    
    if ($completo) {
        return date('d \d\e F \d\e Y', $timestamp);
    }
    
    return date('d/m/Y', $timestamp);
}

/**
 * Formata um número com separadores de milhar
 */
function formatarNumero($numero) {
    return number_format($numero, 0, ',', '.');
}


/**
 * Formata bytes para KB/MB/GB.
 */
function formatarTamanhoArquivo($bytes, $precisao = 2) {
    $bytes = (float)$bytes;
    if ($bytes <= 0) {
        return '0 B';
    }

    $unidades = ['B', 'KB', 'MB', 'GB', 'TB'];
    $base = (int) floor(log($bytes, 1024));
    $base = min($base, count($unidades) - 1);
    $valor = $bytes / (1024 ** $base);

    return number_format($valor, $precisao, ',', '.') . ' ' . $unidades[$base];
}

/**
 * Calcula tempo relativo (ex: "há 5 minutos")
 */
function tempoRelativo($data) {
    if (empty($data)) return '';
    
    $agora = new DateTime();
    $publicacao = new DateTime($data);
    $diferenca = $agora->diff($publicacao);
    
    if ($diferenca->days > 30) {
        return $diferenca->format('%m meses atrás');
    } elseif ($diferenca->days > 0) {
        return $diferenca->format('%d dias atrás');
    } elseif ($diferenca->h > 0) {
        return $diferenca->format('%h horas atrás');
    } else {
        return $diferenca->format('%i minutos atrás');
    }
}

/**
 * Limita o texto a um número de caracteres
 */
function limitarTexto($texto, $limite = 150, $reticencias = '...') {
    if (strlen($texto) <= $limite) {
        return $texto;
    }
    
    $texto = substr($texto, 0, $limite);
    $ultimoEspaco = strrpos($texto, ' ');
    
    if ($ultimoEspaco !== false) {
        $texto = substr($texto, 0, $ultimoEspaco);
    }
    
    return $texto . $reticencias;
}

/**
 * Gera um slug amigável para URLs
 */
function gerarSlug($texto) {
    $texto = preg_replace('/[áàãâä]/u', 'a', $texto);
    $texto = preg_replace('/[éèêë]/u', 'e', $texto);
    $texto = preg_replace('/[íìîï]/u', 'i', $texto);
    $texto = preg_replace('/[óòõôö]/u', 'o', $texto);
    $texto = preg_replace('/[úùûü]/u', 'u', $texto);
    $texto = preg_replace('/[ç]/u', 'c', $texto);
    $texto = preg_replace('/[^a-z0-9]/i', '-', $texto);
    $texto = strtolower(trim($texto, '-'));
    return preg_replace('/-+/', '-', $texto);
}

// ============================================
// FUNÇÕES DE SESSÃO E AUTENTICAÇÃO
// ============================================

/**
 * Verifica se o utilizador está logado
 */
function estaLogado() {
    return isset($_SESSION['utilizador_id']);
}

/**
 * Verifica se o utilizador tem permissão para a ação
 */
function temPermissao($permissao_id) {
    if (!estaLogado()) return false;
    
    $permissoes = isset($_SESSION['utilizador_permissoes']) 
        ? json_decode($_SESSION['utilizador_permissoes'], true) 
        : [];
    
    return in_array($permissao_id, $permissoes);
}

/**
 * Verifica se o utilizador é administrador
 */
function isAdmin() {
    return isset($_SESSION['utilizador_nivel']) && $_SESSION['utilizador_nivel'] === 'admin';
}

// ============================================
// FUNÇÕES DE REDIRECIONAMENTO
// ============================================

/**
 * Redireciona para uma URL
 */
function redirecionar($url) {
    header('Location: ' . $url);
    exit;
}

/**
 * Redireciona de volta para a página anterior
 */
function voltar() {
    $url = $_SERVER['HTTP_REFERER'] ?? 'index.php';
    redirecionar($url);
}

// ============================================
// FUNÇÕES DE MENSAGENS FLASH
// ============================================

/**
 * Define uma mensagem flash na sessão
 */
function setFlash($tipo, $mensagem) {
    $_SESSION['flash'] = [
        'tipo' => $tipo,     // 'success', 'error', 'info', 'warning'
        'mensagem' => $mensagem
    ];
}

/**
 * Retorna e limpa a mensagem flash
 */
function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// ============================================
// FUNÇÕES DE SEGURANÇA
// ============================================

/**
 * Sanitiza entrada para evitar XSS
 */
function sanitizar($dados) {
    if (is_array($dados)) {
        return array_map('sanitizar', $dados);
    }
    return htmlspecialchars(trim($dados), ENT_QUOTES, 'UTF-8');
}

/**
 * Gera uma senha aleatória
 */
function gerarSenhaAleatoria($length = 14) {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789!@#$%';
    $senha = '';
    for ($i = 0; $i < $length; $i++) {
        $senha .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $senha;
}

// ============================================
// FUNÇÕES DE UPLOAD
// ============================================

/**
 * Faz upload de um arquivo
 */
if (!function_exists('uploadArquivo')) {
    function uploadArquivo($file, $destino, $tiposPermitidos = ['image/jpeg', 'image/png', 'image/gif']) {
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }

        $tipo = $file['type'] ?? '';
        if (!in_array($tipo, $tiposPermitidos)) {
            return false;
        }

        $subpasta = trim(str_replace(['\\', '//'], '/', basename((string)$destino)), '/');
        $upload = uploadArquivoNuvem($file, $subpasta !== '' ? $subpasta : 'geral');
        return $upload['success'] ? $upload['url'] : false;
    }
}

/**
 * Upload universal para nuvem (Cloudinary) estritamente cloud-only.
 * Retorna array: ['success'=>bool,'url'=>string|null,'message'=>string]
 */
if (!function_exists('uploadArquivoNuvem')) {
    function uploadArquivoNuvem($file, $subpasta = 'geral') {
        if (!isset($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['success' => false, 'url' => null, 'message' => 'Arquivo inválido para upload.'];
        }

        $cloudName = getenv('CLOUDINARY_CLOUD_NAME') ?: '';
        $uploadPreset = getenv('CLOUDINARY_UPLOAD_PRESET') ?: '';
        $folderBase = getenv('CLOUDINARY_FOLDER') ?: 'ipikk';

        if ($cloudName === '' || $uploadPreset === '') {
            return ['success' => false, 'url' => null, 'message' => 'Cloudinary não configurado.'];
        }

        $folder = trim($folderBase . '/' . trim($subpasta, '/'), '/');
        $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
        $isPdf = ($ext === 'pdf') || stripos((string)($file['type'] ?? ''), 'pdf') !== false;
        $resourceType = $isPdf ? 'raw' : 'auto';
        $endpoint = "https://api.cloudinary.com/v1_1/{$cloudName}/{$resourceType}/upload";

        $payload = [
            'upload_preset' => $uploadPreset,
            'folder' => $folder,
            'file' => new CURLFile($file['tmp_name'], $file['type'] ?? 'application/octet-stream', $file['name'] ?? 'arquivo'),
        ];

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 45,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response !== false && $httpCode >= 200 && $httpCode < 300) {
            $decoded = json_decode($response, true);
            if (!empty($decoded['secure_url'])) {
                return ['success' => true, 'url' => $decoded['secure_url'], 'message' => 'Upload para nuvem concluído.'];
            }
        }

        error_log("Falha upload Cloudinary ({$httpCode}): " . ($curlError ?: $response));

        return ['success' => false, 'url' => null, 'message' => 'Erro ao enviar arquivo para nuvem (cloud-only, sem fallback local).'];
    }
}

// ============================================
// FUNÇÕES DE PÁGINAS ESTÁTICAS (JSON)
// ============================================

/**
 * Retorna o conteúdo de uma página estática
 * Suporta ambas as tabelas: paginas_estaticas e conteudo_paginas
 */
function getPagina($slug) {
    $db = getDB();
    
    // Primeiro tenta buscar da tabela conteudo_paginas (mais recente)
    $stmt = $db->prepare("SELECT conteudo FROM conteudo_paginas WHERE slug = ?");
    $stmt->execute([$slug]);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($resultado && !empty($resultado['conteudo'])) {
        $dados = json_decode($resultado['conteudo'], true);
        if (is_array($dados)) {
            return $dados;
        }
    }
    
    // Fallback: tenta buscar da tabela paginas_estaticas (antiga)
    $stmt = $db->prepare("SELECT conteudo FROM paginas_estaticas WHERE slug = ? AND ativo = 1");
    $stmt->execute([$slug]);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($resultado && !empty($resultado['conteudo'])) {
        $dados = json_decode($resultado['conteudo'], true);
        if (is_array($dados)) {
            return $dados;
        }
    }
    
    return [];
}

/**
 * Função específica para buscar conteúdo da tabela conteudo_paginas
 * (mais direta, sem fallback)
 */
function getConteudoPagina($slug) {
    $db = getDB();
    $stmt = $db->prepare("SELECT conteudo FROM conteudo_paginas WHERE slug = ?");
    $stmt->execute([$slug]);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($resultado && !empty($resultado['conteudo'])) {
        $dados = json_decode($resultado['conteudo'], true);
        return is_array($dados) ? $dados : [];
    }
    
    return [];
}

/**
 * Normaliza URL de mídia para aceitar caminhos locais e URLs absolutas (cloud).
 */
function normalizarUrlMidia($url, $prefixoRelativo = '../') {
    $url = trim((string)$url);
    if ($url === '') return '';
    if (preg_match('/^https?:\/\//i', $url)) return ajustarCloudinaryPdfUrl($url);
    return rtrim($prefixoRelativo, '/') . '/' . ltrim($url, '/');
}


function ajustarCloudinaryPdfUrl($url) {
    $url = trim((string)$url);
    if ($url === '') return '';
    return $url;
}

/**
 * Retorna um valor específico de uma página estática
 */
function getPaginaValor($slug, $chave, $default = null) {
    $pagina = getPagina($slug);
    
    // Navegação por chaves aninhadas (ex: "menu_principal.0.texto")
    $chaves = explode('.', $chave);
    $valor = $pagina;
    
    foreach ($chaves as $k) {
        if (isset($valor[$k])) {
            $valor = $valor[$k];
        } else {
            return $default;
        }
    }
    
    return $valor;
}
// ============================================
// FUNÇÕES DE CONFIGURAÇÕES
// ============================================

/**
 * Retorna uma configuração do site
 */
function getConfig($chave) {
    static $config = null;
    
    if ($config === null) {
        $db = getDB();
        $stmt = $db->query("SELECT * FROM configuracoes WHERE id = 1");
        $config = $stmt->fetch();
    }
    
    return isset($config[$chave]) ? $config[$chave] : null;
}

// ============================================
// FUNÇÕES DE LOG
// ============================================

/**
 * Registra uma ação no log
 */
function registrarLog($acao, $tabela = null, $registro_id = null, $detalhes = null) {
    if (!isset($_SESSION['utilizador_id'])) {
        return;
    }
    
    $db = getDB();
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    $stmt = $db->prepare("INSERT INTO logs (utilizador_id, acao, tabela, registro_id, detalhes, ip_address, user_agent) 
                          VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $_SESSION['utilizador_id'],
        $acao,
        $tabela,
        $registro_id,
        $detalhes,
        $ip,
        $user_agent
    ]);
}



// ============================================
// FUNÇÕES DE CONTAGEM DE VISITANTES
// ============================================

/**
 * Conta visitante único (baseado em sessão)
 * Chamar no início de cada página pública
 */
function contarVisitante() {
    $db = getDB();
    
    // Se já foi contado nesta sessão, não contar novamente
    if (isset($_SESSION['visitante_contado'])) {
        return;
    }
    
    $hoje = date('Y-m-d');
    
    try {
        // Verificar se já existe registro para hoje
        $stmt = $db->prepare("SELECT id FROM estatisticas 
                              WHERE tipo = 'visitante' AND data_referencia = ?");
        $stmt->execute([$hoje]);
        $registro = $stmt->fetch();
        
        if ($registro) {
            // Atualizar contador existente
            $stmt = $db->prepare("UPDATE estatisticas 
                                  SET contador = contador + 1 
                                  WHERE id = ?");
            $stmt->execute([$registro['id']]);
        } else {
            // Criar novo registro
            $stmt = $db->prepare("INSERT INTO estatisticas 
                                  (tipo, contador, data_referencia) 
                                  VALUES ('visitante', 1, ?)");
            $stmt->execute([$hoje]);
        }
        
        // Marcar que o visitante já foi contado
        $_SESSION['visitante_contado'] = true;
    } catch (PDOException $e) {
        // Se a tabela não existir, não fazer nada
        error_log("Erro ao contar visitante: " . $e->getMessage());
    }
}

// ============================================
// FUNÇÕES DE INCREMENTO DE VISUALIZAÇÕES
// ============================================

/**
 * Incrementa visualizações de uma notícia
 */
function incrementarVisualizacaoNoticia($noticia_id) {
    if (!$noticia_id) return false;
    
    $db = getDB();
    
    try {
        // Atualizar contador na tabela noticias
        $stmt = $db->prepare("UPDATE noticias 
                              SET visualizacoes = visualizacoes + 1 
                              WHERE id = ?");
        $stmt->execute([$noticia_id]);
        
        // Registrar em estatisticas para análise detalhada
        $stmt = $db->prepare("INSERT INTO estatisticas 
                              (tipo, referencia_id, contador, data_referencia) 
                              VALUES ('noticia', ?, 1, CURDATE())");
        $stmt->execute([$noticia_id]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Erro ao incrementar visualização de notícia: " . $e->getMessage());
        return false;
    }
}

/**
 * Incrementa visualizações de um curso
 */
function incrementarVisualizacaoCurso($curso_id) {
    if (!$curso_id) return false;
    
    $db = getDB();
    
    try {
        // Verificar se o campo visualizacoes existe
        $stmt = $db->query("SHOW COLUMNS FROM cursos LIKE 'visualizacoes'");
        $existeCampo = $stmt->fetch();
        
        if (!$existeCampo) {
            // Criar campo se não existir
            $db->exec("ALTER TABLE cursos ADD COLUMN visualizacoes INT DEFAULT 0");
        }
        
        // Atualizar contador
        $stmt = $db->prepare("UPDATE cursos 
                              SET visualizacoes = visualizacoes + 1 
                              WHERE id = ?");
        $stmt->execute([$curso_id]);
        
        // Registrar em estatisticas
        $stmt = $db->prepare("INSERT INTO estatisticas 
                              (tipo, referencia_id, contador, data_referencia) 
                              VALUES ('curso', ?, 1, CURDATE())");
        $stmt->execute([$curso_id]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Erro ao incrementar visualização de curso: " . $e->getMessage());
        return false;
    }
}

/**
 * Incrementa visualizações de uma área de formação
 */
function incrementarVisualizacaoArea($area_id) {
    if (!$area_id) return false;
    
    $db = getDB();
    
    try {
        $stmt = $db->prepare("INSERT INTO estatisticas 
                              (tipo, referencia_id, contador, data_referencia) 
                              VALUES ('area', ?, 1, CURDATE())");
        $stmt->execute([$area_id]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Erro ao incrementar visualização de área: " . $e->getMessage());
        return false;
    }
}

// ============================================
// FUNÇÕES DE ESTATÍSTICAS (para Dashboard)
// ============================================

/**
 * Retorna número de visitantes hoje
 */
function getVisitantesHoje() {
    $db = getDB();
    try {
        $stmt = $db->query("SELECT contador FROM estatisticas 
                            WHERE tipo = 'visitante' AND data_referencia = CURDATE()");
        return $stmt->fetchColumn() ?? 0;
    } catch (PDOException $e) {
        return 0;
    }
}

/**
 * Retorna número de visitantes este mês
 */
function getVisitantesMes() {
    $db = getDB();
    try {
        $stmt = $db->query("SELECT SUM(contador) FROM estatisticas 
                            WHERE tipo = 'visitante' 
                            AND MONTH(data_referencia) = MONTH(CURDATE())
                            AND YEAR(data_referencia) = YEAR(CURDATE())");
        return $stmt->fetchColumn() ?? 0;
    } catch (PDOException $e) {
        return 0;
    }
}

/**
 * Retorna número de visitantes este ano
 */
function getVisitantesAno() {
    $db = getDB();
    try {
        $stmt = $db->query("SELECT SUM(contador) FROM estatisticas 
                            WHERE tipo = 'visitante' 
                            AND YEAR(data_referencia) = YEAR(CURDATE())");
        return $stmt->fetchColumn() ?? 0;
    } catch (PDOException $e) {
        return 0;
    }
}

/**
 * Retorna as notícias mais visualizadas
 */
function getNoticiasMaisVistas($limite = 5) {
    $db = getDB();
    try {
        $stmt = $db->query("SELECT id, titulo, visualizacoes 
                            FROM noticias 
                            WHERE estado = 'publicada'
                            ORDER BY visualizacoes DESC 
                            LIMIT $limite");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Retorna os cursos mais visualizados
 */
function getCursosMaisVistos($limite = 5) {
    $db = getDB();
    try {
        $stmt = $db->query("SELECT id, nome, visualizacoes 
                            FROM cursos 
                            WHERE estado = 'ativo'
                            ORDER BY visualizacoes DESC 
                            LIMIT $limite");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        // Fallback: usar estatisticas
        $stmt = $db->query("SELECT c.id, c.nome, COALESCE(SUM(e.contador), 0) as visualizacoes
                            FROM cursos c
                            LEFT JOIN estatisticas e ON e.referencia_id = c.id AND e.tipo = 'curso'
                            WHERE c.estado = 'ativo'
                            GROUP BY c.id
                            ORDER BY visualizacoes DESC
                            LIMIT $limite");
        return $stmt->fetchAll();
    }
}
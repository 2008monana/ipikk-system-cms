<?php
/**
 * Dashboard - Área Restrita IPIKK
 */

// Caminhos - sobe um nível para acessar a pasta config
define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/config/functions.php';
require_once BASE_PATH . '/config/constants.php';

session_start();

// Verificar se está logado
if (!isset($_SESSION['utilizador_id'])) {
    header('Location: area-restrita.php');
    exit;
}

require_once __DIR__ . '/includes/verificar-permissao.php';

// ===== VERIFICAR PERMISSÃO ESPECÍFICA =====
verificarPermissao('dashboard');
$db = getDB();

// Buscar dados do usuário
$stmt = $db->prepare("SELECT id, nome, email, foto_url FROM utilizadores WHERE id = ?");
$stmt->execute([$_SESSION['utilizador_id']]);
$usuario = $stmt->fetch();

if (!$usuario) {
    session_destroy();
    header('Location: area-restrita.php');
    exit;
}
$permissoes = isset($_SESSION['utilizador_permissoes'])     ? (is_array($_SESSION['utilizador_permissoes']) ? $_SESSION['utilizador_permissoes'] : json_decode($_SESSION['utilizador_permissoes'], true))
    : [];
$nivel = $_SESSION['utilizador_nivel'] ?? 'editor';

if ($nivel !== 'admin' && !in_array('dashboard', $permissoes) && !in_array('*', $permissoes)) {
    header('Location: admin-dashboard.php?erro=permissao');
    exit;
}

// Buscar configurações
$config = $db->query("SELECT * FROM configuracoes WHERE id = 1")->fetch();

// ===== ESTATÍSTICAS =====
$stmt = $db->query("SELECT SUM(contador) as total FROM estatisticas WHERE tipo = 'visitante' AND data_referencia > DATE_SUB(NOW(), INTERVAL 30 DAY)");
$total_visitantes = $stmt->fetch()['total'] ?? 0;

$stmt = $db->query("SELECT COUNT(*) as total FROM mensagens WHERE lida = 0");
$mensagens_nao_lidas = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM noticias WHERE estado = 'publicada'");
$total_noticias = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM cursos WHERE estado = 'ativo'");
$total_cursos = $stmt->fetch()['total'];

// ===== CONTACTOS RECENTES =====
$stmt = $db->query("SELECT id, nome, assunto, data_envio, lida FROM mensagens ORDER BY data_envio DESC LIMIT 5");
$contactos_recentes = $stmt->fetchAll();

// ===== ÚLTIMAS NOTÍCIAS =====
$stmt = $db->query("SELECT id, titulo, data_publicacao, estado FROM noticias ORDER BY created_at DESC LIMIT 5");
$ultimas_noticias = $stmt->fetchAll();

// ===== CURSOS MAIS VISITADOS =====
$stmt = $db->query("
    SELECT 
        c.id, 
        c.nome, 
        c.cor,
        c.icone_classe as icone_curso,
        a.icone_classe as icone_area,
        a.cor_primaria,
        COALESCE(SUM(e.contador), 0) as visualizacoes
    FROM cursos c
    LEFT JOIN areas a ON c.area_id = a.id
    LEFT JOIN estatisticas e ON e.tipo = 'curso' AND e.referencia_id = c.id
    WHERE c.estado = 'ativo'
    GROUP BY c.id
    ORDER BY visualizacoes DESC
    LIMIT 7
");
$cursos_mais_visitados = $stmt->fetchAll();

// ===== ATIVIDADE RECENTE =====
$stmt = $db->prepare("SELECT acao, tabela, detalhes, data_hora FROM logs WHERE utilizador_id = ? ORDER BY data_hora DESC LIMIT 10");
$stmt->execute([$usuario['id']]);
$atividades = $stmt->fetchAll();

function formatarNumeroComK($numero) {
    if ($numero >= 1000) {
        return round($numero / 1000, 1) . 'K';
    }
    return (string)$numero;
}

$titulo_pagina = 'Dashboard';
$css_especifico = 'admin-dashboard.css';

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<main class="conteudo-principal">
    <?php
    // Garantir que $usuario tem a foto_url correta
    if (function_exists('renderAdminTopbar')) {
        renderAdminTopbar($titulo_pagina);
    } else {
        $usuario_topo = $usuario;
        $titulo_topo = $titulo_pagina;
        include 'includes/topbar-fallback.php';
    }
    ?>
        <div class="conteudo-dashboard">
        
        <!-- ESTATÍSTICAS RÁPIDAS -->
        <section class="secao-estatisticas">
            <h2 class="titulo-secao"><i class="fas fa-chart-pie"></i> Estatísticas Rápidas</h2>
            <div class="grade-estatisticas">
                <div class="cartao-estatistica">
                    <div class="icone-estatistica azul"><i class="fas fa-users"></i></div>
                    <div class="info-estatistica">
                        <h3 class="numero"><?php echo formatarNumeroComK($total_visitantes); ?></h3>
                        <p>Visitantes</p>
                    </div>
                </div>
                <div class="cartao-estatistica">
                    <div class="icone-estatistica laranja"><i class="fas fa-envelope"></i></div>
                    <div class="info-estatistica">
                        <h3 class="numero"><?php echo $mensagens_nao_lidas; ?></h3>
                        <p>Não lidas</p>
                    </div>
                </div>
                <div class="cartao-estatistica">
                    <div class="icone-estatistica verde"><i class="fas fa-newspaper"></i></div>
                    <div class="info-estatistica">
                        <h3 class="numero"><?php echo $total_noticias; ?></h3>
                        <p>Publicadas</p>
                    </div>
                </div>
                <div class="cartao-estatistica">
                    <div class="icone-estatistica roxo"><i class="fas fa-graduation-cap"></i></div>
                    <div class="info-estatistica">
                        <h3 class="numero"><?php echo $total_cursos; ?></h3>
                        <p>Cursos Ativos</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CONTACTOS RECENTES -->
        <section class="secao-conteudo">
            <div class="cabecalho-secao">
                <h2 class="titulo-secao"><i class="fas fa-inbox"></i> Contactos Recentes</h2>
                <a href="admin-contactos.php" class="link-ver-todos">Ver Todos <i class="fas fa-arrow-right"></i></a>
            </div>
            <div class="tabela-responsiva">
                <table class="tabela-dados">
                    <thead>
                         <th>Nome</th><th>Assunto</th><th>Data</th><th>Ações</th>
                    </thead>
                    <tbody>
                        <?php foreach($contactos_recentes as $c): ?>
                        <tr class="<?php echo $c['lida'] ? '' : 'nao-lida'; ?>">
                              <td>
                                <?php if(!$c['lida']): ?><span class="indicador-novo"></span><?php endif; ?>
                                <strong><?php echo htmlspecialchars($c['nome']); ?></strong>
                              </td>
                              <td><strong><?php echo htmlspecialchars($c['assunto']); ?></strong></td>
                              <td><strong><?php echo formatarData($c['data_envio']); ?></strong></td>
                              <td><button class="botao-icone" onclick="window.location.href='admin-contactos.php?ver=<?php echo $c['id']; ?>'"><i class="fas fa-eye"></i></button></td>
                          </tr>
                        <?php endforeach; ?>
                    </tbody>
                  </table>
            </div>
        </section>

        <!-- ÚLTIMAS NOTÍCIAS -->
        <section class="secao-conteudo">
            <div class="cabecalho-secao">
                <h2 class="titulo-secao"><i class="fas fa-newspaper"></i> Últimas Notícias</h2>
                <a href="admin-noticias.php" class="link-ver-todos">Ver Todas <i class="fas fa-arrow-right"></i></a>
            </div>
            <div class="tabela-responsiva">
                <table class="tabela-dados">
                    <thead> <th>Título</th><th>Data</th><th>Ações</th> </thead>
                    <tbody>
                        <?php foreach($ultimas_noticias as $n): ?>
                          <tr>
                              <td>
                                <?php if($n['estado'] == 'publicada'): ?>
                                <span class="indicador-publicado"><i class="fas fa-check-circle"></i></span>
                                <?php else: ?>
                                <span class="indicador-rascunho"><i class="fas fa-clock"></i></span>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($n['titulo']); ?>
                              </td>
                              <td><?php echo $n['estado'] == 'publicada' ? 'Publicada: ' : 'Salvo: '; ?><?php echo formatarData($n['data_publicacao']); ?></td>
                              <td><button class="botao-icone" onclick="window.location.href='admin-noticias.php?editar=<?php echo $n['id']; ?>'"><i class="fas fa-edit"></i></button></td>
                          </tr>
                        <?php endforeach; ?>
                    </tbody>
                  </table>
            </div>
        </section>

        <!-- CURSOS MAIS VISITADOS -->
<section class="secao-conteudo">
    <div class="cabecalho-secao">
        <h2 class="titulo-secao"><i class="fas fa-graduation-cap"></i> Cursos Mais Visitados</h2>
        <a href="admin-cursos.php" class="link-ver-todos">Gerir Cursos <i class="fas fa-arrow-right"></i></a>
    </div>
    <div class="grade-cursos">
                <?php 
        foreach($cursos_mais_visitados as $c): 
            $cor_hex = '#6c757d';
            $cor_curso = trim((string)($c['cor'] ?? ''));
            $cor_area = trim((string)($c['cor_primaria'] ?? ''));

            // Cor: priorizar cor do curso, depois da área
            if (preg_match('/^#[0-9a-fA-F]{6}$/', $cor_curso)) {
                $cor_hex = $cor_curso;
            } elseif (preg_match('/^[0-9a-fA-F]{6}$/', $cor_curso)) {
                $cor_hex = '#' . $cor_curso;
            } elseif (preg_match('/^#[0-9a-fA-F]{6}$/', $cor_area)) {
                $cor_hex = $cor_area;
            } elseif (preg_match('/^[0-9a-fA-F]{6}$/', $cor_area)) {
                $cor_hex = '#' . $cor_area;
            }

            // ÍCONE: priorizar ícone do curso, se não tiver usar ícone da área
            $icone_base = !empty($c['icone_curso']) ? $c['icone_curso'] : ($c['icone_area'] ?? 'fa-solid fa-graduation-cap');
            
            // Normalizar o ícone (adicionar prefixo se necessário)
            if (!empty($icone_base)) {
                if (str_starts_with($icone_base, 'fa-') && !str_contains($icone_base, 'fa-solid') && !str_contains($icone_base, 'fas ')) {
                    $icone = 'fa-solid ' . $icone_base;
                } elseif (!str_starts_with($icone_base, 'fa-') && !str_starts_with($icone_base, 'fas') && !str_starts_with($icone_base, 'far')) {
                    $icone = 'fa-solid fa-' . $icone_base;
                } else {
                    $icone = $icone_base;
                }
            } else {
                $icone = 'fa-solid fa-graduation-cap';
            }
        ?>

        <div class="cartao-curso" style="border-left-color: <?php echo $cor_hex; ?>;" onclick="window.location.href='admin-cursos.php?editar=<?php echo $c['id']; ?>'">
            <div class="numero-curso" style="background: <?php echo $cor_hex; ?>20; color: <?php echo $cor_hex; ?>;">
                <i class="<?php echo $icone; ?>"></i>
            </div>
            <div class="info-curso">
                <h3><?php echo htmlspecialchars($c['nome']); ?></h3>
                <p><i class="fas fa-eye"></i> <?php echo formatarNumero($c['visualizacoes']); ?> visualizações</p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

        <!-- ACTIVIDADE RECENTE -->
        <section class="secao-conteudo">
            <div class="cabecalho-secao">
                <h2 class="titulo-secao"><i class="fas fa-bolt"></i> Actividade Recente</h2>
                <a href="admin-logs.php" class="link-ver-todos">Ver Logs <i class="fas fa-arrow-right"></i></a>
            </div>
            <div class="lista-atividades">
                <?php foreach($atividades as $a): ?>
                <div class="item-atividade">
                    <span class="hora-atividade">[<?php echo date('H:i', strtotime($a['data_hora'])); ?>]</span>
                    <i class="fas fa-<?php echo $a['acao'] == 'login' ? 'sign-in-alt' : ($a['acao'] == 'criou' ? 'plus-circle' : 'edit'); ?>"></i>
                    <span><?php echo htmlspecialchars($a['detalhes'] ?? $a['acao'] . ' em ' . $a['tabela']); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</main>


<script>
    // ===== PERFIL DROPDOWN =====
    document.addEventListener('DOMContentLoaded', function() {
        const botaoPerfil = document.getElementById('botaoPerfil');
        const dropdownPerfil = document.getElementById('dropdownPerfil');
        
        if (botaoPerfil && dropdownPerfil) {
            botaoPerfil.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                dropdownPerfil.classList.toggle('visivel');
            });
            
            document.addEventListener('click', function(e) {
                if (!botaoPerfil.contains(e.target) && !dropdownPerfil.contains(e.target)) {
                    dropdownPerfil.classList.remove('visivel');
                }
            });
        }
        
        // ===== MENU MOBILE =====
        const botaoMenuMobile = document.getElementById('botaoMenuMobile');
        const sidebar = document.getElementById('sidebar');
        
        if (botaoMenuMobile && sidebar) {
            botaoMenuMobile.addEventListener('click', function() {
                sidebar.classList.toggle('visivel');
            });
        }
    });
</script>

<?php include 'includes/footer.php'; ?>

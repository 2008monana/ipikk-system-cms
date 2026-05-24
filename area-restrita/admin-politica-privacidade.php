<?php
/**
 * Admin - Política e Privacidade
 */

$css_especifico = 'admin-dashboard.css';

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/config/functions.php';
require_once BASE_PATH . '/config/constants.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['utilizador_id'])) {
    header('Location: area-restrita.php');
    exit;
}

require_once __DIR__ . '/includes/verificar-permissao.php';
verificarPermissao('conteudo_site');

$db = getDB();
$config = $db->query("SELECT * FROM configuracoes WHERE id = 1")->fetch();

function tabelaPoliticaExiste(PDO $db): bool {
    try {
        $stmt = $db->query("SHOW TABLES LIKE 'politica_privacidade'");
        return (bool)$stmt->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

$texto_padrao = <<<'TXT'
1. Utilização do Site
O IPIKK empenha-se em manter a informação disponível neste site atualizada e rigorosa. Ainda assim, não é possível garantir que todos os conteúdos estejam permanentemente atualizados ou isentos de imprecisões.
Este site é de acesso livre e tem como propósito apresentar a oferta formativa, os valores institucionais, as atividades, projetos, notícias e eventos do IPIKK. Os utilizadores podem descarregar, visualizar ou imprimir conteúdos do site exclusivamente para uso pessoal e não comercial.
TXT;

$mensagem = '';
$tipo_mensagem = 'sucesso';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'salvar') {
        $texto = trim($_POST['texto'] ?? '');
        if (tabelaPoliticaExiste($db)) {
            $stmt = $db->prepare("INSERT INTO politica_privacidade (id, titulo, texto, ultima_actualizacao, ativo, updated_at) VALUES (1, ?, ?, ?, 1, NOW()) ON DUPLICATE KEY UPDATE titulo = VALUES(titulo), texto = VALUES(texto), ultima_actualizacao = VALUES(ultima_actualizacao), ativo = 1, updated_at = NOW()");
            $stmt->execute(['Política e Privacidade', $texto, date('Y-m-d')]);
        } else {
            $payload = json_encode(['texto' => $texto], JSON_UNESCAPED_UNICODE);
            $stmt = $db->prepare("INSERT INTO conteudo_paginas (slug, titulo, conteudo, created_at) VALUES ('politica-privacidade', 'Política e Privacidade', ?, NOW()) ON DUPLICATE KEY UPDATE conteudo = VALUES(conteudo), titulo = VALUES(titulo)");
            $stmt->execute([$payload]);
        }
        $mensagem = $texto === ''
            ? 'Conteúdo guardado vazio. A página pública ficará sem termos até novo preenchimento.'
            : 'Conteúdo salvo com sucesso.';
        $tipo_mensagem = $texto === '' ? 'aviso' : 'sucesso';
    }
}

if (tabelaPoliticaExiste($db)) {
    $stmt = $db->query("SELECT texto FROM politica_privacidade WHERE ativo = 1 ORDER BY id DESC LIMIT 1");
    $registo = $stmt->fetch();
    $texto = $registo['texto'] ?? $texto_padrao;
} else {
    $stmt = $db->prepare("SELECT conteudo FROM conteudo_paginas WHERE slug = 'politica-privacidade' LIMIT 1");
    $stmt->execute();
    $registo = $stmt->fetch();
    $dados = $registo && !empty($registo['conteudo']) ? json_decode($registo['conteudo'], true) : [];
    $texto = $dados['texto'] ?? $texto_padrao;
}

$titulo_pagina = 'Política e Privacidade';
include 'includes/header.php';
include 'includes/sidebar.php';
?>
<style>
.politica-wrap{padding:24px;max-width:1100px}
.politica-card{background:#fff;border:1px solid #e9ecef;border-radius:14px;padding:22px;box-shadow:0 6px 20px rgba(0,0,0,.05)}
.politica-titulo{margin:0 0 6px;color:#003072}.politica-sub{margin:0 0 14px;color:#6c757d}
.politica-alerta{padding:12px 14px;border-radius:10px;margin-bottom:14px;font-weight:500}
.politica-alerta.sucesso{background:#e8f8ef;color:#126c3a;border:1px solid #bfe8cd}
.politica-alerta.aviso{background:#fff7e6;color:#8a5a00;border:1px solid #ffe0a6}
.politica-textarea{width:100%;min-height:520px;padding:14px 16px;border:1px solid #ced4da;border-radius:12px;line-height:1.6;resize:vertical}
.politica-textarea:focus{outline:none;border-color:#2e86c1;box-shadow:0 0 0 3px rgba(46,134,193,.15)}
.politica-acoes{display:flex;justify-content:space-between;align-items:center;margin-top:12px;gap:10px;flex-wrap:wrap}
.politica-dica{font-size:.92rem;color:#6c757d}
.btn-guardar{background:#0a9396;color:#fff;border:none;border-radius:999px;padding:10px 18px;font-weight:600;cursor:pointer}
.btn-guardar:hover{background:#087f82}
</style>
<main class="conteudo-principal">
  <div class="politica-wrap">
    <div class="politica-card">
      <h1 class="politica-titulo">Política e Privacidade</h1>
      <p class="politica-sub">Edite os termos e clique em guardar. Para remover os termos da página pública, deixe o campo vazio e guarde.</p>
      <?php if ($mensagem): ?><div class="politica-alerta <?= $tipo_mensagem ?>"><?= htmlspecialchars($mensagem) ?></div><?php endif; ?>
      <form method="post">
        <textarea name="texto" rows="30" class="politica-textarea"><?= htmlspecialchars($texto) ?></textarea>
        <div class="politica-acoes">
          <span class="politica-dica">As alterações são aplicadas imediatamente na página pública.</span>
          <button type="submit" name="acao" value="salvar" class="btn-guardar">Guardar conteúdo</button>
        </div>
      </form>
    </div>
  </div>
</main>
<?php include 'includes/footer.php'; ?>

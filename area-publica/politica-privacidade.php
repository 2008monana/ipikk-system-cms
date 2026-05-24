<?php
require_once '../config/index.php';
$db = getDB();
$config = $db->query("SELECT * FROM configuracoes WHERE id = 1")->fetch();

$areas = $db->query("SELECT * FROM areas WHERE ativo = 1 ORDER BY ordem")->fetchAll();
$todos_cursos = $db->query("SELECT * FROM cursos WHERE estado = 'ativo' ORDER BY nome")->fetchAll();
$cursos_por_area = [];
foreach ($todos_cursos as $curso) {
    $cursos_por_area[$curso['area_id']][] = $curso;
}
$status_inscricoes = $db->query("SELECT status FROM controle_inscricoes WHERE id = 1")->fetch();
$link_inscricao = ($status_inscricoes && $status_inscricoes['status'] === 'abertas') ? 'inscricoes.php' : 'inscricoes-indisponiveis.php';

$texto_padrao = "Política de Privacidade e Utilização do Site\n\n1. Utilização do Site\nO IPIKK empenha-se em manter a informação disponível neste site atualizada e rigorosa. Ainda assim, não é possível garantir que todos os conteúdos estejam permanentemente atualizados ou isentos de imprecisões.\nEste site é de acesso livre e tem como propósito apresentar a oferta formativa, os valores institucionais, as atividades, projetos, notícias e eventos do IPIKK. Os utilizadores podem descarregar, visualizar ou imprimir conteúdos do site exclusivamente para uso pessoal e não comercial.\n\n2. Propriedade Intelectual\nTodo o conteúdo presente neste site — incluindo textos, imagens, logótipos, vídeos, documentos e outros materiais — é propriedade do IPIKK e está protegido por lei. A sua reprodução, distribuição ou utilização para criação de obras derivadas sem autorização prévia e por escrito do IPIKK é proibida, podendo dar origem a responsabilidade civil ou criminal. Esta proteção abrange igualmente o design, a estrutura, o layout e o código fonte do site.\n\n3. Condutas Não Permitidas\nEste site não pode ser utilizado para fins ilegais, abusivos ou difamatórios, nem para a transmissão de vírus ou qualquer código malicioso que possa prejudicar outros utilizadores ou o funcionamento do site. O IPIKK reserva-se o direito de recorrer às vias legais disponíveis contra quem viole estas condições.\n\n4. Dados Pessoais e Privacidade\nO IPIKK respeita a privacidade dos seus utilizadores. Os dados pessoais recolhidos através do site — como nome, email, telefone e mensagens enviadas pelo formulário de contacto — são utilizados exclusivamente para responder a pedidos de informação sobre cursos e inscrições, prestar esclarecimentos institucionais e melhorar a qualidade dos serviços.\nEstes dados não são partilhados com terceiros sem o consentimento do titular, salvo quando exigido por lei. Qualquer utilizador pode, a qualquer momento, aceder, corrigir, atualizar ou solicitar a eliminação dos seus dados, através dos contactos indicados no site.\n\n5. Cookies\nO site do IPIKK pode utilizar cookies para melhorar a experiência de navegação e as funcionalidades disponibilizadas. Os cookies não são utilizados para criar perfis de utilizadores. Caso prefira não autorizar o uso de cookies, algumas funcionalidades do site poderão não funcionar corretamente. Para gerir ou remover cookies, consulte as definições do seu navegador.\n\n6. Estatísticas de Navegação\nO IPIKK recolhe dados estatísticos de navegação — como número de visitas, páginas mais acedidas e tempo de permanência — com o único objetivo de melhorar o desempenho e a experiência no site. Estes dados são tratados de forma anónima e agregada, não permitindo identificar individualmente nenhum utilizador.\n\n7. Links para Sites Externos\nEste site pode conter ligações para sites de terceiros que não são geridos pelo IPIKK. O Instituto não se responsabiliza pelo conteúdo, políticas de privacidade ou práticas desses sites. A presença de uma ligação não implica qualquer aprovação ou recomendação por parte do IPIKK.\n\n8. Limitação de Responsabilidade\nO IPIKK não se responsabiliza por danos diretos ou indiretos resultantes da utilização ou impossibilidade de utilização deste site, incluindo perda de dados, interrupção de atividades ou danos causados por vírus informáticos. Cabe ao utilizador adotar as medidas de segurança adequadas para proteger os seus equipamentos e dados.\n\n9. Alterações a esta Política\nO IPIKK pode atualizar esta Política a qualquer momento. As alterações entram em vigor imediatamente após publicação no site. Recomendamos que consulte esta página periodicamente para se manter informado.\n\n10. Legislação Aplicável\nEsta Política é regida pela legislação da República de Angola. Qualquer litígio será submetido à jurisdição dos tribunais da comarca de Luanda.\n\n11. Contactos\nPara questões relacionadas com esta Política ou para exercer os seus direitos sobre os seus dados pessoais, entre em contacto connosco.\n\nÚltima actualização: 24 de Maio de 2026";

$stmt = $db->prepare("SELECT conteudo FROM conteudo_paginas WHERE slug = 'politica-privacidade' LIMIT 1");
$stmt->execute();
$registo = $stmt->fetch();
$dados = $registo && !empty($registo['conteudo']) ? json_decode($registo['conteudo'], true) : [];
$texto = trim($dados['texto'] ?? $texto_padrao);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>IPIKK - Política de Privacidade</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600&display=swap">
<link href="<?= $config['favicon_url'] ?? 'foto/ipikk_new_logo.png' ?>" rel="icon">
<link rel="stylesheet" href="css/header-footer.css">
<style>
body{font-family:'Montserrat',sans-serif;background:#f8f9fa;color:#212529} .wrap{max-width:960px;margin:30px auto;padding:0 16px}
.card{background:#fff;border:1px solid #e9ecef;border-radius:10px;padding:24px} h1{font-size:2rem;margin-bottom:18px;color:#003072}
h2{font-size:1.15rem;margin:18px 0 8px;color:#003072} p{line-height:1.7;margin-bottom:10px}
</style>
</head><body>
<?php include __DIR__ . '/includes/header.php'; ?>
<main class="wrap"><div class="card">
<?php
$linhas = preg_split('/\R/', $texto);
foreach ($linhas as $linha) {
    $linha = trim($linha);
    if ($linha === '') { continue; }
    if (preg_match('/^\d+\.\s+/', $linha)) echo '<h2>' . htmlspecialchars($linha) . '</h2>';
    elseif (stripos($linha, 'Política de Privacidade e Utilização do Site') === 0) echo '<h1>' . htmlspecialchars($linha) . '</h1>';
    else echo '<p>' . htmlspecialchars($linha) . '</p>';
}
?>
</div></main>
<?php include __DIR__ . '/includes/footer.php'; ?>
<script src="js/header-footer.js"></script>
</body></html>

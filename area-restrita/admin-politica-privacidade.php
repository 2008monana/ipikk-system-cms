<?php
/**
 * Admin - Política e Privacidade
 */

$css_especifico = 'admin-dashboard.css';
require_once '../config/init.php';
require_once __DIR__ . '/includes/verificar-permissao.php';
verificarPermissao('conteudo_site');

$db = getDB();

$texto_padrao = <<<'TXT'
Política de Privacidade e Utilização do Site

1. Utilização do Site
O IPIKK empenha-se em manter a informação disponível neste site atualizada e rigorosa. Ainda assim, não é possível garantir que todos os conteúdos estejam permanentemente atualizados ou isentos de imprecisões.
Este site é de acesso livre e tem como propósito apresentar a oferta formativa, os valores institucionais, as atividades, projetos, notícias e eventos do IPIKK. Os utilizadores podem descarregar, visualizar ou imprimir conteúdos do site exclusivamente para uso pessoal e não comercial.

2. Propriedade Intelectual
Todo o conteúdo presente neste site — incluindo textos, imagens, logótipos, vídeos, documentos e outros materiais — é propriedade do IPIKK e está protegido por lei. A sua reprodução, distribuição ou utilização para criação de obras derivadas sem autorização prévia e por escrito do IPIKK é proibida, podendo dar origem a responsabilidade civil ou criminal. Esta proteção abrange igualmente o design, a estrutura, o layout e o código fonte do site.

3. Condutas Não Permitidas
Este site não pode ser utilizado para fins ilegais, abusivos ou difamatórios, nem para a transmissão de vírus ou qualquer código malicioso que possa prejudicar outros utilizadores ou o funcionamento do site. O IPIKK reserva-se o direito de recorrer às vias legais disponíveis contra quem viole estas condições.

4. Dados Pessoais e Privacidade
O IPIKK respeita a privacidade dos seus utilizadores. Os dados pessoais recolhidos através do site — como nome, email, telefone e mensagens enviadas pelo formulário de contacto — são utilizados exclusivamente para responder a pedidos de informação sobre cursos e inscrições, prestar esclarecimentos institucionais e melhorar a qualidade dos serviços.
Estes dados não são partilhados com terceiros sem o consentimento do titular, salvo quando exigido por lei. Qualquer utilizador pode, a qualquer momento, aceder, corrigir, atualizar ou solicitar a eliminação dos seus dados, através dos contactos indicados no site.

5. Cookies
O site do IPIKK pode utilizar cookies para melhorar a experiência de navegação e as funcionalidades disponibilizadas. Os cookies não são utilizados para criar perfis de utilizadores. Caso prefira não autorizar o uso de cookies, algumas funcionalidades do site poderão não funcionar corretamente. Para gerir ou remover cookies, consulte as definições do seu navegador.

6. Estatísticas de Navegação
O IPIKK recolhe dados estatísticos de navegação — como número de visitas, páginas mais acedidas e tempo de permanência — com o único objetivo de melhorar o desempenho e a experiência no site. Estes dados são tratados de forma anónima e agregada, não permitindo identificar individualmente nenhum utilizador.

7. Links para Sites Externos
Este site pode conter ligações para sites de terceiros que não são geridos pelo IPIKK. O Instituto não se responsabiliza pelo conteúdo, políticas de privacidade ou práticas desses sites. A presença de uma ligação não implica qualquer aprovação ou recomendação por parte do IPIKK.

8. Limitação de Responsabilidade
O IPIKK não se responsabiliza por danos diretos ou indiretos resultantes da utilização ou impossibilidade de utilização deste site, incluindo perda de dados, interrupção de atividades ou danos causados por vírus informáticos. Cabe ao utilizador adotar as medidas de segurança adequadas para proteger os seus equipamentos e dados.

9. Alterações a esta Política
O IPIKK pode atualizar esta Política a qualquer momento. As alterações entram em vigor imediatamente após publicação no site. Recomendamos que consulte esta página periodicamente para se manter informado.

10. Legislação Aplicável
Esta Política é regida pela legislação da República de Angola. Qualquer litígio será submetido à jurisdição dos tribunais da comarca de Luanda.

11. Contactos
Para questões relacionadas com esta Política ou para exercer os seus direitos sobre os seus dados pessoais, entre em contacto connosco.

Última actualização: 24 de Maio de 2026
TXT;

$mensagem = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'salvar') {
        $texto = trim($_POST['texto'] ?? '');
        $payload = json_encode([
            'titulo' => 'Política de Privacidade e Utilização do Site',
            'texto' => $texto,
            'ultima_actualizacao' => date('Y-m-d')
        ], JSON_UNESCAPED_UNICODE);

        $stmt = $db->prepare("INSERT INTO conteudo_paginas (slug, titulo, conteudo, updated_at) VALUES ('politica-privacidade', 'Política de Privacidade', ?, NOW()) ON DUPLICATE KEY UPDATE titulo = VALUES(titulo), conteudo = VALUES(conteudo), updated_at = NOW()");
        $stmt->execute([$payload]);
        $mensagem = 'Conteúdo salvo com sucesso.';
    }

    if ($acao === 'eliminar') {
        $stmt = $db->prepare("UPDATE conteudo_paginas SET conteudo = NULL, updated_at = NOW() WHERE slug = 'politica-privacidade'");
        $stmt->execute();
        $mensagem = 'Conteúdo eliminado da base de dados.';
    }
}

$stmt = $db->prepare("SELECT conteudo FROM conteudo_paginas WHERE slug = 'politica-privacidade' LIMIT 1");
$stmt->execute();
$registo = $stmt->fetch();
$dados = $registo && !empty($registo['conteudo']) ? json_decode($registo['conteudo'], true) : [];
$texto = $dados['texto'] ?? $texto_padrao;

$titulo_pagina = 'Política e Privacidade';
include 'includes/header.php';
include 'includes/sidebar.php';
?>
<main class="conteudo-principal" style="padding:20px;">
    <h1>Política e Privacidade</h1>
    <?php if ($mensagem): ?><p><?= htmlspecialchars($mensagem) ?></p><?php endif; ?>
    <form method="post" style="display:flex;flex-direction:column;gap:12px;max-width:1100px;">
        <textarea name="texto" rows="30" class="campo-form" style="width:100%;padding:12px;"><?= htmlspecialchars($texto) ?></textarea>
        <div style="display:flex;gap:10px;">
            <button type="submit" name="acao" value="salvar" class="btn-salvar">Guardar conteúdo</button>
            <button type="submit" name="acao" value="eliminar" class="btn-cancelar" onclick="return confirm('Tem certeza que deseja eliminar o conteúdo da política?')">Eliminar do banco</button>
        </div>
    </form>
</main>
<?php include 'includes/footer.php'; ?>

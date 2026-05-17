<?php
/**
 * Footer compartilhado da Área Pública - IPIKK
 */
if (!isset($config) || !is_array($config)) {
    $config = getDB()->query("SELECT * FROM configuracoes WHERE id = 1")->fetch();
}
if (!isset($areas) || !is_array($areas)) {
    $areas = getDB()->query("SELECT * FROM areas WHERE ativo = 1 ORDER BY ordem")->fetchAll();
}
if (!isset($link_inscricao)) {
    $status_inscricoes = getDB()->query("SELECT status FROM controle_inscricoes WHERE id = 1")->fetch();
    $link_inscricao = ($status_inscricoes && $status_inscricoes['status'] === 'abertas') ? 'inscricoes.php' : 'inscricoes-indisponiveis.php';
}
$links_ipikk = array_filter(array_map('trim', explode("\n", (string)($config['rodape_links_ipikk'] ?? ''))));
$links_rapidos = array_filter(array_map('trim', explode("\n", (string)($config['rodape_links_rapidos'] ?? ''))));
?>

<footer class="rodape">
    <div class="container-rodape">
        <div class="conteudo-rodape">
            
            <!-- Coluna Logo e Descrição -->
            <div class="coluna-logo">
                <div class="foto-instituto">
                    <img src="<?= $config['logo_url'] ?? 'foto/ipikk_new_logo_1.png' ?>" alt="Instituto IPIKK">
                </div>
                <p class="descricao-rodape"><?= htmlspecialchars($config['instituicao_nome'] ?? 'Instituto Médio Politécnico Industrial do Kilamba Kiaxi Nº8050 "Nova Vida"') ?></p>
                <p class="email-rodape">Email: <a href="mailto:<?= $config['email_geral'] ?? 'geral@ipikk.ao' ?>"><?= $config['email_geral'] ?? 'geral@ipikk.ao' ?></a></p>
            </div>

            <!-- Coluna IPIKK -->
            <div class="coluna-links">
                <h4 class="titulo-rodape">IPIKK</h4>
                <div class="links-rodape">
                    <?php if (!empty($links_ipikk)): foreach ($links_ipikk as $linha): $partes = array_map('trim', explode('|', $linha, 2)); if (count($partes) < 2) continue; ?>
                    <a href="<?= htmlspecialchars($partes[1]) ?>" class="link-rodape"><?= htmlspecialchars($partes[0]) ?></a>
                    <?php endforeach; else: ?>
                    <a href="sobre-nos.php" class="link-rodape">Sobre Nós</a>
                    <a href="<?= $link_inscricao ?>" class="link-rodape">Inscrição</a>
                    <a href="contatos.php" class="link-rodape">Contactos</a>
                    <a href="area-restrita.php" class="link-rodape">Área Restrita</a>
                    <a href="politica-privacidade.php" class="link-rodape">Políticas de Privacidade</a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Coluna Oferta Formativa -->
            <div class="coluna-links">
                <h4 class="titulo-rodape">Oferta Formativa</h4>
                <div class="links-rodape">
                    <a href="oferta-formativa.php" class="link-rodape">Ver Todos os Cursos</a>
                    <?php foreach($areas as $area_rodape): ?>
                    <a href="area.php?slug=<?= $area_rodape['slug'] ?>" class="link-rodape"><?= htmlspecialchars($area_rodape['nome']) ?></a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Links Rápidos (Governo, Ministérios, etc.) -->
            <div class="coluna-links">
                <h4 class="titulo-rodape">Links Rápidos</h4>
                <div class="links-rodape">
                    <?php if (!empty($links_rapidos)): foreach ($links_rapidos as $linha): $partes = array_map('trim', explode('|', $linha, 2)); if (count($partes) < 2) continue; ?>
                    <a href="<?= htmlspecialchars($partes[1]) ?>" class="link-rodape" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars($partes[0]) ?></a>
                    <?php endforeach; else: ?>
                    <a href="https://governo.gov.ao/" class="link-rodape" target="_blank" rel="noopener noreferrer">Governo de Angola</a>
                    <a href="https://luanda.gov.ao/" class="link-rodape" target="_blank" rel="noopener noreferrer">Governo Provincial de Luanda</a>
                    <a href="https://med.gov.ao/" class="link-rodape" target="_blank" rel="noopener noreferrer">Ministério da Educação</a>
                    <a href="https://itel.gov.ao/" class="link-rodape" target="_blank" rel="noopener noreferrer">Instituto de Telecomunicações</a>
                    <a href="https://webmail.ipikk.ao/" class="link-rodape" target="_blank" rel="noopener noreferrer">Webmail IPIKK</a>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <!-- Rodapé Inferior -->
        <div class="rodape-inferior">
            <p>IPIKK <?= date('Y') ?> © Todos os direitos reservados by <strong>IPIKK</strong></p>
        </div>
    </div>
</footer>
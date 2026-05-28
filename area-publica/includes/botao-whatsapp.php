<?php
/**
 * Botão/link reutilizável de WhatsApp da área pública.
 *
 * Variáveis opcionais antes do include:
 * - $whatsapp_valor: número/link a usar. Padrão: $config['whatsapp_numero'].
 * - $whatsapp_classe: classes CSS do link. Padrão: botão flutuante.
 * - $whatsapp_titulo: atributo title/aria-label. Padrão: WhatsApp.
 * - $whatsapp_conteudo: HTML interno do link. Padrão: ícone do WhatsApp.
 */

if (!isset($config) || !is_array($config)) {
    $config = getDB()->query("SELECT * FROM configuracoes WHERE id = 1")->fetch();
}

$whatsapp_valor_final = $whatsapp_valor ?? ($config['whatsapp_numero'] ?? '');
$whatsapp_link_final = montarLinkWhatsApp($whatsapp_valor_final);

if ($whatsapp_link_final):
    $whatsapp_classe_final = $whatsapp_classe ?? 'botao-flutuante whatsapp';
    $whatsapp_titulo_final = $whatsapp_titulo ?? 'WhatsApp';
    $whatsapp_conteudo_final = $whatsapp_conteudo ?? '<i class="fab fa-whatsapp"></i>';
?>
<a href="<?= htmlspecialchars($whatsapp_link_final) ?>" class="<?= htmlspecialchars($whatsapp_classe_final) ?>" target="_blank" rel="noopener" title="<?= htmlspecialchars($whatsapp_titulo_final) ?>" aria-label="<?= htmlspecialchars($whatsapp_titulo_final) ?>">
    <?= $whatsapp_conteudo_final ?>
</a>
<?php
endif;

unset(
    $whatsapp_valor,
    $whatsapp_classe,
    $whatsapp_titulo,
    $whatsapp_conteudo,
    $whatsapp_valor_final,
    $whatsapp_link_final,
    $whatsapp_classe_final,
    $whatsapp_titulo_final,
    $whatsapp_conteudo_final
);

-- Permite guardar números longos ou links de WhatsApp/canais no campo dedicado.
ALTER TABLE configuracoes
    MODIFY COLUMN whatsapp_numero VARCHAR(255) DEFAULT NULL;

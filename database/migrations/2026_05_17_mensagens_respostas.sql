-- Suporte a respostas de mensagens e limitação diária por email.
-- Execute na base de dados existente antes de usar a resposta via painel.

ALTER TABLE mensagens
  ADD COLUMN IF NOT EXISTS respondida TINYINT(1) DEFAULT 0 AFTER lida,
  ADD COLUMN IF NOT EXISTS data_resposta TIMESTAMP NULL DEFAULT NULL AFTER respondida,
  ADD COLUMN IF NOT EXISTS respondido_por INT NULL AFTER data_resposta,
  ADD COLUMN IF NOT EXISTS resposta_texto TEXT NULL AFTER respondido_por;

CREATE INDEX IF NOT EXISTS idx_email_data ON mensagens (email, data_envio);
CREATE INDEX IF NOT EXISTS idx_respondida ON mensagens (respondida);

ALTER TABLE mensagens
  ADD CONSTRAINT mensagens_respondido_por_fk
  FOREIGN KEY (respondido_por) REFERENCES utilizadores(id)
  ON DELETE SET NULL;

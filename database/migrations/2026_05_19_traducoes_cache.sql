CREATE TABLE IF NOT EXISTS traducoes_cache (
  id INT AUTO_INCREMENT PRIMARY KEY,
  source_lang VARCHAR(10) NOT NULL,
  target_lang VARCHAR(10) NOT NULL,
  texto_hash CHAR(64) NOT NULL,
  texto_original TEXT NOT NULL,
  texto_traduzido MEDIUMTEXT NOT NULL,
  provider VARCHAR(40) NOT NULL DEFAULT 'google',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_traducao_cache (source_lang, target_lang, texto_hash),
  KEY idx_updated_at (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

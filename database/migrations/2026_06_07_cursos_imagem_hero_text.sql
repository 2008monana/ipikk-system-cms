-- Permite guardar URLs completas de imagens de capa dos cursos.
-- URLs de serviços de mídia (ex.: Cloudinary) podem ultrapassar VARCHAR(255).
ALTER TABLE cursos MODIFY imagem_hero TEXT NULL;

-- Execute uma vez em bancos criados antes da preferência de e-mail do perfil.
-- Não remove nem altera dados existentes.
ALTER TABLE tb_usuario
    ADD COLUMN avisos_email TINYINT(1) NOT NULL DEFAULT 0;

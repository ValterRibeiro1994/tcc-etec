-- Execute uma vez no banco relate_sbc. A coluna é nullable para não alterar
-- usuários já existentes; registros novos de representante usam uma categoria.
ALTER TABLE tb_usuario
    ADD COLUMN area_atuacao_usuario VARCHAR(30) DEFAULT NULL AFTER cargo_usuario;

-- Categorias usadas pelo cadastro de representantes e pelo mural.
INSERT IGNORE INTO tb_categoria (nome_categoria)
VALUES ('Infraestrutura'), ('Iluminação Publica'), ('Saneamento');

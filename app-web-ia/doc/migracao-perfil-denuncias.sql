-- Execute uma vez no banco relate_sbc para compatibilizar o esquema antigo
-- com as consultas de detalhe e listagem de denúncias do perfil.
ALTER TABLE tb_denuncia
    ADD COLUMN anonima_denuncia TINYINT(1) NOT NULL DEFAULT 0;

-- A autorização de representantes é registrada manualmente pelo administrador.
CREATE TABLE IF NOT EXISTS tb_representante_aprovado (
    id_usuario INT NOT NULL PRIMARY KEY,
    aprovado_em DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

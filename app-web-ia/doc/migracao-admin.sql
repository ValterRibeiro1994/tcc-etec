-- Execute após migracao-chat-notificacoes.sql em bancos legados.
-- A alteração é aditiva e preserva as notificações já existentes.
ALTER TABLE tb_notificacao
    MODIFY tipo_notificacao ENUM(
        'nova_denuncia',
        'status',
        'comentario',
        'mensagem',
        'sistema'
    ) NOT NULL;

CREATE TABLE IF NOT EXISTS tb_admin_auditoria (
    id_auditoria INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    id_administrador INT DEFAULT NULL,
    id_representante INT DEFAULT NULL,
    acao VARCHAR(30) NOT NULL,
    efetuado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_admin_auditoria_data (id_auditoria),
    INDEX idx_admin_auditoria_administrador (id_administrador),
    INDEX idx_admin_auditoria_representante (id_representante)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO tb_usuario (
    perfil_usuario,
    nome_usuario,
    sobrenome_usuario,
    email_usuario,
    cpf_usuario,
    senha_usuario,
    cidade_usuario,
    estado_usuario,
    orgao_usuario,
    cargo_usuario
)
VALUES
(
    'municipe',
    'Joana',
    'Teste',
    'joana.teste@example.test',
    '00000000191',
    '$2y$10$G84NkqzjSIuhIMPJSfep7eNcy1xJmQqqY.WLtsS5NIX3MlY6woTwi',
    'São Bernardo do Campo',
    'SP',
    '',
    ''
),
(
    'municipe',
    'Caio',
    'Teste',
    'caio.teste@example.test',
    '00000000272',
    '$2y$10$G84NkqzjSIuhIMPJSfep7eNcy1xJmQqqY.WLtsS5NIX3MlY6woTwi',
    'São Bernardo do Campo',
    'SP',
    '',
    ''
),
(
    'representante',
    'Renata',
    'Teste',
    'renata.teste@example.test',
    '00000000353',
    '$2y$10$G84NkqzjSIuhIMPJSfep7eNcy1xJmQqqY.WLtsS5NIX3MlY6woTwi',
    'São Bernardo do Campo',
    'SP',
    'Ouvidoria Municipal',
    'Atendente'
);

INSERT INTO tb_denuncia (
    id_usuario,
    titulo_denuncia,
    data_denuncia,
    descricao_denuncia,
    imagem_denuncia,
    nome_categoria,
    cidade_denuncia,
    estado_denuncia,
    logradouro_denuncia,
    numero_denuncia,
    bairro_denuncia,
    cep_denuncia,
    status_denuncia,
    perfil_usuario,
    complemento_denuncia,
    anonima_denuncia
)

SELECT
    u.id_usuario,

    CONCAT(
        '[TESTE] ',
        CASE MOD(n.seq, 5)
            WHEN 0 THEN 'Buraco na via publica'
            WHEN 1 THEN 'Poste sem iluminacao'
            WHEN 2 THEN 'Calcada danificada'
            WHEN 3 THEN 'Lixo acumulado'
            ELSE 'Bueiro entupido'
        END,
        ' #',
        LPAD(n.seq, 3, '0')
    ),

    DATE_SUB(
        NOW(),
        INTERVAL (
            n.seq * 2 + MOD(u.id_usuario, 3)
        ) HOUR
    ),

    CONCAT(
        'Denuncia ficticia criada para testar ',
        'o funcionamento e os limites do mural. ',
        'Registro de teste numero ',
        n.seq,
        '. Nao representa uma ocorrencia real.'
    ),

    NULL,

    (
        SELECT c.nome_categoria
        FROM tb_categoria c
        ORDER BY CRC32(
            CONCAT(
                c.nome_categoria,
                '-',
                u.id_usuario,
                '-',
                n.seq
            )
        )
        LIMIT 1
    ),

    'São Bernardo do Campo',

    'SP',

    CONCAT(
        'Rua Exemplo ',
        LPAD(n.seq, 3, '0'),
        ', ',
        100 + n.seq,
        ' - Centro'
    ),

    CAST(100 + n.seq AS CHAR),

    'Centro',

    '09725300',

    CASE MOD(n.seq, 4)
        WHEN 0 THEN 'pendente'
        WHEN 1 THEN 'em analise'
        WHEN 2 THEN 'em andamento'
        ELSE 'concluida'
    END,

    u.perfil_usuario,

    NULL,

    CASE
        WHEN MOD(n.seq, 3) = 0 THEN 1
        ELSE 0
    END

FROM tb_usuario u

CROSS JOIN (
    SELECT
        a.n * 70 + b.n * 10 + c.n + 1 AS seq

    FROM (
        SELECT 0 AS n
        UNION ALL SELECT 1
        UNION ALL SELECT 2
    ) a

    CROSS JOIN (
        SELECT 0 AS n
        UNION ALL SELECT 1
        UNION ALL SELECT 2
        UNION ALL SELECT 3
        UNION ALL SELECT 4
        UNION ALL SELECT 5
        UNION ALL SELECT 6
    ) b

    CROSS JOIN (
        SELECT 0 AS n
        UNION ALL SELECT 1
        UNION ALL SELECT 2
        UNION ALL SELECT 3
        UNION ALL SELECT 4
        UNION ALL SELECT 5
        UNION ALL SELECT 6
        UNION ALL SELECT 7
        UNION ALL SELECT 8
        UNION ALL SELECT 9
    ) c
) n

WHERE
    (
        u.email_usuario = 'joana.teste@example.test'
        AND n.seq <= 205
    )
    OR
    (
        u.email_usuario IN (
            'caio.teste@example.test',
            'renata.teste@example.test'
        )
        AND n.seq <= 10
    );

    #Teste@2026 senha
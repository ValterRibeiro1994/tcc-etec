<?php

class DenunciasRepositorio
{

    // ==================================================================
    // ALTERADO: agora grava a denúncia na tabela tb_denuncia.
    // - id_usuario e perfil_usuario são buscados no banco pelo e-mail do usuário
    //   autenticado (nunca vêm do formulário).
    // - logradouro_denuncia recebe a junção dos campos de endereço (Endereco::getLogradouro()).
    // - status_denuncia não é enviado: o banco usa o padrão 'pendente'.
<<<<<<< Updated upstream
    // ==================================================================
    public function salvarDenuncia(Denuncia $denuncia): array {
=======
    public function salvarDenuncia(Denuncia $denuncia, bool $anonima = false): array
    {
>>>>>>> Stashed changes
        $tabela_denuncia = $GLOBALS['denuncia'];
        $tabela_usuario = $GLOBALS['usuario'];

        try {
            $conexao = (new Conexao())->getConexao();

            // localiza o autor da denúncia
            $sql = $conexao->prepare(
                "SELECT id_usuario, perfil_usuario FROM $tabela_usuario WHERE email_usuario = :email"
            );
            $sql->bindValue(':email', $denuncia->getUsuario()->getEmail());
            $sql->execute();
            $autor = $sql->fetch(PDO::FETCH_ASSOC);

            if (!$autor)
                return RespostaProcesso::resposta("Usuário não localizado");

            $endereco = $denuncia->getEndereco();
            $foto = $denuncia->getFoto();
            $complemento = $endereco->getComplemento();

            $comando = "
                INSERT INTO $tabela_denuncia (
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
                    perfil_usuario,
                    complemento_denuncia,
                    anonima_denuncia
                ) VALUES (
                    :id_usuario,
                    :titulo,
                    :data,
                    :descricao,
                    :imagem,
                    :categoria,
                    :cidade,
                    :estado,
                    :logradouro,
                    :numero,
                    :bairro,
                    :cep,
                    :perfil,
                    :complemento,
                    :anonima
                )
            ";

            $sql = $conexao->prepare($comando);
            $sql->bindValue(':id_usuario', (int) $autor['id_usuario'], PDO::PARAM_INT);
            $sql->bindValue(':titulo', $denuncia->getTitulo());
            $sql->bindValue(':data', $denuncia->getData());
            $sql->bindValue(':descricao', $denuncia->getTexto());
            $sql->bindValue(':imagem', $foto, $foto === null ? PDO::PARAM_NULL : PDO::PARAM_LOB);
            $sql->bindValue(':categoria', $denuncia->getCategoria());
            $sql->bindValue(':cidade', $endereco->getCidade());
            $sql->bindValue(':estado', $endereco->getEstado());
            $sql->bindValue(':logradouro', $endereco->getLogradouro());
            $sql->bindValue(':numero', $endereco->getNumero());
            $sql->bindValue(':bairro', $endereco->getBairro());
            $sql->bindValue(':cep', $endereco->getCep());
            $sql->bindValue(':perfil', $autor['perfil_usuario']);
            $sql->bindValue(':complemento', $complemento, $complemento === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $sql->bindValue(':anonima', $anonima ? 1 : 0, PDO::PARAM_INT);
            $sql->execute();

            return RespostaProcesso::resposta(
                mensagem: "Denúncia registrada com sucesso",
                resposta: true,
                dados: ["id" => (int) $conexao->lastInsertId()]
            );

        } catch (PDOException $erro) {
            // categoria inexistente (chave estrangeira de nome_categoria)
            if (str_contains(strtoupper($erro->getMessage()), "FOREIGN KEY")) {
                return RespostaProcesso::resposta("Categoria inválida");
            }
            return RespostaProcesso::resposta(
                mensagem: "Erro ao salvar a denúncia",
                resposta: false,
                dados: RespostaProcesso::salvarErro($erro)
            );
        }
    }

    // ==================================================================
    // ADICIONADO: regras de edição/remoção.
    // Só é possível editar ou remover uma denúncia enquanto ela estiver com um destes status.
    // Para liberar em outros status, inclua-os aqui (ex.: ['pendente', 'em analise']).
    // ==================================================================
    public const STATUS_VALIDOS = ['pendente', 'em analise', 'em andamento', 'concluida'];

    private const STATUS_REMOVIVEIS = ['pendente', 'em analise', 'em andamento'];
    private const MSG_STATUS = "Só não é possivel remover denuncias concluidas";

    private function podeRemover(string $status): bool
    {
        return in_array($status, self::STATUS_REMOVIVEIS, true);
    }

    // ADICIONADO: busca UMA denúncia pelo id, mas só se ela pertencer ao usuário do e-mail informado.
    // Devolve null quando não existe ou é de outra pessoa (assim ninguém descobre denúncias alheias).
    private function buscarDoUsuario(PDO $conexao, int $id, string $email, string $colunas = "d.id_denuncia, d.status_denuncia, d.data_denuncia"): array|null
    {
        $tabela_denuncia = $GLOBALS['denuncia'];
        $tabela_usuario = $GLOBALS['usuario'];

        $sql = $conexao->prepare(
            "SELECT $colunas
             FROM $tabela_denuncia d
             INNER JOIN $tabela_usuario u ON u.id_usuario = d.id_usuario
             WHERE d.id_denuncia = :id AND u.email_usuario = :email"
        );
        $sql->bindValue(':id', $id, PDO::PARAM_INT);
        $sql->bindValue(':email', $email);
        $sql->execute();

        $linha = $sql->fetch(PDO::FETCH_ASSOC);
        return $linha ?: null;
    }

    // ==================================================================
    // ADICIONADO: dados completos de uma denúncia do usuário (preenche o formulário de edição).
    // ==================================================================
    public function obterDenunciaDoUsuario(int $id, string $email): array
    {
        try {
            $conexao = (new Conexao())->getConexao();
            $linha = $this->buscarDoUsuario($conexao, $id, $email, "d.*");

            if ($linha === null)
                return RespostaProcesso::resposta("Denúncia não encontrada");

            // o banco guarda o CEP só com números (8 dígitos); o formulário usa 00000-000
            $cep = (string) $linha['cep_denuncia'];
            if (strlen($cep) === 8)
                $cep = substr($cep, 0, 5) . "-" . substr($cep, 5);

            return RespostaProcesso::resposta(
                mensagem: "Denúncia encontrada",
                resposta: true,
                dados: [
                    "id" => (int) $linha['id_denuncia'],
                    "titulo" => $linha['titulo_denuncia'],
                    "descricao" => $linha['descricao_denuncia'],
                    "categoria" => $linha['nome_categoria'],
                    "data" => (new DateTime($linha['data_denuncia']))->format("Y-m-d"), // formato do <input type="date">
                    "estado" => $linha['estado_denuncia'],
                    "cidade" => $linha['cidade_denuncia'],
                    "rua" => $this->extrairRua($linha),
                    "numero" => (string) $linha['numero_denuncia'],
                    "complemento" => (string) ($linha['complemento_denuncia'] ?? ""),
                    "bairro" => $linha['bairro_denuncia'],
                    "cep" => $cep,
                    "status" => $linha['status_denuncia'],
                    "imagem" => $this->imagemParaDataUri($linha['imagem_denuncia'])
                ]
            );
        } catch (PDOException $erro) {
            return RespostaProcesso::resposta(
                mensagem: "Erro ao buscar a denúncia",
                resposta: false,
                dados: RespostaProcesso::salvarErro($erro)
            );
        }
    }

    // ==================================================================
    // ADICIONADO: atualiza uma denúncia do usuário.
    // - só o autor pode editar, e só enquanto o status permitir (STATUS_EDITAVEIS);
    // - logradouro_denuncia é montado de novo pelo Endereco::getLogradouro();
    // - a foto só é trocada se uma nova for enviada;
    // - status e autor da denúncia nunca mudam por aqui.
    // ==================================================================
    public function atualizarDenuncia(int $id, string $email, Denuncia $denuncia, string $status): array
    {
        $tabela_denuncia = $GLOBALS['denuncia'];

        try {
            $conexao = (new Conexao())->getConexao();
            $existente = $this->buscarDoUsuario($conexao, $id, $email);

            if ($existente === null)
                return RespostaProcesso::resposta("Denúncia não encontrada");
            if (!in_array($status, self::STATUS_VALIDOS, true))
                return RespostaProcesso::resposta("Status inválido");

            $endereco = $denuncia->getEndereco();
            $foto = $denuncia->getFoto();
            $complemento = $endereco->getComplemento();

            $campos = "titulo_denuncia = :titulo, descricao_denuncia = :descricao,
                       nome_categoria = :categoria, cidade_denuncia = :cidade, estado_denuncia = :estado,
                       logradouro_denuncia = :logradouro, numero_denuncia = :numero, bairro_denuncia = :bairro,
                       cep_denuncia = :cep, complemento_denuncia = :complemento, status_denuncia = :status";
            if ($foto !== null)
                $campos .= ", imagem_denuncia = :imagem";

            $sql = $conexao->prepare("UPDATE $tabela_denuncia SET $campos WHERE id_denuncia = :id");
            $sql->bindValue(':titulo', $denuncia->getTitulo());
            $sql->bindValue(':descricao', $denuncia->getTexto());
            $sql->bindValue(':categoria', $denuncia->getCategoria());
            $sql->bindValue(':cidade', $endereco->getCidade());
            $sql->bindValue(':estado', $endereco->getEstado());
            $sql->bindValue(':logradouro', $endereco->getLogradouro());
            $sql->bindValue(':numero', $endereco->getNumero());
            $sql->bindValue(':bairro', $endereco->getBairro());
            $sql->bindValue(':cep', $endereco->getCep());
            $sql->bindValue(':complemento', $complemento, $complemento === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $sql->bindValue(':status', $status);
            if ($foto !== null)
                $sql->bindValue(':imagem', $foto, PDO::PARAM_LOB);
            $sql->bindValue(':id', $id, PDO::PARAM_INT);
            $sql->execute();

            return RespostaProcesso::resposta(
                mensagem: "Denúncia atualizada com sucesso",
                resposta: true,
                dados: ["id" => $id]
            );

        } catch (PDOException $erro) {
            // categoria inexistente (chave estrangeira de nome_categoria)
            if (str_contains(strtoupper($erro->getMessage()), "FOREIGN KEY")) {
                return RespostaProcesso::resposta("Categoria inválida");
            }
            return RespostaProcesso::resposta(
                mensagem: "Erro ao atualizar a denúncia",
                resposta: false,
                dados: RespostaProcesso::salvarErro($erro)
            );
        }
    }

    // ==================================================================
    // ADICIONADO: remove uma denúncia do usuário (antes era um método vazio).
    // As respostas da denúncia (tb_resposta) saem junto, na mesma transação,
    // porque elas apontam para a denúncia por chave estrangeira.
    // ==================================================================
    public function apagarDenuncia(int $id, string $email): array
    {
        $tabela_denuncia = $GLOBALS['denuncia'];
        $tabela_resposta = $GLOBALS['resposta'] ?? "tb_resposta"; // não existe no config.php, então usa o nome padrão

        try {
            $conexao = (new Conexao())->getConexao();
            $existente = $this->buscarDoUsuario($conexao, $id, $email);

            if ($existente === null)
                return RespostaProcesso::resposta("Denúncia não encontrada");
            if (!$this->podeRemover($existente['status_denuncia']))
                return RespostaProcesso::resposta(self::MSG_STATUS);

            $conexao->beginTransaction();
            try {
                // 1) respostas ligadas à denúncia (se a tabela ainda não existir no banco, segue sem erro)
                try {
                    $sql = $conexao->prepare("DELETE FROM $tabela_resposta WHERE id_denuncia = :id");
                    $sql->bindValue(':id', $id, PDO::PARAM_INT);
                    $sql->execute();
                } catch (PDOException $erro) {
                    if ((int) ($erro->errorInfo[1] ?? 0) !== 1146)
                        throw $erro; // 1146 = tabela não existe
                }

                // 2) a denúncia
                $sql = $conexao->prepare("DELETE FROM $tabela_denuncia WHERE id_denuncia = :id");
                $sql->bindValue(':id', $id, PDO::PARAM_INT);
                $sql->execute();

                $conexao->commit();
            } catch (PDOException $erro) {
                if ($conexao->inTransaction())
                    $conexao->rollBack();
                throw $erro;
            }

            return RespostaProcesso::resposta(
                mensagem: "Denúncia removida com sucesso",
                resposta: true,
                dados: ["id" => $id]
            );

        } catch (PDOException $erro) {
            // alguma outra tabela ainda aponta para esta denúncia
            if (str_contains(strtoupper($erro->getMessage()), "FOREIGN KEY")) {
                return RespostaProcesso::resposta("Não foi possível remover: a denúncia possui registros vinculados");
            }
            return RespostaProcesso::resposta(
                mensagem: "Erro ao remover a denúncia",
                resposta: false,
                dados: RespostaProcesso::salvarErro($erro)
            );
        }
    }

    // ADICIONADO: o banco só guarda o logradouro já montado ("Rua, número[, complemento] - Bairro").
    // Para preencher o campo "rua" na edição, tiramos do final o bairro, o complemento e o número.
    private function extrairRua(array $linha): string
    {
        $rua = (string) $linha['logradouro_denuncia'];
        $sufixos = [
            " - " . (string) $linha['bairro_denuncia'],
            ", " . (string) ($linha['complemento_denuncia'] ?? ""),
            ", " . (string) $linha['numero_denuncia']
        ];

        foreach ($sufixos as $sufixo) {
            // sufixo vazio (ex.: sem complemento) vira só ", " e é ignorado
            if (trim($sufixo, " ,-") === "")
                continue;
            if (str_ends_with($rua, $sufixo))
                $rua = substr($rua, 0, -strlen($sufixo));
        }
        return $rua;
    }

    // ADICIONADO: imagem (BLOB) -> data URI base64, ou null quando a denúncia não tem foto
    private function imagemParaDataUri($blob): string|null
    {
        if (empty($blob))
            return null;
        $mime = (new finfo(FILEINFO_MIME_TYPE))->buffer($blob);
        if (!$mime || !str_starts_with($mime, "image/"))
            $mime = "image/jpeg";
        return "data:$mime;base64," . base64_encode($blob);
    }

    // ==================================================================
    // Busca as denúncias no banco (tb_denuncia) para o mural
    // ALTERADO: o local do card agora usa direto o logradouro_denuncia, que já é
    // a junção "Rua, número, complemento - bairro" gravada ao criar a denúncia.
    // ==================================================================
<<<<<<< Updated upstream
    public function listarDenuncias(int $limite = 100): array {
        $tabela = $GLOBALS['denuncia']; // tb_denuncia (definida em config.php)

        $comando = "SELECT id_denuncia, titulo_denuncia, data_denuncia, imagem_denuncia,
                           nome_categoria, logradouro_denuncia, status_denuncia
                    FROM $tabela
                    ORDER BY data_denuncia DESC
                    LIMIT :limite";
=======
    public function listarDenuncias(int $pagina = 1, array $filtros = []): array {
    $td = $GLOBALS['denuncia'];
    $tu = $GLOBALS['usuario'];
    $porPagina = 30;
    $pagina = max(1, $pagina);

    $where = [];
    $params = [];
>>>>>>> Stashed changes

    $busca = trim((string) ($filtros['busca'] ?? ''));

    if ($busca !== '') {
        $where[] = '(d.titulo_denuncia LIKE :busca1
            OR d.logradouro_denuncia LIKE :busca2
            OR d.nome_categoria LIKE :busca3
            OR (d.anonima_denuncia = 0
                AND CONCAT(u.nome_usuario, " ", u.sobrenome_usuario) LIKE :busca4))';

<<<<<<< Updated upstream
                // imagem (BLOB) -> data URI base64, pois o JSON não aceita binário
                $imagem = null;
                if (!empty($linha['imagem_denuncia'])) {
                    $mime = (new finfo(FILEINFO_MIME_TYPE))->buffer($linha['imagem_denuncia']);
                    if (!$mime || !str_starts_with($mime, "image/")) $mime = "image/jpeg";
                    $imagem = "data:$mime;base64," . base64_encode($linha['imagem_denuncia']);
                }

                $denuncias[] = [
                    "id"        => (int) $linha['id_denuncia'],
                    "titulo"    => $linha['titulo_denuncia'],
                    "categoria" => $linha['nome_categoria'],
                    "local"     => $linha['logradouro_denuncia'],
                    "data"      => $data,
                    "status"    => $linha['status_denuncia'],
                    "imagem"    => $imagem
                ];
            }

            return RespostaProcesso::resposta(
                mensagem: count($denuncias) . " denúncia(s) encontrada(s)",
                resposta: true,
                dados: $denuncias
            );
        } catch (Exception $erro) {
            return RespostaProcesso::resposta(
                mensagem: "Erro ao buscar denúncias: " . $erro->getMessage(),
                resposta: false,
                dados: RespostaProcesso::salvarErro($erro)
            );
        }
    }
}
=======
        for ($i = 1; $i <= 4; $i++) {
            $params[':busca' . $i] = '%' . $busca . '%';
        }
    }

    if (!empty($filtros['categoria'])) {
        $where[] = 'd.nome_categoria = :categoria';
        $params[':categoria'] = $filtros['categoria'];
    }

    if (!empty($filtros['bairro'])) {
        $where[] = 'd.bairro_denuncia = :bairro';
        $params[':bairro'] = $filtros['bairro'];
    }

    $status = [
        'pendente' => 'pendente',
        'analise' => 'em analise',
        'andamento' => 'em andamento',
        'concluida' => 'concluida'
    ];

    if (isset($status[$filtros['status'] ?? ''])) {
        $where[] = 'd.status_denuncia = :status';
        $params[':status'] = $status[$filtros['status']];
    }

    $origem = "FROM $td d
        INNER JOIN $tu u ON u.id_usuario = d.id_usuario";

    $condicao = $where
        ? ' WHERE ' . implode(' AND ', $where)
        : '';

    try {
        $conexao = (new Conexao())->getConexao();

        $executar = function (
            string $sql,
            array $valores = []
        ) use ($conexao): PDOStatement {

            $stmt = $conexao->prepare($sql);

            foreach ($valores as $chave => $valor) {
                $stmt->bindValue(
                    $chave,
                    $valor,
                    is_int($valor) ? PDO::PARAM_INT : PDO::PARAM_STR
                );
            }

            $stmt->execute();
            return $stmt;
        };

        // COUNT sem LIMIT: total dos resultados encontrados.
        $total = (int) $executar(
            "SELECT COUNT(*) $origem $condicao",
            $params
        )->fetchColumn();

        $totalPaginas = max(1, (int) ceil($total / $porPagina));
        $pagina = min($pagina, $totalPaginas);
        $offset = ($pagina - 1) * $porPagina;

        $linhas = $executar(
            "SELECT
                d.id_denuncia,
                d.titulo_denuncia,
                d.data_denuncia,
                d.imagem_denuncia,
                d.nome_categoria,
                d.bairro_denuncia,
                d.logradouro_denuncia,
                d.status_denuncia,
                d.anonima_denuncia,
                u.nome_usuario,
                u.sobrenome_usuario
             $origem
             $condicao
             ORDER BY d.data_denuncia DESC, d.id_denuncia DESC
             LIMIT :limite OFFSET :offset",
            array_merge($params, [
                ':limite' => $porPagina,
                ':offset' => $offset
            ])
        )->fetchAll(PDO::FETCH_ASSOC);

        $denuncias = [];

        foreach ($linhas as $linha) {
            $denuncias[] = [
                'id' => (int) $linha['id_denuncia'],
                'titulo' => $linha['titulo_denuncia'],
                'categoria' => $linha['nome_categoria'],
                'bairro' => $linha['bairro_denuncia'],
                'local' => $linha['logradouro_denuncia'],

                'data' => !empty($linha['data_denuncia'])
                    ? (new DateTime($linha['data_denuncia']))->format('d/m/Y')
                    : '',

                'status' => $linha['status_denuncia'],

                'imagem' => $this->imagemParaDataUri(
                    $linha['imagem_denuncia']
                ),

                'autor' => (int) $linha['anonima_denuncia'] === 1
                    ? 'Anônimo'
                    : $this->nomeParaExibir(
                        $linha['nome_usuario'],
                        $linha['sobrenome_usuario']
                    )
            ];
        }

        // Categorias e bairros de TODAS as denúncias.
        $categorias = $executar(
            "SELECT DISTINCT d.nome_categoria
             $origem ORDER BY d.nome_categoria"
        )->fetchAll(PDO::FETCH_COLUMN);

        $bairros = $executar(
            "SELECT DISTINCT d.bairro_denuncia
             $origem ORDER BY d.bairro_denuncia"
        )->fetchAll(PDO::FETCH_COLUMN);

        $retorno = RespostaProcesso::resposta(
            'Denúncias consultadas',
            true,
            $denuncias
        );

        $retorno['paginacao'] = [
            'pagina' => $pagina,
            'por_pagina' => $porPagina,
            'total' => $total,
            'total_paginas' => $totalPaginas
        ];

        $retorno['opcoes'] = [
            'categorias' => $categorias,
            'bairros' => $bairros
        ];

        return $retorno;

    } catch (Exception $erro) {
        return RespostaProcesso::resposta(
            'Erro ao buscar denúncias',
            false,
            RespostaProcesso::salvarErro($erro)
        );
    }
}

    // ==================================================================
    // ADICIONADO: apenas as denúncias feitas pelo usuário logado (pelo e-mail do token).
    // O INNER JOIN com tb_usuario + WHERE garante que ninguém vê denúncias de outra pessoa.
    // ==================================================================
    public function listarDenunciasDoUsuario(
    string $email,
    int $pagina = 1,
    array $filtros = []
): array {

    $td = $GLOBALS['denuncia'];
    $tu = $GLOBALS['usuario'];
    $porPagina = 10;
    $pagina = max(1, $pagina);

    $origem = "FROM $td d
        INNER JOIN $tu u ON u.id_usuario = d.id_usuario";

    $where = ['u.email_usuario = :email'];
    $params = [':email' => $email];

    $status = [
        'pendente' => 'pendente',
        'analise' => 'em analise',
        'andamento' => 'em andamento',
        'concluida' => 'concluida'
    ];

    if (isset($status[$filtros['status'] ?? ''])) {
        $where[] = 'd.status_denuncia = :status';
        $params[':status'] = $status[$filtros['status']];
    }

    $busca = trim((string) ($filtros['busca'] ?? ''));

    if ($busca !== '') {
        $where[] = '(d.titulo_denuncia LIKE :busca1
            OR d.descricao_denuncia LIKE :busca2
            OR d.logradouro_denuncia LIKE :busca3
            OR d.nome_categoria LIKE :busca4)';

        for ($i = 1; $i <= 4; $i++) {
            $params[':busca' . $i] = '%' . $busca . '%';
        }
    }

    $condicao = ' WHERE ' . implode(' AND ', $where);

    try {
        $conexao = (new Conexao())->getConexao();

        $executar = function (
            string $sql,
            array $valores = []
        ) use ($conexao): PDOStatement {

            $stmt = $conexao->prepare($sql);

            foreach ($valores as $chave => $valor) {
                $stmt->bindValue(
                    $chave,
                    $valor,
                    is_int($valor) ? PDO::PARAM_INT : PDO::PARAM_STR
                );
            }

            $stmt->execute();
            return $stmt;
        };

        // Contagem geral, sem paginação nem filtros de tela.
        $resumo = $executar(
            "SELECT
                d.status_denuncia,
                COUNT(*) quantidade
             $origem
             WHERE u.email_usuario = :email
             GROUP BY d.status_denuncia",
            [':email' => $email]
        )->fetchAll(PDO::FETCH_ASSOC);

        $contagens = [
            'concluida' => 0,
            'andamento' => 0,
            'analise' => 0,
            'pendente' => 0
        ];

        $tipos = array_flip($status);

        foreach ($resumo as $linha) {
            $chave = $tipos[$linha['status_denuncia']] ?? null;

            if ($chave !== null) {
                $contagens[$chave] = (int) $linha['quantidade'];
            }
        }

        $contagens['total'] = array_sum($contagens);

        // Quantidade que corresponde ao filtro selecionado.
        $total = (int) $executar(
            "SELECT COUNT(*) $origem $condicao",
            $params
        )->fetchColumn();

        $totalPaginas = max(1, (int) ceil($total / $porPagina));
        $pagina = min($pagina, $totalPaginas);
        $offset = ($pagina - 1) * $porPagina;

        $linhas = $executar(
            "SELECT
                d.id_denuncia,
                d.titulo_denuncia,
                d.descricao_denuncia,
                d.data_denuncia,
                d.nome_categoria,
                d.logradouro_denuncia,
                d.status_denuncia
             $origem
             $condicao
             ORDER BY d.data_denuncia DESC, d.id_denuncia DESC
             LIMIT :limite OFFSET :offset",
            array_merge($params, [
                ':limite' => $porPagina,
                ':offset' => $offset
            ])
        )->fetchAll(PDO::FETCH_ASSOC);

        $denuncias = [];

        foreach ($linhas as $linha) {
            $denuncias[] = [
                'id' => (int) $linha['id_denuncia'],
                'titulo' => $linha['titulo_denuncia'],
                'descricao' => $linha['descricao_denuncia'],
                'categoria' => $linha['nome_categoria'],
                'local' => $linha['logradouro_denuncia'],

                'data' => !empty($linha['data_denuncia'])
                    ? (new DateTime($linha['data_denuncia']))
                        ->format($GLOBALS['formato-data-hora'])
                    : '',

                'status' => $linha['status_denuncia'],

                'removivel' => $this->podeRemover(
                    $linha['status_denuncia']
                )
            ];
        }

        $retorno = RespostaProcesso::resposta(
            'Suas denúncias consultadas',
            true,
            $denuncias
        );

        $retorno['paginacao'] = [
            'pagina' => $pagina,
            'por_pagina' => $porPagina,
            'total' => $total,
            'total_paginas' => $totalPaginas
        ];

        $retorno['resumo'] = $contagens;

        return $retorno;

    } catch (Exception $erro) {
        return RespostaProcesso::resposta(
            'Erro ao buscar suas denúncias',
            false,
            RespostaProcesso::salvarErro($erro)
        );
    }
}

    // ==================================================================
    // ADICIONADO: dados completos de UMA denúncia para a página de detalhe (qualquer pessoa pode ver).
    // Não devolve e-mail nem dados privados do autor: só o nome para exibição.
    // ==================================================================
    public function obterDetalhe(int $id): array
    {
        $tabela_denuncia = $GLOBALS['denuncia'];
        $tabela_usuario = $GLOBALS['usuario'];

        try {
            $conexao = (new Conexao())->getConexao();
            $sql = $conexao->prepare(
                "SELECT d.*, u.nome_usuario, u.sobrenome_usuario
                 FROM $tabela_denuncia d
                 INNER JOIN $tabela_usuario u ON u.id_usuario = d.id_usuario
                 WHERE d.id_denuncia = :id"
            );
            $sql->bindValue(':id', $id, PDO::PARAM_INT);
            $sql->execute();
            $linha = $sql->fetch(PDO::FETCH_ASSOC);

            if (!$linha)
                return RespostaProcesso::resposta("Denúncia não encontrada");

            $cep = (string) $linha['cep_denuncia'];
            if (strlen($cep) === 8)
                $cep = substr($cep, 0, 5) . "-" . substr($cep, 5);

            return RespostaProcesso::resposta(
                mensagem: "Denúncia encontrada",
                resposta: true,
                dados: [
                    "id" => (int) $linha['id_denuncia'],
                    "titulo" => $linha['titulo_denuncia'],
                    "descricao" => $linha['descricao_denuncia'],
                    "categoria" => $linha['nome_categoria'],
                    "data" => (new DateTime($linha['data_denuncia']))->format("d/m/Y"),
                    "local" => $linha['logradouro_denuncia'],
                    "cidade" => $linha['cidade_denuncia'],
                    "estado" => $linha['estado_denuncia'],
                    "cep" => $cep,
                    "status" => $linha['status_denuncia'],
                    "imagem" => $this->imagemParaDataUri($linha['imagem_denuncia']),
                    "autor" => ((int) $linha['anonima_denuncia'] === 1)
                        ? 'Anônimo'
                        : $this->nomeParaExibir(
                            $linha['nome_usuario'],
                            $linha['sobrenome_usuario']
                        )
                ]
            );
        } catch (PDOException $erro) {
            return RespostaProcesso::resposta(
                mensagem: "Erro ao buscar a denúncia",
                resposta: false,
                dados: RespostaProcesso::salvarErro($erro)
            );
        }
    }

    // ==================================================================
    // ADICIONADO: comentários de uma denúncia (tabela tb_resposta), do mais novo para o mais antigo.
    // ==================================================================
    public function listarComentarios(int $id): array
    {
        $tabela_usuario = $GLOBALS['usuario'];
        $tabela_resposta = $GLOBALS['resposta'] ?? "tb_resposta";

        try {
            $conexao = (new Conexao())->getConexao();
            $sql = $conexao->prepare(
                "SELECT r.id_resposta, r.descricao_resposta, r.criada_em,
                        u.nome_usuario, u.sobrenome_usuario, u.perfil_usuario
                 FROM $tabela_resposta r
                 INNER JOIN $tabela_usuario u ON u.id_usuario = r.id_usuario
                 WHERE r.id_denuncia = :id
                 ORDER BY r.criada_em DESC, r.id_resposta DESC"
            );
            $sql->bindValue(':id', $id, PDO::PARAM_INT);
            $sql->execute();

            $comentarios = [];
            foreach ($sql->fetchAll(PDO::FETCH_ASSOC) as $linha) {
                $comentarios[] = [
                    "id" => (int) $linha['id_resposta'],
                    "texto" => $linha['descricao_resposta'],
                    "data" => (new DateTime($linha['criada_em']))->format("d/m/Y H:i"),
                    "autor" => $this->nomeParaExibir($linha['nome_usuario'], $linha['sobrenome_usuario']),
                    "perfil" => $linha['perfil_usuario']
                ];
            }

            return RespostaProcesso::resposta(
                mensagem: count($comentarios) . " comentário(s)",
                resposta: true,
                dados: $comentarios
            );
        } catch (PDOException $erro) {
            return RespostaProcesso::resposta(
                mensagem: "Erro ao buscar os comentários",
                resposta: false,
                dados: RespostaProcesso::salvarErro($erro)
            );
        }
    }

    // ==================================================================
    // ADICIONADO: grava um comentário. O autor vem do e-mail do token (nunca do formulário).
    // ==================================================================
    public function salvarComentario(int $id, string $email, string $texto): array
    {
        $tabela_denuncia = $GLOBALS['denuncia'];
        $tabela_usuario = $GLOBALS['usuario'];
        $tabela_resposta = $GLOBALS['resposta'] ?? "tb_resposta";

        try {
            $conexao = (new Conexao())->getConexao();

            $sql = $conexao->prepare("SELECT id_usuario FROM $tabela_usuario WHERE email_usuario = :email");
            $sql->bindValue(':email', $email);
            $sql->execute();
            $autor = $sql->fetch(PDO::FETCH_ASSOC);
            if (!$autor)
                return RespostaProcesso::resposta("Usuário não localizado");

            $sql = $conexao->prepare("SELECT id_denuncia FROM $tabela_denuncia WHERE id_denuncia = :id");
            $sql->bindValue(':id', $id, PDO::PARAM_INT);
            $sql->execute();
            if (!$sql->fetch(PDO::FETCH_ASSOC))
                return RespostaProcesso::resposta("Denúncia não encontrada");

            $sql = $conexao->prepare(
                "INSERT INTO $tabela_resposta (id_usuario, id_denuncia, descricao_resposta, criada_em)
                 VALUES (:id_usuario, :id_denuncia, :texto, :criada_em)"
            );
            $sql->bindValue(':id_usuario', (int) $autor['id_usuario'], PDO::PARAM_INT);
            $sql->bindValue(':id_denuncia', $id, PDO::PARAM_INT);
            $sql->bindValue(':texto', $texto);
            $sql->bindValue(':criada_em', date($GLOBALS['formato-data-hora']));
            $sql->execute();

            return RespostaProcesso::resposta(
                mensagem: "Comentário publicado",
                resposta: true,
                dados: ["id" => (int) $conexao->lastInsertId()]
            );
        } catch (PDOException $erro) {
            return RespostaProcesso::resposta(
                mensagem: "Erro ao publicar o comentário",
                resposta: false,
                dados: RespostaProcesso::salvarErro($erro)
            );
        }
    }



    // Nome público de quem denunciou: primeiro nome + inicial do sobrenome (ex.: "Maria S.").
    // Para mostrar o nome completo, troque o retorno por: $nome . " " . $sobrenome
    private function nomeParaExibir(string $nome, string $sobrenome): string
    {
        // o cadastro guarda os nomes com htmlspecialchars; aqui voltamos ao texto normal
        $nome = html_entity_decode(trim($nome), ENT_QUOTES, 'UTF-8');
        $sobrenome = html_entity_decode(trim($sobrenome), ENT_QUOTES, 'UTF-8');

        if ($sobrenome === "")
            return $nome;
        return $nome . " " . mb_strtoupper(mb_substr($sobrenome, 0, 1)) . ".";
    }
}
>>>>>>> Stashed changes

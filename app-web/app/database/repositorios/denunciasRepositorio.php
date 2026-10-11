<?php

class DenunciasRepositorio {

    // ==================================================================
    // ALTERADO: agora grava a denúncia na tabela tb_denuncia.
    // - id_usuario e perfil_usuario são buscados no banco pelo e-mail do usuário
    //   autenticado (nunca vêm do formulário).
    // - logradouro_denuncia recebe a junção dos campos de endereço (Endereco::getLogradouro()).
    // - status_denuncia não é enviado: o banco usa o padrão 'pendente'.
    // ==================================================================
    public function salvarDenuncia(Denuncia $denuncia): array {
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

            if (!$autor) return RespostaProcesso::resposta("Usuário não localizado");

            $endereco = $denuncia->getEndereco();
            $foto = $denuncia->getFoto();
            $complemento = $endereco->getComplemento();

            $comando = "
                INSERT INTO $tabela_denuncia (
                    id_usuario, titulo_denuncia, data_denuncia, descricao_denuncia,
                    imagem_denuncia, nome_categoria, cidade_denuncia, estado_denuncia,
                    logradouro_denuncia, numero_denuncia, bairro_denuncia, cep_denuncia,
                    perfil_usuario, complemento_denuncia
                ) VALUES (
                    :id_usuario, :titulo, :data, :descricao,
                    :imagem, :categoria, :cidade, :estado,
                    :logradouro, :numero, :bairro, :cep,
                    :perfil, :complemento
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

    public function apagarDenuncia(){}

    // ==================================================================
    // Busca as denúncias no banco (tb_denuncia) para o mural
    // ALTERADO: o local do card agora usa direto o logradouro_denuncia, que já é
    // a junção "Rua, número, complemento - bairro" gravada ao criar a denúncia.
    // ==================================================================
    public function listarDenuncias(int $limite = 100): array {
        $tabela = $GLOBALS['denuncia']; // tb_denuncia (definida em config.php)

        $comando = "SELECT id_denuncia, titulo_denuncia, data_denuncia, imagem_denuncia,
                           nome_categoria, logradouro_denuncia, status_denuncia
                    FROM $tabela
                    ORDER BY data_denuncia DESC
                    LIMIT :limite";

        try {
            $conexao = (new Conexao())->getConexao();
            $sql = $conexao->prepare($comando);
            $sql->bindValue(':limite', $limite, PDO::PARAM_INT);
            $sql->execute();
            $linhas = $sql->fetchAll(PDO::FETCH_ASSOC);

            $denuncias = [];
            foreach ($linhas as $linha) {
                // data no formato brasileiro
                $data = (new DateTime($linha['data_denuncia']))->format("d/m/Y");

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
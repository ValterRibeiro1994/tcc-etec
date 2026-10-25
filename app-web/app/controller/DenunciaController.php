<?php

// Rotas:
//   GET  ./denuncia/mural   ->  página do mural (home-mural.html)
//   GET  ./denuncia/listar  ->  JSON com TODAS as denúncias do mural (inclui o nome de quem denunciou)
//   GET  ./denuncia/minhas  ->  JSON só com as denúncias do usuário logado (token no cabeçalho Authorization)
//   GET  ./denuncia/criar   ->  página do formulário "Registrar uma denúncia"
//   POST ./denuncia/criar   ->  grava a denúncia (exige usuário logado)
class DenunciaController {

    // ADICIONADO: rota oficial da página do mural
    public function mural(array $requisicao){
        // para o mural o usuario de denuncias o usuario não precisa estar logado
        if ($requisicao['metodo'] === "GET") {
            return RespostaProcesso::resposta(
                mensagem: "Mural de denúncias", resposta: true,
                formato: "html", pagina: "app/view/paginas/home-mural.html"
            );
        }
    }

    public function listar(array $requisicao){
        if ($requisicao['metodo'] !== 'GET') {
            return RespostaProcesso::resposta('Método inválido');
        }
        $pagina = max(1, min(1000000, (int)($requisicao['pagina'] ?? 1)));
        $filtros = [
            'busca' => mb_substr(trim((string)($requisicao['busca'] ?? '')), 0, 150),
            'categoria' => $requisicao['categoria'] ?? '',
            'bairro' => $requisicao['bairro'] ?? '',
            'status' => $requisicao['status'] ?? ''
        ];
        return (new DenunciasRepositorio())->listarDenuncias($pagina, $filtros);
    }

    // ADICIONADO: denúncias do próprio usuário (usadas em "Minhas denúncias" e no perfil)
    public function minhas(array $requisicao){
        if ($requisicao['metodo'] !== 'GET') {
            return RespostaProcesso::resposta('Método inválido');
        }
        $resposta = UsuarioLogadoService::obter();
        if (!$resposta['resposta']) return $resposta;
        $pagina = max(1, min(1000000, (int)($requisicao['pagina'] ?? 1)));
        $filtros = [
            'status' => $requisicao['status'] ?? '',
            'busca' => mb_substr(trim((string)($requisicao['busca'] ?? '')), 0, 150)
        ];
        return (new DenunciasRepositorio())->listarDenunciasDoUsuario(
            $resposta['dados']['email'], $pagina, $filtros
        );
    }

    public function criar(array $requisicao){
        if ($requisicao['metodo'] === "GET") {
            return RespostaProcesso::resposta(
                mensagem: "Formulário de denúncia", resposta: true,
                formato: "html", pagina: "app/view/paginas/criar-denuncia.html"
            );
        }

        if ($requisicao['metodo'] === "POST") {
            // Throwable pega também erros fatais (ex.: método inexistente) e devolve JSON.
            // ATENÇÃO: em produção, remova o arquivo/linha da mensagem.
            try {
                return $this->salvar($requisicao);
            } catch (Throwable $erro) {
                return RespostaProcesso::resposta(
                    "Erro interno ao registrar a denúncia: " . $erro->getMessage()
                    . " (" . basename($erro->getFile()) . ":" . $erro->getLine() . ")"
                );
            }
        }

        return RespostaProcesso::resposta("Requisição inválida para criar denúncia");
    }


    public function obter(array $requisicao){
        if ($requisicao['metodo'] !== "GET") {
            return RespostaProcesso::resposta("Requisição inválida para obter a denúncia");
        }

        try {
            $resposta = UsuarioLogadoService::obter();
            if (!$resposta['resposta']) return $resposta;

            $id = $this->lerId($requisicao);
            if ($id === null) return RespostaProcesso::resposta("Denúncia inválida");

            $repositorio = new DenunciasRepositorio();
            return $repositorio->obterDenunciaDoUsuario($id, $resposta['dados']['email']);
        } catch (Throwable $erro) {
            return RespostaProcesso::resposta(
                "Erro interno ao buscar a denúncia: " . $erro->getMessage()
                . " (" . basename($erro->getFile()) . ":" . $erro->getLine() . ")"
            );
        }
    }

    // ==================================================================
    // ADICIONADO: GET devolve a página do formulário de edição; POST grava as alterações.
    // ==================================================================
    public function editar(array $requisicao){
        if ($requisicao['metodo'] === "GET") {
            // a página só é o "esqueleto": quem busca os dados (com o token) é o editar-denuncia.js
            return RespostaProcesso::resposta(
                mensagem: "Formulário de edição de denúncia", resposta: true,
                formato: "html", pagina: "app/view/paginas/editar-denuncia.html"
            );
        }

        if ($requisicao['metodo'] === "POST") {
            // ATENÇÃO: em produção, remova o arquivo/linha da mensagem.
            try {
                return $this->atualizar($requisicao);
            } catch (Throwable $erro) {
                return RespostaProcesso::resposta(
                    "Erro interno ao editar a denúncia: " . $erro->getMessage()
                    . " (" . basename($erro->getFile()) . ":" . $erro->getLine() . ")"
                );
            }
        }

        return RespostaProcesso::resposta("Requisição inválida para editar denúncia");
    }

    // ==================================================================
    // ADICIONADO: remove uma denúncia do usuário logado.
    // Espera um POST com "id-denuncia" (e o "metodo" que o roteador exige).
    // ==================================================================
    public function remover(array $requisicao){
        if ($requisicao['metodo'] !== "POST") {
            return RespostaProcesso::resposta("Requisição inválida para remover denúncia");
        }

        try {
            $resposta = UsuarioLogadoService::obter();
            if (!$resposta['resposta']) return $resposta;

            $id = $this->lerId($requisicao);
            if ($id === null) return RespostaProcesso::resposta("Denúncia inválida");

            $repositorio = new DenunciasRepositorio();
            return $repositorio->apagarDenuncia($id, $resposta['dados']['email']);
        } catch (Throwable $erro) {
            return RespostaProcesso::resposta(
                "Erro interno ao remover a denúncia: " . $erro->getMessage()
                . " (" . basename($erro->getFile()) . ":" . $erro->getLine() . ")"
            );
        }
    }

        // ==================================================================
    // ADICIONADO: GET ./denuncia/ver?id=N -> página com todas as informações e a aba de comentários
    // ==================================================================
    public function ver(array $requisicao){
        if ($requisicao['metodo'] === "GET") {
            return RespostaProcesso::resposta(
                mensagem: "Detalhe da denúncia", resposta: true,
                formato: "html", pagina: "app/view/paginas/ver-denuncia.html"
            );
        }
        return RespostaProcesso::resposta("Requisição inválida para ver a denúncia");
    }

    // ADICIONADO: GET ./denuncia/detalhe?id=N -> JSON de UMA denúncia (público, como o mural)
    public function detalhe(array $requisicao){
        if ($requisicao['metodo'] !== "GET") {
            return RespostaProcesso::resposta("Requisição inválida para obter o detalhe da denúncia");
        }

        try {
            $id = $this->lerId($requisicao);
            if ($id === null) return RespostaProcesso::resposta("Denúncia inválida");

            $repositorio = new DenunciasRepositorio();
            return $repositorio->obterDetalhe($id);
        } catch (Throwable $erro) {
            return RespostaProcesso::resposta(
                "Erro interno ao buscar a denúncia: " . $erro->getMessage()
                . " (" . basename($erro->getFile()) . ":" . $erro->getLine() . ")"
            );
        }
    }

    // ADICIONADO: GET ./denuncia/comentarios?id=N -> JSON com os comentários da denúncia (público)
    public function comentarios(array $requisicao){
        if ($requisicao['metodo'] !== "GET") {
            return RespostaProcesso::resposta("Requisição inválida para listar comentários");
        }

        try {
            $id = $this->lerId($requisicao);
            if ($id === null) return RespostaProcesso::resposta("Denúncia inválida");

            $repositorio = new DenunciasRepositorio();
            return $repositorio->listarComentarios($id);
        } catch (Throwable $erro) {
            return RespostaProcesso::resposta(
                "Erro interno ao buscar os comentários: " . $erro->getMessage()
                . " (" . basename($erro->getFile()) . ":" . $erro->getLine() . ")"
            );
        }
    }

    // ADICIONADO: POST ./denuncia/comentar -> publica um comentário (exige login).
    // Espera "id-denuncia" e "comentario" (e o "metodo" que o roteador exige).
    public function comentar(array $requisicao){
        if ($requisicao['metodo'] !== "POST") {
            return RespostaProcesso::resposta("Requisição inválida para comentar");
        }

        try {
            $resposta = UsuarioLogadoService::obter();
            if (!$resposta['resposta']) return $resposta;

            $id = $this->lerId($requisicao);
            if ($id === null) return RespostaProcesso::resposta("Denúncia inválida");

            $texto = trim((string) ($requisicao['comentario'] ?? ""));
            if ($texto === "") return RespostaProcesso::resposta("Escreva um comentário");
            if (mb_strlen($texto) > 1000) return RespostaProcesso::resposta("Comentário muito longo (máximo de 1000 caracteres)");

            $repositorio = new DenunciasRepositorio();
            return $repositorio->salvarComentario($id, $resposta['dados']['email'], $texto);
        } catch (Throwable $erro) {
            return RespostaProcesso::resposta(
                "Erro interno ao publicar o comentário: " . $erro->getMessage()
                . " (" . basename($erro->getFile()) . ":" . $erro->getLine() . ")"
            );
        }
    }


    


    private function atualizar(array $requisicao){
        $resposta = UsuarioLogadoService::obter();
        if (!$resposta['resposta']) return $resposta;

        $id = $this->lerId($requisicao);
        if ($id === null) return RespostaProcesso::resposta("Denúncia inválida");

        // ADICIONADO: só aceita os status que existem no banco
        $status = trim((string) ($requisicao['status-denuncia'] ?? ""));
        if (!in_array($status, DenunciasRepositorio::STATUS_VALIDOS, true)) {
            return RespostaProcesso::resposta("Status inválido");
        }

        try {
            $denuncia = $this->montarDenuncia($requisicao, $resposta['dados']);
        } catch (Exception $erro) {
            return RespostaProcesso::resposta($erro->getMessage());
        }

        $repositorio = new DenunciasRepositorio();
        return $repositorio->atualizarDenuncia($id, $resposta['dados']['email'], $denuncia, $status);
    }

    // ADICIONADO: o id vem como "id-denuncia" (POST) ou "id" (GET). Devolve null se não for um inteiro positivo.
    private function lerId(array $requisicao): int|null {
        $valor = $requisicao['id-denuncia'] ?? $requisicao['id'] ?? null;
        $id = filter_var($valor, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        return $id === false ? null : $id;
    }

    // ADICIONADO (trecho que antes ficava dentro de salvar()):
    // confere os campos obrigatórios, valida e monta os objetos da denúncia.
    // Lança Exception com uma mensagem clara quando algo estiver errado.
    private function montarDenuncia(array $requisicao, array $dados_token): Denuncia {
        $perfil = $dados_token['perfil'];


        // Campos obrigatórios (os nomes são os mesmos do formulário)
        $obrigatorios = [
            "titulo-denuncia", "descricao-denuncia", "categoria-denuncia", "data-denuncia",
            "estado-denuncia", "cidade-denuncia", "rua-denuncia", "numero-denuncia",
            "bairro-denuncia", "cep-denuncia"
        ];
        foreach ($obrigatorios as $campo) {
            if (!isset($requisicao[$campo]) || trim((string) $requisicao[$campo]) === "") {
                throw new Exception("Preencha o campo '$campo'");
            }
        }

        if (!preg_match('/^[A-Za-z]{2}$/', trim($requisicao['estado-denuncia']))) {
            throw new Exception("Informe o estado com a sigla de 2 letras (ex.: SP)");
        }

        // Valida e monta os objetos (cada classe lança Exception com mensagem clara)
        $usuario = new Usuario(new DadosPessoais(
            perfil: new Perfil($perfil),
            nome: new Nome($dados_token['nome']),
            sobrenome: new Sobrenome($dados_token['sobrenome']),
            email: new Email($dados_token['email'])
        ));

        $descricao = new Descricao(
            titulo: $requisicao['titulo-denuncia'],
            data: new Data($requisicao['data-denuncia']),
            categoria: $requisicao['categoria-denuncia'],
            texto: $requisicao['descricao-denuncia']
        );

        // O logradouro NÃO é recebido: Endereco o monta com rua, número, complemento e bairro
        $endereco = new Endereco(
            cidade: $requisicao['cidade-denuncia'],
            estado: $requisicao['estado-denuncia']
        );
        $endereco->setRua($requisicao['rua-denuncia']);
        $endereco->setNumero($requisicao['numero-denuncia']);
        $endereco->setComplemento($requisicao['complemento-denuncia'] ?? null);
        $endereco->setBairro($requisicao['bairro-denuncia']);
        $endereco->setCep($requisicao['cep-denuncia']);
        $endereco->getLogradouro(); // confere agora se a junção cabe em 150 caracteres

        // foto opcional (JPG/PNG até 5MB). Na edição, sem foto nova a foto antiga é mantida.
        $imagem = new Imagem($_FILES['foto-denuncia'] ?? null);

        return new Denuncia(
            usuario: $usuario,
            endereco: $endereco,
            descricao: $descricao,
            imagem: $imagem
        );
    }
    private function salvar(array $requisicao){
        // 1) Quem está enviando? O usuário vem do TOKEN validado pelo servidor,
        //    nunca de campos do formulário (que o cliente poderia alterar).
        //    ALTERADO: agora usa o UsuarioLogadoService (mesma regra das outras rotas com login).
        $resposta = UsuarioLogadoService::obter();
        if (!$resposta['resposta']) return $resposta;

        $dados_token = $resposta['dados'];
        $perfil = $dados_token['perfil'];

        // 2) Campos obrigatórios (os nomes são os mesmos do formulário)
        $obrigatorios = [
            "titulo-denuncia", "descricao-denuncia", "categoria-denuncia", "data-denuncia",
            "estado-denuncia", "cidade-denuncia", "rua-denuncia", "numero-denuncia",
            "bairro-denuncia", "cep-denuncia"
        ];
        foreach ($obrigatorios as $campo) {
            if (!isset($requisicao[$campo]) || trim((string) $requisicao[$campo]) === "") {
                return RespostaProcesso::resposta("Preencha o campo '$campo'");
            }
        }

        if (!preg_match('/^[A-Za-z]{2}$/', trim($requisicao['estado-denuncia']))) {
            return RespostaProcesso::resposta("Informe o estado com a sigla de 2 letras (ex.: SP)");
        }

        // 3) Valida e monta os objetos (cada classe lança Exception com mensagem clara)
        try {
            $usuario = new Usuario(new DadosPessoais(
                perfil: new Perfil($perfil),
                nome: new Nome($dados_token['nome']),
                sobrenome: new Sobrenome($dados_token['sobrenome']),
                email: new Email($dados_token['email'])
            ));

            $descricao = new Descricao(
                titulo: $requisicao['titulo-denuncia'],
                data: new Data($requisicao['data-denuncia']),
                categoria: $requisicao['categoria-denuncia'],
                texto: $requisicao['descricao-denuncia']
            );

            // O logradouro NÃO é recebido: Endereco o monta com rua, número, complemento e bairro
            $endereco = new Endereco(
                cidade: $requisicao['cidade-denuncia'],
                estado: $requisicao['estado-denuncia']
            );
            $endereco->setRua($requisicao['rua-denuncia']);
            $endereco->setNumero($requisicao['numero-denuncia']);
            $endereco->setComplemento($requisicao['complemento-denuncia'] ?? null);
            $endereco->setBairro($requisicao['bairro-denuncia']);
            $endereco->setCep($requisicao['cep-denuncia']);
            $endereco->getLogradouro(); // confere agora se a junção cabe em 150 caracteres

            // foto opcional (JPG/PNG até 5MB)
            $imagem = new Imagem($_FILES['foto-denuncia'] ?? null);

            $denuncia = new Denuncia(
                usuario: $usuario,
                endereco: $endereco,
                descricao: $descricao,
                imagem: $imagem
            );
        } catch (Exception $erro) {
            return RespostaProcesso::resposta($erro->getMessage());
        }

        // 4) Grava no banco
        $repositorio = new DenunciasRepositorio();
        return $repositorio->salvarDenuncia(
            $denuncia,
            (string) ($requisicao['anonima-denuncia'] ?? '0') === '1'
        );
    }
}

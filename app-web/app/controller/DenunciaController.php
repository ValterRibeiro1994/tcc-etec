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
        if ($requisicao['metodo'] === "POST"){
            $repositorio = new DenunciasRepositorio();
            $denuncias = $repositorio->listarDenuncias();
            return RespostaProcesso::resposta(
                mensagem: "Controller: Denuncias listadas com sucesso", resposta: true,
                dados: $denuncias, formato: "json"
            );
        }

        return RespostaProcesso::resposta(
                mensagem: "Controller: Requisição inválida para listar denúncias", resposta: false,
                dados: [], formato: "json"
        );
    }

    // ADICIONADO: denúncias do próprio usuário (usadas em "Minhas denúncias" e no perfil)
    public function minhas(array $requisicao){
        if ($requisicao['metodo'] !== "GET") {
            return RespostaProcesso::resposta("Requisição inválida para listar suas denúncias");
        }

        // quem está pedindo? vem do token validado pelo servidor
        $resposta = UsuarioLogadoService::obter();
        if (!$resposta['resposta']) return $resposta;

        $repositorio = new DenunciasRepositorio();
        return $repositorio->listarDenunciasDoUsuario($resposta['dados']['email']);
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
        return $repositorio->salvarDenuncia($denuncia);
    }
}

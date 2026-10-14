<?php

class CadastroController {
    public function index(array $requisicao){
        // if (SessaoController::estaConectado()) return RespostaProcesso::respostaProcesso("./app/view/paginas/index.html", true, formato: "html");
        // o index do cadastro não possui formulario na pagina.
        if ($requisicao['metodo'] == "GET") return RespostaProcesso::resposta(
            mensagem:"Get realizado com sucesso", resposta: true, 
            formato: "html", 
            pagina: "app/view/paginas/selecionar-cadastro.html");
        
        // envia json informando o erro para o front 
        throw new Exception("requisição invalida para cadastro");

    }

    public function municipe(array $requisicao){
       // if (SessaoController::estaConectado()) return RespostaProcesso::respostaProcesso("app/view/paginas/index.html", true, formato: "html");
        
        // o método get apenas exibe o formulario para o cadastro do denunciante
        if ($requisicao['metodo'] == "GET") return RespostaProcesso::resposta(
            mensagem:"Get realizado com sucesso para municipe", resposta:true,
            formato:"html",
            pagina: "app/view/paginas/cadastro-denunciante.html");
        
        // o método post deve receber os dados preenchidos do formulario
        if ($requisicao['metodo'] == "POST") return $this->cadastrarUsuario($requisicao);
        
        throw new Exception("Requisição invalida para Cadastro");
    }

    public function representante(array $requisicao){
       // if (SessaoController::estaConectado()) return RespostaProcesso::respostaProcesso("app/view/paginas/home-mural.html", true, formato: "html");
        if (!array_key_exists("metodo", $requisicao)) return RespostaProcesso::resposta("Requisição invalida");

        if ($requisicao['metodo'] == "GET") return RespostaProcesso::resposta(
        mensagem:"Get realizado com sucesso para representante", resposta:true,
        formato:"html",
        pagina:  "app/view/paginas/cadastro-representante.html");

        if ($requisicao['metodo'] == "POST") return $this->cadastrarUsuario($requisicao);
        return RespostaProcesso::resposta("Requisição indefinida");
    }

    public function denuncia(array $requisicao){
        // if (!SessaoController::estaConectado()) throw new Exception("Usuario desconectado");
        $metodo = $requisicao['metodo'];
        if ($metodo == "GET") return RespostaProcesso::resposta(mensagem:"ok", pagina: "app/view/paginas/criar-denuncia.html", formato: "html");
        // if ($metodo == "POST") return $this->cadastroDenuncia($requisicao);   
    }

    // private function cadastroDenuncia(array $requisicao){
    //     $dados_esperado = [
    //         "id-usuario", "nome-usuario", "titulo-denuncia",
    //         "data-denuncia", "descricao-denuncia", "imagem-denuncia",
    //         "categoria-denuncia", "cidade-denuncia", "estado-denuncia",
    //         "logradouro-denuncia", "numero-denuncia", "bairro-denuncia",
    //         "cep-denuncia", "status-denuncia", "perfil-usuario"
    //     ];

    //     $n = count($dados_esperado);
    //     for ($x = 0; $x < $n; $x++){
    //         $chave = $dados_esperado[$x];
    //         if (!array_key_exists($chave, $requisicao)) throw new Exception("Chave $chave não enviada");
    //         if (!empty($requisicao[$chave])) throw new Exception("Chave $chave vazia !!!");
    //     }

    //     $descricao = new Descricao(
    //         titulo: $requisicao['titulo-denuncia'],
    //         data: new Data($requisicao['data-denuncia']),
    //         categoria: $requisicao['categoria-denuncia'],
    //         texto: $requisicao['descricao-denuncia']
    //     );

    //     $endereco = new Endereco(
    //         cidade: $requisicao['cidade-denuncia'],
    //         estado: $requisicao['estado-denuncia']
    //     );

    //     // guarda o usuario que está online
    //     $resposta = SessaoController::obterUsuario();
    //     if (!$resposta['resposta']) {
    //         // fecha a sessão em caso de erro
    //         SessaoController::encerrarSessao();
    //         return $resposta;
    //     }

    //     // cria o usuario que está ativo nessa sessão
    //     $usuario_sessao = new Usuario();
    //     $usuario_logado = $usuario_sessao->converterArrayParaUsuario($resposta['dados']);

    //     // localiza o usuario no banco pelo id
    //     $repositorio = new UsuarioRepositorio();
    //     $resposta =  $repositorio->obterUsuario(id: $usuario_logado->getId());
    //     if (!$resposta['resposta']){
    //         SessaoController::encerrarSessao();
    //         return $resposta;
    //     }

    //     $usuario_banco = new Usuario();
    //     $resposta = $usuario_banco->converterArrayParaUsuario($resposta['dados']);
    //     if (is_array($resposta)){
    //         return $resposta;
    //     }
    //     $usuario_registrado = $resposta;

    //     // garanta que a sessão não foi alterada
    //     if ($usuario_logado->getNome() != $usuario_registrado->getNome()) throw new Exception("Acessonão autorizado");
    //     if ($usuario_logado->getEstado() != $usuario_registrado->getEstado()) throw new Exception("Acesso não autorizado");
    //     if ($usuario_logado->getPerfil() != $usuario_registrado->getPerfil()) throw new Exception("Acesso não autorizado");
    //     if ($usuario_logado->getPerfil() == "representante"){
    //         if ($usuario_logado->getCargo() != $usuario_registrado->getCargo()) throw new Exception("Acesso não autorizado");
    //     }

    //     // o usuario não alterou a sessão - salvamos o usuario completo do banco,
    //     $usuario_logado = $usuario_registrado;
    //     $usuario_logado->setSenha(limpar: true);


    //     $endereco->setLogradouro($requisicao['logradouro-denuncia']);
    //     $endereco->setBairro($requisicao['bairro-denuncia']);
    //     $endereco->setNumero($requisicao['numero-denuncia']);
    //     $endereco->setCep($requisicao['cep-denuncia']);
        
    //     $denuncia = new Denuncia(
    //         usuario: $usuario_logado,
    //         endereco: $endereco,
    //         descricao: $descricao,
    //         imagem: new Imagem($requisicao['imagem-denuncia'])
    //     );

    //     $repositorio = new DenunciasRepositorio();
    //     return $repositorio->salvarDenuncia($denuncia);

    // }

    private function cadastrarUsuario(array $dados){
        // verifica a existencia e o envio dos atributos necessarios para o cadastro
        $dados_esperado = [
            'nome', 'sobrenome', 'cpf', 'perfil', 
            'email', 'senha', 'confirmar-senha',
            'cidade', 'estado', 'orgao', 'cargo'
        ];

        foreach ($dados_esperado as $chave) {
            if (!array_key_exists($chave, $dados)) {
                return RespostaProcesso::resposta(
                    mensagem: "Controller: Campo $chave não enviado", resposta: false,
                    dados: $dados
                );
            }
        }

        try {

            // valida as senhas
            if ($dados['senha'] !== $dados['confirmar-senha']){
                return RespostaProcesso::resposta(
                    mensagem: "Controller: Senhas diferentes", resposta: false,
                    dados: $dados, formato: "json"
                );
            }

            $senha = new Senha($dados['senha']);
            $cpf = new Cpf($dados['cpf']);

            // valida os dados do usuario
            $usuario = new Usuario(
                dados_usuario: new DadosPessoais(
                    perfil: new Perfil($dados['perfil']),
                    nome: new Nome($dados['nome']),
                    sobrenome: new Sobrenome($dados['sobrenome']),
                    email: new Email($dados['email'])
                )
            );

            $usuario->setCpf($cpf);
            $usuario->setSenha($senha);
            $usuario->setEndereco(
                endereco: new Endereco(
                    cidade: $dados['cidade'],
                    estado: $dados['estado']
                )
            );

            if ($usuario->getPerfil() == "representante"){
                $usuario->setPrefeitura(
                    prefeitura: new Prefeitura(
                        orgao: $dados['orgao'],
                        cargo: $dados['cargo'])
                    );
            } else {
                $usuario->setPrefeitura(
                    orgao: "Não informado", cargo: "Não informado"
                );
            }

            // salva o usuario no banco
            $repositorio = new UsuarioRepositorio(new Conexao());
            $resposta = $repositorio->adicionarUsuario($usuario);
            if (!$resposta['resposta']) return $resposta;
            return RespostaProcesso::resposta(
                mensagem: "Controller: Cadastro realizado com sucesso", resposta: true,
                formato: "json"
            );


        } catch (Exception $erro) {
            return RespostaProcesso::resposta(
                mensagem: "Controller: Erro ao cadastrar usuario", resposta: false,
                dados: RespostaProcesso::salvarErro($erro), formato: "json"
            );
        }

    }
}
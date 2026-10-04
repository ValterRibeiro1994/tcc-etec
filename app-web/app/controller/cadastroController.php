<?php

class CadastroController {
    public function index(array $requisicao){
        if (SessaoController::estaConectado()) return RespostaProcesso::respostaProcesso("./app/view/paginas/index.html", true, formato: "html");
        // o index do cadastro não possui formulario na pagina.
        if ($requisicao['metodo'] == "GET") return RespostaProcesso::respostaProcesso("app/view/paginas/selecionar-cadastro.html", status: true, formato: "html");
        
        // envia json informando o erro para o front 
        throw new Exception("requisição invalida para cadastro");

    }

    public function municipe(array $requisicao){
        if (SessaoController::estaConectado()) return RespostaProcesso::respostaProcesso("app/view/paginas/index.html", true, formato: "html");
        
        // o método get apenas exibe o formulario para o cadastro do denunciante
        if ($requisicao['metodo'] == "GET") return RespostaProcesso::respostaProcesso("app/view/paginas/cadastro-denunciante.html", status: true, formato: "html");
        
        // o método post deve receber os dados preenchidos do formulario
        if ($requisicao['metodo'] == "POST") return $this->cadastrarDenunciante($requisicao);
        
        throw new Exception("Requisição invalida para Cadastro");
    }

    public function representante(array $requisicao){
        if (SessaoController::estaConectado()) return RespostaProcesso::respostaProcesso("app/view/paginas/home-mural.html", true, formato: "html");
        if (!array_key_exists("metodo", $requisicao)) return RespostaProcesso::respostaProcesso("Requisição invalida");
        if ($requisicao['metodo'] == "GET") return RespostaProcesso::respostaProcesso("app/view/paginas/cadastro-representante.html", true, formato: "html");
        if ($requisicao['metodo'] == "POST") return $this->cadastrarRepresentante($requisicao);
        return RespostaProcesso::respostaProcesso("Requisição indefinida");
    }

    public function denuncia(array $requisicao){
        if (!SessaoController::estaConectado()) throw new Exception("Usuario desconectado");
        $metodo = $requisicao['metodo'];
        if ($metodo == "GET") return RespostaProcesso::respostaProcesso("app/view/paginas/criar-denuncia.html", formato: "html");
        if ($metodo == "POST") return $this->cadastroDenuncia($requisicao);   
    }

    private function cadastroDenuncia(array $requisicao){
        $dados_esperado = [
            "id-usuario", "nome-usuario", "titulo-denuncia",
            "data-denuncia", "descricao-denuncia", "imagem-denuncia",
            "categoria-denuncia", "cidade-denuncia", "estado-denuncia",
            "logradouro-denuncia", "numero-denuncia", "bairro-denuncia",
            "cep-denuncia", "status-denuncia", "perfil-usuario"
        ];

        $n = count($dados_esperado);
        for ($x = 0; $x < $n; $x++){
            $chave = $dados_esperado[$x];
            if (!array_key_exists($chave, $requisicao)) throw new Exception("Chave $chave não enviada");
            if (!empty($requisicao[$chave])) throw new Exception("Chave $chave vazia !!!");
        }

        $descricao = new Descricao(
            titulo: $requisicao['titulo-denuncia'],
            data: new Data($requisicao['data-denuncia']),
            categoria: $requisicao['categoria-denuncia'],
            texto: $requisicao['descricao-denuncia']
        );

        $endereco = new Endereco(
            cidade: $requisicao['cidade-denuncia'],
            estado: $requisicao['estado-denuncia']
        );

        // guarda o usuario que está online
        $usuario_logado = SessaoController::obterUsuario();

        // localiza o usuario no banco pelo id
        $repositorio = new UsuarioRepositorio();
        $usuario_registrado =  $repositorio->obterUsuario(id: $usuario_logado->getId());

        // garanta que a sessão não foi alterada
        if ($usuario_logado->getNome() != $usuario_registrado->getNome()) throw new Exception("Acessonão autorizado");
        if ($usuario_logado->getEstado() != $usuario_registrado->getEstado()) throw new Exception("Acesso não autorizado");
        if ($usuario_logado->getPerfil() != $usuario_registrado->getPerfil()) throw new Exception("Acesso não autorizado");
        if ($usuario_logado->getPerfil() == "representante"){
            if ($usuario_logado->getCargo() != $usuario_registrado->getCargo()) throw new Exception("Acesso não autorizado");
        }

        // o usuario não alterou a sessão - salvamos o usuario completo do banco,
        $usuario_logado = $usuario_registrado;
        $usuario_logado->setSenha(limpar: true);


        $endereco->setLogradouro($requisicao['logradouro-denuncia']);
        $endereco->setBairro($requisicao['bairro-denuncia']);
        $endereco->setNumero($requisicao['numero-denuncia']);
        $endereco->setCep($requisicao['cep-denuncia']);
        
        $denuncia = new Denuncia(
            usuario: $usuario_logado,
            endereco: $endereco,
            descricao: $descricao,
            imagem: new Imagem($requisicao['imagem-denuncia'])
        );

        $repositorio = new DenunciasRepositorio();
        return $repositorio->salvarDenuncia($denuncia);

    }

    private function cadastrarDenunciante(array $dados){
        // verifica a existencia e o envio dos atributos necessarios para o cadastro
        $dados_esperado = [
            'nome', 'sobrenome', 'cpf', 
            'email', 'senha', 'confirmar-senha',
            'cidade', 'estado'
        ];
        
        $n = count($dados_esperado);
        for ($i = 0; $i < $n; $i++){
            $atributo = $dados_esperado[$i];
            if (!array_key_exists($atributo, $dados)) throw new Exception("Atributo '$atributo' não enviado");

            // Remove espaços em branco antes de verificar se está vazio
            $dados[$atributo] = trim($dados[$atributo]);
            if (empty($dados[$atributo])) throw new Exception("Atributo '$atributo' não pode estar vazio");
        }

        try {
            // Compara a igualdade da senha
            if ($dados['senha'] != $dados['confirmar-senha']) throw new Exception("Senhas não conferem");

            $usuario = new Usuario(
                dados_usuario: new DadosPessoais(
                    new Nome($dados['nome']),
                    new Sobrenome($dados['sobrenome']),
                    new Email($dados['email']),
                    new Cpf($dados['cpf'])
                ),
                endereco: new Endereco(
                    $dados['cidade'],
                    $dados['estado']
                ),
                senha: new Senha(
                    $dados['senha']
                )
            );

            // define o perfil do usuario
            $usuario->setPerfil("municipe");

            // envia o denunciante para o banco de dados
            $repositorio = new UsuarioRepositorio();
            return $repositorio->cadastrarUsuario($usuario);
        } catch (Exception $error) {
            throw $error;
        }
    }

    private function cadastrarRepresentante(array $dados){
        $dados_esperado = [
        'nome', 'sobrenome', 'cpf', 
        'email', 'senha', 'confirmar-senha',
        'cidade', 'estado', 'nome-prefeitura',
        'cargo-prefeitura'
        ];

        $n = count($dados_esperado);
        for ($x = 0; $x < $n; $x++) {
            $entrada = $dados_esperado[$x];
            if (!array_key_exists($entrada, $dados)) throw new Exception("Campo $entrada não enviado");
            if (empty(trim($dados[$entrada]))) throw new Exception("Campo $entrada vazio");
        }

        try {

            // Compara a igualdade da senha
            if ($dados['senha'] != $dados['confirmar-senha']) throw new Exception("Senhas não conferem");

            // cria o usuario
            $usuario = new Usuario(
                dados_usuario: new DadosPessoais(
                    nome: new Nome($dados['nome']),
                    sobrenome: new Sobrenome($dados['sobrenome']),
                    email: new Email($dados['email']),
                    cpf: new Cpf($dados['cpf'])
                ),
                
                endereco: new Endereco(
                    estado: $dados['estado'], 
                    cidade: $dados['cidade']
                    ),
                
                
                senha: new Senha(
                    senha: $dados['senha']
                    ),

               
                prefeitura: new Prefeitura(
                    orgao: $dados['nome-prefeitura'],
                    cargo: $dados['cargo-prefeitura']
                    ),

                );

            // setta o perfil do usuario
            $usuario->setPerfil("representante");

            // inicia o repositorio 
            $repositorio = new UsuarioRepositorio();
            
            // envia o resultado do processo
            return $repositorio->cadastrarUsuario($usuario);

        } catch (Exception $erro) {
            return RespostaProcesso::respostaProcesso($erro->getMessage(), dados: array($erro));
        }
    }

}
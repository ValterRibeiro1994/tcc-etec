<?php

class CadastroController {
    public function index(array $requisicao){
        if (!array_key_exists("metodo", $requisicao)) return RespostaProcesso::respostaProcesso("Requisição invalida");

        // o index do cadastro não possui formulario na pagina.
        if ($requisicao['metodo'] == "GET") return RespostaProcesso::respostaProcesso("app/view/paginas/selecionar-cadastro.html", status: true, formato: "text/html");
        
        // envia json informando o erro para o front 
        return RespostaProcesso::respostaProcesso("requisição invalida para o controle de cadastro");

    }

    public function municipe(array $requisicao){
        if (!array_key_exists("metodo", $requisicao)) return RespostaProcesso::respostaProcesso("Requisição invalida");

        // o método get apenas exibe o formulario para o cadastro do denunciante
        if ($requisicao['metodo'] == "GET") return RespostaProcesso::respostaProcesso("app/view/paginas/cadastro-denunciante.html", status: true, formato: "text/html");
        
        // o método post deve receber os dados preenchidos do formulario
        if ($requisicao['metodo'] == "POST") return $this->cadastrarDenunciante($requisicao);
        
        // para o processo de cadastro deve existir apenas POST e GET
        return RespostaProcesso::respostaProcesso("Requisiões indefinidas");
    }

    public function representante(array $requisicao){
        if (!array_key_exists("metodo", $requisicao)) return RespostaProcesso::respostaProcesso("Requisição invalida");
        if ($requisicao['metodo'] == "GET") return RespostaProcesso::respostaProcesso("app/view/paginas/cadastro-representante.html", true, formato: "text/html");
        if ($requisicao['metodo'] == "POST") return $this->cadastrarRepresentante($requisicao);
        return RespostaProcesso::respostaProcesso("Requisição indefinida");
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
            if (!array_key_exists($atributo, $dados)) return RespostaProcesso::respostaProcesso("Atributo '$atributo' não enviado");

            // Remove espaços em branco antes de verificar se está vazio
            $dados[$atributo] = trim($dados[$atributo]);
            if (empty($dados[$atributo])) return RespostaProcesso::respostaProcesso("Atributo '$atributo' não pode estar vazio");
        }

        try {
            // Compara a igualdade da senha
            if ($dados['senha'] != $dados['confirmar-senha']) return RespostaProcesso::respostaProcesso("Senhas não conferem", dados: $dados);

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
            $resposta = $repositorio->cadastrarUsuario($usuario);
            return $resposta;
            
        } catch (Exception $error) {
            $mensagem = "Erro: " . $error->getMessage();
            $erro = [
                'arquivo'=> $error->getFile(),
                "codigo"=> $error->getCode(),
                "linha"=> $error->getLine()
            ];
            return RespostaProcesso::respostaProcesso($mensagem, dados:$erro);
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
            if (!array_key_exists($entrada, $dados)) return RespostaProcesso::respostaProcesso("Campo $entrada não enviado", dados: $dados);
            if (empty($dados[$entrada])) return RespostaProcesso::respostaProcesso("Campo $entrada vazio", dados: $dados);
        }

        try {

            // Compara a igualdade da senha
            if ($dados['senha'] != $dados['confirmar-senha']) return RespostaProcesso::respostaProcesso("Senhas não conferem", dados: $dados);

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
            
            // salva o representante no banco
            $resposta =  $repositorio->cadastrarUsuario($usuario);
            
            // envia o resultado do processo
            return $resposta;

        } catch (Exception $erro) {
            return RespostaProcesso::respostaProcesso($erro->getMessage(), dados: array($erro));
        }
    }

}
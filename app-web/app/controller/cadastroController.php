<?php

class CadastroController {
    public function index(array $requisicao){
        if (!array_key_exists("metodo", $requisicao)){
            return RespostaProcesso::respostaProcesso("Requisição invalida");
        }

        // o index do cadastro não possui formulario na pagina.
        if ($requisicao['metodo'] == "GET"){
            return RespostaProcesso::respostaProcesso("app/view/paginas/selecionar-cadastro.html", status: true, formato: "text/html");
        }
        
        // envia json informando o erro para o front 
        return RespostaProcesso::respostaProcesso("requisição invalida para o controle de cadastro");

    }    
    public function municipe(array $requisicao){
        if (!array_key_exists("metodo", $requisicao)){
            return RespostaProcesso::respostaProcesso("Requisição invalida");
        }

        // o método get apenas exibe o formulario para o cadastro do denunciante
        if ($requisicao['metodo'] == "GET"){
            return RespostaProcesso::respostaProcesso("app/view/paginas/cadastro-denunciante.html", status: true, formato: "text/html");
        } 
        // o método post deve receber os dados preenchidos do formulario
        else if ($requisicao['metodo'] == "POST"){
            return $this->cadastrarDenunciante($requisicao);
        } 
        // para o processo de cadastro deve existir apenas POST e GET
        else {
            return RespostaProcesso::respostaProcesso("Requisiões indefinidas");
        }
    }

    public function representante(array $requisicao){
        return RespostaProcesso::respostaProcesso("Criar processo para cadastro de representante");
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
            if (!array_key_exists($atributo, $dados)){
                return RespostaProcesso::respostaProcesso("Atributo '$atributo' não enviado");
            }

            // Remove espaços em branco antes de verificar se está vazio
            $dados[$atributo] = trim($dados[$atributo]);
            
            if (empty($dados[$atributo])){
                return RespostaProcesso::respostaProcesso("Atributo '$atributo' não pode estar vazio");
            }
        }

        try {
            // valida os dados pessoais do usuario
            $nome = new Nome($dados['nome']);
            $sobrenome = new Sobrenome($dados['sobrenome']);
            $email = new Email($dados['email']);
            $cpf = new Cpf($dados['cpf']);

            // armazena os dados pessoais
            $dados_pessoais = new DadosPessoais($nome, $sobrenome, $email, $cpf);

            // armazena o endereço recebido
            $endereco = new Endereco();
            $endereco->setCidade($dados['cidade']);
            $endereco->setEstado($dados['estado']);

            // compara as senhas recebidas
            $senha = $dados['senha'];
            $confirmar_senha = $dados['confirmar-senha'];
            if ($senha !== $confirmar_senha){
                return RespostaProcesso::respostaProcesso("Senhas diferentes");
            }

            // valida a senha recebida
            $senha = new Senha($senha);
            
            // cria o denunciante
            $denunciante = new Municipe($dados_pessoais, $endereco, $senha);

            // envia o denunciante para o banco de dados
            $repositorio = new UsuarioRepositorio();
            $resposta = $repositorio->salvarMunicipe($denunciante);
            if (!$resposta['resposta']){
                return $resposta;
            }

            return RespostaProcesso::respostaProcesso("Cadastro do denunciante em processo - criar BD !!!", true, $dados);
            
        } catch (Exception $error) {
            $mensagem = "Erro: " . $error->getMessage();
            $arquivo = $error->getFile();
            $codigo = $error->getCode();
            $linha = $error->getLine();
            $erro = [
                'arquivo'=>$arquivo,
                "codigo"=>$codigo,
                "linha"=>$linha
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
        for ($x = 0; $x < $n;) {
            $entrada = $dados_esperado[$x];
            if (!array_key_exists($entrada, $dados)){
                return RespostaProcesso::respostaProcesso("Campo $entrada não enviado", dados: $dados);
            }

            if (empty($dados[$entrada])){
                return RespostaProcesso::respostaProcesso("Campo $entrada vazio", dados: $dados);
            }
        }

        try {
            // valida os dados pessoais recebidos
            $nome = new Nome($dados['nome']);
            $sobrenome = new Sobrenome($dados['sobrenome']);
            $cpf = new Cpf($dados['cpf']);
            $email = new Email($dados['email']);

            // valida a senha recebida
            if ($dados['senha'] != $dados['confirmar-senha']){
                return RespostaProcesso::respostaProcesso("Senhas não conferem", dados: $dados);
            }
            $senha = new Senha($dados['senha']);

            // valida o endereço recebido
            $endereco = new Endereco();
            $endereco->setEstado($dados['estado']);
            $endereco->setCidade($dados['cidade']);

            // criar object-values para validação do orgão e do cargo do representante
            // criar processo para armazenar o representante no banco


        } catch (Exception $erro) {
            return RespostaProcesso::respostaProcesso($erro->getMessage(), dados: array($erro));
        }
    }

}
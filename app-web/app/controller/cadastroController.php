<?php

class CadastroController {
    public function municipe(array $requisicao){
        if (!array_key_exists("metodo", $requisicao)){
            return RespostaProcesso::respostaProcesso("Requisição invalida");
        }

        if ($requisicao['metodo'] == "GET"){
            return RespostaProcesso::respostaProcesso("app/view/paginas/cadastro-denunciante.html", status: true, formato: "text/html");
        } else if ($requisicao['metodo'] == "POST"){
            return $this->cadastrarDenunciante($requisicao);
        } else {
            return RespostaProcesso::respostaProcesso("Requisiões indefinidas");
        }
    }

    public function representante(array $requisicao){
        return RespostaProcesso::respostaProcesso("Criar processo para cadastro de representante");
    }

    private function cadastrarDenunciante(array $dados){
        // verifica a existencia e o envio dos atributos necessarios para o cadastro
        $dados_esperado = ['nome', 'sobrenome', 'cpf', 'email', 'senha', 'confirmar-senha'];
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

            // compara as senhas recebidas
            $senha = $dados['senha'];
            $confirmar_senha = $dados['confirmar-senha'];
            if ($senha !== $confirmar_senha){
                return RespostaProcesso::respostaProcesso("Senhas diferentes");
            }

            // valida a senha recebida
            $senha = new Senha($senha);

            // cria o denunciante
            $denunciante = new Denunciante($dados_pessoais, $senha);
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
}
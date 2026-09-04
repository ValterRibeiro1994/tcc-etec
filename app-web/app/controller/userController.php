<?php


class UserController {
    public function index(array $requisicao){
        if (!array_key_exists("metodo", $requisicao)){
            return RespostaProcesso::respostaProcesso("Método desconhecido", dados: $requisicao);
        }

        // apenas exibe a pagina de cadastro
        if ($requisicao['metodo'] == "GET" && ($requisicao['dados']['user'] == "denunciante")){
            $pagina = "./app/view/paginas/cadastro.html";
            $formato = "html";
            $dados = [
                "pagina"=>$pagina,
                "formato"=>$formato,
            ];
            return RespostaProcesso::respostaProcesso("sucesso", true, $dados);
            
        } else if ($requisicao['metodo'] == "GET" && ($requisicao['dados']['user']) == "prefeitura"){
            $pagina = "./app/view/paginas/cadastroRepresentante.html";
            $formato = "html";
            $dados = [
                "pagina"=> $pagina, 
                "formato"=>$formato
            ];
            return RespostaProcesso::respostaProcesso("Sucesso", true, $dados);

        } else if ($requisicao['metodo'] == "POST"){
            // cadastra o usuario no banco

            // 1) - verifica os dados necessarios para o cadastro
            $dados_necessarios = ["nome", "sobrenome", "email", "cpf", "senha", "confirmar-senha"];
            
            // 2) - captura os dados recebidos na requisição
            $dados_requisicao = $requisicao['dados'];
            
            // 3) verifica se as chaves estão vazias e que existem
            $n = count($dados_necessarios);
            for ($x = 0; $x < $n; $x++){
                // CAPTURA A CHAVE ATUAL
                $chave_analisada = $dados_necessarios[$x];

                // VERIFICA SE ELA EXISTE NA REQUISIÇÃO
                if (!array_key_exists($chave_analisada, $dados_requisicao)){
                    return RespostaProcesso::respostaProcesso("Chave '$chave_analisada' não existe");
                }

                // verifica se a chave está vazia
                if (empty($dados_requisicao[$chave_analisada])){
                    return RespostaProcesso::respostaProcesso("Chave '$chave_analisada' vazia");
                }

            }

            // 4) captura os dados
            $nome = new Nome($dados_requisicao['nome']);
            $sobrenome = new Sobrenome($dados_requisicao['sobrenome']);
            $email = new Email($dados_requisicao['email']);
            $cpf = new Cpf($dados_requisicao['cpf']);
            $senha = $dados_requisicao['senha'];
            $confirmar_senha = $dados_requisicao['confirmar-senha'];
        }


    }
}
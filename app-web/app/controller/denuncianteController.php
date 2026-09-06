<?php


class DenuncianteController {
    public function index(array $requisicao){
        // garanta que o método tenha sido definido
        if (!array_key_exists("metodo", $requisicao)){
            return RespostaProcesso::respostaProcesso("Método não enviado", dados: $requisicao);
        }

        if ($requisicao['metodo'] == "GET"){
            // A página principal do denunciante deve ser chamada
            $dados = [
                "pagina" => "/app/view/paginas/denunciante.html",
                "tipo-resposta"=>"text/html"
            ];
            return RespostaProcesso::respostaProcesso("Criar página principal para o denunciante", true, $dados);
        }

        return RespostaProcesso::respostaProcesso("POST não esperado para essa função ", dados: $requisicao);
    }

    public function cadastrar(array $requisicao){
        if (!array_key_exists("metodo", $requisicao)){
            return RespostaProcesso::respostaProcesso("Método não enviado", dados: $requisicao);
        }

        if ($requisicao['metodo'] == "GET"){
            // a pagina para cadastrar o denunciante deve ser exibida
            $dados = [
                "pagina"=>"app/view/paginas/cadastro-denunciante.html",
                "tipo-resposta"=> "text/html"
            ];

            return RespostaProcesso::respostaProcesso("Chamar cadastro denunciante...", true, $dados);
        } else {
            $dados = [
                'tipo-resposta'=>"application/json",
                'resposta-processo' => $this->cadastrarDenunciante($requisicao)
            ];
            return RespostaProcesso::respostaProcesso("Em andamento", true, $dados);
        }

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

            // $dados_pessoais = new DadosPessoais($nome, $sobrenome, $email, $cpf);
            return RespostaProcesso::respostaProcesso("Cadastro do denunciante em processo !!!", true, $dados);
            
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
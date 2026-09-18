<?php

class LoginController {
    public function index(array $requisicao){
        if (!array_key_exists("metodo", $requisicao)){
            return RespostaProcesso::respostaProcesso("Método não enviado para o controller !!!");
        }

        if ($requisicao['metodo'] == "GET"){
            return RespostaProcesso::respostaProcesso("app/view/paginas/login.html", true, formato: "text/html");
        } else if ($requisicao['metodo'] == "POST"){
            return $this->logar($requisicao);
        }
    }

    private function logar(array $requisicao){
        $campos = ['email', 'senha'];
        for ($x = 0; $x < 2; $x++){
            $entrada = $campos[$x];
            if (!array_key_exists($entrada, $requisicao)){
                return RespostaProcesso::respostaProcesso("Campo '$entrada' não enviado");
            }
        }

        try {
            $email = new Email($requisicao['email']);
            $senha = new Senha($requisicao['senha']);

            //salvar no banco
            return RespostaProcesso::respostaProcesso("Criar processo para validar o acesso no banco", true);
        } catch (Exception $erro){
            $mensagem = "ERRO PROCESSO LOGIN: " . $erro->getMessage();
            return RespostaProcesso::respostaProcesso($mensagem);
        }
    }

    private function status(array $requisicao){
        if ($requisicao['metodo'] == "GET"){
            if (SessaoController::estaConectado()){
                return RespostaProcesso::respostaProcesso("Sessão ativa", true, ['token'=>$_SESSION['user']['token']]);
            } else {
                return RespostaProcesso::respostaProcesso("Sessão fechada", false);
            }
        }
    }
}
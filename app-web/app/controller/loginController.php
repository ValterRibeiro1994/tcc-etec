<?php

class LoginController {
    public function index(array $requisicao){
        
        if ($requisicao['metodo'] == "GET") {
            if ($this->logado()) return RespostaProcesso::resposta(
                mensagem: "Login usuario conectado",
                pagina: "app/view/paginas/index.html", resposta: true, 
                formato: "html");

            return RespostaProcesso::resposta(
                mensagem: "Usuario Precisa logar", formato: "html",
                pagina: "app/view/paginas/login.html", resposta: true
            );
        }
        if ($requisicao['metodo'] == "POST") {
            if ($this->logado()) return RespostaProcesso::respostaProcesso("Usuario já está logado");
            return $this->logar($requisicao);
        }

        throw new Exception("Requisição não permitida");
    }

    private function logar(array $requisicao){
        $campos = ['email', 'senha', 'lembrar'];
        for ($x = 0; $x < 2; $x++){
            $entrada = $campos[$x];
            if (!array_key_exists($entrada, $requisicao)) return RespostaProcesso::resposta("Campo '$entrada' não enviado", dados:$requisicao);
        }

        // valida os dados recebidos pelo front-end
        $email = new Email($requisicao['email']);
        $senha = new Senha($requisicao['senha']);

        // iniciar repositorio
        $repositorio = new UsuarioRepositorio();

        
        
    }

    private function logado(){
        $resposta =  SessaoController::estaConectado();
        return $resposta['resposta'];
    }
}
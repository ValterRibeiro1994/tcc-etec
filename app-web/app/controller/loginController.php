<?php

class LoginController {
    public function index(array $requisicao){
        
        if ($requisicao['metodo'] == "GET") {
            if ($this->logado()) return RespostaProcesso::respostaProcesso("app/view/paginas/home-mural.html", true, formato: "html");
            return RespostaProcesso::respostaProcesso("app/view/paginas/login.html", true, formato: "html");
        }
        if ($requisicao['metodo'] == "POST") {
            if ($this->logado()) return RespostaProcesso::respostaProcesso("Usuario já está logado");
            return $this->logar($requisicao);
        }
    }

    private function logar(array $requisicao){
        $campos = ['email', 'senha', 'lembrar'];
        for ($x = 0; $x < 2; $x++){
            $entrada = $campos[$x];
            if (!array_key_exists($entrada, $requisicao)) throw new Exception("Campo '$entrada' não enviado");
        }

        // valida os dados recebidos pelo front-end
        $email = new Email($requisicao['email']);
        $senha = new Senha($requisicao['senha']);

        // iniciar repositorio
        $repositorio = new UsuarioRepositorio();
        
        // resgatar a senha armazenada no banco
        $resposta = $repositorio->obterSenha($email);
        if (!$resposta['resposta']) throw new Exception($resposta['mensagem']);

        $senha_banco = $resposta['mensagem']; // senha armazenada com hash

        // comparar a senha recebida pela senha do banco
        if (!password_verify($requisicao['senha'], $senha_banco)) throw new Exception("Acesso Negado");
        
        $usuario = $repositorio->obterUsuario($email);

        SessaoController::salvarUsuario($usuario, $requisicao['lembrar']);
        return RespostaProcesso::respostaProcesso("Acesso autorizado", true);
    }

    private function logado(){
        return SessaoController::estaConectado();
    }
}
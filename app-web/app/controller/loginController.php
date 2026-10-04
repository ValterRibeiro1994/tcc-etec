<?php

class LoginController {
    public function index(array $requisicao){
        
        if ($requisicao['metodo'] == "GET") {
            if ($this->logado()) return RespostaProcesso::respostaProcesso("app/view/paginas/index.html", true, formato: "html");
            return RespostaProcesso::respostaProcesso("app/view/paginas/login.html", true, formato: "html");
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
            if (!array_key_exists($entrada, $requisicao)) throw new Exception("Campo '$entrada' não enviado");
        }

        // valida os dados recebidos pelo front-end
        $email = new Email($requisicao['email']);
        $senha = new Senha($requisicao['senha']);

        // iniciar repositorio
        $repositorio = new UsuarioRepositorio();
        
        // resgatar a senha armazenada no banco
        $hash_banco = $repositorio->obterSenha($email);
        
        // comparar a senha recebida pela senha do banco
        if (!password_verify($requisicao['senha'], $hash_banco)) throw new Exception("Acesso Negado");
        
        $usuario = $repositorio->obterUsuario($email);
        // enviar token para cliente
        $token = new TokenController();
        $dados = [
            "token" => ""
        ];

        // checa o tempo de expiração
        if ($requisicao['lembrar'] == false){
            $dados['token'] = $token->gerarToken($usuario, (10 * 60)); // parametro deve ser passado em numero de segundos
        } else {
            $dados['token'] = $token->gerarToken($usuario, 86400); // dia em segundos
        }
        
        SessaoController::salvarUsuario($usuario, $requisicao['lembrar']);        
        return RespostaProcesso::respostaProcesso("Acesso autorizado", true, dados: $dados);
    }

    private function logado(){
        // captura o cabeçalho de autenticação
        return SessaoController::estaConectado();
    }
}
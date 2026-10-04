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
            if (!array_key_exists($entrada, $requisicao)) return RespostaProcesso::resposta("Campo '$entrada' não enviado", dados:$requisicao);
        }

        // valida os dados recebidos pelo front-end
        $email = new Email($requisicao['email']);
        $senha = new Senha($requisicao['senha']);

        // iniciar repositorio
        $repositorio = new UsuarioRepositorio();
        
        // resgatar a senha armazenada no banco
        $resposta = $repositorio->obterSenha($email);
        if (!$resposta['resposta']){
            return $resposta;
        }

        // comparar a senha recebida pela senha do banco
        $hash_banco = $resposta['mensagem'];
        if (!password_verify($requisicao['senha'], $hash_banco)) return RespostaProcesso::resposta("Acesso Negado");
        
        
        $resposta = $repositorio->obterUsuario($email);
        if (!$resposta['resposta']){
            return $resposta;
        }

        try {
            $usuario = $resposta['dados'][0];
            // limpar as senhas por garantia
            $usuario->setSenha(limpar: true);

            // verificar igualdade nos emails
            if ($usuario->getEmail() != $requisicao['email']){
                return RespostaProcesso::resposta("Erro usuário: Email invalido", false, array($usuario));
            }

            $token = new TokenController();
            $dados = [
                "token" => "",
                "perfil" => $usuario->getPerfil(),
                "nome" => $usuario->getNome(),
                "sobrenome" => $usuario->getSobrenome(),
                "email" => $usuario->getEmail()
            ];
    
            // checa o tempo de expiração
            if ($requisicao['lembrar'] == false){
                $tempo = 600;               
            } else {
                $tempo = 86400;
            }

            // gera o token
            $resposta = $token->gerarToken($usuario, $tempo);
            if (!$resposta['resposta']){
                return $resposta;
            }

            // guarda o token 
            $token = $resposta['mensagem'];
            $dados["token"] = $token;

            // envie o resultado
            return RespostaProcesso::resposta("Acesso liberado", true, array($dados));
            
        } catch (Exception $erro) {
            SessaoController::encerrarSessao();
            $dados = RespostaProcesso::salvarErro($erro);
            return RespostaProcesso::resposta("Acesso não autorizado", false, dados: $dados);
        }
    }

    private function logado(){
        $resposta =  SessaoController::estaConectado();
        return $resposta['resposta'];
    }
}
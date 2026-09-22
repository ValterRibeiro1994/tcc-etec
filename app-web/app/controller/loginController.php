<?php

class LoginController {
    public function index(array $requisicao){
        if (!array_key_exists("metodo", $requisicao)) return RespostaProcesso::respostaProcesso("Método não enviado para o controller !!!");
        if ($requisicao['metodo'] == "GET") return RespostaProcesso::respostaProcesso("app/view/paginas/login.html", true, formato: "text/html");
        if ($requisicao['metodo'] == "POST") return $this->logar($requisicao);
    }

    private function logar(array $requisicao){
        $campos = ['email', 'senha'];
        for ($x = 0; $x < 2; $x++){
            $entrada = $campos[$x];
            if (!array_key_exists($entrada, $requisicao)) return RespostaProcesso::respostaProcesso("Campo '$entrada' não enviado");
        }

        try {
            $email = new Email($requisicao['email']);
            $senha = new Senha($requisicao['senha']);

            //Captura a senha no banco
            $conexao = new Conexao();
            $conexao = $conexao->getConexao();
            $comando = "SELECT perfil_usuario, senha_usuario FROM tb_usuario WHERE = :email";
            $sql = $conexao->prepare($comando);
            $sql->bindValue(":email", $email->getEmail());
            $sql->execute();
            $resposta = $sql->fetch(PDO::FETCH_ASSOC);
            if (!$resposta) throw new Exception("ERRO LOGIN: Email não encontrado");
            
            // fecha a conexão
            $sql = null;
            $conexao = null;

            // pega o hash do banco
            $senha_banco = $resposta['senha_usuario'];

            // compara as senha do usuario e a senha armazenada no banco
            if (!password_verify($requisicao['senha'], $senha_banco)) throw new Exception("ERRO LOGIN: Senha invalida");

            // pega o perfil do usuario
            $perfil_usuario = $resposta['perfil_usuario'];
            
            // armazena a sessão do usuário
            return RespostaProcesso::respostaProcesso("Criar processo para armazenar a sessão do usuario", true);
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
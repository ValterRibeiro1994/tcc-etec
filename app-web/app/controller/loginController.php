<?php

class LoginController {
    public function index(array $requisicao){
        if (!array_key_exists("metodo", $requisicao)) return RespostaProcesso::respostaProcesso("Método não enviado para o controller !!!");
        if ($requisicao['metodo'] == "GET") return RespostaProcesso::respostaProcesso("app/view/paginas/login.html", true, formato: "text/html");
        if ($requisicao['metodo'] == "POST") return $this->logar($requisicao);
    }

    private function logar(array $requisicao){
        $campos = ['email', 'senha', 'lembrar'];
        for ($x = 0; $x < 2; $x++){
            $entrada = $campos[$x];
            if (!array_key_exists($entrada, $requisicao)) return RespostaProcesso::respostaProcesso("Campo '$entrada' não enviado");
        }

        try {

            // valida os dados recebidos pelo front-end
            $email = new Email($requisicao['email']);
            $senha = new Senha($requisicao['senha']);

            //Localizar o usuario dentro do banco
            $conexao = new Conexao(); // inicia o objeto conexão
            $conexao = $conexao->getConexao(); // inicia a conexão
            $comando = "
                        SELECT 
                            id_usuario AS id, 
                            perfil_usuario AS tipo_usuario, 
                            senha_usuario AS senha,
                            email_usuario AS email,
                            nome_usuario AS nome,
                            cpf_usuario AS cpf,
                            sobrenome_usuario AS sobrenome
                        FROM 
                            tb_usuario 
                        WHERE 
                            email_usuario = :email
                        ";

            // prepara o comando sql 
            $sql = $conexao->prepare($comando);
            $sql->bindValue(":email", $email->getEmail());
            $sql->execute();
            $resposta = $sql->fetch(PDO::FETCH_ASSOC);
            if (!$resposta) throw new Exception("ERRO LOGIN: Email não encontrado");
            
            // fecha a conexão enquanto aguarda a proxima parte do processo
            $sql = null;
            $conexao = null;
            
            // pega o hash do banco
            $senha_banco = $resposta['senha'];
            
            // compara as senha do usuario e a senha armazenada no banco
            if (!password_verify($requisicao['senha'], $senha_banco)) throw new Exception("ERRO LOGIN: Senha invalida");

            // a senha já foi conferida e pode ser apagada da memoria para evitar incidentes
            // $senha = null; // senha do objeto manter para criar o usuario
            $senha_banco = null;
            $resposta['senha'] = null;
            $requisicao['senha'] = null;


            // pega o id do usuario
            $id_usuario = $resposta['id'];

            // pega o perfil do usuario
            $perfil_usuario = $resposta['tipo_usuario'];

            // captura os dados complementares do usuario
            if ($perfil_usuario === "municipe") {
                $comando = "
                            SELECT 
                                cidade_municipe AS cidade,
                                estado_municipe AS estado
                            FROM
                                tb_municipe
                            WHERE 
                                id_usuario = :id 
                ";
            } else if ($perfil_usuario === "representante"){
                $comando = "
                            SELECT
                                cidade_representante AS cidade,
                                estado_representante AS estado,
                                orgao_representante AS orgao,
                                cargo_representante AS cargo
                            FROM
                                tb_representante
                            WHERE
                                id_usuario = :id
                ";
            } else {
                throw new Exception("PERFIL NÂO CADASTRADO");
            }


            // pega os dados pessoais do usuario
            $nome_usuario =  $resposta['nome'];
            $sobrenome_usuario = $resposta['sobrenome'];
            $email_usuario = $resposta['email'];
            $cpf_usuario = $resposta['cpf'];
           
            $dados_pessoais = new DadosPessoais(
                new Nome($nome_usuario),
                new Sobrenome($sobrenome_usuario),
                new Email($email_usuario),
                new Cpf($cpf_usuario)
            );

            $conexao = new Conexao();
            $conexao = $conexao->getConexao();

            $sql = $conexao->prepare($comando);
            $sql->bindValue(":id", $id_usuario);
            $sql->execute();

            // captura os dados da resposta
            $resposta = $sql->fetch(PDO::FETCH_ASSOC);

            // verifica se não está vazio
            if ($sql->rowCount() == 0 || !$resposta) throw new Exception("Usuario não cadastrado no sistema");

            // encerrando conexão
            $sql = null;
            $conexao = null;

            // captura o endereço registrado
            $cidade = $resposta['cidade'];
            $estado = $resposta['estado'];

            $endereco_usuario = new Endereco();
            $endereco_usuario->setCidade($cidade);
            $endereco_usuario->setEstado($estado);

            // limpa a senha antes de armazenar na sessão
            $senha->limparSenha();

            if ($perfil_usuario == "representante"){
                $orgao = $resposta['orgao'];
                $cargo = $resposta['cargo'];

                $usuario = new Representante(
                    $dados_pessoais,
                    $endereco_usuario,
                    $senha,
                    new Prefeitura($orgao, $cargo)
                );
            } else {
                $usuario = new Municipe(
                    $dados_pessoais,
                    $endereco_usuario,
                    $senha
                );
            }
            

            
            // armazena a sessão do usuário
            return RespostaProcesso::respostaProcesso("Criar processo para armazenar a sessão do usuario", true);
        } catch (Exception $erro){
            $mensagem = "ERRO PROCESSO LOGIN: " . $erro->getMessage() . "\nLINHA: " . $erro->getLine()  . "\nCODIGO: " . $erro->getCode() . "\nArquivo: " . $erro->getFile();
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
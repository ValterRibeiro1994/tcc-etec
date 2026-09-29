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

            // iniciar repositorio
            $repositorio = new UsuarioRepositorio();

            // verificar se o email existe
            $resposta = $repositorio->emailExiste($requisicao['email']);
            if ($resposta['resposta']) return $resposta;

            // resgatar a senha armazenada no banco
            $resposta = $repositorio->obterSenha($email);
            if (!$resposta['resposta']) return $resposta;
            $senha_banco = $resposta['mensagem']; // senha armazenada com hash

            // comparar a senha recebida pela senha do banco
            if (!password_verify($requisicao['senha'], $senha_banco)) return RespostaProcesso::respostaProcesso("Acesso Negado");

            $usuario = $repositorio->obterUsuario($email);


            //Localizar o usuario dentro do banco
            $conexao = new Conexao(); // inicia o objeto conexão
            $conexao = $conexao->getConexao(); // inicia a conexão
            $comando = "
                        SELECT 
                            id_usuario AS id, 
                            perfil_usuario AS tipo_usuario, 
                            nome_usuario AS nome,
                            sobrenome_usuario AS sobrenome,
                            email_usuario AS email,
                            cpf_usuario AS cpf,
                            senha_usuario AS senha
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
            
            // cria o usuario em etapas a partir daqui
            $usuario = new Usuario();
            $usuario->setId($resposta['id']);
            $usuario->setPerfil($resposta['tipo_usuario']);
            $usuario->setSenha($senha);
            
            // armazena os dados pessoais
            $dados_pessoais = new DadosPessoais(
                new Nome($resposta['nome']),
                new Sobrenome($resposta['sobrenome']),
                new Email($email->getEmail()),
                new Cpf($resposta['cpf']),
            );
            $usuario->setDadosPessoais($dados_pessoais);

            // guarda o hash armazenado no banco
            $senha_banco = $resposta['senha'];
            

            // compara as senha do usuario e a senha armazenada no banco
            if (!password_verify(
                $usuario->getSenha(), 
                $senha_banco)) 
                throw new Exception("ERRO LOGIN: Senha invalida");

            // a senha já foi conferida e pode ser apagada da memoria para evitar incidentes
            $usuario->setSenha(limpar:true);
            $senha_banco = null;
            $resposta['senha'] = null;
            $requisicao['senha'] = null;


            // captura os dados complementares do usuario
            $perfil_usuario = $usuario->getPerfil();
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

            $conexao = new Conexao();
            $conexao = $conexao->getConexao();

            $sql = $conexao->prepare($comando);
            $sql->bindValue(":id", $usuario->getId());
            $sql->execute();

            // captura os dados do banco
            $resposta = $sql->fetch(PDO::FETCH_ASSOC);

            // verifica se não está vazio
            if ($sql->rowCount() == 0 || !$resposta) throw new Exception("Usuario não cadastrado no sistema");

            // encerrando conexão
            $sql = null;
            $conexao = null;

            // guarda o endereço registrado
            $endereco = new Endereco();
            $endereco->setCidade($resposta['cidade']);
            $endereco->setEstado($resposta['estado']);
            $usuario->setEndereco($endereco);

            if ($perfil_usuario == "representante"){
                $prefeitura = new Prefeitura($resposta['orgao'], $resposta['cargo']);
                $usuario->setPrefeitura($prefeitura);
            }

            // cria a sessão do usuario 
            SessaoController::salvarUsuario($usuario, $requisicao['lembrar']);
            return RespostaProcesso::respostaProcesso("Acesso autorizado", true);
        } catch (Exception $erro){
            $mensagem = $erro->getMessage() . "\nLINHA: " . $erro->getLine()  . "\nCODIGO: " . $erro->getCode() . "\nArquivo: " . $erro->getFile();
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
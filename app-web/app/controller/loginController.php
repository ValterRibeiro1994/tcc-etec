<?php

class LoginController {
    public function index(array $requisicao){
        
        if ($requisicao['metodo'] == "GET") {
            if ($this->estaLogado())
                // o usuario não pode acessar login se está autenticado 
                return RespostaProcesso::resposta(
                mensagem: "Controller: Usuario Conectado",
                pagina: "app/view/paginas/index.html", resposta: true, 
                formato: "html");

            return RespostaProcesso::resposta(
                mensagem: "Controller: Enviando documento html", resposta: true,
                dados: [], formato: "html",
                pagina: "app/view/paginas/login.html"
            );
        }
        if ($requisicao['metodo'] == "POST") {
            if ($this->estaLogado()){
                return RespostaProcesso::resposta(
                    mensagem: "Controller: Usuario Conectado", resposta: false,
                    dados: [], formato: "json"
                );
            }

            // para esse controller é permitido apenas login
            try {
                return $this->efetuarLogin($requisicao);
            } catch (Exception $erro){
                return RespostaProcesso::resposta(
                    mensagem: "Controller: Erro ao efetuar login", resposta: false,
                    dados: RespostaProcesso::salvarErro($erro), formato: "json"
                );
            }
        }

        return RespostaProcesso::resposta(
            mensagem: "Controller: Método de requisição invalida para login", resposta: false,
            dados: $requisicao, formato: "json"
        );
    }

    private function efetuarLogin(array $requisicao){
        $tentativas = SessaoService::obterTentativasLogin();
        if ($tentativas > 3){
            // coloque em espera para proxima tentativa
            SessaoService::editarTempoEspera(3);
            if (SessaoService::espera()){
                return RespostaProcesso::resposta(
                    mensagem: "Controller: Limite de tentativas Excedido, aguarde um pouco antes de tentar novamente",
                    resposta: false, dados: ['tentativas' => $tentativas],
                    formato: "json"
                );
            }
            
            SessaoService::resetarTentativasLogin();
            SessaoService::resetarTempoInicio();
        } else {
            SessaoService::adicionarTentativa();
        }

        $campos = ['email', 'senha', 'lembrar'];
        for ($x = 0; $x < 2; $x++){
            $entrada = $campos[$x];
            if (!array_key_exists($entrada, $requisicao)) {
                return RespostaProcesso::resposta(
                    mensagem: "Controller: Campo $entrada não encontrado", resposta: false, 
                    dados: $requisicao, formato: "json"
                );
            }
        }

        // valida os dados recebidos pelo front-end
        $email = new Email($requisicao['email']);
        $email->validarEmail();

        $senha = new Senha($requisicao['senha']);
        $senha->validarSenha();

        // iniciar repositorio
        $repositorio = new UsuarioRepositorio(new Conexao());
        
        // Localiza o usuario pelo email recebido
        $resposta = $repositorio->obterUsuario($email->getEmail());
        if (!$resposta['resposta']) return $resposta;
        
        // destroi o repositorio que foi aberto
        // $repositorio = null;
        $usuario_db = $resposta['dados'];
        // captura a senha do banco
        $senha = $usuario_db['senha']; // hash armazenado no banco

        if (!password_verify($requisicao['senha'], $senha)) {
            return RespostaProcesso::resposta(
                mensagem: "Controller: Senha inválida", resposta: false,
                dados: $requisicao, formato: "json"
            );
        }

        // cria um novo usuario com os dados recebidos
        $usuario = UtilService::criarUsuario($usuario_db);
        
        // destroi a sessão de visitante
        SessaoService::destruirSessao();

        // cria uma nova sessão para o usuario autenticado
        $resposta = SessaoService::registrarUsuario($usuario, $requisicao['lembrar']);
        if (!$resposta['resposta']) return $resposta;

        // cria o token para o usuario
        $resposta = TokenService::criarToken($usuario, $requisicao['lembrar'] ? 86400 : $GLOBALS['tempo-sessao']);
        if (!$resposta['resposta']) return $resposta;

        return RespostaProcesso::resposta(
            mensagem: "Controller: Login efetuado com sucesso", resposta: true,
            dados: $resposta['dados'], formato: "json"
        );
    }

    private function estalogado(){
        return SessaoService::estaOnline();
    }  
}
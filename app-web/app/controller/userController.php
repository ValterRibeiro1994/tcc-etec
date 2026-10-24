<?php

class UserController {


    private function validarSessao(array $requisicao){
        if (!SessaoService::estaOnline()){
            // visitante não pode ter acesso ao dados de um perfil de usuario
            return RespostaProcesso::resposta(
                mensagem: "Controller: Serviço disponivel apenas para usuario cadastrados", resposta: false,
                formato: "json"
            );
        }

        // captura o tokem do cliente
        if (!array_key_exists("token", $requisicao)){
            return RespostaProcesso::resposta(
                mensagem: "Controller: Token não enviado", resposta: false,
                formato: "json"
            );
        }

        $resposta = TokenService::validarToken($requisicao['token']);
        if (!$resposta['resposta']){
            return RespostaProcesso::resposta(
                mensagem: "Controller: Token invalido", resposta: false,
                dados: $resposta['dados'], formato: "json"
            );
        }

        // extraia o payload do token recebido
        $resposta = TokenService::extrairPayload($requisicao['token']);
        if (!$resposta['resposta']){
            return RespostaProcesso::resposta(
                mensagem: "Controller: Erro ao extrair payload", resposta: false,
                dados: $resposta['dados'], formato: "json"
            );
        }

        $payload = $resposta['dados'];
        // checa se o payload possui todos os campos obrigatorios
        $atributos = ['perfil', 'nome', 'sobrenome', 'email'];
        if (!UtilService::camposExistem($atributos, $payload)){
            // o token é invalido e pode ter sido alterado
            return RespostaProcesso::resposta(
                mensagem: "Controller: Token inválido", resposta: false,
                formato: "json"
            );
        }

        // garanta que todos os atributos sejam validos
        if (!UtilService::perfilExiste(perfis_validos: ['municipe', 'representante', 'visitante'], perfil_recebido: $payload['perfil'])){
            // o perfil enviado no token não existe no sistema
            return RespostaProcesso::resposta(
                mensagem: "Controller: Token invalido", resposta: false,
                formato: "json"
            );
        }

        // se o perfil for de um visitante ou algo fora do sistema, o token é invalido
        if ($payload['perfil'] == "visitante"){
            return RespostaProcesso::resposta(
                mensagem: "Controller: Token de visitante", resposta: false,
                formato: "json"
            );
        }

        // sabemos que até aqui já foi descartada a tentativa de um visitante
        $atributo[] = "cidade";
        $atributos[] = "estado";

        if ($payload['perfil'] == "representante"){
            $atributos[] = 'orgao';
            $atributos[] = 'cargo';
        }

        // compara os dados do payload com o da sessão
        foreach($atributos as $atributo){
            if ($payload[$atributo] != SessaoService::obterAtributoUsuario($atributo)){
                // os mesmos dados de sessão são passados para o token, algum deles foi alterado
                return RespostaProcesso::resposta(
                    mensagem: "Controller: Token invalido", resposta: false,
                    formato: "json"
                );
            }
        }

        return RespostaProcesso::resposta(
            mensagem: "Controller: Sessão valida", resposta: true,
            dados:['payload'=>$payload]
        );
    }
    
    public function index(array $requisicao){
        
        /**
         * GET/user -> redireciona o usuario para a pagina perfil
         */

        if ($requisicao['metodo'] == "GET"){ // pede o arquivo html para usuario
            // verifica se o usuario está online
            if (SessaoService::estaOnline()){
                // encaminha ele para a pagina perfil.html
                return RespostaProcesso::resposta(
                    mensagem: "Carregando Perfil de usuario", resposta: true,
                    formato: "html", pagina: "app/view/paginas/perfil.html"
                );
            }

            // retorna a pagina index, apenas usuario cadastrado pode ter perfil
            return RespostaProcesso::resposta(
                mensagem: "Perfil disponivel apenas para cadastrados", resposta: false,
                formato: "html",
                pagina: "app/view/paginas/index.html"
            );
        }

        // pede os dados do usuario
        if ($requisicao['metodo'] == "POST"){

            $resposta = $this->validarSessao($requisicao);
            if (!$resposta['resposta']) return $resposta;
            
            $payload = $resposta['dados']['payload'];
            // podemos criar o usuario 
            $usuario = UtilService::criarUsuario($payload);

            // localizamos todas as denuncias do usuario
            $repositorio = new DenunciasRepositorio();

            $denuncias = $repositorio->listarDenunciasDoUsuario($usuario->getEmail());
            if (!$resposta['resposta']){
                return RespostaProcesso::resposta(
                    mensagem: "Controller: Erro ao obter denuncias de usuario", resposta: false,
                    dados: $resposta['dados'], formato: "json"
                );
            }

            $denuncias = $denuncias['dados'];

            $resposta = [
                'usuario'=>$usuario->getArray()['dados'],
                'denuncias'=>$denuncias
            ];
            
            return RespostaProcesso::resposta(
                mensagem: "Controller: Dados de perfil carregados com sucesso", resposta: true,
                dados: $resposta, formato: "json"
            );
        }
    }

    public function representante(array $requisicao){
        return RespostaProcesso::resposta("app/view/paginas/cadastro-denunciante.html", true, formato: "html");
    }

    public function logout(array $requisicao){
        if ($requisicao['metodo'] === "GET"){
            $resposta = RespostaProcesso::resposta(
                mensagem: "Usuario desconectado", resposta: true
            );
            
            SessaoService::destruirSessao();

            return $resposta;
        }

        return RespostaProcesso::resposta("Requisição inválida para logout");
    }

    public function municipe(array $requisicao){
        if ($requisicao['metodo'] == "GET"){
            // enviar a página solicitada para o usuario (mural)
            return RespostaProcesso::resposta("app/view/paginas/perfil.html", true, formato: "html");
        }

        if ($requisicao['metodo'] == "POST"){
            return $this->postProcessoMunicipe($requisicao);
        }

        throw new Exception("Requisição inválida");
    }

    private function postProcessoMunicipe(array $requisicao){
        
        return RespostaProcesso::resposta("Criar processo POST para os dados a serem recebidos");
    }

    public function perfil(array $requisicao){
        return RespostaProcesso::resposta(
            mensagem: "Página de perfil", resposta: true,
            formato: "html", pagina: "app/view/paginas/perfil.html"
        );
    }

    public function dados(array $requisicao){
        if ($requisicao['metodo'] !== "GET") {
            return RespostaProcesso::resposta("Requisição inválida para obter os dados do perfil");
        }

        $resposta = UsuarioLogadoService::obter();
        if (!$resposta['resposta']) return $resposta;

        $repositorio = new PerfilRepositorio();
        return $repositorio->obterDadosPerfil($resposta['dados']['email']);
    }
}

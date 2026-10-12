<?php

class UserController {
    public function index(array $requisicao){
        
        if (!SessaoController::estaConectado()) return RespostaProcesso::respostaProcesso("app/view/paginas/index.html", true, formato: "html");

        $perfil = SessaoController::obterAtributoUsuario("perfil"); 
        if ($perfil == "representante"){
            return $this->representante($requisicao);
        }

        if ($perfil == "municipe"){
            return $this->municipe($requisicao);
        }

        throw new Exception("Perfil $perfil inválido para usuarios");
    }

    public function representante(array $requisicao){
        return RespostaProcesso::respostaProcesso("app/view/paginas/cadastro-denunciante.html", true, formato: "html");
    }

    public function logout(array $requisicao){
        if ($requisicao['metodo'] === "GET"){
            // existe uma sessão ?
            if (SessaoController::estaConectado()){
                // se esta conectado o campo token deve existir
                $token = new TokenController();
                $resposta = $token->validarToken();
                if ($resposta['resposta']){
                    SessaoController::encerrarSessao();
                    return RespostaProcesso::respostaProcesso("Usuario desconectado", true);
                } else {
                    SessaoController::encerrarSessao();
                    return $resposta;
                }
            } else {
                return RespostaProcesso::respostaProcesso("Usuario desconectado", true);
            }
        }
        throw new Exception("Requisição inválida");
    }

    public function municipe(array $requisicao){
        if ($requisicao['metodo'] == "GET"){
            // enviar a página solicitada para o usuario (mural)
            return RespostaProcesso::respostaProcesso("app/view/paginas/perfil.html", true, formato: "html");
        }

        if ($requisicao['metodo'] == "POST"){
            return $this->postProcessoMunicipe($requisicao);
        }

        throw new Exception("Requisição inválida");
    }

    private function postProcessoMunicipe(array $requisicao){
        
        return RespostaProcesso::respostaProcesso("Criar processo POST para os dados a serem recebidos");
    }

    // ADICIONADO: rota oficial da página de perfil -> ./user/perfil
    // (quem confere o login é o perfil.js e a rota user/dados, que exige o token)
    public function perfil(array $requisicao){
        return RespostaProcesso::resposta(
            mensagem: "Página de perfil", resposta: true,
            formato: "html", pagina: "app/view/paginas/perfil.html"
        );
    }

    // ADICIONADO: ./user/dados -> JSON com os dados do usuário logado, vindos do banco
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

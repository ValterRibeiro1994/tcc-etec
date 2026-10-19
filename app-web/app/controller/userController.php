<?php

class UserController {
    public function index(array $requisicao){
        
        if (!SessaoController::estaConectado()) return RespostaProcesso::resposta("app/view/paginas/index.html", true, formato: "html");

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

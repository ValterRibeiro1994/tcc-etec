<?php

class UserController {
    public function index(array $requisicao){
        
        if (!SessaoController::estaConectado()) return RespostaProcesso::respostaProcesso("app/view/paginas/index.html", true, formato: "text/html");

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
        return RespostaProcesso::respostaProcesso("app/view/paginas/cadastro-denunciante.html", true, formato: "text/html");
    }

    public function municipe(array $requisicao){
        if ($requisicao['metodo'] == "GET"){
            // enviar a página solicitada para o usuario (mural)
            return RespostaProcesso::respostaProcesso("app/view/paginas/home-mural.html", true, formato: "text/html");
        }

        if ($requisicao['metodo'] == "POST"){
            return $this->postProcessoMunicipe($requisicao);
        }

        throw new Exception("Requisição inválida");
    }

    public function postProcessoMunicipe(array $requisicao){
        return RespostaProcesso::respostaProcesso("Criar processo POST para os dados a serem recebidos");
    }
}
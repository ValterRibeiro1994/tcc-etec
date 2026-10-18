<?php
/**
 * GET/TOKEN -> CRIA TOKEN
 * POST/TOKEN -> VALIDA TOKEN
 * PUT/TOKEN -> RENOVA TOKEN
 * DELETE/TOKEN -> APAGA TOKEN
 */
class TokenController {

    public function index(array $requisicao){
        if (!array_key_exists("metodo", $requisicao)){
            return RespostaProcesso::resposta(
                mensagem: "Controller: Método não enviado", resposta: false,
                dados: $requisicao, formato: "json"
            );
        }

        $metodo = $requisicao['metodo'];
        if ($metodo == "GET"){
            return $this->criar($requisicao);
        } else if ($metodo == "POST"){
            return $this->validar($requisicao);
        } else if ($metodo == "PUT"){
            return $this->renovar($requisicao);
        } else if ($metodo == "DELETE"){
            // return $this->apagar($requisicao);
        } else {
            return RespostaProcesso::resposta(
                mensagem: "Controller: Método de requisição invalido", resposta: false,
                dados: $requisicao, formato: "json"
            );
        }
    }

    public function renovar(array $requisicao){
        if (!array_key_exists("perfil", $requisicao)){
            return RespostaProcesso::resposta(
                    mensagem: "Controller: Chave 'perfil' de atributo não enviado", resposta: false,
                    dados: $requisicao, formato: "json"
                );
        }

        // valida o perfil recebido
        $perfil = new Perfil($requisicao['perfil']);

        if ($requisicao['perfil'] == "visitante"){
            $chaves_necessarias = ['token', 'nome', 'sobrenome', 'perfil', 'email'];
        } else {
            $chaves_necessarias = ['token', 'nome', 'sobrenome', 'perfil', 'email', 'cidade', 'estado'];
            if ($requisicao['perfil'] == "representante"){
                $chaves_necessarias = ['token', 'nome', 'sobrenome', 'perfil', 'email', 'cidade', 'estado', 'orgao', 'cargo'];
            }
        }

        foreach ($chaves_necessarias as $chave) {
            if (!array_key_exists($chave, $requisicao)){
                return RespostaProcesso::resposta(
                    mensagem: "Controller: Chave '$chave' de atributo não enviado", resposta: false,
                    dados: $requisicao, formato: "json"
                );
            }
        }
        // checa se o token é valido
        $resposta = TokenService::validarToken($requisicao['token']);

        // guarda o token recebido pelo cliente
        $resposta['dados']['token'] = clone $requisicao['token'];

        // retorna o mesmo token se for valido não necessita um novo
        if ($resposta['resposta']) return $resposta;

        // recria o usuario com os dados do cliente
        $usuario = new Usuario(
        dados_usuario: new DadosPessoais(
            perfil: $perfil,
            nome: new Nome($requisicao['nome']),
            sobrenome: new Sobrenome($requisicao['sobrenome']),
            email: new Email($requisicao['email'])
            )
        );

        if ($requisicao['perfil'] != "visitante"){
            $usuario->setEndereco(
                endereco: new Endereco(
                    cidade: $requisicao['cidade'],
                    estado: $requisicao['estado']
                )
            );
        }

        if ($requisicao['perfil'] == "representante"){
            $usuario->setPrefeitura(
                prefeitura: new Prefeitura(
                    orgao: $requisicao['orgao'],
                    cargo: $requisicao['cargo']
                )
            );
        }

        // checa se a invalidez é por estar expirado
        if ($resposta['dados']['status'] == "expirado"){
            // retorna um novo token 
            return TokenService::criarToken($usuario);
        }

        // se chegou aqui o token é invalido
        return RespostaProcesso::resposta(
            mensagem: "Controller: Falha ao renovar token", resposta: false,
            dados: $resposta['dados'], formato: "json"
        );

    }

    public function validar(array $requisicao){
        if (!array_key_exists("token", $requisicao)){
            return RespostaProcesso::resposta(
                mensagem: "Chave token não enviada", resposta: false,
                dados: $requisicao, formato: "json"
            );
        }

        return TokenService::validarToken($requisicao['token']);
    }

    public function criar(array $requisicao){
        if (!array_key_exists("perfil", $requisicao)){
            return RespostaProcesso::resposta(
                mensagem: "Controller: Perfil de usuario não enviado", resposta: false,
                dados: $requisicao, formato: "json"
            );
        }

        $perfil_usuario = $requisicao['perfil'];
        if ($perfil_usuario === "visitante"){
            $usuario = new Usuario(new DadosPessoais());
            $token_service = TokenService::criarToken($usuario, $GLOBALS['tempo-sessao']);
            if (!$token_service['resposta']){
                return RespostaProcesso::resposta(
                    mensagem: "Controller: Falha ao criar token", resposta: false,
                    dados: $token_service['dados'], formato: "json"
                );
            }
            return $token_service;
        }

        if ($perfil_usuario !== "visitante"){
            $chaves_necessarias = ['nome', 'sobrenome', 'cidade', 'estado', 'email'];
            foreach ($chaves_necessarias as $chave) {
                if (!array_key_exists($chave, $requisicao)){
                    return RespostaProcesso::resposta(
                        mensagem: "Controller: Chave $chave não enviada", resposta: false,
                        dados: $requisicao, formato: "json"
                    );
                }
            }

            $usuario = new Usuario(
                dados_usuario: new DadosPessoais(
                    perfil: new Perfil($requisicao['perfil']),
                    nome: new Nome($requisicao['nome']),
                    sobrenome: new Sobrenome($requisicao['sobrenome']),
                    email: new Email($requisicao['email'])
                )
            );
            $usuario->setCidade($requisicao['cidade']);
            $usuario->setEstado($requisicao['estado']);

            if ($perfil_usuario === "representante"){
                $chaves_necessarias = ['orgao', 'cargo'];
                foreach ($chaves_necessarias as $chave) {
                    if (!array_key_exists($chave, $requisicao)){
                        return RespostaProcesso::resposta(
                            mensagem: "Controller: Chave $chave não enviada", resposta: false,
                            dados: $requisicao, formato: "json"
                        );
                    }
                }

                $usuario->setPrefeitura(
                    prefeitura: new Prefeitura(
                        orgao: $requisicao['orgao'],
                        cargo: $requisicao['cargo']
                    )
                );
            }

            $token_service = TokenService::criarToken($usuario, $GLOBALS['tempo-sessao']);
            if (!$token_service['resposta']){
                return RespostaProcesso::resposta(
                    mensagem: "Controller: Falha ao criar token", resposta: false,
                    dados: $token_service['dados'], formato: "json"
                );
            }
            return $token_service;
        }
    }

}
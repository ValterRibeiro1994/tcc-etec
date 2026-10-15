<?php

class TokenController {
    public function visitante(array $requisicao){
        /**
         * GARANTIR QUE A ENTRADA ESTEJA CORRETA 
         */
        $entradas = ['token-funcao'];
        foreach ($entradas as $chave) {
            if (!array_key_exists($chave, $requisicao)){
                return RespostaProcesso::resposta(
                    mensagem: "Controller: Chave $chave não enviada na requisicao", resposta: false,
                    dados: $requisicao, formato: "json"
                );
            }
        };

        /**
         * Executar o processo solicitado
         */
        $funcoes = ['criar', 'validar', 'renovar', 'editar'];
        foreach ($funcoes as $funcao) {
            if ($requisicao['token-funcao'] !== $funcao) continue;
            try {
                return $this->$funcao($requisicao);
            } catch (Exception $erro){
                return RespostaProcesso::resposta(
                    mensagem: "Controller: Erro ao $funcao token", resposta: false,
                    dados: RespostaProcesso::salvarErro($erro), formato: "json"
                );
            }
        }

    }

    private function criar(array $requisicao){
        if (!array_key_exists("perfil", $requisicao)){
            return RespostaProcesso::resposta(
                mensagem: "Controller: Chave 'perfil' não enviada", resposta: false,
                dados: $requisicao, formato: "json"
            );
        }

        try {
            $usuario = new Usuario(
                dados_usuario: new DadosPessoais(
                    perfil: new Perfil($requisicao['perfil'])
                    )
                );

            if ($usuario->getPerfil() === "visitante"){
                // cria o token
                return TokenService::criarToken($usuario, $GLOBALS['tempo-sessao']);
            }

            $entradas = ['nome', "sobrenome", "email", "cidade", "estado"];
            foreach ($entradas as $entrada) {
                if (!array_key_exists($entrada, $requisicao)){
                    return RespostaProcesso::resposta(
                        mensagem: "Controller: Chave $entrada não enviada", resposta: false,
                        dados: $requisicao, formato: 'json'
                    );
                }
            }

            $usuario->setNome($requisicao['nome']);
            $usuario->setSobrenome($requisicao['sobrenome']);
            $usuario->setEmail($requisicao['email']);
            $usuario->setCidade($requisicao['cidade']);
            $usuario->setEstado($requisicao['estado']);

            if ($usuario->getPerfil() == "municipe") return TokenService::criarToken($usuario);
            $entradas = ['orgao', 'cidade'];
            foreach ($entradas as $entrada) {
                if (!array_key_exists($entrada, $requisicao)){
                    return RespostaProcesso::resposta(
                        mensagem: "Controller: Chave $entrada não enviada", resposta: false,
                        dados: $requisicao, formato: 'json'
                    );
                }
            }

            $usuario->setPrefeitura(orgao: $requisicao['orgao'], cargo: $requisicao['cargo']);
            return TokenService::criarToken($usuario);

        } catch (Exception $erro){
            return RespostaProcesso::resposta(
                mensagem: "Controller: Erro ao 'criar' token", resposta: false,
                dados: RespostaProcesso::salvarErro($erro), formato: "json"
            );
        }
    }
}
<?php

use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class TokenService {

    public static function validarToken(string $token_cliente){
        try {
            $token = str_replace("Bearer ", "", $token_cliente);
            $decodificar = JWT::decode($token, new Key($GLOBALS['token-chave'], $GLOBALS['tkn-algo']));
            return RespostaProcesso::resposta(
                mensagem: "Service: Token válido", resposta: true,
                dados: ['status'=>'valido']
            );
        } catch (ExpiredException $erro){
            return RespostaProcesso::resposta(
                mensagem: "Service: Token expirado", resposta: false,
                dados: ['status'=> 'expirado']
            );
        } catch (Exception $erro) {
            $dados = RespostaProcesso::salvarErro($erro);
            $dados['status'] = 'invalido';
            return RespostaProcesso::resposta(
                mensagem: "Service: Erro ao validar token", resposta: false,
                dados: $dados
            );
        }
    }

    public static function criarToken(Usuario $usuario, int $expira = 86400){
        try {
            $dados = [
                "perfil" => $usuario->getPerfil(),
                'nome' => $usuario->getNome(),
                'sobrenome' => $usuario->getSobrenome(),
                "email" => $usuario->getEmail()
            ];

            $configuracao = [
                "iss" => "localhost",
                "aud" => "localhost",
                "iat" => time(),
                "exp" => time() + $expira,
                "perfil" => $usuario->getPerfil(),
                'nome' => $usuario->getNome(),
                'sobrenome' => $usuario->getSobrenome(),
                "email" => $usuario->getEmail()
            ];

            if ($usuario->getPerfil() != "visitante"){
                $configuracao['cidade'] = $usuario->getCidade();
                $dados['cidade'] = $usuario->getCidade();
                $configuracao['estado'] = $usuario->getEstado();
                $dados['estado'] = $usuario->getEstado();
                $expira = $GLOBALS['tempo-sessao'];
                $configuracao['exp'] = time() + $expira;
            }

            if ($usuario->getPerfil() == "representante"){
                $configuracao['orgao'] = $usuario->getPrefeitura()->getOrgao();
                $dados['orgao'] = $usuario->getPrefeitura()->getOrgao();
                $configuracao['cargo'] = $usuario->getPrefeitura()->getCargo();
                $dados['cargo'] = $usuario->getPrefeitura()->getCargo();
            }

            $jwt = JWT::encode($configuracao, $GLOBALS['token-chave'], $GLOBALS['tkn-algo']);
            $dados['token'] = $jwt;
            return RespostaProcesso::resposta(
                mensagem: "Service: Token gerado com sucesso para " . $usuario->getPerfil(), 
                resposta: true,
                dados: $dados
            );
        } catch (Exception $erro) {
            $dados = RespostaProcesso::salvarErro($erro);
            return RespostaProcesso::resposta(
                mensagem: "Service: Erro ao gerar token" , resposta: false,
                dados: $dados
            );
        }
    }

    public static function renovarToken(Usuario $usuario, string $token_cliente){
        // recupera o payload do token recebido
        $payload = (array) JWT::decode($token_cliente, new Key($GLOBALS['token-chave'], $GLOBALS['tkn-algo']));
        
        // checa se tem 'perfil' atributo no payload
        if (!array_key_exists("perfil", $payload)){
            return RespostaProcesso::resposta(
                mensagem: "Service: Token invalido", resposta: false,
                dados: ['status'=>"invalido"]
            );
        }

        // verifica se o token é pertencente a um visitante
        if ($payload['perfil'] === "visitante"){
            // checa se o perfil no payload corresponde ao perfil de usuario recebido
            if ($usuario->getPerfil() !== "visitante"){
                return RespostaProcesso::resposta(
                    mensagem: "Service: Tokens incompativeis", resposta: false,
                    dados: ['status'=>"invalido"]
                );
            }
                    
            // cria um novo token para o visitante
            return self::criarToken($usuario, $GLOBALS['tempo-sessao']);
        }

        // verifica se o perfil pertence a alguem já cadastrado
        if ($payload['perfil'] === "municipe" || $payload['perfil'] === "representante"){
            // o payload deve ter nome, sobrenome, email, cidade, estado 
            // orgao e cargo no caso do representante
            if ($payload['perfil'] === "representante"){
                $dados_payload = ['nome', 'sobrenome', 'email', 'cidade', 'estado', 'orgao', 'cargo'];
            } else {
                $dados_payload = ['nome', 'sobrenome', 'email', 'cidade', 'estado'];
            }

            foreach($dados_payload as $chave){
                if (!array_key_exists($chave, $payload)){
                    return RespostaProcesso::resposta(
                        mensagem: "Service: Token Inválido", resposta: false,
                        dados:['status'=>"invalido"]
                    );
                }

                // compara os dados do payload com a do usuario recebido
                $comando = "get" . ucfirst($chave);
                if ($payload[$chave] !== $usuario->$comando()){
                    return RespostaProcesso::resposta(
                        mensagem: "Service: Token Invalido", resposta: false,
                        dados:['status'=>'invalido']
                    );
                }
            }

            return self::criarToken($usuario);
        }

        return RespostaProcesso::resposta(
            mensagem:"Service: Token Inválido", resposta: false,
            dados: ['status'=>'invalido']
        );
    }

    public static function extrairPayload(string $token_cliente) {
        try {
            // considera que o token já tenha sido validado antes
            $token = str_replace("Bearer ", "", $token_cliente);
            $decodificar = JWT::decode($token, new Key($GLOBALS['token-chave'], $GLOBALS['tkn-algo']));
            return RespostaProcesso::resposta(
                mensagem: "Service: Token extraido com sucesso", resposta: true,
                dados: (array) $decodificar
            );
        } catch (Exception $erro){
            return RespostaProcesso::resposta(
                mensagem: "Service: Falha ao extrair payload", resposta: false,
                dados: RespostaProcesso::salvarErro($erro)
            );
        }
    }
}
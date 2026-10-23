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
        $resposta['dados']['token'] = $requisicao['token'];

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
            if ($usuario->getPerfil() != "visitante") {
                $lembrar = true;
            } else {
                $lembrar = false;
            }
            // retorna um novo token 
            $resposta =  TokenService::criarToken($usuario);
            if ($resposta['resposta']){
                SessaoService::registrarUsuario($usuario, $lembrar);
            } else {
                SessaoService::destruirSessao();
            }
            return $resposta;
        }

        // se chegou aqui o token é invalido
        SessaoService::destruirSessao();
        return RespostaProcesso::resposta(
            mensagem: "Controller: Falha ao renovar token", resposta: false,
            dados: $resposta['dados'], formato: "json"
        );

    }

    public function validar(array $requisicao){
        if (!array_key_exists("token", $requisicao)){
            return RespostaProcesso::resposta(
                mensagem: "Controller: Token não enviado", resposta: false,
                dados: ['status'=>'invalido'], formato: "json"
            );
        }

        // $headers = getallheaders();
        // if (!array_key_exists("Authorization", $headers)){
        //     return RespostaProcesso::resposta(
        //         mensagem: "Controller: Cabeçalho de autenticação não enviado", resposta: false,
        //         dados: ['status'=>'invalido'], formato: "json"
        //     );
        // }

        return TokenService::validarToken($requisicao['token']);
    }

    public function criar(array $requisicao){
        // crio o usuario com os dados recebidos
        $usuario = UtilService::criarUsuario($requisicao);
        
        if ($requisicao['lembrar']){
            $expira = 86400;
        } else {
            $expira = $GLOBALS['tempo-sessao'];
        }
        
        // cria o token para o usuario
        $token = TokenService::criarToken($usuario, $expira);
        if (!$token['resposta']){
            return RespostaProcesso::resposta(
                mensagem: "Controller: Não foi possivel gerar o token", resposta: false,
                dados: $token['dados'], formato: "json"
            );
        }

        // inicia uma sessão para o usuario
        $sessao = SessaoService::registrarUsuario($usuario, $requisicao['lembrar']);
        if (!$sessao['resposta']){
            return RespostaProcesso::resposta(
                mensagem: "Controller: Falha ao iniciar sessão", resposta: false,
                dados: $sessao['dados'], formato: "json"
            );
        }

        return RespostaProcesso::resposta(
            mensagem: "Controller: Sessão iniciada com sucesso", resposta: true,
            dados: $token['dados'], formato: "json"
        );

    }
}

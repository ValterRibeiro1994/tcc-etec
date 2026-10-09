<?php

class HomeController {
    public function index(array $requisicao) {
        $server = $_SERVER['REQUEST_METHOD'];
        $metodo = $requisicao['metodo'];

        if ($server == "GET" && $metodo == "GET"){
            return RespostaProcesso::resposta(
                mensagem: "Sucesso", resposta: true,
                formato: "html", pagina: "app/view/paginas/index.html"
            );
        }

        if ($metodo == "GET" && $server == "POST"){
            $chaves = ['perfil', 'token'];
            foreach($chaves as $chave){
                if (!array_key_exists($chave, $requisicao)) {
                    return RespostaProcesso::resposta(
                        mensagem: "Chave $chave não enviada para essa solicitação", resposta: false,
                        dados: $requisicao, formato: "json"
                    );
                }
            }

            // validar os valores dentro da token
            if ($requisicao['token'] !== "criar" && $requisicao['token'] !== "validar"){
                    return RespostaProcesso::resposta(
                        mensagem: "Função não conhecida para token", resposta: false,
                        dados: $requisicao, formato: "json"
                    );
            }
            }

            if ($requisicao['perfil'] == "visitante"){

                // criar um novo token (chave token se refere a função, se não existir um token envia 'criar')
                if ($requisicao['token'] == "criar"){
                    $usuario = new Usuario(
                        dados_usuario: new DadosPessoais(
                            perfil: new Perfil("visitante"),
                            nome: new Nome("visitante"),
                            sobrenome: new Sobrenome("visitante"),
                            email: new Email("visitante@gmail.com")
                    ));
                    
                    $resposta = TokenService::criarToken($usuario, expira: 600);
                    if (!$resposta['resposta']){
                        return RespostaProcesso::resposta(
                            mensagem: "Não foi possivel criar token para visitante", resposta: false,
                            dados: $resposta['dados'], formato: "json"
                        );
                    }

                    $sessao = SessaoService::registrarUsuario($usuario, false);
                    if (!$sessao['resposta']){
                        return RespostaProcesso::resposta(
                            mensagem: "Erro ao registrar visitante" . $sessao['mensagem'], resposta: false,
                            dados: $sessao['dados'], formato: "json"
                        );
                    }
                    
                    $token = $resposta['dados'];
                    return RespostaProcesso::resposta(
                        mensagem: "Token criado com sucesso para visitante", resposta: true,
                        dados: $token, formato: "json"
                    );
                }

                // se o token já existir e quiser apenas uma validação dele envie 'validar' na chave token
                // e envie uma chave 'token-valor' com o token já recebido
                if ($requisicao['token'] == "validar"){
                    if (!array_key_exists('token-valor', $requisicao)){
                        return RespostaProcesso::resposta(
                            mensagem: "Chave 'token-valor' não enviado para validação", resposta: false,
                            dados: $requisicao, formato: "json"
                        );
                    }

                    // valida o token
                    $resposta = TokenService::validarToken($requisicao['token-valor']);
                    if (!$resposta['resposta']){
                        return RespostaProcesso::resposta(
                            mensagem: "Erro ao validar Token: " . $resposta['mensagem'], resposta: false,
                            dados: $resposta['dados'], formato: "json"
                        );
                    }

                    return RespostaProcesso::resposta(
                        mensagem: "Token validado com sucesso", resposta: true,
                        dados: $resposta['dados'], formato: "json"
                    );
                    
                }
            }

            if ($requisicao['perfil'] == "municipe"){
                $dados_necessarios = ['nome', 'sobrenome', 'email'];
                foreach($dados_necessarios as $chave){
                    if (!array_key_exists($chave, $requisicao)){
                        return RespostaProcesso::resposta(
                            mensagem: "Chave $chave não enviada para 'municipe'", resposta: false,
                            dados: $requisicao, formato: "json"
                        );
                    }
                }

                if ($requisicao['token'] == "criar"){

                    try {
                        // cria um usuario com os dados recebidos
                        $municipe = new Usuario(
                            dados_usuario: new DadosPessoais(
                                perfil: new Perfil($requisicao['perfil']),
                                nome: new Nome($requisicao['nome']),
                                sobrenome: new Sobrenome($requisicao['sobrenome']),
                                email: new Email($requisicao['email'])
                        ));
    
                        // cria o token para o usuario
                        $resposta = TokenService::criarToken($municipe, 600);
                        if (!$resposta['resposta']){
                            return RespostaProcesso::resposta(
                                mensagem: "Erro ao criar token para 'municipe'", resposta:false,
                                dados: $resposta['dados'], formato: "json"
                            );
                        }

                        // envia o token gerado para o cliente
                        return RespostaProcesso::resposta(
                            mensagem: "Token gerado com sucesso para 'municipe'", resposta: false,
                            dados: $resposta['dados'], formato: "json"
                        );
                        
                    } catch (Exception $erro) {
                        return RespostaProcesso::resposta(
                            "Erro ao gerar token para 'municipe': " . $erro->getMessage(), resposta: false,
                            dados: RespostaProcesso::salvarErro($erro), formato: "json"
                        );
                    }



                }

                if ($requisicao['token'] == "validar"){
                    // captura o token
                    $token = $requisicao['token-valor'];

                    $municipe = new Usuario(
                        dados_usuario: new DadosPessoais(
                            perfil: new Perfil($requisicao['prefil']),
                            nome: new Nome($requisicao['nome']),
                            sobrenome: new Sobrenome($requisicao['sobrenome']),
                            email: new Email($requisicao['email'])
                        )
                    );

                    $usuario_sessao = SessaoService::obterUsuario();
                    if ($usuario_sessao['nome'] != $municipe->getNome()){
                        
                    }

                }
            }



        }
    }
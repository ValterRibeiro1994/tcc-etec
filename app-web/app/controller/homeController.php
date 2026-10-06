<?php

class HomeController {

    private function processoGet(array $requisicao){
        if (array_key_exists("perfil", $requisicao)){
            if ($requisicao['perfil'] == "visitante"){
                return $this->criarVisitante();   
            }
        }
        $resposta = $this->validarConexao($requisicao);
        if (!$resposta['resposta']){
            // se não tem sessão aberta cria um visitante
            $resposta = $this->criarVisitante();
            if (!$resposta['resposta']){
                return $resposta;
            }
            return RespostaProcesso::resposta(
                mensagem: "Visitante registrado", resposta: true,
                dados: $resposta['dados'], formato: "html",
                pagina: "app/view/paginas/index.html"
            );
        }
        return $resposta;
    }

    private function criarVisitante(){
        
        // inicia uma sessão de visitante
        $visitante = new Usuario(
            dados_usuario: new DadosPessoais(
                nome: new Nome("visitante"),
                sobrenome: new Sobrenome("visitante"),
                email: new Email("visitante@gmail.com"),
                cpf: null
            )
        );
        $visitante->setPerfil("visitante");

        $resposta = SessaoController::registrarSessao(usuario: $visitante, lembrar: false);
        if (!$resposta['resposta']){
            return RespostaProcesso::resposta(
                mensagem: $resposta['mensagem'], resposta: false, 
                dados: $resposta['dados'], formato: "html",  
                pagina: "app/view/paginas/index.html"
            );
        }

        $jwt = new TokenController();
        $resposta = $jwt->gerarToken(usuario: $visitante, expira: 600);
        if (!$resposta['resposta']){
            // não foi possivel gerar o token
            return RespostaProcesso::resposta(
                mensagem: $resposta['mensagem'], resposta: false, 
                dados: $resposta['dados'], formato: "html", 
                pagina: "app/view/paginas/index.html"
            );
        }

        $dados = [
            "token" => $resposta['mensagem'],
            "perfil" => $visitante->getPerfil(),
            "nome" => $visitante->getNome(),
            "sobrenome" => $visitante->getSobrenome(),
            "email" => $visitante->getEmail(),
        ];
        return RespostaProcesso::resposta(
            mensagem: "Token gerado com sucesso", resposta: true, 
            dados: $dados, formato: "html",
            pagina: "app/view/paginas/index.html");
                
    }

    private function validarConexao(array $requisicao){
            // existe uma sessão aberta?
            $resposta = SessaoController::estaConectado();
            if ($resposta['resposta']) { // sim
                // o cliente deve ter um token com a chave Authorization enviado no header
                // o servidor deve ter o token armazenado na sessão $_SESSION
                // um que pode ser enviado por 
                $jwt = new TokenController();
                $resposta = $jwt->validarToken(); // valida o token do usuario
                
                // se o token é falso 
                if (!$resposta['resposta']) { // token adulterado ou invalido
                    // se não tem um token valido e a sessão está aberta tem golpe ae
                    SessaoController::encerrarSessao();
                    return RespostaProcesso::resposta(
                        mensagem: $resposta['mensagem'], resposta: false, 
                        dados: $resposta['dados'], formato:"html", 
                        pagina: "app/view/paginas/index.html");
                }

                return RespostaProcesso::resposta(
                    mensagem: "Sucesso", resposta: true, 
                    dados: $resposta, formato: "html", 
                    pagina: "app/view/paginas/index.html");
            }
            return $resposta;

    }

    public function index(array $requisicao) {
        if ($requisicao['metodo'] == "GET" && $_SERVER['REQUEST_METHOD'] == "GET") {
            try {
                return $this->processoGet($requisicao);
            } catch (Exception $erro) {
                $dados = RespostaProcesso::salvarErro($erro);
                SessaoController::encerrarSessao();
                return RespostaProcesso::resposta($erro->getMessage(), dados:$dados);
            }

        } else if($requisicao['metodo'] == "GET" && $_SERVER['REQUEST_METHOD'] == "POST") { // o usuario está online

            try {

                $chaves = ['perfil', 'formato'];
                $n = count($chaves);
                for ($x = 0; $x < $n; $x++){
                    $chave = $chaves[$x];
                    if (!array_key_exists($chave, $requisicao)){
                        return RespostaProcesso::resposta(
                            mensagem: "Chave $chave não enviada", resposta: false,
                            dados: $requisicao, formato: "json");
                    }
                }

                if ($requisicao['perfil'] == "visitante"){
                    $resposta = $this->validarConexao($requisicao);
                    if (!$resposta['resposta']){
                        $resposta = $this->criarVisitante();
                        $resposta['formato'] = "json";
                        return $resposta;
                    }
                }
                // valida o token do usuario
                $jwt = new TokenController();
                $resposta = $jwt->validarToken();
                if (!$resposta['resposta']){
                    SessaoController::encerrarSessao();
                    $resposta['formato'] = "json";
                    return $resposta;
                }

                return RespostaProcesso::resposta(
                    mensagem: "Acesso liberado", resposta: true, 
                    dados: $resposta, formato: "json",
                    pagina: ""
                );
            } catch (Exception $erro) {
                SessaoController::encerrarSessao();
                $dados = RespostaProcesso::salvarErro($erro);
                return RespostaProcesso::resposta("Erro: " . $erro->getMessage(), false, $dados);
            }
        }
        }
    }
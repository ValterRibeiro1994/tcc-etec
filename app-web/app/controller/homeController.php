<?php

class HomeController {
    public function index(array $requisicao) {

        if ($requisicao['metodo'] == "GET"){
            return RespostaProcesso::resposta(
                mensagem: "Sucesso", resposta: true,
                formato: "html", pagina: "app/view/paginas/index.html"
            );
        }

        if ($requisicao['metodo'] == "POST"){
            $chaves = ['perfil', 'nome', 'sobrenome', 'email', 'token', 'lembrar'];
            if (!array_key_exists($chaves[0], $requisicao)) {
                return RespostaProcesso::resposta(
                    mensagem: "Perfil de usuario não enviado", resposta:false,
                    dados:$requisicao, formato: "json"
                );
            }

            if ($requisicao['perfil'] == "municipe" || $requisicao['perfil'] == "representante"){
                $chaves[] = "cidade";
                $chaves[] = "estado";
            }

            if ($requisicao['perfil'] == "representante"){
                $chaves[] = "orgao";
                $chaves[] = "cargo";
            }

            foreach ($chaves as $chave) {
                if (!array_key_exists($chave, $requisicao)){
                    return RespostaProcesso::resposta(
                        mensagem: "Chave $chave não enviada", resposta: false,
                        dados: $requisicao, formato: "json"
                    );
                }
            }

            // a chave token se refere a função
            $funcao = $requisicao['token'];

            // para criar um token é necessario criar um usuario antes
            $usuario = new Usuario(
                dados_usuario: new DadosPessoais(
                    perfil: new Perfil($requisicao['perfil']), 
                    nome: new  Nome($requisicao['nome']), 
                    sobrenome: new Sobrenome($requisicao['sobrenome']), 
                    email: new Email($requisicao['email'],
                     )
                )
            );

            if ($usuario->getPerfil() != "visitante"){
                $usuario->setEndereco(cidade: $requisicao['cidade'], estado: $requisicao['estado']);
            }

            if ($usuario->getPerfil() == "representante"){
                $usuario->setPrefeitura(orgao: $requisicao['orgao'], cargo: $requisicao['cargo']);
            }

            if ($funcao == "criar"){
                // ou seja esta finalizando o login
                $resposta = TokenService::criarToken($usuario);
                if (!$resposta['resposta']){
                    
                }
            }
        }

    }
}
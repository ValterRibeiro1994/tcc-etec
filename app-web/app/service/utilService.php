<?php

class UtilService {
    public static function perfilExiste(array $perfis_validos, string $perfil_recebido){
        foreach($perfis_validos as $perfil){
            if ($perfil == $perfil_recebido) return true;
        }
        return false;
    }

    public static function criarUsuario(array $dados){
        // como parte de todo usuario os campos abaixos são obrigatorios em todo processo
        if (array_key_exists("perfil", $dados)){
            if ($dados['perfil'] == "visitante"){
                return new Usuario(new DadosPessoais());
            }
        }
        
        $atributos = ['perfil', 'nome', 'sobrenome', 'email'];
        self::camposExistem($atributos, $dados);

        $usuario = new Usuario(
            dados_usuario: new DadosPessoais(
                perfil: new Perfil($dados['perfil']),
                nome: new Nome($dados['nome']),
                sobrenome: new Sobrenome($dados['sobrenome']),
                email: new Email($dados['email'])
            )
        );

        // como parte de  todo municipe ou representante cidade e estado são obrigatorios
        $atributos = ['cidade', 'estado'];
        self::camposExistem($atributos, $dados);

        $usuario->setEndereco(
                endereco: new Endereco(
                    cidade: $dados['cidade'],
                    estado: $dados['estado']
            )
        );

        if ($dados['perfil'] == "representante"){
            $atributos = ['orgao', 'cargo'];
            self::camposExistem($atributos, $dados);

            $usuario->setPrefeitura(
                new Prefeitura(
                    orgao: $dados['orgao'],
                    cargo: $dados['cargo']
                )
            );
        }

        // dados que não são obrigatorios mas podem ser necessarios em algum caso
        if (array_key_exists("senha", $dados)){
            $usuario->setSenha($dados['senha']);
        }

        if (array_key_exists("cpf", $dados)){
            $usuario->setCpf($dados['cpf']);
        }

        if (array_key_exists("id", $dados)){
            $usuario->setId((int) $dados['id']);
        }

        return $usuario;
    }

    public static function camposExistem(array $chaves, array $dados){
        foreach($chaves as $chave){;
            if (!array_key_exists($chave, $dados)) throw new InvalidArgumentException("Chave '$chave' não enviada");
        }
        return true;
    }
}
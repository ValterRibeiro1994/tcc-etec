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
        $atributos = ['perfil', 'nome', 'sobrenome', 'email'];
        if (!self::camposExistem($atributos, $dados)){
            throw new Exception("Service: Atributos obrigatorios não enviado");            
        }

        // validamos o perfil
        if (!self::perfilExiste(
            perfis_validos: ['municipe', 'representante', 'visitante'],
            perfil_recebido: $dados['perfil']
            )
        ) throw new Exception("Service: Perfil invalido");

        if ($dados['perfil'] == "visitante"){
            return new Usuario(new DadosPessoais());
        }

        // como parte de  todo municipe ou representante cidade e estado são obrigatorios
        $atributos = ['cidade', 'estado'];
        if (!self::camposExistem($atributos, $dados)){
            throw new Exception("Service: Atributo obrigatorio não enviado");
        }

        if ($dados['perfil'] == "representante"){
            $atributos = ['orgao', 'cargo'];
            if (!self::camposExistem($atributos, $dados['perfil'])){
                throw new Exception("Service: Atributo obrigatorio não enviado");
            }
        }
        
        $usuario = new Usuario(
            dados_usuario: new DadosPessoais(
                perfil: new Perfil($dados['perfil']),
                nome: new Nome($dados['nome']),
                sobrenome: new Sobrenome($dados['sobrenome']),
                email: new Email($dados['email'])
            )
        );

        $usuario->setEndereco(
            new Endereco(
                cidade: $dados['cidade'],
                estado: $dados['estado']
            )
        );

        if ($dados['perfil'] == "representante"){
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

        return $usuario;
    }

    public static function camposExistem(array $chaves, array $dados){
        foreach($chaves as $chave){
            if (!array_key_exists($chave, $dados)) return false;
        }
        return true;
    }
}
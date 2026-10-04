<?php

class RespostaProcesso {
    public static function respostaProcesso(string $mensagem, bool $status = false, array $dados = [], string $formato = "json"){
        return ['resposta'=>$status, "mensagem"=>$mensagem, "dados"=>$dados, "formato" => $formato];
    }

    public static function resposta(string $mensagem, bool $resposta = false, array|object $dados = []){
        return ['resposta' => $resposta, 'mensagem' => $mensagem, 'dados' => $dados];
    }

    public static function salvarErro(Exception $erro){
        return [
                "mensagem" => $erro->getMessage(),
                "codigo" => $erro->getCode(),
                "linha" => $erro->getLine(),
                "arquivo" => $erro->getFile(),
            ];
            
    }

    public static function erroProcesso(Exception $erro){
        $mensagem = $erro->getMessage();
        $mensagem = strtoupper($mensagem);
        if (str_contains( $mensagem, "SQLSTATE")){
            $codigo = $erro->getCode();
            if ($codigo == 23000){
                if (str_contains($mensagem, "CPF"))return RespostaProcesso::respostaProcesso("CPF já cadastrado");
                if (str_contains($mensagem, "EMAIL")) return RespostaProcesso::respostaProcesso("Email Já cadastrado");
                return RespostaProcesso::respostaProcesso("Usuario já cadastrado: $mensagem");
            }
            return RespostaProcesso::respostaProcesso("Código desconhecido: " . $mensagem);
        } else {
            return RespostaProcesso::respostaProcesso("ERRO:" . $mensagem);
        }
    }
}
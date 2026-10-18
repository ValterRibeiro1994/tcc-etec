<?php

class RespostaProcesso {
    public static function resposta(string $mensagem, bool $resposta = false, array $dados = [], string $formato = "json", string $pagina = ""){
        $resposta_ = ['resposta' => $resposta, 'mensagem' => $mensagem, 'dados' => $dados, 'formato' => $formato, 'pagina' => $pagina];
        return $resposta_;
    }

    public static function salvarErro(Exception $erro){
        return [
                "mensagem" => $erro->getMessage(),
                "codigo" => $erro->getCode(),
                "linha" => $erro->getLine(),
                "arquivo" => $erro->getFile(),
            ];
            
    }
}
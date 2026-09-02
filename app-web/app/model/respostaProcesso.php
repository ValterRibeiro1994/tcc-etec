<?php

class RespostaProcesso {
    public static function respostaProcesso(string $mensagem, bool $status = false, array $dados = []){
        return ['status'=>$status, "mensagem"=>$mensagem, "dados"=>$dados];
    }
}
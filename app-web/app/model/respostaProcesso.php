<?php

class RespostaProcesso {
    public static function respostaProcesso(string $mensagem, bool $status = false, array $dados = []){
        return ['resposta'=>$status, "mensagem"=>$mensagem, "dados"=>$dados];
    }
}
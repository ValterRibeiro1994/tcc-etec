<?php

class RespostaProcesso {
    public static function respostaProcesso(string $mensagem, bool $status = false, array $dados = [], string $formato = "json"){
        return ['resposta'=>$status, "mensagem"=>$mensagem, "dados"=>$dados, "formato" => $formato];
    }

}
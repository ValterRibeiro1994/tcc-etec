<?php

class Response {
    public static function response(string $mensagem, bool $status, string $formato = "json", array $dados = []){
        return [
            'formato' => $formato,
            'mensagem' => $mensagem,
            'status' => $status,
            'dados' => $dados
            ];
    }
}
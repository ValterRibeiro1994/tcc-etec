<?php

class RespostaProcesso {
    public static function resposta(string $mensagem, bool $resposta = false, array $dados = [], string $formato = "json", string $pagina = ""){
        $resposta_ = ['resposta' => $resposta, 'mensagem' => $mensagem, 'dados' => $dados, 'formato' => $formato, 'pagina' => $pagina];
        return $resposta_;
    }

    public static function salvarErro(Throwable $erro): array {
        error_log(sprintf(
            'Erro da aplicação: %s em %s:%d (código %s)',
            $erro->getMessage(),
            $erro->getFile(),
            $erro->getLine(),
            (string) $erro->getCode()
        ));

        return [
            "mensagem" => "Erro interno do servidor.",
            "codigo" => (string) $erro->getCode()
        ];
    }
}
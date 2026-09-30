<?php
include_once("app/config.php");
include_once("app/autoload.php");
include_once("app/roteador.php");

new AutoLoadFiles();

try {
    $rotas = new Roteador();
    $resposta = $rotas->getResposta();

    $formato = $resposta['formato'];
    $include = false;
    if ($formato === "html") {
        $header = "Content-Type: text/html; charset=utf-8";
        $include = true;
    } else if ($formato == "json"){
        $header = "Content-Type: application/json; charset=utf-8";
    } else {
        throw new Exception("Formato $formato inválido para resposta");
    }

    if ($include){
        header($header);
        include_once($resposta['mensagem']);
        exit();
    }

    header($header);
    echo json_encode($resposta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit();

} catch (Exception $erro) {
    $dados_erro = [
        "mensagem"=> "Erro: " . $erro->getMessage(),
        "arquivo"=>  "Arquivo: " . $erro->getFile(),
        "linha" => "Linha: " . $erro->getLine(),
        "codigo"=> "Trecho Código: " . $erro->getCode(),
    ];
    
    $resposta = RespostaProcesso::respostaProcesso($dados_erro['mensagem'], false, $dados_erro);
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode($resposta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}

<?php
include_once("app/config.php");
include_once("app/autoload.php");
include_once("app/roteador.php");

new AutoLoadFiles();

$rotas = new Roteador();
$resposta_servidor = $rotas->getResposta();

if ($resposta_servidor['resposta']){
    if ($resposta_servidor['dados']['tipo-resposta'] == "text/html"){
        include_once($resposta_servidor['dados']['pagina']);
        exit();
    } else {
        header("Content-Type: application/json; charset=utf-8");
        echo json_encode($resposta_servidor['dados']['resposta-processo'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit();
    }
} else {
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode($resposta_servidor['dados']['resposta-processo'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit();
}

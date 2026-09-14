<?php

include_once("../relate/app/autoload.php");
include_once("../relate/app/config.php");

new AutoLoadFiles();

Sessao::carregarEstilos();
Sessao::carregarScripts();

$request = new Request();
$response = $request->getResponse();

if ($response['formato'] == "html"){
    header("Content-Type: text/html; charset=utf-8");
    echo($response['mensagem']);
    exit();
} else if ($response['formato'] == "json"){
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit();
}

?>

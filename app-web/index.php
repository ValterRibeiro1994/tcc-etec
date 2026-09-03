<?php
include_once("app/config.php");
include_once("app/autoload.php");
include_once("app/roteador.php");

new AutoLoadFiles();

// capturar a requisição recebida do cliente
$dados = [];

// verificar o recurso solicitado atraves do URI
$uri = $_SERVER['REQUEST_URI'];

// verificar o tipo de requisição
$tipo = $_SERVER['REQUEST_METHOD'];

// capturar os dados da requisição se houver
if ($tipo == "GET"){
    $dados = $_GET;
} else if ($tipo == "POST") {
    $dados = $_POST;
} else {
    throw new Exception("Error Processing Request", 1);
    // criar um sistema de erro para essas situações
}
    
// validar a requisição recebida
// -- criar função para garantir que a uri não possua caracteres maliciosos
$requisicao = [
    "uri" => $uri,
    "metodo" => $tipo,
    "dados" =>$dados,
    ];  

// encaminhar a requisição recebida para o controller correspondente
$roteador = new Roteador($requisicao);

// esperar a resposta do controller
$resposta = $roteador->getResposta();

if ($resposta['dados']['formato'] == "html"){
    include_once($resposta['dados']['pagina']);
} else {
    echo json_encode($resposta['dados']);
}
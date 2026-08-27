<?php
echo("<br>Iniciando index.php<br>");
include_once("app/config.php");
include_once("app/autoload.php");
include_once("app/roteador.php");
echo("<br> dependencias instaladas<br>");

new AutoLoadFiles();

// capturar a requisição recebida do cliente
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
// -- criar um roteador para encaminhar as requisições

$roteador = new Roteador($requisicao);
// esperar a resposta do controller
// enviar resposta para o cliente


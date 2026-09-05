<?php
include_once("app/config.php");
include_once("app/autoload.php");
include_once("app/roteador.php");

new AutoLoadFiles();

$rotas = new Roteador();
$resposta_servidor = $rotas->getResposta();
if ($resposta_servidor['resposta']){
    echo("<br><h3>Comunicação realizada com sucesso</h3><br><hr>");
    var_dump($resposta_servidor['dados']);
} else {
    echo("<br><h3>Falha na comunicação com servidor</h3><br><hr>");
    var_dump($resposta_servidor);
}

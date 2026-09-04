<?php
include_once("app/config.php");
include_once("app/autoload.php");
include_once("app/roteador.php");

new AutoLoadFiles();

try {
    $roteador = new Roteador();
} catch (Exception $e){
    echo($e->getMessage());
}


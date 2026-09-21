<?php

abstract class Usuario {
    abstract function getId(PDO $conexao);
    abstract function getNome();
    abstract function getSobrenome();
    abstract function getEmail();
    abstract function getCpf();
    abstract function getSenha();

}
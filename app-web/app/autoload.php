<?php

class AutoLoadFiles
{
    private array $pastas_acessiveis = [
        "controller/",
        "database/",
        "database/repositorios/",
        "model/",
        "model/entidades/",
        "model/objects-values/",
        "recursos/jwt/",
        "recursos/jwt/php-jwt/",
        "recursos/jwt/php-jwt/src/",
    ];

    public function __construct()
    {
        spl_autoload_register([$this, 'carregarClasse']);
    }

    private function carregarClasse(string $classe): void
    {
        $prefixo = "Firebase\\JWT\\";
        if (strncmp($classe, $prefixo, strlen($prefixo)) === 0) {
            $arquivo = __DIR__ . DIRECTORY_SEPARATOR
                . "recursos" . DIRECTORY_SEPARATOR
                . "jwt" . DIRECTORY_SEPARATOR
                . "php-jwt" . DIRECTORY_SEPARATOR
                . "src" . DIRECTORY_SEPARATOR
                . str_replace("\\", DIRECTORY_SEPARATOR, substr($classe, strlen($prefixo)))
                . ".php";

            if (is_file($arquivo)) {
                require_once $arquivo;
                return;
            }
        }

        foreach ($this->pastas_acessiveis as $pasta) {

            $arquivo = __DIR__ . "/" . $pasta . lcfirst($classe) . ".php";

            if (is_file($arquivo)) {
                require_once $arquivo;
                return;
            }
        }
    }
}
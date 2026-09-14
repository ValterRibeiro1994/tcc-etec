<?php

class AutoLoadFiles
{
    private array $pastas_acessiveis = [
        "/",
        "paginas/",
        "controles/",
        "banco-de-dados/",
        "modelos/",
        "recursos/",
        "sessao/",
        "modelos/entidades/",
        "modelos/objects-values/",
    ];

    public function __construct()
    {
        spl_autoload_register([$this, 'carregarClasse']);
    }

    private function carregarClasse(string $classe): void
    {
        foreach ($this->pastas_acessiveis as $pasta) {

            $arquivo = __DIR__ . "/" . $pasta . lcfirst($classe) . ".php";

            if (is_file($arquivo)) {
                require_once $arquivo;
                return;
            }
        }
    }
}
<?php

abstract class Usuario {
    public DadosPessoais $dados_pessoais;
    public function __construct(DadosPessoais $dados_pessoais){
        $this->dados_pessoais = $dados_pessoais;
    }

    abstract function getNome();
    abstract function getSobrenome();


}
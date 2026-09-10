<?php

class RepresentantePrefeitura extends Usuario {

    public function __construct(DadosPessoais $dados_pessoais)
    {
        parent::__construct($dados_pessoais);
    }

    public function getNome() {
        return $this->dados_pessoais->getNome();
    }

    public function getSobrenome() {
        return $this->dados_pessoais->getSobrenome();
    }
}

?>
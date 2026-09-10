<?php

class Denunciante extends Usuario {
    // para não ter muitos parametros se pode reduzir nome, sobrenome, email e cpf em uma classe
    // para esses dados pessoais
    private Senha $senha;

    public function __construct(DadosPessoais $dadosDenunciante, Senha $senha) {
        parent::__construct($dadosDenunciante);
        $this->senha = $senha;
    }

    public function getNome() {
        return $this->dados_pessoais->getNome();
    }

    public function getSobrenome() {
        return $this->dados_pessoais->getSobrenome();
    }

    public function getEmail() {
        return $this->dados_pessoais->getEmail();
    }

    public function getCpf() {
        return $this->dados_pessoais->getCpf();
    }

    public function getSenha() {
        return $this->senha->getSenha();
    }
    
}
?>
<?php

class Denunciante {
    // para não ter muitos parametros se pode reduzir nome, sobrenome, email e cpf em uma classe
    // para esses dados pessoais
    private DadosPessoais $dadosDenunciante;
    private Senha $senha;
    private int $id;

    public function __construct(DadosPessoais $dadosDenunciante, Senha $senha) {
        $this->dadosDenunciante = $dadosDenunciante;
        $this->senha = $senha;
    }

    public function getNome() {
        return $this->dadosDenunciante->getNome();
    }

    public function getSobrenome() {
        return $this->dadosDenunciante->getSobrenome();
    }

    public function getEmail() {
        return $this->dadosDenunciante->getEmail();
    }

    public function getCpf() {
        return $this->dadosDenunciante->getCpf();
    }

    public function getSenha() {
        return $this->senha->getSenha();
    }

    public function setId(int $novo_id){
        $this->id = $novo_id;
    }


    
}
?>
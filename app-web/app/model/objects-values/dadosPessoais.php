<?php

class DadosPessoais {
    private Nome $nome;
    private Sobrenome $sobrenome;
    private Email $email;
    private Cpf $cpf;

    public function __construct(Nome $nome, Sobrenome $sobrenome, Email $email, Cpf $cpf) {
        $this->nome = $nome;
        $this->sobrenome = $sobrenome;
        $this->email = $email;
        $this->cpf = $cpf;
    }

    public function getNome(): string {
        return $this->nome->getNome();
    }

    public function getSobrenome(): string {
        return $this->sobrenome->getSobrenome();
    }

    public function getEmail(): string {
        return $this->email->getEmail();
    }

    public function getCpf(): string {
        return $this->cpf->getCpf();
    } 

    public function setNome(Nome $nome): void {
        $this->nome = $nome;
    }

    public function setSobrenome(Sobrenome $sobrenome): void {
        $this->sobrenome = $sobrenome;
    }

    public function setEmail(Email $email): void {
        $this->email = $email;
    }

    public function setCpf(Cpf $cpf): void {
        $this->cpf = $cpf;
    }
}
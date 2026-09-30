<?php

class DadosPessoais {
    private Nome|null $nome;
    private Sobrenome|null $sobrenome;
    private Email|null $email;
    private Cpf|null $cpf;

    public function __construct(Nome|null $nome = null, Sobrenome|null $sobrenome = null, Email|null $email = null, Cpf|null $cpf = null) {
        $this->nome = $nome;
        $this->sobrenome = $sobrenome;
        $this->email = $email;
        $this->cpf = $cpf;
    }

    public function getNome(): string|null {
        return $this->nome->getNome();
    }

    public function getSobrenome(): string|null {
        return $this->sobrenome->getSobrenome();
    }

    public function getEmail(): string|null {
        return $this->email->getEmail();
    }

    public function getCpf(): string|null {
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
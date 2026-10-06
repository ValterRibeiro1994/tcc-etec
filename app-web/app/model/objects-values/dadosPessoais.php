<?php

class DadosPessoais {
    private Nome|null $nome;
    private Sobrenome|null $sobrenome;
    private Email|null $email;
    private Cpf|null $cpf;

    public function __construct(Nome|null $nome = null, Sobrenome|null $sobrenome = null, Email|null $email = null, Cpf|null $cpf = null) {
        if ($nome != null) $this->nome = $nome;
        if ($sobrenome != null) $this->sobrenome = $sobrenome;
        if ($email != null) $this->email = $email;
        if ($cpf != null) $this->cpf = $cpf;
    }

    public function getNome(): string|null {
        if ($this->nome == null) return null;
        return $this->nome->getNome();
        }
        
        public function getSobrenome(): string|null {
        if ($this->sobrenome == null) return null;
        return $this->sobrenome->getSobrenome();
    }

    public function getEmail(): string|null {
        if ($this->sobrenome == null) return null;
        return $this->email->getEmail();
    }

    public function getCpf(): string|null {
        if ($this->cpf == null) return null;
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
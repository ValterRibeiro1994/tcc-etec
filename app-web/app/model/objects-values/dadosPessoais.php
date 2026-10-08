<?php

class DadosPessoais {
    private Perfil $perfil;
    private Nome $nome;
    private Sobrenome $sobrenome;
    private Email $email;

    public function __construct(Perfil $perfil = null, Nome $nome = null, Sobrenome $sobrenome = null, Email $email = null) {
        if ($perfil != null) $this->perfil = $perfil;
        if ($nome != null) $this->nome = $nome;
        if ($sobrenome != null) $this->sobrenome = $sobrenome;
        if ($email != null) $this->email = $email;
    }


    public function getPerfil(): string {
        if ($this->perfil == null){
            $this->setPerfil("Não informado");
        }

        return $this->perfil->getPerfil();
    }

    public function getNome(): string {
        if ($this->nome == null) {
            $this->setNome("Não informado");
        }
        return $this->getNome();
    }
        
        public function getSobrenome(): string {
        if ($this->sobrenome == null) {
            $this->setSobrenome("Sobrenome não informado");
        }
        return $this->sobrenome->getSobrenome();
    }

    public function getEmail(): string {
        if ($this->email == null){
            $this->setEmail("Email não informado");
        };
        return $this->email->getEmail();
    }

    // public function getCpf(): string {
    //     if ($this->cpf == null){
    //         $this->setCpf("Cpf não informado");
    //     }
    //     return $this->cpf->getCpf();
    // } 


    public function setPerfil(Perfil|string  $perfil){
        if (is_string($perfil)){
            $perfil = new Perfil($perfil);
        }
        $this->perfil = $perfil;
    }

    public function setNome(Nome|string $nome): void {
        if (is_string($nome)){
            $nome = new Nome($nome);
        }
        $this->nome = $nome;
    }

    public function setSobrenome(Sobrenome|string $sobrenome): void {
        if (is_string($sobrenome)){
            $sobrenome = new Sobrenome($sobrenome);
        }    
        $this->sobrenome = $sobrenome;
    }

    public function setEmail(Email|string $email): void {
        if (is_string($email)){
            $email = new Email($email);
        }
        $this->email = $email;
    }

    // public function setCpf(Cpf|string $cpf): void {
    //     if (is_string($cpf)){
    //         $cpf = new Cpf($cpf);
    //     }
    //     $this->cpf = $cpf;
    // }
}
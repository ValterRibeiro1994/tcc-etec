<?php

class DadosPessoais {
    private Perfil $perfil;
    private Nome $nome;
    private Sobrenome $sobrenome;
    private Email $email;

    public function __construct(Perfil $perfil = null, Nome $nome = null, Sobrenome $sobrenome = null, Email $email = null) {
        $this->perfil = new Perfil() ?? $perfil;
        $this->nome = new Nome() ?? $nome;
        $this->sobrenome = new Sobrenome() ?? $sobrenome;
        $this->email = new Email() ?? $email;
    }


    public function getPerfil(): string {
        return $this->perfil->getPerfil();
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


}
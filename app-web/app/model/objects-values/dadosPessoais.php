<?php

class DadosPessoais {
    private Perfil $perfil;
    private Nome $nome;
    private Sobrenome $sobrenome;
    private Email $email;

    public function __construct(Perfil $perfil = null, Nome $nome = null, Sobrenome $sobrenome = null, Email $email = null) {
        $this->perfil = new Perfil();
        if ($perfil != null) $this->perfil = $perfil;

        $this->nome = new Nome();
        if ($nome != null) $this->setNome($nome);

        $this->sobrenome = new Sobrenome();
        if ($sobrenome != null) $this->setSobrenome($sobrenome);

        $this->email = new Email();
        if ($email != null) $this->setEmail($email);
    }


    public function getPerfil(): string {
        if ($this->perfil == null){
            $this->setPerfil("visitante");
        }

        return $this->perfil->getPerfil();
    }

    public function getNome(): string {
        if ($this->nome == null) {
            $this->setNome("visitante");
        }
        return $this->nome->getNome();
    }
        
    public function getSobrenome(): string {
        if ($this->sobrenome == null) {
            $this->setSobrenome("visitante");
        }
        return $this->sobrenome->getSobrenome();
    }

    public function getEmail(): string {
        if ($this->email == null){
            $this->setEmail("visitante@gmail.com");
        };
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

        if (!$email->validarEmail()){
            throw new InvalidArgumentException("Cliente: Email invalido");
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
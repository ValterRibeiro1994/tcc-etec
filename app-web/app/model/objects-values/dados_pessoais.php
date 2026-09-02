<?php

class DadosPessoais {
    private Nome $nome;
    private Sobrenome $sobrenome;
    private Email $email;
    private Cpf $cpf;

    public function __construct(Nome $nome, Sobrenome $sobrenome, Email $email, Cpf $cpf){
        $this->nome = $nome;
        $this->sobrenome = $sobrenome;
        $this->email = $email;
        $this->cpf = $cpf;
    }

    
}
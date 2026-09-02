<?php

class DadosPessoais {
    private string $nome;
    private string $sobrenome;
    private string $email;
    private string $cpf;

    public function __construct(string $nome, string $sobrenome, string $email, string $cpf){
        $cpf = new Cpf($cpf); // valida e limpa o cpf
        $email; // criar classe para validação de email
        // definir regras para o nome no banco
        // definir regras para sobrenome
        
    }
}
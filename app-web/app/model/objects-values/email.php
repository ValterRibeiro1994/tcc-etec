<?php

class Email {
    private string $email;
    public function __construct(string $email){
        $this->validarEmail($email);
         
    }

    private function validarEmail(string $email, int $limite = 120){
        if (strlen($email) > $limite){
            throw new Exception("Limite de caracteres excedido");
        }

        // sanitiza o email
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);

        // valida o email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)){
            throw new Exception("Email Inválido");
        }
        $this->email = $email;
    }

    public function getEmail() {
        return $this->email;
    }
}

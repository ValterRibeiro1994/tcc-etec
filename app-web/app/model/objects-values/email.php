<?php

class Email {
    private string $email;
    public function __construct(string $email = "visitante@gmail.com"){
        $this->email = $this->sanitizarEmail($email);;
    }

    public function validarEmail(): void {
        $limite = 120;
        $email = $this->sanitizarEmail($this->email);
        if (strlen($email) > $limite) {
            throw new InvalidArgumentException("Cliente: Email invalido");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Cliente: Email invalido");
        }

    }

    public function setEmail(string $email){
        $this->email = $this->sanitizarEmail($email);
    }

    public function getEmail(): string {
        return $this->email;
    }
    
    private function sanitizarEmail(string $email){
        return filter_var($email, FILTER_SANITIZE_EMAIL);
    }
}

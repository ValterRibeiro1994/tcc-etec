<?php

class Email {
    private string $email;
    public function __construct(string $email){
        $email = $this->sanitizarEmail($email);
        $this->validarEmail($email);
        $this->email = $email;
    }

    private function validarEmail(string $email, int $limite = 120): void {
        if (strlen($email) > $limite) throw new Exception("Email inválido");
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new Exception("Email Inválido");
    }

    public function getEmail(): string {
        return $this->email;
    }

    private function sanitizarEmail(string $email){
        return filter_var($email, FILTER_SANITIZE_EMAIL);
    }
}

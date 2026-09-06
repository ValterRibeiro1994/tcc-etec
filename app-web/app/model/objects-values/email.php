<?php

class Email {
    private string $email;
    public function __construct(string $email){
        if ($this->validarEmail($email)){
            $this->email = $email;
        }
    }

    private function validarEmail(string $email){
        return true;
    }

    public function getEmail() {
        return $this->email;
    }
}

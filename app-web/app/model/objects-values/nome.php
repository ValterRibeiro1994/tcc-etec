<?php

class Nome {
    private string|null $nome;
    public function __construct(string $nome = null, int $limite = 20){
        if ($nome !=  null){
            $nome = htmlspecialchars($nome, ENT_QUOTES, 'UTF-8');
            $n = strlen($nome);
            if ($n > $limite || $n < 3) throw new Exception("Nome Invalido");
            $this->nome = $nome;
        } else {
            $this->nome = $nome;
        }
    }

    public function getNome(): string|null {
        return $this->nome;
    }
}

class Sobrenome {
    private string $sobrenome;
    public function __construct(string $sobrenome = null, int $limite = 60){
        if ($sobrenome != null){
            $sobrenome = htmlspecialchars($sobrenome, ENT_QUOTES, 'UTF-8');
            $n = strlen($sobrenome);
            if ($n > $limite || $n < 3) throw new Exception("Sobrenome Invalido");
            $this->sobrenome = $sobrenome;
        } else {
            $this->sobrenome = $sobrenome;
        }
    }

    public function getSobrenome(): string|null {
        if ($this->sobrenome == null) return "informar sobrenome";
        return $this->sobrenome;
    }
}

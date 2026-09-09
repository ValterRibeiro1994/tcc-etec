<?php

class Endereco {
    private string $logradouro;
    private string $bairro; 
    private string $numero;
    private string $cidade;
    private string $estado;
    private string $cep;
    
    // SETTERS
    public function setLogradouro(string $logradouro){
        $logradouro = trim($logradouro); // remove excesso de espaços em branco
        if (empty($logradouro)){
            throw new InvalidArgumentException("Logradouro Invalido");
        }

        $this->logradouro = $logradouro;
    }
 
    public function setBairro(string $bairro){
        $bairro = trim($bairro);
        if (empty($bairro)){
            throw new InvalidArgumentException("Bairro Invalido");
        }

        $this->bairro = $bairro;
    }

    public function setNumero(string $numero){
        $numero = trim($numero);
        if (empty($numero)){
            throw new InvalidArgumentException("Numero Invalido");
        }

        $this->numero = $numero;
    }

    public function setCidade(string $cidade){
        $cidade = trim($cidade);
        if (empty($cidade)){
            throw new InvalidArgumentException("Cidade Invalida");
        }

        $this->cidade = $cidade;
    }

    public function setEstado(string $estado){
        $estado = trim($estado);
        if (empty($estado)){
            throw new InvalidArgumentException("Estado Invalido");
        }

        $this->estado = $estado;
    }

    public function setCep(string $cep){
        $cep = trim($cep);
        if (empty($cep)){
            throw new InvalidArgumentException("Cep Invalido");
        }

        $this->cep = $cep;
    }

    // GETTERS
    public function getLogradouro(): string {
        return $this->logradouro;
    }

    public function getBairro(): string {
        return $this->bairro;
    }

    public function getNumero(): string {
        return $this->numero;
    }

    public function getCidade(): string {
        return $this->cidade;
    }

    public function getEstado(): string {
        return $this->estado;
    }

    public function getCep(): string {
        return $this->cep;
    }
    
}

?>

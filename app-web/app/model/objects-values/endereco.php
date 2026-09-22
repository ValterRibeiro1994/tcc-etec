<?php

class Endereco {
    private string $logradouro;
    private string $bairro; 
    private string $numero;
    private string $cidade;
    private string $estado;
    private string $cep;
    
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
        if (empty($numero)) throw new InvalidArgumentException("Numero Invalido");
        $this->numero = $numero;
    }

    public function setCidade(string $cidade, int $limite = 50){
        $cidade = strtoupper(trim($cidade));
        if (empty($cidade)) throw new InvalidArgumentException("Cidade Não informada");
        if (strlen($cidade) > $limite) throw new Exception("CIDADE: Limite de caracteres excedido");
        $this->cidade = $cidade;
    }

    public function setEstado(string $estado, int $limite = 2){
        $estado = strtoupper(trim($estado));
        if (empty($estado)) throw new InvalidArgumentException("Estado Invalido");
        if (strlen($estado) > $limite) throw new Exception("Limite de caracteres inválido");
        $this->estado = $estado;
    }

    public function setCep(string $cep, int $limite = 8){
        $cep_limpo = "";
        $cpf = trim($cep);
        $n = strlen($cep);
        for ($x = 0; $x < $n; $x++){
            if (ctype_digit($cpf[$x])) $cep_limpo .= $cpf[$x];
        }

        if (strlen($cep_limpo) != $limite) throw new Exception("Limite de caracteres inválidos");
        $this->cep = $cep_limpo;
    }

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

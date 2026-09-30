<?php

class Endereco {
    private string|null $logradouro = null;
    private string|null $bairro = null; 
    private string|null $numero = null;
    private string|null $cidade = null;
    private string|null $estado = null;
    private string|null $cep = null;

    public function __construct(string $cidade = null, string $estado = null){
        if ($cidade !== null) $this->setCidade($cidade);
        if ($estado !== null) $this->setEstado($estado);

    }
    
    
    public function setLogradouro(string $logradouro){
        $logradouro = trim($logradouro); // remove excesso de espaços em branco
        if (empty($logradouro)) throw new Exception("Logradouro Invalido");
        $this->logradouro = $logradouro;
    }
 
    public function setBairro(string $bairro){
        $bairro = trim($bairro);
        if (empty($bairro)) throw new Exception("Bairro Invalido");
        $this->bairro = $bairro;
    }

    public function setNumero(string $numero){
        $numero = trim($numero);
        if (empty($numero)) throw new Exception("Numero Invalido");
        $this->numero = $numero;
    }

    public function setCidade(string $cidade, int $limite = 50){
        $cidade = strtoupper(trim($cidade));
        if (empty($cidade)) throw new Exception("Cidade Invalida");
        if (strlen($cidade) > $limite) throw new Exception("Cidade invalida");
        $this->cidade = $cidade;
    }

    public function setEstado(string $estado, int $limite = 2){
        $estado = strtoupper(trim($estado));
        if (empty($estado)) throw new Exception("Estado Invalido");
        if (strlen($estado) > $limite) throw new Exception("Estado inválido");
        $this->estado = $estado;
    }

    public function setCep(string $cep, int $limite = 8){
        $cep_limpo = ""; // armazena apenas os números do cpf
        $cpf = trim($cep); // remove excesso de espaços em branco
        
        // loop para capturar apenas os espaços em branco
        $n = strlen($cep);
        for ($x = 0; $x < $n; $x++) if (ctype_digit($cpf[$x])) $cep_limpo .= $cpf[$x];
        if (strlen($cep_limpo) != $limite) throw new Exception("Cep Invalido");

        // salva os numeros do cpf
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

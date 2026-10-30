<?php

class Endereco {
    private string|null $rua = null;
    private string|null $bairro = null; 
    private string|null $numero = null;
    private string|null $complemento = null;
    private string|null $cidade = null;
    private string|null $estado = null;
    private string|null $cep = null;

    public function __construct(string|null $cidade = null, string|null $estado = null){
        if ($cidade !== null) $this->setCidade($cidade);
        if ($estado !== null) $this->setEstado($estado);

    }

    // funções auxiliares
    private function validar(string $texto, int $limite){
        // função para validar se uma string está vazia e seu limite
        return (!empty(mb_trim($texto, encoding: "utf-8")) && mb_strlen($texto, encoding: "utf-8") <= $limite);
    }
    
    // ==================================================================
    // ALTERADO: o logradouro NÃO é mais informado diretamente.
    // Ele é montado (getLogradouro) a partir de rua, número, complemento e bairro.
    // O método setLogradouro() foi removido e substituído por setRua().
    // ==================================================================
    public function setRua(string $rua, int $limite = 100){
        $rua = trim(preg_replace('/\s+/', ' ', $rua)); // remove excesso de espaços em branco
        if ($rua === "") throw new Exception("Rua Invalida");
        if (mb_strlen($rua) > $limite) throw new Exception("Rua muito longa (máximo de $limite caracteres)");
        $this->rua = $rua;
    }
 
    public function setBairro(string $bairro, int $limite = 80, bool $validar = false){
        if ($validar){
            if (!$this->validar($bairro, $limite)){
                throw new Exception("Cliente: Bairro invalido");
            }
            $this->bairro = $bairro;
            return;
        }

        $this->bairro = $bairro;
    }

    public function setNumero(string $numero, int $limite = 30, bool $validar = false){
        if (!$validar){
            $this->numero = $numero;
            return;
        }

        if (!$this->validar($numero, $limite)) throw new Exception("Cliente: Endereço, número invalido");
        $this->numero = $numero;
        return;
    }

    // O complemento é opcional: texto vazio ou null deixam o complemento sem valor
    public function setComplemento(string $complemento, int $limite = 100){
        $complemento = trim(preg_replace('/\s+/', ' ', (string) $complemento));
        if ($complemento === "") {
            $this->complemento = null;
            return;
        }
        if (mb_strlen($complemento) > $limite) throw new Exception("Complemento muito longo (máximo de $limite caracteres)");
        $this->complemento = $complemento;
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
        $cep_limpo = ""; // armazena apenas os números do cep
        $cep = trim($cep); // remove excesso de espaços em branco
        
        // loop para capturar apenas os números
        $n = strlen($cep);
        for ($x = 0; $x < $n; $x++) if (ctype_digit($cep[$x])) $cep_limpo .= $cep[$x];
        if (strlen($cep_limpo) != $limite) throw new Exception("Cep Invalido");

        // salva os numeros do cep
        $this->cep = $cep_limpo;
    }

    public function getRua(): string {
        if ($this->rua === null) throw new Exception("Rua não informada");
        return $this->rua;
    }

    /**
     * LOGRADOURO = junção dos campos de endereço, no formato:
     *   "Rua das Flores, 123, Apto 4 - Centro"
     *   (rua, número, complemento [se existir] - bairro)
     * Cidade, estado e CEP continuam em campos próprios.
     * O limite de 150 caracteres é o tamanho da coluna logradouro_denuncia.
     */
    public function getLogradouro(): string {
        $partes = [$this->getRua(), $this->getNumero()];
        if ($this->complemento !== null) $partes[] = $this->complemento;

        $logradouro = implode(", ", $partes) . " - " . $this->getBairro();
        if (mb_strlen($logradouro) > 150) throw new Exception("Endereço muito longo: reduza a rua, o complemento ou o bairro");
        return $logradouro;
    }

    public function getBairro(): string {
        if ($this->bairro === null) return "";
        return $this->bairro;
    }

    public function getNumero(): string {
        if ($this->numero === null) return "";
        return $this->numero;
    }

    public function getComplemento(): string {
        if ($this->complemento === null) return "";
        return $this->complemento;
    }

    public function getCidade(): string {
        if ($this->cidade === null) {
            return "";
        }
        return $this->cidade;
    }

    public function getEstado(): string {
        if ($this->estado === null) return "";// não informado
        return $this->estado;
    }

    public function getCep(): string {
        if ($this->cep === null) return "";
        return $this->cep;
    }
    
}

?>

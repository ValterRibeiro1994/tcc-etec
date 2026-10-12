<?php
class Data {
    private string|null $data = null;

    public function __construct(string|null $data = null)
    {
        // ALTERADO: antes o construtor não guardava o valor ($this->data;)
        if ($data !== null) $this->setData($data);
    }

    public function getData(){
        if ($this->data === null) throw new Exception("Data não enviada");
        return $this->data;
    }

    // Recebe a data no formato do <input type="date"> (AAAA-MM-DD)
    // e guarda como "AAAA-MM-DD HH:MM:SS" (coluna datetime do banco)
    public function setData(string $data){
        $data = trim($data);

        $objeto = DateTime::createFromFormat('!Y-m-d', $data);
        if (!$objeto || $objeto->format('Y-m-d') !== $data) throw new Exception("Data inválida");

        if ($objeto > new DateTime('today')) throw new Exception("A data da ocorrência não pode estar no futuro");

        $this->data = $objeto->format('Y-m-d') . ' ' . date('H:i:s');
    }
}

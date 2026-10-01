<?php

class Descricao {
    private string|null $titulo;
    private Data|null $data;
    private string|null $categoria;
    private string|null $texto;

    public function __construct(string|null $titulo = null, Data|null $data, string|null $categoria = null, string|null $texto = null){
            $this->titulo = $titulo;
            $this->data = $data;
            $this->categoria = $categoria;
            $this->texto = $texto;
    }

    public function getData(): string {
        if ($this->data == null) throw new Exception("Data não enviada para descrição");
        return $this->data->getData();
    }

    public function getTitulo(): string {
        if ($this->titulo == null) throw new Exception("Titulo não enviado para descrição");
        return $this->titulo;
    }

    public function getTexto(): string {
        if ($this->texto == null) throw new Exception("Texto não enviado para descrição");
        return $this->texto;
    }
}
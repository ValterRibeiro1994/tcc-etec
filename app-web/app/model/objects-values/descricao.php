<?php

class Descricao {
    private string|null $titulo = null;
    private Data|null $data = null;
    private string|null $categoria = null;
    private string|null $texto = null;

    // ALTERADO: todos os parâmetros agora têm valor padrão e os textos são validados
    public function __construct(string|null $titulo = null, Data|null $data = null, string|null $categoria = null, string|null $texto = null){
            if ($titulo !== null) $this->setTitulo($titulo);
            if ($categoria !== null) $this->setCategoria($categoria);
            if ($texto !== null) $this->setTexto($texto);
            $this->data = $data;
    }

    // limites das colunas: titulo_denuncia varchar(60), nome_categoria varchar(30)
    public function setTitulo(string $titulo, int $limite = 60){
        $titulo = trim($titulo);
        if ($titulo === "") throw new Exception("Título inválido");
        if (mb_strlen($titulo) > $limite) throw new Exception("Título muito longo (máximo de $limite caracteres)");
        $this->titulo = $titulo;
    }

    public function setCategoria(string $categoria, int $limite = 30){
        $categoria = trim($categoria);
        if ($categoria === "") throw new Exception("Categoria inválida");
        if (mb_strlen($categoria) > $limite) throw new Exception("Categoria inválida");
        $this->categoria = $categoria;
    }

    public function setTexto(string $texto, int $limite = 2000){
        $texto = trim($texto);
        if ($texto === "") throw new Exception("Descrição inválida");
        if (mb_strlen($texto) > $limite) throw new Exception("Descrição muito longa (máximo de $limite caracteres)");
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

    // ADICIONADO: a categoria precisa ser lida para gravar nome_categoria
    public function getCategoria(): string {
        if ($this->categoria == null) throw new Exception("Categoria não enviada para descrição");
        return $this->categoria;
    }

    public function getTexto(): string {
        if ($this->texto == null) throw new Exception("Texto não enviado para descrição");
        return $this->texto;
    }
}

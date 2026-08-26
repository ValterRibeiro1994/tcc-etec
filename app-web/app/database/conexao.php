<?php

class ConexaoPDO {
    private string $usuario;
    private string $senha;
    private string $host;
    private string $database;
    
    public function __construct(string $usuario, string $senha, string $host, string $database)
    {
        $this->usuario = $usuario;
        $this->senha = $senha;
        $this->host = $host;
        $this->database = $database;
    }

    public function getConexao(): PDO {
        $conexao = new PDO("mysql:host=$this->host;dbname=$this->database;charset=utf8", $this->usuario, $this->senha);
        $conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conexao;
    }
}
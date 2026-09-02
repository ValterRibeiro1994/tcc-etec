<?php 
class ConexaoPDO { 
    private string $usuario; 
    private string $senha; 
    private string $host; 
    private string $database; 

    public function __construct() {
        $this->usuario = $GLOBALS['usuario_banco']; 
        $this->senha = $GLOBALS['senha_banco']; 
        $this->host = $GLOBALS['host_banco']; 
        $this->database = $GLOBALS['database_banco']; 
    }

    public function getConexao(): PDO { 
        $conexao = new PDO("mysql:host={$this->host};dbname={$this->database};charset=utf8", $this->usuario, $this->senha); 
        $conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
        return $conexao; 
    }

    
}

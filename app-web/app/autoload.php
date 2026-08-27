<?php 

class AutoLoadFiles {
    private array $pastas_acessiveis = [
        "controller/", 
        "database/", "database/repositorios/", 
        "model/", "model/entidades/", "model/objects-values/",       
        ];

    public function __construct(){
        echo("<br>Iniciando autoload");
        $n = count($this->pastas_acessiveis);
        for ($i=0; $i < $n; $i++) { 
            $pasta_de_arquivos = $GLOBALS['caminho_pasta_projeto'] . $this->pastas_acessiveis[$i];
            $arquivos = scandir($pasta_de_arquivos);
            foreach ($arquivos as $arquivo){
                if (str_contains($arquivo, ".php")){
                    $caminho_do_arquivo = $pasta_de_arquivos . $arquivo;
                    if (file_exists($caminho_do_arquivo)){
                        require_once($caminho_do_arquivo);
                    } 
                }
            }
        }

    }

     
}
<?php

// caminhos de diretorio principal do projeto
$GLOBALS['caminho_raiz_projeto'] = "/app-web/";
$GLOBALS['caminho_pasta_projeto'] = __DIR__ . "/app/";

// dados de acesso ao banco de dados
$GLOBALS['usuario_banco'] = "root";
$GLOBALS['senha_banco'] = "";
$GLOBALS['host_banco'] = "localhost";
$GLOBALS['database_banco'] = "relate_sbc";

//tabelas do banco de dados
$GLOBALS['usuario'] = "tb_usuario";
<<<<<<< Updated upstream
$GLOBALS['municipe'] = "tb_municipe";
$GLOBALS['representante'] = "tb_representante";
$GLOBALS['resposta'] = "tb_resposta";
$GLOBALS['denuncia'] = "tb_denuncia";
$GLOBALS['categorias'] = "tb_categoria";
=======
$GLOBALS['denuncia'] = "tb_denuncia";
$GLOBALS['categorias'] = "tb_categoria";    

// debug
$GLOBALS['debug'] = true;

// tempo de sessao
$GLOBALS['tempo-sessao'] = 6000000;

$GLOBALS['formato-data-hora'] = 'Y-m-d H:i:s';
>>>>>>> Stashed changes

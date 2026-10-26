<?php
// caminhos
$GLOBALS['raiz-app'] =  '/app-web/';

// dados de acesso ao banco de dados
$GLOBALS['usuario_banco'] = "root";
$GLOBALS['senha_banco'] = "";
$GLOBALS['host_banco'] = "localhost";
$GLOBALS['database_banco'] = "relate_sbc";

//tabelas do banco de dados
$GLOBALS['usuario'] = "tb_usuario";
$GLOBALS['denuncia'] = "tb_denuncia";
$GLOBALS['categorias'] = "tb_categoria";    

// debug
$GLOBALS['debug'] = true;

// tempo de sessao
$GLOBALS['tempo-sessao'] = 3000;

// formato padrão para data e hora do sistema
$GLOBALS['formato-data-hora'] = 'Y-m-d H:i:s';

// chave temporaria para token
$GLOBALS['token-chave'] = "projeto-relate-tcc-etec-lauro-gomes";
$GLOBALS['tkn-algo'] = "HS256"; // algoritmo de criptografia do token
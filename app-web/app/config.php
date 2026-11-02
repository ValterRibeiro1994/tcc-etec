<?php
// caminhos
$GLOBALS['raiz-app'] =  '/';

// dados de acesso ao banco de dados
$GLOBALS['usuario_banco'] = "if0_43311871";
$GLOBALS['senha_banco'] = "c2bNRADchtX";
$GLOBALS['host_banco'] = "sql211.infinityfree.com";
$GLOBALS['database_banco'] = "if0_43311871_relate_sbc";

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
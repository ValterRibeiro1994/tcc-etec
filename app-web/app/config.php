<?php
// caminhos
$GLOBALS['raiz-app'] =  '/app-web/'; // branch main use '/app-web/' | branch produção use '/'

// dados de acesso ao banco de dados
$GLOBALS['usuario_banco'] = "root";
$GLOBALS['senha_banco'] = "";
$GLOBALS['host_banco'] = "localhost";
$GLOBALS['database_banco'] = "relate_sbc";

//tabelas do banco de dados
$GLOBALS['usuario'] = "tb_usuario";
$GLOBALS['denuncia'] = "tb_denuncia";
$GLOBALS['categorias'] = "tb_categoria";    

$GLOBALS['conversa'] = "tb_conversa";
$GLOBALS['mensagem'] = "tb_mensagem";

// debug
$GLOBALS['debug'] = true;

// tempo de sessao
$GLOBALS['tempo-sessao'] = 1200000000000;

// fuso horário do sistema (sem isso o PHP usa o padrão do servidor, geralmente UTC, e as horas saem erradas)
date_default_timezone_set('America/Sao_Paulo');

// formato padrão para data e hora do sistema
$GLOBALS['formato-data-hora'] = 'Y-m-d H:i:s';

// chave temporaria para token
$GLOBALS['token-chave'] = "projeto-relate-tcc-etec-lauro-gomes";
$GLOBALS['tkn-algo'] = "HS256"; // algoritmo de criptografia do token

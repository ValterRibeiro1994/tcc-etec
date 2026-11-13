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
$GLOBALS['tempo-sessao'] = 43200; // 12 horas (o valor anterior durava muitos anos)

// fuso horário do sistema (sem isso o PHP usa o padrão do servidor, geralmente UTC, e as horas saem erradas)
date_default_timezone_set('America/Sao_Paulo');

// formato padrão para data e hora do sistema
$GLOBALS['formato-data-hora'] = 'Y-m-d H:i:s';

// A chave do JWT precisa ser secreta: a anterior era pública e podia ser adivinhada.
// No primeiro acesso criamos um segredo aleatório APENAS neste servidor.
// Não publique app/segredo-jwt.php no GitHub nem copie o arquivo entre ambientes.
$arquivoSegredo = __DIR__ . '/segredo-jwt.php';
if (!is_file($arquivoSegredo)) {
    $arquivoNovo = @fopen($arquivoSegredo, 'x'); // x = cria só se ainda não existe
    if ($arquivoNovo !== false) {
        $conteudo = '<?php return ' . var_export(bin2hex(random_bytes(32)), true) . ';';
        fwrite($arquivoNovo, $conteudo);
        fclose($arquivoNovo);
    }
}
if (!is_file($arquivoSegredo)) {
    throw new RuntimeException('Crie app/segredo-jwt.php com uma chave aleatória; a pasta app não permite escrita.');
}
$segredo = require $arquivoSegredo;
if (!is_string($segredo) || strlen($segredo) < 32) {
    throw new RuntimeException('Chave JWT inválida em app/segredo-jwt.php.');
}
$GLOBALS['token-chave'] = $segredo;
$GLOBALS['tkn-algo'] = 'HS256';

<?php
// caminhos
// Pasta-base detectada pela URL do index.php reescrito pelo Apache.
$diretorioWeb = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
$GLOBALS['raiz-app'] = rtrim($diretorioWeb, '/') . '/'; // WAMP: /app-web/; servidor: /, quando reconhecido
// Nesta hospedagem IIS, Web é a raiz pública; evita redirecionamentos para /app-web/.
$hostAtual = strtolower(explode(':', $_SERVER['HTTP_HOST'] ?? '')[0]);
if ($hostAtual === 'tcc3edsmodetecgr6.websiteseguro.com') {
    $GLOBALS['raiz-app'] = '/';
}

// dados de acesso ao banco de dados
// Credenciais da hospedagem devem ficar APENAS em app/config-banco.php.
$GLOBALS['usuario_banco'] = 'root';
$GLOBALS['senha_banco'] = '';
$GLOBALS['host_banco'] = 'localhost';
$GLOBALS['database_banco'] = 'relate_sbc';
$arquivoBanco = __DIR__ . '/config-banco.php';
if (is_file($arquivoBanco)) {
    $banco = require $arquivoBanco;
    foreach (['usuario_banco', 'senha_banco', 'host_banco', 'database_banco'] as $campo) {
        if (isset($banco[$campo])) $GLOBALS[$campo] = (string) $banco[$campo];
    }
}

//tabelas do banco de dados
$GLOBALS['usuario'] = "tb_usuario";
$GLOBALS['denuncia'] = "tb_denuncia";
$GLOBALS['categorias'] = "tb_categoria";    
$GLOBALS['resposta_tb'] = "tb_resposta";
$GLOBALS['conversa'] = "tb_conversa";
$GLOBALS['mensagem'] = "tb_mensagem";
$GLOBALS['notificacao'] = "tb_notificacao";
$GLOBALS['representante_aprovado'] = "tb_representante_aprovado";
$GLOBALS['historico_status'] = "tb_historico_status";
$GLOBALS['admin_auditoria'] = "tb_admin_auditoria";

// debug
$GLOBALS['debug'] = false; // Hospedagem pública: não mostrar erros internos ao visitante

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

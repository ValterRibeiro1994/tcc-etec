<?php
include_once("app/config.php");
include_once("app/autoload.php");
include_once("app/roteador.php");

new AutoLoadFiles();

// checa se existe uma sessão aberta 
if (!SessaoService::estaOnline()){ // se não
    // checa pela existencia de um perfil se alguma conexão já foi aberta anteriormente
    $perfil = SessaoService::obterAtributoUsuario("perfil");
    if (!$perfil){ // se não foi aberta nenhuma sessão
        // cria um perfil de visitante
        $visitante = new Usuario(new DadosPessoais());
        $resposta = SessaoService::registrarUsuario($visitante, false);
        if (!$resposta['resposta']){
            SessaoService::destruirSessao();  
        }
    }
}

try {
    $rotas = new Roteador();
    $resposta = $rotas->getResposta();
    // var_dump($resposta);
    // var_dump($GLOBALS);
    // exit();
    if ($resposta['formato'] == "html"){
        header("Content-type: text/html; charset=utf-8");
        $pagina = $resposta['pagina'];
        include_once($pagina);
        exit();
    }

    // var_dump($resposta);
    header("Content-type: application/json; charset=utf-8");
    
    echo json_encode($resposta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit();

} catch (Exception $erro) {
    $dados = RespostaProcesso::salvarErro($erro);
    $resposta = RespostaProcesso::resposta($dados['mensagem'], false, $dados);
    echo json_encode($resposta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit();
}

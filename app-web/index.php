<?php
include_once("app/config.php");
include_once("app/autoload.php");
include_once("app/roteador.php");

new AutoLoadFiles();
try {
    $rotas = new Roteador();
    $resposta = $rotas->getResposta();
    
    if ($resposta['formato'] == "html"){
        $header ="Content-type: text/html; charset=utf-8";
        header($header);
        $pagina = $resposta['pagina'];
        //var_dump($resposta);
        include_once($pagina);
        exit();
    }

    header("Content-type: application/json; charset=utf-8");
    echo json_encode($resposta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit();

} catch (Exception $erro) {
    $dados = RespostaProcesso::salvarErro($erro);
    $resposta = RespostaProcesso::resposta($dados['mensagem'], false, $dados);   
    echo json_encode($resposta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit();
}

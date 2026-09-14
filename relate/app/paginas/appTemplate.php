<?php

class AppTemplate {
    private string $pagina;
    private string $cabecalho;
    private string $rodape;
    private string $corpo;
    private string $head;
    private string $body;


    public function __construct(string $nome_pagina, string $titulo_pagina) {
        // partes do body
        $this->criarCabecalho();
        $this->criarFooter();
        $this->criarCorpo($nome_pagina);

        // body
        $this->criarBody();

        // partes do html
        $this->criarHead($titulo_pagina);

        // html
        $this->criarPagina();
    }

    public function getTemplate() {
        return $this->pagina;
    }

    private function criarPagina() {
        include_once("../relate/app/paginas/htmlTemplate.php");
        $html = new HtmlTemplate($this->head, $this->body);
        $this->pagina = $html->getTemplate();
    }

    private function criarCabecalho(){
        include_once("../relate/app/paginas/headerTemplate.php");
        $header = new HeaderTemplate();
        $this->cabecalho = $header->getTemplate();
    }

    private function criarFooter(){
        include_once("../relate/app/paginas/footerTemplate.php");
        $footer = new FooterTemplate();
        $this->rodape = $footer->getTemplate();
    }

    private function criarCorpo(string $pagina){
        // chama o arquivo
        $arquivo = strtolower($pagina);
        $caminho = "../relate/app/paginas/$arquivo" . "Pagina.php";
        include_once($caminho);
        
        // chama a classe
        $classe = ucfirst($arquivo) . "Pagina";

        if (!class_exists($classe)){
            $this->corpo = "<br><h1 class='display-1 text-danger'> ARQUIVO $arquivo NÃO  ENCONTRADO </h1>";
            return;
        }

        $corpo = new $classe();
        $this->corpo = $corpo->getTemplate();
    }

    private function criarHead(string $titulo) {
        $estilos = $_SESSION['config']['estilos'];
        include_once("../relate/app/paginas/headTemplate.php");
        
        $head = new HeadTemplate($titulo, $estilos);
        $this->head = $head->getTemplate();

    }

    private function criarBody() {
        $scripts = $_SESSION['config']['scripts'];
        include_once("../relate/app/paginas/bodyTemplate.php");
        $body = new BodyTemplate(
            $this->cabecalho,
            $this->corpo,
            $this->rodape,
            $scripts
        );

        $this->body = $body->getTemplate();
    }

}
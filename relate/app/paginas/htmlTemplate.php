<?php

class HtmlTemplate {
    private string $template;

    public function __construct(string $head, string $body){
        $this->template = $this->criarHtml($head, $body);
    }

    public function getTemplate(){
        return $this->template;
    }

    private function criarHtml(string $head, string $body){
        return "
            <!DOCTYPE html>
            <html lang='pt-br'>
                $head
                $body
            </html>
        ";
    }
}
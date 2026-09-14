<?php

class LoginController {
    private array $jsonResponse;
    private string $template;

    public function index(array $request): array {
        if ($request['metodo'] == "GET"){
            $template = new AppTemplate("login", "Conecte-se");
            $html = $template->getTemplate();
            return Response::response($html, true, formato: "html");
        }

        return Response::response("Metodo invalido", false);


    }
}
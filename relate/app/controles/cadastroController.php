<?php

class CadastroController {
    private array $jsonResponse;
    private string $template;

    public function index(array $request){
        if ($request['metodo'] == "GET"){
            $template = new AppTemplate("cadastro", "Cadastre-se");
            return Response::response($template->getTemplate(), true, "html");
        }
        return Response::response("Cadastro em DEsenvolimentp", false, "html");
    }
}
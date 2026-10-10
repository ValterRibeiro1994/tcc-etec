<?php
 
// Rota: GET  ./denuncia/listar  ->  devolve JSON com as denúncias do mural
class DenunciaController {
 
    public function listar(array $requisicao){
        if ($requisicao['metodo'] !== "GET") {
            return RespostaProcesso::resposta("Requisição inválida para listar denúncias");
        }
 
        $repositorio = new DenunciasRepositorio();
        return $repositorio->listarDenuncias();
    }
}
 
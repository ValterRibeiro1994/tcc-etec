<?php 

class DenunciaController {
    private string $repositorio;
    // private DenunciasTemplate $view;
    private Denuncia $model;
    
    public function index(array $dados){
        return ['resposta'=>true, 'mensagem'=>'controller iniciado com sucesso'];
    }
}


// passos para criar a denuncia no banco 

// passo 1: id do denunciante 
// -> deve existir um login já efetuado
// -> -> capturar o id e o nome do usuario


// passo 2: Local da denuncia
// -> capturar os dados do local da denuncia

// passo 3: Informações da denuncia
// -> capturar as descrições da denuncia, imagem e data

// passo 4: criar a denuncia
// -> salvar a denuncia no banco
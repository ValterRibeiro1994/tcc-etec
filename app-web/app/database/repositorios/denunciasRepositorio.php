<?php

class DenunciasRepositorio {
    public function salvarDenuncia(Denuncia $denuncia){
        $usuario = $denuncia->getUsuario();
        $data = $denuncia->getData();
        $titulo = $denuncia->getTitulo();
        $texto = $denuncia->getTexto();
        $imagem = $denuncia->getFoto();

        return RespostaProcesso::respostaProcesso("Finalizar Processo para armazenar denuncia no banco", true);
        
    }

    public function apagarDenuncia(){}
}
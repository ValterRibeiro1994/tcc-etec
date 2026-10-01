<?php

class DenunciasRepositorio {
    public function salvarDenuncia(Conexao $conexao, Denuncia $denuncia){
        $usuario = $denuncia->getUsuario();
        $data = $denuncia->getData();
        $titulo = $denuncia->getTitulo();
        $texto = $denuncia->getTexto();
    }

    public function apagarDenuncia(){}
}
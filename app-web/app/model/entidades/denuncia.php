<?php

class Denuncia {
    private Usuario|null $usuario;
    private Endereco|null $endereco;
    private Descricao|null $descricao;
    private Imagem|null $foto;

    public function __construct(Usuario $usuario = null, Endereco $endereco = null, Descricao $descricao = null, Imagem $imagem = null){
        if ($usuario != null) $this->usuario = $usuario;
        if ($endereco != null) $this->endereco = $endereco;
        if ($descricao != null) $this->descricao = $descricao;
        if ($imagem != null) $this->foto = $imagem;
    }
    
    public function getUsuario(): Usuario {
        if ($this->usuario == null) throw new Exception("Usuário não enviado");
        return $this->usuario;
    }

    public function getNome(): string {
        if ($this->usuario == null) throw new Exception("Usuário não enviado");
        return $this->usuario->getNome();
    }

    public function getData(): string {
        if ($this->descricao == null) throw new Exception("Descrição não enviada");
        return $this->descricao->getData();
    }

    public function getTitulo(): string {
        if ($this->descricao == null) throw new Exception("Descrição não enviada");
        return $this->descricao->getTitulo();
    }

    public function getTexto(): string {
        if ($this->descricao == null) throw new Exception("Descrição não enviada");
        return $this->descricao->getTexto();
    }

    public function getFoto() {
        if ($this->foto == null) throw new Exception("Foto não enviada");
        return $this->foto->getFoto();
    }
}
?>
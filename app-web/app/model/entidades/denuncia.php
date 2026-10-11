<?php

class Denuncia {
    // ALTERADO: propriedades iniciam como null (antes ficavam sem valor e geravam erro ao serem lidas)
    private Usuario|null $usuario = null;
    private Endereco|null $endereco = null;
    private Descricao|null $descricao = null;
    private Imagem|null $foto = null;

    public function __construct(Usuario|null $usuario = null, Endereco|null $endereco = null, Descricao|null $descricao = null, Imagem|null $imagem = null){
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

    // ADICIONADO: o repositório precisa do endereço (rua/logradouro, número, bairro, cidade, estado, cep, complemento)
    public function getEndereco(): Endereco {
        if ($this->endereco == null) throw new Exception("Endereço não enviado");
        return $this->endereco;
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

    // ADICIONADO: nome_categoria da denúncia
    public function getCategoria(): string {
        if ($this->descricao == null) throw new Exception("Descrição não enviada");
        return $this->descricao->getCategoria();
    }

    // ALTERADO: a foto é opcional, então devolve null quando não existe (antes lançava exceção)
    public function getFoto() {
        if ($this->foto == null) return null;
        return $this->foto->getFoto();
    }
}
?>
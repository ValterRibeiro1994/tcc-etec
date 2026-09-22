<?php

class Prefeitura {
    private string $orgao;
    private string $cargo;
    private int $limite = 120;

    public function __construct(string $orgao, string $cargo){
        $this->validarCargo($cargo);
        $this->validarOrgao($orgao);
    }

    public function getOrgao(): string {
        return $this->orgao;
    }

    public function getCargo(): string {
        return $this->cargo;
    }

    private function validarOrgao(string $nome_orgao){
        $orgao = trim($nome_orgao);
        if (empty($orgao)) throw new Exception("ERRO REPRESENTANTE: Orgão não enviado");
        if (strlen($orgao) > $this->limite) throw new Exception("ERRO REPRESENTANTE: Limite de caracteres excedido para orgão");
        $this->orgao = $orgao;
    }

    private function validarCargo(string $cargo){
        $cargo = trim($cargo);
        if (empty($cargo)) throw new Exception("ERRO REPRESENTANTE: Cargo inválido");
        if (strlen($cargo) > $this->limite) throw new Exception("ERRO REPRESENTANTE: Limite de caracteres excedido para cargo");
        $this->cargo = $cargo;
    }
}
<?php

class Prefeitura {
    private string $orgao;
    private string $cargo;
    private int $limite = 120;

    public function __construct(string $orgao, string $cargo){
        $this->setCargo($cargo);
        $this->setOrgao($orgao);

    }

    public function getOrgao(): string {
        return $this->orgao;
    }

    public function getCargo(): string {
        return $this->cargo;
    }

    public function setCargo(string $novo_cargo){
        $this->validarCargo($novo_cargo);
        $this->cargo = $novo_cargo;
    }

    public function setOrgao(string $novo_orgao){
        $this->validarOrgao($novo_orgao);
        $this->cargo = $novo_orgao;
    }

    private function validarOrgao(string $nome_orgao): bool {
        $orgao = trim($nome_orgao);
        if (strlen($orgao) > $this->limite) throw new Exception("ERRO: Limite de caracteres excedido para orgão");
        return true;
    }

    private function validarCargo(string $cargo): bool {
        $cargo = trim($cargo);
        if (strlen($cargo) > $this->limite) throw new Exception("ERRO: Limite de caracteres excedido para cargo");
        return true;
    }
}
<?php

class Prefeitura {
    private string $orgao;
    private string $cargo;
    private int $limite = 120;
    private ?string $area = null;   // categoria que o representante atende (null = todas)

        public function __construct(string $orgao = null, string $cargo = null, string $area = null){
        $this->orgao = "não informado";
        if ($orgao != null) $this->setOrgao($orgao); 

        $this->cargo = "não informado";
        if ($cargo != null) $this->setCargo($cargo);

        if ($area != null) $this->setArea($area);
    }

    public function getOrgao(): string {
        return $this->orgao;
    }

    public function getCargo(): string {
        return $this->cargo;
    }

        public function getArea(): ?string {
        return $this->area;
    }

    public function setArea(string $nova_area){
        $area = trim($nova_area);
        if ($area === '' || mb_strlen($area) > 30) {
            throw new InvalidArgumentException("Cliente: Área de atuação inválida");
        }
        $this->area = $area;
    }

    public function setCargo(string $novo_cargo){
        $this->validarCargo($novo_cargo);
        $this->cargo = $novo_cargo;
    }

    public function setOrgao(string $novo_orgao){
        $this->validarOrgao($novo_orgao);
        $this->orgao = $novo_orgao;
    }

    private function validarOrgao(string $nome_orgao): bool {
        $orgao = trim($nome_orgao);
        if (strlen($orgao) > $this->limite) throw new InvalidArgumentException("ERRO: Limite de caracteres excedido para orgão");
        return true;
    }

    private function validarCargo(string $cargo): bool {
        $cargo = trim($cargo);
        if (strlen($cargo) > $this->limite) throw new InvalidArgumentException("ERRO: Limite de caracteres excedido para cargo");
        return true;
    }
}
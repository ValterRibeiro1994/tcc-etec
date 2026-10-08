<?php

class Perfil {
    private string $perfil;
    public function __construct(string $perfil)
    {
        if (!$this->validarPerfil($perfil)) throw new Exception("Perfil $perfil invalido");
        $this->perfil = $perfil;

    }

    public function getPerfil(): string {
        return $this->perfil;
    }

    public function setPerfil(string $perfil){
        if (!$this->validarPerfil($perfil)) throw new Exception("Perfil $perfil invalido");
        $this->perfil = $perfil;
    }

    private function validarPerfil(string $perfil){
        if (!ctype_alpha($perfil) && !is_string($perfil)){
            return false;
        }

        $perfis_autorizados = [
            "visitante", "municipe", "representante"
        ];

        foreach ($perfis_autorizados as $perfil_usuario) {
            if ($perfil == $perfil_usuario) return true;
        }
        return false;
    }
}
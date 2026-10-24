<?php

class Perfil {
    private string $perfil;
    public function __construct(string $perfil = "visitante")
    {
        if (!$this->validarPerfil($perfil)){
            throw new Exception("Perfil: Perfil não indentificado no sistema");
        }
        $this->perfil = $perfil;

    }

    public function getPerfil(): string {
        return $this->perfil;
    }

    public function setPerfil(string $perfil){
        if (!$this->validarPerfil($perfil)) {
            throw new Exception("Perfil: Perfil não indentificado no sistema");
        }
        
        $this->perfil = $perfil;
    }

    private function validarPerfil(string $perfil){
        if (!ctype_alpha($perfil) && !is_string($perfil)){
            return false;
        }
            
        $perfil = mb_strtolower($perfil, "UTF-8");
        $perfis_autorizados = [
            "visitante", "municipe", "representante"
        ];

        return UtilService::perfilExiste($perfis_autorizados, $perfil);
    }
}
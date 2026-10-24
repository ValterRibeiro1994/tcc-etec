<?php

class Perfil {
    private string $perfil;
    public function __construct(string $perfil = "visitante")
    {
        $this->perfil = mb_strtolower($perfil, "UTF-8");;

    }

    public function getPerfil(): string {
        return $this->perfil;
    }

    public function setPerfil(string $perfil){
        $this->perfil = $perfil;
    }

    public function validarPerfil(){
        $perfil = mb_strtolower($this->perfil, "UTF-8");
        if (!ctype_alpha($perfil) && !is_string($perfil)){
            throw new InvalidArgumentException("Perfil: Perfil não indentificado no sistema");
        }
        
        $perfis_autorizados = [
            "visitante", "municipe", "representante"
        ];

        if (!UtilService::perfilExiste($perfis_autorizados, $perfil)){
            throw new InvalidArgumentException("Perfil: Perfil não indentificado no sistema");
        }
    }
}
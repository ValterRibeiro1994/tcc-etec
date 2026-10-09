<?php

class UtilService {
    public static function validarPerfil(array $perfis_validos, string $perfil_recebido){
        foreach($perfis_validos as $perfil){
            if ($perfil == $perfil_recebido) return true;
        }
        return false;
    }
}
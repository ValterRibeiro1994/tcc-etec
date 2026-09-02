<?php
 
class Cpf {
    private string $cpf;

    public function __construct(string $cpf){
        $cpf = $this->limparCaracteres($cpf);
        if (strlen($cpf) != 11){
            throw new Exception("CPF Invalido");
        }

        $caracteres_repetidos = $this->validarCaracteresRepetidos($cpf);
        if (!$caracteres_repetidos['status']){
            throw new Exception($caracteres_repetidos['mensagem']);
        }

        $primeiro_digito = $this->validarPrimeiroDigito($cpf);
        if (!$primeiro_digito['status']){
            throw new Exception($primeiro_digito['mensagem']);
        }

        $segundo_digito = $this->validarSegundoDigito($cpf);
        if (!$segundo_digito['status']){
            throw new Exception($segundo_digito['mensagem']);
        }

        $this->cpf = $cpf;
    }

    public function getCpf(): string {
        return $this->cpf;
    }

    private function validarCaracteresRepetidos(string $cpf){
        if ($cpf === str_repeat($cpf[0], 11)){
            return RespostaProcesso::respostaProcesso("CPF Invalido");
        }
        return RespostaProcesso::respostaProcesso("CPF Valido", true);
    }

    private function limparCaracteres(string $cpf){
        $cpf_limpo = "";
        $n = strlen($cpf);
        for ($i=0; $i < $n; $i++) { 
            $letra = $cpf[$i];
            if (ctype_digit($letra)){
                $cpf_limpo .= $letra;
            }
        }
        return $cpf_limpo;
    }

    private function validarPrimeiroDigito(string $cpf){

        $soma = 0;
        for ($i = 0; $i < 9; $i++){
            $soma += $cpf[$i] * (10 - $i);
        }

        $resto = ($soma * 10) % 11;
        if ($resto == 10){
            $resto = 0;
        }

        if ($resto != $cpf[9]){
            return RespostaProcesso::respostaProcesso("CPF Invalido");
        }

        return RespostaProcesso::respostaProcesso("1° Digito Válido", true);
    }

    private function validarSegundoDigito(string $cpf){

        $soma = 0;
        for ($i = 0; $i < 10; $i++){
            $soma += $cpf[$i] * (11 - $i);
        }

        $resto = ($soma * 10) % 11;
        if ($resto == 10){
            $resto = 0;
        }

        if ($resto != $cpf[9]){
            return RespostaProcesso::respostaProcesso("CPF Invalido");
        }

        return RespostaProcesso::respostaProcesso("2° Digito Válido", true);
    }
}
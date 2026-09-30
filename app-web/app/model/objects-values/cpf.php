<?php
 
class Cpf {
    private string $cpf;

    public function __construct(string $cpf) {

        // limpar o cpf 
        $cpf = $this->limparCaracteres($cpf);
        
        // validar o CPF
        if (strlen($cpf) != 11) throw new Exception("CPF Invalido");
        $this->validarCaracteresRepetidos($cpf);
        $this->validarPrimeiroDigito($cpf);
        $this->validarSegundoDigito($cpf);

        // salva o CPF
        $this->cpf = $cpf;
    }

    public function getCpf(): string {
        return $this->cpf;
    }

    private function validarCaracteresRepetidos(string $cpf): void {
        if ($cpf === str_repeat($cpf[0], 11)) throw new Exception("CPF Invalido");
    }

    private function limparCaracteres(string $cpf): string {
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

    private function validarPrimeiroDigito(string $cpf): void{

        $soma = 0;
        for ($i = 0; $i < 9; $i++){
            $soma += $cpf[$i] * (10 - $i);
        }

        $resto = ($soma * 10) % 11;
        if ($resto == 10){
            $resto = 0;
        }

        if ($resto != $cpf[9]) throw new Exception("CPF Invalido");
    }

    private function validarSegundoDigito(string $cpf): void {

        $soma = 0;
        for ($i = 0; $i < 10; $i++){
            $soma += $cpf[$i] * (11 - $i);
        }

        $resto = ($soma * 10) % 11;
        if ($resto == 10){
            $resto = 0;
        }

        if ($resto != $cpf[10]) throw new Exception("CPF Invalido");
    }
}
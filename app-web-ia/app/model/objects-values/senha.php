<?php

class Senha {
    private string $senha;

    public function __construct(string $senha){
        $this->senha = $senha;
    }

    public function validarSenha() {
    $senha = $this->senha;
    $faltando = [];

    if (strlen($senha) < 8) $faltando[] = "no mínimo 8 caracteres";
    if (!preg_match('/[A-Z]/', $senha)) $faltando[] = "uma letra maiúscula";
    if (!preg_match('/[a-z]/', $senha)) $faltando[] = "uma letra minúscula";
    if (!preg_match('/[0-9]/', $senha)) $faltando[] = "um número";
    if (!preg_match('/[[:punct:]]/', $senha)) $faltando[] = "um caractere especial (ex: @ # $ % !)";

    if (count($faltando) > 0) {
        throw new InvalidArgumentException("Cliente: Senha fraca. A senha precisa ter " . implode(", ", $faltando) . ".");
    }

    // não aceita espaços, acentos ou outros símbolos fora do teclado padrão
    if (preg_match('/[^A-Za-z0-9[:punct:]]/', $senha)) {
        throw new InvalidArgumentException("Cliente: A senha não pode ter espaços ou letras com acento.");
    }
}

    public function setSenha(string $senha = ""){
        $this->senha = $senha;
    }

    public function getSenha() {
        return $this->senha;
    }

    public function hashSenha(){
        // fator de custo menor para um processamento mais rapido
        $options = ['cost' => 10];
        return password_hash($this->senha, PASSWORD_BCRYPT, $options);
    }

}
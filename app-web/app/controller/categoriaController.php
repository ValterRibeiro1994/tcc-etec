<?php

class CategoriaController {
    public function categorias(array $requisicao){
        if (!array_key_exists("metodo", $requisicao)){
            return RespostaProcesso::respostaProcesso("Método não enviado", dados: $requisicao);
        }

        if ($requisicao['metodo'] == "GET"){
            return $this->getCategorias();
        }

        return RespostaProcesso::respostaProcesso("Requisição invalida");
    }

    private function getCategorias(){
        $tabela = $GLOBALS['categorias'];
        $comando = "SELECT id_categoria, nome_categoria FROM $tabela";
        try {
            $conexao = new Conexao();
            $conexao = $conexao->getConexao();
            $sql = $conexao->prepare($comando);
            $sql->execute();
            $resposta = $sql->fetch(PDO::FETCH_ASSOC);
            if ($sql->rowCount() == 0 || !$resposta){
                return RespostaProcesso::respostaProcesso("Categorias não foram registradas", true);
            }

            return RespostaProcesso::respostaProcesso("Categorias obtidas", dados: $resposta);

        } catch (Exception $erro) {
            $msg = "ERRO: " . $erro->getMessage();
            return RespostaProcesso::respostaProcesso($msg, false);
        }
    }
}
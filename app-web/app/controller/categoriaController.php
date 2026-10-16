<?php

class CategoriaController {
    public function categorias(array $requisicao){
        if (!array_key_exists("metodo", $requisicao)){
            return RespostaProcesso::resposta("Método não enviado", dados: $requisicao);
        }

        if ($requisicao['metodo'] == "GET"){
            return $this->getCategorias();
        }

        return RespostaProcesso::resposta("Requisição invalida");
    }

    private function getCategorias(){
        $tabela = $GLOBALS['categorias'];
        $comando = "SELECT id_categoria, nome_categoria FROM $tabela";
        try {
            $conexao = new Conexao();
            $conexao = $conexao->getConexao();
            $sql = $conexao->prepare($comando);
            $sql->execute();
            // ALTERADO: fetchAll (antes fetch devolvia só a primeira categoria)
            $resposta = $sql->fetchAll(PDO::FETCH_ASSOC);
            if ($sql->rowCount() == 0 || !$resposta){
                return RespostaProcesso::resposta("Categorias não foram registradas", true);
            }

            // ALTERADO: status true (antes ficava false por padrão e o front entendia como erro)
            return RespostaProcesso::resposta("Categorias obtidas", true, $resposta);

        } catch (Exception $erro) {
            $msg = "ERRO: " . $erro->getMessage();
            return RespostaProcesso::resposta($msg, false);
        }
    }
}
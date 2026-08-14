<?php


class DenunciasRespositorio {
    private array $denunciasVetor;
    private PDO $conexão;

    public function __construct(PDO $conexao)
    {
        $this->conexão = $conexao;
    }

    /*
     * Método: salvarDenuncia.
     * Parâmetro: denunciaModel.
     * Retorno: Booleano (True em caso de sucesso, False em caso de falha).
     * Função: Salvar uma denuncia realizada diretamente no banco de dados.
    */
    public function salvarDenuncia($denuncia): bool {
        return true;
    }

    /*
     * Método: buscarDenuncia.
     * Parâmetro: ID/CEP/denunciaModel.
     * Retorno: denunciaModel.
     * Função: Localizar uma denuncia armazenada no banco de dados.
    */
    public function buscarDenuncia(){

    }

    /*
     * Método: editarDenuncia.
     * Parâmetro: denunciaModel.
     * Retorno: Booleano (True em caso de sucesso, False em caso de falha).
     * Função: Editar os dados de uma denuncia realizada no banco de dados.
    */
    public function editarDenuncia(): bool {
        return true;
    }

    /*
     * Método: removerDenuncia.
     * Parâmetro: ID/denunciaModel.
     * Retorno: Booleano (True em caso de sucesso, False em caso de falha).
     * Função: Remover uma denuncia realizada diretamente no banco de dados.
    */
    public function removerDenuncia(): bool {
        return true;
    }


}




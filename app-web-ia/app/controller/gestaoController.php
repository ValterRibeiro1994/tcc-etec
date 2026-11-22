<?php

/**
 * Enderecos alternativos para as funcoes do painel do representante.
 * A hospedagem IIS bloqueou /painel/resumo com HTTP 404.5.
 *
 * Esta classe NAO remove a verificacao de token nem de permissao:
 * ela delega cada operacao ao PainelController original.
 */
class GestaoController extends PainelController
{
    public function indicadores(array $requisicao): array
    {
        return $this->resumo($requisicao);
    }

    public function registros(array $requisicao): array
    {
        return $this->listar($requisicao);
    }

    public function arquivo(array $requisicao): array
    {
        return $this->exportar($requisicao);
    }

    public function trilha(array $requisicao): array
    {
        return $this->historico($requisicao);
    }

    public function atualizar(array $requisicao): array
    {
        return $this->mudarStatus($requisicao);
    }
}

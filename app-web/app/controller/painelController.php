<?php

// Os endpoints são REST simples, conforme o roteador do projeto.
// Nenhuma ação administrativa fica disponível para munícipes ou visitantes.
class PainelController {
    private function entrada(array $requisicao, string $metodo): array {
        if (($requisicao['metodo'] ?? '') !== $metodo)
            return RespostaProcesso::resposta('Método HTTP não permitido.');
        $login = UsuarioLogadoService::obter();
        if (!$login['resposta']) return $login;
        if (($login['dados']['perfil'] ?? '') !== 'representante')
            return RespostaProcesso::resposta('Somente representantes podem administrar denúncias.');
        return $login;
    }

    public function resumo(array $requisicao): array {
        $login = $this->entrada($requisicao, 'GET');
        if (!$login['resposta']) return $login;
        return (new PainelRepositorio())->resumo($login['dados']['email']);
    }

    private function lerFiltros(array $r): array {
        return ['status' => (string) ($r['status'] ?? ''),
                'categoria' => (string) ($r['categoria'] ?? ''),
                'busca' => (string) ($r['busca'] ?? '')];
    }

    public function listar(array $requisicao): array {
        $login = $this->entrada($requisicao, 'GET');
        if (!$login['resposta']) return $login;
        $pagina = max(1, min(10000, (int) ($requisicao['pagina'] ?? 1)));
        return (new PainelRepositorio())->listar($login['dados']['email'], $pagina,
            $this->lerFiltros($requisicao));
    }

    // Exportação retorna JSON (como as outras rotas); o JavaScript monta o CSV sem trocar o index.php.
    public function exportar(array $requisicao): array {
        $login = $this->entrada($requisicao, 'GET');
        if (!$login['resposta']) return $login;
        return (new PainelRepositorio())->listar($login['dados']['email'], 1,
            $this->lerFiltros($requisicao), true);
    }

    public function historico(array $requisicao): array {
        $login = $this->entrada($requisicao, 'GET');
        if (!$login['resposta']) return $login;
        $id = filter_var($requisicao['id'] ?? null, FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]);
        if (!$id) return RespostaProcesso::resposta('Id inválido.');
        return (new PainelRepositorio())->historico($login['dados']['email'], (int) $id);
    }

    public function mudarStatus(array $requisicao): array {
        $login = $this->entrada($requisicao, 'POST');
        if (!$login['resposta']) return $login;
        $id = filter_var($requisicao['id'] ?? null, FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]);
        if (!$id) return RespostaProcesso::resposta('Id inválido.');
        return (new PainelRepositorio())->alterarStatus($login['dados']['email'], (int) $id,
            trim((string) ($requisicao['status'] ?? '')),
            trim((string) ($requisicao['justificativa'] ?? '')));
    }
}

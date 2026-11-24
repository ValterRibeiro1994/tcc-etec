<?php
// Perfil 3: administrador-geral. O PHP SEMPRE consulta o banco para confirmar o papel.
// Criação do primeiro administrador só é permitida manualmente no phpMyAdmin.
class AdminController {
    private function autenticar(array $requisicao, string $metodo): array {
        if (($requisicao['metodo'] ?? '') !== $metodo) {
            return RespostaProcesso::resposta('Método de requisição não permitido.');
        }
        $login = UsuarioLogadoService::obter();
        if (!$login['resposta']) return $login;
        if (($login['dados']['perfil'] ?? '') !== 'administrador') {
            return RespostaProcesso::resposta('Área exclusiva do administrador-geral.');
        }
        // O role no JWT não basta: reconsulta tb_usuario em toda requisição.
        $id = (new AdminRepositorio())->idAdministrador($login['dados']['email']);
        if ($id === null) return RespostaProcesso::resposta('Permissão revogada ou usuário não autorizado.');
        return RespostaProcesso::resposta('Administrador autenticado', true,
            ['id' => $id, 'email' => $login['dados']['email']]);
    }

    public function resumo(array $requisicao): array {
        $login = $this->autenticar($requisicao, 'GET');
        if (!$login['resposta']) return $login;
        return (new AdminRepositorio())->resumo();
    }

    public function representantes(array $requisicao): array {
        $login = $this->autenticar($requisicao, 'GET');
        if (!$login['resposta']) return $login;
        $pagina = max(1, min(10000, (int)($requisicao['pagina'] ?? 1)));
        $busca = mb_substr(trim((string)($requisicao['busca'] ?? '')), 0, 100);
        $situacao = (string)($requisicao['situacao'] ?? 'todos');
        return (new AdminRepositorio())->representantes($pagina, $busca, $situacao);
    }

    public function decidir(array $requisicao): array {
        $login = $this->autenticar($requisicao, 'POST');
        if (!$login['resposta']) return $login;
        $id = filter_var($requisicao['id'] ?? null, FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]);
        if (!$id) return RespostaProcesso::resposta('ID do representante inválido.');
        $acao = (string)($requisicao['acao'] ?? '');
        if (!in_array($acao, ['aprovar', 'revogar'], true)) {
            return RespostaProcesso::resposta('Ação inválida.');
        }
        return (new AdminRepositorio())->decidir((int)$login['dados']['id'], (int)$id, $acao);
    }


        public function usuarios(array $requisicao): array {
        $login = $this->autenticar($requisicao, 'GET');
        if (!$login['resposta']) return $login;
        $pagina = max(1, min(10000, (int)($requisicao['pagina'] ?? 1)));
        $busca = mb_substr(trim((string)($requisicao['busca'] ?? '')), 0, 100);
        $perfil = (string)($requisicao['perfil'] ?? 'municipe');
        if (!in_array($perfil, ['municipe', 'representante', 'administrador'], true)) $perfil = 'municipe';
        return (new AdminRepositorio())->usuarios((int)$login['dados']['id'], $pagina, $busca, $perfil);
    }

    public function alterarFuncao(array $requisicao): array {
        $login = $this->autenticar($requisicao, 'POST');
        if (!$login['resposta']) return $login;
        $id = filter_var($requisicao['id'] ?? null, FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]);
        if (!$id) return RespostaProcesso::resposta('ID do usuário inválido.');
        $funcao = (string)($requisicao['funcao'] ?? '');
        if (!in_array($funcao, ['municipe', 'representante', 'administrador'], true)) {
            return RespostaProcesso::resposta('Função inválida.');
        }
        return (new AdminRepositorio())->alterarFuncao((int)$login['dados']['id'], (int)$id, $funcao);
    }
}

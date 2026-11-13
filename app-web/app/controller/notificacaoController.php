<?php

// Rotas protegidas por JWT: GET notificacao/minhas e POST notificacao/ler, notificacao/lerTodas.
class NotificacaoController {
    private function autenticar(array $requisicao, string $metodo): array {
        if (($requisicao['metodo'] ?? '') !== $metodo) {
            return RespostaProcesso::resposta('Método inválido');
        }
        return UsuarioLogadoService::obter();
    }

    public function minhas(array $requisicao): array {
        $login = $this->autenticar($requisicao, 'GET');
        if (!$login['resposta']) return $login;
        return (new NotificacoesRepositorio())->minhas($login['dados']['email']);
    }

    public function ler(array $requisicao): array {
        $login = $this->autenticar($requisicao, 'POST');
        if (!$login['resposta']) return $login;
        $id = filter_var($requisicao['id'] ?? null, FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]);
        if ($id === false || $id === null) return RespostaProcesso::resposta('Notificação inválida');
        return (new NotificacoesRepositorio())->marcar($login['dados']['email'], (int) $id);
    }

    public function lerTodas(array $requisicao): array {
        $login = $this->autenticar($requisicao, 'POST');
        if (!$login['resposta']) return $login;
        return (new NotificacoesRepositorio())->marcar($login['dados']['email']);
    }
}

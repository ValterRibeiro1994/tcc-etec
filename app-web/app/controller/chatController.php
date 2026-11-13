<?php

// Chat entre REPRESENTANTE e DENUNCIANTE (uma conversa por denúncia).
//
// Rotas (todas exigem login; o token vai no cabeçalho "Authorization"):
//   POST ./chat/iniciar    -> só representante; cria a conversa da denúncia (ou devolve a existente)
//   POST ./chat/enviar     -> quem participa da conversa; recusa se estiver encerrada
//   POST ./chat/encerrar   -> só o representante da conversa
//   GET  ./chat/mensagens  -> mensagens novas (?conversa=X&depois=ID) e marca como lidas
//   GET  ./chat/minhas     -> lista de conversas do usuário + total de não lidas
//
// Toda regra de acesso é conferida AQUI e no repositório (não basta esconder botão no front).
class ChatController {

    private const LIMITE_TEXTO = 1000;

    private ChatRepositorio $repositorio;

    public function __construct(){
        $this->repositorio = new ChatRepositorio();
    }

    // Confere o método HTTP e o login. Devolve [erro|null, usuário|null];
    // o usuário traz perfil, nome, sobrenome, email (do token) e id (do banco).
    private function autenticar(array $requisicao, string $metodo): array {
        if (($requisicao['metodo'] ?? '') !== $metodo) {
            return [RespostaProcesso::resposta('Método inválido'), null];
        }

        $logado = UsuarioLogadoService::obter();
        if (!$logado['resposta']) return [$logado, null];

        $id = $this->repositorio->obterIdUsuario($logado['dados']['email']);
        if ($id === null) {
            return [RespostaProcesso::resposta('Usuário não encontrado'), null];
        }

        $usuario = $logado['dados'];
        $usuario['id'] = $id;
        return [null, $usuario];
    }

    // POST chat/iniciar  (campo: denuncia)
    public function iniciar(array $requisicao){
        [$erro, $usuario] = $this->autenticar($requisicao, 'POST');
        if ($erro) return $erro;

        // perfil validado no servidor (o repositório confere de novo no banco)
        if ($usuario['perfil'] !== 'representante') {
            return RespostaProcesso::resposta('Somente o representante pode iniciar uma conversa');
        }

        $idDenuncia = (int) ($requisicao['denuncia'] ?? 0);
        if ($idDenuncia <= 0) {
            return RespostaProcesso::resposta('Denúncia inválida');
        }

        return $this->repositorio->iniciarConversa($idDenuncia, $usuario['id']);
    }

    // POST chat/enviar  (campos: conversa, texto)
    public function enviar(array $requisicao){
        [$erro, $usuario] = $this->autenticar($requisicao, 'POST');
        if ($erro) return $erro;

        $idConversa = (int) ($requisicao['conversa'] ?? 0);
        if ($idConversa <= 0) {
            return RespostaProcesso::resposta('Conversa inválida');
        }

        $texto = trim((string) ($requisicao['texto'] ?? ''));
        if ($texto === '') {
            return RespostaProcesso::resposta('Escreva uma mensagem');
        }
        if (mb_strlen($texto) > self::LIMITE_TEXTO) {
            return RespostaProcesso::resposta('Mensagem muito longa (máximo de ' . self::LIMITE_TEXTO . ' caracteres)');
        }

        return $this->repositorio->enviarMensagem($idConversa, $usuario['id'], $texto);
    }

    // POST chat/encerrar  (campo: conversa)
    public function encerrar(array $requisicao){
        [$erro, $usuario] = $this->autenticar($requisicao, 'POST');
        if ($erro) return $erro;

        if ($usuario['perfil'] !== 'representante') {
            return RespostaProcesso::resposta('Somente o representante pode encerrar a conversa');
        }

        $idConversa = (int) ($requisicao['conversa'] ?? 0);
        if ($idConversa <= 0) {
            return RespostaProcesso::resposta('Conversa inválida');
        }

        return $this->repositorio->encerrarConversa($idConversa, $usuario['id']);
    }

    // GET chat/mensagens?conversa=X&depois=ID
    public function mensagens(array $requisicao){
        [$erro, $usuario] = $this->autenticar($requisicao, 'GET');
        if ($erro) return $erro;

        $idConversa = (int) ($requisicao['conversa'] ?? 0);
        if ($idConversa <= 0) {
            return RespostaProcesso::resposta('Conversa inválida');
        }
        $depois = max(0, (int) ($requisicao['depois'] ?? 0));

        return $this->repositorio->listarMensagens($idConversa, $usuario['id'], $depois);
    }

    // GET chat/minhas
    public function minhas(array $requisicao){
        [$erro, $usuario] = $this->autenticar($requisicao, 'GET');
        if ($erro) return $erro;

        return $this->repositorio->listarConversas($usuario['id']);
    }
}
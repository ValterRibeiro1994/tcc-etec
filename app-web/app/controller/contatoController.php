
<?php

class ContatoController {

    public function enviar(array $requisicao): array {

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return RespostaProcesso::resposta(
                'Método não permitido.'
            );
        }

        $nome = is_string($requisicao['nome'] ?? null)
            ? trim($requisicao['nome']) : '';

        $email = is_string($requisicao['email'] ?? null)
            ? trim($requisicao['email']) : '';

        $tipo = is_string($requisicao['tipo'] ?? null)
            ? trim($requisicao['tipo']) : '';

        $assunto = is_string($requisicao['assunto'] ?? null)
            ? trim($requisicao['assunto']) : '';

        $mensagem = is_string($requisicao['mensagem'] ?? null)
            ? trim($requisicao['mensagem']) : '';

        // Campo invisível para dificultar envios de bots.
        if (!empty($requisicao['site'])) {
            return RespostaProcesso::resposta(
                'Mensagem recebida.',
                true
            );
        }

        // Verificação dos campos obrigatórios.
        if (
            $nome === '' ||
            $email === '' ||
            $assunto === '' ||
            $mensagem === ''
        ) {
            return RespostaProcesso::resposta(
                'Preencha todos os campos obrigatórios.'
            );
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return RespostaProcesso::resposta(
                'Informe um e-mail válido.'
            );
        }

        $tiposPermitidos = [
            'duvida',
            'problema',
            'sugestao',
            'elogio',
            'outro'
        ];

        if (!in_array($tipo, $tiposPermitidos, true)) {
            return RespostaProcesso::resposta(
                'Selecione um motivo de contato.'
            );
        }

        if (
            strlen($nome) > 400 ||
            strlen($email) > 254 ||
            strlen($assunto) > 400 ||
            strlen($mensagem) > 4000
        ) {
            return RespostaProcesso::resposta(
                'Uma das informações é muito longa.'
            );
        }

        // Limita envios consecutivos na mesma sessão.
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (
            isset($_SESSION['ultimo_contato']) &&
            time() - $_SESSION['ultimo_contato'] < 60
        ) {
            return RespostaProcesso::resposta(
                'Aguarde um minuto antes de enviar novamente.'
            );
        }

        // Arquivos de configuração e biblioteca.
        $arquivoConfig = __DIR__ . '/../config-email.php';

        $arquivoPHPMailer = __DIR__ .
            '/../../vendor/autoload.php';

        if (
            !is_file($arquivoConfig) ||
            !is_file($arquivoPHPMailer)
        ) {
            return RespostaProcesso::resposta(
                'O envio de e-mails ainda não foi configurado.'
            );
        }

        $config = require $arquivoConfig;

        $usuarioSMTP = $config['usuario'] ?? '';
        $senhaSMTP = $config['senha_app'] ?? '';

        if (
            $usuarioSMTP === '' ||
            $senhaSMTP === '' ||
            $senhaSMTP === 'COLE_A_SENHA_DE_APP_AQUI'
        ) {
            return RespostaProcesso::resposta(
                'Configure o e-mail de envio.'
            );
        }

        require_once $arquivoPHPMailer;

        try {

            $correio = new \PHPMailer\PHPMailer\PHPMailer(true);

            // Configura o servidor SMTP do Gmail.
            $correio->isSMTP();
            $correio->Host = 'smtp.gmail.com';
            $correio->SMTPAuth = true;

            $correio->Username = $usuarioSMTP;
            $correio->Password = $senhaSMTP;

            $correio->SMTPSecure =
                \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;

            $correio->Port = 587;
            $correio->CharSet = 'UTF-8';
            $correio->Timeout = 15;

            // Define quem envia e quem recebe.
            $correio->setFrom(
                $usuarioSMTP,
                'RelateSBC - Fale Conosco'
            );

            $correio->addAddress('kaialt@gmail.com');

            // Responder irá para o e-mail do visitante.
            $correio->addReplyTo($email, $nome);

            // Conteúdo do e-mail.
            $correio->isHTML(false);

            $correio->Subject = '[RelateSBC] ' .
                str_replace(["\r", "\n"], ' ', $assunto);

            $correio->Body =
                "Nova mensagem do Fale Conosco\n\n" .
                "Nome: $nome\n" .
                "E-mail: $email\n" .
                "Motivo: $tipo\n" .
                "Assunto: $assunto\n\n" .
                "Mensagem:\n$mensagem";

            // Tenta enviar a mensagem.
            $correio->send();

            $_SESSION['ultimo_contato'] = time();

            return RespostaProcesso::resposta(
                'Mensagem enviada com sucesso!',
                true
            );

        } catch (\Throwable $erro) {

            error_log(
                'Erro ao enviar contato: ' .
                $erro->getMessage()
            );

            return RespostaProcesso::resposta(
                'Não foi possível enviar a mensagem.'
            );
        }
    }
}

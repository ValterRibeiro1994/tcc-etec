<?php

// Consultas do chat entre REPRESENTANTE e DENUNCIANTE.
// Regras guardadas aqui (e não só no front):
//   - uma conversa por denúncia;
//   - só quem participa da conversa lê ou escreve nela (conversa alheia = "não encontrada");
//   - só o representante cria/encerra; conversa encerrada não recebe mensagens;
//   - denúncia anônima: o representante só recebe "Denunciante" (nunca o nome).
class ChatRepositorio {

    private const LIMITE_TEXTO = 1000;
    private const LIMITE_POR_MINUTO = 20;
    private const LIMITE_MENSAGENS_POR_BUSCA = 100;

    // ---------- utilitários internos ----------

    private function conectar(): PDO {
        return (new Conexao())->getConexao();
    }

    // Cada nome de parâmetro (:nome) só pode aparecer UMA vez na consulta.
    private function executar(PDO $conexao, string $sql, array $valores = []): PDOStatement {
        $stmt = $conexao->prepare($sql);
        foreach ($valores as $chave => $valor) {
            if ($valor === null) {
                $stmt->bindValue($chave, null, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue($chave, $valor, is_int($valor) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
        }
        $stmt->execute();
        return $stmt;
    }

    private function agora(): string {
        return (new DateTime())->format($GLOBALS['formato-data-hora']);
    }

    private function erro(string $mensagem, Exception $erro): array {
        return RespostaProcesso::resposta($mensagem, false, RespostaProcesso::salvarErro($erro));
    }

    // Devolve a conversa se o usuário participa dela; senão null.
    private function buscarConversaDoParticipante(PDO $conexao, int $idConversa, int $idUsuario): ?array {
        $tc = $GLOBALS['conversa'];
        $linha = $this->executar(
            $conexao,
            "SELECT id_conversa, id_denuncia, id_representante, id_denunciante, status_conversa
               FROM $tc
              WHERE id_conversa = :conversa
                AND (id_representante = :rep OR id_denunciante = :den)",
            [':conversa' => $idConversa, ':rep' => $idUsuario, ':den' => $idUsuario]
        )->fetch(PDO::FETCH_ASSOC);

        return $linha ?: null;
    }

    // ---------- usuário ----------

    public function obterIdUsuario(string $email): ?int {
        try {
            $tu = $GLOBALS['usuario'];
            $id = $this->executar(
                $this->conectar(),
                "SELECT id_usuario FROM $tu WHERE email_usuario = :email",
                [':email' => $email]
            )->fetchColumn();
            return $id === false ? null : (int) $id;
        } catch (Exception $erro) {
            return null;
        }
    }

    // ---------- iniciar (só representante) ----------

    // Cria a conversa da denúncia, ou devolve a que já existe.
    // A denúncia precisa ser da cidade/estado (e da área de atuação) do representante.
    public function iniciarConversa(int $idDenuncia, int $idRepresentante): array {
        try {
            $conexao = $this->conectar();
            $tu = $GLOBALS['usuario'];
            $td = $GLOBALS['denuncia'];
            $tc = $GLOBALS['conversa'];

            $rep = $this->executar(
                $conexao,
                "SELECT perfil_usuario, cidade_usuario, estado_usuario, area_atuacao_usuario
                   FROM $tu WHERE id_usuario = :id",
                [':id' => $idRepresentante]
            )->fetch(PDO::FETCH_ASSOC);

            if (!$rep || $rep['perfil_usuario'] !== 'representante') {
                return RespostaProcesso::resposta('Somente o representante pode iniciar uma conversa');
            }

            $where = 'id_denuncia = :denuncia AND cidade_denuncia = :cidade AND estado_denuncia = :estado';
            $params = [
                ':denuncia' => $idDenuncia,
                ':cidade' => $rep['cidade_usuario'],
                ':estado' => $rep['estado_usuario']
            ];
            if (!empty($rep['area_atuacao_usuario'])) {
                $where .= ' AND nome_categoria = :area';
                $params[':area'] = $rep['area_atuacao_usuario'];
            }

            $denuncia = $this->executar(
                $conexao,
                "SELECT id_usuario FROM $td WHERE $where",
                $params
            )->fetch(PDO::FETCH_ASSOC);

            if (!$denuncia) {
                return RespostaProcesso::resposta('Você não pode responder esta denúncia');
            }

            $idDenunciante = (int) $denuncia['id_usuario'];
            if ($idDenunciante === $idRepresentante) {
                return RespostaProcesso::resposta('Você não pode abrir uma conversa em uma denúncia sua');
            }

            // já existe? (no máximo uma conversa por denúncia)
            $existente = $this->buscarPorDenuncia($conexao, $idDenuncia);
            if ($existente) {
                return $this->respostaConversaExistente($existente, $idRepresentante);
            }

            try {
                $this->executar(
                    $conexao,
                    "INSERT INTO $tc (id_denuncia, id_representante, id_denunciante, status_conversa, criada_em)
                     VALUES (:denuncia, :rep, :den, 'aberta', :agora)",
                    [
                        ':denuncia' => $idDenuncia,
                        ':rep' => $idRepresentante,
                        ':den' => $idDenunciante,
                        ':agora' => $this->agora()
                    ]
                );
                $idNova = (int) $conexao->lastInsertId();
                return RespostaProcesso::resposta(
                    'Conversa iniciada', true,
                    ['conversa_id' => $idNova, 'criada' => true]
                );
            } catch (Exception $erro) {
                // duas pessoas clicaram ao mesmo tempo: a UNIQUE barrou a segunda
                if ($erro->getCode() == '23000') {
                    $existente = $this->buscarPorDenuncia($conexao, $idDenuncia);
                    if ($existente) {
                        return $this->respostaConversaExistente($existente, $idRepresentante);
                    }
                }
                throw $erro;
            }

        } catch (Exception $erro) {
            return $this->erro('Erro ao iniciar a conversa', $erro);
        }
    }

    private function buscarPorDenuncia(PDO $conexao, int $idDenuncia): ?array {
        $tc = $GLOBALS['conversa'];
        $linha = $this->executar(
            $conexao,
            "SELECT id_conversa, id_representante FROM $tc WHERE id_denuncia = :denuncia",
            [':denuncia' => $idDenuncia]
        )->fetch(PDO::FETCH_ASSOC);
        return $linha ?: null;
    }

    private function respostaConversaExistente(array $existente, int $idRepresentante): array {
        if ((int) $existente['id_representante'] !== $idRepresentante) {
            return RespostaProcesso::resposta('Esta denúncia já está sendo atendida por outro representante');
        }
        return RespostaProcesso::resposta(
            'Conversa já existente', true,
            ['conversa_id' => (int) $existente['id_conversa'], 'criada' => false]
        );
    }

    // ---------- enviar ----------

    public function enviarMensagem(int $idConversa, int $idUsuario, string $texto): array {
        try {
            $conexao = $this->conectar();
            $tm = $GLOBALS['mensagem'];

            $conversa = $this->buscarConversaDoParticipante($conexao, $idConversa, $idUsuario);
            if (!$conversa) {
                return RespostaProcesso::resposta('Conversa não encontrada');
            }
            if ($conversa['status_conversa'] !== 'aberta') {
                return RespostaProcesso::resposta('Esta conversa foi encerrada');
            }

            // limite de envios por minuto
            $desde = (new DateTime('-60 seconds'))->format($GLOBALS['formato-data-hora']);
            $recentes = (int) $this->executar(
                $conexao,
                "SELECT COUNT(*) FROM $tm WHERE id_autor = :autor AND enviada_em >= :desde",
                [':autor' => $idUsuario, ':desde' => $desde]
            )->fetchColumn();
            if ($recentes >= self::LIMITE_POR_MINUTO) {
                return RespostaProcesso::resposta('Muitas mensagens em pouco tempo. Aguarde um instante.');
            }

            $agora = $this->agora();
            $this->executar(
                $conexao,
                "INSERT INTO $tm (id_conversa, id_autor, texto_mensagem, enviada_em)
                 VALUES (:conversa, :autor, :texto, :agora)",
                [':conversa' => $idConversa, ':autor' => $idUsuario, ':texto' => $texto, ':agora' => $agora]
            );

            return RespostaProcesso::resposta('Mensagem enviada', true, [
                'id' => (int) $conexao->lastInsertId(),
                'minha' => true,
                'texto' => $texto,
                'enviada_em' => $agora
            ]);

        } catch (Exception $erro) {
            return $this->erro('Erro ao enviar a mensagem', $erro);
        }
    }

    // ---------- mensagens novas (polling) ----------

    // Devolve as mensagens com id > $depois e marca como lidas as do outro participante.
    public function listarMensagens(int $idConversa, int $idUsuario, int $depois): array {
        try {
            $conexao = $this->conectar();
            $tm = $GLOBALS['mensagem'];

            $conversa = $this->buscarConversaDoParticipante($conexao, $idConversa, $idUsuario);
            if (!$conversa) {
                return RespostaProcesso::resposta('Conversa não encontrada');
            }

            $linhas = $this->executar(
                $conexao,
                "SELECT id_mensagem, id_autor, texto_mensagem, enviada_em
                   FROM $tm
                  WHERE id_conversa = :conversa AND id_mensagem > :depois
                  ORDER BY id_mensagem ASC
                  LIMIT " . self::LIMITE_MENSAGENS_POR_BUSCA,
                [':conversa' => $idConversa, ':depois' => $depois]
            )->fetchAll(PDO::FETCH_ASSOC);

            $mensagens = [];
            $maiorId = 0;
            foreach ($linhas as $linha) {
                $id = (int) $linha['id_mensagem'];
                $maiorId = max($maiorId, $id);
                $mensagens[] = [
                    'id' => $id,
                    'minha' => (int) $linha['id_autor'] === $idUsuario,
                    'texto' => $linha['texto_mensagem'],
                    'enviada_em' => $linha['enviada_em']
                ];
            }

            // quem abriu a conversa leu as mensagens do outro lado (só até a última que recebeu)
            if ($maiorId > 0) {
                $this->executar(
                    $conexao,
                    "UPDATE $tm SET lida_em = :agora
                      WHERE id_conversa = :conversa AND id_autor <> :usuario
                        AND lida_em IS NULL AND id_mensagem <= :maior",
                    [
                        ':agora' => $this->agora(),
                        ':conversa' => $idConversa,
                        ':usuario' => $idUsuario,
                        ':maior' => $maiorId
                    ]
                );
            }

            // até qual mensagem MINHA o outro lado já leu (para o "visto" da bolha)
            $lidasAte = (int) $this->executar(
                $conexao,
                "SELECT COALESCE(MAX(id_mensagem), 0) FROM $tm
                  WHERE id_conversa = :conversa AND id_autor = :usuario AND lida_em IS NOT NULL",
                [':conversa' => $idConversa, ':usuario' => $idUsuario]
            )->fetchColumn();

            $retorno = RespostaProcesso::resposta('Mensagens consultadas', true, $mensagens);
            $retorno['conversa'] = [
                'id' => $idConversa,
                'status' => $conversa['status_conversa']
            ];
            $retorno['lidas_ate'] = $lidasAte;
            $retorno['agora'] = $this->agora();
            return $retorno;

        } catch (Exception $erro) {
            return $this->erro('Erro ao buscar as mensagens', $erro);
        }
    }

    // ---------- encerrar (só representante) ----------

    public function encerrarConversa(int $idConversa, int $idRepresentante): array {
        try {
            $conexao = $this->conectar();
            $tc = $GLOBALS['conversa'];

            $conversa = $this->buscarConversaDoParticipante($conexao, $idConversa, $idRepresentante);
            // só o representante da conversa encerra (o denunciante também "participa", mas não encerra)
            if (!$conversa || (int) $conversa['id_representante'] !== $idRepresentante) {
                return RespostaProcesso::resposta('Conversa não encontrada');
            }
            if ($conversa['status_conversa'] === 'encerrada') {
                return RespostaProcesso::resposta('Esta conversa já foi encerrada');
            }

            $this->executar(
                $conexao,
                "UPDATE $tc SET status_conversa = 'encerrada', encerrada_em = :agora
                  WHERE id_conversa = :conversa AND id_representante = :rep",
                [':agora' => $this->agora(), ':conversa' => $idConversa, ':rep' => $idRepresentante]
            );

            return RespostaProcesso::resposta('Conversa encerrada', true, ['status' => 'encerrada']);

        } catch (Exception $erro) {
            return $this->erro('Erro ao encerrar a conversa', $erro);
        }
    }

    // ---------- lista de conversas do usuário ----------

    public function listarConversas(int $idUsuario): array {
        try {
            $conexao = $this->conectar();
            $tc = $GLOBALS['conversa'];
            $tm = $GLOBALS['mensagem'];
            $td = $GLOBALS['denuncia'];
            $tu = $GLOBALS['usuario'];

            $linhas = $this->executar(
                $conexao,
                "SELECT
                    c.id_conversa, c.id_denuncia, c.id_representante, c.status_conversa, c.criada_em,
                    d.titulo_denuncia, d.cidade_denuncia, d.anonima_denuncia,
                    u.nome_usuario, u.sobrenome_usuario,
                    (SELECT m.texto_mensagem FROM $tm m
                      WHERE m.id_conversa = c.id_conversa
                      ORDER BY m.id_mensagem DESC LIMIT 1) AS ultima_texto,
                    (SELECT MAX(m.enviada_em) FROM $tm m
                      WHERE m.id_conversa = c.id_conversa) AS ultima_em,
                    (SELECT COUNT(*) FROM $tm m
                      WHERE m.id_conversa = c.id_conversa
                        AND m.id_autor <> :nao_lida_de
                        AND m.lida_em IS NULL) AS nao_lidas
                   FROM $tc c
                   JOIN $td d ON d.id_denuncia = c.id_denuncia
                   JOIN $tu u ON u.id_usuario = c.id_denunciante
                  WHERE c.id_representante = :rep OR c.id_denunciante = :den
                  ORDER BY COALESCE(
                      (SELECT MAX(m2.enviada_em) FROM $tm m2 WHERE m2.id_conversa = c.id_conversa),
                      c.criada_em
                  ) DESC, c.id_conversa DESC",
                [':nao_lida_de' => $idUsuario, ':rep' => $idUsuario, ':den' => $idUsuario]
            )->fetchAll(PDO::FETCH_ASSOC);

            $conversas = [];
            $totalNaoLidas = 0;

            foreach ($linhas as $linha) {
                $souRepresentante = (int) $linha['id_representante'] === $idUsuario;

                if ($souRepresentante) {
                    // denúncia anônima: o nome NUNCA sai do servidor
                    $interlocutor = !empty($linha['anonima_denuncia'])
                        ? 'Denunciante'
                        : $linha['nome_usuario'] . ' ' . mb_substr($linha['sobrenome_usuario'], 0, 1) . '.';
                    $semMensagens = 'Conversa iniciada. Envie a primeira mensagem.';
                } else {
                    $interlocutor = 'Prefeitura de ' . $linha['cidade_denuncia'];
                    $semMensagens = 'A prefeitura iniciou uma conversa.';
                }

                $naoLidas = (int) $linha['nao_lidas'];
                $totalNaoLidas += $naoLidas;

                $previa = $linha['ultima_texto'] ?? $semMensagens;
                if (mb_strlen($previa) > 80) {
                    $previa = mb_substr($previa, 0, 79) . '…';
                }

                $conversas[] = [
                    'id' => (int) $linha['id_conversa'],
                    'denuncia_id' => (int) $linha['id_denuncia'],
                    'titulo' => $linha['titulo_denuncia'],
                    'interlocutor' => $interlocutor,
                    'sou_representante' => $souRepresentante,
                    'status' => $linha['status_conversa'],
                    'previa' => $previa,
                    'ultima_em' => $linha['ultima_em'] ?? $linha['criada_em'],
                    'nao_lidas' => $naoLidas
                ];
            }

            $retorno = RespostaProcesso::resposta('Conversas consultadas', true, $conversas);
            $retorno['total_nao_lidas'] = $totalNaoLidas;
            $retorno['agora'] = $this->agora();
            return $retorno;

        } catch (Exception $erro) {
            return $this->erro('Erro ao buscar as conversas', $erro);
        }
    }
}
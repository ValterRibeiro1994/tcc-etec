<?php

// Todas as notificações pertencem a um usuário do banco. O navegador nunca define o destinatário.
class NotificacoesRepositorio {

    // Reutiliza a conexão da operação que originou a notificação. Assim funciona dentro de transações.
    public static function registrar(PDO $conexao, int $destinatario, ?int $denuncia, string $tipo, string $titulo, string $mensagem): void {
        $sql = $conexao->prepare(
            'INSERT INTO tb_notificacao (id_usuario, id_denuncia, tipo_notificacao, titulo_notificacao, mensagem_notificacao, criada_em)
             VALUES (:usuario, :denuncia, :tipo, :titulo, :mensagem, :criada)'
        );
        $sql->bindValue(':usuario', $destinatario, PDO::PARAM_INT);
        $sql->bindValue(':denuncia', $denuncia, $denuncia === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $sql->bindValue(':tipo', $tipo);
        $sql->bindValue(':titulo', mb_substr($titulo, 0, 100));
        $sql->bindValue(':mensagem', mb_substr($mensagem, 0, 300));
        $sql->bindValue(':criada', date($GLOBALS['formato-data-hora']));
        $sql->execute();
    }

    // Representantes da mesma cidade/UF e cuja área abrange a categoria da denúncia.
    // Evita avisar a pessoa que gerou o evento.
    public static function avisarRepresentantes(PDO $conexao, int $denuncia, int $autorEvento, string $tipo, string $titulo, string $mensagem): void {
        $sql = $conexao->prepare(
            "SELECT u.id_usuario FROM tb_usuario u
             INNER JOIN tb_representante_aprovado a ON a.id_usuario = u.id_usuario
             INNER JOIN tb_denuncia d ON d.id_denuncia = :denuncia
             WHERE u.perfil_usuario = 'representante'
               AND u.cidade_usuario = d.cidade_denuncia
               AND u.estado_usuario = d.estado_denuncia
               AND (u.area_atuacao_usuario IS NULL OR u.area_atuacao_usuario = '' OR u.area_atuacao_usuario = d.nome_categoria)
               AND u.id_usuario <> :autor"
        );
        $sql->execute([':denuncia' => $denuncia, ':autor' => $autorEvento]);
        foreach ($sql->fetchAll(PDO::FETCH_COLUMN) as $destinatario) {
            self::registrar($conexao, (int) $destinatario, $denuncia, $tipo, $titulo, $mensagem);
        }
    }

    public static function avisarComentario(PDO $conexao, int $denuncia, int $autorEvento): void {
        $sql = $conexao->prepare('SELECT id_usuario FROM tb_denuncia WHERE id_denuncia = :id');
        $sql->execute([':id' => $denuncia]);
        $autorDenuncia = (int) $sql->fetchColumn();
        if ($autorDenuncia > 0 && $autorDenuncia !== $autorEvento) {
            self::registrar($conexao, $autorDenuncia, $denuncia, 'comentario', 'Novo comentário',
                'Uma pessoa comentou na sua denúncia #' . $denuncia . '.');
        }
        // Não repetir notificação ao dono, caso ele também seja representante.
        $sql = $conexao->prepare(
            "SELECT u.id_usuario FROM tb_usuario u
             JOIN tb_representante_aprovado a ON a.id_usuario = u.id_usuario
             JOIN tb_denuncia d ON d.id_denuncia = :denuncia
             WHERE u.perfil_usuario = 'representante'
               AND u.cidade_usuario = d.cidade_denuncia AND u.estado_usuario = d.estado_denuncia
               AND (u.area_atuacao_usuario IS NULL OR u.area_atuacao_usuario = '' OR u.area_atuacao_usuario = d.nome_categoria)
               AND u.id_usuario <> :evento AND u.id_usuario <> :dono"
        );
        $sql->execute([':denuncia' => $denuncia, ':evento' => $autorEvento, ':dono' => $autorDenuncia]);
        foreach ($sql->fetchAll(PDO::FETCH_COLUMN) as $id) {
            self::registrar($conexao, (int) $id, $denuncia, 'comentario', 'Novo comentário na área',
                'Há um novo comentário na denúncia #' . $denuncia . '.');
        }
    }

    // O e-mail vem do token validado pelo servidor. O id é resolvido no banco.
    public function minhas(string $email): array {
        try {
            $conexao = (new Conexao())->getConexao();
            $sql = $conexao->prepare(
                'SELECT n.id_notificacao AS id, n.id_denuncia AS denuncia, n.tipo_notificacao AS tipo,
                        n.titulo_notificacao AS titulo, n.mensagem_notificacao AS mensagem,
                        n.criada_em AS data, (n.lida_em IS NOT NULL) AS lida
                 FROM tb_notificacao n JOIN tb_usuario u ON u.id_usuario = n.id_usuario
                 WHERE u.email_usuario = :email ORDER BY n.id_notificacao DESC LIMIT 150'
            );
            $sql->execute([':email' => $email]);
            $lista = $sql->fetchAll(PDO::FETCH_ASSOC);
            foreach ($lista as &$n) {
                $n['id'] = (int) $n['id'];
                $n['denuncia'] = $n['denuncia'] === null ? null : (int) $n['denuncia'];
                $n['lida'] = (int) $n['lida'];
            }
            unset($n);
            $sql = $conexao->prepare(
                'SELECT COUNT(*) FROM tb_notificacao n JOIN tb_usuario u ON u.id_usuario=n.id_usuario
                 WHERE u.email_usuario=:email AND n.lida_em IS NULL'
            );
            $sql->execute([':email' => $email]);
            $retorno = RespostaProcesso::resposta('Notificações consultadas', true, $lista);
            $retorno['nao_lidas'] = (int) $sql->fetchColumn();
            return $retorno;
        } catch (PDOException $erro) {
            error_log('Notificações: ' . $erro->getMessage());
            return RespostaProcesso::resposta('Não foi possível consultar as notificações. Confira a migração SQL.');
        }
    }

    public function marcar(string $email, ?int $id = null): array {
        try {
            $conexao = (new Conexao())->getConexao();
            $filtro = $id === null ? '' : ' AND n.id_notificacao = :id';
            $sql = $conexao->prepare(
                'UPDATE tb_notificacao n JOIN tb_usuario u ON u.id_usuario = n.id_usuario
                 SET n.lida_em = :agora
                 WHERE u.email_usuario = :email AND n.lida_em IS NULL' . $filtro
            );
            $sql->bindValue(':agora', date($GLOBALS['formato-data-hora']));
            $sql->bindValue(':email', $email);
            if ($id !== null) $sql->bindValue(':id', $id, PDO::PARAM_INT);
            $sql->execute();
            return RespostaProcesso::resposta('Notificações marcadas como lidas', true,
                ['alteradas' => $sql->rowCount()]);
        } catch (PDOException $erro) {
            error_log('Marcação de notificação: ' . $erro->getMessage());
            return RespostaProcesso::resposta('Não foi possível atualizar as notificações.');
        }
    }
}

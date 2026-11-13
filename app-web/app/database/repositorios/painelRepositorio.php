<?php

// Painel da prefeitura: todas as consultas conferem o representante REAL no banco,
// além do JWT já conferido pelo controller. Nunca confia em cidade ou área recebida por POST.
class PainelRepositorio {
    private const STATUS = ['pendente', 'em analise', 'em andamento', 'concluida'];

    private function executar(PDO $conexao, string $sql, array $parametros = []): PDOStatement {
        $stmt = $conexao->prepare($sql);
        foreach ($parametros as $chave => $valor) {
            $stmt->bindValue($chave, $valor, is_int($valor) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt;
    }

    private function representante(PDO $conexao, string $email): ?array {
        $u = $this->executar($conexao,
            'SELECT u.id_usuario, u.perfil_usuario, u.cidade_usuario, u.estado_usuario, u.area_atuacao_usuario
             FROM tb_usuario u
             INNER JOIN tb_representante_aprovado a ON a.id_usuario = u.id_usuario
             WHERE u.email_usuario = :email', [':email' => $email]
        )->fetch(PDO::FETCH_ASSOC);
        return ($u && $u['perfil_usuario'] === 'representante') ? $u : null;
    }

    private function escopo(array $representante): array {
        $where = 'd.cidade_denuncia = :cidade AND d.estado_denuncia = :uf';
        $args = [':cidade' => $representante['cidade_usuario'], ':uf' => $representante['estado_usuario']];
        if (!empty($representante['area_atuacao_usuario'])) {
            $where .= ' AND d.nome_categoria = :area';
            $args[':area'] = $representante['area_atuacao_usuario'];
        }
        return [$where, $args];
    }

    private function filtros(array $filtros): array {
        $where = '';
        $args = [];
        if (in_array($filtros['status'] ?? '', self::STATUS, true)) {
            $where .= ' AND d.status_denuncia = :status';
            $args[':status'] = $filtros['status'];
        }
        if (trim((string) ($filtros['categoria'] ?? '')) !== '') {
            $where .= ' AND d.nome_categoria = :categoria';
            $args[':categoria'] = mb_substr(trim($filtros['categoria']), 0, 30);
        }
        if (trim((string) ($filtros['busca'] ?? '')) !== '') {
            $where .= ' AND (d.titulo_denuncia LIKE :busca1 OR d.bairro_denuncia LIKE :busca2 OR d.logradouro_denuncia LIKE :busca3)';
            $texto = '%' . mb_substr(trim($filtros['busca']), 0, 100) . '%';
            $args[':busca1'] = $texto;
            $args[':busca2'] = $texto;
            $args[':busca3'] = $texto;
        }
        return [$where, $args];
    }

    public function resumo(string $email): array {
        try {
            $db = (new Conexao())->getConexao();
            $rep = $this->representante($db, $email);
            if (!$rep) return RespostaProcesso::resposta('Acesso exclusivo a representantes aprovados');
            [$onde, $args] = $this->escopo($rep);

            $contagens = ['pendente' => 0, 'em analise' => 0, 'em andamento' => 0, 'concluida' => 0];
            $linhas = $this->executar($db,
                "SELECT d.status_denuncia, COUNT(*) quantidade FROM tb_denuncia d WHERE $onde GROUP BY d.status_denuncia",
                $args)->fetchAll(PDO::FETCH_ASSOC);
            foreach ($linhas as $item) $contagens[$item['status_denuncia']] = (int) $item['quantidade'];
            $contagens['total'] = array_sum($contagens);

            $categorias = $this->executar($db,
                "SELECT d.nome_categoria AS categoria, COUNT(*) quantidade FROM tb_denuncia d WHERE $onde
                 GROUP BY d.nome_categoria ORDER BY quantidade DESC, categoria", $args
            )->fetchAll(PDO::FETCH_ASSOC);
            foreach ($categorias as &$cat) $cat['quantidade'] = (int) $cat['quantidade'];
            unset($cat);

            $atrasadas = (int) $this->executar($db,
                "SELECT COUNT(*) FROM tb_denuncia d WHERE $onde
                 AND d.status_denuncia <> 'concluida' AND d.data_denuncia < DATE_SUB(NOW(), INTERVAL 7 DAY)",
                $args)->fetchColumn();
            $fechadasMes = (int) $this->executar($db,
                "SELECT COUNT(DISTINCT h.id_denuncia) FROM tb_historico_status h
                 JOIN tb_denuncia d ON d.id_denuncia = h.id_denuncia WHERE $onde
                 AND h.status_novo = 'concluida'
                 AND h.alterado_em >= DATE_FORMAT(CURRENT_DATE(), '%Y-%m-01')", $args
            )->fetchColumn();
            return RespostaProcesso::resposta('Painel consultado', true, [
                'contagens' => $contagens,
                'categorias' => $categorias,
                'atrasadas' => $atrasadas,
                'concluidas_mes' => $fechadasMes,
                'cidade' => $rep['cidade_usuario'] . '/' . $rep['estado_usuario'],
                'area' => $rep['area_atuacao_usuario'] ?: 'Todas as categorias'
            ]);
        } catch (PDOException $erro) {
            error_log('Painel resumo: ' . $erro->getMessage());
            return RespostaProcesso::resposta('Falha ao carregar o painel. Confira as tabelas SQL.');
        }
    }

    public function listar(string $email, int $pagina, array $filtros, bool $exportar = false): array {
        try {
            $db = (new Conexao())->getConexao();
            $rep = $this->representante($db, $email);
            if (!$rep) return RespostaProcesso::resposta('Acesso exclusivo a representantes aprovados');
            [$escopo, $args] = $this->escopo($rep);
            [$extra, $extras] = $this->filtros($filtros);
            $args = array_merge($args, $extras);
            $onde = $escopo . $extra;
            $quantidade = (int) $this->executar($db,
                "SELECT COUNT(*) FROM tb_denuncia d WHERE $onde", $args)->fetchColumn();
            $porPagina = $exportar ? 1000 : 10;
            $paginas = max(1, (int) ceil($quantidade / $porPagina));
            $pagina = max(1, min($paginas, $pagina));
            $args[':limite'] = $porPagina;
            $args[':inicio'] = ($pagina - 1) * $porPagina;
            $lista = $this->executar($db,
                "SELECT d.id_denuncia AS id, d.titulo_denuncia AS titulo, d.nome_categoria AS categoria,
                        d.bairro_denuncia AS bairro, d.cidade_denuncia AS cidade,
                        d.status_denuncia AS status, d.data_denuncia AS data,
                        d.anonima_denuncia AS anonima
                 FROM tb_denuncia d WHERE $onde
                 ORDER BY d.data_denuncia DESC, d.id_denuncia DESC
                 LIMIT :limite OFFSET :inicio", $args
            )->fetchAll(PDO::FETCH_ASSOC);
            foreach ($lista as &$d) {
                $d['id'] = (int) $d['id'];
                $d['anonima'] = (int) $d['anonima'];
            }
            unset($d);
            $ret = RespostaProcesso::resposta('Denúncias consultadas', true, $lista);
            $ret['paginacao'] = ['pagina' => $pagina, 'por_pagina' => $porPagina,
                'total' => $quantidade, 'total_paginas' => $paginas];
            return $ret;
        } catch (PDOException $erro) {
            error_log('Painel lista: ' . $erro->getMessage());
            return RespostaProcesso::resposta('Falha ao consultar denúncias do painel.');
        }
    }

    public function historico(string $email, int $denuncia): array {
        try {
            $db = (new Conexao())->getConexao();
            $rep = $this->representante($db, $email);
            if (!$rep) return RespostaProcesso::resposta('Acesso exclusivo a representantes aprovados');
            [$onde, $args] = $this->escopo($rep);
            $args[':id'] = $denuncia;
            $existe = $this->executar($db,
                "SELECT 1 FROM tb_denuncia d WHERE d.id_denuncia = :id AND $onde", $args)->fetchColumn();
            if (!$existe) return RespostaProcesso::resposta('Denúncia fora da sua área de atendimento');
            $registros = $this->executar($db,
                'SELECT h.status_anterior, h.status_novo, h.justificativa, h.alterado_em,
                        COALESCE(u.nome_usuario, \'Representante removido\') AS responsavel
                 FROM tb_historico_status h LEFT JOIN tb_usuario u ON u.id_usuario=h.id_representante
                 WHERE h.id_denuncia = :id ORDER BY h.id_historico DESC LIMIT 30', [':id' => $denuncia]
            )->fetchAll(PDO::FETCH_ASSOC);
            return RespostaProcesso::resposta('Histórico consultado', true, $registros);
        } catch (PDOException $erro) {
            error_log('Painel histórico: ' . $erro->getMessage());
            return RespostaProcesso::resposta('Falha ao consultar histórico.');
        }
    }

    public function alterarStatus(string $email, int $id, string $statusNovo, string $justificativa): array {
        if (!in_array($statusNovo, self::STATUS, true)) return RespostaProcesso::resposta('Status inválido.');
        if (mb_strlen($justificativa) < 5 || mb_strlen($justificativa) > 350)
            return RespostaProcesso::resposta('A justificativa precisa ter entre 5 e 350 caracteres.');
        $db = null;
        try {
            $db = (new Conexao())->getConexao();
            $rep = $this->representante($db, $email);
            if (!$rep) return RespostaProcesso::resposta('Acesso exclusivo a representantes aprovados');
            [$onde, $args] = $this->escopo($rep);
            $args[':id'] = $id;
            $db->beginTransaction();
            // FOR UPDATE impede duas mudanças simultâneas de gravarem histórico incorreto.
            $denuncia = $this->executar($db,
                "SELECT d.id_usuario, d.status_denuncia, d.titulo_denuncia FROM tb_denuncia d
                 WHERE d.id_denuncia = :id AND $onde FOR UPDATE", $args)->fetch(PDO::FETCH_ASSOC);
            if (!$denuncia) {
                $db->rollBack();
                return RespostaProcesso::resposta('Denúncia não localizada na sua cidade ou área.');
            }
            $anterior = $denuncia['status_denuncia'];
            if ($anterior === $statusNovo) {
                $db->rollBack();
                return RespostaProcesso::resposta('A denúncia já está nesse status.');
            }
            $this->executar($db,
                'UPDATE tb_denuncia SET status_denuncia = :status WHERE id_denuncia = :id',
                [':status' => $statusNovo, ':id' => $id]);
            $this->executar($db,
                'INSERT INTO tb_historico_status (id_denuncia, id_representante, status_anterior,
                     status_novo, justificativa, alterado_em)
                 VALUES (:denuncia, :rep, :antigo, :novo, :motivo, :agora)',
                [':denuncia' => $id, ':rep' => (int) $rep['id_usuario'], ':antigo' => $anterior,
                 ':novo' => $statusNovo, ':motivo' => $justificativa,
                 ':agora' => date($GLOBALS['formato-data-hora'])]);
            if ((int) $denuncia['id_usuario'] !== (int) $rep['id_usuario']) {
                NotificacoesRepositorio::registrar($db, (int) $denuncia['id_usuario'], $id, 'status',
                    'Status da sua denúncia atualizado',
                    'A denúncia #' . $id . ' mudou de "' . $anterior . '" para "' . $statusNovo . '". ' . $justificativa);
            }
            $db->commit();
            return RespostaProcesso::resposta('Status atualizado, histórico salvo e autor notificado.', true,
                ['id' => $id, 'status' => $statusNovo]);
        } catch (Throwable $erro) {
            if ($db && $db->inTransaction()) $db->rollBack();
            error_log('Alteração de status: ' . $erro->getMessage());
            return RespostaProcesso::resposta('Não foi possível alterar o status. Confira a instalação SQL.');
        }
    }
}

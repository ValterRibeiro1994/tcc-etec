<?php
// Consultas exclusivas do administrador-geral. Usa PDO com parâmetros em todas as entradas.
class AdminRepositorio {
    private function consulta(PDO $db, string $sql, array $parametros = []): PDOStatement {
        $stmt = $db->prepare($sql);
        foreach ($parametros as $chave => $valor) {
            $stmt->bindValue($chave, $valor, is_int($valor) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt;
    }

    public function idAdministrador(string $email): ?int {
        try {
            $db = (new Conexao())->getConexao();
            $id = $this->consulta($db,
                "SELECT id_usuario FROM tb_usuario
                 WHERE email_usuario = :email AND perfil_usuario = 'administrador' LIMIT 1",
                [':email' => $email])->fetchColumn();
            return $id === false ? null : (int)$id;
        } catch (PDOException $erro) {
            error_log('Verificação de administrador: ' . $erro->getMessage());
            return null;
        }
    }

    public function resumo(): array {
        try {
            $db = (new Conexao())->getConexao();
            $perfis = ['municipe' => 0, 'representante' => 0, 'administrador' => 0];
            $sql = $this->consulta($db,
                'SELECT perfil_usuario AS perfil, COUNT(*) AS quantidade
                 FROM tb_usuario GROUP BY perfil_usuario');
            foreach ($sql->fetchAll(PDO::FETCH_ASSOC) as $linha) {
                $perfis[$linha['perfil']] = (int)$linha['quantidade'];
            }
            $aprovados = (int)$this->consulta($db,
                "SELECT COUNT(*) FROM tb_representante_aprovado a
                 JOIN tb_usuario u ON u.id_usuario = a.id_usuario
                 WHERE u.perfil_usuario = 'representante'")->fetchColumn();
            $status = ['pendente' => 0, 'em analise' => 0, 'em andamento' => 0, 'concluida' => 0];
            $consultaStatus = $this->consulta($db,
                'SELECT status_denuncia AS status, COUNT(*) AS quantidade
                 FROM tb_denuncia GROUP BY status_denuncia');
            foreach ($consultaStatus->fetchAll(PDO::FETCH_ASSOC) as $linha) {
                $status[$linha['status']] = (int)$linha['quantidade'];
            }
            $recentes = $this->consulta($db,
                'SELECT id_denuncia AS id, titulo_denuncia AS titulo,
                        nome_categoria AS categoria, status_denuncia AS status,
                        cidade_denuncia AS cidade, data_denuncia AS data
                 FROM tb_denuncia ORDER BY id_denuncia DESC LIMIT 8')->fetchAll(PDO::FETCH_ASSOC);
            $auditoria = $this->consulta($db,
                "SELECT a.acao, a.efetuado_em AS data,
                        COALESCE(ad.nome_usuario, 'Conta removida') AS administrador,
                        COALESCE(r.nome_usuario, 'Conta removida') AS representante
                 FROM tb_admin_auditoria a
                 LEFT JOIN tb_usuario ad ON ad.id_usuario = a.id_administrador
                 LEFT JOIN tb_usuario r ON r.id_usuario = a.id_representante
                 ORDER BY a.id_auditoria DESC LIMIT 15")->fetchAll(PDO::FETCH_ASSOC);
            return RespostaProcesso::resposta('Resumo geral', true, [
                'perfis' => $perfis, 'aprovados' => $aprovados,
                'pendentes' => max(0, $perfis['representante'] - $aprovados),
                'denuncias' => array_sum($status), 'status' => $status,
                'recentes' => $recentes, 'auditoria' => $auditoria
            ]);
        } catch (PDOException $erro) {
            error_log('Admin resumo: ' . $erro->getMessage());
            return RespostaProcesso::resposta('Não foi possível carregar o resumo. Execute as migrações 06 e 07.');
        }
    }

    public function representantes(int $pagina, string $busca, string $situacao): array {
        try {
            $db = (new Conexao())->getConexao();
            $filtro = "u.perfil_usuario = 'representante'";
            if ($situacao === 'pendentes') $filtro .= ' AND a.id_usuario IS NULL';
            if ($situacao === 'aprovados') $filtro .= ' AND a.id_usuario IS NOT NULL';
            $args = [];
            if ($busca !== '') {
                $filtro .= ' AND (u.nome_usuario LIKE :nome OR u.sobrenome_usuario LIKE :sobrenome'
                         . ' OR u.email_usuario LIKE :email OR u.cidade_usuario LIKE :cidade)';
                $termo = '%' . $busca . '%';
                $args = [':nome' => $termo, ':sobrenome' => $termo,
                         ':email' => $termo, ':cidade' => $termo];
            }
            $origem = ' FROM tb_usuario u LEFT JOIN tb_representante_aprovado a ON a.id_usuario = u.id_usuario';
            $total = (int)$this->consulta($db,
                'SELECT COUNT(*)' . $origem . ' WHERE ' . $filtro, $args)->fetchColumn();
            $porPagina = 10;
            $totalPaginas = max(1, (int)ceil($total / $porPagina));
            $pagina = min($pagina, $totalPaginas);
            $args[':limite'] = $porPagina;
            $args[':inicio'] = ($pagina - 1) * $porPagina;
            $dados = $this->consulta($db,
                'SELECT u.id_usuario AS id, u.nome_usuario AS nome,
                        u.sobrenome_usuario AS sobrenome, u.email_usuario AS email,
                        u.cidade_usuario AS cidade, u.estado_usuario AS estado,
                        u.area_atuacao_usuario AS area, u.orgao_usuario AS orgao,
                        u.cargo_usuario AS cargo, a.aprovado_em AS aprovado_em'
                . $origem . ' WHERE ' . $filtro
                . ' ORDER BY (a.id_usuario IS NULL) DESC, u.id_usuario DESC'
                . ' LIMIT :limite OFFSET :inicio', $args)->fetchAll(PDO::FETCH_ASSOC);
            foreach ($dados as &$item) $item['id'] = (int)$item['id'];
            unset($item);
            $resposta = RespostaProcesso::resposta('Representantes encontrados', true, $dados);
            $resposta['paginacao'] = ['pagina' => $pagina, 'total' => $total,
                'por_pagina' => $porPagina, 'total_paginas' => $totalPaginas];
            return $resposta;
        } catch (PDOException $erro) {
            error_log('Admin representantes: ' . $erro->getMessage());
            return RespostaProcesso::resposta('Não foi possível consultar representantes. Confira a migração 06.');
        }
    }

    public function decidir(int $idAdmin, int $idRepresentante, string $acao): array {
        $db = null;
        try {
            $db = (new Conexao())->getConexao();
            $db->beginTransaction();
            // Confirma permissão ATUAL dentro da transação (token antigo não basta).
            $admin = $this->consulta($db,
                "SELECT id_usuario FROM tb_usuario WHERE id_usuario=:id AND perfil_usuario='administrador' FOR UPDATE",
                [':id' => $idAdmin])->fetchColumn();
            if ($admin === false) {
                $db->rollBack();
                return RespostaProcesso::resposta('Sua permissão administrativa foi revogada.');
            }
            $rep = $this->consulta($db,
                "SELECT id_usuario FROM tb_usuario WHERE id_usuario=:id AND perfil_usuario='representante' FOR UPDATE",
                [':id' => $idRepresentante])->fetchColumn();
            if ($rep === false) {
                $db->rollBack();
                return RespostaProcesso::resposta('A conta informada não é de representante.');
            }
            $existe = $this->consulta($db,
                'SELECT id_usuario FROM tb_representante_aprovado WHERE id_usuario = :id FOR UPDATE',
                [':id' => $idRepresentante])->fetchColumn() !== false;
            if ($acao === 'aprovar' && $existe) {
                $db->rollBack();
                return RespostaProcesso::resposta('Esse representante já está aprovado.');
            }
            if ($acao === 'revogar' && !$existe) {
                $db->rollBack();
                return RespostaProcesso::resposta('Esse representante já não está aprovado.');
            }
            if ($acao === 'aprovar') {
                $this->consulta($db,
                    'INSERT INTO tb_representante_aprovado (id_usuario, aprovado_em) VALUES (:id, NOW())',
                    [':id' => $idRepresentante]);
            } else {
                $this->consulta($db,
                    'DELETE FROM tb_representante_aprovado WHERE id_usuario = :id',
                    [':id' => $idRepresentante]);
            }
            $this->consulta($db,
                'INSERT INTO tb_admin_auditoria (id_administrador, id_representante, acao)
                 VALUES (:admin, :rep, :acao)',
                [':admin' => $idAdmin, ':rep' => $idRepresentante, ':acao' => $acao]);
            NotificacoesRepositorio::registrar($db, $idRepresentante, null, 'sistema',
                'Autorização de representante',
                $acao === 'aprovar'
                    ? 'Sua conta foi aprovada para administrar denúncias da sua região.'
                    : 'Seu acesso ao painel de representante foi revogado pela administração.');
            $db->commit();
            return RespostaProcesso::resposta(
                $acao === 'aprovar' ? 'Representante aprovado.' : 'Autorização revogada.', true);
        } catch (Throwable $erro) {
            if ($db && $db->inTransaction()) $db->rollBack();
            error_log('Admin decidir: ' . $erro->getMessage());
            return RespostaProcesso::resposta('Não foi possível concluir a ação. Confira as migrações.');
        }
    }

    // Contas por função (munícipe, representante ou administrador), com busca e paginação.
    // A contagem por função respeita a busca, para os filtros mostrarem quantos resultados há em cada um.
    public function usuarios(int $idAdmin, int $pagina, string $busca, string $perfil): array {
        try {
            $db = (new Conexao())->getConexao();
            $filtro = '1 = 1';
            $args = [];
            if ($busca !== '') {
                $filtro = "(CONCAT(u.nome_usuario, ' ', u.sobrenome_usuario) LIKE :completo"
                        . ' OR u.email_usuario LIKE :email OR u.cidade_usuario LIKE :cidade)';
                $termo = '%' . addcslashes($busca, '\\%_') . '%';
                $args = [':completo' => $termo, ':email' => $termo, ':cidade' => $termo];
            }
            $resumo = ['municipe' => 0, 'representante' => 0, 'administrador' => 0];
            $contagem = $this->consulta($db,
                'SELECT u.perfil_usuario AS perfil, COUNT(*) AS quantidade
                 FROM tb_usuario u WHERE ' . $filtro . ' GROUP BY u.perfil_usuario', $args);
            foreach ($contagem->fetchAll(PDO::FETCH_ASSOC) as $linha) {
                if (array_key_exists($linha['perfil'], $resumo)) {
                    $resumo[$linha['perfil']] = (int)$linha['quantidade'];
                }
            }
            if (!array_key_exists($perfil, $resumo)) $perfil = 'municipe';
            $total = $resumo[$perfil];
            $porPagina = 10;
            $totalPaginas = max(1, (int)ceil($total / $porPagina));
            $pagina = min($pagina, $totalPaginas);
            $args[':perfil'] = $perfil;
            $args[':eu'] = $idAdmin;
            $args[':limite'] = $porPagina;
            $args[':inicio'] = ($pagina - 1) * $porPagina;
            $dados = $this->consulta($db,
                'SELECT u.id_usuario AS id, u.nome_usuario AS nome,
                        u.sobrenome_usuario AS sobrenome, u.email_usuario AS email,
                        u.cidade_usuario AS cidade, u.estado_usuario AS estado,
                        u.perfil_usuario AS perfil, u.area_atuacao_usuario AS area,
                        u.orgao_usuario AS orgao, u.cargo_usuario AS cargo,
                        a.aprovado_em AS aprovado_em, (u.id_usuario = :eu) AS voce
                 FROM tb_usuario u
                 LEFT JOIN tb_representante_aprovado a ON a.id_usuario = u.id_usuario
                 WHERE u.perfil_usuario = :perfil AND ' . $filtro
                . ' ORDER BY u.nome_usuario ASC, u.sobrenome_usuario ASC, u.id_usuario ASC'
                . ' LIMIT :limite OFFSET :inicio', $args)->fetchAll(PDO::FETCH_ASSOC);
            foreach ($dados as &$item) {
                $item['id'] = (int)$item['id'];
                $item['voce'] = (int)$item['voce'] === 1;
            }
            unset($item);
            $resposta = RespostaProcesso::resposta('Usuários encontrados', true, $dados);
            $resposta['paginacao'] = ['pagina' => $pagina, 'total' => $total,
                'por_pagina' => $porPagina, 'total_paginas' => $totalPaginas];
            $resposta['resumo'] = $resumo;
            return $resposta;
        } catch (PDOException $erro) {
            error_log('Admin usuarios: ' . $erro->getMessage());
            return RespostaProcesso::resposta('Não foi possível consultar os usuários. Confira as migrações (detalhes no log de erros do PHP).');
        }
    }

    // Troca a função de uma conta. Tudo acontece em uma transação e a permissão do
    // administrador é conferida de novo lá dentro.
    public function alterarFuncao(int $idAdmin, int $idUsuario, string $novaFuncao): array {
        $nomes = ['municipe' => 'munícipe', 'representante' => 'representante', 'administrador' => 'administrador'];
        if (!array_key_exists($novaFuncao, $nomes)) return RespostaProcesso::resposta('Função inválida.');
        if ($idAdmin === $idUsuario) {
            return RespostaProcesso::resposta('Você não pode alterar a sua própria função.');
        }
        $db = null;
        try {
            $db = (new Conexao())->getConexao();
            $db->beginTransaction();
            $admin = $this->consulta($db,
                "SELECT id_usuario FROM tb_usuario WHERE id_usuario = :id AND perfil_usuario = 'administrador' FOR UPDATE",
                [':id' => $idAdmin])->fetchColumn();
            if ($admin === false) {
                $db->rollBack();
                return RespostaProcesso::resposta('Sua permissão administrativa foi revogada.');
            }
            $atual = $this->consulta($db,
                'SELECT perfil_usuario FROM tb_usuario WHERE id_usuario = :id FOR UPDATE',
                [':id' => $idUsuario])->fetchColumn();
            if ($atual === false) {
                $db->rollBack();
                return RespostaProcesso::resposta('Usuário não encontrado.');
            }
            if ($atual === $novaFuncao) {
                $db->rollBack();
                return RespostaProcesso::resposta('Esse usuário já possui essa função.');
            }
            $this->consulta($db,
                'UPDATE tb_usuario SET perfil_usuario = :perfil WHERE id_usuario = :id',
                [':perfil' => $novaFuncao, ':id' => $idUsuario]);
            // A aprovação só faz sentido para representante: remove para não "voltar" aprovado depois.
            if ($novaFuncao !== 'representante') {
                $this->consulta($db,
                    'DELETE FROM tb_representante_aprovado WHERE id_usuario = :id',
                    [':id' => $idUsuario]);
            }
            $this->consulta($db,
                'INSERT INTO tb_admin_auditoria (id_administrador, id_representante, acao)
                 VALUES (:admin, :usuario, :acao)',
                [':admin' => $idAdmin, ':usuario' => $idUsuario, ':acao' => 'tornar_' . $novaFuncao]);
            $aviso = 'Sua conta agora é de ' . $nomes[$novaFuncao] . '. Saia e entre novamente para atualizar o seu acesso.';
            if ($novaFuncao === 'representante') {
                $aviso = 'Sua conta agora é de representante. O acesso ao painel depende da aprovação da administração. Saia e entre novamente.';
            }
            NotificacoesRepositorio::registrar($db, $idUsuario, null, 'sistema', 'Função da conta alterada', $aviso);
            $db->commit();
            return RespostaProcesso::resposta('Função alterada para ' . $nomes[$novaFuncao] . '.', true);
        } catch (Throwable $erro) {
            if ($db && $db->inTransaction()) $db->rollBack();
            error_log('Admin alterarFuncao: ' . $erro->getMessage());
            return RespostaProcesso::resposta('Não foi possível alterar a função. Execute doc/migracao-admin.sql (detalhes no log de erros do PHP).');
        }
    }

}

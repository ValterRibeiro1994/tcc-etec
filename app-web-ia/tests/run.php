<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/model/objects-values/cpf.php';
require_once __DIR__ . '/../app/model/objects-values/email.php';
require_once __DIR__ . '/../app/model/objects-values/senha.php';
require_once __DIR__ . '/../app/model/objects-values/nome.php';
require_once __DIR__ . '/../app/model/objects-values/data.php';
require_once __DIR__ . '/../app/model/objects-values/descricao.php';
require_once __DIR__ . '/../app/model/objects-values/endereco.php';
require_once __DIR__ . '/../app/model/objects-values/perfil.php';
require_once __DIR__ . '/../app/model/objects-values/prefeitura.php';
require_once __DIR__ . '/../app/service/utilService.php';
require_once __DIR__ . '/../app/model/respostaProcesso.php';
require_once __DIR__ . '/../app/database/repositorios/perfilRepositorio.php';
require_once __DIR__ . '/../app/roteador.php';

class RoteadortesteController {
    public function capturar(array $requisicao): array {
        return $requisicao;
    }
}

$passed = 0;
$failed = 0;

function test(string $name, callable $case): void {
    global $passed, $failed;

    try {
        $case();
        $passed++;
        echo "PASS: $name\n";
    } catch (Throwable $erro) {
        $failed++;
        fwrite(STDERR, "FAIL: $name - {$erro->getMessage()}\n");
    }
}

function expectSame(mixed $expected, mixed $actual): void {
    if ($expected !== $actual) {
        throw new RuntimeException('Valores diferentes: ' . var_export($expected, true) . ' !== ' . var_export($actual, true));
    }
}

function expectThrows(callable $case, string $class = Throwable::class): void {
    try {
        $case();
    } catch (Throwable $erro) {
        if (!$erro instanceof $class) {
            throw new RuntimeException("Esperava $class, recebido " . get_class($erro));
        }
        return;
    }

    throw new RuntimeException("Esperava exceção $class");
}

test('CPF válido e inválido', function (): void {
    $cpf = new Cpf('529.982.247-25');
    $cpf->validarCpf();
    expectSame('52998224725', $cpf->getCpf());
    expectThrows(function (): void {
        (new Cpf('111.111.111-11'))->validarCpf();
    }, InvalidArgumentException::class);
});

test('E-mail válido e inválido', function (): void {
    $email = new Email('pessoa@example.com');
    $email->validarEmail();
    expectSame('pessoa@example.com', $email->getEmail());
    expectThrows(function (): void {
        (new Email('invalido'))->validarEmail();
    }, InvalidArgumentException::class);
});

test('Política de senha aceita forte e rejeita fraca', function (): void {
    (new Senha('Abcdef1!'))->validarSenha();
    expectThrows(function (): void {
        (new Senha('abcdefg1'))->validarSenha();
    }, InvalidArgumentException::class);
});

test('Nome respeita limite e codifica caracteres HTML', function (): void {
    expectSame('&lt;Ana&gt;', (new Nome('<Ana>'))->getNome());
    expectThrows(function (): void {
        new Nome('Ab');
    }, Exception::class);
});

test('Data rejeita datas impossíveis e futuras', function (): void {
    expectSame('2024-02-29', substr((new Data('2024-02-29'))->getData(), 0, 10));
    expectThrows(function (): void {
        new Data('2025-02-29');
    }, Exception::class);
    expectThrows(function (): void {
        new Data((new DateTimeImmutable('tomorrow'))->format('Y-m-d'));
    }, Exception::class);
});

test('Descrição valida campos obrigatórios e limite', function (): void {
    $descricao = new Descricao('Título', new Data('2024-02-29'), 'Infraestrutura', 'Descrição');
    expectSame('Descrição', $descricao->getTexto());
    expectThrows(function (): void {
        (new Descricao())->setTitulo(str_repeat('x', 61));
    }, Exception::class);
});

test('Endereço normaliza campos e valida CEP', function (): void {
    $endereco = new Endereco('São Bernardo', 'sp');
    $endereco->setRua(' Rua Central ');
    $endereco->setNumero('12');
    $endereco->setBairro(' Centro ', validar: true);
    $endereco->setCep('09.700-000');
    expectSame('SÃO BERNARDO', $endereco->getCidade());
    expectSame('Rua Central, 12 - Centro', $endereco->getLogradouro());
    expectSame('09700000', $endereco->getCep());
    expectThrows(function (): void {
        (new Endereco())->setBairro('   ', validar: true);
    }, Exception::class);
});

test('Perfil e área da prefeitura aceitam apenas valores previstos', function (): void {
    $perfil = new Perfil('REPRESENTANTE');
    $perfil->validarPerfil();
    expectSame('representante', $perfil->getPerfil());
    expectThrows(function (): void {
        (new Perfil('superuser'))->validarPerfil();
    }, InvalidArgumentException::class);
    expectThrows(function (): void {
        (new Prefeitura())->setArea(str_repeat('x', 31));
    }, InvalidArgumentException::class);
});

test('Atualização de campo do perfil usa o ID do usuário, não o valor atualizado', function (): void {
    $pdo = new PDO('sqlite::memory:');
    $pdo->exec('CREATE TABLE tb_usuario (id_usuario INTEGER PRIMARY KEY, avisos_email INTEGER)');
    $pdo->exec('INSERT INTO tb_usuario (id_usuario, avisos_email) VALUES (1, 0), (2, 0)');
    $GLOBALS['usuario'] = 'tb_usuario';

    try {
        (new PerfilRepositorio($pdo))->alterarCampo(2, 'avisos_email', 1);
        $valores = array_map('intval', $pdo->query(
            'SELECT id_usuario, avisos_email FROM tb_usuario ORDER BY id_usuario'
        )->fetchAll(PDO::FETCH_KEY_PAIR));
        expectSame([1 => 0, 2 => 1], $valores);
        expectThrows(function () use ($pdo): void {
            (new PerfilRepositorio($pdo))->alterarCampo(2, 'cpf_usuario', '52998224725');
        }, RuntimeException::class);
    } finally {
        unset($GLOBALS['usuario']);
    }
});

test('Consulta de perfil respeita o nome de tabela definido em config.php', function (): void {
    $pdo = new PDO('sqlite::memory:');
    $pdo->exec('CREATE TABLE usuarios_personalizados (
        nome_usuario TEXT, sobrenome_usuario TEXT, email_usuario TEXT, perfil_usuario TEXT,
        cidade_usuario TEXT, estado_usuario TEXT, orgao_usuario TEXT, cargo_usuario TEXT
    )');
    $pdo->exec("INSERT INTO usuarios_personalizados VALUES (
        'Ana', 'Silva', 'ana@example.test', 'municipe', 'São Bernardo do Campo', 'SP', NULL, NULL
    )");
    $GLOBALS['usuario'] = 'usuarios_personalizados';

    set_error_handler(function (int $nivel, string $mensagem): void {
        throw new ErrorException($mensagem, 0, $nivel);
    });
    try {
        $resultado = (new PerfilRepositorio($pdo))->obterDadosPerfil('ana@example.test');
    } finally {
        restore_error_handler();
        unset($GLOBALS['usuario']);
    }

    expectSame(true, $resultado['resposta']);
    expectSame('Ana', $resultado['dados']['nome']);
    expectSame('municipe', $resultado['dados']['perfil']);
    expectSame('São Bernardo do Campo', $resultado['dados']['cidade']);
});

test('Nome de tabela manual não aceita conteúdo SQL executável', function (): void {
    $pdo = new PDO('sqlite::memory:');
    $GLOBALS['usuario'] = 'tb_usuario; DROP TABLE tb_usuario';
    try {
        expectThrows(function () use ($pdo): void {
            (new PerfilRepositorio($pdo))->buscarConta('ana@example.test');
        }, RuntimeException::class);
    } finally {
        unset($GLOBALS['usuario']);
    }
});

test('Exclusão de conta limpa conversas e dependências sem apagar dados de terceiros', function (): void {
    $pdo = new PDO('sqlite::memory:');
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec('CREATE TABLE tb_usuario (id_usuario INTEGER PRIMARY KEY)');
    $pdo->exec('INSERT INTO tb_usuario VALUES (1), (2)');
    $pdo->exec('CREATE TABLE tb_denuncia (
        id_denuncia INTEGER PRIMARY KEY, id_usuario INTEGER NOT NULL,
        FOREIGN KEY (id_usuario) REFERENCES tb_usuario(id_usuario)
    )');
    $pdo->exec('INSERT INTO tb_denuncia VALUES (10, 1), (20, 2)');
    $pdo->exec('CREATE TABLE tb_resposta (
        id_resposta INTEGER PRIMARY KEY, id_usuario INTEGER NOT NULL, id_denuncia INTEGER NOT NULL,
        FOREIGN KEY (id_usuario) REFERENCES tb_usuario(id_usuario),
        FOREIGN KEY (id_denuncia) REFERENCES tb_denuncia(id_denuncia)
    )');
    $pdo->exec('INSERT INTO tb_resposta VALUES (1, 2, 10), (2, 1, 20), (3, 2, 20)');
    $pdo->exec('CREATE TABLE tb_conversa (
        id_conversa INTEGER PRIMARY KEY, id_denuncia INTEGER NOT NULL,
        id_representante INTEGER NOT NULL, id_denunciante INTEGER NOT NULL,
        FOREIGN KEY (id_denuncia) REFERENCES tb_denuncia(id_denuncia),
        FOREIGN KEY (id_representante) REFERENCES tb_usuario(id_usuario),
        FOREIGN KEY (id_denunciante) REFERENCES tb_usuario(id_usuario)
    )');
    $pdo->exec('INSERT INTO tb_conversa VALUES (100, 10, 2, 1), (200, 20, 1, 2)');
    $pdo->exec('CREATE TABLE tb_mensagem (
        id_mensagem INTEGER PRIMARY KEY, id_conversa INTEGER NOT NULL,
        FOREIGN KEY (id_conversa) REFERENCES tb_conversa(id_conversa)
    )');
    $pdo->exec('INSERT INTO tb_mensagem VALUES (1, 100), (2, 200)');
    $pdo->exec('CREATE TABLE tb_notificacao (
        id_notificacao INTEGER PRIMARY KEY, id_usuario INTEGER NOT NULL, id_denuncia INTEGER,
        FOREIGN KEY (id_usuario) REFERENCES tb_usuario(id_usuario),
        FOREIGN KEY (id_denuncia) REFERENCES tb_denuncia(id_denuncia) ON DELETE SET NULL
    )');
    $pdo->exec('INSERT INTO tb_notificacao VALUES (1, 1, 20), (2, 2, 10), (3, 2, 20)');
    $pdo->exec('CREATE TABLE tb_representante_aprovado (
        id_usuario INTEGER PRIMARY KEY,
        FOREIGN KEY (id_usuario) REFERENCES tb_usuario(id_usuario)
    )');
    $pdo->exec('INSERT INTO tb_representante_aprovado VALUES (1)');
    $pdo->exec('CREATE TABLE tb_historico_status (
        id_historico INTEGER PRIMARY KEY, id_denuncia INTEGER, id_representante INTEGER
    )');
    $pdo->exec('INSERT INTO tb_historico_status VALUES (1, 10, 1), (2, 20, 1)');
    $pdo->exec('CREATE TABLE tb_admin_auditoria (
        id_auditoria INTEGER PRIMARY KEY, id_administrador INTEGER, id_representante INTEGER
    )');
    $pdo->exec('INSERT INTO tb_admin_auditoria VALUES (1, 1, 2)');

    foreach ([
        'usuario' => 'tb_usuario',
        'denuncia' => 'tb_denuncia',
        'resposta_tb' => 'tb_resposta',
        'conversa' => 'tb_conversa',
        'mensagem' => 'tb_mensagem',
        'notificacao' => 'tb_notificacao',
        'representante_aprovado' => 'tb_representante_aprovado',
        'historico_status' => 'tb_historico_status',
        'admin_auditoria' => 'tb_admin_auditoria'
    ] as $chave => $tabela) {
        $GLOBALS[$chave] = $tabela;
    }

    try {
        (new PerfilRepositorio($pdo))->excluirConta(1);
    } finally {
        foreach (array_keys([
            'usuario' => true, 'denuncia' => true, 'resposta_tb' => true,
            'conversa' => true, 'mensagem' => true, 'notificacao' => true,
            'representante_aprovado' => true, 'historico_status' => true,
            'admin_auditoria' => true
        ]) as $chave) {
            unset($GLOBALS[$chave]);
        }
    }

    foreach ([
        'tb_usuario' => 1,
        'tb_denuncia' => 1,
        'tb_resposta' => 1,
        'tb_conversa' => 0,
        'tb_mensagem' => 0,
        'tb_notificacao' => 2,
        'tb_representante_aprovado' => 0
    ] as $tabela => $esperado) {
        expectSame($esperado, (int) $pdo->query("SELECT COUNT(*) FROM $tabela")->fetchColumn());
    }
    expectSame(1, (int) $pdo->query(
        'SELECT COUNT(*) FROM tb_notificacao WHERE id_usuario = 2 AND id_denuncia = 20'
    )->fetchColumn());
    expectSame(1, (int) $pdo->query(
        'SELECT COUNT(*) FROM tb_notificacao WHERE id_usuario = 2 AND id_denuncia IS NULL'
    )->fetchColumn());
    expectSame(1, (int) $pdo->query(
        'SELECT COUNT(*) FROM tb_historico_status WHERE id_denuncia = 20 AND id_representante IS NULL'
    )->fetchColumn());
    expectSame(0, (int) $pdo->query(
        'SELECT COUNT(*) FROM tb_historico_status WHERE id_denuncia = 10'
    )->fetchColumn());
    expectSame(null, $pdo->query(
        'SELECT id_administrador FROM tb_admin_auditoria WHERE id_auditoria = 1'
    )->fetchColumn());
});

test('Esquema documenta tabelas administrativas e tipos de notificação usados pelo servidor', function (): void {
    $esquema = file_get_contents(__DIR__ . '/../doc/script-tabelas.md');
    $migracao = file_get_contents(__DIR__ . '/../doc/migracao-admin.sql');
    $migracaoChat = file_get_contents(__DIR__ . '/../doc/migracao-chat-notificacoes.sql');
    $migracaoPerfil = file_get_contents(__DIR__ . '/../doc/migracao-preferencias-perfil.sql');
    expectSame(true, is_string($esquema) && str_contains($esquema, 'CREATE TABLE IF NOT EXISTS tb_admin_auditoria'));
    expectSame(true, is_string($esquema) && str_contains($esquema, "'sistema'"));
    expectSame(true, is_string($migracao) && str_contains($migracao, 'MODIFY tipo_notificacao ENUM'));
    expectSame(true, is_string($migracaoChat) && str_contains($migracaoChat, 'CREATE TABLE IF NOT EXISTS tb_conversa'));
    expectSame(true, is_string($migracaoChat) && str_contains($migracaoChat, 'CREATE TABLE IF NOT EXISTS tb_notificacao'));
    expectSame(true, is_string($migracaoPerfil) && str_contains($migracaoPerfil, 'ADD COLUMN avisos_email'));
});

test('Roteador fornece método POST ao logout sem corpo e preserva túnel de método legado', function (): void {
    $requestMethodAnterior = $_SERVER['REQUEST_METHOD'] ?? null;
    $requestUriAnterior = $_SERVER['REQUEST_URI'] ?? null;
    $postAnterior = $_POST;
    $raizAnterior = $GLOBALS['raiz-app'] ?? null;
    $GLOBALS['raiz-app'] = '/app-web/';
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['REQUEST_URI'] = '/app-web/roteadorteste/capturar';

    try {
        $_POST = [];
        $logout = (new Roteador())->getResposta();
        expectSame('POST', $logout['metodo']);

        $_POST = ['metodo' => 'PUT', 'perfil' => 'visitante'];
        $renovacao = (new Roteador())->getResposta();
        expectSame('PUT', $renovacao['metodo']);
        expectSame('visitante', $renovacao['perfil']);
    } finally {
        $_POST = $postAnterior;
        if ($requestMethodAnterior === null) unset($_SERVER['REQUEST_METHOD']);
        else $_SERVER['REQUEST_METHOD'] = $requestMethodAnterior;
        if ($requestUriAnterior === null) unset($_SERVER['REQUEST_URI']);
        else $_SERVER['REQUEST_URI'] = $requestUriAnterior;
        if ($raizAnterior === null) unset($GLOBALS['raiz-app']);
        else $GLOBALS['raiz-app'] = $raizAnterior;
    }
});

test('Comentários e exclusão de denúncias usam a tabela de respostas configurada', function (): void {
    $repositorio = file_get_contents(__DIR__ . '/../app/database/repositorios/denunciasRepositorio.php');
    expectSame(true, is_string($repositorio) && str_contains($repositorio, '$GLOBALS[\'resposta_tb\']'));
    expectSame(false, is_string($repositorio) && str_contains($repositorio, '$GLOBALS[\'resposta\'] ??'));
});

test('Erros internos não expõem mensagem ou caminho do servidor ao cliente', function (): void {
    $erro = RespostaProcesso::salvarErro(new RuntimeException('senha supersecreta em C:\\private\\app.php'));
    expectSame('Erro interno do servidor.', $erro['mensagem']);
    expectSame(false, array_key_exists('arquivo', $erro));
    expectSame(false, array_key_exists('linha', $erro));
});

printf("\nResultado: %d aprovados, %d falharam.\n", $passed, $failed);
exit($failed === 0 ? 0 : 1);

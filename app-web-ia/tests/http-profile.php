<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/database/conexao.php';
require_once __DIR__ . '/../app/autoload.php';
new AutoLoadFiles();

$baseUrl = getenv('APP_TEST_BASE_URL') ?: 'http://localhost/app-web';
$password = 'ProfileTest!2026';
$suffix = bin2hex(random_bytes(6));
$emailOwner = 'profile-owner-' . $suffix . '@example.test';
$emailOther = 'profile-other-' . $suffix . '@example.test';
$emailChanged = 'profile-changed-' . $suffix . '@example.test';
$city = 'Teste ' . $suffix;
$ownerId = null;
$otherId = null;
$reportOwnerId = null;
$reportOtherId = null;
$reportDoneId = null;
$cookieJars = [];
$passed = 0;

function assertApi(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function reportPass(string $case): void
{
    global $passed;
    $passed++;
    echo "PASS: $case\n";
}

function requestApi(string $route, string $method = 'GET', array $data = [], ?string $token = null, ?string $cookieJar = null): array
{
    global $baseUrl;

    $url = rtrim($baseUrl, '/') . '/' . ltrim($route, '/');
    if ($method === 'GET' && $data !== []) {
        $url .= '?' . http_build_query($data);
    }

    $curl = curl_init($url);
    $headers = ['Accept: application/json'];
    if ($token !== null) {
        $headers[] = 'Authorization: ' . $token;
    }
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_CUSTOMREQUEST => $method
    ]);

    if ($method === 'POST') {
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    }
    if ($cookieJar !== null) {
        curl_setopt($curl, CURLOPT_COOKIEFILE, $cookieJar);
        curl_setopt($curl, CURLOPT_COOKIEJAR, $cookieJar);
    }

    $body = curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $error = curl_error($curl);
    curl_close($curl);

    if (!is_string($body)) {
        throw new RuntimeException('HTTP request failed: ' . $error);
    }
    $json = json_decode($body, true);
    if (!is_array($json)) {
        throw new RuntimeException("Non-JSON response from $route (HTTP $status)");
    }
    return ['status' => $status, 'json' => $json];
}

function loginApi(string $email, string $password, string $cookieJar): string
{
    $response = requestApi('login', 'POST', [
        'email' => $email,
        'senha' => $password,
        'lembrar' => '0'
    ], cookieJar: $cookieJar);
    assertApi($response['status'] === 200 && ($response['json']['resposta'] ?? false) === true, 'Synthetic account login should succeed');
    $token = $response['json']['dados']['token'] ?? null;
    assertApi(is_string($token) && $token !== '', 'Login response should contain a token');
    return $token;
}

function postApi(string $route, array $data, string $token, string $cookieJar): array
{
    return requestApi($route, 'POST', $data, $token, $cookieJar);
}

function sqlCount(PDO $pdo, string $sql, array $params = []): int
{
    $statement = $pdo->prepare($sql);
    $statement->execute($params);
    return (int) $statement->fetchColumn();
}

try {
    if (!function_exists('curl_init')) {
        throw new RuntimeException('PHP cURL extension is required for HTTP integration tests.');
    }

    $pdo = (new Conexao())->getConexao();
    $categoria = $pdo->query('SELECT nome_categoria FROM tb_categoria ORDER BY id_categoria LIMIT 1')->fetchColumn();
    if (!is_string($categoria) || $categoria === '') {
        throw new RuntimeException('At least one category is required for the database integration tests.');
    }

    $inserirUsuario = $pdo->prepare(
        'INSERT INTO tb_usuario (
            perfil_usuario, nome_usuario, sobrenome_usuario, email_usuario, cpf_usuario,
            senha_usuario, cidade_usuario, estado_usuario, orgao_usuario, cargo_usuario,
            area_atuacao_usuario, avisos_email
        ) VALUES (
            \'municipe\', :nome, :sobrenome, :email, :cpf, :senha,
            :cidade, \'SP\', \'\', \'\', NULL, 0
        )'
    );
    foreach ([['nome' => 'Teste', 'email' => $emailOwner], ['nome' => 'Outro', 'email' => $emailOther]] as $conta) {
        $inserirUsuario->execute([
            ':nome' => $conta['nome'],
            ':sobrenome' => 'Integração',
            ':email' => $conta['email'],
            ':cpf' => (string) random_int(10000000000, 99999999999),
            ':senha' => password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]),
            ':cidade' => $city
        ]);
        if ($conta['email'] === $emailOwner) $ownerId = (int) $pdo->lastInsertId();
        else $otherId = (int) $pdo->lastInsertId();
    }

    $inserirDenuncia = $pdo->prepare(
        'INSERT INTO tb_denuncia (
            id_usuario, titulo_denuncia, data_denuncia, descricao_denuncia, imagem_denuncia,
            nome_categoria, cidade_denuncia, estado_denuncia, logradouro_denuncia,
            numero_denuncia, bairro_denuncia, cep_denuncia, status_denuncia,
            perfil_usuario, complemento_denuncia, anonima_denuncia
        ) VALUES (
            :usuario, :titulo, :data, :descricao, NULL, :categoria, :cidade, \'SP\',
            :logradouro, \'1\', \'Centro\', \'09700000\', :status, \'municipe\', NULL, 0
        )'
    );
    foreach ([
        ['usuario' => $ownerId, 'titulo' => 'Denúncia própria', 'status' => 'pendente'],
        ['usuario' => $otherId, 'titulo' => 'Denúncia de terceiro', 'status' => 'pendente'],
        ['usuario' => $ownerId, 'titulo' => 'Denúncia concluída', 'status' => 'concluida']
    ] as $denuncia) {
        $inserirDenuncia->execute([
            ':usuario' => $denuncia['usuario'],
            ':titulo' => $denuncia['titulo'],
            ':data' => date('Y-m-d H:i:s', time() - 86400),
            ':descricao' => 'Registro sintético para teste automatizado.',
            ':categoria' => $categoria,
            ':cidade' => $city,
            ':logradouro' => 'Rua de Teste, 1 - Centro',
            ':status' => $denuncia['status']
        ]);
        if ($denuncia['titulo'] === 'Denúncia própria') $reportOwnerId = (int) $pdo->lastInsertId();
        elseif ($denuncia['titulo'] === 'Denúncia de terceiro') $reportOtherId = (int) $pdo->lastInsertId();
        else $reportDoneId = (int) $pdo->lastInsertId();
    }

    $cookieOwner = tempnam(sys_get_temp_dir(), 'relate-owner-');
    $cookieOther = tempnam(sys_get_temp_dir(), 'relate-other-');
    if ($cookieOwner === false || $cookieOther === false) {
        throw new RuntimeException('Could not create temporary HTTP cookie jars.');
    }
    $cookieJars = [$cookieOwner, $cookieOther];

    $ownerToken = loginApi($emailOwner, $password, $cookieOwner);
    $otherToken = loginApi($emailOther, $password, $cookieOther);
    reportPass('Login sets up authenticated sessions for synthetic accounts');

    $semToken = requestApi('user/logout', 'POST');
    assertApi(($semToken['json']['resposta'] ?? true) === false, 'Logout without a token must be denied');
    $metodoGet = requestApi('user/logout', 'GET');
    assertApi(($metodoGet['json']['resposta'] ?? true) === false, 'Logout via GET must be denied');
    $tokenInvalido = requestApi('user/logout', 'POST', token: 'not-a-valid-token', cookieJar: $cookieOwner);
    assertApi(($tokenInvalido['json']['resposta'] ?? true) === false, 'Logout with malformed token must be denied');
    $tokenExpirado = Firebase\JWT\JWT::encode([
        'exp' => time() - 60,
        'perfil' => 'municipe',
        'email' => $emailOwner
    ], $GLOBALS['token-chave'], $GLOBALS['tkn-algo']);
    $expirado = requestApi('user/logout', 'POST', token: $tokenExpirado, cookieJar: $cookieOwner);
    assertApi(($expirado['json']['resposta'] ?? true) === false, 'Logout with expired token must be denied by the protected endpoint');
    $semCorpo = requestApi('user/logout', 'POST', token: $ownerToken, cookieJar: $cookieOwner);
    assertApi(($semCorpo['json']['resposta'] ?? false) === true, 'Authenticated POST logout without a body should succeed');
    $posLogout = requestApi('user/preferencias', 'GET', token: $ownerToken, cookieJar: $cookieOwner);
    assertApi(($posLogout['json']['resposta'] ?? true) === false, 'Sensitive profile access after logout must require a new PHP session');
    $ownerCookieAfterLogout = tempnam(sys_get_temp_dir(), 'relate-owner-');
    if ($ownerCookieAfterLogout === false) throw new RuntimeException('Could not create a replacement cookie jar.');
    $cookieJars[] = $ownerCookieAfterLogout;
    $ownerToken = loginApi($emailOwner, $password, $ownerCookieAfterLogout);
    reportPass('Logout rejects missing credentials and GET, succeeds with empty POST, and destroys the authenticated session');

    $prefs = requestApi('user/preferencias', 'GET', token: $ownerToken, cookieJar: $ownerCookieAfterLogout);
    assertApi(($prefs['json']['resposta'] ?? false) === true, 'Authenticated preference read should succeed');
    $semAutorizacao = requestApi('user/alterarLocal', 'POST', ['estado' => 'SP', 'cidade' => 'Tentativa inválida'], cookieJar: $ownerCookieAfterLogout);
    assertApi(($semAutorizacao['json']['resposta'] ?? true) === false, 'Profile mutation without bearer token must be denied');
    $salvo = postApi('user/preferencias', ['avisos_email' => '1'], $ownerToken, $ownerCookieAfterLogout);
    assertApi(($salvo['json']['resposta'] ?? false) === true, 'Valid email preference should save');
    assertApi(sqlCount($pdo, 'SELECT avisos_email FROM tb_usuario WHERE id_usuario = :id', [':id' => $ownerId]) === 1, 'Preference should persist in database');
    $invalido = postApi('user/preferencias', ['avisos_email' => '2'], $ownerToken, $ownerCookieAfterLogout);
    assertApi(($invalido['json']['resposta'] ?? true) === false, 'Invalid preference value must be rejected');

    $senhaErrada = postApi('user/alterarEmail', ['email' => $emailChanged, 'senha' => 'Wrong!2026'], $ownerToken, $ownerCookieAfterLogout);
    assertApi(($senhaErrada['json']['resposta'] ?? true) === false, 'Email change with a wrong password must be denied');
    $emailRuim = postApi('user/alterarEmail', ['email' => 'not-an-email', 'senha' => $password], $ownerToken, $ownerCookieAfterLogout);
    assertApi(($emailRuim['json']['resposta'] ?? true) === false, 'Malformed email must be rejected');
    $emailDuplicado = postApi('user/alterarEmail', ['email' => $emailOther, 'senha' => $password], $ownerToken, $ownerCookieAfterLogout);
    assertApi(($emailDuplicado['json']['resposta'] ?? true) === false, 'Email belonging to another account must not be assigned');

    $localInvalido = postApi('user/alterarLocal', ['estado' => 'XX', 'cidade' => 'Cidade inválida'], $ownerToken, $ownerCookieAfterLogout);
    assertApi(($localInvalido['json']['resposta'] ?? true) === false, 'Invalid state code must be rejected');
    $localSalvo = postApi('user/alterarLocal', ['estado' => 'RJ', 'cidade' => 'Cidade de Teste'], $ownerToken, $ownerCookieAfterLogout);
    assertApi(($localSalvo['json']['resposta'] ?? false) === true, 'Valid location change should succeed');
    assertApi(($localSalvo['json']['dados']['novo_login'] ?? false) === true, 'Location change must require reauthentication');
    assertApi(sqlCount($pdo, 'SELECT COUNT(*) FROM tb_usuario WHERE id_usuario = :id AND estado_usuario = \'RJ\'', [':id' => $ownerId]) === 1, 'Location change should persist');

    $ownerCookieAfterLocation = tempnam(sys_get_temp_dir(), 'relate-owner-');
    if ($ownerCookieAfterLocation === false) throw new RuntimeException('Could not create a replacement cookie jar.');
    $cookieJars[] = $ownerCookieAfterLocation;
    $ownerToken = loginApi($emailOwner, $password, $ownerCookieAfterLocation);
    $emailSalvo = postApi('user/alterarEmail', ['email' => $emailChanged, 'senha' => $password], $ownerToken, $ownerCookieAfterLocation);
    assertApi(($emailSalvo['json']['resposta'] ?? false) === true, 'Valid email change should succeed');
    assertApi(($emailSalvo['json']['dados']['novo_login'] ?? false) === true, 'Email change must require reauthentication');
    assertApi(sqlCount($pdo, 'SELECT COUNT(*) FROM tb_usuario WHERE id_usuario = :id AND email_usuario = :email', [':id' => $ownerId, ':email' => $emailChanged]) === 1, 'Email change should persist');
    reportPass('Profile settings persist preference/location/email and reject invalid values or incorrect/duplicate credentials');

    $ownerCookieAfterEmail = tempnam(sys_get_temp_dir(), 'relate-owner-');
    if ($ownerCookieAfterEmail === false) throw new RuntimeException('Could not create a replacement cookie jar.');
    $cookieJars[] = $ownerCookieAfterEmail;
    $ownerToken = loginApi($emailChanged, $password, $ownerCookieAfterEmail);
    $novoPassword = 'ProfileChanged!2026';
    $senhaAtualErrada = postApi('user/alterarSenha', ['senha_atual' => 'Wrong!2026', 'senha_nova' => $novoPassword], $ownerToken, $ownerCookieAfterEmail);
    assertApi(($senhaAtualErrada['json']['resposta'] ?? true) === false, 'Password change with wrong current password must be rejected');
    $senhaFraca = postApi('user/alterarSenha', ['senha_atual' => $password, 'senha_nova' => 'weak'], $ownerToken, $ownerCookieAfterEmail);
    assertApi(($senhaFraca['json']['resposta'] ?? true) === false, 'Weak password must be rejected');
    $senhaSalva = postApi('user/alterarSenha', ['senha_atual' => $password, 'senha_nova' => $novoPassword], $ownerToken, $ownerCookieAfterEmail);
    assertApi(($senhaSalva['json']['resposta'] ?? false) === true, 'Valid password change should succeed');
    assertApi(($senhaSalva['json']['dados']['novo_login'] ?? false) === true, 'Password change must require reauthentication');
    $ownerCookieAfterPassword = tempnam(sys_get_temp_dir(), 'relate-owner-');
    if ($ownerCookieAfterPassword === false) throw new RuntimeException('Could not create a replacement cookie jar.');
    $cookieJars[] = $ownerCookieAfterPassword;
    $ownerToken = loginApi($emailChanged, $novoPassword, $ownerCookieAfterPassword);
    reportPass('Password changes validate current password and policy, persist a new hash, and allow login with the new secret');

    $semAuth = requestApi('denuncia/obter', 'GET', ['id' => $reportOwnerId]);
    assertApi(($semAuth['json']['resposta'] ?? true) === false, 'Report edit read without token must be denied');
    $leituraPropria = requestApi('denuncia/obter', 'GET', ['id' => $reportOwnerId], $ownerToken);
    assertApi(($leituraPropria['json']['resposta'] ?? false) === true, 'Owner should read their report for editing');
    $leituraTerceiro = requestApi('denuncia/obter', 'GET', ['id' => $reportOwnerId], $otherToken);
    assertApi(($leituraTerceiro['json']['resposta'] ?? true) === false, 'Another user must not read owner-only edit data');
    $dadosEdicao = [
        'id-denuncia' => (string) $reportOwnerId,
        'titulo-denuncia' => 'Título atualizado por teste',
        'descricao-denuncia' => 'Descrição atualizada por teste.',
        'categoria-denuncia' => $categoria,
        'data-denuncia' => date('Y-m-d'),
        'estado-denuncia' => 'RJ',
        'cidade-denuncia' => 'Cidade de Teste',
        'rua-denuncia' => 'Rua Atualizada',
        'numero-denuncia' => '2',
        'bairro-denuncia' => 'Centro',
        'cep-denuncia' => '09700000',
        'status-denuncia' => 'pendente'
    ];
    $tentativaTerceiro = postApi('denuncia/editar', $dadosEdicao, $otherToken, $cookieOther);
    assertApi(($tentativaTerceiro['json']['resposta'] ?? true) === false, 'Another user must not edit the report');
    $statusInvalido = $dadosEdicao;
    $statusInvalido['status-denuncia'] = 'administrador';
    $respostaStatus = postApi('denuncia/editar', $statusInvalido, $ownerToken, $ownerCookieAfterPassword);
    assertApi(($respostaStatus['json']['resposta'] ?? true) === false, 'Invalid report status must be rejected');
    $edicao = postApi('denuncia/editar', $dadosEdicao, $ownerToken, $ownerCookieAfterPassword);
    assertApi(($edicao['json']['resposta'] ?? false) === true, 'Owner should update their report: ' . json_encode($edicao['json'], JSON_UNESCAPED_UNICODE));
    assertApi(sqlCount($pdo, 'SELECT COUNT(*) FROM tb_denuncia WHERE id_denuncia = :id AND titulo_denuncia = :titulo AND estado_denuncia = \'RJ\'', [':id' => $reportOwnerId, ':titulo' => 'Título atualizado por teste']) === 1, 'Report edits should persist to database');
    reportPass('Report edit read/write is owner-scoped and validates status and persisted fields');

    $comentarioSemAuth = requestApi('denuncia/comentar', 'POST', ['id-denuncia' => (string) $reportOwnerId, 'comentario' => 'Sem autenticação']);
    assertApi(($comentarioSemAuth['json']['resposta'] ?? true) === false, 'Comment creation without token must be denied');
    $comentarioVazio = postApi('denuncia/comentar', ['id-denuncia' => (string) $reportOwnerId, 'comentario' => '   '], $ownerToken, $ownerCookieAfterPassword);
    assertApi(($comentarioVazio['json']['resposta'] ?? true) === false, 'Empty comment must be rejected');
    $comentarioLongo = postApi('denuncia/comentar', ['id-denuncia' => (string) $reportOwnerId, 'comentario' => str_repeat('x', 1001)], $ownerToken, $ownerCookieAfterPassword);
    assertApi(($comentarioLongo['json']['resposta'] ?? true) === false, 'Comment beyond 1000 characters must be rejected');
    $xssText = '<script>alert(1)</script>';
    $comentario = postApi('denuncia/comentar', [
        'id-denuncia' => (string) $reportOwnerId,
        'comentario' => $xssText,
        'id_usuario' => (string) $ownerId
    ], $otherToken, $cookieOther);
    assertApi(($comentario['json']['resposta'] ?? false) === true, 'A valid public comment should be accepted');
    assertApi(sqlCount($pdo, 'SELECT COUNT(*) FROM tb_resposta WHERE id_resposta = :id AND id_usuario = :autor', [
        ':id' => $comentario['json']['dados']['id'] ?? 0,
        ':autor' => $otherId
    ]) === 1, 'Comment author must come from the authenticated token rather than submitted data');
    $lista = requestApi('denuncia/comentarios', 'GET', ['id' => $reportOwnerId]);
    assertApi(($lista['json']['resposta'] ?? false) === true, 'Comments should be publicly readable');
    assertApi(($lista['json']['dados'][0]['texto'] ?? null) === $xssText, 'Comment text should be returned as data for safe text rendering');
    reportPass('Comments reject anonymous/empty/oversized submissions and preserve text as data');

    $removerOutro = postApi('denuncia/remover', ['id-denuncia' => (string) $reportOtherId], $ownerToken, $ownerCookieAfterPassword);
    assertApi(($removerOutro['json']['resposta'] ?? true) === false, 'User must not delete another user report');
    $removerConcluida = postApi('denuncia/remover', ['id-denuncia' => (string) $reportDoneId], $ownerToken, $ownerCookieAfterPassword);
    assertApi(($removerConcluida['json']['resposta'] ?? true) === false, 'Completed report must not be deletable');
    $removerInvalida = postApi('denuncia/remover', ['id-denuncia' => 'invalid'], $ownerToken, $ownerCookieAfterPassword);
    assertApi(($removerInvalida['json']['resposta'] ?? true) === false, 'Malformed report ID must be rejected');
    $remover = postApi('denuncia/remover', ['id-denuncia' => (string) $reportOwnerId], $ownerToken, $ownerCookieAfterPassword);
    assertApi(($remover['json']['resposta'] ?? false) === true, 'Owner should remove an eligible report');
    assertApi(sqlCount($pdo, 'SELECT COUNT(*) FROM tb_denuncia WHERE id_denuncia = :id', [':id' => $reportOwnerId]) === 0, 'Removed report should be deleted');
    assertApi(sqlCount($pdo, 'SELECT COUNT(*) FROM tb_resposta WHERE id_denuncia = :id', [':id' => $reportOwnerId]) === 0, 'Report removal should delete its comments');
    reportPass('Report deletion enforces ownership/status, rejects invalid IDs, and removes linked comments');

    $comentarioConta = postApi('denuncia/comentar', [
        'id-denuncia' => (string) $reportDoneId,
        'comentario' => 'Comentário de terceiro ligado à conta sintética.'
    ], $otherToken, $cookieOther);
    assertApi(($comentarioConta['json']['resposta'] ?? false) === true, 'Other user should comment on report retained for account-deletion test');
    $exclusaoSenhaErrada = postApi('user/excluir', ['senha' => 'Wrong!2026'], $ownerToken, $ownerCookieAfterPassword);
    assertApi(($exclusaoSenhaErrada['json']['resposta'] ?? true) === false, 'Account deletion with wrong password must be denied');
    $exclusao = postApi('user/excluir', ['senha' => $novoPassword], $ownerToken, $ownerCookieAfterPassword);
    assertApi(($exclusao['json']['resposta'] ?? false) === true, 'Account deletion with current password should succeed');
    assertApi(sqlCount($pdo, 'SELECT COUNT(*) FROM tb_usuario WHERE id_usuario = :id', [':id' => $ownerId]) === 0, 'Account deletion should remove the account');
    assertApi(sqlCount($pdo, 'SELECT COUNT(*) FROM tb_denuncia WHERE id_usuario = :id', [':id' => $ownerId]) === 0, 'Account deletion should remove the account reports');
    assertApi(sqlCount($pdo, 'SELECT COUNT(*) FROM tb_resposta WHERE id_denuncia = :id', [':id' => $reportDoneId]) === 0, 'Account deletion should remove comments linked to its reports');
    reportPass('Account deletion requires the current password and removes owned reports and their comments');

    $logout = requestApi('user/logout', 'POST', token: $ownerToken, cookieJar: $ownerCookieAfterPassword);
    assertApi(($logout['json']['resposta'] ?? false) === true, 'Final logout should succeed');
    reportPass('Authenticated user can log out after profile and report operations');
} finally {
    if (isset($pdo) && $pdo instanceof PDO) {
        foreach ([$reportOwnerId, $reportOtherId, $reportDoneId] as $reportId) {
            if ($reportId) {
                foreach (['tb_resposta', 'tb_notificacao', 'tb_historico_status'] as $table) {
                    $delete = $pdo->prepare("DELETE FROM $table WHERE id_denuncia = :id");
                    $delete->execute([':id' => $reportId]);
                }
                $delete = $pdo->prepare('DELETE FROM tb_denuncia WHERE id_denuncia = :id');
                $delete->execute([':id' => $reportId]);
            }
        }
        foreach ([$ownerId, $otherId] as $userId) {
            if ($userId) {
                foreach (['tb_representante_aprovado', 'tb_notificacao', 'tb_historico_status'] as $table) {
                    $delete = $pdo->prepare("DELETE FROM $table WHERE " . ($table === 'tb_historico_status' ? 'id_representante' : 'id_usuario') . ' = :id');
                    $delete->execute([':id' => $userId]);
                }
                $delete = $pdo->prepare('DELETE FROM tb_usuario WHERE id_usuario = :id');
                $delete->execute([':id' => $userId]);
            }
        }
    }
    foreach ($cookieJars as $cookieJar) {
        if (is_file($cookieJar)) unlink($cookieJar);
    }
}

printf("\nResultado: %d cenários de integração aprovados.\n", $passed);

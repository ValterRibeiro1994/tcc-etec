<?php

declare(strict_types=1);

$passed = 0;
$failed = 0;

function clientTest(string $name, callable $case): void
{
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

function clientSource(string $path): string
{
    $source = file_get_contents(__DIR__ . '/../' . $path);
    if ($source === false) {
        throw new RuntimeException("Não foi possível ler $path");
    }
    return $source;
}

function clientExpect(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

clientTest('Autenticação e cadastros não têm valores demonstrativos', function (): void {
    foreach ([
        'app/view/paginas/login.html',
        'app/view/paginas/cadastro-denunciante.html',
        'app/view/paginas/cadastro-representante.html'
    ] as $path) {
        $html = clientSource($path);
        clientExpect(!preg_match('/<(?:input)[^>]*\bvalue="(?:teste|treste|[^"]*@email\.com|@Ts123456|111\.111\.111-11)/i', $html),
            "Valor de demonstração pré-preenchido em $path");
    }
});

clientTest('Resposta HTTP do login é validada antes do JSON', function (): void {
    $js = clientSource('app/view/scripts/login.js');
    clientExpect(
        strpos($js, 'if (!servidor.ok)') !== false
            && strpos($js, 'if (!servidor.ok)') < strpos($js, 'await servidor.json()'),
        'O login deve validar HTTP antes de interpretar JSON'
    );
    clientExpect(!str_contains($js, 'console.log('), 'O login não deve registrar respostas no console');
});

clientTest('Criação da denúncia não depende de iframe e preserva autenticação', function (): void {
    $js = clientSource('app/view/scripts/criar-denuncia.js');
    clientExpect(str_contains($js, 'if (!token)'), 'A página deve continuar exigindo usuário autenticado');
    clientExpect(
        !str_contains($js, 'if (window.self === window.top) return;'),
        'A página não deve abortar quando aberta fora de iframe'
    );
    clientExpect(str_contains($js, 'credentials: \'same-origin\''), 'A requisição protegida deve usar credenciais same-origin');
    clientExpect(str_contains($js, 'if (!resposta.ok)'), 'A resposta HTTP deve ser validada');
});

clientTest('Respostas inválidas não expõem o corpo do servidor no console', function (): void {
    foreach ([
        'app/view/scripts/mural.js',
        'app/view/scripts/mural-configuracoes.js',
        'app/view/scripts/ver-denuncia.js'
    ] as $path) {
        $js = clientSource($path);
        clientExpect(
            !preg_match('/console\.(?:error|log|debug)\([^)]*,\s*(?:texto|json|resposta|token|dados|erro|error)\s*\)/i', $js),
            "Possível log de payload sensível em $path"
        );
    }
});

clientTest('Listas de municípios ignoram respostas fora de ordem', function (): void {
    foreach ([
        'app/view/scripts/cadastro-denunciante.js',
        'app/view/scripts/cadastro-representante.js',
        'app/view/scripts/mural-configuracoes.js'
    ] as $path) {
        $js = clientSource($path);
        clientExpect(str_contains($js, 'sequencia'), "Falta controle de concorrência em $path");
        clientExpect(str_contains($js, 'credentials: \'omit\''), "A consulta pública do IBGE deve omitir credenciais em $path");
    }
});

clientTest('Renovação só reenvia os claims mínimos do visitante', function (): void {
    $js = clientSource('app/view/scripts/start-app.js');
    clientExpect(str_contains($js, 'localStorage.getItem("perfil") !== "visitante"'),
        'Contas cadastradas não devem tentar renovar sessão sem login');
    foreach (['metodo: "PUT"', 'perfil: "visitante"', 'token: localStorage.getItem("token")', 'email: localStorage.getItem("email")'] as $campo) {
        clientExpect(str_contains($js, $campo), "Campo necessário à renovação do visitante ausente: $campo");
    }
    clientExpect(str_contains($js, 'body: dadosRenovacao'), 'A renovação deve usar exclusivamente os campos mínimos');
});

clientTest('Falhas no cadastro preservam mensagem e diagnóstico', function (): void {
    foreach ([
        'app/view/scripts/cadastro-denunciante.js',
        'app/view/scripts/cadastro-representante.js'
    ] as $path) {
        $js = clientSource($path);
        clientExpect(str_contains($js, 'resposta.status'), "O status HTTP deve ficar visível em $path");
        clientExpect(str_contains($js, 'error.message'), "O erro deve ser mostrado ao usuário em $path");
        clientExpect(str_contains($js, 'error.name, error.message'), "O console deve manter detalhes úteis em $path");
        clientExpect(str_contains($js, 'resultado.dados && resultado.dados.codigo'),
            "O código seguro do servidor deve ser registrado em $path");
    }
});

clientTest('Perfil usa endpoint correto por perfil e só carrega após obter os dados do usuário', function (): void {
    $js = clientSource('app/view/scripts/perfil.js');
    clientExpect(str_contains($js, "perfilAtual === 'representante' ? 'denuncia/recebidas?' : 'denuncia/minhas?'"),
        'Representante deve consultar denúncias da sua área; munícipe consulta as próprias');
    clientExpect(str_contains($js, 'carregarDados().then(carregado =>'), 'A listagem deve aguardar o perfil do usuário');
    clientExpect(str_contains($js, "perfilAtual === 'representante'"), 'A apresentação deve distinguir o perfil representante');
    clientExpect(str_contains($js, 'resposta HTTP ${resposta.status}'), 'A falha deve informar status HTTP');
    clientExpect(str_contains($js, 'Content-Type ${contentType}'), 'A falha deve informar Content-Type');
    clientExpect(str_contains($js, 'erro.message'), 'O diagnóstico de parsing deve registrar a causa técnica');
    clientExpect(str_contains($js, 'codigo: json.dados && json.dados.codigo'),
        'O código da falha do perfil deve ser registrado no console');
    clientExpect(!preg_match('/console\\.(?:error|log|debug)\\([^\\n]*(?:texto|token|dados)\\s*[,)]/i', $js),
        'O diagnóstico não deve imprimir corpo ou credenciais da resposta');
});

clientTest('Logout usa POST e exige token válido no servidor', function (): void {
    $js = clientSource('app/view/scripts/navbar.js');
    $controller = clientSource('app/controller/userController.php');
    clientExpect(str_contains($js, "urlApp('user/logout')"), 'O cliente deve manter o endpoint de logout');
    clientExpect(str_contains($js, "method: 'POST'"), 'Logout não pode encerrar sessão por GET');
    $inicioLogout = strpos($controller, 'public function logout(');
    $fimLogout = strpos($controller, 'public function municipe(', $inicioLogout);
    $logout = substr($controller, $inicioLogout, $fimLogout - $inicioLogout);
    clientExpect(str_contains($logout, "REQUEST_METHOD") && str_contains($logout, "'POST'"),
        'O servidor deve aceitar logout somente por POST');
    clientExpect(str_contains($logout, 'UsuarioLogadoService::obter()'),
        'O servidor deve validar o token antes de destruir a sessão');
    clientExpect(str_contains($js, "localStorage.removeItem(chave)")
        && str_contains($js, "'token', 'perfil'"),
        'O cliente deve apagar a credencial depois da confirmação do servidor');
    clientExpect(str_contains($js, 'alert(erro.message ||'),
        'Falha ao desconectar deve ser informada ao usuário');
    clientExpect(str_contains($js, "new URL('../../../' + rota, SRC_DO_SCRIPT)"),
        'A URL do logout deve respeitar instalação em subpasta');
});

clientTest('Formulários de conta enviam credencial, método e tratam reautenticação', function (): void {
    $js = clientSource('app/view/scripts/mural-configuracoes.js');
    $controlador = clientSource('app/controller/userController.php');
    foreach (['user/preferencias', 'user/alterarEmail', 'user/alterarSenha', 'user/alterarLocal', 'user/excluir'] as $rota) {
        clientExpect(str_contains($js, $rota), "Formulário ausente para $rota");
    }
    clientExpect(str_contains($js, "headers: { 'Authorization': sessao.token }"),
        'As alterações devem enviar a credencial atual');
    clientExpect(str_contains($js, "Object.assign({ metodo: 'POST' }, corpo)"),
        'As alterações devem continuar compatíveis com o roteador POST');
    clientExpect(str_contains($js, "json.dados.novo_login"),
        'Mudanças sensíveis devem direcionar para novo login');
    clientExpect(str_contains($controlador, "SessaoService::estaOnline()"),
        'O servidor deve exigir uma sessão ativa para alterar a conta');
    clientExpect(str_contains($controlador, 'password_verify($senhaAtual, $conta[\'senha\'])'),
        'E-mail, senha e exclusão devem exigir senha atual');
});

clientTest('Edição e comentários de denúncia enviam token e validam campos no cliente', function (): void {
    $edicao = clientSource('app/view/scripts/editar-denuncia.js');
    $comentarios = clientSource('app/view/scripts/ver-denuncia.js');
    $api = clientSource('app/controller/DenunciaController.php');
    clientExpect(str_contains($edicao, "urlApp('denuncia/obter?id="),
        'A edição deve carregar dados da denúncia antes de salvar');
    clientExpect(str_contains($edicao, "urlApp('denuncia/editar')"),
        'A edição deve enviar os campos ao endpoint de atualização');
    clientExpect(str_contains($edicao, "'Authorization': token"),
        'A edição deve enviar credencial');
    clientExpect(str_contains($comentarios, "urlApp('denuncia/comentarios?id="),
        'A tela deve carregar comentários existentes');
    clientExpect(str_contains($comentarios, "urlApp('denuncia/comentar')"),
        'A tela deve enviar novos comentários');
    clientExpect(str_contains($comentarios, "corpo.append('comentario', texto)"),
        'O comentário deve ser enviado com o texto validado');
    clientExpect(str_contains($comentarios, "querySelector('[data-campo=\"texto\"]').textContent = c.texto"),
        'Texto de comentário deve ser renderizado sem interpretar HTML');
    clientExpect(str_contains($api, 'mb_strlen($texto) > 1000'),
        'O servidor deve impor o limite de 1000 caracteres');
    clientExpect(str_contains($api, 'UsuarioLogadoService::obter()'),
        'O servidor deve autenticar criação de comentário');
});

clientTest('Atualização de status mantém o botão após aguardar a resposta', function (): void {
    $js = clientSource('app/view/scripts/mural-painel.js');
    clientExpect(str_contains($js, 'const botaoSalvar = evento.currentTarget;'),
        'O botão deve ser capturado antes do primeiro await');
    clientExpect(str_contains($js, 'botaoSalvar.disabled = true;')
        && str_contains($js, 'finally { botaoSalvar.disabled = false; }'),
        'O botão deve ser reabilitado após sucesso ou falha');
    clientExpect(!str_contains($js, 'evento.currentTarget.disabled'),
        'currentTarget não deve ser acessado após await');
});

printf("\nResultado: %d aprovados, %d falharam.\n", $passed, $failed);
exit($failed === 0 ? 0 : 1);

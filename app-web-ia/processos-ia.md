# Auditoria técnica do projeto

**Data:** 19/11/2026  
**Escopo:** arquivos próprios PHP, JavaScript, HTML e documentação em `app/` e `doc/`. Bibliotecas de terceiros foram excluídas da análise funcional.

## Resumo executivo

Foram encontrados defeitos funcionais, riscos de exposição de dados e gargalos de consulta. Corrigi nesta execução os defeitos reproduzíveis no código e adicionei uma suíte PHP independente do banco. Os problemas que dependem do modelo de dados, de credenciais ou de decisões de produto permanecem listados como pendências.

Esta auditoria não certifica a ausência de todos os bugs: não havia configuração local do MySQL, servidor HTTP nem dependências de e-mail para executar os fluxos de ponta a ponta. A análise estática e os testes cobrem parte do sistema; os casos de uso listados em “Cobertura ainda necessária” continuam sem testes de integração.

## Achados corrigidos nesta execução

| ID | Prioridade | Problema e impacto | Correção |
|---|---|---|---|
| C1 | Alta | `PerfilRepositorio::alterarCampo()` vinculava o valor alterado como ID do usuário; mudanças de preferência, e-mail ou senha podiam não alterar a conta correta. | Vinculação corrigida e coberta por teste SQLite que verifica duas contas distintas. [perfilRepositorio.php](app/database/repositorios/perfilRepositorio.php#L79) |
| C2 | Alta | Respostas de erro podiam devolver dados enviados em login/cadastro/token (inclusive senha, CPF e token); erros internos também expunham mensagem, caminho e linha do servidor, e login distinguia conta inexistente de senha incorreta. | Removida a devolução dos payloads; erros são registrados no log com resposta genérica e login usa a mesma mensagem para credenciais inválidas. Os erros inesperados de denúncia também não incluem mais detalhes internos na resposta. [respostaProcesso.php](app/model/respostaProcesso.php#L9), [loginController.php](app/controller/loginController.php), [cadastroController.php](app/controller/cadastroController.php), [DenunciaController.php](app/controller/DenunciaController.php) |
| C3 | Alta | A autenticação do perfil não comparava a cidade do JWT com a sessão por erro de variável. A carga de denúncias verificava o resultado anterior (autenticação), não o resultado do repositório. | Corrigidas a lista de atributos comparados e a verificação da resposta do repositório; nenhum resultado também é tratado como lista vazia válida. [userController.php](app/controller/userController.php#L69), [usuarioRepositorio.php](app/database/repositorios/usuarioRepositorio.php#L160) |
| C4 | Alta | A troca de visitante para usuário autenticado não regenerava o ID da sessão; o cookie `Secure` era desativado independentemente do protocolo. | ID regenerado após autenticação e cookie `Secure` habilitado quando a requisição chega por HTTPS; HTTP local continua funcional. [sessaoService.php](app/service/sessaoService.php#L10) |
| C5 | Média | Se a conexão MySQL falhasse, repositórios testavam o array de resposta como booleano. Como o array é truthy mesmo em erro, o fluxo prosseguia sem PDO e podia terminar em erro não tratado. | Verificação agora consulta o campo `resposta`; mensagens de conexão e de restrição única não repassam detalhes SQL ao cliente. [usuarioRepositorio.php](app/database/repositorios/usuarioRepositorio.php#L48) |
| C6 | Média | `Endereco` chamava `mb_trim()`, inexistente no PHP 8.0–8.3, e `strtoupper()` não convertia corretamente cidades com acentos UTF-8. | Substituído por `trim()` e `mb_strtoupper()`, com limite contado em caracteres. A assinatura nullable de `Prefeitura` também foi explicitada para evitar depreciação no PHP 8.4. [endereco.php](app/model/objects-values/endereco.php#L19), [prefeitura.php](app/model/objects-values/prefeitura.php#L9) |
| C7 | Baixa | `denuncia/mural` podia terminar sem resposta para método diferente de GET; a ramificação DELETE do token também retornava `null`. | As duas rotas agora respondem explicitamente que a operação é inválida/não implementada. A revogação efetiva de JWT continua pendente. [DenunciaController.php](app/controller/DenunciaController.php#L13), [tokenController.php](app/controller/tokenController.php#L27) |

## Pendências confirmadas

| ID | Prioridade | Evidência | Risco / recomendação |
|---|---|---|---|
| P1 | Alta | A exclusão de conta remove comentários e denúncias, mas não remove nem anonimiza conversas e mensagens vinculadas. O esquema não define `ON DELETE CASCADE` para várias dessas referências. [perfilRepositorio.php](app/database/repositorios/perfilRepositorio.php#L98), [script-tabelas.md](doc/script-tabelas.md#L86) | A exclusão pode falhar para usuários que participaram do chat; mesmo que funcione, apagar mensagens históricas pode violar requisitos de auditoria. Definir política de retenção/anonimização e executar testes com o esquema MySQL real antes de alterar as FKs. |
| P1 | Alta | Existem rotinas incompletas: `UserController::postProcessoMunicipe()` retorna apenas texto de placeholder; `UsuarioRepositorio::obterRespostasUsuario()` não implementa consulta. [userController.php](app/controller/userController.php#L186), [usuarioRepositorio.php](app/database/repositorios/usuarioRepositorio.php#L195) | Os fluxos correspondentes não estão implementados. Confirmar se são rotas de produto; implementar ou remover do contrato público. |
| P2 | Média | A listagem pública seleciona `imagem_denuncia` para até 30 itens e transforma cada BLOB em Data URI; as pesquisas usam `LIKE '%termo%'` e executam contagem, lista e duas consultas de opções. [denunciasRepositorio.php](app/database/repositorios/denunciasRepositorio.php#L334) | Uma página pode transferir dezenas/centenas de MB quando há imagens grandes; buscas não aproveitam índices B-tree com curinga inicial. Separar imagem/miniatura sob demanda, paginar opções e medir planos `EXPLAIN` antes de criar índices compostos ou FULLTEXT. |
| P2 | Média | `obterDenunciasUsuario()` carrega todas as denúncias do perfil, incluindo BLOBs, sem paginação ou ordenação. [usuarioRepositorio.php](app/database/repositorios/usuarioRepositorio.php#L160) | Resposta e consumo de memória crescem sem limite. Reutilizar paginação e carregar imagens somente quando solicitadas. |
| P2 | Média | O polling do chat executa ciclos a cada 4 s; mensagens novas são buscadas em cada ciclo enquanto a conversa está aberta. [mural-mensagens.js](app/view/scripts/mural-mensagens.js#L9), [mural-mensagens.js](app/view/scripts/mural-mensagens.js#L443) | Aumenta a carga proporcionalmente ao número de usuários simultâneos. Avaliar backoff adaptativo ou SSE/WebSocket e medir consultas por usuário antes/depois. |
| P2 | Média | O limite de tentativas de login é guardado na sessão do navegador. [sessaoService.php](app/service/sessaoService.php#L104), [loginController.php](app/controller/loginController.php#L45) | É contornável iniciando novas sessões e não limita tentativas por conta/IP. Usar rate limit compartilhado (com janela, bloqueio progressivo e proteção de enumeração), sem registrar senha/token. |
| P2 | Média | O JWT contém nome, e-mail, cidade e dados do órgão; o cliente o guarda em `localStorage`. JWT é assinado, não cifrado, e scripts executados na origem podem lê-lo. | Reduzir claims ao mínimo necessário; preferir sessão/cookie HttpOnly com desenho de CSRF adequado. Até essa migração, manter prevenção contra XSS e evitar colocar mais dados pessoais no token. |
| P2 | Média | O script SQL cria `relate_sbc`, enquanto o padrão e o exemplo de configuração apontam para `tcc_bd6`. [script-tabelas.md](doc/script-tabelas.md#L1), [config.php](app/config.php#L17), [config-banco.example.php](app/config-banco.example.php#L8) | Seguir somente a documentação pode conectar ao banco errado. Alinhar nomes por ambiente e documentar como a configuração local substitui o padrão. O script também usa `ALTER TABLE` e inserções de categorias que não são idempotentes em reexecuções. |
| P2 | Média | O controlador de contato depende de `config-email.php` e `vendor/autoload.php`, ausentes no workspace; o destinatário está fixo no código. [contatoController.php](app/controller/contatoController.php#L85) | O envio não está configurado neste ambiente e o destino não é administrável por ambiente. Configurar destinatário/SMTP externamente e adicionar testes com transporte de e-mail falso. |
| P3 | Baixa | As respostas JSON de erro e sucesso não definem códigos HTTP por resultado; o roteador também aceita apenas GET/POST reais e vários métodos são simulados no campo `metodo`. [index.php](index.php#L12), [roteador.php](app/roteador.php#L10) | Clientes e proxies não conseguem distinguir erro de validação de sucesso apenas pelo status HTTP. Formalizar o contrato REST e migrar com compatibilidade dos scripts existentes. |

## Cobertura de testes

Foi criada a suíte [run.php](tests/run.php), sem dependência de PHPUnit, cobrindo:

- CPF válido e rejeição de dígitos repetidos;
- e-mail e política de senha;
- codificação/limites de nome e descrição;
- data inválida/futura;
- normalização e validação de endereço com acentos;
- perfil e limite de área;
- atualização do campo da conta no usuário correto, usando SQLite em memória;
- ausência de caminho e mensagem de exceção na resposta pública.

Comando: `php tests\run.php`

## Cobertura ainda necessária

Não há testes de integração para cadastro, login, renovação de token, permissões por perfil, denúncias, comentários, notificações, chat, painel, exportação, exclusão de conta, upload de imagem ou envio de contato. Para esses cenários, criar banco descartável com o esquema MySQL oficial e cobrir, no mínimo, autorização (anônimo/outro usuário/representante não aprovado/admin), entradas inválidas, paginação, concorrência, falha de banco e limites de payload. Criar testes de navegador para os fluxos principais e XSS/CSRF conforme o mecanismo de sessão escolhido.

## Validação executada e limitações

- `php tests\run.php`: 10 testes aprovados no PHP 8.0.30 e no PHP 8.4.15 do WAMP.
- `php -l`: 56 arquivos PHP em `app/` e `tests/` sem erros de sintaxe.
- Não foi possível executar testes MySQL/HTTP: não existem `app/config-banco.php`, `app/segredo-jwt.php` nem configuração de e-mail local. Node.js também não está disponível para validação automatizada dos scripts JavaScript.

## Próximas etapas recomendadas

1. Provisionar banco de teste, alinhar o nome do banco e automatizar os fluxos críticos de cadastro/autenticação/denúncia.
2. Decidir a política de exclusão/anonimização e ajustar/testar as relações de chat e histórico.
3. Medir a listagem atual; implementar carregamento de miniaturas e paginação de todas as listas, depois adicionar índices sustentados por `EXPLAIN`.
4. Reforçar rate limit de autenticação e planejar migração segura do JWT em `localStorage`.
5. Incluir testes de navegador e executar varredura de segurança/dependências em pipeline.

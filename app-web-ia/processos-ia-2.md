# Auditoria de front-end e comunicação com o servidor

## Escopo

Revisão do front-end e de seus pontos de comunicação com o back-end, sem alteração intencional de funcionalidades. Foram inventariados os 29 pontos de chamada `fetch` nos scripts JavaScript da interface e examinados método, destino, payload, credenciais, validação HTTP/JSON, concorrência e tratamento de erro. Também foram revisados os formulários de autenticação/cadastro e os pontos de exibição de dados recebidos.

Esta auditoria não substitui um teste de intrusão nem uma auditoria completa do back-end.

## Achados e correções

| Prioridade | Achado | Correção |
|---|---|---|
| Alta | Login e formulários de cadastro continham valores de teste preenchidos, inclusive e-mail, CPF e senha. Isso expunha dados no HTML e permitia envio acidental de informações de demonstração. | Removidos os valores dos campos em `login.html`, `cadastro-denunciante.html` e `cadastro-representante.html`; adicionados atributos de autocomplete adequados. |
| Alta | A rotina de renovação de token havia sido reduzida a `metodo` e `token`, mas o endpoint também exige os claims mínimos do visitante. A renovação de sessão de visitante poderia falhar. | Renovação limitada a sessões de visitante e envio apenas dos campos exigidos para esse perfil. Sessões de contas cadastradas expiram e devem autenticar novamente, conforme a validação do back-end. |
| Média | A tela de criação de denúncia encerrava sua inicialização quando aberta fora de um iframe, embora a rota também possa ser acessada diretamente. | Removido o bloqueio de inicialização; autenticação continua sendo exigida e a comunicação com o modal continua opcional. |
| Média | Respostas não-OK e respostas não JSON nem sempre eram tratadas consistentemente; alguns logs podiam imprimir payloads ou mensagens originadas do servidor. | Padronizadas verificações HTTP nas chamadas revisadas e mensagens de erro genéricas no console; removida a impressão de respostas e dados pessoais. |
| Média | A validação de token enviava o mesmo token no corpo e no cabeçalho `Authorization`, sem necessidade para esse endpoint. | Removido o cabeçalho duplicado; o token continua no corpo, como exigido pelo controller. |
| Média | Respostas assíncronas antigas do serviço do IBGE podiam substituir a lista de municípios da UF mais recente. | Incluídos controles de sequência e validação da estrutura JSON nas telas de cadastro e configurações. Requisições ao IBGE usam HTTPS e `credentials: omit`. |
| Baixa | A limpeza do armazenamento local podia apagar chaves não relacionadas à sessão ou deixar dados de perfil obsoletos após logout. | Limpeza seletiva das chaves de sessão nos fluxos revisados. |

Arquivos principais alterados: [start-app.js](app/view/scripts/start-app.js), [login.js](app/view/scripts/login.js), [criar-denuncia.js](app/view/scripts/criar-denuncia.js), [cadastro-denunciante.js](app/view/scripts/cadastro-denunciante.js), [cadastro-representante.js](app/view/scripts/cadastro-representante.js), [mural.js](app/view/scripts/mural.js), [mural-configuracoes.js](app/view/scripts/mural-configuracoes.js), [perfil.js](app/view/scripts/perfil.js), [editar-denuncia.js](app/view/scripts/editar-denuncia.js), [ver-denuncia.js](app/view/scripts/ver-denuncia.js), [navbar.js](app/view/scripts/navbar.js) e [contato.js](app/view/scripts/contato.js).

## Testes e validação

- [tests/run.php](tests/run.php): 10 testes existentes passaram em PHP 8.0.30 e 8.4.15.
- [tests/client-contracts.php](tests/client-contracts.php): 6 testes de regressão estática passaram em PHP 8.0.30 e 8.4.15. Cobrem valores demonstrativos, validação HTTP do login, acesso à criação fora de iframe, logs de payload, concorrência/credenciais do IBGE e contrato de renovação.
- Verificação manual no navegador local: os campos de login e cadastro carregaram vazios; o formulário de login vazio foi considerado inválido pelo navegador.
- A página do mural foi carregada, mas a consulta de dados falhou no back-end local. Assim, não foi possível validar dinamicamente as 29 chamadas com todos os fluxos de autenticação e dados reais.
- O ambiente não disponibilizou Node.js no PATH; a validação automatizada de JavaScript foi feita pelos testes de contrato e pelo carregamento das páginas no navegador.

## Riscos e limitações pendentes

1. **Token e dados de perfil no `localStorage`:** continuam acessíveis a qualquer JavaScript executado na origem. A correção estrutural recomendada é migrar a sessão para cookie `HttpOnly`, `Secure` e `SameSite`, com proteção CSRF e ajustes coordenados no back-end. Isso excede uma correção exclusivamente client-side e poderia alterar o contrato de autenticação.
2. **Integração com o back-end:** chamadas protegidas e operações de gravação não foram integralmente testadas com uma conta e uma base de dados de teste funcional. Os testes locais não comprovam autorização por perfil, persistência nem tratamento de falhas de cada endpoint.
3. **Cabeçalhos de segurança e transporte:** a validação em `localhost` não comprova HTTPS em produção nem políticas CSP, CORS, HSTS, cookies ou limites de requisição no servidor.
4. **Desempenho de busca:** os fluxos de busca/paginação foram inspecionados no cliente, mas não houve benchmark de rede ou de consultas com volume representativo. A otimização do banco e do back-end deve ser avaliada separadamente.
5. **Serviço do IBGE:** a UF é enviada a um serviço externo para carregar municípios. O pedido não inclui credenciais; ainda assim, a disponibilidade e a privacidade do acesso dependem desse terceiro.

## Resultado

Foram corrigidos defeitos observáveis no cliente e adicionados testes de regressão sem introduzir ou remover funcionalidades de produto. A cobertura em execução real permanece parcial devido à falha da consulta de dados do mural no ambiente local; os resultados acima distinguem explicitamente as verificações estáticas das verificações no navegador.

## Atualização: falha no cadastro local (20/11/2026)

Após relatos de falha no cadastro, a inspeção dos logs do PHP e do esquema MariaDB/MySQL local identificou a causa concreta: `tb_usuario` não tinha a coluna `area_atuacao_usuario` usada pelo `INSERT` de todos os cadastros (`SQLSTATE 42S22`). A tabela de categorias também estava vazia, impedindo selecionar uma área no cadastro de representante.

Foi aplicada a migração aditiva [migracao-cadastro-area.sql](doc/migracao-cadastro-area.sql), que adiciona a coluna nullable e insere, sem duplicação, as três categorias já documentadas pelo projeto. O front-end dos dois cadastros agora registra nome/mensagem e código/status sem despejar payloads, trata resposta HTTP não-JSON e apresenta a falha no formulário. Detalhes SQL permanecem somente no log PHP do servidor.

Validação após a correção: cadastros sintéticos de munícipe e representante responderam HTTP 200 e sucesso; os registros temporários foram removidos ao fim do teste. O banco local usa tabelas MyISAM, portanto a migração não adicionou chave estrangeira nem alterou dados existentes.

## Atualização: carregamento do perfil (20/11/2026)

O log local mostrou dois defeitos independentes: `PerfilRepositorio::obterDadosPerfil` lia `$GLOBALS['municipe']` e `$GLOBALS['representante']`, que não existem e geravam warnings antes do JSON; e o banco local não tinha `anonima_denuncia`, usada pela rotina legada de carregamento do perfil. Os warnings corrompiam a resposta de `user/dados`, levando ao erro de parsing visto no console.

Removi as leituras globais não utilizadas, adicionei diagnósticos seguros no `perfil.js` (rota, status e Content-Type, sem despejar a resposta pessoal) e fiz o perfil carregar as denúncias depois de conhecer o usuário. Munícipes consultam `denuncia/minhas`; representantes consultam `denuncia/recebidas`, cujo escopo é validado no servidor por cidade, estado e área cadastrada. A interface de representante não oferece ações de editar/remover denúncias de terceiros.

Foi aplicada a migração aditiva [migracao-perfil-denuncias.sql](doc/migracao-perfil-denuncias.sql) no banco local para incluir `anonima_denuncia` e criar a tabela de aprovação ausente. As tabelas legadas usam MyISAM; por isso a nova tabela de aprovação não cria chave estrangeira nesse ambiente.

Teste integrado pós-correção: com contas e uma denúncia sintéticas, `user/dados` retornou JSON válido para munícipe e representante; `denuncia/minhas` retornou a denúncia do munícipe e `denuncia/recebidas` retornou a mesma denúncia para representante aprovado dentro do escopo de cidade/estado/área. Todos os registros temporários foram removidos.

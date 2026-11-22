# Relatório de modernização e validação

**Data inicial:** 20/11/2026  
**Projeto:** RelateSBC, PHP/MySQL e JavaScript  
**Escopo desta entrega:** correções comprovadas, compatibilidade do esquema, segurança de logout e testes de regressão.

## Resumo executivo

Foram encontrados defeitos que impediam o funcionamento do painel de conversas e notificações no banco WAMP local, além de divergências entre o esquema e os fluxos administrativos. Também havia exclusão de conta incompleta quando existiam conversas e logout acionável por `GET`.

Os defeitos prioritários desta entrega foram corrigidos. A configuração manual em [config.php](app/config.php) foi mantida. As migrações aditivas foram testadas em bancos descartáveis e as tabelas ausentes foram criadas no banco WAMP local sem alterar registros existentes.

**Esta entrega não é uma reescrita integral do sistema.** O projeto tem dezenas de controladores, repositórios e scripts de interface, mas não possui uma especificação executável de todos os fluxos nem uma suíte end-to-end completa. Substituí-lo todo de uma vez, sem esses contratos, poderia remover funcionalidades. O trabalho feito é uma primeira etapa segura; a reestruturação total deve ser incremental, com critérios de paridade e testes por fluxo.

## Achados e correções

| Prioridade | Achado | Ação e estado |
|---|---|---|
| P0 | O banco local não tinha `tb_conversa` nem `tb_notificacao`; o navegador e o log PHP mostravam falhas ao carregar conversas/notificações. As tabelas centrais locais (`tb_usuario`, `tb_denuncia`, `tb_resposta`, `tb_categoria`) usam MyISAM. | Criada [migracao-chat-notificacoes.sql](doc/migracao-chat-notificacoes.sql), com índices e sem chaves estrangeiras incompatíveis com as tabelas MyISAM existentes. Migração aplicada no WAMP; após isso, uma nova abertura do mural não produziu erros de console. |
| P0 | A administração consultava `tb_admin_auditoria` não definida no esquema e inseria notificações do tipo `sistema`, ausente do `ENUM`. A decisão administrativa podia falhar ou ser revertida. | O esquema completo [script-tabelas.md](doc/script-tabelas.md) agora define a tabela e o tipo. [migracao-admin.sql](doc/migracao-admin.sql) atualiza bancos antigos e foi aplicada localmente. |
| P0 | A migração de perfil tinha vírgula final inválida na definição de `tb_representante_aprovado`; o script completo também não terminava o `INSERT` de categorias com ponto e vírgula. | Corrigidos em [migracao-perfil-denuncias.sql](doc/migracao-perfil-denuncias.sql) e [script-tabelas.md](doc/script-tabelas.md). |
| P1 | Excluir uma conta com mensagens/conversas podia falhar por dependências pendentes. Em bancos legados sem chaves estrangeiras, notificações e histórico também poderiam ficar órfãos. | [perfilRepositorio.php](app/database/repositorios/perfilRepositorio.php) agora limpa mensagens, conversas, notificações, aprovações, respostas e denúncias na ordem necessária; preserva registros de auditoria/histórico aplicáveis e não apaga dados de outros usuários. Teste com SQLite e chaves estrangeiras verifica o isolamento. |
| P1 | O repositório de perfil lia a tabela configurada, mas consultava `tb_usuario` literalmente. Nomes de tabela configuráveis não eram respeitados nessa superfície. | As consultas de perfil/conta agora usam os nomes definidos em `config.php`, com validação estrita do identificador para não aceitar SQL arbitrário. Foram adicionadas configurações explícitas para as novas tabelas. |
| P1 | `user/logout` destruía a sessão por `GET`, sem validar token. Uma navegação forçada podia encerrar a sessão. | O cliente agora envia `POST`; o servidor só encerra a sessão após validar o token de usuário. Teste de contrato cobre o método e a validação. |
| P2 | A cobertura automatizada existente exercitava objetos de valor e contratos estáticos, sem cobrir exclusão com dependências ou configuração manual de tabelas. | Adicionados testes de regressão e segurança em [run.php](tests/run.php) e [client-contracts.php](tests/client-contracts.php). |

### Integridade e limites do banco legado

As novas tabelas criadas por migração são InnoDB e indexadas. Não recebem chaves estrangeiras para as tabelas principais porque as tabelas existentes são MyISAM. O código aplica as verificações e a limpeza referencial, mas operações que envolvem tabelas MyISAM **não são transacionais**, mesmo quando o PDO abre uma transação. Converter as tabelas centrais para InnoDB exige backup, plano de conversão e validação de dados; isso não foi feito nesta entrega.

O esquema completo para novas instalações mantém InnoDB e suas chaves estrangeiras. As instruções de migração legada estão no final de [script-tabelas.md](doc/script-tabelas.md).

## Verificações executadas

| Verificação | Resultado |
|---|---|
| `tests/run.php` com PHP 8.0.30 | **16 aprovados, 0 falharam** |
| `tests/client-contracts.php` com PHP 8.0.30 | **12 aprovados, 0 falharam** |
| `tests/run.php` com PHP 8.4.15 | **16 aprovados, 0 falharam** |
| `tests/client-contracts.php` com PHP 8.4.15 | **12 aprovados, 0 falharam** |
| Sintaxe PHP em `app/` e `tests/`, PHP 8.4.15 | **59 arquivos sem erros** |
| `tests/http-profile.php` com PHP 8.0.30 e 8.4.15 | **9 cenários aprovados em cada versão**, com contas e dados sintéticos via HTTP e banco WAMP |
| Esquema completo documentado em banco descartável | **Passou** |
| Migrações de perfil, chat/notificações e administração em banco descartável baseado em MyISAM | **Passou** |
| Reaplicação da migração administrativa em banco descartável | **Passou** |
| HTTP sem credenciais: logout GET/POST e rotas protegidas de chat/notificação | HTTP 200 com resposta de negócio `false`; requisições sem credenciais foram recusadas |
| HTTP sem credenciais: listagem pública de denúncias e categorias | HTTP 200 com resposta de negócio `true` |
| Navegação de páginas públicas e rotas protegidas | Páginas responderam; as páginas protegidas redirecionaram para login quando aplicável |
| Mural após criação das tabelas ausentes | Nenhum novo erro de console no carregamento verificado |

Os testes de esquema e migração foram executados em schemas temporários com nomes exclusivos, removidos ao fim de cada teste. As migrações no WAMP foram aditivas; a suíte HTTP posterior criou apenas usuários e registros sintéticos, que foram removidos ao final.

## Testes recomendados que ainda faltam

Os itens abaixo são necessários para afirmar cobertura dos casos de uso do site. Não foram executados nesta entrega:

| Prioridade | Fluxo/casos a automatizar | Estado |
|---|---|---|
| P0 | Cadastro de munícipe e representante: campos válidos/inválidos, CPF/e-mail duplicados, senha, categoria, município indisponível e erro SQL | Não executado end-to-end |
| P0 | Login: senha correta/incorreta, mensagens não enumeráveis, expiração, limitação de tentativas entre sessões e logout | Logout coberto por integração HTTP autenticada; demais casos de login e rate limit ainda sem E2E |
| P0 | Autorização: visitante, usuário, representante pendente/aprovado e administrador; tentativa de acesso cruzado a registros | Propriedade de denúncias e acesso cruzado cobertos na integração; matriz completa de perfis ainda não executada |
| P1 | Denúncia: criar, editar, remover, anonimato, upload MIME/tamanho inválido, limites, erro de rede e consistência de dados | Edição e remoção, incluindo autorização e persistência, cobertas por integração HTTP; criação, upload e erros de rede ainda não |
| P1 | Mural/detalhes: busca, filtros, paginação, estados sem resultados, comentários e exposição de dados anônimos | Comentários (anônimo, vazio, excesso de tamanho e persistência segura) cobertos por integração; mural, filtros, paginação e privacidade ainda sem E2E |
| P1 | Representante/administração: aprovação/revogação, mudança de status, histórico, exportação, concorrência e rollback | Não executado end-to-end |
| P1 | Conversas e notificações: iniciar, autorização de participantes, envio, encerramento, leitura e notificações do tipo `sistema` | Migração validada; fluxos funcionais não executados |
| P1 | Conta/configuração: preferências, troca de e-mail/senha/localidade, exclusão com todas as dependências e falha intermediária | Preferências, e-mail, senha, localidade, exclusão e logout cobertos por integração HTTP; falhas intermediárias e fluxos completos no navegador ainda não |
| P2 | Contato: validação, abuso/rate limit e integração real de e-mail | Não executado |
| P2 | Falha/timeout/JSON inválido em cada `fetch`, respostas fora de ordem e renovação/expiração de token | Cobertura parcial em contratos; matriz completa não executada |
| P2 | Acessibilidade/teclado/mobile, volume de dados, concorrência, CPU e memória | Não executado |
| P2 | Testes de segurança dinâmicos: XSS, CSRF, controle de acesso, brute force, upload, SQL injection e limites de requisição | Integração cobre token ausente/inválido/expirado, autorização de propriedade e validação de campos; pentest de XSS/CSRF, brute force, upload e limites não executado |

Os cenários de integração usam contas e registros sintéticos no banco WAMP, autenticação e requisições HTTP reais ao app; não usam contas reais. As tabelas legadas são MyISAM, portanto operações de banco não têm rollback transacional garantido. O script remove os usuários, denúncias, comentários e cookies temporários ao final. Cadastro, mensagens/conversas e contato continuam sem integração end-to-end.

## Riscos e trabalho futuro

1. **Token em `localStorage`:** uma injeção de script pode ler o token. Avaliar migração compatível para cookie `HttpOnly`, `Secure` e `SameSite`, com desenho explícito de CSRF e estratégia de transição.
2. **Limite de login por sessão:** a contagem atual é associada à sessão PHP e pode ser contornada abrindo novas sessões. Adicionar limitação compartilhada por conta/IP com expiração, observabilidade e proteção contra bloqueio abusivo.
3. **Município quando o IBGE falha:** os formulários permitem valor digitado quando a lista não carrega. Definir fallback e validação no servidor antes de alterar esse comportamento.
4. **Privacidade de denúncia anônima:** revisar se rua e CEP exatos devem ser expostos no detalhe público. É necessária decisão de produto; nenhuma visibilidade funcional foi alterada nesta entrega.
5. **Rota de teste legada:** revisar o endpoint público que retorna sucesso de exemplo para cadastro, para evitar respostas de sucesso sem persistência.
6. **Métodos HTTP:** validar a convenção do roteador (GET/POST) contra todas as chamadas; a renovação de token e campos `metodo` merecem testes de contrato dedicados.
7. **Desempenho:** estabelecer baseline com consultas representativas, `EXPLAIN`, volumes reais anonimizados e teste de carga antes de alterar paginação, índices ou cache.
8. **Reescrita arquitetural:** decompor em etapas (configuração/DI, roteamento e middleware, domínio/repositorios, cliente e telas), mantendo `config.php` como arquivo manual de ambiente/tabelas e exigindo testes de paridade para cada fluxo antes de remover implementação legada.

## Limitações de ferramentas e ambiente

Não havia Node.js/npm, PHPUnit, Composer CLI ou ferramenta de análise estática disponível no `PATH`. Por isso, os testes JavaScript existentes foram executados como contratos PHP e a validação visual foi feita no navegador integrado. Não houve teste de integração com o serviço externo de municípios nem com o provedor de e-mail. A produção não foi acessada.

## Atualização de testes — 22/11/2026

Foi corrigido o logout sem corpo: o navegador envia `POST /user/logout`, mas o roteador exigia o campo `metodo` no formulário e rejeitava o pedido antes do controlador. O roteador agora assume `POST` quando esse campo está ausente e mantém o túnel legado de `PUT`. O cliente envia o token no cabeçalho, impede cliques repetidos e informa falhas ao usuário.

Também foram corrigidos o uso da chave configurável da tabela de respostas e o complemento opcional da edição de denúncia. Uma falha observada no painel ao salvar status foi corrigida: o handler agora guarda o botão antes do `await`, evitando acessar `event.currentTarget` depois da conclusão assíncrona. Os testes de integração autenticados exercitam logout sem corpo, credenciais ausentes/inválidas/expiradas, reautenticação, preferências, edição de e-mail/senha/localidade, escopo de edição/exclusão de denúncias, comentários e exclusão de conta.

| Validação atualizada | Resultado |
|---|---|
| `tests/run.php`, PHP 8.0.30 e 8.4.15 | **16/16 aprovados em cada versão** |
| `tests/client-contracts.php`, PHP 8.0.30 e 8.4.15 | **12/12 aprovados em cada versão** |
| `tests/http-profile.php`, PHP 8.0.30 e 8.4.15 | **9/9 cenários aprovados em cada versão** |
| `php -l` em `app/` e `tests/`, PHP 8.4.15 | **59 arquivos sem erros** |

Os testes HTTP usam contas e registros sintéticos no WAMP e removem os registros e cookies temporários ao final. O exercício do cliente no navegador foi limitado ao handler de logout com resposta HTTP simulada; não equivale a uma validação visual nem a um fluxo completo com sessão real. Continuam pendentes os casos de uso listados acima como não executados.

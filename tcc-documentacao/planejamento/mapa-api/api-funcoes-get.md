# Funções para cada chamada de api

## Método GET
- ***/home***
    - 1°  Verifica se o usuario já fez login
        - Se Sim
            - atualiza o header para ON
            - Envia a página para o usuario
            - **FIM**
        - Se Não
            - atualiza o header para Off
            - Envia a página para o usuario
            - **FIM**

- ***/home/denuncias***
    - 1° Verifica se o usuario já fez login
        - Se Sim
            - Armazena nome, estado
            - Filtra as denuncias pelo estado
            - Envia a página para o usuario
            - **FIM**
        - Se Não
            - Filtra as denuncias mais recentes
            - Envia a página para o usuario
            - **FIM**

- ***/cadastro***
    - 1° Verifica se o usuario está ativo
        - Se Sim
            - Redirecionar o usuario para Home
            - **FIM**
        - Se Não
            - Envia a página Selecionar Cadastro
            - **FIM**

- ***/cadastro/municipe***
    - 1° Verifica se o usuario está ativo
        - Se Sim
            - Redirecionar o usuario para Home
            - **FIM**
        - Se Não
            - Envia a página Cadastrar Municipe
            - **FIM**

- ***/cadastro/representante***
    - 1° Verifica se o usuario está ativo
        - Se Sim
            - Redirecionar o usuario para Home
            - **FIM**
        - Se Não
            - Envia a página Cadastrar Representante
            - **FIM**

- ***/login***
    - 1° Verifica se o usuario está ativo
        - Se Sim
            - Redirecionar o usuario para Home
            - **FIM**
        - Se Não
            - Envia a página Fazer Login
            - **FIM**

- ***/user***
    - 1° Verifica se o usuario está ativo
        - Se Sim
            - Verificar o Perfil do usuario
                - Se Municipe
                    - Chamar **/user/municipe**
                    - **FIM**
                - Se Representante
                    - Chamar **/user/representante**
                    - **FIM**
        - Se Não
            - Chamar **/home**
            - **FIM**

- ***/user/municipe***
     - 1° Verifica se o usuario está ativo
         - Se Sim
             - Verificar o perfil do usuario
                 - Se Municipe
                     - Enviar Pagina Home Municipe
                     - **FIM**
                 - Se Outro
                     -  chamar **/home**
                     - **FIM**
         - Se Não
             - chamar **/home**
             - **FIM**
# Funções para cada chamada de api

## Método GET
- ***/home***
    - 1°  Verifica se o usuario já fez login
    
        - Se Sim
            
                atualiza o header para ON 
                Envia a página para o usuario
                **FIM**


        - Se Não

                atualiza o header para Off
                Envia a página para o usuario
                **FIM**

- ***/home/denuncias***
        
    - 1° Verifique se o usuario já fez login

        - Se Sim

                Armazena nome, estado
                Filtra as denuncias pelo estado
                Envia a página para o usuario
                **FIM**
        - Se nao

                Filtra as denuncias mais recentes
                Envia a pagina para o usuario
                **FIM**

- ***/cadastro***
        
    - 1° Verifique se o usuario está ativo
        - Se Sim

                Redirecionar o usuario para Home
                **FIM**
        
        - Se Não

                Envia a pagina Selecionar Cadastro
                **FIM**

- ***/cadastro/municipe***
        
    - 1° Verifica se o usuario está ativo
        
        - Se Sim

                Redirecione o usuario para Home
                **FIM**

        - Se Não
        
                Envia a página Cadastrar Municipe
                **FIM**

- ***/cadastro/representante***

    - 1° Verifica se o usuario está ativo

        - Se Sim 

                Redirecione o usuario para Home
                **FIM**

        - Se Não

                Envia a página Cadastrar Representante
                **FIM**

- ***/login***

    - 1° Verifica se o usuario está ativo

        - Se Sim 

                Redirecione o usuario para Home
                **FIM**

        - Se Não

                Envia a página Fazer Login
                **FIM**


-***/
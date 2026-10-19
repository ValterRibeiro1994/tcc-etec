const DEBUG = true;
console.log("Iniciando app");
// checa se temos um token armazenado
if (!localStorage.getItem("token")){ // se nao
    console.log("\t -> Processo para TOKEN não armazenado iniciado");
    async function criarVisitante() {
        // solcita um token de visitante
        console.log("\t\t --> Função para criar uma sessão de visitante iniciada...");
        console.log("Solicitando um token de visitante ao servidor");
        let servidor = await fetch("../app-web/token/criar/?perfil=visitante", {
            method: "GET",
        });

        console.log("aguardando resposta do servidor...");
        let resposta = await servidor.json();
        if (DEBUG){
            console.log("\tDebug ativo: resposta do servidor...");
            console.log(resposta);
        }
        if (resposta.resposta){
            console.log("Servidor respondeu com sucesso");
            localStorage.setItem("token", resposta.dados.token);
            localStorage.setItem("token", resposta.dados.token)
            localStorage.setItem("perfil", resposta.dados.perfil);
            localStorage.setItem("nome", resposta.dados.nome);
            localStorage.setItem("sobrenome", resposta.dados.sobrenome);
            localStorage.setItem("email", resposta.dados.email)
            console.log("Os dados do servidor foram armazenados com sucesso.")
        } else {
            console.log("Servidou respondeu com falha");
            localStorage.clear();
            console.log("Limpamos todos os dados do armazenamento");
        }
        console.log("\t\t --> Função para criar visitante encerrada.");
    
    }
    criarVisitante();
    console.log("\t -> Processo para TOKEN não armazenado encerrado");
} else { //existe um token 
    console.log("\t -> Processo para TOKEN armazenado iniciado");
    // valida o token que temos
    async function validarToken() {
        console.log("Recuperando os valores armazenados");
        let NOME = localStorage.getItem("nome");
        let SOBRENOME = localStorage.getItem("sobrenome");
        let EMAIL = localStorage.getItem("email");
        let PERFIL = localStorage.getItem("perfil");
        let CIDADE = null;
        let ESTADO = null;
        let ORGAO = null;
        let CARGO = null;
    
        if (PERFIL !== "visitante"){
            CIDADE = localStorage.getItem("cidade");
            ESTADO = localStorage.getItem("estado");
        }
    
        if (PERFIL == "representante"){
            ORGAO = localStorage.getItem("orgao");
            CARGO = localStorage.getItem("cargo");
        }
        
        console.log("Criando o corpo da requisição");
        let dados = new URLSearchParams({
            "metodo": "POST",
            'token': localStorage.getItem("token"),
            'perfil': PERFIL,
            'nome': NOME,
            'sobrenome': SOBRENOME,
            'email': EMAIL,
            'cidade': CIDADE,
            'estado': ESTADO,
            'orgao': ORGAO,
            'cargo': CARGO
        });

        console.log("Enviando a solicitação para o servidor...");
        console.log("Dados da requisição: ", dados);
        let servidor = await fetch("../app-web/token/validar", {
            method: "POST",
            headers: {"Authorization": localStorage.getItem("token")},
            body: dados
        });

        console.log("aguardando resposta do servidor...");
        let resposta = await servidor.json();

        if (DEBUG){
            console.log("Debug ativo: validar token");
            // console.log(resposta);
            console.log(JSON.stringify(resposta));
        }

        if (!resposta.resposta){ // se houve erro
            if (resposta.dados.status === "expirado"){
                console.log("\t -> Processo renovar token iniciado");
                console.log("O token está expirado");
                console.log("Solicitando a renovação do token");
                // renova o token de acesso
                dados.set("metodo", "PUT");
                let servidor = await fetch("../app-web/token/renovar?metodo=PUT", {
                    method: "POST",
                    headers: {"Content-Type": "application/json"},
                    body: JSON.stringify(dados)
                });

                console.log("Aguardando resposta do servidor");
                let resposta = await servidor.json();
                if (DEBUG){
                    console.log("Debug ativo: Renovando token");
                    console.log(JSON.stringify(resposta));
                }
                if (!resposta.resposta){
                    // se houve falha na renovação do token limpa o armazenamento
                    localStorage.clear();
                    console.log("Limpamos os dados armazenados...");
                    console.log("Servidor respondeu com erro ao renovar o token...");
                } else {
                    console.log("Servidor respondeu com sucesso...");
                    localStorage.setItem("token", resposta.dados.token);
                    localStorage.setItem("perfil", resposta.dados.perfil);
                    localStorage.setItem("nome", resposta.dados.nome);
                    localStorage.setItem("sobrenome", resposta.dados.sobrenome);
                    localStorage.setItem("email", resposta.dados.email);

                    if (resposta.dados.perfil != "visitante"){
                        localStorage.setItem("cidade", resposta.dados.cidade);
                        localStorage.setItem("estado", resposta.dados.estado);
                    }

                    if (resposta.dados.perfil == "representante"){
                        localStorage.setItem("orgao", resposta.dados.orgao);
                        localStorage.setItem("cargo", resposta.dados.cargo);
                    }
                    console.log("Dados armazenados com sucesso");
                }
                console.log("\t -> Processo renovar token encerrado.");
            } else {
                localStorage.clear();
                console.log("Os dados armazenados foram limpos");
                console.log("Servidor respondeu com erro ao validar o token");
            }
        }
    }
    validarToken();
}


const DEBUG = true;

if (!localStorage.getItem("token")) {

    async function criarVisitante() {
        let servidor = await fetch("./token/criar/?perfil=visitante&nome=visitante&sobrenome=visitante&email=visitante@gmail.com&lembrar=false", {
            method: "GET",
        });

        let resposta = await servidor.json();
        
        if (DEBUG) {
            console.log(resposta);
        }

        if (resposta.resposta) {
            localStorage.setItem("token", resposta.dados.token);
            localStorage.setItem("perfil", resposta.dados.perfil);
            localStorage.setItem("nome", resposta.dados.nome);
            localStorage.setItem("sobrenome", resposta.dados.sobrenome);
            localStorage.setItem("email", resposta.dados.email);
        } else {
            localStorage.clear();
        }
    }
    
    criarVisitante();
} else { 

    async function validarToken() {
        let NOME = localStorage.getItem("nome");
        let SOBRENOME = localStorage.getItem("sobrenome");
        let EMAIL = localStorage.getItem("email");
        let PERFIL = localStorage.getItem("perfil");
        let CIDADE = null;
        let ESTADO = null;
        let ORGAO = null;
        let CARGO = null;
    
        if (PERFIL !== "visitante") {
            CIDADE = localStorage.getItem("cidade");
            ESTADO = localStorage.getItem("estado");
        }
    
        if (PERFIL == "representante") {
            ORGAO = localStorage.getItem("orgao");
            CARGO = localStorage.getItem("cargo");
        }
        
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

        let servidor = await fetch("./token/validar", {
            method: "POST",
            headers: {"Authorization": "Bearer " + localStorage.getItem("token")},
            body: dados
        });

        let resposta = await servidor.json();

        if (DEBUG) {
            console.log(JSON.stringify(resposta));
        }

        if (!resposta.resposta) {
            if (resposta.dados.status === "expirado") {
                dados.set("metodo", "PUT");
                let servidor = await fetch("./token/renovar", {
                    method: "POST",
                    body: dados
                });

                let resposta = await servidor.json();
                
                if (DEBUG) {
                    console.log(JSON.stringify(resposta));
                }
 
                if (!resposta.resposta) {
                    localStorage.clear();
                } else {
                    localStorage.setItem("token", resposta.dados.token);
                    localStorage.setItem("perfil", resposta.dados.perfil);
                    localStorage.setItem("nome", resposta.dados.nome);
                    localStorage.setItem("sobrenome", resposta.dados.sobrenome);
                    localStorage.setItem("email", resposta.dados.email);

                    if (resposta.dados.perfil != "visitante") {
                        localStorage.setItem("cidade", resposta.dados.cidade);
                        localStorage.setItem("estado", resposta.dados.estado);
                    }

                    if (resposta.dados.perfil == "representante") {
                        localStorage.setItem("orgao", resposta.dados.orgao);
                        localStorage.setItem("cargo", resposta.dados.cargo);
                    }
                }
            } else {
                localStorage.clear();
            }
        }
    }
    
    validarToken();
}
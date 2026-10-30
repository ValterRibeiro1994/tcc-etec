const DEBUG = true;
const RAIZ = "../app-web/";

async function gerenciarAutenticacaoEPerfil() {
    // 1. Fase de Autenticação / Token (Primeira Parte)
    if (!localStorage.getItem("token")) {
        await criarVisitante();
    } else {
        await validarToken();
    }

    // 2. Fase de Verificação e Requisição de Dados do Perfil (Segunda Parte)
    let perfilAtual = localStorage.getItem("perfil");
    let tokenAtual = localStorage.getItem("token");
    
    if (!perfilAtual || !tokenAtual) {
        await desconectar();
        localStorage.clear();
        alert("Faça login antes de acessar seu perfil");
        window.location.href = RAIZ + "login";
        return;
    }

    if (perfilAtual == "municipe" || perfilAtual == "representante") {
        
        // Obtém os dados do usuário e de denúncias
        let dados = await obterDados();
        if (DEBUG) {
            console.log("\n-> Dados obtidos: " + JSON.stringify(dados));
        }

        if (dados.resposta) {
            if (DEBUG) {
                console.log("\n-> Resposta : " + JSON.stringify(dados.dados));
                console.log("\n-> Dados usuario: " + JSON.stringify(dados.dados.usuario));
                console.log("\n-> Dados denuncia: " + JSON.stringify(dados.dados.denuncias));
                console.log("\n\n\t\t--- LISTANDO DADOS ---\n\n");
            }

            // Armazena os dados do usuário no localStorage
            let dados_usuario = dados.dados.usuario;
            if (DEBUG) {
                console.log("\tListando dados de usuario\n");
                if (Object.keys(dados_usuario).length == 0) {
                    console.log("Lista de usuario vazia");
                }
            }
            Object.entries(dados_usuario).forEach(([chave, valor]) => {
                if (DEBUG) {
                    console.log(`Chave: "${chave}": Valor: "${valor}"`);
                }
                localStorage.setItem(chave, valor);
            });

            let dados_denuncias = dados.dados.denuncias;
            if (DEBUG) {
                console.log("\tListando dados de denuncias\n");
                if (Object.keys(dados_denuncias).length == 0) {
                    console.log("Lista de denuncias vazia");
                }
            }
            
            if (dados_denuncias && Object.keys(dados_denuncias).length > 0) {
                Object.entries(dados_denuncias).forEach(([chave, valor]) => {
                    if (DEBUG) {
                        console.log(`Chave: "${chave}": Valor: "${valor}"`);
                    }
                    localStorage.setItem(chave, valor);
                });
            } else {
                localStorage.setItem("denuncias", JSON.stringify([]));
            }
            
        }
    }
}

// Funções Auxiliares

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
        if (resposta.dados && resposta.dados.status === "expirado") {
            dados.set("metodo", "PUT");
            let servidorRenovacao = await fetch("./token/renovar", {
                method: "POST",
                body: dados
            });

            let respostaRenovacao = await servidorRenovacao.json();
            
            if (DEBUG) {
                console.log(JSON.stringify(respostaRenovacao));
            }

            if (!respostaRenovacao.resposta) {
                localStorage.clear();
            } else {
                localStorage.setItem("token", respostaRenovacao.dados.token);
                localStorage.setItem("perfil", respostaRenovacao.dados.perfil);
                localStorage.setItem("nome", respostaRenovacao.dados.nome);
                localStorage.setItem("sobrenome", respostaRenovacao.dados.sobrenome);
                localStorage.setItem("email", respostaRenovacao.dados.email);

                if (respostaRenovacao.dados.perfil != "visitante") {
                    localStorage.setItem("cidade", respostaRenovacao.dados.cidade);
                    localStorage.setItem("estado", respostaRenovacao.dados.estado);
                }

                if (respostaRenovacao.dados.perfil == "representante") {
                    localStorage.setItem("orgao", respostaRenovacao.dados.orgao);
                    localStorage.setItem("cargo", respostaRenovacao.dados.cargo);
                }
            }
        } else {
            localStorage.clear();
        }
    }
}

async function desconectar() {
    await fetch("../user/logout", {
        method: "GET"
    });
}

async function obterDados() {
    let token = localStorage.getItem("token");
    let perfil = localStorage.getItem("perfil");
    let nome = localStorage.getItem("nome");
    let sobrenome = localStorage.getItem("sobrenome");
    let email = localStorage.getItem("email");

    let dados_post = new URLSearchParams({
        'metodo': "POST",
        'perfil': perfil,
        'nome': nome,
        'sobrenome': sobrenome,
        'email': email,
        'token': token,
        'lembrar': true
    });

    if (perfil != "visitante"){
        dados_post.append("cidade", localStorage.getItem("cidade"));
        dados_post.append("estado", localStorage.getItem("estado"));
    }

    if (perfil == "representante"){
        dados_post.append("orgao", localStorage.getItem("orgao"));
        dados_post.append("cargo", localStorage.getItem("cargo"));
    }

    let servidor = await fetch(RAIZ + "user", {
        method: "POST",
        body: dados_post
    });

    let resposta = await servidor.json();
    if (DEBUG){
        console.log("\n-> Processo POST concluido: " + JSON.stringify(resposta));
    }

    return resposta;
}

// Execução principal
gerenciarAutenticacaoEPerfil();

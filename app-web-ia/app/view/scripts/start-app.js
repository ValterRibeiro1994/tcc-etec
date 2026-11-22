const SCRIPT_START_APP = Array.from(document.scripts)
    .find(script => script.src.includes('/app/view/scripts/start-app.js'))?.src;
const RAIZ = SCRIPT_START_APP
    ? new URL('../../../', SCRIPT_START_APP).href
    : new URL('../../../', window.location.href).href;
const CHAVES_SESSAO = ['token', 'perfil', 'nome', 'sobrenome', 'email', 'cidade', 'estado', 'orgao', 'cargo', 'denuncias'];

function limparSessaoLocal() {
    CHAVES_SESSAO.forEach(chave => localStorage.removeItem(chave));
}

async function lerJson(resposta) {
    if (!resposta.ok) throw new Error('Falha na comunicação com o servidor.');
    try {
        return await resposta.json();
    } catch (erro) {
        throw new Error('Resposta inválida do servidor.');
    }
}

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
        limparSessaoLocal();
        alert("Faça login antes de acessar seu perfil");
        window.location.href = RAIZ + "login";
        return;
    }

    if (perfilAtual == "municipe" || perfilAtual == "representante" || perfilAtual == "administrador") {
        
        // Obtém os dados do usuário e de denúncias
        let dados = await obterDados();
        if (dados.resposta) {
            // Armazena os dados do usuário no localStorage
            let dados_usuario = dados.dados.usuario;
            ['nome', 'sobrenome', 'email', 'perfil', 'cidade', 'estado', 'orgao', 'cargo']
                .forEach(chave => {
                    if (dados_usuario[chave] !== undefined && dados_usuario[chave] !== null) {
                        localStorage.setItem(chave, dados_usuario[chave]);
                    }
            });

        }
    }
}

// Funções Auxiliares

async function criarVisitante() {
    let servidor = await fetch(RAIZ + "token/criar/?perfil=visitante&nome=visitante&sobrenome=visitante&email=visitante@gmail.com&lembrar=false", {
        method: "GET",
        credentials: "same-origin"
    });

    let resposta = await lerJson(servidor);

    if (resposta.resposta) {
        localStorage.setItem("token", resposta.dados.token);
        localStorage.setItem("perfil", resposta.dados.perfil);
        localStorage.setItem("nome", resposta.dados.nome);
        localStorage.setItem("sobrenome", resposta.dados.sobrenome);
        localStorage.setItem("email", resposta.dados.email);
    } else {
        limparSessaoLocal();
    }
}

async function validarToken() {
    let dados = new URLSearchParams({
        "metodo": "POST",
        'token': localStorage.getItem("token")
    });

    let servidor = await fetch(RAIZ + "token/validar", {
        method: "POST",
        credentials: "same-origin",
        body: dados
    });

    let resposta = await lerJson(servidor);

    if (!resposta.resposta) {
        if (resposta.dados && resposta.dados.status === "expirado") {
            if (localStorage.getItem("perfil") !== "visitante") {
                limparSessaoLocal();
                return;
            }

            const dadosRenovacao = new URLSearchParams({
                metodo: "PUT",
                token: localStorage.getItem("token"),
                perfil: "visitante",
                nome: localStorage.getItem("nome") || "visitante",
                sobrenome: localStorage.getItem("sobrenome") || "visitante",
                email: localStorage.getItem("email") || "visitante@gmail.com"
            });
            let servidorRenovacao = await fetch(RAIZ + "token/renovar", {
                method: "POST",
                credentials: "same-origin",
                body: dadosRenovacao
            });

            let respostaRenovacao = await lerJson(servidorRenovacao);

            if (!respostaRenovacao.resposta) {
                limparSessaoLocal();
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
            limparSessaoLocal();
        }
    }
}

async function desconectar() {
    await fetch(RAIZ + "user/logout", {
        method: "GET",
        credentials: "same-origin"
    });
}

async function obterDados() {
    let token = localStorage.getItem("token");

    let dados_post = new URLSearchParams({
        'metodo': "POST",
        'token': token
    });

    let servidor = await fetch(RAIZ + "user", {
        method: "POST",
        credentials: "same-origin",
        body: dados_post
    });

    return lerJson(servidor);
}

// Execução principal
gerenciarAutenticacaoEPerfil().catch(error => {
    console.error('Não foi possível validar a sessão.');
});

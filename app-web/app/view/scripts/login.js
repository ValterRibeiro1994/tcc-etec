const formLogin = window.document.getElementById("form-login-main");
const tagResposta = window.document.getElementById("resposta-formulario");
const debug = true;
// console.clear();
// Inicializa o fluxo
main();

function main() {
    if (existeToken()) {
        if (debug) {
            console.log("Usuário já registrou login");
            window.location.href = "/home";
            return;
        }
        return;
    }

    // Ativa o escutador de eventos do formulário de login
    prepararEventoLogin();
}

function prepararEventoLogin() {
    if (!formLogin) return;

    formLogin.addEventListener("submit", async function(evento) {
        evento.preventDefault(); // Evita recarregar a página

        // Prepara e valida localmente
        const validacao = prepararFormulario(formLogin);
        if (!validacao.resposta) {
            exibirResposta(false, validacao.mensagem);
            return;
        }

        try {
            const dados = new URLSearchParams(validacao.dados);
            dados.append("metodo", "POST");
            const servidor = await fetch("./login", {
                method: "POST",
                body: dados
            });

            const respostaJson = await servidor.json();
            
            if (debug) {
                console.log("Resposta do Servidor:", respostaJson);
            }

            if (respostaJson.resposta) {
                let dados_token = respostaJson.dados;
                localStorage.setItem('token', dados_token.token);
                localStorage.setItem("nome", dados_token.nome);
                localStorage.setItem("sobrenome", dados_token.sobrenome);
                localStorage.setItem("email", dados_token.email);
                localStorage.setItem('perfil', dados_token.perfil);
                localStorage.setItem('cidade', dados_token.cidade);
                localStorage.setItem('estado', dados_token.estado);
                if (dados_token.perfil === "representante"){
                    localStorage.setItem('orgao', dados_token.orgao);
                    localStorage.setItem('cargo', dados_token.cargo);
                }

                exibirResposta(true, "Sucesso: Login efetuado com sucesso");
               window.location.href = "/home";
                return;
            }

            // Trata o erro vindo do servidor
            const respostas = respostaJson.mensagem.split(":");
            const origem = respostas[0] ? respostas[0].trim().toLowerCase() : "";

            if (origem === "cliente") {
                exibirResposta(false, respostas[1] || "Erro nos dados enviados");
            } else {
                exibirResposta(false, "Erro: Não foi possível efetuar login");
            }

        } catch (error) {
            console.error(error);
            exibirResposta(false, "Erro de conexão com o servidor.");
        }
    });
}

// Passamos 'dados' como argumento para corrigir o erro de escopo
function validarEntradas(entradas, dados) {
    for (let x = 0; x < entradas.length; x++) {
        const campo = entradas[x];
        const valor = dados.get(campo);

        if (!valor || valor.toString().trim() === "") {
            console.log("Todos os campos devem ser preenchidos");
            alert("Todos os campos devem ser preenchidos");
            return false;
        }
    }
    return true;
}

function prepararFormulario(form) {
    const campos_esperados = ['email', 'senha', 'lembrar'];
    const dados = new FormData(form);
    dados.append("metodo", "POST");

    if (dados.get("lembrar") == null) {
        dados.append("lembrar", "false");
    }

    // Corrigido: enviando 'dados' para a validação
    if (validarEntradas(campos_esperados, dados) === false) {
        return { resposta: false, mensagem: "Erro: Todos os campos devem ser preenchidos" };
    }

    return { resposta: true, mensagem: "Formulário validado", dados: dados };
}

function existeToken() {
    const token = localStorage.getItem("token");
    if (!token) {
        return false; 
    }

    const perfil = localStorage.getItem("perfil");
    if (perfil) {
        if (perfil === "visitante") {
            return false;
        }

        const perfis_validos = ['municipe', 'representante'];
        if (perfis_validos.includes(perfil)) {
            return true;
        }
    }

    return false;
}

function exibirResposta(resposta, mensagem) {
    console.log(resposta, mensagem);
    if (!tagResposta) return;
    
    tagResposta.textContent = message = mensagem;
    // Reseta as classes antes de aplicar a nova
    tagResposta.classList.remove("bg-success", "bg-warning", "text-white");
    
    if (resposta) {
        tagResposta.classList.add("text-white", "bg-success");
    } else {
        tagResposta.classList.add("text-white", "bg-danger");
    }
}

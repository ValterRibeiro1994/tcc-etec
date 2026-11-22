const formLogin = window.document.getElementById("form-login-main");
const tagResposta = window.document.getElementById("resposta-formulario");
// Botão "olho": alterna o campo de senha entre escondido (password) e visível (text)
function prepararOlhoSenha() {
    const campo = window.document.getElementById("campo-senha-login");
    const botao = window.document.getElementById("btn-ver-senha");
    if (!campo || !botao) return;

    const iconeAberto = botao.querySelector(".icone-olho");
    const iconeRiscado = botao.querySelector(".icone-olho-riscado");

    botao.addEventListener("click", function () {
        const mostrar = campo.type === "password";
        campo.type = mostrar ? "text" : "password";

        iconeAberto.hidden = mostrar;
        iconeRiscado.hidden = !mostrar;

        const rotulo = mostrar ? "Ocultar senha" : "Mostrar senha";
        botao.setAttribute("aria-label", rotulo);
        botao.setAttribute("title", rotulo);
        botao.setAttribute("aria-pressed", mostrar ? "true" : "false");

        // devolve o foco ao campo, com o cursor no final do texto
        campo.focus();
        const fim = campo.value.length;
        campo.setSelectionRange(fim, fim);
    });
}


    // Aviso deixado pela tela de cadastro ("Conta criada com sucesso!")
    const avisoCadastro = sessionStorage.getItem("aviso-login");
    if (avisoCadastro) {
        sessionStorage.removeItem("aviso-login");
        exibirResposta(true, avisoCadastro);
    }

    // Ativa o escutador de eventos do formulário de login
    prepararEventoLogin();


function prepararEventoLogin() {
    if (!formLogin) return;

    formLogin.addEventListener("submit", async function(evento) {
        evento.preventDefault();

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
                credentials: "same-origin",
                body: dados
            });

            if (!servidor.ok) throw new Error("Falha na comunicação com o servidor.");
            const respostaJson = await servidor.json();

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

               exibirResposta(true, "Login efetuado com sucesso!");
                setTimeout(function () { window.location.href = "denuncia/mural"; }, 900);
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
            console.error('Não foi possível concluir o login.');
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
            return false;
        }
    }
    return true;
}

function prepararFormulario(form) {
    const campos_esperados = ['email', 'senha', 'lembrar'];
    const dados = new FormData(form);
    dados.append("metodo", "POST");
    dados.append("perfil", "visitante");
    dados.append("token", localStorage.getItem("token"));

    if (dados.get("lembrar") == null) {
        dados.append("lembrar", "false");
    }

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

        const perfis_validos = ['municipe', 'representante', 'administrador'];
        if (perfis_validos.includes(perfil)) {
            return true;
        }
    }

    return false;
}

function exibirResposta(resposta, mensagem) {
    if (!tagResposta) return;

    tagResposta.textContent = mensagem;
    tagResposta.hidden = false;
    // O visual do aviso está em login.css (.login-aviso)
    tagResposta.classList.remove("login-aviso-ok", "login-aviso-erro");
    tagResposta.classList.add(resposta ? "login-aviso-ok" : "login-aviso-erro");
}

// main();
prepararOlhoSenha();
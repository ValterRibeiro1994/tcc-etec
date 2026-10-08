async function iniciarAplicacao() {
    if (localStorage.getItem("token") == null) {
        try {
            console.log("Criando sessão de visitante...");

            const server = await fetch("./home", {
                method: "POST",
                body: new URLSearchParams({ metodo: "GET", perfil: "visitante", token: "criar" })
            })

            if (!server.ok) {
                throw new Error(`Erro HTTP: ${server.status}`);
            }

            const response = await server.json();

            console.log("Resposta recebida:", response);

            if (!response.resposta) {
                throw new Error(
                    response.mensagem ?? "Não foi possível criar a sessão."
                );
            }

            const dados = response.dados;

            localStorage.setItem("token",   );
            localStorage.setItem("nome", dados.nome);
            localStorage.setItem("sobrenome", dados.sobrenome);
            localStorage.setItem("perfil", dados.perfil);
            console.log("Salvando token", localStorage.getItem("token"));

            console.log("Sessão criada com sucesso.");

        } catch (error) {
            
            console.error("Erro:", error);
        }

    } else {
        const token = localStorage.getItem("token");
        // localStorage.clear()
        console.log("Token encontrado:", token);
    }
}

iniciarAplicacao();
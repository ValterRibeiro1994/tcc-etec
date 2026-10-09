async function iniciarAplicacao() {
    // existe token ?
    if (localStorage.getItem("token") == null) { // não
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
                console.log(JSON.stringify(response));
                // apaga 
            } else {

                
                const dados = response.dados;
                
                localStorage.setItem("token",   dados.token);
                localStorage.setItem("nome", dados.nome);
                localStorage.setItem("sobrenome", dados.sobrenome);
                localStorage.setItem("perfil", dados.perfil);
                console.log("Salvando token", localStorage.getItem("token"));
                
                console.log("Sessão criada com sucesso.");
        }

        } catch (error) {
            
            console.error("Erro:", error);
        }

    } else {
        try {

            // armazena o token 
            const token = localStorage.getItem("token");
            // envia o token para validação
            console.log("Enviando token para validação");
            let perfil = localStorage.getItem("perfil");
            let dados =  new URLSearchParams({metodo: "GET", token: "validar", 'token-valor': token, 'perfil': perfil});
            let server  = await fetch("./home", {
                method: "POST",
                headers: {"Authorization": token},
                body: dados
            });

            let response = await server.json();
            if (response.resposta){
                console.log("Servidor respondeu com TRUE o token é valido");
                console.log(JSON.stringify(response))
            } else {
                console.log("Servidor respondeu com false")
               console.log(JSON.stringify(response))
            }
            
        } catch (error){
            console.log("Erro:", error)
        }
    }
}

iniciarAplicacao();
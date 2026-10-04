// Verifica se o usuário já está conectado e o redireciona
if (localStorage.getItem("token") !== null || localStorage.getItem("ativo") !== null) {
    alert("Desconecte antes de cadastrar");
    window.location.href = "./home";    
}

const form = window.document.getElementById("form-login-main");

form.addEventListener("submit", async function(evento){
    evento.preventDefault(); // Evita o recarregamento da página
    
    let campos_esperados = ['email', 'senha', 'lembrar'];
    let dados = new FormData(form);
    dados.append("metodo", "POST");

    // Define 'lembrar' como false caso não tenha sido marcado
    if (dados.get("lembrar") == null) {
        dados.append("lembrar", false);
    }

    // Valida se todos os campos obrigatórios foram preenchidos
    let n = campos_esperados.length;
    for (let x = 0; x < n; x++){
        let campo = campos_esperados[x];
        let valor = dados.get(campo);

        if (!valor || valor.toString().trim() === "") {
            console.log("Todos os campos devem ser preenchidos");
            alert("Todos os campos devem ser preenchidos");
            return;
        }
    }

    let servidor = await fetch("./login", {
        method: "POST",
        body: dados
    });

    let resposta = await servidor.json();
    
    // Tratamento para falha na autenticação
    if (!resposta.resposta){
        let desconectar = await fetch("./user/logout", {
            method: "GET",
            headers: { "Authorization": localStorage.getItem("token") } 
        });

        let response = await desconectar.json();
        console.log("\nResposta sessao: Acesso negado", JSON.stringify(response));
        return;
    }

    console.log(resposta.mensagem);
    let token = resposta.dados.token;
    if (!token) {
        console.error("Resposta: O servidor autorizou o login, mas não retornou um token.");
        return;
    }
    
    // Decodifica o payload do JWT recebido
    const base64Url = token.split('.')[1];
    const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
    const payload = JSON.parse(window.atob(base64));

    // Salva as credenciais no armazenamento local
    localStorage.clear();
    localStorage.setItem("token", token);
    localStorage.setItem("id", payload.id);
    localStorage.setItem("nome", payload.nome);
    localStorage.setItem("email", payload.email);
    localStorage.setItem("ativo", "true");

    alert(`Acesso autorizado: Seja bem vindo ${payload.nome}`);
    window.location.href = "./home";
});

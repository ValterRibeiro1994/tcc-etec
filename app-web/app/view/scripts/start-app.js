// recupera o token armazenado
let TOKEN = localStorage.getItem("token");

// checa se temos um token armazenado
if (!TOKEN){ // se nao
    
    async function criarVisitante() {
        // solcita um token de visitante
        // let dados = {'perfil': 'visitante'};
        let servidor = await fetch("../app-web/token/criar/?perfil=visitante", {
            method: "GET",
        });

        let resposta = await servidor.json();
        console.log(resposta);
        if (resposta.resposta){
            TOKEN = resposta.dados.token;
            console.log(TOKEN);
            localStorage.setItem("token", resposta.dados.token)
            localStorage.setItem("nome", resposta.dados.nome);
        }
    
    }
    criarVisitante();
} else { //existe um token 

    let nome, sobrenome, perfil, email;
    
}


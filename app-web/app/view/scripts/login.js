const form = window.document.getElementById("form-login-main");
try {
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
        console.log(JSON.stringify(resposta));

        
    });
} catch (error){
    console.log(error)
}

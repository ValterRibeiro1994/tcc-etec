const formMain = window.document.getElementById("form-login-main");

async function formSubmit(form) {
    form.addEventListener("submit", async function (event) {
        event.preventDefault();

        let dados = new FormData(form);
        dados.append("metodo", "POST");

        // query seletor para encontrar o checkinput 
        let lembrar = document.querySelector("input[name='lembrar']");
        
        if (lembrar) {
            // Se o usuario apertou para lembrar ele já existe no formulario
            dados.delete("lembrar"); 
            
            // Se o usuario não apertou para lembrar, o formulario não recebe o input marcado
            // adiciona o input mesmo se não foi marcado
            dados.append("lembrar", lembrar.checked);
        }

        let fecth = await fetch("./login", {
            method: "POST",
            body: dados // Envia o FormData completo com a chave 'lembrar' garantida
        });

        let resposta = await fecth.json();
        if (resposta.resposta) {
            window.alert("Acesso autorizado");
            window.location.href = "./home";
        } else {
            console.log(JSON.stringify(resposta));
        }
    });
}

formSubmit(formMain);

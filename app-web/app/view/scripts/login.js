// const formHeader = window.document.getElementById("form-login");
const formMain = window.document.getElementById("form-login-main");

async function formSubmit(form) {
    form.addEventListener("submit", async function  (event) {
        event.preventDefault();
        

        let dados = new FormData(form);
        dados.append("metodo", "POST");

        let  fecth =  await  fetch("./login", {
            method: "POST",
            // headers: "Content-type: application/json",
            body: dados
        })

        let resposta = await fecth.json();
        if (resposta.resposta){
            window.alert("Acesso autorizado")
            window.location.href = "./home"
        } else {
            console.log(JSON.stringify(resposta));
            
        }
      
    })

}

// formSubmit(formHeader)
formSubmit(formMain)
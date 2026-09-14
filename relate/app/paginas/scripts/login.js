const formHeader = window.document.getElementById("formHeader");

// window.location.href = "./user"
console.log(window.location.href)

async function formCabecalho() {
    formHeader.addEventListener("submit", async function  (event) {
        event.preventDefault();

        let dados = new FormData(formHeader);

        let  fecth =  await  fetch("loginController", {
            method: "POST",
            headers: "Content-type: application/json",
            body: dados
        })

        let resposta = await fecth.json();
        if (resposta.status){
            window.location.href = "./user"
        } else {
            alert(resposta.mensagem)
        }

    })
}
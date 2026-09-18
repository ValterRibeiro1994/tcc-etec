const passwordIcons = document.querySelectorAll('.form-field i');

passwordIcons.forEach(icon => {

    icon.addEventListener('click', function () {

        const input = this.parentElement.querySelector('input');

        if (input.type === 'password') {
            input.type = 'text';
            this.classList.remove('fa-eye-slash');
            this.classList.add('fa-eye');
        } else {
            input.type = 'password';
            this.classList.remove('fa-eye');
            this.classList.add('fa-eye-slash');
        }
    });
});


const formHeader = window.document.getElementById("form-login");
async function formCabecalho() {
    formHeader.addEventListener("submit", async function  (event) {
        event.preventDefault();

        let dados = new FormData(formHeader);
        dados.append("metodo", "POST");
        console.log(dados)

        let  fecth =  await  fetch("./login", {
            method: "POST",
            // headers: "Content-type: application/json",
            body: dados
        })

        let resposta = await fecth.json();
        console.log(JSON.stringify(resposta));

        if (resposta.resposta){
            window.alert("Acesso autorizado");
            window.location.href = "./home"
        } else {
            window.alert("Acesso não autorizado");
            window.location.href = "./login"
        }
      
    })

}

formCabecalho();

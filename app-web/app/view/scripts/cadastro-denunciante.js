const DEBUG = true;

// formulario de cadastro
const form = document.getElementById("form-cadastro-denunciante");
form.addEventListener("submit", async function (event) {
    event.preventDefault();
    
    let dadosForm = new FormData(form);
    dadosForm.append('metodo', 'POST');
    dadosForm.append('perfil', 'municipe');
    dadosForm.append('orgao', 'não informado');
    dadosForm.append('cargo', 'não informado');
    dadosForm.append("token", localStorage.getItem("token"));
    
    try {
        let resposta = await fetch("../cadastro/municipe", {
            method: 'POST',
            body: dadosForm
        });
        
        let resultado = await resposta.json();
        console.log(resultado);

        

        let paragrafo = document.getElementById("respostaProcesso");
        paragrafo.textContent = resultado.mensagem;

        if (resultado.resposta){
            paragrafo.classList.add("alert", "alert-success");
        } else {
            paragrafo.classList.add("alert", "alert-warning");
        }

    } catch (error) {
        console.error('Erro ao enviar', error);
    }
})


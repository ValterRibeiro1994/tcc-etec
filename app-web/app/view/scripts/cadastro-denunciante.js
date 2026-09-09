console.log("JS ok");

const form = document.getElementById("form-cadastro-denunciante");
form.addEventListener("submit", async function (event) {
    event.preventDefault();
    
    let dadosForm = new FormData(form);
    dadosForm.append('metodo', 'POST');  
    
    try {
        let resposta = await fetch("municipe", {
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
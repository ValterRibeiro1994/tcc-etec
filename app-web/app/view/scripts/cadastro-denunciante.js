const form = document.getElementById("form-cadastro-denunciante");
form.addEventListener("submit", async function (event) {
    event.preventDefault();
    console.clear();
    console.log("Evento iniciado");
    let dadosForm = new FormData(form);
    dadosForm.append('metodo', 'POST');  
    
    try {
        let resposta = await fetch("cadastrar", {
            method: 'POST',
            body: dadosForm
        });
        
        let resultado = await resposta.json();

        let paragrafo = document.getElementById("respostaProcesso");
        paragrafo.textContent = resultado.mensagem;

        if (resultado.resposta){
            paragrafo.classList.add("bg-primary", "fs-3", "text-center", "p-1", "border", "border-dark", "rounded-pill");
        } else {
            paragrafo.classList.add("bg-danger", "text-white", "text-center", "border", "border-warning", "p-1", "fs-3", "rounded-pill");
        }
        
        console.log("Status: " + resultado.resposta);
        console.log("Mensagem: " + resultado.mensagem);
        console.log("Dados recebido = { ");
        console.log(resultado.dados);
        console.log(" }\n\n")
    } catch (error) {
        console.error('Erro ao enviar', error);
    }

    
})
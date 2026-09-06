const form = document.getElementById("form-cadastro-denunciante");
form.addEventListener("submit", async function (event) {
    event.preventDefault();
    console.clear();
    console.log("Evento iniciado");
    let dadosForm = new FormData(form);
    // dadosForm.append('_metodo', 'POST'); 
    
    try {
        let resposta = await fetch("app-web/denunciante/cadastrar", {
            method: 'POST',
            body: dadosForm
        });
        
        let resultado = await resposta.json();
        console.log(resultado);
    } catch (error) {
        console.error('Erro ao enviar', error);
    }

    
})
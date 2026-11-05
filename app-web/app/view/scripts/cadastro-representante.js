// formulario do representante
const form = document.getElementById("form-cadastro-representante");

form.addEventListener("submit", async function (event) {
    event.preventDefault();
    
    let dadosForm = new FormData(form);
    dadosForm.append('metodo', 'POST');  
    dadosForm.append('perfil', 'representante');  
    
    
    try {
        let resposta = await fetch("../cadastro/representante", {
            method: 'POST',
            body: dadosForm
        });
        let resultado = await resposta.json();
        console.log(resultado);

       let paragrafo = document.getElementById("respostaProcesso");
paragrafo.textContent = resultado.mensagem.replace(/^Cliente:\s*/, "");

if (resultado.resposta){
    paragrafo.classList.add("alert", "alert-success");
    localStorage.clear(); 
    setTimeout(function () {
        window.location.href = "/app-web/login"; 
    }, 1500);
} else {
    paragrafo.classList.add("alert", "alert-warning");
}
    } catch (error) {
        console.error('Erro ao enviar', error);
    }
})

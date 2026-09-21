/** 
 * exemplo de função para obter as categorias registradas no sistema
 * algumas partes do formulario de denuncia deve ser escritas com JS 
 * 
 window.addEventListener('load', async function (event) {
    try {
        let resposta = await fetch('../categoria/categorias', {
            method: 'GET',
        });
        resultado = await resposta.json();
    } catch (error) {
        console.log(error);
    }
});
 * 
 */


const form = document.getElementById("form-cadastro-denunciante");
form.addEventListener("submit", async function (event) {
    event.preventDefault();
    
    let dadosForm = new FormData(form);
    dadosForm.append('metodo', 'POST');  
    
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


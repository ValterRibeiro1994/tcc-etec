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

// Área de atuação: usa as categorias de denúncia cadastradas no banco
const selectArea = document.getElementById("area");

async function carregarAreas() {
    try {
        const resposta = await fetch("../categoria/categorias", { method: 'GET' });
        const json = await resposta.json();
        const categorias = Array.isArray(json.dados) ? json.dados : [];

        if (!json.resposta || categorias.length === 0) {
            selectArea.innerHTML = '<option value="" selected disabled>Nenhuma área disponível</option>';
            return;
        }

        const padrao = new Option("Escolha a área que você atende", "", true, true);
        padrao.disabled = true;
        selectArea.replaceChildren(padrao);
        categorias.forEach(function (categoria) {
            selectArea.add(new Option(categoria.nome_categoria, categoria.nome_categoria));
        });
    } catch (erro) {
        console.error('Erro ao carregar áreas:', erro);
        selectArea.innerHTML = '<option value="" selected disabled>Erro ao carregar áreas</option>';
    }
}

carregarAreas();

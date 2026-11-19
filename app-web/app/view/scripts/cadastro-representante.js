// formulario do representante

function configurarCidadesIBGE(selectEstado, inputCidade, listaCidades) {
    let cidades = [];
    const cache = {};

    function semAcento(texto) {
        return String(texto).normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
    }

    function aplicar(nomes) {
        cidades = nomes;
        listaCidades.replaceChildren(...nomes.map(nome => new Option(nome)));
    }

    async function carregar(uf) {
        if (!uf) return aplicar([]);
        if (cache[uf]) return aplicar(cache[uf]);
        try {
            const resposta = await fetch('https://servicodados.ibge.gov.br/api/v1/localidades/estados/' + uf + '/municipios?orderBy=nome');
            if (!resposta.ok) throw new Error('IBGE indisponível');
            const nomes = (await resposta.json()).map(c => c.nome);
            cache[uf] = nomes;
            if (selectEstado.value !== uf) return;
            aplicar(nomes);
        } catch (e) {
            aplicar([]);
        }
    }

    selectEstado.addEventListener('change', function () {
        inputCidade.value = '';
        carregar(selectEstado.value);
    });

    carregar(selectEstado.value);

    return {
        // devolve o nome oficial da cidade, ou null se não estiver na lista
        validar: function () {
            const digitada = inputCidade.value.trim();
            if (!digitada) return null;
            if (!cidades.length) return digitada; // IBGE fora do ar: aceita texto livre
            const achada = cidades.find(nome => semAcento(nome) === semAcento(digitada));
            if (achada) inputCidade.value = achada;
            return achada || null;
        }
    };
}

const form = document.getElementById("form-cadastro-representante");

const cidadesIBGE = configurarCidadesIBGE(
    document.getElementById("estado"),
    document.getElementById("cidade"),
    document.getElementById("lista-cidades")
);

form.addEventListener("submit", async function (event) {
    event.preventDefault();

    if (!cidadesIBGE.validar()) {
    let aviso = document.getElementById("respostaProcesso");
    aviso.textContent = "Escolha uma cidade da lista sugerida.";
    aviso.classList.remove("alert-success");
    aviso.classList.add("alert", "alert-warning");
    return;
}
    
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
        window.location.href = new URL('../../../login', document.querySelector('script[src*="cadastro-representante.js"]').src).href; 
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

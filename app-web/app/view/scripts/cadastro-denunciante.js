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

const DEBUG = true;

// formulario de cadastro
const form = document.getElementById("form-cadastro-denunciante");

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
        paragrafo.textContent = resultado.mensagem.replace(/^Cliente:\s*/, "");

        if (resultado.resposta){
            paragrafo.classList.add("alert", "alert-success");
            // vai para o login depois de 1,5 segundo
            setTimeout(function () {
                window.location.href = "../login";
            }, 1500);
        } else {
            paragrafo.classList.add("alert", "alert-warning");
        }

    } catch (error) {
        console.error('Erro ao enviar', error);
    }
})
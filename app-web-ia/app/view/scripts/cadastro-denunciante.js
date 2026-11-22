function configurarCidadesIBGE(selectEstado, inputCidade, listaCidades) {
    let cidades = [];
    const cache = {};
    let sequenciaCarregamento = 0;

    function semAcento(texto) {
        return String(texto).normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
    }

    function aplicar(nomes) {
        cidades = nomes;
        listaCidades.replaceChildren(...nomes.map(nome => new Option(nome)));
    }

    async function carregar(uf) {
        const requisicao = ++sequenciaCarregamento;
        if (!uf) return aplicar([]);
        if (cache[uf]) return aplicar(cache[uf]);
        try {
            const resposta = await fetch('https://servicodados.ibge.gov.br/api/v1/localidades/estados/' + encodeURIComponent(uf) + '/municipios?orderBy=nome', {
                credentials: 'omit'
            });
            if (!resposta.ok) throw new Error('IBGE indisponível');
            const dados = await resposta.json();
            if (!Array.isArray(dados) || !dados.every(c => c && typeof c.nome === 'string')) {
                throw new Error('Resposta inválida do IBGE');
            }
            const nomes = dados.map(c => c.nome);
            cache[uf] = nomes;
            if (requisicao !== sequenciaCarregamento || selectEstado.value !== uf) return;
            aplicar(nomes);
        } catch (e) {
            if (requisicao === sequenciaCarregamento && selectEstado.value === uf) aplicar([]);
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
            credentials: 'same-origin',
            body: dadosForm
        });
        let resultado;
        try {
            resultado = await resposta.json();
        } catch (erro) {
            throw new Error(`O servidor respondeu com HTTP ${resposta.status}, mas retornou uma resposta inválida.`);
        }
        if (!resposta.ok) {
            throw new Error(`HTTP ${resposta.status}: ${resultado.mensagem || 'Falha ao enviar o cadastro.'}`);
        }

        let paragrafo = document.getElementById("respostaProcesso");
        paragrafo.textContent = String(resultado.mensagem || "Não foi possível concluir o cadastro.").replace(/^Cliente:\s*/, "");

        if (resultado.resposta){
            paragrafo.classList.add("alert", "alert-success");
            // vai para o login depois de 1,5 segundo
            setTimeout(function () {
                window.location.href = "../login";
            }, 1500);
        } else {
            console.error('O servidor recusou o cadastro.', {
                codigo: resultado.dados && resultado.dados.codigo,
                mensagem: resultado.mensagem
            });
            paragrafo.classList.add("alert", "alert-warning");
        }

    } catch (error) {
        console.error('Falha ao enviar cadastro:', error.name, error.message);
        const paragrafo = document.getElementById("respostaProcesso");
        paragrafo.textContent = error.message || "Não foi possível enviar o cadastro. Tente novamente.";
        paragrafo.classList.add("alert", "alert-warning");
    }
})
// formulario do representante

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

const form = document.getElementById("form-cadastro-representante");

const cidadesIBGE = configurarCidadesIBGE(
    document.getElementById("estado"),
    document.getElementById("cidade"),
    document.getElementById("lista-cidades")
);

form.addEventListener("submit", async function (event) {
    event.preventDefault();

    if (!campoArea.value) {
        const avisoArea = document.getElementById("respostaProcesso");
        avisoArea.textContent = "Escolha pelo menos uma área de atuação.";
        avisoArea.classList.remove("alert-success");
        avisoArea.classList.add("alert", "alert-warning");
        areaChips.scrollIntoView({ behavior: "smooth", block: "center" });
        return;
    }

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
    ['token', 'perfil', 'nome', 'sobrenome', 'email', 'cidade', 'estado', 'orgao', 'cargo']
        .forEach(chave => localStorage.removeItem(chave));
    setTimeout(function () {
        window.location.href = new URL('../../../login', document.querySelector('script[src*="cadastro-representante.js"]').src).href; 
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

// Área de atuação: usa as categorias de denúncia cadastradas no banco
const areaChips = document.getElementById("area-chips");
const campoArea = document.getElementById("area"); // input hidden que vai para o PHP

// guarda no input hidden os nomes marcados, separados por vírgula
function atualizarAreasSelecionadas() {
    const marcadas = [...areaChips.querySelectorAll('.area-chip[aria-pressed="true"]')]
        .map(chip => chip.dataset.valor);
    campoArea.value = marcadas.join(",");
}

function criarChip(nome) {
    const chip = document.createElement("button");
    chip.type = "button";
    chip.className = "area-chip";
    chip.dataset.valor = nome;
    chip.setAttribute("aria-pressed", "false");

    const marca = document.createElement("span");   // "+" desligado, "✓" ligado (via CSS)
    marca.className = "area-chip-marca";
    marca.setAttribute("aria-hidden", "true");

    const texto = document.createElement("span");
    texto.textContent = nome;

    chip.append(marca, texto);

    chip.addEventListener("click", function () {
        const ligado = chip.getAttribute("aria-pressed") === "true";
        chip.setAttribute("aria-pressed", ligado ? "false" : "true");
        atualizarAreasSelecionadas();
    });

    return chip;
}

// usa as categorias de denúncia cadastradas no banco
async function carregarAreas() {
    try {
        const resposta = await fetch("../categoria/categorias", {
            method: 'GET',
            credentials: 'same-origin'
        });
        if (!resposta.ok) throw new Error('Falha ao carregar áreas');
        const json = await resposta.json();
        const categorias = Array.isArray(json.dados) ? json.dados : [];

        if (!json.resposta || categorias.length === 0) {
            areaChips.textContent = "Nenhuma área disponível.";
            return;
        }

        areaChips.replaceChildren(...categorias.map(c => criarChip(c.nome_categoria)));
    } catch (erro) {
        console.error('Falha ao carregar categorias do cadastro:', erro.name, erro.message);
        areaChips.textContent = "Erro ao carregar áreas. Recarregue a página.";
    }
}

carregarAreas();

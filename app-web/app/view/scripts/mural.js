const links = document.querySelectorAll(".menu-lateral-link");
const telas = document.querySelectorAll(".conteudo-principal .tela");
const botaoCriar = document.querySelector(".menu-lateral-btn-criar");

function mostrarPagina(pagina) {
    const telaSelecionada = document.querySelector("#tela-" + pagina);

    // Se a tela não existe, não mexe em nada
    if (!telaSelecionada) return;

    telas.forEach(function (tela) {
        tela.classList.remove("ativa");
    });
    telaSelecionada.classList.add("ativa");

    links.forEach(function (link) {
        link.classList.remove("ativo");
    });

    const linkSelecionado = document.querySelector(
        '.menu-lateral-link[data-pagina="' + pagina + '"]'
    );
    if (linkSelecionado) {
        linkSelecionado.classList.add("ativo");
    }
}

const paginas = [
    "mural",
    "denuncias",
    "acompanhamento",
    "favoritos",
    "historico",
    "configuracoes"
];

links.forEach(function (link, indice) {
    link.dataset.pagina = paginas[indice];

    link.addEventListener("click", function (event) {
        event.preventDefault();
        mostrarPagina(link.dataset.pagina);
    });
});

botaoCriar.addEventListener("click", function () {
    mostrarPagina("nova-denuncia");
});

mostrarPagina("mural");


/*FILTROS PARA MINHAS DENUNCIAS*/
const chips = document.querySelectorAll(".filtro-chip");
const itensDenuncia = document.querySelectorAll(".denuncia-item");

chips.forEach(function (chip) {
    chip.addEventListener("click", function () {
        chips.forEach(function (c) { c.classList.remove("ativo"); });
        chip.classList.add("ativo");

        const filtro = chip.dataset.filtro;

        itensDenuncia.forEach(function (item) {
            const mostrar = filtro === "todas" || item.dataset.status === filtro;
            item.style.display = mostrar ? "" : "none";
        });
    });
});
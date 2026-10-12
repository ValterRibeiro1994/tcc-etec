const links = document.querySelectorAll(".menu-lateral-link");
const telas = document.querySelectorAll(".conteudo-principal .tela");
const botaoCriar = document.querySelector(".menu-lateral-btn-criar");

function mostrarPagina(pagina) {
    if (pagina == "criar-denuncia")
    {
        const propriedadesTelinha = "width=500,height=400,left=200,top=100,resizable=yes,scrollbars=yes,toolbar=no,location=no,status=no,menubar=no";
        const popup = window.open('criardenuncia', "popupWindow", propriedadesTelinha);

                // Verifica se o popup foi bloqueado pelo navegador
                if (!popup || popup.closed || typeof popup.closed === 'undefined') {
                    alert("O popup foi bloqueado pelo navegador. Habilite popups para este site.");
                }
    }
     
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
    "configuracoes",
    "perfil"
];

links.forEach(function (link, indice) {
    link.dataset.pagina = paginas[indice];
    
    link.addEventListener("click", function (event) {
        event.preventDefault();
        mostrarPagina(link.dataset.pagina);
    });
});

botaoCriar.addEventListener("click", function () {
    mostrarPagina("criar-denuncia");
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

/* ==========================================================
   MURAL - RelateSBC
   Busca as denúncias no servidor (./denuncia/listar) e cria
   um card na tela para cada uma, usando o card modelo que já
   existe no home-mural.html (#card-mural).
   ========================================================== */
 
(function () {
    'use strict';
 
    // Lido agora (durante a execução do script) para montar a URL certa
    // em qualquer página, não importa em que pasta ela esteja.
    const SRC_DO_SCRIPT = document.currentScript ? document.currentScript.src : '';
 
    // Aparência do status (classes do Bootstrap já usadas no projeto)
    const STATUS = {
        'pendente':     { texto: 'Pendente',     classe: 'text-bg-danger'  },
        'em analise':   { texto: 'Em análise',   classe: 'text-bg-primary' },
        'em andamento': { texto: 'Em andamento', classe: 'text-bg-warning' },
        'concluida':    { texto: 'Concluída',    classe: 'text-bg-success' }
    };
 
    // Raiz do projeto: app/view/scripts/mural.js -> ../../../
    function urlApp(rota) {
        if (SRC_DO_SCRIPT) return new URL('../../../' + rota, SRC_DO_SCRIPT).href;
        return './' + rota;
    }
 
    function mostrarMensagem(texto) {
        const area = document.getElementById('mural-mensagem');
        if (!area) return;
        area.textContent = texto;
        area.style.display = texto ? 'block' : 'none';
    }
 
    // Preenche um card (clone do modelo) com os dados de uma denúncia
    function montarCard(modelo, denuncia) {
        const card = modelo.cloneNode(true);
 
        // Guarda as referências aos elementos pelos ids do modelo e, em seguida,
        // remove os ids do clone para não haver ids repetidos na página.
        const img       = card.querySelector('#img-card');
        const categoria = card.querySelector('#categoria-denuncia');
        const titulo    = card.querySelector('#titulo-denuncia');
        const local     = card.querySelector('#local-denuncia');
        const data      = card.querySelector('#data-denuncia');
        const status    = card.querySelector('#status-denuncia');
 
        card.removeAttribute('id');
        card.querySelectorAll('[id]').forEach(function (el) { el.removeAttribute('id'); });
 
        card.dataset.idDenuncia = denuncia.id;
        card.style.display = '';
 
        // textContent (e não innerHTML) evita injeção de HTML vindo do banco
        titulo.textContent    = denuncia.titulo;
        categoria.textContent = denuncia.categoria;
        local.textContent     = denuncia.local;
        data.textContent      = denuncia.data;
 
        if (denuncia.imagem) {
            img.style.backgroundImage = 'url("' + denuncia.imagem + '")';
            img.textContent = '';
        } else {
            img.style.backgroundImage = 'none';
            img.textContent = 'Sem imagem';
            img.classList.add('d-flex', 'align-items-center', 'justify-content-center', 'text-secondary');
        }
 
        const info = STATUS[denuncia.status] || { texto: denuncia.status, classe: 'text-bg-secondary' };
        status.innerHTML = ''; // remove o badge de exemplo
        const badge = document.createElement('span');
        badge.className = 'badge rounded-pill ' + info.classe;
        badge.textContent = info.texto;
        status.appendChild(badge);
 
        return card;
    }
 
    async function carregarMural() {
        const grade = document.getElementById('grid-denuncias');
        const modelo = document.getElementById('card-mural');
        if (!grade || !modelo) return;
 
        // O card de exemplo vira só um modelo: sai da tela e é copiado para cada denúncia.
        const copiaModelo = modelo.cloneNode(true);
        modelo.remove();
 
        mostrarMensagem('Carregando denúncias...');
 
        try {
            const resposta = await fetch(urlApp('denuncia/listar'), {
                method: 'GET',
                credentials: 'same-origin'
            });
 
            const json = await resposta.json();
 
            if (!json.resposta) {
                console.error('Erro ao listar denúncias:', json);
                mostrarMensagem('Não foi possível carregar as denúncias agora. Tente novamente mais tarde.');
                return;
            }
 
            const denuncias = json.dados || [];
            if (denuncias.length === 0) {
                mostrarMensagem('Nenhuma denúncia registrada ainda.');
                return;
            }
 
            mostrarMensagem('');
 
            const fragmento = document.createDocumentFragment();
            denuncias.forEach(function (denuncia) {
                fragmento.appendChild(montarCard(copiaModelo, denuncia));
            });
            grade.appendChild(fragmento);
        } catch (erro) {
            console.error('Erro de rede ao carregar o mural:', erro);
            mostrarMensagem('Erro de conexão ao carregar as denúncias.');
        }
    }
 
    document.addEventListener('DOMContentLoaded', carregarMural);
})();
/* 
   MURAL - RelateSBC  (home-mural.html)
   1) Menu lateral: troca de telas, "Nova denúncia" e link "perfil"
   2) Mural: lista TODAS as denúncias (./denuncia/listar), com o nome de quem denunciou
   3) Minhas denúncias: lista só as do usuário logado (./denuncia/minhas)
*/

(function () {
    'use strict';

    // Lido agora (durante a execução do script) para montar as URLs corretamente
    const SRC_DO_SCRIPT = document.currentScript ? document.currentScript.src : '';

    // Raiz do projeto: app/view/scripts/mural.js -> ../../../
    function urlApp(rota) {
        if (SRC_DO_SCRIPT) return new URL('../../../' + rota, SRC_DO_SCRIPT).href;
        return './' + rota;
    }

    // Texto/cor de cada status do banco (status_denuncia) e o valor usado nos filtros
    const STATUS = {
        'pendente':     { texto: 'Pendente',     classe: 'text-bg-danger',  filtro: 'pendente'  },
        'em analise':   { texto: 'Em análise',   classe: 'text-bg-primary', filtro: 'analise'   },
        'em andamento': { texto: 'Em andamento', classe: 'text-bg-warning', filtro: 'andamento' },
        'concluida':    { texto: 'Concluída',    classe: 'text-bg-success', filtro: 'concluida' }
    };

        // ADICIONADO: tira acentos e maiúsculas, para a busca achar "iluminacao" em "Iluminação"
    function normalizarTexto(texto) {
        return String(texto || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
    }

    // ADICIONADO: o PHP monta o local como "Rua, número, complemento - Bairro"; o bairro é o que vem depois do último " - "
    function bairroDoLocal(local) {
        const posicao = (local || '').lastIndexOf(' - ');
        return posicao === -1 ? '' : local.slice(posicao + 3).trim();
    }

    // Ordem dos links do menu lateral (usada só se o link não tiver data-pagina no HTML)
    const PAGINAS = ['mural', 'denuncias', 'acompanhamento', 'favoritos', 'historico', 'configuracoes', 'perfil'];

    /* ---------- Sessão ---------- */

    // Devolve o token só se for de um usuário LOGADO (não visitante) e ainda válido.
    // Lê o payload do JWT; quem valida de verdade é o servidor.
    function lerToken() {
        const token = localStorage.getItem('token');
        if (!token) return null;

        try {
            const base64 = token.split('.')[1].replace(/-/g, '+').replace(/_/g, '/');
            const payload = JSON.parse(window.atob(base64));
            if (payload.exp && payload.exp * 1000 < Date.now()) return null; // expirado
            if (!payload.perfil || payload.perfil === 'visitante') return null;
            return token;
        } catch (erro) {
            return null;
        }
    }

    // Busca um JSON no servidor (com o token, quando a rota exige login)
    async function buscarJson(rota, token) {
        const opcoes = { method: 'GET', credentials: 'same-origin' };
        if (token) opcoes.headers = { 'Authorization': token };

        const resposta = await fetch(urlApp(rota), opcoes);
        const texto = await resposta.text();
        try {
            return JSON.parse(texto);
        } catch (erro) {
            console.error('Resposta do servidor (não é JSON):', texto);
            throw new Error('Resposta inválida do servidor');
        }
    }

    /* ---------- 1) Menu lateral ---------- */

    const links = document.querySelectorAll('.menu-lateral-link');
    const telas = document.querySelectorAll('.conteudo-principal .tela');
    const botaoCriar = document.querySelector('.menu-lateral-btn-criar');

    function irParaLogin(mensagem) {
        if (mensagem) alert(mensagem);
        window.location.href = urlApp('login');
    }

    // Abre o formulário de denúncia em popup, só para usuário logado
    function abrirFormularioDenuncia() {
        if (!lerToken()) {
            irParaLogin('Faça login para registrar uma denúncia.');
            return;
        }

        abrirModalDenuncia(urlApp('denuncia/criar'));
    }

    // Expansão: navega para a denúncia com estado do mural preservado.
    let paginaMural = 1;

    function irParaDetalhe(id) {
        const area = document.querySelector('.conteudo-principal');
        const ativa = document.querySelector('.conteudo-principal .tela.ativa');
        const estado = {
            pagina: ativa?.id.replace('tela-', '') || 'mural',
            paginaMural,
            rolagem: area?.scrollTop || 0,
            busca: campoBusca?.value || '',
            categoria: selCategoria?.value || '',
            bairro: selBairro?.value || '',
            status: selStatusMural?.value || '',
            ...RelateMinhas.estado(),
            filtrosExtras: !!(painelExtras && !painelExtras.classList.contains('d-none'))
        };
        try { sessionStorage.setItem('mural-retorno', JSON.stringify(estado)); }
        catch (erro) { console.warn('Não foi possível salvar retorno do mural:', erro); }
        window.location.href = urlApp('denuncia/ver?id=' + encodeURIComponent(id));
    }

    async function enviarPost(rota, token, dados) {
        const corpo = new FormData();
        corpo.append('metodo', 'POST');
        Object.entries(dados).forEach(([chave, valor]) => corpo.append(chave, valor));
        const resposta = await fetch(urlApp(rota), {
            method: 'POST', credentials: 'same-origin', headers: { Authorization: token }, body: corpo
        });
        const texto = await resposta.text();
        try { return JSON.parse(texto); }
        catch (erro) { console.error('Resposta inválida:', texto); throw new Error('Resposta inválida do servidor'); }
    }

    // ALTERADO: o link "perfil" leva para a página de perfil, e só se estiver logado
    function irParaPerfil() {
        if (!lerToken()) {
            irParaLogin('Faça login para acessar o seu perfil.');
            return;
        }
        window.location.href = urlApp('user/perfil');
    }

    function mostrarPagina(pagina) {
        if (pagina === 'criar-denuncia') { abrirFormularioDenuncia(); return; }
        if (pagina === 'perfil') { irParaPerfil(); return; }

        const telaSelecionada = document.querySelector('#tela-' + pagina);

        // Se a tela não existe, não mexe em nada
        if (!telaSelecionada) return;

        telas.forEach(function (tela) { tela.classList.remove('ativa'); });
        telaSelecionada.classList.add('ativa');

        links.forEach(function (link) { link.classList.remove('ativo'); });
        const linkSelecionado = document.querySelector('.menu-lateral-link[data-pagina="' + pagina + '"]');
        if (linkSelecionado) linkSelecionado.classList.add('ativo');
    }

    links.forEach(function (link, indice) {
        if (!link.dataset.pagina) link.dataset.pagina = PAGINAS[indice];

        link.addEventListener('click', function (evento) {
            evento.preventDefault();
            mostrarPagina(link.dataset.pagina);
        });
    });

    if (botaoCriar) {
        botaoCriar.addEventListener('click', function () {
            mostrarPagina('criar-denuncia');
        });
    }

        if (botaoCriar) {
        botaoCriar.addEventListener('click', function () {
            mostrarPagina('criar-denuncia');
        });
    }

    // ADICIONADO: abre o modal sozinho quando a URL for denuncia/mural?nova=1.
    // É para onde vão os links "Registrar denúncia" (rodapé, perfil) e quem abre denuncia/criar direto.
    const parametrosUrl = new URLSearchParams(window.location.search);
    if (parametrosUrl.get('nova') === '1') {
        parametrosUrl.delete('nova');
        const resto = parametrosUrl.toString();
        // limpa o ?nova=1 da barra de endereço (senão o modal reabriria ao dar F5)
        history.replaceState(null, '', window.location.pathname + (resto ? '?' + resto : '') + window.location.hash);
        abrirFormularioDenuncia();
    }

    /* ---------- 2) Mural: todas as denúncias ---------- */

    function mostrarMensagemMural(texto) {
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
        const autor     = card.querySelector('#autor-denuncia');
        const local     = card.querySelector('#local-denuncia');
        const data      = card.querySelector('#data-denuncia');
        const status    = card.querySelector('#status-denuncia');

        card.removeAttribute('id');
        card.querySelectorAll('[id]').forEach(function (el) { el.removeAttribute('id'); });

        card.dataset.idDenuncia = denuncia.id;
        card.style.display = '';
        card.tabIndex = 0;
        card.setAttribute('role', 'link');
        card.setAttribute('aria-label', 'Abrir denúncia: ' + denuncia.titulo);

        // textContent (e não innerHTML) evita injeção de HTML vindo do banco
        titulo.textContent    = denuncia.titulo;
        categoria.textContent = denuncia.categoria;
        local.textContent     = denuncia.local;
        data.textContent      = denuncia.data;

        // ADICIONADO: nome de quem fez a denúncia (vem do INNER JOIN com tb_usuario)
        if (autor) autor.textContent = denuncia.autor || 'Anônimo';

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
        card.dataset.categoria = denuncia.categoria || '';
        card.dataset.bairro    = denuncia.bairro || bairroDoLocal(denuncia.local);
        card.dataset.status    = info.filtro || '';
        card.dataset.busca     = normalizarTexto([denuncia.titulo, denuncia.categoria, denuncia.local, denuncia.autor].join(' '));
        return card;
    }

    // Cards funcionam com mouse e teclado (Enter/Espaço).
    const gradeMural = document.getElementById('grid-denuncias');
    if (gradeMural) {
        function abrirCard(evento) {
            const card = evento.target.closest('.card[data-id-denuncia]');
            if (!card || !gradeMural.contains(card)) return;
            irParaDetalhe(card.dataset.idDenuncia);
        }
        gradeMural.addEventListener('click', abrirCard);
        gradeMural.addEventListener('keydown', evento => {
            if (evento.key === 'Enter' || evento.key === ' ') {
                evento.preventDefault();
                abrirCard(evento);
            }
        });
    }

    let modeloCardMural = null;
    let sequenciaMural = 0;
    async function carregarMural(pagina = 1) {
        const grade = document.getElementById('grid-denuncias');
        if (!grade) return;
        if (!modeloCardMural) {
            const original = document.getElementById('card-mural');
            if (!original) return;
            modeloCardMural = original.cloneNode(true);
            original.remove();
        }
        const requisicao = ++sequenciaMural;
        mostrarMensagemMural('Carregando denúncias...');
        const parametros = new URLSearchParams({
            pagina: String(pagina), busca: campoBusca?.value || '',
            categoria: selCategoria?.value || '', bairro: selBairro?.value || '',
            status: selStatusMural?.value || ''
        });
        try {
            const json = await buscarJson('denuncia/listar?' + parametros);
            if (requisicao !== sequenciaMural) return;
            if (!json.resposta) throw new Error(json.mensagem || 'Falha ao consultar denúncias');
            paginaMural = json.paginacao.pagina;
            const denuncias = json.dados || [];
            preencherOpcoes(selCategoria, json.opcoes?.categorias || []);
            preencherOpcoes(selBairro, json.opcoes?.bairros || []);
            grade.replaceChildren();
            const fragmento = document.createDocumentFragment();
            denuncias.forEach(d => fragmento.appendChild(montarCard(modeloCardMural, d)));
            grade.appendChild(fragmento);
            mostrarMensagemMural(denuncias.length ? '' : 'Nenhuma denúncia encontrada.');
            RelatePaginacao.mostrar('paginacao-mural', json.paginacao,
                novaPagina => carregarMural(novaPagina).then(() => {
                    document.querySelector('.conteudo-principal')?.scrollTo({top:0,behavior:'smooth'});
                })
            );
        } catch (erro) {
            if (requisicao !== sequenciaMural) return;
            console.error('Erro ao carregar mural:', erro);
            mostrarMensagemMural('Não foi possível carregar as denúncias.');
        }
    }

    /* ---------- 3) Minhas denúncias: só as do usuário logado ---------- */

        /* ---------- ADICIONADO: filtros do mural ---------- */

    const campoBusca     = document.getElementById('busca-mural');
    const selCategoria   = document.getElementById('filtro-categoria');
    const selBairro      = document.getElementById('filtro-bairro');
    const selStatusMural = document.getElementById('filtro-status-mural');
    const btnMaisFiltros = document.getElementById('btn-mais-filtros');
    const painelExtras   = document.getElementById('filtros-extras');
    const btnLimpar      = document.getElementById('btn-limpar-filtros');

    // Filtros do mural são enviados ao banco; seleção preservada ao repopular.
    function preencherOpcoes(select, valores) {
        if (!select) return;
        const anterior = select.value;
        while (select.options.length > 1) select.remove(1);
        [...new Set(valores.filter(Boolean))].sort((a,b) => a.localeCompare(b,'pt-BR'))
            .forEach(valor => select.add(new Option(valor,valor)));
        if (anterior && ![...select.options].some(o => o.value === anterior))
            select.add(new Option(anterior, anterior));
        select.value = anterior;
    }
    function aplicarFiltrosMural() { return carregarMural(1); }
    let atrasoBusca;
    if (campoBusca) campoBusca.addEventListener('input', () => {
        clearTimeout(atrasoBusca);
        atrasoBusca = setTimeout(aplicarFiltrosMural, 260);
    });
    [selCategoria,selBairro,selStatusMural].forEach(select => {
        if (select) select.addEventListener('change', aplicarFiltrosMural);
    });
    if (btnMaisFiltros && painelExtras) btnMaisFiltros.addEventListener('click', () => {
        painelExtras.classList.toggle('d-none');
    });
    if (btnLimpar) btnLimpar.addEventListener('click', () => {
        if (campoBusca) campoBusca.value = '';
        [selCategoria,selBairro,selStatusMural].forEach(select => { if (select) select.value=''; });
        aplicarFiltrosMural();
    });

        window.RelateMural = {
        urlApp, lerToken, buscarJson, enviarPost, irParaLogin, irParaDetalhe, STATUS,
        recarregarMural: () => carregarMural(paginaMural)
    };

    /* ---------- Início ---------- */
    
    document.addEventListener('DOMContentLoaded', function () {
        const logado = !!lerToken();
        document.querySelectorAll('#btn-entrar, #btn-cadastro').forEach(
            botao => botao.style.display = logado ? 'none' : '');
        document.querySelectorAll('#usuario-logado').forEach(
            bloco => bloco.style.display = logado ? '' : 'none');
        let retorno=null;
        try {
            retorno=JSON.parse(sessionStorage.getItem('mural-retorno')||'null');
            sessionStorage.removeItem('mural-retorno');
        }catch(erro){console.warn('Estado de navegação inválido:',erro);}
        if(retorno){
            if(campoBusca)campoBusca.value=retorno.busca||'';
            function selecionar(select,valor){
                if(!select||!valor)return;
                if(![...select.options].some(o=>o.value===valor))select.add(new Option(valor,valor));
                select.value=valor;
            }
            selecionar(selCategoria,retorno.categoria);
            selecionar(selBairro,retorno.bairro);
            selecionar(selStatusMural,retorno.status);
           
            if(painelExtras)painelExtras.classList.toggle('d-none',!retorno.filtrosExtras);
        }
        mostrarPagina(retorno?.pagina || 'mural');
        Promise.all([carregarMural(retorno?.paginaMural||1),RelateMinhas.iniciar(retorno)])
            .then(()=>{
                if(!retorno)return;
                const area=document.querySelector('.conteudo-principal');
                requestAnimationFrame(()=>{ if(area)area.scrollTop=Number(retorno.rolagem)||0; });
            });
    });
})();

document.querySelectorAll('[data-ir-para]').forEach(function (el) {
    el.addEventListener('click', function (e) {
        e.preventDefault();
        var alvo = document.getElementById('link-' + el.dataset.irPara);
        if (alvo) {
            alvo.click();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    });
});
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

    // ADICIONADO: "Minhas denúncias" recebe a data como 2026-10-20 14:30:00; o mural já recebe 20/10/2026
    function formatarDataBr(valor) {
        const partes = /^(\d{4})-(\d{2})-(\d{2})/.exec(valor || '');
        return partes ? partes[3] + '/' + partes[2] + '/' + partes[1] : (valor || '');
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
    /* ---------- Modal do formulário de denúncia ---------- */
let modalDenuncia = null;
let focoAnteriorModal = null;

function teclaModalDenuncia(evento) {
    if (evento.key === 'Escape') fecharModalDenuncia();
}

function fecharModalDenuncia() {
    if (!modalDenuncia) return;

    const modal = modalDenuncia;
    modalDenuncia = null;

    modal.classList.remove('aberto');
    document.body.classList.remove('modal-denuncia-aberto');
    document.removeEventListener('keydown', teclaModalDenuncia);
    setTimeout(function () { modal.remove(); }, 200);

    if (focoAnteriorModal && focoAnteriorModal.focus) focoAnteriorModal.focus();
}

function abrirModalDenuncia(url) {
    if (modalDenuncia) return;
    focoAnteriorModal = document.activeElement;

    const fundo = document.createElement('div');
    fundo.className = 'modal-denuncia';
    fundo.setAttribute('role', 'dialog');
    fundo.setAttribute('aria-modal', 'true');
    fundo.setAttribute('aria-label', 'Registrar uma denúncia');

    const card = document.createElement('div');
    card.className = 'modal-denuncia-card';

    const botaoFechar = document.createElement('button');
    botaoFechar.type = 'button';
    botaoFechar.className = 'modal-denuncia-fechar';
    botaoFechar.setAttribute('aria-label', 'Fechar');
    botaoFechar.textContent = '\u00D7';
    botaoFechar.addEventListener('click', fecharModalDenuncia);

    const quadro = document.createElement('iframe');
    quadro.className = 'modal-denuncia-frame';
    quadro.title = 'Formulário de denúncia';
    quadro.addEventListener('load', function () { card.classList.add('pronto'); });
    quadro.src = url;

    card.append(botaoFechar, quadro);
    fundo.appendChild(card);
    document.body.appendChild(fundo);
    document.body.classList.add('modal-denuncia-aberto');
    document.addEventListener('keydown', teclaModalDenuncia);

    requestAnimationFrame(function () { fundo.classList.add('aberto'); });
    botaoFechar.focus();

    modalDenuncia = fundo;
}

// Respostas do formulário (iframe): denúncia criada ou cancelada
window.addEventListener('message', function (evento) {
    if (evento.origin !== window.location.origin || !evento.data) return;

    if (evento.data.tipo === 'denuncia:fechar') {
        fecharModalDenuncia();
    } else if (evento.data.tipo === 'denuncia:criada') {
        fecharModalDenuncia();
        window.location.reload(); // atualiza o mural com a nova denúncia
    }
});

    // Expansão: navega para a denúncia com estado do mural preservado.
    let paginaMural = 1;
    let paginaMinhas = 1;
    function irParaDetalhe(id) {
        const area = document.querySelector('.conteudo-principal');
        const ativa = document.querySelector('.conteudo-principal .tela.ativa');
        const estado = {
            pagina: ativa?.id.replace('tela-', '') || 'mural',
            paginaMural, paginaMinhas,
            rolagem: area?.scrollTop || 0,
            busca: campoBusca?.value || '',
            categoria: selCategoria?.value || '',
            bairro: selBairro?.value || '',
            status: selStatusMural?.value || '',
            filtroMinhas: filtroAtual,
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

    let filtroAtual = 'todas';

    function mostrarMensagemMinhas(texto, comLinkLogin) {
        const area = document.getElementById('minhas-mensagem');
        if (!area) return;

        area.textContent = texto;
        if (texto && comLinkLogin) {
            const link = document.createElement('a');
            link.href = urlApp('login');
            link.textContent = 'Entrar';
            area.appendChild(document.createTextNode(' '));
            area.appendChild(link);
        }
        area.style.display = texto ? 'block' : 'none';
    }

    function definirTexto(id, valor) {
        const elemento = document.getElementById(id);
        if (elemento) elemento.textContent = valor;
    }

    // Contagem é feita no banco (COUNT/GROUP BY), nunca apenas com as 10 linhas.
    function atualizarResumo(resumo = {}) {
        definirTexto('resumo-concluidas', resumo.concluida ?? 0);
        definirTexto('resumo-andamento', resumo.andamento ?? 0);
        definirTexto('resumo-analise', resumo.analise ?? 0);
        definirTexto('resumo-pendentes', resumo.pendente ?? 0);
        definirTexto('total-minhas', resumo.total ?? 0);
    }

    function resumirTexto(texto, maximo) {
        texto = (texto || '').replace(/\s+/g, ' ').trim();
        return texto.length > maximo ? texto.slice(0, maximo - 1) + '…' : texto;
    }

    function montarItemMinhaDenuncia(modelo, denuncia) {
        const item = modelo.content.firstElementChild.cloneNode(true);
        const info = STATUS[denuncia.status] || { texto: denuncia.status, filtro: '' };

        function campo(nome) { return item.querySelector('[data-campo="' + nome + '"]'); }

        item.dataset.status = info.filtro; // a cor da borda/etiqueta vem do CSS por esse atributo
        item.dataset.idDenuncia = denuncia.id;
        item.dataset.descricaoCompleta = denuncia.descricao || '';
        item.tabIndex = 0;
        item.setAttribute('role','link');
        if (denuncia.removivel === false) {
            const remover = item.querySelector('[data-acao="remover"]');
            if (remover) { remover.disabled = true; remover.title = 'Denúncia concluída não pode ser removida'; }
        }

        campo('titulo').textContent    = denuncia.titulo;
        campo('descricao').textContent = resumirTexto(denuncia.descricao, 110);
        campo('local').textContent     = denuncia.local;
        campo('data').textContent      = formatarDataBr(denuncia.data);
        campo('status').textContent    = info.texto;

        return item;
    }

    // Filtros e ações: a consulta abrange todas as denúncias do usuário.
    function aplicarFiltro() { return carregarMinhasDenuncias(1); }
    document.querySelectorAll('.filtro-chip').forEach(chip => {
        chip.addEventListener('click', () => {
            document.querySelectorAll('.filtro-chip').forEach(c => c.classList.toggle('ativo',c===chip));
            filtroAtual = chip.dataset.filtro;
            aplicarFiltro();
        });
    });
    const listaMinhas = document.getElementById('lista-minhas-denuncias');
    if (listaMinhas) {
        listaMinhas.addEventListener('click', evento => {
            const botao = evento.target.closest('[data-acao]');
            const item = evento.target.closest('.denuncia-item[data-id-denuncia]');
            if (!item) return;
            if (!botao || botao.dataset.acao === 'ver-mais') {
                irParaDetalhe(item.dataset.idDenuncia);
            } else if (botao.dataset.acao === 'editar') {
                window.location.href = urlApp('denuncia/editar?id='+encodeURIComponent(item.dataset.idDenuncia));
            } else if (botao.dataset.acao === 'remover' && !botao.disabled) {
                removerDenuncia(item,botao);
            }
        });
    }
    async function removerDenuncia(item, botao) {
        const token = lerToken();
        if (!token) { irParaLogin('Faça login para remover uma denúncia.'); return; }
        const titulo = item.querySelector('[data-campo="titulo"]')?.textContent || '';
        if (!confirm(`Deseja realmente remover a denúncia "${titulo}"?`)) return;
        botao.disabled = true;
        try {
            const json = await enviarPost('denuncia/remover',token,{'id-denuncia':item.dataset.idDenuncia});
            if (!json.resposta) throw new Error(json.mensagem || 'Não foi possível remover');
            await Promise.all([carregarMinhasDenuncias(paginaMinhas), carregarMural(paginaMural)]);
        } catch (erro) {
            alert(erro.message || 'Erro ao remover denúncia.');
            botao.disabled = false;
        }
    }
    let sequenciaMinhas = 0;
    async function carregarMinhasDenuncias(pagina=1) {
        const lista = document.getElementById('lista-minhas-denuncias');
        const modelo = document.getElementById('modelo-minha-denuncia');
        if (!lista || !modelo) return;
        const token=lerToken();
        if (!token) {
            atualizarResumo(); lista.replaceChildren();
            mostrarMensagemMinhas('Faça login para ver as suas denúncias.',true);
            RelatePaginacao.mostrar('paginacao-minhas',null,()=>{});
            return;
        }
        const requisicao=++sequenciaMinhas;
        mostrarMensagemMinhas('Carregando suas denúncias...');
        const params=new URLSearchParams({pagina:String(pagina), status:filtroAtual==='todas'?'':filtroAtual});
        try {
            const json=await buscarJson('denuncia/minhas?'+params,token);
            if(requisicao!==sequenciaMinhas)return;
            if(!json.resposta)throw new Error(json.mensagem||'Falha ao consultar suas denúncias');
            paginaMinhas=json.paginacao.pagina;
            atualizarResumo(json.resumo);
            lista.replaceChildren();
            const fragmento=document.createDocumentFragment();
            (json.dados||[]).forEach(d => fragmento.appendChild(montarItemMinhaDenuncia(modelo,d)));
            lista.appendChild(fragmento);
            mostrarMensagemMinhas(json.dados.length?'':'Nenhuma denúncia encontrada neste filtro.');
            RelatePaginacao.mostrar('paginacao-minhas',json.paginacao,
                n => carregarMinhasDenuncias(n).then(()=>document.querySelector('.conteudo-principal')?.scrollTo({top:0,behavior:'smooth'})));
        }catch(erro){
            if(requisicao!==sequenciaMinhas)return;
            console.error('Erro ao carregar minhas denúncias:',erro);
            mostrarMensagemMinhas('Não foi possível carregar suas denúncias.');
        }
    }

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
            filtroAtual=retorno.filtroMinhas||'todas';
            document.querySelectorAll('.filtro-chip').forEach(
                chip=>chip.classList.toggle('ativo',chip.dataset.filtro===filtroAtual));
            if(painelExtras)painelExtras.classList.toggle('d-none',!retorno.filtrosExtras);
        }
        mostrarPagina(retorno?.pagina || 'mural');
        Promise.all([carregarMural(retorno?.paginaMural||1),carregarMinhasDenuncias(retorno?.paginaMinhas||1)])
            .then(()=>{
                if(!retorno)return;
                const area=document.querySelector('.conteudo-principal');
                requestAnimationFrame(()=>{ if(area)area.scrollTop=Number(retorno.rolagem)||0; });
            });
    });
})();


// NOTIFICACOES 
(function () {
    // true = notificações de EXEMPLO (sem backend e sem login). Troque para false quando as rotas existirem.
    const USAR_EXEMPLO = true;

    const ROTA_LISTAR = 'notificacao/minhas';     // GET  -> { resposta, dados: [...] }
    const ROTA_LER = 'notificacao/ler';           // POST { id }
    const ROTA_LER_TODAS = 'notificacao/ler-todas'; // POST
    // Cada item de "dados": { id, tipo: resolvida|status|comentario|apoio, titulo, mensagem, data: 'AAAA-MM-DD HH:MM:SS', lida: 0|1 }

    const lista = document.getElementById('lista-notificacoes');
    const vazio = document.getElementById('notificacoes-vazio');
    const vazioTitulo = document.getElementById('notificacoes-vazio-titulo');
    const vazioTexto = document.getElementById('notificacoes-vazio-texto');
    const modelo = document.getElementById('modelo-notificacao');
    const badge = document.getElementById('badge-notificacoes');
    const btnTodas = document.getElementById('btn-marcar-todas');
    const filtros = document.getElementById('filtro-notificacoes');

    const SRC = document.currentScript ? document.currentScript.src : '';
    const urlApp = rota => SRC ? new URL('../../../' + rota, SRC).href : './' + rota;

    const ICONES = {
        resolvida:  '<svg viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
        status:     '<svg viewBox="0 0 24 24"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>',
        comentario: '<svg viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>',
       
    };

    let notificacoes = [];
    let filtro = 'todas';
    let estado = 'ok'; // 'ok' | 'sem-login' | 'erro'

    /* ---------- Sessão e servidor (igual ao mural.js) ---------- */

    function lerToken() {
        const token = localStorage.getItem('token');
        if (!token) return null;
        try {
            const payload = JSON.parse(atob(token.split('.')[1].replace(/-/g, '+').replace(/_/g, '/')));
            if (payload.exp && payload.exp * 1000 < Date.now()) return null;
            return payload.perfil && payload.perfil !== 'visitante' ? token : null;
        } catch (e) {
            return null;
        }
    }

    async function requisitar(rota, metodo, corpo) {
        const opcoes = { method: metodo || 'GET', credentials: 'same-origin', headers: { 'Authorization': lerToken() } };
        if (corpo) {
            opcoes.headers['Content-Type'] = 'application/json';
            opcoes.body = JSON.stringify(corpo);
        }
        const resposta = await fetch(urlApp(rota), opcoes);
        const texto = await resposta.text();
        try {
            return JSON.parse(texto);
        } catch (e) {
            console.error('Resposta do servidor (não é JSON):', texto);
            throw new Error('Resposta inválida do servidor');
        }
    }

    /* ---------- Datas ---------- */

    function grupoDaData(data) {
        const dias = Math.round((new Date().setHours(0, 0, 0, 0) - new Date(data).setHours(0, 0, 0, 0)) / 86400000);
        if (dias < 1) return 'Hoje';
        if (dias <= 7) return 'Esta semana';
        return 'Anteriores';
    }

    function tempoRelativo(data) {
        const min = Math.floor((Date.now() - data) / 60000);
        if (isNaN(min)) return '';
        if (min < 1) return 'agora';
        if (min < 60) return 'há ' + min + ' min';
        if (min < 1440) return 'há ' + Math.floor(min / 60) + ' h';
        if (min < 2880) return 'ontem';
        if (min < 10080) return 'há ' + Math.floor(min / 1440) + ' dias';
        return data.toLocaleDateString('pt-BR');
    }

    /* ---------- Tela ---------- */

    function mostrarVazio(titulo, texto, comLogin) {
        vazio.hidden = false;
        vazioTitulo.textContent = titulo;
        vazioTexto.textContent = texto;
        if (comLogin) {
            const link = document.createElement('a');
            link.href = urlApp('login');
            link.textContent = 'Entrar';
            vazioTexto.append(' ', link);
        }
    }

    function criarItem(n) {
        const item = modelo.content.firstElementChild.cloneNode(true);
        const icone = item.querySelector('[data-campo="icone"]');

        item.classList.toggle('nao-lida', !n.lida);
        icone.dataset.tipo = n.tipo;
        icone.innerHTML = ICONES[n.tipo]; // SVG fixo do código
        item.querySelector('[data-campo="titulo"]').textContent = n.titulo;     // textContent evita HTML vindo do banco
        item.querySelector('[data-campo="mensagem"]').textContent = n.mensagem;
        item.querySelector('[data-campo="tempo"]').textContent = tempoRelativo(n.data);
        item.querySelector('.notificacao-corpo').addEventListener('click', () => marcarLidas([n], ROTA_LER, { id: n.id }));
        return item;
    }

    function mostrar() {
        lista.innerHTML = '';
        vazio.hidden = true;

        const naoLidas = estado === 'ok' ? notificacoes.filter(n => !n.lida).length : 0;
        badge.textContent = naoLidas > 99 ? '99+' : naoLidas;
        badge.hidden = naoLidas === 0;
        btnTodas.disabled = naoLidas === 0;

        if (estado === 'sem-login') return mostrarVazio('Entre para ver suas notificações', 'Faça login para acompanhar o andamento das suas denúncias.', true);
        if (estado === 'erro') return mostrarVazio('Não foi possível carregar', 'Tente novamente em alguns instantes.');

        const visiveis = notificacoes
            .filter(n => filtro === 'todas' || !n.lida)
            .sort((a, b) => b.data - a.data);

        if (visiveis.length === 0) {
            return filtro === 'nao-lidas' && notificacoes.length > 0
                ? mostrarVazio('Tudo em dia', 'Você já leu todas as suas notificações.')
                : mostrarVazio('Nenhuma notificação por aqui', 'Quando uma denúncia mudar de status ou receber um comentário, você verá o aviso aqui.');
        }

        ['Hoje', 'Esta semana', 'Anteriores'].forEach(function (nome) {
            const doGrupo = visiveis.filter(n => grupoDaData(n.data) === nome);
            if (doGrupo.length === 0) return;

            const titulo = document.createElement('h3');
            titulo.className = 'notificacoes-grupo-titulo';
            titulo.textContent = nome;

            const ul = document.createElement('ul');
            ul.className = 'notificacoes-lista';
            ul.append(...doGrupo.map(criarItem));

            const bloco = document.createElement('div');
            bloco.className = 'notificacoes-grupo';
            bloco.append(titulo, ul);
            lista.appendChild(bloco);
        });
    }

    /* ---------- Ações ---------- */

    // Marca como lidas na hora; se o servidor não confirmar, desfaz.
    async function marcarLidas(alvo, rota, corpo) {
        const pendentes = alvo.filter(n => !n.lida);
        if (pendentes.length === 0) return;

        pendentes.forEach(n => { n.lida = true; });
        mostrar();
        if (USAR_EXEMPLO) return;

        try {
            const json = await requisitar(rota, 'POST', corpo);
            if (!json.resposta) throw new Error(json.mensagem || 'Falha ao marcar como lida');
        } catch (erro) {
            console.error('Erro ao marcar notificações como lidas:', erro);
            pendentes.forEach(n => { n.lida = false; });
            mostrar();
        }
    }

    btnTodas.addEventListener('click', () => marcarLidas(notificacoes, ROTA_LER_TODAS));

    filtros.addEventListener('click', function (e) {
        const chip = e.target.closest('.notif-chip');
        if (!chip) return;
        filtro = chip.dataset.filtro;
        filtros.querySelectorAll('.notif-chip').forEach(c => c.classList.toggle('ativo', c === chip));
        mostrar();
    });

    /* ---------- Carregamento ---------- */

    function dadosExemplo() {
        const atras = min => new Date(Date.now() - min * 60000);
        return [
            { id: 1, tipo: 'resolvida',  titulo: 'Denúncia resolvida',  mensagem: 'O buraco na Rua Marechal Deodoro foi consertado.',            data: atras(12),        lida: 0 },
            { id: 2, tipo: 'comentario', titulo: 'Novo comentário',     mensagem: 'A prefeitura respondeu sua denúncia sobre iluminação.',       data: atras(95),        lida: 0 },
            { id: 3, tipo: 'status',     titulo: 'Status atualizado',   mensagem: 'Sua denúncia passou de "Em análise" para "Em andamento".',    data: atras(1800),      lida: 0 },
            { id: 4, tipo: 'status',     titulo: 'Status atualizado',   mensagem: 'Sua denúncia foi recebida e está em análise.',                data: atras(17280),     lida: 1 }
        ];
    }

    // Aceita data como Date (exemplo) ou 'AAAA-MM-DD HH:MM:SS' (MySQL)
    function normalizar(n) {
        return {
            id: n.id,
            tipo: ICONES[n.tipo] ? n.tipo : 'status',
            titulo: n.titulo || '',
            mensagem: n.mensagem || '',
            data: n.data instanceof Date ? n.data : new Date(String(n.data).replace(' ', 'T')),
            lida: Number(n.lida) === 1
        };
    }

    async function carregar() {
        estado = 'ok';
        notificacoes = [];

        if (USAR_EXEMPLO) {
            notificacoes = dadosExemplo().map(normalizar);
        } else if (!lerToken()) {
            estado = 'sem-login';
        } else {
            try {
                const json = await requisitar(ROTA_LISTAR);
                if (!json.resposta) throw new Error(json.mensagem || 'Erro ao listar notificações');
                notificacoes = (json.dados || []).map(normalizar);
            } catch (erro) {
                console.error('Erro ao carregar notificações:', erro);
                estado = 'erro';
            }
        }
        mostrar();
    }

    carregar(); // carrega já no início para o contador do menu aparecer em qualquer tela

    // Ao abrir a aba, busca de novo para pegar o que chegou depois.
    const linkMenu = document.querySelector('.menu-lateral-link[data-pagina="notificacoes"]');
    if (linkMenu && !USAR_EXEMPLO) linkMenu.addEventListener('click', carregar);
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


(function () {
    'use strict';
 
    const { urlApp, lerToken, buscarJson, enviarPost, irParaLogin, irParaDetalhe, STATUS, recarregarMural } = window.RelateMural;
 
    // "Minhas denúncias" recebe a data como 2026-10-20 14:30:00; o mural já recebe 20/10/2026
    function formatarDataBr(valor) {
        const partes = /^(\d{4})-(\d{2})-(\d{2})/.exec(valor || '');
        return partes ? partes[3] + '/' + partes[2] + '/' + partes[1] : (valor || '');
    }
 
    let filtroAtual = 'todas';
    let paginaMinhas = 1;
 
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
        item.setAttribute('role', 'link');
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
            document.querySelectorAll('.filtro-chip').forEach(c => c.classList.toggle('ativo', c === chip));
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
                window.location.href = urlApp('denuncia/editar?id=' + encodeURIComponent(item.dataset.idDenuncia));
            } else if (botao.dataset.acao === 'remover' && !botao.disabled) {
                removerDenuncia(item, botao);
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
            const json = await enviarPost('denuncia/remover', token, { 'id-denuncia': item.dataset.idDenuncia });
            if (!json.resposta) throw new Error(json.mensagem || 'Não foi possível remover');
            await Promise.all([carregarMinhasDenuncias(paginaMinhas), recarregarMural()]);
        } catch (erro) {
            alert(erro.message || 'Erro ao remover denúncia.');
            botao.disabled = false;
        }
    }
 
    let sequenciaMinhas = 0;
    async function carregarMinhasDenuncias(pagina = 1) {
        const lista = document.getElementById('lista-minhas-denuncias');
        const modelo = document.getElementById('modelo-minha-denuncia');
        if (!lista || !modelo) return;
        const token = lerToken();
        if (!token) {
            atualizarResumo(); lista.replaceChildren();
            mostrarMensagemMinhas('Faça login para ver as suas denúncias.', true);
            RelatePaginacao.mostrar('paginacao-minhas', null, () => {});
            return;
        }
        const requisicao = ++sequenciaMinhas;
        mostrarMensagemMinhas('Carregando suas denúncias...');
        const params = new URLSearchParams({ pagina: String(pagina), status: filtroAtual === 'todas' ? '' : filtroAtual });
        try {
            const json = await buscarJson('denuncia/minhas?' + params, token);
            if (requisicao !== sequenciaMinhas) return;
            if (!json.resposta) throw new Error(json.mensagem || 'Falha ao consultar suas denúncias');
            paginaMinhas = json.paginacao.pagina;
            atualizarResumo(json.resumo);
            lista.replaceChildren();
            const fragmento = document.createDocumentFragment();
            (json.dados || []).forEach(d => fragmento.appendChild(montarItemMinhaDenuncia(modelo, d)));
            lista.appendChild(fragmento);
            mostrarMensagemMinhas(json.dados.length ? '' : 'Nenhuma denúncia encontrada neste filtro.');
            RelatePaginacao.mostrar('paginacao-minhas', json.paginacao,
                n => carregarMinhasDenuncias(n).then(() => document.querySelector('.conteudo-principal')?.scrollTo({ top: 0, behavior: 'smooth' })));
        } catch (erro) {
            if (requisicao !== sequenciaMinhas) return;
            console.error('Erro ao carregar minhas denúncias:', erro);
            mostrarMensagemMinhas('Não foi possível carregar suas denúncias.');
        }
    }
 
    // Chamado pelo mural.js ao iniciar a página (retorno = estado salvo ao voltar de denuncia/ver, ou null)
    function iniciar(retorno) {
        if (retorno) {
            filtroAtual = retorno.filtroMinhas || 'todas';
            document.querySelectorAll('.filtro-chip').forEach(
                chip => chip.classList.toggle('ativo', chip.dataset.filtro === filtroAtual));
        }
        return carregarMinhasDenuncias(retorno?.paginaMinhas || 1);
    }
 
    // Estado que o mural.js salva ao abrir uma denúncia
    function estado() {
        return { filtroMinhas: filtroAtual, paginaMinhas };
    }
 
    window.RelateMinhas = { iniciar, estado };
})();
 
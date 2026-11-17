(function () {
    'use strict';

    // Área do REPRESENTANTE: mesma tela de "Minhas denúncias", mas com as denúncias da cidade dele
    // e um único botão por card: "Responder".
    const { urlApp, lerToken, perfilLogado, buscarJson, irParaLogin, irParaDetalhe, STATUS } = window.RelateMural;

    function formatarDataBr(valor) {
        const partes = /^(\d{4})-(\d{2})-(\d{2})/.exec(valor || '');
        return partes ? partes[3] + '/' + partes[2] + '/' + partes[1] : (valor || '');
    }

    let filtroAtual = 'todas';
    let paginaAtual = 1;
    let sequencia = 0;

    const lista = document.getElementById('lista-recebidas');
    const modelo = document.getElementById('modelo-recebida');
    const filtros = document.getElementById('filtro-recebidas');

    function mostrarMensagem(texto, comLinkLogin) {
        const area = document.getElementById('recebidas-mensagem');
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

    // Contagem feita no banco (COUNT/GROUP BY), nunca só com as 10 linhas da página.
    function atualizarResumo(resumo = {}) {
        definirTexto('rec-resumo-concluidas', resumo.concluida ?? 0);
        definirTexto('rec-resumo-andamento', resumo.andamento ?? 0);
        definirTexto('rec-resumo-analise', resumo.analise ?? 0);
        definirTexto('rec-resumo-pendentes', resumo.pendente ?? 0);
        definirTexto('total-recebidas', resumo.total ?? 0);
    }

    function resumirTexto(texto, maximo) {
        texto = (texto || '').replace(/\s+/g, ' ').trim();
        return texto.length > maximo ? texto.slice(0, maximo - 1) + '…' : texto;
    }

    function montarItem(denuncia) {
        const item = modelo.content.firstElementChild.cloneNode(true);
        const info = STATUS[denuncia.status] || { texto: denuncia.status, filtro: '' };

        function campo(nome) { return item.querySelector('[data-campo="' + nome + '"]'); }

        item.dataset.status = info.filtro;
        item.dataset.idDenuncia = denuncia.id;
        item.tabIndex = 0;
        item.setAttribute('role', 'link');

        campo('titulo').textContent    = denuncia.titulo;
        campo('descricao').textContent = resumirTexto(denuncia.descricao, 110);
        campo('local').textContent     = denuncia.local;
        campo('data').textContent      = formatarDataBr(denuncia.data);
        campo('status').textContent    = info.texto;

        return item;
    }

    // Filtros
    if (filtros) {
        filtros.querySelectorAll('.notif-chip').forEach(chip => {
            chip.addEventListener('click', () => {
                filtros.querySelectorAll('.notif-chip').forEach(c => c.classList.toggle('ativo', c === chip));
                filtroAtual = chip.dataset.filtro;
                carregar(1);
            });
        });
    }

    // Cliques: o card abre os detalhes; o botão "Responder" inicia a conversa
    if (lista) {
        lista.addEventListener('click', evento => {
            const item = evento.target.closest('.denuncia-item[data-id-denuncia]');
            if (!item) return;
            const botao = evento.target.closest('[data-acao]');

            if (botao && botao.dataset.acao === 'responder') {
                responder(item.dataset.idDenuncia, botao);
            } else {
                irParaDetalhe(item.dataset.idDenuncia);
            }
        });
    }

    // PRÓXIMA ETAPA: aqui entra o chat (POST chat/iniciar + abrir a aba Mensagens).
    // O mensagens.js vai definir window.RelateChat.iniciar(idDenuncia).
    async function responder(idDenuncia, botao) {
        if (window.RelateChat && typeof window.RelateChat.iniciar === 'function') {
            botao.disabled = true;
            try { await window.RelateChat.iniciar(idDenuncia); }
            finally { botao.disabled = false; }
            return;
        }
        alert('O chat com o denunciante ainda está em desenvolvimento.');
    }

    async function carregar(pagina = 1) {
        if (!lista || !modelo) return;

        const token = lerToken();
        if (!token) {
            atualizarResumo(); lista.replaceChildren();
            mostrarMensagem('Faça login para ver as denúncias.', true);
            RelatePaginacao.mostrar('paginacao-recebidas', null, () => {});
            return;
        }

        const requisicao = ++sequencia;
        mostrarMensagem('Carregando denúncias...');
        const params = new URLSearchParams({
            pagina: String(pagina),
            status: filtroAtual === 'todas' ? '' : filtroAtual
        });

        try {
            const json = await buscarJson('denuncia/recebidas?' + params, token);
            if (requisicao !== sequencia) return;
            if (!json.resposta) throw new Error(json.mensagem || 'Falha ao consultar as denúncias');

            paginaAtual = json.paginacao.pagina;
            atualizarResumo(json.resumo);

            definirTexto('recebidas-descricao', json.area
            ? 'Denúncias de ' + json.area + ' da sua cidade. Responda os moradores.'
            : 'Acompanhe as denúncias da sua cidade e responda os moradores.');

            lista.replaceChildren();
            const fragmento = document.createDocumentFragment();
            (json.dados || []).forEach(d => fragmento.appendChild(montarItem(d)));
            lista.appendChild(fragmento);

            mostrarMensagem(json.dados.length ? '' : 'Nenhuma denúncia encontrada neste filtro.');
            RelatePaginacao.mostrar('paginacao-recebidas', json.paginacao,
                n => carregar(n).then(() => document.querySelector('.conteudo-principal')?.scrollTo({ top: 0, behavior: 'smooth' })));
        } catch (erro) {
            if (requisicao !== sequencia) return;
            console.error('Erro ao carregar denúncias recebidas:', erro);
            mostrarMensagem(erro.message || 'Não foi possível carregar as denúncias.');
        }
    }

    // Chamado pelo mural.js ao iniciar (só para representante)
    function iniciar(retorno) {
        if (perfilLogado() !== 'representante') return Promise.resolve();
        if (retorno) {
            filtroAtual = retorno.filtroRecebidas || 'todas';
            if (filtros) {
                filtros.querySelectorAll('.notif-chip').forEach(
                    chip => chip.classList.toggle('ativo', chip.dataset.filtro === filtroAtual));
            }
        }
        return carregar(retorno?.paginaRecebidas || 1);
    }

    // Estado que o mural.js salva ao abrir uma denúncia (para voltar no mesmo ponto)
    function estado() {
        return { filtroRecebidas: filtroAtual, paginaRecebidas: paginaAtual };
    }

    window.RelateRecebidas = { iniciar, estado };
})();
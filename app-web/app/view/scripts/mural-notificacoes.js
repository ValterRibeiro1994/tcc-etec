// Notificações REAIS: nenhuma notificação de demonstração e nenhuma consulta para visitantes.
(function () {
    'use strict';
    const { urlApp, lerToken, buscarJson, enviarPost, irParaDetalhe } = window.RelateMural;
    const link = document.getElementById('link-notificacoes');
    const lista = document.getElementById('lista-notificacoes');
    const modelo = document.getElementById('modelo-notificacao');
    const vazio = document.getElementById('notificacoes-vazio');
    const vazioTitulo = document.getElementById('notificacoes-vazio-titulo');
    const vazioTexto = document.getElementById('notificacoes-vazio-texto');
    const badge = document.getElementById('badge-notificacoes');
    const filtros = document.getElementById('filtro-notificacoes');
    const botaoTodas = document.getElementById('btn-marcar-todas');
    const aviso = document.getElementById('notificacoes-mensagem');
    if (!link || !lista || !modelo) return;

    let dados = [];
    let naoLidasServidor = 0;
    let filtroAtual = 'todas';
    let executando = false;
    const ICONES = {
        nova_denuncia: '📋', status: '🔄', comentario: '💬', mensagem: '✉️', sistema: '🛡️'
    };

    function exibirAviso(texto) {
        if (!aviso) return;
        aviso.textContent = texto;
        aviso.style.display = texto ? 'block' : 'none';
    }

    function horario(texto) {
        const data = new Date(String(texto || '').replace(' ', 'T'));
        return isNaN(data.getTime()) ? '' : data.toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' });
    }

    function grupo(texto) {
        const data = new Date(String(texto || '').replace(' ', 'T'));
        if (isNaN(data.getTime())) return 'Anteriores';
        const hoje = new Date(); hoje.setHours(0, 0, 0, 0);
        const dia = new Date(data); dia.setHours(0, 0, 0, 0);
        const dias = Math.round((hoje - dia) / 86400000);
        return dias === 0 ? 'Hoje' : dias <= 7 ? 'Esta semana' : 'Anteriores';
    }

    function renderizar() {
        const logado = !!lerToken();
        link.style.display = logado ? '' : 'none';
        if (!logado) {
            dados = []; naoLidasServidor = 0;
            lista.replaceChildren();
            if (badge) badge.hidden = true;
            return;
        }
        const visiveis = dados.filter(n => filtroAtual === 'todas' || !Number(n.lida));
        lista.replaceChildren();
        if (badge) {
            badge.textContent = naoLidasServidor > 99 ? '99+' : String(naoLidasServidor);
            badge.hidden = naoLidasServidor === 0;
        }
        botaoTodas.disabled = naoLidasServidor === 0;
        vazio.hidden = visiveis.length > 0;
        if (!visiveis.length) {
            vazioTitulo.textContent = filtroAtual === 'nao-lidas' ? 'Tudo em dia' : 'Nenhuma notificação por aqui';
            vazioTexto.textContent = 'Os avisos sobre denúncias e mensagens aparecerão aqui.';
            return;
        }
        for (const titulo of ['Hoje', 'Esta semana', 'Anteriores']) {
            const grupoDados = visiveis.filter(n => grupo(n.data) === titulo);
            if (!grupoDados.length) continue;
            const secao = document.createElement('section');
            secao.className = 'notificacoes-grupo';
            const cabecalho = document.createElement('h3');
            cabecalho.className = 'notificacoes-grupo-titulo';
            cabecalho.textContent = titulo;
            const ul = document.createElement('ul');
            ul.className = 'notificacoes-lista';
            for (const n of grupoDados) {
                const item = modelo.content.firstElementChild.cloneNode(true);
                item.classList.toggle('nao-lida', !Number(n.lida));
                const icone = item.querySelector('[data-campo="icone"]');
                icone.textContent = ICONES[n.tipo] || '🔔';
                icone.dataset.tipo = n.tipo;
                item.querySelector('[data-campo="titulo"]').textContent = n.titulo;
                item.querySelector('[data-campo="mensagem"]').textContent = n.mensagem;
                item.querySelector('[data-campo="tempo"]').textContent = horario(n.data);
                item.querySelector('.notificacao-corpo').addEventListener('click', () => abrirNotificacao(n));
                ul.appendChild(item);
            }
            secao.append(cabecalho, ul);
            lista.appendChild(secao);
        }
    }

    async function atualizar() {
        if (!lerToken() || executando) { renderizar(); return; }
        executando = true;
        try {
            const resposta = await buscarJson('notificacao/minhas', lerToken());
            if (!resposta.resposta) throw new Error(resposta.mensagem);
            dados = resposta.dados || [];
            naoLidasServidor = Number(resposta.nao_lidas) || 0;
            exibirAviso('');
        } catch (erro) {
            console.error('Notificações:', erro);
            exibirAviso('Não foi possível carregar as notificações. Confira se a migração SQL foi executada.');
            dados = []; naoLidasServidor = 0;
        } finally {
            executando = false;
            renderizar();
        }
    }

    async function abrirNotificacao(n) {
        if (!lerToken()) return;
        if (!Number(n.lida)) {
            try {
                const retorno = await enviarPost('notificacao/ler', lerToken(), { id: n.id });
                if (!retorno.resposta) throw new Error(retorno.mensagem);
                n.lida = 1;
                naoLidasServidor = Math.max(0, naoLidasServidor - 1);
                renderizar();
            } catch (erro) { exibirAviso('Não foi possível marcar como lida.'); return; }
        }
        if (n.denuncia) irParaDetalhe(n.denuncia);
        else if (n.tipo === 'mensagem') document.getElementById('link-mensagens')?.click();
    }

    botaoTodas?.addEventListener('click', async () => {
        if (!lerToken()) return;
        botaoTodas.disabled = true;
        try {
            const retorno = await enviarPost('notificacao/lerTodas', lerToken(), {});
            if (!retorno.resposta) throw new Error(retorno.mensagem);
            dados.forEach(n => { n.lida = 1; });
            naoLidasServidor = 0;
            exibirAviso(''); renderizar();
        } catch (erro) { exibirAviso('Não foi possível marcar todas como lidas.'); }
        finally { botaoTodas.disabled = naoLidasServidor === 0; }
    });

    filtros?.addEventListener('click', evento => {
        const chip = evento.target.closest('[data-filtro]');
        if (!chip) return;
        filtroAtual = chip.dataset.filtro;
        filtros.querySelectorAll('[data-filtro]').forEach(el => el.classList.toggle('ativo', el === chip));
        renderizar();
    });

    link.addEventListener('click', atualizar);
    window.addEventListener('storage', atualizar);
    // Atualiza contador a cada 30s, sem fazer consultas quando não houver login.
    setInterval(() => { if (!document.hidden && lerToken()) atualizar(); }, 30000);
    renderizar();
    if (lerToken()) atualizar();
    window.RelateNotificacoes = { atualizar };
})();

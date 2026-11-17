// Dashboard da prefeitura. O JS exibe dados; as permissões são verificadas no PainelController/PainelRepositorio.
(function () {
    'use strict';
    const { lerToken, perfilLogado, buscarJson, enviarPost, irParaDetalhe } = window.RelateMural;
    const pagina = document.getElementById('tela-painel');
    const link = document.getElementById('link-painel');
    if (!pagina || !link) return;
    const elementos = {
        escopo: document.getElementById('painel-escopo'),
        total: document.getElementById('painel-total'),
        pendente: document.getElementById('painel-pendentes'),
        analise: document.getElementById('painel-analise'),
        andamento: document.getElementById('painel-andamento'),
        concluida: document.getElementById('painel-concluidas'),
        atrasadas: document.getElementById('painel-atrasadas'),
        fechadas: document.getElementById('painel-fechadas-mes'),
        categorias: document.getElementById('painel-categorias'),
        lista: document.getElementById('painel-lista'),
        aviso: document.getElementById('painel-aviso'),
        busca: document.getElementById('painel-busca'),
        filtroStatus: document.getElementById('painel-filtro-status'),
        filtroCategoria: document.getElementById('painel-filtro-categoria'),
        paginacao: document.getElementById('paginacao-painel')
    };
    const STATUS = ['pendente', 'em analise', 'em andamento', 'concluida'];
    const ROTULOS = { 'pendente': 'Pendente', 'em analise': 'Em análise', 'em andamento': 'Em andamento', 'concluida': 'Concluída' };
    let paginaAtual = 1;
    let sequencia = 0;
    let ultimaLista = [];
    let timerBusca;

    function autorizado() { return perfilLogado() === 'representante' && !!lerToken(); }
    function avisar(texto, erro = false) {
        elementos.aviso.textContent = texto;
        elementos.aviso.hidden = !texto;
        elementos.aviso.classList.toggle('painel-erro', erro);
    }
    function valor(id, conteudo) { elementos[id].textContent = String(conteudo ?? 0); }
    function filtros() {
        return new URLSearchParams({ status: elementos.filtroStatus.value,
            categoria: elementos.filtroCategoria.value, busca: elementos.busca.value.trim() });
    }

    async function carregarResumo() {
        const json = await buscarJson('painel/resumo', lerToken());
        if (!json.resposta) throw new Error(json.mensagem);
        const dados = json.dados;
        elementos.escopo.textContent = dados.cidade + ' · ' + dados.area;
        valor('total', dados.contagens.total); valor('pendente', dados.contagens.pendente);
        valor('analise', dados.contagens['em analise']); valor('andamento', dados.contagens['em andamento']);
        valor('concluida', dados.contagens.concluida);
        valor('atrasadas', dados.atrasadas); valor('fechadas', dados.concluidas_mes);
        elementos.categorias.replaceChildren();
        const max = Math.max(1, ...dados.categorias.map(item => Number(item.quantidade)));
        for (const item of dados.categorias.slice(0, 10)) {
            const linha = document.createElement('div'); linha.className = 'painel-categoria-linha';
            const texto = document.createElement('span'); texto.textContent = item.categoria + ' (' + item.quantidade + ')';
            const trilho = document.createElement('div'); trilho.className = 'painel-categoria-trilho';
            const preenchimento = document.createElement('div'); preenchimento.className = 'painel-categoria-progresso';
            preenchimento.style.width = (100 * item.quantidade / max) + '%';
            trilho.appendChild(preenchimento); linha.append(texto, trilho);
            elementos.categorias.appendChild(linha);
        }
        if (!dados.categorias.length) elementos.categorias.textContent = 'Nenhuma denúncia na área de atuação.';
        const anterior = elementos.filtroCategoria.value;
        elementos.filtroCategoria.replaceChildren(new Option('Todas as categorias', ''));
        for (const c of dados.categorias) elementos.filtroCategoria.add(new Option(c.categoria, c.categoria));
        if ([...elementos.filtroCategoria.options].some(o => o.value === anterior)) elementos.filtroCategoria.value = anterior;
    }

    function montarCard(d) {
        const modelo = document.getElementById('modelo-painel-denuncia');
        const item = modelo.content.firstElementChild.cloneNode(true);
        const txt = nome => item.querySelector('[data-campo="' + nome + '"]');
        txt('titulo').textContent = '#' + d.id + ' · ' + d.titulo;
        txt('dados').textContent = d.categoria + ' · ' + d.bairro + ' · ' + d.data;
        txt('status').textContent = ROTULOS[d.status] || d.status;
        const select = item.querySelector('[data-acao="status"]');
        for (const nome of STATUS) select.add(new Option(ROTULOS[nome], nome));
        select.value = d.status;
        item.querySelector('[data-acao="abrir"]').addEventListener('click', () => irParaDetalhe(d.id));
        item.querySelector('[data-acao="responder"]').addEventListener('click', () => {
            const chat = window.RelateChat;
            if (!chat || typeof chat.iniciar !== 'function') { avisar('Chat indisponível.', true); return; }
            chat.iniciar(d.id).catch(e => avisar(e.message || 'Não foi possível iniciar conversa.', true));
        });
        item.querySelector('[data-acao="historico"]').addEventListener('click', async () => {
            const area = item.querySelector('.painel-historico');
            if (!area.hidden) { area.hidden = true; return; }
            area.hidden = false; area.textContent = 'Carregando histórico...';
            try {
                const ret = await buscarJson('painel/historico?id=' + encodeURIComponent(d.id), lerToken());
                if (!ret.resposta) throw new Error(ret.mensagem);
                area.replaceChildren();
                if (!ret.dados.length) { area.textContent = 'Nenhuma mudança de status registrada ainda.'; return; }
                const ul = document.createElement('ul');
                for (const h of ret.dados) {
                    const li = document.createElement('li');
                    li.textContent = h.alterado_em + ' · ' + h.status_anterior + ' → ' + h.status_novo +
                        ' · ' + h.responsavel + ' · ' + h.justificativa;
                    ul.appendChild(li);
                }
                area.appendChild(ul);
            } catch (erro) { area.textContent = erro.message; }
        });
        item.querySelector('[data-acao="salvar"]').addEventListener('click', async evento => {
            const novo = select.value;
            const justificativa = item.querySelector('[data-acao="justificativa"]').value.trim();
            if (novo === d.status) { avisar('Escolha um status diferente do atual.', true); return; }
            if (justificativa.length < 5 || justificativa.length > 350) {
                avisar('Informe uma justificativa entre 5 e 350 caracteres.', true); return;
            }
            evento.currentTarget.disabled = true;
            try {
                const res = await enviarPost('painel/mudarStatus', lerToken(), { id: d.id, status: novo, justificativa });
                if (!res.resposta) throw new Error(res.mensagem);
                avisar('Status da denúncia #' + d.id + ' atualizado. O autor receberá uma notificação.');
                await Promise.all([carregarResumo(), carregarLista(paginaAtual)]);
                window.RelateNotificacoes?.atualizar();
                if (window.RelateRecebidas) window.RelateRecebidas.iniciar();
            } catch (erro) { avisar(erro.message, true); }
            finally { evento.currentTarget.disabled = false; }
        });
        return item;
    }

    async function carregarLista(paginaSolicitada = 1) {
        const ordem = ++sequencia;
        const params = filtros(); params.set('pagina', String(paginaSolicitada));
        const json = await buscarJson('painel/listar?' + params, lerToken());
        if (ordem !== sequencia) return;
        if (!json.resposta) throw new Error(json.mensagem);
        paginaAtual = json.paginacao.pagina;
        ultimaLista = json.dados || [];
        elementos.lista.replaceChildren();
        if (!ultimaLista.length) elementos.lista.textContent = 'Nenhuma denúncia corresponde aos filtros.';
        else ultimaLista.forEach(d => elementos.lista.appendChild(montarCard(d)));
        RelatePaginacao.mostrar('paginacao-painel', json.paginacao, n => carregarLista(n));
    }

    async function carregar() {
        if (!autorizado()) return;
        avisar('Carregando painel...');
        try { await carregarResumo(); await carregarLista(paginaAtual); avisar(''); }
        catch (erro) { avisar(erro.message || 'Erro no painel.', true); console.error('Painel:', erro); }
    }

    // Protege CSV contra fórmulas que uma planilha poderia executar ao abrir o arquivo.
    function csvCampo(valor) {
        let texto = String(valor ?? '');
        if (/^[\s]*[=+@\-\t\r]/.test(texto)) texto = "'" + texto;
        return '"' + texto.replace(/"/g, '""') + '"';
    }
    document.getElementById('painel-exportar')?.addEventListener('click', async () => {
        if (!autorizado()) return;
        try {
            const json = await buscarJson('painel/exportar?' + filtros(), lerToken());
            if (!json.resposta) throw new Error(json.mensagem);
            const linhas = [['ID','Título','Categoria','Bairro','Cidade','Status','Data','Anônima']];
            for (const d of json.dados) linhas.push([d.id,d.titulo,d.categoria,d.bairro,d.cidade,d.status,d.data,d.anonima ? 'Sim':'Não']);
            const arquivo = '\uFEFF' + linhas.map(l => l.map(csvCampo).join(';')).join('\r\n');
            const url = URL.createObjectURL(new Blob([arquivo], { type: 'text/csv;charset=utf-8' }));
            const a = document.createElement('a'); a.href = url; a.download = 'relatesbc-painel.csv'; a.click();
            setTimeout(() => URL.revokeObjectURL(url), 1000);
            if (json.paginacao.total > 1000) avisar('Exportadas as 1.000 denúncias mais recentes; aplique filtros para exportar outras.');
            else avisar('CSV exportado.');
        } catch (erro) { avisar('Falha na exportação: ' + erro.message, true); }
    });
    document.getElementById('painel-atualizar')?.addEventListener('click', carregar);
    elementos.filtroStatus.addEventListener('change', () => carregarLista(1).catch(e => avisar(e.message, true)));
    elementos.filtroCategoria.addEventListener('change', () => carregarLista(1).catch(e => avisar(e.message, true)));
    elementos.busca.addEventListener('input', () => {
        clearTimeout(timerBusca);
        timerBusca = setTimeout(() => carregarLista(1).catch(e => avisar(e.message, true)), 300);
    });
    link.addEventListener('click', carregar);
    window.RelatePainel = { carregar };
})();

// Tela do terceiro perfil: administrador-geral (não é o representante).
// A interface só exibe os dados; cada requisição é autorizada de novo pelo PHP.
(function () {
    'use strict';
    if (!window.RelateMural) return;
    const { lerToken, perfilLogado, buscarJson, enviarPost } = window.RelateMural;
    const link = document.getElementById('link-admin');
    const lista = document.getElementById('admin-lista');
    const aviso = document.getElementById('admin-aviso');
    const busca = document.getElementById('admin-busca');
    const filtro = document.getElementById('admin-filtro');
    const botaoAnterior = document.getElementById('admin-anterior');
    const botaoProximo = document.getElementById('admin-proximo');
    const legendaPagina = document.getElementById('admin-pagina');
    if (!link || !lista) return;
    let pagina = 1;
    let paginas = 1;
    let timer = null;

    function autorizado() { return perfilLogado() === 'administrador' && !!lerToken(); }
    function avisar(texto, erro = false) {
        aviso.textContent = texto;
        aviso.hidden = !texto;
        aviso.classList.toggle('erro', erro);
    }
    function texto(id, valor) {
        const el = document.getElementById(id);
        if (el) el.textContent = String(valor ?? 0);
    }

    async function carregarResumo() {
        const res = await buscarJson('admin/resumo', lerToken());
        if (!res.resposta) throw new Error(res.mensagem);
        const d = res.dados;
        texto('admin-municipes', d.perfis.municipe);
        texto('admin-representantes', d.perfis.representante);
        texto('admin-pendentes', d.pendentes);
        texto('admin-aprovados', d.aprovados);
        texto('admin-denuncias', d.denuncias);
        texto('admin-concluidas', d.status.concluida);
        const status = document.getElementById('admin-status');
        status.replaceChildren();
        const nomes = { pendente: 'Pendente', 'em analise': 'Em análise', 'em andamento': 'Em andamento', concluida: 'Concluída' };
        for (const [chave, quantidade] of Object.entries(d.status)) {
            const linha = document.createElement('div');
            linha.className = 'admin-status-linha';
            const nome = document.createElement('span'); nome.textContent = nomes[chave] || chave;
            const numero = document.createElement('strong'); numero.textContent = quantidade;
            linha.append(nome, numero); status.appendChild(linha);
        }
        const auditoria = document.getElementById('admin-auditoria');
        auditoria.replaceChildren();
        if (!d.auditoria.length) auditoria.textContent = 'Nenhuma aprovação ou revogação registrada.';
        for (const acao of d.auditoria) {
            const linha = document.createElement('p');
            linha.className = 'admin-auditoria-item';
            linha.textContent = `${acao.data} · ${acao.administrador} decidiu ${acao.acao} acesso de ${acao.representante}`;
            auditoria.appendChild(linha);
        }
        const recentes = document.getElementById('admin-recentes');
        recentes.replaceChildren();
        if (!d.recentes.length) recentes.textContent = 'Nenhuma denúncia cadastrada.';
        for (const denuncia of d.recentes) {
            const linha = document.createElement('p');
            linha.className = 'admin-denuncia-item';
            linha.textContent = `#${denuncia.id} · ${denuncia.titulo} · ${denuncia.cidade} · ${denuncia.status}`;
            recentes.appendChild(linha);
        }
    }

    function criarRepresentante(d) {
        const div = document.createElement('article');
        div.className = 'admin-representante';
        const topo = document.createElement('header');
        const nome = document.createElement('h4');
        nome.textContent = `${d.nome} ${d.sobrenome} (#${d.id})`;
        const situacao = document.createElement('span');
        situacao.className = 'admin-situacao' + (d.aprovado_em ? ' ok' : '');
        situacao.textContent = d.aprovado_em ? 'Aprovado' : 'Pendente';
        topo.append(nome, situacao);
        const local = document.createElement('p');
        local.textContent = `${d.email} · ${d.cidade}/${d.estado} · ${d.area || 'Todas as categorias'}`;
        const detalhes = document.createElement('p');
        detalhes.textContent = `${d.orgao || 'Órgão não informado'} · ${d.cargo || 'Cargo não informado'}`;
        const botao = document.createElement('button');
        const acao = d.aprovado_em ? 'revogar' : 'aprovar';
        botao.type = 'button';
        botao.className = acao === 'aprovar' ? 'admin-btn-primario' : 'admin-btn-critico';
        botao.textContent = acao === 'aprovar' ? 'Aprovar representante' : 'Revogar autorização';
        botao.addEventListener('click', async () => {
            const pergunta = acao === 'aprovar'
                ? `Autorizar ${d.nome} a administrar denúncias da região e área cadastradas?`
                : `Revogar o acesso administrativo de ${d.nome}?`;
            if (!window.confirm(pergunta)) return;
            botao.disabled = true;
            try {
                const resposta = await enviarPost('admin/decidir', lerToken(), { id: d.id, acao });
                if (!resposta.resposta) throw new Error(resposta.mensagem);
                avisar(resposta.mensagem);
                await Promise.all([carregarResumo(), carregarRepresentantes()]);
                window.RelateNotificacoes?.atualizar();
            } catch (erro) {
                avisar(erro.message || 'Falha ao salvar autorização.', true);
            } finally { botao.disabled = false; }
        });
        div.append(topo, local, detalhes, botao);
        return div;
    }

    async function carregarRepresentantes() {
        const params = new URLSearchParams({ pagina: String(pagina), busca: busca.value.trim(), situacao: filtro.value });
        const resposta = await buscarJson('admin/representantes?' + params, lerToken());
        if (!resposta.resposta) throw new Error(resposta.mensagem);
        pagina = resposta.paginacao.pagina;
        paginas = resposta.paginacao.total_paginas;
        lista.replaceChildren();
        if (!resposta.dados.length) lista.textContent = 'Nenhuma conta encontrada.';
        else for (const d of resposta.dados) lista.appendChild(criarRepresentante(d));
        legendaPagina.textContent = `Página ${pagina} de ${paginas} · ${resposta.paginacao.total} representantes`;
        botaoAnterior.disabled = pagina <= 1;
        botaoProximo.disabled = pagina >= paginas;
    }

    async function carregar() {
        if (!autorizado()) return;
        avisar('Carregando administração geral...');
        try {
            await Promise.all([carregarResumo(), carregarRepresentantes()]);
            avisar('');
        } catch (erro) {
            avisar(erro.message || 'Não foi possível carregar o painel.', true);
            console.error('Administração geral:', erro);
        }
    }
    document.getElementById('admin-atualizar')?.addEventListener('click', carregar);
    botaoAnterior.addEventListener('click', () => { if (pagina > 1) { pagina--; carregarRepresentantes().catch(e => avisar(e.message, true)); } });
    botaoProximo.addEventListener('click', () => { if (pagina < paginas) { pagina++; carregarRepresentantes().catch(e => avisar(e.message, true)); } });
    busca.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(() => { pagina = 1; carregarRepresentantes().catch(e => avisar(e.message, true)); }, 300);
    });
    filtro.addEventListener('change', () => { pagina = 1; carregarRepresentantes().catch(e => avisar(e.message, true)); });
    link.addEventListener('click', carregar);
    window.RelateAdmin = { carregar };
})();

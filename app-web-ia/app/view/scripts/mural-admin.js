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
                       const funcoes = { tornar_municipe: 'munícipe', tornar_representante: 'representante', tornar_administrador: 'administrador' };
            linha.textContent = funcoes[acao.acao]
                ? `${acao.data} · ${acao.administrador} alterou a função de ${acao.representante} para ${funcoes[acao.acao]}`
                : `${acao.data} · ${acao.administrador} decidiu ${acao.acao} acesso de ${acao.representante}`;
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
        local.textContent = `${d.email} · ${d.cidade}/${d.estado} · ${(d.area || 'Todas as categorias').split(',').join(', ')}`;
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
            console.error('Não foi possível carregar a administração geral.');
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










// Página "Usuários e funções" do administrador-geral: lista as contas por função e permite alterá-la.
// A interface só exibe os dados; cada requisição é autorizada de novo pelo PHP.
(function () {
    'use strict';
    if (!window.RelateMural) return;
    const { lerToken, perfilLogado, buscarJson, enviarPost } = window.RelateMural;
    const link = document.getElementById('link-admin-usuarios');
    const lista = document.getElementById('lista-usuarios');
    const aviso = document.getElementById('usuarios-aviso');
    const mensagem = document.getElementById('usuarios-mensagem');
    const busca = document.getElementById('usuarios-busca');
    const modal = document.getElementById('modal-funcao');
    if (!link || !lista || !modal) return;
    const formulario = document.getElementById('form-funcao');
    const pessoa = document.getElementById('modal-funcao-pessoa');
    const opcoes = document.getElementById('modal-funcao-opcoes');
    const avisoModal = document.getElementById('modal-funcao-aviso');
    const botaoConfirmar = document.getElementById('modal-funcao-confirmar');

    const FUNCOES = {
        municipe: { nome: 'Munícipe', descricao: 'Registra e acompanha as próprias denúncias.' },
        representante: { nome: 'Representante', descricao: 'Atende as denúncias da sua região depois de aprovado.' },
        administrador: { nome: 'Administrador', descricao: 'Aprova representantes e altera a função das contas.' }
    };
    let filtroAtual = 'municipe';
    let pagina = 1;
    let sequencia = 0;
    let timer = null;
    let alvo = null; // conta que está aberta no quadro de alteração

    function autorizado() { return perfilLogado() === 'administrador' && !!lerToken(); }
    function avisar(texto, erro = false) {
        aviso.textContent = texto;
        aviso.hidden = !texto;
        aviso.classList.toggle('erro', erro);
    }
    function mostrarMensagem(texto) {
        mensagem.textContent = texto;
        mensagem.style.display = texto ? 'block' : 'none';
    }

    function atualizarContagens(resumo = {}) {
        document.querySelectorAll('[data-contagem]').forEach(el => {
            el.textContent = String(resumo[el.dataset.contagem] ?? 0);
        });
    }

    function criarLinhaMeta(icone, texto) {
        const linha = document.createElement('span');
        linha.className = 'meta-linha';
        linha.innerHTML = icone; // ícone fixo do próprio código; o texto abaixo entra sempre como textContent
        const conteudo = document.createElement('span');
        conteudo.textContent = texto;
        linha.appendChild(conteudo);
        return linha;
    }
    const ICONE_LOCAL = '<svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>';
    const ICONE_CARGO = '<svg viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>';

    function criarSelo(tipo, texto) {
        const selo = document.createElement('span');
        selo.className = 'status-badge usuario-selo';
        selo.dataset.tipo = tipo;
        selo.textContent = texto;
        return selo;
    }

    function criarUsuario(d) {
        const item = document.createElement('article');
        item.className = 'usuario-item';
        const principal = document.createElement('div');
        principal.className = 'usuario-principal';
        const nome = document.createElement('h3');
        nome.textContent = `${d.nome} ${d.sobrenome}`;
        if (d.voce) {
            const voce = document.createElement('span');
            voce.className = 'usuario-voce';
            voce.textContent = 'você';
            nome.append(' ', voce);
        }
        const email = document.createElement('p');
        email.textContent = d.email;
        principal.append(nome, email);

        const meta = document.createElement('div');
        meta.className = 'usuario-meta';
        meta.appendChild(criarLinhaMeta(ICONE_LOCAL, `${d.cidade}/${d.estado}`));
        if (d.perfil === 'representante') {
            meta.appendChild(criarLinhaMeta(ICONE_CARGO,
                `${d.orgao || 'Órgão não informado'} · ${d.cargo || 'Cargo não informado'}`));
        }

        const selos = document.createElement('div');
        selos.className = 'usuario-selos';
        selos.appendChild(criarSelo(d.perfil, (FUNCOES[d.perfil] || { nome: d.perfil }).nome));
        if (d.perfil === 'representante') {
            selos.appendChild(d.aprovado_em ? criarSelo('aprovado', 'Aprovado') : criarSelo('aguardando', 'Aguardando aprovação'));
        }

        const acoes = document.createElement('div');
        acoes.className = 'usuario-acoes';
        const botao = document.createElement('button');
        botao.type = 'button';
        botao.className = 'btn-acao btn-acao-principal';
        botao.textContent = 'Alterar função';
        if (d.voce) {
            botao.disabled = true;
            botao.title = 'Você não pode alterar a sua própria função';
        } else {
            botao.addEventListener('click', () => abrirModal(d));
        }
        acoes.appendChild(botao);

        item.append(principal, meta, selos, acoes);
        return item;
    }

    async function carregarUsuarios() {
        if (!autorizado()) return;
        const requisicao = ++sequencia;
        mostrarMensagem('Carregando usuários...');
        const params = new URLSearchParams({ pagina: String(pagina), busca: busca.value.trim(), perfil: filtroAtual });
        try {
            const json = await buscarJson('admin/usuarios?' + params, lerToken());
            if (requisicao !== sequencia) return;
            if (!json.resposta) throw new Error(json.mensagem);
            pagina = json.paginacao.pagina;
            atualizarContagens(json.resumo);
            lista.replaceChildren();
            const fragmento = document.createDocumentFragment();
            json.dados.forEach(d => fragmento.appendChild(criarUsuario(d)));
            lista.appendChild(fragmento);
            mostrarMensagem(json.dados.length ? '' : (busca.value.trim()
                ? 'Nenhuma conta encontrada para essa pesquisa.' : 'Nenhuma conta com essa função.'));
            RelatePaginacao.mostrar('paginacao-usuarios', json.paginacao, n => {
                pagina = n;
                carregarUsuarios().then(() => document.querySelector('.conteudo-principal')?.scrollTo({ top: 0, behavior: 'smooth' }));
            }, 'usuários');
        } catch (erro) {
            if (requisicao !== sequencia) return;
            console.error('Não foi possível carregar os usuários.');
            mostrarMensagem(erro.message || 'Não foi possível carregar os usuários.');
        }
    }

    /* ---------- Quadro "Alterar função" ---------- */

    function textoAviso(novaFuncao) {
        if (novaFuncao === alvo.perfil) return '';
        const partes = [];
        if (novaFuncao === 'representante') partes.push('A conta ficará aguardando aprovação na página Administração geral.');
        if (novaFuncao === 'administrador') partes.push('Administradores aprovam representantes e alteram a função de qualquer conta.');
        if (alvo.perfil === 'representante') partes.push('A aprovação atual como representante será removida.');
        partes.push('A pessoa precisa sair e entrar novamente para o novo acesso valer.');
        return partes.join(' ');
    }

    function atualizarQuadro() {
        const escolhida = modal.querySelector('input[name="funcao"]:checked')?.value;
        avisoModal.classList.remove('erro');
        avisoModal.textContent = escolhida ? textoAviso(escolhida) : '';
        botaoConfirmar.disabled = !escolhida || escolhida === alvo.perfil;
    }

    function abrirModal(d) {
        alvo = d;
        pessoa.textContent = `${d.nome} ${d.sobrenome} · ${d.email}`;
        opcoes.replaceChildren();
        for (const [valor, info] of Object.entries(FUNCOES)) {
            const rotulo = document.createElement('label');
            rotulo.className = 'funcao-opcao';
            const radio = document.createElement('input');
            radio.type = 'radio';
            radio.name = 'funcao';
            radio.value = valor;
            radio.checked = valor === d.perfil;
            radio.addEventListener('change', atualizarQuadro);
            const texto = document.createElement('span');
            const titulo = document.createElement('strong');
            titulo.textContent = info.nome;
            if (valor === d.perfil) {
                const atual = document.createElement('em');
                atual.className = 'funcao-atual';
                atual.textContent = 'atual';
                titulo.append(' ', atual);
            }
            const descricao = document.createElement('small');
            descricao.textContent = info.descricao;
            texto.append(titulo, descricao);
            rotulo.append(radio, texto);
            opcoes.appendChild(rotulo);
        }
        atualizarQuadro();
        modal.showModal();
    }

    async function confirmar() {
        const funcao = modal.querySelector('input[name="funcao"]:checked')?.value;
        if (!alvo || !funcao || funcao === alvo.perfil) return;
        botaoConfirmar.disabled = true;
        try {
            const resposta = await enviarPost('admin/alterarFuncao', lerToken(), { id: alvo.id, funcao });
            if (!resposta.resposta) throw new Error(resposta.mensagem);
            modal.close();
            avisar(resposta.mensagem);
            await Promise.all([carregarUsuarios(), window.RelateAdmin?.carregar()]);
        } catch (erro) {
            avisoModal.textContent = erro.message || 'Falha ao alterar a função.';
            avisoModal.classList.add('erro');
            botaoConfirmar.disabled = false;
        }
    }

    formulario.addEventListener('submit', evento => { evento.preventDefault(); confirmar(); });
    document.getElementById('modal-funcao-cancelar').addEventListener('click', () => modal.close());
    modal.addEventListener('click', evento => { if (evento.target === modal) modal.close(); });
    modal.addEventListener('close', () => { alvo = null; });

    /* ---------- Filtros e busca ---------- */

    document.querySelectorAll('#usuarios-filtros .notif-chip').forEach(chip => {
        chip.addEventListener('click', () => {
            document.querySelectorAll('#usuarios-filtros .notif-chip').forEach(c => c.classList.toggle('ativo', c === chip));
            filtroAtual = chip.dataset.filtro;
            pagina = 1;
            carregarUsuarios();
        });
    });
    busca.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(() => { pagina = 1; carregarUsuarios(); }, 300);
    });
    link.addEventListener('click', () => { avisar(''); carregarUsuarios(); });
    window.RelateAdminUsuarios = { carregar: carregarUsuarios };
})();
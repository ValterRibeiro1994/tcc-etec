(function () {
    'use strict';

    // true = não chama o backend (só simula o salvamento) e mostra todas as seções sem exigir login.
    // >>> TROQUE PARA false QUANDO AS ROTAS EXISTIREM <<<
    const USAR_EXEMPLO = true;

    const ROTAS = {
        preferencias: 'usuario/preferencias',   // GET -> { resposta, dados: { avisos_email: 0|1 } } | POST { avisos_email }
        email:        'usuario/alterar-email',  // POST { email, senha }
        senha:        'usuario/alterar-senha',  // POST { senha_atual, senha_nova }
        local:        'usuario/alterar-local',  // POST { estado, cidade }
        excluir:      'usuario/excluir'         // POST { senha }
    };
    const TAMANHO_MINIMO_SENHA = 8; // ajuste conforme a regra do cadastro

    const ESTADOS = {
        AC: 'Acre', AL: 'Alagoas', AP: 'Amapá', AM: 'Amazonas', BA: 'Bahia', CE: 'Ceará',
        DF: 'Distrito Federal', ES: 'Espírito Santo', GO: 'Goiás', MA: 'Maranhão',
        MT: 'Mato Grosso', MS: 'Mato Grosso do Sul', MG: 'Minas Gerais', PA: 'Pará',
        PB: 'Paraíba', PR: 'Paraná', PE: 'Pernambuco', PI: 'Piauí', RJ: 'Rio de Janeiro',
        RN: 'Rio Grande do Norte', RS: 'Rio Grande do Sul', RO: 'Rondônia', RR: 'Roraima',
        SC: 'Santa Catarina', SP: 'São Paulo', SE: 'Sergipe', TO: 'Tocantins'
    };

    const $ = id => document.getElementById(id);
    if (!$('tela-configuracoes')) return;

    const SRC = document.currentScript ? document.currentScript.src : '';
    const urlApp = rota => SRC ? new URL('../../../' + rota, SRC).href : './' + rota;

    /* ---------- Sessão (mesma lógica do mural.js) ---------- */

    function lerSessao() {
        const token = localStorage.getItem('token');
        if (!token) return null;
        try {
            const base64 = token.split('.')[1].replace(/-/g, '+').replace(/_/g, '/');
            const bytes = Uint8Array.from(atob(base64), c => c.charCodeAt(0));
            const payload = JSON.parse(new TextDecoder().decode(bytes));
            if (payload.exp && payload.exp * 1000 < Date.now()) return null;
            if (!payload.perfil || payload.perfil === 'visitante') return null;
            return { token: token, payload: payload };
        } catch (e) {
            return null;
        }
    }

    const sessao = lerSessao();
    const logado = USAR_EXEMPLO || !!sessao;

    // Quem não está logado só vê a aparência
    document.querySelectorAll('[data-requer-login]').forEach(el => { el.hidden = !logado; });
    $('config-aviso-login').hidden = logado;

    /* ---------- Servidor ---------- */

    async function lerJson(resposta) {
        const texto = await resposta.text();
        try {
            return JSON.parse(texto);
        } catch (e) {
            console.error('Resposta do servidor (não é JSON):', texto);
            throw new Error('Resposta inválida do servidor.');
        }
    }

    async function buscar(rota) {
        const resposta = await fetch(urlApp(rota), {
            method: 'GET',
            credentials: 'same-origin',
            headers: { 'Authorization': sessao.token }
        });
        return lerJson(resposta);
    }

    // O roteador lê $_POST e exige o campo "metodo", por isso o corpo vai como formulário.
    async function salvar(rota, corpo) {
        if (USAR_EXEMPLO) return { resposta: true };
        const resposta = await fetch(urlApp(rota), {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Authorization': sessao.token },
            body: new URLSearchParams(Object.assign({ metodo: 'POST' }, corpo))
        });
        return lerJson(resposta);
    }

    function mensagem(id, texto, ok) {
        const el = $(id);
        el.textContent = texto;
        el.className = 'config-msg ' + (ok ? 'ok' : 'erro');
        el.hidden = !texto;
    }

    // Envia um formulário, mostra o resultado e desativa o botão enquanto espera
    async function enviar(form, msgId, rota, corpo, textoOk, aoSucesso) {
        const botao = form.querySelector('button[type="submit"]');
        botao.disabled = true;
        mensagem(msgId, '', true);
        try {
            const json = await salvar(rota, corpo);
            if (!json.resposta) throw new Error(json.mensagem || 'Não foi possível salvar.');
            if (json.token) localStorage.setItem('token', json.token); // o token guarda cidade/estado
            mensagem(msgId, textoOk, true);
            if (aoSucesso) aoSucesso();
        } catch (erro) {
            console.error(erro);
            mensagem(msgId, erro.message || 'Erro de conexão. Tente novamente.', false);
        } finally {
            botao.disabled = false;
        }
    }

    /* ---------- Tema claro / escuro ---------- */

    const botoesTema = document.querySelectorAll('#config-tema .config-opcao');

    function marcarTema(tema) {
        botoesTema.forEach(b => b.classList.toggle('ativo', b.dataset.tema === tema));
    }

    function aplicarTema(tema) {
        const html = document.documentElement;
        if (tema === 'escuro') {
            html.setAttribute('data-tema', 'escuro');
            html.setAttribute('data-bs-theme', 'dark');
        } else {
            html.removeAttribute('data-tema');
            html.setAttribute('data-bs-theme', 'light');
        }
        try { localStorage.setItem('tema', tema); } catch (e) {}
        marcarTema(tema);
    }

    botoesTema.forEach(b => b.addEventListener('click', () => aplicarTema(b.dataset.tema)));
    marcarTema(document.documentElement.dataset.tema === 'escuro' ? 'escuro' : 'claro');

    /* ---------- Avisos por e-mail ---------- */

    const chkEmail = $('config-avisos-email');

    chkEmail.addEventListener('change', async function () {
        const ligado = chkEmail.checked;
        chkEmail.disabled = true;
        mensagem('msg-avisos', '', true);
        try {
            const json = await salvar(ROTAS.preferencias, { avisos_email: ligado ? 1 : 0 });
            if (!json.resposta) throw new Error(json.mensagem || 'Não foi possível salvar.');
            mensagem('msg-avisos', ligado ? 'Você vai receber avisos por e-mail.' : 'Avisos por e-mail desativados.', true);
        } catch (erro) {
            console.error(erro);
            chkEmail.checked = !ligado; // desfaz se o servidor não confirmou
            mensagem('msg-avisos', erro.message || 'Erro de conexão. Tente novamente.', false);
        } finally {
            chkEmail.disabled = false;
        }
    });

    async function carregarPreferencias() {
        if (USAR_EXEMPLO || !sessao) return;
        try {
            const json = await buscar(ROTAS.preferencias);
            if (json.resposta && json.dados) chkEmail.checked = Number(json.dados.avisos_email) === 1;
        } catch (erro) {
            console.error('Erro ao carregar preferências:', erro);
        }
    }

    /* ---------- Estado e cidade ---------- */

    const selectEstado = $('config-estado');
    const inputCidade = $('config-cidade');
    const listaCidades = $('lista-cidades');
    let cidades = [];

    Object.keys(ESTADOS).forEach(uf => selectEstado.add(new Option(uf + ' - ' + ESTADOS[uf], uf)));

    const ufDoToken = sessao ? String(sessao.payload.estado || '').toUpperCase() : '';
    selectEstado.value = ESTADOS[ufDoToken] ? ufDoToken : 'SP';
    inputCidade.value = sessao ? (sessao.payload.cidade || '') : '';

    function semAcento(texto) {
        return String(texto).normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
    }

    // Sugestões de cidade vindas do IBGE. Se falhar, o campo continua aceitando texto livre.
    async function carregarCidades(uf) {
        try {
            const resposta = await fetch('https://servicodados.ibge.gov.br/api/v1/localidades/estados/' + uf + '/municipios?orderBy=nome');
            if (!resposta.ok) throw new Error('IBGE indisponível');
            const nomes = (await resposta.json()).map(c => c.nome);
            if (selectEstado.value !== uf) return; // o usuário já trocou de estado
            cidades = nomes;
            listaCidades.replaceChildren(...nomes.map(nome => new Option(nome)));
        } catch (e) {
            cidades = [];
            listaCidades.replaceChildren();
        }
    }

    selectEstado.addEventListener('change', function () {
        inputCidade.value = '';
        carregarCidades(selectEstado.value);
    });

    $('form-local').addEventListener('submit', function (e) {
        e.preventDefault();
        const digitada = inputCidade.value.trim();
        if (!digitada) return mensagem('msg-local', 'Informe a cidade.', false);

        let cidade = digitada;
        if (cidades.length) {
            const achada = cidades.find(nome => semAcento(nome) === semAcento(digitada));
            if (!achada) return mensagem('msg-local', 'Escolha uma cidade da lista sugerida.', false);
            cidade = achada;
        }

        enviar(this, 'msg-local', ROTAS.local, { estado: selectEstado.value, cidade: cidade },
            'Localização atualizada.', () => { inputCidade.value = cidade; });
    });

    /* ---------- Alterar e-mail ---------- */

    $('form-email').addEventListener('submit', function (e) {
        e.preventDefault();
        enviar(this, 'msg-email', ROTAS.email,
            { email: $('config-email-novo').value.trim(), senha: $('config-email-senha').value },
            'E-mail atualizado.', () => this.reset());
    });

    /* ---------- Alterar senha ---------- */

    $('form-senha').addEventListener('submit', function (e) {
        e.preventDefault();
        const atual = $('config-senha-atual').value;
        const nova = $('config-senha-nova').value;
        const confirma = $('config-senha-confirma').value;

        if (nova.length < TAMANHO_MINIMO_SENHA) return mensagem('msg-senha', 'A nova senha precisa ter pelo menos ' + TAMANHO_MINIMO_SENHA + ' caracteres.', false);
        if (nova !== confirma) return mensagem('msg-senha', 'A confirmação não é igual à nova senha.', false);
        if (nova === atual) return mensagem('msg-senha', 'A nova senha precisa ser diferente da atual.', false);

        enviar(this, 'msg-senha', ROTAS.senha, { senha_atual: atual, senha_nova: nova },
            'Senha alterada.', () => this.reset());
    });

    /* ---------- Sair e excluir ---------- */

    // Reaproveita o botão "Sair" do topo, que já tem a lógica de logout do site.
    function sair() {
        const botao = $('logout-btn');
        if (botao) {
            botao.click();
        } else {
            localStorage.removeItem('token');
            window.location.href = urlApp('home');
        }
    }

    $('config-sair').addEventListener('click', sair);

    $('form-excluir').addEventListener('submit', async function (e) {
        e.preventDefault();
        if (!confirm('Tem certeza que deseja excluir sua conta? Essa ação não pode ser desfeita.')) return;

        if (USAR_EXEMPLO) {
            mensagem('msg-excluir', 'Modo exemplo: nenhuma conta foi excluída.', true);
            return;
        }

        const botao = this.querySelector('button[type="submit"]');
        botao.disabled = true;
        mensagem('msg-excluir', '', true);
        try {
            const json = await salvar(ROTAS.excluir, { senha: $('config-excluir-senha').value });
            if (!json.resposta) throw new Error(json.mensagem || 'Não foi possível excluir a conta.');
            sair();
        } catch (erro) {
            console.error(erro);
            mensagem('msg-excluir', erro.message || 'Erro de conexão. Tente novamente.', false);
            botao.disabled = false;
        }
    });

    carregarCidades(selectEstado.value);
    carregarPreferencias();
})();
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
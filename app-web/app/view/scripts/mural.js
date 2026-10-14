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

        const propriedadesTelinha = 'width=500,height=400,left=200,top=100,resizable=yes,scrollbars=yes,toolbar=no,location=no,status=no,menubar=no';
        const popup = window.open(urlApp('denuncia/criar'), 'popupWindow', propriedadesTelinha);

        // Verifica se o popup foi bloqueado pelo navegador
        if (!popup || popup.closed || typeof popup.closed === 'undefined') {
            alert('O popup foi bloqueado pelo navegador. Habilite popups para este site.');
        }
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

        return card;
    }

    async function carregarMural() {
        const grade = document.getElementById('grid-denuncias');
        const modelo = document.getElementById('card-mural');
        if (!grade || !modelo) return;

        // O card de exemplo vira só um modelo: sai da tela e é copiado para cada denúncia.
        const copiaModelo = modelo.cloneNode(true);
        modelo.remove();

        mostrarMensagemMural('Carregando denúncias...');

        try {
            const json = await buscarJson('denuncia/listar');

            if (!json.resposta) {
                console.error('Erro ao listar denúncias:', json);
                mostrarMensagemMural('Não foi possível carregar as denúncias agora. Tente novamente mais tarde.');
                return;
            }

            const denuncias = json.dados || [];
            if (denuncias.length === 0) {
                mostrarMensagemMural('Nenhuma denúncia registrada ainda.');
                return;
            }

            mostrarMensagemMural('');

            const fragmento = document.createDocumentFragment();
            denuncias.forEach(function (denuncia) {
                fragmento.appendChild(montarCard(copiaModelo, denuncia));
            });
            grade.appendChild(fragmento);
        } catch (erro) {
            console.error('Erro de rede ao carregar o mural:', erro);
            mostrarMensagemMural('Erro de conexão ao carregar as denúncias.');
        }
    }

    /* ---------- 3) Minhas denúncias: só as do usuário logado ---------- */

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

    // Números do resumo (Concluídas / Em andamento / Em análise / Pendentes)
    function atualizarResumo(denuncias) {
        const contagem = { concluida: 0, andamento: 0, analise: 0, pendente: 0 };
        denuncias.forEach(function (denuncia) {
            const info = STATUS[denuncia.status];
            if (info) contagem[info.filtro]++;
        });

        definirTexto('resumo-concluidas', contagem.concluida);
        definirTexto('resumo-andamento', contagem.andamento);
        definirTexto('resumo-analise', contagem.analise);
        definirTexto('resumo-pendentes', contagem.pendente);
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

        campo('titulo').textContent    = denuncia.titulo;
        campo('descricao').textContent = resumirTexto(denuncia.descricao, 110);
        campo('local').textContent     = denuncia.local;
        campo('data').textContent      = denuncia.data;
        campo('status').textContent    = info.texto;

        return item;
    }

    // Mostra/esconde os itens conforme o chip escolhido. Calcula na hora do clique,
    // porque os itens são criados depois que a página carrega.
    function aplicarFiltro() {
        const itens = document.querySelectorAll('#lista-minhas-denuncias .denuncia-item');
        let visiveis = 0;

        itens.forEach(function (item) {
            const mostrar = filtroAtual === 'todas' || item.dataset.status === filtroAtual;
            item.style.display = mostrar ? '' : 'none';
            if (mostrar) visiveis++;
        });

        if (itens.length > 0) {
            mostrarMensagemMinhas(visiveis === 0 ? 'Nenhuma denúncia com este status.' : '');
        }
    }

    document.querySelectorAll('.filtro-chip').forEach(function (chip) {
        chip.addEventListener('click', function () {
            document.querySelectorAll('.filtro-chip').forEach(function (c) { c.classList.remove('ativo'); });
            chip.classList.add('ativo');
            filtroAtual = chip.dataset.filtro;
            aplicarFiltro();
        });
    });

    async function carregarMinhasDenuncias() {
        const lista = document.getElementById('lista-minhas-denuncias');
        const modelo = document.getElementById('modelo-minha-denuncia');
        if (!lista || !modelo) return;

        const token = lerToken();
        if (!token) {
            atualizarResumo([]);
            mostrarMensagemMinhas('Faça login para ver as suas denúncias.', true);
            return;
        }

        mostrarMensagemMinhas('Carregando suas denúncias...');

        try {
            const json = await buscarJson('denuncia/minhas', token);

            if (!json.resposta) {
                console.error('Erro ao listar minhas denúncias:', json);
                atualizarResumo([]);
                mostrarMensagemMinhas(json.mensagem || 'Não foi possível carregar as suas denúncias.', true);
                return;
            }

            const denuncias = json.dados || [];
            lista.innerHTML = '';
            atualizarResumo(denuncias);

            if (denuncias.length === 0) {
                mostrarMensagemMinhas('Você ainda não registrou nenhuma denúncia.');
                return;
            }

            mostrarMensagemMinhas('');

            const fragmento = document.createDocumentFragment();
            denuncias.forEach(function (denuncia) {
                fragmento.appendChild(montarItemMinhaDenuncia(modelo, denuncia));
            });
            lista.appendChild(fragmento);
            aplicarFiltro();
        } catch (erro) {
            console.error('Erro de rede ao carregar minhas denúncias:', erro);
            mostrarMensagemMinhas('Erro de conexão ao carregar as suas denúncias.');
        }
    }

    /* ---------- Início ---------- */

    document.addEventListener('DOMContentLoaded', function () {
        mostrarPagina('mural');
        carregarMural();
        carregarMinhasDenuncias();
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
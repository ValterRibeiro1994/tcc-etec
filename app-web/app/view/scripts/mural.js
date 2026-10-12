/* ==========================================================
   MURAL - RelateSBC  (home-mural.html)
   1) Menu lateral: troca de telas, "Nova denúncia" e link "perfil"
   2) Mural: lista TODAS as denúncias (./denuncia/listar), com o nome de quem denunciou
   3) Minhas denúncias: lista só as do usuário logado (./denuncia/minhas)
   ========================================================== */

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

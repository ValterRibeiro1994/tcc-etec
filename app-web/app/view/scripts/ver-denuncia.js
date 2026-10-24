/* ==========================================================
   VER DENÚNCIA - RelateSBC  (ver-denuncia.html)
   - Mostra todas as informações de UMA denúncia (./denuncia/detalhe?id=N) - qualquer pessoa pode ver
   - Aba "Comentários": lista (./denuncia/comentarios?id=N) e publica (./denuncia/comentar, POST)
   - Só usuário logado comenta (token no localStorage)
   ========================================================== */

(function () {
    'use strict';

    // Lido agora (durante a execução do script) para montar as URLs corretamente
    const SRC_DO_SCRIPT = document.currentScript ? document.currentScript.src : '';

    // Raiz do projeto: app/view/scripts/ver-denuncia.js -> ../../../
    function urlApp(rota) {
        if (SRC_DO_SCRIPT) return new URL('../../../' + rota, SRC_DO_SCRIPT).href;
        return './' + rota;
    }

    // Texto/cor de cada status do banco (status_denuncia)
    const STATUS = {
        'pendente': { texto: 'Pendente', classe: 'text-bg-danger' },
        'em analise': { texto: 'Em análise', classe: 'text-bg-primary' },
        'em andamento': { texto: 'Em andamento', classe: 'text-bg-warning' },
        'concluida': { texto: 'Concluída', classe: 'text-bg-success' }
    };

    // Devolve o token só se for de um usuário LOGADO (não visitante) e ainda válido
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

    // "SÃO BERNARDO DO CAMPO" -> "São Bernardo do Campo"
    function formatarCidade(texto) {
        const minusculas = ['de', 'da', 'do', 'das', 'dos', 'e'];
        return (texto || '').toLowerCase().split(' ').map(function (parte, i) {
            if (i > 0 && minusculas.indexOf(parte) !== -1) return parte;
            return parte.charAt(0).toUpperCase() + parte.slice(1);
        }).join(' ');
    }

    // Lê a resposta como texto e converte para JSON (mostra o erro do PHP no console se não for JSON)
    async function lerJson(resposta) {
        const texto = await resposta.text();
        try {
            return JSON.parse(texto);
        } catch (erro) {
            console.error('Resposta do servidor (não é JSON):', texto);
            throw new Error('Resposta inválida do servidor');
        }
    }

    /* ---------- Elementos ---------- */

    const mensagem = document.getElementById('detalhe-mensagem');
    const conteudo = document.getElementById('detalhe-conteudo');
    const btnVoltar = document.getElementById('btn-voltar');
    const imagem = document.getElementById('detalhe-imagem');
    const elCategoria = document.getElementById('detalhe-categoria');
    const elStatus = document.getElementById('detalhe-status');
    const elTitulo = document.getElementById('detalhe-titulo');
    const elAutor = document.getElementById('detalhe-autor');
    const elData = document.getElementById('detalhe-data');
    const elDescricao = document.getElementById('detalhe-descricao');
    const elLocal = document.getElementById('detalhe-local');
    const elCidade = document.getElementById('detalhe-cidade');
    const elCep = document.getElementById('detalhe-cep');

    const form = document.getElementById('form-comentario');
    const campoTexto = document.getElementById('texto-comentario');
    const btnComentar = document.getElementById('btn-comentar');
    const contador = document.getElementById('contador-comentario');
    const avisoLogin = document.getElementById('aviso-login');
    const caixaMsg = document.getElementById('mensagem-comentario');
    const lista = document.getElementById('lista-comentarios');
    const vazio = document.getElementById('comentarios-vazio');
    const modelo = document.getElementById('modelo-comentario');
    const totalBadge = document.getElementById('total-comentarios');

    /* ---------- id da denúncia (vem da URL: ?id=12) ---------- */

    const idDenuncia = new URLSearchParams(window.location.search).get('id');
    if (!idDenuncia || !/^[1-9][0-9]*$/.test(idDenuncia)) {
        mensagem.textContent = 'Denúncia não informada.';
        return;
    }

    // "Voltar": volta para a página de onde veio (mural, perfil...) quando for do próprio site
    if (btnVoltar) {
        btnVoltar.addEventListener('click', function (evento) {

            try {
                // Quando o usuário veio do mural, o estado
                // salvo será restaurado ao voltar.
                if (sessionStorage.getItem('mural-retorno')) {
                    return;
                }

                // Se veio de outra página interna, como
                // o perfil, retorna à página anterior.
                if (
                    document.referrer &&
                    new URL(document.referrer).origin === location.origin
                ) {
                    evento.preventDefault();
                    history.back();
                }

            } catch (erro) {
                // Se não houver histórico utilizável,
                // segue normalmente para o mural.
            }
        });
    }

    /* ---------- Detalhes ---------- */

    function mostrarDetalhe(d) {
        // textContent (e não innerHTML) evita injeção de HTML vindo do banco
        elTitulo.textContent = d.titulo;
        elCategoria.textContent = d.categoria;
        elAutor.textContent = d.autor || 'Anônimo';
        elData.textContent = d.data;
        elDescricao.textContent = d.descricao;
        elLocal.textContent = d.local;
        elCidade.textContent = formatarCidade(d.cidade) + (d.estado ? ' / ' + d.estado : '');
        elCep.textContent = d.cep;

        // Informações da nova interface
        document.getElementById('detalhe-cidade-topo').textContent =
            formatarCidade(d.cidade);

        document.getElementById('detalhe-numero').textContent =
            '#' + d.id;

        document.getElementById('detalhe-data-info').textContent =
            d.data;

        const info = STATUS[d.status] || { texto: d.status, classe: 'text-bg-secondary' };
        elStatus.textContent = info.texto;
        elStatus.className = 'badge rounded-pill ' + info.classe;
        document.getElementById('detalhe-status-info').textContent =
            info.texto;

        if (d.imagem) {
            imagem.src = d.imagem;
            imagem.classList.remove('d-none');

            document.getElementById('detalhe-sem-imagem').hidden = true;
        }

        document.title = 'RelateSBC - ' + d.titulo;
        mensagem.style.display = 'none';
        conteudo.classList.remove('d-none');
    }

    async function carregarDetalhe() {
        try {
            const resposta = await fetch(urlApp('denuncia/detalhe?id=' + encodeURIComponent(idDenuncia)));
            const json = await lerJson(resposta);

            if (!json.resposta) {
                mensagem.textContent = json.mensagem || 'Não foi possível carregar a denúncia.';
                return;
            }
            mostrarDetalhe(json.dados);
        } catch (erro) {
            console.error('Erro ao carregar a denúncia:', erro);
            mensagem.textContent = 'Erro de conexão ao carregar a denúncia.';
        }
    }

    /* ---------- Comentários ---------- */

    function mostrarMensagemComentario(texto, tipo) {
        caixaMsg.textContent = texto;
        caixaMsg.className = texto ? 'alert alert-' + tipo : 'alert d-none'; // alert-danger | alert-success
    }

    function montarComentario(c) {
        const item = modelo.content.firstElementChild.cloneNode(true);

        item.querySelector('[data-campo="autor"]').textContent = c.autor;
        item.querySelector('[data-campo="data"]').textContent = c.data;
        item.querySelector('[data-campo="texto"]').textContent = c.texto;

        // representante da prefeitura ganha uma etiqueta
        const perfil = item.querySelector('[data-campo="perfil"]');
        if (c.perfil === 'representante') {
            perfil.textContent = 'Prefeitura';
        } else {
            perfil.textContent = 'Cidadão';
        }
        return item;
    }

    async function carregarComentarios() {
        try {
            const resposta = await fetch(urlApp('denuncia/comentarios?id=' + encodeURIComponent(idDenuncia)));
            const json = await lerJson(resposta);

            if (!json.resposta) {
                mostrarMensagemComentario(json.mensagem || 'Não foi possível carregar os comentários.', 'danger');
                return;
            }

            const comentarios = json.dados || [];
            lista.innerHTML = '';
            totalBadge.textContent = comentarios.length;
            vazio.classList.toggle('d-none', comentarios.length > 0);

            const fragmento = document.createDocumentFragment();
            comentarios.forEach(function (c) { fragmento.appendChild(montarComentario(c)); });
            lista.appendChild(fragmento);
        } catch (erro) {
            console.error('Erro ao carregar comentários:', erro);
            mostrarMensagemComentario('Erro de conexão ao carregar os comentários.', 'danger');
        }
    }

    // contador de caracteres (o servidor também limita a 1000)
    campoTexto.addEventListener('input', function () {
        contador.textContent = campoTexto.value.length + '/1000';
    });

    form.addEventListener('submit', async function (evento) {
        evento.preventDefault();
        mostrarMensagemComentario('', '');

        const token = lerToken();
        if (!token) {
            mostrarMensagemComentario('Faça login para comentar.', 'danger');
            return;
        }

        const texto = campoTexto.value.trim();
        if (texto === '') {
            mostrarMensagemComentario('Escreva um comentário antes de enviar.', 'danger');
            return;
        }

        const corpo = new FormData();
        corpo.append('metodo', 'POST'); // o roteador exige essa chave
        corpo.append('id-denuncia', idDenuncia);
        corpo.append('comentario', texto);

        btnComentar.disabled = true;
        const textoOriginal = btnComentar.textContent;
        btnComentar.textContent = 'Enviando...';

        try {
            const resposta = await fetch(urlApp('denuncia/comentar'), {
                method: 'POST',
                headers: { 'Authorization': token },
                body: corpo
            });
            const json = await lerJson(resposta);

            if (!json.resposta) {
                mostrarMensagemComentario(json.mensagem || 'Não foi possível publicar o comentário.', 'danger');
                return;
            }

            campoTexto.value = '';
            contador.textContent = '0/1000';
            mostrarMensagemComentario('Comentário publicado!', 'success');
            carregarComentarios();
        } catch (erro) {
            console.error('Erro ao publicar comentário:', erro);
            mostrarMensagemComentario('Erro de conexão ao publicar o comentário. Tente novamente.', 'danger');
        } finally {
            btnComentar.disabled = false;
            btnComentar.textContent = textoOriginal;
        }
    });

    /* ---------- Início ---------- */
    // Atualiza os botões da navbar padrão do site
    const logado = !!lerToken();

    document.querySelectorAll(
        '#btn-entrar, #btn-cadastro, #btn-entrar-mob, #btn-cadastro-mob'
    ).forEach(function (elemento) {
        elemento.style.display = logado ? 'none' : '';
    });

    document.querySelectorAll(
        '#usuario-logado, #usuario-logado-mob'
    ).forEach(function (elemento) {
        elemento.style.display = logado ? '' : 'none';
    });

    // quem não está logado vê os comentários, mas não o campo para escrever
    if (!lerToken()) {
        form.classList.add('d-none');
        avisoLogin.classList.remove('d-none');
    }

    carregarDetalhe();
    carregarComentarios();
})();
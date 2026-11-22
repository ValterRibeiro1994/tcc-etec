
// função para desconectar 
async function desconectar() {
     // se não existe um token ou um perfil, desconecte o usuario
    await fetch("../user/logout", {
        method: "GET",
        credentials: "same-origin"
    });
}

(function () {
    'use strict';

    
    const SRC_DO_SCRIPT = import.meta.url; 

    function urlApp(rota) {
        if (SRC_DO_SCRIPT) return new URL('../../../' + rota, SRC_DO_SCRIPT).href;
        return './' + rota;
    }

    // Cor e texto de cada status (classes do Bootstrap já usadas no perfil.html)
    const STATUS = {
        'pendente':     { texto: 'PENDENTE',     classe: 'bg-danger'  },
        'em analise':   { texto: 'EM ANÁLISE',   classe: 'bg-primary' },
        'em andamento': { texto: 'EM ANDAMENTO', classe: 'bg-warning' },
        'concluida':    { texto: 'CONCLUÍDA',    classe: 'bg-success' }
    };

    const ROTULO_PERFIL = { 'municipe': 'MUNÍCIPE', 'representante': 'REPRESENTANTE', 'administrador': 'ADMINISTRADOR-GERAL' };

    /* ---------- Sessão: só usuário logado vê o perfil ---------- */

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

    const token = lerToken();
    if (!token) {
        alert('Faça login para acessar o seu perfil.');
        window.location.href = urlApp('login');
        return;
    }

    async function buscarJson(rota) {
        const resposta = await fetch(urlApp(rota), {
            method: 'GET',
            headers: { 'Authorization': token },
            credentials: 'same-origin'
        });
        const contentType = resposta.headers.get('content-type') || 'ausente';
        if (!resposta.ok) {
            throw new Error(`${rota}: resposta HTTP ${resposta.status} (${contentType}).`);
        }
        const texto = await resposta.text();
        try {
            return JSON.parse(texto);
        } catch (erro) {
            console.error(`Resposta inválida em ${rota}: HTTP ${resposta.status}, Content-Type ${contentType}, ${erro.message}`);
            throw new Error(`${rota}: resposta inválida (HTTP ${resposta.status}, Content-Type ${contentType}).`);
        }
    }

    /* ---------- Elementos ---------- */

    const elNome     = document.getElementById('perfil-nome');
    const elBadge    = document.getElementById('perfil-badge');
    const elLocal    = document.getElementById('perfil-local');
    const elLista    = document.getElementById('lista-denuncias-perfil');
    const elModelo   = document.getElementById('modelo-denuncia-perfil');
    const elMensagem = document.getElementById('perfil-mensagem');
    const elBusca    = document.getElementById('busca-perfil');
    const btnNova    = document.getElementById('btn-nova-denuncia');
    let perfilAtual = null;

    function mostrarMensagem(texto, comLinkLogin) {
        if (!elMensagem) return;
        elMensagem.textContent = texto;
        if (texto && comLinkLogin) {
            const link = document.createElement('a');
            link.href = urlApp('login');
            link.textContent = 'Entrar novamente';
            elMensagem.appendChild(document.createTextNode(' '));
            elMensagem.appendChild(link);
        }
        elMensagem.style.display = texto ? 'block' : 'none';
    }

    // "SÃO BERNARDO DO CAMPO" -> "São Bernardo do Campo"
    function formatarCidade(texto) {
        const minusculas = ['de', 'da', 'do', 'das', 'dos', 'e'];
        return (texto || '').toLowerCase().split(' ').map(function (parte, i) {
            if (i > 0 && minusculas.indexOf(parte) !== -1) return parte;
            return parte.charAt(0).toUpperCase() + parte.slice(1);
        }).join(' ');
    }

    function resumirTexto(texto, maximo) {
        texto = (texto || '').replace(/\s+/g, ' ').trim();
        return texto.length > maximo ? texto.slice(0, maximo - 1) + '…' : texto;
    }

    /* ---------- Dados do usuário ---------- */

    async function carregarDados() {
        try {
            const json = await buscarJson('user/dados');

            if (!json.resposta) {
                console.error('user/dados recusou a consulta do perfil.', {
                    mensagem: json.mensagem,
                    codigo: json.dados && json.dados.codigo
                });
                mostrarMensagem(json.mensagem || 'Não foi possível carregar o seu perfil.', true);
                return false;
            }

            const usuario = json.dados;
            if (!usuario || typeof usuario !== 'object' ||
                !usuario.nome || !usuario.sobrenome || !usuario.perfil) {
                throw new Error('user/dados: campos de perfil ausentes na resposta.');
            }
            perfilAtual = usuario.perfil;
            elNome.textContent = usuario.nome + ' ' + usuario.sobrenome;
            elBadge.textContent = ROTULO_PERFIL[usuario.perfil] || String(usuario.perfil).toUpperCase();

            const partes = [];
            if (usuario.orgao) partes.push(usuario.orgao + (usuario.cargo ? ' — ' + usuario.cargo : ''));
            if (usuario.cidade) partes.push(formatarCidade(usuario.cidade) + (usuario.estado ? ' / ' + usuario.estado : ''));
            elLocal.textContent = partes.join('  |  ');
            return true;
        } catch (erro) {
            console.error('Falha ao carregar os dados do perfil:', erro);
            mostrarMensagem(erro.message || 'Não foi possível carregar os dados do seu perfil.');
            return false;
        }
    }

    /* ---------- Denúncias do próprio usuário ---------- */

    function montarLinha(denuncia) {
        const linha = elModelo.content.firstElementChild.cloneNode(true);
        const info = STATUS[denuncia.status] || { texto: String(denuncia.status).toUpperCase(), classe: 'bg-secondary' };

        function campo(nome) { return linha.querySelector('[data-campo="' + nome + '"]'); }

        campo('titulo').textContent    = denuncia.titulo;
        campo('descricao').textContent = resumirTexto(denuncia.descricao, 90);
        campo('local').textContent     = denuncia.local;
        campo('data').textContent      = denuncia.data;

        const badge = campo('status');
        badge.className = 'badge rounded-pill ' + info.classe;
        badge.textContent = info.texto;

        // texto usado pela caixa "Pesquisar"
        linha.dataset.busca = (denuncia.titulo + ' ' + denuncia.local + ' ' + denuncia.categoria + ' ' + denuncia.descricao).toLowerCase();
        linha.dataset.idDenuncia = denuncia.id;
        linha.dataset.descricaoCompleta = denuncia.descricao || '';
        if (denuncia.removivel === false) {
            const botao = linha.querySelector('[data-acao="remover"]');
            if (botao) { botao.disabled = true; botao.title = 'Não é possível remover denúncias concluídas'; }
        }
        if (perfilAtual === 'representante') {
            linha.querySelector('[data-acao="editar"]')?.remove();
            linha.querySelector('[data-acao="remover"]')?.remove();
        }

        return linha;
    }

    let paginaPerfil = 1;
    let sequenciaPerfil = 0;

    async function carregarDenuncias(pagina = 1) {
        if (!elLista || !elModelo) return;
        if (!perfilAtual) return;
        const requisicao = ++sequenciaPerfil;
        mostrarMensagem(perfilAtual === 'representante'
            ? 'Carregando denúncias da sua área...'
            : 'Carregando suas denúncias...');
        const parametros = new URLSearchParams({
            pagina: String(pagina), busca: elBusca?.value.trim() || ''
        });
        try {
            const rota = perfilAtual === 'representante' ? 'denuncia/recebidas?' : 'denuncia/minhas?';
            const json = await buscarJson(rota + parametros);
            if (requisicao !== sequenciaPerfil) return;
            if (!json.resposta) throw new Error(json.mensagem || 'Não foi possível consultar as denúncias.');
            paginaPerfil = json.paginacao.pagina;
            elLista.replaceChildren();
            const fragmento = document.createDocumentFragment();
            (json.dados || []).forEach(d => fragmento.appendChild(montarLinha(d)));
            elLista.appendChild(fragmento);
            mostrarMensagem(json.dados.length ? '' : perfilAtual === 'representante'
                ? 'Nenhuma denúncia encontrada para sua área de atendimento.'
                : 'Nenhuma denúncia encontrada.');
            RelatePaginacao.mostrar('paginacao-perfil', json.paginacao,
                paginaNova => carregarDenuncias(paginaNova)
            );
        } catch (erro) {
            if (requisicao !== sequenciaPerfil) return;
            console.error('Falha ao carregar as denúncias do perfil:', erro);
            mostrarMensagem(erro.message || 'Não foi possível carregar as denúncias.');
        }
    }

    async function removerDenuncia(linha, botao) {
        const titulo = linha.querySelector('[data-campo="titulo"]')?.textContent || '';
        if (!confirm(`Deseja realmente remover a denúncia "${titulo}"?`)) return;
        const corpo = new FormData();
        corpo.append('metodo','POST');
        corpo.append('id-denuncia',linha.dataset.idDenuncia);
        botao.disabled = true;
        try {
            const resposta = await fetch(urlApp('denuncia/remover'), {
                method:'POST', credentials:'same-origin', headers:{Authorization: token}, body:corpo
            });
            if (!resposta.ok) throw new Error('Falha ao remover denúncia.');
            const texto = await resposta.text();
            let json;
            try { json = JSON.parse(texto); }
            catch (erro) { console.error('O servidor retornou uma resposta inválida.'); throw new Error('Resposta inválida do servidor'); }
            if (!json.resposta) throw new Error(json.mensagem || 'Erro ao remover');
            await carregarDenuncias(paginaPerfil);
        } catch(erro) {
            alert(erro.message || 'Erro ao remover a denúncia.');
            botao.disabled = false;
        }
    }

    if (elLista) {
        elLista.addEventListener('click', evento => {
            const linha = evento.target.closest('li[data-id-denuncia]');
            if (!linha) return;
            const botao = evento.target.closest('[data-acao]');
            if (botao?.disabled) return;
            const id = encodeURIComponent(linha.dataset.idDenuncia);
            if (!botao || botao.dataset.acao === 'ver-mais') {
                window.location.href = urlApp('denuncia/ver?id=' + id);
            } else if (botao.dataset.acao === 'editar') {
                window.location.href = urlApp('denuncia/editar?id=' + id);
            } else if (botao.dataset.acao === 'remover') {
                removerDenuncia(linha,botao);
            }
        });
    }

    // Busca em TODAS as páginas (filtro SQL), com debounce.
    let atrasoPesquisa;
    if (elBusca) elBusca.addEventListener('input', () => {
        clearTimeout(atrasoPesquisa);
        atrasoPesquisa = setTimeout(() => carregarDenuncias(1),260);
    });

    if (btnNova) {
        btnNova.addEventListener('click', function () {
            window.location.href = urlApp('denuncia/criar');
        });
    }
    
    carregarDados().then(carregado => {
        if (carregado) return carregarDenuncias();
    });
})();

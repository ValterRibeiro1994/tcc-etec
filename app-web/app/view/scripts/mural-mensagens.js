(function () {
    'use strict';

    // Aba "Mensagens": lista de conversas + conversa aberta, com polling.
    // Rotas usadas: chat/minhas, chat/mensagens, chat/enviar, chat/encerrar, chat/iniciar
    // Todo texto vindo do servidor entra na tela com textContent (nunca innerHTML).
    const { urlApp, lerToken, perfilLogado, buscarJson, enviarPost, irParaLogin, irParaDetalhe } = window.RelateMural;

    const INTERVALO_MS = 4000;        // a cada ciclo
    const CICLOS_LISTA_ABA = 3;       // com a aba aberta: atualiza a lista ~12 s
    const CICLOS_LISTA_FORA = 12;     // com a aba fechada: só o contador do menu, ~48 s

    // ---------- elementos ----------
    const tela = document.getElementById('tela-mensagens');
    if (!tela) return;

    const layout = document.getElementById('chat-layout');
    const vazioGeral = document.getElementById('chat-vazio-geral');
    const vazioGeralTexto = document.getElementById('chat-vazio-geral-texto');
    const filtros = document.getElementById('filtro-conversas');
    const listaEl = document.getElementById('lista-conversas');
    const modeloConversa = document.getElementById('modelo-conversa');
    const modeloMensagem = document.getElementById('modelo-mensagem');

    const vazioSelecione = document.getElementById('chat-vazio-selecione');
    const conversaEl = document.getElementById('chat-conversa');
    const cabAvatar = document.getElementById('chat-cab-avatar');
    const cabTitulo = document.getElementById('chat-cab-titulo');
    const cabInterlocutor = document.getElementById('chat-cab-interlocutor');
    const cabSelo = document.getElementById('chat-cab-selo');
    const cabLink = document.getElementById('chat-cab-link');
    const btnVoltar = document.getElementById('chat-voltar');
    const btnEncerrar = document.getElementById('btn-encerrar-conversa');
    const mensagensEl = document.getElementById('chat-mensagens');
    const avisoEncerrada = document.getElementById('chat-aviso-encerrada');
    const form = document.getElementById('chat-form');
    const campoTexto = document.getElementById('chat-texto');
    const campoLimite = document.getElementById('chat-limite');
    const btnEnviar = document.getElementById('chat-enviar');
    const badge = document.getElementById('badge-mensagens');
    const linkMenu = document.getElementById('link-mensagens');

    // ---------- estado ----------
    let conversas = [];
    let filtroAtual = 'todas';
    let ativaId = null;          // conversa aberta
    let ultimoId = 0;            // maior id de mensagem já exibido na conversa aberta
    let ultimoDia = '';          // para os separadores "Hoje", "Ontem"...
    let hojeServidor = '';       // 'YYYY-MM-DD' segundo o servidor (fuso pode diferir do navegador)
    let enviando = false;
    let ocupado = false;
    let ciclo = 0;

    // ---------- datas ----------
    function doisDigitos(n) { return String(n).padStart(2, '0'); }

    function dataLocalIso(data) {
        return data.getFullYear() + '-' + doisDigitos(data.getMonth() + 1) + '-' + doisDigitos(data.getDate());
    }

    function hoje() { return hojeServidor || dataLocalIso(new Date()); }

    function ontem() {
        const [a, m, d] = hoje().split('-').map(Number);
        return dataLocalIso(new Date(a, m - 1, d - 1));
    }

    function atualizarHoje(agora) {
        if (agora && /^\d{4}-\d{2}-\d{2}/.test(agora)) hojeServidor = agora.slice(0, 10);
    }

    function horaDe(texto) { return (texto || '').slice(11, 16); }

    function rotuloDia(dia) {
        if (dia === hoje()) return 'Hoje';
        if (dia === ontem()) return 'Ontem';
        const [a, m, d] = dia.split('-');
        return d + '/' + m + '/' + a;
    }

    function horaDaLista(texto) {
        const dia = (texto || '').slice(0, 10);
        if (dia === hoje()) return horaDe(texto);
        if (dia === ontem()) return 'Ontem';
        const [, m, d] = dia.split('-');
        return d + '/' + m;
    }

    function iniciais(titulo) {
        const palavras = (titulo || '?').trim().split(/\s+/).slice(0, 2);
        return palavras.map(p => p.charAt(0)).join('').toUpperCase() || '?';
    }

    // ---------- chamadas ao servidor ----------
    async function chamarGet(rota) {
        return buscarJson(rota, lerToken());
    }

    async function chamarPost(rota, dados) {
        return enviarPost(rota, lerToken(), dados);
    }

    // ---------- lista de conversas ----------
    function atualizarBadge(total) {
        if (!badge) return;
        badge.textContent = total > 99 ? '99+' : String(total);
        badge.hidden = !(total > 0);
    }

    function textoVazioGeral(logado) {
        vazioGeralTexto.textContent = '';
        if (!logado) {
            vazioGeralTexto.appendChild(document.createTextNode('Faça login para ver as suas mensagens. '));
            const link = document.createElement('a');
            link.href = urlApp('login');
            link.textContent = 'Entrar';
            vazioGeralTexto.appendChild(link);
        } else if (perfilLogado() === 'representante') {
            vazioGeralTexto.textContent =
                'Você ainda não iniciou nenhuma conversa. Use o botão Responder em uma denúncia do painel.';
        } else {
            vazioGeralTexto.textContent =
                'Quando a prefeitura responder uma denúncia sua, a conversa aparece aqui.';
        }
    }

    function filtrar(lista) {
        if (filtroAtual === 'nao-lidas') return lista.filter(c => c.nao_lidas > 0);
        if (filtroAtual === 'abertas') return lista.filter(c => c.status === 'aberta');
        return lista;
    }

    function renderizarLista() {
        const semConversas = conversas.length === 0;
        layout.classList.toggle('sem-conversas', semConversas);
        vazioGeral.hidden = !semConversas;
        listaEl.replaceChildren();
        if (semConversas) return;

        const visiveis = filtrar(conversas);
        if (visiveis.length === 0) {
            const aviso = document.createElement('li');
            aviso.className = 'chat-conversa-previa';
            aviso.style.padding = '18px';
            aviso.textContent = 'Nenhuma conversa neste filtro.';
            listaEl.appendChild(aviso);
            return;
        }

        const fragmento = document.createDocumentFragment();
        visiveis.forEach(c => {
            const item = modeloConversa.content.firstElementChild.cloneNode(true);
            const campo = nome => item.querySelector('[data-campo="' + nome + '"]');

            item.dataset.id = c.id;
            item.classList.toggle('ativa', c.id === ativaId);
            item.classList.toggle('nao-lida', c.nao_lidas > 0);

            campo('avatar').textContent = iniciais(c.titulo);
            campo('titulo').textContent = c.titulo;
            campo('previa').textContent = c.previa;
            campo('hora').textContent = horaDaLista(c.ultima_em);
            campo('selo').dataset.status = c.status;
            campo('selo').textContent = c.status === 'aberta' ? 'Aberta' : 'Encerrada';

            const contador = campo('contador');
            contador.hidden = !(c.nao_lidas > 0);
            contador.textContent = String(c.nao_lidas);

            fragmento.appendChild(item);
        });
        listaEl.appendChild(fragmento);
    }

    async function carregarLista() {
        const token = lerToken();
        if (!token) {
            conversas = [];
            atualizarBadge(0);
            textoVazioGeral(false);
            renderizarLista();
            return;
        }

        try {
            const json = await chamarGet('chat/minhas');
            if (!json.resposta) throw new Error(json.mensagem || 'Falha ao consultar as conversas');
            atualizarHoje(json.agora);
            conversas = json.dados || [];
            atualizarBadge(json.total_nao_lidas || 0);
            textoVazioGeral(true);
            renderizarLista();

            // a conversa aberta pode ter mudado de status (ex.: encerrada pela prefeitura)
            const atual = conversas.find(c => c.id === ativaId);
            if (atual) aplicarEstadoConversa(atual);
        } catch (erro) {
            console.error('Erro ao carregar conversas:', erro);
        }
    }

    // ---------- conversa aberta ----------
    function aplicarEstadoConversa(c) {
        const aberta = c.status === 'aberta';

        cabSelo.dataset.status = c.status;
        cabSelo.textContent = aberta ? 'Aberta' : 'Encerrada';
        btnEncerrar.hidden = !(aberta && c.sou_representante);
        avisoEncerrada.hidden = aberta;

        form.classList.toggle('desativado', !aberta);
        campoTexto.disabled = !aberta;
        btnEnviar.disabled = !aberta;
        campoTexto.placeholder = aberta ? 'Escreva uma mensagem...' : 'Conversa encerrada';
    }

    function rolarParaOFim() {
        mensagensEl.scrollTop = mensagensEl.scrollHeight;
    }

    function pertoDoFim() {
        return mensagensEl.scrollHeight - mensagensEl.scrollTop - mensagensEl.clientHeight < 80;
    }

    function criarBolha(m) {
        const bolha = modeloMensagem.content.firstElementChild.cloneNode(true);
        bolha.classList.add(m.minha ? 'minha' : 'deles');
        if (m.id) bolha.dataset.id = m.id;
        bolha.querySelector('[data-campo="texto"]').textContent = m.texto;
        bolha.querySelector('[data-campo="hora"]').textContent = horaDe(m.enviada_em);
        bolha.querySelector('[data-campo="check"]').hidden = !m.minha;
        return bolha;
    }

    function adicionarMensagem(m) {
        if (m.id && mensagensEl.querySelector('.chat-bolha[data-id="' + m.id + '"]')) return null;

        const dia = (m.enviada_em || '').slice(0, 10);
        if (dia && dia !== ultimoDia) {
            const separador = document.createElement('div');
            separador.className = 'chat-dia';
            separador.textContent = rotuloDia(dia);
            mensagensEl.appendChild(separador);
            ultimoDia = dia;
        }

        const bolha = criarBolha(m);
        mensagensEl.appendChild(bolha);
        if (m.id) ultimoId = Math.max(ultimoId, m.id);
        return bolha;
    }

    function marcarLidas(lidasAte) {
        mensagensEl.querySelectorAll('.chat-bolha.minha[data-id]').forEach(b => {
            b.classList.toggle('lida', Number(b.dataset.id) <= lidasAte);
        });
    }

    function avisoNaConversa(texto) {
        const aviso = document.createElement('div');
        aviso.className = 'chat-sistema';
        aviso.textContent = texto;
        mensagensEl.appendChild(aviso);
        rolarParaOFim();
        setTimeout(() => aviso.remove(), 6000);
    }

    async function buscarNovas(primeiraVez) {
        if (!ativaId) return;
        const idDaBusca = ativaId;

        try {
            const json = await chamarGet('chat/mensagens?conversa=' + idDaBusca + '&depois=' + ultimoId);
            if (idDaBusca !== ativaId) return;   // o usuário trocou de conversa no meio da busca
            if (!json.resposta) throw new Error(json.mensagem || 'Falha ao buscar mensagens');
            atualizarHoje(json.agora);

            const seguir = primeiraVez || pertoDoFim();
            let chegouDoOutro = false;
            (json.dados || []).forEach(m => {
                if (adicionarMensagem(m) && !m.minha) chegouDoOutro = true;
            });
            marcarLidas(json.lidas_ate || 0);
            if (seguir) rolarParaOFim();

            // status pode ter mudado do outro lado
            const c = conversas.find(x => x.id === idDaBusca);
            if (c && json.conversa && c.status !== json.conversa.status) {
                c.status = json.conversa.status;
                aplicarEstadoConversa(c);
                renderizarLista();
            }
            // ao ler mensagens, o contador da lista e do menu precisa baixar
            if (primeiraVez || chegouDoOutro) carregarLista();
        } catch (erro) {
            console.error('Erro ao buscar mensagens:', erro);
            if (primeiraVez) avisoNaConversa('Não foi possível carregar as mensagens.');
        }
    }

    function abrir(id) {
        const c = conversas.find(x => x.id === id);
        if (!c) return;

        ativaId = id;
        ultimoId = 0;
        ultimoDia = '';
        mensagensEl.replaceChildren();

        vazioSelecione.hidden = true;
        conversaEl.hidden = false;
        layout.classList.add('conversa-aberta');

        cabTitulo.textContent = c.titulo;
        cabInterlocutor.textContent = c.interlocutor;
        cabAvatar.textContent = c.sou_representante ? iniciais(c.interlocutor) : 'PR';
        cabAvatar.classList.toggle('prefeitura', !c.sou_representante);
        cabLink.dataset.denuncia = c.denuncia_id;
        aplicarEstadoConversa(c);
        campoTexto.value = '';
        ajustarCampo();

        renderizarLista();
        buscarNovas(true);
        if (!campoTexto.disabled) campoTexto.focus({ preventScroll: true });
    }

    function fecharConversa() {
        ativaId = null;
        layout.classList.remove('conversa-aberta');
        conversaEl.hidden = true;
        vazioSelecione.hidden = false;
        renderizarLista();
    }

    // ---------- enviar ----------
    function ajustarCampo() {
        campoTexto.style.height = 'auto';
        campoTexto.style.height = Math.min(campoTexto.scrollHeight, 120) + 'px';
        const tamanho = campoTexto.value.length;
        campoLimite.hidden = tamanho < 800;
        campoLimite.textContent = tamanho + '/1000';
    }

    async function enviar(evento) {
        evento.preventDefault();
        const texto = campoTexto.value.trim();
        if (!texto || !ativaId || enviando || campoTexto.disabled) return;

        const idDaConversa = ativaId;
        const agoraLocal = hoje() + ' ' + doisDigitos(new Date().getHours()) + ':' + doisDigitos(new Date().getMinutes());

        // aparece na hora (esmaecida) e é confirmada pelo servidor
        const bolha = adicionarMensagem({ minha: true, texto: texto, enviada_em: agoraLocal });
        bolha.classList.add('enviando');
        rolarParaOFim();
        campoTexto.value = '';
        ajustarCampo();

        enviando = true;
        try {
            const json = await chamarPost('chat/enviar', { conversa: idDaConversa, texto: texto });
            if (!json.resposta) throw new Error(json.mensagem || 'Não foi possível enviar a mensagem');

            bolha.classList.remove('enviando');
            bolha.dataset.id = json.dados.id;
            bolha.querySelector('[data-campo="hora"]').textContent = horaDe(json.dados.enviada_em);
            if (idDaConversa === ativaId) ultimoId = Math.max(ultimoId, json.dados.id);
            carregarLista();
        } catch (erro) {
            bolha.remove();
            campoTexto.value = texto;      // devolve o texto para a pessoa tentar de novo
            ajustarCampo();
            avisoNaConversa(erro.message || 'Não foi possível enviar a mensagem.');
            buscarNovas(false);            // pode ter sido encerrada no meio tempo
        } finally {
            enviando = false;
        }
    }

    async function encerrar() {
        if (!ativaId) return;
        if (!window.confirm('Encerrar esta conversa? Depois disso ninguém poderá enviar novas mensagens.')) return;

        btnEncerrar.disabled = true;
        try {
            const json = await chamarPost('chat/encerrar', { conversa: ativaId });
            if (!json.resposta) throw new Error(json.mensagem || 'Não foi possível encerrar a conversa');
            const c = conversas.find(x => x.id === ativaId);
            if (c) {
                c.status = 'encerrada';
                aplicarEstadoConversa(c);
            }
            renderizarLista();
            carregarLista();
        } catch (erro) {
            avisoNaConversa(erro.message || 'Não foi possível encerrar a conversa.');
        } finally {
            btnEncerrar.disabled = false;
        }
    }

    // ---------- eventos ----------
    listaEl.addEventListener('click', evento => {
        const item = evento.target.closest('.chat-conversa-item[data-id]');
        if (item) abrir(Number(item.dataset.id));
    });

    if (filtros) {
        filtros.querySelectorAll('.notif-chip').forEach(chip => {
            chip.addEventListener('click', () => {
                filtros.querySelectorAll('.notif-chip').forEach(c => c.classList.toggle('ativo', c === chip));
                filtroAtual = chip.dataset.filtro;
                renderizarLista();
            });
        });
    }

    form.addEventListener('submit', enviar);
    campoTexto.addEventListener('input', ajustarCampo);
    campoTexto.addEventListener('keydown', evento => {
        // Enter envia; Shift+Enter quebra a linha
        if (evento.key === 'Enter' && !evento.shiftKey) {
            evento.preventDefault();
            form.requestSubmit();
        }
    });

    btnEncerrar.addEventListener('click', encerrar);
    btnVoltar.addEventListener('click', fecharConversa);
    cabLink.addEventListener('click', evento => {
        evento.preventDefault();
        if (cabLink.dataset.denuncia) irParaDetalhe(cabLink.dataset.denuncia);
    });

    if (linkMenu) linkMenu.addEventListener('click', () => carregarLista());

    // ---------- polling ----------
    async function ciclar() {
        if (document.hidden || !lerToken() || ocupado) return;
        ocupado = true;
        ciclo++;
        try {
            const abaVisivel = tela.classList.contains('ativa');
            if (abaVisivel) {
                if (ativaId && !enviando) await buscarNovas(false);
                if (ciclo % CICLOS_LISTA_ABA === 0) await carregarLista();
            } else if (ciclo % CICLOS_LISTA_FORA === 0) {
                await carregarLista();   // só para o contador do menu
            }
        } finally {
            ocupado = false;
        }
    }

    setInterval(ciclar, INTERVALO_MS);

    // ---------- botão "Responder" (painel do representante) ----------
    async function iniciar(idDenuncia) {
        if (!lerToken()) {
            irParaLogin('Faça login para continuar.');
            return;
        }

        const json = await chamarPost('chat/iniciar', { denuncia: idDenuncia });
        if (!json.resposta) {
            window.alert(json.mensagem || 'Não foi possível iniciar a conversa.');
            return;
        }

        await carregarLista();
        if (linkMenu) linkMenu.click();          // troca para a aba Mensagens
        abrir(json.dados.conversa_id);
    }

    window.RelateChat = { iniciar };

    // o texto de apoio da aba muda conforme o perfil
    if (perfilLogado() === 'representante') {
        const apoio = tela.querySelector('.mural-descricao p');
        if (apoio) apoio.textContent = 'Converse com os moradores sobre as denúncias da sua cidade.';
    }

    // primeira carga: preenche o contador do menu
    carregarLista();
})();
/**
 * Sabemos que start-app.js cuida da parte do token
 * precisamos garantir que nessa pagina o acesso seja bloqueado para visitantes
 * precisamos garantir que os dados recebidos do servidor sejam persistidos
 * precisamos editar os dados da pagina com os campos recebidos
*/

const DEBUG = true;
const RAIZ = "../app-web/";
// função para desconectar 
async function desconectar() {
     // se não existe um token ou um perfil, desconecte o usuario
    await fetch("../user/logout", {
        method: "GET"
    });
}

async function obterDados() {
    let token = localStorage.getItem("token");
    let perfil = localStorage.getItem("perfil");
    let nome = localStorage.getItem("nome");
    let sobrenome = localStorage.getItem("sobrenome");
    let email = localStorage.getItem("email");

    let dados_post = new URLSearchParams({
        'metodo': "POST",
        'perfil': perfil,
        'nome': nome,
        'sobrenome': sobrenome,
        'email': email,
        'token': token,
        'lembrar': true
    });

    if (perfil != "visitante"){
        dados_post.append("cidade", localStorage.getItem("cidade"));
        dados_post.append("estado", localStorage.getItem("estado"));
    }

    if (perfil ==  "representante"){
        dados_post.append("orgao", localStorage.getItem("orgao"));
        dados_post.append("cargo", localStorage.getItem("cargo"));
    }

    let servidor = await fetch(RAIZ + "user", {
        method: "POST",
        body: dados_post
    });

    let resposta = await servidor.json();
    if (DEBUG){
        console.log("\n-> Processo POST concluido: " + JSON.stringify(resposta));
    }

    return resposta;
}

let token = localStorage.getItem("token");
let perfil = localStorage.getItem("perfil");
if (!perfil || !token){
   
    await desconectar();
    localStorage.clear();
    alert("Faça login antes de acessar seu perfil");
    window.location.href = RAIZ + "login";
}

//solicita os dados de perfil para o servidor
if (perfil == "visitante"){
    // redireciona o visitante, essa não é area dele
    alert("Faça login antes de acessar um perfil");
    window.location.href = RAIZ + "login";
}

// obtem os dados do usuario e de denuncias do usuario
let dados = await obterDados();
if (DEBUG){
    console.log("\n-> Dados obtidos: " + JSON.stringify(dados));
}

if (dados.resposta){
    if (DEBUG){
       console.log("\n-> Resposta : " + JSON.stringify(dados.dados));
       console.log("\n-> Dados usuario: " + JSON.stringify(dados.dados.usuario));
       console.log("\n-> Dados denuncia: " + JSON.stringify(dados.dados.denuncias));
       console.log("\n\n\t\t--- LISTANDO DADOS ---\n\n");
    }

    // armazena os dados do usuario no localstorage
    
    let dados_usuario = dados.dados.usuario;
    if (DEBUG){
        console.log("\tListando dados de usuario\n");
        if (dados_usuario.length == 0){
            console.log("Lista de usuario vazia");
        }
    }
    Object.entries(dados_usuario).forEach(([chave, valor]) => {
        if (DEBUG){
            console.log(`Chave: "${chave}": Valor: "${valor}"`);
        }
        localStorage.setItem(chave, valor);
    })

    let dados_denuncias = dados.dados.denuncias;
    if (DEBUG){
        console.log("\tListando dados de denuncias\n");
        if (dados_denuncias.length == 0){
            console.log("Lista de denuncias vazia");
        }
    }
    
    if (dados_denuncias.length > 0){
        Object.entries(dados_denuncias).forEach(([chave, valor]) => {
            if (DEBUG){
                console.log(`Chave: "${chave}": Valor: "${valor}"`);
            }
            localStorage.setItem(chave, valor);
        })
    } else {
        localStorage.setItem("denuncias", []);
    }

}


/* ==========================================================
   PERFIL - RelateSBC  (perfil.html)
   - Exige usuário logado (senão vai para o login)
   - Preenche nome, tipo de perfil e local com os dados do banco (./user/dados)
   - Lista só as denúncias do próprio usuário (./denuncia/minhas)
   ========================================================== */

(function () {
    'use strict';

    // Lido agora (durante a execução do script) para montar as URLs corretamente
    const SRC_DO_SCRIPT = document.currentScript ? document.currentScript.src : '';

    // Raiz do projeto: app/view/scripts/perfil.js -> ../../../
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

    const ROTULO_PERFIL = { 'municipe': 'MUNÍCIPE', 'representante': 'REPRESENTANTE' };

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
        const texto = await resposta.text();
        try {
            return JSON.parse(texto);
        } catch (erro) {
            console.error('Resposta do servidor (não é JSON):', texto);
            throw new Error('Resposta inválida do servidor');
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

    /* ---------- Dados do usuário (tb_usuario + tb_municipe/tb_representante) ---------- */

    async function carregarDados() {
        try {
            const json = await buscarJson('user/dados');

            if (!json.resposta) {
                console.error('Erro ao obter dados do perfil:', json);
                mostrarMensagem(json.mensagem || 'Não foi possível carregar o seu perfil.', true);
                return;
            }

            const usuario = json.dados;
            elNome.textContent = usuario.nome + ' ' + usuario.sobrenome;
            elBadge.textContent = ROTULO_PERFIL[usuario.perfil] || String(usuario.perfil).toUpperCase();

            const partes = [];
            if (usuario.orgao) partes.push(usuario.orgao + (usuario.cargo ? ' — ' + usuario.cargo : ''));
            if (usuario.cidade) partes.push(formatarCidade(usuario.cidade) + (usuario.estado ? ' / ' + usuario.estado : ''));
            elLocal.textContent = partes.join('  |  ');
        } catch (erro) {
            console.error('Erro de rede ao carregar o perfil:', erro);
            mostrarMensagem('Erro de conexão ao carregar o seu perfil.');
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

        return linha;
    }

    async function carregarDenuncias() {
        if (!elLista || !elModelo) return;

        try {
            const json = await buscarJson('denuncia/minhas');

            if (!json.resposta) {
                console.error('Erro ao listar denúncias do perfil:', json);
                mostrarMensagem(json.mensagem || 'Não foi possível carregar as suas denúncias.', true);
                return;
            }

            const denuncias = json.dados || [];
            elLista.innerHTML = '';

            if (denuncias.length === 0) {
                mostrarMensagem('Você ainda não registrou nenhuma denúncia.');
                return;
            }

            const fragmento = document.createDocumentFragment();
            denuncias.forEach(function (denuncia) { fragmento.appendChild(montarLinha(denuncia)); });
            elLista.appendChild(fragmento);
        } catch (erro) {
            console.error('Erro de rede ao carregar as denúncias do perfil:', erro);
            mostrarMensagem('Erro de conexão ao carregar as suas denúncias.');
        }
    }

    /* ---------- Pesquisar (filtra a lista já carregada) ---------- */

    if (elBusca) {
        elBusca.addEventListener('input', function () {
            const termo = elBusca.value.trim().toLowerCase();
            elLista.querySelectorAll('li').forEach(function (linha) {
                linha.style.display = (!termo || (linha.dataset.busca || '').indexOf(termo) !== -1) ? '' : 'none';
            });
        });
    }

    if (btnNova) {
        btnNova.addEventListener('click', function () {
            window.location.href = urlApp('denuncia/criar');
        });
    }

    /* ---------- Início ---------- */

    carregarDados();
    carregarDenuncias();
})();

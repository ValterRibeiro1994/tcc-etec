/*
   EDITAR DENÚNCIA - RelateSBC  (editar-denuncia.html)
   - Exige usuário logado (token no localStorage)
   - Pega o id da denúncia na URL (?id=12)
   - Busca os dados em ./denuncia/obter?id=12 e preenche o formulário
   - Carrega as categorias do banco (./categoria/categorias)
   - Envia as alterações para ./denuncia/editar (POST) com o token
   Baseado no criar-denuncia.js (mesmas regras de campos, foto e prévia).
 */

(function () {
    'use strict';

    const SRC_DO_SCRIPT = document.currentScript ? document.currentScript.src : '';

    const PAGINA_MURAL = 'denuncia/mural';
    const PAGINA_LOGIN = 'login';

    const TAMANHO_MAXIMO_FOTO = 5 * 1024 * 1024; // 5 MB
    const TIPOS_FOTO = ['image/jpeg', 'image/png'];
    const TEXTO_FOTO_PADRAO = 'JPG ou PNG — tamanho máximo de 5MB';

    function urlApp(rota) {
        if (SRC_DO_SCRIPT) return new URL('../../../' + rota, SRC_DO_SCRIPT).href;
        return './' + rota;
    }

    /* ---------- Sessão: só usuário logado edita denúncia ---------- */

    // Lê o payload do JWT (a validação de verdade é feita no servidor)
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
        alert('Faça login para editar uma denúncia.');
        window.location.href = urlApp(PAGINA_LOGIN);
        return;
    }

    /* ---------- Elementos ---------- */

    const form        = document.getElementById('form-denuncia');
    const inputId     = document.getElementById('id-denuncia');
    const inputTitulo = document.getElementById('titulo-denuncia');
    const inputDescr  = document.getElementById('descricao-denuncia');
    const selectCateg = document.getElementById('categoria-denuncia');
    const selectStatus = document.getElementById('status-denuncia');
    const inputData   = document.getElementById('data-denuncia');
    const inputUf     = document.getElementById('estado-denuncia');
    const inputCidade = document.getElementById('cidade-denuncia');
    const inputCep    = document.getElementById('cep-denuncia');
    const inputRua    = document.getElementById('rua-denuncia');
    const inputNumero = document.getElementById('numero-denuncia');
    const inputCompl  = document.getElementById('complemento-denuncia');
    const inputBairro = document.getElementById('bairro-denuncia');
    const inputFoto   = document.getElementById('foto-denuncia');
    const fotoInfo    = document.getElementById('foto-info');
    const fotoAtual   = document.getElementById('foto-atual');
    const fotoAtualImg = document.getElementById('foto-atual-img');
    const previa      = document.getElementById('previa-logradouro');
    const caixaMsg    = document.getElementById('mensagem-formulario');
    const btnEnviar   = document.getElementById('btn-enviar');
    const btnCancelar = document.getElementById('btn-cancelar');

    if (!form) return;

    /* ---------- Mensagens ---------- */

    function mostrarMensagem(texto, tipo) {
        caixaMsg.textContent = texto;
        caixaMsg.className = 'alert alert-' + tipo;
        caixaMsg.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function limparMensagem() {
        caixaMsg.textContent = '';
        caixaMsg.className = 'alert d-none';
    }

    // Volta para onde o usuário estava
    function sairDaEdicao(atualizar) {
        try {
            if (window.opener && !window.opener.closed) {
                if (atualizar) window.opener.location.reload();
                window.close();
                return;
            }
        } catch (erroOpener) {}

        try {
            if (document.referrer && new URL(document.referrer).origin === window.location.origin) {
                window.location.href = document.referrer;
                return;
            }
        } catch (erroReferrer) {}

        window.location.href = urlApp(PAGINA_MURAL);
    }

    /* ---------- id da denúncia (vem da URL: ?id=12) ---------- */

    const idDenuncia = new URLSearchParams(window.location.search).get('id');
    if (!idDenuncia || !/^[1-9][0-9]*$/.test(idDenuncia)) {
        mostrarMensagem('Denúncia não informada. Volte e clique em Editar na denúncia desejada.', 'danger');
        btnEnviar.disabled = true;
        return;
    }
    inputId.value = idDenuncia;

    /* ---------- Categorias (tb_categoria) ---------- */

    async function carregarCategorias() {
        try {
            const resposta = await fetch(urlApp('categoria/categorias'), { method: 'GET' });
            const json = await resposta.json();
            const categorias = Array.isArray(json.dados) ? json.dados : [];

            selectCateg.innerHTML = '';

            if (!json.resposta || categorias.length === 0) {
                selectCateg.innerHTML = '<option value="" selected disabled>Nenhuma categoria disponível</option>';
                return;
            }

            const padrao = document.createElement('option');
            padrao.value = '';
            padrao.selected = true;
            padrao.disabled = true;
            padrao.textContent = 'Selecione uma categoria';
            selectCateg.appendChild(padrao);

            categorias.forEach(function (categoria) {
                const opcao = document.createElement('option');
                opcao.value = categoria.nome_categoria; 
                opcao.textContent = categoria.nome_categoria;
                selectCateg.appendChild(opcao);
            });
        } catch (erro) {
            console.error('Erro ao carregar categorias:', erro);
            selectCateg.innerHTML = '<option value="" selected disabled>Erro ao carregar categorias</option>';
        }
    }

    /* ---------- Campos: data, UF, CEP ---------- */

    // não permite escolher uma data futura
    const hoje = new Date();
    inputData.max = hoje.getFullYear() + '-' +
        String(hoje.getMonth() + 1).padStart(2, '0') + '-' +
        String(hoje.getDate()).padStart(2, '0');

    // UF: só letras, em maiúsculas
    inputUf.addEventListener('input', function () {
        inputUf.value = inputUf.value.replace(/[^a-zA-Z]/g, '').toUpperCase();
    });

    // CEP: só números, no formato 00000-000
    inputCep.addEventListener('input', function () {
        const numeros = inputCep.value.replace(/\D/g, '').slice(0, 8);
        inputCep.value = numeros.length > 5 ? numeros.slice(0, 5) + '-' + numeros.slice(5) : numeros;
    });

    /* ---------- Prévia do logradouro ---------- */
    // "Rua, número, complemento - bairro". Serve só para conferência.

    function atualizarPrevia() {
        const rua = inputRua.value.trim().replace(/\s+/g, ' ');
        const numero = inputNumero.value.trim();
        const complemento = inputCompl.value.trim().replace(/\s+/g, ' ');
        const bairro = inputBairro.value.trim();

        if (!rua || !numero || !bairro) {
            previa.textContent = 'preencha rua, número e bairro';
            return;
        }

        const partes = [rua, numero];
        if (complemento) partes.push(complemento);
        previa.textContent = partes.join(', ') + ' - ' + bairro;
    }

    [inputRua, inputNumero, inputCompl, inputBairro].forEach(function (campo) {
        campo.addEventListener('input', atualizarPrevia);
    });

    /* ---------- Foto ---------- */

    inputFoto.addEventListener('change', function () {
        const arquivo = inputFoto.files[0];
        if (!arquivo) {
            fotoInfo.textContent = TEXTO_FOTO_PADRAO;
            return;
        }

        if (TIPOS_FOTO.indexOf(arquivo.type) === -1) {
            mostrarMensagem('A imagem deve ser JPG ou PNG.', 'danger');
            inputFoto.value = '';
            fotoInfo.textContent = TEXTO_FOTO_PADRAO;
            return;
        }

        if (arquivo.size > TAMANHO_MAXIMO_FOTO) {
            mostrarMensagem('A imagem deve ter no máximo 5MB.', 'danger');
            inputFoto.value = '';
            fotoInfo.textContent = TEXTO_FOTO_PADRAO;
            return;
        }

        limparMensagem();
        fotoInfo.textContent = arquivo.name;
    });

    /* ---------- Preenche o formulário com a denúncia gravada ---------- */

    function preencherFormulario(denuncia) {
        inputTitulo.value = denuncia.titulo || '';
        inputDescr.value  = denuncia.descricao || '';
        inputData.value   = denuncia.data || '';
        inputUf.value     = denuncia.estado || '';
        inputCidade.value = denuncia.cidade || '';
        inputRua.value    = denuncia.rua || '';
        inputNumero.value = denuncia.numero || '';
        inputCompl.value  = denuncia.complemento || '';
        inputBairro.value = denuncia.bairro || '';
        inputCep.value    = denuncia.cep || '';

        selectStatus.value = denuncia.status || 'pendente';

        if (denuncia.imagem) {
            fotoAtualImg.src = denuncia.imagem;
            fotoAtual.classList.remove('d-none');
        }

        atualizarPrevia();
    }

    async function carregarDenuncia() {
        btnEnviar.disabled = true;

        try {
            const resposta = await fetch(urlApp('denuncia/obter?id=' + encodeURIComponent(idDenuncia)), {
                method: 'GET',
                headers: { 'Authorization': token }
            });

            const texto = await resposta.text();
            let json;
            try {
                json = JSON.parse(texto);
            } catch (erroJson) {
                console.error('Resposta do servidor (não é JSON):', texto);
                mostrarMensagem('Erro no servidor ao buscar a denúncia.', 'danger');
                return;
            }

            if (!json.resposta) {
                mostrarMensagem(json.mensagem || 'Não foi possível carregar a denúncia.', 'danger');
                return;
            }

            preencherFormulario(json.dados);
            btnEnviar.disabled = false;
        } catch (erro) {
            console.error('Erro ao carregar a denúncia:', erro);
            mostrarMensagem('Erro de conexão ao carregar a denúncia. Tente novamente.', 'danger');
        }
    }

    /* ---------- Envio ---------- */

    form.addEventListener('submit', async function (evento) {
        evento.preventDefault();
        limparMensagem();

        const dados = new FormData(form); // já leva o id-denuncia (campo oculto) e a foto, se escolhida
        dados.append('metodo', 'POST'); // o roteador exige essa chave

        let enviado = false;
        btnEnviar.disabled = true;
        const textoOriginal = btnEnviar.textContent;
        btnEnviar.textContent = 'Salvando...';

        try {
            const resposta = await fetch(urlApp('denuncia/editar'), {
                method: 'POST',
                headers: { 'Authorization': token },
                body: dados
            });

            const texto = await resposta.text();
            let json;
            try {
                json = JSON.parse(texto);
            } catch (erroJson) {
                console.error('Resposta do servidor (não é JSON):', texto);
                const semTags = texto.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
                mostrarMensagem('Erro no servidor: ' + semTags.slice(0, 300), 'danger');
                return;
            }

            if (!json.resposta) {
                mostrarMensagem(json.mensagem || 'Não foi possível salvar as alterações.', 'danger');
                return;
            }

            enviado = true;
            mostrarMensagem('Denúncia atualizada com sucesso!', 'success');
            setTimeout(function () { sairDaEdicao(true); }, 1200);
        } catch (erro) {
            console.error('Erro ao salvar a denúncia:', erro);
            mostrarMensagem('Erro de conexão ao salvar a denúncia. Tente novamente.', 'danger');
        } finally {
            if (!enviado) {
                btnEnviar.disabled = false;
                btnEnviar.textContent = textoOriginal;
            }
        }
    });

    btnCancelar.addEventListener('click', function () {
        sairDaEdicao(false);
    });

    carregarCategorias().then(carregarDenuncia);
})();

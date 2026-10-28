/* ==========================================================
   CRIAR DENÚNCIA - RelateSBC
   - Exige usuário logado (token no localStorage)
   - Carrega as categorias do banco (./categoria/categorias)
   - Mostra a prévia do logradouro (rua, número, complemento - bairro)
   - Envia o formulário para ./denuncia/criar (POST) com o token
   ========================================================== */

(function () {
    'use strict';

    // ADICIONADO: fora do modal (iframe) a página só redireciona para o mural (ver <head> do HTML).
    // Sem isso, quem não está logado veria o alert/redirecionamento antes do redirect acontecer.
    if (window.self === window.top) return;

    // Lido agora (durante a execução do script) para montar as URLs corretamente
    const SRC_DO_SCRIPT = document.currentScript ? document.currentScript.src : '';

    // Página para onde o usuário vai depois de enviar (ou cancelar)
    // ALTERADO: rota oficial do mural (antes 'teste/homemural')
    const PAGINA_MURAL = 'denuncia/mural';
    const PAGINA_LOGIN = 'login';

    const TAMANHO_MAXIMO_FOTO = 5 * 1024 * 1024; // 5 MB
    const TIPOS_FOTO = ['image/jpeg', 'image/png'];

    // Raiz do projeto: app/view/scripts/criar-denuncia.js -> ../../../
    function urlApp(rota) {
        if (SRC_DO_SCRIPT) return new URL('../../../' + rota, SRC_DO_SCRIPT).href;
        return './' + rota;
    }

    // Se esta página estiver dentro do modal do mural (iframe), avisa a página de origem.
    // Retorna true quando o aviso foi enviado (nesse caso quem fecha é o mural).
    function avisarModal(tipo) {
        if (window.self === window.top) return false;
        window.parent.postMessage({ tipo: tipo }, window.location.origin);
        return true;
    }
    /* ---------- Sessão: só usuário logado cria denúncia ---------- */

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
        alert('Faça login para registrar uma denúncia.');
        // ALTERADO: navega a janela principal; antes a tela de login abria DENTRO do modal
        window.top.location.href = urlApp(PAGINA_LOGIN);
        return;
    }

    /* ---------- Elementos ---------- */

    const form        = document.getElementById('form-denuncia');
    const selectCateg = document.getElementById('categoria-denuncia');
    const inputData   = document.getElementById('data-denuncia');
    const inputUf     = document.getElementById('estado-denuncia');
    const inputCep    = document.getElementById('cep-denuncia');
    const inputRua    = document.getElementById('rua-denuncia');
    const inputNumero = document.getElementById('numero-denuncia');
    const inputCompl  = document.getElementById('complemento-denuncia');
    const inputBairro = document.getElementById('bairro-denuncia');
    const inputFoto   = document.getElementById('foto-denuncia');
    const fotoInfo    = document.getElementById('foto-info');
    const previa      = document.getElementById('previa-logradouro');
    const caixaMsg    = document.getElementById('mensagem-formulario');
    const btnEnviar   = document.getElementById('btn-enviar');
    const btnCancelar = document.getElementById('btn-cancelar');

    if (!form) return;

    /* ---------- Mensagens ---------- */

    function mostrarMensagem(texto, tipo) {
        caixaMsg.textContent = texto;
        caixaMsg.className = 'alert alert-' + tipo; // alert-danger | alert-success
        caixaMsg.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function limparMensagem() {
        caixaMsg.textContent = '';
        caixaMsg.className = 'alert d-none';
    }

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
                opcao.value = categoria.nome_categoria; // o banco usa o nome como chave estrangeira
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
    // Mesmo formato montado no servidor (Endereco::getLogradouro):
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
            fotoInfo.textContent = 'JPG ou PNG — tamanho máximo de 5MB';
            return;
        }

        if (TIPOS_FOTO.indexOf(arquivo.type) === -1) {
            mostrarMensagem('A imagem deve ser JPG ou PNG.', 'danger');
            inputFoto.value = '';
            fotoInfo.textContent = 'JPG ou PNG — tamanho máximo de 5MB';
            return;
        }

        if (arquivo.size > TAMANHO_MAXIMO_FOTO) {
            mostrarMensagem('A imagem deve ter no máximo 5MB.', 'danger');
            inputFoto.value = '';
            fotoInfo.textContent = 'JPG ou PNG — tamanho máximo de 5MB';
            return;
        }

        limparMensagem();
        fotoInfo.textContent = arquivo.name;
    });

    /* ---------- Envio ---------- */

    form.addEventListener('submit', async function (evento) {
        evento.preventDefault();
        limparMensagem();

        const dados = new FormData(form);
        dados.append('metodo', 'POST'); // o roteador exige essa chave

        let enviado = false; // depois de salvar, o botão continua desativado (evita enviar duas vezes)
        btnEnviar.disabled = true;
        const textoOriginal = btnEnviar.textContent;
        btnEnviar.textContent = 'Enviando...';

        try {
            const resposta = await fetch(urlApp('denuncia/criar'), {
                method: 'POST',
                headers: { 'Authorization': token },
                body: dados
            });

            // Lê como texto primeiro: se o PHP devolver um erro em HTML (e não JSON),
            // mostramos o que o servidor respondeu em vez de um erro genérico.
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
                mostrarMensagem(json.mensagem || 'Não foi possível registrar a denúncia.', 'danger');
                return;
            }

            enviado = true;
            mostrarMensagem('Denúncia registrada com sucesso! Redirecionando para o mural...', 'success');
            setTimeout(function () {

                 // dentro do modal do mural, quem atualiza e fecha é o mural
                if (avisarModal('denuncia:criada')) return;
                // ALTERADO: se o formulário foi aberto em popup pelo mural, atualiza o mural e fecha o popup
                try {
                    if (window.opener && !window.opener.closed) {
                        window.opener.location.reload();
                        window.close();
                        return;
                    }
                } catch (erroOpener) { /* sem acesso à janela de origem: segue para o redirecionamento */ }
                window.location.href = urlApp(PAGINA_MURAL);
            }, 1500);
        } catch (erro) {
            console.error('Erro ao enviar denúncia:', erro);
            mostrarMensagem('Erro de conexão ao enviar a denúncia. Tente novamente.', 'danger');
        } finally {
            if (!enviado) {
                btnEnviar.disabled = false;
                btnEnviar.textContent = textoOriginal;
            }
        }
    });

    btnCancelar.addEventListener('click', function () {
         // dentro do modal do mural, apenas pede para o mural fechar o modal
        if (avisarModal('denuncia:fechar')) return;
        // ALTERADO: em popup, apenas fecha; fora dele, volta para o mural
        if (window.opener && !window.opener.closed) {
            window.close();
            return;
        }
        window.location.href = urlApp(PAGINA_MURAL);
    });

    /* ---------- Início ---------- */

    carregarCategorias();
})();

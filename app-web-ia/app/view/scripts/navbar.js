
function menuAbrir() {
    let menuMobile = document.querySelector('.mobile-menu');
    if (!menuMobile) return;
    menuMobile.classList.toggle('abrir');
}

function abrirSidebar() {
    const sidebar = document.querySelector('.menu-lateral');
    if (sidebar) sidebar.classList.add('aberta');
}

function fecharSidebar() {
    const sidebar = document.querySelector('.menu-lateral');
    if (sidebar) sidebar.classList.remove('aberta');
}

// ---------- logout ----------

(function () {
    'use strict';

    const SRC_DO_SCRIPT = document.currentScript ? document.currentScript.src : '';

    function urlApp(rota) {
        if (SRC_DO_SCRIPT) return new URL('../../../' + rota, SRC_DO_SCRIPT).href;
        return './' + rota;
    }

    const botoesLogout = [
        document.getElementById('logout-btn'),
        document.getElementById('logout-btn-mob')
    ];

    let logado = false;
    try {
        const token = localStorage.getItem('token');
        if (token) {
            const parte = token.split('.')[1].replace(/-/g, '+').replace(/_/g, '/');
            const dados = JSON.parse(atob(parte));
            logado = ['municipe', 'representante', 'administrador'].includes(dados.perfil)
                && dados.exp * 1000 > Date.now();
        }
    } catch (erro) {
        // Um token inválido deixa o menu no modo visitante.
    }

    const botoesVisitante = document.querySelectorAll(
        '#btn-entrar, #btn-cadastro, #btn-entrar-mob, #btn-cadastro-mob'
    );
    for (const botao of botoesVisitante) {
        botao.style.display = logado ? 'none' : '';
    }

    const grupos = document.querySelectorAll('#usuario-logado, #usuario-logado-mob');
    for (const grupo of grupos) {
        grupo.style.display = logado ? '' : 'none';
    }

    for (const botao of botoesLogout) {
        if (botao) botao.style.display = logado ? '' : 'none';
    }

    botoesLogout.forEach(function (botao) {
        if (!botao) return;

        botao.addEventListener('click', async function (evento) {
            evento.preventDefault();

            try {
                botao.disabled = true;
                const token = localStorage.getItem('token');
                if (!token) {
                    ['token', 'perfil', 'nome', 'sobrenome', 'email', 'cidade', 'estado', 'orgao', 'cargo', 'denuncias']
                        .forEach(chave => localStorage.removeItem(chave));
                    console.error('Não foi encontrado um token para encerrar a sessão.');
                    return;
                }

                const resposta = await fetch(urlApp('user/logout'), {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Authorization': token }
                });

                if (!resposta.ok) throw new Error('Falha ao encerrar a sessão.');
                const json = await resposta.json();

                if (json.resposta === true) {
                    ['token', 'perfil', 'nome', 'sobrenome', 'email', 'cidade', 'estado', 'orgao', 'cargo', 'denuncias']
                        .forEach(chave => localStorage.removeItem(chave));
                    alert('Usuario Desconectado');
                    window.location.href = urlApp('home');
                } else {
                    throw new Error(json.mensagem || 'Não foi possível encerrar a sessão.');
                }
            } catch (erro) {
                console.error('Não foi possível encerrar a sessão.', erro);
                alert(erro.message || 'Não foi possível encerrar a sessão. Tente novamente.');
            } finally {
                botao.disabled = false;
            }
        });
    });
})();

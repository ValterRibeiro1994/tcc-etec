
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
                const token = localStorage.getItem('token');
                if (!token) {
                    localStorage.removeItem('token');
                    console.error('Não foi encontrado um token para encerrar a sessão.');
                    return;
                }

                const resposta = await fetch(urlApp('user/logout'), {
                    method: 'GET',
                    headers: { 'Authorization': token }
                });

                const json = await resposta.json();
                localStorage.removeItem('token');

                if (json.resposta === true) {
                    alert('Usuario Desconectado');
                    window.location.href = urlApp('home');
                } else {
                    console.error(JSON.stringify(json));
                }
            } catch (erro) {
                console.error(erro);
            }
        });
    });
})();

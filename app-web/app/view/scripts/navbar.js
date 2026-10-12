/* ==========================================================
   NAVBAR - RelateSBC
   - menuAbrir / abrirSidebar / fecharSidebar (usadas pelos onclick do HTML)
   - botão Sair (logout)
   ALTERADO: as URLs agora são montadas a partir da posição deste arquivo,
   então funcionam em qualquer página, não importa em que pasta ela esteja
   (antes "./user/logout" e "./home" quebravam nas páginas de 2 níveis).
   Os botões Entrar/Cadastre-se já são links <a href>, não precisam de JavaScript
   (removidos os alert de teste e o redirecionamento duplicado).
   ========================================================== */

// ---------- funções globais (chamadas pelos onclick do HTML) ----------

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

    // Lido agora (durante a execução do script) para montar as URLs corretamente
    const SRC_DO_SCRIPT = document.currentScript ? document.currentScript.src : '';

    // Raiz do projeto: app/view/scripts/navbar.js -> ../../../
    function urlApp(rota) {
        if (SRC_DO_SCRIPT) return new URL('../../../' + rota, SRC_DO_SCRIPT).href;
        return './' + rota;
    }

    const botoesLogout = [
        document.getElementById('logout-btn'),
        document.getElementById('logout-btn-mob')
    ];

    botoesLogout.forEach(function (botao) {
        if (!botao) return;

        botao.addEventListener('click', async function (evento) {
            evento.preventDefault();

            try {
                const token = localStorage.getItem('token');
                if (!token) {
                    localStorage.clear();
                    console.error('Não foi encontrado um token para encerrar a sessão.');
                    return;
                }

                const resposta = await fetch(urlApp('user/logout'), {
                    method: 'GET',
                    headers: { 'Authorization': token }
                });

                const json = await resposta.json();
                localStorage.clear();

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

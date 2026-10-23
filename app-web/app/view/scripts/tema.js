/*
 * Tema claro / escuro - carregue este arquivo no <head> (sem defer) para a página
 * já abrir no tema certo, sem piscar branco.
 *
 * - Lê a escolha salva em localStorage ('tema' = 'claro' | 'escuro') e aplica no <html>.
 * - Qualquer <input type="checkbox" id="toggle-tema"> da página liga/desliga o tema escuro.
 * - Se o tema mudar em outra aba, esta aba acompanha.
 */
(function () {
    'use strict';

    var html = document.documentElement;

    function lerTema() {
        try { return localStorage.getItem('tema') === 'escuro' ? 'escuro' : 'claro'; }
        catch (e) { return 'claro'; }
    }

    function aplicar(tema) {
        if (tema === 'escuro') {
            html.setAttribute('data-tema', 'escuro');
            html.setAttribute('data-bs-theme', 'dark');
        } else {
            html.removeAttribute('data-tema');
            html.setAttribute('data-bs-theme', 'light');
        }
    }

    function salvar(tema) {
        try { localStorage.setItem('tema', tema); } catch (e) {}
    }

    function sincronizarControles() {
        var escuro = html.getAttribute('data-tema') === 'escuro';
        var chk = document.getElementById('toggle-tema');
        if (chk) chk.checked = escuro;
        // botões da tela de configurações (home-mural) continuam funcionando
        document.querySelectorAll('#config-tema .config-opcao').forEach(function (b) {
            b.classList.toggle('ativo', b.dataset.tema === (escuro ? 'escuro' : 'claro'));
        });
    }

    aplicar(lerTema());

    document.addEventListener('DOMContentLoaded', function () {
        sincronizarControles();
        var chk = document.getElementById('toggle-tema');
        if (chk) {
            chk.addEventListener('change', function () {
                var tema = chk.checked ? 'escuro' : 'claro';
                aplicar(tema);
                salvar(tema);
            });
        }
    });

    window.addEventListener('storage', function (e) {
        if (e.key === 'tema') {
            aplicar(lerTema());
            sincronizarControles();
        }
    });
})();

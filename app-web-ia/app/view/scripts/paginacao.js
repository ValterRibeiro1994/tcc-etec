window.RelatePaginacao = {
    mostrar(id, info, irPara) {
        const area = document.getElementById(id);
        if (!area) return;

        area.replaceChildren();
        if (!info) return;

        const pagina = Number(info.pagina) || 1;
        const total = Number(info.total) || 0;
        const paginas = Number(info.total_paginas) || 1;
        const quantidade = Number(info.por_pagina) || 10;

        const inicio = total ? (pagina - 1) * quantidade + 1 : 0;
        const fim = Math.min(pagina * quantidade, total);

        const resumo = document.createElement('span');
        resumo.className = 'paginacao-resumo';
        resumo.textContent =
            `Exibindo ${inicio}–${fim} de ${total} denúncias`;

        area.appendChild(resumo);

        if (paginas <= 1) return;

        const botoes = document.createElement('div');
        botoes.className = 'paginacao-botoes';

        function criar(rotulo, destino, desativado = false, atual = false) {
            const botao = document.createElement('button');

            botao.type = 'button';
            botao.textContent = rotulo;
            botao.disabled = desativado || atual;
            botao.className =
                'paginacao-btn' + (atual ? ' atual' : '');

            if (atual) {
                botao.setAttribute('aria-current', 'page');
            }

            if (!botao.disabled) {
                botao.addEventListener('click', () => irPara(destino));
            }

            botoes.appendChild(botao);
        }

        criar('Anterior', pagina - 1, pagina === 1);

        const primeira = Math.max(1, pagina - 2);
        const ultima = Math.min(paginas, pagina + 2);

        for (let n = primeira; n <= ultima; n++) {
            criar(String(n), n, false, n === pagina);
        }

        criar('Próxima', pagina + 1, pagina === paginas);

        area.appendChild(botoes);
    }
};
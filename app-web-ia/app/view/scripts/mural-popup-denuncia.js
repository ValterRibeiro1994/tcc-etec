 /* ---------- Modal do formulário de denúncia ---------- */
let modalDenuncia = null;
let focoAnteriorModal = null;

function teclaModalDenuncia(evento) {
    if (evento.key === 'Escape') fecharModalDenuncia();
}

function fecharModalDenuncia() {
    if (!modalDenuncia) return;

    const modal = modalDenuncia;
    modalDenuncia = null;

    modal.classList.remove('aberto');
    document.body.classList.remove('modal-denuncia-aberto');
    document.removeEventListener('keydown', teclaModalDenuncia);
    setTimeout(function () { modal.remove(); }, 200);

    if (focoAnteriorModal && focoAnteriorModal.focus) focoAnteriorModal.focus();
}

function abrirModalDenuncia(url) {
    if (modalDenuncia) return;
    focoAnteriorModal = document.activeElement;

    const fundo = document.createElement('div');
    fundo.className = 'modal-denuncia';
    fundo.setAttribute('role', 'dialog');
    fundo.setAttribute('aria-modal', 'true');
    fundo.setAttribute('aria-label', 'Registrar uma denúncia');

    const card = document.createElement('div');
    card.className = 'modal-denuncia-card';

    const botaoFechar = document.createElement('button');
    botaoFechar.type = 'button';
    botaoFechar.className = 'modal-denuncia-fechar';
    botaoFechar.setAttribute('aria-label', 'Fechar');
    botaoFechar.textContent = '\u00D7';
    botaoFechar.addEventListener('click', fecharModalDenuncia);

    const quadro = document.createElement('iframe');
    quadro.className = 'modal-denuncia-frame';
    quadro.title = 'Formulário de denúncia';
    quadro.addEventListener('load', function () { card.classList.add('pronto'); });
    quadro.src = url;

    card.append(botaoFechar, quadro);
    fundo.appendChild(card);
    document.body.appendChild(fundo);
    document.body.classList.add('modal-denuncia-aberto');
    document.addEventListener('keydown', teclaModalDenuncia);

    requestAnimationFrame(function () { fundo.classList.add('aberto'); });
    botaoFechar.focus();

    modalDenuncia = fundo;
}

// Respostas do formulário (iframe): denúncia criada ou cancelada
window.addEventListener('message', function (evento) {
    if (evento.origin !== window.location.origin || !evento.data) return;

    if (evento.data.tipo === 'denuncia:fechar') {
        fecharModalDenuncia();
    } else if (evento.data.tipo === 'denuncia:criada') {
        fecharModalDenuncia();
        window.location.reload(); // atualiza o mural com a nova denúncia
    }
});
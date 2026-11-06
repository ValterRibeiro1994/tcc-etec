
document.addEventListener('DOMContentLoaded', function () {

    const formulario = document.getElementById('form-contato');
    const mensagem = document.getElementById('contato-mensagem');
    const contador = document.getElementById('contato-contador');
    const aviso = document.getElementById('contato-msg');
    const botao = document.getElementById('contato-enviar');

    if (!formulario) return;

    function mostrarAviso(texto, sucesso) {
        aviso.textContent = texto;
        aviso.hidden = false;

        aviso.classList.remove('ok', 'erro');
        aviso.classList.add(sucesso ? 'ok' : 'erro');
    }

    mensagem.addEventListener('input', function () {
        contador.textContent = mensagem.value.length + '/1000';
    });

    formulario.addEventListener('submit', async function (evento) {

        evento.preventDefault();

        const nome = document.getElementById('contato-nome').value.trim();
        const email = document.getElementById('contato-email').value.trim();
        const assunto = document.getElementById('contato-assunto').value.trim();
        const texto = mensagem.value.trim();

        if (!nome || !email || !assunto || !texto) {
            mostrarAviso('Preencha todos os campos obrigatórios.', false);
            return;
        }

        botao.disabled = true;
        aviso.hidden = true;

        try {
            const resposta = await fetch(formulario.action, {
                method: 'POST',
                body: new FormData(formulario)
            });

            const dados = await resposta.json();

            mostrarAviso(dados.mensagem, dados.resposta);

            if (dados.resposta) {
                formulario.reset();
                contador.textContent = '0/1000';
            }

        } catch (erro) {
            console.error('Erro no contato:', erro);

            mostrarAviso(
                'Erro ao conectar com o servidor.',
                false
            );
        }

        botao.disabled = false;
    });
});

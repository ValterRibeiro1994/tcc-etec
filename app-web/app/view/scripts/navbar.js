const btnCadastro = document.getElementById('btn-cadastro');
const btnEntrar = document.getElementById('btn-entrar');

// BOTÃO CADASTRE-SE -> Vai para a escolha de CADASTRO
if (btnCadastro) {
    btnCadastro.addEventListener('click', function () {
        window.location.href = 'seleciona-cadastro.html';
    });
}

// BOTÃO ENTRAR -> Vai para a escolha de LOGIN
if (btnEntrar) {
    btnEntrar.addEventListener('click', function () {
        window.location.href = 'seleciona-login.html';
    });
}
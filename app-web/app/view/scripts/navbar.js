const btnCadastro = document.getElementById('btn-cadastro');
const btnEntrar = document.getElementById('btn-entrar');

// BOTÃO CADASTRE-SE -> Vai para a escolha de CADASTRO
if (btnCadastro) {
    btnCadastro.addEventListener('click', function () {
        window.location.href = 'cadastro';
    });
}

// BOTÃO ENTRAR -> Vai para a escolha de LOGIN
if (btnEntrar) {
    btnEntrar.addEventListener('click', function () {
        window.location.href = 'login';
    });
}



function menuAbrir() {
    let menuMobile = document.querySelector('.mobile-menu');
    if (menuMobile.classList.contains('abrir')){
        menuMobile.classList.remove('abrir');
    } else{
        menuMobile.classList.add('abrir');
    }
}
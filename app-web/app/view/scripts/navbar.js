const btnCadastro = document.getElementById('btn-cadastro');
const btnEntrar = document.getElementById('btn-entrar');
const logoutbtn = document.getElementById('logout-btn');

// BOTÃO CADASTRE-SE -> Vai para a escolha de CADASTRO
if (btnCadastro) {
    btnCadastro.addEventListener('click', function () {
        alert("Cadastro apertado");
        window.location.href = "./cadastro";
    });
}

// BOTÃO ENTRAR -> Vai para a escolha de LOGIN
if (btnEntrar) {
    btnEntrar.addEventListener('click', function () {
        alert("Login apertado")
        window.location.href = "./login";
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


function abrirSidebar() {
    const sidebar = document.querySelector('.menu-lateral');

    sidebar.classList.add('aberta');
}

function fecharSidebar() {
    const sidebar = document.querySelector('.menu-lateral');

    sidebar.classList.remove('aberta');
}
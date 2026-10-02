const btnCadastro = document.getElementById('btn-cadastro');
const btnEntrar = document.getElementById('btn-entrar');
const logoutbtn = document.getElementById('logout-btn');
const logoutbtnMob = document.getElementById('logout-btn-mob');

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


let botoes = [logoutbtn, logoutbtnMob];
let n = botoes.length;

for (let index = 0; index < n; index++) {
    let btn = botoes[index]; 
    if (btn) {
    btn.addEventListener("click", async function(event) {
        event.preventDefault();
        try {
            let submit = await fetch("./user/logout", {
            method: "GET",
            })
            let resposta = await submit.json();
            if (resposta.resposta == true){
                alert("Usuario Desconectado");
                window.location.href = "./home";
            } else {
              console.log(JSON.stringify(resposta));  
            }
        } catch (error) {
            console.log(error);
        }
    })
}
    
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
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
        // checa se o usuario está on antes de adicionar logout
        if (localStorage.getItem("token") !== null){
            alert("Usuario não está conectado !!");
            localStorage.clear();
            window.location.href = "./login"; // redireciona para login
            return;
        }

        btn.addEventListener("click", async function(event) {
            event.preventDefault();
            try {
                let token = localStorage.getItem("token");
                if (!token) {
                    localStorage.clear();
                    console.error("Não foi encontrado um token para encerrar a sessão.");
                    return;
                }

                let submit = await fetch("./user/logout", {
                    method: "GET",
                    headers: {"Authorization": token}
                });

                let resposta = await submit.json();
                if (resposta.resposta == true){
                    localStorage.clear();
                    alert("Usuario Desconectado");
                    window.location.href = "./home";
                } else {
                    localStorage.clear()
                    console.error(JSON.stringify(resposta));  
                }
            } catch (error) {
                console.error(error);
            }
        })
    }
    
}

// funções
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
const form = document.getElementById("cadastro-form");

const senha = document.querySelector('input[name="password"]');
const confirmarSenha = document.querySelector('input[name="confirm-password"]');

form.addEventListener("submit", function(event) {

     /** Fazer fetch para o servidor para receber o json
     * tcc-etec.likesyou.org/usuario/criar/?nome=""&sobrenome=""&email=""=&senha=""&confirmar_senha="";
     * 
     */

    if (senha.value !== confirmarSenha.value) {
        event.preventDefault();

        alert("As senhas não são iguais!");
    }
});

const passwordIcons = document.querySelectorAll('.form-field i');

passwordIcons.forEach(icon => {

    icon.addEventListener('click', function () {

        const input = this.parentElement.querySelector('input');

        if (input.type === "password") {

            input.type = "text";

            this.classList.remove("fa-eye-slash");
            this.classList.add("fa-eye");

        } else {

            input.type = "password";

            this.classList.remove("fa-eye");
            this.classList.add("fa-eye-slash");
        }
    });
});
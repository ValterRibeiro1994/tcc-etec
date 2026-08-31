const form = document.getElementById("formCadastro");

const senha = document.getElementById("password");
const confirmarsenha = document.getElementById("confirm-password");

form.addEventListener("submit", function(event){
    if (senha.value !== confirmarsenha.value) {
        event.preventDefault();
        
        alert("As senha não são iguais!");
    }
});


const passwordIcons = document.querySelectorAll('.password-icon');

passwordIcons.forEach(icon => {
    icon.addEventListener('click', function () {
        const input = this.parentElement.querySelector('.form_control');
        input.type = input.type === 'password' ? 'text' : 'password';
        this.classList.toggle('fa-eye');
        this.classList.toggle('fa-eye-slash');
    })
})
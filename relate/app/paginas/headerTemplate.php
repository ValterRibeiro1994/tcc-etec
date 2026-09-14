<?php
include_once("../relate/app/sessao/sessao.php");

class HeaderTemplate {
    private string $template;

    public function __construct(){
        if (Sessao::estaConectado()){
            $nome = $_SESSION['user']['nome'];
            $sobrenome = $_SESSION['user']['sobrenome'];
            $this->template = $this->headerOn($nome, $sobrenome);
        } else {
            $this->template = $this->headerOff();
        }
    }

    public function getTemplate():  string {
        return $this->template;
    }

    private function headerOff(): string {
        return "
            <header>
                <nav class='navbar navbar-expand-lg navbar-dark bg-dark'>
                    <div class='container-fluid'>
                        <a href='#' class='navbar-brand'>
                            <img src='app/recursos/imagens/logo-site-pequeno.png' alt='Logo do site' class='img-fluid rounded-3'>
                        </a>
                        <button type='button' class='navbar-toggler' data-bs-toggle='collapse' data-bs-target='#menuNavegacao' aria-controls='menuNavegacao' aria-expanded='false' aria-label='Abrir menu'>
                            <span class='navbar-toggler-icon'></span>
                        </button>
                        <div class='collapse navbar-collapse' id='menuNavegacao'>
                            <ul class='navbar-nav me-auto mb-3 mb-lg-0'>
                                <li class='nav-item'>
                                    <a href='#' class='nav-link fs-5 text-white'>Início</a>
                                </li>

                                <li class='nav-item'>
                                    <a href='#' class='nav-link fs-5 text-white'>Sobre</a>
                                </li>

                                <li class='nav-item'>
                                    <a href='#' class='nav-link fs-5 text-white'>Denúncias</a>
                                </li>

                                <li class='nav-item'>
                                    <a href='denunciante/cadastrar' class='nav-link fs-5 text-white'>Cadastre-se</a>
                                </li>
                            </ul>
                            <form id='formHeader' method='post' class='d-flex gap-2'>
                                <input name='email' value='teste@email.com' type='email' class='form-control' placeholder='Email' aria-label='Email' required>
                                <input name='senha' value='senha01' type='password' class='form-control' placeholder='Senha' aria-label='Senha' required>
                                <button type='submit' class='btn btn-success text-nowrap'>
                                    Conectar
                                </button>
                            </form>
                        </div>
                    </div>
                </nav>
            </header>
        ";
    }

    private function headerOn(string $nome, string $sobrenome): string {
        return "
            <header>
                <nav class='navbar navbar-expand-lg navbar-dark bg-dark'>
                    <div class='container-fluid'>
                        
                        <a href='#' class='navbar-brand'>
                            <img src='app/recursos/imagens/logo-site-pequeno.png' alt='Logo do site' class='img-fluid rounded-3'>
                        </a>
                        
                        <button type='button' class='navbar-toggler' data-bs-toggle='collapse' data-bs-target='#menuNavegacao' aria-controls='menuNavegacao' aria-expanded='false' aria-label='Abrir menu'>
                            <span class='navbar-toggler-icon'></span>
                        </button>

                        <div class='collapse navbar-collapse' id='menuNavegacao'>
                            <ul class='navbar-nav me-auto mb-3 mb-lg-0'>
                                <li class='nav-item'>
                                    <a href='#' class='nav-link fs-5 text-white'>Início</a>
                                </li>

                                <li class='nav-item'>
                                    <a href='#' class='nav-link fs-5 text-white'>Sobre</a>
                                </li>

                                <li class='nav-item'>
                                    <a href='#' class='nav-link fs-5 text-white'>Denúncias</a>
                                </li>
                            </ul>

                            <div class='d-flex justify-content-end'>
                                <h3 class='display-6 mx-auto text-white text-center'>Bem vindo $nome $sobrenome</h3>
                                <a href='' type='button' class='btn btn-success my-auto mx-4'>Sair</a>
                            </div>

                        </div>
                    </div>
                </nav>
            </header>
        ";
    }
}
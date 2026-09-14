<?php

class HomePagina {
    private string $template;

    public function __construct(){
        $this->template = $this->criarMain();
    }

    public function getTemplate(){
        return $this->template;
    }

    private function criarMain(){
        return "
            <main class='img-fundo'>
                <div class='p-3 container-fluid'>
                    <h1 class='display-1 text-dark p-3'>MINHA DENÚNCIA</h1>
                    <h5 class='display-6 text-dark fw-semibold p-1 me-3'>Incluindo a todos para um bem maior</h5>
                    <p class='fs-2 text-dark fw-light pt-2 mb-0'>Cidade limpa, segura e organizada é dever de todos.</p>
                    <p class='fs-2 text-dark fw-light pt-0 mt-0 pb-3'>Sua denuncia faz a diferença.</p>
                    <a class='btn btn-success m-5 p-3'>Download do APP</a>
                </div>

                <div class='container'>
                    <div class='row justify-content-center g-4'>
                        <div class='col-12 col-md-4'>
                            <div class='card h-100 border border-3 border-dark shadow-lg rounded-5 text-center p-1'>
                                <img src='app/recursos/imagens/icone-lixeira.png' alt='' class='card-img-top d-block mx-auto rounded-2 w-50'>
                                <div class='card-body'>
                                    <h5 class='card-title'>Lixo e entulho</h5>
                                    <p class='card-text'>Descarte irregular de lixo, entulho e materiais.</p>
                                    <a href='' class='btn btn-success'>Denuncie aqui</a>
                                </div>
                            </div>
                        </div>

                        <div class='col-12 col-md-4'>
                            <div class='card h-100 border border-3 border-dark shadow-lg rounded-5 text-center p-1'>
                                <img src='app/recursos/imagens/icone-iluminacao.png' alt='' class='card-img-top d-block mx-auto rounded-2 w-50'>

                                <div class='card-body'>
                                    <h5 class='card-title'>Iluminação Pública</h5>
                                    <p class='card-text'>Lâmpadas queimadas ou postes com problemas.</p>
                                    <a href='' class='btn btn-success'>Denuncie aqui</a>
                                </div>
                            </div>
                        </div>

                        <div class='col-12 col-md-4'>
                            <div class='card h-100 border border-3 border-dark shadow-lg rounded-5 text-center p-1'>
                                <img src='app/recursos/imagens/icone-vias.png' alt='' class='card-img-top d-block mx-auto rounded-2 w-50'>

                                <div class='card-body'>
                                    <h5 class='card-title'>Buracos e Vias</h5>
                                    <p class='card-text'>Buracos, asfalto danificado e calçadas quebradas.</p>
                                    <a href='' class='btn btn-success'>Denuncie aqui</a>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </main>
        ";
    }
}
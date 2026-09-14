<?php

class FooterTemplate {
    private string $template;

    public function __construct(){
        $this->template = $this->criarFooter();
    }

    public function getTemplate(){
        return $this->template;
    }

    private function criarFooter(){
        return "
                <footer class='container-fluid p-1 mt-2 bg-dark text-white'>
                    <div class='row'>
                        <div class='col-5'>

                        </div>
                        <h5 class='col-5'>
                            Para você
                        </h5>
                    </div>
                    <div class='row'>
                        <div class='col-5'></div>
                            <ul class='nav flex-column col-5'>
                                <li class='nav-item'>
                                    <a class='nav-link' href='#'>Link</a>
                                </li>
                                <li class='nav-item'>
                                    <a class='nav-link' href='#'>Link</a>
                                </li>
                                <li class='nav-item'>
                                    <a class='nav-link' href='#'>Link</a>
                                </li>
                                <li class='nav-item'>
                                    <a class='nav-link disabled' href='#'>Disabled</a>
                                </li>
                            </ul>
                    </div>
                </footer>
        ";
    }
}
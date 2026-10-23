<?php

class CadastroController {
    public function index(array $requisicao){
        // if (SessaoController::estaConectado()) return RespostaProcesso::respostaProcesso("./app/view/paginas/index.html", true, formato: "html");
        // o index do cadastro não possui formulario na pagina.
        if ($requisicao['metodo'] == "GET"){
            
            if (SessaoService::estaOnline()){
                return RespostaProcesso::resposta(
                    mensagem: "Usuario já está conectado", resposta: false,
                    dados: [], formato: "html", pagina: "app/view/paginas/perfil.html"
                );
            }
            return RespostaProcesso::resposta(    
                mensagem:"Get realizado com sucesso", resposta: true, 
                formato: "html", 
                pagina: "app/view/paginas/selecionar-cadastro.html");
            } 
        
        // envia json informando o erro para o front 
        throw new Exception("requisição invalida para cadastro");

    }

    public function municipe(array $requisicao){
       // if (SessaoController::estaConectado()) return RespostaProcesso::respostaProcesso("app/view/paginas/index.html", true, formato: "html");
        
        // o método get apenas exibe o formulario para o cadastro do denunciante
        if ($requisicao['metodo'] == "GET") {
            if (SessaoService::estaOnline()){
                return RespostaProcesso::resposta(
                    mensagem: "Usuario já está conectado", resposta: false,
                    dados: [], formato: "html", pagina: "app/view/paginas/perfil.html"
                );
            }

            return RespostaProcesso::resposta(
                mensagem:"Get realizado com sucesso para municipe", resposta:true,
                formato:"html",
                pagina: "app/view/paginas/cadastro-denunciante.html");
                
                // o método post deve receber os dados preenchidos do formulario
        }
        if ($requisicao['metodo'] == "POST") return $this->cadastrarUsuario($requisicao);
        
        throw new Exception("Requisição invalida para Cadastro");
    }

    public function representante(array $requisicao){
       if ($requisicao['metodo'] == "GET") {
            if (SessaoService::estaOnline()){
                return RespostaProcesso::resposta(
                    mensagem: "Usuario já está conectado", resposta: false,
                    dados: [], formato: "html", pagina: "app/view/paginas/perfil.html"
                );
            }
            
            return RespostaProcesso::resposta(
                mensagem:"Get realizado com sucesso para municipe", resposta:true,
                formato:"html",
                pagina: "app/view/paginas/cadastro-representante.html");
                
                // o método post deve receber os dados preenchidos do formulario
        }
        if ($requisicao['metodo'] == "POST") return $this->cadastrarUsuario($requisicao);
        
        throw new Exception("Requisição invalida para Cadastro");
    }

    private function cadastrarUsuario(array $dados){
        // verifica a existencia e o envio dos atributos necessarios para o cadastro
        $dados_esperado = [
            'nome', 'sobrenome', 'cpf', 'perfil', 
            'email', 'senha', 'confirmar-senha',
            'cidade', 'estado', 'orgao', 'cargo'
        ];

        foreach ($dados_esperado as $chave) {
            if (!array_key_exists($chave, $dados) || empty($dados[$chave])) {
                return RespostaProcesso::resposta(
                    mensagem: "Cliente: Campo $chave não enviado", resposta: false,
                    dados: $dados, formato: "json"
                );
            }
        }

        try {

            // valida as senhas
            if ($dados['senha'] !== $dados['confirmar-senha']){
                return RespostaProcesso::resposta(
                    mensagem: "Cliente: Senhas diferentes", resposta: false,
                    dados: $dados, formato: "json"
                );
            }

            $senha = new Senha($dados['senha']);
            if (!$senha->validarSenha()){
                return RespostaProcesso::resposta(
                    mensagem: "Cliente: Senha invalida", resposta: false,
                    dados: $dados, formato: "json"
                );
            }

            $usuario = UtilService::criarUsuario($dados);
            // $usuario->setSenha($senha);
            // $usuario->setCpf($dados['cpf']);

            // salva o usuario no banco
            $repositorio = new UsuarioRepositorio(new Conexao());
            $resposta = $repositorio->adicionarUsuario($usuario);
            if (!$resposta['resposta']) return $resposta;
            return RespostaProcesso::resposta(
                mensagem: "Controller: Cadastro realizado com sucesso", resposta: true,
                formato: "json"
            );


        } catch (Exception $erro) {
            return RespostaProcesso::resposta(
                mensagem: "Controller: Erro ao cadastrar usuario", resposta: false,
                dados: RespostaProcesso::salvarErro($erro), formato: "json"
            );
        }

    }
}
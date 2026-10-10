<?php
 
class DenunciasRepositorio {
    public function salvarDenuncia(Denuncia $denuncia){
        $usuario = $denuncia->getUsuario();
        $data = $denuncia->getData();
        $titulo = $denuncia->getTitulo();
        $texto = $denuncia->getTexto();
        $imagem = $denuncia->getFoto();
 
        return RespostaProcesso::respostaProcesso("Finalizar Processo para armazenar denuncia no banco", true);
        
    }
 
    public function apagarDenuncia(){}
 
    // ==================================================================
    // NOVO: busca as denúncias no banco (tb_denuncia) para o mural
    // Retorna apenas os campos exibidos nos cards da home-mural.html
    // ==================================================================
    public function listarDenuncias(int $limite = 100): array {
        $tabela = $GLOBALS['denuncia']; // tb_denuncia (definida em config.php)
 
        $comando = "SELECT id_denuncia, titulo_denuncia, data_denuncia, imagem_denuncia,
                           nome_categoria, logradouro_denuncia, numero_denuncia,
                           bairro_denuncia, cidade_denuncia, estado_denuncia, status_denuncia
                    FROM $tabela
                    ORDER BY data_denuncia DESC
                    LIMIT :limite";
 
        try {
            $conexao = (new Conexao())->getConexao();
            $sql = $conexao->prepare($comando);
            $sql->bindValue(':limite', $limite, PDO::PARAM_INT);
            $sql->execute();
            $linhas = $sql->fetchAll(PDO::FETCH_ASSOC);
 
            $denuncias = [];
            foreach ($linhas as $linha) {
                // local no formato "Rua X, 123 - Bairro"
                $rua = $linha['logradouro_denuncia'];
                if (!empty($linha['numero_denuncia'])) $rua .= ", " . $linha['numero_denuncia'];
                $local = $rua . " - " . $linha['bairro_denuncia'];
 
                // data no formato brasileiro
                $data = (new DateTime($linha['data_denuncia']))->format("d/m/Y");
 
                // imagem (BLOB) -> data URI base64, pois o JSON não aceita binário
                $imagem = null;
                if (!empty($linha['imagem_denuncia'])) {
                    $mime = (new finfo(FILEINFO_MIME_TYPE))->buffer($linha['imagem_denuncia']);
                    if (!$mime || !str_starts_with($mime, "image/")) $mime = "image/jpeg";
                    $imagem = "data:$mime;base64," . base64_encode($linha['imagem_denuncia']);
                }
 
                $denuncias[] = [
                    "id"        => (int) $linha['id_denuncia'],
                    "titulo"    => $linha['titulo_denuncia'],
                    "categoria" => $linha['nome_categoria'],
                    "local"     => $local,
                    "data"      => $data,
                    "status"    => $linha['status_denuncia'],
                    "imagem"    => $imagem
                ];
            }
 
            return RespostaProcesso::resposta(
                mensagem: count($denuncias) . " denúncia(s) encontrada(s)",
                resposta: true,
                dados: $denuncias
            );
        } catch (Exception $erro) {
            return RespostaProcesso::resposta(
                mensagem: "Erro ao buscar denúncias: " . $erro->getMessage(),
                resposta: false,
                dados: RespostaProcesso::salvarErro($erro)
            );
        }
    }
}
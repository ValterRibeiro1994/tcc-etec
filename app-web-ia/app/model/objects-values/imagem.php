<?php

// Recebe o item do $_FILES (ex.: $_FILES['foto-denuncia']), valida e guarda os bytes
// para gravar na coluna imagem_denuncia (BLOB). A foto é opcional.
class Imagem {
    private string|null $bytes = null;

    private const TAMANHO_MAXIMO = 5242880; // 5 MB
    private const TIPOS_PERMITIDOS = ['image/jpeg', 'image/png'];

    public function __construct(array|null $arquivo = null){
        if ($arquivo !== null) $this->setArquivo($arquivo);
    }

    public function setArquivo(array $arquivo): void {
        $erro = $arquivo['error'] ?? UPLOAD_ERR_NO_FILE;

        // nenhuma foto enviada: tudo bem, ela é opcional
        if ($erro === UPLOAD_ERR_NO_FILE) return;

        if ($erro === UPLOAD_ERR_INI_SIZE || $erro === UPLOAD_ERR_FORM_SIZE) {
            throw new Exception("A imagem é maior que o tamanho permitido");
        }
        if ($erro !== UPLOAD_ERR_OK) throw new Exception("Não foi possível receber a imagem");

        if ($arquivo['size'] > self::TAMANHO_MAXIMO) throw new Exception("A imagem deve ter no máximo 5MB");
        if (!is_uploaded_file($arquivo['tmp_name'])) throw new Exception("Arquivo de imagem inválido");

        // o tipo é conferido pelo conteúdo do arquivo, não pelo nome/extensão enviados
        $tipo = (new finfo(FILEINFO_MIME_TYPE))->file($arquivo['tmp_name']);
        if (!in_array($tipo, self::TIPOS_PERMITIDOS, true)) throw new Exception("A imagem deve ser JPG ou PNG");

        $bytes = file_get_contents($arquivo['tmp_name']);
        if ($bytes === false || $bytes === "") throw new Exception("Não foi possível ler a imagem");
        $this->bytes = $bytes;
    }

    public function temFoto(): bool {
        return $this->bytes !== null;
    }

    // devolve os bytes da imagem ou null quando não há foto
    public function getFoto(): string|null {
        return $this->bytes;
    }
}

package com.example.tcc

import java.time.LocalDate

/**
 * Regras iguais às do site/MySQL. O que o app valida aqui é o mesmo que o PHP exige na
 * importação, então nada é aceito no celular e recusado depois no banco de dados.
 */
object Regras {
    // Categorias que existem em tb_categoria (chave estrangeira em tb_denuncia).
    // Se criar uma categoria nova no banco, inclua o nome aqui e em regrasfirebase.txt.
    val CATEGORIAS = listOf("Iluminação Publica", "Infraestrutura", "Saneamento")

    const val TITULO_MAX = 60
    const val DESCRICAO_MAX = 2000      // o site limita a 2000
    const val RUA_MAX = 100
    const val NUMERO_MAX = 30
    const val COMPLEMENTO_MAX = 100
    const val BAIRRO_MAX = 80
    const val CIDADE_MAX = 50
    const val LOGRADOURO_MAX = 150      // coluna logradouro_denuncia
    const val FOTO_MAX_BYTES = 5L * 1024 * 1024   // o site aceita no máximo 5 MB

    val UFS = setOf(
        "AC", "AL", "AP", "AM", "BA", "CE", "DF", "ES", "GO", "MA", "MT", "MS", "MG", "PA",
        "PB", "PR", "PE", "PI", "RJ", "RN", "RS", "RO", "RR", "SC", "SP", "SE", "TO"
    )

    fun somenteDigitos(s: String) = s.filter(Char::isDigit)

    fun cpfValido(bruto: String): Boolean {
        val c = somenteDigitos(bruto)
        if (c.length != 11 || c.all { it == c[0] }) return false
        fun digito(n: Int): Int {
            var soma = 0
            for (i in 0 until n) soma += (c[i] - '0') * (n + 1 - i)
            val resto = (soma * 10) % 11
            return if (resto == 10) 0 else resto
        }
        return digito(9) == c[9] - '0' && digito(10) == c[10] - '0'
    }

    /** O site grava o texto já "escapado" (htmlspecialchars) e conta bytes; aqui contamos igual. */
    private fun bytesComoNoSite(s: String): Int =
        s.replace("&", "&amp;").replace("<", "&lt;").replace(">", "&gt;")
            .replace("\"", "&quot;").replace("'", "&#039;")
            .toByteArray(Charsets.UTF_8).size

    fun nomeValido(s: String) = bytesComoNoSite(s.trim()) in 3..20
    fun sobrenomeValido(s: String) = bytesComoNoSite(s.trim()) in 3..60

    fun dataValida(s: String): Boolean {
        val d = runCatching { LocalDate.parse(s) }.getOrNull() ?: return false
        return !d.isAfter(LocalDate.now())
    }
}

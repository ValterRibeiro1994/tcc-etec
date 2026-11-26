package com.example.tcc

import android.app.Activity
import android.text.InputFilter
import android.text.InputType
import android.widget.EditText
import android.widget.LinearLayout
import android.widget.ScrollView
import android.widget.Toast
import androidx.appcompat.app.AlertDialog

/**
 * Pede os dados que o banco de dados do site exige (nome, sobrenome, CPF, cidade, UF).
 * Usado por quem entrou com o Google ou criou a conta antes de esses campos existirem.
 */
fun Activity.pedirPerfilCompleto(atual: PerfilUsuario, aoSalvar: () -> Unit) {
    val dp = resources.displayMetrics.density
    fun campo(dica: String, valor: String, tipo: Int, max: Int) = EditText(this).apply {
        hint = dica
        setText(valor)
        inputType = tipo
        filters = arrayOf(InputFilter.LengthFilter(max))
        setSingleLine()
    }

    val nome = campo("Nome", atual.nome.take(20), InputType.TYPE_CLASS_TEXT or InputType.TYPE_TEXT_VARIATION_PERSON_NAME or InputType.TYPE_TEXT_FLAG_CAP_WORDS, 20)
    val sobrenome = campo("Sobrenome", atual.sobrenome, InputType.TYPE_CLASS_TEXT or InputType.TYPE_TEXT_VARIATION_PERSON_NAME or InputType.TYPE_TEXT_FLAG_CAP_WORDS, 60)
    val cpf = campo("CPF (somente números)", atual.cpf, InputType.TYPE_CLASS_NUMBER, 14)
    val cidade = campo("Cidade", atual.cidade, InputType.TYPE_CLASS_TEXT or InputType.TYPE_TEXT_FLAG_CAP_WORDS, Regras.CIDADE_MAX)
    val estado = campo("UF (ex.: SP)", atual.estado, InputType.TYPE_CLASS_TEXT or InputType.TYPE_TEXT_FLAG_CAP_CHARACTERS, 2)

    val coluna = LinearLayout(this).apply {
        orientation = LinearLayout.VERTICAL
        val p = (20 * dp).toInt()
        setPadding(p, p / 2, p, 0)
        listOf(nome, sobrenome, cpf, cidade, estado).forEach { addView(it) }
    }

    val dialogo = AlertDialog.Builder(this)
        .setTitle("Complete seu cadastro")
        .setMessage("Esses dados são necessários para registrar sua denúncia no sistema.")
        .setView(ScrollView(this).apply { addView(coluna) })
        .setPositiveButton("Salvar", null)
        .setNegativeButton("Cancelar", null)
        .create()

    dialogo.setOnShowListener {
        dialogo.getButton(AlertDialog.BUTTON_POSITIVE).setOnClickListener {
            val perfil = PerfilUsuario(
                nome.text.toString(), sobrenome.text.toString(), cpf.text.toString(),
                cidade.text.toString(), estado.text.toString()
            )
            val problema = perfil.validar()
            if (problema != null) {
                Toast.makeText(this, problema, Toast.LENGTH_LONG).show()
                return@setOnClickListener
            }
            it.isEnabled = false
            UsuarioRepository().salvarPerfil(perfil,
                sucesso = { dialogo.dismiss(); aoSalvar() },
                erro = { msg -> it.isEnabled = true; Toast.makeText(this, msg, Toast.LENGTH_LONG).show() })
        }
    }
    dialogo.show()
}

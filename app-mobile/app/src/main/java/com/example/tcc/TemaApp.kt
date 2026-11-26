package com.example.tcc

import android.app.Application
import android.content.Context
import androidx.appcompat.app.AppCompatDelegate

/**
 * Aplica o tema (claro/escuro) salvo assim que o app abre,
 * antes de qualquer tela ser desenhada.
 */
class TemaApp : Application() {

    override fun onCreate() {
        super.onCreate()
        aplicarTema(modoEscuroAtivo(this))
    }

    companion object {
        private const val PREFS = "Configuracoes"
        private const val CHAVE_MODO_ESCURO = "modo_escuro"

        fun modoEscuroAtivo(context: Context): Boolean =
            context.getSharedPreferences(PREFS, Context.MODE_PRIVATE)
                .getBoolean(CHAVE_MODO_ESCURO, false)

        fun salvarModoEscuro(context: Context, ativo: Boolean) {
            context.getSharedPreferences(PREFS, Context.MODE_PRIVATE)
                .edit()
                .putBoolean(CHAVE_MODO_ESCURO, ativo)
                .apply()
        }

        fun aplicarTema(escuro: Boolean) {
            AppCompatDelegate.setDefaultNightMode(
                if (escuro) AppCompatDelegate.MODE_NIGHT_YES
                else AppCompatDelegate.MODE_NIGHT_NO
            )
        }
    }
}

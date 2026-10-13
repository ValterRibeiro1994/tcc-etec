package com.example.tcc

import android.content.Context
import androidx.appcompat.app.AppCompatDelegate

object ThemeManager {

    private const val PREFS = "RelateSBC_Config"
    private const val KEY_DARK_MODE = "dark_mode"

    fun aplicarTema(context: Context) {

        val prefs = context.getSharedPreferences(
            PREFS,
            Context.MODE_PRIVATE
        )

        val modoEscuro = prefs.getBoolean(
            KEY_DARK_MODE,
            false
        )

        AppCompatDelegate.setDefaultNightMode(
            if (modoEscuro)
                AppCompatDelegate.MODE_NIGHT_YES
            else
                AppCompatDelegate.MODE_NIGHT_NO
        )
    }

    fun modoEscuroAtivo(context: Context): Boolean {
        val prefs = context.getSharedPreferences(
            PREFS,
            Context.MODE_PRIVATE
        )

        return prefs.getBoolean(KEY_DARK_MODE, false)
    }

    fun alternarTema(context: Context) {

        val novoModo = !modoEscuroAtivo(context)

        // Salva a escolha permanentemente
        context.getSharedPreferences(
            PREFS,
            Context.MODE_PRIVATE
        ).edit()
            .putBoolean(KEY_DARK_MODE, novoModo)
            .apply()

        // Aplica o tema imediatamente
        AppCompatDelegate.setDefaultNightMode(
            if (novoModo)
                AppCompatDelegate.MODE_NIGHT_YES
            else
                AppCompatDelegate.MODE_NIGHT_NO
        )
    }
}

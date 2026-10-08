package com.example.tcc

import android.content.Context
import com.google.gson.Gson
import com.google.gson.reflect.TypeToken

object ItemRepository {

    private const val PREFS = "cadastros"
    private const val KEY_ITENS = "itens"

    fun salvar(context: Context, item: Item) {

        val lista = buscarTodos(context).toMutableList()

        lista.add(item)

        salvarLista(context, lista)
    }

    fun buscarTodos(context: Context): List<Item> {

        val prefs = context.getSharedPreferences(
            PREFS,
            Context.MODE_PRIVATE
        )

        val json = prefs.getString(KEY_ITENS, null)
            ?: return emptyList()

        val type = object :
            TypeToken<List<Item>>() {}.type

        return Gson().fromJson(json, type)
    }

    private fun salvarLista(
        context: Context,
        lista: List<Item>
    ) {

        val prefs = context.getSharedPreferences(
            PREFS,
            Context.MODE_PRIVATE
        )

        val json = Gson().toJson(lista)

        prefs.edit()
            .putString(KEY_ITENS, json)
            .apply()
    }
}
package com.example.tcc

data class Item(
    val id: Long = System.currentTimeMillis(),
    val titulo: String,
    val categoria: String,
    val descricao: String,
    val fotoUri: String?,
    val endereco: String,
    val firebaseId: String = "",
    val usuarioUid: String = "",
    val status: String = "pendente"
)

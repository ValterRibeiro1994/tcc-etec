package com.example.tcc

import com.google.firebase.Timestamp

data class Item(
    val id: Long = System.currentTimeMillis(),
    val titulo: String,
    val categoria: String,
    val descricao: String,
    val fotoUri: String?,
    val endereco: String,
    val firebaseId: String = "",
    val usuarioUid: String = "",
    val mysqlId: Long = 0L,
    val idDenuncia: Long = 0L,
    val mysqlUserId: Long = 0L,
    val usuarioEmail: String = "",
    val status: String = "pendente",
    val criadoEm: Timestamp? = null,
    val dataOcorrencia: String = "",
    val rua: String = "",
    val numero: String = "",
    val complemento: String = "",
    val bairro: String = "",
    val cidade: String = "",
    val estado: String = "",
    val cep: String = "",
    val anonima: Boolean = false,
    val perfilUsuario: String = "municipe",
    val autorNome: String = "",
    val fotoPublicId: String = "",
    val origem: String = ""
)

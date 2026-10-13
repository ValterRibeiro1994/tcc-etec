package com.example.tcc

import com.google.firebase.auth.FirebaseAuth
import com.google.firebase.firestore.FieldValue
import com.google.firebase.firestore.FirebaseFirestore

class UsuarioRepository {

    private val auth = FirebaseAuth.getInstance()
    private val db = FirebaseFirestore.getInstance()

    fun cadastrar(
        nome: String,
        email: String,
        senha: String,
        sucesso: () -> Unit,
        erro: (String) -> Unit
    ) {
        auth.createUserWithEmailAndPassword(email, senha)
            .addOnSuccessListener { resultado ->

                val usuario = resultado.user

                if (usuario == null) {
                    erro("Não foi possível obter o usuário.")
                    return@addOnSuccessListener
                }

                val dados = hashMapOf(
                    "uid" to usuario.uid,
                    "nome" to nome,
                    "email" to (usuario.email ?: email),
                    "criadoEm" to FieldValue.serverTimestamp()
                )

                db.collection("usuarios")
                    .document(usuario.uid)
                    .set(dados)
                    .addOnSuccessListener {
                        sucesso()
                    }
                    .addOnFailureListener { exception ->
                        erro(
                            "Conta criada, mas o perfil não foi salvo: " +
                                    exception.localizedMessage
                        )
                    }
            }
            .addOnFailureListener { exception ->
                erro(
                    exception.localizedMessage
                        ?: "Erro ao cadastrar usuário."
                )
            }
    }
}

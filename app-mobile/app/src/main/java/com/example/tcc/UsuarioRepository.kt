package com.example.tcc

import com.google.firebase.auth.FirebaseAuth
import com.google.firebase.firestore.FieldValue
import com.google.firebase.firestore.FirebaseFirestore
import com.google.firebase.firestore.SetOptions

/** Dados que o MySQL (tb_usuario) exige de todo usuário. */
data class PerfilUsuario(
    val nome: String = "",
    val sobrenome: String = "",
    val cpf: String = "",
    val cidade: String = "",
    val estado: String = ""
) {
    /** Retorna a mensagem do primeiro problema, ou null se estiver tudo certo. */
    fun validar(): String? = when {
        !Regras.nomeValido(nome) -> "Nome deve ter de 3 a 20 caracteres."
        !Regras.sobrenomeValido(sobrenome) -> "Sobrenome deve ter de 3 a 60 caracteres."
        !Regras.cpfValido(cpf) -> "CPF inválido."
        cidade.isBlank() || cidade.trim().length > Regras.CIDADE_MAX -> "Informe a cidade (até 50 letras)."
        estado.trim().uppercase() !in Regras.UFS -> "Informe a UF com 2 letras (ex.: SP)."
        else -> null
    }
}

class UsuarioRepository {

    private val auth = FirebaseAuth.getInstance()
    private val db = FirebaseFirestore.getInstance()

    fun cadastrar(
        perfil: PerfilUsuario,
        email: String,
        senha: String,
        sucesso: () -> Unit,
        erro: (String) -> Unit
    ) {
        perfil.validar()?.let { erro(it); return }

        auth.createUserWithEmailAndPassword(email.trim(), senha)
            .addOnSuccessListener { resultado ->
                val usuario = resultado.user
                if (usuario == null) {
                    erro("Não foi possível obter o usuário.")
                    return@addOnSuccessListener
                }
                gravarPerfil(usuario.uid, usuario.email ?: email.trim(), perfil, criando = true,
                    sucesso = sucesso,
                    erro = { erro("Conta criada, mas o perfil não foi salvo: $it") })
            }
            .addOnFailureListener { exception ->
                erro(exception.localizedMessage ?: "Erro ao cadastrar usuário.")
            }
    }

    /** Salva (ou completa) o perfil do usuário logado — usado também por quem entrou com o Google. */
    fun salvarPerfil(perfil: PerfilUsuario, sucesso: () -> Unit, erro: (String) -> Unit) {
        val usuario = auth.currentUser ?: run { erro("Faça login novamente."); return }
        perfil.validar()?.let { erro(it); return }
        gravarPerfil(usuario.uid, usuario.email.orEmpty(), perfil, criando = false, sucesso = sucesso, erro = erro)
    }

    private fun gravarPerfil(
        uid: String,
        email: String,
        perfil: PerfilUsuario,
        criando: Boolean,
        sucesso: () -> Unit,
        erro: (String) -> Unit
    ) {
        val dados = hashMapOf<String, Any>(
            "uid" to uid,
            "nome" to perfil.nome.trim(),
            "sobrenome" to perfil.sobrenome.trim(),
            "email" to email,
            "perfil" to "municipe",
            "cpf" to Regras.somenteDigitos(perfil.cpf),
            "cidade" to perfil.cidade.trim(),
            "estado" to perfil.estado.trim().uppercase()
        )
        if (criando) {
            dados["orgao"] = ""
            dados["cargo"] = ""
            dados["areaAtuacao"] = ""
            dados["avisosEmail"] = false
            dados["telefone"] = ""
            dados["criadoEm"] = FieldValue.serverTimestamp()
        }
        db.collection("usuarios").document(uid).set(dados, SetOptions.merge())
            .addOnSuccessListener { sucesso() }
            .addOnFailureListener { erro(it.localizedMessage ?: "Erro ao salvar o perfil.") }
    }

    /**
     * Informa se o perfil do usuário logado já tem tudo que o MySQL exige.
     * Depois da importação o CPF é apagado do Firestore e fica o mysqlUserId no lugar.
     */
    fun perfilCompleto(resultado: (completo: Boolean, atual: PerfilUsuario) -> Unit, erro: (String) -> Unit) {
        val uid = auth.currentUser?.uid ?: run { erro("Faça login para continuar."); return }
        db.collection("usuarios").document(uid).get()
            .addOnSuccessListener { doc ->
                val atual = PerfilUsuario(
                    nome = doc.getString("nome").orEmpty(),
                    sobrenome = doc.getString("sobrenome").orEmpty(),
                    cpf = doc.getString("cpf").orEmpty(),
                    cidade = doc.getString("cidade").orEmpty(),
                    estado = doc.getString("estado").orEmpty()
                )
                val vinculado = doc.getLong("mysqlUserId") != null
                val completo = if (vinculado) true else atual.validar() == null
                resultado(completo, atual)
            }
            .addOnFailureListener { erro(it.localizedMessage ?: "Erro ao ler o perfil.") }
    }
}

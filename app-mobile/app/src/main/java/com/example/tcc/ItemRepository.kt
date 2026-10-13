package com.example.tcc

import android.content.Context
import android.net.Uri
import android.os.Handler
import android.os.Looper
import com.google.firebase.auth.FirebaseAuth
import com.google.firebase.firestore.FieldValue
import com.google.firebase.firestore.FirebaseFirestore
import com.google.firebase.firestore.ListenerRegistration
import com.google.firebase.firestore.QueryDocumentSnapshot
import org.json.JSONObject
import java.net.HttpURLConnection
import java.net.URL
import java.util.UUID
import java.util.concurrent.Executors

object ItemRepository {
    private val auth get() = FirebaseAuth.getInstance()
    private val db get() = FirebaseFirestore.getInstance()
    private val executor = Executors.newSingleThreadExecutor()
    private val mainHandler = Handler(Looper.getMainLooper())

    fun salvar(
        context: Context,
        item: Item,
        imagemLocal: Uri,
        sucesso: () -> Unit,
        erro: (String) -> Unit
    ) {
        val uid = auth.currentUser?.uid ?: run {
            erro("Faça login para enviar uma denúncia.")
            return
        }
        if (item.titulo.isBlank() || item.descricao.isBlank() || item.endereco.isBlank()) {
            erro("Preencha título, descrição e endereço.")
            return
        }
        if (item.titulo.length > 200 || item.descricao.length > 5000 ||
            item.categoria.length > 100 || item.endereco.length > 500) {
            erro("Um dos campos ultrapassou o limite permitido.")
            return
        }
        if (CloudinaryConfig.CLOUD_NAME.startsWith("COLOQUE_") ||
            CloudinaryConfig.UPLOAD_PRESET.startsWith("COLOQUE_")) {
            erro("Configure CloudinaryConfig.kt antes de enviar denúncias.")
            return
        }
        val appContext = context.applicationContext
        executor.execute {
            try {
                val imagem = enviarImagem(appContext, imagemLocal)
                mainHandler.post {
                    if (auth.currentUser?.uid != uid) {
                        erro("A conta mudou durante o envio. Faça login novamente.")
                        return@post
                    }
                    val dados = hashMapOf(
                        "usuarioUid" to uid,
                        "titulo" to item.titulo.trim(),
                        "categoria" to item.categoria.trim(),
                        "descricao" to item.descricao.trim(),
                        "endereco" to item.endereco.trim(),
                        "fotoUri" to imagem.first,
                        "fotoPublicId" to imagem.second,
                        "status" to "pendente",
                        "criadoEm" to FieldValue.serverTimestamp()
                    )
                    db.collection("denuncias").add(dados)
                        .addOnSuccessListener { sucesso() }
                        .addOnFailureListener { ex ->
                            // A imagem já enviada pode ficar órfã. Exclusão segura exige backend.
                            erro(ex.localizedMessage ?: "Foto enviada, mas falhou ao salvar a denúncia.")
                        }
                }
            } catch (ex: Exception) {
                mainHandler.post {
                    erro(ex.localizedMessage ?: "Falha ao enviar imagem para Cloudinary.")
                }
            }
        }
    }

    private fun enviarImagem(context: Context, uri: Uri): Pair<String, String> {
        val cloud = CloudinaryConfig.CLOUD_NAME
        require(cloud.matches(Regex("[A-Za-z0-9_-]+"))) { "Cloud name inválido." }
        val boundary = "----RelateSBC${UUID.randomUUID().toString().replace("-", "")}" 
        val url = URL("https://api.cloudinary.com/v1_1/$cloud/image/upload")
        val conn = (url.openConnection() as HttpURLConnection)
        try {
            conn.requestMethod = "POST"
            conn.connectTimeout = 20000
            conn.readTimeout = 60000
            conn.doOutput = true
            conn.setRequestProperty("Content-Type", "multipart/form-data; boundary=$boundary")
            conn.setChunkedStreamingMode(8192)
            conn.outputStream.use { out ->
                fun writeText(value: String) = out.write(value.toByteArray(Charsets.UTF_8))
                writeText("--$boundary\r\n")
                writeText("Content-Disposition: form-data; name=\"upload_preset\"\r\n\r\n")
                writeText(CloudinaryConfig.UPLOAD_PRESET)
                writeText("\r\n--$boundary\r\n")
                writeText("Content-Disposition: form-data; name=\"file\"; filename=\"denuncia.jpg\"\r\n")
                val mime = context.contentResolver.getType(uri) ?: "image/jpeg"
                require(mime.startsWith("image/")) { "Selecione uma imagem válida." }
                writeText("Content-Type: $mime\r\n\r\n")
                context.contentResolver.openInputStream(uri)?.use { input ->
                    val buffer = ByteArray(8192)
                    var total = 0L
                    while (true) {
                        val count = input.read(buffer)
                        if (count == -1) break
                        total += count
                        require(total <= 10L * 1024 * 1024) { "A imagem deve ter até 10 MB." }
                        out.write(buffer, 0, count)
                    }
                    require(total > 0) { "A imagem está vazia." }
                } ?: error("Não foi possível abrir a imagem selecionada.")
                writeText("\r\n--$boundary--\r\n")
            }
            val status = conn.responseCode
            val stream = if (status in 200..299) conn.inputStream else conn.errorStream
            val resposta = stream?.bufferedReader()?.use { it.readText() }.orEmpty()
            if (status !in 200..299) {
                val mensagem = runCatching {
                    JSONObject(resposta).getJSONObject("error").getString("message")
                }.getOrDefault("Erro HTTP $status")
                error("Cloudinary: $mensagem")
            }
            val json = JSONObject(resposta)
            val secureUrl = json.getString("secure_url")
            require(secureUrl.startsWith("https://res.cloudinary.com/$cloud/")) {
                "Resposta inválida do Cloudinary."
            }
            return secureUrl to json.getString("public_id")
        } finally {
            conn.disconnect()
        }
    }

    private fun converter(doc: QueryDocumentSnapshot): Item = Item(
        id = doc.getLong("id") ?: 0L,
        titulo = doc.getString("titulo").orEmpty(),
        categoria = doc.getString("categoria").orEmpty(),
        descricao = doc.getString("descricao").orEmpty(),
        fotoUri = doc.getString("fotoUri"),
        endereco = doc.getString("endereco").orEmpty(),
        firebaseId = doc.id,
        usuarioUid = doc.getString("usuarioUid").orEmpty(),
        status = doc.getString("status") ?: "pendente"
    )

    fun buscarTodos(sucesso: (List<Item>) -> Unit, erro: (String) -> Unit) {
        db.collection("denuncias").get()
            .addOnSuccessListener { sucesso(it.map(::converter)) }
            .addOnFailureListener { erro(it.localizedMessage ?: "Erro ao buscar denúncias.") }
    }

    fun observarTodas(
        sucesso: (List<Item>) -> Unit,
        erro: (String) -> Unit
    ): ListenerRegistration = db.collection("denuncias")
        .addSnapshotListener { resultado, ex ->
            if (ex != null) {
                erro(ex.localizedMessage ?: "Erro ao atualizar denúncias.")
            } else {
                sucesso(resultado?.documents?.mapNotNull { doc ->
                    (doc as? QueryDocumentSnapshot)?.let(::converter)
                } ?: emptyList())
            }
        }

    fun buscarMinhas(sucesso: (List<Item>) -> Unit, erro: (String) -> Unit) {
        val uid = auth.currentUser?.uid ?: run {
            erro("Usuário não autenticado.")
            return
        }
        db.collection("denuncias").whereEqualTo("usuarioUid", uid).get()
            .addOnSuccessListener { sucesso(it.map(::converter)) }
            .addOnFailureListener { erro(it.localizedMessage ?: "Erro ao buscar suas denúncias.") }
    }
}

package com.example.tcc

import android.content.Context
import android.net.Uri
import android.os.Handler
import android.os.Looper
import com.google.firebase.Timestamp
import com.google.firebase.auth.FirebaseAuth
import com.google.firebase.firestore.FieldValue
import com.google.firebase.firestore.FirebaseFirestore
import com.google.firebase.firestore.ListenerRegistration
import com.google.firebase.firestore.QueryDocumentSnapshot
import org.json.JSONObject
import java.net.HttpURLConnection
import java.net.URL
import java.time.LocalDate
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
        validarItem(item)?.let { erro(it); return }
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
                    val dados = hashMapOf<String, Any>(
                        "usuarioUid" to uid,
                        "titulo" to item.titulo.trim(),
                        "categoria" to item.categoria.trim(),
                        "descricao" to item.descricao.trim(),
                        "endereco" to item.endereco.trim(),
                        "fotoUri" to imagem.first,
                        "fotoPublicId" to imagem.second,
                        "status" to "pendente",
                        "criadoEm" to FieldValue.serverTimestamp(),
                        "dataOcorrencia" to item.dataOcorrencia,
                        "rua" to item.rua.trim(),
                        "numero" to item.numero.trim(),
                        "complemento" to item.complemento.trim(),
                        "bairro" to item.bairro.trim(),
                        "cidade" to item.cidade.trim(),
                        "estado" to item.estado.trim().uppercase(),
                        "cep" to Regras.somenteDigitos(item.cep),
                        "anonima" to item.anonima,
                        "perfilUsuario" to "municipe",
                        "origem" to "app",
                        // false = ainda não importada para o MySQL; o PHP marca true depois de importar
                        "sincronizado" to false
                    )
                    db.collection("denuncias").add(dados)
                        .addOnSuccessListener { sucesso() }
                        .addOnFailureListener { ex ->
                            erro(ex.localizedMessage ?: "Foto enviada, mas falhou ao salvar a denúncia.")
                        }
                }
            } catch (ex: Exception) {
                mainHandler.post { erro(ex.localizedMessage ?: "Falha ao enviar imagem para Cloudinary.") }
            }
        }
    }

    /** Mesmas regras do site/MySQL: o que passa aqui é aceito na importação. */
    private fun validarItem(i: Item): String? = when {
        i.titulo.isBlank() || i.titulo.trim().length > Regras.TITULO_MAX -> "Título obrigatório (até ${Regras.TITULO_MAX} caracteres)."
        i.categoria !in Regras.CATEGORIAS -> "Escolha uma categoria válida."
        i.descricao.isBlank() || i.descricao.trim().length > Regras.DESCRICAO_MAX -> "Descrição obrigatória (até ${Regras.DESCRICAO_MAX} caracteres)."
        i.rua.isBlank() || i.rua.trim().length > Regras.RUA_MAX -> "Rua obrigatória (até ${Regras.RUA_MAX} caracteres)."
        i.numero.isBlank() || i.numero.trim().length > Regras.NUMERO_MAX -> "Número obrigatório (até ${Regras.NUMERO_MAX} caracteres)."
        i.complemento.trim().length > Regras.COMPLEMENTO_MAX -> "Complemento muito longo (até ${Regras.COMPLEMENTO_MAX} caracteres)."
        i.bairro.isBlank() || i.bairro.trim().length > Regras.BAIRRO_MAX -> "Bairro obrigatório (até ${Regras.BAIRRO_MAX} caracteres)."
        i.cidade.isBlank() || i.cidade.trim().length > Regras.CIDADE_MAX -> "Cidade obrigatória (até ${Regras.CIDADE_MAX} caracteres)."
        i.estado.trim().uppercase() !in Regras.UFS -> "Informe a UF com 2 letras (ex.: SP)."
        Regras.somenteDigitos(i.cep).length != 8 -> "Informe um CEP válido com 8 números."
        !Regras.dataValida(i.dataOcorrencia) -> "Informe uma data válida (AAAA-MM-DD) que não esteja no futuro."
        i.endereco.trim().length > Regras.LOGRADOURO_MAX -> "Endereço muito longo: reduza a rua, o complemento ou o bairro."
        else -> null
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
                        require(total <= Regras.FOTO_MAX_BYTES) { "A imagem deve ter até 5 MB." }
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
                val mensagem = runCatching { JSONObject(resposta).getJSONObject("error").getString("message") }
                    .getOrDefault("Erro HTTP $status")
                error("Cloudinary: $mensagem")
            }
            val json = JSONObject(resposta)
            val secureUrl = json.getString("secure_url")
            require(secureUrl.startsWith("https://res.cloudinary.com/$cloud/")) { "Resposta inválida do Cloudinary." }
            return secureUrl to json.getString("public_id")
        } finally {
            conn.disconnect()
        }
    }

    private fun converter(doc: QueryDocumentSnapshot): Item {
        val mysqlId = doc.getLong("mysqlId") ?: 0L
        val idDenuncia = doc.getLong("idDenuncia") ?: 0L
        val id = mysqlId.takeIf { it != 0L }
            ?: idDenuncia.takeIf { it != 0L }
            ?: doc.getLong("id")
            ?: doc.id.removePrefix("web_").toLongOrNull() ?: 0L
        return Item(
            id = id,
            titulo = doc.getString("titulo").orEmpty(),
            categoria = doc.getString("categoria").orEmpty(),
            descricao = doc.getString("descricao").orEmpty(),
            fotoUri = doc.getString("fotoUri")?.takeIf { it.isNotBlank() },
            endereco = doc.getString("endereco").orEmpty(),
            firebaseId = doc.id,
            usuarioUid = doc.getString("usuarioUid").orEmpty(),
            mysqlId = mysqlId,
            idDenuncia = idDenuncia,
            mysqlUserId = doc.getLong("mysqlUserId") ?: 0L,
            usuarioEmail = doc.getString("usuarioEmail").orEmpty(),
            status = doc.getString("status") ?: "pendente",
            criadoEm = doc.getTimestamp("criadoEm"),
            dataOcorrencia = doc.getString("dataOcorrencia").orEmpty(),
            rua = doc.getString("rua").orEmpty(),
            numero = doc.getString("numero").orEmpty(),
            complemento = doc.getString("complemento").orEmpty(),
            bairro = doc.getString("bairro").orEmpty(),
            cidade = doc.getString("cidade").orEmpty(),
            estado = doc.getString("estado").orEmpty(),
            cep = doc.getString("cep").orEmpty(),
            anonima = doc.getBoolean("anonima") ?: false,
            perfilUsuario = doc.getString("perfilUsuario") ?: "municipe",
            autorNome = if (doc.getBoolean("anonima") == true) "Anônimo" else doc.getString("autorNome").orEmpty(),
            fotoPublicId = doc.getString("fotoPublicId").orEmpty(),
            origem = doc.getString("origem").orEmpty()
        )
    }

    fun buscarTodos(sucesso: (List<Item>) -> Unit, erro: (String) -> Unit) {
        db.collection("denuncias").get()
            .addOnSuccessListener { sucesso(it.map(::converter).sortedByDescending { item -> item.criadoEm?.seconds ?: 0L }) }
            .addOnFailureListener { erro(it.localizedMessage ?: "Erro ao buscar denúncias.") }
    }

    fun observarTodas(sucesso: (List<Item>) -> Unit, erro: (String) -> Unit): ListenerRegistration =
        db.collection("denuncias").addSnapshotListener { resultado, ex ->
            if (ex != null) erro(ex.localizedMessage ?: "Erro ao atualizar denúncias.")
            else sucesso(resultado?.documents?.mapNotNull { doc -> (doc as? QueryDocumentSnapshot)?.let(::converter) }
                ?.sortedByDescending { item -> item.criadoEm?.seconds ?: 0L } ?: emptyList())
        }

    fun buscarMinhas(sucesso: (List<Item>) -> Unit, erro: (String) -> Unit) {
        val uid = auth.currentUser?.uid ?: run { erro("Usuário não autenticado."); return }
        db.collection("denuncias").whereEqualTo("usuarioUid", uid).get()
            .addOnSuccessListener { sucesso(it.map(::converter)) }
            .addOnFailureListener { erro(it.localizedMessage ?: "Erro ao buscar suas denúncias.") }
    }
}
package com.example.tcc

import android.content.Intent
import android.net.Uri
import android.os.Bundle
import android.text.InputFilter
import android.widget.ArrayAdapter
import android.widget.CheckBox
import android.widget.Button
import android.widget.ImageButton
import android.widget.EditText
import android.widget.ImageView
import android.widget.Spinner
import android.widget.Toast
import androidx.activity.enableEdgeToEdge
import androidx.activity.result.PickVisualMediaRequest
import androidx.activity.result.contract.ActivityResultContracts
import androidx.appcompat.app.AppCompatActivity
import androidx.core.view.ViewCompat
import androidx.core.view.WindowInsetsCompat
import androidx.core.widget.NestedScrollView

class ComplaintScreen : AppCompatActivity() {
    private lateinit var editTitulo: EditText
    private lateinit var spinnerCategoria: Spinner
    private lateinit var editDescricao: EditText
    private lateinit var editEndereco: EditText
    private lateinit var editNumero: EditText
    private lateinit var editComplemento: EditText
    private lateinit var editBairro: EditText
    private lateinit var editCidade: EditText
    private lateinit var editEstado: EditText
    private lateinit var editCep: EditText
    private lateinit var editData: EditText
    private lateinit var chkAnonima: CheckBox
    private lateinit var imageFoto: ImageView
    private lateinit var btnCadastrar: Button
    private var fotoUri: Uri? = null

    private val selecionarFoto = registerForActivityResult(
        ActivityResultContracts.PickVisualMedia()
    ) { uri ->
        if (uri != null) {
            fotoUri = uri
            imageFoto.setImageURI(uri)
        }
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        enableEdgeToEdge()
        setContentView(R.layout.activity_complaint_screen)
        ViewCompat.setOnApplyWindowInsetsListener(findViewById(R.id.main)) { v, insets ->
            val bars = insets.getInsets(WindowInsetsCompat.Type.systemBars())
            v.setPadding(bars.left, bars.top, bars.right, bars.bottom)
            insets
        }

        editTitulo = findViewById(R.id.editTitulo)
        spinnerCategoria = findViewById(R.id.spinnerCategoria)
        editDescricao = findViewById(R.id.editDescricao)
        editEndereco = findViewById(R.id.editEndereco)
        editNumero = findViewById(R.id.editNumero)
        editComplemento = findViewById(R.id.editComplemento)
        editBairro = findViewById(R.id.editBairro)
        editCidade = findViewById(R.id.editCidade)
        editEstado = findViewById(R.id.editEstado)
        editCep = findViewById(R.id.editCep)
        editData = findViewById(R.id.editData)
        chkAnonima = findViewById(R.id.chkAnonima)
        // mesmos limites das colunas do banco de dados
        mapOf(
            editTitulo to Regras.TITULO_MAX, editDescricao to Regras.DESCRICAO_MAX,
            editEndereco to Regras.RUA_MAX, editNumero to Regras.NUMERO_MAX,
            editComplemento to Regras.COMPLEMENTO_MAX, editBairro to Regras.BAIRRO_MAX,
            editCidade to Regras.CIDADE_MAX, editEstado to 2, editCep to 9
        ).forEach { (campo, max) -> campo.filters = arrayOf(InputFilter.LengthFilter(max)) }
        editData.setText(java.time.LocalDate.now().toString())
        imageFoto = findViewById(R.id.imageFoto)
        btnCadastrar = findViewById(R.id.btnCadastrar)

        val categorias = Regras.CATEGORIAS.toTypedArray()
        spinnerCategoria.adapter = ArrayAdapter(
            this, android.R.layout.simple_spinner_item, categorias
        ).apply {
            setDropDownViewResource(android.R.layout.simple_spinner_dropdown_item)
        }

        findViewById<Button>(R.id.btnSelecionarFoto).setOnClickListener {
            selecionarFoto.launch(PickVisualMediaRequest(ActivityResultContracts.PickVisualMedia.ImageOnly))
        }
        btnCadastrar.setOnClickListener { cadastrarDenuncia() }
        findViewById<ImageButton>(R.id.btnMain).setOnClickListener {
            startActivity(Intent(this, MainActivity::class.java))
            finish()
        }
        findViewById<ImageButton>(R.id.btnDenuncia).setOnClickListener {
            findViewById<NestedScrollView>(R.id.nestedScrollView).smoothScrollTo(0, 0)
        }
        findViewById<ImageButton>(R.id.btnUser).setOnClickListener {
            startActivity(Intent(this, UsersScreen::class.java))
            finish()
        }
        findViewById<Button>(R.id.btnNovaDenuncia).setOnClickListener { limparFormulario() }
    }

    private fun cadastrarDenuncia() {
        val titulo = editTitulo.text.toString().trim()
        val categoria = spinnerCategoria.selectedItem.toString()
        val descricao = editDescricao.text.toString().trim()
        val rua = editEndereco.text.toString().trim()
        val numero = editNumero.text.toString().trim()
        val complemento = editComplemento.text.toString().trim()
        val bairro = editBairro.text.toString().trim()
        val cidade = editCidade.text.toString().trim()
        val estado = editEstado.text.toString().trim().uppercase()
        val cep = editCep.text.toString().trim()
        val data = editData.text.toString().trim()
        val imagem = fotoUri
        val endereco = buildString {
            append(rua).append(", ").append(numero)
            if (complemento.isNotBlank()) append(", ").append(complemento)
            append(" - ").append(bairro)
        }

        if (titulo.isBlank()) { editTitulo.error = "Digite um título"; editTitulo.requestFocus(); return }
        if (descricao.isBlank()) { editDescricao.error = "Digite uma descrição"; editDescricao.requestFocus(); return }
        if (imagem == null) { Toast.makeText(this, "Selecione uma foto", Toast.LENGTH_SHORT).show(); return }
        if (rua.isBlank()) { editEndereco.error = "Digite a rua"; editEndereco.requestFocus(); return }
        if (numero.isBlank()) { editNumero.error = "Digite o número"; editNumero.requestFocus(); return }
        if (bairro.isBlank()) { editBairro.error = "Digite o bairro"; editBairro.requestFocus(); return }
        if (cidade.isBlank()) { editCidade.error = "Digite a cidade"; editCidade.requestFocus(); return }
        if (estado.length != 2) { editEstado.error = "Informe a UF com 2 letras"; editEstado.requestFocus(); return }
        if (cep.replace("-", "").length != 8 || !cep.replace("-", "").all(Char::isDigit)) { editCep.error = "Informe um CEP válido"; editCep.requestFocus(); return }
        if (!Regras.dataValida(data)) {
            editData.error = "Use uma data válida (AAAA-MM-DD), que não esteja no futuro"
            editData.requestFocus()
            return
        }

        val item = Item(
            titulo = titulo, categoria = categoria, descricao = descricao,
            fotoUri = imagem.toString(), endereco = endereco, dataOcorrencia = data,
            rua = rua, numero = numero, complemento = complemento, bairro = bairro,
            cidade = cidade, estado = estado, cep = Regras.somenteDigitos(cep),
            anonima = chkAnonima.isChecked
        )

        // O MySQL exige nome, sobrenome, CPF, cidade e UF do autor: confere antes de enviar.
        btnCadastrar.isEnabled = false
        btnCadastrar.text = "Verificando..."
        UsuarioRepository().perfilCompleto(
            resultado = { completo, atual ->
                if (isFinishing || isDestroyed) return@perfilCompleto
                if (completo) {
                    enviar(item, imagem)
                } else {
                    restaurarBotao()
                    pedirPerfilCompleto(atual) { cadastrarDenuncia() }
                }
            },
            erro = { mensagem ->
                if (isFinishing || isDestroyed) return@perfilCompleto
                restaurarBotao()
                Toast.makeText(this, mensagem, Toast.LENGTH_LONG).show()
            }
        )
    }

    private fun enviar(item: Item, imagem: Uri) {
        btnCadastrar.text = "Enviando..."
        ItemRepository.salvar(
            context = this,
            item = item,
            imagemLocal = imagem,
            sucesso = {
                if (isFinishing || isDestroyed) return@salvar
                Toast.makeText(this, "Denúncia cadastrada com sucesso!", Toast.LENGTH_SHORT).show()
                val intent = Intent(this, MainActivity::class.java)
                intent.flags = Intent.FLAG_ACTIVITY_CLEAR_TOP or Intent.FLAG_ACTIVITY_SINGLE_TOP
                startActivity(intent)
                finish()
            },
            erro = { mensagem ->
                if (isFinishing || isDestroyed) return@salvar
                restaurarBotao()
                Toast.makeText(this, mensagem, Toast.LENGTH_LONG).show()
            }
        )
    }

    private fun restaurarBotao() {
        btnCadastrar.isEnabled = true
        btnCadastrar.text = "Cadastrar"
    }

    private fun limparFormulario() {
        editTitulo.text?.clear()
        editDescricao.text?.clear()
        editEndereco.text?.clear()
        editNumero.text?.clear()
        editComplemento.text?.clear()
        editBairro.text?.clear()
        editCidade.text?.clear()
        editEstado.text?.clear()
        editCep.text?.clear()
        editData.setText(java.time.LocalDate.now().toString())
        chkAnonima.isChecked = false
        spinnerCategoria.setSelection(0)
        fotoUri = null
        imageFoto.setImageDrawable(null)
        findViewById<NestedScrollView>(R.id.nestedScrollView).smoothScrollTo(0, 0)
    }
}

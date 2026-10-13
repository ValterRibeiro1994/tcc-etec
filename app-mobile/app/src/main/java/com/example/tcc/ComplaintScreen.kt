package com.example.tcc

import android.content.Intent
import android.net.Uri
import android.os.Bundle
import android.widget.ArrayAdapter
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
        imageFoto = findViewById(R.id.imageFoto)
        btnCadastrar = findViewById(R.id.btnCadastrar)

        val categorias = arrayOf("Lixo", "Iluminação", "Pavimentação", "Outros")
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
        val endereco = editEndereco.text.toString().trim()
        val imagem = fotoUri

        if (titulo.isBlank()) { editTitulo.error = "Digite um título"; editTitulo.requestFocus(); return }
        if (descricao.isBlank()) { editDescricao.error = "Digite uma descrição"; editDescricao.requestFocus(); return }
        if (imagem == null) { Toast.makeText(this, "Selecione uma foto", Toast.LENGTH_SHORT).show(); return }
        if (endereco.isBlank()) { editEndereco.error = "Digite o endereço"; editEndereco.requestFocus(); return }

        btnCadastrar.isEnabled = false
        btnCadastrar.text = "Enviando..."
        val item = Item(titulo = titulo, categoria = categoria, descricao = descricao,
            fotoUri = imagem.toString(), endereco = endereco)
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
                btnCadastrar.isEnabled = true
                btnCadastrar.text = "Cadastrar"
                Toast.makeText(this, mensagem, Toast.LENGTH_LONG).show()
            }
        )
    }

    private fun limparFormulario() {
        editTitulo.text?.clear()
        editDescricao.text?.clear()
        editEndereco.text?.clear()
        spinnerCategoria.setSelection(0)
        fotoUri = null
        imageFoto.setImageDrawable(null)
        findViewById<NestedScrollView>(R.id.nestedScrollView).smoothScrollTo(0, 0)
    }
}

package com.example.tcc

import android.content.Intent
import android.net.Uri
import android.os.Bundle
import android.widget.ArrayAdapter
import android.widget.Button
import android.widget.EditText
import android.widget.ImageView
import android.widget.Spinner
import android.widget.Toast

import androidx.activity.enableEdgeToEdge
import androidx.activity.result.contract.ActivityResultContracts
import androidx.appcompat.app.AppCompatActivity
import androidx.core.view.ViewCompat
import androidx.core.view.WindowInsetsCompat

class ComplaintScreen : AppCompatActivity() {

    private lateinit var editTitulo: EditText
    private lateinit var spinnerCategoria: Spinner
    private lateinit var editDescricao: EditText
    private lateinit var editEndereco: EditText
    private lateinit var imageFoto: ImageView

    private var fotoUri: Uri? = null

    private val selecionarFoto =
        registerForActivityResult(
            ActivityResultContracts.OpenDocument()
        ) { uri ->

            if (uri != null) {

                // Mantém acesso à foto mesmo depois
                // que o aplicativo for fechado.

                try {

                    contentResolver.takePersistableUriPermission(
                        uri,
                        Intent.FLAG_GRANT_READ_URI_PERMISSION
                    )

                } catch (_: SecurityException) {
                }

                fotoUri = uri

                // Mostra a foto no formulário
                imageFoto.setImageURI(uri)
            }
        }


    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        enableEdgeToEdge()

        setContentView(
            R.layout.activity_complaint_screen
        )

        ViewCompat.setOnApplyWindowInsetsListener(
            findViewById(R.id.main)
        ) { v, insets ->

            val systemBars =
                insets.getInsets(
                    WindowInsetsCompat.Type.systemBars()
                )

            v.setPadding(
                systemBars.left,
                systemBars.top,
                systemBars.right,
                systemBars.bottom
            )

            insets
        }

        editTitulo =
            findViewById(R.id.editTitulo)

        spinnerCategoria =
            findViewById(R.id.spinnerCategoria)

        editDescricao =
            findViewById(R.id.editDescricao)

        editEndereco =
            findViewById(R.id.editEndereco)

        imageFoto =
            findViewById(R.id.imageFoto)

        configurarCategorias()

        val btnSelecionarFoto =
            findViewById<Button>(
                R.id.btnSelecionarFoto
            )

        btnSelecionarFoto.setOnClickListener {

            selecionarFoto.launch(
                arrayOf("image/*")
            )
        }

        val btnCadastrar =
            findViewById<Button>(
                R.id.btnCadastrar
            )

        btnCadastrar.setOnClickListener {

            cadastrarDenuncia()
        }

        val botaoHome =
            findViewById<Button>(
                R.id.btnMain
            )

        botaoHome.setOnClickListener {

            val intent = Intent(
                this,
                MainActivity::class.java
            )

            startActivity(intent)

            finish()
        }

        val botaoComplaint =
            findViewById<Button>(
                R.id.btnDenuncia
            )

        botaoComplaint.setOnClickListener {

            // Já estamos na tela de denúncia.
            // Então não precisamos abrir outra
            // ComplaintScreen.

            findViewById<androidx.core.widget.NestedScrollView>(
                R.id.nestedScrollView
            ).smoothScrollTo(0, 0)
        }

        val botaoUser =
            findViewById<Button>(
                R.id.btnUser
            )

        botaoUser.setOnClickListener {

            val intent = Intent(
                this,
                UsersScreen::class.java
            )

            startActivity(intent)

            finish()
        }

        val botaoNovaDenuncia =
            findViewById<Button>(
                R.id.btnNovaDenuncia
            )

        botaoNovaDenuncia.setOnClickListener {

            // Limpa o formulário para começar
            // uma nova denúncia.

            limparFormulario()
        }
    }
    private fun configurarCategorias() {

        val categorias = arrayOf(
            "Lixo",
            "Iluminação",
            "Pavimentação",
            "Outros"
        )

        val adapter =
            ArrayAdapter(
                this,
                android.R.layout.simple_spinner_item,
                categorias
            )

        adapter.setDropDownViewResource(
            android.R.layout.simple_spinner_dropdown_item
        )

        spinnerCategoria.adapter =
            adapter
    }

    private fun cadastrarDenuncia() {

        // Pegar informações digitadas

        val titulo =
            editTitulo.text
                .toString()
                .trim()

        val categoria =
            spinnerCategoria
                .selectedItem
                .toString()

        val descricao =
            editDescricao.text
                .toString()
                .trim()

        val endereco =
            editEndereco.text
                .toString()
                .trim()

        if (titulo.isEmpty()) {

            editTitulo.error =
                "Digite um título"

            editTitulo.requestFocus()

            return
        }

        if (descricao.isEmpty()) {

            editDescricao.error =
                "Digite uma descrição"

            editDescricao.requestFocus()

            return
        }


        // =========================
        // VALIDAR FOTO
        // =========================

        if (fotoUri == null) {

            Toast.makeText(
                this,
                "Selecione uma foto",
                Toast.LENGTH_SHORT
            ).show()

            return
        }

        if (endereco.isEmpty()) {

            editEndereco.error =
                "Digite o endereço"

            editEndereco.requestFocus()

            return
        }

        val novaDenuncia = Item(

            titulo = titulo,

            categoria = categoria,

            descricao = descricao,

            fotoUri = fotoUri.toString(),

            endereco = endereco
        )

        ItemRepository.salvar(
            this,
            novaDenuncia
        )


        Toast.makeText(
            this,
            "Denúncia cadastrada com sucesso!",
            Toast.LENGTH_SHORT
        ).show()

        val intent = Intent(
            this,
            MainActivity::class.java
        )

        // Evita criar várias MainActivity
        // empilhadas.
        intent.flags =
            Intent.FLAG_ACTIVITY_CLEAR_TOP or
                    Intent.FLAG_ACTIVITY_SINGLE_TOP

        startActivity(intent)

        finish()
    }

    private fun limparFormulario() {

        editTitulo.text?.clear()

        editDescricao.text?.clear()

        editEndereco.text?.clear()

        spinnerCategoria.setSelection(0)

        fotoUri = null

        imageFoto.setImageDrawable(null)


        // Volta para cima da página

        findViewById<androidx.core.widget.NestedScrollView>(
            R.id.nestedScrollView
        ).smoothScrollTo(0, 0)
    }
}
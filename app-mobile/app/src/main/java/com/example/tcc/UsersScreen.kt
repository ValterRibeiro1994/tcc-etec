package com.example.tcc

import android.content.Context
import android.content.Intent
import android.os.Bundle
import androidx.activity.enableEdgeToEdge
import androidx.appcompat.app.AppCompatActivity
import androidx.core.view.ViewCompat
import androidx.core.view.WindowInsetsCompat
import com.example.tcc.databinding.ActivityUsersScreenBinding

class UsersScreen : AppCompatActivity() {

    private lateinit var binding: ActivityUsersScreenBinding

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        enableEdgeToEdge()

        binding = ActivityUsersScreenBinding.inflate(layoutInflater)
        setContentView(binding.root)

        ViewCompat.setOnApplyWindowInsetsListener(binding.main) { v, insets ->

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

        val sharedPref =
            getSharedPreferences(
                "DadosDoUsuario",
                Context.MODE_PRIVATE
            )

        val nome =
            sharedPref.getString(
                "Nome_salvo",
                ""
            ) ?: ""

        val email =
            sharedPref.getString(
                "email_salvo",
                ""
            ) ?: ""

        if (nome.isNotBlank()) {

            binding.txtName.text = nome

        } else {

            binding.txtName.text = "Usuário"
        }

        if (email.isNotBlank()) {

            binding.txtEmail.text = email

        } else {

            binding.txtEmail.text =
                "Email não informado"
        }

        if (nome.isNotBlank()) {

            val primeiraLetra =
                nome.trim()
                    .first()
                    .uppercaseChar()

            binding.txtInicial.text =
                primeiraLetra.toString()

        } else {

            binding.txtInicial.text = "U"
        }

        carregarEstatisticas()

        binding.btnLogout.setOnClickListener {

            val intent =
                Intent(
                    this,
                    LoginScreen::class.java
                )
            intent.flags =
                Intent.FLAG_ACTIVITY_NEW_TASK or
                        Intent.FLAG_ACTIVITY_CLEAR_TASK

            startActivity(intent)

            finish()
        }

        binding.btnMain.setOnClickListener {

            val intent =
                Intent(
                    this,
                    MainActivity::class.java
                )

            startActivity(intent)

            finish()
        }

        binding.btnDenuncia.setOnClickListener {

            val intent =
                Intent(
                    this,
                    ComplaintScreen::class.java
                )

            startActivity(intent)

            finish()
        }

        binding.btnUser.setOnClickListener {

        }


        binding.btnDarkMode.setOnClickListener {

        }
    }

    private fun carregarEstatisticas() {

        val denuncias =
            ItemRepository.buscarTodos(this)

        val total =
            denuncias.size

        val resolvidas = 0

        val andamento = total


        binding.txtSolicitacoes.text =
            total.toString()

        binding.txtResolvidas.text =
            resolvidas.toString()

        binding.txtAndamento.text =
            andamento.toString()
    }

    override fun onResume() {
        super.onResume()

        carregarEstatisticas()
    }
}
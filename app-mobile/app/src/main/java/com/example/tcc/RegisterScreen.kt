package com.example.tcc

import android.os.Bundle
import android.widget.Button
import android.content.Intent
import android.widget.Toast
import android.content.Context
import androidx.activity.enableEdgeToEdge
import androidx.appcompat.app.AppCompatActivity
import androidx.core.view.ViewCompat
import androidx.core.view.WindowInsetsCompat
import com.example.tcc.databinding.ActivityRegisterScreenBinding

class RegisterScreen : AppCompatActivity() {
    private lateinit var binding: ActivityRegisterScreenBinding

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        enableEdgeToEdge()

        binding = ActivityRegisterScreenBinding.inflate(layoutInflater)
        setContentView(binding.root)

        ViewCompat.setOnApplyWindowInsetsListener(findViewById(R.id.main)) { v, insets ->
            val systemBars = insets.getInsets(WindowInsetsCompat.Type.systemBars())
            v.setPadding(systemBars.left, systemBars.top, systemBars.right, systemBars.bottom)
            insets
        }


        val usuarioRepository = UsuarioRepository()

        binding.button.setOnClickListener {

            val nome = binding.edtName.text.toString().trim()
            val email = binding.edtEmail.text.toString().trim()
            val senha = binding.edtPassword.text.toString()

            if (nome.isBlank()) {
                binding.edtName.error = "Digite seu nome"
                return@setOnClickListener
            }

            if (!android.util.Patterns.EMAIL_ADDRESS
                    .matcher(email).matches()
            ) {
                binding.edtEmail.error = "E-mail inválido"
                return@setOnClickListener
            }

            if (senha.length < 6) {
                binding.edtPassword.error =
                    "A senha deve ter pelo menos 6 caracteres"
                return@setOnClickListener
            }

            binding.button.isEnabled = false

            usuarioRepository.cadastrar(
                nome = nome,
                email = email,
                senha = senha,

                sucesso = {
                    Toast.makeText(
                        this,
                        "Cadastro realizado com sucesso!",
                        Toast.LENGTH_SHORT
                    ).show()

                    startActivity(
                        Intent(this, MainActivity::class.java)
                    )
                    finish()
                },

                erro = { mensagem ->
                    binding.button.isEnabled = true

                    Toast.makeText(
                        this,
                        mensagem,
                        Toast.LENGTH_LONG
                    ).show()
                }
            )
        }
    }
}
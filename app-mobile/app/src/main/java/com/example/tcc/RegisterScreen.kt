package com.example.tcc

import android.os.Bundle
import android.widget.Button
import android.content.Intent
import android.widget.Toast
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

        binding.button.setOnClickListener {
            val Email_atual = binding.edtEmail.text.toString()
            val Senha_atual = binding.edtPassword.text.toString()

            if (Email_atual.isNotEmpty() && Senha_atual.isNotEmpty()) {

                val intent = Intent(this, LoginScreen::class.java).apply {
                    putExtra("Key_Email", Email_atual)
                    putExtra("Key_Senha", Senha_atual)
                }

                startActivity(intent)
                finish()

            } else {
                // Exibe uma mensagem de aviso caso algum campo esteja em branco
                Toast.makeText(this, "Por favor, preencha todos os campos", Toast.LENGTH_SHORT).show()
            }
        }

    }
}
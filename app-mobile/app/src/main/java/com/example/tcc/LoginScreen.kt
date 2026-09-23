package com.example.tcc

import android.content.Intent
import android.os.Bundle
import android.widget.Button
import android.widget.Toast
import android.content.Context
import androidx.activity.enableEdgeToEdge
import androidx.appcompat.app.AppCompatActivity
import androidx.core.view.ViewCompat
import androidx.core.view.WindowInsetsCompat
import com.example.tcc.databinding.ActivityLoginScreenBinding

class LoginScreen : AppCompatActivity() {
    private lateinit var binding: ActivityLoginScreenBinding

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        enableEdgeToEdge()

        binding = ActivityLoginScreenBinding.inflate(layoutInflater)
        setContentView(binding.root)

        ViewCompat.setOnApplyWindowInsetsListener(findViewById(R.id.main)) { v, insets ->
            val systemBars = insets.getInsets(WindowInsetsCompat.Type.systemBars())
            v.setPadding(systemBars.left, systemBars.top, systemBars.right, systemBars.bottom)
            insets
        }

        val sharedPref = getSharedPreferences("DadosDoUsuario", Context.MODE_PRIVATE)

        val emailCadastrado = sharedPref.getString("email_salvo", "") ?: "."
        val senhaCadastrada = intent.getStringExtra("Key_Senha") ?: "."


        binding.btn.setOnClickListener {
            val emailLogin = binding.edtEmailLogin.text.toString()
            val senhaLogin = binding.edtPasswordLogin.text.toString()

            val intent = Intent(this, MainActivity::class.java)


            if (emailLogin == emailCadastrado && senhaLogin == senhaCadastrada) {
                startActivity(intent)
                finish()
            } else {
                Toast.makeText(this, "Senha ou Email estão errados", Toast.LENGTH_SHORT).show()
            }
        }

        binding.btnRegister.setOnClickListener {
            val intent = Intent(this, RegisterScreen::class.java)
            startActivity(intent)
        }

    }
}
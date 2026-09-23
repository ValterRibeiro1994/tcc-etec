package com.example.tcc

import android.content.Context
import android.os.Bundle
import androidx.activity.enableEdgeToEdge
import androidx.appcompat.app.AppCompatActivity
import androidx.core.view.ViewCompat
import androidx.core.view.WindowInsetsCompat
import com.example.tcc.databinding.ActivityRegisterScreenBinding
import com.example.tcc.databinding.ActivityUsersScreenBinding

class UsersScreen : AppCompatActivity() {
    private lateinit var binding: ActivityUsersScreenBinding

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        enableEdgeToEdge()

        binding = ActivityUsersScreenBinding.inflate(layoutInflater)
        setContentView(binding.root)

        ViewCompat.setOnApplyWindowInsetsListener(findViewById(R.id.main)) { v, insets ->
            val systemBars = insets.getInsets(WindowInsetsCompat.Type.systemBars())
            v.setPadding(systemBars.left, systemBars.top, systemBars.right, systemBars.bottom)
            insets
        }

        val sharedPref = getSharedPreferences("DadosDoUsuario", Context.MODE_PRIVATE)

        val email = sharedPref.getString("email_salvo", "") ?: "."

        binding.txtEmail.text = "Email: " + email
    }
}
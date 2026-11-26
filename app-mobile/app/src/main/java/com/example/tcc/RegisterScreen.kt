package com.example.tcc

import android.content.Intent
import android.os.Bundle
import android.text.InputFilter
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

        // mesmos limites das colunas de tb_usuario
        binding.edtName.filters = arrayOf(InputFilter.LengthFilter(20))
        binding.edtSobrenome.filters = arrayOf(InputFilter.LengthFilter(60))
        binding.edtCpf.filters = arrayOf(InputFilter.LengthFilter(14))
        binding.edtCidade.filters = arrayOf(InputFilter.LengthFilter(Regras.CIDADE_MAX))
        binding.edtEstado.filters = arrayOf(InputFilter.LengthFilter(2))

        val usuarioRepository = UsuarioRepository()

        binding.button.setOnClickListener {
            val perfil = PerfilUsuario(
                nome = binding.edtName.text.toString().trim(),
                sobrenome = binding.edtSobrenome.text.toString().trim(),
                cpf = binding.edtCpf.text.toString(),
                cidade = binding.edtCidade.text.toString().trim(),
                estado = binding.edtEstado.text.toString().trim().uppercase()
            )
            val email = binding.edtEmail.text.toString().trim()
            val senha = binding.edtPassword.text.toString()

            when {
                !Regras.nomeValido(perfil.nome) -> {
                    binding.edtName.error = "Nome deve ter de 3 a 20 caracteres"; return@setOnClickListener
                }
                !Regras.sobrenomeValido(perfil.sobrenome) -> {
                    binding.edtSobrenome.error = "Sobrenome deve ter de 3 a 60 caracteres"; return@setOnClickListener
                }
                !Regras.cpfValido(perfil.cpf) -> {
                    binding.edtCpf.error = "CPF inválido"; return@setOnClickListener
                }
                perfil.cidade.isBlank() -> {
                    binding.edtCidade.error = "Digite sua cidade"; return@setOnClickListener
                }
                perfil.estado !in Regras.UFS -> {
                    binding.edtEstado.error = "Use a sigla com 2 letras (ex.: SP)"; return@setOnClickListener
                }
                !android.util.Patterns.EMAIL_ADDRESS.matcher(email).matches() || email.length > 120 -> {
                    binding.edtEmail.error = "E-mail inválido"; return@setOnClickListener
                }
                senha.length < 6 -> {
                    binding.edtPassword.error = "A senha deve ter pelo menos 6 caracteres"
                    return@setOnClickListener
                }
            }

            binding.button.isEnabled = false

            usuarioRepository.cadastrar(
                perfil = perfil,
                email = email,
                senha = senha,
                sucesso = {
                    Toast.makeText(this, "Cadastro realizado com sucesso!", Toast.LENGTH_SHORT).show()
                    startActivity(Intent(this, MainActivity::class.java))
                    finish()
                },
                erro = { mensagem ->
                    binding.button.isEnabled = true
                    Toast.makeText(this, mensagem, Toast.LENGTH_LONG).show()
                }
            )
        }
    }
}

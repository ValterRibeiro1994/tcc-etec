package com.example.tcc

import android.content.Intent
import android.os.Bundle
import android.widget.Toast
import androidx.activity.enableEdgeToEdge
import androidx.appcompat.app.AppCompatActivity
import androidx.core.view.ViewCompat
import androidx.core.view.WindowInsetsCompat
import com.example.tcc.databinding.ActivityUsersScreenBinding
import com.google.firebase.auth.FirebaseAuth
import com.google.firebase.auth.FirebaseUser
import com.google.firebase.firestore.FirebaseFirestore

class UsersScreen : AppCompatActivity() {

    private lateinit var binding: ActivityUsersScreenBinding
    private lateinit var auth: FirebaseAuth
    private lateinit var db: FirebaseFirestore

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        enableEdgeToEdge()

        binding = ActivityUsersScreenBinding.inflate(layoutInflater)
        setContentView(binding.root)

        auth = FirebaseAuth.getInstance()
        db = FirebaseFirestore.getInstance()

        ViewCompat.setOnApplyWindowInsetsListener(binding.main) { v, insets ->

            val systemBars = insets.getInsets(
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

        // Verifica se existe usuário autenticado
        val usuario = auth.currentUser

        if (usuario == null) {
            abrirLogin()
            return
        }

        // Carrega os dados do usuário no Firebase
        carregarUsuario(usuario)

        // Carrega as estatísticas das denúncias
        carregarEstatisticas()

        // BOTÃO SAIR
        binding.btnLogout.setOnClickListener {

            auth.signOut()

            abrirLogin()
        }

        // BOTÃO PÁGINA PRINCIPAL
        binding.btnMain.setOnClickListener {

            startActivity(
                Intent(this, MainActivity::class.java)
            )

            finish()
        }

        // BOTÃO DENÚNCIAS
        binding.btnDenuncia.setOnClickListener {

            startActivity(
                Intent(this, ComplaintScreen::class.java)
            )

            finish()
        }

        // BOTÃO PERFIL
        binding.btnUser.setOnClickListener {
            // Já está na tela de perfil
        }

        // BOTÃO MODO ESCURO
        binding.btnDarkMode.setOnClickListener {
            // Funcionalidade futura
        }
    }

    // BUSCAR DADOS DO USUÁRIO NO FIRESTORE
    private fun carregarUsuario(usuario: FirebaseUser) {

        // Exibe os dados disponíveis enquanto consulta o Firestore
        val nomeInicial = usuario.displayName ?: "Usuário"

        atualizarInterface(
            nomeInicial,
            usuario.email ?: "E-mail não informado"
        )

        db.collection("usuarios")
            .document(usuario.uid)
            .get()
            .addOnSuccessListener { documento ->

                if (isFinishing || isDestroyed) {
                    return@addOnSuccessListener
                }

                // Evita mostrar dados se a sessão mudou
                if (auth.currentUser?.uid != usuario.uid) {
                    return@addOnSuccessListener
                }

                if (documento.exists()) {

                    val nome = documento.getString("nome")
                        ?.takeIf { it.isNotBlank() }
                        ?: nomeInicial

                    val email = documento.getString("email")
                        ?.takeIf { it.isNotBlank() }
                        ?: usuario.email
                        ?: "E-mail não informado"

                    atualizarInterface(nome, email)

                } else {

                    // Usuário autenticado, mas sem perfil no Firestore
                    atualizarInterface(
                        nomeInicial,
                        usuario.email ?: "E-mail não informado"
                    )
                }
            }
            .addOnFailureListener { erro ->

                if (isFinishing || isDestroyed) {
                    return@addOnFailureListener
                }

                Toast.makeText(
                    this,
                    "Não foi possível carregar o perfil: ${erro.localizedMessage}",
                    Toast.LENGTH_LONG
                ).show()
            }
    }

    // ATUALIZAR NOME, EMAIL E INICIAL DO AVATAR
    private fun atualizarInterface(nome: String, email: String) {

        binding.txtName.text = nome

        binding.txtEmail.text = email

        val inicial = nome.trim()
            .firstOrNull()
            ?.uppercaseChar()
            ?.toString()
            ?: "U"

        binding.txtInicial.text = inicial
    }

    // ESTATÍSTICAS DAS DENÚNCIAS

    private fun carregarEstatisticas() {

        binding.txtSolicitacoes.text = "..."
        binding.txtResolvidas.text = "..."
        binding.txtAndamento.text = "..."

        val uidConsultado = FirebaseAuth.getInstance()
            .currentUser?.uid ?: return

        ItemRepository.buscarMinhas(
            sucesso = { denuncias ->

                if (isFinishing || isDestroyed) {
                    return@buscarMinhas
                }

                if (FirebaseAuth.getInstance().currentUser?.uid != uidConsultado) {
                    return@buscarMinhas
                }

                val total = denuncias.size

                val resolvidas = denuncias.count {
                    it.status == "resolvida"
                }

                val andamento = denuncias.count {
                    it.status == "em_analise"
                }

                binding.txtSolicitacoes.text = total.toString()
                binding.txtResolvidas.text = resolvidas.toString()
                binding.txtAndamento.text = andamento.toString()
            },

            erro = {
                if (!isFinishing && !isDestroyed) {
                    binding.txtSolicitacoes.text = "-"
                    binding.txtResolvidas.text = "-"
                    binding.txtAndamento.text = "-"
                }
            }
        )
    }


    // VOLTAR PARA A TELA DE LOGIN
    private fun abrirLogin() {

        val intent = Intent(
            this,
            LoginScreen::class.java
        )

        intent.flags =
            Intent.FLAG_ACTIVITY_NEW_TASK or
                    Intent.FLAG_ACTIVITY_CLEAR_TASK

        startActivity(intent)
        finish()
    }

    override fun onResume() {
        super.onResume()

        carregarEstatisticas()
    }
}

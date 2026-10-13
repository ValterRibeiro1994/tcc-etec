
package com.example.tcc

import android.content.Context
import android.content.Intent
import android.os.Bundle
import android.widget.Toast
import androidx.activity.enableEdgeToEdge
import androidx.appcompat.app.AppCompatActivity
import androidx.core.view.ViewCompat
import androidx.core.view.WindowInsetsCompat
import androidx.credentials.CredentialManager
import androidx.credentials.CustomCredential
import androidx.credentials.GetCredentialRequest
import androidx.credentials.exceptions.GetCredentialCancellationException
import androidx.activity.result.PickVisualMediaRequest
import androidx.lifecycle.lifecycleScope
import com.example.tcc.databinding.ActivityLoginScreenBinding
import com.google.android.libraries.identity.googleid.GetSignInWithGoogleOption
import com.google.android.libraries.identity.googleid.GoogleIdTokenCredential
import com.google.firebase.auth.FirebaseAuth
import com.google.firebase.auth.GoogleAuthProvider
import kotlinx.coroutines.launch

class LoginScreen : AppCompatActivity() {

    private lateinit var binding: ActivityLoginScreenBinding
    private lateinit var auth: FirebaseAuth
    private lateinit var credentialManager: CredentialManager

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        enableEdgeToEdge()

        binding = ActivityLoginScreenBinding.inflate(layoutInflater)
        setContentView(binding.root)

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

        auth = FirebaseAuth.getInstance()
        credentialManager = CredentialManager.create(this)




        binding.btn.setOnClickListener {

            val email = binding.edtEmailLogin.text
                .toString().trim()

            val senha = binding.edtPasswordLogin.text
                .toString()

            if (email.isBlank() || senha.isBlank()) {
                Toast.makeText(
                    this,
                    "Preencha e-mail e senha",
                    Toast.LENGTH_SHORT
                ).show()

                return@setOnClickListener
            }

            binding.btn.isEnabled = false

            auth.signInWithEmailAndPassword(email, senha)
                .addOnSuccessListener {

                    startActivity(
                        Intent(this, MainActivity::class.java)
                    )

                    finish()
                }
                .addOnFailureListener {

                    binding.btn.isEnabled = true

                    Toast.makeText(
                        this,
                        "Não foi possível entrar. Verifique seus dados.",
                        Toast.LENGTH_LONG
                    ).show()
                }
        }


        // TELA DE CADASTRO
        binding.btnRegister.setOnClickListener {
            startActivity(
                Intent(this, RegisterScreen::class.java)
            )
        }

        // LOGIN COM GOOGLE
        binding.btnGoogle.setOnClickListener {
            entrarComGoogle()
        }
    }

    private fun entrarComGoogle() {

        val googleOption =
            GetSignInWithGoogleOption.Builder(
                getString(R.string.default_web_client_id)
            ).build()

        val request = GetCredentialRequest.Builder()
            .addCredentialOption(googleOption)
            .build()

        lifecycleScope.launch {

            try {
                val result = credentialManager.getCredential(
                    context = this@LoginScreen,
                    request = request
                )

                val credential = result.credential

                if (
                    credential is CustomCredential &&
                    credential.type ==
                    GoogleIdTokenCredential.TYPE_GOOGLE_ID_TOKEN_CREDENTIAL
                ) {
                    val googleCredential =
                        GoogleIdTokenCredential.createFrom(
                            credential.data
                        )

                    autenticarFirebase(googleCredential.idToken)

                } else {
                    Toast.makeText(
                        this@LoginScreen,
                        "Credencial Google inválida",
                        Toast.LENGTH_SHORT
                    ).show()
                }

            } catch (e: GetCredentialCancellationException) {
                // O usuário cancelou o login.
            } catch (e: Exception) {
                Toast.makeText(
                    this@LoginScreen,
                    "Erro ao entrar com Google: ${e.localizedMessage}",
                    Toast.LENGTH_LONG
                ).show()
            }
        }
    }

    private fun autenticarFirebase(idToken: String) {

        val firebaseCredential =
            GoogleAuthProvider.getCredential(idToken, null)

        auth.signInWithCredential(firebaseCredential)
            .addOnCompleteListener(this) { task ->

                if (task.isSuccessful) {

                    val usuario = auth.currentUser

                    val nome = usuario?.displayName ?: "Usuário"
                    val email = usuario?.email ?: ""

                    val sharedPref = getSharedPreferences(
                        "DadosDoUsuario",
                        Context.MODE_PRIVATE
                    )

                    sharedPref.edit()
                        .putString("Nome_salvo", nome)
                        .putString("email_salvo", email)
                        .apply()

                    Toast.makeText(
                        this,
                        "Bem-vindo, $nome!",
                        Toast.LENGTH_SHORT
                    ).show()

                    abrirMain()

                } else {

                    Toast.makeText(
                        this,
                        "Erro na autenticação: ${task.exception?.localizedMessage}",
                        Toast.LENGTH_LONG
                    ).show()
                }
            }
    }

    private fun abrirMain() {

        val intent = Intent(
            this,
            MainActivity::class.java
        )

        intent.flags =
            Intent.FLAG_ACTIVITY_NEW_TASK or
                    Intent.FLAG_ACTIVITY_CLEAR_TASK

        startActivity(intent)
        finish()
    }
}

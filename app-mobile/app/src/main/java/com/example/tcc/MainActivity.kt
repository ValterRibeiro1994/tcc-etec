package com.example.tcc

import android.content.Intent
import android.os.Bundle
import android.view.View
import android.widget.Button
import android.widget.TextView
import android.widget.Toast
import androidx.activity.enableEdgeToEdge
import androidx.appcompat.app.AppCompatActivity
import androidx.core.view.ViewCompat
import androidx.core.view.WindowInsetsCompat
import androidx.recyclerview.widget.LinearLayoutManager
import androidx.recyclerview.widget.RecyclerView
import com.example.tcc.databinding.ActivityMainBinding
import com.google.firebase.firestore.ListenerRegistration

class MainActivity : AppCompatActivity() {
    private lateinit var binding: ActivityMainBinding
    private lateinit var recyclerItens: RecyclerView
    private lateinit var textSemResultados: TextView
    private lateinit var itemAdapter: ItemAdapter
    private var listaCompleta: List<Item> = emptyList()
    private var filtroAtual = "Todos"
    private var listener: ListenerRegistration? = null

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        enableEdgeToEdge()
        binding = ActivityMainBinding.inflate(layoutInflater)
        setContentView(binding.root)
        ViewCompat.setOnApplyWindowInsetsListener(binding.main) { v, insets ->
            val bars = insets.getInsets(WindowInsetsCompat.Type.systemBars())
            v.setPadding(bars.left, bars.top, bars.right, bars.bottom)
            insets
        }
        recyclerItens = findViewById(R.id.recyclerItens)
        textSemResultados = findViewById(R.id.textSemResultados)
        recyclerItens.layoutManager = LinearLayoutManager(this)
        itemAdapter = ItemAdapter(emptyList())
        recyclerItens.adapter = itemAdapter
        configurarFiltros()
        findViewById<Button>(R.id.btnMain).setOnClickListener {
            if (itemAdapter.itemCount > 0) recyclerItens.smoothScrollToPosition(0)
        }
        findViewById<Button>(R.id.btnDenuncia).setOnClickListener {
            startActivity(Intent(this, ComplaintScreen::class.java))
        }
        findViewById<Button>(R.id.btnNovaDenuncia).setOnClickListener {
            startActivity(Intent(this, ComplaintScreen::class.java))
        }
        findViewById<Button>(R.id.btnUser).setOnClickListener {
            startActivity(Intent(this, UsersScreen::class.java))
        }
    }

    override fun onStart() {
        super.onStart()
        listener = ItemRepository.observarTodas(
            sucesso = { itens ->
                listaCompleta = itens
                filtrar(filtroAtual)
            },
            erro = { mensagem ->
                Toast.makeText(this, mensagem, Toast.LENGTH_LONG).show()
            }
        )
    }

    override fun onStop() {
        listener?.remove()
        listener = null
        super.onStop()
    }

    private fun configurarFiltros() {
        mapOf(
            R.id.btnTodos to "Todos",
            R.id.btnLixo to "Lixo",
            R.id.btnIluminacao to "Iluminação",
            R.id.btnPavimentacao to "Pavimentação",
            R.id.btnOutros to "Outros"
        ).forEach { (id, categoria) ->
            findViewById<Button>(id).setOnClickListener { filtrar(categoria) }
        }
    }

    private fun filtrar(categoria: String) {
        filtroAtual = categoria
        val filtrada = if (categoria == "Todos") listaCompleta else listaCompleta.filter {
            it.categoria.equals(categoria, ignoreCase = true)
        }
        itemAdapter.atualizarLista(filtrada)
        recyclerItens.visibility = if (filtrada.isEmpty()) View.GONE else View.VISIBLE
        textSemResultados.visibility = if (filtrada.isEmpty()) View.VISIBLE else View.GONE
    }
}

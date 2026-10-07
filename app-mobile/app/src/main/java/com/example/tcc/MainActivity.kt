package com.example.tcc

import android.content.Intent
import android.os.Bundle
import android.view.View
import android.widget.Button
import android.widget.TextView

import androidx.activity.enableEdgeToEdge
import androidx.appcompat.app.AppCompatActivity
import androidx.core.view.ViewCompat
import androidx.core.view.WindowInsetsCompat
import androidx.recyclerview.widget.LinearLayoutManager
import androidx.recyclerview.widget.RecyclerView

import com.example.tcc.databinding.ActivityMainBinding

class MainActivity : AppCompatActivity() {

    private lateinit var binding: ActivityMainBinding

    private lateinit var recyclerItens: RecyclerView
    private lateinit var textSemResultados: TextView
    private lateinit var itemAdapter: ItemAdapter

    private var listaCompleta: List<Item> = emptyList()

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        enableEdgeToEdge()

        binding = ActivityMainBinding.inflate(layoutInflater)
        setContentView(binding.root)

        // ==============================
        // BARRA DO SISTEMA
        // ==============================

        ViewCompat.setOnApplyWindowInsetsListener(binding.main) { v, insets ->

            val systemBars =
                insets.getInsets(WindowInsetsCompat.Type.systemBars())

            v.setPadding(
                systemBars.left,
                systemBars.top,
                systemBars.right,
                systemBars.bottom
            )

            insets
        }


        // ==============================
        // RECYCLERVIEW
        // ==============================

        recyclerItens = findViewById(R.id.recyclerItens)

        textSemResultados =
            findViewById(R.id.textSemResultados)

        recyclerItens.layoutManager =
            LinearLayoutManager(this)

        itemAdapter =
            ItemAdapter(emptyList())

        recyclerItens.adapter =
            itemAdapter


        // ==============================
        // FILTROS
        // ==============================

        configurarFiltros()


        // ==============================
        // BOTÃO MAIN
        // ==============================

        val botaoHome =
            findViewById<Button>(R.id.btnMain)

        botaoHome.setOnClickListener {

            // Já estamos na MainActivity.
            // Apenas volta a lista para o topo.

            if (itemAdapter.itemCount > 0) {
                recyclerItens.smoothScrollToPosition(0)
            }
        }


        // ==============================
        // BOTÃO DENÚNCIA
        // ==============================

        val botaoComplaint =
            findViewById<Button>(R.id.btnDenuncia)

        botaoComplaint.setOnClickListener {

            val intent = Intent(
                this,
                ComplaintScreen::class.java
            )

            startActivity(intent)
        }


        // ==============================
        // + NOVA DENÚNCIA
        // ==============================

        val botaoNovaDenuncia =
            findViewById<Button>(R.id.btnNovaDenuncia)

        botaoNovaDenuncia.setOnClickListener {

            val intent = Intent(
                this,
                ComplaintScreen::class.java
            )

            startActivity(intent)
        }


        // ==============================
        // BOTÃO USER
        // ==============================

        val botaoUser =
            findViewById<Button>(R.id.btnUser)

        botaoUser.setOnClickListener {

            val intent = Intent(
                this,
                UsersScreen::class.java
            )

            startActivity(intent)
        }
    }


    // =====================================
    // SEMPRE ATUALIZA AO VOLTAR PARA MAIN
    // =====================================

    override fun onResume() {
        super.onResume()

        carregarDenuncias()
    }


    // =====================================
    // CARREGAR DENÚNCIAS
    // =====================================

    private fun carregarDenuncias() {

        listaCompleta =
            ItemRepository.buscarTodos(this)

        filtrar("Todos")
    }


    // =====================================
    // CONFIGURAR FILTROS
    // =====================================

    private fun configurarFiltros() {

        val btnTodos =
            findViewById<Button>(R.id.btnTodos)

        val btnLixo =
            findViewById<Button>(R.id.btnLixo)

        val btnIluminacao =
            findViewById<Button>(R.id.btnIluminacao)

        val btnPavimentacao =
            findViewById<Button>(R.id.btnPavimentacao)

        val btnOutros =
            findViewById<Button>(R.id.btnOutros)


        btnTodos.setOnClickListener {
            filtrar("Todos")
        }


        btnLixo.setOnClickListener {
            filtrar("Lixo")
        }


        btnIluminacao.setOnClickListener {
            filtrar("Iluminação")
        }


        btnPavimentacao.setOnClickListener {
            filtrar("Pavimentação")
        }


        btnOutros.setOnClickListener {
            filtrar("Outros")
        }
    }


    // =====================================
    // FILTRAR
    // =====================================

    private fun filtrar(categoria: String) {

        val listaFiltrada =
            if (categoria == "Todos") {

                listaCompleta

            } else {

                listaCompleta.filter { item ->

                    item.categoria.equals(
                        categoria,
                        ignoreCase = true
                    )
                }
            }


        itemAdapter.atualizarLista(
            listaFiltrada
        )


        // Se não tiver resultado

        if (listaFiltrada.isEmpty()) {

            recyclerItens.visibility =
                View.GONE

            textSemResultados.visibility =
                View.VISIBLE

        } else {

            recyclerItens.visibility =
                View.VISIBLE

            textSemResultados.visibility =
                View.GONE
        }
    }
}
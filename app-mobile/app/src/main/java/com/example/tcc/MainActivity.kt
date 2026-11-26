package com.example.tcc

import android.content.Intent
import android.os.Bundle
import android.view.View
import android.widget.Button
import android.widget.ImageButton
import android.widget.TextView

import androidx.activity.enableEdgeToEdge
import androidx.appcompat.app.AppCompatActivity
import androidx.core.content.ContextCompat
import androidx.core.view.ViewCompat
import androidx.core.view.WindowInsetsCompat
import androidx.recyclerview.widget.LinearLayoutManager
import androidx.recyclerview.widget.RecyclerView

import com.example.tcc.databinding.ActivityMainBinding
import com.google.android.material.button.MaterialButton

class MainActivity : AppCompatActivity() {

    private lateinit var binding: ActivityMainBinding

    private lateinit var recyclerItens: RecyclerView
    private lateinit var textSemResultados: TextView
    private lateinit var itemAdapter: ItemAdapter

    private var listaCompleta: List<Item> = emptyList()

    private var botoesFiltro: Map<String, MaterialButton> = emptyMap()
    private var categoriaAtual = TODOS

    companion object {
        private const val TODOS = "Todos"
    }

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
            findViewById<ImageButton>(R.id.btnMain)

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
            findViewById<ImageButton>(R.id.btnDenuncia)

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
            findViewById<ImageButton>(R.id.btnUser)

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
        ItemRepository.buscarTodos(
            sucesso = { lista ->
                if (isFinishing || isDestroyed) return@buscarTodos
                listaCompleta = lista
                // Mantém o filtro que o usuário escolheu ao voltar para a tela
                filtrar(categoriaAtual)
            },
            erro = { _ -> }
        )
    }


    // =====================================
    // CONFIGURAR FILTROS
    // =====================================

    private fun configurarFiltros() {

        // Cada botão aponta para o MESMO texto que a denúncia grava
        // (Regras.CATEGORIAS), senão o filtro nunca encontra nada.
        botoesFiltro = mapOf(
            TODOS to findViewById(R.id.btnTodos),
            Regras.CATEGORIAS[0] to findViewById(R.id.btnIluminacao),
            Regras.CATEGORIAS[1] to findViewById(R.id.btnInfraestrutura),
            Regras.CATEGORIAS[2] to findViewById(R.id.btnSaneamento)
        )

        botoesFiltro.forEach { (categoria, botao) ->
            botao.setOnClickListener { filtrar(categoria) }
        }
    }


    // =====================================
    // DESTACAR O FILTRO SELECIONADO
    // =====================================

    private fun destacarFiltro(categoria: String) {

        val corSelecionado = ContextCompat.getColorStateList(this, R.color.sbc_botao_principal)
        val corNormal = ContextCompat.getColorStateList(this, R.color.sbc_circulo_stat)
        val textoSelecionado = ContextCompat.getColorStateList(this, R.color.white)
        val textoNormal = ContextCompat.getColorStateList(this, R.color.sbc_texto)

        botoesFiltro.forEach { (cat, botao) ->
            val ativo = cat == categoria
            botao.backgroundTintList = if (ativo) corSelecionado else corNormal
            botao.setTextColor(if (ativo) textoSelecionado else textoNormal)
        }
    }


    // =====================================
    // FILTRAR
    // =====================================

    private fun filtrar(categoria: String) {

        categoriaAtual = categoria
        destacarFiltro(categoria)

        val listaFiltrada =
            if (categoria == TODOS) {

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
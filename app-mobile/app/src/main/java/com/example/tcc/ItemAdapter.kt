package com.example.tcc

import android.net.Uri
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.ImageView
import android.widget.TextView
import androidx.recyclerview.widget.RecyclerView

class ItemAdapter(
    private var lista: List<Item>
) : RecyclerView.Adapter<ItemAdapter.ItemViewHolder>() {

    class ItemViewHolder(view: View) :
        RecyclerView.ViewHolder(view) {

        val foto: ImageView =
            view.findViewById(R.id.cardFoto)

        val titulo: TextView =
            view.findViewById(R.id.cardTitulo)

        val categoria: TextView =
            view.findViewById(R.id.cardCategoria)

        val descricao: TextView =
            view.findViewById(R.id.cardDescricao)

        val endereco: TextView =
            view.findViewById(R.id.cardEndereco)

        val status: TextView =
            view.findViewById(R.id.cardStatus)

        val tempo: TextView =
            view.findViewById(R.id.cardTempo)
    }

    override fun onCreateViewHolder(
        parent: ViewGroup,
        viewType: Int
    ): ItemViewHolder {

        val view = LayoutInflater
            .from(parent.context)
            .inflate(
                R.layout.item_card,
                parent,
                false
            )

        return ItemViewHolder(view)
    }

    override fun onBindViewHolder(
        holder: ItemViewHolder,
        position: Int
    ) {

        val item = lista[position]

        holder.titulo.text = item.titulo
        holder.categoria.text = item.categoria
        holder.descricao.text = item.descricao
        holder.endereco.text = item.endereco

        // Por enquanto
        holder.status.text = "Em andamento"
        holder.tempo.text = "agora"

        if (!item.fotoUri.isNullOrEmpty()) {

            try {
                holder.foto.setImageURI(
                    Uri.parse(item.fotoUri)
                )
            } catch (_: Exception) {
                holder.foto.setImageDrawable(null)
            }
        }
    }

    override fun getItemCount(): Int {
        return lista.size
    }

    fun atualizarLista(novaLista: List<Item>) {
        lista = novaLista
        notifyDataSetChanged()
    }
}

package com.example.tcc

import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.ImageView
import android.widget.TextView
import androidx.recyclerview.widget.RecyclerView
import com.bumptech.glide.Glide
import com.bumptech.glide.load.engine.DiskCacheStrategy

class ItemAdapter(
    private var itens: List<Item>
) : RecyclerView.Adapter<ItemAdapter.ItemViewHolder>() {

    class ItemViewHolder(view: View) :
        RecyclerView.ViewHolder(view) {

        val foto: ImageView =
            view.findViewById(R.id.cardFoto)

        val status: TextView =
            view.findViewById(R.id.cardStatus)

        val tempo: TextView =
            view.findViewById(R.id.cardTempo)

        val titulo: TextView =
            view.findViewById(R.id.cardTitulo)

        val categoria: TextView =
            view.findViewById(R.id.cardCategoria)

        val descricao: TextView =
            view.findViewById(R.id.cardDescricao)

        val endereco: TextView =
            view.findViewById(R.id.cardEndereco)
    }

    override fun onCreateViewHolder(
        parent: ViewGroup,
        viewType: Int
    ): ItemViewHolder {

        val view = LayoutInflater.from(parent.context)
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
        val item = itens[position]

        holder.titulo.text = item.titulo
        holder.categoria.text = item.categoria
        holder.descricao.text = item.descricao
        holder.endereco.text = item.endereco

        holder.status.text = when (item.status) {
            "resolvida" -> "Resolvida"
            "em_analise" -> "Em análise"
            else -> "Pendente"
        }

        // O modelo Item ainda não possui data de criação.
        holder.tempo.text = ""

        // Carregar foto do Cloudinary
        Glide.with(holder.itemView.context)
            .load(item.fotoUri)
            .diskCacheStrategy(DiskCacheStrategy.AUTOMATIC)
            .centerCrop()
            .placeholder(android.R.drawable.ic_menu_gallery)
            .error(android.R.drawable.ic_menu_report_image)
            .into(holder.foto)
    }

    override fun getItemCount(): Int = itens.size

    fun atualizarLista(novaLista: List<Item>) {
        itens = novaLista
        notifyDataSetChanged()
    }

    override fun onViewRecycled(holder: ItemViewHolder) {
        Glide.with(holder.itemView.context)
            .clear(holder.foto)

        super.onViewRecycled(holder)
    }
}

package com.alima.sinyalsahamindo.ui

import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.TextView
import androidx.recyclerview.widget.RecyclerView
import com.alima.sinyalsahamindo.R
import com.alima.sinyalsahamindo.data.model.SignalItem

class SignalAdapter : RecyclerView.Adapter<SignalAdapter.SignalViewHolder>() {
    private val items = mutableListOf<SignalItem>()

    fun submitData(newItems: List<SignalItem>) {
        items.clear()
        items.addAll(newItems.sortedByDescending { it.id })
        notifyDataSetChanged()
    }

    fun upsertSignal(signal: SignalItem) {
        val index = items.indexOfFirst { it.id == signal.id }
        if (index >= 0) {
            items[index] = signal
        } else {
            items.add(0, signal)
        }
        items.sortByDescending { it.id }
        notifyDataSetChanged()
    }

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): SignalViewHolder {
        val view = LayoutInflater.from(parent.context).inflate(R.layout.item_signal_chat, parent, false)
        return SignalViewHolder(view)
    }

    override fun onBindViewHolder(holder: SignalViewHolder, position: Int) {
        holder.bind(items[position])
    }

    override fun getItemCount(): Int = items.size

    class SignalViewHolder(itemView: View) : RecyclerView.ViewHolder(itemView) {
        private val bubble = itemView.findViewById<View>(R.id.bubble)
        private val tvTitle = itemView.findViewById<TextView>(R.id.tvTitle)
        private val tvType = itemView.findViewById<TextView>(R.id.tvType)
        private val tvBody = itemView.findViewById<TextView>(R.id.tvBody)
        private val tvMeta = itemView.findViewById<TextView>(R.id.tvMeta)

        fun bind(item: SignalItem) {
            tvTitle.text = item.title ?: "-"
            val type = item.signal_type?.uppercase() ?: "-"
            tvType.text = "[$type] ${item.stock_code ?: "-"}"

            val body = buildString {
                append("Entry: ${item.entry_price ?: "-"}")
                append("\nTP: ${item.take_profit ?: "-"} | SL: ${item.stop_loss ?: "-"}")
                if (!item.note.isNullOrBlank()) {
                    append("\nCatatan: ${item.note}")
                }
            }
            tvBody.text = body
            tvMeta.text = item.published_at ?: ""

            when (item.signal_type?.lowercase()) {
                "buy" -> {
                    bubble.setBackgroundResource(R.drawable.bg_signal_buy)
                    tvType.setTextColor(itemView.context.getColor(R.color.green_buy))
                }
                "sell" -> {
                    bubble.setBackgroundResource(R.drawable.bg_signal_sell)
                    tvType.setTextColor(itemView.context.getColor(R.color.red_sell))
                }
                else -> {
                    bubble.setBackgroundResource(R.drawable.bg_signal_hold)
                    tvType.setTextColor(itemView.context.getColor(R.color.text_dark))
                }
            }
        }
    }
}

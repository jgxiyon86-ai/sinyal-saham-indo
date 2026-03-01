package com.alima.sinyalsahamindo.ui

import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import androidx.recyclerview.widget.RecyclerView
import com.alima.sinyalsahamindo.R
import com.alima.sinyalsahamindo.data.model.SignalItem
import com.alima.sinyalsahamindo.databinding.ItemSignalAdminBinding

class SignalAdapter(
    private var signals: List<SignalItem>,
    private val onSelectionChanged: (Set<Int>) -> Unit,
    private val onDeleteClicked: (SignalItem) -> Unit
) : RecyclerView.Adapter<SignalAdapter.ViewHolder>() {

    private val selectedIds = mutableSetOf<Int>()

    inner class ViewHolder(val binding: ItemSignalAdminBinding) : RecyclerView.ViewHolder(binding.root)

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): ViewHolder {
        val binding = ItemSignalAdminBinding.inflate(LayoutInflater.from(parent.context), parent, false)
        return ViewHolder(binding)
    }

    override fun onBindViewHolder(holder: ViewHolder, position: Int) {
        val item = signals[position]
        holder.binding.tvStockCode.text = item.stock_code ?: "???"
        holder.binding.tvTitle.text = item.title ?: "Sinyal Tanpa Judul"
        
        val type = item.signal_type
        holder.binding.tvTypeTag.text = if (!type.isNullOrBlank()) type.uppercase() else "N/A"
        
        // UI Polish - Tag Color
        if (type?.lowercase() == "buy") {
            holder.binding.tvTypeTag.setBackgroundResource(R.drawable.bg_button_teal)
        } else {
            holder.binding.tvTypeTag.setBackgroundResource(R.drawable.bg_input_modern)
        }
        
        holder.binding.tvPriceInfo.text = "E: ${item.entry_price ?: "-"} • TP: ${item.take_profit ?: "-"}"
        
        val tier = item.tier_target
        holder.binding.tvTierInfo.text = if (!tier.isNullOrBlank()) tier.uppercase() else "ALL TIERS"

        holder.binding.tvPublishedAt.text = "Published: ${item.published_at?.replace("T", " ") ?: "-"}"
        holder.binding.tvExpiresAt.text = "Expires: ${item.expires_at?.replace("T", " ") ?: "-"}"

        // Selection & Delete Logic
        holder.binding.cbSelect.visibility = View.VISIBLE
        holder.binding.btnDeleteSignal.visibility = View.VISIBLE
        
        holder.binding.cbSelect.setOnCheckedChangeListener(null)
        holder.binding.cbSelect.isChecked = selectedIds.contains(item.id)
        holder.binding.cbSelect.setOnCheckedChangeListener { _, isChecked ->
            if (isChecked) selectedIds.add(item.id) else selectedIds.remove(item.id)
            onSelectionChanged(selectedIds)
        }
        
        holder.binding.btnDeleteSignal.setOnClickListener { onDeleteClicked(item) }
        holder.binding.root.setOnClickListener { holder.binding.cbSelect.toggle() }
    }

    override fun getItemCount() = signals.size

    fun updateData(newData: List<SignalItem>) {
        this.signals = newData
        notifyDataSetChanged()
    }
}

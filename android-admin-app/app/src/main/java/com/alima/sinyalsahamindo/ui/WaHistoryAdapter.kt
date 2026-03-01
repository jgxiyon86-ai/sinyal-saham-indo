package com.alima.sinyalsahamindo.ui

import android.view.LayoutInflater
import android.view.ViewGroup
import androidx.recyclerview.widget.RecyclerView
import com.alima.sinyalsahamindo.data.model.WaLogItem
import com.alima.sinyalsahamindo.databinding.ItemWaHistoryBinding

class WaHistoryAdapter(
    private var logs: List<WaLogItem> = emptyList()
) : RecyclerView.Adapter<WaHistoryAdapter.ViewHolder>() {

    inner class ViewHolder(val binding: ItemWaHistoryBinding) : RecyclerView.ViewHolder(binding.root)

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): ViewHolder {
        val binding = ItemWaHistoryBinding.inflate(LayoutInflater.from(parent.context), parent, false)
        return ViewHolder(binding)
    }

    override fun onBindViewHolder(holder: ViewHolder, position: Int) {
        val item = logs[position]
        holder.binding.tvLogType.text = item.blast_type?.uppercase() ?: "GENERAL"
        holder.binding.tvLogDate.text = item.blasted_at?.replace("T", " ")?.take(16) ?: "-"
        holder.binding.tvLogSummary.text = "Berhasil dikirim ke ${item.recipients_count} penerima"
        holder.binding.tvLogAdmin.text = "Oleh: ${item.admin?.name ?: "Admin"}"
    }

    override fun getItemCount() = logs.size

    fun updateData(newData: List<WaLogItem>) {
        this.logs = newData
        notifyDataSetChanged()
    }
}

package com.alima.adminsinyal.ui

import android.view.LayoutInflater
import android.view.ViewGroup
import androidx.recyclerview.widget.RecyclerView
import com.alima.adminsinyal.data.model.SignalItem
import com.alima.adminsinyal.databinding.ItemSignalBinding
import java.time.Instant
import java.time.OffsetDateTime
import java.time.ZoneId
import java.time.format.DateTimeFormatter
import java.util.Locale

class SignalAdapter(
    private val onBlastClick: (SignalItem) -> Unit,
    private val onDeleteClick: (SignalItem) -> Unit,
    private val onSelectionChanged: (Int) -> Unit,
) : RecyclerView.Adapter<SignalAdapter.SignalViewHolder>() {
    private val wibZone: ZoneId = ZoneId.of("Asia/Jakarta")
    private val wibFormatter: DateTimeFormatter =
        DateTimeFormatter.ofPattern("yyyy-MM-dd HH:mm", Locale.US)

    private val items = mutableListOf<SignalItem>()
    private val selectedIds = linkedSetOf<Int>()

    fun submitList(newItems: List<SignalItem>) {
        items.clear()
        items.addAll(newItems)
        val validIds = items.map { it.id }.toSet()
        selectedIds.retainAll(validIds)
        onSelectionChanged(selectedIds.size)
        notifyDataSetChanged()
    }

    fun getSelectedSignalIds(): List<Int> = selectedIds.toList()

    fun clearSelection() {
        selectedIds.clear()
        onSelectionChanged(0)
        notifyDataSetChanged()
    }

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): SignalViewHolder {
        val binding = ItemSignalBinding.inflate(LayoutInflater.from(parent.context), parent, false)
        return SignalViewHolder(binding)
    }

    override fun onBindViewHolder(holder: SignalViewHolder, position: Int) {
        holder.bind(items[position])
    }

    override fun getItemCount(): Int = items.size

    inner class SignalViewHolder(private val binding: ItemSignalBinding) : RecyclerView.ViewHolder(binding.root) {
        fun bind(item: SignalItem) {
            binding.tvTitle.text = "${item.title ?: "-"} (#${item.id})"
            binding.tvMeta.text = "${item.stock_code ?: "-"} | ${item.signal_type ?: "-"}"
            binding.tvPublish.text = "Publish: ${toWibText(item.published_at)}"
            binding.tvExpired.text = "Expired: ${toWibText(item.expires_at)}"
            binding.tvNote.text = item.note ?: "-"
            binding.cbSelect.setOnCheckedChangeListener(null)
            binding.cbSelect.isChecked = selectedIds.contains(item.id)
            binding.cbSelect.setOnCheckedChangeListener { _, checked ->
                if (checked) {
                    selectedIds.add(item.id)
                } else {
                    selectedIds.remove(item.id)
                }
                onSelectionChanged(selectedIds.size)
            }
            binding.btnBlastThis.setOnClickListener { onBlastClick(item) }
            binding.btnDeleteThis.setOnClickListener { onDeleteClick(item) }
        }

        private fun toWibText(raw: String?): String {
            if (raw.isNullOrBlank()) return "-"

            val instant = runCatching { Instant.parse(raw) }.getOrNull()
                ?: runCatching { OffsetDateTime.parse(raw).toInstant() }.getOrNull()
                ?: return raw

            return "${wibFormatter.format(instant.atZone(wibZone))} WIB"
        }
    }
}

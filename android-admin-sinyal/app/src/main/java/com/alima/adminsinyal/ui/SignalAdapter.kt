package com.alima.adminsinyal.ui

import android.view.LayoutInflater
import android.view.ViewGroup
import androidx.recyclerview.widget.RecyclerView
import com.alima.adminsinyal.data.model.SignalItem
import com.alima.adminsinyal.databinding.ItemSignalBinding

class SignalAdapter(
    private val onBlastClick: (SignalItem) -> Unit,
    private val onSelectionChanged: (Int) -> Unit,
) : RecyclerView.Adapter<SignalAdapter.SignalViewHolder>() {

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
            binding.tvPublish.text = item.published_at ?: "Belum publish"
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
        }
    }
}

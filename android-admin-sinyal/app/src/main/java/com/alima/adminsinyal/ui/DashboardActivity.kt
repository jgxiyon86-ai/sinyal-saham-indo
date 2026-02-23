package com.alima.adminsinyal.ui

import android.content.Intent
import android.app.DatePickerDialog
import android.app.TimePickerDialog
import android.os.Bundle
import android.widget.ArrayAdapter
import android.widget.EditText
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import androidx.appcompat.app.AlertDialog
import androidx.lifecycle.lifecycleScope
import androidx.recyclerview.widget.LinearLayoutManager
import com.alima.adminsinyal.data.model.AdminCreateSignalRequest
import com.alima.adminsinyal.data.model.SignalItem
import com.alima.adminsinyal.data.model.SignalWaBlastRequest
import com.alima.adminsinyal.data.model.TierItem
import com.alima.adminsinyal.data.network.RetrofitClient
import com.alima.adminsinyal.databinding.ActivityDashboardBinding
import com.alima.adminsinyal.utils.SessionManager
import kotlinx.coroutines.Job
import kotlinx.coroutines.delay
import kotlinx.coroutines.launch
import org.json.JSONArray
import org.json.JSONObject
import java.text.SimpleDateFormat
import java.util.Calendar
import java.util.Locale

class DashboardActivity : AppCompatActivity() {
    private lateinit var binding: ActivityDashboardBinding
    private lateinit var sessionManager: SessionManager
    private lateinit var signalAdapter: SignalAdapter
    private var pollingJob: Job? = null
    private var selectedCount: Int = 0
    private val tiers = mutableListOf<TierItem>()
    private val signalTypeOptions = listOf(
        "BUY" to "buy",
        "SELL" to "sell"
    )

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityDashboardBinding.inflate(layoutInflater)
        setContentView(binding.root)

        sessionManager = SessionManager(this)
        if (sessionManager.getToken().isBlank()) {
            logout()
            return
        }

        setupList()
        setupActions()
        setupTierDropdowns()
        setupDateTimePickers()
        fetchTiers()
        fetchSignals()
    }

    override fun onStart() {
        super.onStart()
        startPolling()
    }

    override fun onStop() {
        super.onStop()
        stopPolling()
    }

    private fun setupList() {
        signalAdapter = SignalAdapter(
            onBlastClick = { item -> blastSingleSignal(item) },
            onDeleteClick = { item -> confirmDeleteSignal(item) },
            onSelectionChanged = { count ->
                selectedCount = count
                updateSignalCountLabel(signalAdapter.itemCount)
            },
        )
        binding.rvSignals.apply {
            layoutManager = LinearLayoutManager(this@DashboardActivity)
            adapter = signalAdapter
        }
    }

    private fun setupActions() {
        binding.btnCreateSignal.setOnClickListener { createSignal() }
        binding.btnSendBlast.setOnClickListener { sendBlastBulk() }
        binding.btnSendSelected.setOnClickListener { sendBlastSelected() }
        binding.btnRefreshSignals.setOnClickListener { fetchSignals() }
        binding.btnLogout.setOnClickListener { logout() }
    }

    private fun setupTierDropdowns() {
        val initial = listOf("Semua tier")
        val adapter = ArrayAdapter(this, android.R.layout.simple_spinner_item, initial).apply {
            setDropDownViewResource(android.R.layout.simple_spinner_dropdown_item)
        }
        binding.spSignalTier.adapter = adapter
        binding.spBlastTier.adapter = adapter

        val signalTypeAdapter = ArrayAdapter(
            this,
            android.R.layout.simple_spinner_item,
            signalTypeOptions.map { it.first }
        ).apply {
            setDropDownViewResource(android.R.layout.simple_spinner_dropdown_item)
        }
        binding.spSignalType.adapter = signalTypeAdapter
    }

    private fun fetchTiers() {
        lifecycleScope.launch {
            try {
                val resp = RetrofitClient.api.getTiers(bearer())
                if (resp.isSuccessful) {
                    tiers.clear()
                    tiers.addAll(resp.body()?.tiers.orEmpty())
                    val labels = mutableListOf("Semua tier")
                    labels.addAll(tiers.map { it.name?.ifBlank { "Tier #${it.id}" } ?: "Tier #${it.id}" })
                    val adapter = ArrayAdapter(this@DashboardActivity, android.R.layout.simple_spinner_item, labels).apply {
                        setDropDownViewResource(android.R.layout.simple_spinner_dropdown_item)
                    }
                    binding.spSignalTier.adapter = adapter
                    binding.spBlastTier.adapter = adapter
                } else {
                    binding.tvResult.text = "Gagal load tier (${resp.code()})"
                }
            } catch (e: Exception) {
                binding.tvResult.text = "Error load tier: ${e.message}"
            }
        }
    }

    private fun startPolling() {
        if (pollingJob?.isActive == true) return
        pollingJob = lifecycleScope.launch {
            while (true) {
                fetchSignals(silent = true)
                delay(20000)
            }
        }
    }

    private fun stopPolling() {
        pollingJob?.cancel()
        pollingJob = null
    }

    private fun createSignal() {
        val entry = binding.etEntry.text.toString().trim().toIntOrNull()
        val tp = binding.etTakeProfit.text.toString().trim().toIntOrNull()
        val sl = binding.etStopLoss.text.toString().trim().toIntOrNull()

        if (binding.etEntry.text.toString().trim().isNotBlank() && entry == null) {
            toast("Entry harus angka bulat")
            return
        }
        if (binding.etTakeProfit.text.toString().trim().isNotBlank() && tp == null) {
            toast("Take Profit harus angka bulat")
            return
        }
        if (binding.etStopLoss.text.toString().trim().isNotBlank() && sl == null) {
            toast("Stop Loss harus angka bulat")
            return
        }

        val request = AdminCreateSignalRequest(
            title = binding.etTitle.text.toString().trim(),
            stock_code = binding.etCode.text.toString().trim(),
            signal_type = selectedSignalTypeValue(),
            entry_price = entry,
            take_profit = tp,
            stop_loss = sl,
            note = binding.etNote.text.toString().trim().ifBlank { null },
            image_url = binding.etImageUrl.text.toString().trim().ifBlank { null },
            published_at = binding.etPublishedAt.text.toString().trim().ifBlank { null },
            expires_at = binding.etExpiresAt.text.toString().trim().ifBlank { null },
            tier_target = selectedSignalTierTarget(),
        )

        if (request.title.isBlank() || request.stock_code.isBlank() || request.signal_type.isBlank()) {
            toast("Judul, kode, dan tipe wajib")
            return
        }

        setLoading(true)
        lifecycleScope.launch {
            try {
                val resp = RetrofitClient.api.createSignal(bearer(), request)
                if (resp.isSuccessful) {
                    val id = resp.body()?.signal?.id
                    binding.tvResult.text = "Sinyal tersimpan. ID: ${id ?: "-"}"
                    clearSignalForm()
                    fetchSignals(silent = true)
                } else {
                    val detail = parseError(resp.errorBody()?.string())
                    binding.tvResult.text = detail.ifBlank { "Gagal simpan sinyal (${resp.code()})" }
                }
            } catch (e: Exception) {
                binding.tvResult.text = "Error: ${e.message}"
            } finally {
                setLoading(false)
            }
        }
    }

    private fun sendBlastBulk() {
        val signalIds = parseIntList(binding.etSignalIds.text.toString())
        if (signalIds.isEmpty()) {
            toast("Isi Signal IDs dulu")
            return
        }

        val tier = selectedBlastTierId()
        sendBlast(SignalWaBlastRequest(signal_ids = signalIds, tier_id = tier), "bulk")
    }

    private fun sendBlastSelected() {
        val selectedIds = signalAdapter.getSelectedSignalIds()
        if (selectedIds.isEmpty()) {
            toast("Pilih sinyal dulu dari list")
            return
        }

        val tier = selectedBlastTierId()
        sendBlast(SignalWaBlastRequest(signal_ids = selectedIds, tier_id = tier), "selected")
    }

    private fun blastSingleSignal(item: SignalItem) {
        val tier = selectedBlastTierId()
        sendBlast(SignalWaBlastRequest(signal_ids = listOf(item.id), tier_id = tier), "signal #${item.id}")
    }

    private fun sendBlast(request: SignalWaBlastRequest, mode: String) {
        setLoading(true)
        lifecycleScope.launch {
            try {
                val resp = RetrofitClient.api.sendWaBlast(bearer(), request)
                if (resp.isSuccessful) {
                    val body = resp.body()
                    binding.tvResult.text = "Blast $mode sukses. Sent=${body?.sent ?: 0}, Failed=${body?.failed ?: 0}"
                    if (mode == "selected") {
                        signalAdapter.clearSelection()
                    }
                } else {
                    val detail = parseError(resp.errorBody()?.string())
                    binding.tvResult.text = if (detail.isNotBlank()) {
                        "Blast $mode gagal (${resp.code()}): $detail"
                    } else {
                        "Blast $mode gagal (${resp.code()})"
                    }
                }
            } catch (e: Exception) {
                binding.tvResult.text = "Error: ${e.message}"
            } finally {
                setLoading(false)
            }
        }
    }

    private fun fetchSignals(silent: Boolean = false) {
        if (!silent) setLoading(true)
        lifecycleScope.launch {
            try {
                val resp = RetrofitClient.api.getSignals(bearer())
                if (resp.isSuccessful) {
                    val items = resp.body()?.signals?.data.orEmpty()
                    signalAdapter.submitList(items)
                    updateSignalCountLabel(items.size)
                    if (!silent) binding.tvResult.text = "List sinyal diperbarui"
                } else if (!silent) {
                    binding.tvResult.text = "Gagal load sinyal (${resp.code()})"
                }
            } catch (e: Exception) {
                if (!silent) binding.tvResult.text = "Error load sinyal: ${e.message}"
            } finally {
                if (!silent) setLoading(false)
            }
        }
    }

    private fun parseIntList(raw: String): List<Int> {
        return raw.split(",")
            .map { it.trim() }
            .filter { it.isNotBlank() }
            .mapNotNull { it.toIntOrNull() }
            .distinct()
    }

    private fun bearer(): String = "Bearer ${sessionManager.getToken()}"

    private fun selectedSignalTierTarget(): String {
        val index = binding.spSignalTier.selectedItemPosition
        return if (index <= 0) "all" else tiers.getOrNull(index - 1)?.id?.toString() ?: "all"
    }

    private fun selectedBlastTierId(): Int? {
        val index = binding.spBlastTier.selectedItemPosition
        return if (index <= 0) null else tiers.getOrNull(index - 1)?.id
    }

    private fun selectedSignalTypeValue(): String {
        val idx = binding.spSignalType.selectedItemPosition
        return signalTypeOptions.getOrNull(idx)?.second ?: "buy"
    }

    private fun parseError(body: String?): String {
        if (body.isNullOrBlank()) return ""
        return runCatching {
            val json = JSONObject(body)
            val msg = json.optString("message")
            if (msg.isNotBlank()) return@runCatching msg
            val errors = json.optJSONObject("errors") ?: return@runCatching ""
            val keys = errors.keys()
            while (keys.hasNext()) {
                val key = keys.next()
                val value = errors.opt(key)
                when (value) {
                    is JSONArray -> if (value.length() > 0) return@runCatching value.optString(0)
                    is String -> if (value.isNotBlank()) return@runCatching value
                }
            }
            ""
        }.getOrDefault("")
    }

    private fun updateSignalCountLabel(total: Int) {
        binding.tvSignalCount.text = "$total sinyal | terpilih: $selectedCount"
    }

    private fun setLoading(isLoading: Boolean) {
        binding.progress.visibility = if (isLoading) android.view.View.VISIBLE else android.view.View.GONE
        binding.btnCreateSignal.isEnabled = !isLoading
        binding.btnSendBlast.isEnabled = !isLoading
        binding.btnSendSelected.isEnabled = !isLoading
        binding.btnRefreshSignals.isEnabled = !isLoading
    }

    private fun confirmDeleteSignal(item: SignalItem) {
        AlertDialog.Builder(this)
            .setTitle("Hapus Sinyal")
            .setMessage("Hapus sinyal #${item.id} (${item.title ?: "-"})?")
            .setPositiveButton("Hapus") { _, _ -> deleteSignal(item.id) }
            .setNegativeButton("Batal", null)
            .show()
    }

    private fun deleteSignal(signalId: Int) {
        setLoading(true)
        lifecycleScope.launch {
            try {
                val resp = RetrofitClient.api.deleteSignal(bearer(), signalId)
                if (resp.isSuccessful) {
                    binding.tvResult.text = "Sinyal #$signalId berhasil dihapus"
                    fetchSignals(silent = true)
                } else {
                    val detail = parseError(resp.errorBody()?.string())
                    binding.tvResult.text = if (detail.isNotBlank()) {
                        "Hapus gagal (${resp.code()}): $detail"
                    } else {
                        "Hapus gagal (${resp.code()})"
                    }
                }
            } catch (e: Exception) {
                binding.tvResult.text = "Error hapus: ${e.message}"
            } finally {
                setLoading(false)
            }
        }
    }

    private fun clearSignalForm() {
        binding.etTitle.text?.clear()
        binding.etCode.text?.clear()
        binding.etEntry.text?.clear()
        binding.etTakeProfit.text?.clear()
        binding.etStopLoss.text?.clear()
        binding.etImageUrl.text?.clear()
        binding.etPublishedAt.text?.clear()
        binding.etExpiresAt.text?.clear()
        binding.etNote.text?.clear()
        binding.spSignalType.setSelection(0)
        binding.spSignalTier.setSelection(0)
    }

    private fun setupDateTimePickers() {
        val formatter = SimpleDateFormat("yyyy-MM-dd HH:mm", Locale.US)
        attachDateTimePicker(binding.etPublishedAt, formatter)
        attachDateTimePicker(binding.etExpiresAt, formatter)
    }

    private fun attachDateTimePicker(input: EditText, formatter: SimpleDateFormat) {
        input.setOnClickListener {
            val calendar = Calendar.getInstance()
            DatePickerDialog(
                this,
                { _, y, m, d ->
                    calendar.set(Calendar.YEAR, y)
                    calendar.set(Calendar.MONTH, m)
                    calendar.set(Calendar.DAY_OF_MONTH, d)
                    TimePickerDialog(
                        this,
                        { _, h, min ->
                            calendar.set(Calendar.HOUR_OF_DAY, h)
                            calendar.set(Calendar.MINUTE, min)
                            input.setText(formatter.format(calendar.time))
                        },
                        calendar.get(Calendar.HOUR_OF_DAY),
                        calendar.get(Calendar.MINUTE),
                        true
                    ).show()
                },
                calendar.get(Calendar.YEAR),
                calendar.get(Calendar.MONTH),
                calendar.get(Calendar.DAY_OF_MONTH)
            ).show()
        }
    }

    private fun logout() {
        stopPolling()
        sessionManager.clear()
        startActivity(Intent(this, LoginActivity::class.java))
        finish()
    }

    private fun toast(message: String) {
        Toast.makeText(this, message, Toast.LENGTH_SHORT).show()
    }
}

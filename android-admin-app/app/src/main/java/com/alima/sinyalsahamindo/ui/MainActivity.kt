package com.alima.sinyalsahamindo.ui

import android.content.Intent
import android.os.Bundle
import android.view.View
import android.widget.ArrayAdapter
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import com.alima.sinyalsahamindo.data.model.AdminCreateSignalRequest
import com.alima.sinyalsahamindo.data.model.SignalWaBlastRequest
import com.alima.sinyalsahamindo.data.model.TierItem
import com.alima.sinyalsahamindo.data.network.RetrofitProvider
import com.alima.sinyalsahamindo.databinding.ActivityMainBinding
import com.alima.sinyalsahamindo.util.SessionManager
import kotlinx.coroutines.launch
import org.json.JSONArray
import org.json.JSONObject

class MainActivity : AppCompatActivity() {
    private lateinit var binding: ActivityMainBinding
    private lateinit var sessionManager: SessionManager
    private var tiers: List<TierItem> = emptyList()
    private var lastCreatedSignalId: Int? = null
    private val signalTypeOptions = listOf(
        "BUY" to "buy",
        "SELL" to "sell",
        "HOLD" to "hold"
    )

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityMainBinding.inflate(layoutInflater)
        setContentView(binding.root)

        sessionManager = SessionManager(this)
        if (sessionManager.getToken().isNullOrBlank()) {
            goToLogin()
            return
        }

        bindSignalType()
        bindTierSpinner(emptyList())
        loadTiers()

        binding.btnCreateSignal.setOnClickListener { createSignal() }
        binding.btnSendWaBlast.setOnClickListener { sendWaBlast() }
        binding.btnLogout.setOnClickListener { logout() }
    }

    private fun bindSignalType() {
        val items = signalTypeOptions.map { it.first }
        binding.spSignalType.adapter = ArrayAdapter(this, android.R.layout.simple_spinner_dropdown_item, items)
    }

    private fun bindTierSpinner(list: List<TierItem>) {
        val labels = mutableListOf("Semua Tier")
        labels.addAll(list.map { it.name })
        binding.spTierTarget.adapter = ArrayAdapter(this, android.R.layout.simple_spinner_dropdown_item, labels)
        binding.spBlastTier.adapter = ArrayAdapter(this, android.R.layout.simple_spinner_dropdown_item, labels)
    }

    private fun loadTiers() {
        val token = sessionManager.getToken() ?: return
        lifecycleScope.launch {
            try {
                val response = RetrofitProvider.api.getAdminTiers("Bearer $token")
                if (response.isSuccessful) {
                    tiers = response.body()?.tiers.orEmpty()
                    bindTierSpinner(tiers)
                    showInfo("Tier berhasil dimuat.")
                } else {
                    showError("Gagal load tier: ${response.code()}")
                }
            } catch (e: Exception) {
                showError("Gagal load tier: ${e.message}")
            }
        }
    }

    private fun createSignal() {
        val token = sessionManager.getToken() ?: return
        val title = binding.etTitle.text?.toString()?.trim().orEmpty()
        val stockCode = binding.etStockCode.text?.toString()?.trim().orEmpty()
        val signalType = selectedSignalTypeValue()
        val entry = binding.etEntry.text?.toString()?.trim().orEmpty()
        val tp = binding.etTp.text?.toString()?.trim().orEmpty()
        val sl = binding.etSl.text?.toString()?.trim().orEmpty()
        val note = binding.etNote.text?.toString()?.trim().orEmpty()
        val imageUrl = binding.etImageUrl.text?.toString()?.trim().orEmpty()
        val tierTarget = selectedTierTarget()

        if (title.isBlank() || stockCode.isBlank()) {
            showError("Judul dan kode saham wajib diisi.")
            return
        }

        binding.btnCreateSignal.isEnabled = false
        lifecycleScope.launch {
            try {
                val req = AdminCreateSignalRequest(
                    title = title,
                    stock_code = stockCode,
                    signal_type = signalType,
                    entry_price = entry.toDoubleOrNull(),
                    take_profit = tp.toDoubleOrNull(),
                    stop_loss = sl.toDoubleOrNull(),
                    note = note.ifBlank { null },
                    image_url = imageUrl.ifBlank { null },
                    published_at = null,
                    expires_at = null,
                    tier_target = tierTarget
                )
                val response = RetrofitProvider.api.createAdminSignal("Bearer $token", req)
                if (response.isSuccessful) {
                    val signalId = response.body()?.signal?.id
                    lastCreatedSignalId = signalId
                    if (signalId != null) {
                        binding.etSignalIds.setText(signalId.toString())
                    }
                    showInfo("Sinyal berhasil dibuat. ID: ${signalId ?: "-"}")
                } else {
                    val msg = parseErrorMessage(response.errorBody()?.string())
                    showError(msg.ifBlank { "Gagal create sinyal (${response.code()})" })
                }
            } catch (e: Exception) {
                showError("Gagal create sinyal: ${e.message}")
            } finally {
                binding.btnCreateSignal.isEnabled = true
            }
        }
    }

    private fun sendWaBlast() {
        val token = sessionManager.getToken() ?: return
        val idsInput = binding.etSignalIds.text?.toString()?.trim().orEmpty()
        val ids = idsInput.split(",")
            .mapNotNull { it.trim().toIntOrNull() }
            .distinct()

        if (ids.isEmpty()) {
            showError("Isi Signal ID dulu. Contoh: 12 atau 12,13")
            return
        }

        binding.btnSendWaBlast.isEnabled = false
        lifecycleScope.launch {
            try {
                val req = SignalWaBlastRequest(
                    signal_ids = ids,
                    tier_id = selectedBlastTierId()
                )
                val response = RetrofitProvider.api.sendSignalWaBlast("Bearer $token", req)
                if (response.isSuccessful) {
                    val sent = (response.body()?.get("sent") ?: 0).toString()
                    val failed = (response.body()?.get("failed") ?: 0).toString()
                    showInfo("WA Blast diproses. Sent: $sent, Failed: $failed")
                } else {
                    val msg = parseErrorMessage(response.errorBody()?.string())
                    showError(msg.ifBlank { "Gagal kirim WA blast (${response.code()})" })
                }
            } catch (e: Exception) {
                showError("Gagal kirim WA blast: ${e.message}")
            } finally {
                binding.btnSendWaBlast.isEnabled = true
            }
        }
    }

    private fun logout() {
        val token = sessionManager.getToken()
        lifecycleScope.launch {
            if (!token.isNullOrBlank()) {
                runCatching { RetrofitProvider.api.logout("Bearer $token") }
            }
            sessionManager.clear()
            goToLogin()
        }
    }

    private fun selectedTierTarget(): String {
        val idx = binding.spTierTarget.selectedItemPosition
        return if (idx <= 0) "all" else tiers.getOrNull(idx - 1)?.id?.toString() ?: "all"
    }

    private fun selectedSignalTypeValue(): String {
        val idx = binding.spSignalType.selectedItemPosition
        return signalTypeOptions.getOrNull(idx)?.second ?: "buy"
    }

    private fun selectedBlastTierId(): Int? {
        val idx = binding.spBlastTier.selectedItemPosition
        return if (idx <= 0) null else tiers.getOrNull(idx - 1)?.id
    }

    private fun showError(msg: String) {
        binding.tvStatus.visibility = View.VISIBLE
        binding.tvStatus.text = msg
    }

    private fun showInfo(msg: String) {
        binding.tvStatus.visibility = View.VISIBLE
        binding.tvStatus.text = msg
    }

    private fun parseErrorMessage(body: String?): String {
        if (body.isNullOrBlank()) return ""
        return runCatching {
            val json = JSONObject(body)
            val directMessage = json.optString("message")
            if (directMessage.isNotBlank() && directMessage != "null") {
                return@runCatching directMessage
            }

            val errors = json.optJSONObject("errors")
            if (errors != null) {
                val keys = errors.keys()
                while (keys.hasNext()) {
                    val key = keys.next()
                    val value = errors.opt(key)
                    when (value) {
                        is JSONArray -> {
                            if (value.length() > 0) {
                                return@runCatching value.optString(0)
                            }
                        }
                        is String -> if (value.isNotBlank()) return@runCatching value
                    }
                }
            }

            ""
        }.getOrDefault("")
    }

    private fun goToLogin() {
        startActivity(Intent(this, LoginActivity::class.java))
        finish()
    }
}

package com.alima.sinyalsahamindo.ui

import android.app.DatePickerDialog
import android.app.TimePickerDialog
import android.content.Intent
import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.widget.ArrayAdapter
import android.widget.Button
import android.widget.EditText
import android.widget.TextView
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import androidx.recyclerview.widget.LinearLayoutManager
import com.alima.sinyalsahamindo.R
import com.alima.sinyalsahamindo.data.model.*
import com.alima.sinyalsahamindo.data.network.RetrofitProvider
import com.alima.sinyalsahamindo.databinding.ActivityMainBinding
import com.alima.sinyalsahamindo.util.SessionManager
import com.google.android.material.bottomsheet.BottomSheetDialog
import kotlinx.coroutines.launch
import java.text.SimpleDateFormat
import java.util.*

class MainActivity : AppCompatActivity() {
    private lateinit var binding: ActivityMainBinding
    private lateinit var sessionManager: SessionManager
    private var tiers: List<TierItem> = emptyList()
    private var allSignals: List<SignalItem> = emptyList()
    private var selectedBlastIds = mutableSetOf<Int>()
    private val waHistoryAdapter = WaHistoryAdapter()
    private val signalTypeOptions = listOf("BUY" to "buy", "SELL" to "sell", "HOLD" to "hold")
    private val sdf = SimpleDateFormat("yyyy-MM-dd HH:mm:ss", Locale.getDefault())

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityMainBinding.inflate(layoutInflater)
        setContentView(binding.root)

        sessionManager = SessionManager(this)
        if (sessionManager.getToken().isNullOrBlank()) {
            goToLogin()
            return
        }

        setupUI()
        loadTiers()
        loadSignals()
    }

    private fun setupUI() {
        binding.rvSignals.layoutManager = LinearLayoutManager(this)
        binding.rvWaHistory.layoutManager = LinearLayoutManager(this)
        binding.rvWaHistory.adapter = waHistoryAdapter
        
        // Navigation Logic
        binding.bottomNavigation.setOnItemSelectedListener { item ->
            when (item.itemId) {
                R.id.nav_history -> showLayout("history")
                R.id.nav_blast -> showLayout("blast")
            }
            true
        }

        binding.fabAdd.setOnClickListener {
            binding.bottomNavigation.menu.findItem(R.id.nav_history).isChecked = false
            binding.bottomNavigation.menu.findItem(R.id.nav_blast).isChecked = false
            showLayout("create")
        }

        binding.layoutHistory.setOnRefreshListener { loadSignals() }
        
        // Form Setup
        binding.spSignalType.adapter = ArrayAdapter(this, android.R.layout.simple_spinner_dropdown_item, signalTypeOptions.map { it.first })
        
        binding.etPublishedAt.setOnClickListener { showDateTimePicker(binding.etPublishedAt) }
        binding.etExpiresAt.setOnClickListener { showDateTimePicker(binding.etExpiresAt) }
        
        binding.btnSubmitCreate.setOnClickListener { createSignal() }
        binding.btnFinalBlast.setOnClickListener { sendWaBlast() }
        binding.btnProfile.setOnClickListener { showProfileDialog() }
        binding.btnLogout.setOnClickListener { logout() }

        showLayout("history")
    }

    private fun showLayout(tag: String) {
        binding.layoutHistory.visibility = if (tag == "history") View.VISIBLE else View.GONE
        binding.layoutCreate.visibility = if (tag == "create") View.VISIBLE else View.GONE
        binding.layoutBlast.visibility = if (tag == "blast") View.VISIBLE else View.GONE
        
        if (tag == "history") loadSignals()
        if (tag == "blast") {
            updateBlastSummary()
            loadWaBlastHistory()
        }
        if (tag == "create") clearForm()
    }

    private fun showDateTimePicker(editText: EditText) {
        val calendar = Calendar.getInstance()
        DatePickerDialog(this, { _, year, month, day ->
            calendar.set(Calendar.YEAR, year)
            calendar.set(Calendar.MONTH, month)
            calendar.set(Calendar.DAY_OF_MONTH, day)
            TimePickerDialog(this, { _, hour, min ->
                calendar.set(Calendar.HOUR_OF_DAY, hour)
                calendar.set(Calendar.MINUTE, min)
                calendar.set(Calendar.SECOND, 0)
                editText.setText(sdf.format(calendar.time))
            }, calendar.get(Calendar.HOUR_OF_DAY), calendar.get(Calendar.MINUTE), true).show()
        }, calendar.get(Calendar.YEAR), calendar.get(Calendar.MONTH), calendar.get(Calendar.DAY_OF_MONTH)).show()
    }

    private fun loadSignals() {
        val token = sessionManager.getToken() ?: return
        binding.layoutHistory.isRefreshing = true
        lifecycleScope.launch {
            try {
                val response = RetrofitProvider.api.getSignals("Bearer $token")
                if (response.isSuccessful) {
                    allSignals = response.body()?.getSignalList().orEmpty()
                    
                    // FILTER KERAS: Hanya Sinyal Aktif
                    val now = System.currentTimeMillis()
                    val activeSignals = allSignals.filter {
                        val expireStr = it.expires_at
                        if (expireStr.isNullOrBlank()) true 
                        else {
                            try {
                                val expireTime = sdf.parse(expireStr.replace("T", " "))?.time ?: 0L
                                expireTime >= now
                            } catch (e: Exception) { true }
                        }
                    }
                    
                    binding.rvSignals.adapter = SignalAdapter(activeSignals, { selected ->
                        selectedBlastIds = selected.toMutableSet()
                    }, { item ->
                        deleteSignal(item)
                    })
                } else showError("Gagal memuat data.")
            } catch (e: Exception) { showError("Error: ${e.message}") }
            finally { binding.layoutHistory.isRefreshing = false }
        }
    }

    private fun deleteSignal(item: SignalItem) {
        val token = sessionManager.getToken() ?: return
        lifecycleScope.launch {
            try {
                val response = RetrofitProvider.api.deleteSignal("Bearer $token", item.id)
                if (response.isSuccessful) {
                    showInfo("Sinyal berhasil dihapus.")
                    loadSignals()
                } else showError("Gagal menghapus sinyal.")
            } catch (e: Exception) { showError("Error: ${e.message}") }
        }
    }

    private fun updateBlastSummary() {
        binding.tvSelectCount.text = "${selectedBlastIds.size} Sinyal Terpilih"
        binding.tvBlastWarning.visibility = if (selectedBlastIds.isEmpty()) View.VISIBLE else View.GONE
        binding.btnFinalBlast.isEnabled = selectedBlastIds.isNotEmpty()
    }

    private fun createSignal() {
        val token = sessionManager.getToken() ?: return
        val stock = binding.etStockCode.text.toString().trim()
        val title = binding.etTitle.text.toString().trim()
        val entry = binding.etEntry.text.toString().trim()
        val tp = binding.etTp.text.toString().trim()
        val sl = binding.etSl.text.toString().trim()
        val pub = binding.etPublishedAt.text.toString().trim()
        val exp = binding.etExpiresAt.text.toString().trim()
        
        val typeVal = signalTypeOptions.getOrNull(binding.spSignalType.selectedItemPosition)?.second ?: "buy"
        val tierVal = if (binding.spTierTarget.selectedItemPosition <= 0) "all" else tiers.getOrNull(binding.spTierTarget.selectedItemPosition - 1)?.id?.toString() ?: "all"

        if (stock.isEmpty()) { showError("Kode saham wajib diisi."); return }

        binding.btnSubmitCreate.isEnabled = false
        lifecycleScope.launch {
            try {
                val req = AdminCreateSignalRequest(
                    title = if (title.isEmpty()) "Sinyal $stock" else title,
                    stock_code = stock,
                    signal_type = typeVal,
                    entry_price = entry.toDoubleOrNull(),
                    take_profit = tp.toDoubleOrNull(),
                    stop_loss = sl.toDoubleOrNull(),
                    note = null,
                    image_url = null,
                    published_at = pub.ifBlank { null },
                    expires_at = exp.ifBlank { null },
                    tier_target = tierVal
                )
                val response = RetrofitProvider.api.createAdminSignal("Bearer $token", req)
                if (response.isSuccessful) {
                    showInfo("Sinyal Berhasil Dibuat!")
                    binding.bottomNavigation.selectedItemId = R.id.nav_history
                } else showError("Gagal simpan sinyal.")
            } catch (e: Exception) { showError("Gagal: ${e.message}") }
            finally { binding.btnSubmitCreate.isEnabled = true }
        }
    }

    private fun sendWaBlast() {
        val token = sessionManager.getToken() ?: return
        if (selectedBlastIds.isEmpty()) return
        
        binding.btnFinalBlast.isEnabled = false
        lifecycleScope.launch {
            try {
                val req = SignalWaBlastRequest(signal_ids = selectedBlastIds.toList(), tier_id = null)
                val response = RetrofitProvider.api.sendSignalWaBlast("Bearer $token", req)
                if (response.isSuccessful) {
                    showInfo("WA Blast Masuk Antrian.")
                    selectedBlastIds.clear()
                    updateBlastSummary()
                    loadWaBlastHistory()
                } else showError("Gagal memproses WA Blast.")
            } catch (e: Exception) { showError("Gagal: ${e.message}") }
            finally { binding.btnFinalBlast.isEnabled = true }
        }
    }

    private fun loadWaBlastHistory() {
        val token = sessionManager.getToken() ?: return
        lifecycleScope.launch {
            try {
                val response = RetrofitProvider.api.getWaBlastHistory("Bearer $token")
                if (response.isSuccessful) {
                    val logs = response.body()?.history ?: emptyList()
                    waHistoryAdapter.updateData(logs)
                }
            } catch (e: Exception) { }
        }
    }

    private fun clearForm() {
        binding.etStockCode.text.clear()
        binding.etTitle.text.clear()
        binding.etEntry.text.clear()
        binding.etTp.text.clear()
        binding.etSl.text.clear()
        val now = Calendar.getInstance()
        binding.etPublishedAt.setText(sdf.format(now.time))
        now.add(Calendar.DAY_OF_YEAR, 1)
        binding.etExpiresAt.setText(sdf.format(now.time))
    }

    private fun loadTiers() {
        val token = sessionManager.getToken() ?: return
        lifecycleScope.launch {
            try {
                val response = RetrofitProvider.api.getAdminTiers("Bearer $token")
                if (response.isSuccessful) {
                    tiers = response.body()?.tiers.orEmpty()
                    val labels = mutableListOf("Semua Tier")
                    labels.addAll(tiers.map { it.name })
                    val adapter = ArrayAdapter(this@MainActivity, android.R.layout.simple_spinner_dropdown_item, labels)
                    binding.spTierTarget.adapter = adapter
                }
            } catch (e: Exception) { }
        }
    }

    private fun showProfileDialog() {
        val dialog = BottomSheetDialog(this)
        val view = LayoutInflater.from(this).inflate(R.layout.dialog_profile, null)
        dialog.setContentView(view)
        val tvEmail = view.findViewById<TextView>(R.id.tvAdminEmail)
        tvEmail.text = sessionManager.getEmail() ?: "Administrator"
        view.findViewById<Button>(R.id.btnSubmitChangePassword).setOnClickListener {
            val old = view.findViewById<EditText>(R.id.etOldPassword).text.toString()
            val new = view.findViewById<EditText>(R.id.etNewPassword).text.toString()
            val conf = view.findViewById<EditText>(R.id.etNewPasswordConfirm).text.toString()
            if (new != conf) { showError("Konfirmasi tidak cocok."); return@setOnClickListener }
            changePassword(old, new, conf, dialog)
        }
        dialog.show()
    }

    private fun changePassword(o: String, n: String, c: String, d: BottomSheetDialog) {
        val token = sessionManager.getToken() ?: return
        lifecycleScope.launch {
            try {
                val r = RetrofitProvider.api.changePassword("Bearer $token", ChangePasswordRequest(o, n, c))
                if (r.isSuccessful) { showInfo("Password diubah."); d.dismiss() }
                else showError("Gagal ubah password.")
            } catch (e: Exception) { showError("Error: ${e.message}") }
        }
    }

    private fun logout() {
        sessionManager.clear()
        goToLogin()
    }

    private fun showError(msg: String) {
        binding.tvStatus.visibility = View.VISIBLE
        binding.tvStatus.text = msg
        binding.tvStatus.setTextColor(getColor(R.color.red_sell))
        binding.tvStatus.postDelayed({ binding.tvStatus.visibility = View.GONE }, 4000)
    }

    private fun showInfo(msg: String) {
        binding.tvStatus.visibility = View.VISIBLE
        binding.tvStatus.text = msg
        binding.tvStatus.setTextColor(getColor(R.color.teal_600))
        binding.tvStatus.postDelayed({ binding.tvStatus.visibility = View.GONE }, 4000)
    }

    private fun goToLogin() {
        startActivity(Intent(this, LoginActivity::class.java).addFlags(Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TASK))
        finish()
    }
}

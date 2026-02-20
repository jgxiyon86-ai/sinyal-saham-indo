package com.alima.sinyalsahamindo.ui

import android.content.BroadcastReceiver
import android.content.Context
import android.content.Intent
import android.content.IntentFilter
import android.os.Build
import android.os.Bundle
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import androidx.recyclerview.widget.LinearLayoutManager
import androidx.work.ExistingPeriodicWorkPolicy
import androidx.work.PeriodicWorkRequestBuilder
import androidx.work.WorkManager
import com.alima.sinyalsahamindo.data.SignalRepository
import com.alima.sinyalsahamindo.data.model.SignalItem
import com.alima.sinyalsahamindo.databinding.ActivityMainBinding
import com.alima.sinyalsahamindo.push.AppFirebaseMessagingService
import com.alima.sinyalsahamindo.util.AlertHelper
import com.alima.sinyalsahamindo.util.SessionManager
import com.alima.sinyalsahamindo.worker.SignalSyncWorker
import kotlinx.coroutines.delay
import kotlinx.coroutines.isActive
import kotlinx.coroutines.launch
import java.util.concurrent.TimeUnit

class MainActivity : AppCompatActivity() {
    private lateinit var binding: ActivityMainBinding
    private lateinit var sessionManager: SessionManager
    private val repository = SignalRepository()
    private val adapter = SignalAdapter()
    private val pushReceiver = object : BroadcastReceiver() {
        override fun onReceive(context: Context?, intent: Intent?) {
            if (intent?.action != AppFirebaseMessagingService.ACTION_SIGNAL_PUSH) return
            parseSignalFromIntent(intent)?.let { signal ->
                adapter.upsertSignal(signal)
                sessionManager.saveLastSignalId(maxOf(sessionManager.getLastSignalId(), signal.id))
            }
        }
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityMainBinding.inflate(layoutInflater)
        setContentView(binding.root)

        sessionManager = SessionManager(this)
        val token = sessionManager.getToken()
        if (token.isNullOrBlank()) {
            goToLogin()
            return
        }

        binding.rvSignals.layoutManager = LinearLayoutManager(this)
        binding.rvSignals.adapter = adapter
        AppFirebaseMessagingService.fetchFcmToken { token ->
            if (!token.isNullOrBlank()) {
                sessionManager.saveFcmToken(token)
                val bearer = sessionManager.getToken()
                if (!bearer.isNullOrBlank()) {
                    lifecycleScope.launch {
                        try {
                            com.alima.sinyalsahamindo.data.network.RetrofitProvider.api.updateFcmToken(
                                "Bearer $bearer",
                                token
                            )
                        } catch (_: Exception) {
                        }
                    }
                }
            }
        }
        binding.swipeRefresh.setOnRefreshListener { fetchSignals(alertOnNew = true) }
        binding.btnRefresh.setOnClickListener { fetchSignals(alertOnNew = true) }
        binding.btnTestAlert.setOnClickListener {
            AlertHelper.hardAlert(
                this,
                SignalItem(
                    id = (System.currentTimeMillis() / 1000L).toInt(),
                    title = "Test Alert dari Aplikasi",
                    stock_code = "TEST",
                    signal_type = "buy",
                    entry_price = "100",
                    take_profit = "110",
                    stop_loss = "95",
                    note = "Ini notifikasi uji getar, suara, dan percakapan.",
                    published_at = System.currentTimeMillis().toString()
                )
            )
        }
        binding.btnLogout.setOnClickListener {
            sessionManager.clear()
            goToLogin()
        }

        scheduleBackgroundSync()
        fetchSignals(alertOnNew = false)
        startRealtimePolling()
        handleSignalIntent(intent)
    }

    override fun onResume() {
        super.onResume()
        val filter = IntentFilter(AppFirebaseMessagingService.ACTION_SIGNAL_PUSH)
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
            registerReceiver(pushReceiver, filter, RECEIVER_NOT_EXPORTED)
        } else {
            @Suppress("DEPRECATION")
            registerReceiver(pushReceiver, filter)
        }
    }

    override fun onPause() {
        super.onPause()
        runCatching { unregisterReceiver(pushReceiver) }
    }

    override fun onNewIntent(intent: Intent) {
        super.onNewIntent(intent)
        setIntent(intent)
        handleSignalIntent(intent)
    }

    private fun fetchSignals(alertOnNew: Boolean) {
        val token = sessionManager.getToken() ?: return
        lifecycleScope.launch {
            binding.swipeRefresh.isRefreshing = true
            try {
                val signals = repository.fetchSignals(token)
                adapter.submitData(signals)
                if (alertOnNew) {
                    handleNewSignals(signals)
                } else {
                    signals.maxOfOrNull { it.id }?.let { sessionManager.saveLastSignalId(it) }
                }
            } catch (_: Exception) {
            } finally {
                binding.swipeRefresh.isRefreshing = false
            }
        }
    }

    private fun startRealtimePolling() {
        lifecycleScope.launch {
            while (isActive) {
                fetchSignals(alertOnNew = true)
                delay(20_000)
            }
        }
    }

    private fun handleNewSignals(signals: List<SignalItem>) {
        val lastId = sessionManager.getLastSignalId()
        val newSignals = signals.filter { it.id > lastId }.sortedBy { it.id }
        newSignals.forEach { AlertHelper.hardAlert(this, it) }
        signals.maxOfOrNull { it.id }?.let { sessionManager.saveLastSignalId(it) }
    }

    private fun scheduleBackgroundSync() {
        val request = PeriodicWorkRequestBuilder<SignalSyncWorker>(15, TimeUnit.MINUTES).build()
        WorkManager.getInstance(this).enqueueUniquePeriodicWork(
            "signal_sync",
            ExistingPeriodicWorkPolicy.UPDATE,
            request
        )
    }

    private fun goToLogin() {
        startActivity(Intent(this, LoginActivity::class.java))
        finish()
    }

    private fun handleSignalIntent(intent: Intent?) {
        parseSignalFromIntent(intent)?.let { signal ->
            adapter.upsertSignal(signal)
            sessionManager.saveLastSignalId(maxOf(sessionManager.getLastSignalId(), signal.id))
        }
    }

    private fun parseSignalFromIntent(intent: Intent?): SignalItem? {
        intent ?: return null
        if (!intent.hasExtra(AppFirebaseMessagingService.EXTRA_ID)) return null
        val id = intent.getIntExtra(AppFirebaseMessagingService.EXTRA_ID, 0)
        if (id <= 0) return null

        return SignalItem(
            id = id,
            title = intent.getStringExtra(AppFirebaseMessagingService.EXTRA_TITLE),
            stock_code = intent.getStringExtra(AppFirebaseMessagingService.EXTRA_STOCK_CODE),
            signal_type = intent.getStringExtra(AppFirebaseMessagingService.EXTRA_SIGNAL_TYPE),
            entry_price = intent.getStringExtra(AppFirebaseMessagingService.EXTRA_ENTRY_PRICE),
            take_profit = intent.getStringExtra(AppFirebaseMessagingService.EXTRA_TAKE_PROFIT),
            stop_loss = intent.getStringExtra(AppFirebaseMessagingService.EXTRA_STOP_LOSS),
            note = intent.getStringExtra(AppFirebaseMessagingService.EXTRA_NOTE),
            published_at = intent.getStringExtra(AppFirebaseMessagingService.EXTRA_PUBLISHED_AT),
        )
    }
}

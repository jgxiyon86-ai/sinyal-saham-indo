package com.alima.sinyalsahamindo.ui

import android.Manifest
import android.content.Intent
import android.content.pm.PackageManager
import android.os.Build
import android.os.Bundle
import androidx.activity.result.contract.ActivityResultContracts
import androidx.appcompat.app.AppCompatActivity
import androidx.core.content.ContextCompat
import androidx.lifecycle.lifecycleScope
import com.alima.sinyalsahamindo.data.model.LoginRequest
import com.alima.sinyalsahamindo.data.network.RetrofitProvider
import com.alima.sinyalsahamindo.push.AppFirebaseMessagingService
import com.alima.sinyalsahamindo.databinding.ActivityLoginBinding
import com.alima.sinyalsahamindo.util.AlertHelper
import com.alima.sinyalsahamindo.util.SessionManager
import com.alima.sinyalsahamindo.worker.SignalWorkScheduler
import kotlinx.coroutines.launch
import org.json.JSONObject

class LoginActivity : AppCompatActivity() {
    private lateinit var binding: ActivityLoginBinding
    private lateinit var sessionManager: SessionManager

    private val notificationPermissionLauncher = registerForActivityResult(
        ActivityResultContracts.RequestPermission()
    ) { }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityLoginBinding.inflate(layoutInflater)
        setContentView(binding.root)

        sessionManager = SessionManager(this)
        AlertHelper.createChannel(this)
        requestNotificationPermissionIfNeeded()
        AppFirebaseMessagingService.fetchFcmToken { token ->
            if (!token.isNullOrBlank()) {
                sessionManager.saveFcmToken(token)
            }
        }

        if (!sessionManager.getToken().isNullOrBlank()) {
            startActivity(Intent(this, MainActivity::class.java))
            finish()
            return
        }

        binding.btnLogin.setOnClickListener {
            login()
        }

        intent.getStringExtra("forced_logout_message")?.let { msg ->
            showError(msg)
        }
    }

    private fun login() {
        val email = binding.etEmail.text?.toString()?.trim().orEmpty()
        val password = binding.etPassword.text?.toString().orEmpty()
        binding.tvError.visibility = android.view.View.GONE

        if (email.isBlank() || password.isBlank()) {
            binding.tvError.text = "Email dan password wajib diisi."
            binding.tvError.visibility = android.view.View.VISIBLE
            return
        }

        binding.btnLogin.isEnabled = false
        lifecycleScope.launch {
            try {
                val response = RetrofitProvider.api.login(LoginRequest(email, password))
                if (response.isSuccessful) {
                    val token = response.body()?.token
                    if (!token.isNullOrBlank()) {
                        sessionManager.saveToken(token)
                        val fcm = sessionManager.getFcmToken()
                        if (!fcm.isNullOrBlank()) {
                            try {
                                RetrofitProvider.api.updateFcmToken("Bearer $token", fcm)
                            } catch (_: Exception) {
                            }
                        }
                        SignalWorkScheduler.schedule(this@LoginActivity)
                        startActivity(Intent(this@LoginActivity, MainActivity::class.java))
                        finish()
                    } else {
                        showError("Token login tidak ditemukan.")
                    }
                } else {
                    val msg = runCatching {
                        JSONObject(response.errorBody()?.string().orEmpty()).optString("message")
                    }.getOrNull().orEmpty()
                    showError(msg.ifBlank { "Login gagal. Cek akun client." })
                }
            } catch (e: Exception) {
                showError("Gagal konek ke server: ${e.message}")
            } finally {
                binding.btnLogin.isEnabled = true
            }
        }
    }

    private fun showError(message: String) {
        binding.tvError.text = message
        binding.tvError.visibility = android.view.View.VISIBLE
    }

    private fun requestNotificationPermissionIfNeeded() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
            val granted = ContextCompat.checkSelfPermission(
                this,
                Manifest.permission.POST_NOTIFICATIONS
            ) == PackageManager.PERMISSION_GRANTED
            if (!granted) {
                notificationPermissionLauncher.launch(Manifest.permission.POST_NOTIFICATIONS)
            }
        }
    }
}

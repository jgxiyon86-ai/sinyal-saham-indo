package com.alima.sinyalsahamindo.ui

import android.content.Intent
import android.os.Bundle
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import com.alima.sinyalsahamindo.data.model.LoginRequest
import com.alima.sinyalsahamindo.data.network.RetrofitProvider
import com.alima.sinyalsahamindo.databinding.ActivityLoginBinding
import com.alima.sinyalsahamindo.util.SessionManager
import kotlinx.coroutines.launch
import org.json.JSONObject

class LoginActivity : AppCompatActivity() {
    private lateinit var binding: ActivityLoginBinding
    private lateinit var sessionManager: SessionManager

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityLoginBinding.inflate(layoutInflater)
        setContentView(binding.root)

        sessionManager = SessionManager(this)

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
                    val role = response.body()?.user?.role
                    if (!token.isNullOrBlank()) {
                        if (role != "admin") {
                            showError("Akun ini bukan admin.")
                            return@launch
                        }
                        sessionManager.saveToken(token)
                        response.body()?.user?.email?.let { sessionManager.saveEmail(it) }
                        startActivity(Intent(this@LoginActivity, MainActivity::class.java))
                        finish()
                    } else {
                        showError("Token login tidak ditemukan.")
                    }
                } else {
                    val msg = runCatching {
                        JSONObject(response.errorBody()?.string().orEmpty()).optString("message")
                    }.getOrNull().orEmpty()
                    showError(msg.ifBlank { "Login gagal. Cek akun admin." })
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
}

package com.alima.adminsinyal.ui

import android.content.Intent
import android.os.Bundle
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import com.alima.adminsinyal.data.model.LoginRequest
import com.alima.adminsinyal.data.network.RetrofitClient
import com.alima.adminsinyal.databinding.ActivityLoginBinding
import com.alima.adminsinyal.utils.SessionManager
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
        if (sessionManager.getToken().isNotBlank()) {
            goDashboard()
            return
        }

        binding.btnLogin.setOnClickListener {
            login()
        }
    }

    private fun login() {
        val email = binding.etEmail.text.toString().trim()
        val password = binding.etPassword.text.toString().trim()

        if (email.isBlank() || password.isBlank()) {
            toast("Email dan password wajib diisi")
            return
        }

        setLoading(true)
        lifecycleScope.launch {
            try {
                val resp = RetrofitClient.api.login(LoginRequest(email, password))
                if (!resp.isSuccessful || resp.body() == null) {
                    val rawError = resp.errorBody()?.string().orEmpty()
                    val parsed = parseServerError(rawError)
                    toast("Login gagal (${resp.code()}): $parsed")
                    return@launch
                }

                val body = resp.body()!!
                val role = body.user?.role ?: ""
                if (role.lowercase() != "admin") {
                    toast("Akun ini bukan admin")
                    return@launch
                }

                val token = body.token ?: body.access_token ?: ""
                if (token.isBlank()) {
                    toast("Token login tidak ditemukan")
                    return@launch
                }

                sessionManager.saveToken(token)
                goDashboard()
            } catch (e: Exception) {
                toast("Error: ${e.message}")
            } finally {
                setLoading(false)
            }
        }
    }

    private fun goDashboard() {
        startActivity(Intent(this, DashboardActivity::class.java))
        finish()
    }

    private fun setLoading(isLoading: Boolean) {
        binding.progress.visibility = if (isLoading) android.view.View.VISIBLE else android.view.View.GONE
        binding.btnLogin.isEnabled = !isLoading
    }

    private fun toast(message: String) {
        Toast.makeText(this, message, Toast.LENGTH_SHORT).show()
    }

    private fun parseServerError(raw: String): String {
        if (raw.isBlank()) return "Response kosong"
        return try {
            val json = JSONObject(raw)
            val message = json.optString("message")
            val errors = json.optJSONObject("errors")
            if (errors != null && errors.keys().hasNext()) {
                val key = errors.keys().next()
                val arr = errors.optJSONArray(key)
                val first = arr?.optString(0).orEmpty()
                if (first.isNotBlank()) "$key: $first" else (message.ifBlank { "Validasi gagal" })
            } else {
                message.ifBlank { raw.take(120) }
            }
        } catch (_: Exception) {
            raw.take(120)
        }
    }
}

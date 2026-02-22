package com.alima.adminsinyal.utils

import android.content.Context

class SessionManager(context: Context) {
    private val pref = context.getSharedPreferences("admin_sinyal_pref", Context.MODE_PRIVATE)

    fun saveToken(token: String) {
        pref.edit().putString(KEY_TOKEN, token).apply()
    }

    fun getToken(): String = pref.getString(KEY_TOKEN, "") ?: ""

    fun clear() {
        pref.edit().clear().apply()
    }

    companion object {
        private const val KEY_TOKEN = "auth_token"
    }
}

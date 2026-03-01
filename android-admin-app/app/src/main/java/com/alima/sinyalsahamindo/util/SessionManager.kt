package com.alima.sinyalsahamindo.util

import android.content.Context

class SessionManager(context: Context) {
    private val prefs = context.getSharedPreferences("session", Context.MODE_PRIVATE)

    fun saveToken(token: String) {
        prefs.edit().putString("token", token).apply()
    }

    fun getToken(): String? = prefs.getString("token", null)

    fun saveFcmToken(token: String) {
        prefs.edit().putString("fcm_token", token).apply()
    }

    fun getFcmToken(): String? = prefs.getString("fcm_token", null)

    fun saveLastSignalId(id: Int) {
        prefs.edit().putInt("last_signal_id", id).apply()
    }

    fun getLastSignalId(): Int = prefs.getInt("last_signal_id", 0)

    fun saveEmail(email: String) {
        prefs.edit().putString("email", email).apply()
    }

    fun getEmail(): String? = prefs.getString("email", null)

    fun clear() {
        prefs.edit().clear().apply()
    }
}

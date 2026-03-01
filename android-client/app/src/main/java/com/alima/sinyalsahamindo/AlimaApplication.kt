package com.alima.sinyalsahamindo

import android.app.Application
import android.util.Log
import androidx.work.Configuration
import androidx.work.WorkManager
import com.google.firebase.FirebaseApp

class AlimaApplication : Application() {
    override fun onCreate() {
        super.onCreate()
        Log.d("AlimaApp", "Application onCreate started")
        FirebaseApp.initializeApp(this)
        
        val config = Configuration.Builder()
            .setMinimumLoggingLevel(Log.DEBUG)
            .build()
        
        try {
            WorkManager.initialize(this, config)
            Log.d("AlimaApp", "WorkManager manual initialize success")
        } catch (e: Exception) {
            Log.e("AlimaApp", "WorkManager already initialized or failed: ${e.message}")
        }
    }
}

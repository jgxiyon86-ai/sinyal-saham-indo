package com.alima.sinyalsahamindo.worker

import android.content.BroadcastReceiver
import android.content.Context
import android.content.Intent
import com.alima.sinyalsahamindo.util.SessionManager

class BootReceiver : BroadcastReceiver() {
    override fun onReceive(context: Context, intent: Intent) {
        if (intent.action != Intent.ACTION_BOOT_COMPLETED) return
        val token = SessionManager(context).getToken()
        if (!token.isNullOrBlank()) {
            SignalWorkScheduler.schedule(context)
        }
    }
}


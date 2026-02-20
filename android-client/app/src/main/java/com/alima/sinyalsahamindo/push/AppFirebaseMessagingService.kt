package com.alima.sinyalsahamindo.push

import android.content.Intent
import android.util.Log
import com.alima.sinyalsahamindo.data.model.SignalItem
import com.alima.sinyalsahamindo.util.AlertHelper
import com.google.firebase.messaging.FirebaseMessaging
import com.google.firebase.messaging.FirebaseMessagingService
import com.google.firebase.messaging.RemoteMessage

class AppFirebaseMessagingService : FirebaseMessagingService() {
    override fun onNewToken(token: String) {
        super.onNewToken(token)
        Log.d("FCM", "New token: $token")
    }

    override fun onMessageReceived(message: RemoteMessage) {
        super.onMessageReceived(message)
        val signal = parseSignal(message.data, message)
        AlertHelper.hardAlert(applicationContext, signal)

        sendBroadcast(
            Intent(ACTION_SIGNAL_PUSH).apply {
                setPackage(packageName)
                putExtra(EXTRA_ID, signal.id)
                putExtra(EXTRA_TITLE, signal.title ?: "")
                putExtra(EXTRA_STOCK_CODE, signal.stock_code ?: "")
                putExtra(EXTRA_SIGNAL_TYPE, signal.signal_type ?: "")
                putExtra(EXTRA_ENTRY_PRICE, signal.entry_price ?: "")
                putExtra(EXTRA_TAKE_PROFIT, signal.take_profit ?: "")
                putExtra(EXTRA_STOP_LOSS, signal.stop_loss ?: "")
                putExtra(EXTRA_NOTE, signal.note ?: "")
                putExtra(EXTRA_PUBLISHED_AT, signal.published_at ?: "")
            }
        )
    }

    companion object {
        const val ACTION_SIGNAL_PUSH = "com.alima.sinyalsahamindo.ACTION_SIGNAL_PUSH"
        const val EXTRA_ID = "signal_id"
        const val EXTRA_TITLE = "signal_title"
        const val EXTRA_STOCK_CODE = "signal_stock_code"
        const val EXTRA_SIGNAL_TYPE = "signal_type"
        const val EXTRA_ENTRY_PRICE = "signal_entry_price"
        const val EXTRA_TAKE_PROFIT = "signal_take_profit"
        const val EXTRA_STOP_LOSS = "signal_stop_loss"
        const val EXTRA_NOTE = "signal_note"
        const val EXTRA_PUBLISHED_AT = "signal_published_at"

        fun fetchFcmToken(callback: (String?) -> Unit) {
            FirebaseMessaging.getInstance().token
                .addOnCompleteListener { task ->
                    if (!task.isSuccessful) {
                        callback(null)
                        return@addOnCompleteListener
                    }
                    callback(task.result)
                }
        }

        fun parseSignal(data: Map<String, String>, message: RemoteMessage? = null): SignalItem {
            return SignalItem(
                id = data["id"]?.toIntOrNull() ?: (System.currentTimeMillis() / 1000L).toInt(),
                title = data["title"] ?: data["notif_title"] ?: message?.notification?.title,
                stock_code = data["stock_code"] ?: "",
                signal_type = data["signal_type"] ?: "hold",
                entry_price = data["entry_price"],
                take_profit = data["take_profit"],
                stop_loss = data["stop_loss"],
                note = data["note"] ?: data["notif_body"] ?: message?.notification?.body,
                published_at = data["published_at"]
            )
        }
    }
}

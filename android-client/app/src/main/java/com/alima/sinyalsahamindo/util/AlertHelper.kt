package com.alima.sinyalsahamindo.util

import android.app.PendingIntent
import android.app.NotificationChannel
import android.app.NotificationManager
import android.content.Intent
import android.content.Context
import android.media.RingtoneManager
import android.media.ToneGenerator
import android.media.AudioManager
import android.os.Build
import android.os.VibrationEffect
import android.os.Vibrator
import androidx.core.app.NotificationCompat
import androidx.core.app.NotificationManagerCompat
import com.alima.sinyalsahamindo.R
import com.alima.sinyalsahamindo.data.model.SignalItem
import com.alima.sinyalsahamindo.push.AppFirebaseMessagingService
import com.alima.sinyalsahamindo.ui.MainActivity

object AlertHelper {
    private const val CHANNEL_ID = "signal_alert_channel"

    fun createChannel(context: Context) {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            val manager = context.getSystemService(Context.NOTIFICATION_SERVICE) as NotificationManager
            if (manager.getNotificationChannel(CHANNEL_ID) == null) {
                val channel = NotificationChannel(
                    CHANNEL_ID,
                    "Hard Alert Sinyal",
                    NotificationManager.IMPORTANCE_HIGH
                ).apply {
                    description = "Alert keras untuk sinyal baru"
                    enableVibration(true)
                }
                manager.createNotificationChannel(channel)
            }
        }
    }

    fun hardAlert(context: Context, signal: SignalItem) {
        createChannel(context)

        val vibrator = context.getSystemService(Context.VIBRATOR_SERVICE) as Vibrator
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            vibrator.vibrate(VibrationEffect.createWaveform(longArrayOf(0, 500, 250, 500), -1))
        } else {
            @Suppress("DEPRECATION")
            vibrator.vibrate(longArrayOf(0, 500, 250, 500), -1)
        }

        val tone = ToneGenerator(AudioManager.STREAM_ALARM, 100)
        tone.startTone(ToneGenerator.TONE_CDMA_ALERT_CALL_GUARD, 800)

        val openMainIntent = Intent(context, MainActivity::class.java).apply {
            flags = Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TOP
            putExtra(AppFirebaseMessagingService.EXTRA_ID, signal.id)
            putExtra(AppFirebaseMessagingService.EXTRA_TITLE, signal.title ?: "")
            putExtra(AppFirebaseMessagingService.EXTRA_STOCK_CODE, signal.stock_code ?: "")
            putExtra(AppFirebaseMessagingService.EXTRA_SIGNAL_TYPE, signal.signal_type ?: "")
            putExtra(AppFirebaseMessagingService.EXTRA_ENTRY_PRICE, signal.entry_price ?: "")
            putExtra(AppFirebaseMessagingService.EXTRA_TAKE_PROFIT, signal.take_profit ?: "")
            putExtra(AppFirebaseMessagingService.EXTRA_STOP_LOSS, signal.stop_loss ?: "")
            putExtra(AppFirebaseMessagingService.EXTRA_NOTE, signal.note ?: "")
            putExtra(AppFirebaseMessagingService.EXTRA_PUBLISHED_AT, signal.published_at ?: "")
        }
        val pendingIntent = PendingIntent.getActivity(
            context,
            signal.id,
            openMainIntent,
            PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE
        )

        val messageText = "${signal.stock_code ?: "-"} ${signal.signal_type?.uppercase() ?: ""}"
        val style = NotificationCompat.MessagingStyle("Admin Sinyal")
            .addMessage(messageText, System.currentTimeMillis(), "Admin")

        val notification = NotificationCompat.Builder(context, CHANNEL_ID)
            .setSmallIcon(R.drawable.ic_stat_signal)
            .setContentTitle("Sinyal Baru Masuk")
            .setContentText(signal.title ?: "Cek sinyal terbaru sekarang")
            .setStyle(style)
            .setSound(RingtoneManager.getDefaultUri(RingtoneManager.TYPE_ALARM))
            .setPriority(NotificationCompat.PRIORITY_MAX)
            .setCategory(NotificationCompat.CATEGORY_MESSAGE)
            .setContentIntent(pendingIntent)
            .setAutoCancel(true)
            .build()

        NotificationManagerCompat.from(context).notify(signal.id, notification)
    }
}

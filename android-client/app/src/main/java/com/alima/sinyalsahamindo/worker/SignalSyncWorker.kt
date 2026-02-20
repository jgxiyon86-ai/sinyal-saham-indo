package com.alima.sinyalsahamindo.worker

import android.content.Context
import androidx.work.CoroutineWorker
import androidx.work.WorkerParameters
import com.alima.sinyalsahamindo.data.SignalRepository
import com.alima.sinyalsahamindo.util.AlertHelper
import com.alima.sinyalsahamindo.util.SessionManager

class SignalSyncWorker(
    appContext: Context,
    params: WorkerParameters
) : CoroutineWorker(appContext, params) {
    private val sessionManager = SessionManager(appContext)
    private val repository = SignalRepository()

    override suspend fun doWork(): Result {
        return try {
            val token = sessionManager.getToken().orEmpty()
            if (token.isBlank()) return Result.success()

            val signals = repository.fetchSignals(token)
            val lastId = sessionManager.getLastSignalId()
            val newSignals = signals.filter { it.id > lastId }.sortedBy { it.id }
            newSignals.forEach { AlertHelper.hardAlert(applicationContext, it) }
            signals.maxOfOrNull { it.id }?.let { sessionManager.saveLastSignalId(it) }
            Result.success()
        } catch (_: Exception) {
            Result.retry()
        }
    }
}

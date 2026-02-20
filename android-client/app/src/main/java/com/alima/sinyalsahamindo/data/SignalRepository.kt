package com.alima.sinyalsahamindo.data

import com.alima.sinyalsahamindo.data.model.SignalItem
import com.alima.sinyalsahamindo.data.network.RetrofitProvider

class SignalRepository {
    suspend fun fetchSignals(token: String): List<SignalItem> {
        val response = RetrofitProvider.api.getSignals("Bearer $token")
        if (response.code() == 401) throw UnauthorizedException()
        if (!response.isSuccessful) return emptyList()
        return response.body()?.signals.orEmpty()
    }
}

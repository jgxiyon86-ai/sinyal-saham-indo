package com.alima.sinyalsahamindo.data.model

import com.google.gson.JsonElement
import com.google.gson.Gson

data class SignalResponse(
    val signals: List<SignalItem> = emptyList()
)

data class SignalItem(
    val id: Int,
    val title: String?,
    val stock_code: String?,
    val signal_type: String?,
    val entry_price: String?,
    val take_profit: String?,
    val stop_loss: String?,
    val note: String?,
    val published_at: String?
)

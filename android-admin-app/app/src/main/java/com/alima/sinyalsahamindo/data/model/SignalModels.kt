package com.alima.sinyalsahamindo.data.model

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

data class AdminCreateSignalRequest(
    val title: String,
    val stock_code: String,
    val signal_type: String,
    val entry_price: Double?,
    val take_profit: Double?,
    val stop_loss: Double?,
    val note: String?,
    val image_url: String?,
    val published_at: String?,
    val expires_at: String?,
    val tier_target: String
)

data class AdminCreateSignalResponse(
    val message: String?,
    val signal: SignalItem?
)

data class SignalWaBlastRequest(
    val signal_ids: List<Int>,
    val tier_id: Int?
)

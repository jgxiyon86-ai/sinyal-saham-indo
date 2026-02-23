package com.alima.adminsinyal.data.model

data class LoginRequest(
    val email: String,
    val password: String,
)

data class UserData(
    val id: Int,
    val name: String,
    val email: String,
    val role: String? = null,
)

data class LoginResponse(
    val token: String? = null,
    val access_token: String? = null,
    val user: UserData? = null,
    val message: String? = null,
)

data class AdminCreateSignalRequest(
    val title: String,
    val stock_code: String,
    val signal_type: String,
    val entry_price: Int? = null,
    val take_profit: Int? = null,
    val stop_loss: Int? = null,
    val note: String? = null,
    val image_url: String? = null,
    val published_at: String? = null,
    val expires_at: String? = null,
    val tier_target: String? = null,
    val tier_ids: List<Int>? = null,
)

data class AdminCreateSignalResponse(
    val message: String? = null,
    val signal: SignalItem? = null,
)

data class SignalWaBlastRequest(
    val signal_ids: List<Int>,
    val tier_id: Int? = null,
)

data class SignalWaBlastResponse(
    val message: String? = null,
    val sent: Int? = null,
    val failed: Int? = null,
    val batch_id: Int? = null,
    val queued_targets: Int? = null,
    val targets: Int? = null,
)

data class SignalItem(
    val id: Int,
    val title: String? = null,
    val stock_code: String? = null,
    val signal_type: String? = null,
    val published_at: String? = null,
    val expires_at: String? = null,
    val note: String? = null,
)

data class SignalListResponse(
    val signals: SignalPage? = null,
    val message: String? = null,
)

data class SignalPage(
    val data: List<SignalItem>? = null,
)

data class TierItem(
    val id: Int,
    val name: String? = null,
)

data class TierListResponse(
    val tiers: List<TierItem>? = null,
)

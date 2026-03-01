package com.alima.sinyalsahamindo.data.model

import com.google.gson.JsonElement
import com.google.gson.Gson

data class SignalResponse(
    val signals: JsonElement? = null
) {
    fun getSignalList(): List<SignalItem> {
        val element = signals ?: return emptyList()
        val gson = Gson()
        return try {
            if (element.isJsonArray) {
                element.asJsonArray.map { gson.fromJson(it, SignalItem::class.java) }
            } else if (element.isJsonObject) {
                val data = element.asJsonObject.get("data")
                if (data != null && data.isJsonArray) {
                    data.asJsonArray.map { gson.fromJson(it, SignalItem::class.java) }
                } else {
                    emptyList()
                }
            } else {
                emptyList()
            }
        } catch (e: Exception) {
            emptyList()
        }
    }
}

data class SignalItem(
    val id: Int,
    val title: String?,
    val stock_code: String?,
    val signal_type: String?,
    val entry_price: String?,
    val take_profit: String?,
    val stop_loss: String?,
    val note: String?,
    val published_at: String?,
    val expires_at: String?,
    val tier_target: String? = null
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

data class WaBlastHistoryResponse(
    val history: List<WaLogItem>?
)

data class WaLogItem(
    val id: Int,
    val blast_type: String?,
    val recipients_count: Int,
    val status: String?,
    val blasted_at: String?,
    val admin: AdminShortInfo?
)

data class AdminShortInfo(
    val id: Int,
    val name: String?
)

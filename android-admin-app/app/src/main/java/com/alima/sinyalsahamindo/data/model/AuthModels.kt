package com.alima.sinyalsahamindo.data.model

data class LoginRequest(
    val email: String,
    val password: String
)

data class LoginResponse(
    val token: String?,
    val user: User?
)

data class User(
    val id: Int,
    val name: String,
    val email: String,
    val role: String?
)

data class TierItem(
    val id: Int,
    val name: String
)

data class TierListResponse(
    val tiers: List<TierItem> = emptyList()
)

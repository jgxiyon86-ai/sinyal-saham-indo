package com.alima.adminsinyal.data.network

import com.alima.adminsinyal.data.model.AdminCreateSignalRequest
import com.alima.adminsinyal.data.model.AdminCreateSignalResponse
import com.alima.adminsinyal.data.model.LoginRequest
import com.alima.adminsinyal.data.model.LoginResponse
import com.alima.adminsinyal.data.model.SignalListResponse
import com.alima.adminsinyal.data.model.SignalWaBlastRequest
import com.alima.adminsinyal.data.model.SignalWaBlastResponse
import com.alima.adminsinyal.data.model.TierListResponse
import retrofit2.Response
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.Header
import retrofit2.http.POST

interface ApiService {
    @POST("auth/login")
    suspend fun login(
        @Body body: LoginRequest,
    ): Response<LoginResponse>

    @POST("admin/signals")
    suspend fun createSignal(
        @Header("Authorization") bearerToken: String,
        @Body body: AdminCreateSignalRequest,
    ): Response<AdminCreateSignalResponse>

    @POST("admin/signals/wa-blast")
    suspend fun sendWaBlast(
        @Header("Authorization") bearerToken: String,
        @Body body: SignalWaBlastRequest,
    ): Response<SignalWaBlastResponse>

    @GET("admin/signals")
    suspend fun getSignals(
        @Header("Authorization") bearerToken: String,
    ): Response<SignalListResponse>

    @GET("admin/tiers")
    suspend fun getTiers(
        @Header("Authorization") bearerToken: String,
    ): Response<TierListResponse>
}

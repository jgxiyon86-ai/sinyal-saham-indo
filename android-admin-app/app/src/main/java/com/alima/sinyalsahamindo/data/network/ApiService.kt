package com.alima.sinyalsahamindo.data.network

import com.alima.sinyalsahamindo.data.model.LoginRequest
import com.alima.sinyalsahamindo.data.model.LoginResponse
import com.alima.sinyalsahamindo.data.model.AdminCreateSignalRequest
import com.alima.sinyalsahamindo.data.model.AdminCreateSignalResponse
import com.alima.sinyalsahamindo.data.model.SignalWaBlastRequest
import com.alima.sinyalsahamindo.data.model.SignalResponse
import com.alima.sinyalsahamindo.data.model.TierListResponse
import retrofit2.http.Field
import retrofit2.http.FormUrlEncoded
import retrofit2.Response
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.Header
import retrofit2.http.POST

interface ApiService {
    @POST("auth/login")
    suspend fun login(@Body request: LoginRequest): Response<LoginResponse>

    @GET("client/signals")
    suspend fun getSignals(@Header("Authorization") bearerToken: String): Response<SignalResponse>

    @GET("admin/tiers")
    suspend fun getAdminTiers(@Header("Authorization") bearerToken: String): Response<TierListResponse>

    @POST("admin/signals")
    suspend fun createAdminSignal(
        @Header("Authorization") bearerToken: String,
        @Body request: AdminCreateSignalRequest
    ): Response<AdminCreateSignalResponse>

    @POST("admin/signals/wa-blast")
    suspend fun sendSignalWaBlast(
        @Header("Authorization") bearerToken: String,
        @Body request: SignalWaBlastRequest
    ): Response<Map<String, Any>>

    @POST("auth/logout")
    suspend fun logout(@Header("Authorization") bearerToken: String): Response<Map<String, Any>>

    @FormUrlEncoded
    @POST("auth/fcm-token")
    suspend fun updateFcmToken(
        @Header("Authorization") bearerToken: String,
        @Field("fcm_token") fcmToken: String
    ): Response<Map<String, Any>>

    @POST("admin/clients/pindah")
    suspend fun pindahClients(
        @Header("Authorization") bearerToken: String,
        @Body request: Map<String, Any>
    ): Response<Map<String, Any>>
}

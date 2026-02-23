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
import retrofit2.http.DELETE
import retrofit2.http.GET
import retrofit2.http.Header
import retrofit2.http.POST
import retrofit2.http.Path

interface ApiService {
    @POST("/api/auth/login")
    suspend fun login(
        @Body body: LoginRequest,
    ): Response<LoginResponse>

    @POST("/api/admin/signals")
    suspend fun createSignal(
        @Header("Authorization") bearerToken: String,
        @Body body: AdminCreateSignalRequest,
    ): Response<AdminCreateSignalResponse>

    @POST("/api/admin/signals/wa-blast")
    suspend fun sendWaBlast(
        @Header("Authorization") bearerToken: String,
        @Body body: SignalWaBlastRequest,
    ): Response<SignalWaBlastResponse>

    @GET("/api/admin/signals")
    suspend fun getSignals(
        @Header("Authorization") bearerToken: String,
    ): Response<SignalListResponse>

    @DELETE("/api/admin/signals/{id}")
    suspend fun deleteSignal(
        @Header("Authorization") bearerToken: String,
        @Path("id") id: Int,
    ): Response<Map<String, String>>

    @GET("/api/admin/tiers")
    suspend fun getTiers(
        @Header("Authorization") bearerToken: String,
    ): Response<TierListResponse>
}

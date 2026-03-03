package com.alima.sinyalsahamindo.data.network

import com.alima.sinyalsahamindo.data.model.LoginRequest
import com.alima.sinyalsahamindo.data.model.LoginResponse
import com.alima.sinyalsahamindo.data.model.SignalResponse
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

    @POST("auth/logout")
    suspend fun logout(@Header("Authorization") bearerToken: String): Response<Map<String, Any>>

    @FormUrlEncoded
    @POST("auth/fcm-token")
    suspend fun updateFcmToken(
        @Header("Authorization") bearerToken: String,
        @Field("fcm_token") fcmToken: String
    ): Response<Map<String, Any>>

    @FormUrlEncoded
    @POST("auth/change-password")
    suspend fun changePassword(
        @Header("Authorization") bearerToken: String,
        @Field("old_password") oldPass: String,
        @Field("new_password") newPass: String,
        @Field("new_password_confirmation") confirmPass: String
    ): Response<Map<String, Any>>
}

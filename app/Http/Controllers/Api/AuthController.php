<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = \App\Models\User::with('tier')->where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json(['message' => 'Email atau password tidak valid.'], 422);
        }

        if (! $user->is_active) {
            return response()->json(['message' => 'Akun tidak aktif.'], 403);
        }

        // Single-device login: hapus semua token lama sebelum buat token baru.
        $user->tokens()->delete();

        $token = $user->createToken('mobile-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user()->load('tier'),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->update([
            'fcm_token' => null,
        ]);
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Logout berhasil.',
        ]);
    }

    public function updateFcmToken(Request $request): JsonResponse
    {
        $data = $request->validate([
            'fcm_token' => ['required', 'string', 'max:500'],
        ]);

        $request->user()->update([
            'fcm_token' => $data['fcm_token'],
        ]);

        return response()->json([
            'message' => 'FCM token berhasil disimpan.',
        ]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'old_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (! \Illuminate\Support\Facades\Hash::check($data['old_password'], $request->user()->password)) {
            return response()->json(['message' => 'Password lama salah.'], 422);
        }

        $request->user()->update([
            'password' => \Illuminate\Support\Facades\Hash::make($data['new_password']),
        ]);

        return response()->json([
            'message' => 'Password berhasil diubah.',
        ]);
    }
}

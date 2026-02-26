import 'dart:convert';

import 'package:flutter_client/app_config.dart';
import 'package:flutter_client/models/auth_models.dart';
import 'package:flutter_client/models/signal_models.dart';
import 'package:http/http.dart' as http;

class ApiException implements Exception {
  final String message;
  final int? statusCode;

  ApiException(this.message, {this.statusCode});

  @override
  String toString() => message;
}

class UnauthorizedException extends ApiException {
  UnauthorizedException()
    : super('Session login sudah tidak valid.', statusCode: 401);
}

class ApiService {
  Uri _uri(String path) => Uri.parse('${AppConfig.baseApiUrl}$path');

  Future<LoginResponse> login(LoginRequest request) async {
    final response = await http.post(
      _uri('/auth/login'),
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: jsonEncode(request.toJson()),
    );

    final body = _decodeBody(response.body);
    if (response.statusCode >= 400) {
      throw ApiException(
        (body['message'] ?? 'Login gagal').toString(),
        statusCode: response.statusCode,
      );
    }

    return LoginResponse.fromJson(body);
  }

  Future<List<SignalItem>> getSignals(String token) async {
    final response = await http.get(
      _uri('/client/signals'),
      headers: {'Accept': 'application/json', 'Authorization': 'Bearer $token'},
    );

    if (response.statusCode == 401) {
      throw UnauthorizedException();
    }

    final body = _decodeBody(response.body);
    if (response.statusCode >= 400) {
      throw ApiException(
        (body['message'] ?? 'Gagal mengambil sinyal').toString(),
        statusCode: response.statusCode,
      );
    }

    return SignalResponse.fromJson(body).signals;
  }

  Future<void> logout(String token) async {
    await http.post(
      _uri('/auth/logout'),
      headers: {'Accept': 'application/json', 'Authorization': 'Bearer $token'},
    );
  }

  Map<String, dynamic> _decodeBody(String body) {
    if (body.trim().isEmpty) {
      return {};
    }

    final decoded = jsonDecode(body);
    if (decoded is Map<String, dynamic>) {
      return decoded;
    }

    return {};
  }
}

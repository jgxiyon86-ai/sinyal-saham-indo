import 'package:flutter/material.dart';
import 'package:flutter_client/screens/login_screen.dart';
import 'package:flutter_client/screens/signals_screen.dart';
import 'package:flutter_client/services/api_service.dart';
import 'package:flutter_client/services/session_store.dart';

void main() {
  runApp(const SinyalSahamApp());
}

class SinyalSahamApp extends StatefulWidget {
  const SinyalSahamApp({super.key});

  @override
  State<SinyalSahamApp> createState() => _SinyalSahamAppState();
}

class _SinyalSahamAppState extends State<SinyalSahamApp> {
  final _api = ApiService();
  final _store = SessionStore();

  bool _loading = true;
  String? _token;
  String? _name;
  String? _infoMessage;

  @override
  void initState() {
    super.initState();
    _bootstrap();
  }

  Future<void> _bootstrap() async {
    final token = await _store.readToken();
    final profile = await _store.readProfile();
    if (!mounted) {
      return;
    }

    setState(() {
      _token = token;
      _name = profile['name'];
      _loading = false;
    });
  }

  Future<void> _saveLogin(String token, String name, String email) async {
    await _store.saveSession(token: token, name: name, email: email);
    if (!mounted) {
      return;
    }
    setState(() {
      _token = token;
      _name = name;
      _infoMessage = null;
    });
  }

  Future<void> _logout() async {
    final token = _token;
    if (token != null) {
      await _api.logout(token);
    }
    await _store.clear();

    if (!mounted) {
      return;
    }
    setState(() {
      _token = null;
      _name = null;
      _infoMessage = null;
    });
  }

  Future<void> _forceLogout(String message) async {
    await _store.clear();
    if (!mounted) {
      return;
    }
    setState(() {
      _token = null;
      _name = null;
      _infoMessage = message;
    });
  }

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Sinyal Saham Indo',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        useMaterial3: true,
        brightness: Brightness.light,
        colorScheme: ColorScheme.fromSeed(seedColor: const Color(0xFF0B69E3)),
      ),
      home: _loading
          ? const Scaffold(body: Center(child: CircularProgressIndicator()))
          : _token == null
          ? LoginScreen(
              apiService: _api,
              onLoginSuccess: _saveLogin,
              infoMessage: _infoMessage,
            )
          : SignalsScreen(
              apiService: _api,
              token: _token!,
              name: _name,
              onLogout: _logout,
              onUnauthorized: (message) => _forceLogout(message),
            ),
    );
  }
}

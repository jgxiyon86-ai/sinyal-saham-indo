import 'package:flutter/material.dart';
import 'package:flutter_client/models/signal_models.dart';
import 'package:flutter_client/services/api_service.dart';
import 'package:intl/intl.dart';

class SignalsScreen extends StatefulWidget {
  final ApiService apiService;
  final String token;
  final String? name;
  final Future<void> Function() onLogout;
  final void Function(String message) onUnauthorized;

  const SignalsScreen({
    super.key,
    required this.apiService,
    required this.token,
    required this.name,
    required this.onLogout,
    required this.onUnauthorized,
  });

  @override
  State<SignalsScreen> createState() => _SignalsScreenState();
}

class _SignalsScreenState extends State<SignalsScreen> {
  bool _isLoading = true;
  String? _error;
  List<SignalItem> _signals = [];

  @override
  void initState() {
    super.initState();
    _loadSignals();
  }

  Future<void> _loadSignals() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final signals = await widget.apiService.getSignals(widget.token);
      setState(() => _signals = signals);
    } on UnauthorizedException {
      widget.onUnauthorized(
        'Session dipakai di perangkat lain. Silakan login ulang.',
      );
    } on ApiException catch (e) {
      setState(() => _error = e.message);
    } catch (_) {
      setState(() => _error = 'Gagal memuat sinyal.');
    } finally {
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }

  String _formatDate(String? value) {
    if (value == null || value.isEmpty) {
      return '-';
    }
    final date = DateTime.tryParse(value);
    if (date == null) {
      return value;
    }
    return DateFormat('dd MMM yyyy HH:mm').format(date.toLocal());
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Sinyal - ${widget.name ?? 'Client'}'),
        actions: [
          IconButton(
            onPressed: _isLoading ? null : _loadSignals,
            icon: const Icon(Icons.refresh),
          ),
          IconButton(
            onPressed: () async {
              await widget.onLogout();
            },
            icon: const Icon(Icons.logout),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: _loadSignals,
        child: _isLoading
            ? const Center(child: CircularProgressIndicator())
            : _error != null
            ? ListView(
                children: [
                  const SizedBox(height: 120),
                  Center(
                    child: Text(
                      _error!,
                      style: const TextStyle(color: Colors.red),
                    ),
                  ),
                ],
              )
            : _signals.isEmpty
            ? ListView(
                children: const [
                  SizedBox(height: 120),
                  Center(child: Text('Belum ada sinyal untuk tier kamu.')),
                ],
              )
            : ListView.builder(
                padding: const EdgeInsets.all(12),
                itemCount: _signals.length,
                itemBuilder: (context, index) {
                  final signal = _signals[index];
                  return Card(
                    margin: const EdgeInsets.only(bottom: 12),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Padding(
                      padding: const EdgeInsets.all(14),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Expanded(
                                child: Text(
                                  signal.title ?? '-',
                                  style: const TextStyle(
                                    fontSize: 17,
                                    fontWeight: FontWeight.w700,
                                  ),
                                ),
                              ),
                              Container(
                                padding: const EdgeInsets.symmetric(
                                  horizontal: 10,
                                  vertical: 4,
                                ),
                                decoration: BoxDecoration(
                                  color: const Color(0xFFE4F2FF),
                                  borderRadius: BorderRadius.circular(999),
                                ),
                                child: Text(
                                  (signal.signalType ?? '-').toUpperCase(),
                                  style: const TextStyle(
                                    fontWeight: FontWeight.w700,
                                    color: Color(0xFF0B69E3),
                                  ),
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 8),
                          Text('Kode: ${signal.stockCode ?? '-'}'),
                          Text('Entry: ${signal.entryPrice ?? '-'}'),
                          Text('Take Profit: ${signal.takeProfit ?? '-'}'),
                          Text('Stop Loss: ${signal.stopLoss ?? '-'}'),
                          Text('Publikasi: ${_formatDate(signal.publishedAt)}'),
                          const SizedBox(height: 8),
                          Text(
                            signal.note?.trim().isEmpty ?? true
                                ? '-'
                                : signal.note!,
                          ),
                        ],
                      ),
                    ),
                  );
                },
              ),
      ),
    );
  }
}

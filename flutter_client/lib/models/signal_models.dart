class SignalItem {
  final int id;
  final String? title;
  final String? stockCode;
  final String? signalType;
  final String? entryPrice;
  final String? takeProfit;
  final String? stopLoss;
  final String? note;
  final String? publishedAt;

  const SignalItem({
    required this.id,
    required this.title,
    required this.stockCode,
    required this.signalType,
    required this.entryPrice,
    required this.takeProfit,
    required this.stopLoss,
    required this.note,
    required this.publishedAt,
  });

  factory SignalItem.fromJson(Map<String, dynamic> json) => SignalItem(
    id: (json['id'] as num).toInt(),
    title: json['title'] as String?,
    stockCode: json['stock_code'] as String?,
    signalType: json['signal_type'] as String?,
    entryPrice: json['entry_price']?.toString(),
    takeProfit: json['take_profit']?.toString(),
    stopLoss: json['stop_loss']?.toString(),
    note: json['note'] as String?,
    publishedAt: json['published_at'] as String?,
  );
}

class SignalResponse {
  final List<SignalItem> signals;

  const SignalResponse({required this.signals});

  factory SignalResponse.fromJson(Map<String, dynamic> json) {
    final raw = json['signals'] as List<dynamic>? ?? const [];
    return SignalResponse(
      signals: raw
          .whereType<Map<String, dynamic>>()
          .map(SignalItem.fromJson)
          .toList(),
    );
  }
}

class AppConfig {
  static const String baseApiUrl = String.fromEnvironment(
    'BASE_API_URL',
    defaultValue: 'https://sinyal.cuanholic.com/api',
  );
}

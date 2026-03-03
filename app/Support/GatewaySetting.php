<?php

namespace App\Support;

use App\Models\AppSetting;

class GatewaySetting
{
    public static function baseUrl(): string
    {
        $value = AppSetting::getValue('gateway_base_url');
        if (is_string($value) && trim($value) !== '') {
            return rtrim(trim($value), '/');
        }

        return rtrim((string) config('services.alima_gateway.base_url', ''), '/');
    }

    public static function appApiKey(): string
    {
        $value = AppSetting::getValue('gateway_app_api_key');
        if (is_string($value) && trim($value) !== '') {
            return trim($value);
        }

        return trim((string) config('services.alima_gateway.app_api_key', ''));
    }

    public static function sessionId(): string
    {
        $value = AppSetting::getValue('gateway_session_id');
        if (is_string($value) && trim($value) !== '') {
            return trim($value);
        }

        return trim((string) config('services.alima_gateway.session_id', ''));
    }

    public static function panelUrl(): string
    {
        $value = AppSetting::getValue('gateway_panel_url');
        if (is_string($value) && trim($value) !== '') {
            return rtrim(trim($value), '/');
        }

        return rtrim((string) config('services.alima_gateway.panel_url', ''), '/');
    }

    public static function appId(): string
    {
        $value = AppSetting::getValue('gateway_app_id');
        if (is_string($value) && trim($value) !== '') {
            return trim($value);
        }

        return trim((string) config('services.alima_gateway.app_id', ''));
    }

    public static function birthdayAutoTime(): string
    {
        $value = AppSetting::getValue('wa_birthday_auto_time');
        if (is_string($value) && trim($value) !== '') {
            return trim($value);
        }

        return trim((string) env('WA_BIRTHDAY_AUTO_TIME', '08:00'));
    }

    public static function signalWaDelaySeconds(): int
    {
        $value = AppSetting::getValue('signal_wa_delay_seconds');
        $parsed = is_string($value) ? (int) $value : 0;
        if ($parsed >= 3 && $parsed <= 120) {
            return $parsed;
        }

        return (int) env('SIGNAL_WA_DELAY_SECONDS', 12);
    }

    public static function signalWaMaxRecipients(): int
    {
        $value = AppSetting::getValue('signal_wa_max_recipients');
        $parsed = is_string($value) ? (int) $value : 0;
        if ($parsed >= 1 && $parsed <= 300) {
            return $parsed;
        }

        return (int) env('SIGNAL_WA_MAX_RECIPIENTS', 40);
    }

    public static function signalWaOpeningText(): string
    {
        $value = AppSetting::getValue('signal_wa_opening_text');
        if (is_string($value) && trim($value) !== '') {
            return trim($value);
        }

        return (string) env('SIGNAL_WA_OPENING', 'Halo {name}, berikut update sinyal saham kamu hari ini:');
    }

    public static function signalWaClosingText(): string
    {
        $value = AppSetting::getValue('signal_wa_closing_text');
        if (is_string($value) && trim($value) !== '') {
            return trim($value);
        }

        return (string) env('SIGNAL_WA_CLOSING', 'Gunakan manajemen risiko. Bukan ajakan beli/jual.');
    }

    public static function signalWaImageUrl(): string
    {
        $value = AppSetting::getValue('signal_wa_image_url');
        if (is_string($value)) {
            return trim($value);
        }

        return '';
    }

    public static function signalWaGroupMessages(): bool
    {
        $value = AppSetting::getValue('signal_wa_group_messages');
        if ($value === null) {
            return (bool) env('SIGNAL_WA_GROUP_MESSAGES', true);
        }

        return (bool) $value;
    }
}

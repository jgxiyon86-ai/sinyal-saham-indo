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

    public static function birthdayAutoTime(): string
    {
        $value = AppSetting::getValue('wa_birthday_auto_time');
        if (is_string($value) && trim($value) !== '') {
            return trim($value);
        }

        return trim((string) env('WA_BIRTHDAY_AUTO_TIME', '08:00'));
    }
}


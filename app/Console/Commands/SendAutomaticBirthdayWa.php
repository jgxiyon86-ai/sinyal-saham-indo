<?php

namespace App\Console\Commands;

use App\Models\MessageTemplate;
use App\Models\User;
use App\Models\WaBlastLog;
use App\Services\FonnteService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use RuntimeException;

class SendAutomaticBirthdayWa extends Command
{
    protected $signature = 'wa:auto-birthday {--date=} {--dry-run}';

    protected $description = 'Kirim otomatis WA ulang tahun untuk klient yang berulang tahun hari ini.';

    public function handle(FonnteService $fonnteService): int
    {
        $date = $this->option('date')
            ? Carbon::parse((string) $this->option('date'))
            : now();

        $dateKey = $date->toDateString();
        $isDryRun = (bool) $this->option('dry-run');

        $alreadyRun = WaBlastLog::query()
            ->where('blast_type', 'birthday')
            ->whereIn('status', ['auto-sent', 'auto-partial', 'auto-dry-run'])
            ->whereDate('blasted_at', $dateKey)
            ->exists();

        if ($alreadyRun && ! $isDryRun) {
            $this->warn("Auto birthday blast untuk {$dateKey} sudah pernah dijalankan.");
            return self::SUCCESS;
        }

        $templates = MessageTemplate::query()
            ->where('event_type', 'birthday')
            ->where('is_active', true)
            ->get();

        if ($templates->isEmpty()) {
            $this->warn('Template birthday aktif tidak ditemukan.');
            return self::SUCCESS;
        }

        $clients = User::query()
            ->with('tier')
            ->where('role', 'client')
            ->where('is_active', true)
            ->whereNotNull('whatsapp_number')
            ->whereMonth('birth_date', $date->month)
            ->whereDay('birth_date', $date->day)
            ->get();

        if ($clients->isEmpty()) {
            $this->info("Tidak ada klient ulang tahun pada {$dateKey}.");
            return self::SUCCESS;
        }

        if (! $isDryRun && (string) config('services.alima_gateway.app_api_key') === '') {
            $this->error('ALIMA_GATEWAY_APP_API_KEY belum diisi di file .env');
            return self::FAILURE;
        }

        if (! $isDryRun && (string) config('services.alima_gateway.session_id') === '') {
            $this->error('ALIMA_GATEWAY_SESSION_ID belum diisi di file .env');
            return self::FAILURE;
        }

        $success = 0;
        $failed = 0;
        $rows = [];

        foreach ($clients as $client) {
            $template = $this->pickTemplateForClient($client, $templates);
            if (! $template) {
                $failed++;
                $rows[] = [
                    'name' => $client->name,
                    'whatsapp_number' => $client->whatsapp_number,
                    'message' => null,
                    'status' => 'failed',
                    'response' => 'Template birthday untuk agama client tidak ditemukan.',
                ];
                continue;
            }

            $message = $this->renderTemplate($template->content, $client, $date);

            if ($isDryRun) {
                $success++;
                $rows[] = [
                    'name' => $client->name,
                    'whatsapp_number' => $client->whatsapp_number,
                    'message' => $message,
                    'image_url' => $template->image_url,
                    'status' => 'dry-run',
                    'response' => 'Simulasi tanpa kirim.',
                ];
                continue;
            }

            try {
                $result = $fonnteService->sendMessage(
                    (string) $client->whatsapp_number,
                    $message,
                    $template->image_url
                );
                $success++;
                $rows[] = [
                    'name' => $client->name,
                    'whatsapp_number' => $client->whatsapp_number,
                    'message' => $message,
                    'image_url' => $template->image_url,
                    'status' => 'sent',
                    'response' => $result,
                ];
            } catch (RuntimeException $e) {
                $failed++;
                $rows[] = [
                    'name' => $client->name,
                    'whatsapp_number' => $client->whatsapp_number,
                    'message' => $message,
                    'image_url' => $template->image_url,
                    'status' => 'failed',
                    'response' => $e->getMessage(),
                ];
            }
        }

        $templateUsed = $templates->first();
        WaBlastLog::create([
            'admin_id' => User::query()->where('role', 'admin')->value('id'),
            'message_template_id' => $templateUsed?->id,
            'blast_type' => 'birthday',
            'filters' => [
                'date' => $dateKey,
                'source' => 'scheduler',
            ],
            'recipients_count' => $clients->count(),
            'rendered_messages' => collect($rows)->toJson(),
            'status' => $isDryRun
                ? 'auto-dry-run'
                : ($failed > 0 ? 'auto-partial' : 'auto-sent'),
            'blasted_at' => now(),
        ]);

        $this->info("Selesai. Berhasil: {$success}, Gagal: {$failed}, Total target: ".$clients->count());

        return self::SUCCESS;
    }

    private function pickTemplateForClient(User $client, $templates): ?MessageTemplate
    {
        $exact = $templates->first(function (MessageTemplate $template) use ($client) {
            return $template->religion
                && strtolower($template->religion) === strtolower((string) $client->religion);
        });

        if ($exact) {
            return $exact;
        }

        return $templates->first(fn (MessageTemplate $template) => empty($template->religion));
    }

    private function renderTemplate(string $content, User $client, Carbon $date): string
    {
        return strtr($content, [
            '{name}' => $client->name,
            '{religion}' => (string) $client->religion,
            '{birth_date}' => (string) $client->birth_date?->format('Y-m-d'),
            '{capital_amount}' => (string) $client->capital_amount,
            '{tier}' => (string) optional($client->tier)->name,
            '{date}' => $date->toDateString(),
        ]);
    }
}

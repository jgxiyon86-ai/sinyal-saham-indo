<?php

namespace Database\Seeders;

use App\Models\MessageTemplate;
use App\Models\Tier;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $tiers = collect([
            ['name' => 'Starter', 'min_capital' => 0, 'max_capital' => 9999999, 'description' => 'Tier awal untuk modal kecil.', 'wa_blast_limit' => 40],
            ['name' => 'Growth', 'min_capital' => 10000000, 'max_capital' => 49999999, 'description' => 'Tier menengah dengan akses sinyal lebih luas.', 'wa_blast_limit' => 60],
            ['name' => 'Priority', 'min_capital' => 50000000, 'max_capital' => null, 'description' => 'Tier prioritas untuk modal besar.', 'wa_blast_limit' => 80],
        ])->map(function (array $tierData) {
            return Tier::updateOrCreate(
                ['name' => $tierData['name']],
                $tierData
            );
        });

        User::updateOrCreate([
            'email' => 'admin@sinyalsahamindo.local',
        ], [
            'name' => 'Super Admin',
            'password' => 'admin12345',
            'role' => 'admin',
            'tier_id' => null,
            'address' => 'Jakarta',
            'whatsapp_number' => '628123456789',
            'birth_date' => '1990-01-01',
            'religion' => 'islam',
            'capital_amount' => 0,
            'is_active' => true,
        ]);

        User::updateOrCreate([
            'email' => 'client@sinyalsahamindo.local',
        ], [
            'name' => 'Client Demo',
            'password' => 'client12345',
            'role' => 'client',
            'tier_id' => $tiers->first()?->id,
            'address' => 'Bandung',
            'whatsapp_number' => '628111111111',
            'birth_date' => '1995-06-15',
            'religion' => 'islam',
            'capital_amount' => 5000000,
            'is_active' => true,
        ]);

        MessageTemplate::updateOrCreate([
            'name' => 'Ucapan Ulang Tahun Default',
        ], [
            'event_type' => 'birthday',
            'religion' => null,
            'content' => 'Selamat ulang tahun {name}. Semoga sehat, berkah, dan sukses selalu. Tim Sinyal Saham Indo.',
            'is_active' => true,
        ]);

        MessageTemplate::updateOrCreate([
            'name' => 'Ucapan Idul Fitri',
        ], [
            'event_type' => 'holiday',
            'religion' => 'islam',
            'content' => 'Selamat Hari Raya Idul Fitri {name}. Mohon maaf lahir dan batin. Salam hangat dari Sinyal Saham Indo.',
            'is_active' => true,
        ]);
    }
}

<?php

namespace Database\Seeders;

use App\Models\PaymentGateway;
use Illuminate\Database\Seeder;

class PaymentGatewaySeeder extends Seeder
{
    public function run(): void
    {
        $presetPath = storage_path('app/payment-presets');
        if (!is_dir($presetPath)) {
            return;
        }

        $files = glob($presetPath . '/*.json');

        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);
            if (!$data) continue;

            $gateway = PaymentGateway::firstOrCreate(
                ['name' => $data['name'], 'created_by' => null],
                [
                    'format' => $data['format'],
                    'display_name' => $data['display_name'] ?? $data['name'],
                    'description' => $data['description'] ?? '',
                    'base_url' => $data['base_url'] ?? '',
                    'extra_config' => $data['extra_config'] ?? null,
                    'supported_currencies' => $data['supported_currencies'] ?? [],
                    'is_enabled' => false,
                    'is_test_mode' => true,
                    'logo' => $data['logo'] ?? null,
                    'sort_order' => $data['sort_order'] ?? 0,
                    'created_by' => null,
                ]
            );
        }
    }
}

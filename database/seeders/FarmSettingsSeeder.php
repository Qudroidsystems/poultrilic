<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Support\FarmSettings;
use Illuminate\Database\Seeder;

/** Default prices, costs and business details. Only missing keys are added; values the farm set are kept. */
class FarmSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $values = FarmSettings::DEFAULTS + [
            'farm_name' => env('FARM_NAME', 'Prime Farm'),
            'farm_address' => env('FARM_ADDRESS', ''),
            'farm_phone' => env('FARM_PHONE', ''),
            'invoice_footer' => 'Thank you for your patronage.',
        ];
        foreach ($values as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => (string) $value]);
        }
        FarmSettings::flush();
    }
}

<?php

namespace Database\Seeders;

use App\Models\LivestockType;
use App\Support\LivestockTemplates;
use Illuminate\Database\Seeder;

/**
 * Installs livestock types from the built-in templates (Fish ponds and Goats & sheep by default; set
 * SEED_LIVESTOCK=cattle,goats,fish,pigs,crops to choose). Types that already exist by name are skipped.
 */
class LivestockTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $keys = array_filter(array_map('trim', explode(',', (string) env('SEED_LIVESTOCK', 'fish,goats'))));
        foreach ($keys as $key) {
            $tpl = LivestockTemplates::TEMPLATES[$key] ?? null;
            if ($tpl && ! LivestockType::where('name', $tpl['type']['name'])->exists()) {
                LivestockTemplates::install($key);
            }
        }
    }
}

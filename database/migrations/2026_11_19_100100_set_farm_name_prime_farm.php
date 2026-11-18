<?php

use App\Support\FarmSettings;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The farm is Prime Farm; "Poultrilic" is only the software. Replace a missing or
 * placeholder business name (left by the seeders or the default app name) with "Prime Farm".
 * A name the farm typed itself is left alone.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('settings')) return;
        $current = trim((string) DB::table('settings')->where('key', 'farm_name')->value('value'));
        if (in_array(strtolower($current), ['', 'poultrilic', 'poultrilics', 'poultrilic farm', 'poultrilic demo farm', 'laravel'], true)) {
            DB::table('settings')->updateOrInsert(['key' => 'farm_name'], ['value' => 'Prime Farm', 'updated_at' => now(), 'created_at' => now()]);
        }
        FarmSettings::flush();
    }

    public function down(): void
    {
    }
};

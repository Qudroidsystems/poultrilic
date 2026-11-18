<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seeds Poultrilic in parts. Every seeder is safe to run again, and each is logged in `seeder_log` so a normal
 * `php artisan db:seed` only runs what has not run yet (permissions and roles are always refreshed, so new
 * permissions reach existing roles). A failing seeder is reported and the rest carry on.
 *
 *   php artisan db:seed                          core: permissions, roles, admin user, farm settings
 *   SEED_DEMO=true php artisan db:seed            + a demo farm (flocks with daily records, sales, livestock)
 *   php artisan db:seed --class=DemoFarmSeeder    the demo farm on its own
 *   SEED_FORCE=true php artisan db:seed           run everything again, ignoring the log
 *
 * Admin account: ADMIN_EMAIL / ADMIN_PASSWORD / ADMIN_NAME in .env (see UserTableSeeder).
 */
class DatabaseSeeder extends Seeder
{
    /** Always run: they only add what is missing and resync role permissions. */
    protected array $repeatable = [PermissionTableSeeder::class, RoleTableSeeder::class];

    protected array $stats = ['ran' => 0, 'skipped' => 0, 'failed' => 0];

    public function run(): void
    {
        $this->ensureLogTable();
        $start = microtime(true);

        $this->banner('POULTRILIC · DATABASE SEEDING', now()->format('Y-m-d H:i:s'));

        $this->section('🔐 Access: permissions, roles and the admin account');
        $this->safeCall(PermissionTableSeeder::class, 'Permissions');
        $this->safeCall(RoleTableSeeder::class, 'Roles (Super Admin, Farm Manager, Record Keeper…)');
        $this->safeCall(UserTableSeeder::class, 'Admin user');

        $this->section('⚙️  Farm settings');
        $this->safeCall(FarmSettingsSeeder::class, 'Prices, costs and business details');

        if (filter_var(env('SEED_DEMO', false), FILTER_VALIDATE_BOOLEAN)) {
            $this->section('🐔 Demo farm');
            $this->safeCall(LivestockTemplateSeeder::class, 'Livestock types (fish ponds, goats & sheep)');
            $this->safeCall(DemoFarmSeeder::class, 'Flocks, daily records, sales, expenses, livestock records, reports');
        } else {
            $this->command->line('  <fg=gray>Demo farm skipped (set SEED_DEMO=true to add one).</>');
        }

        $this->summary(microtime(true) - $start);
    }

    /** Run a seeder once (unless repeatable or SEED_FORCE), log it, and keep going if it fails. */
    protected function safeCall(string $class, string $label): void
    {
        $force = filter_var(env('SEED_FORCE', false), FILTER_VALIDATE_BOOLEAN);
        if (! $force && ! in_array($class, $this->repeatable, true) && $this->hasRun($class)) {
            $this->command->line("  ⏭️  {$label} <fg=gray>(already run)</>");
            $this->stats['skipped']++;

            return;
        }

        $this->command->getOutput()->write("  ⏳ {$label}…");
        $t = microtime(true);
        try {
            $this->callSilent($class);
            $this->log($class, true);
            $this->command->getOutput()->write("\r\033[K");
            $this->command->info(sprintf('  ✅ %s (%.1fs)', $label, microtime(true) - $t));
            $this->stats['ran']++;
        } catch (\Throwable $e) {
            $this->log($class, false, $e->getMessage());
            $this->command->getOutput()->write("\r\033[K");
            $this->command->error("  ❌ {$label}: " . $e->getMessage());
            $this->stats['failed']++;
        }
    }

    protected function ensureLogTable(): void
    {
        if (Schema::hasTable('seeder_log')) {
            return;
        }
        Schema::create('seeder_log', function ($table) {
            $table->id();
            $table->string('seeder_name')->unique();
            $table->boolean('success')->default(false);
            $table->text('error')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    protected function hasRun(string $class): bool
    {
        return DB::table('seeder_log')->where('seeder_name', $class)->where('success', true)->exists();
    }

    protected function log(string $class, bool $ok, ?string $error = null): void
    {
        DB::table('seeder_log')->updateOrInsert(['seeder_name' => $class], [
            'success' => $ok, 'error' => $error ? mb_substr($error, 0, 2000) : null,
            'completed_at' => $ok ? now() : null, 'updated_at' => now(), 'created_at' => now(),
        ]);
    }

    protected function banner(string $title, string $sub): void
    {
        $this->command->info('');
        $this->command->info('╔' . str_repeat('═', 62) . '╗');
        $this->command->info('║ ' . str_pad($title, 60) . ' ║');
        $this->command->info('║ ' . str_pad('Started ' . $sub, 60) . ' ║');
        $this->command->info('╚' . str_repeat('═', 62) . '╝');
    }

    protected function section(string $title): void
    {
        $this->command->info('');
        $this->command->line("<options=bold>{$title}</>");
    }

    protected function summary(float $secs): void
    {
        $this->command->info('');
        $this->command->info(sprintf('Done in %.1fs · %d ran · %d skipped · %d failed', $secs, $this->stats['ran'], $this->stats['skipped'], $this->stats['failed']));
        foreach (['users', 'roles', 'permissions', 'flocks', 'daily_entries', 'livestock_units', 'livestock_records', 'sales', 'report_definitions'] as $t) {
            if (Schema::hasTable($t) && ($n = DB::table($t)->count()) > 0) {
                $this->command->line(sprintf('  %-20s %s', $t, number_format($n)));
            }
        }
        $this->command->info('');
    }
}

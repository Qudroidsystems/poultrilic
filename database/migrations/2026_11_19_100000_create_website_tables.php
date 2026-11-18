<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The farm's public website, managed from the portal (Website menu); ported from csskabba-portal.
 *
 * Content the farm writes lives here; what the portal already knows (farm name, contacts,
 * birds on the farm, eggs collected, laying rate, farm units, egg price) is read live.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('website_settings')) {
            Schema::create('website_settings', function (Blueprint $t) {
                $t->string('key', 80)->primary();
                $t->longText('value')->nullable();
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('website_slides')) {
            Schema::create('website_slides', function (Blueprint $t) {
                $t->id();
                $t->string('title', 150)->nullable();
                $t->string('subtitle', 255)->nullable();
                $t->string('image')->nullable();
                $t->string('button_text', 60)->nullable();
                $t->string('button_link')->nullable();
                $t->unsignedSmallInteger('sort')->default(0);
                $t->boolean('published')->default(true);
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('website_pages')) {
            Schema::create('website_pages', function (Blueprint $t) {
                $t->id();
                $t->string('title', 150);
                $t->string('slug', 160)->unique();
                $t->string('menu', 30)->nullable();          // about | farm | products | none
                $t->string('summary', 300)->nullable();
                $t->longText('body')->nullable();
                $t->string('banner')->nullable();
                $t->unsignedSmallInteger('sort')->default(0);
                $t->boolean('published')->default(true);
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('website_posts')) {
            Schema::create('website_posts', function (Blueprint $t) {
                $t->id();
                $t->string('type', 20)->default('news');       // news | event | tip
                $t->string('title', 200);
                $t->string('slug', 220)->unique();
                $t->string('category', 60)->nullable();
                $t->string('excerpt', 400)->nullable();
                $t->longText('body')->nullable();
                $t->string('cover')->nullable();
                $t->date('event_date')->nullable();
                $t->string('event_time', 40)->nullable();
                $t->string('location', 150)->nullable();
                $t->boolean('featured')->default(false);
                $t->boolean('published')->default(true);
                $t->timestamp('published_at')->nullable();
                $t->unsignedBigInteger('created_by')->nullable();
                $t->timestamps();
                $t->index(['type', 'published', 'published_at']);
            });
        }

        if (!Schema::hasTable('website_albums')) {
            Schema::create('website_albums', function (Blueprint $t) {
                $t->id();
                $t->string('title', 150);
                $t->string('slug', 160)->unique();
                $t->string('description', 500)->nullable();
                $t->date('taken_on')->nullable();
                $t->unsignedBigInteger('cover_photo_id')->nullable();
                $t->unsignedSmallInteger('sort')->default(0);
                $t->boolean('published')->default(true);
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('website_photos')) {
            Schema::create('website_photos', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('album_id')->index();
                $t->string('path');
                $t->string('thumb')->nullable();
                $t->string('caption', 255)->nullable();
                $t->unsignedInteger('width')->nullable();
                $t->unsignedInteger('height')->nullable();
                $t->unsignedSmallInteger('sort')->default(0);
                $t->timestamps();
            });
        }

        // Simple lists: products, testimonials, FAQs, values, partners, highlights.
        if (!Schema::hasTable('website_items')) {
            Schema::create('website_items', function (Blueprint $t) {
                $t->id();
                $t->string('kind', 20)->index();               // product | testimonial | faq | value | partner | highlight
                $t->string('title', 200)->nullable();
                $t->text('body')->nullable();
                $t->string('subtitle', 200)->nullable();       // product: unit, e.g. "per crate of 30"
                $t->string('image')->nullable();
                $t->string('price', 60)->nullable();           // product: fixed price text, e.g. "₦4,500"
                $t->string('price_source', 30)->nullable();    // product: live price from the portal (egg_crate | egg_piece)
                $t->string('stock_source', 30)->nullable();    // product: live stock from the portal (eggs | birds)
                $t->string('link')->nullable();
                $t->string('icon', 60)->nullable();
                $t->unsignedSmallInteger('sort')->default(0);
                $t->boolean('published')->default(true);
                $t->timestamps();
            });
        }

        // The farm team shown on the "Our team" page.
        if (!Schema::hasTable('website_people')) {
            Schema::create('website_people', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('user_id')->nullable()->index();
                $t->string('name', 150);
                $t->string('role', 150)->nullable();
                $t->string('group', 40)->default('staff');     // leadership | staff | partners
                $t->text('bio')->nullable();
                $t->string('photo')->nullable();
                $t->unsignedSmallInteger('sort')->default(0);
                $t->boolean('published')->default(true);
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('website_messages')) {
            Schema::create('website_messages', function (Blueprint $t) {
                $t->id();
                $t->string('type', 20)->default('contact');    // contact | order | visit | newsletter
                $t->string('name', 150)->nullable();
                $t->string('email', 150)->nullable();
                $t->string('phone', 40)->nullable();
                $t->string('subject', 200)->nullable();
                $t->text('message')->nullable();
                $t->json('data')->nullable();                  // order: product, quantity, delivery; visit: date
                $t->string('ip', 45)->nullable();
                $t->timestamp('read_at')->nullable();
                $t->timestamps();
            });
        }

        // No sample content: every section of the website stays hidden until the farm adds
        // its own text and photos, and the numbers, products and farm units come live from the portal.
        $now = now();
        if (!DB::table('website_settings')->exists()) {
            foreach (['enabled' => '1', 'show_stats' => '1', 'stats_items' => 'birds,eggs_month,laying_rate,units,customers,years',
                      'show_prices' => '1', 'orders_open' => '1'] as $k => $v) {
                DB::table('website_settings')->insert(['key' => $k, 'value' => $v, 'created_at' => $now, 'updated_at' => $now]);
            }
        }

        // Permission for the Website menu, given to the admin roles that exist.
        if (Schema::hasTable('permissions')) {
            $id = DB::table('permissions')->where('name', 'Manage website')->where('guard_name', 'web')->value('id');
            if (!$id) {
                $row = ['name' => 'Manage website', 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now];
                if (Schema::hasColumn('permissions', 'title')) $row['title'] = 'Website';
                $id = DB::table('permissions')->insertGetId($row);
            }
            foreach (DB::table('roles')->whereIn('name', ['Super Admin', 'Admin', 'Administrator', 'super-admin', 'admin'])->pluck('id') as $rid) {
                DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $id, 'role_id' => $rid]);
            }
            try { app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions(); } catch (\Throwable $e) {}
        }
    }

    public function down(): void
    {
        foreach (['website_messages', 'website_people', 'website_items', 'website_photos', 'website_albums', 'website_posts', 'website_pages', 'website_slides', 'website_settings'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};

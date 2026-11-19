<?php

namespace App\Services\Website;

use App\Models\Customer;
use App\Models\DailyEntry;
use App\Models\Flock;
use App\Models\LivestockType;
use App\Models\LivestockUnit;
use App\Models\Website\WebsiteItem;
use App\Models\Website\WebsitePage;
use App\Services\PoultryMetrics;
use App\Support\FarmSettings;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Everything the farm website needs, in one place (ported from csskabba-portal):
 *  - the website settings the admin edits (cached),
 *  - live farm data from the portal: name and contacts, birds, eggs, laying rate,
 *    egg stock and price, farm units, customers,
 *  - image handling for uploads (resized, with thumbnails) and HTML cleaning
 *    for text written in the admin editor.
 */
class WebsiteContent
{
    protected const CACHE = 'website.settings.v1';

    // ── settings ────────────────────────────────────────────────────────

    public static function settings(): array
    {
        if (!Schema::hasTable('website_settings')) return [];
        return Cache::rememberForever(self::CACHE, fn () => DB::table('website_settings')->pluck('value', 'key')->all());
    }

    public static function get(string $key, $default = null)
    {
        $v = self::settings()[$key] ?? null;
        return ($v === null || $v === '') ? $default : $v;
    }

    public static function put(array $values): void
    {
        $now = now();
        foreach ($values as $k => $v) {
            DB::table('website_settings')->updateOrInsert(['key' => $k], ['value' => is_array($v) ? json_encode($v) : $v, 'updated_at' => $now, 'created_at' => $now]);
        }
        self::flush();
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE);
        self::$farm = null;
        self::$ready = null;
        Cache::forget('website.stats');
        Cache::forget('website.menu');
    }

    /**
     * The website is on unless the admin switched it off. It also shows before
     * `php artisan migrate` has created its tables (live farm data only, no content yet).
     */
    public static function enabled(): bool
    {
        return self::get('enabled', '1') === '1';
    }

    /** True once the website's own tables exist (content, orders and messages can be stored). */
    private static ?bool $ready = null;

    public static function ready(): bool
    {
        try {
            return self::$ready ??= Schema::hasTable('website_settings') && Schema::hasTable('website_items')
                && Schema::hasTable('website_posts') && Schema::hasTable('website_albums') && Schema::hasTable('website_messages');
        } catch (\Throwable $e) {
            return false;
        }
    }

    // ── the farm (from the portal) ──────────────────────────────────────

    /** Name, logo and contacts: Business details in Prices & Costs, plus the website's own extras. */
    private static ?object $farm = null;

    public static function farm(): object
    {
        if (self::$farm) return self::$farm;

        $phones = collect(preg_split('/[,;\/]+/', FarmSettings::text('farm_phone') . ',' . self::get('extra_phones', '')))
            ->map(fn ($p) => trim($p))->filter()->unique()->values()->all();
        $name = FarmSettings::text('farm_name') ?: config('app.name');
        $logo = self::get('logo');
        // The old site's "Prime Farm" logo is only used when that is the farm's name.
        $old = 'website/assets/images/logo/prime-farm-logo.jpeg';
        if (!$logo && stripos($name, 'prime farm') !== false && is_file(public_path($old))) $logo = $old;

        return self::$farm = (object) [
            'name'    => $name,
            'tagline' => self::get('tagline'),
            'address' => FarmSettings::text('farm_address') ?: null,
            'email'   => self::get('email'),
            'phones'  => $phones,
            'logo'    => $logo ? self::fileUrl($logo) : null,
        ];
    }

    /** Base URL of the portal sign-in. */
    public static function portalUrl(string $path = ''): string
    {
        return url($path);
    }

    public static function homeUrl(): string
    {
        return url('/');
    }

    /**
     * Live numbers for the counters strip, straight from the farm records
     * (cached for an hour). The admin picks which ones are shown.
     */
    public static function stats(): array
    {
        $all = Cache::remember('website.stats', now()->addHour(), function () {
            $out = [];
            try {
                $birds = (int) Flock::where('status', 'active')->sum('current_bird_count');
                $out['birds'] = ['value' => $birds, 'label' => 'Birds on the farm today', 'suffix' => '', 'icon' => 'ri-seedling-line'];

                $last = DailyEntry::max('entry_date');
                if ($last) {
                    $to = Carbon::parse($last);
                    $m = app(PoultryMetrics::class)->summary(null, $to->copy()->subDays(29), $to);
                    $out['eggs_month'] = ['value' => (int) ($m['eggs_laid'] ?? 0), 'label' => 'Eggs collected in 30 days', 'suffix' => '', 'icon' => 'ri-checkbox-blank-circle-line'];
                    if (!empty($m['hen_day_pct'])) {
                        $out['laying_rate'] = ['value' => (int) round($m['hen_day_pct']), 'label' => 'Laying rate (hen-day)', 'suffix' => '%', 'icon' => 'ri-line-chart-line'];
                    }
                    $out['egg_stock'] = ['value' => self::eggStock(), 'label' => 'Eggs ready to sell', 'suffix' => '', 'icon' => 'ri-store-2-line'];
                }

                $types = LivestockType::where('active', true)->count();
                $heads = (int) LivestockUnit::where('status', '!=', 'closed')->sum('current_count');
                if ($types) $out['units'] = ['value' => $types + 1, 'label' => 'Farm enterprises', 'suffix' => '', 'icon' => 'ri-plant-line'];
                if ($heads) $out['animals'] = ['value' => $heads, 'label' => 'Other animals & stock', 'suffix' => '', 'icon' => 'ri-bear-smile-line'];

                $customers = (int) Customer::count();
                if ($customers) $out['customers'] = ['value' => $customers, 'label' => 'Customers served', 'suffix' => '+', 'icon' => 'ri-user-smile-line'];
            } catch (\Throwable $e) {
            }
            $founded = (int) self::get('founded_year', 0);
            if ($founded > 1900 && $founded <= now()->year) {
                $out['years'] = ['value' => max(1, now()->year - $founded), 'label' => 'Years of farming', 'suffix' => '+', 'icon' => 'ri-calendar-check-line'];
            }
            return $out;
        });

        $chosen = array_filter(explode(',', (string) self::get('stats_items', 'birds,eggs_month,laying_rate,units,customers,years')));
        return array_values(array_filter(array_map(fn ($k) => $all[$k] ?? null, $chosen), fn ($s) => $s && $s['value'] > 0));
    }

    /** The stat choices the admin can tick. */
    public const STAT_CHOICES = [
        'birds' => 'Birds on the farm today', 'eggs_month' => 'Eggs collected in the last 30 days of records',
        'laying_rate' => 'Laying rate (hen-day %)', 'egg_stock' => 'Eggs ready to sell', 'units' => 'Farm enterprises (poultry + farm units)',
        'animals' => 'Other animals & stock (farm units)', 'customers' => 'Customers served', 'years' => 'Years of farming (from "Year started")',
    ];

    /** Eggs in the store, from the latest record of each active flock. */
    public static function eggStock(): int
    {
        try {
            return (int) Flock::where('status', 'active')->get()->sum(function (Flock $f) {
                $e = $f->dailyEntries()->orderByDesc('entry_date')->orderByDesc('id')->first();
                return (int) ($e->egg_stock ?? 0);
            });
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /** Live price text for a product that follows the portal's prices. */
    public static function livePrice(?string $source): ?string
    {
        $egg = (float) FarmSettings::eggPrice();
        return match ($source) {
            'egg_crate' => $egg > 0 ? '₦' . number_format($egg * 30) : null,
            'egg_piece' => $egg > 0 ? '₦' . number_format($egg, $egg == floor($egg) ? 0 : 2) : null,
            default     => null,
        };
    }

    /** Live availability for a product: [label, in stock?] or null. */
    public static function liveStock(?string $source): ?array
    {
        return match ($source) {
            'eggs'  => ($n = self::eggStock()) > 0 ? ['Available now · about ' . number_format(intdiv($n, 30)) . ' crates', true] : ['Sold out for now — order ahead', false],
            'birds' => ($n = (int) Flock::where('status', 'active')->sum('current_bird_count')) > 0 ? ['Available · ' . number_format($n) . ' birds on the farm', true] : ['Ask about availability', false],
            default => null,
        };
    }

    /** Products for the website: the farm's own list, with live prices and stock. */
    public static function products(?int $limit = null)
    {
        if (!Schema::hasTable('website_items')) return collect();
        return WebsiteItem::where('kind', 'product')->where('published', true)->orderBy('sort')->orderBy('id')
            ->when($limit, fn ($q) => $q->limit($limit))->get()
            ->map(function (WebsiteItem $p) {
                $p->display_price = self::get('show_prices', '1') === '1' ? (self::livePrice($p->price_source) ?? $p->price) : null;
                $p->stock = self::liveStock($p->stock_source);
                return $p;
            });
    }

    /** What the farm keeps: poultry (from flocks) plus each active farm-unit type. */
    public static function enterprises()
    {
        $list = collect();
        try {
            $flocks = Flock::where('status', 'active')->get();
            if ($flocks->count()) {
                $layers = $flocks->where('flock_type', 'layer')->count();
                $list->push((object) [
                    'name' => 'Poultry', 'icon' => 'ri-seedling-line', 'color' => '#d97706',
                    'text' => $layers ? 'Laying hens producing fresh eggs every day.' : 'Healthy birds raised with care.',
                    'count' => (int) $flocks->sum('current_bird_count'), 'count_label' => 'birds',
                    'units' => $flocks->count(), 'unit_label' => \Illuminate\Support\Str::plural('flock', $flocks->count()),
                ]);
            }
            foreach (LivestockType::where('active', true)->orderBy('sort')->orderBy('name')->get() as $t) {
                $units = LivestockUnit::where('livestock_type_id', $t->id)->where('status', '!=', 'closed');
                $list->push((object) [
                    'name' => $t->name, 'icon' => $t->icon ?: 'ri-plant-line', 'color' => $t->color ?: '#0d9488',
                    'text' => $t->description, 'count' => (int) (clone $units)->sum('current_count'),
                    'count_label' => $t->count_label ?: 'head', 'units' => (clone $units)->count(),
                    'unit_label' => \Illuminate\Support\Str::plural($t->unit_label ?: 'unit', (clone $units)->count()),
                ]);
            }
        } catch (\Throwable $e) {
        }
        return $list;
    }

    /** Upcoming events posted on the website. */
    public static function upcomingEvents(int $limit = 4)
    {
        if (!Schema::hasTable('website_posts')) return collect();
        return DB::table('website_posts')->where('type', 'event')->where('published', true)
            ->whereDate('event_date', '>=', today())->orderBy('event_date')->limit($limit)->get()
            ->map(fn ($e) => (object) ['title' => $e->title, 'date' => $e->event_date, 'time' => $e->event_time, 'location' => $e->location,
                                       'url' => route('site.post', $e->slug), 'excerpt' => $e->excerpt]);
    }

    /** A stored file path (relative to the public disk, or a full URL) as a URL. */
    public static function fileUrl(?string $path): ?string
    {
        if (!$path) return null;
        if (Str::startsWith($path, ['http://', 'https://', '//'])) return $path;
        if (Str::startsWith($path, '/')) return url($path);
        if (is_file(public_path($path))) return asset($path);
        $path = ltrim($path, '/');
        // Normal case: public/storage is the `php artisan storage:link` symlink.
        if (is_link(public_path('storage')) || is_file(public_path('storage/' . $path))) return asset('storage/' . $path);
        // Hosts where public/storage is a plain folder: stream website files through Laravel.
        if (Str::startsWith($path, 'website/') && Storage::disk('public')->exists($path)) return route('site.media', ['path' => $path]);
        return asset('storage/' . $path);
    }

    /** Pages grouped by menu (about, farm, products). */
    public static function menu(): array
    {
        if (!Schema::hasTable('website_pages')) return [];
        return Cache::remember('website.menu', 300, fn () => WebsitePage::where('published', true)->whereNotNull('menu')->where('menu', '!=', 'none')
            ->orderBy('sort')->orderBy('title')->get(['title', 'slug', 'menu'])->groupBy('menu')->map(fn ($g) => $g->map(fn ($p) => ['title' => $p->title, 'slug' => $p->slug])->all())->all());
    }

    // ── uploads ─────────────────────────────────────────────────────────

    /**
     * Saves an uploaded image on the public disk, scaled down to $max px on
     * the long side (and a $thumb px thumbnail when asked).
     * @return array{path:string, thumb:?string, width:int, height:int}
     */
    public static function storeImage(UploadedFile $file, string $dir, int $max = 1920, ?int $thumb = null): array
    {
        $dir = 'website/' . trim($dir, '/');
        $name = date('Ymd') . '-' . Str::random(10);
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg');

        $info = @getimagesize($file->getRealPath());
        if (!$info || !function_exists('imagecreatetruecolor') || in_array($ext, ['svg', 'gif'], true)) {
            $path = $file->storeAs($dir, $name . '.' . $ext, 'public');
            return ['path' => $path, 'thumb' => null, 'width' => (int) ($info[0] ?? 0), 'height' => (int) ($info[1] ?? 0)];
        }

        [$w, $h, $type] = $info;
        $src = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($file->getRealPath()),
            IMAGETYPE_PNG  => @imagecreatefrompng($file->getRealPath()),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($file->getRealPath()) : null,
            default        => null,
        };
        if (!$src) {
            $path = $file->storeAs($dir, $name . '.' . $ext, 'public');
            return ['path' => $path, 'thumb' => null, 'width' => $w, 'height' => $h];
        }
        if ($type === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
            $o = @exif_read_data($file->getRealPath())['Orientation'] ?? 1;
            if (in_array($o, [3, 6, 8], true)) {
                $src = imagerotate($src, [3 => 180, 6 => -90, 8 => 90][$o], 0);
                [$w, $h] = [imagesx($src), imagesy($src)];
            }
        }
        $keepAlpha = $type === IMAGETYPE_PNG;
        $save = function ($im, string $file) use ($keepAlpha) {
            Storage::disk('public')->makeDirectory(dirname($file));
            $full = Storage::disk('public')->path($file);
            $keepAlpha ? imagepng($im, $full, 6) : imagejpeg($im, $full, 82);
        };
        $resize = function (int $limit) use ($src, $w, $h, $keepAlpha) {
            $scale = min(1, $limit / max($w, $h));
            $nw = max(1, (int) round($w * $scale)); $nh = max(1, (int) round($h * $scale));
            $dst = imagecreatetruecolor($nw, $nh);
            if ($keepAlpha) { imagealphablending($dst, false); imagesavealpha($dst, true); }
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
            return [$dst, $nw, $nh];
        };

        $ext = $keepAlpha ? 'png' : 'jpg';
        [$big, $bw, $bh] = $resize($max);
        $path = "{$dir}/{$name}.{$ext}";
        $save($big, $path);
        imagedestroy($big);

        $thumbPath = null;
        if ($thumb) {
            [$small] = $resize($thumb);
            $thumbPath = "{$dir}/thumbs/{$name}.{$ext}";
            $save($small, $thumbPath);
            imagedestroy($small);
        }
        imagedestroy($src);

        return ['path' => $path, 'thumb' => $thumbPath, 'width' => $bw, 'height' => $bh];
    }

    public static function deleteFile(?string $path): void
    {
        if ($path && !Str::startsWith($path, ['http', '/'])) Storage::disk('public')->delete($path);
    }

    /** Text from the admin editor, limited to safe formatting (and YouTube embeds). */
    public static function clean(?string $html): ?string
    {
        if ($html === null || trim($html) === '') return null;
        if (!class_exists(\HTMLPurifier::class)) return strip_tags($html, '<p><br><b><strong><i><em><u><h2><h3><h4><ul><ol><li><a><img><blockquote><table><thead><tbody><tr><th><td><hr><span>');

        $config = \HTMLPurifier_Config::createDefault();
        $config->set('Cache.SerializerPath', storage_path('framework/cache'));
        $config->set('HTML.Allowed', 'p,br,b,strong,i,em,u,s,h2,h3,h4,ul,ol,li,a[href|target|title],img[src|alt|width|height],blockquote,table,thead,tbody,tr,th,td,hr,span[style],div[class],iframe[src|width|height]');
        $config->set('CSS.AllowedProperties', 'color,background-color,text-align,font-weight,font-style,text-decoration');
        $config->set('HTML.SafeIframe', true);
        $config->set('URI.SafeIframeRegexp', '%^(https?:)?//(www\.youtube(?:-nocookie)?\.com/embed/|player\.vimeo\.com/video/)%');
        $config->set('Attr.AllowedFrameTargets', ['_blank']);
        return (new \HTMLPurifier($config))->purify($html);
    }

    public static function uniqueSlug(string $table, string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: Str::random(6);
        $slug = $base;
        $i = 2;
        while (DB::table($table)->where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }
}

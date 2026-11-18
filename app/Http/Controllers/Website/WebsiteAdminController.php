<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Website\WebsiteAlbum;
use App\Models\Website\WebsiteItem;
use App\Models\Website\WebsiteMessage;
use App\Models\Website\WebsitePage;
use App\Models\Website\WebsitePerson;
use App\Models\Website\WebsitePhoto;
use App\Models\Website\WebsitePost;
use App\Models\Website\WebsiteSlide;
use App\Services\Website\WebsiteContent as W;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Website admin (portal › Website), ported from csskabba-portal: settings, home slider,
 * products, pages, news & events, gallery, team, testimonials / FAQs / values / partners,
 * and orders and messages from the site. Simple content types share one list + form.
 */
class WebsiteAdminController extends Controller
{
    /**
     * type => [model, title, fields]; field = [name, label, kind, options]
     * kinds: text, textarea, html, image, select, date, check, number, icon
     */
    protected function types(): array
    {
        $pageMenus = ['about' => 'About the farm', 'farm' => 'Our farm', 'products' => 'Products', 'none' => 'Not in the menu'];
        $item = fn (string $kind, array $fields) => [WebsiteItem::class, WebsiteItem::KINDS[$kind][0], $fields, ['kind' => $kind], WebsiteItem::KINDS[$kind][1]];

        return [
            'slides' => [WebsiteSlide::class, 'Home slider', [
                ['image', 'Background photo (wide, at least 1600px)', 'image', ['max' => 2400]],
                ['title', 'Heading', 'text', ['required' => true]],
                ['subtitle', 'Text under the heading', 'textarea'],
                ['button_text', 'Button text', 'text'],
                ['button_link', 'Button link', 'text', ['hint' => 'e.g. /products or https://…']],
                ['sort', 'Order', 'number'],
                ['published', 'Show on the website', 'check'],
            ], [], 'The big rotating pictures at the top of the home page.'],
            'products' => $item('product', [
                ['title', 'Product', 'text', ['required' => true, 'hint' => 'e.g. Fresh table eggs, Live broilers, Catfish']],
                ['subtitle', 'Sold by', 'text', ['hint' => 'e.g. per crate of 30, per kg, per bird']],
                ['body', 'Description', 'textarea'],
                ['image', 'Photo', 'image', ['max' => 1400]],
                ['price', 'Price (fixed)', 'text', ['hint' => 'e.g. ₦4,500. Leave empty to show "Ask for price".']],
                ['price_source', 'Live price from the portal', 'select', ['options' => ['' => 'No — use the fixed price', 'egg_crate' => 'Egg price × 30 (per crate)', 'egg_piece' => 'Egg price (per egg)']]],
                ['stock_source', 'Live availability', 'select', ['options' => ['' => 'Not shown', 'eggs' => 'From egg stock in the farm records', 'birds' => 'From birds on the farm']]],
                ['sort', 'Order', 'number'], ['published', 'Show', 'check'],
            ]),
            'pages' => [WebsitePage::class, 'Pages', [
                ['title', 'Title', 'text', ['required' => true]],
                ['menu', 'Show under menu', 'select', ['options' => $pageMenus]],
                ['summary', 'Short summary', 'textarea', ['hint' => 'Shown under the title and in page lists.']],
                ['banner', 'Banner photo (optional)', 'image', ['max' => 2200]],
                ['body', 'Content', 'html'],
                ['sort', 'Order', 'number'],
                ['published', 'Published', 'check'],
            ], [], 'About the farm, how you raise your birds, delivery terms… anything with its own page.'],
            'posts' => [WebsitePost::class, 'News & events', [
                ['type', 'Type', 'select', ['options' => ['news' => 'News', 'event' => 'Event', 'tip' => 'Farm tip']]],
                ['title', 'Title', 'text', ['required' => true]],
                ['category', 'Category tag', 'text', ['hint' => 'e.g. Harvest, Training, Offers']],
                ['cover', 'Cover photo', 'image', ['max' => 1800]],
                ['excerpt', 'Short summary', 'textarea'],
                ['body', 'Full story', 'html'],
                ['event_date', 'Event date (events)', 'date'],
                ['event_time', 'Event time (events)', 'text', ['hint' => 'e.g. 10:00am – 2:00pm']],
                ['location', 'Venue (events)', 'text'],
                ['published_at', 'Publish date', 'date'],
                ['featured', 'Feature on the home page', 'check'],
                ['published', 'Published', 'check'],
            ], [], 'Announcements, offers, farm days, training sessions and farming tips.'],
            'people' => [WebsitePerson::class, 'Our team', [
                ['name', 'Name', 'text', ['required' => true]],
                ['role', 'Role / title', 'text'],
                ['group', 'Group', 'select', ['options' => ['leadership' => 'Management', 'staff' => 'Farm team', 'partners' => 'Advisers & vets']]],
                ['photo', 'Photo', 'image', ['max' => 900]],
                ['bio', 'Short bio', 'textarea'],
                ['sort', 'Order', 'number'],
                ['published', 'Show on the website', 'check'],
            ], [], 'People shown on the "Our team" page.'],
            'testimonials' => $item('testimonial', [
                ['title', 'Name', 'text', ['required' => true]], ['subtitle', 'Who they are', 'text', ['hint' => 'e.g. Supermarket owner, Ikeja']],
                ['body', 'What they said', 'textarea', ['required' => true]], ['image', 'Photo (optional)', 'image', ['max' => 500]],
                ['sort', 'Order', 'number'], ['published', 'Show', 'check'],
            ]),
            'faqs' => $item('faq', [
                ['title', 'Question', 'text', ['required' => true]], ['body', 'Answer', 'textarea', ['required' => true]],
                ['sort', 'Order', 'number'], ['published', 'Show', 'check'],
            ]),
            'values' => $item('value', [
                ['title', 'Value', 'text', ['required' => true]], ['body', 'Short description', 'textarea'],
                ['icon', 'Icon', 'icon'], ['sort', 'Order', 'number'], ['published', 'Show', 'check'],
            ]),
            'highlights' => $item('highlight', [
                ['title', 'Highlight', 'text', ['required' => true]], ['body', 'Short text', 'textarea'],
                ['icon', 'Icon', 'icon'], ['sort', 'Order', 'number'], ['published', 'Show', 'check'],
            ]),
            'partners' => $item('partner', [
                ['title', 'Name', 'text', ['required' => true]], ['image', 'Logo', 'image', ['max' => 600, 'required' => true]],
                ['link', 'Website (optional)', 'text'], ['sort', 'Order', 'number'], ['published', 'Show', 'check'],
            ]),
        ];
    }

    public function __construct()
    {
        $this->middleware('permission:Manage website');
    }

    // ── overview ────────────────────────────────────────────────────────

    public function index()
    {
        $count = fn ($t, $w = null) => Schema::hasTable($t) ? ($w ? $w(DB::table($t))->count() : DB::table($t)->count()) : 0;
        return view('website.admin.index', [
            'pagetitle' => 'Website',
            'counts' => [
                'slides' => $count('website_slides'), 'pages' => $count('website_pages'), 'posts' => $count('website_posts'),
                'albums' => $count('website_albums'), 'photos' => $count('website_photos'), 'people' => $count('website_people'),
                'testimonials' => $count('website_items', fn ($q) => $q->where('kind', 'testimonial')),
                'faqs' => $count('website_items', fn ($q) => $q->where('kind', 'faq')),
                'values' => $count('website_items', fn ($q) => $q->where('kind', 'value')),
                'highlights' => $count('website_items', fn ($q) => $q->where('kind', 'highlight')),
                'partners' => $count('website_items', fn ($q) => $q->where('kind', 'partner')),
                'products' => $count('website_items', fn ($q) => $q->where('kind', 'product')),
                'unread' => $count('website_messages', fn ($q) => $q->whereNull('read_at')->where('type', '!=', 'newsletter')),
                'orders' => $count('website_messages', fn ($q) => $q->where('type', 'order')->whereNull('read_at')),
                'subscribers' => $count('website_messages', fn ($q) => $q->where('type', 'newsletter')),
            ],
            'recent' => WebsiteMessage::where('type', '!=', 'newsletter')->latest()->limit(6)->get(),
            'enabled' => W::enabled(),
        ]);
    }

    // ── settings ────────────────────────────────────────────────────────

    public function settings()
    {
        return view('website.admin.settings', ['pagetitle' => 'Website settings', 's' => W::settings(), 'farm' => W::farm()]);
    }

    public function saveSettings(Request $request)
    {
        $text = ['tagline', 'hero_title', 'hero_subtitle', 'hero_button_text', 'hero_button_link', 'intro_title', 'values_title', 'footer_about',
                 'social_facebook', 'social_instagram', 'social_x', 'social_youtube', 'social_tiktok', 'whatsapp', 'office_hours',
                 'meta_description', 'founded_year', 'primary_color', 'secondary_color', 'accent_color', 'email', 'extra_phones', 'delivery_note'];
        $rules = array_fill_keys($text, 'nullable|string|max:500');
        $rules = array_merge($rules, [
            'primary_color' => 'nullable|regex:/^#[0-9a-fA-F]{6}$/', 'secondary_color' => 'nullable|regex:/^#[0-9a-fA-F]{6}$/',
            'accent_color' => 'nullable|regex:/^#[0-9a-fA-F]{6}$/', 'founded_year' => 'nullable|integer|min:1800|max:' . date('Y'),
            'intro_body' => 'nullable|string|max:20000', 'map_embed' => 'nullable|string|max:3000',
            'hero_image' => 'nullable|image|max:8192', 'intro_image' => 'nullable|image|max:8192', 'logo' => 'nullable|image|max:4096',
            'email' => 'nullable|email|max:150', 'stats_items' => 'nullable|array', 'stats_items.*' => 'in:' . implode(',', array_keys(W::STAT_CHOICES)),
        ]);
        $data = $request->validate($rules);

        $values = [];
        foreach ($text as $k) $values[$k] = trim((string) ($data[$k] ?? ''));
        foreach (['social_facebook', 'social_instagram', 'social_x', 'social_youtube', 'social_tiktok'] as $k) {
            if ($values[$k] !== '' && !Str::startsWith($values[$k], ['http://', 'https://'])) $values[$k] = 'https://' . $values[$k];
        }
        $values['intro_body'] = W::clean($data['intro_body'] ?? '') ?? '';

        $map = trim((string) ($data['map_embed'] ?? ''));
        $values['map_embed'] = preg_match('#<iframe[^>]+src="https://(www\.)?google\.com/maps/embed[^"]*"[^>]*></iframe>#i', $map, $m) ? $m[0] : '';
        foreach (['enabled', 'show_stats', 'show_prices', 'orders_open'] as $k) $values[$k] = $request->boolean($k) ? '1' : '0';
        $values['stats_items'] = implode(',', $data['stats_items'] ?? []);

        foreach (['hero_image' => 2400, 'intro_image' => 1600, 'logo' => 600] as $k => $max) {
            if ($request->hasFile($k)) {
                W::deleteFile(W::get($k));
                $values[$k] = W::storeImage($request->file($k), 'settings', $max)['path'];
            } elseif ($request->boolean('remove_' . $k)) {
                W::deleteFile(W::get($k));
                $values[$k] = '';
            }
        }

        W::put($values);
        return back()->with('success', 'Website settings saved.');
    }

    // ── generic content (slides, pages, posts, people, items) ───────────

    protected function type(string $type): array
    {
        $t = $this->types()[$type] ?? abort(404);
        return ['model' => $t[0], 'title' => $t[1], 'fields' => $t[2], 'scope' => $t[3] ?? [], 'about' => $t[4] ?? '', 'key' => $type];
    }

    public function list(Request $request, string $type)
    {
        $t = $this->type($type);
        $q = $t['model']::query()->where($t['scope']);
        if ($request->filled('q')) {
            $first = $t['fields'][0][2] === 'image' ? $t['fields'][1][0] : $t['fields'][0][0];
            $q->where($first, 'like', '%' . $request->get('q') . '%');
        }
        if ($type === 'posts' && $request->filled('kind')) $q->where('type', $request->get('kind'));
        $hasSort = collect($t['fields'])->contains(fn ($f) => $f[0] === 'sort');
        $rows = ($hasSort ? $q->orderBy('sort') : $q->orderByDesc('published_at'))->orderByDesc('id')->paginate(25)->withQueryString();
        return view('website.admin.list', ['pagetitle' => $t['title'], 't' => $t, 'rows' => $rows]);
    }

    public function create(string $type)
    {
        $t = $this->type($type);
        $row = new $t['model']();
        $row->published = true;
        if ($type === 'posts') { $row->type = request('kind', 'news'); $row->published_at = now(); }
        return view('website.admin.form', ['pagetitle' => 'New — ' . $t['title'], 't' => $t, 'row' => $row]);
    }

    public function edit(string $type, int $id)
    {
        $t = $this->type($type);
        $row = $t['model']::where($t['scope'])->findOrFail($id);
        return view('website.admin.form', ['pagetitle' => 'Edit — ' . $t['title'], 't' => $t, 'row' => $row]);
    }

    public function store(Request $request, string $type)
    {
        $t = $this->type($type);
        $row = new $t['model']($t['scope']);
        $this->fill($request, $t, $row);
        return redirect()->route('website.admin.list', $type)->with('success', 'Saved.');
    }

    public function update(Request $request, string $type, int $id)
    {
        $t = $this->type($type);
        $row = $t['model']::where($t['scope'])->findOrFail($id);
        $this->fill($request, $t, $row);
        return redirect()->route('website.admin.list', $type)->with('success', 'Saved.');
    }

    public function destroy(string $type, int $id)
    {
        $t = $this->type($type);
        $row = $t['model']::where($t['scope'])->findOrFail($id);
        foreach ($t['fields'] as [$name, , $kind]) if ($kind === 'image') W::deleteFile($row->{$name});
        $row->delete();
        W::flush();
        Cache::forget('website.menu');
        return back()->with('success', 'Deleted.');
    }

    protected function fill(Request $request, array $t, $row): void
    {
        $rules = [];
        foreach ($t['fields'] as $f) {
            [$name, $label, $kind] = $f;
            $opt = $f[3] ?? [];
            $req = !empty($opt['required']) ? 'required' : 'nullable';
            $rules[$name] = match ($kind) {
                'image'  => ($req === 'required' && !$row->{$name} ? 'required' : 'nullable') . '|image|max:8192',
                'html'   => 'nullable|string|max:200000',
                'date'   => 'nullable|date',
                'number' => 'nullable|integer|min:0|max:65000',
                'check'  => 'nullable',
                'select' => $req . '|in:' . implode(',', array_keys($opt['options'] ?? [])),
                'textarea' => $req . '|string|max:5000',
                default  => $req . '|string|max:255',
            };
        }
        $data = $request->validate($rules);

        foreach ($t['fields'] as $f) {
            [$name, , $kind] = $f;
            $opt = $f[3] ?? [];
            if ($kind === 'image') {
                if ($request->hasFile($name)) {
                    W::deleteFile($row->{$name});
                    $row->{$name} = W::storeImage($request->file($name), $t['key'], $opt['max'] ?? 1600)['path'];
                } elseif ($request->boolean('remove_' . $name)) {
                    W::deleteFile($row->{$name});
                    $row->{$name} = null;
                }
            } elseif ($kind === 'check') {
                $row->{$name} = $request->boolean($name);
            } elseif ($kind === 'html') {
                $row->{$name} = W::clean($data[$name] ?? null);
            } elseif ($kind === 'number') {
                $row->{$name} = (int) ($data[$name] ?? 0);
            } else {
                $row->{$name} = ($data[$name] ?? null) === '' ? null : ($data[$name] ?? null);
            }
        }

        $table = $row->getTable();
        if (Schema::hasColumn($table, 'slug')) {
            $source = $row->title ?? $row->name ?? Str::random(6);
            if (!$row->slug || $row->isDirty('title')) $row->slug = W::uniqueSlug($table, $source, $row->id);
        }
        if ($row instanceof WebsitePost) {
            $row->published_at = $row->published_at ?: now();
            $row->created_by = $row->created_by ?: auth()->id();
        }
        $row->save();
        W::flush();
        Cache::forget('website.menu');
    }

    // ── people from staff ───────────────────────────────────────────────

    public function importStaff(Request $request)
    {
        $data = $request->validate(['user_ids' => 'required|array', 'user_ids.*' => 'integer', 'group' => 'required|in:leadership,staff,partners']);
        $added = 0;
        foreach (\App\Models\User::with('roles')->whereIn('id', $data['user_ids'])->get() as $u) {
            if (WebsitePerson::where('user_id', $u->id)->exists()) continue;
            WebsitePerson::create([
                'user_id' => $u->id, 'name' => $u->name, 'role' => $u->roles->pluck('name')->first(),
                'group' => $data['group'], 'photo' => $u->profile_image ?: null, 'published' => true,
                'sort' => (int) WebsitePerson::max('sort') + 1,
            ]);
            $added++;
        }
        return back()->with('success', "{$added} team member(s) added. Edit them to add a role and a short bio.");
    }

    public function staffOptions()
    {
        $taken = WebsitePerson::whereNotNull('user_id')->pluck('user_id');
        return response()->json(\App\Models\User::with('roles')->whereNotIn('id', $taken)->orderBy('first_name')->get()
            ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'job_title' => $u->roles->pluck('name')->first()]));
    }

    // ── gallery ─────────────────────────────────────────────────────────

    public function albums()
    {
        return view('website.admin.albums', [
            'pagetitle' => 'Gallery',
            'albums' => WebsiteAlbum::withCount('photos')->with('photos')->orderBy('sort')->orderByDesc('taken_on')->orderByDesc('id')->get(),
        ]);
    }

    public function saveAlbum(Request $request, ?int $id = null)
    {
        $data = $request->validate([
            'title' => 'required|string|max:150', 'description' => 'nullable|string|max:500',
            'taken_on' => 'nullable|date', 'sort' => 'nullable|integer|min:0', 'published' => 'nullable',
        ]);
        $album = $id ? WebsiteAlbum::findOrFail($id) : new WebsiteAlbum();
        $album->fill([
            'title' => $data['title'], 'description' => $data['description'] ?? null, 'taken_on' => $data['taken_on'] ?? null,
            'sort' => (int) ($data['sort'] ?? 0), 'published' => $request->boolean('published', !$id),
        ]);
        if (!$album->slug || $album->isDirty('title')) $album->slug = W::uniqueSlug('website_albums', $data['title'], $album->id);
        $album->save();
        return redirect()->route('website.admin.album', $album->id)->with('success', $id ? 'Album saved.' : 'Album created — now add photos.');
    }

    public function album(int $id)
    {
        return view('website.admin.album', ['pagetitle' => 'Gallery album', 'album' => WebsiteAlbum::with('photos')->findOrFail($id)]);
    }

    public function uploadPhotos(Request $request, int $id)
    {
        $album = WebsiteAlbum::findOrFail($id);
        $request->validate(['photos' => 'required|array|max:40', 'photos.*' => 'image|max:12288']);
        $sort = (int) $album->photos()->max('sort');
        $added = 0;
        foreach ($request->file('photos') as $file) {
            $img = W::storeImage($file, 'gallery/' . $album->id, 2000, 640);
            WebsitePhoto::create(['album_id' => $album->id, 'path' => $img['path'], 'thumb' => $img['thumb'],
                                  'width' => $img['width'], 'height' => $img['height'], 'sort' => ++$sort]);
            $added++;
        }
        if (!$album->cover_photo_id) $album->update(['cover_photo_id' => $album->photos()->value('id')]);
        if ($request->expectsJson()) return response()->json(['ok' => true, 'added' => $added]);
        return back()->with('success', "{$added} photo(s) uploaded.");
    }

    public function updatePhotos(Request $request, int $id)
    {
        $album = WebsiteAlbum::findOrFail($id);
        $data = $request->validate(['captions' => 'nullable|array', 'captions.*' => 'nullable|string|max:255',
                                    'order' => 'nullable|array', 'cover' => 'nullable|integer', 'delete' => 'nullable|array']);
        foreach ((array) ($data['captions'] ?? []) as $pid => $cap) {
            WebsitePhoto::where('album_id', $album->id)->where('id', $pid)->update(['caption' => $cap]);
        }
        foreach ((array) ($data['order'] ?? []) as $pid => $n) {
            WebsitePhoto::where('album_id', $album->id)->where('id', $pid)->update(['sort' => (int) $n]);
        }
        foreach ((array) ($data['delete'] ?? []) as $pid) {
            $p = WebsitePhoto::where('album_id', $album->id)->find($pid);
            if ($p) { W::deleteFile($p->path); W::deleteFile($p->thumb); $p->delete(); }
        }
        $cover = $data['cover'] ?? $album->cover_photo_id;
        if (!WebsitePhoto::where('album_id', $album->id)->where('id', $cover)->exists()) $cover = $album->photos()->value('id');
        $album->update(['cover_photo_id' => $cover]);
        return back()->with('success', 'Photos updated.');
    }

    public function deleteAlbum(int $id)
    {
        $album = WebsiteAlbum::with('photos')->findOrFail($id);
        foreach ($album->photos as $p) { W::deleteFile($p->path); W::deleteFile($p->thumb); $p->delete(); }
        $album->delete();
        return redirect()->route('website.admin.albums')->with('success', 'Album deleted.');
    }

    // ── messages ────────────────────────────────────────────────────────

    public function messages(Request $request)
    {
        $type = $request->get('type');
        return view('website.admin.messages', [
            'pagetitle' => 'Website messages',
            'type' => $type,
            'rows' => WebsiteMessage::when($type, fn ($q) => $q->where('type', $type), fn ($q) => $q->where('type', '!=', 'newsletter'))
                ->latest()->paginate(30)->withQueryString(),
            'counts' => WebsiteMessage::selectRaw('type, COUNT(*) as n, SUM(read_at IS NULL) as unread')->groupBy('type')->get()->keyBy('type'),
        ]);
    }

    public function readMessage(int $id)
    {
        $m = WebsiteMessage::findOrFail($id);
        if (!$m->read_at) $m->update(['read_at' => now()]);
        return response()->json(['ok' => true]);
    }

    public function deleteMessage(int $id)
    {
        WebsiteMessage::findOrFail($id)->delete();
        return back()->with('success', 'Message deleted.');
    }

    public function exportMessages(Request $request)
    {
        $type = $request->get('type', 'order');
        $rows = WebsiteMessage::where('type', $type)->latest()->get();
        $name = 'website-' . $type . '-' . date('Ymd') . '.csv';
        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'Type', 'Name', 'Phone', 'E-mail', 'Subject', 'Message', 'Details']);
            foreach ($rows as $r) {
                fputcsv($out, [$r->created_at?->format('Y-m-d H:i'), $r->type, $r->name, $r->phone, $r->email, $r->subject, $r->message,
                               collect($r->data ?? [])->map(fn ($v, $k) => "{$k}: {$v}")->implode('; ')]);
            }
            fclose($out);
        }, $name, ['Content-Type' => 'text/csv']);
    }
}

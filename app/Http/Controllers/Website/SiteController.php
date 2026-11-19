<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Website\WebsiteAlbum;
use App\Models\Website\WebsiteItem;
use App\Models\Website\WebsiteMessage;
use App\Models\Website\WebsitePage;
use App\Models\Website\WebsitePerson;
use App\Models\Website\WebsitePost;
use App\Models\Website\WebsiteSlide;
use App\Services\Website\WebsiteContent as W;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * The farm's public website (ported from csskabba-portal's school website).
 * Nothing is hard-coded: content comes from Website in the portal, and the
 * numbers, products, prices, stock and farm units come live from the farm records.
 */
class SiteController extends Controller
{
    public function home()
    {
        if (!W::enabled()) return redirect()->route('login');

        if (!W::ready()) {
            // Before `php artisan migrate`: show the site with the live farm data only.
            return view('website.home', [
                'slides' => collect(), 'values' => collect(), 'highlights' => collect(), 'testimonials' => collect(),
                'faqs' => collect(), 'partners' => collect(), 'products' => collect(), 'news' => collect(),
                'events' => collect(), 'albums' => collect(), 'enterprises' => W::enterprises(), 'stats' => W::stats(),
            ]);
        }

        return view('website.home', [
            'slides'       => WebsiteSlide::where('published', true)->orderBy('sort')->orderBy('id')->get(),
            'values'       => $this->items('value'),
            'highlights'   => $this->items('highlight'),
            'testimonials' => $this->items('testimonial'),
            'faqs'         => $this->items('faq', 6),
            'partners'     => $this->items('partner'),
            'products'     => W::products(6),
            'enterprises'  => W::enterprises(),
            'news'         => WebsitePost::live()->whereIn('type', ['news', 'tip'])->orderByDesc('featured')->orderByDesc('published_at')->limit(3)->get(),
            'events'       => W::upcomingEvents(3),
            'albums'       => WebsiteAlbum::where('published', true)->with('photos')->orderBy('sort')->orderByDesc('taken_on')->limit(8)->get(),
            'stats'        => W::get('show_stats', '1') === '1' ? W::stats() : [],
        ]);
    }

    /** About the farm: the farm's own About page (when written), story, process, live numbers, team. */
    public function about()
    {
        $ready = W::ready();
        $pages = $ready ? WebsitePage::where('published', true)->whereIn('menu', ['about', 'farm'])->orderBy('sort')->get() : collect();
        return view('website.about', [
            'page'        => $pages->firstWhere('menu', 'about'),
            'more'        => $pages->slice(1)->values(),
            'people'      => $ready ? WebsitePerson::where('published', true)->orderBy('sort')->limit(4)->get() : collect(),
            'enterprises' => W::enterprises(),
            'stats'       => W::get('show_stats', '1') === '1' ? W::stats() : [],
        ]);
    }

    public function products()
    {
        return view('website.products', [
            'products'    => W::productsOrEggs(),
            'enterprises' => W::enterprises(),
            'faqs'        => $this->items('faq'),
            'pages'       => W::ready() ? WebsitePage::where('menu', 'products')->where('published', true)->orderBy('sort')->get() : collect(),
        ]);
    }

    public function order(Request $request)
    {
        if (!W::ready()) return back()->withErrors(['website' => 'Online forms are not available yet. Please call or WhatsApp us.']);
        abort_unless(W::get('orders_open', '1') === '1', 404);
        $data = $request->validate([
            'product'  => 'required|string|max:150',
            'quantity' => 'required|string|max:60',
            'delivery' => 'nullable|in:pickup,delivery',
            'needed_on' => 'nullable|date|after_or_equal:today',
            'address'  => 'nullable|string|max:300',
            'name'     => 'required|string|max:150',
            'phone'    => 'required|string|max:40',
            'email'    => 'nullable|email|max:150',
            'message'  => 'nullable|string|max:2000',
            'website'  => 'nullable|max:0', // honeypot
        ]);
        WebsiteMessage::create([
            'type' => 'order', 'name' => $data['name'], 'email' => $data['email'] ?? null, 'phone' => $data['phone'],
            'subject' => 'Order — ' . $data['quantity'] . ' × ' . $data['product'],
            'message' => $data['message'] ?? null, 'ip' => $request->ip(),
            'data' => array_filter(array_intersect_key($data, array_flip(['product', 'quantity', 'delivery', 'needed_on', 'address']))),
        ]);

        return redirect()->to(route('site.products') . '#order')
            ->with('site_success', 'Thank you! We have your order and will call you to confirm the price and delivery.');
    }

    public function page(string $slug)
    {
        abort_unless(W::ready(), 404);
        $page = WebsitePage::where('slug', $slug)->where('published', true)->firstOrFail();
        $siblings = $page->menu ? WebsitePage::where('menu', $page->menu)->where('published', true)->orderBy('sort')->get(['title', 'slug']) : collect();
        return view('website.page', compact('page', 'siblings'));
    }

    public function news(Request $request)
    {
        if (!W::ready()) return view('website.news', ['posts' => $this->emptyPage(9), 'categories' => collect(), 'type' => null]);
        $type = in_array($request->get('type'), ['news', 'event', 'tip'], true) ? $request->get('type') : null;
        $q = WebsitePost::live()
            ->when($type, fn ($x) => $x->where('type', $type))
            ->when($request->filled('category'), fn ($x) => $x->where('category', $request->get('category')))
            ->when($request->filled('q'), fn ($x) => $x->where(fn ($w) => $w->where('title', 'like', '%' . $request->get('q') . '%')->orWhere('excerpt', 'like', '%' . $request->get('q') . '%')));
        return view('website.news', [
            'posts' => $q->orderByDesc('published_at')->orderByDesc('id')->paginate(9)->withQueryString(),
            'categories' => WebsitePost::live()->whereNotNull('category')->distinct()->orderBy('category')->pluck('category'),
            'type' => $type,
        ]);
    }

    public function post(string $slug)
    {
        abort_unless(W::ready(), 404);
        $post = WebsitePost::live()->where('slug', $slug)->firstOrFail();
        $more = WebsitePost::live()->where('id', '!=', $post->id)->where('type', $post->type)->orderByDesc('published_at')->limit(3)->get();
        return view('website.post', compact('post', 'more'));
    }

    public function gallery()
    {
        if (!W::ready()) return view('website.gallery', ['albums' => $this->emptyPage(12)]);
        return view('website.gallery', [
            'albums' => WebsiteAlbum::where('published', true)->whereHas('photos')->withCount('photos')->with('photos')
                ->orderBy('sort')->orderByDesc('taken_on')->paginate(12),
        ]);
    }

    public function album(string $slug)
    {
        abort_unless(W::ready(), 404);
        $album = WebsiteAlbum::where('slug', $slug)->where('published', true)->with('photos')->firstOrFail();
        return view('website.album', compact('album'));
    }

    public function team()
    {
        if (!W::ready()) return view('website.team', ['people' => collect()]);
        $people = WebsitePerson::where('published', true)->orderBy('sort')->orderBy('name')->get()->groupBy('group');
        return view('website.team', compact('people'));
    }

    public function contact()
    {
        return view('website.contact');
    }

    public function sendContact(Request $request)
    {
        if (!W::ready()) return back()->withErrors(['website' => 'Online forms are not available yet. Please call or WhatsApp us.']);
        $data = $request->validate([
            'type'       => 'nullable|in:contact,visit',
            'name'       => 'required|string|max:150',
            'email'      => 'nullable|email|max:150',
            'phone'      => 'nullable|string|max:40',
            'subject'    => 'nullable|string|max:200',
            'message'    => 'required|string|max:3000',
            'visit_date' => 'nullable|date|after_or_equal:today',
            'website'    => 'nullable|max:0',
        ]);
        WebsiteMessage::create([
            'type' => $data['type'] ?? 'contact', 'name' => $data['name'], 'email' => $data['email'] ?? null, 'phone' => $data['phone'] ?? null,
            'subject' => $data['subject'] ?? (($data['type'] ?? '') === 'visit' ? 'Farm visit request' : 'Website enquiry'),
            'message' => $data['message'], 'ip' => $request->ip(),
            'data' => !empty($data['visit_date']) ? ['visit_date' => $data['visit_date']] : null,
        ]);
        return back()->with('site_success', 'Thank you for reaching out. We will get back to you soon.');
    }

    public function newsletter(Request $request)
    {
        if (!W::ready()) return back()->withErrors(['website' => 'Online forms are not available yet. Please call or WhatsApp us.']);
        $data = $request->validate(['email' => 'required|email|max:150', 'website' => 'nullable|max:0']);
        if (!WebsiteMessage::where('type', 'newsletter')->where('email', $data['email'])->exists()) {
            WebsiteMessage::create(['type' => 'newsletter', 'email' => $data['email'], 'subject' => 'Newsletter sign-up', 'ip' => $request->ip()]);
        }
        return back()->with('site_success', 'You are subscribed. Thank you!');
    }

    /** Live numbers as JSON, so the home page can refresh them without a reload. */
    public function live()
    {
        return response()->json(['stats' => W::get('show_stats', '1') === '1' ? W::stats() : [], 'at' => now()->toIso8601String()])
            ->header('Cache-Control', 'public, max-age=300');
    }

    /** Website images when public/storage is not linked (see WebsiteContent::fileUrl). */
    public function media(string $path)
    {
        abort_unless(str_starts_with($path, 'website/') && !str_contains($path, '..') && Storage::disk('public')->exists($path), 404);
        return Storage::disk('public')->response($path, null, ['Cache-Control' => 'public, max-age=2592000']);
    }

    private function emptyPage(int $per)
    {
        return new \Illuminate\Pagination\LengthAwarePaginator([], 0, $per, 1, ['path' => request()->url()]);
    }

    private function items(string $kind, ?int $limit = null)
    {
        if (!W::ready()) return collect();
        return WebsiteItem::where('kind', $kind)->where('published', true)->orderBy('sort')->when($limit, fn ($q) => $q->limit($limit))->get();
    }
}

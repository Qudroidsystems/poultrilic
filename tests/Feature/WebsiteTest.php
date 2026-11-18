<?php

use App\Models\Flock;
use App\Models\Website\WebsiteItem;
use App\Models\Website\WebsiteMessage;
use App\Services\Website\WebsiteContent as W;
use App\Support\FarmSettings;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    FarmSettings::set(['farm_name' => 'Sunrise Farm', 'farm_phone' => '0803 000 1111', 'egg_price' => 200]);
    W::flush();
});

it('shows the farm website with live numbers from the farm records and no sample content', function () {
    $flock = Flock::create(['name' => 'A', 'flock_type' => 'layer', 'initial_bird_count' => 1200, 'status' => 'active']);
    plDays($flock, now()->subDays(3)->toDateString(), [[2, 900, 2], [1, 950, 2], [0, 980, 2]]);
    W::flush();

    $res = $this->get('/')->assertOk()
        ->assertSee('Sunrise Farm')
        ->assertSee('The farm today')
        ->assertSee('Birds on the farm today')
        ->assertSee(number_format(1197))           // 1200 birds − 3 deaths
        ->assertSee('Poultry')                      // enterprise from the flocks
        ->assertDontSee('Prime Farm')->assertDontSee('+012 345 6789')->assertDontSee('3652');
    expect(W::stats())->not->toBeEmpty();
    $this->getJson(route('site.live'))->assertOk()->assertJsonStructure(['stats', 'at']);
});

it('lists products with the live egg price and takes an order', function () {
    WebsiteItem::create(['kind' => 'product', 'title' => 'Fresh table eggs', 'subtitle' => 'per crate of 30', 'price_source' => 'egg_crate', 'published' => true]);
    WebsiteItem::create(['kind' => 'product', 'title' => 'Manure', 'subtitle' => 'per bag', 'price' => '₦1,500', 'published' => true]);

    $this->get(route('site.products'))->assertOk()
        ->assertSee('₦6,000')        // 200 × 30, from Prices & Costs
        ->assertSee('₦1,500')
        ->assertSee("today's price", false);

    $this->post(route('site.order'), ['product' => 'Fresh table eggs', 'quantity' => '10 crates', 'delivery' => 'pickup', 'name' => 'Ada', 'phone' => '0803'])
        ->assertRedirect();
    $m = WebsiteMessage::first();
    expect($m->type)->toBe('order')->and($m->data['quantity'])->toBe('10 crates');

    // spam bots that fill the hidden field are refused
    $this->post(route('site.order'), ['product' => 'x', 'quantity' => '1', 'name' => 'Bot', 'phone' => '1', 'website' => 'spam'])->assertSessionHasErrors('website');
});

it('serves the other public pages and hides empty sections', function () {
    foreach (['site.news', 'site.gallery', 'site.team', 'site.contact'] as $r) {
        $this->get(route($r))->assertOk();
    }
    $this->post(route('site.contact.send'), ['name' => 'Ada', 'message' => 'Do you sell broilers?'])->assertRedirect();
    expect(WebsiteMessage::where('type', 'contact')->count())->toBe(1);
    $this->get('/')->assertDontSee('What our customers say')->assertDontSee('Frequently asked questions');
});

it('lets a website manager edit the site from the portal', function () {
    $admin = plUser(['Manage website', 'dashboard']);
    $this->actingAs($admin);
    foreach (['website.admin.index', 'website.admin.settings', 'website.admin.messages', 'website.admin.albums'] as $r) {
        $this->get(route($r))->assertOk();
    }
    $this->get(route('website.admin.list', 'products'))->assertOk();
    $this->get(route('website.admin.create', 'products'))->assertOk();
    $this->post(route('website.admin.store', 'products'), ['title' => 'Live broilers', 'subtitle' => 'per bird', 'price' => '₦7,000', 'stock_source' => 'birds', 'published' => 1])
        ->assertRedirect(route('website.admin.list', 'products'));
    $this->post(route('website.admin.store', 'pages'), ['title' => 'About the farm', 'menu' => 'about', 'body' => '<p>Hello<script>alert(1)</script></p>', 'published' => 1])->assertRedirect();
    $this->post(route('website.admin.settings.save'), ['tagline' => 'Fresh every day', 'enabled' => 1, 'show_stats' => 1, 'stats_items' => ['birds'], 'orders_open' => 1])->assertRedirect();

    auth()->logout();
    W::flush();
    $this->get('/')->assertSee('Fresh every day')->assertSee('Live broilers')->assertSee('About the farm', false);
    $this->get(route('site.page', 'about-the-farm'))->assertOk()->assertSee('Hello')->assertDontSee('<script>alert(1)</script>', false);

    $this->actingAs(plUser(['dashboard']))->get(route('website.admin.index'))->assertForbidden();
});

it('sends "/" to the sign-in page when the website is switched off', function () {
    W::put(['enabled' => '0']);
    $this->get('/')->assertRedirect(route('login'));
});

it('calls the farm Prime Farm unless another name is saved', function () {
    \App\Models\Setting::where('key', 'farm_name')->delete();
    FarmSettings::flush();
    W::flush();
    expect(FarmSettings::text('farm_name'))->toBe('Prime Farm');
    $this->get('/login')->assertSee('Prime Farm')->assertDontSee('>Poultrilic<', false);
    $this->get('/')->assertSee('Prime Farm');
});

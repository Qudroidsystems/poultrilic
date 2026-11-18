<?php

use App\Models\Customer;
use App\Models\Flock;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\User;
use App\Support\FarmSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function salesUser(array $permissions = []): User
{
    $user = User::factory()->create();
    foreach ($permissions as $p) {
        Permission::findOrCreate($p, 'web');
    }
    $user->givePermissionTo($permissions);

    return $user;
}

function allSalesPerms(): array
{
    return ['View sale', 'Create sale', 'Update sale', 'Delete sale', 'Create payment', 'Delete payment',
            'View customer', 'Create customer', 'Update customer', 'Delete customer'];
}

function makeSale(array $over = []): Sale
{
    $eggs = $over['eggs'] ?? 300;
    $price = $over['price_per_egg'] ?? 250;

    return Sale::create(array_merge([
        'invoice_number' => 'INV-2026-' . str_pad((string) (Sale::count() + 1), 4, '0', STR_PAD_LEFT),
        'sale_date' => now()->subDays(3)->toDateString(),
        'eggs' => $eggs, 'price_per_egg' => $price, 'total' => $eggs * $price,
    ], $over));
}

function pay(Sale $sale, float $amount, array $over = []): SalePayment
{
    return $sale->payments()->create(array_merge([
        'paid_on' => now()->subDay()->toDateString(), 'amount' => $amount, 'method' => 'cash',
    ], $over));
}

// ------------------------------------------------------------ model maths

it('derives paid, balance and status from payments', function () {
    $sale = makeSale();                       // 300 eggs x 250 = 75,000
    expect($sale->status)->toBe('unpaid')->and($sale->balance)->toBe(75000.0);

    pay($sale, 25000);
    $sale = $sale->fresh();
    expect($sale->status)->toBe('partial')->and($sale->paid)->toBe(25000.0)->and($sale->balance)->toBe(50000.0);

    pay($sale, 50000);
    $sale = $sale->fresh();
    expect($sale->status)->toBe('paid')->and($sale->balance)->toBe(0.0);
});

it('numbers invoices per year without gaps or clashes', function () {
    expect(Sale::nextInvoiceNumber(2026))->toBe('INV-2026-0001');

    makeSale(['invoice_number' => 'INV-2026-0001']);
    makeSale(['invoice_number' => 'INV-2026-0009']);
    makeSale(['invoice_number' => 'INV-2025-0120']);

    expect(Sale::nextInvoiceNumber(2026))->toBe('INV-2026-0010')
        ->and(Sale::nextInvoiceNumber(2025))->toBe('INV-2025-0121')
        ->and(Sale::nextInvoiceNumber(2027))->toBe('INV-2027-0001');
});

it('sums receivables across open invoices only', function () {
    $a = makeSale(['eggs' => 300, 'price_per_egg' => 250]);       // owes 75,000
    $b = makeSale(['eggs' => 100, 'price_per_egg' => 200]);       // 20,000, paid in full
    $c = makeSale(['eggs' => 60, 'price_per_egg' => 250]);        // 15,000, owes 5,000
    pay($b, 20000);
    pay($c, 10000);

    expect(Sale::receivables())->toBe(['outstanding' => 80000.0, 'invoices' => 2]);
});

// ------------------------------------------------------------ permissions

it('blocks sales pages and actions without permission', function () {
    $sale = makeSale();
    $this->actingAs(User::factory()->create());

    $this->get('/sales')->assertForbidden();
    $this->get("/sales/{$sale->id}")->assertForbidden();
    $this->get("/sales/{$sale->id}/invoice")->assertForbidden();
    $this->get('/customers')->assertForbidden();
    $this->post('/sales', [])->assertForbidden();
    $this->post("/sales/{$sale->id}/payments", [])->assertForbidden();

    $this->actingAs(salesUser(['View sale']));
    $this->get('/sales')->assertOk();
    $this->post('/sales', ['sale_date' => now()->toDateString(), 'crates' => 1])->assertForbidden();
    $this->post("/sales/{$sale->id}/payments", [])->assertForbidden();
});

// ------------------------------------------------------------ creating invoices

it('creates an invoice from crates and pieces, using the farm egg price by default', function () {
    $this->actingAs(salesUser(allSalesPerms()));
    $customer = Customer::create(['name' => 'Mama Tolu']);

    $this->post('/sales', [
        'sale_date' => now()->toDateString(), 'customer_id' => $customer->id, 'crates' => 10, 'pieces' => 5,
    ])->assertRedirect();

    $sale = Sale::firstOrFail();
    expect($sale->eggs)->toBe(305)
        ->and((float) $sale->price_per_egg)->toBe(250.0)
        ->and((float) $sale->total)->toBe(76250.0)
        ->and($sale->invoice_number)->toBe('INV-' . now()->year . '-0001')
        ->and($sale->customer_id)->toBe($customer->id);

    FarmSettings::set(['egg_price' => 300]);
    $this->post('/sales', ['sale_date' => now()->toDateString(), 'crates' => 1])->assertRedirect();
    expect((float) Sale::latest('id')->first()->total)->toBe(9000.0)
        ->and(Sale::latest('id')->first()->invoice_number)->toBe('INV-' . now()->year . '-0002');
});

it('keeps a custom price and lets walk-in sales have no customer', function () {
    $this->actingAs(salesUser(allSalesPerms()));

    $this->post('/sales', ['sale_date' => now()->toDateString(), 'pieces' => 12, 'price_per_egg' => 275.5])->assertRedirect();

    $sale = Sale::firstOrFail();
    expect($sale->customer_id)->toBeNull()->and($sale->customer_name)->toBe('Walk-in customer')
        ->and((float) $sale->total)->toBe(3306.0);
});

it('validates invoices', function (array $bad, string $field) {
    $this->actingAs(salesUser(allSalesPerms()));

    $this->post('/sales', array_merge(['sale_date' => now()->toDateString(), 'crates' => 2], $bad))->assertSessionHasErrors($field);
    expect(Sale::count())->toBe(0);
})->with([
    'future date' => [['sale_date' => '2999-01-01'], 'sale_date'],
    'no eggs' => [['crates' => 0, 'pieces' => 0], 'eggs'],
    'pieces over a crate' => [['pieces' => 30], 'pieces'],
    'negative crates' => [['crates' => -1], 'crates'],
    'zero price' => [['price_per_egg' => 0], 'price_per_egg'],
    'unknown customer' => [['customer_id' => 999], 'customer_id'],
]);

// ------------------------------------------------------------ editing and deleting

it('recalculates the total on edit but never renumbers, and refuses to go below what was paid', function () {
    $this->actingAs(salesUser(allSalesPerms()));
    $sale = makeSale(['invoice_number' => 'INV-2026-0042']);          // 75,000
    pay($sale, 60000);

    $this->put("/sales/{$sale->id}", ['sale_date' => $sale->sale_date->toDateString(), 'crates' => 5, 'price_per_egg' => 250])
        ->assertSessionHasErrors('eggs');                              // 150 x 250 = 37,500 < 60,000 paid
    expect((float) $sale->fresh()->total)->toBe(75000.0);

    $this->put("/sales/{$sale->id}", ['sale_date' => $sale->sale_date->toDateString(), 'crates' => 12, 'price_per_egg' => 250])
        ->assertRedirect();
    expect((float) $sale->fresh()->total)->toBe(90000.0)->and($sale->fresh()->invoice_number)->toBe('INV-2026-0042');
});

it('deletes an invoice only when it has no payments', function () {
    $this->actingAs(salesUser(allSalesPerms()));
    $sale = makeSale();
    pay($sale, 100);

    $this->delete("/sales/{$sale->id}")->assertSessionHasErrors('sale');
    expect(Sale::count())->toBe(1);

    $sale->payments()->delete();
    $this->delete("/sales/{$sale->id}")->assertRedirect(route('sales.index'));
    expect(Sale::count())->toBe(0);
});

// ------------------------------------------------------------ payments

it('records payments and rejects overpayment, bad dates and bad methods', function () {
    $this->actingAs(salesUser(allSalesPerms()));
    $sale = makeSale();                                              // 75,000

    $this->post("/sales/{$sale->id}/payments", ['paid_on' => now()->toDateString(), 'amount' => 30000, 'method' => 'transfer', 'reference' => 'TRF123'])
        ->assertRedirect()->assertSessionHas('success');
    expect($sale->fresh()->balance)->toBe(45000.0)->and(SalePayment::first()->reference)->toBe('TRF123');

    $this->post("/sales/{$sale->id}/payments", ['paid_on' => now()->toDateString(), 'amount' => 45000.01, 'method' => 'cash'])
        ->assertSessionHasErrors('amount');
    $this->post("/sales/{$sale->id}/payments", ['paid_on' => now()->addDay()->toDateString(), 'amount' => 10, 'method' => 'cash'])
        ->assertSessionHasErrors('paid_on');
    $this->post("/sales/{$sale->id}/payments", ['paid_on' => now()->subDays(30)->toDateString(), 'amount' => 10, 'method' => 'cash'])
        ->assertSessionHasErrors('paid_on');                          // before the invoice date
    $this->post("/sales/{$sale->id}/payments", ['paid_on' => now()->toDateString(), 'amount' => 10, 'method' => 'bitcoin'])
        ->assertSessionHasErrors('method');
    $this->post("/sales/{$sale->id}/payments", ['paid_on' => now()->toDateString(), 'amount' => 0, 'method' => 'cash'])
        ->assertSessionHasErrors('amount');

    expect(SalePayment::count())->toBe(1);

    $this->post("/sales/{$sale->id}/payments", ['paid_on' => now()->toDateString(), 'amount' => 45000, 'method' => 'cash'])->assertRedirect();
    expect($sale->fresh()->status)->toBe('paid');
    $this->post("/sales/{$sale->id}/payments", ['paid_on' => now()->toDateString(), 'amount' => 1, 'method' => 'cash'])
        ->assertSessionHasErrors('amount');                           // nothing left to pay
});

it('deletes a payment only through its own invoice', function () {
    $this->actingAs(salesUser(allSalesPerms()));
    $a = makeSale();
    $b = makeSale();
    $p = pay($a, 500);

    $this->delete("/sales/{$b->id}/payments/{$p->id}")->assertNotFound();
    expect(SalePayment::count())->toBe(1);

    $this->delete("/sales/{$a->id}/payments/{$p->id}")->assertRedirect();
    expect(SalePayment::count())->toBe(0);
});

// ------------------------------------------------------------ listing

it('filters by status and summarises the whole filtered set', function () {
    $this->actingAs(salesUser(['View sale']));
    $paid = makeSale(['eggs' => 100, 'price_per_egg' => 100]);        // 10,000
    $part = makeSale(['eggs' => 100, 'price_per_egg' => 200]);        // 20,000
    $none = makeSale(['eggs' => 100, 'price_per_egg' => 300]);        // 30,000
    pay($paid, 10000);
    pay($part, 5000);

    $this->get('/sales')->assertOk()
        ->assertViewHas('invoiced', 60000.0)->assertViewHas('collected', 15000.0)->assertViewHas('outstanding', 45000.0);
    $this->get('/sales?status=paid')->assertViewHas('invoiced', 10000.0);
    $this->get('/sales?status=partial')->assertViewHas('invoiced', 20000.0)->assertViewHas('collected', 5000.0);
    $this->get('/sales?status=unpaid')->assertViewHas('invoiced', 30000.0);
    $this->get('/sales?status=open')->assertViewHas('invoiced', 50000.0)->assertViewHas('outstanding', 45000.0);
    $this->get('/sales?status=nonsense')->assertSessionHasErrors('status');
});

it('filters by customer and date', function () {
    $this->actingAs(salesUser(['View sale']));
    $ada = Customer::create(['name' => 'Ada']);
    makeSale(['customer_id' => $ada->id, 'eggs' => 100, 'price_per_egg' => 100]);
    makeSale(['eggs' => 100, 'price_per_egg' => 100]);
    makeSale(['sale_date' => now()->subDays(90)->toDateString(), 'customer_id' => $ada->id, 'eggs' => 100, 'price_per_egg' => 100]);

    $this->get('/sales')->assertViewHas('invoiced', 20000.0);                              // last 30 days
    $this->get("/sales?customer_id={$ada->id}")->assertViewHas('invoiced', 10000.0);
    $this->get("/sales?customer_id={$ada->id}&from=2000-01-01")->assertViewHas('invoiced', 20000.0);
});

// ------------------------------------------------------------ PDFs

it('streams a PDF invoice and receipt', function () {
    FarmSettings::set(['farm_name' => 'Tolu Farms', 'invoice_footer' => 'Thanks!']);
    $this->actingAs(salesUser(['View sale']));
    $sale = makeSale(['customer_id' => Customer::create(['name' => 'Ada'])->id]);
    $p = pay($sale, 1000);

    $inv = $this->get("/sales/{$sale->id}/invoice")->assertOk();
    expect($inv->headers->get('content-type'))->toContain('application/pdf')
        ->and(substr($inv->getContent(), 0, 4))->toBe('%PDF');

    $rcpt = $this->get("/sales/{$sale->id}/payments/{$p->id}/receipt")->assertOk();
    expect(substr($rcpt->getContent(), 0, 4))->toBe('%PDF');

    // a receipt only opens under its own invoice
    $other = makeSale();
    $this->get("/sales/{$other->id}/payments/{$p->id}/receipt")->assertNotFound();
});

it('renders the invoice and receipt templates with the business details', function () {
    FarmSettings::set(['farm_name' => 'Tolu Farms', 'farm_address' => '12 Farm Rd', 'invoice_footer' => 'Thanks!']);
    $sale = makeSale(['customer_id' => Customer::create(['name' => 'Ada'])->id]);
    $p = pay($sale, 1000);
    $sale->load(['customer', 'payments']);
    $business = ['name' => FarmSettings::text('farm_name'), 'address' => FarmSettings::text('farm_address'), 'phone' => '', 'footer' => FarmSettings::text('invoice_footer')];

    $html = view('pdf.invoice', compact('sale', 'business'))->render();
    expect($html)->toContain('Tolu Farms')->toContain('12 Farm Rd')->toContain($sale->invoice_number)
        ->toContain('74,000.00')->toContain('PART PAID')->toContain('Thanks!');

    $html = view('pdf.receipt', compact('sale', 'business') + ['payment' => $p])->render();
    expect($html)->toContain($p->receipt_number)->toContain('1,000.00')->toContain('74,000.00');
});

it('falls back to the farm name in config when no business name is set', function () {
    \App\Models\Setting::where('key', 'farm_name')->delete();   // the upgrade saves "Prime Farm"; test the fallback
    FarmSettings::flush();
    expect(FarmSettings::text('farm_name'))->toBe('Prime Farm');
    config(['poultrilic.farm_name' => 'Another Farm']);
    FarmSettings::flush();
    expect(FarmSettings::text('farm_name'))->toBe('Another Farm');
});

// ------------------------------------------------------------ customers

it('manages customers and protects those with invoices', function () {
    $this->actingAs(salesUser(allSalesPerms()));

    $this->post('/customers', ['name' => 'Ada', 'phone' => '0803', 'email' => 'ada@example.com'])->assertRedirect();
    $c = Customer::firstOrFail();
    $this->put("/customers/{$c->id}", ['name' => 'Ada O.'])->assertRedirect();
    expect($c->fresh()->name)->toBe('Ada O.');

    $this->post('/customers', ['name' => ''])->assertSessionHasErrors('name');
    $this->post('/customers', ['name' => 'X', 'email' => 'nope'])->assertSessionHasErrors('email');

    makeSale(['customer_id' => $c->id]);
    $this->delete("/customers/{$c->id}")->assertSessionHasErrors('customer');
    expect(Customer::count())->toBe(1);

    Sale::query()->delete();
    $this->delete("/customers/{$c->id}")->assertRedirect();
    expect(Customer::count())->toBe(0);
});

it('lists what each customer owes', function () {
    $this->actingAs(salesUser(['View customer']));
    $ada = Customer::create(['name' => 'Ada']);
    $s = makeSale(['customer_id' => $ada->id]);                       // 75,000
    pay($s, 15000);

    $this->get('/customers')->assertOk()->assertSee('60,000.00');
});

// ------------------------------------------------------------ settings + dashboard + migration

it('saves business details from the settings page', function () {
    $this->actingAs(salesUser(['View settings', 'Update settings']));

    $this->put('/settings', [
        'egg_price' => 250, 'feed_cost_per_bag' => 15000, 'bag_weight_kg' => 50, 'feed_reorder_days' => 7,
        'bird_cost' => 2000, 'drug_cost_per_day' => 5000, 'daily_labor_cost' => 10000,
        'farm_name' => 'Tolu Farms', 'farm_address' => '12 Farm Rd', 'farm_phone' => '0803 000', 'invoice_footer' => '',
    ])->assertRedirect();

    expect(FarmSettings::text('farm_name'))->toBe('Tolu Farms')
        ->and(FarmSettings::text('farm_phone'))->toBe('0803 000')
        ->and(FarmSettings::text('invoice_footer'))->toBe('Thank you for your patronage.');   // blank → default

    $this->get('/settings')->assertOk()->assertSee('Tolu Farms');
});

it('shows what customers owe on the dashboard', function () {
    Flock::create(['name' => 'Placeholder', 'initial_bird_count' => 10, 'status' => 'active']);
    $this->actingAs(salesUser(['dashboard', 'View sale']));

    $this->get('/dashboard')->assertOk()->assertDontSee('Customers owe');

    $s = makeSale();
    pay($s, 5000);
    $this->get('/dashboard')->assertOk()->assertSee('Customers owe')->assertSee('70,000.00')
        ->assertViewHas('receivables', ['outstanding' => 70000.0, 'invoices' => 1]);
});

it('grants the sales permissions to Super Admin and dashboard roles only', function () {
    $admin = Role::create(['name' => 'Super Admin']);
    $staff = Role::create(['name' => 'Staff']);
    $staff->givePermissionTo(Permission::create(['name' => 'View flock']));

    (require database_path('migrations/2026_11_05_140100_add_sales_permissions.php'))->up();
    (require database_path('migrations/2026_11_05_140100_add_sales_permissions.php'))->up();

    expect($admin->fresh()->hasPermissionTo('Create payment'))->toBeTrue()
        ->and($admin->fresh()->hasPermissionTo('View customer'))->toBeTrue()
        ->and($staff->fresh()->hasPermissionTo('View sale'))->toBeFalse()
        ->and(Permission::where('name', 'View sale')->count())->toBe(1);
});

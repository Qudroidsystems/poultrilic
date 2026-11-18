<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Flock;
use App\Models\LivestockType;
use App\Models\LivestockUnit;
use App\Models\ReportDefinition;
use App\Models\Sale;
use Illuminate\Http\Request;

/** Spotlight (⌘K): flocks, livestock, customers, invoices and reports the user can open. */
class SearchController extends Controller
{
    public function __invoke(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['results' => []]);
        }
        $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $q) . '%';
        $u = $request->user();
        $out = [];

        if ($u->can('View flock')) {
            foreach (Flock::where(fn ($w) => $w->where('name', 'like', $like)->orWhere('breed', 'like', $like)->orWhere('house', 'like', $like))
                ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")->limit(6)->get() as $f) {
                $out[] = ['group' => 'Flocks', 'title' => $f->display_name, 'icon' => 'ri-home-4-line',
                    'sub' => $f->type_label . ' · ' . number_format($f->current_bird_count) . ' birds · ' . ucfirst($f->status),
                    'url' => route('poultry.flocks.show', $f)];
            }
        }

        if ($u->can('View livestock')) {
            foreach (LivestockUnit::with('type')->where('name', 'like', $like)->limit(6)->get() as $unit) {
                if (! $unit->type) {
                    continue;
                }
                $out[] = ['group' => 'Livestock', 'title' => $unit->name, 'icon' => $unit->type->icon,
                    'sub' => $unit->type->name . ' · ' . number_format($unit->current_count) . ' ' . strtolower($unit->type->count_label),
                    'url' => route('livestock.units.show', [$unit->type, $unit])];
            }
            foreach (LivestockType::where('active', true)->where('name', 'like', $like)->limit(3)->get() as $t) {
                $out[] = ['group' => 'Livestock', 'title' => $t->name, 'icon' => $t->icon, 'sub' => 'All ' . strtolower(\Illuminate\Support\Str::plural($t->unit_label)), 'url' => route('livestock.index', $t)];
            }
        }

        if ($u->can('View customer')) {
            foreach (Customer::where(fn ($w) => $w->where('name', 'like', $like)->orWhere('phone', 'like', $like))->limit(5)->get() as $c) {
                $out[] = ['group' => 'Customers', 'title' => $c->name, 'icon' => 'ri-user-3-line', 'sub' => $c->phone, 'url' => route('customers.index', ['search' => $c->name])];
            }
        }

        if ($u->can('View sale')) {
            foreach (Sale::with('customer')->where('invoice_number', 'like', $like)->latest('sale_date')->limit(5)->get() as $s) {
                $out[] = ['group' => 'Invoices', 'title' => $s->invoice_number, 'icon' => 'ri-receipt-line',
                    'sub' => trim(($s->customer?->name ?? '') . ' · ₦' . number_format((float) $s->total, 2), ' ·'), 'url' => route('sales.show', $s)];
            }
        }

        if ($u->can('View reports')) {
            foreach (ReportDefinition::where('name', 'like', $like)->limit(5)->get() as $r) {
                $out[] = ['group' => 'Reports', 'title' => $r->name, 'icon' => 'ri-bar-chart-2-line', 'sub' => $r->description, 'url' => route('reports.show', $r)];
            }
        }

        return response()->json(['results' => $out]);
    }
}

<?php

namespace App\Support;

use App\Models\LivestockType;
use Illuminate\Support\Facades\DB;

/**
 * Ready-made record forms the admin can install with one click and then change freely.
 * Field: [key, label, kind, unit, aggregate, headcount, options?]
 */
class LivestockTemplates
{
    public const TEMPLATES = [
        'cattle' => [
            'type' => ['name' => 'Cattle', 'unit_label' => 'Herd', 'count_label' => 'Cattle', 'icon' => 'ri-bear-smile-line', 'color' => '#eb6834',
                'description' => 'Beef or dairy herds: births, deaths, sales, milk, feed and treatments.'],
            'fields' => [
                ['births', 'Births (calves)', 'number', null, 'sum', 'add'],
                ['purchased', 'Bought in', 'number', null, 'sum', 'add'],
                ['deaths', 'Deaths', 'number', null, 'sum', 'subtract'],
                ['sold', 'Sold', 'number', null, 'sum', 'subtract'],
                ['milk_litres', 'Milk', 'decimal', 'litres', 'sum', 'none'],
                ['feed_kg', 'Feed / supplement', 'decimal', 'kg', 'sum', 'none'],
                ['avg_weight_kg', 'Average weight (sample)', 'decimal', 'kg', 'avg', 'none'],
                ['sales_amount', 'Sales value', 'money', '₦', 'sum', 'none'],
                ['treatment', 'Treatment / vaccine', 'text', null, 'none', 'none'],
            ],
        ],
        'goats' => [
            'type' => ['name' => 'Goats & sheep', 'unit_label' => 'Pen', 'count_label' => 'Animals', 'icon' => 'ri-seedling-line', 'color' => '#6250d6',
                'description' => 'Small ruminants: kidding/lambing, deaths, sales, weights and health.'],
            'fields' => [
                ['births', 'Kids / lambs born', 'number', null, 'sum', 'add'],
                ['purchased', 'Bought in', 'number', null, 'sum', 'add'],
                ['deaths', 'Deaths', 'number', null, 'sum', 'subtract'],
                ['sold', 'Sold / slaughtered', 'number', null, 'sum', 'subtract'],
                ['feed_kg', 'Feed / supplement', 'decimal', 'kg', 'sum', 'none'],
                ['avg_weight_kg', 'Average weight (sample)', 'decimal', 'kg', 'avg', 'none'],
                ['sales_amount', 'Sales value', 'money', '₦', 'sum', 'none'],
                ['treatment', 'Treatment / deworming', 'text', null, 'none', 'none'],
            ],
        ],
        'fish' => [
            'type' => ['name' => 'Fish ponds', 'unit_label' => 'Pond', 'count_label' => 'Fish', 'icon' => 'ri-drop-line', 'color' => '#2a78d6',
                'description' => 'Catfish / tilapia ponds: stocking, mortality, feed, water quality, harvest.'],
            'fields' => [
                ['stocked', 'Fingerlings stocked', 'number', null, 'sum', 'add'],
                ['deaths', 'Dead fish removed', 'number', null, 'sum', 'subtract'],
                ['harvested', 'Fish harvested', 'number', null, 'sum', 'subtract'],
                ['harvest_kg', 'Harvest weight', 'decimal', 'kg', 'sum', 'none'],
                ['feed_kg', 'Feed', 'decimal', 'kg', 'sum', 'none'],
                ['feed_size', 'Feed size', 'select', 'mm', 'none', 'none', ['0.5', '1.0', '1.5', '2.0', '3.0', '4.5', '6.0', '9.0']],
                ['avg_weight_g', 'Average fish weight (sample)', 'decimal', 'g', 'avg', 'none'],
                ['water_changed', 'Water changed', 'yes_no', null, 'count', 'none'],
                ['ph', 'Water pH', 'decimal', null, 'avg', 'none'],
                ['temp_c', 'Water temperature', 'decimal', '°C', 'avg', 'none'],
                ['sales_amount', 'Sales value', 'money', '₦', 'sum', 'none'],
            ],
        ],
        'pigs' => [
            'type' => ['name' => 'Pigs', 'unit_label' => 'Pen', 'count_label' => 'Pigs', 'icon' => 'ri-home-heart-line', 'color' => '#e87ba4',
                'description' => 'Piggery: farrowing, weaning, deaths, sales, feed and weights.'],
            'fields' => [
                ['born_alive', 'Piglets born alive', 'number', null, 'sum', 'add'],
                ['stillborn', 'Stillborn', 'number', null, 'sum', 'none'],
                ['purchased', 'Bought in', 'number', null, 'sum', 'add'],
                ['deaths', 'Deaths', 'number', null, 'sum', 'subtract'],
                ['sold', 'Sold / slaughtered', 'number', null, 'sum', 'subtract'],
                ['feed_kg', 'Feed', 'decimal', 'kg', 'sum', 'none'],
                ['avg_weight_kg', 'Average weight (sample)', 'decimal', 'kg', 'avg', 'none'],
                ['sales_amount', 'Sales value', 'money', '₦', 'sum', 'none'],
                ['treatment', 'Treatment / vaccine', 'text', null, 'none', 'none'],
            ],
        ],
        'crops' => [
            'type' => ['name' => 'Crops & produce', 'unit_label' => 'Plot', 'count_label' => 'Stands', 'icon' => 'ri-plant-line', 'color' => '#008300',
                'description' => 'Plots or fields: inputs, labour, rainfall and harvest.'],
            'fields' => [
                ['activity', 'Activity', 'select', null, 'none', 'none', ['Land preparation', 'Planting', 'Weeding', 'Fertiliser', 'Spraying', 'Irrigation', 'Harvest', 'Other']],
                ['labour_days', 'Labour', 'decimal', 'man-days', 'sum', 'none'],
                ['input_cost', 'Inputs cost', 'money', '₦', 'sum', 'none'],
                ['rain_mm', 'Rainfall', 'decimal', 'mm', 'sum', 'none'],
                ['harvest_kg', 'Harvest', 'decimal', 'kg', 'sum', 'none'],
                ['sales_amount', 'Sales value', 'money', '₦', 'sum', 'none'],
            ],
        ],
    ];

    public static function install(string $key): LivestockType
    {
        $t = self::TEMPLATES[$key] ?? throw new \InvalidArgumentException("Unknown template {$key}");

        return DB::transaction(function () use ($t) {
            $type = LivestockType::create($t['type'] + ['active' => true, 'sort' => LivestockType::max('sort') + 1]);
            foreach ($t['fields'] as $i => $f) {
                $type->fields()->create([
                    'key' => $f[0], 'label' => $f[1], 'kind' => $f[2], 'unit' => $f[3], 'aggregate' => $f[4],
                    'headcount' => $f[5], 'options' => $f[6] ?? null, 'sort' => $i + 1,
                ]);
            }

            return $type;
        });
    }
}

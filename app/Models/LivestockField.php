<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LivestockField extends Model
{
    public const KINDS = [
        'number' => 'Whole number', 'decimal' => 'Decimal number', 'money' => 'Amount (₦)',
        'text' => 'Text', 'date' => 'Date', 'select' => 'Choice from a list', 'yes_no' => 'Yes / No',
    ];

    public const AGGREGATES = [
        'sum' => 'Add up (total)', 'avg' => 'Average', 'last' => 'Latest value', 'max' => 'Highest', 'min' => 'Lowest',
        'count' => 'Count of days filled in', 'none' => 'Do not total',
    ];

    public const HEADCOUNT = [
        'none' => 'No effect', 'add' => 'Adds to the head count (births, purchases, stocking)',
        'subtract' => 'Takes from the head count (deaths, sales, harvest)', 'set' => 'Is the head count (a physical count)',
    ];

    protected $fillable = ['livestock_type_id', 'key', 'label', 'kind', 'unit', 'options', 'aggregate', 'headcount', 'required', 'help', 'sort'];

    protected $casts = ['options' => 'array', 'required' => 'boolean', 'sort' => 'integer'];

    public function type()
    {
        return $this->belongsTo(LivestockType::class, 'livestock_type_id');
    }

    public function isNumeric(): bool
    {
        return in_array($this->kind, ['number', 'decimal', 'money'], true);
    }

    public function displayLabel(): string
    {
        return $this->unit ? "{$this->label} ({$this->unit})" : $this->label;
    }

    /** Validation rule for one value. */
    public function rule(): array
    {
        $r = [$this->required ? 'required' : 'nullable'];

        return array_merge($r, match ($this->kind) {
            'number' => ['integer', 'min:0'],
            'decimal', 'money' => ['numeric', 'min:0'],
            'date' => ['date'],
            'select' => ['string', 'in:' . implode(',', array_map(fn ($o) => str_replace(',', '\,', $o), $this->options ?? []))],
            'yes_no' => ['boolean'],
            default => ['string', 'max:1000'],
        });
    }

    /** Cast a raw value for storage. */
    public function cast(mixed $v): mixed
    {
        if ($v === null || $v === '') {
            return null;
        }

        return match ($this->kind) {
            'number' => (int) round((float) str_replace(',', '', (string) $v)),
            'decimal', 'money' => round((float) str_replace(',', '', (string) $v), 2),
            'yes_no' => in_array(strtolower((string) $v), ['1', 'yes', 'y', 'true', 'on'], true),
            default => trim((string) $v),
        };
    }

    public function format(mixed $v): string
    {
        if ($v === null || $v === '') {
            return '–';
        }

        return match ($this->kind) {
            'number' => number_format((float) $v),
            'decimal' => rtrim(rtrim(number_format((float) $v, 2), '0'), '.'),
            'money' => '₦' . number_format((float) $v, 2),
            'yes_no' => $v ? 'Yes' : 'No',
            default => (string) $v,
        };
    }
}

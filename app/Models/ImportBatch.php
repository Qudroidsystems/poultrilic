<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportBatch extends Model
{
    protected $fillable = ['kind', 'target_id', 'filename', 'stored_path', 'status', 'summary', 'user_id'];

    protected $casts = ['summary' => 'array'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getTargetLabelAttribute(): string
    {
        if ($this->kind === 'livestock') {
            return LivestockType::whereKey($this->target_id)->value('name') ?? 'Livestock';
        }

        return 'Poultry';
    }
}

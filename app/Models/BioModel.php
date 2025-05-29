<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BioModel extends Model
{
    use HasFactory;
    protected $table = "staff_bio";

    protected $fillable = [
        'user_id',
        'firstname',
        'lastname',
        'othernames',
        'phone',
        'address',
        'gender',
        'maritalstatus',
        'nationality',
        'dob',
    ];



    /**
     * Get the user that owns the StaffBioModel
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function staffId(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

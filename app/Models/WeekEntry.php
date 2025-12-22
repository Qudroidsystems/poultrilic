<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WeekEntry extends Model
{
    use HasFactory;

    protected $fillable = ['flock_id', 'week_name', 'week_number'];

    public function flock()
    {
        return $this->belongsTo(Flock::class);
    }

    public function dailyEntries()
    {
        return $this->hasMany(DailyEntry::class);
    }

    // Dynamic accessor for total_egg_in_farm
    public function getTotalEggInFarmAttribute()
    {
        return $this->dailyEntries()->sum('outstanding_egg');
    }
    
    // Calculate total feed consumed for the week
    public function getTotalFeedConsumedAttribute()
    {
        return $this->dailyEntries()->sum('daily_feeds');
    }
    
    // Calculate total eggs produced for the week
    public function getTotalEggsProducedAttribute()
    {
        $total = 0;
        foreach ($this->dailyEntries as $entry) {
            $eggData = $this->parseEggString($entry->daily_egg_production);
            $total += $eggData['total_pieces'];
        }
        return $total;
    }
    
    // Helper method to parse egg strings
    private function parseEggString($eggString)
    {
        if (empty($eggString) || $eggString === '0 Cr 0PC') {
            return ['crates' => 0, 'pieces' => 0, 'total_pieces' => 0];
        }
        
        try {
            if (strpos($eggString, ',') !== false) {
                preg_match('/([\d,]+)\s*CR?/', $eggString, $matches);
                if (count($matches) === 2) {
                    $crates = (int)str_replace(',', '', $matches[1]);
                    return [
                        'crates' => $crates,
                        'pieces' => 0,
                        'total_pieces' => $crates * 30
                    ];
                }
            } else {
                preg_match('/(\d+)\s*Cr\s*(\d+)PC/', $eggString, $matches);
                if (count($matches) === 3) {
                    $crates = (int)$matches[1];
                    $pieces = (int)$matches[2];
                    return [
                        'crates' => $crates,
                        'pieces' => $pieces,
                        'total_pieces' => ($crates * 30) + $pieces
                    ];
                }
                
                preg_match('/(\d+)\s*CR/', $eggString, $matches);
                if (count($matches) === 2) {
                    $crates = (int)$matches[1];
                    return [
                        'crates' => $crates,
                        'pieces' => 0,
                        'total_pieces' => $crates * 30
                    ];
                }
            }
            
            return ['crates' => 0, 'pieces' => 0, 'total_pieces' => 0];
        } catch (\Exception $e) {
            return ['crates' => 0, 'pieces' => 0, 'total_pieces' => 0];
        }
    }
}
<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Flock;
use App\Models\WeekEntry;
use App\Models\DailyEntry;

class FlockAnalyticsService
{
    const EGGS_PER_CRATE = 30;
    const BAG_WEIGHT_KG = 50;
    const EGG_PRICE_NAIRA = 100; // ₦100 per egg
    
    /**
     * Calculate metrics for single or multiple flocks
     */
    public static function calculateMetrics($flockId = null, $startDate = null, $endDate = null)
    {
        if ($flockId) {
            return self::calculateSingleFlockMetrics($flockId, $startDate, $endDate);
        }
        
        return self::calculateAllFlockMetrics($startDate, $endDate);
    }
    
    /**
     * Calculate metrics for a single flock
     */
    public static function calculateSingleFlockMetrics($flockId, $startDate = null, $endDate = null)
    {
        $flock = Flock::find($flockId);
        
        if (!$flock) {
            return self::emptyMetrics();
        }
        
        // Get daily entries for date range
        $dailyEntries = self::getDailyEntries($flockId, $startDate, $endDate);
        
        // Calculate basic metrics
        $totalBirds = $flock->initial_bird_count;
        $currentBirds = $flock->current_bird_count;
        $totalMortality = $flock->calculateMortality();
        $mortalityRate = $flock->mortality_rate;
        
        // Calculate production metrics
        $productionData = self::calculateProductionMetrics($dailyEntries);
        $feedData = self::calculateFeedMetrics($dailyEntries);
        $revenueData = self::calculateRevenueMetrics($dailyEntries);
        
        return array_merge(
            compact('totalBirds', 'currentBirds', 'totalMortality', 'mortalityRate'),
            $productionData,
            $feedData,
            $revenueData,
            [
                'daily_entries_count' => $dailyEntries->count(),
                'flock' => $flock
            ]
        );
    }
    
    /**
     * Calculate metrics for all flocks
     */
    public static function calculateAllFlockMetrics($startDate = null, $endDate = null)
    {
        $flocks = Flock::active()->get();
        
        if ($flocks->isEmpty()) {
            return self::emptyMetrics();
        }
        
        $totalBirds = 0;
        $currentBirds = 0;
        $totalMortality = 0;
        
        // Get all daily entries for date range
        $dailyEntries = self::getDailyEntries(null, $startDate, $endDate);
        
        foreach ($flocks as $flock) {
            $totalBirds += $flock->initial_bird_count;
            $currentBirds += $flock->current_bird_count;
            $totalMortality += $flock->calculateMortality();
        }
        
        $mortalityRate = $totalBirds > 0 ? ($totalMortality / $totalBirds) * 100 : 0;
        
        // Calculate production metrics
        $productionData = self::calculateProductionMetrics($dailyEntries);
        $feedData = self::calculateFeedMetrics($dailyEntries);
        $revenueData = self::calculateRevenueMetrics($dailyEntries);
        
        return array_merge(
            compact('totalBirds', 'currentBirds', 'totalMortality', 'mortalityRate'),
            $productionData,
            $feedData,
            $revenueData,
            [
                'daily_entries_count' => $dailyEntries->count(),
                'flock_count' => $flocks->count()
            ]
        );
    }
    
    /**
     * Calculate production metrics from daily entries
     */
    private static function calculateProductionMetrics($dailyEntries)
    {
        $totalEggPieces = 0;
        $totalSoldPieces = 0;
        $totalBrokenEggs = 0;
        
        foreach ($dailyEntries as $entry) {
            $eggData = self::parseEggData($entry->daily_egg_production);
            $soldData = self::parseEggData($entry->daily_sold_egg);
            
            $totalEggPieces += $eggData['total_pieces'];
            $totalSoldPieces += $soldData['total_pieces'];
            $totalBrokenEggs += $entry->broken_egg ?? 0;
        }
        
        $cratesProduced = floor($totalEggPieces / self::EGGS_PER_CRATE);
        $piecesProduced = $totalEggPieces % self::EGGS_PER_CRATE;
        
        $cratesSold = floor($totalSoldPieces / self::EGGS_PER_CRATE);
        $piecesSold = $totalSoldPieces % self::EGGS_PER_CRATE;
        
        return [
            'total_egg_pieces' => $totalEggPieces,
            'total_egg_crates' => $cratesProduced,
            'total_egg_pieces_remainder' => $piecesProduced,
            'total_sold_pieces' => $totalSoldPieces,
            'total_sold_crates' => $cratesSold,
            'total_sold_pieces_remainder' => $piecesSold,
            'total_broken_eggs' => $totalBrokenEggs,
            'egg_production_string' => "{$cratesProduced} Cr {$piecesProduced}PC",
            'egg_sales_string' => "{$cratesSold} Cr {$piecesSold}PC",
        ];
    }
    
    /**
     * Calculate feed metrics
     */
    private static function calculateFeedMetrics($dailyEntries)
    {
        $totalFeedBags = $dailyEntries->sum('daily_feeds');
        $totalFeedKg = $totalFeedBags * self::BAG_WEIGHT_KG;
        
        return [
            'total_feed_bags' => $totalFeedBags,
            'total_feed_kg' => $totalFeedKg,
        ];
    }
    
    /**
     * Calculate revenue metrics in Naira
     */
    private static function calculateRevenueMetrics($dailyEntries)
    {
        $totalSoldPieces = 0;
        foreach ($dailyEntries as $entry) {
            $soldData = self::parseEggData($entry->daily_sold_egg);
            $totalSoldPieces += $soldData['total_pieces'];
        }
        
        $revenue = $totalSoldPieces * self::EGG_PRICE_NAIRA;
        
        return [
            'total_revenue' => $revenue,
            'revenue_per_egg' => self::EGG_PRICE_NAIRA,
        ];
    }
    
    /**
     * Parse egg data string
     */
    public static function parseEggData($eggString)
    {
        if (empty($eggString) || $eggString === '0 Cr 0PC' || $eggString === '0 Cr 0PC') {
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
                        'total_pieces' => $crates * self::EGGS_PER_CRATE
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
                        'total_pieces' => ($crates * self::EGGS_PER_CRATE) + $pieces
                    ];
                }
                
                preg_match('/(\d+)\s*CR/', $eggString, $matches);
                if (count($matches) === 2) {
                    $crates = (int)$matches[1];
                    return [
                        'crates' => $crates,
                        'pieces' => 0,
                        'total_pieces' => $crates * self::EGGS_PER_CRATE
                    ];
                }
            }
            
            return ['crates' => 0, 'pieces' => 0, 'total_pieces' => 0];
        } catch (\Exception $e) {
            return ['crates' => 0, 'pieces' => 0, 'total_pieces' => 0];
        }
    }
    
    /**
     * Get daily entries for a flock or all flocks
     */
    private static function getDailyEntries($flockId = null, $startDate = null, $endDate = null)
    {
        $query = DailyEntry::with('weekEntry.flock');
        
        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }
        
        if ($flockId) {
            $query->whereHas('weekEntry', function($q) use ($flockId) {
                $q->where('flock_id', $flockId);
            });
        }
        
        return $query->get();
    }
    
    /**
     * Return empty metrics array
     */
    private static function emptyMetrics()
    {
        return [
            'totalBirds' => 0,
            'currentBirds' => 0,
            'totalMortality' => 0,
            'mortalityRate' => 0,
            'total_egg_pieces' => 0,
            'total_egg_crates' => 0,
            'total_egg_pieces_remainder' => 0,
            'total_sold_pieces' => 0,
            'total_sold_crates' => 0,
            'total_sold_pieces_remainder' => 0,
            'total_broken_eggs' => 0,
            'total_feed_bags' => 0,
            'total_feed_kg' => 0,
            'total_revenue' => 0,
            'daily_entries_count' => 0,
        ];
    }
    
    /**
     * Get flock summary for dashboard
     */
    public static function getFlockSummary($flockId = null, $startDate = null, $endDate = null)
    {
        $metrics = self::calculateMetrics($flockId, $startDate, $endDate);
        
        return [
            'total_birds' => $metrics['totalBirds'],
            'current_birds' => $metrics['currentBirds'],
            'total_mortality' => $metrics['totalMortality'],
            'mortality_rate' => number_format($metrics['mortalityRate'], 1) . '%',
            'survival_rate' => number_format(100 - $metrics['mortalityRate'], 1) . '%',
            'total_eggs_produced' => $metrics['total_egg_pieces'],
            'total_eggs_sold' => $metrics['total_sold_pieces'],
            'total_feed_consumed' => $metrics['total_feed_bags'] . ' bags',
            'total_revenue' => '₦' . number_format($metrics['total_revenue'], 2),
            'egg_production_string' => $metrics['egg_production_string'],
            'egg_sales_string' => $metrics['egg_sales_string'],
        ];
    }
    
    /**
     * Format currency in Naira
     */
    public static function formatNaira($amount)
    {
        return '₦' . number_format($amount, 2);
    }
    
    /**
     * Calculate production rate from daily entries
     */
    public static function calculateProductionRate($dailyEntries, $currentBirds)
    {
        if ($currentBirds <= 0 || $dailyEntries->count() === 0) {
            return 0;
        }

        // Filter out entries with 0 or negative birds and unrealistic production
        $validEntries = $dailyEntries->filter(function($entry) {
            if ($entry->current_birds <= 0) {
                return false;
            }
            
            $eggData = self::parseEggData($entry->daily_egg_production);
            
            // Cap unrealistic production at 110% of bird count
            $maxPossible = $entry->current_birds * 1.1;
            if ($eggData['total_pieces'] > $maxPossible) {
                return false;
            }
            
            return true;
        });
        
        if ($validEntries->count() === 0) {
            return 0;
        }

        // Get total eggs produced from valid entries only
        $totalEggs = 0;
        $daysWithProduction = 0;
        
        foreach ($validEntries as $entry) {
            $eggData = self::parseEggData($entry->daily_egg_production);
            
            // Only count days with egg production data
            if ($eggData['total_pieces'] > 0) {
                $totalEggs += $eggData['total_pieces'];
                $daysWithProduction++;
            }
        }
        
        if ($daysWithProduction === 0) {
            return 0;
        }
        
        // Use average birds across valid entries
        $avgBirds = $validEntries->avg('current_birds');
        
        if ($avgBirds <= 0) {
            return 0;
        }
        
        // Calculate average eggs per bird per day
        $avgEggsPerBirdPerDay = ($totalEggs / $daysWithProduction) / $avgBirds;
        
        // Cap at 100% (1 egg per bird per day is 100%)
        $productionRate = min(100, max(0, $avgEggsPerBirdPerDay * 100));
        
        return $productionRate;
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Click extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'click_id',
        'offer_id',
        'source',
        'timestamp',
        'signature',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'timestamp' => 'datetime',
        'offer_id' => 'integer',
    ];

    /**
     * Scope to filter clicks by date range
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $startDate
     * @param string $endDate
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('timestamp', [
            Carbon::parse($startDate)->startOfDay(),
            Carbon::parse($endDate)->endOfDay()
        ]);
    }

    /**
     * Scope to filter clicks by offer ID
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $offerId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByOffer($query, $offerId)
    {
        return $query->where('offer_id', $offerId);
    }

    /**
     * Scope to filter clicks by source
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $source
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBySource($query, $source)
    {
        return $query->where('source', $source);
    }

    /**
     * Get clicks for a specific date
     *
     * @param string $date
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getClicksForDate($date)
    {
        return static::whereDate('timestamp', $date)->get();
    }

    /**
     * Get aggregated clicks data
     *
     * @param string $startDate
     * @param string $endDate
     * @param array $groupBy
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getAggregatedData($startDate, $endDate, $groupBy = ['offer_id'])
    {
        $query = static::selectRaw(implode(', ', $groupBy) . ', COUNT(*) as clicks_count')
            ->dateRange($startDate, $endDate)
            ->groupBy($groupBy);

        return $query->get();
    }
}

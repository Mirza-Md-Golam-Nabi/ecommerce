<?php

namespace App\Jobs;

use App\Services\Reports\DailySummaryBuilder;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Rebuild report summaries for the given days after an order (or customer) changes.
 * Unique until processing: many saves in one request queue only one job per set of days.
 */
class RefreshDailySummaries implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Queueable;

    /**
     * @param  list<string>  $dates  Y-m-d
     */
    public function __construct(public array $dates)
    {
        sort($this->dates);
        $this->dates = array_values(array_unique($this->dates));
    }

    public function uniqueId(): string
    {
        return implode(',', $this->dates);
    }

    public function handle(DailySummaryBuilder $builder): void
    {
        foreach ($this->dates as $date) {
            $builder->rebuild(CarbonImmutable::parse($date));
        }
    }
}

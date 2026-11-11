<?php

namespace App\Console\Commands;

use App\Services\Reports\DailySummaryBuilder;
use App\Services\Reports\DateRange;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('reports:rebuild {--from= : First day (Y-m-d)} {--to= : Last day (Y-m-d), default today} {--days= : Rebuild the last N days instead}')]
#[Description('Rebuild the daily report summary tables from raw orders')]
class RebuildReports extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(DailySummaryBuilder $builder): int
    {
        $range = match (true) {
            filled($this->option('days')) => DateRange::lastDays(max(1, (int) $this->option('days'))),
            filled($this->option('from')) => new DateRange(
                CarbonImmutable::parse($this->option('from'))->startOfDay(),
                filled($this->option('to')) ? CarbonImmutable::parse($this->option('to'))->startOfDay() : CarbonImmutable::today(),
            ),
            default => DateRange::lastDays(3),
        };

        $days = $builder->rebuildRange($range);

        $this->info("Rebuilt {$days} day(s): {$range->fromDate()} → {$range->toDate()}");

        return self::SUCCESS;
    }
}

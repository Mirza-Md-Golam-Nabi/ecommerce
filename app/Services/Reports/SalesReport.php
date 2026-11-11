<?php

namespace App\Services\Reports;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Sales, profit and order figures from `daily_sales_summaries` (never from raw orders).
 *
 * @phpstan-type Totals array{orders_placed: int, orders_cancelled: int, orders_delivered: int, booked_sales: int, revenue: int, delivery_income: int, cogs: int, gross_profit: int, estimated_cost_orders: int, lost_sales: int, unavailable_lines: int, items_delivered: int, new_customers: int}
 */
final class SalesReport
{
    public const METRICS = [
        'orders_placed', 'orders_cancelled', 'orders_delivered', 'booked_sales', 'revenue', 'delivery_income',
        'cogs', 'gross_profit', 'estimated_cost_orders', 'lost_sales', 'unavailable_lines', 'items_delivered', 'new_customers',
    ];

    public const GROUP_BY = ['day', 'week', 'month'];

    /**
     * @return Totals
     */
    public function totals(DateRange $range): array
    {
        $row = (array) DB::table('daily_sales_summaries')
            ->whereBetween('date', [$range->fromDate(), $range->toDate()])
            ->selectRaw(implode(', ', array_map(fn (string $metric): string => "coalesce(sum({$metric}), 0) as {$metric}", self::METRICS)))
            ->first();

        /** @var Totals */
        return array_map(fn (mixed $value): int => (int) $value, $row);
    }

    /**
     * Margin % of revenue, e.g. 12.5; null when there is no revenue.
     *
     * @param  Totals  $totals
     */
    public static function margin(array $totals): ?float
    {
        return $totals['revenue'] > 0 ? round($totals['gross_profit'] * 100 / $totals['revenue'], 1) : null;
    }

    /**
     * Totals per day/week/month, including empty periods (for continuous charts).
     *
     * @return list<array<string, int|string>>
     */
    public function series(DateRange $range, string $groupBy = 'day'): array
    {
        $groupBy = in_array($groupBy, self::GROUP_BY, true) ? $groupBy : 'day';

        $daily = DB::table('daily_sales_summaries')
            ->whereBetween('date', [$range->fromDate(), $range->toDate()])
            ->get()
            ->keyBy(fn (object $row): string => substr((string) $row->date, 0, 10));

        $periods = [];

        foreach ($range->days() as $day) {
            [$key, $label] = $this->period(CarbonImmutable::parse($day), $groupBy);

            $periods[$key] ??= ['period' => $key, 'label' => $label, ...array_fill_keys(self::METRICS, 0)];

            if ($row = $daily->get($day)) {
                foreach (self::METRICS as $metric) {
                    $periods[$key][$metric] += (int) $row->{$metric};
                }
            }
        }

        return array_values($periods);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function period(CarbonImmutable $day, string $groupBy): array
    {
        return match ($groupBy) {
            'week' => [$day->startOfWeek()->toDateString(), $day->startOfWeek()->format('d M').' – '.$day->endOfWeek()->format('d M')],
            'month' => [$day->format('Y-m'), $day->format('F Y')],
            default => [$day->toDateString(), $day->format('d M')],
        };
    }
}

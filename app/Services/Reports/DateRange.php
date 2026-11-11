<?php

namespace App\Services\Reports;

use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;

/**
 * Inclusive date range for reports (whole days, app timezone Asia/Dhaka).
 */
final class DateRange
{
    public function __construct(
        public readonly CarbonImmutable $from,
        public readonly CarbonImmutable $to,
    ) {}

    public static function lastDays(int $days): self
    {
        $today = CarbonImmutable::today();

        return new self($today->subDays($days - 1), $today);
    }

    public static function thisMonth(): self
    {
        return new self(CarbonImmutable::today()->startOfMonth(), CarbonImmutable::today());
    }

    /**
     * From report page filters `['from' => 'Y-m-d', 'until' => 'Y-m-d']`; defaults to the last 30 days.
     *
     * @param  array<string, mixed>|null  $filters
     */
    public static function fromFilters(?array $filters): self
    {
        $default = self::lastDays(30);

        $from = filled($filters['from'] ?? null) ? CarbonImmutable::parse($filters['from'])->startOfDay() : $default->from;
        $to = filled($filters['until'] ?? null) ? CarbonImmutable::parse($filters['until'])->startOfDay() : $default->to;

        return $from->greaterThan($to) ? new self($to, $from) : new self($from, $to);
    }

    public function fromDate(): string
    {
        return $this->from->toDateString();
    }

    public function toDate(): string
    {
        return $this->to->toDateString();
    }

    /**
     * @return list<string> Every day in the range as Y-m-d.
     */
    public function days(): array
    {
        return array_map(
            fn ($day): string => $day->toDateString(),
            iterator_to_array(CarbonPeriod::create($this->from, $this->to)),
        );
    }
}

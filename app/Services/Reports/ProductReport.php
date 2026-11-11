<?php

namespace App\Services\Reports;

use App\Services\Catalog\CategoryTree;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Product and category figures from `daily_product_sales`.
 */
final class ProductReport
{
    public const SORTS = ['quantity_sold', 'revenue', 'profit'];

    public function __construct(private CategoryTree $tree) {}

    /**
     * Best-selling products.
     *
     * @return list<array{product_id: int, name: string, unit_label: string, quantity_sold: int, grams_sold: int, revenue: int, cogs: int, profit: int}>
     */
    public function top(DateRange $range, string $sortBy = 'revenue', int $limit = 10): array
    {
        $sortBy = in_array($sortBy, self::SORTS, true) ? $sortBy : 'revenue';

        return $this->withProduct($range)
            ->selectRaw('sum(s.quantity_sold) as quantity_sold, sum(s.grams_sold) as grams_sold, sum(s.revenue) as revenue, sum(s.cogs) as cogs, sum(s.profit) as profit')
            ->havingRaw('sum(s.quantity_sold) > 0')
            ->orderByDesc($sortBy)
            ->limit($limit)
            ->get()
            ->map(fn (object $row): array => $this->castRow($row, ['quantity_sold', 'grams_sold', 'revenue', 'cogs', 'profit']))
            ->all();
    }

    /**
     * Products most often not found in the market, with the sales value lost.
     *
     * @return list<array{product_id: int, name: string, unit_label: string, unavailable_count: int, lost_value: int}>
     */
    public function unavailable(DateRange $range, int $limit = 10): array
    {
        return $this->withProduct($range)
            ->selectRaw('sum(s.unavailable_count) as unavailable_count, sum(s.lost_value) as lost_value')
            ->havingRaw('sum(s.lost_value) > 0')
            ->orderByDesc('unavailable_count')
            ->orderByDesc('lost_value')
            ->limit($limit)
            ->get()
            ->map(fn (object $row): array => $this->castRow($row, ['unavailable_count', 'lost_value']))
            ->all();
    }

    /**
     * Sales per category (the leaf category the product belonged to when ordered).
     *
     * @return list<array{category_id: int, name: string, quantity_sold: int, revenue: int, profit: int}>
     */
    public function categories(DateRange $range): array
    {
        return DB::table('daily_product_sales')
            ->whereBetween('date', [$range->fromDate(), $range->toDate()])
            ->groupBy('category_id')
            ->selectRaw('category_id, sum(quantity_sold) as quantity_sold, sum(revenue) as revenue, sum(profit) as profit')
            ->havingRaw('sum(revenue) > 0')
            ->orderByDesc('revenue')
            ->get()
            ->map(fn (object $row): array => [
                'category_id' => (int) $row->category_id,
                'name' => $this->tree->pathLabel((int) $row->category_id) ?: 'মুছে ফেলা ক্যাটাগরি',
                'quantity_sold' => (int) $row->quantity_sold,
                'revenue' => (int) $row->revenue,
                'profit' => (int) $row->profit,
            ])
            ->all();
    }

    private function withProduct(DateRange $range): Builder
    {
        return DB::table('daily_product_sales as s')
            ->join('products as p', 'p.id', '=', 's.product_id')
            ->whereBetween('s.date', [$range->fromDate(), $range->toDate()])
            ->groupBy('s.product_id', 'p.name', 'p.unit_label')
            ->select('s.product_id', 'p.name', 'p.unit_label');
    }

    /**
     * @param  list<string>  $numeric
     * @return array<string, int|string>
     */
    private function castRow(object $row, array $numeric): array
    {
        $data = (array) $row;
        $data['product_id'] = (int) $data['product_id'];

        foreach ($numeric as $column) {
            $data[$column] = (int) $data[$column];
        }

        return $data;
    }
}

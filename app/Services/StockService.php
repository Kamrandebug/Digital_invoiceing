<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StockService
{
    /**
     * Apply purchase line quantities (stock in).
     *
     * @param  iterable<int, object|array<string, mixed>>  $lines  rows with ProductID, Quantity, Rate
     */
    public function applyPurchaseLines(iterable $lines): void
    {
        foreach ($lines as $line) {
            $productId = (int) ($this->lineValue($line, 'ProductID') ?? 0);
            $qty = (float) ($this->lineValue($line, 'Quantity') ?? 0);
            $rate = (float) ($this->lineValue($line, 'Rate') ?? 0);

            if ($productId <= 0 || $qty <= 0) {
                continue;
            }

            $this->adjustProductStock($productId, $qty, $rate);
        }
    }

    /**
     * Reverse purchase line quantities (stock out) — used on edit/delete.
     *
     * @param  iterable<int, object|array<string, mixed>>  $lines
     */
    public function reversePurchaseLines(iterable $lines): void
    {
        foreach ($lines as $line) {
            $productId = (int) ($this->lineValue($line, 'ProductID') ?? 0);
            $qty = (float) ($this->lineValue($line, 'Quantity') ?? 0);

            if ($productId <= 0 || $qty <= 0) {
                continue;
            }

            $this->adjustProductStock($productId, -$qty, null);
        }
    }

    /**
     * Net adjustment per product when editing purchase lines.
     *
     * @param  array<int, float>  $oldQtyByProduct  productId => qty previously posted
     * @param  array<int, float>  $newQtyByProduct  productId => qty from new lines
     * @param  array<int, float>  $newRateByProduct productId => last unit cost from new lines
     */
    public function syncPurchaseEdit(array $oldQtyByProduct, array $newQtyByProduct, array $newRateByProduct = []): void
    {
        $productIds = array_unique(array_merge(array_keys($oldQtyByProduct), array_keys($newQtyByProduct)));

        foreach ($productIds as $productId) {
            $oldQty = (float) ($oldQtyByProduct[$productId] ?? 0);
            $newQty = (float) ($newQtyByProduct[$productId] ?? 0);
            $delta = $newQty - $oldQty;

            if (abs($delta) < 0.0000001) {
                if ($newQty > 0 && isset($newRateByProduct[$productId])) {
                    $this->updateCostOnly($productId, (float) $newRateByProduct[$productId]);
                }
                continue;
            }

            $rate = $delta > 0 ? ($newRateByProduct[$productId] ?? null) : null;
            $this->adjustProductStock((int) $productId, $delta, $rate);
        }
    }

    public function adjustProductStock(int $productId, float $quantityDelta, ?float $unitCost = null): void
    {
        if ($productId <= 0 || abs($quantityDelta) < 0.0000001) {
            return;
        }

        if ($this->hasColumn('tbl_product', 'CurrentQuantity')) {
            DB::table('tbl_product')
                ->where('ID', $productId)
                ->update([
                    'CurrentQuantity' => DB::raw('COALESCE(CurrentQuantity, 0) + '.((float) $quantityDelta)),
                ]);
        }

        if ($unitCost !== null && $quantityDelta > 0) {
            $this->updateCostOnly($productId, $unitCost);
        }

        $this->refreshStockStatus($productId);
    }

    public function updateCostOnly(int $productId, float $unitCost): void
    {
        if (! $this->hasColumn('tbl_product', 'Cost')) {
            return;
        }

        DB::table('tbl_product')
            ->where('ID', $productId)
            ->update(['Cost' => round($unitCost, 6)]);
    }

    public function refreshStockStatus(int $productId): void
    {
        if (! $this->hasColumn('tbl_product', 'StockStatus')) {
            return;
        }

        $columns = $this->hasColumn('tbl_product', 'CurrentQuantity') ? ['CurrentQuantity'] : [];
        if ($this->hasColumn('tbl_product', 'LowStockLevel')) {
            $columns[] = 'LowStockLevel';
        }

        if ($columns === []) {
            return;
        }

        $product = DB::table('tbl_product')
            ->where('ID', $productId)
            ->first($columns);

        if (! $product) {
            return;
        }

        $qty = (float) ($product->CurrentQuantity ?? 0);
        $low = (float) ($product->LowStockLevel ?? 0);
        $threshold = $low > 0 ? $low : 10;

        $status = 1;
        if ($qty <= 0) {
            $status = 2;
        } elseif ($qty < $threshold) {
            $status = 0;
        }

        DB::table('tbl_product')
            ->where('ID', $productId)
            ->update(['StockStatus' => $status]);
    }

    private function hasColumn(string $table, string $column): bool
    {
        try {
            return Schema::hasColumn($table, $column);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @param  object|array<string, mixed>  $line
     */
    private function lineValue(object|array $line, string $key): mixed
    {
        if (is_array($line)) {
            return $line[$key] ?? null;
        }

        return $line->{$key} ?? null;
    }

    /**
     * @param  iterable<int, object|array<string, mixed>>  $lines
     * @return array<int, float>
     */
    public static function aggregateQuantitiesByProduct(iterable $lines): array
    {
        $map = [];
        foreach ($lines as $line) {
            $svc = new self;
            $productId = (int) ($svc->lineValue($line, 'ProductID') ?? 0);
            $qty = (float) ($svc->lineValue($line, 'Quantity') ?? 0);
            if ($productId > 0 && $qty > 0) {
                $map[$productId] = ($map[$productId] ?? 0) + $qty;
            }
        }

        return $map;
    }

    /**
     * @param  iterable<int, object|array<string, mixed>>  $lines
     * @return array<int, float> last rate per product
     */
    public static function lastRatesByProduct(iterable $lines): array
    {
        $map = [];
        foreach ($lines as $line) {
            $svc = new self;
            $productId = (int) ($svc->lineValue($line, 'ProductID') ?? 0);
            if ($productId > 0) {
                $map[$productId] = (float) ($svc->lineValue($line, 'Rate') ?? 0);
            }
        }

        return $map;
    }
}

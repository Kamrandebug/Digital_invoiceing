<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class PurchaseLedgerImport implements ToCollection
{
    /** @var list<array<string, mixed>> */
    private array $rows = [];

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            if ($index === 0) {
                continue;
            }

            $values = $row->values()->all();
            if ($this->rowIsEmpty($values)) {
                continue;
            }

            $this->rows[] = [
                'RowNo' => $index + 1,
                'InvoiceNo' => $this->cellString($values, 0),
                'InvoiceType' => $this->cellString($values, 1),
                'InvoiceDate' => $this->cellString($values, 2),
                'BuyerRegistrationNo' => $this->cellString($values, 3),
                'BuyerName' => $this->cellString($values, 4),
                'TaxpayerType' => $this->cellString($values, 5),
                'SellerRegistrationNo' => $this->cellString($values, 6),
                'SellerName' => $this->cellString($values, 7),
                'SaleType' => $this->cellString($values, 8),
                'SaleOriginationProvince' => $this->cellString($values, 9),
                'Quantity' => $this->cellDecimal($values, 10),
                'ProductDescription' => $this->cellString($values, 11),
                'HsCode' => $this->cellString($values, 12),
                'Rate' => $this->cellString($values, 13),
                'Uom' => $this->cellString($values, 14),
                'ValueSalesExcludingST' => $this->cellDecimal($values, 15),
                'SalesTaxFedInStMode' => $this->cellDecimal($values, 16),
                'FixedNotifiedValueOrRetailPrice' => $this->cellDecimal($values, 17),
            ];
        }
    }

    /** @return list<array<string, mixed>> */
    public function mappedRows(): array
    {
        return $this->rows;
    }

    /**
     * @param  array<int, mixed>  $values
     */
    private function rowIsEmpty(array $values): bool
    {
        foreach ($values as $value) {
            if ($value !== null && trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<int, mixed>  $values
     */
    private function cellString(array $values, int $index): ?string
    {
        if (! array_key_exists($index, $values)) {
            return null;
        }

        $value = trim((string) ($values[$index] ?? ''));

        return $value === '' ? null : $value;
    }

    /**
     * @param  array<int, mixed>  $values
     */
    private function cellDecimal(array $values, int $index): float
    {
        if (! array_key_exists($index, $values)) {
            return 0.0;
        }

        $raw = trim((string) ($values[$index] ?? ''));
        if ($raw === '') {
            return 0.0;
        }

        $normalized = str_replace([',', ' '], '', $raw);
        if (! is_numeric($normalized)) {
            return 0.0;
        }

        return (float) $normalized;
    }
}

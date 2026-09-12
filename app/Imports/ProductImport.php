<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ProductImport implements ToCollection, WithCalculatedFormulas, WithMultipleSheets
{
    /** @var list<array<string, mixed>> */
    private array $rows = [];
    private array $validationErrors = [];

    /**
     * WithMultipleSheets: only process the first sheet (index 0 = Products Import).
     * This prevents the hidden Lookup_Data sheet from being read as data.
     */
    public function sheets(): array
    {
        return [0 => $this];
    }

    public function collection(Collection $rows): void
    {
        if ($rows->isEmpty()) {
            return;
        }

        // Detect header row
        $firstRow = $rows->first()->values()->all();
        $headerMap = $this->buildHeaderMap($firstRow);
        $hasHeaders = !empty($headerMap);

        $startIndex = $hasHeaders ? 1 : 0;

        for ($i = $startIndex; $i < $rows->count(); $i++) {
            $rowValues = $rows[$i]->values()->all();
            if ($this->rowIsEmpty($rowValues)) {
                continue;
            }

            $mapped = $hasHeaders 
                ? $this->mapWithHeaders($rowValues, $headerMap, $i + 1)
                : $this->mapPositional($rowValues, $i + 1);

            if ($mapped !== null) {
                $this->rows[] = $mapped;
            }
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function mappedRows(): array
    {
        return $this->rows;
    }

    /**
     * @return list<string>
     */
    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    public static function normalizeRate(mixed $val): ?string
    {
        if ($val === null) return null;
        $str = trim((string)$val);
        if ($str === '') return null;

        if (str_ends_with($str, '%')) {
            return $str;
        }

        if (is_numeric($str)) {
            $num = (float)$str;
            if ($num == 0.0) {
                return '0%';
            }
            if ($num > 0 && $num < 1.0) {
                $percentage = round($num * 100, 4);
                return rtrim(rtrim((string)$percentage, '0'), '.') . '%';
            }
            return rtrim(rtrim((string)$num, '0'), '.') . '%';
        }

        return $str;
    }

    private function buildHeaderMap(array $firstRow): array
    {
        $map = [];
        $knownKeys = [
            'code'            => ['productcode', 'product_code', 'product code', 'itemcode', 'item_code', 'item code', 'product code *', 'code *', 'code'],
            'name'            => ['productname', 'product_name', 'product name', 'itemname', 'item_name', 'product name *', 'name *', 'name'],
            'producttype'     => ['producttype', 'product_type', 'product type', 'itemtype', 'type'],
            'saleprice'       => ['saleprice', 'sale_price', 'sale price', 'price', 'unitprice', 'unit_price', 'sale price *', 'price *'],
            'tprate'          => ['tprate', 'tp_rate', 'tp rate', 'fixednotifiedvalue', 'fixed_notified_value', 'fixed notified value', 'fixed value', 'notified value'],
            'hscode'          => ['hscode', 'hs_code', 'hs code', 'hscodes'],
            'uom'             => ['uom', 'uom_desc', 'uom desc', 'uom description', 'unit of measure', 'unit of measurement'],
            'saletype'        => ['saletype', 'sale_type', 'sale type', 'saletypedesc', 'sale_type_desc', 'sale type desc', 'transaction type'],
            'saletypetorate'  => ['saletypetorate', 'sale_type_to_rate', 'sale type rate', 'saletyperate', 'rate', 'tax rate', 'tax rate %', 'saletypetoratedesc'],
            'sroschedule'     => ['sroschedule', 'sro_schedule', 'sro schedule', 'sroscheduledesc', 'sro_schedule_desc', 'sro schedule desc'],
            'sroitemserialno' => ['sroitemserialno', 'sro_item_serial_no', 'sro item serial no', 'sroitem', 'sro_item', 'sro item', 'sro serial no'],
            'saleinformation' => ['saleinformation', 'sale_information', 'sale info', 'productdescription', 'product_description', 'product description', 'description', 'notes', 'remarks'],
        ];

        foreach ($firstRow as $idx => $val) {
            if ($val === null) continue;
            $clean = strtolower(trim((string)$val));
            $cleanAlpha = preg_replace('/[^a-z0-9]/', '', $clean);

            foreach ($knownKeys as $canonical => $aliases) {
                foreach ($aliases as $alias) {
                    $aliasAlpha = preg_replace('/[^a-z0-9]/', '', $alias);
                    if ($cleanAlpha === $aliasAlpha) {
                        $map[$canonical] = $idx;
                        break 2;
                    }
                }
            }
        }

        if (isset($map['name']) || isset($map['code'])) {
            return $map;
        }

        return [];
    }

    private function mapWithHeaders(array $values, array $headerMap, int $rowNum): ?array
    {
        $code = $this->getHeaderValue($values, $headerMap, 'code');
        $name = $this->getHeaderValue($values, $headerMap, 'name');

        if (empty($name) && empty($code)) {
            return null; // Ignore blank row
        }

        $productType = $this->getHeaderValue($values, $headerMap, 'producttype', 'Product');
        $salePrice = (float) $this->getHeaderValue($values, $headerMap, 'saleprice', 0);
        $tpRate = (float) $this->getHeaderValue($values, $headerMap, 'tprate', 0);
        $isFixedRate = ($tpRate > 1) ? 1 : 0;
        $hsCode = $this->getHeaderValue($values, $headerMap, 'hscode');

        $uomInput = $this->getHeaderValue($values, $headerMap, 'uom');
        $saleTypeInput = $this->getHeaderValue($values, $headerMap, 'saletype');
        $rateInput = self::normalizeRate($this->getHeaderValue($values, $headerMap, 'saletypetorate'));
        $sroScheduleInput = $this->getHeaderValue($values, $headerMap, 'sroschedule');
        $sroItemInput = $this->getHeaderValue($values, $headerMap, 'sroitemserialno');
        $saleInformation = $this->getHeaderValue($values, $headerMap, 'saleinformation');

        if (empty($saleInformation)) {
            $saleInformation = $name ?: $code;
        }

        return [
            'RowNo'              => $rowNum,
            'Code'               => $code,
            'Name'               => $name,
            'Type'               => $productType ?: 'Product',
            'CategoryID'         => 1,
            'CurrentQuantity'    => 0.0,
            'Unit'               => 1,
            'SalePrice'          => $salePrice,
            'Cost'               => 0.0,
            'TPRate'             => $tpRate,
            'IsFixedRate'        => $isFixedRate,
            'Weight'             => 0.0,
            'Image'              => null,
            'HsCode'             => $hsCode ?: null,
            'UOM'                => null,
            'Uom_Desc'           => $uomInput ?: null,
            'SaleType'           => null,
            'SaleTypeDesc'       => $saleTypeInput ?: null,
            'SaleTypeToRate'     => null,
            'SaleTypeToRateDesc' => $rateInput ?: null,
            'SroSchedule'        => null,
            'SroScheduleDesc'    => $sroScheduleInput ?: null,
            'SroItem'            => null,
            'SroItemSerialNo'    => $sroItemInput ?: null,
            'SaleInformation'    => $saleInformation,
            'Status'             => 1,
        ];
    }

    private function mapPositional(array $v, int $rowNum): ?array
    {
        $code = trim((string)($v[0] ?? ''));
        $name = trim((string)($v[1] ?? ''));

        if (empty($name) && empty($code)) {
            return null;
        }

        $productType = trim((string)($v[2] ?? 'Product')) ?: 'Product';
        $salePrice = (float)($v[3] ?? 0);
        $tpRate = (float)($v[4] ?? 0);
        $isFixedRate = ($tpRate > 1) ? 1 : 0;
        $hsCode = trim((string)($v[5] ?? ''));

        $uomInput = trim((string)($v[6] ?? ''));
        $saleTypeInput = trim((string)($v[7] ?? ''));
        $rateInput = self::normalizeRate($v[8] ?? '');
        $sroScheduleInput = trim((string)($v[9] ?? ''));
        $sroItemInput = trim((string)($v[10] ?? ''));
        $saleInformation = trim((string)($v[11] ?? ''));

        if (empty($saleInformation)) {
            $saleInformation = $name ?: $code;
        }

        return [
            'RowNo'              => $rowNum,
            'Code'               => $code,
            'Name'               => $name,
            'Type'               => $productType,
            'CategoryID'         => 1,
            'CurrentQuantity'    => 0.0,
            'Unit'               => 1,
            'SalePrice'          => $salePrice,
            'Cost'               => 0.0,
            'TPRate'             => $tpRate,
            'IsFixedRate'        => $isFixedRate,
            'Weight'             => 0.0,
            'Image'              => null,
            'HsCode'             => $hsCode ?: null,
            'UOM'                => null,
            'Uom_Desc'           => $uomInput ?: null,
            'SaleType'           => null,
            'SaleTypeDesc'       => $saleTypeInput ?: null,
            'SaleTypeToRate'     => null,
            'SaleTypeToRateDesc' => $rateInput ?: null,
            'SroSchedule'        => null,
            'SroScheduleDesc'    => $sroScheduleInput ?: null,
            'SroItem'            => null,
            'SroItemSerialNo'    => $sroItemInput ?: null,
            'SaleInformation'    => $saleInformation,
            'Status'             => 1,
        ];
    }

    private function getHeaderValue(array $values, array $headerMap, string $key, mixed $default = null): mixed
    {
        if (!isset($headerMap[$key])) {
            return $default;
        }
        $idx = $headerMap[$key];
        $val = $values[$idx] ?? $default;
        if ($val === null) return $default;
        $trimmed = trim((string)$val);
        return $trimmed !== '' ? $trimmed : $default;
    }

    private function rowIsEmpty(array $rowValues): bool
    {
        foreach ($rowValues as $val) {
            if ($val !== null && trim((string)$val) !== '') {
                return false;
            }
        }
        return true;
    }
}

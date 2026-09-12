<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class BulkInvoiceImport implements ToCollection, WithCalculatedFormulas, WithMultipleSheets
{
    /** @var list<array<string, mixed>> */
    private array $rows = [];
    private array $validationErrors = [];

    /**
     * WithMultipleSheets: only process the first sheet (index 0 = Invoice Import).
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

    public static function parseInvoiceType(mixed $val): int
    {
        if (is_numeric($val) && (int)$val > 0) {
            return (int)$val;
        }
        $clean = strtolower(trim((string)$val));
        if (in_array($clean, ['sale invoice', 'sales invoice', 'sale', 'sales', 'si', 'tax invoice', 'invoice', ''])) {
            return 4;
        }
        if (in_array($clean, ['credit note', 'cn', 'credit'])) {
            return 3;
        }
        if (in_array($clean, ['debit note', 'dn', 'debit'])) {
            return 2;
        }
        return 4; // Default to 4 (Sale Invoice)
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
            'invoicetype'                     => ['invoicetype', 'invoice_type', 'invoice type', 'invoicetype *', 'invoice type *', 'doctype', 'doc_type', 'type'],
            'invoicedate'                     => ['invoicedate', 'invoice_date', 'invoice date', 'invoicedate *', 'invoice date *', 'date', 'date *'],
            'invoicerefno'                    => ['invoicerefno', 'invoice_ref_no', 'invoice ref no', 'invoicerefno *', 'invoice ref no *', 'docno', 'doc_no', 'doc no', 'invoiceno', 'invoice_no', 'invoice no'],
            'saleofterm'                      => ['saleofterm', 'sale_of_term', 'sale of term', 'saleofterm *', 'sale of term *', 'term', 'terms', 'paymentterm'],
            'customercode'                    => ['customercode', 'customer_code', 'customer code', 'customercode *', 'customer code *', 'customerntn', 'customer_ntn', 'ntn', 'buyerntn', 'buyer_ntn'],
            'productcode'                     => ['productcode', 'product_code', 'product code', 'productcode *', 'product code *', 'itemcode', 'item_code'],
            'hscode'                          => ['hscode', 'hs_code', 'hs code', 'hscode *', 'hs code *'],
            'productdescription'              => ['productdescription', 'product_description', 'product description', 'productdescription *', 'description', 'details'],
            'saletype'                        => ['saletype', 'sale_type', 'sale type', 'saletype *', 'sale type *', 'saletypedesc', 'transactiontype'],
            'fbrrate'                         => ['fbrrate', 'fbr_rate', 'fbr rate', 'fbr rate *', 'fbrrate *', 'rate', 'taxrate', 'saletypetorate'],
            'quantity'                        => ['quantity', 'qty', 'quantity *', 'qty *'],
            'uom'                             => ['uom', 'uom_desc', 'uom *', 'uom desc *', 'unit of measure', 'unit', 'uomdesc', 'uomdescription', 'uom_description'],
            'valuesalesexcludingst'           => ['valuesalesexcludingst', 'value_sales_excluding_st', 'value sales excluding st', 'valuesalesexcludingst *', 'salesvalue', 'amount', 'netamount'],
            'fixednotifiedvalueorretailprice' => ['fixednotifiedvalueorretailprice', 'fixed_notified_value_or_retail_price', 'fixed notified value', 'fixednotifiedvalue', 'retailprice', 'tprate', 'fixed notified value (tp rate)', 'fixednotifiedvalue (tp rate)', 'fixednotifiedvaluetprate'],
            'salestaxapplicable'              => ['salestaxapplicable', 'sales_tax_applicable', 'sales tax applicable', 'salestaxapplicable *', 'gstamount', 'salestax'],
            'extratax'                        => ['extratax', 'extra_tax', 'extra tax'],
            'furthertax'                      => ['furthertax', 'further_tax', 'further tax'],
            'salestaxwithheldatsource'        => ['salestaxwithheldatsource', 'sales_tax_withheld_at_source', 'sales tax withheld at source', 'withheldtax'],
            'sroschedule'                     => ['sroschedule', 'sro_schedule', 'sro schedule', 'sroscheduledesc'],
            'sroitemserialno'                 => ['sroitemserialno', 'sro_item_serial_no', 'sro item serial no', 'sroitem', 'sro item'],
            'petroleumlevyon'                 => ['petroleumlevyon', 'petroleum_levy_on', 'petroleum levy on', 'levy'],
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

        if (isset($map['invoicerefno']) || isset($map['customercode']) || isset($map['productcode'])) {
            return $map;
        }

        return [];
    }

    private function mapWithHeaders(array $values, array $headerMap, int $rowNum): ?array
    {
        $docNo = $this->getHeaderValue($values, $headerMap, 'invoicerefno');
        $customerCode = $this->getHeaderValue($values, $headerMap, 'customercode');
        $productCode = $this->getHeaderValue($values, $headerMap, 'productcode');

        if (empty($docNo) && empty($customerCode) && empty($productCode)) {
            return null; // Ignore blank row
        }

        $invoiceTypeRaw = $this->getHeaderValue($values, $headerMap, 'invoicetype', 'Sale Invoice');
        $invoiceType = self::parseInvoiceType($invoiceTypeRaw);
        $invoiceDate = $this->parseDate($this->getHeaderValue($values, $headerMap, 'invoicedate'));
        $saleOfTerm = $this->getHeaderValue($values, $headerMap, 'saleofterm', 'CASH');
        $hsCode = $this->normalizeHsCode($this->getHeaderValue($values, $headerMap, 'hscode'));
        $productDescription = $this->getHeaderValue($values, $headerMap, 'productdescription');
        $saleType = $this->getHeaderValue($values, $headerMap, 'saletype');
        $rateRaw = $this->getHeaderValue($values, $headerMap, 'fbrrate');
        $rate = self::normalizeRate($rateRaw);
        $qty = (float) $this->getHeaderValue($values, $headerMap, 'quantity', 0);
        $uom = $this->getHeaderValue($values, $headerMap, 'uom');
        $amount = (float) $this->getHeaderValue($values, $headerMap, 'valuesalesexcludingst', 0);
        $fixedNotified = $this->getNullableFloat($this->getHeaderValue($values, $headerMap, 'fixednotifiedvalueorretailprice'));
        $salesTax = (float) $this->getHeaderValue($values, $headerMap, 'salestaxapplicable', 0);
        $extraTax = (float) $this->getHeaderValue($values, $headerMap, 'extratax', 0);
        $furtherTax = (float) $this->getHeaderValue($values, $headerMap, 'furthertax', 0);
        $withheldTax = (float) $this->getHeaderValue($values, $headerMap, 'salestaxwithheldatsource', 0);
        $sroSchedule = $this->getHeaderValue($values, $headerMap, 'sroschedule');
        $sroItemSerialNo = $this->getHeaderValue($values, $headerMap, 'sroitemserialno');
        $petroleumLevyOn = $this->getHeaderValue($values, $headerMap, 'petroleumlevyon', 'No Levy');

        return [
            'RowNo'                           => $rowNum,
            'InvoiceType'                     => $invoiceType,
            'InvoiceDate'                     => $invoiceDate,
            'InvoiceRefNo'                    => $docNo,
            'DocNo'                           => $docNo,
            'SaleOfTerm'                      => $saleOfTerm,
            'CustomerCode'                    => $customerCode,
            'ProductCode'                     => $productCode,
            'HsCode'                          => $hsCode,
            'ProductDescription'              => $productDescription,
            'SaleTypeDesc'                    => $saleType,
            'SaleTypeToRateDesc'              => $rate,
            'Quantity'                        => $qty,
            'Uom_Desc'                        => $uom,
            'ValueSalesExcludingST'           => $amount,
            'FixedNotifiedValueOrRetailPrice' => $fixedNotified,
            'SalesTaxApplicable'              => $salesTax,
            'ExtraTax'                        => $extraTax,
            'FurtherTax'                      => $furtherTax,
            'SalesTaxWithheldAtSource'        => $withheldTax,
            'SroScheduleDesc'                 => $sroSchedule,
            'SroItemSerialNo'                 => $sroItemSerialNo,
            'PetroleumLevyOn'                 => $petroleumLevyOn,
        ];
    }

    private function mapPositional(array $v, int $rowNum): ?array
    {
        $invoiceType = self::parseInvoiceType($v[0] ?? 'Sale Invoice');
        $invoiceDate = $this->parseDate(trim((string)($v[1] ?? '')));
        $docNo = trim((string)($v[2] ?? ''));
        $saleOfTerm = trim((string)($v[3] ?? 'CASH')) ?: 'CASH';
        $customerCode = trim((string)($v[4] ?? ''));
        $productCode = trim((string)($v[5] ?? ''));

        if (empty($docNo) && empty($customerCode) && empty($productCode)) {
            return null;
        }

        $hsCode = $this->normalizeHsCode(trim((string)($v[6] ?? '')));
        $productDescription = trim((string)($v[7] ?? ''));
        $saleType = trim((string)($v[8] ?? ''));
        $rate = self::normalizeRate($v[9] ?? '');
        $qty = (float)($v[10] ?? 0);
        $uom = trim((string)($v[11] ?? ''));
        $amount = (float)($v[12] ?? 0);
        $fixedNotified = $this->getNullableFloat($v[13] ?? null);
        $salesTax = (float)($v[14] ?? 0);
        $extraTax = (float)($v[15] ?? 0);
        $furtherTax = (float)($v[16] ?? 0);
        $withheldTax = (float)($v[17] ?? 0);
        $sroSchedule = trim((string)($v[18] ?? ''));
        $sroItemSerialNo = trim((string)($v[19] ?? ''));
        $petroleumLevyOn = trim((string)($v[20] ?? 'No Levy')) ?: 'No Levy';

        return [
            'RowNo'                           => $rowNum,
            'InvoiceType'                     => $invoiceType,
            'InvoiceDate'                     => $invoiceDate,
            'InvoiceRefNo'                    => $docNo,
            'DocNo'                           => $docNo,
            'SaleOfTerm'                      => $saleOfTerm,
            'CustomerCode'                    => $customerCode,
            'ProductCode'                     => $productCode,
            'HsCode'                          => $hsCode,
            'ProductDescription'              => $productDescription,
            'SaleTypeDesc'                    => $saleType,
            'SaleTypeToRateDesc'              => $rate,
            'Quantity'                        => $qty,
            'Uom_Desc'                        => $uom,
            'ValueSalesExcludingST'           => $amount,
            'FixedNotifiedValueOrRetailPrice' => $fixedNotified,
            'SalesTaxApplicable'              => $salesTax,
            'ExtraTax'                        => $extraTax,
            'FurtherTax'                      => $furtherTax,
            'SalesTaxWithheldAtSource'        => $withheldTax,
            'SroScheduleDesc'                 => $sroSchedule,
            'SroItemSerialNo'                 => $sroItemSerialNo,
            'PetroleumLevyOn'                 => $petroleumLevyOn,
        ];
    }

    private function normalizeHsCode(?string $val): ?string
    {
        if (empty($val)) return null;
        $val = trim($val);
        if (str_contains($val, '.')) {
            [$main, $sub] = explode('.', $val, 2);
            $sub = str_pad(substr($sub, 0, 4), 4, '0');
            return $main . '.' . $sub;
        }
        return $val;
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

    private function getNullableFloat(mixed $val): ?float
    {
        if ($val === null || trim((string)$val) === '') return null;
        return (float)$val;
    }

    private function parseDate(?string $val): ?string
    {
        if (empty($val)) return null;
        $t = strtotime($val);
        if ($t === false) return $val;
        return date('Y-m-d', $t);
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

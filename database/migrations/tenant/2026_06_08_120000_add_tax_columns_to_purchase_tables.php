<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        $schema = Schema::connection('tenant');

        if ($schema->hasTable('tbl_purchase_header')) {
            $schema->table('tbl_purchase_header', function (Blueprint $table) use ($schema) {
                if (! $schema->hasColumn('tbl_purchase_header', 'ValueSalesExcludingST')) {
                    $table->decimal('ValueSalesExcludingST', 18, 2)->default(0)->after('SubTotal');
                }
                if (! $schema->hasColumn('tbl_purchase_header', 'SalesTaxApplicable')) {
                    $table->decimal('SalesTaxApplicable', 18, 2)->default(0)->after('ValueSalesExcludingST');
                }
                if (! $schema->hasColumn('tbl_purchase_header', 'Discount')) {
                    $table->decimal('Discount', 18, 2)->default(0)->after('SalesTaxApplicable');
                }
                if (! $schema->hasColumn('tbl_purchase_header', 'ExtraTax')) {
                    $table->decimal('ExtraTax', 18, 2)->default(0)->after('Discount');
                }
                if (! $schema->hasColumn('tbl_purchase_header', 'FurtherTax')) {
                    $table->decimal('FurtherTax', 18, 2)->default(0)->after('ExtraTax');
                }
                if (! $schema->hasColumn('tbl_purchase_header', 'FedPayable')) {
                    $table->decimal('FedPayable', 18, 2)->default(0)->after('FurtherTax');
                }
                if (! $schema->hasColumn('tbl_purchase_header', 'SalesTaxWithheldAtSource')) {
                    $table->decimal('SalesTaxWithheldAtSource', 18, 2)->default(0)->after('FedPayable');
                }
            });
        }

        $detailColumns = [
            'HsCode' => 'string:50',
            'SaleTypeID' => 'unsignedBigInteger',
            'SaleTypeDesc' => 'string:255',
            'SaleTypeToRateID' => 'unsignedBigInteger',
            'SaleTypeToRateDesc' => 'string:255',
            'SroScheduleID' => 'unsignedBigInteger',
            'SroScheduleDesc' => 'string:255',
            'SroItemID' => 'unsignedBigInteger',
            'SroItemSerialNo' => 'string:255',
            'RateType' => 'string:10',
            'TPRate' => 'decimal:18,4',
            'DiscountPercentage' => 'decimal:8,2',
            'DiscountAmount' => 'decimal:18,2',
            'GstPercentage' => 'decimal:8,2',
            'GSTAmount' => 'decimal:18,2',
            'ValueSalesExcludingST' => 'decimal:18,2',
            'FixedNotifiedValueOrRetailPrice' => 'decimal:18,2',
            'SalesTaxApplicable' => 'decimal:18,2',
            'SalesTaxWithheldAtSource' => 'decimal:18,2',
            'ExtraTax' => 'decimal:18,2',
            'FurtherTax' => 'decimal:18,2',
            'FedPayable' => 'decimal:18,2',
            'TotalValues' => 'decimal:18,2',
        ];

        foreach (['tbl_purchase_detail', 'tbl_temp_purchase_detail'] as $tableName) {
            if (! $schema->hasTable($tableName)) {
                continue;
            }
            $schema->table($tableName, function (Blueprint $table) use ($schema, $tableName, $detailColumns) {
                foreach ($detailColumns as $column => $type) {
                    if ($schema->hasColumn($tableName, $column)) {
                        continue;
                    }
                    if (str_starts_with($type, 'string:')) {
                        $len = (int) substr($type, 7);
                        $table->string($column, $len)->nullable()->collation('utf8mb4_general_ci');
                    } elseif (str_starts_with($type, 'decimal:')) {
                        [$precision, $scale] = array_map('intval', explode(',', substr($type, 8)));
                        $table->decimal($column, $precision, $scale)->default(0);
                    } elseif ($type === 'unsignedBigInteger') {
                        $table->unsignedBigInteger($column)->nullable();
                    }
                }
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection('tenant');

        if ($schema->hasTable('tbl_purchase_header')) {
            $schema->table('tbl_purchase_header', function (Blueprint $table) {
                $table->dropColumn([
                    'ValueSalesExcludingST',
                    'SalesTaxApplicable',
                    'Discount',
                    'ExtraTax',
                    'FurtherTax',
                    'FedPayable',
                    'SalesTaxWithheldAtSource',
                ]);
            });
        }

        $detailColumns = [
            'HsCode', 'SaleTypeID', 'SaleTypeDesc', 'SaleTypeToRateID', 'SaleTypeToRateDesc',
            'SroScheduleID', 'SroScheduleDesc', 'SroItemID', 'SroItemSerialNo', 'RateType', 'TPRate',
            'DiscountPercentage', 'DiscountAmount', 'GstPercentage', 'GSTAmount',
            'ValueSalesExcludingST', 'FixedNotifiedValueOrRetailPrice', 'SalesTaxApplicable',
            'SalesTaxWithheldAtSource', 'ExtraTax', 'FurtherTax', 'FedPayable', 'TotalValues',
        ];

        foreach (['tbl_purchase_detail', 'tbl_temp_purchase_detail'] as $tableName) {
            if (! $schema->hasTable($tableName)) {
                continue;
            }
            $schema->table($tableName, function (Blueprint $table) use ($detailColumns) {
                $table->dropColumn($detailColumns);
            });
        }
    }
};

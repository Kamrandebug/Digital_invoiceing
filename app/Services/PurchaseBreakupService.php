<?php

namespace App\Services;

use App\Exports\DynamicBreakupExport;
use App\Jobs\GenerateBreakupJob;
use App\Models\PurchaseModel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class PurchaseBreakupService
{
    public const STATUS_NOT_GENERATED = 0;

    public const STATUS_PROCESSING = 1;

    public const STATUS_GENERATED = 2;

    public const STATUS_FAILED = 3;

    public const STATUS_EXPIRED = 4;

    public function __construct(
        protected ExcelColumnService $excelColumnService
    ) {}

    /**
     * @return array{tenant_id: int, dbserver: ?string, dbuser: ?string, dbpassword: ?string, dbname: ?string}
     */
    public static function tenantContextFromSession(): array
    {
        $tenant = session('selected_tenant', []);

        return [
            'tenant_id' => (int) ($tenant['id'] ?? 0),
            'dbserver' => $tenant['dbserver'] ?? null,
            'dbuser' => $tenant['dbuser'] ?? null,
            'dbpassword' => $tenant['dbpassword'] ?? null,
            'dbname' => $tenant['dbname'] ?? config('database.connections.tenant.database'),
        ];
    }

    /**
     * @param  array{tenant_id?: int, dbserver?: ?string, dbuser?: ?string, dbpassword?: ?string, dbname?: ?string}  $context
     */
    public static function applyTenantContext(array $context): void
    {
        if (! empty($context['dbserver']) && ! empty($context['dbuser'])) {
            config([
                'database.connections.tenant.url' => null,
                'database.connections.tenant.host' => $context['dbserver'],
                'database.connections.tenant.username' => $context['dbuser'],
                'database.connections.tenant.password' => $context['dbpassword'] ?? '',
                'database.connections.tenant.database' => $context['dbname'] ?? config('database.connections.tenant.database'),
            ]);
        } elseif (! empty($context['dbname'])) {
            config(['database.connections.tenant.database' => $context['dbname']]);
        }

        DB::purge('tenant');
        DB::reconnect('tenant');
        DB::setDefaultConnection('tenant');
    }

    public function countActiveLines(int $purchaseId): int
    {
        return (int) DB::table('tbl_purchase_detail')
            ->where('PurchaseID', $purchaseId)
            ->where('Status', '!=', 2)
            ->count();
    }

    /**
     * @return array{header: object, line: object}|null
     */
    public function loadPurchaseContext(int $purchaseId): ?array
    {
        $header = DB::table('tbl_purchase_header as h')
            ->leftJoin('tbl_customer as sup', 'h.SupplierID', '=', 'sup.ID')
            ->where('h.ID', $purchaseId)
            ->where('h.Status', '!=', 2)
            ->select(
                'h.ID',
                'h.DocNo',
                'h.PurchaseDate',
                'h.SupplierID',
                'h.TotalAmount',
                PurchaseModel::sqlSupplierDisplayName()
            )
            ->first();

        if (! $header) {
            return null;
        }

        $lines = DB::table('tbl_purchase_detail as d')
            ->leftJoin('tbl_product as p', 'd.ProductID', '=', 'p.ID')
            ->where('d.PurchaseID', $purchaseId)
            ->where('d.Status', '!=', 2)
            ->select(
                'd.ID as DetailID',
                'd.ProductID',
                'd.Quantity',
                'd.Rate',
                'd.Amount',
                DB::raw('COALESCE(p.Name, d.ProductDescription) as ProductName')
            )
            ->get();

        if ($lines->count() !== 1) {
            return null;
        }

        return ['header' => $header, 'line' => $lines->first()];
    }

    public function getLatestJob(int $purchaseId): ?object
    {
        return DB::connection('tenant')->table('purchase_breakup_jobs')
            ->where('purchase_id', $purchaseId)
            ->orderByDesc('version_no')
            ->first();
    }

    /**
     * Settings from the most recent breakup job (for regenerate modal pre-fill).
     *
     * @return array{min_invoice_amount: ?float, max_invoice_amount: ?float, invoice_prefix: string, allow_decimal_qty: bool}|null
     */
    public function getLastJobSettings(int $purchaseId): ?array
    {
        $job = $this->getLatestJob($purchaseId);
        if (! $job || empty($job->settings_json)) {
            return null;
        }

        $decoded = json_decode((string) $job->settings_json, true);
        if (! is_array($decoded)) {
            return null;
        }

        return [
            'min_invoice_amount' => isset($decoded['min_invoice_amount'])
                ? (float) $decoded['min_invoice_amount']
                : null,
            'max_invoice_amount' => isset($decoded['max_invoice_amount'])
                ? (float) $decoded['max_invoice_amount']
                : null,
            'invoice_prefix' => trim((string) ($decoded['invoice_prefix'] ?? 'INV')) ?: 'INV',
            'allow_decimal_qty' => (bool) ($decoded['allow_decimal_qty'] ?? false),
        ];
    }

    public function hasProcessingJob(int $purchaseId): bool
    {
        return DB::connection('tenant')->table('purchase_breakup_jobs')
            ->where('purchase_id', $purchaseId)
            ->where('breakup_status', self::STATUS_PROCESSING)
            ->exists();
    }

    /**
     * Mark jobs left in Processing (e.g. queue worker never ran) as Failed.
     */
    public function expireStaleProcessingJobs(int $maxAgeMinutes = 2): int
    {
        return DB::connection('tenant')->table('purchase_breakup_jobs')
            ->where('breakup_status', self::STATUS_PROCESSING)
            ->where(function ($q) use ($maxAgeMinutes) {
                $q->where('updated_at', '<', now()->subMinutes($maxAgeMinutes))
                    ->orWhere(function ($q2) use ($maxAgeMinutes) {
                        $q2->whereNull('updated_at')
                            ->where('created_at', '<', now()->subMinutes($maxAgeMinutes));
                    });
            })
            ->update([
                'breakup_status' => self::STATUS_FAILED,
                'updated_at' => now(),
            ]);
    }

    /**
     * @param  array{min_invoice_amount: float, max_invoice_amount: float, invoice_prefix: string, allow_decimal_qty?: bool}  $settings
     * @return array{success: bool, message?: string, job_id?: int}
     */
    public function startGeneration(int $purchaseId, array $settings, int $userId, bool $isRegenerate = false): array
    {
        if ($this->hasProcessingJob($purchaseId)) {
            if ($isRegenerate) {
                DB::connection('tenant')->table('purchase_breakup_jobs')
                    ->where('purchase_id', $purchaseId)
                    ->where('breakup_status', self::STATUS_PROCESSING)
                    ->update([
                        'breakup_status' => self::STATUS_FAILED,
                        'updated_at' => now(),
                    ]);
            } else {
                return ['success' => false, 'message' => 'A breakup job is already processing for this purchase. Use Regenerate if it is stuck.'];
            }
        }

        $ctx = $this->loadPurchaseContext($purchaseId);
        if (! $ctx) {
            $lineCount = $this->countActiveLines($purchaseId);
            if ($lineCount === 0) {
                return ['success' => false, 'message' => 'Purchase not found.'];
            }
            if ($lineCount > 1) {
                return ['success' => false, 'message' => 'Breakup is only available for purchases with exactly one line item.'];
            }

            return ['success' => false, 'message' => 'Purchase not found.'];
        }

        $line = $ctx['line'];
        $rate = (float) $line->Rate;
        $qty = (float) $line->Quantity;
        $amount = (float) ($line->Amount ?: ($qty * $rate));

        $minAmount = (float) ($settings['min_invoice_amount'] ?? 0);
        $maxAmount = (float) ($settings['max_invoice_amount'] ?? 0);
        $prefix = trim((string) ($settings['invoice_prefix'] ?? 'INV'));
        $allowDecimal = (bool) ($settings['allow_decimal_qty'] ?? false);

        if ($rate <= 0 || $qty <= 0) {
            return ['success' => false, 'message' => 'Purchase line must have positive quantity and rate.'];
        }

        if ($minAmount <= 0 || $maxAmount <= 0 || $minAmount > $maxAmount) {
            return ['success' => false, 'message' => 'Min and max invoice amounts must be positive and min must not exceed max.'];
        }

        if ($maxAmount > $amount) {
            return ['success' => false, 'message' => 'Max invoice amount cannot exceed the purchase line amount.'];
        }

        $minQty = (int) ceil($minAmount / $rate);
        $maxQty = (int) floor($maxAmount / $rate);

        if (! $allowDecimal) {
            if ($minQty < 1 || $maxQty < 1 || $minQty > $maxQty) {
                return ['success' => false, 'message' => 'Amount range does not produce a valid integer quantity range for this rate.'];
            }
        } else {
            $minQty = max(0.0001, $minAmount / $rate);
            $maxQty = max($minQty, $maxAmount / $rate);
        }

        $latest = $this->getLatestJob($purchaseId);
        $version = 1;
        if ($latest) {
            if (! $isRegenerate && (int) $latest->breakup_status === self::STATUS_GENERATED) {
                return ['success' => false, 'message' => 'Breakup already generated. Use Regenerate to create a new version.'];
            }
            if (! $isRegenerate && (int) $latest->breakup_status === self::STATUS_PROCESSING) {
                return ['success' => false, 'message' => 'Breakup is already processing.'];
            }
            $version = (int) $latest->version_no + 1;
        }

        $settingsPayload = [
            'min_invoice_amount' => $minAmount,
            'max_invoice_amount' => $maxAmount,
            'invoice_prefix' => $prefix,
            'allow_decimal_qty' => $allowDecimal,
            'min_qty' => $minQty,
            'max_qty' => $maxQty,
        ];

        $jobId = DB::connection('tenant')->table('purchase_breakup_jobs')->insertGetId([
            'purchase_id' => $purchaseId,
            'version_no' => $version,
            'breakup_status' => self::STATUS_PROCESSING,
            'settings_json' => json_encode($settingsPayload),
            'total_rows' => 0,
            'created_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $tenantContext = self::tenantContextFromSession();

        // Run inline so generation completes without a separate queue worker (XAMPP/local).
        GenerateBreakupJob::dispatchSync($jobId, $purchaseId, $tenantContext);

        $job = $this->getLatestJob($purchaseId);
        $status = $job ? (int) $job->breakup_status : self::STATUS_FAILED;

        if ($status === self::STATUS_FAILED) {
            return ['success' => false, 'message' => 'Breakup generation failed. Check logs and try again.'];
        }

        return [
            'success' => true,
            'job_id' => $jobId,
            'status' => $status,
        ];
    }

    /**
     * @param  array{tenant_id?: int, dbserver?: ?string, dbuser?: ?string, dbpassword?: ?string, dbname?: ?string}  $tenantContext
     */
    public function processJob(int $jobId, int $purchaseId, array $tenantContext): void
    {
        self::applyTenantContext($tenantContext);

        $this->excelColumnService->ensureDefaultColumns();

        $job = DB::connection('tenant')->table('purchase_breakup_jobs')->where('id', $jobId)->first();
        if (! $job || (int) $job->breakup_status !== self::STATUS_PROCESSING) {
            return;
        }

        try {
            $ctx = $this->loadPurchaseContext($purchaseId);
            if (! $ctx) {
                throw new \RuntimeException('Purchase must have exactly one active line item.');
            }

            $settings = json_decode((string) $job->settings_json, true) ?: [];
            $rows = $this->generateInvoiceRows($ctx, $settings, $jobId);
            $this->insertInvoiceRows($purchaseId, $jobId, $rows);

            $fileInfo = $this->buildExcelFile(
                $purchaseId,
                $jobId,
                $ctx['header']->DocNo ?? 'PUR',
                (int) $job->version_no,
                $tenantContext,
                $rows
            );

            DB::connection('tenant')->table('purchase_breakup_jobs')->where('id', $jobId)->update([
                'breakup_status' => self::STATUS_GENERATED,
                'total_rows' => count($rows),
                'file_name' => $fileInfo['file_name'],
                'file_path' => $fileInfo['file_path'],
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            DB::connection('tenant')->table('purchase_breakup_jobs')->where('id', $jobId)->update([
                'breakup_status' => self::STATUS_FAILED,
                'updated_at' => now(),
            ]);

            report($e);
        }
    }

    /**
     * @param  array{header: object, line: object}  $ctx
     * @param  array<string, mixed>  $settings
     * @return array<int, array<string, mixed>>
     */
    protected function generateInvoiceRows(array $ctx, array $settings, int $jobId): array
    {
        $header = $ctx['header'];
        $line = $ctx['line'];
        $rate = (float) $line->Rate;
        $totalQty = (float) $line->Quantity;
        $totalAmount = (float) ($line->Amount ?: ($totalQty * $rate));
        $allowDecimal = (bool) ($settings['allow_decimal_qty'] ?? false);
        $minQty = $allowDecimal ? (float) ($settings['min_qty'] ?? 1) : (int) ($settings['min_qty'] ?? 1);
        $maxQty = $allowDecimal ? (float) ($settings['max_qty'] ?? 1) : (int) ($settings['max_qty'] ?? 1);
        $prefix = trim((string) ($settings['invoice_prefix'] ?? 'INV'));
        $supplierName = (string) ($header->SupplierDisplayName ?? '');
        $productName = (string) ($line->ProductName ?? '');
        $purchaseDate = $header->PurchaseDate;

        $remainingQty = $totalQty;
        $rows = [];
        $seq = 1;
        $maxIterations = 50000;
        $iterations = 0;

        while ($remainingQty > 0.0000001 && $iterations < $maxIterations) {
            $iterations++;

            if ($remainingQty <= $maxQty + 0.0000001) {
                $qty = $remainingQty;
            } else {
                if ($allowDecimal) {
                    $qty = $minQty + (mt_rand() / mt_getrandmax()) * ($maxQty - $minQty);
                    $qty = round($qty, 4);
                    $qty = max($minQty, min($maxQty, $qty));
                } else {
                    $qty = mt_rand((int) $minQty, (int) $maxQty);
                }

                if ($qty > $remainingQty) {
                    $qty = $remainingQty;
                }

                if (! $allowDecimal && $remainingQty - $qty > 0 && $remainingQty - $qty < $minQty) {
                    $qty = $remainingQty;
                }
            }

            if ($qty <= 0) {
                $qty = $remainingQty;
            }

            $amount = round($qty * $rate, 2);
            $invoiceNo = $prefix.'-'.str_pad((string) $seq, 5, '0', STR_PAD_LEFT);

            $rows[] = [
                'invoice_no' => $invoiceNo,
                'invoice_date' => $purchaseDate,
                'supplier_name' => $supplierName,
                'product_name' => $productName,
                'qty' => $qty,
                'rate' => $rate,
                'amount' => $amount,
                'remarks' => null,
            ];

            $remainingQty -= $qty;
            $seq++;
        }

        $sumQty = array_sum(array_column($rows, 'qty'));
        $sumAmount = array_sum(array_column($rows, 'amount'));

        if (abs($sumQty - $totalQty) > 0.01) {
            throw new \RuntimeException('Generated quantity does not match purchase quantity.');
        }

        if (abs($sumAmount - $totalAmount) > 0.05) {
            $diff = round($totalAmount - $sumAmount, 2);
            if ($diff !== 0.0 && count($rows) > 0) {
                $last = count($rows) - 1;
                $rows[$last]['amount'] = round($rows[$last]['amount'] + $diff, 2);
            }
        }

        return $rows;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    protected function insertInvoiceRows(int $purchaseId, int $jobId, array $rows): void
    {
        $now = now();
        $chunks = array_chunk($rows, 500);

        foreach ($chunks as $chunk) {
            $insert = [];
            foreach ($chunk as $row) {
                $insert[] = [
                    'purchase_id' => $purchaseId,
                    'job_id' => $jobId,
                    'invoice_no' => $row['invoice_no'],
                    'invoice_date' => $row['invoice_date'],
                    'supplier_name' => $row['supplier_name'],
                    'product_name' => $row['product_name'],
                    'qty' => $row['qty'],
                    'rate' => $row['rate'],
                    'amount' => $row['amount'],
                    'remarks' => $row['remarks'],
                    'created_at' => $now,
                ];
            }
            DB::connection('tenant')->table('purchase_breakup_invoice_data')->insert($insert);
        }
    }

    /**
     * @param  array{tenant_id?: int}  $tenantContext
     * @param  array<int, array<string, mixed>>  $generatedRows
     * @return array{file_name: string, file_path: string}
     */
    protected function buildExcelFile(
        int $purchaseId,
        int $jobId,
        string $docNo,
        int $version,
        array $tenantContext,
        array $generatedRows
    ): array {
        $tenantId = (int) ($tenantContext['tenant_id'] ?? 0);
        $safeDoc = preg_replace('/[^A-Za-z0-9\-_]/', '_', $docNo) ?: 'purchase';
        $fileName = "{$safeDoc}-breakup-v{$version}.xlsx";
        $relativePath = 'purchase-breakup/'.$tenantId.'/'.$fileName;

        $exportRows = collect($generatedRows);
        if ($exportRows->isEmpty()) {
            $exportRows = $this->excelColumnService->getBreakupData($purchaseId, $jobId);
        }

        if ($exportRows->isEmpty()) {
            throw new \RuntimeException('No breakup rows available to export.');
        }

        Excel::store(
            new DynamicBreakupExport($exportRows, $this->excelColumnService),
            $relativePath,
            'local'
        );

        if (! Storage::disk('local')->exists($relativePath)) {
            throw new \RuntimeException('Excel file was not created at '.$relativePath);
        }

        return [
            'file_name' => $fileName,
            'file_path' => $relativePath,
        ];
    }

    /**
     * Rebuild Excel from DB (e.g. fix empty files from earlier runs).
     */
    public function rebuildExcelForPurchase(int $purchaseId): ?string
    {
        $job = $this->getLatestJob($purchaseId);
        if (! $job || (int) $job->breakup_status !== self::STATUS_GENERATED) {
            return null;
        }

        $data = $this->excelColumnService->getBreakupData($purchaseId, (int) $job->id);
        if ($data->isEmpty()) {
            return null;
        }

        $tenantContext = self::tenantContextFromSession();
        $header = DB::connection('tenant')->table('tbl_purchase_header')->where('ID', $purchaseId)->value('DocNo');

        $fileInfo = $this->buildExcelFile(
            $purchaseId,
            (int) $job->id,
            $header ?? 'PUR',
            (int) $job->version_no,
            $tenantContext,
            $data->map(fn ($row) => (array) $row)->all()
        );

        DB::connection('tenant')->table('purchase_breakup_jobs')->where('id', $job->id)->update([
            'file_name' => $fileInfo['file_name'],
            'file_path' => $fileInfo['file_path'],
            'updated_at' => now(),
        ]);

        return $this->resolveDownloadPath((object) array_merge((array) $job, $fileInfo));
    }

    public function resolveDownloadPath(object $job): ?string
    {
        $stored = trim((string) ($job->file_path ?? ''));
        $fileName = (string) ($job->file_name ?? '');
        $disk = Storage::disk('local');
        $normalized = str_replace('\\', '/', $stored);

        if ($stored !== '' && str_starts_with($normalized, 'purchase-breakup/') && $disk->exists($stored)) {
            return $disk->path($stored);
        }

        if ($stored !== '' && is_file($stored)) {
            return $stored;
        }

        $tenantId = (int) (session('selected_tenant.id') ?? 0);
        if ($fileName !== '' && $tenantId > 0) {
            $guess = 'purchase-breakup/'.$tenantId.'/'.$fileName;
            if ($disk->exists($guess)) {
                return $disk->path($guess);
            }
        }

        if ($stored !== '' && preg_match('#purchase-breakup/(\d+)/#', $normalized, $m) && $fileName !== '') {
            $guess = 'purchase-breakup/'.$m[1].'/'.$fileName;
            if ($disk->exists($guess)) {
                return $disk->path($guess);
            }
        }

        return null;
    }

    /**
     * @param  Collection<int, object>|array<int, object>  $oldLines
     * @param  Collection<int, object>|array<int, object>  $newLines
     */
    public function expireIfMaterialChange(int $purchaseId, $oldLines, $newLines): void
    {
        if ($this->linesMateriallyChanged($oldLines, $newLines)) {
            DB::connection('tenant')->table('purchase_breakup_jobs')
                ->where('purchase_id', $purchaseId)
                ->where('breakup_status', self::STATUS_GENERATED)
                ->update([
                    'breakup_status' => self::STATUS_EXPIRED,
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * @param  Collection<int, object>|array<int, object>  $oldLines
     * @param  Collection<int, object>|array<int, object>  $newLines
     */
    protected function linesMateriallyChanged($oldLines, $newLines): bool
    {
        $normalize = function ($lines) {
            $out = [];
            foreach ($lines as $l) {
                $out[] = [
                    'ProductID' => (int) ($l->ProductID ?? 0),
                    'Quantity' => round((float) ($l->Quantity ?? 0), 4),
                    'Rate' => round((float) ($l->Rate ?? 0), 4),
                ];
            }
            usort($out, fn ($a, $b) => $a['ProductID'] <=> $b['ProductID']);

            return $out;
        };

        return $normalize($oldLines) !== $normalize($newLines);
    }

    public function statusLabel(?int $status, bool $singleLine): string
    {
        if (! $singleLine) {
            return 'N/A';
        }

        return match ((int) ($status ?? self::STATUS_NOT_GENERATED)) {
            self::STATUS_PROCESSING => 'Processing',
            self::STATUS_GENERATED => 'Generated',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_EXPIRED => 'Expired',
            default => 'Not Generated',
        };
    }

    public function statusBadgeClass(?int $status, bool $singleLine): string
    {
        if (! $singleLine) {
            return 'bg-secondary-subtle text-secondary';
        }

        return match ((int) ($status ?? self::STATUS_NOT_GENERATED)) {
            self::STATUS_PROCESSING => 'bg-warning-subtle text-warning-emphasis',
            self::STATUS_GENERATED => 'bg-success-subtle text-success-emphasis',
            self::STATUS_FAILED => 'bg-danger-subtle text-danger-emphasis',
            self::STATUS_EXPIRED => 'bg-secondary-subtle text-secondary-emphasis',
            default => 'bg-light text-muted',
        };
    }
}

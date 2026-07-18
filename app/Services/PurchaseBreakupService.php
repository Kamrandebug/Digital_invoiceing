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
        ExcelColumnService::clearSchemaCaches();
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
     * @return array{customer_id: ?int, min_invoice_amount: ?float, max_invoice_amount: ?float, from_date: ?string, end_date: ?string, invoice_prefix: string, allow_decimal_qty: bool}|null
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
            'customer_id' => isset($decoded['customer_id']) ? (int) $decoded['customer_id'] : null,
            'min_invoice_amount' => isset($decoded['min_invoice_amount'])
                ? (float) $decoded['min_invoice_amount']
                : null,
            'max_invoice_amount' => isset($decoded['max_invoice_amount'])
                ? (float) $decoded['max_invoice_amount']
                : null,
            'from_date' => ! empty($decoded['from_date']) ? (string) $decoded['from_date'] : null,
            'end_date' => ! empty($decoded['end_date']) ? (string) $decoded['end_date'] : null,
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
     * @param  array{customer_id: int, customer_name?: string, min_invoice_amount: float, max_invoice_amount: float, from_date: string, end_date: string, invoice_prefix: string, allow_decimal_qty?: bool}  $settings
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
            'customer_id' => (int) ($settings['customer_id'] ?? 0),
            'customer_name' => trim((string) ($settings['customer_name'] ?? '')),
            'min_invoice_amount' => $minAmount,
            'max_invoice_amount' => $maxAmount,
            'from_date' => (string) ($settings['from_date'] ?? ''),
            'end_date' => (string) ($settings['end_date'] ?? ''),
            'invoice_prefix' => $prefix,
            'allow_decimal_qty' => $allowDecimal,
            'min_qty' => $minQty,
            'max_qty' => $maxQty,
            'doc2' => trim((string) ($settings['doc2'] ?? '')),
            'doc3' => trim((string) ($settings['doc3'] ?? '')),
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
        $doc2 = trim((string) ($settings['doc2'] ?? ''));
        $doc3 = trim((string) ($settings['doc3'] ?? ''));
        $partyName = $this->resolveBreakupCustomerName($settings);
        $productName = (string) ($line->ProductName ?? '');
        $fromDate = (string) ($settings['from_date'] ?? '');
        $endDate = (string) ($settings['end_date'] ?? '');
        $purchaseDate = $header->PurchaseDate ?? null;
        if ($fromDate === '' && $purchaseDate) {
            $fromDate = \Carbon\Carbon::parse($purchaseDate)->format('Y-m-d');
        }
        if ($endDate === '' && $fromDate !== '') {
            $endDate = $fromDate;
        }

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
                'DocNo2' => $doc2,
                'DocNo3' => $doc3,
                'invoice_date' => $this->randomInvoiceDate($fromDate, $endDate, $purchaseDate),
                'supplier_name' => $partyName,
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
                    'DocNo2' => $row['DocNo2'] ?? null,
                    'DocNo3' => $row['DocNo3'] ?? null,
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

        $package = $this->excelColumnService->buildExportPackage($purchaseId, $jobId);

        if ($package['rows']->isEmpty()) {
            throw new \RuntimeException('No breakup rows available to export.');
        }

        Excel::store(
            new DynamicBreakupExport($package['rows'], $package['columns'], $this->excelColumnService),
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

        $package = $this->excelColumnService->buildExportPackage($purchaseId, (int) $job->id);
        if ($package['rows']->isEmpty()) {
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
            $package['rows']->map(fn ($row) => (array) $row)->all()
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
                    'TotalValues' => round((float) ($l->TotalValues ?? 0), 2),
                ];
            }
            usort($out, fn ($a, $b) => $a['ProductID'] <=> $b['ProductID']);

            return $out;
        };

        return json_encode($normalize($oldLines)) !== json_encode($normalize($newLines));
    }

    protected function randomInvoiceDate(string $fromDate, string $endDate, mixed $fallbackDate = null): string
    {
        try {
            $from = \Carbon\Carbon::parse($fromDate)->startOfDay();
            $end = \Carbon\Carbon::parse($endDate)->startOfDay();
        } catch (\Throwable) {
            if ($fallbackDate) {
                return \Carbon\Carbon::parse($fallbackDate)->format('Y-m-d');
            }

            return now()->format('Y-m-d');
        }

        if ($end->lt($from)) {
            $end = $from->copy();
        }

        $days = (int) $from->diffInDays($end);
        if ($days <= 0) {
            return $from->format('Y-m-d');
        }

        return $from->copy()->addDays(mt_rand(0, $days))->format('Y-m-d');
    }

    /**
     * Customer selected in Generate Breakup modal (never the purchase supplier).
     *
     * @param  array<string, mixed>  $settings
     */
    protected function resolveBreakupCustomerName(array $settings): string
    {
        $name = trim((string) ($settings['customer_name'] ?? ''));
        if ($name !== '') {
            return $name;
        }

        $customerId = (int) ($settings['customer_id'] ?? 0);
        if ($customerId <= 0) {
            return '';
        }

        $customer = DB::connection('tenant')->table('tbl_customer')
            ->where('ID', $customerId)
            ->where('Status', 1)
            ->first();

        if (! $customer) {
            return '';
        }

        $display = trim((string) ($customer->BusinessName ?? ''));
        if ($display !== '') {
            return $display;
        }

        $display = trim((string) ($customer->DisplayName ?? ''));
        if ($display !== '') {
            return $display;
        }

        $display = trim(trim((string) ($customer->FirstName ?? '')).' '.trim((string) ($customer->LastName ?? '')));

        return $display !== '' ? $display : ('Customer #'.$customerId);
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

    /**
     * Post breakup invoices to FBR
     *
     * @return array{success: bool, message: string}
     */
    public function postBreakupToFbr(int $purchaseId, int $jobId, int $userId): array
    {
        $job = DB::connection('tenant')
            ->table('purchase_breakup_jobs')
            ->where('id', $jobId)
            ->first();

        if (! $job || (int) $job->breakup_status !== self::STATUS_GENERATED) {
            return ['success' => false, 'message' => 'No generated breakup found for the specified job.'];
        }

        // Get total rows to ensure there are records to post
        $rowCount = DB::connection('tenant')
            ->table('purchase_breakup_invoice_data')
            ->where('purchase_id', $purchaseId)
            ->where('job_id', $jobId)
            ->count();

        if ($rowCount === 0) {
            return ['success' => false, 'message' => 'No breakup invoice data rows found.'];
        }

        $settings = json_decode((string) ($job->settings_json ?? '{}'), true) ?: [];
        $customerId = (int) ($settings['customer_id'] ?? 0);
        if ($customerId <= 0) {
            return ['success' => false, 'message' => 'Invalid customer ID in breakup settings.'];
        }

        // Check if any invoice from this breakup was already posted/created to prevent double posting.
        $breakupInvoiceNos = DB::connection('tenant')
            ->table('purchase_breakup_invoice_data')
            ->where('purchase_id', $purchaseId)
            ->where('job_id', $jobId)
            ->pluck('invoice_no')
            ->filter()
            ->all();

        if (! empty($breakupInvoiceNos)) {
            $existingInvoicesCount = DB::connection('tenant')
                ->table('tbl_sales_invoice_header')
                ->whereIn('DocNo', $breakupInvoiceNos)
                ->where('Status', '!=', 2)
                ->count();

            if ($existingInvoicesCount > 0) {
                return [
                    'success' => false,
                    'message' => 'Some invoices from this breakup have already been posted. Please check the Invoices list.',
                ];
            }
        }

        // Resolve InvoiceType for "Sale Invoice"
        $invTypes = \App\Models\InvoiceModel::Load_InvoiceTypes();
        $saleInvType = $invTypes->first(function ($x) {
            return strcasecmp(trim($x->docDescription ?? ''), 'Sale Invoice') === 0;
        });
        $invoiceType = $saleInvType ? (int) $saleInvType->docTypeId : 4;

        // Generate a single RequestID for all these staging records
        $requestId = (string) \Illuminate\Support\Str::uuid();
        $now = now()->toDateTimeString();

        // Calculate the starting sequential InvoiceID/TID.
        $maxId = DB::connection('tenant')->table('tbl_sales_invoice_header')->max('ID');
        $startId = $maxId ? (int) $maxId + 1 : 1;

        try {
            // Set row numbering session variable
            DB::connection('tenant')->statement("SET @row_num := ?", [$startId - 1]);

            // Execute the bulk INSERT ... SELECT statement
            $sql = "
                INSERT INTO tbl_temp_invoice_detail (
                    InvoiceID,
                    TID,
                    CustomerID,
                    SaleOfTerm,
                    InvoiceDate,
                    DueDate,
                    TermDays,
                    InvoiceType,
                    DocNo,
                    DocNo2,
                    DocNo3,
                    ProductID,
                    Details,
                    ProductDescription,
                    HsCode,
                    UomID,
                    SaleTypeID,
                    SaleTypeToRateID,
                    SroScheduleID,
                    SroItemID,
                    TPRate,
                    Quantity,
                    Rate,
                    Amount,
                    DiscountPercentage,
                    DiscountAmount,
                    Discount,
                    GstPercentage,
                    GSTAmount,
                    SalesTaxWithheldAtSource,
                    ExtraTax,
                    FurtherTax,
                    FedPayable,
                    TotalValues,
                    Type,
                    Statusi,
                    CreatedBy,
                    CreatedOn,
                    UpdatedBy,
                    UpdatedOn,
                    RequestID
                )
                SELECT
                    (@row_num := @row_num + 1) AS InvoiceID,
                    @row_num AS TID,
                    :customer_id AS CustomerID,
                    NULL AS SaleOfTerm,
                    b.invoice_date AS InvoiceDate,
                    b.invoice_date AS DueDate,
                    0 AS TermDays,
                    :invoice_type AS InvoiceType,
                    b.invoice_no AS DocNo,
                    b.DocNo2 AS DocNo2,
                    b.DocNo3 AS DocNo3,
                    d.ProductID,
                    d.ProductDescription AS Details,
                    d.ProductDescription AS ProductDescription,
                    d.HsCode,
                    d.UomID,
                    d.SaleTypeID,
                    d.SaleTypeToRateID,
                    d.SroScheduleID,
                    d.SroItemID,
                    d.TPRate,
                    b.qty AS Quantity,
                    d.Rate AS Rate,
                    b.amount AS Amount,
                    d.DiscountPercentage,
                    ROUND(d.DiscountAmount * (b.qty / COALESCE(NULLIF(d.Quantity, 0), 1)), 2) AS DiscountAmount,
                    ROUND(d.DiscountAmount * (b.qty / COALESCE(NULLIF(d.Quantity, 0), 1)), 2) AS Discount,
                    d.GstPercentage,
                    ROUND(d.GSTAmount * (b.qty / COALESCE(NULLIF(d.Quantity, 0), 1)), 2) AS GSTAmount,
                    ROUND(d.SalesTaxWithheldAtSource * (b.qty / COALESCE(NULLIF(d.Quantity, 0), 1)), 2) AS SalesTaxWithheldAtSource,
                    ROUND(d.ExtraTax * (b.qty / COALESCE(NULLIF(d.Quantity, 0), 1)), 2) AS ExtraTax,
                    ROUND(d.FurtherTax * (b.qty / COALESCE(NULLIF(d.Quantity, 0), 1)), 2) AS FurtherTax,
                    ROUND(d.FedPayable * (b.qty / COALESCE(NULLIF(d.Quantity, 0), 1)), 2) AS FedPayable,
                    ROUND(d.TotalValues * (b.qty / COALESCE(NULLIF(d.Quantity, 0), 1)), 2) AS TotalValues,
                    'SI' AS Type,
                    1 AS Statusi,
                    :user_id AS CreatedBy,
                    :now_created AS CreatedOn,
                    :user_id_updated AS UpdatedBy,
                    :now_updated AS UpdatedOn,
                    :request_id AS RequestID
                FROM purchase_breakup_invoice_data b
                JOIN tbl_purchase_detail d ON d.PurchaseID = b.purchase_id
                WHERE b.purchase_id = :purchase_id
                  AND b.job_id = :job_id
                  AND d.Status != 2
                ORDER BY b.id
            ";

            DB::connection('tenant')->statement($sql, [
                'customer_id' => $customerId,
                'invoice_type' => $invoiceType,
                'user_id' => $userId,
                'now_created' => $now,
                'user_id_updated' => $userId,
                'now_updated' => $now,
                'request_id' => $requestId,
                'purchase_id' => $purchaseId,
                'job_id' => $jobId,
            ]);

            $saveResult = \App\Models\InvoiceModel::save_transaction($requestId);
            if (is_array($saveResult) && ($saveResult['status'] ?? false) === false) {
                throw new \RuntimeException('Save transaction failed: ' . ($saveResult['message'] ?? 'Unknown error'));
            }

        } catch (\Throwable $e) {
            DB::connection('tenant')->table('tbl_temp_invoice_detail')->where('RequestID', $requestId)->delete();
            return ['success' => false, 'message' => 'Database error while creating invoices: ' . $e->getMessage()];
        }

        // Fetch the generated invoice header IDs
        $invoiceIds = DB::connection('tenant')
            ->table('tbl_sales_invoice_header')
            ->whereIn('DocNo', $breakupInvoiceNos)
            ->where('Status', '!=', 2)
            ->pluck('ID')
            ->all();

        // Loop to post each sales invoice to FBR.
        $postedCount = 0;
        $failedInvoices = [];

        foreach ($invoiceIds as $invoiceId) {
            try {
                $fbrResult = \App\Models\InvoiceModel::post_to_fbr($invoiceId);
                $fbrResult['success'] = true;
                if ($fbrResult['success']) {
                    $postedCount++;
                } else {
                    $failedInvoices[] = "ID {$invoiceId}: " . ($fbrResult['message'] ?? 'Post failed');
                }
            } catch (\Throwable $ex) {
                $failedInvoices[] = "ID {$invoiceId}: " . $ex->getMessage();
            }
        }

        $successCount = count($invoiceIds);
        $message = "Successfully created {$successCount} sale invoices. {$postedCount} of them posted to FBR successfully.";
        if (count($failedInvoices) > 0) {
            $message .= " (" . count($failedInvoices) . " FBR postings failed. You can retry posting them from the Invoices list.)";
        }

        return [
            'success' => true,
            'message' => $message,
        ];
    }
}

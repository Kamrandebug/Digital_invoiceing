<?php

namespace App\Http\Controllers;

use App\Services\ExcelColumnService;
use App\Services\PurchaseBreakupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PurchaseBreakupController extends Controller
{
    public function __construct(
        protected PurchaseBreakupService $breakupService,
        protected ExcelColumnService $excelColumnService
    ) {}

    public function generate(Request $request, int $id)
    {
        return $this->dispatchGeneration($request, $id, false);
    }

    public function regenerate(Request $request, int $id)
    {
        return $this->dispatchGeneration($request, $id, true);
    }

    protected function dispatchGeneration(Request $request, int $id, bool $isRegenerate)
    {
        $validated = $request->validate([
            'customer_id' => 'required|integer|min:1',
            'min_invoice_amount' => 'required|numeric|min:0.01',
            'max_invoice_amount' => 'required|numeric|min:0.01|gte:min_invoice_amount',
            'from_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:from_date',
            'invoice_prefix' => 'required|string|max:50',
            'allow_decimal_qty' => 'sometimes|boolean',
        ]);

        $customer = DB::connection('tenant')->table('tbl_customer')
            ->where('ID', (int) $validated['customer_id'])
            ->where('Status', 1)
            ->first();

        if (! $customer) {
            return response()->json([
                'success' => false,
                'message' => 'Please select an active customer.',
            ], 422);
        }

        $settings = [
            'customer_id' => (int) $validated['customer_id'],
            'customer_name' => $this->resolveCustomerDisplayName($customer),
            'min_invoice_amount' => (float) $validated['min_invoice_amount'],
            'max_invoice_amount' => (float) $validated['max_invoice_amount'],
            'from_date' => $validated['from_date'],
            'end_date' => $validated['end_date'],
            'invoice_prefix' => $validated['invoice_prefix'],
            'allow_decimal_qty' => $request->boolean('allow_decimal_qty'),
        ];

        $result = $this->breakupService->startGeneration(
            $id,
            $settings,
            (int) $request->user()->id,
            $isRegenerate
        );

        if (! $result['success']) {
            return response()->json($result, 422);
        }

        return response()->json($result);
    }

    public function preview(int $id)
    {
        $job = $this->breakupService->getLatestJob($id);
        if (! $job || (int) $job->breakup_status !== PurchaseBreakupService::STATUS_GENERATED) {
            return response('<div class="alert alert-warning m-0">No generated breakup available for preview.</div>', 404);
        }

        $columns = $this->excelColumnService->getVisibleColumns();
        $rows = $this->excelColumnService->getBreakupData($id, (int) $job->id, 100);
        $total = (int) ($job->total_rows ?? 0);

        $totals = DB::connection('tenant')
            ->table('purchase_breakup_invoice_data')
            ->where('purchase_id', $id)
            ->where('job_id', (int) $job->id)
            ->selectRaw('COALESCE(SUM(qty), 0) as total_qty, COALESCE(SUM(amount), 0) as total_amount')
            ->first();

        return view('pages.dashboards.Purchase._breakup-preview-partial', [
            'columns' => $columns,
            'rows' => $rows,
            'total' => $total,
            'totalQty' => (float) ($totals->total_qty ?? 0),
            'totalAmount' => (float) ($totals->total_amount ?? 0),
        ]);
    }

    public function download(int $id): BinaryFileResponse|\Illuminate\Http\JsonResponse
    {
        $job = $this->breakupService->getLatestJob($id);
        if (! $job) {
            abort(404, 'Breakup not found.');
        }

        $status = (int) $job->breakup_status;
        if ($status === PurchaseBreakupService::STATUS_EXPIRED) {
            abort(403, 'Breakup has expired. Please regenerate.');
        }
        if ($status !== PurchaseBreakupService::STATUS_GENERATED) {
            abort(403, 'Breakup file is not available for download.');
        }

        $path = $this->breakupService->resolveDownloadPath($job);

        if (! $path || (is_file($path) && filesize($path) < 512)) {
            $path = $this->breakupService->rebuildExcelForPurchase($id);
        }

        if (! $path) {
            abort(404, 'Breakup file not found on disk. Try Regenerate.');
        }

        return response()->download($path, $job->file_name ?? 'breakup.xlsx');
    }

    public function settings(int $id)
    {
        $settings = $this->breakupService->getLastJobSettings($id);
        $job = $this->breakupService->getLatestJob($id);

        return response()->json([
            'purchase_id' => $id,
            'settings' => $settings,
            'version_no' => $job?->version_no,
            'breakup_status' => $job ? (int) $job->breakup_status : PurchaseBreakupService::STATUS_NOT_GENERATED,
        ]);
    }

    public function status(int $id)
    {
        $job = $this->breakupService->getLatestJob($id);
        $lineCount = $this->breakupService->countActiveLines($id);

        return response()->json([
            'purchase_id' => $id,
            'line_count' => $lineCount,
            'single_line' => $lineCount === 1,
            'breakup_status' => $job ? (int) $job->breakup_status : PurchaseBreakupService::STATUS_NOT_GENERATED,
            'job_id' => $job?->id,
            'version_no' => $job?->version_no,
            'total_rows' => $job?->total_rows,
            'status_label' => $this->breakupService->statusLabel(
                $job ? (int) $job->breakup_status : null,
                $lineCount === 1
            ),
        ]);
    }

    protected function resolveCustomerDisplayName(object $customer): string
    {
        $name = trim((string) ($customer->BusinessName ?? ''));
        if ($name !== '') {
            return $name;
        }

        $name = trim((string) ($customer->DisplayName ?? ''));
        if ($name !== '') {
            return $name;
        }

        $name = trim(trim((string) ($customer->FirstName ?? '')).' '.trim((string) ($customer->LastName ?? '')));

        return $name !== '' ? $name : ('Customer #'.(int) ($customer->ID ?? 0));
    }
}

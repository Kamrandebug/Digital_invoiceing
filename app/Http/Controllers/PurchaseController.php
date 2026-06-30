<?php

namespace App\Http\Controllers;

use App\Models\InvoiceModel;
use App\Models\PurchaseModel;
use App\Imports\PurchaseLedgerImport;
use App\Services\PurchaseBreakupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        $filters = [
            'search' => $request->input('search'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
        ];

        $rules = [];
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $rules['start_date'] = 'date';
            $rules['end_date'] = 'date|after_or_equal:start_date';
        }
        if ($request->filled('search')) {
            $rules['search'] = 'string|max:100';
        }
        if (! empty($rules)) {
            $request->validate($rules);
        }

        app(PurchaseBreakupService::class)->expireStaleProcessingJobs();

        $purchases = PurchaseModel::getlistingdata($filters)->paginate(15);

        $customers = DB::select(InvoiceModel::Load_Customer());

        return view('pages.dashboards.Purchase.purchase-list', compact('purchases', 'customers'));
    }

    public function create()
    {
        return view('pages.dashboards.Purchase.purchase-form', $this->formData('create'));
    }

    public function modal(Request $request, $id = null)
    {
        $mode = $request->query('mode', 'create');
        $data = $this->formData($mode, $id);

        if ($id && in_array($mode, ['edit', 'show']) && ! $data['purchase']) {
            return response('<div class="alert alert-danger m-3">Purchase not found.</div>', 404);
        }

        $data['inModal'] = true;

        return view('pages.dashboards.Purchase._purchase-form-partial', $data);
    }

    public function show($id)
    {
        $data = $this->formData('show', $id);
        if (! $data['purchase']) {
            abort(404, 'Purchase not found.');
        }
        $data['inModal'] = false;

        return view('pages.dashboards.Purchase.purchase-form', $data);
    }

    public function edit($id)
    {
        $data = $this->formData('edit', $id);
        if (! $data['purchase']) {
            abort(404, 'Purchase not found.');
        }
        $data['inModal'] = false;

        return view('pages.dashboards.Purchase.purchase-form', $data);
    }

    public function save_transaction(Request $request)
    {
        $request->validate([
            'SupplierID' => 'required|integer',
            'PurchaseDate' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.ProductID' => 'required|integer|min:1',
            'items.*.Quantity' => 'required|numeric|gt:0',
            'items.*.Rate' => 'required|numeric|min:0',
        ], [
            'SupplierID.required' => 'Please select a supplier.',
            'PurchaseDate.required' => 'Purchase date is required.',
            'items.required' => 'At least one line item is required.',
            'items.*.Quantity.gt' => 'Quantity must be greater than zero.',
        ]);

        try {
            $requestId = (string) Str::uuid();
            $userId = auth()->id() ?? 1;
            $now = now()->toDateTimeString();
            $docNo = $request->input('DocNo') ?: $this->generateDocNo();

            $maxId = (int) (DB::table('tbl_purchase_header')->max('ID') ?? 0);
            $nextId = $maxId + 1;

            $this->insertTempLines($request, $requestId, $nextId, $nextId, $docNo, $userId, $now, 1);

            $saveResult = PurchaseModel::save_transaction($requestId);
            if (is_array($saveResult) && ($saveResult['status'] ?? false) === false) {
                return $this->failResponse($request, $saveResult['message'] ?? 'Unable to save purchase.', 422);
            }

            return $this->successResponse($request, 'Purchase created successfully.');
        } catch (\Throwable $e) {
            \Log::error('Purchase save_transaction failed', ['error' => $e->getMessage()]);

            return $this->failResponse($request, 'Server error: '.$e->getMessage(), 500);
        }
    }

    public function edit_transaction(Request $request, $id)
    {
        $purchase = DB::table('tbl_purchase_header')
            ->where('ID', $id)
            ->where('Status', '!=', 2)
            ->first();

        if (! $purchase) {
            return $this->failResponse($request, 'Purchase not found.', 404);
        }

        $request->validate([
            'SupplierID' => 'required|integer',
            'PurchaseDate' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.ProductID' => 'required|integer|min:1',
            'items.*.Quantity' => 'required|numeric|gt:0',
            'items.*.Rate' => 'required|numeric|min:0',
        ]);

        try {
            $requestId = (string) Str::uuid();
            $userId = auth()->id() ?? 1;
            $now = now()->toDateTimeString();
            $docNo = $request->input('DocNo') ?: $purchase->DocNo;

            $this->insertTempLines($request, $requestId, (int) $id, (int) $id, $docNo, $userId, $now, (int) $request->input('Status', 1));

            $editResult = PurchaseModel::edit_transaction($requestId);
            if (is_array($editResult) && ($editResult['status'] ?? false) === false) {
                return $this->failResponse($request, $editResult['message'] ?? 'Unable to update purchase.', 422);
            }

            return $this->successResponse($request, 'Purchase updated successfully.');
        } catch (\Throwable $e) {
            \Log::error('Purchase edit_transaction failed', ['error' => $e->getMessage(), 'id' => $id]);

            return $this->failResponse($request, 'Server error: '.$e->getMessage(), 500);
        }
    }

    public function delete_transaction($id)
    {
        try {
            $purchase = DB::table('tbl_purchase_header')->where('ID', $id)->first();
            if (! $purchase) {
                return redirect()->back()->with('error', 'Purchase not found.');
            }

            $requestId = (string) Str::uuid();
            $userId = auth()->id() ?? 1;
            $now = now()->toDateTimeString();

            DB::table('tbl_temp_purchase_detail')->insert([
                'TID' => $id,
                'PurchaseID' => $id,
                'DocNo' => $purchase->DocNo ?? '',
                'Quantity' => 0,
                'Rate' => 0,
                'Status' => 2,
                'CreatedBy' => (int) ($purchase->CreatedBy ?? $userId),
                'CreatedOn' => $purchase->CreatedOn ?? $now,
                'UpdatedBy' => $userId,
                'UpdatedOn' => $now,
                'RequestID' => $requestId,
            ]);

            PurchaseModel::delete_transaction($requestId);

            return redirect()->route('purchases.index')->with('success', 'Purchase archived successfully.');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Error archiving purchase: '.$e->getMessage());
        }
    }

    public function exportCSV(Request $request)
    {
        $filename = 'purchases-export-'.date('Y-m-d-His').'.csv';
        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $purchases = PurchaseModel::getlistingdata([
            'search' => $request->input('search'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
        ])->get();

        $callback = function () use ($purchases) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($file, ['Doc No', 'Supplier', 'Purchase Date', 'Sub Total', 'Discount', 'Total GST', 'Total Amount', 'Status', 'Created Date']);

            foreach ($purchases as $row) {
                fputcsv($file, [
                    $row->DocNo,
                    $row->SupplierDisplayName,
                    $row->PurchaseDate,
                    number_format($row->SubTotal ?? 0, 2),
                    number_format($row->Discount ?? 0, 2),
                    number_format($row->TotalGST ?? $row->SalesTaxApplicable ?? 0, 2),
                    number_format($row->TotalAmount ?? 0, 2),
                    $row->Status == 1 ? 'Active' : 'Archived',
                    $row->CreatedOn,
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function downloadImportTemplate(): BinaryFileResponse
    {
        $path = public_path('assets/templates/Purchase_Ledger.xlsx');

        if (! is_file($path)) {
            abort(404, 'Import template not found.');
        }

        return response()->download($path, 'Purchase_Ledger.xlsx');
    }

    public function importPurchases(Request $request)
    {
        $request->validate([
            'import_file' => 'required|file|mimes:xlsx,xls|max:5120',
        ], [
            'import_file.required' => 'Please select an Excel file to import.',
            'import_file.mimes' => 'Import file must be an Excel workbook (.xlsx or .xls).',
            'import_file.max' => 'Import file must not exceed 5 MB.',
        ]);

        try {
            $requestId = (string) Str::uuid();
            $userId = auth()->id() ?? 1;
            $now = now()->toDateTimeString();

            $import = new PurchaseLedgerImport;
            Excel::import($import, $request->file('import_file'));
            $rows = $import->mappedRows();

            if (empty($rows)) {
                return $this->failResponse($request, 'No data rows found in the import file.', 422);
            }

            $insertRows = [];
            foreach ($rows as $row) {
                $insertRows[] = array_merge($row, [
                    'RequestID' => $requestId,
                    'ErrMsg' => null,
                    'CreatedBy' => $userId,
                    'CreatedOn' => $now,
                    'UpdatedBy' => $userId,
                    'UpdatedOn' => $now,
                ]);
            }

            foreach (array_chunk($insertRows, 200) as $chunk) {
                DB::table('tbl_temp_purchase_importtable')->insert($chunk);
            }

            $saveResult = PurchaseModel::save_import_data($requestId);
            if (is_array($saveResult) && ($saveResult['status'] ?? false) === false) {
                return $this->failResponse($request, $saveResult['message'] ?? 'Unable to import purchases.', 422);
            }

            $message = count($rows).' purchase row(s) imported successfully.';
            session()->flash('success', $message);

            return $this->successResponse($request, $message);
        } catch (\Throwable $e) {
            \Log::error('Purchase import failed', ['error' => $e->getMessage()]);

            return $this->failResponse($request, 'Import failed: '.$e->getMessage(), 500);
        }
    }

    private function insertTempLines(
        Request $request,
        string $requestId,
        int $tid,
        int $purchaseId,
        string $docNo,
        int $userId,
        string $now,
        int $status
    ): void {
        foreach ($request->items as $item) {
            $qty = (float) ($item['Quantity'] ?? 0);
            $rate = (float) ($item['Rate'] ?? 0);
            $amount = isset($item['Amount']) ? (float) $item['Amount'] : round($qty * $rate, 2);

            DB::table('tbl_temp_purchase_detail')->insert([
                'TID' => $tid,
                'PurchaseID' => $purchaseId,
                'SupplierID' => (int) $request->SupplierID,
                'PurchaseDate' => $request->PurchaseDate,
                'DocNo' => (string) $docNo,
                'ProductID' => (int) ($item['ProductID'] ?? 0),
                'ProductDescription' => $item['Details'] ?? ($item['ProductDescription'] ?? null),
                'HsCode' => $item['HsCode'] ?? null,
                'UomID' => $item['UomID'] ?? null,
                'SaleTypeID' => $item['SaleTypeID'] ?? null,
                'SaleTypeToRateID' => $item['SaleTypeToRateID'] ?? null,
                'SroScheduleID' => $item['SroScheduleID'] ?? null,
                'SroItemID' => $item['SroItemID'] ?? null,
                'RateType' => $item['RateType'] ?? null,
                'TPRate' => $item['TPRate'] ?? 0,
                'Quantity' => $qty,
                'Rate' => $rate,
                'Amount' => $amount,
                'DiscountPercentage' => $item['DiscountPercentage'] ?? '0',
                'DiscountAmount' => $item['DiscountAmount'] ?? 0,
                'GstPercentage' => $item['GstPercentage'] ?? '0',
                'GSTAmount' => $item['GSTAmount'] ?? 0,
                'ValueSalesExcludingST' => $amount,
                'FixedNotifiedValueOrRetailPrice' => $item['FixedNotifiedValueOrRetailPrice'] ?? (round((float) ($item['TPRate'] ?? 0) * $qty, 2)),
                'SalesTaxApplicable' => $item['GSTAmount'] ?? 0,
                'SalesTaxWithheldAtSource' => $item['SalesTaxWithheldAtSource'] ?? ($request->SalesTaxWithheldAtSource ?? 0),
                'ExtraTax' => $item['ExtraTax'] ?? ($request->ExtraTax ?? 0),
                'FurtherTax' => $item['FurtherTax'] ?? ($request->FurtherTax ?? 0),
                'FedPayable' => $item['FedPayable'] ?? ($request->FedPayable ?? 0),
                'TotalValues' => $item['Total'] ?? 0,
                'Notes' => $request->Notes ?? null,
                'Status' => $status,
                'CreatedBy' => $userId,
                'CreatedOn' => $now,
                'UpdatedBy' => $userId,
                'UpdatedOn' => $now,
                'RequestID' => $requestId,
                'ErrMsg' => null,
            ]);
        }
    }

    /** @return array<string, mixed> */
    private function formData(string $mode, int|string|null $id = null): array
    {
        $purchase = null;
        $items = collect();

        if ($id && in_array($mode, ['edit', 'show'])) {
            $purchase = DB::table('tbl_purchase_header as h')
                ->leftJoin('tbl_customer as sup', 'h.SupplierID', '=', 'sup.ID')
                ->select(
                    'h.*',
                    PurchaseModel::sqlSupplierDisplayName(),
                    DB::raw('COALESCE(sup.IsAdvanceTax, 0) as SupplierIsAdvanceTax'),
                    DB::raw('COALESCE(sup.AdvanceTax, 0) as SupplierAdvanceTax')
                )
                ->where('h.ID', $id)
                ->where('h.Status', '!=', 2)
                ->first();

            if ($purchase) {
                $purchaseId = $purchase->PurchaseID ?? $purchase->ID;
                $items = DB::table('tbl_purchase_detail as d')
                    ->leftJoin('tbl_product as p', 'd.ProductID', '=', 'p.ID')
                    ->select('d.*', 'p.Name as ProductName', DB::raw('d.TotalValues as Total'))
                    ->where('d.PurchaseID', $purchaseId)
                    ->where('d.Status', '!=', 2)
                    ->get();
            }
        }

        return [
            'mode' => $mode,
            'purchase' => $purchase,
            'items' => $items,
            'suppliers' => DB::select(PurchaseModel::Load_Supplier()),
            'products' => DB::select(PurchaseModel::Load_Product()),
            'inModal' => false,
        ];
    }

    private function generateDocNo(): string
    {
        $last = DB::table('tbl_purchase_header')
            ->where('DocNo', 'like', 'PUR-%')
            ->orderByDesc('ID')
            ->value('DocNo');

        $num = 1;
        if ($last && preg_match('/PUR-(\d+)/', (string) $last, $m)) {
            $num = (int) $m[1] + 1;
        }

        return 'PUR-'.str_pad((string) $num, 6, '0', STR_PAD_LEFT);
    }

    private function successResponse(Request $request, string $message)
    {
        if ($request->header('X-Requested-With') === 'XMLHttpRequest' || $request->expectsJson()) {
            return response()->json(['status' => 'success', 'message' => $message]);
        }

        return redirect()->route('purchases.index')->with('success', $message);
    }

    private function failResponse(Request $request, string $message, int $code = 422)
    {
        if ($request->header('X-Requested-With') === 'XMLHttpRequest' || $request->expectsJson()) {
            return response()->json(['status' => 'error', 'message' => $message], $code);
        }

        return redirect()->back()->with('error', $message)->withInput();
    }
}

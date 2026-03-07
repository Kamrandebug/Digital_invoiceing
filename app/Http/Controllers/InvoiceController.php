<?php

namespace App\Http\Controllers;

use App\Models\InvoiceModel;
use App\Models\Customer; // Added
use App\Models\ProductModel; // Added
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InvoiceController extends Controller
{
    // ══════════════════════════════════════════════════════════
    //  INDEX — Listing from tbl_sales_invoice_header
    // ══════════════════════════════════════════════════════════

    public function index(Request $request)
    {
        $filters = [
            'search'     => $request->input('search'),
            'start_date' => $request->input('start_date'),
            'end_date'   => $request->input('end_date'),
        ];

        $rules    = [];
        $messages = [];

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $rules['start_date'] = 'date';
            $rules['end_date']   = 'date|after_or_equal:start_date';
            $messages['end_date.after_or_equal'] = 'End date must be after or equal to start date.';
        }

        if ($request->filled('search')) {
            $rules['search']        = 'string|max:100';
            $messages['search.max'] = 'Search term is too long.';
        }

        if (!empty($rules)) {
            $request->validate($rules, $messages);
        }

        $invoices = InvoiceModel::getlistingdata($filters)->paginate(15);

        return view('pages.dashboards.Invoice.invoice-list', compact('invoices'));
    }

    // ══════════════════════════════════════════════════════════
    //  CREATE — Load form with dropdowns
    // ══════════════════════════════════════════════════════════

    public function create()
    {
        // Use InvoiceModel helpers (raw SQL) to load dropdown data
        $customerSql = InvoiceModel::Load_Customer();
        $productSql  = InvoiceModel::Load_Product();

        return view('pages.dashboards.Invoice._invoice-form-partial', [
            'mode'         => 'create',
            'invoice'      => null,
            'items'        => collect(),
            'invoiceTypes' => InvoiceModel::Load_InvoiceTypes(),
            'customers'    => DB::select($customerSql),
            'products'     => DB::select($productSql),
            'inModal'      => false,
        ]);
    }

    // ══════════════════════════════════════════════════════════
    //  MODAL — AJAX load (create | edit | show)
    // ══════════════════════════════════════════════════════════

    public function modal(Request $request, $id = null)
    {
        $mode    = $request->query('mode', 'create');
        $invoice = null;
        $items   = collect();

        if ($id && in_array($mode, ['edit', 'show'])) {

            $invoice = DB::table('tbl_sales_invoice_header as h')
                ->leftJoin('tbl_customer as cust', 'h.CustomerID', '=', 'cust.ID')
                ->select(
                    'h.*',
                    DB::raw("
                        COALESCE(NULLIF(TRIM(cust.DisplayName),''),
                            CONCAT(COALESCE(cust.FirstName,''),' ',COALESCE(cust.LastName,''))
                        ) AS CustomerDisplayName
                    ")
                )
                ->where('h.ID', $id)
                ->where('h.Status', '!=', 2)
                ->first();

            if (!$invoice) {
                return response('<div class="alert alert-danger m-3">Invoice not found.</div>', 404);
            }

            $items = DB::table('tbl_sales_invoice_detail as d')
                ->leftJoin('tbl_product as p', 'd.ProductID', '=', 'p.ID')
                ->select('d.*', 'p.Name as ProductName')
                ->where('d.InvoiceID', $id)
                ->where('d.Status', '!=', 2)
                ->get();
        }

        // Use InvoiceModel helpers (raw SQL) to load dropdown data
        $customerSql = InvoiceModel::Load_Customer();
        $productSql  = InvoiceModel::Load_Product();

        return view('pages.dashboards.Invoice._invoice-form-partial', [
            'mode'         => $mode,
            'invoice'      => $invoice,
            'items'        => $items,
            'invoiceTypes' => InvoiceModel::Load_InvoiceTypes(),
            'customers'    => DB::select($customerSql),
            'products'     => DB::select($productSql),
            'inModal'      => true,
        ]);
    }

    // ══════════════════════════════════════════════════════════
    //  SHOW
    // ══════════════════════════════════════════════════════════

    public function show($id)
    {
        $invoice = DB::table('tbl_sales_invoice_header')
            ->where('ID', $id)
            ->where('Status', '!=', 2)
            ->first();

        if (!$invoice) {
            abort(404, 'Invoice not found.');
        }

        $items = DB::table('tbl_sales_invoice_detail as d')
            ->leftJoin('tbl_product as p', 'd.ProductID', '=', 'p.ID')
            ->select('d.*', 'p.Name as ProductName')
            ->where('d.InvoiceID', $id)
            ->where('d.Status', '!=', 2)
            ->get();

        $customerSql = InvoiceModel::Load_Customer();
        $productSql  = InvoiceModel::Load_Product();

        return view('pages.dashboards.Invoice._invoice-form-partial', [
            'mode'         => 'show',
            'invoice'      => $invoice,
            'items'        => $items,
            'invoiceTypes' => InvoiceModel::Load_InvoiceTypes(),
            'customers'    => DB::select($customerSql),
            'products'     => DB::select($productSql),
            'inModal'      => false,
        ]);
    }

    // ══════════════════════════════════════════════════════════
    //  EDIT — Load form with existing data
    // ══════════════════════════════════════════════════════════

    public function edit($id)
    {
        $invoice = DB::table('tbl_sales_invoice_header')
            ->where('ID', $id)
            ->where('Status', '!=', 2)
            ->first();

        if (!$invoice) {
            abort(404, 'Invoice not found.');
        }

        $items = DB::table('tbl_sales_invoice_detail as d')
            ->leftJoin('tbl_product as p', 'd.ProductID', '=', 'p.ID')
            ->select('d.*', 'p.Name as ProductName')
            ->where('d.InvoiceID', $id)
            ->where('d.Status', '!=', 2)
            ->get();

        $customerSql = InvoiceModel::Load_Customer();
        $productSql  = InvoiceModel::Load_Product();

        return view('pages.dashboards.Invoice._invoice-form-partial', [
            'mode'         => 'edit',
            'invoice'      => $invoice,
            'items'        => $items,
            'invoiceTypes' => InvoiceModel::Load_InvoiceTypes(),
            'customers'    => DB::select($customerSql),
            'products'     => DB::select($productSql),
            'inModal'      => false,
        ]);
    }

    // ══════════════════════════════════════════════════════════
    //  SAVE TRANSACTION — Create new invoice
    // ══════════════════════════════════════════════════════════

    public function save_transaction(Request $request)
{
    // ✅ Validate first
    $request->validate([
        'CustomerID'       => 'required|integer',
        'InvoiceDate'      => 'required|date',
        'items'            => 'required|array|min:1',
        'items.*.Quantity' => 'required|numeric|min:0',
        'items.*.Rate'     => 'required|numeric|min:0',
    ], [
        'CustomerID.required'    => 'Please select a customer.',
        'InvoiceDate.required'   => 'Invoice date is required.',
        'items.required'         => 'At least one line item is required.',
        'items.min'              => 'At least one line item is required.',
    ]);

    try {
        $requestId = (string) Str::uuid();
        $userId    = auth()->id() ?? 1;
        $now       = now()->toDateTimeString();
        $docNo     = $request->input('DocNo') ?: $this->generateDocNo();

        // 1. Calculate Next ID
        $maxId = DB::table('tbl_sales_invoice_header')->max('ID');
        $nextId = $maxId ? $maxId + 1 : 1;

        // 2. Insert each line item into temp with RequestID (Includes Header Info)
        $docNo2 = $request->input('DocNo2');
        $docNo3 = $request->input('DocNo3');
            foreach ($request->items as $item) {
            DB::table('tbl_temp_invoice_detail')->insert([
                'InvoiceID'          => $nextId,
                'TID'                => $nextId,
                'CustomerID'         => (int) $request->CustomerID,
                'InvoiceDate'        => $request->InvoiceDate,
                'DueDate'            => $request->InvoiceDate,
                'TermDays'           => 0,
                'InvoiceType'        => $request->input('InvoiceType'),
                'DocNo'              => (string) ($docNo ?? ''),
                'DocNo2'             => $docNo2 !== null ? (string) $docNo2 : '',
                'DocNo3'             => $docNo3 !== null ? (string) $docNo3 : '',
                'ProductID'          => $item['ProductID']         ?? 0,
                'Details'            => $item['Details']           ?? null,
                'ProductDescription' => $item['Details']           ?? null,
                'HsCode'             => $item['HsCode']            ?? null,
                'UomID'              => $item['UomID']             ?? null,
                'SaleTypeID'         => $item['SaleTypeID']         ?? null,
                'SaleTypeToRateID'   => $item['SaleTypeToRateID']  ?? null,
                'SroScheduleID'      => $item['SroScheduleID']     ?? null,
                'SroItemID'          => $item['SroItemID']         ?? null,
                'Quantity'           => $item['Quantity'],
                'Rate'               => $item['Rate'],
                'Amount'             => $item['Amount']            ?? 0,
                'DiscountPercentage' => $item['DiscountPercentage'] ?? '0',
                'DiscountAmount'     => $item['DiscountAmount']    ?? 0,
                'GstPercentage'      => $item['GstPercentage']     ?? '0',
                'GSTAmount'          => $item['GSTAmount']         ?? 0,
                'Total'              => $item['Total']             ?? 0,
                // 'SubTotal'           => $request->SubTotal         ?? 0,
                'Notes'              => $request->Notes            ?? null,
                'DiscountAllowed'    => $request->DiscountAllowed  ?? '0',
                'Type'               => 'SI',
                'Status'             => 1,
                'CreatedBy'          => $userId,
                'CreatedOn'          => $now,
                'UpdatedBy'          => $userId,
                'UpdatedOn'          => $now,
                'RequestID'          => $requestId,
            ]);
        }

        // 3. Execute model transaction (inserts header + detail from temp; no return)
        InvoiceModel::save_transaction($requestId);

        // 4. Clean temp
        // DB::table('tbl_temp_invoice_detail')->where('RequestID', $requestId)->delete();

        if ($request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json(['status' => 'success', 'message' => 'Invoice created successfully.']);
        }
        return redirect()->route('invoices.index')->with('success', 'Invoice created successfully.');

    } catch (\Exception $e) {
        \Log::error('Invoice save_transaction failed', ['error' => $e->getMessage()]);

        if ($request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()], 500);
        }
        return redirect()->back()->with('error', 'Error: ' . $e->getMessage())->withInput();
    }
}
    // ══════════════════════════════════════════════════════════
    //  EDIT TRANSACTION — Update existing invoice
    // ══════════════════════════════════════════════════════════

  public function edit_transaction(Request $request, $id)
{
    // 1. Check invoice exists
    $invoice = DB::table('tbl_sales_invoice_header')
        ->where('ID', $id)
        ->where('Status', '!=', 2)
        ->first();

    if (!$invoice) {
        if ($request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json(['status' => 'error', 'message' => 'Invoice not found.'], 404);
        }
        return redirect()->back()->with('error', 'Invoice not found.');
    }

    // 2. ✅ Validate (newly added)
    $request->validate([
        'CustomerID'       => 'required|integer',
        'InvoiceDate'      => 'required|date',
        'Status'           => 'required|in:1,2',
        'items'            => 'required|array|min:1',
        'items.*.Quantity' => 'required|numeric|min:0',
        'items.*.Rate'     => 'required|numeric|min:0',
    ]);

    // 3. ✅ Only try block — the correct one (was lines 412-498)
    try {
        $requestId = (string) Str::uuid();
        $userId    = auth()->id() ?? 1;
        $now       = now()->toDateTimeString();
        $docNo     = $request->input('DocNo') ?: $invoice->DocNo;

        // 2. Insert new line items into temp (Wait, we should insert header updates too!)
        // The Model's edit_transaction UPDATEs the header FROM the temp table.
        // So we must populate the temp table with the NEW header values.
        $docNo2 = $request->input('DocNo2');
        $docNo3 = $request->input('DocNo3');
        foreach ($request->items as $item) {
            DB::table('tbl_temp_invoice_detail')->insert([
                'InvoiceID'          => $id,
                'TID'                => $id, // CRITICAL for Model Join
                'CustomerID'         => (int) $request->CustomerID,
                'InvoiceDate'        => $request->InvoiceDate,
                'DueDate'            => $request->InvoiceDate,
                'TermDays'           => 0,
                'InvoiceType'        => $request->input('InvoiceType'),
                'DocNo'              => (string) ($docNo ?? ''),
                'DocNo2'             => $docNo2 !== null ? (string) $docNo2 : '',
                'DocNo3'             => $docNo3 !== null ? (string) $docNo3 : '',
                'ProductID'          => $item['ProductID']         ?? 0,
                'Details'            => $item['Details']           ?? null,
                'ProductDescription' => $item['Details']           ?? null,
                'HsCode'             => $item['HsCode']            ?? null,
                'UomID'              => $item['UomID']             ?? null,
                'SaleTypeID'         => $item['SaleTypeID']         ?? null,
                'SaleTypeToRateID'   => $item['SaleTypeToRateID']  ?? null,
                'SroScheduleID'      => $item['SroScheduleID']     ?? null,
                'SroItemID'          => $item['SroItemID']         ?? null,
                'Quantity'           => $item['Quantity'],
                'Rate'               => $item['Rate'],
                'Amount'             => $item['Amount']            ?? 0,
                'DiscountPercentage' => $item['DiscountPercentage'] ?? '0',
                'DiscountAmount'     => $item['DiscountAmount']    ?? 0,
                'GstPercentage'      => $item['GstPercentage']     ?? '0',
                'GSTAmount'          => $item['GSTAmount']         ?? 0,
                'Total'              => $item['Total']             ?? 0,
                //'SubTotal'           => $request->SubTotal         ?? 0,
                'Notes'              => $request->Notes            ?? null,
                'DiscountAllowed'    => $request->DiscountAllowed  ?? '0',
                'Type'               => 'SI',
                'Status'             => $request->Status,
                'CreatedBy'          => $userId,
                'CreatedOn'          => $now,
                'UpdatedBy'          => $userId,
                'UpdatedOn'          => $now,
                'RequestID'          => $requestId,
            ]);
        }

        // 2a. Since Model::edit_transaction ONLY updates the header,
        // we must manually handle the details replacement here in the controller
        // OR rely on the model to do it (which it currently doesn't).
        // Assuming we must fix the logic to work with the *current* model limitations:
        
        // Clear old details
        DB::table('tbl_sales_invoice_detail')->where('InvoiceID', $id)->delete();

        // Insert new details directly (mirroring what save_transaction does in Model)
        foreach ($request->items as $item) {
            DB::table('tbl_sales_invoice_detail')->insert([
                'InvoiceID'          => $id,
                'InvoiceDate'        => $request->InvoiceDate,
                'CustomerID'         => $request->CustomerID,
                'ProductID'          => $item['ProductID']         ?? 0,
                'Details'            => $item['Details']           ?? null,
                'HsCode'             => $item['HsCode']            ?? null,
                'UomID'              => $item['UomID']             ?? null,
                'SaleTypeID'         => $item['SaleTypeID']         ?? null,
                'SaleTypeToRateID'   => $item['SaleTypeToRateID']  ?? null,
                'SroScheduleID'      => $item['SroScheduleID']     ?? null,
                'SroItemID'          => $item['SroItemID']         ?? null,
                'Quantity'           => $item['Quantity'],
                'Rate'               => $item['Rate'],
                'Amount'             => $item['Amount']            ?? 0,
                'DiscountPercentage' => $item['DiscountPercentage'] ?? '0',
                'DiscountAmount'     => $item['DiscountAmount']    ?? 0,
                'GstPercentage'      => $item['GstPercentage']     ?? '0',
                'GSTAmount'          => $item['GSTAmount']         ?? 0,
                'Type'               => 'SI',
                'Status'             => 1,
                'CreatedBy'          => $userId,
                'CreatedOn'          => $now,
                'UpdatedBy'          => $userId,
                'UpdatedOn'          => $now,
                'UMQuantity'         => 1,
                'UMRate'             => 1,
                'UMFactorRate'       => 1,
                'Unit'               => $item['Unit'] ?? 0,
            ]);
        }

        // Call model (executes update inside model)
        InvoiceModel::edit_transaction($requestId);

        if ($request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json(['status' => 'success', 'message' => 'Invoice updated successfully.']);
        }
        return redirect()->route('invoices.index')->with('success', 'Invoice updated successfully.');

    } catch (\Exception $e) {
        \Log::error('Invoice edit_transaction failed', ['error' => $e->getMessage(), 'id' => $id]);

        if ($request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()], 500);
        }
        return redirect()->back()->with('error', 'Error: ' . $e->getMessage())->withInput();
    }
}

    // ══════════════════════════════════════════════════════════
    //  DELETE TRANSACTION — Soft delete invoice
    // ══════════════════════════════════════════════════════════

    public function delete_transaction($id)
    {
        try {
    $invoice = DB::table('tbl_sales_invoice_header')->where('ID', $id)->first();
    if (!$invoice) return redirect()->back()->with('error', 'Invoice not found.');

    $requestId = (string) Str::uuid();
    $userId    = auth()->id() ?? 1;
    $now       = now()->toDateTimeString();

    // Insert a row into tbl_temp_invoice_detail so model's delete_transaction can read it (same pattern as CustomerController)
    DB::table('tbl_temp_invoice_detail')->insert([
        'InvoiceID'  => $id,
        'TID'        => $id,
        'DocNo'      => $invoice->DocNo ?? '',
        'Quantity'   => 0,
        'Rate'       => 0,
        'Status'     => 2,
        'CreatedBy'  => (int)($invoice->CreatedBy ?? $userId),
        'CreatedOn'  => $invoice->CreatedOn ?? $now,
        'UpdatedBy'  => $userId,
        'UpdatedOn'  => $now,
        'RequestID'  => $requestId,
    ]);

    InvoiceModel::delete_transaction($requestId);

    return redirect()->route('invoices.index')->with('success', 'Invoice archived successfully.');

}catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error archiving invoice: ' . $e->getMessage());
        }
    }

    // ══════════════════════════════════════════════════════════
    //  EXPORT CSV
    // ══════════════════════════════════════════════════════════

    public function exportCSV(Request $request)
    {
        $filename = 'invoices-export-' . date('Y-m-d-His') . '.csv';

        $headers = [
            'Content-type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $invoices = InvoiceModel::getlistingdata([
            'search'     => $request->input('search'),
            'start_date' => $request->input('start_date'),
            'end_date'   => $request->input('end_date'),
        ])->get();

        $callback = function () use ($invoices) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM

            fputcsv($file, [
                'Doc No', 'Customer', 'Invoice Date', 'Due Date',
                'Sub Total', 'Total Discount', 'Total GST', 'Total Amount',
                'Status', 'Created Date',
            ]);

            foreach ($invoices as $inv) {
                fputcsv($file, [
                    $inv->DocNo,
                    $inv->CustomerDisplayName,
                    $inv->InvoiceDate,
                    $inv->DueDate,
                    number_format($inv->SubTotal      ?? 0, 2),
                    number_format($inv->TotalDiscount ?? 0, 2),
                    number_format($inv->TotalGST      ?? 0, 2),
                    number_format($inv->TotalAmount   ?? 0, 2),
                    $inv->Status == 1 ? 'Active' : 'Archived',
                    $inv->CreatedOn,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ══════════════════════════════════════════════════════════
    //  PRIVATE HELPER — Auto-generate Doc No
    // ══════════════════════════════════════════════════════════

   private function generateDocNo(): string
{
    return DB::transaction(function () {

        // Lock the last row so no other request can read it simultaneously
        $last = DB::table('tbl_sales_invoice_header')
            ->whereNotNull('DocNo')
            ->orderByDesc('ID')
            ->lockForUpdate()
            ->value('DocNo');

        $next = ($last && preg_match('/(\d+)$/', $last, $m))
            ? (int) $m[1] + 1
            : 1;

        return 'INV-' . str_pad($next, 6, '0', STR_PAD_LEFT);
    });
}
}
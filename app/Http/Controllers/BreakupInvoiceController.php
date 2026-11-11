<?php

namespace App\Http\Controllers;

use App\Models\BreakupInvoiceModel;
use App\Models\InvoiceModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BreakupInvoiceController extends Controller
{
    /**
     * Listing of Breakup Invoicing batches.
     */
    public function index(Request $request)
    {
        BreakupInvoiceModel::ensureModuleRegistered();

        $filters = [
            'search'     => $request->input('search'),
            'start_date' => $request->input('start_date'),
            'end_date'   => $request->input('end_date'),
        ];

        // Summary Statistics
        $summary = DB::connection('tenant')->table('tbl_bulk_invoice_request')
            ->where('SourceType', 'BREAKUP_GENERATOR')
            ->where('Status', '!=', 2)
            ->selectRaw('
                COUNT(*) as total_batches,
                COALESCE(SUM(TotalInvoices), 0) as total_invoices,
                COALESCE(SUM(TotalAmount), 0) as total_amount,
                COALESCE(SUM(TotalTax), 0) as total_tax,
                COALESCE(SUM(FbrPostedCount), 0) as total_fbr_posted
            ')
            ->first();

        $batches = BreakupInvoiceModel::getListingData($filters)->paginate(50);

        // Active Customers using central InvoiceModel::Load_Customer()
        $customers = DB::connection('tenant')->select(InvoiceModel::Load_Customer());

        // Active Products for Modal Dropdown
        $products = DB::connection('tenant')->table('tbl_product')
            ->where('Status', 1)
            ->select(
                'ID', 'Code', 'Name', 'SalePrice', 'TPRate', 'IsFixedRate',
                'Uom_Desc', 'SaleTypeDesc', 'SaleTypeToRateDesc', 'HsCode'
            )
            ->orderBy('Name')
            ->get();

        return view('pages.dashboards.Invoice.breakup-invoices-list', compact(
            'batches',
            'summary',
            'filters',
            'customers',
            'products'
        ));
    }

    /**
     * AJAX: Fetch product defaults from tbl_product on dropdown selection.
     */
    public function getProductInfo(int $id)
    {
        $product = BreakupInvoiceModel::getProductBreakupInfo($id);

        if (! $product) {
            return response()->json(['status' => false, 'message' => 'Product not found.'], 404);
        }

        return response()->json([
            'status'  => true,
            'product' => $product,
        ]);
    }

    /**
     * Generate Staging Rows in tbl_temp_invoice_detail and return preview partial.
     */
    public function generatePreview(Request $request)
    {
        $request->validate([
            'customer_id'       => 'required|integer|min:1',
            'product_id'        => 'required|integer|min:1',
            'total_target_qty'  => 'required|numeric|min:0.0001',
            'min_qty'           => 'required|numeric|min:0.0001',
            'max_qty'           => 'required|numeric|min:0.0001|gte:min_qty',
            'from_date'         => 'required|date',
            'to_date'           => 'required|date|after_or_equal:from_date',
            'sale_rate'         => 'nullable|numeric|min:0',
            'tp_rate'           => 'nullable|numeric|min:0',
            'is_fixed_value'    => 'nullable|integer|in:0,1',
            'numbering_mode'    => 'nullable|string|in:auto,custom',
            'invoice_prefix'    => 'nullable|string|max:50',
            'sale_of_term'      => 'nullable|string|in:CASH,CREDIT',
            'allow_decimal_qty' => 'sometimes|boolean',
            'doc2'              => 'nullable|string|max:50',
            'doc3'              => 'nullable|string|max:50',
        ]);

        $requestId = trim((string) $request->input('request_id')) ?: (string) Str::uuid();
        $userId = (int) (session('selected_tenant.user_id') ?: auth()->id() ?: 1);

        $params = $request->all();
        $params['allow_decimal_qty'] = $request->boolean('allow_decimal_qty');

        $result = BreakupInvoiceModel::generate_breakup_temp($requestId, $params, $userId);

        if (! $result['status']) {
            return response()->json(['status' => false, 'message' => $result['message']], 422);
        }

        // Fetch staged rows to render preview table partial
        $rows = DB::connection('tenant')->table('tbl_temp_invoice_detail')
            ->where('RequestID', $requestId)
            ->orderBy('ID')
            ->get();

        $previewHtml = view('pages.dashboards.Invoice._breakup-preview-partial', [
            'rows'             => $rows,
            'requestId'        => $requestId,
            'targetQty'        => (float) $params['total_target_qty'],
            'totalInvoices'      => $result['total_invoices'],
            'totalQty'           => $result['total_qty'],
            'totalExclTax'       => $result['total_excl_tax'],
            'totalFixedNotified' => $result['total_fixed_notified'] ?? 0,
            'totalTax'           => $result['total_tax'],
            'totalAmount'        => $result['total_amount'],
        ])->render();

        return response()->json([
            'status'       => true,
            'message'      => $result['message'],
            'request_id'   => $requestId,
            'preview_html' => $previewHtml,
            'totals'       => $result,
        ]);
    }

    /**
     * AJAX: Inline edit single row in preview grid.
     */
    public function updateRow(Request $request)
    {
        $request->validate([
            'temp_id' => 'required|integer',
            'qty'     => 'required|numeric|min:0.0001',
            'date'    => 'nullable|date',
            'rate'    => 'nullable|numeric|min:0',
        ]);

        $tempId = (int) $request->input('temp_id');
        $qty    = (float) $request->input('qty');
        $date   = $request->input('date');
        $rate   = $request->has('rate') ? (float) $request->input('rate') : null;
        $userId = (int) (session('selected_tenant.user_id') ?: auth()->id() ?: 1);

        $result = BreakupInvoiceModel::edit_temp_row($tempId, $qty, $date, $rate, $userId);

        if (! $result['status']) {
            return response()->json($result, 422);
        }

        return response()->json($result);
    }

    /**
     * AJAX: Delete single row from preview grid.
     */
    public function deleteRow(Request $request)
    {
        $request->validate([
            'temp_id' => 'required|integer',
        ]);

        $result = BreakupInvoiceModel::delete_temp_row((int) $request->input('temp_id'));

        if (! $result['status']) {
            return response()->json($result, 422);
        }

        return response()->json($result);
    }

    /**
     * Commit the staged breakup batch into live invoice tables.
     */
    public function save(Request $request)
    {
        $request->validate([
            'request_id'  => 'required|string',
            'batch_title' => 'nullable|string|max:150',
        ]);

        $requestId  = trim((string) $request->input('request_id'));
        $batchTitle = trim((string) $request->input('batch_title'));
        $userId     = auth()->id() ?? 1;

        $result = BreakupInvoiceModel::save_transaction($requestId, $batchTitle, $userId);

        if (! $result['status']) {
            if ($request->expectsJson()) {
                return response()->json($result, 422);
            }
            return redirect()->back()->with('error', $result['message']);
        }

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        return redirect()->route('breakup-invoices.index')->with('success', $result['message']);
    }

    /**
     * Rollback / hard delete a breakup invoice batch using the history-first pipeline.
     */
    public function rollback(Request $request, string $id)
    {
        $userId = (int) (session('selected_tenant.user_id') ?: auth()->id() ?: 1);
        $result = BreakupInvoiceModel::delete_transaction($id, $userId);

        if (! $result['status']) {
            if ($request->expectsJson()) {
                return response()->json($result, 422);
            }
            return redirect()->back()->with('error', $result['message']);
        }

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        return redirect()->route('breakup-invoices.index')->with('success', $result['message']);
    }

    /**
     * View all invoices and line items for a specific batch in a modal (shared with Bulk Invoices).
     */
    public function getBatchInvoices(string $requestId)
    {
        return app(BulkInvoiceController::class)->getBatchInvoices($requestId);
    }
}


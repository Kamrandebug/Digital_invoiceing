<?php

namespace App\Http\Controllers;

use App\Models\InvoiceModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleReportController extends Controller
{
    /**
     * @param  array<string, mixed>  $filters
     */
    private function baseQuery(array $filters)
    {
        $q = DB::table('tbl_sales_invoice_detail as d')
            ->join('tbl_sales_invoice_header as h', 'd.InvoiceID', '=', 'h.ID')
            ->leftJoin('tbl_customer as cust', 'h.CustomerID', '=', 'cust.ID')
            ->leftJoin('tbl_product as p', 'd.ProductID', '=', 'p.ID')
            ->select(
                'h.ID as header_id',
                'h.DocNo',
                'h.InvoiceDate',
                'h.Status as header_status',
                'h.FbrTransactionID',
                'h.SubTotal as header_sub_total',
                'h.Total as header_total',
                'd.Quantity',
                'd.Rate',
                'd.Amount',
                'd.SalesTaxApplicable as GSTAmount',
                'd.Discount as DiscountAmount',
                'd.ExtraTax',
                'd.FurtherTax',
                'd.FedPayable',
                'd.HsCode',
                'd.ProductDescription as line_description',
                'd.Uom_Desc',
                'p.Name as product_name',
                'p.ID as product_id',
                'h.CustomerID',
                InvoiceModel::sqlCustomerDisplayName()
            )
            ->where(function ($w) {
                $w->whereNull('d.Status')->orWhere('d.Status', '!=', 2);
            });

        $statusFilter = $filters['invoice_status'] ?? '';

        if ($statusFilter === 'archived') {
            $q->where('h.Status', 2);
        } else {
            if ($statusFilter === 'posted_fbr') {
                $q->where('h.Status', '!=', 2)
                    ->whereRaw("TRIM(IFNULL(h.FbrTransactionID, '')) <> ''");
            } elseif ($statusFilter === 'not_posted_fbr') {
                $q->where('h.Status', '!=', 2)
                    ->whereRaw("TRIM(IFNULL(h.FbrTransactionID, '')) = ''");
            } elseif ($statusFilter === 'active') {
                $q->where('h.Status', 1);
            } else {
                $q->where(function ($w) {
                    $w->whereNull('h.Status')->orWhere('h.Status', '!=', 2);
                });
            }
        }

        if (! empty($filters['customer_id'])) {
            $q->where('h.CustomerID', (int) $filters['customer_id']);
        }

        if (! empty($filters['product_id'])) {
            $q->where('d.ProductID', (int) $filters['product_id']);
        }

        if (! empty($filters['start_date'])) {
            $q->whereDate('h.InvoiceDate', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $q->whereDate('h.InvoiceDate', '<=', $filters['end_date']);
        }

        return $q->orderByDesc('h.InvoiceDate')->orderBy('h.DocNo')->orderBy('d.ID');
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{rows: \Illuminate\Support\Collection, totals: object}
     */
    private function fetchReport(array $filters): array
    {
        $rows = $this->baseQuery($filters)->get();

        $totals = (object) [
            'line_count' => $rows->count(),
            'qty' => (float) $rows->sum('Quantity'),
            'amount' => (float) $rows->sum('Amount'),
            'gst' => (float) $rows->sum('GSTAmount'),
            'discount' => (float) $rows->sum('DiscountAmount'),
            'extra_tax' => (float) $rows->sum('ExtraTax'),
            'further_tax' => (float) $rows->sum('FurtherTax'),
            'fed_payable' => (float) $rows->sum('FedPayable'),
        ];

        return ['rows' => $rows, 'totals' => $totals];
    }

    private function validatedFilters(Request $request): array
    {
        $rules = [
            'customer_id' => 'nullable|integer|min:1',
            'product_id' => 'nullable|integer|min:1',
            'invoice_status' => 'nullable|string|max:24',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ];

        $request->validate($rules, [
            'end_date.after_or_equal' => 'End date must be on or after start date.',
        ]);

        $status = (string) $request->input('invoice_status', '');
        $allowed = ['', 'active', 'archived', 'posted_fbr', 'not_posted_fbr'];
        if (! in_array($status, $allowed, true)) {
            $status = '';
        }

        return [
            'customer_id' => $request->input('customer_id'),
            'product_id' => $request->input('product_id'),
            'invoice_status' => $status,
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
        ];
    }

    public function index(Request $request)
    {
        $filters = $this->validatedFilters($request);
        $customerSql = InvoiceModel::Load_Customer();
        $productSql = InvoiceModel::Load_Product();
        $customers = DB::select($customerSql);
        $products = DB::select($productSql);

        $report = $this->fetchReport($filters);

        return view('pages.dashboards.Reports.sale-report', [
            'filters' => $filters,
            'customers' => $customers,
            'products' => $products,
            'rows' => $report['rows'],
            'totals' => $report['totals'],
        ]);
    }

    public function exportCsv(Request $request)
    {
        $filters = $this->validatedFilters($request);
        $report = $this->fetchReport($filters);
        $rows = $report['rows'];

        $filename = 'sale-report-'.date('Y-m-d-His').'.csv';
        $headers = [
            'Content-type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename={$filename}",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($rows) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($file, [
                'Doc No', 'Invoice Date', 'Invoice Status', 'Customer', 'Product', 'Description',
                'HS Code', 'UOM', 'Qty', 'Rate', 'Amount', 'Discount', 'GST', 'Extra Tax', 'Further Tax', 'FED Payable', 'FBR Transaction ID',
            ]);

            foreach ($rows as $r) {
                fputcsv($file, [
                    $r->DocNo,
                    $r->InvoiceDate,
                    self::statusLabel($r),
                    $r->CustomerDisplayName ?? '',
                    $r->product_name ?? '',
                    $r->line_description ?? '',
                    $r->HsCode ?? '',
                    $r->Uom_Desc ?? '',
                    number_format((float) ($r->Quantity ?? 0), 6, '.', ''),
                    number_format((float) ($r->Rate ?? 0), 6, '.', ''),
                    number_format((float) ($r->Amount ?? 0), 2, '.', ''),
                    number_format((float) ($r->DiscountAmount ?? 0), 2, '.', ''),
                    number_format((float) ($r->GSTAmount ?? 0), 2, '.', ''),
                    number_format((float) ($r->ExtraTax ?? 0), 2, '.', ''),
                    number_format((float) ($r->FurtherTax ?? 0), 2, '.', ''),
                    number_format((float) ($r->FedPayable ?? 0), 2, '.', ''),
                    $r->FbrTransactionID ?? '',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function printView(Request $request)
    {
        $filters = $this->validatedFilters($request);
        $report = $this->fetchReport($filters);

        return view('pages.dashboards.Reports.sale-report-print', [
            'filters' => $filters,
            'rows' => $report['rows'],
            'totals' => $report['totals'],
            'printed_at' => now(),
        ]);
    }

    public static function statusLabel(object $r): string
    {
        $st = (int) ($r->header_status ?? 0);
        if ($st === 2) {
            return 'Archived';
        }
        $fbr = trim((string) ($r->FbrTransactionID ?? ''));

        return $fbr !== '' ? 'Posted to FBR' : 'Draft (not posted to FBR)';
    }
}

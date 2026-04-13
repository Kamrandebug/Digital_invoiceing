<?php

namespace App\Http\Controllers;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class DashboardsController extends Controller
{
    public function index2(): View|RedirectResponse
    {
        $selectedTenant = session('selected_tenant');
        if (! is_array($selectedTenant) || empty($selectedTenant['dbname'])) {
            return redirect()
                ->route('tenants.index')
                ->with('error', 'Please select a tenant first to view the dashboard.');
        }

        $db = DB::connection('tenant');
        $today = Carbon::today();
        $startOfMonth = $today->copy()->startOfMonth();
        $endOfMonth = $today->copy()->endOfMonth();
        $startOfYear = $today->copy()->startOfYear();
        $salesTrendStart = $today->copy()->subDays(13);

        $amountColumn = $this->detectInvoiceAmountColumn();
        $statusInvoiceColumn = $this->hasColumn('tbl_sales_invoice_header', 'Statusi') ? 'Statusi' : null;
        $statusCustomerColumn = $this->hasColumn('tbl_customer', 'Status') ? 'Status' : null;
        $archiveCustomerColumn = $this->hasColumn('tbl_customer', 'IsArchive') ? 'IsArchive' : null;
        $statusProductColumn = $this->hasColumn('tbl_product', 'Status') ? 'Status' : null;
        $invoiceCreatedOnColumn = $this->hasColumn('tbl_sales_invoice_header', 'CreatedOn') ? 'CreatedOn' : 'InvoiceDate';

        $invoiceBase = $db->table('tbl_sales_invoice_header');
        if ($statusInvoiceColumn) {
            $invoiceBase->where($statusInvoiceColumn, '!=', 2);
        }

        $salesToday = 0.0;
        $salesMonth = 0.0;
        if ($amountColumn !== null) {
            $salesToday = (float) (clone $invoiceBase)
                ->whereDate('InvoiceDate', $today)
                ->sum($amountColumn);
            $salesMonth = (float) (clone $invoiceBase)
                ->whereBetween('InvoiceDate', [$startOfMonth, $endOfMonth])
                ->sum($amountColumn);
        }

        $totalInvoices = (int) (clone $invoiceBase)->count();

        $customersQuery = $db->table('tbl_customer');
        if ($statusCustomerColumn) {
            $customersQuery->where($statusCustomerColumn, 1);
        }
        if ($archiveCustomerColumn) {
            $customersQuery->where($archiveCustomerColumn, 0);
        }
        $activeCustomers = (int) $customersQuery->count();

        $productsQuery = $db->table('tbl_product');
        if ($statusProductColumn) {
            $productsQuery->where($statusProductColumn, 1);
        }
        $totalProducts = (int) (clone $productsQuery)->count();
        $lowStockProducts = (int) (clone $productsQuery)
            ->where('CurrentQuantity', '<', 10)
            ->count();
        $inventoryValue = (float) (clone $productsQuery)
            ->selectRaw('COALESCE(SUM(COALESCE(CurrentQuantity,0) * COALESCE(Cost,0)),0) as total')
            ->value('total');

        $fbrSuccessInvoices = (int) (clone $invoiceBase)
            ->whereNotNull('FbrTransactionID')
            ->where('FbrTransactionID', '!=', '')
            ->count();

        $salesTrendRows = (clone $invoiceBase)
            ->selectRaw('DATE(InvoiceDate) as sales_date, COALESCE(SUM(' . ($amountColumn ?? '0') . '),0) as total')
            ->whereDate('InvoiceDate', '>=', $salesTrendStart)
            ->groupBy(DB::raw('DATE(InvoiceDate)'))
            ->orderBy('sales_date')
            ->get()
            ->keyBy('sales_date');

        $salesTrendLabels = [];
        $salesTrendData = [];
        for ($d = $salesTrendStart->copy(); $d->lte($today); $d->addDay()) {
            $dateKey = $d->format('Y-m-d');
            $salesTrendLabels[] = $d->format('d M');
            $salesTrendData[] = (float) data_get($salesTrendRows, $dateKey . '.total', 0);
        }

        $monthlyRows = (clone $invoiceBase)
            ->selectRaw('MONTH(InvoiceDate) as month_no, COALESCE(SUM(' . ($amountColumn ?? '0') . '),0) as total')
            ->whereDate('InvoiceDate', '>=', $startOfYear)
            ->groupBy(DB::raw('MONTH(InvoiceDate)'))
            ->orderBy('month_no')
            ->get()
            ->keyBy('month_no');

        $monthlySalesLabels = [];
        $monthlySalesData = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthlySalesLabels[] = Carbon::create($today->year, $m, 1)->format('M');
            $monthlySalesData[] = (float) data_get($monthlyRows, $m . '.total', 0);
        }

        $statusRows = (clone $invoiceBase)
            ->selectRaw('COALESCE(' . ($statusInvoiceColumn ?: '1') . ', 0) as status_code, COUNT(*) as total')
            ->groupBy('status_code')
            ->orderBy('status_code')
            ->get();

        $statusLabels = [];
        $statusData = [];
        foreach ($statusRows as $row) {
            $code = (int) $row->status_code;
            $statusLabels[] = match ($code) {
                1 => 'Active',
                2 => 'Archived',
                default => 'Status ' . $code,
            };
            $statusData[] = (int) $row->total;
        }
        if ($statusLabels === []) {
            $statusLabels = ['No Data'];
            $statusData = [0];
        }

        $topCustomers = (clone $invoiceBase)
            ->leftJoin('tbl_customer as c', 'c.ID', '=', 'tbl_sales_invoice_header.CustomerID')
            ->selectRaw("
                tbl_sales_invoice_header.CustomerID as customer_id,
                COALESCE(NULLIF(c.BusinessName,''), NULLIF(CONCAT(COALESCE(c.FirstName,''), ' ', COALESCE(c.LastName,'')), ' '), CONCAT('Customer #', tbl_sales_invoice_header.CustomerID)) as customer_name,
                COALESCE(SUM(" . ($amountColumn ?? '0') . "),0) as total_sales
            ")
            ->groupBy('tbl_sales_invoice_header.CustomerID', 'customer_name')
            ->orderByDesc('total_sales')
            ->limit(5)
            ->get();

        $lowStockList = (clone $productsQuery)
            ->select(['Name', 'CurrentQuantity'])
            ->where('CurrentQuantity', '<', 10)
            ->orderBy('CurrentQuantity')
            ->limit(10)
            ->get();

        $recentInvoices = (clone $invoiceBase)
            ->select([
                'ID',
                'InvoiceNumber',
                'InvoiceDate',
                'FbrTransactionID',
                DB::raw(($amountColumn ?: '0') . ' as amount_value'),
            ])
            ->orderByDesc($invoiceCreatedOnColumn)
            ->limit(10)
            ->get();

        return view('pages.dashboards.index2', [
            'selectedTenant' => $selectedTenant,
            'kpis' => [
                'sales_today' => $salesToday,
                'sales_month' => $salesMonth,
                'total_invoices' => $totalInvoices,
                'active_customers' => $activeCustomers,
                'total_products' => $totalProducts,
                'low_stock_products' => $lowStockProducts,
                'inventory_value' => $inventoryValue,
                'fbr_success_invoices' => $fbrSuccessInvoices,
            ],
            'salesTrendLabels' => $salesTrendLabels,
            'salesTrendData' => $salesTrendData,
            'monthlySalesLabels' => $monthlySalesLabels,
            'monthlySalesData' => $monthlySalesData,
            'statusLabels' => $statusLabels,
            'statusData' => $statusData,
            'topCustomers' => $topCustomers,
            'lowStockList' => $lowStockList,
            'recentInvoices' => $recentInvoices,
        ]);
    }

    private function detectInvoiceAmountColumn(): ?string
    {
        foreach (['NetAmount', 'TotalValues', 'ValueSalesExcludingST'] as $candidate) {
            if ($this->hasColumn('tbl_sales_invoice_header', $candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function hasColumn(string $table, string $column): bool
    {
        try {
            return Schema::connection('tenant')->hasColumn($table, $column);
        } catch (\Throwable) {
            return false;
        }
    }
}

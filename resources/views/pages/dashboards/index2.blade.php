@extends('layouts.master')

@section('styles')
<style>
    .dash-kpi-card .card-title { font-size: 12px; color: #6b7280; margin-bottom: 4px; }
    .dash-kpi-card .card-value { font-size: 24px; font-weight: 700; line-height: 1.1; }
    .dash-mini { font-size: 12px; color: #64748b; }
    .dash-chart { height: 320px; }
</style>
@endsection

@section('content')
<div class="page-header-breadcrumb mb-3">
    <div class="d-flex align-center justify-content-between flex-wrap gap-1">
        <div>
            <h1 class="page-title fw-medium fs-18 mb-0">Dashboard</h1>
            <div class="dash-mini mt-1">
                Tenant: <strong>{{ $selectedTenant['TenantName'] ?? 'Selected Tenant' }}</strong>
                @if(!empty($selectedTenant['dbname']))
                    | DB: {{ $selectedTenant['dbname'] }}
                @endif
            </div>
        </div>
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="javascript:void(0);">Dashboards</a></li>
            <li class="breadcrumb-item active" aria-current="page">Digital Invoicing</li>
        </ol>
    </div>
</div>

<div class="row">
    <div class="col-xl-3 col-md-6">
        <div class="card custom-card dash-kpi-card">
            <div class="card-body">
                <div class="card-title">Total Sales Today</div>
                <div class="card-value">PKR {{ number_format($kpis['sales_today'] ?? 0, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card custom-card dash-kpi-card">
            <div class="card-body">
                <div class="card-title">Total Sales This Month</div>
                <div class="card-value">PKR {{ number_format($kpis['sales_month'] ?? 0, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-6">
        <div class="card custom-card dash-kpi-card">
            <div class="card-body">
                <div class="card-title">Total Invoices</div>
                <div class="card-value">{{ number_format($kpis['total_invoices'] ?? 0) }}</div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-6">
        <div class="card custom-card dash-kpi-card">
            <div class="card-body">
                <div class="card-title">Active Customers</div>
                <div class="card-value">{{ number_format($kpis['active_customers'] ?? 0) }}</div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-6">
        <div class="card custom-card dash-kpi-card">
            <div class="card-body">
                <div class="card-title">Total Products</div>
                <div class="card-value">{{ number_format($kpis['total_products'] ?? 0) }}</div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card custom-card dash-kpi-card">
            <div class="card-body">
                <div class="card-title">Low Stock Products</div>
                <div class="card-value">{{ number_format($kpis['low_stock_products'] ?? 0) }}</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card custom-card dash-kpi-card">
            <div class="card-body">
                <div class="card-title">Inventory Value</div>
                <div class="card-value">PKR {{ number_format($kpis['inventory_value'] ?? 0, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card custom-card dash-kpi-card">
            <div class="card-body">
                <div class="card-title">FBR Successful Invoices</div>
                <div class="card-value">{{ number_format($kpis['fbr_success_invoices'] ?? 0) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-8">
        <div class="card custom-card">
            <div class="card-header"><div class="card-title">Sales Trend (Last 14 Days)</div></div>
            <div class="card-body"><canvas id="salesTrendChart" class="dash-chart"></canvas></div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card custom-card">
            <div class="card-header"><div class="card-title">Invoice Status Breakdown</div></div>
            <div class="card-body"><canvas id="statusChart" class="dash-chart"></canvas></div>
        </div>
    </div>
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-header"><div class="card-title">Monthly Sales (Current Year)</div></div>
            <div class="card-body"><canvas id="monthlySalesChart" class="dash-chart"></canvas></div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-4">
        <div class="card custom-card">
            <div class="card-header"><div class="card-title">Top Customers</div></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Customer</th><th class="text-end">Sales</th></tr></thead>
                        <tbody>
                            @forelse($topCustomers as $c)
                                <tr>
                                    <td>{{ $c->customer_name }}</td>
                                    <td class="text-end">PKR {{ number_format((float) $c->total_sales, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="text-center text-muted">No data</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card custom-card">
            <div class="card-header"><div class="card-title">Low Stock Products</div></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Product</th><th class="text-end">Quantity</th></tr></thead>
                        <tbody>
                            @forelse($lowStockList as $p)
                                <tr>
                                    <td>{{ $p->Name }}</td>
                                    <td class="text-end">{{ number_format((float) $p->CurrentQuantity, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="text-center text-muted">No low stock products</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card custom-card">
            <div class="card-header"><div class="card-title">Recent Invoices</div></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Invoice</th><th class="text-end">Amount</th><th>FBR</th></tr></thead>
                        <tbody>
                            @forelse($recentInvoices as $inv)
                                <tr>
                                    <td>
                                        #{{ $inv->InvoiceNumber ?? $inv->ID }}
                                        <div class="dash-mini">{{ \Illuminate\Support\Carbon::parse($inv->InvoiceDate)->format('d M Y') }}</div>
                                    </td>
                                    <td class="text-end">PKR {{ number_format((float) ($inv->amount_value ?? 0), 2) }}</td>
                                    <td>
                                        @if(!empty($inv->FbrTransactionID))
                                            <span class="badge bg-success-transparent">Posted</span>
                                        @else
                                            <span class="badge bg-warning-transparent">Pending</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted">No recent invoices</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
(function () {
    const currencyTick = (v) => 'PKR ' + Number(v || 0).toLocaleString();

    const salesTrendCtx = document.getElementById('salesTrendChart');
    if (salesTrendCtx) {
        new Chart(salesTrendCtx, {
            type: 'line',
            data: {
                labels: @json($salesTrendLabels),
                datasets: [{
                    label: 'Sales',
                    data: @json($salesTrendData),
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99,102,241,.15)',
                    fill: true,
                    tension: 0.35,
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { ticks: { callback: currencyTick } } } }
        });
    }

    const monthlyCtx = document.getElementById('monthlySalesChart');
    if (monthlyCtx) {
        new Chart(monthlyCtx, {
            type: 'bar',
            data: {
                labels: @json($monthlySalesLabels),
                datasets: [{
                    label: 'Monthly Sales',
                    data: @json($monthlySalesData),
                    backgroundColor: '#22c55e',
                    borderRadius: 8,
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, scales: { y: { ticks: { callback: currencyTick } } } }
        });
    }

    const statusCtx = document.getElementById('statusChart');
    if (statusCtx) {
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: @json($statusLabels),
                datasets: [{
                    data: @json($statusData),
                    backgroundColor: ['#6366f1', '#22c55e', '#f59e0b', '#ef4444', '#64748b'],
                }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });
    }
})();
</script>
@endsection

@extends('layouts.master')

@php
    $salesTrendSum = isset($salesTrendData) ? array_sum($salesTrendData) : 0;
    $monthlySalesSum = isset($monthlySalesData) ? array_sum($monthlySalesData) : 0;
    $statusSum = isset($statusData) ? array_sum($statusData) : 0;
@endphp

@section('styles')
<style>
    .dash-page {
        --dash-radius: 14px;
        --dash-shadow: 0 4px 24px rgba(15, 23, 42, 0.06);
        --dash-shadow-hover: 0 12px 32px rgba(15, 23, 42, 0.1);
        --dash-chart-h: 260px;
    }
    .dash-hero {
        background: linear-gradient(125deg, #f8fafc 0%, #eef2ff 40%, #faf5ff 100%);
        border: 1px solid rgba(99, 102, 241, 0.12);
        border-radius: 16px;
        padding: 1.25rem 1.35rem;
        margin-bottom: 1.25rem;
        box-shadow: var(--dash-shadow);
    }
    .dash-hero h1 {
        font-size: 1.4rem;
        font-weight: 700;
        letter-spacing: -0.02em;
        color: #0f172a;
        margin: 0;
    }
    .dash-hero .dash-tenant-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        background: rgba(255, 255, 255, 0.9);
        border: 1px solid rgba(99, 102, 241, 0.2);
        color: #4338ca;
        font-size: 0.8rem;
        font-weight: 600;
        padding: 0.3rem 0.75rem;
        border-radius: 999px;
        margin-top: 0.5rem;
    }
    .dash-breadcrumb .breadcrumb {
        background: transparent;
        padding: 0;
        margin: 0;
    }
    .dash-breadcrumb .breadcrumb-item a { color: #64748b; text-decoration: none; }
    .dash-breadcrumb .breadcrumb-item.active { color: #6366f1; font-weight: 500; }

    .dash-kpi {
        border: none;
        border-radius: var(--dash-radius);
        box-shadow: var(--dash-shadow);
        overflow: hidden;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        height: 100%;
    }
    .dash-kpi:hover {
        transform: translateY(-3px);
        box-shadow: var(--dash-shadow-hover);
    }
    .dash-kpi .card-body {
        padding: 1rem 1.1rem;
        position: relative;
        padding-left: 1rem;
    }
    .dash-kpi-label {
        font-size: 0.68rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #64748b;
        margin-bottom: 0.3rem;
    }
    .dash-kpi-value {
        font-size: 1.28rem;
        font-weight: 800;
        letter-spacing: -0.02em;
        color: #0f172a;
        line-height: 1.2;
    }
    .dash-kpi-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        flex-shrink: 0;
    }
    .dash-kpi--today .dash-kpi-icon { background: linear-gradient(135deg, #d1fae5, #a7f3d0); color: #047857; }
    .dash-kpi--month .dash-kpi-icon { background: linear-gradient(135deg, #e0e7ff, #c7d2fe); color: #4338ca; }
    .dash-kpi--invoices .dash-kpi-icon { background: linear-gradient(135deg, #dbeafe, #bfdbfe); color: #1d4ed8; }
    .dash-kpi--customers .dash-kpi-icon { background: linear-gradient(135deg, #cffafe, #a5f3fc); color: #0e7490; }
    .dash-kpi--products .dash-kpi-icon { background: linear-gradient(135deg, #ede9fe, #ddd6fe); color: #6d28d9; }
    .dash-kpi--lowstock .dash-kpi-icon { background: linear-gradient(135deg, #fef3c7, #fde68a); color: #b45309; }
    .dash-kpi--inventory .dash-kpi-icon { background: linear-gradient(135deg, #f1f5f9, #e2e8f0); color: #475569; }
    .dash-kpi--fbr .dash-kpi-icon { background: linear-gradient(135deg, #dcfce7, #bbf7d0); color: #15803d; }
    .dash-kpi-accent {
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 4px;
        border-radius: 4px 0 0 4px;
    }
    .dash-kpi--today .dash-kpi-accent { background: linear-gradient(180deg, #10b981, #34d399); }
    .dash-kpi--month .dash-kpi-accent { background: linear-gradient(180deg, #6366f1, #818cf8); }
    .dash-kpi--invoices .dash-kpi-accent { background: linear-gradient(180deg, #3b82f6, #60a5fa); }
    .dash-kpi--customers .dash-kpi-accent { background: linear-gradient(180deg, #06b6d4, #22d3ee); }
    .dash-kpi--products .dash-kpi-accent { background: linear-gradient(180deg, #8b5cf6, #a78bfa); }
    .dash-kpi--lowstock .dash-kpi-accent { background: linear-gradient(180deg, #f59e0b, #fbbf24); }
    .dash-kpi--inventory .dash-kpi-accent { background: linear-gradient(180deg, #64748b, #94a3b8); }
    .dash-kpi--fbr .dash-kpi-accent { background: linear-gradient(180deg, #22c55e, #4ade80); }

    .dash-chart-card {
        border: none;
        border-radius: var(--dash-radius);
        box-shadow: var(--dash-shadow);
        overflow: hidden;
    }
    .dash-chart-card .card-header {
        background: linear-gradient(180deg, #fafafa 0%, #fff 100%);
        border-bottom: 1px solid rgba(15, 23, 42, 0.06);
        padding: 0.85rem 1.1rem;
    }
    .dash-chart-card .card-title {
        font-size: 0.92rem;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.45rem;
    }
    .dash-chart-card .card-title i { color: #6366f1; font-size: 1.05rem; }
    .dash-chart-hint {
        font-size: 0.72rem;
        color: #94a3b8;
        margin: 0;
        padding: 0.35rem 1.1rem 0;
    }
    .dash-chart-wrap {
        height: var(--dash-chart-h);
        position: relative;
        width: 100%;
        overflow: hidden;
    }
    .dash-chart-wrap > canvas {
        display: block;
        max-width: 100%;
    }
    .dash-chart-card .card-body { padding-bottom: 1rem; }

    .dash-table-card {
        border: none;
        border-radius: var(--dash-radius);
        box-shadow: var(--dash-shadow);
        overflow: hidden;
    }
    .dash-table-card .card-header {
        background: linear-gradient(180deg, #fafafa 0%, #fff 100%);
        border-bottom: 1px solid rgba(15, 23, 42, 0.06);
        padding: 0.8rem 1rem;
    }
    .dash-table-card .card-title {
        font-size: 0.88rem;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.4rem;
    }
    .dash-table-card .card-title i { color: #6366f1; }
    .dash-table-card .table thead th {
        font-size: 0.65rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #64748b;
        font-weight: 600;
        background: #f8fafc;
        padding: 0.6rem 0.9rem;
        border-bottom-width: 1px;
    }
    .dash-table-card .table tbody td {
        padding: 0.55rem 0.9rem;
        vertical-align: middle;
        border-color: rgba(15, 23, 42, 0.05);
    }
    .dash-table-card .table tbody tr:hover { background: rgba(99, 102, 241, 0.04); }
    .dash-mini { font-size: 11px; color: #94a3b8; }
    .dash-empty-cell { padding: 1.75rem 1rem !important; color: #94a3b8; font-size: 0.875rem; }
</style>
@endsection

@section('content')
<div class="dash-page">
    <div class="dash-hero">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h1>Dashboard</h1>
                <div class="dash-tenant-pill">
                    <i class="ri-building-2-line"></i>
                    {{ $selectedTenant['TenantName'] ?? 'Selected Tenant' }}
                </div>
                <div class="text-muted small mt-2 d-none d-md-block">Sales, inventory &amp; FBR at a glance</div>
            </div>
            <div class="dash-breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Digital Invoicing</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-1">
        <div class="col-xl-3 col-md-6">
            <div class="card custom-card dash-kpi dash-kpi--today">
                <span class="dash-kpi-accent" aria-hidden="true"></span>
                <div class="card-body d-flex align-items-start justify-content-between gap-2 ps-3">
                    <div class="min-w-0">
                        <div class="dash-kpi-label">Total Sales Today</div>
                        <div class="dash-kpi-value text-truncate">PKR {{ number_format($kpis['sales_today'] ?? 0, 2) }}</div>
                    </div>
                    <div class="dash-kpi-icon"><i class="ri-calendar-check-line"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card custom-card dash-kpi dash-kpi--month">
                <span class="dash-kpi-accent" aria-hidden="true"></span>
                <div class="card-body d-flex align-items-start justify-content-between gap-2 ps-3">
                    <div class="min-w-0">
                        <div class="dash-kpi-label">Sales This Month</div>
                        <div class="dash-kpi-value text-truncate">PKR {{ number_format($kpis['sales_month'] ?? 0, 2) }}</div>
                    </div>
                    <div class="dash-kpi-icon"><i class="ri-line-chart-line"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card custom-card dash-kpi dash-kpi--invoices">
                <span class="dash-kpi-accent" aria-hidden="true"></span>
                <div class="card-body d-flex align-items-start justify-content-between gap-2 ps-3">
                    <div class="min-w-0">
                        <div class="dash-kpi-label">Total Invoices</div>
                        <div class="dash-kpi-value">{{ number_format($kpis['total_invoices'] ?? 0) }}</div>
                    </div>
                    <div class="dash-kpi-icon"><i class="ri-file-list-3-line"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card custom-card dash-kpi dash-kpi--customers">
                <span class="dash-kpi-accent" aria-hidden="true"></span>
                <div class="card-body d-flex align-items-start justify-content-between gap-2 ps-3">
                    <div class="min-w-0">
                        <div class="dash-kpi-label">Active Customers</div>
                        <div class="dash-kpi-value">{{ number_format($kpis['active_customers'] ?? 0) }}</div>
                    </div>
                    <div class="dash-kpi-icon"><i class="ri-team-line"></i></div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card custom-card dash-kpi dash-kpi--products">
                <span class="dash-kpi-accent" aria-hidden="true"></span>
                <div class="card-body d-flex align-items-start justify-content-between gap-2 ps-3">
                    <div class="min-w-0">
                        <div class="dash-kpi-label">Total Products</div>
                        <div class="dash-kpi-value">{{ number_format($kpis['total_products'] ?? 0) }}</div>
                    </div>
                    <div class="dash-kpi-icon"><i class="ri-box-3-line"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card custom-card dash-kpi dash-kpi--lowstock">
                <span class="dash-kpi-accent" aria-hidden="true"></span>
                <div class="card-body d-flex align-items-start justify-content-between gap-2 ps-3">
                    <div class="min-w-0">
                        <div class="dash-kpi-label">Low Stock</div>
                        <div class="dash-kpi-value">{{ number_format($kpis['low_stock_products'] ?? 0) }}</div>
                    </div>
                    <div class="dash-kpi-icon"><i class="ri-alarm-warning-line"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card custom-card dash-kpi dash-kpi--inventory">
                <span class="dash-kpi-accent" aria-hidden="true"></span>
                <div class="card-body d-flex align-items-start justify-content-between gap-2 ps-3">
                    <div class="min-w-0">
                        <div class="dash-kpi-label">Inventory Value</div>
                        <div class="dash-kpi-value text-truncate">PKR {{ number_format($kpis['inventory_value'] ?? 0, 2) }}</div>
                    </div>
                    <div class="dash-kpi-icon"><i class="ri-stack-line"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card custom-card dash-kpi dash-kpi--fbr">
                <span class="dash-kpi-accent" aria-hidden="true"></span>
                <div class="card-body d-flex align-items-start justify-content-between gap-2 ps-3">
                    <div class="min-w-0">
                        <div class="dash-kpi-label">FBR Posted</div>
                        <div class="dash-kpi-value">{{ number_format($kpis['fbr_success_invoices'] ?? 0) }}</div>
                    </div>
                    <div class="dash-kpi-icon"><i class="ri-shield-check-line"></i></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-1 align-items-start">
        <div class="col-xl-8">
            <div class="card custom-card dash-chart-card w-100">
                <div class="card-header">
                    <div class="card-title"><i class="ri-pulse-line"></i> Sales trend <span class="text-muted fw-normal fs-12">(last 14 days)</span></div>
                </div>
                @if($salesTrendSum == 0)
                    <p class="dash-chart-hint">No sales in this period yet — the line will grow as you record invoices.</p>
                @endif
                <div class="card-body pt-2">
                    <div class="dash-chart-wrap"><canvas id="salesTrendChart"></canvas></div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card custom-card dash-chart-card w-100">
                <div class="card-header">
                    <div class="card-title"><i class="ri-pie-chart-2-line"></i> Invoice status</div>
                </div>
                @if($statusSum == 0)
                    <p class="dash-chart-hint">No status split yet.</p>
                @endif
                <div class="card-body pt-2">
                    <div class="dash-chart-wrap"><canvas id="statusChart"></canvas></div>
                </div>
            </div>
        </div>
        <div class="col-xl-12">
            <div class="card custom-card dash-chart-card">
                <div class="card-header">
                    <div class="card-title"><i class="ri-bar-chart-grouped-line"></i> Monthly sales <span class="text-muted fw-normal fs-12">(current year)</span></div>
                </div>
                @if($monthlySalesSum == 0)
                    <p class="dash-chart-hint">No monthly totals yet — bars will fill as sales grow.</p>
                @endif
                <div class="card-body pt-2">
                    <div class="dash-chart-wrap"><canvas id="monthlySalesChart"></canvas></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-4">
            <div class="card custom-card dash-table-card h-100">
                <div class="card-header"><div class="card-title"><i class="ri-vip-crown-line"></i> Top customers</div></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Customer</th><th class="text-end">Sales</th></tr></thead>
                            <tbody>
                                @forelse($topCustomers as $c)
                                    <tr>
                                        <td class="fw-medium">{{ $c->customer_name }}</td>
                                        <td class="text-end fw-semibold text-primary">PKR {{ number_format((float) $c->total_sales, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="2" class="text-center dash-empty-cell">No customer sales yet</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card custom-card dash-table-card h-100">
                <div class="card-header"><div class="card-title"><i class="ri-alert-line"></i> Low stock</div></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Product</th><th class="text-end">Qty</th></tr></thead>
                            <tbody>
                                @forelse($lowStockList as $p)
                                    <tr>
                                        <td>{{ $p->Name }}</td>
                                        <td class="text-end"><span class="badge bg-warning-transparent text-warning-emphasis">{{ number_format((float) $p->CurrentQuantity, 2) }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="2" class="text-center dash-empty-cell">All products above threshold</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card custom-card dash-table-card h-100">
                <div class="card-header"><div class="card-title"><i class="ri-history-line"></i> Recent invoices</div></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Invoice</th><th class="text-end">Amount</th><th>FBR</th></tr></thead>
                            <tbody>
                                @forelse($recentInvoices as $inv)
                                    <tr>
                                        <td>
                                            <span class="fw-semibold">{{ trim((string) ($inv->DocNo ?? '')) !== '' ? $inv->DocNo : ('#' . ($inv->InvoiceNumber ?? $inv->ID)) }}</span>
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
                                    <tr><td colspan="3" class="text-center dash-empty-cell">No recent invoices</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
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
    const currencyTick = function (v) { return 'PKR ' + Number(v || 0).toLocaleString(); };
    const gridColor = 'rgba(148, 163, 184, 0.2)';
    const tickColor = '#64748b';

    const chartsToResize = [];

    const salesTrendSeries = (@json($salesTrendData)).map(Number);
    const salesTrendMax = salesTrendSeries.length ? Math.max.apply(null, salesTrendSeries) : 0;

    const salesTrendCtx = document.getElementById('salesTrendChart');
    if (salesTrendCtx && typeof Chart !== 'undefined') {
        chartsToResize.push(new Chart(salesTrendCtx, {
            type: 'line',
            data: {
                labels: @json($salesTrendLabels),
                datasets: [{
                    label: 'Sales',
                    data: salesTrendSeries,
                    borderColor: '#6366f1',
                    backgroundColor: function (context) {
                        const chart = context.chart;
                        const { ctx, chartArea } = chart;
                        if (!chartArea) return 'rgba(99,102,241,.12)';
                        const g = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                        g.addColorStop(0, 'rgba(99,102,241,.25)');
                        g.addColorStop(1, 'rgba(99,102,241,0)');
                        return g;
                    },
                    fill: true,
                    tension: 0.4,
                    borderWidth: 2.5,
                    pointRadius: 3,
                    pointHoverRadius: 6,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#6366f1',
                    pointBorderWidth: 2,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { intersect: false, mode: 'index' },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(15,23,42,.92)',
                        padding: 10,
                        cornerRadius: 8,
                        callbacks: { label: function (ctx) { return currencyTick(ctx.parsed.y); } }
                    }
                },
                scales: {
                    x: { grid: { color: gridColor }, ticks: { color: tickColor, maxRotation: 0 } },
                    y: Object.assign(
                        {
                            beginAtZero: true,
                            min: 0,
                            grid: { color: gridColor },
                        },
                        salesTrendMax <= 0
                            ? {
                                max: 1,
                                ticks: {
                                    color: tickColor,
                                    maxTicksLimit: 3,
                                    callback: function (value) {
                                        return Number(value) === 0 ? currencyTick(0) : '';
                                    }
                                }
                            }
                            : {
                                ticks: { color: tickColor, callback: currencyTick, maxTicksLimit: 8 }
                            }
                    )
                }
            }
        }));
    }

    const monthlyCtx = document.getElementById('monthlySalesChart');
    const monthlySeries = (@json($monthlySalesData)).map(Number);
    const monthlyMax = monthlySeries.length ? Math.max.apply(null, monthlySeries) : 0;

    if (monthlyCtx && typeof Chart !== 'undefined') {
        chartsToResize.push(new Chart(monthlyCtx, {
            type: 'bar',
            data: {
                labels: @json($monthlySalesLabels),
                datasets: [{
                    label: 'Monthly Sales',
                    data: monthlySeries,
                    backgroundColor: function (context) {
                        const i = context.dataIndex;
                        const a = ['#6366f1', '#8b5cf6', '#a855f7', '#d946ef', '#ec4899', '#f43f5e', '#f97316', '#eab308', '#22c55e', '#14b8a6', '#06b6d4', '#3b82f6'];
                        return a[i % a.length];
                    },
                    borderRadius: 8,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(15,23,42,.92)',
                        padding: 10,
                        cornerRadius: 8,
                        callbacks: { label: function (ctx) { return currencyTick(ctx.parsed.y); } }
                    }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { color: tickColor } },
                    y: Object.assign(
                        { beginAtZero: true, min: 0, grid: { color: gridColor } },
                        monthlyMax <= 0
                            ? {
                                max: 1,
                                ticks: {
                                    color: tickColor,
                                    maxTicksLimit: 3,
                                    callback: function (value) {
                                        return Number(value) === 0 ? currencyTick(0) : '';
                                    }
                                }
                            }
                            : {
                                ticks: { color: tickColor, callback: currencyTick, maxTicksLimit: 8 }
                            }
                    )
                }
            }
        }));
    }

    const statusCtx = document.getElementById('statusChart');
    let statusLabels = @json($statusLabels);
    let statusData = (@json($statusData)).map(Number);
    const statusPalette = ['#6366f1', '#22c55e', '#f59e0b', '#ef4444', '#94a3b8', '#8b5cf6'];
    let statusColors = statusPalette.slice(0, Math.max(statusData.length, 1));
    const statusSumRaw = statusData.reduce(function (a, b) { return a + (Number(b) || 0); }, 0);
    const statusIsEmpty = statusSumRaw <= 0;
    if (statusIsEmpty) {
        statusLabels = ['No data'];
        statusData = [1];
        statusColors = ['#e2e8f0'];
    }

    if (statusCtx && typeof Chart !== 'undefined') {
        chartsToResize.push(new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: statusLabels,
                datasets: [{
                    data: statusData,
                    backgroundColor: statusColors,
                    borderWidth: 0,
                    hoverOffset: 8,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '58%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            padding: 10,
                            boxWidth: 8,
                            color: tickColor,
                            font: { size: 10, weight: '500' }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15,23,42,.92)',
                        padding: 10,
                        cornerRadius: 8,
                        callbacks: {
                            label: function (ctx) {
                                if (statusIsEmpty) return 'No breakdown yet';
                                return (ctx.label || '') + ': ' + Number(ctx.raw).toLocaleString();
                            }
                        }
                    }
                }
            }
        }));
    }

    requestAnimationFrame(function () {
        chartsToResize.forEach(function (ch) {
            if (ch && typeof ch.resize === 'function') ch.resize();
        });
    });
})();
</script>
@endsection

@extends('layouts.master')

@php
    $salesTrendSum = isset($salesTrendData) ? array_sum($salesTrendData) : 0;
    $monthlySalesSum = isset($monthlySalesData) ? array_sum($monthlySalesData) : 0;
    $statusSum = isset($statusData) ? array_sum($statusData) : 0;
@endphp

@section('styles')
<style>
    /* Chart.js needs a fixed-height box; theme .card does not size canvas. */
    .dash-chart-wrap {
        height: 260px;
        position: relative;
        width: 100%;
        overflow: hidden;
    }
    .dash-chart-wrap > canvas {
        display: block;
        max-width: 100%;
    }
</style>
@endsection

@section('content')
<div class="page-header-breadcrumb mb-3">
    <div class="d-flex align-center justify-content-between flex-wrap">
        <div>
            <h1 class="page-title fw-medium fs-18 mb-0">Dashboard</h1>
            <div class="text-muted fs-12 mt-1">
                <i class="ri-building-2-line me-1"></i>{{ $selectedTenant['TenantName'] ?? 'Selected Tenant' }}
            </div>
            <div class="text-muted fs-12 mt-1 d-none d-md-block">Sales, inventory &amp; FBR at a glance</div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Digital Invoicing</li>
            </ol>
        </div>
    </div>
</div>

<div class="row g-3 mb-1">
    <div class="col-xl-3 col-md-6">
        <div class="card custom-card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between gap-2">
                    <div class="min-w-0 flex-grow-1">
                        <span class="d-block text-muted fs-12 text-uppercase fw-semibold mb-1">Total Sales Today</span>
                        <span class="d-block h5 fw-semibold mb-0 text-truncate">PKR {{ number_format($kpis['sales_today'] ?? 0, 2) }}</span>
                    </div>
                    <span class="avatar avatar-md avatar-rounded bg-success-transparent flex-shrink-0"><i class="ri-calendar-check-line fs-18"></i></span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card custom-card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between gap-2">
                    <div class="min-w-0 flex-grow-1">
                        <span class="d-block text-muted fs-12 text-uppercase fw-semibold mb-1">Sales This Month</span>
                        <span class="d-block h5 fw-semibold mb-0 text-truncate">PKR {{ number_format($kpis['sales_month'] ?? 0, 2) }}</span>
                    </div>
                    <span class="avatar avatar-md avatar-rounded bg-primary-transparent flex-shrink-0"><i class="ri-line-chart-line fs-18"></i></span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card custom-card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between gap-2">
                    <div class="min-w-0 flex-grow-1">
                        <span class="d-block text-muted fs-12 text-uppercase fw-semibold mb-1">Total Invoices</span>
                        <span class="d-block h5 fw-semibold mb-0">{{ number_format($kpis['total_invoices'] ?? 0) }}</span>
                    </div>
                    <span class="avatar avatar-md avatar-rounded bg-info-transparent flex-shrink-0"><i class="ri-file-list-3-line fs-18"></i></span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card custom-card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between gap-2">
                    <div class="min-w-0 flex-grow-1">
                        <span class="d-block text-muted fs-12 text-uppercase fw-semibold mb-1">Active Customers</span>
                        <span class="d-block h5 fw-semibold mb-0">{{ number_format($kpis['active_customers'] ?? 0) }}</span>
                    </div>
                    <span class="avatar avatar-md avatar-rounded bg-teal-transparent flex-shrink-0"><i class="ri-team-line fs-18"></i></span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card custom-card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between gap-2">
                    <div class="min-w-0 flex-grow-1">
                        <span class="d-block text-muted fs-12 text-uppercase fw-semibold mb-1">Total Products</span>
                        <span class="d-block h5 fw-semibold mb-0">{{ number_format($kpis['total_products'] ?? 0) }}</span>
                    </div>
                    <span class="avatar avatar-md avatar-rounded bg-purple-transparent flex-shrink-0"><i class="ri-box-3-line fs-18"></i></span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card custom-card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between gap-2">
                    <div class="min-w-0 flex-grow-1">
                        <span class="d-block text-muted fs-12 text-uppercase fw-semibold mb-1">Low Stock</span>
                        <span class="d-block h5 fw-semibold mb-0">{{ number_format($kpis['low_stock_products'] ?? 0) }}</span>
                    </div>
                    <span class="avatar avatar-md avatar-rounded bg-warning-transparent flex-shrink-0"><i class="ri-alarm-warning-line fs-18"></i></span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card custom-card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between gap-2">
                    <div class="min-w-0 flex-grow-1">
                        <span class="d-block text-muted fs-12 text-uppercase fw-semibold mb-1">Inventory Value</span>
                        <span class="d-block h5 fw-semibold mb-0 text-truncate">PKR {{ number_format($kpis['inventory_value'] ?? 0, 2) }}</span>
                    </div>
                    <span class="avatar avatar-md avatar-rounded bg-secondary-transparent flex-shrink-0"><i class="ri-stack-line fs-18"></i></span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card custom-card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between gap-2">
                    <div class="min-w-0 flex-grow-1">
                        <span class="d-block text-muted fs-12 text-uppercase fw-semibold mb-1">FBR Posted</span>
                        <span class="d-block h5 fw-semibold mb-0">{{ number_format($kpis['fbr_success_invoices'] ?? 0) }}</span>
                    </div>
                    <span class="avatar avatar-md avatar-rounded bg-green-transparent flex-shrink-0"><i class="ri-shield-check-line fs-18"></i></span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-1 align-items-start">
    <div class="col-xl-8">
        <div class="card custom-card w-100">
            <div class="card-header">
                <div class="card-title"><i class="ri-pulse-line me-1"></i>Sales trend <span class="text-muted fw-normal fs-12">(last 14 days)</span></div>
            </div>
            @if($salesTrendSum == 0)
                <p class="text-muted small px-3 pt-2 mb-0">No sales in this period yet — the line will grow as you record invoices.</p>
            @endif
            <div class="card-body pt-2">
                <div class="dash-chart-wrap"><canvas id="salesTrendChart"></canvas></div>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card custom-card w-100">
            <div class="card-header">
                <div class="card-title"><i class="ri-pie-chart-2-line me-1"></i>Invoice status</div>
            </div>
            @if($statusSum == 0)
                <p class="text-muted small px-3 pt-2 mb-0">No status split yet.</p>
            @endif
            <div class="card-body pt-2">
                <div class="dash-chart-wrap"><canvas id="statusChart"></canvas></div>
            </div>
        </div>
    </div>
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title"><i class="ri-bar-chart-grouped-line me-1"></i>Monthly sales <span class="text-muted fw-normal fs-12">(current year)</span></div>
            </div>
            @if($monthlySalesSum == 0)
                <p class="text-muted small px-3 pt-2 mb-0">No monthly totals yet — bars will fill as sales grow.</p>
            @endif
            <div class="card-body pt-2">
                <div class="dash-chart-wrap"><canvas id="monthlySalesChart"></canvas></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-xl-4">
        <div class="card custom-card h-100">
            <div class="card-header"><div class="card-title"><i class="ri-vip-crown-line me-1"></i>Top customers</div></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead><tr><th>Customer</th><th class="text-end">Sales</th></tr></thead>
                        <tbody>
                            @forelse($topCustomers as $c)
                                <tr>
                                    <td class="fw-medium">{{ $c->customer_name }}</td>
                                    <td class="text-end fw-semibold text-primary">PKR {{ number_format((float) $c->total_sales, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="text-center text-muted py-5">No customer sales yet</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card custom-card h-100">
            <div class="card-header"><div class="card-title"><i class="ri-alert-line me-1"></i>Low stock</div></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead><tr><th>Product</th><th class="text-end">Qty</th></tr></thead>
                        <tbody>
                            @forelse($lowStockList as $p)
                                <tr>
                                    <td>{{ $p->Name }}</td>
                                    <td class="text-end"><span class="badge bg-warning-transparent">{{ number_format((float) $p->CurrentQuantity, 2) }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="text-center text-muted py-5">All products above threshold</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card custom-card h-100">
            <div class="card-header"><div class="card-title"><i class="ri-history-line me-1"></i>Recent invoices</div></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead><tr><th>Invoice</th><th class="text-end">Amount</th><th>FBR</th></tr></thead>
                        <tbody>
                            @forelse($recentInvoices as $inv)
                                <tr>
                                    <td>
                                        <span class="fw-semibold">{{ trim((string) ($inv->DocNo ?? '')) !== '' ? $inv->DocNo : ('#' . ($inv->InvoiceNumber ?? $inv->ID)) }}</span>
                                        <div class="text-muted fs-11">{{ \Illuminate\Support\Carbon::parse($inv->InvoiceDate)->format('d M Y') }}</div>
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
                                <tr><td colspan="3" class="text-center text-muted py-5">No recent invoices</td></tr>
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
    const currencyTick = function (v) { return 'PKR ' + Number(v || 0).toLocaleString(); };
    const rs = getComputedStyle(document.documentElement);
    function themeRgb(prop) {
        var v = rs.getPropertyValue(prop).trim();
        return v ? 'rgb(' + v + ')' : '#888888';
    }
    function themeRgba(prop, a) {
        var v = rs.getPropertyValue(prop).trim();
        return v ? 'rgba(' + v + ', ' + a + ')' : 'rgba(0,0,0,' + a + ')';
    }
    var tickColor = rs.getPropertyValue('--text-muted').trim() || '#5d6576';
    var gridColor = themeRgba('--primary-rgb', 0.12);
    var primaryRgb = themeRgb('--primary-rgb');
    var chartWhite = rs.getPropertyValue('--custom-white').trim() || '#ffffff';
    var tooltipBg = themeRgba('--dark-rgb', 0.92);
    var barRgbProps = ['--primary-rgb', '--secondary-rgb', '--success-rgb', '--warning-rgb', '--info-rgb', '--orange-rgb', '--purple-rgb', '--teal-rgb', '--green-rgb', '--pink-rgb', '--danger-rgb'];
    var barColors = barRgbProps.map(function (p) { return themeRgb(p); });
    var emptyChartFill = rs.getPropertyValue('--default-border').trim() || '#e2e8ee';

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
                    borderColor: primaryRgb,
                    backgroundColor: function (context) {
                        const chart = context.chart;
                        const { ctx, chartArea } = chart;
                        if (!chartArea) return themeRgba('--primary-rgb', 0.12);
                        const g = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                        g.addColorStop(0, themeRgba('--primary-rgb', 0.25));
                        g.addColorStop(1, themeRgba('--primary-rgb', 0));
                        return g;
                    },
                    fill: true,
                    tension: 0.4,
                    borderWidth: 2.5,
                    pointRadius: 3,
                    pointHoverRadius: 6,
                    pointBackgroundColor: chartWhite,
                    pointBorderColor: primaryRgb,
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
                        backgroundColor: tooltipBg,
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
                        return barColors[context.dataIndex % barColors.length];
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
                        backgroundColor: tooltipBg,
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
    const statusPalette = barColors.slice(0, 6);
    let statusColors = statusPalette.slice(0, Math.max(statusData.length, 1));
    const statusSumRaw = statusData.reduce(function (a, b) { return a + (Number(b) || 0); }, 0);
    const statusIsEmpty = statusSumRaw <= 0;
    if (statusIsEmpty) {
        statusLabels = ['No data'];
        statusData = [1];
        statusColors = [emptyChartFill];
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
                        backgroundColor: tooltipBg,
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

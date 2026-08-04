<!DOCTYPE html>
<html lang="en" dir="ltr" data-nav-layout="vertical" data-theme-mode="light" data-header-styles="transparent" data-width="fullwidth" data-menu-styles="transparent" data-page-style="flat" data-toggled="close" data-vertical-style="default">

    <head>

        <!-- Meta Data -->
        <meta charset="UTF-8">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="Description" content="Laravel Bootstrap Responsive Admin Web Dashboard Template">
        <meta name="Author" content="Spruko Technologies Private Limited">
        <meta name="keywords" content="laravel, laravel admin panel, laravel dashboard, bootstrap dashboard, bootstrap admin panel, vite laravel, admin dashboard, admin panel in laravel, admin dashboard ui, laravel admin, admin panel template, laravel framework, dashboard, admin dashboard template, laravel template.">
    
        <!-- Title-->
        <title> Digital Invoicing </title>
        
        <!-- Favicon -->
        <link rel="icon" href="{{asset('build/assets/images/brand-logos/favicon.ico')}}" type="image/x-icon">
    
        <!-- ICONS CSS -->
        <link href="{{asset('build/assets/icon-fonts/icons.css')}}" rel="stylesheet">

        @include('layouts.components.styles')
      
        <!-- APP CSS & APP SCSS -->
        @vite(['resources/sass/app.scss'])

        @yield('styles')

    </head>

    <body class="">

        <div class="progress-top-bar"></div>

        <!-- Start::main-switcher -->
        @include('layouts.components.switcher')
        <!-- End::main-switcher -->

        <!-- Loader -->
        <div id="loader" >
            <img src="{{asset('build/assets/images/media/loader.svg')}}" alt="">
        </div>
        <!-- Loader -->

        <div class="page @if(request()->routeIs('tenants.index')) tenants-selection-page @endif">

            <!-- Start::main-header -->
            @include('layouts.components.main-header')
            <!-- End::main-header -->

            <!-- Start::main-sidebar (hidden on tenant selection page) -->
            @unless(request()->routeIs('tenants.index'))
            @include('layouts.components.main-sidebar')
            @endunless
            <!-- End::main-sidebar -->

            <!-- Start::app-content -->
            <div class="main-content app-content">
                @if(session('selected_tenant.expiry_warning') && !request()->routeIs('tenants.index'))
                    <div class="alert alert-warning alert-dismissible fade show m-3" role="alert">
                        <i class="ri-alert-line align-middle me-2 fs-16"></i>
                        <strong>Subscription Notice:</strong> The subscription for this tenant will expire in <strong>{{ session('selected_tenant.days_left') }}</strong> days. Please renew to avoid service disruption.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                 @yield('content')				
            </div>
            <!-- End::content  -->

            <!-- Start::main-footer -->
<!--            @include('layouts.components.footer')-->
            <!-- End::main-footer -->

            <!-- Start::main-modal -->
            @include('layouts.components.modal')
            @include('layouts.components.business-modal')
            <!-- End::main-modal -->

            @yield('modals')  

        </div>

        <!-- Scripts -->
        @include('layouts.components.scripts')

        <!-- Sticky JS -->
        @vite('resources/assets/js/sticky.js')

        <!-- Custom-Switcher JS -->
        @vite('resources/assets/js/custom-switcher.js')

        <!-- App JS-->
        @vite('resources/js/app.js')

        <!-- End Scripts -->

        <!-- Audit Log History Offcanvas Drawer -->
        <div class="offcanvas offcanvas-end" tabindex="-1" id="audit_history_drawer" aria-labelledby="audit_history_drawer_label" style="width: 500px; z-index: 1060;">
            <div class="offcanvas-header border-bottom">
                <h6 id="audit_history_drawer_label" class="offcanvas-title fw-semibold text-dark"><i class="ri-history-line me-2"></i> Audit Log</h6>
                <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body" id="audit_history_drawer_body" style="padding-top: 1rem;">
                {{-- Populated dynamically --}}
            </div>
        </div>

        <script>
        let activeAuditLogs = [];
        let activeLookups = {};

        function showAuditHistory(module, recordId) {
            const drawerEl = document.getElementById('audit_history_drawer');
            const drawerBody = document.getElementById('audit_history_drawer_body');
            const bsOffcanvas = bootstrap.Offcanvas.getOrCreateInstance(drawerEl);

            drawerBody.innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 text-muted">Fetching history trail...</p>
                </div>`;

            bsOffcanvas.show();

            fetch(`/audit-logs/history?module=${module}&record_id=${recordId}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => { if (!res.ok) throw new Error('Status: ' + res.status); return res.json(); })
            .then(res => {
                if (res.status !== 'success' || !res.data) {
                    drawerBody.innerHTML = `<div class="alert alert-danger">Failed to load history: ${res.message || 'Unknown error'}</div>`;
                    return;
                }

                activeAuditLogs = res.data;
                activeLookups = res.lookups || {};

                if (activeAuditLogs.length === 0) {
                    drawerBody.innerHTML = `
                        <div class="text-center py-5">
                            <i class="ri-history-line text-muted mb-2 fs-24"></i>
                            <p class="text-muted small">No modification logs exist for this record.</p>
                        </div>`;
                    return;
                }

                // Render summary directory table
                let html = `
                    <div class="mb-3">
                        <div class="table-responsive border rounded" style="max-height: 180px; overflow-y: auto;">
                            <table class="table text-nowrap listing-table mb-0" id="audit_summary_table" style="table-layout: fixed;">
                                <thead class="sticky-top">
                                    <tr>
                                        <th style="width: 25%;">Action</th>
                                        <th style="width: 40%;">Action Date</th>
                                        <th style="width: 35%;">Action By</th>
                                    </tr>
                                </thead>
                                <tbody>`;

                activeAuditLogs.forEach((log, idx) => {
                    let actionBadge = 'bg-primary-transparent text-primary';
                    if (log.Action === 'CREATE') actionBadge = 'bg-success-transparent text-success';
                    if (log.Action === 'DELETE') actionBadge = 'bg-danger-transparent text-danger';
                    if (log.Action === 'UPDATE') actionBadge = 'bg-warning-transparent text-warning';

                    const dateStr = new Date(log.CreatedOn).toLocaleString('en-US', {
                        month: 'short',
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit',
                        hour12: true
                    });

                    html += `
                        <tr style="cursor: pointer;" onclick="selectAuditLogDetail(${idx})" id="audit_row_${idx}">
                            <td><span class="badge ${actionBadge} text-uppercase">${log.Action}</span></td>
                            <td>${dateStr}</td>
                            <td class="text-truncate">${log.UserName}</td>
                        </tr>`;
                });

                html += `
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="border-top pt-3" id="audit_detail_pane">
                        <div class="text-center text-muted py-4">
                            <i class="ri-information-line fs-20 d-block mb-1"></i>
                            Select an action row above to view details
                        </div>
                    </div>`;

                drawerBody.innerHTML = html;

                // Auto-select first row
                if (activeAuditLogs.length > 0) {
                    selectAuditLogDetail(0);
                }
            })
            .catch(err => {
                drawerBody.innerHTML = `<div class="alert alert-danger">An error occurred: ${err.message}</div>`;
            });
        }

        function selectAuditLogDetail(index) {
            // Highlight selected summary row
            const rows = document.querySelectorAll('#audit_summary_table tbody tr');
            rows.forEach(r => {
                r.querySelectorAll('td').forEach(td => {
                    td.style.setProperty('background-color', '', 'important');
                    td.style.setProperty('color', '', 'important');
                });
            });
            const selectedRow = document.getElementById(`audit_row_${index}`);
            if (selectedRow) {
                selectedRow.querySelectorAll('td').forEach(td => {
                    td.style.setProperty('background-color', 'rgba(var(--primary-rgb), 0.08)', 'important');
                    td.style.setProperty('color', 'rgb(var(--primary-rgb))', 'important');
                });
            }

            const log = activeAuditLogs[index];
            const detailPane = document.getElementById('audit_detail_pane');
            if (!log || !detailPane) return;

            let actionBadge = 'bg-primary-transparent text-primary';
            if (log.Action === 'CREATE') actionBadge = 'bg-success-transparent text-success';
            if (log.Action === 'DELETE') actionBadge = 'bg-danger-transparent text-danger';
            if (log.Action === 'UPDATE') actionBadge = 'bg-warning-transparent text-warning';

            const dateStr = new Date(log.CreatedOn).toLocaleString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: true
            });

            // Parse changes
            let diffHtml = '';
            try {
                const oldVals = typeof log.OldValues === 'string' ? JSON.parse(log.OldValues) : log.OldValues || {};
                const newVals = typeof log.NewValues === 'string' ? JSON.parse(log.NewValues) : log.NewValues || {};
                const allKeys = new Set([...Object.keys(oldVals), ...Object.keys(newVals)]);
                const ignored = ['password', 'RequestID', 'CreatedOn', 'UpdatedOn', 'CreatedBy', 'UpdatedBy'];
                
                let fields = [];
                allKeys.forEach(k => {
                    if (ignored.includes(k)) return;
                    const o = oldVals[k] !== undefined ? oldVals[k] : null;
                    const n = newVals[k] !== undefined ? newVals[k] : null;
                    if (JSON.stringify(o) !== JSON.stringify(n)) {
                        fields.push({ key: k, old: o, new: n });
                    }
                });

                if (fields.length > 0) {
                    diffHtml = `
                        <div class="table-responsive mt-2">
                            <table class="table text-nowrap listing-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Module</th>
                                        <th>From</th>
                                        <th>To</th>
                                    </tr>
                                </thead>
                                <tbody>`;
                    
                    fields.forEach(f => {
                        let lookup = activeLookups[f.key] || null;
                        
                        let oldValStr = f.old;
                        if (lookup && lookup[f.old] !== undefined) {
                            oldValStr = lookup[f.old];
                        } else if (f.old === null) {
                            oldValStr = '[Not Set]';
                        }

                        let newValStr = f.new;
                        if (lookup && lookup[f.new] !== undefined) {
                            newValStr = lookup[f.new];
                        } else if (f.new === null) {
                            newValStr = '[Removed]';
                        }

                        const oldIsSpecial = f.old === null;
                        const newIsSpecial = f.new === null;

                        const oldSpan = oldIsSpecial 
                            ? `<span class="text-muted italic">${oldValStr}</span>`
                            : `<span class="text-decoration-line-through">${escapeHtml(String(oldValStr))}</span>`;

                        const newSpan = newIsSpecial 
                            ? `<span class="text-muted italic">${newValStr}</span>`
                            : `<span>${escapeHtml(String(newValStr))}</span>`;

                        diffHtml += `
                            <tr>
                                <td>${f.key}</td>
                                <td>${oldSpan}</td>
                                <td>${newSpan}</td>
                            </tr>`;
                    });
                    
                    diffHtml += '</tbody></table></div>';
                } else {
                    diffHtml = '<div class="alert alert-light py-2 text-center text-muted small">No field differences logged (e.g. metadata or ignorable fields modified).</div>';
                }
            } catch(e) {
                console.error("Diff parse error:", e);
                diffHtml = '<div class="alert alert-danger small">Error parsing record changes.</div>';
            }

            detailPane.innerHTML = `
                <div class="pb-2 border-bottom mb-2">
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="badge ${actionBadge} text-uppercase">${log.Action} Details</span>
                        <small class="text-muted">${dateStr}</small>
                    </div>
                    <div class="mt-2">
                        <p class="mb-0 text-dark">Performed by: ${log.UserName}</p>
                        <small class="text-muted d-block">IP: ${log.IPAddress || 'N/A'} | Agent: ${log.UserAgent || 'N/A'}</small>
                    </div>
                </div>
                ${diffHtml}`;
        }

        function escapeHtml(text) {
            if (!text) return '';
            return text
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }
        </script>

    </body>

</html>

{{-- ══════════════════════════════════════════════════════════════════════ --}}
{{-- SHARED BATCH INVOICES VIEW MODAL (Used by Bulk Invoices & Breakup Invoices) --}}
{{-- Any change here will automatically reflect in both modules!           --}}
{{-- ══════════════════════════════════════════════════════════════════════ --}}

<style>
    /* ── Batch Invoices Modal (View Invoices UI Aligned with Portal Theme) ── */
    #batchInvoicesModal .modal-content {
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }
    #batchInvoicesModal .modal-header {
        background-color: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        padding: 0.65rem 1.15rem;
    }
    #batchInvoicesModal .modal-footer {
        background-color: #f8fafc;
        border-top: 1px solid #e2e8f0;
        padding: 0.55rem 1.15rem;
    }
    #batchInvoicesDetailTable {
        font-size: 12.5px;
        margin-bottom: 0;
        font-family: inherit;
    }
    #batchInvoicesDetailTable thead th {
        padding: 0.35rem 0.55rem !important;
        font-size: 12px;
        font-weight: 600;
        color: #334155;
        background-color: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        vertical-align: middle;
        white-space: nowrap;
    }
    #batchInvoicesDetailTable tbody td,
    #batchInvoicesDetailTable tfoot td {
        padding: 0.28rem 0.55rem !important;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
        line-height: 1.3;
        font-size: 12px;
    }
    #batchInvoicesDetailTable .batch-invoice-row {
        transition: background-color 0.15s ease;
    }
    #batchInvoicesDetailTable .batch-invoice-row:hover {
        background-color: #f8fafc;
    }
    .toggle-items-btn {
        width: 22px;
        height: 22px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        color: #475569;
        background-color: #f1f5f9;
        border: 1px solid #e2e8f0;
        transition: all 0.2s ease;
    }
    .toggle-items-btn:hover {
        background-color: rgba(var(--primary-rgb, 111, 66, 193), 0.12);
        color: var(--primary-color, #6f42c1);
        border-color: rgba(var(--primary-rgb, 111, 66, 193), 0.3);
    }
    .toggle-items-btn.active {
        background-color: var(--primary-color, #6f42c1);
        color: #ffffff;
        border-color: var(--primary-color, #6f42c1);
    }
    .batch-items-container {
        background-color: #f8fafc;
        border-top: 1px dashed #cbd5e1;
        border-bottom: 1px solid #e2e8f0;
        padding: 10px 14px;
    }
    .batch-inner-table {
        font-size: 12px;
        background-color: #ffffff;
        margin-bottom: 0;
        font-family: inherit;
    }
    .batch-inner-table thead th {
        background-color: #f8fafc !important;
        color: #334155;
        font-size: 12px;
        font-weight: 600;
        padding: 0.35rem 0.55rem !important;
        border-bottom: 1px solid #e2e8f0;
        vertical-align: middle;
        white-space: nowrap;
    }
    .batch-inner-table tbody td {
        padding: 0.28rem 0.55rem !important;
        font-size: 11.5px;
        vertical-align: middle;
        border-color: #f1f5f9;
        white-space: nowrap;
    }

    /* ── Modal Tabs Styling ── */
    .batch-modal-tabs-container {
        background-color: transparent;
        border-bottom: 1px solid #e2e8f0;
        padding: 0;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        justify-content: flex-start;
        flex-wrap: wrap;
    }
    .modal-invoice-tabs {
        border-bottom: none;
        margin-bottom: 0;
        display: flex;
        gap: 6px;
        flex-wrap: nowrap;
        overflow-x: auto;
    }
    .modal-invoice-tabs .nav-link {
        border: 1px solid #e2e8f0;
        border-bottom: 1px solid #e2e8f0;
        border-top-left-radius: 6px;
        border-top-right-radius: 6px;
        padding: 6px 13px;
        font-size: 12px;
        font-weight: 500;
        color: #475569;
        background: #ffffff;
        margin-bottom: -1px;
        transition: all 0.15s ease-in-out;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        white-space: nowrap;
        text-decoration: none;
        cursor: pointer;
        box-shadow: 0 1px 2px rgba(0,0,0,0.02);
    }
    .modal-invoice-tabs .nav-link:hover {
        color: #1e293b;
        background: #f8fafc;
        border-color: #cbd5e1;
        box-shadow: 0 2px 4px rgba(0,0,0,0.04);
    }
    .modal-invoice-tabs .nav-link.active {
        background: #ffffff;
        border-color: #cbd5e1 #cbd5e1 #ffffff;
        border-bottom: 1px solid #ffffff !important;
        font-weight: 600;
        position: relative;
        z-index: 2;
        box-shadow: 0 -2px 6px rgba(0,0,0,0.04);
    }
    .modal-invoice-tabs .nav-link .badge {
        font-size: 10.5px;
        padding: 2px 7px;
        border-radius: 12px;
        font-weight: 600;
        transition: all 0.15s ease-in-out;
    }
    .modal-invoice-tabs .tab-not-posted { border-color: #dbeafe; }
    .modal-invoice-tabs .tab-not-posted:hover { border-color: #60a5fa; }
    .modal-invoice-tabs .tab-not-posted .tab-icon { color: #0284c7; }
    .modal-invoice-tabs .tab-not-posted .tab-badge { background-color: #e0f2fe; color: #0369a1; }
    .modal-invoice-tabs .tab-not-posted.active {
        color: #0284c7;
        border-top: 3px solid #0284c7 !important;
        border-left-color: #cbd5e1;
        border-right-color: #cbd5e1;
    }
    .modal-invoice-tabs .tab-not-posted.active .tab-badge {
        background-color: #0284c7;
        color: #ffffff;
    }

    .modal-invoice-tabs .tab-posted { border-color: #dcfce7; }
    .modal-invoice-tabs .tab-posted:hover { border-color: #4ade80; }
    .modal-invoice-tabs .tab-posted .tab-icon { color: #16a34a; }
    .modal-invoice-tabs .tab-posted .tab-badge { background-color: #dcfce7; color: #15803d; }
    .modal-invoice-tabs .tab-posted.active {
        color: #16a34a;
        border-top: 3px solid #16a34a !important;
        border-left-color: #cbd5e1;
        border-right-color: #cbd5e1;
    }
    .modal-invoice-tabs .tab-posted.active .tab-badge {
        background-color: #16a34a;
        color: #ffffff;
    }

    .modal-invoice-tabs .tab-error { border-color: #fee2e2; }
    .modal-invoice-tabs .tab-error:hover { border-color: #f87171; }
    .modal-invoice-tabs .tab-error .tab-icon { color: #dc2626; }
    .modal-invoice-tabs .tab-error .tab-badge { background-color: #fee2e2; color: #b91c1c; }
    .modal-invoice-tabs .tab-error.active {
        color: #dc2626;
        border-top: 3px solid #dc2626 !important;
        border-left-color: #cbd5e1;
        border-right-color: #cbd5e1;
    }
    .modal-invoice-tabs .tab-error.active .tab-badge {
        background-color: #dc2626;
        color: #ffffff;
    }

    .modal-invoice-tabs .tab-cancel { border-color: #ffedd5; }
    .modal-invoice-tabs .tab-cancel:hover { border-color: #fb923c; }
    .modal-invoice-tabs .tab-cancel .tab-icon { color: #ea580c; }
    .modal-invoice-tabs .tab-cancel .tab-badge { background-color: #ffedd5; color: #c2410c; }
    .modal-invoice-tabs .tab-cancel.active {
        color: #ea580c;
        border-top: 3px solid #ea580c !important;
        border-left-color: #cbd5e1;
        border-right-color: #cbd5e1;
    }
    .modal-invoice-tabs .tab-cancel.active .tab-badge {
        background-color: #ea580c;
        color: #ffffff;
    }

    .modal-invoice-tabs .tab-all { border-color: #e2e8f0; }
    .modal-invoice-tabs .tab-all:hover { border-color: #94a3b8; }
    .modal-invoice-tabs .tab-all .tab-icon { color: #64748b; }
    .modal-invoice-tabs .tab-all .tab-badge { background-color: #f1f5f9; color: #475569; }
    .modal-invoice-tabs .tab-all.active {
        color: #1e293b;
        border-top: 3px solid #475569 !important;
        border-left-color: #cbd5e1;
        border-right-color: #cbd5e1;
    }
    .modal-invoice-tabs .tab-all.active .tab-badge {
        background-color: #475569;
        color: #ffffff;
    }
</style>

{{-- ── Shared Batch Invoices Modal HTML ── --}}
<div class="modal fade" id="batchInvoicesModal" tabindex="-1" aria-labelledby="batchInvoicesModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <div>
                        <h6 class="modal-title fw-semibold text-dark mb-0 fs-14 d-flex align-items-center" id="batchInvoicesModalLabel">
                            <i class="ri-file-list-3-line text-primary me-2 fs-16"></i> <span id="batchModalTitle">Batch Invoices</span>
                        </h6>
                        <div class="mt-1 d-flex align-items-center gap-1">
                            <span class="badge bg-light text-muted border font-monospace fs-10">Request ID: <span id="batchModalRequestId"></span></span>
                        </div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary-transparent text-primary fw-semibold fs-11 px-2 py-1" id="batchModalInvoicesPill">0 Invoices</span>
                    <span class="badge bg-success-transparent text-success fw-semibold fs-11 px-2 py-1" id="batchModalAmountPill">PKR 0.00</span>
                    <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body p-3">
                {{-- Quick search & helper text in modal --}}
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <div class="input-group input-group-sm" style="max-width: 320px;">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="ri-search-line"></i></span>
                        <input type="text" id="batchInvoiceFilterInput" class="form-control border-start-0" placeholder="Filter invoices in this batch..." onkeyup="filterBatchInvoicesTable()">
                    </div>
                    <div class="fs-11 text-muted d-flex align-items-center gap-1" id="batchModalSummaryText">
                        <i class="ri-information-line text-primary fs-13"></i> Showing all invoices in this batch. Click <i class="ri-arrow-down-s-line text-primary fw-bold"></i> to view line items.
                    </div>
                </div>

                {{-- Tabs Strip (Under Search, Above Table) --}}
                <div class="batch-modal-tabs-container">
                    <ul class="nav modal-invoice-tabs" id="batchModalStatusTabs">
                        <li class="nav-item">
                            <button type="button" class="nav-link tab-not-posted" data-tab-status="not_posted" onclick="switchBatchModalTab('not_posted')">
                                <i class="fas fa-clock tab-icon me-1"></i> Not Posted
                                <span class="badge tab-badge" id="batchTabCountNotPosted">0</span>
                            </button>
                        </li>
                        <li class="nav-item">
                            <button type="button" class="nav-link tab-posted" data-tab-status="posted" onclick="switchBatchModalTab('posted')">
                                <i class="fas fa-check-circle tab-icon me-1"></i> Successfully Posted
                                <span class="badge tab-badge" id="batchTabCountPosted">0</span>
                            </button>
                        </li>
                        <li class="nav-item">
                            <button type="button" class="nav-link tab-error" data-tab-status="error" onclick="switchBatchModalTab('error')">
                                <i class="fas fa-exclamation-triangle tab-icon me-1"></i> FBR Error
                                <span class="badge tab-badge" id="batchTabCountError">0</span>
                            </button>
                        </li>
                        <li class="nav-item">
                            <button type="button" class="nav-link tab-cancel" data-tab-status="cancelled" onclick="switchBatchModalTab('cancelled')">
                                <i class="fas fa-ban tab-icon me-1"></i> FBR Cancelled
                                <span class="badge tab-badge" id="batchTabCountCancelled">0</span>
                            </button>
                        </li>
                        <li class="nav-item">
                            <button type="button" class="nav-link tab-all active" data-tab-status="all" onclick="switchBatchModalTab('all')">
                                <i class="fas fa-list tab-icon me-1"></i> All Invoices
                                <span class="badge tab-badge" id="batchTabCountAll">0</span>
                            </button>
                        </li>
                    </ul>
                </div>

                {{-- Loading Spinner --}}
                <div id="batchModalLoader" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading batch invoices...</span>
                    </div>
                    <p class="text-muted mt-2 fs-12">Loading invoices and line items...</p>
                </div>

                {{-- Invoices Table Container --}}
                <div id="batchModalTableContainer" class="table-responsive rounded border d-none">
                    <table class="table text-nowrap listing-table table-hover align-middle mb-0" id="batchInvoicesDetailTable">
                        <thead>
                            <tr>
                                <th style="width: 36px;" class="text-center">#</th>
                                <th style="width: 95px;">Invoice Date</th>
                                <th>Customer</th>
                                <th style="width: 120px;">Doc No</th>
                                <th class="text-end" style="width: 110px;">Amount (PKR)</th>
                                <th class="text-end" style="width: 120px;">Fixed Notified</th>
                                <th class="text-center" style="width: 65px;">Tax %</th>
                                <th class="text-end" style="width: 105px;">GST (PKR)</th>
                                <th class="text-end" style="width: 115px;">Total (PKR)</th>
                                <th class="text-center" style="width: 95px;">FBR Status</th>
                                <th class="text-center" style="width: 45px;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="batchInvoicesTableBody">
                            {{-- Populated via AJAX --}}
                        </tbody>
                        <tfoot class="table-light sticky-bottom fw-bold fs-12 border-top" id="batchInvoicesTableFoot">
                            <tr>
                                <td colspan="4" class="text-end text-uppercase pe-2 fw-semibold text-dark">Totals:</td>
                                <td class="text-end font-monospace" id="batchFootExcl">0.00</td>
                                <td class="text-end font-monospace text-muted" id="batchFootFixed">0.00</td>
                                <td class="text-center font-monospace">-</td>
                                <td class="text-end font-monospace text-primary" id="batchFootGST">0.00</td>
                                <td class="text-end font-monospace text-dark" id="batchFootTotal">0.00</td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                    <div id="batchModalEmptyState" class="text-center py-5 d-none">
                        <i class="fas fa-search fa-2x text-muted mb-2 d-block"></i>
                        <span class="text-muted fs-12" id="batchModalEmptyMsg">No invoices match the current filter.</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-between align-items-center">
                <a href="#" id="batchModalFullViewBtn" target="_blank" class="btn btn-sm btn-outline-primary btn-wave waves-light">
                    <i class="ri-external-link-line me-1"></i> Open in Full Invoices List
                </a>
                <button type="button" class="btn btn-sm btn-light border btn-wave waves-light" data-bs-dismiss="modal">
                    <i class="ri-close-line me-1"></i> Close
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ── Shared Batch Invoices Modal Scripts ── --}}
<script>
let currentBatchRequestId = null;
let currentBatchTab = 'all';

/**
 * Open Modal & Fetch Invoices via AJAX
 * Can be called with (requestId, title, customEndpoint)
 */
function viewBatchInvoices(requestId, title, customEndpoint) {
    currentBatchRequestId = requestId;
    currentBatchTab = 'all';

    document.getElementById('batchModalTitle').textContent = title || 'Batch Invoices';
    document.getElementById('batchModalRequestId').textContent = requestId;
    document.getElementById('batchInvoiceFilterInput').value = '';

    // Reset tabs
    document.querySelectorAll('#batchModalStatusTabs .nav-link').forEach(btn => {
        btn.classList.toggle('active', btn.getAttribute('data-tab-status') === 'all');
    });

    updateFullViewLink('all');

    const summaryText = document.getElementById('batchModalSummaryText');
    if (summaryText) {
        summaryText.innerHTML = '<i class="ri-information-line text-primary fs-13"></i> Showing all invoices in this batch. Click <i class="ri-arrow-down-s-line text-primary fw-bold"></i> to view line items.';
    }

    const loader = document.getElementById('batchModalLoader');
    const container = document.getElementById('batchModalTableContainer');
    const tbody = document.getElementById('batchInvoicesTableBody');
    const emptyState = document.getElementById('batchModalEmptyState');

    loader.classList.remove('d-none');
    container.classList.add('d-none');
    if (emptyState) emptyState.classList.add('d-none');
    tbody.innerHTML = '';

    const modal = new bootstrap.Modal(document.getElementById('batchInvoicesModal'));
    modal.show();

    const fetchUrl = customEndpoint || ('/bulk-invoices/' + requestId + '/invoices');

    fetch(fetchUrl)
        .then(res => res.json())
        .then(data => {
            loader.classList.add('d-none');
            container.classList.remove('d-none');

            if (!data.status || data.status !== 'success') {
                tbody.innerHTML = '<tr><td colspan="10" class="text-center py-4 text-danger">' + (data.message || 'Failed to load invoices.') + '</td></tr>';
                return;
            }

            const batch = data.batch || {};
            const invoices = data.invoices || [];
            const counts = data.counts || {
                all: invoices.length,
                not_posted: 0,
                posted: 0,
                error: 0,
                cancelled: 0
            };

            // Update Counts on Badges
            document.getElementById('batchTabCountNotPosted').textContent = counts.not_posted ?? 0;
            document.getElementById('batchTabCountPosted').textContent = counts.posted ?? 0;
            document.getElementById('batchTabCountError').textContent = counts.error ?? 0;
            document.getElementById('batchTabCountCancelled').textContent = counts.cancelled ?? 0;
            document.getElementById('batchTabCountAll').textContent = counts.all ?? invoices.length;

            const liveTotalInvoices = (data.summary && data.summary.total_invoices !== undefined) ? data.summary.total_invoices : invoices.length;
            const liveTotalAmount = (data.summary && data.summary.total_amount !== undefined) ? data.summary.total_amount : invoices.reduce((acc, inv) => acc + (parseFloat(inv.TotalValues) || 0), 0);

            document.getElementById('batchModalInvoicesPill').innerHTML = '<i class="ri-file-text-line me-1"></i> ' + liveTotalInvoices + ' Invoices';
            document.getElementById('batchModalAmountPill').innerHTML = '<i class="ri-money-dollar-circle-line me-1"></i> PKR ' + Number(liveTotalAmount || 0).toLocaleString('en-US', { minimumFractionDigits: 2 });

            if (invoices.length === 0) {
                tbody.innerHTML = '<tr><td colspan="9" class="text-center py-4 text-muted">No invoices found for this batch.</td></tr>';
                return;
            }

            let html = '';
            invoices.forEach((inv, idx) => {
                const statusKey = inv.status_key || 'not_posted';
                const isPosted = inv.FbrTransactionID && inv.FbrTransactionID.trim() !== '';
                let fbrStatusBadge = '';

                if (statusKey === 'posted') {
                    fbrStatusBadge = '<span class="badge rounded-pill bg-success-subtle text-success" style="font-size:11px; padding:5px 10px;"><i class="fas fa-check-circle me-1"></i>Posted</span>';
                } else if (statusKey === 'error') {
                    fbrStatusBadge = '<span class="badge rounded-pill bg-danger-subtle text-danger" style="font-size:11px; padding:5px 10px;" title="FBR Error"><i class="fas fa-exclamation-triangle me-1"></i>FBR Error</span>';
                } else if (statusKey === 'cancelled') {
                    fbrStatusBadge = '<span class="badge rounded-pill bg-warning-subtle text-warning" style="font-size:11px; padding:5px 10px;"><i class="fas fa-ban me-1"></i>FBR Cancelled</span>';
                } else {
                    fbrStatusBadge = '<span class="badge rounded-pill bg-light text-muted border" style="font-size:11px; padding:5px 10px;"><i class="fas fa-clock me-1 text-muted"></i>Not Posted</span>';
                }

                const customerDisplay = inv.CustomerName || inv.CustomerNTN || inv.CustomerCode || 'Walk-In';
                const customerCodeSubtitle = inv.CustomerCode ? '<small class="text-muted font-monospace fs-10 d-block">' + inv.CustomerCode + '</small>' : '';

                const exclVal = parseFloat(inv.ValueSalesExcludingST) || 0;
                const fixedVal = parseFloat(inv.FixedNotifiedValueOrRetailPrice) || 0;
                const gstVal = parseFloat(inv.SalesTaxApplicable) || 0;
                const totalVal = parseFloat(inv.TotalValues) || 0;
                const taxRateDisplay = inv.tax_rate ? `<span class="text-dark font-monospace fs-12">${inv.tax_rate}</span>` : '<span class="text-muted fs-11">-</span>';

                html += `
                <tr class="batch-invoice-row" id="inv-row-${inv.HeaderID}" data-status="${statusKey}" data-excl="${exclVal}" data-fixed="${fixedVal}" data-gst="${gstVal}" data-total="${totalVal}">
                    <td class="text-center">
                        <button type="button" class="btn btn-xs toggle-items-btn" id="toggle-btn-${inv.HeaderID}" onclick="toggleBatchInvoiceItems(${inv.HeaderID})" title="Toggle Line Items">
                            <i class="ri-arrow-right-s-line fs-14" id="toggle-icon-${inv.HeaderID}"></i>
                        </button>
                    </td>
                    <td><span class="fs-12 text-dark font-monospace">${inv.InvoiceDate || ''}</span></td>
                    <td>
                        <div class="fw-semibold text-dark fs-12">${customerDisplay}</div>
                        ${customerCodeSubtitle}
                    </td>
                    <td><span class="badge bg-primary-transparent text-primary font-monospace fw-bold fs-11">${inv.DocNo || ''}</span></td>
                    <td class="text-end font-monospace">${exclVal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                    <td class="text-end font-monospace text-muted">${fixedVal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                    <td class="text-center">${taxRateDisplay}</td>
                    <td class="text-end font-monospace text-primary">${gstVal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                    <td class="text-end font-monospace fw-bold text-dark">${totalVal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                    <td class="text-center">
                        ${fbrStatusBadge}
                        ${isPosted ? `<small class="d-block text-muted font-monospace fs-10 mt-1">TxID: <strong>${inv.FbrTransactionID}</strong></small>` : ''}
                    </td>
                    <td class="text-center">
                        <a href="/invoices/${inv.HeaderID}/print-view" target="_blank" class="btn btn-xs btn-icon btn-outline-light text-muted hover-primary" title="Print Invoice">
                            <i class="ri-printer-line fs-13"></i>
                        </a>
                    </td>
                </tr>
                <tr class="d-none batch-items-row" id="items-row-${inv.HeaderID}" data-parent-status="${statusKey}">
                    <td colspan="11" class="p-0 border-0">
                        <div class="batch-items-container">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge bg-primary-transparent text-primary fw-semibold fs-11">
                                    <i class="ri-shopping-bag-3-line me-1"></i> Products & Services (${inv.items_count || (inv.items ? inv.items.length : 0)})
                                </span>
                                <span class="badge bg-white text-dark border font-monospace fs-11">
                                    <span class="text-muted fw-normal">Sale of Term:</span> <strong class="text-primary">${inv.SaleOfTerm || 'CASH'}</strong>
                                </span>
                            </div>
                            <div class="table-responsive rounded border bg-white shadow-none">
                                <table class="table table-sm table-bordered align-middle text-nowrap mb-0 batch-inner-table">
                                    <thead>
                                        <tr>
                                            <th style="width: 32px;" class="text-center">#</th>
                                            <th>Product / Services</th>
                                            <th>Description</th>
                                            <th class="text-center">UOM</th>
                                            <th class="text-end">Qty</th>
                                            <th class="text-end">Unit Rate</th>
                                            <th class="text-end">Amount</th>
                                            <th class="text-end">Fixed Notified</th>
                                            <th class="text-center">Tax %</th>
                                            <th class="text-end">GST Amount</th>
                                            <th class="text-end">Net</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${(inv.items || []).map((it, i) => {
                                            const itemFixedVal = parseFloat(it.FixedNotifiedValueOrRetailPrice) || 0;
                                            const itemTaxRate = it.SaleTypeToRateDesc ? (it.SaleTypeToRateDesc.match(/(\d+(\.\d+)?)%/) ? it.SaleTypeToRateDesc.match(/(\d+(\.\d+)?)%/)[0] : it.SaleTypeToRateDesc) : (inv.tax_rate || '18%');
                                            return `
                                            <tr>
                                                <td class="text-center text-muted fs-11">${i + 1}</td>
                                                <td>
                                                    <strong class="text-dark">${it.ProductCode || ''}</strong>
                                                    ${it.ProductName ? '<span class="text-muted fs-11 ms-1">('+it.ProductName+')</span>' : ''}
                                                </td>
                                                <td class="text-dark fs-11">${it.ProductDescription || '-'}</td>
                                                <td class="text-center">
                                                    <span class="badge bg-light text-secondary border font-monospace fs-10">${it.Uom_Desc || '-'}</span>
                                                    ${it.HsCode ? '<small class="text-muted font-monospace fs-9 d-block mt-0.5">HS: ' + it.HsCode + '</small>' : ''}
                                                </td>
                                                <td class="text-end font-monospace">${Number(it.Quantity || 0).toFixed(2)}</td>
                                                <td class="text-end font-monospace">${Number(it.Rate || 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}</td>
                                                <td class="text-end font-monospace">${Number(it.Amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}</td>
                                                <td class="text-end font-monospace text-muted">${itemFixedVal.toLocaleString('en-US', { minimumFractionDigits: 2 })}</td>
                                                <td class="text-center"><span class="text-dark font-monospace fs-11">${itemTaxRate}</span></td>
                                                <td class="text-end font-monospace text-muted">${Number(it.GSTAmount || 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}</td>
                                                <td class="text-end font-monospace fw-bold text-dark">${Number(it.TotalValues || 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}</td>
                                            </tr>
                                            `;
                                        }).join('')}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </td>
                </tr>
                `;
            });

            tbody.innerHTML = html;
            applyBatchModalFilter();
        })
        .catch(err => {
            loader.classList.add('d-none');
            container.classList.remove('d-none');
            tbody.innerHTML = '<tr><td colspan="9" class="text-center py-4 text-danger">Error loading batch invoices: ' + err.message + '</td></tr>';
        });
}

/**
 * Switch Active Tab in Modal
 */
function switchBatchModalTab(tabStatus) {
    currentBatchTab = tabStatus;

    document.querySelectorAll('#batchModalStatusTabs .nav-link').forEach(btn => {
        btn.classList.toggle('active', btn.getAttribute('data-tab-status') === tabStatus);
    });

    updateFullViewLink(tabStatus);

    const summaryText = document.getElementById('batchModalSummaryText');
    const labelMap = {
        'all': 'all invoices',
        'not_posted': 'Not Posted invoices',
        'posted': 'Successfully Posted invoices',
        'error': 'FBR Error invoices',
        'cancelled': 'FBR Cancelled invoices'
    };
    if (summaryText) {
        summaryText.innerHTML = `<i class="ri-information-line text-primary fs-13"></i> Showing ${labelMap[tabStatus] || 'invoices'} in this batch. Click <i class="ri-arrow-down-s-line text-primary fw-bold"></i> to view line items.`;
    }

    applyBatchModalFilter();
}

/**
 * Update bottom "Open in Full Invoices List" link
 */
function updateFullViewLink(tabStatus) {
    const fullViewBtn = document.getElementById('batchModalFullViewBtn');
    if (fullViewBtn && currentBatchRequestId) {
        fullViewBtn.href = '/invoices?search=' + encodeURIComponent(currentBatchRequestId) + '&fbr_status=' + encodeURIComponent(tabStatus);
    }
}

/**
 * Apply both active Tab filter and Search input
 */
function applyBatchModalFilter() {
    const filter = (document.getElementById('batchInvoiceFilterInput').value || '').toLowerCase();
    const rows = document.querySelectorAll('.batch-invoice-row');
    const emptyState = document.getElementById('batchModalEmptyState');
    const tableEl = document.getElementById('batchInvoicesDetailTable');
    let visibleCount = 0;
    let sumExcl = 0;
    let sumFixed = 0;
    let sumGst = 0;
    let sumTotal = 0;

    rows.forEach(row => {
        const rowStatus = row.getAttribute('data-status');
        const text = row.textContent.toLowerCase();
        const headerId = row.id.replace('inv-row-', '');
        const itemsRow = document.getElementById('items-row-' + headerId);

        const matchesTab = (currentBatchTab === 'all' || rowStatus === currentBatchTab);
        const matchesSearch = text.includes(filter);

        if (matchesTab && matchesSearch) {
            row.style.display = '';
            visibleCount++;
            sumExcl += parseFloat(row.getAttribute('data-excl') || 0);
            sumFixed += parseFloat(row.getAttribute('data-fixed') || 0);
            sumGst += parseFloat(row.getAttribute('data-gst') || 0);
            sumTotal += parseFloat(row.getAttribute('data-total') || 0);
        } else {
            row.style.display = 'none';
            if (itemsRow) itemsRow.classList.add('d-none');
        }
    });

    const footExcl = document.getElementById('batchFootExcl');
    const footFixed = document.getElementById('batchFootFixed');
    const footGst = document.getElementById('batchFootGST');
    const footTotal = document.getElementById('batchFootTotal');
    const footTable = document.getElementById('batchInvoicesTableFoot');

    if (footExcl) footExcl.textContent = sumExcl.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    if (footFixed) footFixed.textContent = sumFixed.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    if (footGst) footGst.textContent = sumGst.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    if (footTotal) footTotal.textContent = sumTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    if (footTable) footTable.style.display = visibleCount > 0 ? '' : 'none';

    if (emptyState && tableEl) {
        if (visibleCount === 0 && rows.length > 0) {
            emptyState.classList.remove('d-none');
            const emptyMsg = document.getElementById('batchModalEmptyMsg');
            if (emptyMsg) {
                const tabTitles = {
                    'not_posted': 'Not Posted',
                    'posted': 'Successfully Posted',
                    'error': 'FBR Error',
                    'cancelled': 'FBR Cancelled',
                    'all': 'All Invoices'
                };
                emptyMsg.textContent = `No invoices found in '${tabTitles[currentBatchTab] || currentBatchTab}' status matching your filter.`;
            }
        } else {
            emptyState.classList.add('d-none');
        }
    }
}

/**
 * Toggle Line Items Accordion in Modal
 */
function toggleBatchInvoiceItems(headerId) {
    const itemsRow = document.getElementById('items-row-' + headerId);
    const icon = document.getElementById('toggle-icon-' + headerId);
    const btn = document.getElementById('toggle-btn-' + headerId);
    if (!itemsRow) return;

    if (itemsRow.classList.contains('d-none')) {
        itemsRow.classList.remove('d-none');
        if (icon) {
            icon.classList.remove('ri-arrow-right-s-line');
            icon.classList.add('ri-arrow-down-s-line');
        }
        if (btn) btn.classList.add('active');
    } else {
        itemsRow.classList.add('d-none');
        if (icon) {
            icon.classList.remove('ri-arrow-down-s-line');
            icon.classList.add('ri-arrow-right-s-line');
        }
        if (btn) btn.classList.remove('active');
    }
}

/**
 * Live search filter within the modal
 */
function filterBatchInvoicesTable() {
    applyBatchModalFilter();
}
</script>


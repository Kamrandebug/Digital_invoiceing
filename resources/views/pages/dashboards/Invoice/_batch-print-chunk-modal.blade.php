{{-- ══════════════════════════════════════════════════════════════════════════════ --}}
{{--  BATCH PRINT CHUNK MODAL — Handles large batches (>100 invoices) cleanly      --}}
{{--  Uses Standard Theme & Bootstrap 5 classes                                      --}}
{{-- ══════════════════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="batchPrintChunkModal" tabindex="-1" aria-labelledby="batchPrintChunkModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow border-0">
            <div class="modal-header bg-light border-bottom py-2 px-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar avatar-sm bg-primary-transparent text-primary rounded-circle d-flex align-items-center justify-content-center">
                        <i class="fas fa-print"></i>
                    </div>
                    <div>
                        <h6 class="modal-title fw-bold mb-0" id="batchPrintChunkModalLabel">Print Invoices in Batch</h6>
                        <small class="text-muted" id="batchPrintModalSubtitle">Select a 100-invoice part to print</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-3">
                <div class="alert alert-info py-2 px-3 mb-3 fs-12 d-flex align-items-center gap-2">
                    <i class="fas fa-info-circle fa-lg text-info flex-shrink-0"></i>
                    <div>
                        This batch contains <strong id="batchPrintTotalCountBadge">0</strong> invoices. To prevent browser memory crashes and printer paper jams, invoices are organized into <strong>100-invoice parts</strong>. Each part opens in a new tab ready for printing or saving to PDF.
                    </div>
                </div>

                <div class="card border mb-3">
                    <div class="card-header bg-light py-2 px-3 d-flex justify-content-between align-items-center">
                        <span class="fw-semibold fs-12 text-dark">
                            <i class="fas fa-layer-group me-1 text-primary"></i> Batch Parts (100 Invoices per Part)
                        </span>
                        <span class="badge bg-secondary-transparent text-secondary fs-11" id="batchPrintPartsSummary">0 Parts</span>
                    </div>
                    <div class="card-body p-2" id="batchPrintPartsContainer" style="max-height: 280px; overflow-y: auto;">
                        {{-- Populated dynamically via JS --}}
                    </div>
                </div>

                {{-- Custom Range Accordion / Card --}}
                <div class="border rounded p-2 bg-light">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="fw-semibold fs-12 text-dark">
                            <i class="fas fa-sliders-h me-1 text-secondary"></i> Custom Invoice Range
                        </span>
                        <small class="text-muted">Max 100 invoices per request</small>
                    </div>
                    <div class="row g-2 align-items-center">
                        <div class="col-sm-4">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white">Start From #</span>
                                <input type="number" id="customPrintStart" class="form-control form-control-sm text-end" value="1" min="1" step="1">
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white">Count</span>
                                <input type="number" id="customPrintCount" class="form-control form-control-sm text-end" value="100" min="1" max="100" step="1">
                            </div>
                        </div>
                        <div class="col-sm-4 text-end">
                            <button type="button" class="btn btn-sm btn-outline-primary w-100" onclick="triggerCustomBatchPrint()">
                                <i class="fas fa-external-link-alt me-1"></i> Print Range
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer bg-light py-2 px-3">
                <button type="button" class="btn btn-sm btn-light border text-dark" data-bs-dismiss="modal">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    let activeBatchPrintRequestId = null;
    let activeBatchPrintTotal = 0;
    let activeBatchPrintTitle = '';

    /**
     * Trigger Batch Print:
     * - If totalInvoices <= 100: directly opens print window
     * - If totalInvoices > 100: opens chunk selector modal
     */
    function triggerBatchPrint(requestId, totalInvoices, batchTitle) {
        if (!requestId) {
            alert('Invalid batch request ID.');
            return;
        }

        totalInvoices = parseInt(totalInvoices, 10) || 0;
        activeBatchPrintRequestId = requestId;
        activeBatchPrintTotal = totalInvoices;
        activeBatchPrintTitle = batchTitle || 'Batch Invoices';

        const printBaseUrl = "{{ url('/invoices/batch') }}/" + encodeURIComponent(requestId) + "/print";

        if (totalInvoices <= 100) {
            // Direct 1-click print in new tab
            window.open(printBaseUrl + '?offset=0&limit=100', '_blank');
            return;
        }

        // Open Chunk Modal for >100 invoices
        document.getElementById('batchPrintModalSubtitle').textContent = activeBatchPrintTitle;
        document.getElementById('batchPrintTotalCountBadge').textContent = totalInvoices;

        const chunkSize = 100;
        const numParts = Math.ceil(totalInvoices / chunkSize);
        document.getElementById('batchPrintPartsSummary').textContent = numParts + ' Parts (' + totalInvoices + ' Total Invoices)';

        const container = document.getElementById('batchPrintPartsContainer');
        container.innerHTML = '';

        for (let p = 1; p <= numParts; p++) {
            const offset = (p - 1) * chunkSize;
            const end = Math.min(totalInvoices, offset + chunkSize);
            const countInPart = end - offset;
            const partUrl = printBaseUrl + '?offset=' + offset + '&limit=' + chunkSize;

            const div = document.createElement('div');
            div.className = 'd-flex align-items-center justify-content-between p-2 mb-1 bg-white border rounded hover-bg-light';
            div.innerHTML = `
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary text-white fs-11">Part ${p}</span>
                    <strong class="fs-12 text-dark">Invoices ${offset + 1} – ${end}</strong>
                    <span class="text-muted fs-11">(${countInPart} ${countInPart === 1 ? 'invoice' : 'invoices'})</span>
                </div>
                <a href="${partUrl}" target="_blank" class="btn btn-sm btn-outline-success">
                    <i class="fas fa-print me-1"></i> Print Part ${p}
                </a>
            `;
            container.appendChild(div);
        }

        // Reset custom range inputs
        document.getElementById('customPrintStart').value = 1;
        document.getElementById('customPrintStart').max = totalInvoices;
        document.getElementById('customPrintCount').value = Math.min(100, totalInvoices);

        const modalEl = document.getElementById('batchPrintChunkModal');
        const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modal.show();
    }

    function triggerCustomBatchPrint() {
        if (!activeBatchPrintRequestId) return;
        const start = Math.max(1, parseInt(document.getElementById('customPrintStart').value, 10) || 1);
        let count = Math.max(1, Math.min(100, parseInt(document.getElementById('customPrintCount').value, 10) || 100));
        const offset = start - 1;

        const printBaseUrl = "{{ url('/invoices/batch') }}/" + encodeURIComponent(activeBatchPrintRequestId) + "/print";
        const url = printBaseUrl + '?offset=' + offset + '&limit=' + count;
        window.open(url, '_blank');
    }
</script>


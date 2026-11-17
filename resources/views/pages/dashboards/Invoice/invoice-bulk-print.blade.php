@php
    $invoicesData    = $invoicesData ?? [];
    $totalInvoices   = $totalInvoices ?? count($invoicesData);
    $seller          = $seller ?? null;
    $template        = $template ?? null;
    $allTemplates    = $allTemplates ?? collect();
    $templateFields  = $templateFields ?? collect();
    $templateColumns = $templateColumns ?? collect();
    $orientation     = $orientation ?? 'portrait';
    $batchTitle      = $batchTitle ?? 'Invoices';
    $requestId       = $requestId ?? null;
    $offset          = $offset ?? 0;
    $limit           = $limit ?? 100;
    $isBatch         = $isBatch ?? false;

    // Template design settings
    $tplId               = (int) ($template->ID ?? 1);
    $tplName             = trim((string) ($template->TemplateName ?? 'Standard Tax Invoice'));
    $docTitle            = trim((string) ($template->CustomTitle ?? 'Sales Tax Invoice')) ?: 'Sales Tax Invoice';
    $paperSize           = trim((string) ($template->PaperSize ?? 'A4')) ?: 'A4';
    $themeStyle          = strtolower(trim((string) ($template->ThemeStyle ?? 'classic'))) ?: 'classic';
    $accentColor         = trim((string) ($template->AccentColor ?? '#1e3a5f')) ?: '#1e3a5f';
    $fontFamily          = trim((string) ($template->FontFamily ?? 'Arial, sans-serif')) ?: 'Arial, sans-serif';
    $baseFontSize        = max(9, min(16, (int) ($template->BaseFontSize ?? 12)));
    $logoMaxHeight       = max(40, min(200, (int) ($template->LogoMaxHeight ?? 105)));
    $topMarginPx         = max(10, min(250, (int) ($template->TopMarginPx ?? 35)));

    $countThisBatch = count($invoicesData);
    $printDocumentTitle = $isBatch
        ? 'Batch-Invoices-'.\Carbon\Carbon::now()->format('Ymd').'-'.($requestId ?: 'batch')
        : 'Bulk-Invoices-'.\Carbon\Carbon::now()->format('Ymd').'-'.$countThisBatch.'-items';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>{{ $printDocumentTitle }}</title>

    {{-- Theme CSS (Bootstrap 5, FontAwesome, Custom CSS) --}}
    <link href="{{ asset('build/assets/libs/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('build/assets/themecss/fontawesome/css/all.min.css') }}" rel="stylesheet">
    <link href="{{ asset('build/assets/themecss/custom.css') }}" rel="stylesheet">

    <style>
        /* Printable Document Sheet Layout for Multi-Tenant Bulk Printing */
        @page { size: {{ $paperSize }} {{ $orientation }}; margin: 20px; }
        @media print {
            @page { size: {{ $paperSize }} {{ $orientation }}; margin: 20px; }
            html, body {
                margin: 0 !important;
                padding: 0 !important;
                background: #fff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .no-print { display: none !important; }

            .bulk-invoice-sheet {
                page-break-after: always !important;
                break-after: page !important;
                padding-top: {{ $topMarginPx }}px !important;
                padding-bottom: 35px !important;
                padding-left: 0 !important;
                padding-right: 0 !important;
                margin-left: 20px !important;
                margin-right: 20px !important;
                width: calc(100% - 40px) !important;
                max-width: calc(100% - 40px) !important;
                box-sizing: border-box !important;
                box-shadow: none !important;
                border: none !important;
                background: #fff !important;
            }
            .bulk-invoice-sheet:last-of-type {
                page-break-after: auto !important;
                break-after: auto !important;
            }

            .print-section-1-logo img { max-height: {{ $logoMaxHeight }}px !important; max-width: 300px !important; }
            .fbr-brand-logo { max-width: 1in !important; max-height: 1in !important; }
            .fbr-qr-image { width: 1in !important; height: 1in !important; }
            .after-table-summary {
                display: grid !important;
                grid-template-columns: minmax(0, 1fr) 360px !important;
                column-gap: 28px !important;
                page-break-inside: avoid !important;
                break-inside: avoid-page !important;
            }
            .invoice-print-footer, .fbr-digital-section, .totals {
                page-break-inside: avoid !important;
                break-inside: avoid-page !important;
            }
            table.items tr {
                page-break-inside: avoid !important;
                break-inside: avoid-page !important;
            }
            table.items thead th {
                font-size: {{ max(9, $baseFontSize - 2) }}px !important;
            }
            .invoice-print-watermark {
                display: block !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .invoice-cancelled-stamp {
                display: inline-block !important;
                color: #dc2626 !important;
                border: 2px solid #dc2626 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }

        body {
            background: #f1f5f9;
            color: #222;
            margin: 0;
            padding: 0;
        }

        /* Screen Presentation */
        @media screen {
            body {
                background: #475569;
                padding-bottom: 80px;
            }
            .bulk-invoice-sheet {
                position: relative;
                font-family: {!! $fontFamily !!};
                font-size: {{ $baseFontSize }}px;
                line-height: {{ $themeStyle === 'compact' ? '1.25' : '1.4' }};
                width: 210mm;
                max-width: 95vw;
                min-height: 270mm;
                margin: 20px auto 30px auto;
                padding: {{ $topMarginPx }}px 35px 35px 35px;
                box-sizing: border-box;
                background: #ffffff;
                box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.35), 0 8px 10px -6px rgba(0, 0, 0, 0.25);
                border-radius: 4px;
            }
            body.orientation-landscape .bulk-invoice-sheet {
                width: 297mm;
                min-height: 190mm;
            }
        }

        .invoice-print {
            position: relative;
            font-family: {!! $fontFamily !!};
            font-size: {{ $baseFontSize }}px;
            line-height: {{ $themeStyle === 'compact' ? '1.25' : '1.4' }};
            box-sizing: border-box;
        }

        .print-section-1 {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            padding-bottom: {{ $themeStyle === 'compact' ? '8px' : '12px' }};
            border-bottom: 2px solid {{ $themeStyle === 'modern' ? $accentColor : '#333' }};
        }
        .print-section-1-logo { flex: 0 0 auto; max-width: 320px; align-self: flex-start; }
        .print-section-1-logo img {
            max-width: 300px;
            max-height: {{ $logoMaxHeight }}px;
            width: auto;
            height: auto;
            display: block;
            object-fit: contain;
        }
        .print-section-1-right {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            margin-left: auto;
        }
        .invoice-doc-title {
            text-align: right;
            margin: 0 0 6px 0;
            font-size: {{ $themeStyle === 'compact' ? '20px' : '24px' }};
            font-weight: 800;
            letter-spacing: 0.04em;
            color: {{ $themeStyle === 'modern' ? $accentColor : '#111' }};
            line-height: 1.1;
        }
        .print-section-1-meta { text-align: right; width: 100%; }
        .print-section-1-meta table { margin-left: auto; border-collapse: collapse; }
        .print-section-1-meta th { text-align: right; padding: 2px 8px 2px 0; color: #555; font-weight: 600; white-space: nowrap; vertical-align: top; }
        .print-section-1-meta td { text-align: right; padding: 2px 0; }

        .print-section-2 {
            display: grid;
            grid-template-columns: 38% 24% 38%;
            align-items: start;
            padding: {{ $themeStyle === 'compact' ? '8px 0 10px' : '14px 0 16px' }};
            border-bottom: 2px solid {{ $themeStyle === 'modern' ? $accentColor : '#333' }};
            margin-bottom: {{ $themeStyle === 'compact' ? '10px' : '16px' }};
        }
        .party-col { min-width: 0; font-size: 10px; text-align: left; }
        @if($themeStyle === 'modern')
        .party-col {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-top: 3px solid {{ $accentColor }};
            border-radius: 6px;
            padding: 10px 12px;
        }
        @endif
        .party-col h2 {
            margin: 0 0 10px 0;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: {{ $themeStyle === 'modern' ? $accentColor : '#111' }};
            text-decoration: {{ $themeStyle === 'modern' ? 'none' : 'underline' }};
            text-decoration-thickness: 2px;
            text-underline-offset: 5px;
        }
        .party-col table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .party-col th { text-align: left; padding: 2px 8px 2px 0; width: 44%; color: #555; font-weight: 600; vertical-align: top; line-height: 1.35; overflow-wrap: break-word; }
        .party-col td { padding: 2px 0 2px 4px; width: 56%; vertical-align: top; text-align: left; overflow-wrap: break-word; line-height: 1.35; text-transform: uppercase; }
        .party-col .party-name { font-size: 12px; font-weight: 700; margin-bottom: 6px; color: #111; text-transform: uppercase; }

        table.items { width: 100%; border-collapse: collapse; margin: {{ $themeStyle === 'compact' ? '10px 0' : '16px 0' }}; }
        table.items th, table.items td {
            border: 1px solid {{ $themeStyle === 'modern' ? '#cbd5e1' : '#ddd' }};
            padding: {{ $themeStyle === 'compact' ? '4px 6px' : '6px 8px' }};
        }
        table.items thead th {
            background: {{ $themeStyle === 'modern' ? $accentColor : '#f5f5f5' }} !important;
            color: {{ $themeStyle === 'modern' ? '#ffffff' : '#222222' }} !important;
            font-size: {{ max(9, $baseFontSize - 2) }}px !important;
            font-weight: 600;
            vertical-align: middle;
            line-height: 1.2;
        }
        table.items tfoot th {
            background: #f8fafc !important;
            font-size: {{ max(10, $baseFontSize - 1) }}px !important;
            font-weight: 700;
            border-top: 2px solid {{ $themeStyle === 'modern' ? $accentColor : '#333' }};
        }
        table.items td { font-size: {{ $themeStyle === 'compact' ? max(9, $baseFontSize - 1) : $baseFontSize }}px; }
        table.items td.col-value-amount { white-space: nowrap; font-variant-numeric: tabular-nums; }
        table.items td.td-desc-goods { vertical-align: top; }
        .after-table-summary {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 360px;
            align-items: start;
            column-gap: 28px;
            margin-top: {{ $themeStyle === 'compact' ? '10px' : '16px' }};
        }
        .invoice-print-footer {
            padding-bottom: 14px;
            border-bottom: 2px solid {{ $themeStyle === 'modern' ? $accentColor : '#333' }};
        }
        .totals { width: 100%; max-width: 360px; justify-self: end; }
        .totals table { width: 100%; border-collapse: separate; border-spacing: 0; table-layout: fixed; }
        .totals th { text-align: right; padding: 3px 10px 3px 0; font-weight: 600; width: 58%; }
        .totals td { text-align: right; padding: 3px 0; white-space: nowrap; width: 42%; }
        .totals tr.totals-rule-top th, .totals tr.totals-rule-top td { border-top: 1px solid #222; padding-top: 6px; }
        .totals tr.totals-rule-bottom th, .totals tr.totals-rule-bottom td { border-bottom: 1px solid #222; padding-bottom: 6px; }
        .notes { margin-top: 14px; padding: 8px 12px; background: #f9f9f9; border-radius: 4px; font-size: 12px; white-space: pre-line; }
        .business-notes { max-width: 96%; margin-left: auto; margin-right: auto; padding: 10px 14px; font-size: 10px; line-height: 1.5; white-space: normal; }
        .business-notes .notes-label { display: block; text-align: left; margin: 0 0 2px 0; }
        .business-notes .notes-body { margin: 0; text-align: center; white-space: pre-line; }
        .fbr-digital-title { font-size: 12px; font-weight: 700; margin-bottom: 10px; color: {{ $accentColor }}; }
        .fbr-digital-row { display: flex; align-items: center; gap: 16px; }
        .fbr-badge-wrap { border: 1px solid #d8e2f0; background: #fff; border-radius: 6px; padding: 6px; display: inline-flex; }
        .fbr-badge-slot { width: 1in; height: 1in; display: flex; align-items: center; justify-content: center; }
        .fbr-brand-logo { max-width: 1in; max-height: 1in; object-fit: contain; }
        .fbr-qr-image { width: 1in; height: 1in; object-fit: contain; }
        .fbr-qr-placeholder { width: 1in; height: 1in; border: 1px dashed #cbd5e1; background: #f8fafc; }
        .invoice-print-watermark {
            position: absolute; top: 42%; left: 50%; transform: translate(-50%, -50%) rotate(-35deg);
            font-size: 76px; font-weight: 900; color: rgba(220, 38, 38, 0.14) !important;
            text-transform: uppercase; letter-spacing: 0.18em; pointer-events: none; z-index: 99;
            border: 6px solid rgba(220, 38, 38, 0.14) !important; padding: 12px 52px; border-radius: 12px; white-space: nowrap;
        }
        .invoice-print-watermark.watermark-draft {
            color: rgba(100, 116, 139, 0.13) !important;
            border-color: rgba(100, 116, 139, 0.13) !important;
        }
        .invoice-cancelled-stamp {
            position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%);
            font-size: 13px; font-weight: 800; color: #dc2626 !important; border: 2px solid #dc2626 !important;
            padding: 3px 12px; border-radius: 4px; letter-spacing: 0.1em; text-transform: uppercase;
        }

        .screen-sheet-label {
            width: 210mm;
            max-width: 95vw;
            margin: 22px auto 6px auto;
        }
        body.orientation-landscape .screen-sheet-label {
            width: 297mm;
        }
    </style>
</head>
<body class="orientation-{{ $orientation }}">

    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    {{--  TOP CONTROL BAR (.no-print) — Standard Theme / Bootstrap Classes      --}}
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 px-3 py-2 bg-light border-bottom sticky-top no-print shadow-sm">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="badge bg-primary fs-12 px-2 py-1">
                <i class="fas fa-layer-group me-1"></i>
                {{ $countThisBatch }} {{ \Illuminate\Support\Str::plural('Invoice', $countThisBatch) }}
            </span>

            @if($totalInvoices > $countThisBatch)
                <span class="badge bg-warning text-dark fs-12 px-2 py-1" title="Showing chunk {{ $offset + 1 }} - {{ $offset + $countThisBatch }} out of {{ $totalInvoices }} total batch invoices">
                    Part {{ (int) floor($offset / $limit) + 1 }} ({{ $offset + 1 }}–{{ $offset + $countThisBatch }} of {{ $totalInvoices }})
                </span>
            @endif

            <span class="text-muted fs-12 fw-semibold ms-1 d-none d-md-inline text-truncate" style="max-width: 260px;" title="{{ $batchTitle }}">
                {{ $batchTitle }}
            </span>

            <div class="vr mx-2 d-none d-sm-inline"></div>

            <label for="bulk_toolbar_template" class="form-label mb-0 fw-semibold fs-12">Template:</label>
            <select id="bulk_toolbar_template" class="form-select form-select-sm w-auto" onchange="onChangeBulkToolbar()">
                @foreach($allTemplates as $tItem)
                    <option value="{{ $tItem->ID }}" @selected((int) $tItem->ID === $tplId)>
                        {{ $tItem->TemplateName }}{{ (int) ($tItem->IsDefault ?? 0) === 1 ? ' (Default)' : '' }}
                    </option>
                @endforeach
            </select>

            <label for="bulk_toolbar_orientation" class="form-label mb-0 fw-semibold fs-12 ms-2">Orientation:</label>
            <select id="bulk_toolbar_orientation" class="form-select form-select-sm w-auto" onchange="onChangeBulkToolbar()">
                <option value="portrait" @selected($orientation === 'portrait')>Portrait</option>
                <option value="landscape" @selected($orientation === 'landscape')>Landscape</option>
            </select>
        </div>

        <div class="d-flex align-items-center gap-2">
            <small class="text-muted d-none d-xl-inline">
                Paper: <strong>{{ $paperSize }}</strong> &bull; Uncheck <em>Headers and footers</em> in browser print dialog
            </small>
            <button type="button" class="btn btn-sm btn-success shadow-sm" onclick="triggerBulkPrint()">
                <i class="fas fa-print me-1"></i> Print / Save as PDF
            </button>
            <button type="button" class="btn btn-sm btn-light border text-dark" onclick="window.close()">
                <i class="fas fa-times me-1"></i> Close
            </button>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    {{--  INVOICE SHEETS LOOP                                                   --}}
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    @forelse($invoicesData as $idx => $invData)
        <div class="screen-sheet-label no-print d-flex justify-content-between align-items-center text-white-50 fs-12 px-1">
            <span>
                <i class="fas fa-file-invoice me-1 text-white"></i>
                <strong class="text-white">Invoice {{ $idx + 1 }} of {{ $countThisBatch }}</strong>
                @if(!empty($invData['invoice']->DocNo))
                    &bull; <span class="text-white">{{ $invData['invoice']->DocNo }}</span>
                @endif
                @if(!empty($invData['invoice']->InvoiceDate))
                    &bull; {{ \Carbon\Carbon::parse($invData['invoice']->InvoiceDate)->format('d M Y') }}
                @endif
            </span>
            <span class="badge bg-dark border border-secondary text-white-50">
                {{ $invData['invoice']->CustomerDisplayName ?? 'Walk-In Customer' }}
            </span>
        </div>

        @include('pages.dashboards.Invoice._single-invoice-print-sheet', [
            'invoice'         => $invData['invoice'],
            'items'           => $invData['items'],
            'buyer'           => $invData['buyer'],
            'seller'          => $seller,
            'template'        => $template,
            'templateFields'  => $templateFields,
            'templateColumns' => $templateColumns,
            'extraCssClasses' => 'bulk-invoice-sheet',
        ])
    @empty
        <div class="container my-5 text-center text-white no-print">
            <div class="card p-5 bg-light text-dark shadow-sm d-inline-block" style="max-width: 500px;">
                <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                <h5 class="fw-bold">No Invoices to Print</h5>
                <p class="text-muted">No invoices were found matching the request criteria or all invoices in this batch have been deleted.</p>
                <button type="button" class="btn btn-secondary mt-2" onclick="window.close()">Close Window</button>
            </div>
        </div>
    @endforelse

    <script src="{{ asset('build/assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script>
        function onChangeBulkToolbar() {
            const tplId  = document.getElementById('bulk_toolbar_template').value;
            const orient = document.getElementById('bulk_toolbar_orientation').value;
            const url = new URL(window.location.href);
            url.searchParams.set('template_id', tplId);
            url.searchParams.set('orientation', orient);
            url.searchParams.set('_', Date.now());
            window.location.href = url.toString();
        }

        function triggerBulkPrint() {
            window.print();
        }

        // Auto prompt print on initial load if user desires, or let them review and click Print
    </script>
</body>
</html>


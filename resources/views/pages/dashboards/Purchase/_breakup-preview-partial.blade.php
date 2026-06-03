@php
    $qtyColIndex = null;
    $amountColIndex = null;
    foreach ($columns as $idx => $col) {
        if ($col->db_column_name === 'qty') {
            $qtyColIndex = $idx;
        }
        if ($col->db_column_name === 'amount') {
            $amountColIndex = $idx;
        }
    }
@endphp

<style>
    .breakup-preview-wrap {
        display: flex;
        flex-direction: column;
        min-height: 0;
    }
    .breakup-preview-scroll {
        flex: 0 0 auto;
        max-height: 42vh;
        overflow-x: auto;
        overflow-y: hidden;
        border: 1px solid var(--bs-border-color, #dee2e6);
        border-radius: 0.25rem 0.25rem 0 0;
    }
    .breakup-preview-scroll.has-vertical-scroll {
        overflow-y: scroll;
        scrollbar-gutter: stable;
        scrollbar-width: thin;
        scrollbar-color: #6c757d #e9ecef;
    }
    .breakup-preview-scroll.has-vertical-scroll::-webkit-scrollbar {
        width: 10px;
    }
    .breakup-preview-scroll.has-vertical-scroll::-webkit-scrollbar-track {
        background: #e9ecef;
        border-radius: 4px;
    }
    .breakup-preview-scroll.has-vertical-scroll::-webkit-scrollbar-thumb {
        background: #6c757d;
        border-radius: 4px;
        border: 2px solid #e9ecef;
    }
    .breakup-preview-scroll.has-vertical-scroll::-webkit-scrollbar-thumb:hover {
        background: #495057;
    }
    .breakup-preview-scroll .listing-table thead th {
        position: sticky;
        top: 0;
        z-index: 2;
        background: var(--bs-gray-300, #dee2e6);
        box-shadow: 0 1px 0 var(--bs-border-color, #dee2e6);
    }
    .breakup-preview-totals {
        flex-shrink: 0;
        border: 1px solid var(--bs-border-color, #dee2e6);
        border-top: 2px solid var(--bs-border-color, #dee2e6);
        border-radius: 0 0 0.25rem 0.25rem;
        background: var(--bs-gray-100, #f8f9fa);
    }
    .breakup-preview-totals .listing-table tbody tr td {
        font-size: 12px !important;
        font-weight: 600 !important;
        padding: 0.1rem !important;
        font-family: inherit;
        background-color: var(--bs-gray-100, #f8f9fa) !important;
        border-bottom: none !important;
    }
    .breakup-preview-totals .listing-table tbody tr td:first-child {
        padding-left: 15px !important;
    }
    .breakup-preview-meta {
        flex-shrink: 0;
        margin: 0.5rem 0 0;
    }
    #breakupPreviewModal .modal-body {
        display: flex;
        flex-direction: column;
        flex: 1 1 auto;
        min-height: 0;
        overflow: hidden;
        padding-bottom: 0.5rem;
    }
    .breakup-preview-data-table {
        table-layout: fixed;
        width: 100%;
    }
</style>

<div class="breakup-preview-wrap">
    <div class="breakup-preview-scroll{{ $rows->count() > 8 ? ' has-vertical-scroll' : '' }}">
        <table class="table text-nowrap listing-table mb-0 breakup-preview-data-table">
            <colgroup>
                @foreach($columns as $col)
                    <col @if(in_array($col->db_column_name, ['qty', 'rate', 'amount'], true)) style="width: 9%;" @elseif($col->db_column_name === 'invoice_no') style="width: 10%;" @elseif($col->db_column_name === 'invoice_date') style="width: 9%;" @endif>
                @endforeach
            </colgroup>
            <thead>
                <tr>
                    @foreach($columns as $col)
                        <th class="{{ in_array($col->db_column_name, ['qty', 'rate', 'amount'], true) ? 'text-end' : '' }}">
                            {{ $col->column_label }}
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        @foreach($columns as $col)
                            @php
                                $key = $col->db_column_name;
                                $val = $row->{$key} ?? '';
                                if ($key === 'invoice_date' && $val) {
                                    $val = \Carbon\Carbon::parse($val)->format('d/m/Y');
                                } elseif (in_array($key, ['qty', 'rate', 'amount'], true) && $val !== '') {
                                    $val = number_format((float) $val, 2);
                                }
                            @endphp
                            <td class="{{ in_array($key, ['qty', 'rate', 'amount'], true) ? 'text-end' : '' }}">{{ $val }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $columns->count() }}" class="text-center text-muted py-3">No breakup rows found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($rows->isNotEmpty() && ($qtyColIndex !== null || $amountColIndex !== null))
    <div class="breakup-preview-totals">
        <table class="table text-nowrap listing-table mb-0 breakup-preview-data-table">
            <colgroup>
                @foreach($columns as $col)
                    <col @if(in_array($col->db_column_name, ['qty', 'rate', 'amount'], true)) style="width: 9%;" @elseif($col->db_column_name === 'invoice_no') style="width: 10%;" @elseif($col->db_column_name === 'invoice_date') style="width: 9%;" @endif>
                @endforeach
            </colgroup>
            <tbody>
                <tr>
                    @foreach($columns as $idx => $col)
                        @php
                            $key = $col->db_column_name;
                            $cell = '';
                            if ($idx === 0) {
                                $cell = 'Total';
                            } elseif ($idx === $qtyColIndex) {
                                $cell = number_format($totalQty ?? 0, 2);
                            } elseif ($idx === $amountColIndex) {
                                $cell = number_format($totalAmount ?? 0, 2);
                            }
                        @endphp
                        <td class="{{ in_array($key, ['qty', 'rate', 'amount'], true) ? 'text-end' : '' }}">{{ $cell }}</td>
                    @endforeach
                </tr>
            </tbody>
        </table>
    </div>
    @endif

    <p class="text-muted fs-12 breakup-preview-meta mb-0">
        Showing first {{ $rows->count() }} of {{ number_format($total) }} entries.
        @if($total > $rows->count())
            Totals are for all {{ number_format($total) }} invoices.
        @endif
    </p>
</div>

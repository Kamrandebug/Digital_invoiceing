@php

    $qtyColIndex = null;

    $amountColIndex = null;

    $gstColIndex = null;

    $numericCols = [

        'qty', 'rate', 'amount',

        'gst_percentage', 'gst_amount', 'gstpercentage', 'gstamount',

        'discount_percentage', 'discount_amount', 'discountpercentage', 'discountamount',

        'extra_tax', 'further_tax', 'fed_payable', 'total_values',

        'extratax', 'furthertax', 'fedpayable', 'totalvalues',

        'valuesalesexcludingst', 'salestaxapplicable',

    ];

    foreach ($columns as $idx => $col) {

        if ($col->db_column_name === 'qty') {

            $qtyColIndex = $idx;

        }

        if ($col->db_column_name === 'amount') {

            $amountColIndex = $idx;

        }

        $gstKey = strtolower((string) $col->db_column_name);

        if (in_array($gstKey, ['gst_amount', 'gstamount'], true)) {

            $gstColIndex = $idx;

        }

    }



    $breakupColWidths = $columns->map(function ($col) {

        $excelW = max(8, (int) ($col->excel_width ?? 14));

        $name = strtolower((string) $col->db_column_name);

        $px = $excelW * 7;



        if (in_array($name, ['invoice_no'], true)) {

            $px = max($px, 88);

        } elseif (in_array($name, ['invoice_date'], true)) {

            $px = max($px, 78);

        } elseif (in_array($name, ['supplier_name', 'product_name', 'remarks'], true)) {

            $px = max($px, 110);

        } elseif (in_array($name, [

            'productdescription', 'product_description', 'description',

            'saletypedesc', 'sale_type', 'sale_type_desc',

            'saletypetoratedesc', 'sale_type_to_rate', 'sale_type_to_rate_desc',

            'sroscheduledesc', 'sro_schedule', 'sro_schedule_desc',

            'sroitemserialno', 'sro_item', 'sro_item_serial_no',

        ], true)) {

            $px = max($px, 96);

        } elseif (in_array($name, ['hscode', 'hs_code'], true)) {

            $px = max($px, 82);

        } elseif (in_array($name, [

            'qty', 'rate', 'amount',

            'gstpercentage', 'gst_percentage', 'gstamount', 'gst_amount',

            'discountpercentage', 'discount_percentage', 'discountamount', 'discount_amount',

            'extratax', 'extra_tax', 'furthertax', 'further_tax',

            'fedpayable', 'fed_payable', 'totalvalues', 'total_values',

        ], true)) {

            $px = max($px, 72);

        } else {

            $px = max($px, 68);

        }



        return $px;

    });

    $tableMinWidth = max(960, (int) $breakupColWidths->sum());

@endphp



<style>

    .breakup-preview-wrap {

        display: flex;

        flex-direction: column;

        min-height: 0;

    }

    .breakup-preview-hscroll {

        flex: 0 0 auto;

        overflow-x: auto;

        overflow-y: hidden;

        border: 1px solid var(--bs-border-color, #dee2e6);

        border-radius: 0.25rem 0.25rem 0 0;

        -webkit-overflow-scrolling: touch;

    }

    .breakup-preview-hscroll:has(.breakup-preview-totals) {

        border-radius: 0.25rem;

    }

    .breakup-preview-table-block {

        width: max-content;

        min-width: {{ $tableMinWidth }}px;

    }

    .breakup-preview-vscroll {

        max-height: 42vh;

        overflow-x: hidden;

        overflow-y: hidden;

    }

    .breakup-preview-vscroll.has-vertical-scroll {

        overflow-y: scroll;

        scrollbar-gutter: stable;

        scrollbar-width: thin;

        scrollbar-color: #6c757d #e9ecef;

    }

    .breakup-preview-vscroll.has-vertical-scroll::-webkit-scrollbar {

        width: 10px;

    }

    .breakup-preview-vscroll.has-vertical-scroll::-webkit-scrollbar-track {

        background: #e9ecef;

        border-radius: 4px;

    }

    .breakup-preview-vscroll.has-vertical-scroll::-webkit-scrollbar-thumb {

        background: #6c757d;

        border-radius: 4px;

        border: 2px solid #e9ecef;

    }

    .breakup-preview-vscroll.has-vertical-scroll::-webkit-scrollbar-thumb:hover {

        background: #495057;

    }

    .breakup-preview-hscroll::-webkit-scrollbar {

        height: 10px;

    }

    .breakup-preview-hscroll::-webkit-scrollbar-track {

        background: #e9ecef;

        border-radius: 4px;

    }

    .breakup-preview-hscroll::-webkit-scrollbar-thumb {

        background: #6c757d;

        border-radius: 4px;

        border: 2px solid #e9ecef;

    }

    .breakup-preview-data-table {

        table-layout: fixed;

        width: max-content;

        min-width: {{ $tableMinWidth }}px;

        border-collapse: collapse;

        margin-bottom: 0;

    }

    .breakup-preview-data-table th,

    .breakup-preview-data-table td {

        border: 1px solid #ddd;

        padding: 6px 8px;

        text-align: left;

        box-sizing: border-box;

        vertical-align: middle;

        min-width: 0;

    }

    /* Match invoice print detail table header behaviour */

    .breakup-preview-data-table thead th {

        position: sticky;

        top: 0;

        z-index: 2;

        background: #f5f5f5;

        font-size: 10px !important;

        font-weight: 600;

        white-space: normal;

        word-wrap: break-word;

        overflow-wrap: break-word;

        hyphens: none;

        line-height: 1.2;

        box-shadow: 0 1px 0 #ddd;

    }

    .breakup-preview-data-table tbody td {

        font-size: 12px;

        hyphens: none;

    }

    .breakup-preview-data-table td.num,

    .breakup-preview-data-table th.num {

        text-align: right;

        white-space: nowrap;

        font-variant-numeric: tabular-nums;

    }

    .breakup-preview-data-table td.text-wrap,

    .breakup-preview-data-table th.text-wrap {

        white-space: normal;

        word-break: break-word;

        overflow-wrap: break-word;

    }

    .breakup-preview-totals {

        flex-shrink: 0;

        border-top: 2px solid var(--bs-border-color, #dee2e6);

        background: var(--bs-gray-100, #f8f9fa);

        overflow: hidden;

    }

    .breakup-preview-totals .breakup-preview-data-table thead th {

        position: static;

        box-shadow: none;

    }

    .breakup-preview-totals .listing-table tbody tr td {

        font-size: 12px !important;

        font-weight: 600 !important;

        padding: 6px 8px !important;

        font-family: inherit;

        background-color: var(--bs-gray-100, #f8f9fa) !important;

        border-bottom: none !important;

    }

    .breakup-preview-totals .listing-table tbody tr td:first-child {

        padding-left: 8px !important;

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

</style>



<div class="breakup-preview-wrap">

    <div class="breakup-preview-hscroll">

        <div class="breakup-preview-table-block">

            <div class="breakup-preview-vscroll{{ $rows->count() > 8 ? ' has-vertical-scroll' : '' }}">

                <table class="table listing-table mb-0 breakup-preview-data-table">

            <colgroup>

                @foreach($columns as $idx => $col)

                    <col style="width: {{ $breakupColWidths[$idx] }}px;">

                @endforeach

            </colgroup>

            <thead>

                <tr>

                    @foreach($columns as $col)

                        @php

                            $align = $col->alignment ?? 'left';

                            $thClass = ($align === 'right') ? 'num' : (($align === 'center') ? 'text-center text-wrap' : 'text-wrap');

                        @endphp

                        <th class="{{ $thClass }}" title="{{ $col->column_label }}">{{ $col->column_label }}</th>

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

                                $align = $col->alignment ?? 'left';

                                if ($key === 'invoice_date' && $val) {

                                    $val = \Carbon\Carbon::parse($val)->format('d/m/Y');

                                } elseif (
                                    (($col->alignment ?? 'left') === 'right' || in_array(strtolower($key), $numericCols, true))
                                    && $val !== '' && is_numeric($val)
                                ) {
                                    $val = number_format((float) $val, 2);
                                }

                                $tdClass = ($align === 'right') ? 'num' : (($align === 'center') ? 'text-center' : 'text-wrap');

                            @endphp

                            <td class="{{ $tdClass }}">{{ $val }}</td>

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

            @if($rows->isNotEmpty() && ($qtyColIndex !== null || $amountColIndex !== null || $gstColIndex !== null))

            <div class="breakup-preview-totals">

                <table class="table listing-table mb-0 breakup-preview-data-table">

            <colgroup>

                @foreach($columns as $idx => $col)

                    <col style="width: {{ $breakupColWidths[$idx] }}px;">

                @endforeach

            </colgroup>

            <tbody>

                <tr>

                    @foreach($columns as $idx => $col)

                        @php

                            $key = $col->db_column_name;

                            $align = $col->alignment ?? 'left';

                            $cell = '';

                            if ($idx === 0) {

                                $cell = 'Total';

                            } elseif ($idx === $qtyColIndex) {

                                $cell = number_format($totalQty ?? 0, 2);

                            } elseif ($idx === $amountColIndex) {

                                $cell = number_format($totalAmount ?? 0, 2);

                            } elseif ($idx === $gstColIndex && $totalGstAmount !== null) {

                                $cell = number_format($totalGstAmount, 2);

                            }

                            $tdClass = ($align === 'right') ? 'num' : (($align === 'center') ? 'text-center' : 'text-wrap');

                        @endphp

                        <td class="{{ $tdClass }}">{{ $cell }}</td>

                    @endforeach

                </tr>

            </tbody>

                </table>

            </div>

            @endif

        </div>

    </div>



    <p class="text-muted fs-12 breakup-preview-meta mb-0">

        Showing first {{ $rows->count() }} of {{ number_format($total) }} entries.

        @if($total > $rows->count())

            Totals are for all {{ number_format($total) }} invoices.

        @endif

    </p>

</div>



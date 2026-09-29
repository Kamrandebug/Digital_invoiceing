@php
    $idx = $idx ?? 0;
    $item = $item ?? null;
    $readonly = $readonly ?? false;
    $nameIdx = $idx;
    $selClass = $selClass ?? ($inModal ?? false ? 'form-select' : 'form-select form-select-sm');
    $uid = $uid ?? '';
    $inModal = $inModal ?? false;
@endphp
<tr class="ifp-row purchase-line-row">
    <td class="form-select-sm ifp-col-product">
        @if($readonly)
            <span class="small">{{ $item->ProductName ?? '-' }}</span>
            <input type="hidden" name="items[{{ $nameIdx }}][ProductID]" value="{{ $item->ProductID ?? '' }}">
        @else
            <div class="d-flex gap-1 align-items-start ifp-product-select-wrap">
                <select name="items[{{ $nameIdx }}][ProductID]" class="{{ $selClass }} line-product ifp-product flex-grow-1" style="min-width:0;" required>
                    <option value="">— Select —</option>
                    @foreach($products ?? [] as $p)
                        <option value="{{ $p->ID }}"
                            data-rate="{{ $p->Cost ?? 0 }}"
                            data-cost="{{ $p->Cost ?? 0 }}"
                            data-gst="{{ $p->GstPercentage ?? 0 }}"
                            data-hscode="{{ $p->HsCode ?? '' }}"
                            data-sale-information="{{ $p->SaleInformation ?? '' }}"
                            data-uom-id="{{ $p->UomID ?? '' }}"
                            data-uom-label="{{ trim((string) ($p->UomName ?? '')) }}"
                            data-sale-type-id="{{ $p->SaleTypeID ?? '' }}"
                            data-sale-type-to-rate-id="{{ $p->SaleTypeToRateID ?? '' }}"
                            data-sro-schedule-id="{{ $p->SroScheduleID ?? '' }}"
                            data-sro-item-id="{{ $p->SroItemID ?? '' }}"
                            data-tp-rate="{{ $p->TPRate ?? 0 }}"
                            data-is-fixed-rate="{{ in_array(strtolower(trim((string) ($p->IsFixedRate ?? '0'))), ['1', 'true', 'yes', 'y', 'on'], true) ? 1 : 0 }}"
                            {{ (int) ($item->ProductID ?? 0) === (int) $p->ID ? 'selected' : '' }}>
                            {{ $p->ProductName ?? ('Product #' . $p->ID) }}
                        </option>
                    @endforeach
                </select>
                <button type="button" class="btn btn-sm btn-primary ifp-line-add-entity-btn flex-shrink-0 ifp-new-product-btn"
                        title="New product" aria-label="New product"><i class="ri-add-line"></i></button>
            </div>
        @endif
    </td>
    <td class="ifp-col-desc">
        @if($readonly)
            <span class="small ifp-readonly-desc">{{ $item->ProductDescription ?? '-' }}</span>
        @else
            <input type="hidden" name="items[{{ $nameIdx }}][HsCode]" class="ifp-hscode" value="{{ $item->HsCode ?? '' }}">
            <textarea name="items[{{ $nameIdx }}][Details]" rows="1"
                      class="form-control form-control-sm ifp-details line-details"
                      placeholder="Enter Description">{{ old('items.'.$nameIdx.'.Details', $item->ProductDescription ?? '') }}</textarea>
        @endif
    </td>
    <td class="ifp-col-uom">
        @if($readonly)
            <span class="small">{{ $item->UomName ?? $item->UomID ?? '-' }}</span>
        @else
            <select name="items[{{ $nameIdx }}][UomID]" class="{{ $selClass }} ifp-uom" data-selected="{{ $item->UomID ?? '' }}">
                <option value="">Select UOM</option>
            </select>
        @endif
    </td>
    @include('pages.dashboards.Invoice._invoice-line-tax-sro', [
        'idx' => $nameIdx,
        'item' => $item,
        'uid' => $uid,
        'selClass' => $selClass,
        'inModal' => $inModal,
        'isReadonly' => $readonly,
    ])
    <td class="ifp-col-qty">
        @if($readonly)
            @php
                $qtyVal = (float)($item->Quantity ?? 0);
                $qtyDec = ($qtyVal - floor($qtyVal) > 0.000001) ? 2 : 0;
            @endphp
            <span class="small">{{ number_format($qtyVal, $qtyDec, '.', ',') }}</span>
        @else
            @php
                $qtyVal = (float)old('items.'.$nameIdx.'.Quantity', $item->Quantity ?? 1);
                $qtyDec = ($qtyVal - floor($qtyVal) > 0.000001) ? 2 : 0;
            @endphp
            <input type="text" inputmode="decimal" name="items[{{ $nameIdx }}][Quantity]"
                   class="form-control form-control-sm ifp-qty line-qty ifp-numfmt"
                   value="{{ number_format($qtyVal, $qtyDec, '.', ',') }}">
        @endif
    </td>
    <td class="ifp-col-sale-rate">
        @if($readonly)
            <span class="small">{{ number_format((float) ($item->Rate ?? 0), 2) }}</span>
        @else
            @php
                $rateVal = old('items.'.$nameIdx.'.Rate', $item->Rate ?? 0);
            @endphp
            <input type="text" inputmode="decimal" name="items[{{ $nameIdx }}][Rate]"
                   class="form-control form-control-sm ifp-rate line-rate ifp-numfmt"
                   value="{{ number_format((float) $rateVal, 2, '.', ',') }}">
        @endif
    </td>
    <td class="ifp-col-amount">
        @if($readonly)
            <span class="small">{{ number_format($item->Amount ?? 0, 2) }}</span>
        @else
            <input type="text" name="items[{{ $nameIdx }}][Amount]"
                   class="form-control form-control-sm ifp-amount line-amount ifp-numfmt"
                   value="{{ number_format((float) old('items.'.$nameIdx.'.Amount', $item->Amount ?? 0), 2, '.', ',') }}" readonly>
        @endif
    </td>
    @if($inModal)
    <td class="ifp-col-fixed">
        @if($readonly)
            <span class="small">{{ number_format((float) ($item->FixedNotifiedValueOrRetailPrice ?? ($item->TPRate ?? 0) * ($item->Quantity ?? 0)), 2) }}</span>
        @else
            @php
                $fixedVal = $item->FixedNotifiedValueOrRetailPrice ?? ($item->TPRate ?? 0) * ($item->Quantity ?? 0);
            @endphp
            <input type="text" name="items[{{ $nameIdx }}][FixedNotifiedValueOrRetailPrice]"
                   class="form-control form-control-sm ifp-fixed-notified-value ifp-numfmt"
                   value="{{ number_format((float) $fixedVal, 2, '.', ',') }}" readonly>
        @endif
    </td>
    @endif
    @if(!$inModal)
    <td>
        @if($readonly)
            <span class="small">{{ $item->DiscountPercentage ?? 0 }}%</span>
        @else
            <input type="number" step="0.01" name="items[{{ $nameIdx }}][DiscountPercentage]"
                   class="form-control form-control-sm ifp-disc-pct"
                   value="{{ $item->DiscountPercentage ?? 0 }}" min="0" max="100">
        @endif
    </td>
    @endif
    <td class="ifp-col-disc-amt">
        @if($readonly)
            <span class="small">{{ number_format((float) ($item->DiscountAmount ?? 0), 2) }}</span>
        @else
            <input type="text" name="items[{{ $nameIdx }}][DiscountAmount]"
                   class="form-control form-control-sm ifp-disc-amt ifp-numfmt"
                   value="{{ number_format((float) ($item->DiscountAmount ?? 0), 2, '.', ',') }}" readonly>
        @endif
    </td>
    @if(!$inModal)
    <td>
        @if($readonly)
            <span class="small">{{ $item->GstPercentage ?? 0 }}%</span>
        @else
            <input type="number" step="0.01" name="items[{{ $nameIdx }}][GstPercentage]"
                   class="form-control form-control-sm ifp-gst-pct"
                   value="{{ $item->GstPercentage ?? 0 }}" min="0">
        @endif
    </td>
    @endif
    <td class="ifp-col-gst-amt">
        @if($readonly)
            <span class="small">{{ number_format((float) ($item->GSTAmount ?? 0), 2) }}</span>
        @else
            <input type="text" name="items[{{ $nameIdx }}][GSTAmount]"
                   class="form-control form-control-sm ifp-gst-amt ifp-numfmt"
                   value="{{ number_format((float) ($item->GSTAmount ?? 0), 2, '.', ',') }}" readonly>
        @endif
    </td>
    <td class="ifp-col-net">
        @if($readonly)
            <span class="small fw-bold">{{ number_format((float) ($item->Total ?? $item->TotalValues ?? 0), 2) }}</span>
        @else
            @php
                $totalVal = $item->Total ?? $item->TotalValues ?? 0;
            @endphp
            <input type="text" name="items[{{ $nameIdx }}][Total]"
                   class="form-control form-control-sm ifp-total ifp-numfmt fw-bold"
                   value="{{ number_format((float) $totalVal, 2, '.', ',') }}" readonly>
        @endif
    </td>
    <td class="ifp-col-extra-tax">
        @if($readonly)
            <span class="small">{{ number_format((float) ($item->ExtraTax ?? 0), 2) }}</span>
        @else
            <input type="number" step="0.01" name="items[{{ $nameIdx }}][ExtraTax]"
                   class="form-control form-control-sm ifp-extra-tax"
                   value="{{ $item->ExtraTax ?? 0 }}" min="0">
        @endif
    </td>
    <td class="ifp-col-further-tax">
        @if($readonly)
            <span class="small">{{ number_format((float) ($item->FurtherTax ?? 0), 2) }}</span>
        @else
            <input type="number" step="0.01" name="items[{{ $nameIdx }}][FurtherTax]"
                   class="form-control form-control-sm ifp-further-tax"
                   value="{{ $item->FurtherTax ?? 0 }}" min="0">
        @endif
    </td>
    <td class="ifp-col-fed-payable">
        @if($readonly)
            <span class="small">{{ number_format((float) ($item->FedPayable ?? 0), 2) }}</span>
        @else
            <input type="number" step="0.01" name="items[{{ $nameIdx }}][FedPayable]"
                   class="form-control form-control-sm ifp-fed-payable"
                   value="{{ $item->FedPayable ?? 0 }}" min="0">
        @endif
    </td>
    @if(!$readonly)
    <td class="ifp-col-actions">
        <button type="button" class="btn btn-sm btn-icon btn-danger-light ifp-del btn-remove-line" title="Remove line">
            <i class="ri-delete-bin-5-line"></i>
        </button>
    </td>
    @endif
</tr>

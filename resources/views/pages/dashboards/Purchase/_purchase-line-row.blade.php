@php
    $idx = $idx ?? 0;
    $item = $item ?? null;
    $readonly = $readonly ?? false;
    $nameIdx = $idx;
    $selClass = $selClass ?? ($inModal ?? false ? 'form-select' : 'form-select form-select-sm');
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
                            data-cost="{{ $p->Cost ?? 0 }}"
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
            <textarea name="items[{{ $nameIdx }}][Details]" rows="1"
                      class="form-control form-control-sm ifp-details line-details"
                      placeholder="Enter Description">{{ old('items.'.$nameIdx.'.Details', $item->ProductDescription ?? '') }}</textarea>
        @endif
    </td>
    <td class="ifp-col-qty">
        @if($readonly)
            <span class="small">{{ number_format((float) ($item->Quantity ?? 0), 2) }}</span>
        @else
            @php
                $qtyVal = old('items.'.$nameIdx.'.Quantity', $item->Quantity ?? 1);
                $qtyDisplay = is_numeric($qtyVal) ? number_format((float) $qtyVal, 2, '.', '') : $qtyVal;
            @endphp
            <input type="number" name="items[{{ $nameIdx }}][Quantity]"
                   class="form-control form-control-sm ifp-qty line-qty"
                   value="{{ $qtyDisplay }}" min="0" step="0.01">
        @endif
    </td>
    <td class="ifp-col-sale-rate">
        @if($readonly)
            <span class="small">{{ number_format((float) ($item->Rate ?? 0), 2) }}</span>
        @else
            @php
                $rateVal = old('items.'.$nameIdx.'.Rate', $item->Rate ?? 0);
                $rateDisplay = is_numeric($rateVal) ? number_format((float) $rateVal, 2, '.', '') : $rateVal;
            @endphp
            <input type="number" step="0.01" name="items[{{ $nameIdx }}][Rate]"
                   class="form-control form-control-sm ifp-rate line-rate"
                   value="{{ $rateDisplay }}" min="0">
        @endif
    </td>
    <td class="ifp-col-amount">
        @if($readonly)
            <span class="small">{{ number_format($item->Amount ?? 0, 2) }}</span>
        @else
            <input type="number" step="0.01" name="items[{{ $nameIdx }}][Amount]"
                   class="form-control form-control-sm ifp-amount line-amount"
                   value="{{ old('items.'.$nameIdx.'.Amount', $item->Amount ?? 0) }}" readonly>
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

{{--
  Line item: Sale Type, Sale Type To Rate, SRO Schedule, SRO Item.
  Full page: four <td> cells. Invoice modal ($inModal): one <td> with button + hidden slot (fields open in nested modal — row height unchanged).
  Expects: $idx, $selClass, $inModal, $isReadonly, $item (null = default empty row template), $uid
--}}
@php
    $item = $item ?? null;
    $uid = $uid ?? '';
@endphp

@if(!$inModal)
    <td>
        @if($isReadonly)
            <span class="small">{{ $item->SaleTypeDesc ?? $item->SaleTypeName ?? $item->SaleTypeID ?? '-' }}</span>
        @else
            <select name="items[{{ $idx }}][SaleTypeID]" class="{{ $selClass }} ifp-sale-type" data-selected="{{ $item?->SaleTypeID ?? '' }}">
                <option value="">Loading...</option>
            </select>
        @endif
    </td>
    <td>
        @if($isReadonly)
            <span class="small">{{ $item->SaleTypeToRateDesc ?? $item->SaleTypeToRateName ?? $item->SaleTypeToRateID ?? '-' }}</span>
        @else
            <select name="items[{{ $idx }}][SaleTypeToRateID]" class="{{ $selClass }} ifp-sale-type-to-rate" data-selected="{{ $item?->SaleTypeToRateID ?? '' }}">
                <option value="">Select Sale Type first</option>
            </select>
        @endif
    </td>
    <td>
        @if($isReadonly)
            <span class="small">{{ $item->SroScheduleDesc ?? $item->SroScheduleName ?? $item->SroScheduleID ?? '-' }}</span>
        @else
            <select name="items[{{ $idx }}][SroScheduleID]" class="{{ $selClass }} ifp-sro-schedule" data-selected="{{ $item?->SroScheduleID ?? '' }}">
                <option value="">Select Sale Type To Rate first</option>
            </select>
        @endif
    </td>
    <td>
        @if($isReadonly)
            <span class="small">{{ $item->SroItemSerialNo ?? $item->SroItemName ?? $item->SroItemID ?? '-' }}</span>
        @else
            <select name="items[{{ $idx }}][SroItemID]" class="{{ $selClass }} ifp-sro-item" data-selected="{{ $item?->SroItemID ?? '' }}">
                <option value="">Select SRO Schedule first</option>
            </select>
        @endif
    </td>
@elseif($isReadonly)
    <td class="ifp-col-tax-sro align-middle">
        <div class="ifp-tax-sro-readonly-pill">
            <div class="small text-truncate" title="{{ $item->SaleTypeDesc ?? $item->SaleTypeName ?? $item->SaleTypeID ?? '—' }}">
                {{ $item->SaleTypeDesc ?? $item->SaleTypeName ?? $item->SaleTypeID ?? '—' }}
            </div>
            <div class="small text-truncate" title="{{ $item->SaleTypeToRateDesc ?? $item->SaleTypeToRateName ?? $item->SaleTypeToRateID ?? '—' }}">
                {{ $item->SaleTypeToRateDesc ?? $item->SaleTypeToRateName ?? $item->SaleTypeToRateID ?? '—' }}
            </div>
        </div>
    </td>
@else
    @php
        $saleTypeLabel = trim((string) ($item->SaleTypeDesc ?? $item->SaleTypeName ?? $item->SaleTypeID ?? ''));
        $saleRateLabel = trim((string) ($item->SaleTypeToRateDesc ?? $item->SaleTypeToRateName ?? $item->SaleTypeToRateID ?? ''));
    @endphp
    <td class="ifp-col-tax-sro align-middle">
        <button type="button" class="ifp-tax-sro-open-btn" title="Open Tax &amp; SRO in a dialog" aria-haspopup="dialog">
            <span class="ifp-tax-sro-trigger__icon" aria-hidden="true"><i class="ri-equalizer-line"></i></span>
            <span class="ifp-tax-sro-trigger__text">
                <span class="ifp-tax-sro-trigger__sub">
                    <span class="ifp-tax-sro-trigger__sale">{{ $saleTypeLabel !== '' ? $saleTypeLabel : 'Sale type —' }}</span>
                    <span class="ifp-tax-sro-trigger__sep" aria-hidden="true">·</span>
                    <span class="ifp-tax-sro-trigger__rate">{{ $saleRateLabel !== '' ? $saleRateLabel : 'Rate profile —' }}</span>
                </span>
            </span>
            <i class="ri-arrow-right-s-line ifp-tax-sro-trigger__chev" aria-hidden="true"></i>
        </button>
        <div class="ifp-tax-sro-slot d-none">
            <div class="ifp-tax-sro-fields">
                <div class="ifp-tax-sro-fields__head">Classification &amp; SRO</div>
                <div class="ifp-tax-sro-fields__body">
                    @php
                        $isFilerVal = (int) (($item->IsFiler ?? 0) ? 1 : 0);
                        $salesTaxWithheldVal = (float) ($item->SalesTaxWithheldAtSource ?? 0);
                        $extraTaxPctVal = (float) ($item->ExtraTax ?? 0);
                        $furtherTaxPctVal = (float) ($item->FurtherTax ?? 0);
                        $fedPayablePctVal = (float) ($item->FedPayable ?? 0);
                        $isFixedRaw = strtolower(trim((string) ($item->ProductIsFixedRate ?? $item->IsFixedRate ?? '0')));
                        $isFixedRateVal = in_array($isFixedRaw, ['1', 'true', 'yes', 'y', 'on'], true) ? 1 : 0;
                    @endphp
                    {{-- Listboxes first (same pattern as product modal Tax & SRO) --}}
                    <div class="ifp-tax-sro-listbox-area border-bottom mb-2 pb-2 px-2 pt-2">
                        <div class="row g-0 ifp-tax-picker-row">
                            <div class="col-6 col-lg-4 ifp-tax-listbox-col">
                                <div class="ifp-tax-column-header">Sale type</div>
                                <input type="search" class="form-control form-control-sm ifp-tax-listbox-search" data-ifp-pair="st" placeholder="Search…" autocomplete="off" aria-label="Search sale types">
                                <select id="{{ $uid }}_st_{{ $idx }}" name="items[{{ $idx }}][SaleTypeID]" class="{{ $selClass }} ifp-inv-tax-listbox ifp-sale-type" data-ifp-pair="st" size="5" aria-label="Sale type — choose from list or search" data-selected="{{ $item?->SaleTypeID ?? '' }}">
                                    <option value="" hidden data-ifp-placeholder="1" selected title="Please select sale type">Please select sale type</option>
                                </select>
                            </div>
                            <div class="col-6 col-lg-3 ifp-tax-listbox-col">
                                <div class="ifp-tax-column-header">Sale type to rate</div>
                                <input type="search" class="form-control form-control-sm ifp-tax-listbox-search" data-ifp-pair="str" placeholder="Search…" autocomplete="off" aria-label="Search sale type to rate">
                                <select id="{{ $uid }}_str_{{ $idx }}" name="items[{{ $idx }}][SaleTypeToRateID]" class="{{ $selClass }} ifp-inv-tax-listbox ifp-sale-type-to-rate" data-ifp-pair="str" size="5" aria-label="Sale type to rate — choose from list or search" data-selected="{{ $item?->SaleTypeToRateID ?? '' }}">
                                    <option value="" hidden data-ifp-placeholder="1" selected title="Please select sale type to rate">Please select sale type to rate</option>
                                </select>
                                <input type="hidden" name="items[{{ $idx }}][RateType]" class="ifp-rate-type" value="{{ $item->RateType ?? '' }}">
                            </div>
                            <div class="col-6 col-lg-3 ifp-tax-listbox-col">
                                <div class="ifp-tax-column-header">SRO schedule</div>
                                <input type="search" class="form-control form-control-sm ifp-tax-listbox-search" data-ifp-pair="sros" placeholder="Search…" autocomplete="off" aria-label="Search SRO schedule">
                                <select id="{{ $uid }}_sros_{{ $idx }}" name="items[{{ $idx }}][SroScheduleID]" class="{{ $selClass }} ifp-inv-tax-listbox ifp-sro-schedule" data-ifp-pair="sros" size="5" aria-label="SRO schedule — choose from list or search" data-selected="{{ $item?->SroScheduleID ?? '' }}">
                                    <option value="" hidden data-ifp-placeholder="1" selected title="Please select SRO schedule">Please select SRO schedule</option>
                                </select>
                            </div>
                            <div class="col-6 col-lg-2 ifp-tax-listbox-col">
                                <div class="ifp-tax-column-header">SRO item</div>
                                <input type="search" class="form-control form-control-sm ifp-tax-listbox-search" data-ifp-pair="sroi" placeholder="Search…" autocomplete="off" aria-label="Search SRO item">
                                <select id="{{ $uid }}_sroi_{{ $idx }}" name="items[{{ $idx }}][SroItemID]" class="{{ $selClass }} ifp-inv-tax-listbox ifp-sro-item" data-ifp-pair="sroi" size="5" aria-label="SRO item — choose from list or search" data-selected="{{ $item?->SroItemID ?? '' }}">
                                    <option value="" hidden data-ifp-placeholder="1" selected title="Please select SRO item">Please select SRO item</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row g-2 px-2 pb-2 ifp-tax-sro-fields-lower">
                        {{-- Row 1: Is Filer | Rate | GST Rate % --}}
                        <div class="col-12 col-md-4">
                            <div class="ifp-tax-sro-field mb-0">
                                <label class="ifp-tax-sro-lbl" for="{{ $uid }}_ifiler_{{ $idx }}">Is Filer</label>
                                <input type="hidden" name="items[{{ $idx }}][IsFiler]" class="ifp-is-filer" value="{{ $isFilerVal }}">
                                <div class="form-check ps-0 pt-1">
                                    <input type="checkbox" id="{{ $uid }}_ifiler_{{ $idx }}" class="form-check-input ifp-is-filer-cb m-0" disabled {{ $isFilerVal ? 'checked' : '' }}>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="ifp-tax-sro-field mb-0">
                                <label class="ifp-tax-sro-lbl" for="{{ $uid }}_lr_{{ $idx }}">Rate</label>
                                <input type="number" step="0.000001" id="{{ $uid }}_lr_{{ $idx }}" name="items[{{ $idx }}][Rate]" class="form-control ifp-rate" value="{{ $item?->Rate ?? 0 }}" min="0">
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="ifp-tax-sro-field mb-0">
                                <label class="ifp-tax-sro-lbl" for="{{ $uid }}_gst_{{ $idx }}">GST Rate %</label>
                                <input type="number" step="0.01" id="{{ $uid }}_gst_{{ $idx }}" name="items[{{ $idx }}][GstPercentage]" class="form-control ifp-gst-pct" value="{{ $item?->GstPercentage ?? 0 }}" min="0">
                            </div>
                        </div>
                        {{-- Row 2: Is Fixed | Fixed Rate | Discount % --}}
                        <div class="col-12 col-md-4">
                            <div class="ifp-tax-sro-field mb-0">
                                <label class="ifp-tax-sro-lbl" for="{{ $uid }}_ifr_{{ $idx }}">Is Fixed</label>
                                <input type="hidden" name="items[{{ $idx }}][IsFixedRate]" class="ifp-is-fixed-rate" value="{{ $isFixedRateVal }}">
                                <div class="form-check ps-0 pt-1">
                                    <input type="checkbox" id="{{ $uid }}_ifr_{{ $idx }}" class="form-check-input ifp-is-fixed-rate-cb m-0" disabled {{ $isFixedRateVal ? 'checked' : '' }}>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="ifp-tax-sro-field mb-0">
                                <label class="ifp-tax-sro-lbl" for="{{ $uid }}_fr_{{ $idx }}">Fixed Rate</label>
                                <input type="number" step="0.01" id="{{ $uid }}_fr_{{ $idx }}" name="items[{{ $idx }}][TPRate]" class="form-control ifp-fixed-rate" value="{{ $item?->TPRate ?? 0 }}" readonly>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="ifp-tax-sro-field mb-0">
                                <label class="ifp-tax-sro-lbl" for="{{ $uid }}_dp_{{ $idx }}">Discount %</label>
                                <input type="number" step="0.01" id="{{ $uid }}_dp_{{ $idx }}" name="items[{{ $idx }}][DiscountPercentage]" class="form-control ifp-disc-pct" value="{{ $item?->DiscountPercentage ?? 0 }}" min="0" max="100">
                            </div>
                        </div>
                        {{-- Row 3: Extra Tax % | Further Tax % | FED Payable % --}}
                        <div class="col-12 col-md-4">
                            <div class="ifp-tax-sro-field mb-0">
                                <label class="ifp-tax-sro-lbl" for="{{ $uid }}_etp_{{ $idx }}">Extra Tax %</label>
                                <input type="number" step="0.01" id="{{ $uid }}_etp_{{ $idx }}" class="form-control ifp-extra-tax-pct" value="{{ $extraTaxPctVal }}" readonly>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="ifp-tax-sro-field mb-0">
                                <label class="ifp-tax-sro-lbl" for="{{ $uid }}_ftp_{{ $idx }}">Further Tax %</label>
                                <input type="number" step="0.01" id="{{ $uid }}_ftp_{{ $idx }}" class="form-control ifp-further-tax-pct" value="{{ $furtherTaxPctVal }}" readonly>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="ifp-tax-sro-field mb-0">
                                <label class="ifp-tax-sro-lbl" for="{{ $uid }}_fpp_{{ $idx }}">FED Payable %</label>
                                <input type="number" step="0.01" id="{{ $uid }}_fpp_{{ $idx }}" class="form-control ifp-fed-payable-pct" value="{{ $fedPayablePctVal }}" readonly>
                            </div>
                        </div>
                        {{-- Row 4: Sales Tax Withheld at Source (full width) --}}
                        <div class="col-12">
                            <div class="ifp-tax-sro-field mb-0 ifp-tax-sro-field--last">
                                <label class="ifp-tax-sro-lbl" for="{{ $uid }}_stw_{{ $idx }}">Sales Tax Withheld at Source</label>
                                <input type="number" step="0.01" id="{{ $uid }}_stw_{{ $idx }}" name="items[{{ $idx }}][SalesTaxWithheldAtSource]" class="form-control ifp-sales-tax-withheld" value="{{ $salesTaxWithheldVal }}" readonly>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </td>
@endif

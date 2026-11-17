@php
    $invoice         = $invoice ?? null;
    $items           = $items   ?? collect();
    $seller          = $seller  ?? null;
    $buyer           = $buyer   ?? null;
    $template        = $template ?? null;
    $templateFields  = $templateFields ?? collect();
    $templateColumns = $templateColumns ?? collect();
    $extraCssClasses = $extraCssClasses ?? '';

    if (! $invoice) {
        return;
    }

    // Template design settings with safe fallbacks
    $docTitle            = trim((string) ($template->CustomTitle ?? 'Sales Tax Invoice')) ?: 'Sales Tax Invoice';
    $showLogo            = (int) ($template->ShowLogo ?? 1) === 1;
    $logoMaxHeight       = max(40, min(200, (int) ($template->LogoMaxHeight ?? 105)));
    $showSellerHeader    = (int) ($template->ShowSellerHeader ?? 1) === 1;
    $sellerHeading       = trim((string) ($template->SellerHeading ?? 'Seller')) ?: 'Seller';
    $buyerHeading        = trim((string) ($template->BuyerHeading ?? 'Buyer')) ?: 'Buyer';
    $showDraftWatermark  = (int) ($template->ShowDraftWatermark ?? 0) === 1;
    $showAmountInWords   = (int) ($template->ShowAmountInWords ?? 0) === 1;
    $showBankDetails     = (int) ($template->ShowBankDetails ?? 0) === 1;
    $bankDetailsText     = trim((string) ($template->BankDetailsText ?? ''));
    $showSignatureBox    = (int) ($template->ShowSignatureBox ?? 0) === 1;
    $signatureLabelLeft  = trim((string) ($template->SignatureLabelLeft ?? 'Prepared / Checked By'));
    $signatureLabelRight = trim((string) ($template->SignatureLabelRight ?? 'Authorized Signature & Stamp'));

    // Seller Logo Source Resolver
    $sellerLogoSrc = '';
    if ($seller) {
        $raw = trim((string) ($seller->ImageUrl ?? ''));
        if ($raw !== '') {
            if (preg_match('#^https?://#i', $raw)) {
                $sellerLogoSrc = $raw;
            } elseif (str_starts_with($raw, '//')) {
                $sellerLogoSrc = request()->getScheme().':'.$raw;
            } elseif (str_starts_with($raw, '/')) {
                $absPath = ltrim(str_replace('\\', '/', $raw), '/');
                if (str_starts_with($absPath, 'business_logos/')) {
                    $sellerLogoSrc = rtrim(request()->getSchemeAndHttpHost(), '/').'/storage/'.$absPath;
                } else {
                    $sellerLogoSrc = rtrim(request()->getSchemeAndHttpHost(), '/').$raw;
                }
            } else {
                $path = ltrim(str_replace('\\', '/', $raw), '/');
                if (str_starts_with($path, 'public/')) {
                    $sellerLogoSrc = asset(substr($path, strlen('public/')));
                } elseif (str_starts_with($path, 'storage/')) {
                    $sellerLogoSrc = asset($path);
                } else {
                    $sellerLogoSrc = asset('storage/'.$path);
                }
            }
        }
    }

    // Helper functions for line calculations
    $lineGst = static function ($item): float {
        return (float) ($item->GSTAmount ?? $item->SalesTaxApplicable ?? 0);
    };
    $lineDisc = static function ($item): float {
        return (float) ($item->DiscountAmount ?? $item->Discount ?? 0);
    };
    $lineExtraTax = static function ($item): float {
        return (float) ($item->ExtraTax ?? 0);
    };
    $lineFurtherTax = static function ($item): float {
        return (float) ($item->FurtherTax ?? 0);
    };
    $lineFedPayable = static function ($item): float {
        return (float) ($item->FedPayable ?? 0);
    };

    $sumQty = 0.0;
    $sumFbrQty = 0.0;
    $sumAmount = 0.0;
    $sumDisc = 0.0;
    $sumFixedNotified = 0.0;
    $sumGst = 0.0;
    $sumExtraTax = 0.0;
    $sumFurtherTax = 0.0;
    $sumFedPayable = 0.0;
    $sumNet = 0.0;
    $showFixedNotifiedColumn = false;
    $hasAnyHsCode = false;
    $hasFbrQty = false;

    foreach ($items as $it) {
        $qVal = (float) ($it->raw_Quantity ?? 0);
        $fbrQVal = (float) ($it->raw_FbrQty ?? 0);
        $amtVal = (float) ($it->raw_Amount ?? 0);
        $discVal = (float) ($it->raw_Discount ?? 0);
        $fixedVal = (float) ($it->raw_FixedNotifiedValue ?? 0);
        $gstVal = (float) ($it->raw_SalesTaxAmount ?? 0);
        $extraVal = (float) ($it->raw_ExtraTax ?? 0);
        $furtherVal = (float) ($it->raw_FurtherTax ?? 0);
        $fedVal = (float) ($it->raw_FedPayable ?? 0);
        $netVal = (float) ($it->raw_TotalValues ?? 0);
        if (abs($netVal) < 0.000001) {
            $netVal = max(0, $amtVal - $discVal) + $gstVal + $extraVal + $furtherVal + $fedVal;
        }

        $sumQty += $qVal;
        $sumFbrQty += $fbrQVal;
        $sumAmount += $amtVal;
        $sumDisc += $discVal;
        $sumFixedNotified += $fixedVal;
        $sumGst += $gstVal;
        $sumExtraTax += $extraVal;
        $sumFurtherTax += $furtherVal;
        $sumFedPayable += $fedVal;
        $sumNet += $netVal;

        if ($fixedVal > 0.000001) {
            $showFixedNotifiedColumn = true;
        }
        if (trim((string) ($it->HsCode ?? '')) !== '' && trim((string) ($it->HsCode ?? '')) !== '—') {
            $hasAnyHsCode = true;
        }
        if (($it->raw_Unit ?? 0) > 1 || (abs($fbrQVal - $qVal) > 0.000001 && $fbrQVal > 0.000001)) {
            $hasFbrQty = true;
        }
    }

    $headerSub = (float) ($invoice->SubTotal ?? 0);
    if ($headerSub <= 0 && $sumAmount > 0) {
        $headerSub = $sumAmount;
    }

    $headerDisc = (float) ($invoice->DiscountAmount ?? $invoice->TotalDiscount ?? 0);
    if ($headerDisc <= 0 && $sumDisc > 0) {
        $headerDisc = $sumDisc;
    }

    $headerGst = (float) ($invoice->GSTAmount ?? $invoice->TotalGST ?? $invoice->SalesTaxApplicable ?? 0);
    if ($headerGst <= 0 && $sumGst > 0) {
        $headerGst = $sumGst;
    }

    $headerExtraTax = (float) ($invoice->ExtraTax ?? 0);
    if ($headerExtraTax <= 0 && $sumExtraTax > 0) {
        $headerExtraTax = $sumExtraTax;
    }

    $headerFurtherTax = (float) ($invoice->FurtherTax ?? 0);
    if ($headerFurtherTax <= 0 && $sumFurtherTax > 0) {
        $headerFurtherTax = $sumFurtherTax;
    }

    $headerFedPayable = (float) ($invoice->FedPayable ?? 0);
    if ($headerFedPayable <= 0 && $sumFedPayable > 0) {
        $headerFedPayable = $sumFedPayable;
    }

    $headerTotal = (float) ($invoice->TotalValues ?? $invoice->TotalAmount ?? $invoice->Total ?? 0);
    if ($headerTotal <= 0 && $sumNet > 0) {
        $headerTotal = $sumNet;
    }

    $extraTaxRate = (float) ($buyer->ExtraTax ?? 0);
    $furtherTaxRate = (float) ($buyer->FurtherTax ?? 0);
    $fedPayableRate = (float) ($buyer->FedPayable ?? 0);

    if ($headerSub > 0.000001) {
        if ($extraTaxRate <= 0.000001 && $headerExtraTax > 0.000001) {
            $extraTaxRate = round(($headerExtraTax / $headerSub) * 100, 2);
        }
        if ($furtherTaxRate <= 0.000001 && $headerFurtherTax > 0.000001) {
            $furtherTaxRate = round(($headerFurtherTax / $headerSub) * 100, 2);
        }
        if ($fedPayableRate <= 0.000001 && $headerFedPayable > 0.000001) {
            $fedPayableRate = round(($headerFedPayable / $headerSub) * 100, 2);
        }
    }

    $advanceTaxRate = 0.0;
    $advanceTaxOn = $buyer && (int) ($buyer->IsAdvanceTax ?? $buyer->IsAdvancedTax ?? 0) === 1;
    if ($advanceTaxOn) {
        $advanceTaxRate = (float) ($buyer->AdvanceTax ?? $buyer->AdvanceTaxRate ?? 0);
    }
    $customerWht236g = $advanceTaxRate > 0.000001
        ? round($headerTotal * ($advanceTaxRate / 100.0), 2)
        : 0.0;
    $grandTotalAllTaxes = round($headerTotal + $customerWht236g, 2);
    $finalPayableAmount = $advanceTaxRate > 0.000001 ? $grandTotalAllTaxes : $headerTotal;
    $amountInWordsText  = \App\Models\PrintTemplateModel::amountInWordsPKR($finalPayableAmount);

    $fbrLogoUrl = asset('images/fbr/fbr-digital-invoicing-badge.jpg');
    $fbrTxId = trim((string) ($invoice->FBRInvoiceNumber ?? data_get($invoice, 'FbrTransactionID', '')));
    $qrPx = 320;
    $qrSource = $fbrTxId !== ''
        ? 'https://api.qrserver.com/v1/create-qr-code/?size='.$qrPx.'x'.$qrPx.'&ecc=M&data='.urlencode($fbrTxId)
        : '';

    $isCancelled = (int) ($invoice->Statusi ?? 0) === 8;
    $isDraftUnposted = ! $isCancelled && $fbrTxId === '';

    // Group template fields by SectionGroup
    $fieldsByGroup = $templateFields->groupBy('SectionGroup');
    $metaFields    = $fieldsByGroup->get('meta', collect())->sortBy('CustomDisplayOrder')->values();
    $sellerFields  = $fieldsByGroup->get('seller', collect())->sortBy('CustomDisplayOrder')->values();
    $buyerFields   = $fieldsByGroup->get('buyer', collect())->sortBy('CustomDisplayOrder')->values();
    $summaryFields = $fieldsByGroup->get('summary', collect())->sortBy('CustomDisplayOrder')->values();
    $notesFields   = $fieldsByGroup->get('notes', collect())->sortBy('CustomDisplayOrder')->values();

    // Helper to evaluate 3-state visibility: 1 = Always Show, 2 = Auto-Hide if empty/zero, 0 = Hide
    $shouldShowField = static function (int $mode, bool $hasValue): bool {
        if ($mode === 0) return false;
        if ($mode === 2) return $hasValue;
        return true;
    };

    // Determine visible line-item columns in CustomDisplayOrder
    $orderedColumns = $templateColumns->sortBy('CustomDisplayOrder')->values();
    $visibleColumns = $orderedColumns->filter(function ($col) use (
        $shouldShowField, $hasAnyHsCode, $hasFbrQty, $sumDisc,
        $showFixedNotifiedColumn, $sumExtraTax, $sumFurtherTax, $sumFedPayable
    ) {
        $mode = (int) ($col->CustomIsOn ?? 1);
        $key  = (string) $col->FieldKey;
        $hasData = match ($key) {
            'HsCode'             => $hasAnyHsCode,
            'FbrQty', 'FbrQtyWithUnit', 'HsUnit' => $hasFbrQty,
            'Discount'           => $sumDisc > 0.000001,
            'FixedNotifiedValue' => $showFixedNotifiedColumn,
            'ExtraTax'           => $sumExtraTax > 0.000001,
            'FurtherTax'         => $sumFurtherTax > 0.000001,
            'FedPayable'         => $sumFedPayable > 0.000001,
            default              => true,
        };
        return $shouldShowField($mode, $hasData);
    })->values();

    $visibleColKeys      = $visibleColumns->pluck('FieldKey')->toArray();
    $isHsCodeSeparateCol = in_array('HsCode', $visibleColKeys, true);
    $hasAnyTotalCol      = $visibleColumns->contains(fn ($c) => (int) ($c->IsTotal ?? 0) === 1);
@endphp

<div class="invoice-print {{ $extraCssClasses }}">
    @if($isCancelled)
        <div class="invoice-print-watermark" aria-hidden="true">CANCELLED</div>
        <div class="invoice-cancelled-stamp">CANCELLED INVOICE</div>
    @elseif($showDraftWatermark && $isDraftUnposted)
        <div class="invoice-print-watermark watermark-draft" aria-hidden="true">DRAFT</div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════════ --}}
    {{--  SECTION 1: Logo (Left) | Document Title + Dynamic Meta (Right)    --}}
    {{-- ══════════════════════════════════════════════════════════════════ --}}
    <div class="print-section-1">
        <div class="print-section-1-logo">
            @if($showLogo && $sellerLogoSrc !== '')
                <img src="{{ $sellerLogoSrc }}" alt="{{ trim((string) ($seller->BusinessName ?? '')) ?: 'Business logo' }}">
            @endif
        </div>
        <div class="print-section-1-right">
            <h1 class="invoice-doc-title">{{ $docTitle }}</h1>
            <div class="print-section-1-meta">
                <table>
                    @foreach($metaFields as $mField)
                        @php
                            $mKey  = (string) $mField->FieldKey;
                            $mMode = (int) ($mField->CustomIsOn ?? 1);
                            $mVal  = match ($mKey) {
                                'DocNo'            => trim((string) ($invoice->DocNo ?? '')),
                                'DocNo2'           => trim((string) ($invoice->DocNo2 ?? '')),
                                'DocNo3'           => trim((string) ($invoice->DocNo3 ?? '')),
                                'InvoiceDate'      => $invoice->InvoiceDate ? \Carbon\Carbon::parse($invoice->InvoiceDate)->format('d M Y') : '',
                                'SaleOfTerm'       => trim((string) ($invoice->SaleOfTerm ?? '')),
                                'FbrTransactionID' => $fbrTxId,
                                default            => trim((string) data_get($invoice, $mKey, '')),
                            };
                            $hasVal = $mVal !== '';
                            if (! $shouldShowField($mMode, $hasVal)) {
                                continue;
                            }
                            $displayVal = $hasVal ? $mVal : ($mKey === 'DocNo' ? 'N/A' : '-');
                            $fSize = (int) ($mField->CustomFontSize ?? 12);
                            $isBold = (int) ($mField->IsBold ?? 0) === 1;
                        @endphp
                        <tr style="font-size: {{ $fSize }}px;">
                            <th>{{ $mField->CustomLabel }}</th>
                            <td class="{{ $isBold ? 'fw-bold' : '' }}">{{ $displayVal }}</td>
                        </tr>
                    @endforeach
                </table>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════ --}}
    {{--  SECTION 2: Dynamic Seller (Left) | Spacer | Dynamic Buyer (Right) --}}
    {{-- ══════════════════════════════════════════════════════════════════ --}}
    <div class="print-section-2">
        <div class="party-col party-seller">
            @if($showSellerHeader)
                <h2>{{ $sellerHeading }}</h2>
                @if($seller)
                    @php
                        $sellerNameField = $sellerFields->firstWhere('FieldKey', 'SellerBusinessName');
                        $showSellerName  = $sellerNameField ? $shouldShowField((int) $sellerNameField->CustomIsOn, !empty(trim((string) ($seller->BusinessName ?? '')))) : true;
                    @endphp
                    @if($showSellerName)
                        <div class="party-name" style="font-size: {{ (int) ($sellerNameField->CustomFontSize ?? 12) }}px;">
                            {{ $seller->BusinessName ?? '—' }}
                        </div>
                    @endif
                    <table>
                        @foreach($sellerFields as $sField)
                            @php
                                if ($sField->FieldKey === 'SellerBusinessName') continue;
                                $sVal = match ((string) $sField->FieldKey) {
                                    'SellerAddress'  => trim((string) ($seller->Address ?? '')),
                                    'SellerProvince' => trim((string) ($seller->Province ?? '')),
                                    'SellerPhone'    => trim((string) ($seller->Phone ?? '')),
                                    'SellerNTN'      => trim((string) ($seller->NTN ?? '')),
                                    'SellerSTN'      => trim((string) ($seller->SalesTaxNumber ?? '')),
                                    'SellerEmail'    => trim((string) ($seller->Email ?? '')),
                                    default          => '',
                                };
                                if (! $shouldShowField((int) $sField->CustomIsOn, $sVal !== '')) {
                                    continue;
                                }
                            @endphp
                            <tr style="font-size: {{ (int) ($sField->CustomFontSize ?? 10) }}px;">
                                <th>{{ $sField->CustomLabel }}</th>
                                <td class="{{ (int) ($sField->IsBold ?? 0) === 1 ? 'fw-bold' : '' }}">{{ $sVal !== '' ? $sVal : '—' }}</td>
                            </tr>
                        @endforeach
                    </table>
                @else
                    <p class="m-0 text-muted">No business profile on file.</p>
                @endif
            @endif
        </div>
        <div class="party-spacer" aria-hidden="true"></div>
        <div class="party-col party-buyer">
            <h2>{{ $buyerHeading }}</h2>
            @if($buyer)
                @php
                    $buyerNameField = $buyerFields->firstWhere('FieldKey', 'BuyerBusinessName');
                    $rawBuyerName   = trim((string) ($buyer->BusinessName ?? ($invoice->CustomerDisplayName ?? '')));
                    $showBuyerName  = $buyerNameField ? $shouldShowField((int) $buyerNameField->CustomIsOn, $rawBuyerName !== '') : true;
                @endphp
                @if($showBuyerName)
                    <div class="party-name" style="font-size: {{ (int) ($buyerNameField->CustomFontSize ?? 12) }}px;">
                        {{ $rawBuyerName !== '' ? $rawBuyerName : '—' }}
                    </div>
                @endif
                <table>
                    @foreach($buyerFields as $bField)
                        @php
                            if ($bField->FieldKey === 'BuyerBusinessName') continue;
                            $bVal = match ((string) $bField->FieldKey) {
                                'BuyerAddress'  => trim((string) ($buyer->Address ?? '')),
                                'BuyerProvince' => trim((string) ($buyer->County ?? '')),
                                'BuyerPhone'    => trim((string) ($buyer->Phone ?? '')),
                                'BuyerNTN'      => trim((string) ($buyer->NTN ?? '')),
                                'BuyerSTN'      => trim((string) ($buyer->SalesTaxNumber ?? '')),
                                'BuyerEmail'    => trim((string) ($buyer->Email ?? '')),
                                default         => '',
                            };
                            if (! $shouldShowField((int) $bField->CustomIsOn, $bVal !== '')) {
                                continue;
                            };
                        @endphp
                        <tr style="font-size: {{ (int) ($bField->CustomFontSize ?? 10) }}px;">
                            <th>{{ $bField->CustomLabel }}</th>
                            <td class="{{ (int) ($bField->IsBold ?? 0) === 1 ? 'fw-bold' : '' }}">{{ $bVal !== '' ? $bVal : '—' }}</td>
                        </tr>
                    @endforeach
                </table>
            @else
                <div class="party-name">{{ $invoice->CustomerDisplayName ?? '—' }}</div>
            @endif
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════ --}}
    {{--  SECTION 3: Dynamic Line-Items Table (`tbl_print_template_columns`) --}}
    {{-- ══════════════════════════════════════════════════════════════════ --}}
    <table class="items">
        <thead>
            <tr>
                @foreach($visibleColumns as $col)
                    @php
                        $align = strtolower((string) ($col->CustomAlignment ?? 'left'));
                        $alignClass = $align === 'right' ? 'text-end' : ($align === 'center' ? 'text-center' : 'text-start');
                        $minW  = trim((string) ($col->CustomWidthMin ?? '50px'));
                        $maxW  = trim((string) ($col->CustomWidthMax ?? '150px'));
                    @endphp
                    <th class="{{ $alignClass }}" style="min-width: {{ $minW }}; max-width: {{ $maxW }};">
                        {{ $col->CustomLabel }}
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($items as $idx => $item)
                <tr>
                    @foreach($visibleColumns as $col)
                        @php
                            $cKey  = (string) $col->FieldKey;
                            $align = strtolower((string) ($col->CustomAlignment ?? 'left'));
                            $alignClass = $align === 'right' ? 'text-end' : ($align === 'center' ? 'text-center' : 'text-start');
                            $minW  = trim((string) ($col->CustomWidthMin ?? '50px'));
                            $maxW  = trim((string) ($col->CustomWidthMax ?? '150px'));

                            $cellContent = '';
                            if ($cKey === 'SrNo') {
                                $cellContent = (string) ($idx + 1);
                            } elseif ($cKey === 'CombinedDescription' || $cKey === 'Description') {
                                $pName = trim((string) ($item->ProductName ?? ''));
                                $hCode = trim((string) ($item->HsCode ?? ''));
                                $pDesc = trim((string) ($item->ProductDescription ?? ''));
                                $htmlParts = [];
                                if ($pName !== '' && $pName !== '—') {
                                    $htmlParts[] = '<strong>' . e($pName) . '</strong>';
                                }
                                if (! $isHsCodeSeparateCol && $hCode !== '' && $hCode !== '—') {
                                    $htmlParts[] = ' <span class="text-muted" style="font-size:10.5px;">(HS Code: ' . e($hCode) . ')</span>';
                                }
                                if ($pDesc !== '' && $pDesc !== '—' && strcasecmp($pDesc, $pName) !== 0) {
                                    $htmlParts[] = '<div class="text-muted mt-1" style="font-size:11.5px; line-height:1.35;">' . e($pDesc) . '</div>';
                                }
                                $cellContent = !empty($htmlParts) ? implode('', $htmlParts) : '—';
                            } elseif ($cKey === 'Unit' || ($cKey === 'CustomUnit' && (!isset($item->CustomUnit) || $item->CustomUnit === '—' || $col->CustomLabel === 'Unit'))) {
                                $smartUnit = trim((string) ($item->Unit ?? ''));
                                if ($smartUnit === '' || in_array($smartUnit, ['No Unit', '—', '-'], true)) {
                                    $smartUnit = trim((string) ($item->CustomUnit ?? ''));
                                }
                                if ($smartUnit === '' || in_array($smartUnit, ['No Unit', '—', '-'], true)) {
                                    $smartUnit = trim((string) ($item->HsUnit ?? ''));
                                }
                                $cellContent = ($smartUnit !== '' && !in_array($smartUnit, ['No Unit', '—', '-'], true)) ? $smartUnit : '—';
                            } else {
                                $cellContent = (string) ($item->{$cKey} ?? '—');
                                if ($cellContent === '' || $cellContent === 'No Unit') {
                                    $cellContent = '—';
                                }
                            }
                        @endphp
                        <td class="{{ $alignClass }} {{ in_array($cKey, ['ValueExclTax', 'SalesTaxAmount', 'TotalInclTax', 'Rate', 'FixedNotifiedValue', 'ExtraTax', 'FurtherTax', 'FedPayable'], true) ? 'col-value-amount' : '' }} {{ in_array($cKey, ['Description', 'CombinedDescription'], true) ? 'td-desc-goods' : '' }}"
                            style="min-width: {{ $minW }}; max-width: {{ $maxW }};">
                            {!! $cellContent !!}
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ max(1, $visibleColumns->count()) }}" class="text-center">No line items.</td>
                </tr>
            @endforelse
        </tbody>
        @if($hasAnyTotalCol && $items->count() > 0)
            <tfoot>
                <tr>
                    @foreach($visibleColumns as $colIdx => $col)
                        @php
                            $cKey       = (string) $col->FieldKey;
                            $align      = strtolower((string) ($col->CustomAlignment ?? 'left'));
                            $alignClass = $align === 'right' ? 'text-end' : ($align === 'center' ? 'text-center' : 'text-start');
                            $isTotal    = (int) ($col->IsTotal ?? 0) === 1;
                            $totText    = '';
                            if ($isTotal) {
                                $totText = match ($cKey) {
                                    'FbrQty', 'FbrQtyWithUnit' => number_format($sumFbrQty, 2),
                                    'Quantity', 'QtyWithUnit'  => number_format($sumQty, 2),
                                    'Discount'                 => number_format($sumDisc, 2),
                                    'ValueExclTax'             => number_format($sumAmount, 2),
                                    'FixedNotifiedValue'       => number_format($sumFixedNotified, 2),
                                    'SalesTaxAmount'           => number_format($sumGst, 2),
                                    'ExtraTax'                 => number_format($sumExtraTax, 2),
                                    'FurtherTax'               => number_format($sumFurtherTax, 2),
                                    'FedPayable'               => number_format($sumFedPayable, 2),
                                    'TotalInclTax'             => number_format($sumNet, 2),
                                    default                    => '',
                                };
                            } elseif ($colIdx === 0) {
                                $totText = 'Total';
                            }
                        @endphp
                        <th class="{{ $alignClass }}">{{ $totText }}</th>
                    @endforeach
                </tr>
            </tfoot>
        @endif
    </table>

    {{-- ══════════════════════════════════════════════════════════════════ --}}
    {{--  SECTION 4: FBR QR Badge (Left) + Dynamic Summary Totals (Right)   --}}
    {{-- ══════════════════════════════════════════════════════════════════ --}}
    <div class="invoice-print-footer">
        <div class="after-table-summary">
            <div class="fbr-digital-section">
                <div class="fbr-digital-title">FBR Digital Invoice</div>
                <div class="fbr-digital-row">
                    <div class="fbr-badge-wrap">
                        <div class="fbr-badge-slot">
                            <img src="{{ $fbrLogoUrl }}" alt="FBR Digital Invoicing System" class="fbr-brand-logo">
                        </div>
                    </div>
                    <div class="fbr-badge-wrap">
                        <div class="fbr-badge-slot">
                            @if($fbrTxId !== '')
                                <img src="{{ $qrSource }}" alt="" class="fbr-qr-image" width="{{ $qrPx }}" height="{{ $qrPx }}">
                            @else
                                <div class="fbr-qr-placeholder" aria-hidden="true"></div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="totals">
                <div class="totals-inner">
                    <table>
                        @foreach($summaryFields as $sumField)
                            @php
                                $sKey  = (string) $sumField->FieldKey;
                                $sMode = (int) ($sumField->CustomIsOn ?? 1);
                                $sVal  = match ($sKey) {
                                    'TotalExclTax'    => $headerSub,
                                    'Discount'        => $headerDisc,
                                    'TotalSalesTax'   => $headerGst,
                                    'ExtraTax'        => $headerExtraTax,
                                    'FurtherTax'      => $headerFurtherTax,
                                    'FedPayable'      => $headerFedPayable,
                                    'TotalInclTax'    => $headerTotal,
                                    'CustomerWht236g' => $customerWht236g,
                                    'GrandTotal'      => $grandTotalAllTaxes,
                                    default           => 0.0,
                                };
                                $isNonZero = match ($sKey) {
                                    'CustomerWht236g', 'GrandTotal' => $advanceTaxRate > 0.000001,
                                    default => abs($sVal) > 0.000001,
                                };
                                if (! $shouldShowField($sMode, $isNonZero)) {
                                    continue;
                                }

                                $label = rtrim((string) $sumField->CustomLabel, ':');
                                if ($sKey === 'ExtraTax' && $extraTaxRate > 0.000001) {
                                    $label .= ' (' . number_format($extraTaxRate, 2) . '%)';
                                } elseif ($sKey === 'FurtherTax' && $furtherTaxRate > 0.000001) {
                                    $label .= ' (' . number_format($furtherTaxRate, 2) . '%)';
                                } elseif ($sKey === 'FedPayable' && $fedPayableRate > 0.000001) {
                                    $label .= ' (' . number_format($fedPayableRate, 2) . '%)';
                                } elseif ($sKey === 'CustomerWht236g' && $advanceTaxRate > 0.000001) {
                                    $label .= ' (' . number_format($advanceTaxRate, 2) . '%)';
                                }
                                $label .= ':';

                                $rowClass = match ($sKey) {
                                    'TotalInclTax' => 'totals-inc totals-rule-top totals-rule-bottom',
                                    'GrandTotal'   => 'grand totals-rule-top',
                                    default        => '',
                                };
                                $fSize  = (int) ($sumField->CustomFontSize ?? 12);
                                $isBold = (int) ($sumField->IsBold ?? 0) === 1;
                            @endphp
                            <tr class="{{ $rowClass }}" style="font-size: {{ $fSize }}px;">
                                <th class="{{ $isBold ? 'fw-bold' : '' }}">{{ $label }}</th>
                                <td class="{{ $isBold ? 'fw-bold' : '' }}">{{ number_format($sVal, 2) }} PKR</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            </div>
        </div>

        {{-- Amount in Words --}}
        @if($showAmountInWords)
            <div class="amount-in-words-line text-end mt-2 pt-2 border-top">
                <strong class="text-uppercase" style="font-size: 11.5px; letter-spacing: 0.03em;">AMOUNT IN WORDS:</strong>
                <span class="fw-normal text-dark ms-1" style="font-size: 12px;">{{ $amountInWordsText }}</span>
            </div>
        @endif
    </div>

    {{-- Optional Bank / Payment Details --}}
    @if($showBankDetails && $bankDetailsText !== '')
        <div class="p-2 mt-3 bg-light border rounded" style="white-space: pre-line;">
            <strong>Bank / Payment Instructions:</strong><br>{{ $bankDetailsText }}
        </div>
    @endif

    {{-- Dynamic Notes Fields (`SellerNotes`, `InvoiceNotes`) --}}
    @foreach($notesFields as $nField)
        @php
            $nKey  = (string) $nField->FieldKey;
            $nMode = (int) ($nField->CustomIsOn ?? 2);
            $nText = match ($nKey) {
                'SellerNotes'  => trim((string) ($seller->Notes ?? '')),
                'InvoiceNotes' => trim((string) ($invoice->Notes ?? '')),
                default        => '',
            };
            if (! $shouldShowField($nMode, $nText !== '') || $nText === '') {
                continue;
            }
            $fSize = (int) ($nField->CustomFontSize ?? 12);
        @endphp
        @if($nKey === 'SellerNotes')
            <div class="notes business-notes" style="font-size: {{ $fSize }}px;">
                <strong class="notes-label">{{ $nField->CustomLabel }}</strong>
                <div class="notes-body">{{ ltrim($nText) }}</div>
            </div>
        @else
            <div class="notes" style="font-size: {{ $fSize }}px;">
                <strong>{{ $nField->CustomLabel }}</strong><br>{{ $nText }}
            </div>
        @endif
    @endforeach

    {{-- Optional Signature & Stamp Footer --}}
    @if($showSignatureBox)
        <div class="d-flex justify-content-between align-items-end mt-5 pt-3">
            <div class="border-top border-dark pt-1 text-center fw-semibold" style="width: 220px;">
                {{ $signatureLabelLeft }}
            </div>
            <div class="border-top border-dark pt-1 text-center fw-semibold" style="width: 220px;">
                {{ $signatureLabelRight }}
            </div>
        </div>
    @endif
</div>


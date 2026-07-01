(function (global) {
  'use strict';
  function initPurchaseTaxForm(ctx) {
    const uid = ctx.uid;
    const body = ctx.body;
    const selCls = ctx.selCls;
    const IFP_TAX_COMPACT = !!ctx.inModal;
    var taxSroModalEl = null, taxSroModalBody = null, taxSroSlotHost = null, taxSroSlotNode = null;

    const INV_HSCODE_URL = ctx.routes.hscode;
    const INV_SALE_TYPES_URL = ctx.routes.saleTypes;
    const INV_SALE_TYPE_RATES = ctx.routes.saleTypeRates;
    const INV_SRO_SCHEDULES = ctx.routes.sroSchedules;
    const INV_SRO_ITEMS = ctx.routes.sroItems;

    function rowEl(row, selector) {
        var fromRow = row ? row.querySelector(selector) : null;
        if (fromRow) return fromRow;
        if (!IFP_TAX_COMPACT || !taxSroSlotNode || !taxSroSlotHost) return null;
        var rowTd = row ? row.querySelector('.ifp-col-tax-sro') : null;
        if (rowTd && rowTd === taxSroSlotHost) {
            return taxSroSlotNode.querySelector(selector);
        }
        return null;
    }

    const taxCalc = global.IfpLineTaxCalculator.create({
      uid: uid,
      body: body,
      partySelectId: uid + '_SupplierID',
      whtAmountId: uid + '_SupplierWht236g',
      trWhtId: uid + '_tr_supplier_wht',
      rowEl: rowEl
    });
    const calcRow = taxCalc.calcRow;
    const calcSummary = taxCalc.calcSummary;
    const applySupplierMetaToRow = taxCalc.applyPartyMetaToRow;
    const n = taxCalc.n;

    function restoreTaxSroSlotToRow() {
        if (taxSroModalBody && taxSroSlotHost && taxSroSlotNode && taxSroModalBody.contains(taxSroSlotNode)) {
            taxSroSlotHost.appendChild(taxSroSlotNode);
            taxSroSlotNode.classList.add('d-none');
        }
        taxSroSlotHost = null;
        taxSroSlotNode = null;
    }

    function openTaxSroModalForRow(row) {
        if (!IFP_TAX_COMPACT || !taxSroModalEl || !taxSroModalBody) return;
        var td = row.querySelector('.ifp-col-tax-sro');
        var slot = td && td.querySelector('.ifp-tax-sro-slot');
        if (!td || !slot) return;
        restoreTaxSroSlotToRow();
        taxSroSlotHost = td;
        taxSroSlotNode = slot;
        taxSroModalBody.appendChild(slot);
        slot.classList.remove('d-none');
        bootstrap.Modal.getOrCreateInstance(taxSroModalEl).show();
    }

    if (IFP_TAX_COMPACT) {
        taxSroModalEl = document.getElementById(uid + '_taxSroModal');
        taxSroModalBody = document.getElementById(uid + '_taxSroModalBody');
        if (taxSroModalEl && taxSroModalBody) {
            body.addEventListener('click', function(ev) {
                var btn = ev.target.closest('.ifp-tax-sro-open-btn');
                if (!btn || !body.contains(btn)) return;
                var row = btn.closest('.ifp-row');
                if (row) openTaxSroModalForRow(row);
            });
            taxSroModalEl.addEventListener('shown.bs.modal', function() {
                var host = taxSroSlotHost;
                var row = host ? host.closest('.ifp-row') : null;
                ifpTaxFlashAllListboxes(row);
            });
            taxSroModalEl.addEventListener('hidden.bs.modal', function() {
                var slot = taxSroModalBody.querySelector('.ifp-tax-sro-slot');
                var host = taxSroSlotHost;
                if (slot && host) {
                    host.appendChild(slot);
                    slot.classList.add('d-none');
                }
                taxSroSlotHost = null;
                taxSroSlotNode = null;
            });
        }
    }

    function ifpTaxUseListboxUi(sel) {
        return !!(IFP_TAX_COMPACT && sel && sel.classList && sel.classList.contains('ifp-inv-tax-listbox'));
    }
    function ifpTaxSlotFromSelect(sel) {
        return sel ? sel.closest('.ifp-tax-sro-slot') : null;
    }
    function ifpTaxFilterInputFor(sel) {
        var slot = ifpTaxSlotFromSelect(sel);
        if (!slot || !sel.dataset || !sel.dataset.ifpPair) return null;
        return slot.querySelector('input.ifp-tax-listbox-search[data-ifp-pair="' + sel.dataset.ifpPair + '"]');
    }
    function ifpTaxClearFilter(sel) {
        var inp = ifpTaxFilterInputFor(sel);
        if (inp) inp.value = '';
    }
    function ifpTaxAppendHiddenPlaceholder(sel, labelText) {
        if (!sel || !ifpTaxUseListboxUi(sel)) return null;
        var o = document.createElement('option');
        o.value = '';
        o.textContent = labelText;
        o.setAttribute('data-ifp-placeholder', '1');
        o.setAttribute('hidden', 'hidden');
        if (labelText) o.setAttribute('title', labelText);
        sel.appendChild(o);
        return o;
    }
    function ifpTaxSelectHiddenPlaceholder(sel) {
        if (!sel) return;
        for (var i = 0; i < sel.options.length; i++) {
            var o = sel.options[i];
            if (o.getAttribute('data-ifp-placeholder') === '1') {
                o.selected = true;
            } else {
                o.selected = false;
            }
        }
    }
    function ifpTaxDeselectPlaceholder(sel) {
        if (!sel) return;
        for (var i = 0; i < sel.options.length; i++) {
            if (sel.options[i].getAttribute('data-ifp-placeholder') === '1') {
                sel.options[i].selected = false;
            }
        }
    }
    function ifpTaxListboxThemeRgbBg() {
        try {
            var raw = getComputedStyle(document.documentElement).getPropertyValue('--primary-rgb').trim();
            if (raw) return 'rgb(' + raw + ')';
        } catch (e) {}
        return 'rgb(111, 66, 193)';
    }
    /** Native listbox: paint chosen row (same solid as .btn-primary / save buttons) */
    var IFP_TAX_LB_ROW_FG = '#fff';
    /** Loose match for FBR IDs (string vs number, trim). */
    function ifpTaxOptionValuesMatch(a, b) {
        var sa = String(a != null ? a : '').trim();
        var sb = String(b != null ? b : '').trim();
        if (sa === sb) return true;
        if (sa !== '' && sb !== '' && !isNaN(Number(sa)) && !isNaN(Number(sb)) && Number(sa) === Number(sb)) return true;
        return false;
    }
    function ifpTaxPaintSelectedOption(sel) {
        if (!sel || !ifpTaxUseListboxUi(sel)) return;
        var selVal = String(sel.value || '');
        var si = sel.selectedIndex;
        for (var i = 0; i < sel.options.length; i++) {
            var opt = sel.options[i];
            if (opt.getAttribute('data-ifp-placeholder') === '1') {
                opt.style.removeProperty('background-color');
                opt.style.removeProperty('color');
                opt.style.removeProperty('-webkit-text-fill-color');
                continue;
            }
            opt.style.removeProperty('background-color');
            opt.style.removeProperty('color');
            opt.style.removeProperty('-webkit-text-fill-color');
            var byIndex = (i === si && si >= 0 && opt.getAttribute('data-ifp-placeholder') !== '1');
            var byValue = selVal !== '' && ifpTaxOptionValuesMatch(selVal, opt.value);
            if (byValue || byIndex) {
                opt.style.setProperty('background-color', ifpTaxListboxThemeRgbBg(), 'important');
                opt.style.setProperty('color', IFP_TAX_LB_ROW_FG, 'important');
                opt.style.setProperty('-webkit-text-fill-color', IFP_TAX_LB_ROW_FG, 'important');
            }
        }
    }
    function ifpTaxApplyListboxFilter(sel) {
        if (!sel || !ifpTaxUseListboxUi(sel)) return;
        var inp = ifpTaxFilterInputFor(sel);
        var q = (inp && inp.value) ? String(inp.value).trim().toLowerCase() : '';
        var selVal = String(sel.value || '');
        for (var i = 0; i < sel.options.length; i++) {
            var opt = sel.options[i];
            if (opt.getAttribute('data-ifp-placeholder') === '1') {
                opt.hidden = true;
                continue;
            }
            var displayLabel = ((opt.textContent || '') + '').trim();
            var label = displayLabel.toLowerCase();
            var ov = String(opt.value || '');
            var show = q === '' || label.indexOf(q) !== -1;
            if (ov !== '' && ov === selVal) show = true;
            if (ov === '') {
                show = q === '' || label.indexOf(q) !== -1;
                if (opt.getAttribute('data-ifp-none-visible') === '1' && sel.selectedIndex >= 0 && sel.options[sel.selectedIndex] === opt) {
                    show = true;
                }
            }
            opt.hidden = !show;
            if (displayLabel) opt.setAttribute('title', displayLabel);
            else opt.removeAttribute('title');
        }
    }
    /** Move the chosen row to the top of the list (after hidden placeholder) so it is visible first. */
    function ifpTaxMoveSelectedOptionFirst(sel) {
        if (!sel || !ifpTaxUseListboxUi(sel)) return;
        var selectedOpt = null;
        var si = sel.selectedIndex;
        if (si >= 0 && sel.options[si] && sel.options[si].getAttribute('data-ifp-placeholder') !== '1') {
            selectedOpt = sel.options[si];
        }
        if (!selectedOpt) {
            var selVal = String(sel.value || '').trim();
            for (var i = 0; i < sel.options.length; i++) {
                var opt = sel.options[i];
                if (opt.getAttribute('data-ifp-placeholder') === '1') continue;
                if (selVal !== '' && ifpTaxOptionValuesMatch(selVal, opt.value)) {
                    selectedOpt = opt;
                    break;
                }
                if (!selVal && opt.getAttribute('data-ifp-none-visible') === '1' && opt.selected) {
                    selectedOpt = opt;
                    break;
                }
            }
        }
        if (!selectedOpt) return;
        var insertBefore = null;
        for (var j = 0; j < sel.options.length; j++) {
            var o = sel.options[j];
            if (o.getAttribute('data-ifp-placeholder') === '1') continue;
            if (o !== selectedOpt) {
                insertBefore = o;
                break;
            }
        }
        if (insertBefore) {
            sel.insertBefore(selectedOpt, insertBefore);
        } else {
            var first = sel.options[0];
            if (first && first !== selectedOpt) {
                sel.insertBefore(selectedOpt, first);
            }
        }
        selectedOpt.selected = true;
        sel.value = String(selectedOpt.value);
    }

    function ifpTaxFlashListboxSelection(sel) {
        if (!sel || !ifpTaxUseListboxUi(sel)) return;
        ifpTaxApplyListboxFilter(sel);
        var wantRaw = sel.getAttribute('data-selected');
        if (wantRaw === null || wantRaw === undefined) wantRaw = sel.value || '';
        var want = String(wantRaw).trim();
        if (want === '') {
            var nv = null;
            for (var ni = 0; ni < sel.options.length; ni++) {
                if (sel.options[ni].getAttribute('data-ifp-none-visible') === '1') {
                    nv = sel.options[ni];
                    break;
                }
            }
            if (nv) {
                nv.selected = true;
                sel.value = nv.value;
                ifpTaxDeselectPlaceholder(sel);
                ifpTaxMoveSelectedOptionFirst(sel);
                ifpTaxApplyListboxFilter(sel);
                ifpTaxScrollListboxToSelected(sel);
                return;
            }
            ifpTaxSelectHiddenPlaceholder(sel);
            ifpTaxScrollListboxToSelected(sel);
            ifpTaxPaintSelectedOption(sel);
            return;
        }
        var sv = want;
        var found = false;
        for (var i = 0; i < sel.options.length; i++) {
            var o = sel.options[i];
            if (o.getAttribute('data-ifp-placeholder') === '1') continue;
            if (ifpTaxOptionValuesMatch(sv, o.value)) {
                sel.selectedIndex = i;
                sel.value = String(o.value);
                found = true;
                break;
            }
        }
        if (!found) {
            ifpTaxSelectHiddenPlaceholder(sel);
        } else {
            ifpTaxDeselectPlaceholder(sel);
        }
        ifpTaxMoveSelectedOptionFirst(sel);
        ifpTaxApplyListboxFilter(sel);
        ifpTaxScrollListboxToSelected(sel);
    }
    /** SRO columns: no rate / no rows from API — show a visible "None" row (not the hidden listbox placeholder). */
    function ifpTaxFillSroListWithNone(sel) {
        if (!sel) return;
        ifpTaxClearFilter(sel);
        sel.innerHTML = '';
        var nopt = document.createElement('option');
        nopt.value = '';
        nopt.textContent = 'None';
        nopt.setAttribute('data-ifp-none-visible', '1');
        sel.appendChild(nopt);
        nopt.selected = true;
        sel.setAttribute('data-selected', '');
        if (ifpTaxUseListboxUi(sel)) {
            ifpTaxMoveSelectedOptionFirst(sel);
            ifpTaxScrollListboxToSelected(sel);
        }
    }
    function ifpTaxScrollListboxToSelected(sel) {
        if (!sel || !ifpTaxUseListboxUi(sel)) return;
        requestAnimationFrame(function() {
            try {
                var i = sel.selectedIndex;
                if (i >= 0 && sel.options[i]) {
                    var o = sel.options[i];
                    if (o.getAttribute('data-ifp-placeholder') !== '1') {
                        o.scrollIntoView({ block: 'start' });
                    }
                }
            } catch (e) {}
            ifpTaxPaintSelectedOption(sel);
        });
    }
    function ifpTaxBindListboxRowPaint(sel) {
        if (!sel || !ifpTaxUseListboxUi(sel) || sel.dataset.ifpTaxRowPaint === '1') return;
        sel.dataset.ifpTaxRowPaint = '1';
        function paint() {
            requestAnimationFrame(function() {
                ifpTaxMoveSelectedOptionFirst(sel);
                ifpTaxApplyListboxFilter(sel);
                ifpTaxPaintSelectedOption(sel);
            });
        }
        ['mouseup', 'keyup', 'blur', 'change'].forEach(function(ev) { sel.addEventListener(ev, paint); });
    }
    function ifpTaxBindListboxSearch(sel) {
        var inp = ifpTaxFilterInputFor(sel);
        if (!inp || !sel || !ifpTaxUseListboxUi(sel)) return;
        if (inp.dataset.ifpTaxSearchBound === '1') return;
        inp.dataset.ifpTaxSearchBound = '1';
        function run() {
            ifpTaxApplyListboxFilter(sel);
            ifpTaxFlashListboxSelection(sel);
        }
        inp.addEventListener('input', run);
        inp.addEventListener('search', run);
    }
    function ifpTaxBindAllListboxSearches(row) {
        if (!IFP_TAX_COMPACT) return;
        ['.ifp-sale-type', '.ifp-sale-type-to-rate', '.ifp-sro-schedule', '.ifp-sro-item'].forEach(function(sel) {
            var el = rowEl(row, sel);
            if (el && ifpTaxUseListboxUi(el)) {
                ifpTaxBindListboxSearch(el);
                ifpTaxBindListboxRowPaint(el);
            }
        });
    }
    function ifpTaxFlashAllListboxes(row) {
        if (!IFP_TAX_COMPACT || !row) return;
        ['.ifp-sale-type', '.ifp-sale-type-to-rate', '.ifp-sro-schedule', '.ifp-sro-item'].forEach(function(sel) {
            var el = rowEl(row, sel);
            if (el && ifpTaxUseListboxUi(el)) {
                ifpTaxApplyListboxFilter(el);
                ifpTaxFlashListboxSelection(el);
            }
        });
    }

    function updateTaxSroTriggerSummary(row) {
        if (!row) return;
        var saleLine = row.querySelector('.ifp-tax-sro-trigger__sale');
        var rateLine = row.querySelector('.ifp-tax-sro-trigger__rate');
        if (!saleLine || !rateLine) return;
        var saleTypeSel = rowEl(row, '.ifp-sale-type');
        var saleRateSel = rowEl(row, '.ifp-sale-type-to-rate');
        var saleTypeText = '';
        var saleRateText = '';
        if (saleTypeSel && saleTypeSel.selectedIndex >= 0) {
            saleTypeText = String(saleTypeSel.options[saleTypeSel.selectedIndex].textContent || '').trim();
        }
        if (saleRateSel && saleRateSel.selectedIndex >= 0) {
            saleRateText = String(saleRateSel.options[saleRateSel.selectedIndex].textContent || '').trim();
        }
        saleLine.textContent = (saleTypeText && saleTypeText.toLowerCase().indexOf('select') !== 0 && saleTypeText.toLowerCase().indexOf('please') !== 0) ? saleTypeText : 'Sale type —';
        rateLine.textContent = (saleRateText && saleRateText.toLowerCase().indexOf('select') !== 0 && saleRateText.toLowerCase().indexOf('please') !== 0) ? saleRateText : 'Rate profile —';
        saleLine.setAttribute('title', saleLine.textContent);
        rateLine.setAttribute('title', rateLine.textContent);
        var openBtn = row.querySelector('.ifp-tax-sro-open-btn');
        if (openBtn) {
            openBtn.setAttribute('title', saleLine.textContent + ' · ' + rateLine.textContent + ' — Open Tax & SRO');
        }
    }

    // ── Tax dropdowns (per row): HS Code → UOM; Sale Type → Sale Type To Rate → SRO Schedule → SRO Item ──
    function clearUomForRow(row) {
        var sel = rowEl(row, '.ifp-uom');
        if (!sel) return;
        sel.innerHTML = '';
        var opt = document.createElement('option');
        opt.value = ''; opt.textContent = 'Select UOM';
        sel.appendChild(opt);
    }

    /** When HS code is missing or returns no UOM list, use the product option's default UOM (data-uom-id / data-uom-label). */
    function applyUomFromProductFallback(row) {
        var uomSel = rowEl(row, '.ifp-uom');
        var prodSel = row.querySelector('.ifp-product');
        if (!uomSel || !prodSel || !prodSel.value) return;
        var opt = (prodSel.selectedOptions && prodSel.selectedOptions.length)
            ? prodSel.selectedOptions[0]
            : prodSel.options[prodSel.selectedIndex];
        if (!opt) return;
        var id = String(opt.getAttribute('data-uom-id') || '').trim();
        if (!id) return;
        var lbl = String(opt.getAttribute('data-uom-label') || '').trim();
        var label = lbl || ('Unit #' + id);
        clearUomForRow(row);
        var o = document.createElement('option');
        o.value = id;
        o.textContent = label;
        uomSel.appendChild(o);
        var want = String(uomSel.getAttribute('data-selected') || id).trim();
        uomSel.value = want;
        if (uomSel.value !== want) {
            uomSel.value = id;
            uomSel.setAttribute('data-selected', id);
        }
    }

    function fetchHsCodeInfoForRow(row) {
        var hsInp = rowEl(row, '.ifp-hscode');
        var uomSel = rowEl(row, '.ifp-uom');
        var code = (hsInp && hsInp.value) ? String(hsInp.value).trim() : '';
        if (uomSel) clearUomForRow(row);
        if (!code) {
            applyUomFromProductFallback(row);
            return;
        }
        fetch(INV_HSCODE_URL + '?code=' + encodeURIComponent(code), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data || data.status !== 'success' || !uomSel) return;
                var selected = uomSel.getAttribute('data-selected') || '';
                clearUomForRow(row);
                if (Array.isArray(data.uoms) && data.uoms.length) {
                    data.uoms.forEach(function(item) {
                        var opt = document.createElement('option');
                        opt.value = item.id; opt.textContent = item.text;
                        if (selected && String(selected) === String(item.id)) opt.selected = true;
                        uomSel.appendChild(opt);
                    });
                } else {
                    applyUomFromProductFallback(row);
                }
            })
            .catch(function() {
                if (uomSel) applyUomFromProductFallback(row);
            });
    }

    function ifpFindDefaultSaleTypeOption(sel) {
        if (!sel) return null;
        var want = 'Goods at standard rate (default)';
        var wantLc = want.toLowerCase();
        var opts = sel.querySelectorAll('option');
        for (var i = 0; i < opts.length; i++) {
            var opt = opts[i];
            if (!opt.value || opt.getAttribute('data-ifp-placeholder') === '1') continue;
            var label = (opt.textContent || '').trim();
            var ll = label.toLowerCase();
            if (ll === wantLc || (ll.indexOf('goods at standard rate') !== -1 && ll.indexOf('default') !== -1)) {
                return opt;
            }
        }
        return null;
    }

    function ifpApplyDefaultSaleTypeIfNeeded(sel) {
        if (!sel) return false;
        var saved = String(sel.getAttribute('data-selected') || '').trim();
        if (saved) return false;
        if (String(sel.value || '').trim()) return false;
        var opt = ifpFindDefaultSaleTypeOption(sel);
        if (!opt) return false;
        opt.selected = true;
        sel.value = String(opt.value);
        sel.setAttribute('data-selected', String(opt.value));
        if (ifpTaxUseListboxUi(sel)) {
            ifpTaxDeselectPlaceholder(sel);
            ifpTaxFlashListboxSelection(sel);
        }
        return true;
    }

    function loadSaleTypesForRow(row) {
        var sel = rowEl(row, '.ifp-sale-type');
        if (!sel) return;
        var selected = String(sel.getAttribute('data-selected') || '').trim();
        ifpTaxClearFilter(sel);
        if (IFP_TAX_COMPACT) {
            ifpTaxClearFilter(rowEl(row, '.ifp-sale-type-to-rate'));
            ifpTaxClearFilter(rowEl(row, '.ifp-sro-schedule'));
            ifpTaxClearFilter(rowEl(row, '.ifp-sro-item'));
        }
        sel.innerHTML = '';
        if (ifpTaxUseListboxUi(sel)) {
            ifpTaxAppendHiddenPlaceholder(sel, 'Please select sale type');
            ifpTaxSelectHiddenPlaceholder(sel);
        } else {
            var ph0 = document.createElement('option');
            ph0.value = '';
            ph0.textContent = 'Select Sale Type';
            sel.appendChild(ph0);
        }
        fetch(INV_SALE_TYPES_URL, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data || data.status !== 'success' || !Array.isArray(data.items)) {
                    if (ifpTaxUseListboxUi(sel)) {
                        if (!sel.value) ifpTaxSelectHiddenPlaceholder(sel);
                        else ifpTaxDeselectPlaceholder(sel);
                        ifpTaxFlashListboxSelection(sel);
                    }
                    updateTaxSroTriggerSummary(row);
                    return;
                }
                data.items.forEach(function(item) {
                    var opt = document.createElement('option');
                    opt.value = String(item.id != null ? item.id : '').trim();
                    opt.textContent = item.text;
                    if (selected && ifpTaxOptionValuesMatch(selected, item.id)) opt.selected = true;
                    sel.appendChild(opt);
                });
                if (ifpTaxUseListboxUi(sel)) {
                    ifpTaxDeselectPlaceholder(sel);
                    ifpTaxFlashListboxSelection(sel);
                }
                if (!selected) {
                    ifpApplyDefaultSaleTypeIfNeeded(sel);
                }
                var v = String(sel.value || '').trim();
                if (!v && selected) {
                    var wantSt = String(selected).trim();
                    for (var ii = 0; ii < sel.options.length; ii++) {
                        if (sel.options[ii].getAttribute('data-ifp-placeholder') === '1') continue;
                        if (ifpTaxOptionValuesMatch(wantSt, sel.options[ii].value)) {
                            sel.selectedIndex = ii;
                            sel.value = String(sel.options[ii].value);
                            if (ifpTaxUseListboxUi(sel)) {
                                ifpTaxDeselectPlaceholder(sel);
                                ifpTaxFlashListboxSelection(sel);
                            }
                            v = String(sel.value || '').trim();
                            break;
                        }
                    }
                }
                if (v) {
                    sel.setAttribute('data-selected', v);
                }
                updateTaxSroTriggerSummary(row);
                if (v) loadSaleTypeToRatesForRow(row, v);
            })
            .catch(function() {});
    }

    function loadSaleTypeToRatesForRow(row, transactionTypeId) {
        var sel = rowEl(row, '.ifp-sale-type-to-rate');
        if (!sel) return;
        var selected = String(sel.getAttribute('data-selected') || '').trim();
        ifpTaxClearFilter(sel);
        sel.innerHTML = '';
        if (ifpTaxUseListboxUi(sel)) {
            ifpTaxAppendHiddenPlaceholder(sel, 'Please select sale type to rate');
            ifpTaxSelectHiddenPlaceholder(sel);
        } else {
            var ph = document.createElement('option'); ph.value = ''; ph.textContent = 'Select Sale Type To Rate'; sel.appendChild(ph);
        }
        if (!transactionTypeId) {
            if (ifpTaxUseListboxUi(sel)) ifpTaxFlashListboxSelection(sel);
            updateTaxSroTriggerSummary(row);
            return;
        }
        fetch(INV_SALE_TYPE_RATES + '?transaction_type_id=' + encodeURIComponent(transactionTypeId), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data || data.status !== 'success' || !Array.isArray(data.items)) {
                    if (ifpTaxUseListboxUi(sel)) ifpTaxFlashListboxSelection(sel);
                    updateTaxSroTriggerSummary(row);
                    return;
                }
                data.items.forEach(function(item) {
                    var opt = document.createElement('option');
                    opt.value = String(item.id != null ? item.id : '').trim();
                    opt.textContent = item.text;
                    var rp = (item.rate_pct !== undefined && item.rate_pct !== null && item.rate_pct !== '') ? String(item.rate_pct) : '';
                    if (rp) opt.setAttribute('data-rate-pct', rp);
                    var rv = (item.rate_value !== undefined && item.rate_value !== null && item.rate_value !== '') ? String(item.rate_value) : '';
                    if (rv) opt.setAttribute('data-rate-value', rv);
                    var rt = (item.rate_type !== undefined && item.rate_type !== null && item.rate_type !== '') ? String(item.rate_type) : '';
                    if (rt) opt.setAttribute('data-rate-type', rt);
                    if (selected && ifpTaxOptionValuesMatch(selected, item.id)) opt.selected = true;
                    sel.appendChild(opt);
                });
                if (ifpTaxUseListboxUi(sel)) {
                    ifpTaxDeselectPlaceholder(sel);
                    ifpTaxFlashListboxSelection(sel);
                }
                var v = String(sel.value || '').trim();
                if (!v && selected) {
                    var wantR = String(selected).trim();
                    for (var ri = 0; ri < sel.options.length; ri++) {
                        if (sel.options[ri].getAttribute('data-ifp-placeholder') === '1') continue;
                        if (ifpTaxOptionValuesMatch(wantR, sel.options[ri].value)) {
                            sel.selectedIndex = ri;
                            sel.value = String(sel.options[ri].value);
                            if (ifpTaxUseListboxUi(sel)) {
                                ifpTaxDeselectPlaceholder(sel);
                                ifpTaxFlashListboxSelection(sel);
                            }
                            v = String(sel.value || '').trim();
                            break;
                        }
                    }
                }
                if (!v && data.items.length === 1) {
                    var onlyId = String(data.items[0].id != null ? data.items[0].id : '').trim();
                    sel.setAttribute('data-selected', onlyId);
                    if (ifpTaxUseListboxUi(sel)) {
                        ifpTaxFlashListboxSelection(sel);
                    }
                    v = String(sel.value || '').trim();
                }
                if (ifpTaxUseListboxUi(sel) && !v && data.items.length > 1) {
                    ifpTaxSelectHiddenPlaceholder(sel);
                    ifpTaxFlashListboxSelection(sel);
                }
                if (v) {
                    var opt = sel.options[sel.selectedIndex];
                    if (opt && opt.getAttribute('data-rate-pct')) {
                        var gstEl = rowEl(row, '.ifp-gst-pct');
                        if (gstEl) gstEl.value = opt.getAttribute('data-rate-pct');
                    }
                    var rtInp = rowEl(row, '.ifp-rate-type');
                    if (rtInp) rtInp.value = (opt && opt.getAttribute('data-rate-type')) ? opt.getAttribute('data-rate-type') : '';
                    calcRow(row);
                    calcSummary();
                    updateTaxSroTriggerSummary(row);
                    loadSroSchedulesForRow(row, v);
                } else {
                    updateTaxSroTriggerSummary(row);
                }
            })
            .catch(function() {});
    }

    function loadSroSchedulesForRow(row, rateId) {
        var sel = rowEl(row, '.ifp-sro-schedule');
        if (!sel) return;
        var selected = String(sel.getAttribute('data-selected') || '').trim();
        ifpTaxClearFilter(sel);
        sel.innerHTML = '';

        function finishScheduleNone() {
            ifpTaxFillSroListWithNone(sel);
            loadSroItemsForRow(row, '');
            updateTaxSroTriggerSummary(row);
        }

        if (!rateId) {
            finishScheduleNone();
            return;
        }
        if (ifpTaxUseListboxUi(sel)) {
            ifpTaxAppendHiddenPlaceholder(sel, 'Please select SRO schedule');
            ifpTaxSelectHiddenPlaceholder(sel);
        } else {
            var ph = document.createElement('option'); ph.value = ''; ph.textContent = 'Select SRO Schedule'; sel.appendChild(ph);
        }
        fetch(INV_SRO_SCHEDULES + '?rate_id=' + encodeURIComponent(rateId), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data || data.status !== 'success' || !Array.isArray(data.items)) {
                    finishScheduleNone();
                    return;
                }
                if (!data.items.length) {
                    finishScheduleNone();
                    return;
                }
                data.items.forEach(function(item) {
                    var opt = document.createElement('option');
                    opt.value = String(item.id != null ? item.id : '').trim();
                    opt.textContent = item.text;
                    if (selected && ifpTaxOptionValuesMatch(selected, item.id)) opt.selected = true;
                    sel.appendChild(opt);
                });
                if (ifpTaxUseListboxUi(sel)) {
                    ifpTaxDeselectPlaceholder(sel);
                    ifpTaxFlashListboxSelection(sel);
                }
                var v = String(sel.value || '').trim();
                if (!v && selected) {
                    var wantSch = String(selected).trim();
                    for (var si = 0; si < sel.options.length; si++) {
                        if (sel.options[si].getAttribute('data-ifp-placeholder') === '1') continue;
                        if (ifpTaxOptionValuesMatch(wantSch, sel.options[si].value)) {
                            sel.selectedIndex = si;
                            sel.value = String(sel.options[si].value);
                            if (ifpTaxUseListboxUi(sel)) {
                                ifpTaxDeselectPlaceholder(sel);
                                ifpTaxFlashListboxSelection(sel);
                            }
                            v = String(sel.value || '').trim();
                            break;
                        }
                    }
                }
                if (v) loadSroItemsForRow(row, v);
                else loadSroItemsForRow(row, '');
                updateTaxSroTriggerSummary(row);
            })
            .catch(function() { finishScheduleNone(); });
    }

    function loadSroItemsForRow(row, sroId) {
        var sel = rowEl(row, '.ifp-sro-item');
        if (!sel) return;
        var selected = String(sel.getAttribute('data-selected') || '').trim();
        ifpTaxClearFilter(sel);
        sel.innerHTML = '';

        if (!sroId) {
            ifpTaxFillSroListWithNone(sel);
            return;
        }
        if (ifpTaxUseListboxUi(sel)) {
            ifpTaxAppendHiddenPlaceholder(sel, 'Please select SRO item');
            ifpTaxSelectHiddenPlaceholder(sel);
        } else {
            var ph = document.createElement('option'); ph.value = ''; ph.textContent = 'Select SRO Item'; sel.appendChild(ph);
        }
        fetch(INV_SRO_ITEMS + '?sro_id=' + encodeURIComponent(sroId), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data || data.status !== 'success' || !Array.isArray(data.items)) {
                    ifpTaxFillSroListWithNone(sel);
                    return;
                }
                if (!data.items.length) {
                    ifpTaxFillSroListWithNone(sel);
                    return;
                }
                data.items.forEach(function(item) {
                    var opt = document.createElement('option');
                    opt.value = String(item.id != null ? item.id : '').trim();
                    opt.textContent = item.text;
                    if (selected && ifpTaxOptionValuesMatch(selected, item.id)) opt.selected = true;
                    sel.appendChild(opt);
                });
                if (ifpTaxUseListboxUi(sel)) {
                    ifpTaxDeselectPlaceholder(sel);
                    ifpTaxFlashListboxSelection(sel);
                }
                if (!String(sel.value || '').trim() && selected) {
                    var wantIt = String(selected).trim();
                    for (var ii = 0; ii < sel.options.length; ii++) {
                        if (sel.options[ii].getAttribute('data-ifp-placeholder') === '1') continue;
                        if (ifpTaxOptionValuesMatch(wantIt, sel.options[ii].value)) {
                            sel.selectedIndex = ii;
                            sel.value = String(sel.options[ii].value);
                            if (ifpTaxUseListboxUi(sel)) {
                                ifpTaxDeselectPlaceholder(sel);
                                ifpTaxFlashListboxSelection(sel);
                            }
                            break;
                        }
                    }
                }
            })
            .catch(function() { ifpTaxFillSroListWithNone(sel); });
    }

    function bindRowTaxDropdowns(row) {
        var hsInp = rowEl(row, '.ifp-hscode') || row.querySelector('.ifp-hscode');
        var uomSel = row.querySelector('.ifp-uom');
        var saleTypeSel = rowEl(row, '.ifp-sale-type');
        var saleTypeToRateSel = rowEl(row, '.ifp-sale-type-to-rate');
        var sroScheduleSel = rowEl(row, '.ifp-sro-schedule');
        var sroItemSel = rowEl(row, '.ifp-sro-item');

        if (hsInp && uomSel && hsInp.value && String(hsInp.value).trim() !== '') {
            fetchHsCodeInfoForRow(row);
        }

        if (saleTypeSel) {
            saleTypeSel.addEventListener('change', function() {
                var v = saleTypeSel.value || '';
                saleTypeSel.setAttribute('data-selected', v);
                var rtInp = rowEl(row, '.ifp-rate-type');
                if (saleTypeToRateSel) {
                    saleTypeToRateSel.setAttribute('data-selected', '');
                    ifpTaxClearFilter(saleTypeToRateSel);
                    saleTypeToRateSel.innerHTML = '';
                    if (ifpTaxUseListboxUi(saleTypeToRateSel)) {
                        ifpTaxAppendHiddenPlaceholder(saleTypeToRateSel, 'Please select sale type to rate');
                        ifpTaxSelectHiddenPlaceholder(saleTypeToRateSel);
                    } else {
                        var o = document.createElement('option'); o.value = ''; o.textContent = 'Select Sale Type first'; saleTypeToRateSel.appendChild(o);
                    }
                }
                if (rtInp) rtInp.value = '';
                updateTaxSroTriggerSummary(row);
                if (sroScheduleSel) {
                    sroScheduleSel.setAttribute('data-selected', '');
                    ifpTaxClearFilter(sroScheduleSel);
                    sroScheduleSel.innerHTML = '';
                    if (ifpTaxUseListboxUi(sroScheduleSel)) {
                        ifpTaxAppendHiddenPlaceholder(sroScheduleSel, 'Please select sale type to rate');
                        ifpTaxSelectHiddenPlaceholder(sroScheduleSel);
                    } else {
                        var o2 = document.createElement('option'); o2.value = ''; o2.textContent = 'Select Sale Type To Rate first'; sroScheduleSel.appendChild(o2);
                    }
                }
                if (sroItemSel) {
                    sroItemSel.setAttribute('data-selected', '');
                    ifpTaxClearFilter(sroItemSel);
                    sroItemSel.innerHTML = '';
                    if (ifpTaxUseListboxUi(sroItemSel)) {
                        ifpTaxAppendHiddenPlaceholder(sroItemSel, 'Please select SRO schedule');
                        ifpTaxSelectHiddenPlaceholder(sroItemSel);
                    } else {
                        var o3 = document.createElement('option'); o3.value = ''; o3.textContent = 'Select SRO Schedule first'; sroItemSel.appendChild(o3);
                    }
                }
                loadSaleTypeToRatesForRow(row, v);
            });
        }
        if (saleTypeToRateSel) {
            saleTypeToRateSel.addEventListener('change', function() {
                var v = saleTypeToRateSel.value || '';
                saleTypeToRateSel.setAttribute('data-selected', v);
                var gstEl = rowEl(row, '.ifp-gst-pct');
                var rtInp = rowEl(row, '.ifp-rate-type');
                if (v) {
                    var opt = saleTypeToRateSel.options[saleTypeToRateSel.selectedIndex];
                    if (opt && opt.getAttribute('data-rate-pct') && gstEl) {
                        gstEl.value = opt.getAttribute('data-rate-pct');
                    }
                    if (rtInp) rtInp.value = (opt && opt.getAttribute('data-rate-type')) ? opt.getAttribute('data-rate-type') : '';
                    calcRow(row);
                    calcSummary();
                    updateTaxSroTriggerSummary(row);
                } else {
                    if (gstEl) gstEl.value = '0';
                    if (rtInp) rtInp.value = '';
                    updateTaxSroTriggerSummary(row);
                }
                if (sroScheduleSel) {
                    sroScheduleSel.setAttribute('data-selected', '');
                    ifpTaxClearFilter(sroScheduleSel);
                    sroScheduleSel.innerHTML = '';
                    if (ifpTaxUseListboxUi(sroScheduleSel)) {
                        ifpTaxAppendHiddenPlaceholder(sroScheduleSel, 'Please select SRO schedule');
                        ifpTaxSelectHiddenPlaceholder(sroScheduleSel);
                    } else {
                        var o = document.createElement('option'); o.value = ''; o.textContent = 'Select Sale Type To Rate first'; sroScheduleSel.appendChild(o);
                    }
                }
                if (sroItemSel) {
                    sroItemSel.setAttribute('data-selected', '');
                    ifpTaxClearFilter(sroItemSel);
                    sroItemSel.innerHTML = '';
                    if (ifpTaxUseListboxUi(sroItemSel)) {
                        ifpTaxAppendHiddenPlaceholder(sroItemSel, 'Please select SRO schedule');
                        ifpTaxSelectHiddenPlaceholder(sroItemSel);
                    } else {
                        var o2 = document.createElement('option'); o2.value = ''; o2.textContent = 'Select SRO Schedule first'; sroItemSel.appendChild(o2);
                    }
                }
                loadSroSchedulesForRow(row, v);
            });
        }
        if (sroScheduleSel) {
            sroScheduleSel.addEventListener('change', function() {
                var v = sroScheduleSel.value || '';
                if (sroItemSel) sroItemSel.setAttribute('data-selected', '');
                if (sroItemSel) ifpTaxClearFilter(sroItemSel);
                loadSroItemsForRow(row, v);
            });
        }
        ifpTaxBindAllListboxSearches(row);
    }
    function bindRow(row) {
        // Recalculate summary on input change for any relevant field
        row.querySelectorAll('.ifp-qty, .ifp-rate, .ifp-disc-pct, .ifp-gst-pct, .ifp-extra-tax, .ifp-further-tax, .ifp-fed-payable').forEach(function(inp) {
            inp.addEventListener('input', function() {
                calcRow(row);
                calcSummary();
            });
        });

        // Product select → auto-fill rate, GST, and product fields (HsCode, Description, UOM, Sale Type cascade)
        const prodSel = row.querySelector('.ifp-product');
        const detailsTaInline = row.querySelector('textarea.ifp-details');
        if (detailsTaInline) {
            detailsTaInline.addEventListener('focus', function() {
                detailsTaInline.rows = 3;
                detailsTaInline.setAttribute('title', detailsTaInline.value || '');
            });
            detailsTaInline.addEventListener('input', function() {
                detailsTaInline.setAttribute('title', detailsTaInline.value || '');
            });
            detailsTaInline.addEventListener('blur', function() {
                detailsTaInline.rows = 1;
            });
        }
        if (prodSel) {
            const applySelectedProductToRow = function(forceDetailsFill) {
                const opt = (prodSel.selectedOptions && prodSel.selectedOptions.length)
                    ? prodSel.selectedOptions[0]
                    : prodSel.options[prodSel.selectedIndex];
                if (!opt) return;
                const rateEl = rowEl(row, '.ifp-rate');
                const gstEl  = rowEl(row, '.ifp-gst-pct');
                const ds = opt.dataset || {};
                if (forceDetailsFill) {
                    if (rateEl) rateEl.value = ds.rate || opt.getAttribute('data-rate') || 0;
                    if (gstEl)  gstEl.value  = ds.gst  || opt.getAttribute('data-gst')  || 0;
                }

                const hsCode = ds.hscode || opt.getAttribute('data-hscode') || '';
                const saleInfo = ds.saleInformation || opt.getAttribute('data-sale-information') || '';
                const uomId = ds.uomId || opt.getAttribute('data-uom-id') || '';
                const saleTypeId = ds.saleTypeId || opt.getAttribute('data-sale-type-id') || '';
                const saleTypeToRateId = ds.saleTypeToRateId || opt.getAttribute('data-sale-type-to-rate-id') || '';
                const sroScheduleId = ds.sroScheduleId || opt.getAttribute('data-sro-schedule-id') || '';
                const sroItemId = ds.sroItemId || opt.getAttribute('data-sro-item-id') || '';
                const tpRate = ds.tpRate || opt.getAttribute('data-tp-rate') || 0;
                const isFixedRaw = ds.isFixedRate != null && ds.isFixedRate !== ''
                    ? ds.isFixedRate
                    : (opt.getAttribute('data-is-fixed-rate') || '0');
                const isFixedVal = (parseInt(String(isFixedRaw), 10) || 0) ? 1 : 0;

                const hsInp = rowEl(row, '.ifp-hscode');
                const detailsTa = rowEl(row, 'textarea[name*="[Details]"]');
                const uomSel = rowEl(row, '.ifp-uom');
                const saleTypeSel = rowEl(row, '.ifp-sale-type');
                const saleTypeToRateSel = rowEl(row, '.ifp-sale-type-to-rate');
                const sroScheduleSel = rowEl(row, '.ifp-sro-schedule');
                const sroItemSel = rowEl(row, '.ifp-sro-item');
                const fixedRateEl = rowEl(row, '.ifp-fixed-rate');
                const isFixedHidden = rowEl(row, '.ifp-is-fixed-rate');
                const isFixedCb = rowEl(row, '.ifp-is-fixed-rate-cb');

                if (hsInp) hsInp.value = hsCode;
                if (detailsTa) {
                    const isBlank = String(detailsTa.value || '').trim() === '';
                    if (forceDetailsFill || isBlank) {
                        detailsTa.value = saleInfo;
                        // Trigger listeners bound by autosize/validators in some modal contexts.
                        detailsTa.dispatchEvent(new Event('input', { bubbles: true }));
                        detailsTa.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                }
                if (uomSel) {
                    if (forceDetailsFill || !uomSel.getAttribute('data-selected')) {
                        uomSel.setAttribute('data-selected', uomId);
                    }
                    fetchHsCodeInfoForRow(row);
                }
                if (saleTypeSel && (forceDetailsFill || !saleTypeSel.getAttribute('data-selected'))) saleTypeSel.setAttribute('data-selected', saleTypeId);
                if (saleTypeToRateSel && (forceDetailsFill || !saleTypeToRateSel.getAttribute('data-selected'))) saleTypeToRateSel.setAttribute('data-selected', saleTypeToRateId);
                if (sroScheduleSel && (forceDetailsFill || !sroScheduleSel.getAttribute('data-selected'))) sroScheduleSel.setAttribute('data-selected', sroScheduleId);
                if (sroItemSel && (forceDetailsFill || !sroItemSel.getAttribute('data-selected'))) sroItemSel.setAttribute('data-selected', sroItemId);
                if (fixedRateEl) fixedRateEl.value = n(tpRate).toFixed(2);
                if (isFixedHidden) isFixedHidden.value = String(isFixedVal);
                if (isFixedCb) isFixedCb.checked = !!isFixedVal;
                if (forceDetailsFill) {
                    loadSaleTypesForRow(row);
                }

                calcRow(row);
                calcSummary();
            };

            prodSel.addEventListener('change', function() {
                applySelectedProductToRow(true);
            });

            // On Edit modal open, pre-fill row using current selected product (without overriding typed description).
            if (prodSel.value) {
                applySelectedProductToRow(false);
            }
        }

        applySupplierMetaToRow(row);

        // Delete row
        const delBtn = row.querySelector('.ifp-del');
        if (delBtn) {
            delBtn.addEventListener('click', function() {
                if (body.querySelectorAll('.ifp-row').length <= 1) {
                    alert('At least one line item is required.');
                    return;
                }
                row.remove();
                // reIndex(); // Optional if you handle indices on server-side differently, but good practice
                calcSummary();
            });
        }

    }
    function initAddRowButton() {
        const addBtn = document.getElementById(uid + '_addRow');
        if (!addBtn || addBtn.dataset.pfpTaxBound === '1') return;
        addBtn.dataset.pfpTaxBound = '1';
        addBtn.addEventListener('click', function() {
            const firstRow = body.querySelector('.ifp-row');
            if (!firstRow) return;
            const idx = body.querySelectorAll('.ifp-row').length;
            const clone = firstRow.cloneNode(true);
            clone.querySelectorAll('[name]').forEach(function(el) {
                el.name = el.name.replace(/items\[\d+\]/, 'items[' + idx + ']');
                if (el.tagName === 'SELECT') {
                    el.selectedIndex = 0;
                } else if (el.type === 'checkbox') {
                    el.checked = false;
                } else if (!el.readOnly && el.type !== 'hidden') {
                    el.value = el.classList.contains('ifp-qty') ? '1.00' : '0';
                }
            });
            clone.querySelectorAll('[id]').forEach(function(el) {
                if (el.id) el.id = el.id.replace(/_(\d+)$/, '_' + idx);
            });
            body.appendChild(clone);
            bindRow(clone);
            bindRowTaxDropdowns(clone);
            loadSaleTypesForRow(clone);
            applySupplierMetaToRow(clone);
            calcSummary();
        });
    }

    taxCalc.bindPartyChange();
    body.querySelectorAll('.ifp-row').forEach(function(row) {
      bindRow(row);
      bindRowTaxDropdowns(row);
      loadSaleTypesForRow(row);
    });
    initAddRowButton();
    calcSummary();
    return { calcRow, calcSummary, bindRow, bindRowTaxDropdowns, loadSaleTypesForRow, applySupplierMetaToRow };
  }
  global.IfpPurchaseTaxForm = { init: initPurchaseTaxForm };
})(typeof window !== 'undefined' ? window : globalThis);

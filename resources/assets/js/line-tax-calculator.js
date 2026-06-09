/**
 * Shared line-item tax calculator for Invoice and Purchase forms.
 */
(function (global) {
    'use strict';

    function n(v) {
        return parseFloat(v) || 0;
    }

    function createLineTaxCalculator(config) {
        const uid = config.uid || '';
        const body = config.body;
        const partySelectId = config.partySelectId || (uid + '_CustomerID');
        const whtLabelPctId = config.whtLabelPctId || (uid + '_wht_label_pct');
        const whtAmountId = config.whtAmountId || (uid + '_CustomerWht236g');
        const grandWithWhtId = config.grandWithWhtId || (uid + '_GrandTotalWithWht');
        const trWhtId = config.trWhtId || (uid + '_tr_customer_wht');
        const trGrandId = config.trGrandId || (uid + '_tr_grand_with_wht');
        const rowEl = typeof config.rowEl === 'function'
            ? config.rowEl
            : function (row, selector) {
                return row ? row.querySelector(selector) : null;
            };

        function partyAdvanceMeta() {
            const partySel = document.getElementById(partySelectId);
            if (!partySel || partySel.selectedIndex < 0) {
                return { on: false, rate: 0 };
            }
            const opt = partySel.options[partySel.selectedIndex];
            if (!opt || !opt.value) {
                return { on: false, rate: 0 };
            }
            const advOn = (opt.getAttribute('data-is-advance-tax') || '0') === '1';
            const rate = parseFloat(opt.getAttribute('data-advance-tax-rate') || '0') || 0;
            return { on: advOn && rate > 0.000001, rate: rate };
        }

        function syncWhtSummary(grandIncTaxes) {
            const meta = partyAdvanceMeta();
            const wht = meta.on ? Math.round(grandIncTaxes * (meta.rate / 100) * 100) / 100 : 0;
            const grandWithWht = Math.round((grandIncTaxes + wht) * 100) / 100;
            const pctEl = document.getElementById(whtLabelPctId);
            if (pctEl) {
                pctEl.textContent = meta.rate.toFixed(2);
            }
            const whtEl = document.getElementById(whtAmountId);
            if (whtEl) {
                whtEl.value = wht.toFixed(2);
            }
            const gtwEl = document.getElementById(grandWithWhtId);
            if (gtwEl) {
                gtwEl.value = grandWithWht.toFixed(2);
            }
            const trWht = document.getElementById(trWhtId);
            const trGrand = document.getElementById(trGrandId);
            if (trWht && trGrand) {
                if (meta.on) {
                    trWht.classList.remove('d-none');
                    trGrand.classList.remove('d-none');
                } else {
                    trWht.classList.add('d-none');
                    trGrand.classList.add('d-none');
                }
            }
        }

        function calcRow(row) {
            const qty = n(rowEl(row, '.ifp-qty')?.value);
            const rate = n(rowEl(row, '.ifp-rate')?.value);
            const discPct = n(rowEl(row, '.ifp-disc-pct')?.value);
            const gstPct = n(rowEl(row, '.ifp-gst-pct')?.value);
            let extraTax = n(rowEl(row, '.ifp-extra-tax')?.value);
            let furtherTax = n(rowEl(row, '.ifp-further-tax')?.value);
            let fedPayable = n(rowEl(row, '.ifp-fed-payable')?.value);
            const fixedRateEl = rowEl(row, '.ifp-fixed-rate');
            const fixedNotifiedEl = rowEl(row, '.ifp-fixed-notified-value');
            const fixedRate = n(fixedRateEl?.value);
            const isFixedHidden = rowEl(row, '.ifp-is-fixed-rate');
            const isFixed = (parseInt(isFixedHidden ? String(isFixedHidden.value) : '0', 10) || 0) === 1;
            const extraTaxPct = n(rowEl(row, '.ifp-extra-tax-pct')?.value);
            const furtherTaxPct = n(rowEl(row, '.ifp-further-tax-pct')?.value);
            const fedPayablePct = n(rowEl(row, '.ifp-fed-payable-pct')?.value);
            const rateTypeRaw = (rowEl(row, '.ifp-rate-type')?.value || '');
            const rateType = String(rateTypeRaw).trim().toUpperCase();
            const saleTypeToRateSel = rowEl(row, '.ifp-sale-type-to-rate');
            const selectedRateOpt = saleTypeToRateSel && saleTypeToRateSel.selectedIndex >= 0
                ? saleTypeToRateSel.options[saleTypeToRateSel.selectedIndex]
                : null;
            const rateValue = n(selectedRateOpt && selectedRateOpt.getAttribute('data-rate-value'));

            const amount = qty * rate;
            const discAmt = amount * discPct / 100;
            const taxable = amount - discAmt;
            const fixedNotified = fixedRate * qty;
            extraTax = amount * extraTaxPct / 100;
            furtherTax = amount * furtherTaxPct / 100;
            fedPayable = amount * fedPayablePct / 100;
            let gstAmt;
            if (rateType === 'Z') {
                gstAmt = 0;
                extraTax = 0;
                furtherTax = 0;
                fedPayable = 0;
                const extraTaxEl = rowEl(row, '.ifp-extra-tax');
                const furtherTaxEl = rowEl(row, '.ifp-further-tax');
                const fedPayableEl = rowEl(row, '.ifp-fed-payable');
                if (extraTaxEl) extraTaxEl.value = '0.00';
                if (furtherTaxEl) furtherTaxEl.value = '0.00';
                if (fedPayableEl) fedPayableEl.value = '0.00';
            } else if (rateType === 'R') {
                gstAmt = rateValue * qty;
            } else if (rateType === 'H') {
                const gstBase = isFixed ? fixedNotified : taxable;
                gstAmt = (60 * qty) + (gstBase * gstPct / 100);
            } else {
                const gstBase = isFixed ? fixedNotified : taxable;
                gstAmt = gstBase * gstPct / 100;
            }
            const total = taxable + gstAmt + extraTax + furtherTax + fedPayable;

            function set(cls, val) {
                const el = rowEl(row, '.' + cls);
                if (el) el.value = val.toFixed(2);
            }
            set('ifp-amount', amount);
            set('ifp-disc-amt', discAmt);
            set('ifp-extra-tax', extraTax);
            set('ifp-further-tax', furtherTax);
            set('ifp-fed-payable', fedPayable);
            set('ifp-gst-amt', gstAmt);
            set('ifp-total', total);
            if (fixedNotifiedEl) fixedNotifiedEl.value = fixedNotified.toFixed(2);

            return { amount, discAmt, gstAmt, total };
        }

        function calcSummary() {
            if (!body) return;
            let sub = 0;
            let disc = 0;
            let gst = 0;
            let grand = 0;
            body.querySelectorAll('.ifp-row').forEach(function (row) {
                const r = calcRow(row);
                sub += r.amount;
                disc += r.discAmt;
                gst += r.gstAmt;
                grand += r.total;
            });
            function s(id, v) {
                const el = document.getElementById(uid + '_' + id);
                if (el) el.value = v.toFixed(2);
            }
            s('SubTotal', sub);
            s('TotalDiscount', disc);
            s('TotalGST', gst);
            s('GrandTotal', grand);
            syncWhtSummary(grand);
        }

        function getPartyMeta() {
            const partySel = document.getElementById(partySelectId);
            if (!partySel || partySel.selectedIndex < 0) {
                return { isFiler: 0, salesTaxWithheldAtSource: 0, extraTaxPct: 0, furtherTaxPct: 0, fedPayablePct: 0 };
            }
            const opt = partySel.options[partySel.selectedIndex];
            const ds = opt ? (opt.dataset || {}) : {};
            const isFilerRaw = ds.isFiler != null && ds.isFiler !== '' ? ds.isFiler : (opt?.getAttribute('data-is-filer') || '0');
            const withheldRaw = ds.salesTaxWithheld != null && ds.salesTaxWithheld !== '' ? ds.salesTaxWithheld : (opt?.getAttribute('data-sales-tax-withheld') || '0');
            const extraTaxPctRaw = ds.extraTaxPct != null && ds.extraTaxPct !== '' ? ds.extraTaxPct : (opt?.getAttribute('data-extra-tax-pct') || '0');
            const furtherTaxPctRaw = ds.furtherTaxPct != null && ds.furtherTaxPct !== '' ? ds.furtherTaxPct : (opt?.getAttribute('data-further-tax-pct') || '0');
            const fedPayablePctRaw = ds.fedPayablePct != null && ds.fedPayablePct !== '' ? ds.fedPayablePct : (opt?.getAttribute('data-fed-payable-pct') || '0');
            return {
                isFiler: (parseInt(String(isFilerRaw), 10) || 0) ? 1 : 0,
                salesTaxWithheldAtSource: n(withheldRaw),
                extraTaxPct: n(extraTaxPctRaw),
                furtherTaxPct: n(furtherTaxPctRaw),
                fedPayablePct: n(fedPayablePctRaw),
            };
        }

        function applyPartyMetaToRow(row) {
            const meta = getPartyMeta();
            const isFilerHidden = rowEl(row, '.ifp-is-filer');
            const isFilerCb = rowEl(row, '.ifp-is-filer-cb');
            const withheldInput = rowEl(row, '.ifp-sales-tax-withheld');
            const extraTaxPctInput = rowEl(row, '.ifp-extra-tax-pct');
            const furtherTaxPctInput = rowEl(row, '.ifp-further-tax-pct');
            const fedPayablePctInput = rowEl(row, '.ifp-fed-payable-pct');
            if (isFilerHidden) isFilerHidden.value = String(meta.isFiler);
            if (isFilerCb) isFilerCb.checked = !!meta.isFiler;
            if (withheldInput) withheldInput.value = meta.salesTaxWithheldAtSource.toFixed(2);
            if (extraTaxPctInput) extraTaxPctInput.value = meta.extraTaxPct.toFixed(2);
            if (furtherTaxPctInput) furtherTaxPctInput.value = meta.furtherTaxPct.toFixed(2);
            if (fedPayablePctInput) fedPayablePctInput.value = meta.fedPayablePct.toFixed(2);
        }

        function bindPartyChange() {
            const partySel = document.getElementById(partySelectId);
            if (!partySel || !body) return;
            partySel.addEventListener('change', function () {
                body.querySelectorAll('.ifp-row').forEach(function (row) {
                    applyPartyMetaToRow(row);
                    calcRow(row);
                });
                calcSummary();
            });
        }

        return {
            n,
            calcRow,
            calcSummary,
            getPartyMeta,
            applyPartyMetaToRow,
            bindPartyChange,
            syncWhtSummary,
        };
    }

    global.IfpLineTaxCalculator = {
        create: createLineTaxCalculator,
        n,
    };
})(typeof window !== 'undefined' ? window : globalThis);

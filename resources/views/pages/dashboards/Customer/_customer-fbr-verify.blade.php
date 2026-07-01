{{--
    FBR verification strip (Option A) — NTN registration type + ATL (statl).
    Requires: $fbrPrefix, $isReadonly, $customer (optional)
--}}
@php
    $fbrPrefix = $fbrPrefix ?? 'cf_fbr';
    $registerType = old('RegisterType', $customer->RegisterType ?? '');
    $filerStatus = old('FilerStatus', $customer->FilerStatus ?? '');
    $filerStatusDate = old('FilerStatusDate', $customer->FilerStatusDate ?? '');
    if ($filerStatusDate && !is_string($filerStatusDate)) {
        try {
            $filerStatusDate = \Carbon\Carbon::parse($filerStatusDate)->toDateString();
        } catch (\Throwable $e) {
            $filerStatusDate = '';
        }
    }
    $defaultVerifyDate = $filerStatusDate ?: now()->toDateString();

    $regBadgeClass = match ($registerType) {
        'Registered' => 'status-badge status-active',
        'Un-Registered' => 'status-badge status-inactive',
        default => 'badge bg-secondary',
    };
    $atlBadgeClass = match ($filerStatus) {
        'Active' => 'status-badge status-active',
        'In-Active' => 'status-badge status-inactive',
        default => 'badge bg-secondary',
    };
    $hasFbrData = $registerType !== '' || $filerStatus !== '';
@endphp

@once
<style>
    ._cfp-fbr-strip {
        font-size: 12px;
        line-height: 1.35;
        background: var(--cm-bg-body, #f8f9fa);
        border: 1px solid var(--cm-border-color, #dee2e6);
        border-radius: 6px;
        padding: 6px 10px;
        margin-top: 2px;
        margin-bottom: 4px;
    }
    ._cfp-fbr-strip * {
        font-size: inherit;
    }
    ._cfp-fbr-row {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 6px 12px;
        row-gap: 4px;
    }
    ._cfp-fbr-strip-title {
        font-weight: 600;
        color: var(--cm-text-main, #212529);
        margin: 0;
        white-space: nowrap;
        flex-shrink: 0;
    }
    ._cfp-fbr-strip-title i {
        font-size: 11px;
    }
    ._cfp-fbr-badge-item {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        white-space: nowrap;
        flex-shrink: 0;
    }
    ._cfp-fbr-badge-item .status-badge,
    ._cfp-fbr-badge-item .badge {
        vertical-align: baseline;
    }
    ._cfp-fbr-meta {
        color: #6c757d;
        white-space: nowrap;
        flex-shrink: 0;
    }
    ._cfp-fbr-meta-hint {
        color: #6c757d;
        flex: 1 1 120px;
        min-width: 0;
    }
    ._cfp-fbr-actions {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-left: auto;
        flex-shrink: 0;
    }
    ._cfp-fbr-actions .form-control-sm {
        font-size: 12px;
        padding: 0.15rem 0.35rem;
        min-height: 26px;
        line-height: 1.3;
    }
    ._cfp-fbr-actions .btn-sm {
        font-size: 12px;
        padding: 0.15rem 0.5rem;
        line-height: 1.3;
        min-height: 26px;
    }
    ._cfp-fbr-alerts {
        margin-top: 4px;
    }
    ._cfp-fbr-alert {
        margin: 0;
        padding: 4px 8px;
        font-size: 12px;
        line-height: 1.35;
    }
</style>
@endonce

@if(!$isReadonly)
    <input type="hidden" name="RegisterType" id="{{ $fbrPrefix }}_RegisterType" value="{{ $registerType }}">
    <input type="hidden" name="FilerStatus" id="{{ $fbrPrefix }}_FilerStatus" value="{{ $filerStatus }}">
    <input type="hidden" name="FilerStatusDate" id="{{ $fbrPrefix }}_FilerStatusDate" value="{{ $filerStatusDate }}">
@endif

<div class="col-12">
    <div class="_cfp-fbr-strip" id="{{ $fbrPrefix }}_fbr_strip">
        <div class="_cfp-fbr-row">
            <p class="_cfp-fbr-strip-title mb-0">
                <i class="fas fa-shield-alt text-primary"></i> FBR verification
            </p>

            <div class="_cfp-fbr-badge-item">
                <span class="text-muted">Registration:</span>
                @if($isReadonly)
                    @if($registerType)
                        <span class="{{ $regBadgeClass }}">{{ $registerType }}</span>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                @else
                    <span class="{{ $regBadgeClass }}" id="{{ $fbrPrefix }}_reg_badge">{{ $registerType ?: 'Not verified' }}</span>
                @endif
            </div>

            <div class="_cfp-fbr-badge-item">
                <span class="text-muted">ATL:</span>
                @if($isReadonly)
                    @if($filerStatus)
                        <span class="{{ $atlBadgeClass }}">{{ $filerStatus }}</span>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                @else
                    <span class="{{ $atlBadgeClass }}" id="{{ $fbrPrefix }}_atl_badge">{{ $filerStatus ?: 'Not verified' }}</span>
                @endif
            </div>

            @if($isReadonly)
                @if($filerStatusDate)
                    <span class="_cfp-fbr-meta"><i class="fas fa-calendar-alt"></i> {{ $filerStatusDate }} · FBR</span>
                @elseif(!$hasFbrData)
                    <span class="_cfp-fbr-meta">No verification on file</span>
                @endif
            @else
                <span class="_cfp-fbr-meta {{ $filerStatusDate ? '' : 'd-none' }}" id="{{ $fbrPrefix }}_fbr_as_of_wrap">
                    <i class="fas fa-calendar-alt"></i>
                    <span id="{{ $fbrPrefix }}_fbr_as_of">{{ $filerStatusDate }}</span>
                    · FBR
                </span>
                <span class="_cfp-fbr-meta-hint {{ $filerStatusDate ? 'd-none' : '' }}" id="{{ $fbrPrefix }}_fbr_meta_hint">Enter NTN, then Verify</span>
            @endif

            @if(!$isReadonly)
                <div class="_cfp-fbr-actions">
                    <label class="text-muted mb-0" for="{{ $fbrPrefix }}_fbr_verify_date">As of</label>
                    <input type="date" class="form-control form-control-sm" style="width:9.5rem;"
                           id="{{ $fbrPrefix }}_fbr_verify_date" value="{{ $defaultVerifyDate }}">
                    <button type="button" class="btn btn-sm btn-primary" id="{{ $fbrPrefix }}_fbr_verify_btn">
                        <i class="fas fa-search"></i> Verify
                    </button>
                </div>
            @endif
        </div>

        @if(!$isReadonly)
            <div class="_cfp-fbr-alerts">
                <div class="alert alert-warning _cfp-fbr-alert d-none" id="{{ $fbrPrefix }}_fbr_stale" role="alert">
                    <i class="fas fa-exclamation-triangle"></i> NTN changed — verify again before saving.
                </div>
                <div class="alert alert-danger _cfp-fbr-alert d-none" id="{{ $fbrPrefix }}_fbr_error" role="alert"></div>
            </div>
        @endif
    </div>
</div>

@once
<script>
window.initCustomerFbrVerify = function (prefix, verifyUrl, ntnInputId, isFilerCheckboxId) {
    const strip = document.getElementById(prefix + '_fbr_strip');
    if (!strip) return;

    const ntnEl = document.getElementById(ntnInputId) || document.querySelector('form [name="NTN"]');
    const btn = document.getElementById(prefix + '_fbr_verify_btn');
    const dateEl = document.getElementById(prefix + '_fbr_verify_date');
    const regBadge = document.getElementById(prefix + '_reg_badge');
    const atlBadge = document.getElementById(prefix + '_atl_badge');
    const hidReg = document.getElementById(prefix + '_RegisterType');
    const hidAtl = document.getElementById(prefix + '_FilerStatus');
    const hidDate = document.getElementById(prefix + '_FilerStatusDate');
    const staleEl = document.getElementById(prefix + '_fbr_stale');
    const errEl = document.getElementById(prefix + '_fbr_error');
    const metaHint = document.getElementById(prefix + '_fbr_meta_hint');
    const asOfWrap = document.getElementById(prefix + '_fbr_as_of_wrap');
    const asOfEl = document.getElementById(prefix + '_fbr_as_of');
    const isFilerCb = isFilerCheckboxId ? document.getElementById(isFilerCheckboxId) : document.querySelector('[name="IsFiler"][type="checkbox"]');

    if (!ntnEl || !btn) return;

    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    let verifiedNtn = (ntnEl.value || '').replace(/\s+/g, '');

    function badgeClass(type, value) {
        if (type === 'reg') {
            if (value === 'Registered') return 'status-badge status-active';
            if (value === 'Un-Registered') return 'status-badge status-inactive';
            return 'badge bg-secondary';
        }
        if (value === 'Active') return 'status-badge status-active';
        if (value === 'In-Active') return 'status-badge status-inactive';
        return 'badge bg-secondary';
    }

    function setBadge(el, text, cls) {
        if (!el) return;
        el.textContent = text;
        el.className = cls;
    }

    function showError(msg) {
        if (!errEl) return;
        if (msg) {
            errEl.textContent = msg;
            errEl.classList.remove('d-none');
        } else {
            errEl.textContent = '';
            errEl.classList.add('d-none');
        }
    }

    function markStale() {
        const current = (ntnEl.value || '').replace(/\s+/g, '');
        if (verifiedNtn && current !== verifiedNtn) {
            staleEl?.classList.remove('d-none');
        } else {
            staleEl?.classList.add('d-none');
        }
    }

    ntnEl.addEventListener('input', markStale);
    ntnEl.addEventListener('change', markStale);

    async function runVerify() {
        const ntn = (ntnEl.value || '').trim();
        if (!ntn) {
            showError('Please enter NTN before verifying with FBR.');
            ntnEl.focus();
            return;
        }

        showError('');
        const origHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Verifying…';

        try {
            const res = await fetch(verifyUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    ntn: ntn,
                    filer_status_date: dateEl ? dateEl.value : null,
                }),
            });
            const data = await res.json().catch(() => ({}));

            if (!res.ok || data.status === 'error') {
                showError(data.message || 'FBR verification failed.');
                return;
            }

            const reg = data.register_type || '';
            const atl = data.filer_status || '';
            const asOf = data.filer_status_date || '';

            if (hidReg) hidReg.value = reg;
            if (hidAtl) hidAtl.value = atl;
            if (hidDate) hidDate.value = asOf;

            setBadge(regBadge, reg || 'Unknown', badgeClass('reg', reg));
            setBadge(atlBadge, atl || 'Unknown', badgeClass('atl', atl));

            if (metaHint) metaHint.classList.add('d-none');
            if (asOfWrap) asOfWrap.classList.remove('d-none');
            if (asOfEl) asOfEl.textContent = asOf;

            verifiedNtn = ntn.replace(/\s+/g, '');
            staleEl?.classList.add('d-none');

            if (isFilerCb && data.is_filer !== null && data.is_filer !== undefined) {
                isFilerCb.checked = !!data.is_filer;
                isFilerCb.dispatchEvent(new Event('change', { bubbles: true }));
            }
        } catch (e) {
            showError('Could not reach the server. Please try again.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = origHtml;
        }
    }

    btn.addEventListener('click', runVerify);
};
</script>
@endonce

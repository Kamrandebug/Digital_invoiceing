{{-- Global Business Info modal: open with openBusinessModal('edit', 1) from any page --}}
<div class="modal fade" id="business_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-semibold" id="business_modal_title">Update Business</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" id="business_modal_body">
                {{-- Content loaded via AJAX --}}
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    window.openBusinessModal = function (mode, id) {
        var url = "{{ route('business.modal') }}";
        if (id) {
            url = "{{ route('business.modal.id', ':id') }}".replace(':id', id);
        }
        url += (url.indexOf('?') === -1 ? '?' : '&') + 'mode=' + encodeURIComponent(mode);

        var modalEl = document.getElementById('business_modal');
        var bodyEl = document.getElementById('business_modal_body');
        var titleEl = document.getElementById('business_modal_title');
        if (!modalEl || !bodyEl) return;

        bodyEl.innerHTML = '<div class="p-4 text-center">Loading...</div>';
        titleEl.textContent = (mode === 'edit' ? 'Update Business' : 'Business Info');

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (res) { return res.text(); })
            .then(function (html) {
                bodyEl.innerHTML = html;
                titleEl.textContent = (mode === 'edit' ? 'Update Business' : 'Business Info');
                if (mode !== 'show') attachBusinessFormSubmit(bodyEl);
            })
            .catch(function () {
                bodyEl.innerHTML = '<div class="p-4 text-danger text-center">Failed to load form.</div>';
            });

        var modal = new bootstrap.Modal(modalEl);
        modal.show();
    };

    function attachBusinessFormSubmit(bodyEl) {
        var form = bodyEl.querySelector('form');
        if (!form) return;
        form.removeEventListener('submit', window._businessFormSubmitHandler);
        window._businessFormSubmitHandler = function (e) {
            e.preventDefault();
            var existingAlert = bodyEl.querySelector('.business-form-ajax-alert');
            if (existingAlert) existingAlert.remove();
            var fd = new FormData(form);
            fetch(form.action, {
                method: 'POST',
                body: fd,
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            })
            .then(function (r) {
                if (r.ok) return r.json();
                if (r.status === 422) return r.json().then(function (d) { throw { type: 'validation', data: d }; });
                throw new Error(r.statusText || 'Request failed');
            })
            .then(function (data) {
                if (data.status === 'success') {
                    var msg = document.createElement('div');
                    msg.className = 'alert alert-success alert-dismissible fade show business-form-ajax-alert mx-3 mt-3';
                    msg.innerHTML = '<i class="fas fa-check-circle me-2"></i>' + (data.message || 'Business info updated successfully!') + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                    bodyEl.insertBefore(msg, bodyEl.firstChild);
                    setTimeout(function () {
                        var m = document.getElementById('business_modal');
                        if (m) { var inst = bootstrap.Modal.getInstance(m); if (inst) inst.hide(); }
                    }, 1800);
                }
            })
            .catch(function (err) {
                var msg = document.createElement('div');
                msg.className = 'alert alert-danger alert-dismissible fade show business-form-ajax-alert mx-3 mt-3';
                if (err && err.type === 'validation' && err.data) {
                    var txt = (err.data.message || 'Validation failed.') + (err.data.errors ? ' ' + Object.values(err.data.errors).flat().join(' ') : '');
                    msg.innerHTML = txt + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                } else {
                    msg.textContent = (err && err.message) ? err.message : 'Something went wrong.';
                    msg.innerHTML = msg.textContent + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                }
                bodyEl.insertBefore(msg, bodyEl.firstChild);
            });
        };
        form.addEventListener('submit', window._businessFormSubmitHandler);
    }
})();
</script>

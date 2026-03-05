{{--
    Business Info – opens Update Business modal for record id = 1
--}}

@extends('layouts.master')

@section('content')
<div class="page-header-breadcrumb mb-3">
    <div class="d-flex align-center justify-content-between flex-wrap">
        <h1 class="page-title fw-medium fs-14 mt-2 pt-1 mb-0">Business Info Management</h1>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle"></i> {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if($errors->any())
<div class="alert alert-warning alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-triangle"></i>
    @foreach ($errors->all() as $error)
        {{ $error }}
    @endforeach
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

{{-- Modal (content loaded via AJAX for id = 1) --}}
<div class="modal fade" id="business_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-semibold" id="business_modal_title">Update Business</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0" id="business_modal_body">
                {{-- AJAX content --}}
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function openBusinessModal(mode, id) {
    var url = "{{ route('business.modal') }}";
    if (id) {
        url = "{{ route('business.modal.id', ':id') }}".replace(':id', id);
    }
    url += (url.indexOf('?') === -1 ? '?' : '&') + 'mode=' + encodeURIComponent(mode);

    var modal = new bootstrap.Modal(document.getElementById('business_modal'));
    document.getElementById('business_modal_body').innerHTML = '<div class="p-4 text-center">Loading...</div>';

    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (res) { return res.text(); })
        .then(function (html) {
            document.getElementById('business_modal_body').innerHTML = html;
            document.getElementById('business_modal_title').innerText =
                mode === 'edit' ? 'Update Business' : 'Business Info';
        })
        .catch(function () {
            document.getElementById('business_modal_body').innerHTML =
                '<div class="p-4 text-danger text-center">Failed to load form.</div>';
        });

    modal.show();
}

// Open Update Business modal for record id = 1 on page load
document.addEventListener('DOMContentLoaded', function () {
    openBusinessModal('edit', 1);
});
</script>
@endsection

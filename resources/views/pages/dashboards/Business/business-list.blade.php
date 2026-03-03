{{--
    Business Info List
--}}

@extends('layouts.master')

@section('content')
<div class="page-header-breadcrumb mb-3">
    <div class="d-flex align-center justify-content-between flex-wrap">
        <h1 class="page-title fw-medium fs-14 mt-2 pt-1 mb-0">Business Info Management</h1>
        <div class="d-flex float-end">
            <button type="button"
                    class="btn btn-sm btn-primary btn-wave waves-light"
                    onclick="openBusinessModal('create')">
                <i class="ri-add-line fw-medium align-middle me-1"></i> Add Business
            </button>
        </div>
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

<div class="row">
    <div class="col-xs-12">
        <div class="card custom-card mb-1">
            <div class="card-body p-2">
                <div class="row">
                    <div class="col-md-9">
                        <form action="{{ route('business.index') }}" method="GET">
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <div class="header-search d-md-block d-none">
                                        <label>Search Business</label>
                                        <input type="text"
                                               class="header-search-bar form-control form-control-sm bg-white"
                                               name="search"
                                               value="{{ request('search') }}"
                                               placeholder="Search by name, email, phone...">
                                        <a href="javascript:void(0);" class="header-search-icon border-0" style="top: 25px;">
                                            <i class="bi bi-search fs-12"></i>
                                        </a>
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <div class="row g-2">
                                        <div class="col-md-4">
                                            <label>Start Date</label>
                                            <input type="date" class="form-control form-control-sm"
                                                   name="start_date" value="{{ request('start_date') }}">
                                        </div>
                                        <div class="col-md-4">
                                            <label>End Date</label>
                                            <input type="date" class="form-control form-control-sm"
                                                   name="end_date" value="{{ request('end_date') }}">
                                        </div>
                                        <div class="col">
                                            <button class="btn btn-sm btn-primary btn-margin" type="submit">
                                                <i class="fas fa-search"></i> Search
                                            </button>
                                            <a href="{{ route('business.index') }}" class="btn btn-sm btn-light btn-margin">
                                                <i class="fas fa-times"></i> Clear Filters
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="col-xs-12">
    <div class="card custom-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table text-nowrap" id="businessTable">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Account No</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Contact</th>
                        <th>Status</th>
                        <th>Created On</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($businesses as $index => $biz)
                        <tr>
                            <td>{{ $businesses->firstItem() + $index }}</td>
                            <td><strong>{{ $biz->AccountNo ?? '-' }}</strong></td>
                            <td>
                                <div>{{ $biz->BusinessName ?? trim(($biz->FirstName ?? '') . ' ' . ($biz->LastName ?? '')) }}</div>
                            </td>
                            <td>{{ $biz->Email ?? '-' }}</td>
                            <td>{{ $biz->Mobile ?? $biz->Phone ?? '-' }}</td>
                            <td>
                                @if(($biz->Status ?? 1) == 1)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-danger">Inactive</span>
                                @endif
                            </td>
                            <td style="font-size:13px;">
                                {{ isset($biz->CreatedOn) ? \Carbon\Carbon::parse($biz->CreatedOn)->format('d M Y') : '-' }}
                            </td>
                            <td>
                                <button type="button" class="action-btn btn-info" title="View"
                                        onclick="openBusinessModal('show', {{ $biz->ID }})">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button type="button" class="action-btn btn-warning" title="Edit"
                                        onclick="openBusinessModal('edit', {{ $biz->ID }})">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form action="{{ route('business.delete_transaction', $biz->ID) }}"
                                      method="POST" style="display:inline;"
                                      onsubmit="return confirm('Archive this business record?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="action-btn btn-delete" title="Archive">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <i class="fas fa-briefcase fa-3x text-muted mb-3 d-block"></i>
                                <p class="text-muted mb-2">No business info records found.</p>
                                @if(!request('search') && !request('start_date') && !request('end_date'))
                                    <button type="button" class="btn btn-primary" onclick="openBusinessModal('create')">
                                        <i class="fas fa-plus"></i> Add First Business
                                    </button>
                                @else
                                    <p class="text-muted small">Try adjusting your search or filters.</p>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer border-top-0">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 px-2">
                <span class="text-muted fs-12">
                    Showing {{ $businesses->firstItem() ?? 0 }}
                    to {{ $businesses->lastItem() ?? 0 }}
                    of {{ $businesses->total() }} entries
                </span>
                <nav class="pagination-style-2">
                    {{ $businesses->withQueryString()->links('pagination::bootstrap-5') }}
                </nav>
            </div>
        </div>
    </div>
</div>

{{-- Modal --}}
<div class="modal fade" id="business_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-semibold" id="business_modal_title">Business Info</h6>
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
                mode === 'create' ? 'Add Business' : (mode === 'edit' ? 'Edit Business' : 'Business Info');
        })
        .catch(function () {
            document.getElementById('business_modal_body').innerHTML =
                '<div class="p-4 text-danger text-center">Failed to load form.</div>';
        });

    modal.show();
}
</script>
@endsection


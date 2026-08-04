@extends('layouts.master')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/customer-management.css') }}">
@endsection

@section('content')

<div class="page-header-breadcrumb mb-3">
    <div class="d-flex align-center justify-content-between flex-wrap">
        <h1 class="page-title fw-medium fs-14 mt-2 pt-1 mb-0">Tenant Users</h1>
        <div class="d-flex float-end">
            <button type="button" class="btn btn-sm btn-primary btn-wave waves-light" onclick="openUserModal('create')">
                <i class="ri-add-line fw-medium align-middle me-1"></i> Add User
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

<div class="row">
    <div class="col-xs-12">
        <div class="card custom-card mb-3">
            <div class="card-body p-2">
                <form method="GET" action="{{ route('users.index') }}">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <input type="text"
                                   class="form-control form-control-sm"
                                   name="search"
                                   value="{{ request('search') }}"
                                   placeholder="Search users by name or email...">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="fas fa-search"></i> Search
                            </button>
                            <a href="{{ route('users.index') }}" class="btn btn-sm btn-light">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="col-xs-12">
    <div class="card custom-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table text-nowrap listing-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Email Address</th>
                            <th>Role</th>
                            <th>Added On</th>
                            <th class="action-col">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $index => $user)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td><strong>{{ $user->FirstName }} {{ $user->LastName }}</strong></td>
                            <td>{{ $user->Email }}</td>
                            <td>
                                <span class="badge bg-secondary-light text-secondary">{{ $user->RoleName ?: 'No role assigned' }}</span>
                            </td>
                            <td>{{ \Carbon\Carbon::parse($user->CreatedOn)->format('d M Y') }}</td>
                            <td>
                                <div class="btn-group btn-listing">
                                    <button type="button" class="btn btn-outline-primary btn-list-left" onclick="openUserModal('show', {{ $user->ID }})">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                    <button type="button"
                                            class="btn btn-primary btn-list-right dropdown-toggle dropdown-toggle-split me-2"
                                            data-bs-toggle="dropdown"
                                            data-bs-auto-close="true"
                                            data-bs-popper-config='{"strategy":"fixed"}'
                                            aria-expanded="false">
                                        <span class="sr-only">Toggle Dropdown</span>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <button type="button" class="dropdown-item" onclick="openUserModal('edit', {{ $user->ID }})">
                                                <i class="fas fa-edit me-2"></i> Edit
                                            </button>
                                        </li>
                                        <li>
                                            <form action="{{ route('users.delete_transaction', $user->ID) }}" method="POST" onsubmit="return confirm('Are you sure you want to remove this user from this tenant?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger">
                                                    <i class="fas fa-user-minus me-2"></i> Remove User
                                                </button>
                                            </form>
                                        </li>
                                        <li>
                                            <button type="button" class="dropdown-item" onclick="showAuditHistory('USER', {{ $user->ID }})">
                                                <i class="ri-history-line me-2"></i> Audit Log
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center p-3 text-muted">No users associated with this tenant.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- User Form Modal --}}
<div class="modal fade" id="user_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-semibold" id="user_modal_title">User</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="user_modal_body">
                {{-- Loaded via AJAX --}}
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-primary" id="user_modal_save_btn">
                    <i class="fas fa-save me-1"></i> <span id="user_modal_save_label">Save User</span>
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
// ── Auto-hide alerts ──
setTimeout(function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        alert.classList.remove('show');
        setTimeout(() => alert.remove(), 150);
    });
}, 5000);

// ── Open Modal via AJAX ──
function openUserModal(mode, userId = null) {
    const modalEl    = document.getElementById('user_modal');
    const modalBody  = document.getElementById('user_modal_body');
    const modalTitle = document.getElementById('user_modal_title');
    const saveBtn    = document.getElementById('user_modal_save_btn');
    const saveLabel  = document.getElementById('user_modal_save_label');
    const bsModal    = bootstrap.Modal.getOrCreateInstance(modalEl);

    const titles = { create: 'Add User to Tenant', edit: 'Edit User', show: 'User Details' };
    modalTitle.textContent = titles[mode] || 'User';

    if (mode === 'show') {
        saveBtn.classList.add('d-none');
    } else {
        saveBtn.classList.remove('d-none');
        saveLabel.textContent = mode === 'edit' ? 'Update User' : 'Add User';
    }

    modalBody.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2 text-muted">Loading...</p>
        </div>`;

    bsModal.show();

    let url = mode === 'create'
        ? '{{ route("users.modal") }}?mode=create'
        : `/users/${userId}/modal?mode=${mode}`;

    fetch(url, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
        }
    })
    .then(res => { if (!res.ok) throw new Error('Server error: ' + res.status); return res.text(); })
    .then(html => {
        modalBody.innerHTML = html;

        // Wire Save Button to Form AJAX Submit
        saveBtn.onclick = function () {
            const form = modalBody.querySelector('form');
            if (!form) return;

            saveBtn.disabled = true;
            saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';

            const formData = new FormData(form);

            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    bsModal.hide();
                    showToast('success', data.message);
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = '<i class="fas fa-save me-1"></i> ' + saveLabel.textContent;
                    if (data.errors) {
                        showModalErrors(data.errors);
                    } else if (data.message) {
                        alert(data.message);
                    }
                }
            })
            .catch(err => {
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="fas fa-save me-1"></i> ' + saveLabel.textContent;
                alert('An error occurred during submission: ' + err.message);
            });
        };
    })
    .catch(err => {
        modalBody.innerHTML = `
            <div class="alert alert-danger m-3">
                <i class="fas fa-exclamation-circle me-2"></i>
                Failed to load. Please try again.<br>
                <small class="text-muted">${err.message}</small>
            </div>`;
    });
}

function showModalErrors(errors) {
    const modalBody = document.getElementById('user_modal_body');
    modalBody.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    modalBody.querySelectorAll('.ajax-error-feedback').forEach(el => el.remove());

    let firstField = null;
    Object.entries(errors).forEach(([field, messages]) => {
        const input = modalBody.querySelector(`[name="${field}"]`);
        if (input) {
            input.classList.add('is-invalid');
            const feedback = document.createElement('div');
            feedback.className = 'invalid-feedback ajax-error-feedback';
            feedback.style.display = 'block';
            feedback.textContent = messages[0];
            input.insertAdjacentElement('afterend', feedback);
            if (!firstField) firstField = input;
        }
    });

    if (firstField) {
        firstField.scrollIntoView({ behavior: 'smooth', block: 'center' });
        firstField.focus();
    }
}

function showToast(type, message) {
    const bg = type === 'success' ? 'bg-success' : 'bg-danger';
    const toast = document.createElement('div');
    toast.style.cssText = 'position:fixed;top:20px;right:20px;z-index:9999;min-width:280px;';
    toast.innerHTML = `
        <div class="alert ${bg} text-white shadow d-flex align-items-center gap-2 mb-0">
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
            <span>${message}</span>
            <button type="button" class="btn-close btn-close-white ms-auto" onclick="this.closest('div').parentElement.remove()"></button>
        </div>`;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 4000);
}
</script>
@endsection

@extends('layouts.master')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/customer-management.css') }}">
@endsection

@section('content')

<div class="page-header-breadcrumb mb-3">
    <div class="d-flex align-center justify-content-between flex-wrap">
        <h1 class="page-title fw-medium fs-14 mt-2 pt-1 mb-0">Role Management</h1>
        <div class="d-flex float-end">
            @tenantCan('Roles', 'Add')
            <button type="button" class="btn btn-sm btn-primary btn-wave waves-light" onclick="openRoleModal('create')">
                <i class="ri-add-line fw-medium align-middle me-1"></i> Add Role
            </button>
            @endtenantCan
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
                <form method="GET" action="{{ route('roles.index') }}">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <input type="text"
                                   class="form-control form-control-sm"
                                   name="search"
                                   value="{{ request('search') }}"
                                   placeholder="Search roles by name...">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="fas fa-search"></i> Search
                            </button>
                            <a href="{{ route('roles.index') }}" class="btn btn-sm btn-light">
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
                            <th>Role Name</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Created On</th>
                            <th class="action-col">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($roles as $index => $role)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td><strong>{{ $role->Name }}</strong></td>
                            <td>{{ $role->Description ?: 'No description provided' }}</td>
                            <td>
                                @if(($role->Status ?? 1) == 1)
                                    <span class="status-badge status-active">Active</span>
                                @else
                                    <span class="status-badge status-inactive">Inactive</span>
                                @endif
                            </td>
                            <td>{{ \Carbon\Carbon::parse($role->CreatedOn)->format('d M Y') }}</td>
                            <td>
                                <div class="btn-group btn-listing">
                                    @tenantCan('Roles', 'ViewOnly')
                                    <button type="button" class="btn btn-outline-primary btn-list-left" onclick="openRoleModal('show', {{ $role->ID }})">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                    @endtenantCan

                                    @if(session('selected_tenant.permissions.Roles.Modify') == 1 || session('selected_tenant.permissions.Roles.Delete') == 1)
                                    <button type="button"
                                             class="btn btn-primary btn-list-right dropdown-toggle dropdown-toggle-split me-2"
                                             data-bs-toggle="dropdown"
                                             data-bs-auto-close="true"
                                             data-bs-popper-config='{"strategy":"fixed"}'
                                             aria-expanded="false">
                                        <span class="sr-only">Toggle Dropdown</span>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        @tenantCan('Roles', 'Modify')
                                        <li>
                                            <button type="button" class="dropdown-item" onclick="openRoleModal('edit', {{ $role->ID }})">
                                                <i class="fas fa-edit me-2"></i> Edit
                                            </button>
                                        </li>
                                        @endtenantCan

                                        @tenantCan('Roles', 'Delete')
                                        <li>
                                            <form action="{{ route('roles.delete_transaction', $role->ID) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this role?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger">
                                                    <i class="fas fa-trash-alt me-2"></i> Delete
                                                </button>
                                            </form>
                                        </li>
                                        @endtenantCan

                                        <li>
                                            <button type="button" class="dropdown-item" onclick="showAuditHistory('ROLE', {{ $role->ID }})">
                                                <i class="ri-history-line me-2"></i> Audit Log
                                            </button>
                                        </li>
                                    </ul>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center p-3 text-muted">No roles configured.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Role Form Modal --}}
<div class="modal fade" id="role_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-semibold" id="role_modal_title">Role</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="role_modal_body">
                {{-- Loaded via AJAX --}}
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-primary" id="role_modal_save_btn">
                    <i class="fas fa-save me-1"></i> <span id="role_modal_save_label">Save Role</span>
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
function openRoleModal(mode, roleId = null) {
    const modalEl    = document.getElementById('role_modal');
    const modalBody  = document.getElementById('role_modal_body');
    const modalTitle = document.getElementById('role_modal_title');
    const saveBtn    = document.getElementById('role_modal_save_btn');
    const saveLabel  = document.getElementById('role_modal_save_label');
    const bsModal    = bootstrap.Modal.getOrCreateInstance(modalEl);

    const titles = { create: 'Create Role', edit: 'Edit Role', show: 'Role Details' };
    modalTitle.textContent = titles[mode] || 'Role';

    if (mode === 'show') {
        saveBtn.classList.add('d-none');
    } else {
        saveBtn.classList.remove('d-none');
        saveLabel.textContent = mode === 'edit' ? 'Update Role' : 'Save Role';
    }

    modalBody.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2 text-muted">Loading...</p>
        </div>`;

    bsModal.show();

    let url = mode === 'create'
        ? '{{ route("roles.modal") }}?mode=create'
        : `/roles/${roleId}/modal?mode=${mode}`;

    fetch(url, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
        }
    })
    .then(res => { if (!res.ok) throw new Error('Server error: ' + res.status); return res.text(); })
    .then(html => {
        modalBody.innerHTML = html;
        initializePermissionsMatrix();

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
    const modalBody = document.getElementById('role_modal_body');
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

// ── Initialize and Enforce Permissions Matrix ──
function initializePermissionsMatrix() {
    const modalBody = document.getElementById('role_modal_body');
    if (!modalBody) return;
    const rows = modalBody.querySelectorAll('.permissions-table tbody tr');
    rows.forEach(row => {
        const viewCheckbox = row.querySelector('input[name$="[ViewOnly]"]');
        const otherCheckboxes = row.querySelectorAll('input[type="checkbox"]:not([name$="[ViewOnly]"])');
        if (viewCheckbox) {
            let anyChecked = false;
            otherCheckboxes.forEach(cb => {
                if (cb.checked) anyChecked = true;
            });
            if (anyChecked) {
                viewCheckbox.checked = true;
            }
        }
    });
}

// Event delegation for permissions matrix checkbox interaction
document.getElementById('role_modal_body').addEventListener('change', function(e) {
    const target = e.target;
    if (target && target.type === 'checkbox' && target.classList.contains('form-check-input')) {
        const row = target.closest('tr');
        if (!row) return;
        const viewCheckbox = row.querySelector('input[name$="[ViewOnly]"]');
        const otherCheckboxes = row.querySelectorAll('input[type="checkbox"]:not([name$="[ViewOnly]"])');
        
        if (viewCheckbox) {
            if (target === viewCheckbox) {
                // If ViewOnly was unchecked, uncheck all other checkboxes in the same row
                if (!viewCheckbox.checked) {
                    otherCheckboxes.forEach(cb => cb.checked = false);
                }
            } else {
                // If any other checkbox was checked, ensure ViewOnly is checked
                if (target.checked) {
                    viewCheckbox.checked = true;
                }
            }
        }
    }
});
</script>
@endsection

{{-- User Form Partial --}}
@if($mode !== 'create')
    <div class="d-flex justify-content-end mb-2">
        @tenantCan('Users', 'ViewOnly')
        <button type="button" class="btn btn-sm btn-outline-secondary d-flex align-items-center" onclick="showAuditHistory('USER', {{ $user->ID }})">
            <i class="ri-history-line me-1"></i> Audit History
        </button>
        @endtenantCan
    </div>
@endif

<form action="{{ $mode === 'create' ? route('users.save_transaction') : route('users.edit_transaction', $user->ID) }}" method="POST">
    @csrf
    @if($mode === 'edit')
        @method('PUT')
    @endif

    <div class="mb-3">
        <label for="email" class="form-label fw-medium text-dark">Email Address <span class="text-danger">*</span></label>
        <input type="email" 
               class="form-control" 
               id="email" 
               name="Email" 
               value="{{ old('Email', $user->Email ?? '') }}" 
               required 
               placeholder="e.g. employee@company.com"
               {{ $mode !== 'create' ? 'readonly' : '' }}
               {{ $mode === 'show' ? 'disabled' : '' }}>
        @if($mode === 'create')
            <small class="text-muted fs-11">If this user already has a Go Digital portal login, they will be mapped immediately. If not, a portal account will be automatically provisioned with temporary credentials.</small>
        @endif
    </div>

    <div class="row mb-3">
        <div class="col-md-6">
            <label for="first_name" class="form-label fw-medium text-dark">First Name <span class="text-danger">*</span></label>
            <input type="text" 
                   class="form-control" 
                   id="first_name" 
                   name="FirstName" 
                   value="{{ old('FirstName', $user->FirstName ?? '') }}" 
                   required 
                   placeholder="First name"
                   {{ $mode === 'show' ? 'disabled' : '' }}>
        </div>
        <div class="col-md-6">
            <label for="last_name" class="form-label fw-medium text-dark">Last Name <span class="text-danger">*</span></label>
            <input type="text" 
                   class="form-control" 
                   id="last_name" 
                   name="LastName" 
                   value="{{ old('LastName', $user->LastName ?? '') }}" 
                   required 
                   placeholder="Last name"
                   {{ $mode === 'show' ? 'disabled' : '' }}>
        </div>
    </div>

    <div class="mb-3">
        <label for="role_id" class="form-label fw-medium text-dark">Assigned Role <span class="text-danger">*</span></label>
        <select class="form-select" id="role_id" name="RoleID" required {{ $mode === 'show' ? 'disabled' : '' }}>
            <option value="">-- Select Role --</option>
            @foreach($roles as $role)
                <option value="{{ $role->ID }}" {{ (int)old('RoleID', $user->RoleID ?? 0) === (int)$role->ID ? 'selected' : '' }}>
                    {{ $role->Name }}
                </option>
            @endforeach
        </select>
    </div>
</form>

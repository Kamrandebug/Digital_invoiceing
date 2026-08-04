<style>
    .permissions-table th {
        background-color: #f3f4f6;
        text-align: center;
        padding: 5px 8px !important;
        font-size: 12px;
        font-weight: 600;
    }
    .permissions-table td {
        text-align: center;
        vertical-align: middle;
        padding: 5px 8px !important;
        font-size: 13px;
    }
    .permissions-table td.module-name-cell {
        text-align: left;
        font-weight: 500;
    }
    .checkbox-wrapper {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 0 !important;
        margin: 0 !important;
    }
    .form-check-input {
        cursor: pointer;
        width: 1.05rem;
        height: 1.05rem;
        margin: 0 !important;
        padding: 0 !important;
    }
</style>

@if($mode !== 'create')
    <div class="d-flex justify-content-end mb-2">
        <button type="button" class="btn btn-sm btn-outline-secondary d-flex align-items-center" onclick="showAuditHistory('ROLE', {{ $role->ID }})">
            <i class="ri-history-line me-1"></i> Audit History
        </button>
    </div>
@endif

<form action="{{ $mode === 'create' ? route('roles.save_transaction') : route('roles.edit_transaction', $role->ID) }}" method="POST">
    @csrf
    @if($mode === 'edit')
        @method('PUT')
    @endif

    <div class="row mb-3">
        <div class="col-md-6 mb-2">
            <label for="role_name" class="form-label fw-medium text-dark">Role Name <span class="text-danger">*</span></label>
            <input type="text" 
                   class="form-control" 
                   id="role_name" 
                   name="Name" 
                   value="{{ old('Name', $role->Name ?? '') }}" 
                   required 
                   placeholder="e.g. Accounts Manager, Viewer"
                   {{ $mode === 'show' ? 'disabled' : '' }}>
        </div>
        <div class="col-md-6 mb-2">
            <label for="role_desc" class="form-label fw-medium text-dark">Description</label>
            <textarea class="form-control" 
                      id="role_desc" 
                      name="Description" 
                      rows="1" 
                      placeholder="Brief summary of role rights..."
                      {{ $mode === 'show' ? 'disabled' : '' }}>{{ old('Description', $role->Description ?? '') }}</textarea>
        </div>
    </div>

    <h6 class="fw-semibold text-dark mb-2">Modular Permissions / Rights Matrix</h6>
    <div class="table-responsive border rounded" style="max-height: 220px; overflow-y: auto;">
        <table class="table table-bordered permissions-table">
            <thead>
                <tr>
                    <th class="text-start">Module Name</th>
                    <th>View</th>
                    <th>Add</th>
                    <th>Edit</th>
                    <th>Del</th>
                    <th>Prt</th>
                    <th>Imp</th>
                    <th>Exp</th>
                </tr>
            </thead>
            <tbody>
                @foreach($modules as $module)
                    @php
                        $detail = $roleDetails[$module->ID] ?? null;
                    @endphp
                    <tr>
                        <td class="module-name-cell">
                            <strong>{{ $module->Name }}</strong>
                        </td>
                        <td>
                            <div class="checkbox-wrapper">
                                <input type="checkbox" 
                                       class="form-check-input" 
                                       name="permissions[{{ $module->ID }}][ViewOnly]" 
                                       value="1" 
                                       {{ old("permissions.{$module->ID}.ViewOnly", $detail && $detail->ViewOnly ? 'checked' : '') }}
                                       {{ $mode === 'show' ? 'disabled' : '' }}>
                            </div>
                        </td>
                        <td>
                            <div class="checkbox-wrapper">
                                <input type="checkbox" 
                                       class="form-check-input" 
                                       name="permissions[{{ $module->ID }}][Add]" 
                                       value="1" 
                                       {{ old("permissions.{$module->ID}.Add", $detail && $detail->Add ? 'checked' : '') }}
                                       {{ $mode === 'show' ? 'disabled' : '' }}>
                            </div>
                        </td>
                        <td>
                            <div class="checkbox-wrapper">
                                <input type="checkbox" 
                                       class="form-check-input" 
                                       name="permissions[{{ $module->ID }}][Modify]" 
                                       value="1" 
                                       {{ old("permissions.{$module->ID}.Modify", $detail && $detail->Modify ? 'checked' : '') }}
                                       {{ $mode === 'show' ? 'disabled' : '' }}>
                            </div>
                        </td>
                        <td>
                            <div class="checkbox-wrapper">
                                <input type="checkbox" 
                                       class="form-check-input" 
                                       name="permissions[{{ $module->ID }}][Delete]" 
                                       value="1" 
                                       {{ old("permissions.{$module->ID}.Delete", $detail && $detail->Delete ? 'checked' : '') }}
                                       {{ $mode === 'show' ? 'disabled' : '' }}>
                            </div>
                        </td>
                        <td>
                            <div class="checkbox-wrapper">
                                <input type="checkbox" 
                                       class="form-check-input" 
                                       name="permissions[{{ $module->ID }}][Print]" 
                                       value="1" 
                                       {{ old("permissions.{$module->ID}.Print", $detail && $detail->Print ? 'checked' : '') }}
                                       {{ $mode === 'show' ? 'disabled' : '' }}>
                            </div>
                        </td>
                        <td>
                            <div class="checkbox-wrapper">
                                <input type="checkbox" 
                                       class="form-check-input" 
                                       name="permissions[{{ $module->ID }}][Import]" 
                                       value="1" 
                                       {{ old("permissions.{$module->ID}.Import", $detail && $detail->Import ? 'checked' : '') }}
                                       {{ $mode === 'show' ? 'disabled' : '' }}>
                            </div>
                        </td>
                        <td>
                            <div class="checkbox-wrapper">
                                <input type="checkbox" 
                                       class="form-check-input" 
                                       name="permissions[{{ $module->ID }}][Export]" 
                                       value="1" 
                                       {{ old("permissions.{$module->ID}.Export", $detail && $detail->Export ? 'checked' : '') }}
                                       {{ $mode === 'show' ? 'disabled' : '' }}>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</form>

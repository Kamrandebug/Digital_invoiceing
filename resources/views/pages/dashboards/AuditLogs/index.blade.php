@extends('layouts.master')

@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <h1 class="page-title fw-medium fs-14 mt-2 pt-1 mb-0">System Audit Trail</h1>
    <div class="ms-md-1 ms-0">
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="javascript:void(0);">Management</a></li>
                <li class="breadcrumb-item active" aria-current="page">Audit Logs</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-header justify-content-between">
                <div class="card-title">All Action Logs</div>
                
                {{-- Search & Filters Form --}}
                <form action="{{ route('audit_logs.index') }}" method="GET" class="d-flex flex-wrap gap-2 align-items-center">
                    <input type="text" 
                           name="search" 
                           class="form-control form-control-sm" 
                           placeholder="Search logs..." 
                           value="{{ request('search') }}" 
                           style="width: 180px;">

                    <select name="action_filter" class="form-select form-select-sm" style="width: 120px;">
                        <option value="">-- Action --</option>
                        @foreach($actions as $act)
                            <option value="{{ $act }}" {{ request('action_filter') === $act ? 'selected' : '' }}>{{ strtoupper($act) }}</option>
                        @endforeach
                    </select>

                    <select name="module_filter" class="form-select form-select-sm" style="width: 120px;">
                        <option value="">-- Module --</option>
                        @foreach($modules as $mod)
                            <option value="{{ $mod }}" {{ request('module_filter') === $mod ? 'selected' : '' }}>{{ strtoupper($mod) }}</option>
                        @endforeach
                    </select>

                    <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                    @if(request()->anyFilled(['search', 'action_filter', 'module_filter']))
                        <a href="{{ route('audit_logs.index') }}" class="btn btn-sm btn-light">Reset</a>
                    @endif
                </form>
            </div>
            
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table text-nowrap listing-table">
                        <thead>
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>Performed By</th>
                                <th>Action</th>
                                <th>Module</th>
                                <th>Record ID</th>
                                <th>Date & Time</th>
                                <th>IP Address</th>
                                <th class="action-col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logs as $index => $log)
                                @php
                                    $actionClass = 'bg-warning-transparent text-warning';
                                    if ($log->Action === 'CREATE') $actionClass = 'bg-success-transparent text-success';
                                    if ($log->Action === 'DELETE') $actionClass = 'bg-danger-transparent text-danger';
                                @endphp
                                <tr>
                                    <td>{{ $logs->firstItem() + $index }}</td>
                                    <td>{{ $log->UserName }}</td>
                                    <td>
                                        <span class="badge {{ $actionClass }}">{{ $log->Action }}</span>
                                    </td>
                                    <td>{{ $log->Module }}</td>
                                    <td>#{{ $log->RecordID }}</td>
                                    <td>{{ \Carbon\Carbon::parse($log->CreatedOn)->format('d M Y, h:i A') }}</td>
                                    <td><small class="text-muted">{{ $log->IPAddress }}</small></td>
                                    <td>
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-primary" 
                                                onclick="showAuditHistory('{{ $log->Module }}', {{ $log->RecordID }})">
                                            <i class="fas fa-eye me-1"></i> View
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted p-4">No audit logs found matching criteria.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3 d-flex justify-content-end">
                    {{ $logs->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
@endsection

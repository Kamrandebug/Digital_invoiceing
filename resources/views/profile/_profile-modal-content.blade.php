@php
    $user = auth()->user();
    $activeTab = $tab ?? 'info';
    $tenantUserId = session('selected_tenant.user_id') ?: $user->id;
@endphp

<div class="p-3 p-md-4" style="background-color: var(--cm-bg-body, #f8f9fa);">
    {{-- ── AUDIT HISTORY BUTTON (MATCHING CUSTOMER / USER EDIT MODALS) ── --}}
    <div class="d-flex justify-content-end mb-3">
        @tenantCan('Users', 'ViewOnly')
        <button type="button" class="btn btn-sm btn-outline-pink d-flex align-items-center shadow-xs" onclick="showAuditHistory('USER', {{ $tenantUserId }})">
            <i class="ri-history-line me-1 fs-14"></i> Audit History
        </button>
        @endtenantCan
    </div>

    {{-- ── TAB NAVIGATION (CUSTOMER MODAL STYLE) ── --}}
    <ul class="nav nav-tabs profile-nav-tabs mb-3" id="profileModalTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $activeTab === 'info' ? 'active' : '' }}" id="profile-info-tab" data-bs-toggle="tab" data-bs-target="#profile-info-pane" type="button" role="tab" aria-controls="profile-info-pane" aria-selected="{{ $activeTab === 'info' ? 'true' : 'false' }}">
                <i class="ri-user-line me-1 align-middle fs-14"></i> Basic Information
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $activeTab === 'password' ? 'active' : '' }}" id="profile-security-tab" data-bs-toggle="tab" data-bs-target="#profile-security-pane" type="button" role="tab" aria-controls="profile-security-pane" aria-selected="{{ $activeTab === 'password' ? 'true' : 'false' }}">
                <i class="ri-lock-password-line me-1 align-middle fs-14"></i> Change Password
            </button>
        </li>
    </ul>

    <div class="tab-content" id="profileModalTabsContent">
        
        {{-- ── TAB 1: BASIC INFORMATION ── --}}
        <div class="tab-pane fade {{ $activeTab === 'info' ? 'show active' : '' }}" id="profile-info-pane" role="tabpanel" aria-labelledby="profile-info-tab">
            <div id="profile_info_alert"></div>

            <div class="_profile-panel-shell shadow-xs">
                <form id="profile_info_form" action="{{ route('profile.update-info') }}" method="POST">
                    @csrf
                    
                    {{-- User Summary Box --}}
                    <div class="_profile-top-section d-flex align-items-center">
                        <div class="avatar avatar-lg avatar-rounded bg-primary-transparent me-3 flex-shrink-0">
                            <span class="fs-18 fw-bold text-primary">
                                {{ strtoupper(substr($user->name ?? $user->email ?? 'U', 0, 1)) }}
                            </span>
                        </div>
                        <div>
                            <h6 class="fw-semibold text-dark mb-0 fs-14">{{ $user->name ?? 'User Profile' }}</h6>
                            <span class="text-muted fs-12">{{ $user->email }}</span>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="profile_first_name" class="form-label fs-13 fw-semibold text-dark">First Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="profile_first_name" name="first_name" value="{{ old('first_name', $user->first_name) }}" required placeholder="First name">
                        </div>

                        <div class="col-md-6">
                            <label for="profile_last_name" class="form-label fs-13 fw-semibold text-dark">Last Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="profile_last_name" name="last_name" value="{{ old('last_name', $user->last_name) }}" required placeholder="Last name">
                        </div>

                        <div class="col-md-12">
                            <label for="profile_name" class="form-label fs-13 fw-semibold text-dark">Display Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="profile_name" name="name" value="{{ old('name', $user->name) }}" required placeholder="Display full name">
                        </div>

                        <div class="col-md-6">
                            <label for="profile_email" class="form-label fs-13 fw-semibold text-dark">Email Address (Account Login)</label>
                            <input type="email" class="form-control form-control-sm bg-light" id="profile_email" value="{{ $user->email }}" readonly disabled>
                            <span class="fs-11 text-muted">Email address cannot be changed directly.</span>
                        </div>

                        <div class="col-md-6">
                            <label for="profile_mobile" class="form-label fs-13 fw-semibold text-dark">Mobile Number</label>
                            <input type="text" class="form-control form-control-sm" id="profile_mobile" name="mobile" value="{{ old('mobile', $user->mobile) }}" placeholder="3001234567">
                        </div>

                        <div class="col-md-12">
                            <label for="profile_city" class="form-label fs-13 fw-semibold text-dark">City</label>
                            <input type="text" class="form-control form-control-sm" id="profile_city" name="city" value="{{ old('city', $user->city) }}" placeholder="e.g. Lahore">
                        </div>
                    </div>

                    {{-- Actions matching Customer Modal --}}
                    <div class="d-flex justify-content-end align-items-center gap-2 mt-4 pt-3 border-top">
                        <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">
                            <i class="ri-close-line me-1"></i> Close
                        </button>
                        <button type="submit" class="btn btn-sm btn-primary" id="btn_save_profile_info">
                            <i class="ri-save-line me-1"></i> Save Profile Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ── TAB 2: CHANGE PASSWORD ── --}}
        <div class="tab-pane fade {{ $activeTab === 'password' ? 'show active' : '' }}" id="profile-security-pane" role="tabpanel" aria-labelledby="profile-security-tab">
            <div id="profile_password_alert"></div>

            <div class="_profile-panel-shell shadow-xs">
                <form id="profile_password_form" action="{{ route('profile.update-password') }}" method="POST">
                    @csrf

                    <div class="alert alert-info-transparent fs-12 mb-3">
                        <i class="ri-shield-keyhole-line me-1 fs-15 align-middle"></i>
                        Ensure your new password is at least 8 characters long and contains a mix of letters, numbers, or symbols for optimal security.
                    </div>

                    <div class="row g-3">
                        <div class="col-md-12">
                            <label for="current_password" class="form-label fs-13 fw-semibold text-dark">Current Password <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <input type="password" class="form-control form-control-sm" id="current_password" name="current_password" required placeholder="Enter current password">
                                <button class="btn btn-outline-secondary toggle-password-btn" type="button" data-target="current_password">
                                    <i class="ri-eye-off-line"></i>
                                </button>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="new_password" class="form-label fs-13 fw-semibold text-dark">New Password <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <input type="password" class="form-control form-control-sm" id="new_password" name="password" required placeholder="Enter new password">
                                <button class="btn btn-outline-secondary toggle-password-btn" type="button" data-target="new_password">
                                    <i class="ri-eye-off-line"></i>
                                </button>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="password_confirmation" class="form-label fs-13 fw-semibold text-dark">Confirm New Password <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <input type="password" class="form-control form-control-sm" id="password_confirmation" name="password_confirmation" required placeholder="Confirm new password">
                                <button class="btn btn-outline-secondary toggle-password-btn" type="button" data-target="password_confirmation">
                                    <i class="ri-eye-off-line"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Actions matching Customer Modal --}}
                    <div class="d-flex justify-content-end align-items-center gap-2 mt-4 pt-3 border-top">
                        <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">
                            <i class="ri-close-line me-1"></i> Close
                        </button>
                        <button type="submit" class="btn btn-sm btn-primary" id="btn_save_profile_password">
                            <i class="ri-lock-unlock-line me-1"></i> Update Password
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

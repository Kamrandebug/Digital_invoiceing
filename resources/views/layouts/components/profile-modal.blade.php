{{-- Global User Profile & Account Settings modal --}}
<div class="modal fade effect-fall" id="profile_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered profile-modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-semibold" id="profile_modal_title">User Profile & Account Settings</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" id="profile_modal_body">
                <div class="p-5 text-center">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 text-muted mb-0">Loading profile information...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
#profile_modal .profile-modal-dialog {
    max-width: min(56rem, calc(100vw - 2rem));
}

#profile_modal .modal-content {
    height: auto !important;
    min-height: auto !important;
    background-color: #ffffff;
}

#profile_modal .modal-body {
    max-height: calc(100vh - 8rem);
    overflow-y: auto;
    background-color: var(--cm-bg-body, #f8f9fa);
}

/* Theme Tab Styling matching Customer Modal */
#profile_modal .profile-nav-tabs {
    border-bottom: none !important;
    gap: 8px;
    margin-bottom: 16px;
}
#profile_modal .profile-nav-tabs .nav-link {
    border: 1px solid #dee2e6 !important;
    background-color: #ffffff !important;
    color: #475569 !important;
    font-weight: 600 !important;
    font-size: 13px !important;
    border-radius: 6px !important;
    padding: 8px 18px !important;
    transition: all 0.15s ease-in-out;
}
#profile_modal .profile-nav-tabs .nav-link:hover {
    background-color: #f1f5f9 !important;
    color: #0f172a !important;
}
#profile_modal .profile-nav-tabs .nav-link.active {
    background-color: var(--primary-color, #0e8a74) !important;
    border-color: var(--primary-color, #0e8a74) !important;
    color: #ffffff !important;
}

#profile_modal ._profile-panel-shell {
    background: #ffffff;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 20px;
}

#profile_modal ._profile-top-section {
    background: #ffffff;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 16px 20px;
    margin-bottom: 18px;
}

/* Pink Audit History Button matching customer edit modal */
.btn-outline-pink {
    color: #e83e8c !important;
    border-color: #e83e8c !important;
    background-color: #ffffff !important;
    font-weight: 500;
}
.btn-outline-pink:hover {
    color: #ffffff !important;
    background-color: #e83e8c !important;
    border-color: #e83e8c !important;
}
</style>

<script>
(function () {
    window.openProfileModal = function (tab) {
        tab = tab || 'info';
        var url = "{{ route('profile.modal') }}?tab=" + encodeURIComponent(tab);

        var modalEl = document.getElementById('profile_modal');
        var bodyEl = document.getElementById('profile_modal_body');
        var titleEl = document.getElementById('profile_modal_title');
        if (!modalEl || !bodyEl) return;

        bodyEl.innerHTML = '<div class="p-5 text-center"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted mb-0">Loading profile information...</p></div>';
        if (titleEl) titleEl.textContent = 'User Profile & Account Settings';

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (res) {
                if (!res.ok) throw new Error('Failed to load profile');
                return res.text();
            })
            .then(function (html) {
                bodyEl.innerHTML = html;

                // Execute script tags injected via innerHTML
                Array.from(bodyEl.getElementsByTagName('script')).forEach(function (old) {
                    var s = document.createElement('script');
                    if (old.src) {
                        s.src = old.src;
                        s.async = false;
                    } else {
                        s.textContent = old.textContent;
                    }
                    document.body.appendChild(s);
                    old.remove();
                });

                initProfileModalScripts(bodyEl);
            })
            .catch(function (err) {
                bodyEl.innerHTML = '<div class="p-4 text-danger text-center"><i class="ri-error-warning-line me-1"></i> Failed to load profile modal.</div>';
            });

        var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    };

    function initProfileModalScripts(container) {
        // Handle password eye toggle buttons
        var toggleBtns = container.querySelectorAll('.toggle-password-btn');
        toggleBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var targetId = btn.getAttribute('data-target');
                var input = container.querySelector('#' + targetId);
                var icon = btn.querySelector('i');
                if (input) {
                    if (input.type === 'password') {
                        input.type = 'text';
                        if (icon) {
                            icon.classList.remove('ri-eye-off-line');
                            icon.classList.add('ri-eye-line');
                        }
                    } else {
                        input.type = 'password';
                        if (icon) {
                            icon.classList.remove('ri-eye-line');
                            icon.classList.add('ri-eye-off-line');
                        }
                    }
                }
            });
        });

        // Attach AJAX submit handler to info form
        var infoForm = container.querySelector('#profile_info_form');
        if (infoForm) {
            infoForm.addEventListener('submit', function (e) {
                e.preventDefault();
                var alertBox = container.querySelector('#profile_info_alert');
                var btn = container.querySelector('#btn_save_profile_info');
                if (alertBox) alertBox.innerHTML = '';
                if (btn) {
                    btn.disabled = true;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Saving...';
                }

                fetch(infoForm.action, {
                    method: 'POST',
                    body: new FormData(infoForm),
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="ri-save-line me-1 align-middle"></i> Save Profile Changes';
                    }
                    if (data.status === 'success') {
                        if (alertBox) {
                            alertBox.innerHTML = '<div class="alert alert-success alert-dismissible fade show fs-12 mb-3" role="alert">' +
                                '<i class="ri-checkbox-circle-line me-1"></i> ' + (data.message || 'Profile updated successfully.') +
                                '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
                        }
                    } else {
                        if (alertBox) {
                            alertBox.innerHTML = '<div class="alert alert-danger alert-dismissible fade show fs-12 mb-3" role="alert">' +
                                '<i class="ri-error-warning-line me-1"></i> ' + (data.message || 'Failed to update profile.') +
                                '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
                        }
                    }
                })
                .catch(function (err) {
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="ri-save-line me-1 align-middle"></i> Save Profile Changes';
                    }
                    if (alertBox) {
                        alertBox.innerHTML = '<div class="alert alert-danger alert-dismissible fade show fs-12 mb-3" role="alert">' +
                            '<i class="ri-error-warning-line me-1"></i> An error occurred while updating profile.</div>';
                    }
                });
            });
        }

        // Attach AJAX submit handler to password form
        var passForm = container.querySelector('#profile_password_form');
        if (passForm) {
            passForm.addEventListener('submit', function (e) {
                e.preventDefault();
                var alertBox = container.querySelector('#profile_password_alert');
                var btn = container.querySelector('#btn_save_profile_password');
                if (alertBox) alertBox.innerHTML = '';
                if (btn) {
                    btn.disabled = true;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Updating...';
                }

                fetch(passForm.action, {
                    method: 'POST',
                    body: new FormData(passForm),
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="ri-lock-unlock-line me-1 align-middle"></i> Update Password';
                    }
                    if (data.status === 'success') {
                        passForm.reset();
                        if (alertBox) {
                            alertBox.innerHTML = '<div class="alert alert-success alert-dismissible fade show fs-12 mb-3" role="alert">' +
                                '<i class="ri-checkbox-circle-line me-1"></i> ' + (data.message || 'Password updated successfully.') +
                                '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
                        }
                    } else {
                        if (alertBox) {
                            alertBox.innerHTML = '<div class="alert alert-danger alert-dismissible fade show fs-12 mb-3" role="alert">' +
                                '<i class="ri-error-warning-line me-1"></i> ' + (data.message || 'Failed to update password.') +
                                '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
                        }
                    }
                })
                .catch(function (err) {
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="ri-lock-unlock-line me-1 align-middle"></i> Update Password';
                    }
                    if (alertBox) {
                        alertBox.innerHTML = '<div class="alert alert-danger alert-dismissible fade show fs-12 mb-3" role="alert">' +
                            '<i class="ri-error-warning-line me-1"></i> An error occurred while updating password.</div>';
                    }
                });
            });
        }
    }
})();
</script>

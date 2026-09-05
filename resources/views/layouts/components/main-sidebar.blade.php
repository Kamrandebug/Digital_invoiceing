<aside class="app-sidebar sticky" id="sidebar">

    <!-- Start::main-sidebar-header -->
    <div class="main-sidebar-header">
        @php
            $consultantLogo = session('selected_tenant.ConsultantLogo');
            if (!$consultantLogo && auth()->check()) {
                try {
                    $consultantUser = \Illuminate\Support\Facades\DB::connection('landlord')
                        ->table('users')
                        ->where('id', auth()->id())
                        ->where('isconsultant', 1)
                        ->first(['ConsultantLogo']);
                    if ($consultantUser && !empty($consultantUser->ConsultantLogo)) {
                        $logo = $consultantUser->ConsultantLogo;
                        if (str_starts_with($logo, 'http://') || str_starts_with($logo, 'https://')) {
                            $consultantLogo = $logo;
                        } else {
                            try {
                                $consultantLogo = \Illuminate\Support\Facades\Storage::disk('s3')->temporaryUrl($logo, now()->addMinutes(20));
                            } catch (\Throwable $e) {
                                $consultantLogo = 'https://godigital.pk.s3.ap-southeast-1.amazonaws.com/' . ltrim($logo, '/');
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    // ignore
                }
            }
        @endphp

        @if(!empty($consultantLogo))
            <a href="{{ url('dashboard') }}" class="header-logo">
                <img src="{{ $consultantLogo }}" alt="logo" class="desktop-logo" style="height: 35px; max-height: 35px; width: auto; object-fit: contain;">
                <img src="{{ $consultantLogo }}" alt="logo" class="desktop-dark" style="height: 35px; max-height: 35px; width: auto; object-fit: contain;">
                <img src="{{ asset('build/assets/images/se_logo_icon.png') }}" alt="logo" class="toggle-logo">
                <img src="{{ asset('build/assets/images/brand-logos/toggle-dark.png') }}" alt="logo" class="toggle-dark">
            </a>
        @else
            <a href="{{ url('dashboard') }}" class="header-logo">
                <img src="{{ asset('build/assets/images/se_logo.png') }}" alt="logo" class="desktop-logo">
                <img src="{{ asset('build/assets/images/brand-logos/toggle-dark.png') }}" alt="logo" class="toggle-dark">
                <img src="{{ asset('build/assets/images/brand-logos/desktop-dark.png') }}" alt="logo" class="desktop-dark">
                <img src="{{ asset('build/assets/images/se_logo_icon.png') }}" alt="logo" class="toggle-logo">
            </a>
        @endif
    </div>
    <!-- End::main-sidebar-header -->

    <!-- Start::main-sidebar -->
    <div class="main-sidebar" id="sidebar-scroll">

        <!-- Start::nav -->
        <nav class="main-menu-container nav nav-pills flex-column sub-open">
            <div class="slide-left" id="slide-left">
                <svg xmlns="http://www.w3.org/2000/svg" fill="#7b8191" width="24" height="24"
                    viewBox="0 0 24 24">
                    <path d="M13.293 6.293 7.586 12l5.707 5.707 1.414-1.414L10.414 12l4.293-4.293z"></path>
                </svg>
            </div>
            <ul class="main-menu">

                @php
                    // ── Gather user permissions from session ──────────────────────────────────
                    // Format: ['Products' => ['ViewOnly'=>1,'Add'=>1,...], 'Customers' => [...], ...]
                    // Empty array = Owner/no-role = show everything
                    $sidebarPermissions = session('selected_tenant.permissions', []);
                    $isOwnerOrNoRole    = empty($sidebarPermissions);

                    // ── Check IsPurchased flag (controls Purchases/Suppliers visibility) ───────
                    $isPurchased = false;
                    try {
                        if (\Illuminate\Support\Facades\Schema::hasTable('tbl_business_info')) {
                            $businessInfo = \Illuminate\Support\Facades\DB::table('tbl_business_info')
                                ->where('IsArchive', 0)
                                ->orderBy('ID')
                                ->first(['IsPurchased']);
                            $isPurchased = $businessInfo ? (bool) $businessInfo->IsPurchased : false;
                        }
                    } catch (\Throwable $e) { /* fallback false */ }

                    // ── Load menu groups from DB ──────────────────────────────────────────────
                    // Exclude Reports (ID=4) from dynamic loop — it is rendered as a fixed block
                    // Exclude Management (ID=5) from dynamic loop — rendered separately after Reports
                    $menuGroups = collect();
                    try {
                        $allMainModules = \Illuminate\Support\Facades\DB::connection('tenant')
                            ->table('tbl_main_module')
                            ->where('Status', 1)
                            ->whereNotIn('ID', [4]) // Only Reports (ID=4) is fixed; Management (ID=5) is dynamic
                            ->orderBy('DisplayOrder')
                            ->get();

                        $allModules = \Illuminate\Support\Facades\DB::connection('tenant')
                            ->table('tbl_module')
                            ->where('Status', 1)
                            ->orderBy('DisplayOrder')
                            ->get()
                            ->groupBy('MainModuleID');

                        foreach ($allMainModules as $mainMod) {
                            $children = $allModules->get($mainMod->ID, collect());

                            // Filter children by permission (skip if no ViewOnly access)
                            $visibleChildren = $children->filter(function ($mod) use ($sidebarPermissions, $isOwnerOrNoRole, $isPurchased) {
                                // Hide Purchases & Suppliers if not IsPurchased
                                if (in_array(strtolower($mod->CurrentPage ?? ''), ['purchases', 'suppliers']) && !$isPurchased) {
                                    return false;
                                }
                                // Owner / no role = show all
                                if ($isOwnerOrNoRole) return true;
                                // Check ViewOnly permission (with fallback for Bulk Invoices to Invoices permission)
                                if (($sidebarPermissions[$mod->Name]['ViewOnly'] ?? 0) == 1) {
                                    return true;
                                }
                                if ($mod->Name === 'Bulk Invoices' && ($sidebarPermissions['Invoices']['ViewOnly'] ?? 0) == 1) {
                                    return true;
                                }
                                return false;
                            });

                            if ($visibleChildren->isNotEmpty()) {
                                $menuGroups->push([
                                    'category'  => $mainMod,
                                    'children'  => $visibleChildren,
                                ]);
                            }
                        }

                    } catch (\Throwable $e) {
                        \Log::error('Sidebar menu load failed: ' . $e->getMessage());
                    }

                    // ── Route helper: map CurrentPage slug → named route ──────────────────────
                    $routeMap = [
                        'products'      => ['url'   => url('products')],
                        'customers'     => ['url'   => url('customers-list')],
                        'suppliers'     => ['route' => 'suppliers.index'],
                        'purchases'     => ['route' => 'purchases.index'],
                        'invoices'      => ['route' => 'invoices.index'],
                        'bulk-invoices' => ['route' => 'bulk-invoices.index'],
                        'users'         => ['route' => 'users.index'],
                        'roles'         => ['route' => 'roles.index'],
                        'audit-logs'    => ['route' => 'audit_logs.index'],
                    ];

                    // ── Active route helper ───────────────────────────────────────────────────
                    $routeNameMap = [
                        'products'      => 'products.*',
                        'customers'     => 'customers.*',
                        'suppliers'     => 'suppliers.*',
                        'purchases'     => 'purchases.*',
                        'invoices'      => 'invoices.*',
                        'bulk-invoices' => 'bulk-invoices.*',
                        'users'         => 'users.*',
                        'roles'         => 'roles.*',
                        'audit-logs'    => 'audit_logs.*',
                    ];
                @endphp

                {{-- ═══════════════════════════════════════════════════════════
                     1. DASHBOARD — Always first, fixed
                ═══════════════════════════════════════════════════════════ --}}
                <li class="slide__category"><span class="category-name">Main</span></li>
                <li class="slide {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <a href="{{ route('dashboard') }}" class="side-menu__item">
                        <i class="ri-pie-chart-line menu-icon"></i>
                        <span class="side-menu__label">Dashboard</span>
                    </a>
                </li>

                {{-- ═══════════════════════════════════════════════════════════
                     2. DYNAMIC MODULES — From tbl_main_module + tbl_module
                        - Single child (e.g. Products) → direct <li> link
                        - Multiple children (Sales, Purchases) → collapsible has-sub
                ═══════════════════════════════════════════════════════════ --}}
                @foreach ($menuGroups as $group)
                    @php
                        $childCount = $group['children']->count();
                        // Determine if any child route is currently active
                        $groupActive = $group['children']->contains(function ($mod) use ($routeNameMap) {
                            $pattern = $routeNameMap[strtolower($mod->CurrentPage ?? '')] ?? null;
                            return $pattern && request()->routeIs($pattern);
                        });
                        $catIcon = $group['category']->MainModuleIcon ?: 'ri-folder-line';
                    @endphp

                    @if ($childCount === 1)
                        {{-- ── Single child: render as direct link (e.g. Products) ── --}}
                        @php
                            $mod      = $group['children']->first();
                            $slug     = strtolower($mod->CurrentPage ?? '');
                            $routeConf = $routeMap[$slug] ?? null;
                            $href     = $routeConf
                                ? (isset($routeConf['route']) ? route($routeConf['route']) : $routeConf['url'])
                                : url($mod->ModuleURL ?? '#');
                            $routePattern = $routeNameMap[$slug] ?? null;
                            $isActive = $routePattern && request()->routeIs($routePattern);
                            $icon     = $mod->MainModuleIcon ?: 'ri-circle-line';
                        @endphp
                        <li class="slide {{ $isActive ? 'active' : '' }}">
                            <a href="{{ $href }}" class="side-menu__item">
                                <i class="{{ $icon }} menu-icon"></i>
                                <span class="side-menu__label">{{ $mod->Name }}</span>
                            </a>
                        </li>

                    @else
                        {{-- ── Multiple children: collapsible submenu (Sales, Purchases) ── --}}
                        <li class="slide has-sub {{ $groupActive ? 'open active' : '' }}">
                            <a href="javascript:void(0);" class="side-menu__item">
                                <i class="{{ $catIcon }} menu-icon"></i>
                                <span class="side-menu__label">{{ $group['category']->Name }}</span>
                                <i class="ri-arrow-right-s-line side-menu__angle"></i>
                            </a>
                            <ul class="slide-menu child1" style="{{ $groupActive ? 'display:block;' : '' }}">
                                <li class="slide side-menu__label1">
                                    <a href="javascript:void(0)">{{ $group['category']->Name }}</a>
                                </li>
                                @foreach ($group['children'] as $mod)
                                    @php
                                        $slug      = strtolower($mod->CurrentPage ?? '');
                                        $routeConf = $routeMap[$slug] ?? null;
                                        $href      = $routeConf
                                            ? (isset($routeConf['route']) ? route($routeConf['route']) : $routeConf['url'])
                                            : url($mod->ModuleURL ?? '#');
                                        $routePattern = $routeNameMap[$slug] ?? null;
                                        $isActive  = $routePattern && request()->routeIs($routePattern);
                                        $icon      = $mod->MainModuleIcon ?: 'ri-circle-line';
                                    @endphp
                                    <li class="slide {{ $isActive ? 'active' : '' }}">
                                        <a href="{{ $href }}" class="side-menu__item">
                                            <i class="{{ $icon }} menu-icon"></i>
                                            <span class="side-menu__label">{{ $mod->Name }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </li>
                    @endif
                @endforeach

                {{-- ═══════════════════════════════════════════════════════════
                     3. REPORTS — Fixed, always before Management
                ═══════════════════════════════════════════════════════════ --}}
                <li class="slide__category"><span class="category-name">Reports</span></li>
                <li class="slide has-sub {{ request()->routeIs('reports.*') ? 'open active' : '' }}">
                    <a href="javascript:void(0);" class="side-menu__item">
                        <i class="ri-bar-chart-2-line menu-icon"></i>
                        <span class="side-menu__label">Reports</span>
                        <i class="ri-arrow-right-s-line side-menu__angle"></i>
                    </a>
                    <ul class="slide-menu child1" style="{{ request()->routeIs('reports.*') ? 'display:block;' : '' }}">
                        <li class="slide side-menu__label1">
                            <a href="javascript:void(0)">Reports</a>
                        </li>
                        <li class="slide {{ request()->routeIs('reports.sale') ? 'active' : '' }}">
                            <a href="{{ route('reports.sale') }}" class="side-menu__item">Sale Report</a>
                        </li>
                    </ul>
                </li>

                {{-- ═══════════════════════════════════════════════════════════
                     4. MANAGEMENT — Now fully dynamic in the main loop above
                        (ID=5 no longer excluded from whereNotIn, so it renders
                         as a collapsible has-sub automatically)
                ═══════════════════════════════════════════════════════════ --}}

                {{-- ═══════════════════════════════════════════════════════════
                     5. LOGOUT — Always last, fixed
                ═══════════════════════════════════════════════════════════ --}}
                <li class="slide__category"><span class="category-name">Account</span></li>
                <li class="slide">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <a href="javascript:void(0);" onclick="event.preventDefault(); this.closest('form').submit();"
                            class="side-menu__item">
                            <svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" viewBox="0 0 256 256">
                                <rect width="256" height="256" fill="none" />
                                <path
                                    d="M48,40H208a16,16,0,0,1,16,16V200a16,16,0,0,1-16,16H48a0,0,0,0,1,0,0V40A0,0,0,0,1,48,40Z"
                                    opacity="0.2" />
                                <polyline points="112 40 48 40 48 216 112 216" fill="none" stroke="currentColor"
                                    stroke-linecap="round" stroke-linejoin="round" stroke-width="16" />
                                <line x1="112" y1="128" x2="224" y2="128" fill="none"
                                    stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                    stroke-width="16" />
                                <polyline points="184 88 224 128 184 168" fill="none" stroke="currentColor"
                                    stroke-linecap="round" stroke-linejoin="round" stroke-width="16" />
                            </svg>
                            <span class="side-menu__label">Logout</span>
                        </a>
                    </form>
                </li>

            </ul>

            <div class="slide-right" id="slide-right"><svg xmlns="http://www.w3.org/2000/svg" fill="#7b8191"
                    width="24" height="24" viewBox="0 0 24 24">
                    <path d="M10.707 17.707 16.414 12l-5.707-5.707-1.414 1.414L13.586 12l-4.293 4.293z"></path>
                </svg></div>
        </nav>
        <!-- End::nav -->

    </div>
    <!-- End::main-sidebar -->

    @if(!empty($consultantLogo))
    <style>
        .sidebar-powered-by .desktop-logo,
        .sidebar-powered-by .desktop-dark {
            display: none;
        }
        html[data-theme-mode="light"] .sidebar-powered-by .desktop-logo {
            display: block;
        }
        html[data-theme-mode="dark"] .sidebar-powered-by .desktop-dark {
            display: block;
        }
        html[data-toggled="double-menu-close"] .sidebar-powered-by,
        html[data-toggled="icon-overlay-close"] .sidebar-powered-by,
        html[data-toggled="icon-text-close"] .sidebar-powered-by,
        html[data-toggled="detached-close"] .sidebar-powered-by,
        html[data-toggled="menu-click-closed"] .sidebar-powered-by,
        html[data-toggled="menu-hover-closed"] .sidebar-powered-by,
        html[data-toggled="close"] .sidebar-powered-by {
            display: none !important;
        }
    </style>
    <div class="sidebar-powered-by" style="font-size: 11px; position: absolute; bottom: 15px; left: 24px; z-index: 99; text-align: left;">
        <div class="text-muted" style="margin-bottom: 4px;">powered by</div>
        <div style="display: flex; justify-content: flex-start;">
            <img src="{{ asset('build/assets/images/se_logo.png') }}" alt="Go Digital" class="desktop-logo" style="height: 15px; width: auto;">
            <img src="{{ asset('build/assets/images/brand-logos/desktop-dark.png') }}" alt="Go Digital" class="desktop-dark" style="height: 15px; width: auto;">
        </div>
    </div>
    @endif

{{-- ── Custom Submenu Styling ──────────────────────────────────────────────── --}}
<style>
    /* ── Submenu container: left accent border + indented background ── */
    .slide-menu.child1 {
        border-left: 2px solid var(--primary-color, #6c63ff);
        margin-left: 1.85rem;
        padding-left: 0 !important;
        background: transparent;
        border-radius: 0 0 6px 6px;
    }

    /* ── Submenu items: smaller text, more padding, muted color ── */
    .slide-menu.child1 .slide a.side-menu__item {
        font-size: 0.82rem !important;
        padding: 0.45rem 0.75rem 0.45rem 1rem !important;
        color: var(--menu-prime-color, #7b8191) !important;
        border-radius: 5px;
        margin: 1px 6px;
        gap: 0.5rem;
    }

    /* ── Submenu icons: smaller than parent ── */
    .slide-menu.child1 .slide a.side-menu__item .menu-icon {
        font-size: 0.95rem !important;
        width: 18px !important;
        min-width: 18px !important;
        opacity: 0.75;
    }

    /* ── Hover: soft primary tinted background ── */
    .slide-menu.child1 .slide a.side-menu__item:hover {
        background: rgba(var(--primary-rgb, 108, 99, 255), 0.08) !important;
        color: var(--primary-color, #6c63ff) !important;
    }
    .slide-menu.child1 .slide a.side-menu__item:hover .menu-icon {
        opacity: 1;
        color: var(--primary-color, #6c63ff) !important;
    }

    /* ── Active submenu item: solid primary highlight ── */
    .slide-menu.child1 .slide.active > a.side-menu__item,
    .slide-menu.child1 .slide > a.side-menu__item.active {
        background: rgba(var(--primary-rgb, 108, 99, 255), 0.12) !important;
        color: var(--primary-color, #6c63ff) !important;
        font-weight: 600 !important;
    }
    .slide-menu.child1 .slide.active > a.side-menu__item .menu-icon {
        opacity: 1;
        color: var(--primary-color, #6c63ff) !important;
    }

    /* ── Hide the "label1" repeat header inside submenu ── */
    .slide-menu.child1 .side-menu__label1 {
        display: none !important;
    }

    /* ── Parent collapsible item: slightly bolder when open ── */
    .slide.has-sub.open > a.side-menu__item {
        font-weight: 600;
    }
    .slide.has-sub.open > a.side-menu__item .menu-icon {
        color: var(--primary-color, #6c63ff);
    }

    /* ── Dark mode adjustments ── */
    [data-theme-mode="dark"] .slide-menu.child1 {
        border-left-color: var(--primary-color, #6c63ff);
    }
    [data-theme-mode="dark"] .slide-menu.child1 .slide a.side-menu__item {
        color: rgba(255,255,255,0.55) !important;
    }
    [data-theme-mode="dark"] .slide-menu.child1 .slide a.side-menu__item:hover {
        color: var(--primary-color, #6c63ff) !important;
        background: rgba(var(--primary-rgb, 108, 99, 255), 0.15) !important;
    }
</style>

</aside>

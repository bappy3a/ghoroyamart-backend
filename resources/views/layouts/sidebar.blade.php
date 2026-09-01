<!-- ========== App Menu ========== -->
<div class="app-menu navbar-menu">
    <!-- LOGO -->
    <div class="navbar-brand-box">
        <!-- Dark Logo-->
        <a href="{{ route('dashboard') }}" class="logo logo-dark">
            <span class="logo-sm">
                <img src="{{ asset('logo.png') }}" alt="" height="40" style="width: 90% !important;">
            </span>
            <span class="logo-lg">
                <img src="{{ asset('logo.png') }}" alt="" height="40" style="width: 90% !important;">
            </span>
        </a>
        <!-- Light Logo-->
        <a href="{{ route('dashboard') }}" class="logo logo-light">
            <span class="logo-sm">
                <img src="{{ asset('logo.png') }}" alt="" height="40" style="width: 90% !important;">
            </span>
            <span class="logo-lg">
                <img src="{{ asset('logo.png') }}" alt="" height="40" style="width: 90% !important;">
            </span>
        </a>
        <button type="button" class="btn btn-sm p-0 fs-20 header-item float-end btn-vertical-sm-hover"
            id="vertical-hover">
            <i class="ri-record-circle-line"></i>
        </button>
    </div>

    <div id="scrollbar">
        <div class="container-fluid">

            <div id="two-column-menu">
            </div>
            <ul class="navbar-nav" id="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link menu-link {{ active_menu(['dashboard']) }}" href="{{ route('dashboard') }}">
                        <i class="mdi mdi-speedometer"></i> <span>Dashboard</span>
                    </a>
                </li>

                @can('sliders.show')
                    <li class="nav-item">
                        <a class="nav-link menu-link {{ active_menu(['sliders.*']) }}" href="{{ route('sliders.index') }}">
                            <i class="mdi mdi-view-carousel-outline"></i> <span>Sliders</span>
                        </a>
                    </li>
                @endcan
                <li class="menu-title"><i class="ri-more-fill"></i> <span data-key="t-pages">Inventory</span></li>
                @can('inventory.show')
                    <li class="nav-item">
                        <a class="nav-link menu-link {{ active_menu(['inventory.*']) }}" href="{{ route('inventory.dashboard') }}">
                            <i class="ri-dashboard-3-line"></i> <span>Inventory</span>
                        </a>
                    </li>
                @endcan
                @canany(['products.show', 'products.create', 'variant-attributes.show'])
                    <li class="nav-item">
                        <a class="nav-link menu-link {{ active_menu(['products.*', 'variant-attributes.*']) }}" href="#sidebarApps" data-bs-toggle="collapse" role="button"
                            aria-expanded="{{ active_menu(['products.*', 'variant-attributes.*'], 'true') ?? 'false' }}" aria-controls="sidebarApps">
                            <i class="mdi mdi-view-grid-plus-outline"></i> <span>Products</span>
                        </a>
                        <div class="collapse menu-dropdown {{ active_menu(['products.*', 'variant-attributes.*'], 'show') }}" id="sidebarApps">
                            <ul class="nav nav-sm flex-column">
                                @can('products.show')
                                    <li class="nav-item">
                                        <a href="{{ route('products.index') }}" class="nav-link {{ active_menu(['products.index'], 'active') }}">Products List</a>
                                    </li>
                                @endcan
                                @can('products.create')
                                    <li class="nav-item">
                                        <a href="{{ route('products.create') }}" class="nav-link {{ active_menu(['products.create'], 'active') }}">Create Product</a>
                                    </li>
                                @endcan
                                @can('variant-attributes.show')
                                    <li class="nav-item">
                                        <a href="{{ route('variant-attributes.index') }}" class="nav-link {{ active_menu(['variant-attributes.*'], 'active') }}">Variant Attributes</a>
                                    </li>
                                @endcan
                            </ul>
                        </div>
                    </li>
                @endcanany
                @can('promotion-landing-pages.show')
                    <li class="nav-item">
                        <a class="nav-link menu-link {{ active_menu(['promotion-landing-pages.*']) }}" href="{{ route('promotion-landing-pages.index') }}">
                            <i class="ri-rocket-2-line"></i> <span>Landing Pages</span>
                        </a>
                    </li>
                @endcan
                @php
                    $backendUser = auth()->user();
                    $showModeratorOrderMenu = (bool) $backendUser?->canAny([
                        'moderator-order-management.show',
                        'moderator-order-management.create',
                    ]);
                    $hideRegularOrderMenu = $showModeratorOrderMenu
                        && $backendUser?->user_type === 'staff'
                        && ! $backendUser?->hasRole('Super Admin');
                    $orderMenuRoutes = [
                        'orders.index',
                        'orders.view',
                        'orders.edit',
                        'orders.invoice*',
                        'orders.delivery-receipt*',
                        'orders.update-status',
                        'orders.update',
                        'orders.update-restock',
                        'orders.pending',
                        'orders.confirmed',
                        'orders.packaging',
                        'orders.processing',
                        'orders.shipped',
                        'orders.delivered',
                        'orders.cancelled',
                    ];
                    $moderatorOrderMenuRoutes = [
                        'moderator-order-management.index',
                        'moderator-order-management.create',
                        'moderator-order-management.store',
                    ];
                @endphp

                @canany(['moderator-order-management.show', 'moderator-order-management.create'])
                    <li class="nav-item">
                        <a class="nav-link menu-link {{ active_menu($moderatorOrderMenuRoutes, 'active') }}" href="#sidebarModeratorOrders" data-bs-toggle="collapse" role="button"
                            aria-expanded="{{ active_menu($moderatorOrderMenuRoutes, 'true') ?? 'false' }}" aria-controls="sidebarModeratorOrders">
                            <i class="ri-shopping-basket-2-line"></i> <span>POS</span>
                        </a>
                        <div class="collapse menu-dropdown {{ active_menu($moderatorOrderMenuRoutes, 'show') }}" id="sidebarModeratorOrders">
                            <ul class="nav nav-sm flex-column">
                                @can('moderator-order-management.create')
                                    <li class="nav-item">
                                        <a href="{{ route('moderator-order-management.create') }}" class="nav-link {{ active_menu(['moderator-order-management.create', 'moderator-order-management.store'], 'active') }}">Create Order</a>
                                    </li>
                                @endcan
                                @can('moderator-order-management.show')
                                    <li class="nav-item">
                                        <a href="{{ route('moderator-order-management.index') }}" class="nav-link {{ active_menu(['moderator-order-management.index'], 'active') }}">Order List</a>
                                    </li>
                                @endcan
                            </ul>
                        </div>
                    </li>
                @endcanany

                @unless($hideRegularOrderMenu)
                @canany(['orders.all', 'orders.pending', 'orders.confirmed', 'orders.packaging', 'orders.shipped', 'orders.delivered', 'orders.cancelled'])
                    <li class="nav-item">
                        <a class="nav-link menu-link {{ active_menu($orderMenuRoutes) }}" href="#sidebarOrders" data-bs-toggle="collapse" role="button"
                            aria-expanded="{{ active_menu($orderMenuRoutes, 'true') ?? 'false' }}" aria-controls="sidebarOrders">
                            <i class="mdi mdi-alpha-c-box-outline"></i> <span>Orders</span>
                        </a>
                        <div class="collapse menu-dropdown {{ active_menu($orderMenuRoutes, 'show') }}" id="sidebarOrders">
                            <ul class="nav nav-sm flex-column">
                                @can('orders.all')
                                    <li class="nav-item">
                                        <a href="{{ route('orders.index') }}" class="nav-link {{ active_menu(['orders.index', 'orders.view', 'orders.edit', 'orders.invoice*', 'orders.update-status', 'orders.update'], 'active') }}">Orders</a>
                                    </li>
                                @endcan
                                @can('orders.pending')
                                    <li class="nav-item">
                                        <a href="{{ route('orders.pending') }}" class="nav-link {{ active_menu(['orders.pending'], 'active') }}">Pending Orders</a>
                                    </li>
                                @endcan
                                @can('orders.confirmed')
                                    <li class="nav-item">
                                        <a href="{{ route('orders.confirmed') }}" class="nav-link {{ active_menu(['orders.confirmed'], 'active') }}">Confirmed Orders</a>
                                    </li>
                                @endcan
                                @can('orders.packaging')
                                    <li class="nav-item">
                                        <a href="{{ route('orders.packaging') }}" class="nav-link {{ active_menu(['orders.packaging', 'orders.processing'], 'active') }}">Packaging Orders</a>
                                    </li>
                                @endcan
                                @can('orders.shipped')
                                    <li class="nav-item">
                                        <a href="{{ route('orders.shipped') }}" class="nav-link {{ active_menu(['orders.shipped'], 'active') }}">In Courier</a>
                                    </li>
                                @endcan
                                @can('orders.delivered')
                                    <li class="nav-item">
                                        <a href="{{ route('orders.delivered') }}" class="nav-link {{ active_menu(['orders.delivered'], 'active') }}">Delivered Orders</a>
                                    </li>
                                @endcan
                                @can('orders.cancelled')
                                    <li class="nav-item">
                                        <a href="{{ route('orders.cancelled') }}" class="nav-link {{ active_menu(['orders.cancelled', 'orders.update-restock'], 'active') }}">Cancelled Orders</a>
                                    </li>
                                @endcan
                            </ul>
                        </div>
                    </li>
                @endcanany
                @endunless
                @can('orders.search')
                    <li class="nav-item">
                        <a class="nav-link menu-link {{ active_menu(['orders.search*']) }}" href="{{ route('orders.search') }}">
                            <i class="ri-search-line"></i> <span>Search Order</span>
                        </a>
                    </li>
                @endcan
                @can('categories.show')
                    <li class="nav-item">
                        <a class="nav-link menu-link {{ active_menu(['categories.*']) }}" href="{{ route('categories.index') }}">
                            <i class="mdi mdi-alpha-c-box-outline"></i> <span>Categories</span>
                        </a>
                    </li>
                @endcan
                @can('brands.show')
                    <li class="nav-item">
                        <a class="nav-link menu-link {{ active_menu(['brands.*']) }}" href="{{ route('brands.index') }}">
                            <i class="mdi mdi-alpha-b-box-outline"></i> <span>Brands</span>
                        </a>
                    </li>
                @endcan
                @can('units.show')
                    <li class="nav-item">
                        <a class="nav-link menu-link {{ active_menu(['units.*']) }}" href="{{ route('units.index') }}">
                            <i class="mdi mdi-alpha-u-box-outline"></i> <span>Units</span>
                        </a>
                    </li>
                @endcan
                @can('coupons.show')
                    <li class="nav-item">
                        <a class="nav-link menu-link {{ active_menu(['coupons.*']) }}" href="{{ route('coupons.index') }}">
                            <i class="mdi mdi-ticket-percent-outline"></i> <span>Coupons</span>
                        </a>
                    </li>
                @endcan
                @can('flash-deals.show')
                    <li class="nav-item">
                        <a class="nav-link menu-link {{ active_menu(['flash-deals.*']) }}" href="{{ route('flash-deals.index') }}">
                            <i class="ri-flashlight-line"></i> <span>Flash Deals</span>
                        </a>
                    </li>
                @endcan
                @can('contact-messages.show')
                    <li class="nav-item">
                        <a class="nav-link menu-link {{ active_menu(['contact-messages.*']) }}" href="{{ route('contact-messages.index') }}">
                            <i class="ri-mail-line"></i> <span>Contact Messages</span>
                        </a>
                    </li>
                @endcan
                @canany(['blogs.show', 'blog-categories.show'])
                    <li class="nav-item">
                        <a class="nav-link menu-link {{ active_menu(['blogs.*', 'blog-categories.*']) }}" href="#sidebarBlog" data-bs-toggle="collapse" role="button"
                            aria-expanded="{{ active_menu(['blogs.*', 'blog-categories.*'], 'true') ?? 'false' }}" aria-controls="sidebarBlog">
                            <i class="ri-article-line"></i> <span>Blog</span>
                        </a>
                        <div class="collapse menu-dropdown {{ active_menu(['blogs.*', 'blog-categories.*'], 'show') }}" id="sidebarBlog">
                            <ul class="nav nav-sm flex-column">
                                @can('blogs.show')
                                    <li class="nav-item">
                                        <a href="{{ route('blogs.index') }}" class="nav-link {{ active_menu(['blogs.*'], 'active') }}">Blog Posts</a>
                                    </li>
                                @endcan
                                @can('blog-categories.show')
                                    <li class="nav-item">
                                        <a href="{{ route('blog-categories.index') }}" class="nav-link {{ active_menu(['blog-categories.*'], 'active') }}">Blog Categories</a>
                                    </li>
                                @endcan
                            </ul>
                        </div>
                    </li>
                @endcanany

                @canany(['profit-loss-report.show', 'moderator-order-report.show', 'total-order-report.show'])
                    <li class="menu-title"><i class="ri-more-fill"></i> <span>Reports</span></li>
                @endcanany
                @can('profit-loss-report.show')
                    <li class="nav-item">
                        <a class="nav-link menu-link {{ active_menu(['profit-loss-report.*']) }}" href="{{ route('profit-loss-report.index') }}">
                            <i class="ri-line-chart-line"></i> <span>Profit / Loss Report</span>
                        </a>
                    </li>
                @endcan
                @can('moderator-order-report.show')
                    <li class="nav-item">
                        <a class="nav-link menu-link {{ active_menu(['moderator-order-report.*']) }}" href="{{ route('moderator-order-report.index') }}">
                            <i class="ri-user-star-line"></i> <span>Moderator Order Report</span>
                        </a>
                    </li>
                @endcan
                @can('total-order-report.show')
                    <li class="nav-item">
                        <a class="nav-link menu-link {{ active_menu(['total-order-report.*']) }}" href="{{ route('total-order-report.index') }}">
                            <i class="ri-calendar-check-line"></i> <span>Total Order Report</span>
                        </a>
                    </li>
                @endcan

                <li class="menu-title"><i class="ri-more-fill"></i> <span data-key="t-pages">User Management</span></li>
                @can('customers.show')
                    <li class="nav-item">
                        <a class="nav-link menu-link {{ active_menu(['customers.*']) }}" href="{{ route('customers.index') }}">
                            <i class="ri-user-heart-line"></i> <span>Customers</span>
                        </a>
                    </li>
                @endcan
                @can('users.show')
                    <li class="nav-item">
                        <a class="nav-link menu-link {{ active_menu(['users.*']) }}" href="{{ route('users.index') }}">
                            <i class="mdi mdi-account-multiple-outline"></i> <span>Users</span>
                        </a>
                    </li>
                @endcan
                @can('roles.show')
                    <li class="nav-item">
                        <a class="nav-link menu-link {{ active_menu(['roles.*']) }}" href="{{ route('roles.index') }}">
                            <i class="ri-shield-user-line"></i> <span>Roles</span>
                        </a>
                    </li>
                @endcan
                @can('permissions.show')
                    <li class="nav-item">
                        <a class="nav-link menu-link {{ active_menu(['permissions.*']) }}" href="{{ route('permissions.index') }}">
                            <i class="ri-lock-password-line"></i> <span>Permissions</span>
                        </a>
                    </li>
                @endcan
                <li class="menu-title"><i class="ri-more-fill"></i> <span data-key="t-pages">Others</span></li>
                @can('custom-pages.show')
                    <li class="nav-item">
                        <a class="nav-link menu-link {{ active_menu(['custom-pages.*']) }}" href="{{ route('custom-pages.index') }}">
                            <i class="ri-file-text-line"></i> <span>Custom Pages</span>
                        </a>
                    </li>
                @endcan
                @can('about-page-settings.show')
                    <li class="nav-item">
                        <a class="nav-link menu-link {{ active_menu(['about-page-settings.*']) }}" href="{{ route('about-page-settings.index') }}">
                            <i class="ri-file-list-3-line"></i> <span>About Page Settings</span>
                        </a>
                    </li>
                @endcan
                @can('faqs.show')
                    <li class="nav-item">
                        <a class="nav-link menu-link {{ active_menu(['faqs.*']) }}" href="{{ route('faqs.index') }}">
                            <i class="ri-question-line"></i> <span>FAQs</span>
                        </a>
                    </li>
                @endcan
                @can('home-page-settings.show')
                    <li class="nav-item">
                        <a class="nav-link menu-link {{ active_menu(['home-page-settings.*']) }}" href="{{ route('home-page-settings.index') }}">
                            <i class="ri-home-gear-line"></i> <span>Home Page Settings</span>
                        </a>
                    </li>
                @endcan
                @can('settings.show')
                    <li class="nav-item">
                        <a class="nav-link menu-link {{ active_menu(['settings.*']) }}" href="{{ route('settings.index') }}">
                            <i class="ri-settings-3-line"></i> <span>Settings</span>
                        </a>
                    </li>
                @endcan

            </ul>
        </div>
        <!-- Sidebar -->
    </div>
    <div class="sidebar-background"></div>
</div>
<!-- Left Sidebar End -->
<!-- Vertical Overlay-->
<div class="vertical-overlay"></div>

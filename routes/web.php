<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ApplicationsController;
use App\Http\Controllers\ChartsController;
use App\Http\Controllers\DashboardsController;
use App\Http\Controllers\GeneralController;
use App\Http\Controllers\IconsController;
use App\Http\Controllers\MapsController;
use App\Http\Controllers\PagesController;
use App\Http\Controllers\TablesController;
use App\Http\Controllers\WidgetsController;
use App\Http\Controllers\Controller;

Route::get('/', function () {
    return view('welcome');
});

/******** Dashboards ********/
Route::get('/', function () {
    return redirect('index'); // This will redirect '/' to '/index'
});

// Route::get('/', [DashboardsController::class, 'index']);
Route::get('index', [DashboardsController::class, 'index']);
Route::get('add-product', [DashboardsController::class, 'add_product']);
Route::get('cart', [DashboardsController::class, 'cart']); 
Route::get('checkout', [DashboardsController::class, 'checkout']);
Route::get('crm-companies', [DashboardsController::class, 'crm_companies']);
Route::get('crm-contacts', [DashboardsController::class, 'crm_contacts']);
Route::get('crm-deals', [DashboardsController::class, 'crm_deals']);
Route::get('crm-leads', [DashboardsController::class, 'crm_leads']);
Route::get('crypto-buy-sell', [DashboardsController::class, 'crypto_buy_sell']);
Route::get('crypto-currency-exchange', [DashboardsController::class, 'crypto_currency_exchange']);
Route::get('crypto-marketcap', [DashboardsController::class, 'crypto_marketcap']);
Route::get('crypto-transactions', [DashboardsController::class, 'crypto_transactions']);
Route::get('crypto-wallet', [DashboardsController::class, 'crypto_wallet']);
Route::get('customers-list', [DashboardsController::class, 'customers_list']);
Route::get('index1', [DashboardsController::class, 'index1']);
Route::get('index2', [DashboardsController::class, 'index2']);
Route::get('index3', [DashboardsController::class, 'index3']);
Route::get('index4', [DashboardsController::class, 'index4']);
Route::get('index5', [DashboardsController::class, 'index5']);
Route::get('index6', [DashboardsController::class, 'index6']);
Route::get('index7', [DashboardsController::class, 'index7']);
Route::get('index8', [DashboardsController::class, 'index8']);
Route::get('index9', [DashboardsController::class, 'index9']);
Route::get('index10', [DashboardsController::class, 'index10']);
Route::get('index11', [DashboardsController::class, 'index11']);
Route::get('index12', [DashboardsController::class, 'index12']);
Route::get('index13', [DashboardsController::class, 'index13']);
Route::get('index14', [DashboardsController::class, 'index14']);
Route::get('index15', [DashboardsController::class, 'index15']);
Route::get('job-candidate-details', [DashboardsController::class, 'job_candidate_details']);
Route::get('job-candidate-search', [DashboardsController::class, 'job_candidate_search']);
Route::get('job-company-search', [DashboardsController::class, 'job_company_search']);
Route::get('job-details', [DashboardsController::class, 'job_details']);
Route::get('job-post', [DashboardsController::class, 'job_post']);
Route::get('job-search', [DashboardsController::class, 'job_search']);
Route::get('jobs-list', [DashboardsController::class, 'jobs_list']);
Route::get('nft-create', [DashboardsController::class, 'nft_create']);
Route::get('nft-details', [DashboardsController::class, 'nft_details']);
Route::get('nft-live-auction', [DashboardsController::class, 'nft_live_auction']);
Route::get('nft-marketplace', [DashboardsController::class, 'nft_marketplace']);
Route::get('nft-wallet-integration', [DashboardsController::class, 'nft_wallet_integration']);
Route::get('orders-details', [DashboardsController::class, 'orders_details']);
Route::get('orders', [DashboardsController::class, 'orders']);
Route::get('product-details', [DashboardsController::class, 'product_details']);
Route::get('products', [DashboardsController::class, 'products']);
Route::get('projects-create', [DashboardsController::class, 'projects_create']);
Route::get('projects-list', [DashboardsController::class, 'projects_list']);
Route::get('projects-overview', [DashboardsController::class, 'projects_overview']);

/******** Applications ********/
Route::get('chat', [ApplicationsController::class, 'chat']);
Route::get('file-manager', [ApplicationsController::class, 'file_manager']);
Route::get('full-calendar', [ApplicationsController::class, 'full_calendar']);
Route::get('gallery', [ApplicationsController::class, 'gallery']);
Route::get('mail-settings', [ApplicationsController::class, 'mail_settings']);
Route::get('mail', [ApplicationsController::class, 'mail']);
Route::get('sweet-alerts', [ApplicationsController::class, 'sweet_alerts']);
Route::get('task-kanban-board', [ApplicationsController::class, 'task_kanban_board']);
Route::get('task-list-view', [ApplicationsController::class, 'task_list_view']);
Route::get('to-do-list', [ApplicationsController::class, 'to_do_list']);

/******** Charts ********/
Route::get('apex-area-charts', [ChartsController::class, 'apex_area_charts']);
Route::get('apex-bar-charts', [ChartsController::class, 'apex_bar_charts']);
Route::get('apex-boxplot-charts', [ChartsController::class, 'apex_boxplot_charts']);
Route::get('apex-bubble-charts', [ChartsController::class, 'apex_bubble_charts']);
Route::get('apex-candlestick-charts', [ChartsController::class, 'apex_candlestick_charts']);
Route::get('apex-column-charts', [ChartsController::class, 'apex_column_charts']);
Route::get('apex-funnel-charts', [ChartsController::class, 'apex_funnel_charts']);
Route::get('apex-heatmap-charts', [ChartsController::class, 'apex_heatmap_charts']);
Route::get('apex-line-charts', [ChartsController::class, 'apex_line_charts']);
Route::get('apex-mixed-charts', [ChartsController::class, 'apex_mixed_charts']);
Route::get('apex-pie-charts', [ChartsController::class, 'apex_pie_charts']);
Route::get('apex-polararea-charts', [ChartsController::class, 'apex_polararea_charts']);
Route::get('apex-radar-charts', [ChartsController::class, 'apex_radar_charts']);
Route::get('apex-radialbar-charts', [ChartsController::class, 'apex_radialbar_charts']);
Route::get('apex-rangearea-charts', [ChartsController::class, 'apex_rangearea_charts']);
Route::get('apex-scatter-charts', [ChartsController::class, 'apex_scatter_charts']);
Route::get('apex-timeline-charts', [ChartsController::class, 'apex_timeline_charts']);
Route::get('apex-treemap-charts', [ChartsController::class, 'apex_treemap_charts']);
Route::get('chartjs-charts', [ChartsController::class, 'chartjs_charts']);
Route::get('echarts', [ChartsController::class, 'echarts']);

/******** General ********/
Route::get('accordions-collpase', [GeneralController::class, 'accordions_collpase']);
Route::get('alerts', [GeneralController::class, 'alerts']);
Route::get('avatars', [GeneralController::class, 'avatars']);
Route::get('badge', [GeneralController::class, 'badge']);
Route::get('borders', [GeneralController::class, 'borders']);
Route::get('breadcrumb', [GeneralController::class, 'breadcrumb']);
Route::get('breakpoints', [GeneralController::class, 'breakpoints']);
Route::get('buttongroup', [GeneralController::class, 'buttongroup']);
Route::get('buttons', [GeneralController::class, 'buttons']);
Route::get('cards', [GeneralController::class, 'cards']);
Route::get('carousel', [GeneralController::class, 'carousel']);
Route::get('colors', [GeneralController::class, 'colors']);
Route::get('columns', [GeneralController::class, 'columns']);
Route::get('css-grid', [GeneralController::class, 'css_grid']);
Route::get('draggable-cards', [GeneralController::class, 'draggable_cards']);
Route::get('dropdowns', [GeneralController::class, 'dropdowns']);
Route::get('flex', [GeneralController::class, 'flex']);
Route::get('gutters', [GeneralController::class, 'gutters']);
Route::get('helpers', [GeneralController::class, 'helpers']);
Route::get('images-figures', [GeneralController::class, 'images_figures']);
Route::get('links-interactions', [GeneralController::class, 'links_interactions']);
Route::get('listgroup', [GeneralController::class, 'listgroup']);
Route::get('media-player', [GeneralController::class, 'media_player']);
Route::get('modals-closes', [GeneralController::class, 'modals_closes']);
Route::get('more', [GeneralController::class, 'more']);
Route::get('navbar', [GeneralController::class, 'navbar']);
Route::get('navs-tabs', [GeneralController::class, 'navs_tabs']);
Route::get('object-fit', [GeneralController::class, 'object_fit']);
Route::get('offcanvas', [GeneralController::class, 'offcanvas']);
Route::get('pagination', [GeneralController::class, 'pagination']);
Route::get('placeholders', [GeneralController::class, 'placeholders']);
Route::get('popovers', [GeneralController::class, 'popovers']);
Route::get('position', [GeneralController::class, 'position']);
Route::get('progress', [GeneralController::class, 'progress']);
Route::get('ratings', [GeneralController::class, 'ratings']);
Route::get('ribbons', [GeneralController::class, 'ribbons']);
Route::get('scrollspy', [GeneralController::class, 'scrollspy']);
Route::get('sortable-list', [GeneralController::class, 'sortable_list']);
Route::get('spinners', [GeneralController::class, 'spinners']);
Route::get('swiperjs', [GeneralController::class, 'swiperjs']);
Route::get('toasts', [GeneralController::class, 'toasts']);
Route::get('tooltips', [GeneralController::class, 'tooltips']);
Route::get('tour', [GeneralController::class, 'tour']);
Route::get('typography', [GeneralController::class, 'typography']);

/******** Icons ********/
Route::get('icons', [IconsController::class, 'icons']);

/******** Maps ********/
Route::get('google-maps', [MapsController::class, 'google_maps']);
Route::get('leaflet-maps', [MapsController::class, 'leaflet_maps']);
Route::get('vector-maps', [MapsController::class, 'vector_maps']);

/******** Pages ********/
Route::get('blog-create', [PagesController::class, 'blog_create']);
Route::get('blog-details', [PagesController::class, 'blog_details']);
Route::get('blog', [PagesController::class, 'blog']);
Route::get('coming-soon', [PagesController::class, 'coming_soon']);
Route::get('create-password-basic', [PagesController::class, 'create_password_basic']);
Route::get('create-password-cover', [PagesController::class, 'create_password_cover']);
Route::get('emptypage', [PagesController::class, 'emptypage']);
Route::get('error401', [PagesController::class, 'error401']);
Route::get('error404', [PagesController::class, 'error404']);
Route::get('error500', [PagesController::class, 'error500']);
Route::get('faqs', [PagesController::class, 'faqs']);
Route::get('floating-labels', [PagesController::class, 'floating_labels']);
Route::get('form-advanced', [PagesController::class, 'form_advanced']);
Route::get('form-check-radios', [PagesController::class, 'form_check_radios']);
Route::get('form-color-pickers', [PagesController::class, 'form_color_pickers']);
Route::get('form-datetime-pickers', [PagesController::class, 'form_datetime_pickers']);
Route::get('form-file-uploads', [PagesController::class, 'form_file_uploads']);
Route::get('form-input-group', [PagesController::class, 'form_input_group']);
Route::get('form-input-masks', [PagesController::class, 'form_input_masks']);
Route::get('form-inputs', [PagesController::class, 'form_inputs']);
Route::get('form-layout', [PagesController::class, 'form_layout']);
Route::get('form-range', [PagesController::class, 'form_range']);
Route::get('form-select', [PagesController::class, 'form_select']);
Route::get('form-select2', [PagesController::class, 'form_select2']);
Route::get('form-validation', [PagesController::class, 'form_validation']);
Route::get('form-wizards', [PagesController::class, 'form_wizards']);
Route::get('invoice-create', [PagesController::class, 'invoice_create']);
Route::get('invoice-details', [PagesController::class, 'invoice_details']);
Route::get('invoice-list', [PagesController::class, 'invoice_list']);
Route::get('landing', [PagesController::class, 'landing']);
Route::get('lockscreen-basic', [PagesController::class, 'lockscreen_basic']);
Route::get('lockscreen-cover', [PagesController::class, 'lockscreen_cover']);
Route::get('pricing', [PagesController::class, 'pricing']);
Route::get('profile-settings', [PagesController::class, 'profile_settings']);
Route::get('profile', [PagesController::class, 'profile']);
Route::get('quill-editor', [PagesController::class, 'quill_editor']);
Route::get('reset-password-basic', [PagesController::class, 'reset_password_basic']);
Route::get('reset-password-cover', [PagesController::class, 'reset_password_cover']);
Route::get('search-results', [PagesController::class, 'search_results']);
Route::get('sign-in-basic', [PagesController::class, 'sign_in_basic']);
Route::get('sign-in-cover', [PagesController::class, 'sign_in_cover']);
Route::get('sign-up-basic', [PagesController::class, 'sign_up_basic']);
Route::get('sign-up-cover', [PagesController::class, 'sign_up_cover']);
Route::get('team', [PagesController::class, 'team']);
Route::get('terms-conditions', [PagesController::class, 'terms_conditions']);
Route::get('testimonials', [PagesController::class, 'testimonials']);
Route::get('timeline', [PagesController::class, 'timeline']);
Route::get('two-step-verification-basic', [PagesController::class, 'two_step_verification_basic']);
Route::get('two-step-verification-cover', [PagesController::class, 'two_step_verification_cover']);
Route::get('under-maintenance', [PagesController::class, 'under_maintenance']);

/******** Tables ********/
Route::get('data-tables', [TablesController::class, 'data_tables']);
Route::get('grid-tables', [TablesController::class, 'grid_tables']);
Route::get('tables', [TablesController::class, 'tables']);

/******** Widgets ********/
Route::get('widgets', [WidgetsController::class, 'widgets']);
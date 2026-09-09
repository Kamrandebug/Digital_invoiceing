<?php

namespace App\Http\Controllers;

use App\Models\ProductModel;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    /**
     * Display a listing of products
     */
    public function index(Request $request)
    {
        // Filters
        $filters = [
            'search'     => $request->input('search'),
            'start_date' => $request->input('start_date'),
            'end_date'   => $request->input('end_date'),
        ];

        // Validation
        $rules    = [];
        $messages = [];

        // Date rules
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $rules['start_date'] = 'date';
            $rules['end_date']   = 'date|after_or_equal:start_date';
            $messages['end_date.after_or_equal'] = 'End date must be after or equal to start date.';
        }

        // Search validation
        if ($request->filled('search')) {
            $rules['search']            = 'string|max:100|regex:/^[a-zA-Z0-9\s\-\.\,\@]+$/';
            $messages['search.regex']   = 'Search term contains invalid characters.';
            $messages['search.max']     = 'Search term is too long (max 100 chars).';
        }

        if (!empty($rules)) {
            $request->validate($rules, $messages);
        }

        // Get filtered data
        $products = ProductModel::getlistingdata($filters);

        // Listing: UOM / SaleType / Rate columns use description fields from tbl_product only (no ID fallback).
        foreach ($products as $product) {
            $product->UomLabel      = $this->productListingDesc($product, ['Uom_Desc', 'uom_desc']);
            $product->SaleTypeLabel = $this->productListingDesc($product, ['SaleTypeDesc', 'saletypedesc']);
            $product->SaleRateLabel = $this->productListingDesc($product, ['SaleTypeToRateDesc', 'saletypetoratedesc']);
        }

        return view('pages.dashboards.Product.products', compact('products'));
    }

    /**
     * Create form
     */
    public function create()
    {
        return view('pages.dashboards.Product.products-form', [
            'mode'               => 'create',
            'product'            => new ProductModel(),
            'categories'         => $this->lookupCategories(),
            'units'              => $this->lookupUnits(),
            'defaultCategoryId'  => $this->defaultCategoryId(),
            'defaultUnitId'      => $this->defaultUnitId(),
        ]);
    }

    /**
     * Store product
     */
    public function save_transaction(Request $request)
    {
        // Auto-generate product code (same pattern style as customer AccountNo generation).
        $maxId = DB::table('temp_product')->max('ID');
        $nextId = $maxId ? $maxId + 1 : 1;
        $autoProductCode = 'PROD-' . str_pad($nextId, 6, '0', STR_PAD_LEFT);

        // Format inputs (normalize empty Category/Unit for modal)
        $request->merge([
            'Name'     => trim($request->Name ?? ''),
            'Code'     => $autoProductCode,
            'Category' => $request->input('Category') === '' || $request->input('Category') === null ? null : $request->input('Category'),
            'Unit'     => $request->input('Unit') === '' ? 0 : $request->input('Unit'),
            'IsFixedValue' => $request->boolean('IsFixedValue') ? 1 : 0,
        ]);

        // Validate
        try {
            $validated = $request->validate([
            // Basic Info
            'Name'            => ['required', 'string', 'min:2', 'max:200'],
            'Code'            => ['required', 'string', 'max:50', 'unique:tbl_product,Code'],
            'Type'            => ['nullable', 'string', 'max:50'],
            'Category'        => ['nullable', 'integer'],
            'SaleInformation' => ['nullable', 'string', 'max:500'],
            'Image'           => ['nullable', 'string'],

            // Pricing
            'SalePrice'       => ['required', 'numeric', 'min:0'],
            'Cost'            => ['nullable', 'numeric', 'min:0'],
            'TPRate'          => ['nullable', 'numeric', 'min:0'],
            'IsFixedValue'    => ['required', 'integer', 'in:0,1'],
            'Weight'          => ['nullable', 'numeric', 'min:0'],

            // Stock
            'CurrentQuantity' => ['nullable', 'numeric', 'min:0'],
            'LowStockLevel'   => ['nullable', 'numeric', 'min:0'],
            'Unit'            => ['nullable', 'integer'],

            'Status'          => ['required', 'integer', 'in:0,1'],
        ], [
            'Name.required'          => 'Product name is required.',
            'Name.min'               => 'Product name must be at least 2 characters.',
            'Code.required'          => 'Product code is required.',
            'Code.unique'            => 'This product code is already registered.',
            'Category.required'      => 'Category is required.',
            'SalePrice.required'     => 'Sale price is required.',
            'SalePrice.numeric'      => 'Sale price must be a valid number.',
            'SalePrice.min'          => 'Sale price cannot be negative.',
            'Cost.numeric'           => 'Cost must be a valid number.',
            'IsFixedValue.required'  => 'Fixed value selection is required.',
            'Status.required'        => 'Status is required.',
            'Status.in'              => 'Invalid status value.',
        ]);

            $isFixedValue = (int) $request->input('IsFixedValue', 0) === 1;
            $tpRate = (float) ($request->input('TPRate', 0) ?: 0);

            if ($isFixedValue && $tpRate <= 0) {
                throw ValidationException::withMessages([
                    'TPRate' => 'FixedNotifiedValueOrRetailPrice must be greater than 0.00 when IsFixedValue is checked.',
                ]);
            }

            if (! $isFixedValue && abs($tpRate) > 0.0000001) {
                throw ValidationException::withMessages([
                    'TPRate' => 'FixedNotifiedValueOrRetailPrice must be 0.00 when IsFixedValue is unchecked.',
                ]);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->header('X-Requested-With') === 'XMLHttpRequest' || $request->expectsJson()) {
                return response()->json([
                    'status'  => 'error',
                    'errors'  => $e->errors(),
                    'message' => 'Please fix the highlighted fields.',
                ], 422);
            }
            throw $e;
        }

        try {
            // Generate UUID
            $requestId = (string) Str::uuid();
            $userId    = auth()->id() ?? 1;
            $now       = now()->toDateTimeString();

            // Prepare data for new temp_product structure
            $data = [
                'ID'              => $nextId,
                'TID'             => $nextId,
                'VoucherID'       => 0,
                'ProductID'       => null,
                'Name'            => $request->Name,
                'Code'            => $autoProductCode,
                'Type'            => $request->Type,
                'CategoryID'      => $request->Category ?? null,
                'CategoryName'    => null,
                'CurrentQuantity' => $request->CurrentQuantity ?? 0,
                'Unit'            => $request->Unit ?? 0,
                'UnitName'        => null,
                'SalePrice'       => $request->SalePrice ?? 0,
                'Cost'            => $request->Cost ?? 0,
                'TPRate'          => ((int) $request->input('IsFixedValue', 0) === 1) ? ($request->TPRate ?? 0) : 0,
                'IsFixedRate'     => (int) $request->input('IsFixedValue', 0),
                'Weight'          => $request->Weight ?? 0,
                'Image'           => $request->Image ?? null,
                'HsCode'          => $request->HsCode ?? null,
                'UOM'             => $request->input('UOM', $request->input('UomID', $request->input('uom'))),
                'SaleType'        => $request->SaleType ?: null,
                'SaleTypeToRate'  => $request->SaleTypeToRate ?: null,
                'SroSchedule'     => $request->SROSchedule ?: null,
                'SroItem'         => $request->SROItem ?: null,
                'SaleInformation' => $request->SaleInformation ?? null,
                'Status'          => $request->Status ?? 1,
                'CreatedBy'       => (int)$userId,
                'CreatedOn'       => $now,
                'UpdatedBy'       => (int)$userId,
                'UpdatedOn'       => $now,
                'ErrMsg'          => null,
                'ExcelRow'        => null,
                'Flag'            => 0,
                'RequestID'       => $requestId,
            ];

            // Insert into temp
            \Log::info('Inserting Product Data', ['data' => $data]);
            DB::table('temp_product')->insert($data);

            // Execute model transaction (runs query inside model)
            $saveResult = ProductModel::save_transaction(['RequestID' => $requestId]);
            if (is_array($saveResult) && ($saveResult['status'] ?? false) === false) {
                $message = $saveResult['message'] ?? 'Unable to save product.';
                if ($request->header('X-Requested-With') === 'XMLHttpRequest') {
                    return response()->json(['status' => 'error', 'message' => $message], 422);
                }

                return redirect()->back()->with('error', $message)->withInput();
            }

            if ($request->header('X-Requested-With') === 'XMLHttpRequest' || $request->expectsJson()) {
                $newId = DB::table('tbl_product')->where('Code', $autoProductCode)->value('ID');
                if ($newId) {
                    $this->syncProductLowStockLevel((int) $newId, $request);
                }
                $prodRow = $newId ? DB::table('tbl_product')->where('ID', $newId)->first() : null;
                $payload = ['status' => 'success', 'message' => 'Product created successfully!'];
                if ($prodRow) {
                    $isFixed = in_array(strtolower(trim((string) ($prodRow->IsFixedRate ?? '0'))), ['1', 'true', 'yes', 'y', 'on'], true) ? 1 : 0;
                    $uomLabel = '';
                    $uomId = $prodRow->UomID ?? null;
                    if ($uomId !== null && $uomId !== '' && (string) $uomId !== '0') {
                        foreach (['tbl_Unit', 'tbl_unit', 'Tbl_Unit'] as $ut) {
                            try {
                                $uomLabel = (string) (DB::table($ut)->where('id', $uomId)->value('UnitName') ?? '');
                                if ($uomLabel !== '') {
                                    break;
                                }
                            } catch (\Throwable $e) {
                            }
                        }
                    }
                    $payload['product'] = [
                        'id'                      => (int) $prodRow->ID,
                        'label'                   => (string) ($prodRow->Name ?? $prodRow->Code ?? ('Product #' . $prodRow->ID)),
                        'rate'                    => (float) ($prodRow->SalePrice ?? 0),
                        'cost'                    => (float) ($prodRow->Cost ?? 0),
                        'gst'                     => 0,
                        'hscode'                  => (string) ($prodRow->HsCode ?? ''),
                        'sale_information'        => (string) ($prodRow->SaleInformation ?? ''),
                        'uom_id'                  => $prodRow->UomID ?? '',
                        'uom_label'               => $uomLabel,
                        'sale_type_id'            => $prodRow->SaleTypeID ?? '',
                        'sale_type_to_rate_id'    => $prodRow->SaleTypeToRateID ?? '',
                        'sro_schedule_id'         => $prodRow->SroScheduleID ?? '',
                        'sro_item_id'             => $prodRow->SroItemID ?? '',
                        'tp_rate'                 => (float) ($prodRow->TPRate ?? 0),
                        'is_fixed_rate'           => $isFixed,
                    ];
                }

                return response()->json($payload);
            }

            $newId = DB::table('tbl_product')->where('Code', $autoProductCode)->value('ID');
            if ($newId) {
                $this->syncProductLowStockLevel((int) $newId, $request);
            }

            return redirect()
                ->route('products.index')
                ->with('success', 'Product created successfully with Request ID: ' . $requestId);

        } catch (\Exception $e) {
            if ($request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()], 500);
            }
            return redirect()
                ->back()
                ->with('error', 'Error: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Return product form partial for modal (create / edit / show).
     */
    public function modal(Request $request, $id = null)
    {
        $mode    = $request->query('mode', 'create');
        $permissions = session('selected_tenant.permissions', []);

        if ($mode === 'edit' && (int)($permissions['Products']['Modify'] ?? 0) !== 1) {
            if ($id && (int)($permissions['Products']['ViewOnly'] ?? 0) === 1) {
                $mode = 'show';
            } else {
                return response('<div class="alert alert-danger m-3">Access Denied: You do not have permission to edit products.</div>', 403);
            }
        }
        if ($mode === 'create' && (int)($permissions['Products']['Add'] ?? 0) !== 1) {
            return response('<div class="alert alert-danger m-3">Access Denied: You do not have permission to add products.</div>', 403);
        }
        if ($mode === 'show' && (int)($permissions['Products']['ViewOnly'] ?? 0) !== 1) {
            return response('<div class="alert alert-danger m-3">Access Denied: You do not have permission to view products.</div>', 403);
        }

        $product = null;

        $taxDisplayLabels = null;

        if ($id && in_array($mode, ['edit', 'show'])) {
            $row = DB::table('tbl_product')->where('ID', $id)->first();
            if (!$row) {
                return response('<div class="alert alert-danger m-3">Product not found.</div>', 404);
            }
            $product = new ProductModel();
            $product->setRawAttributes((array) $row, true);
            $product->exists = true;

            // Map CRM columns to modal-friendly properties for selects
            $product->UOM             = $product->UomID           ?? null;
            $product->SaleType        = $product->SaleTypeID      ?? null;
            $product->SaleTypeToRate  = $product->SaleTypeToRateID ?? null;
            $product->SROSchedule     = $product->SroScheduleID   ?? null;
            $product->SROItem         = $product->SroItemID       ?? null;

            $this->hydrateCategoryUnitDisplayNames($product);

            if ($mode === 'show') {
                $taxDisplayLabels = $this->resolveProductTaxDisplayLabels($product);
            }
        }

        return response()
            ->view('pages.dashboards.Product._product-form-partial', [
                'mode'               => $mode,
                'product'            => $product ?? new ProductModel(),
                'inModal'            => true,
                'taxDisplayLabels'   => $taxDisplayLabels,
                'categories'         => $this->lookupCategories(),
                'units'              => $this->lookupUnits(),
                'defaultCategoryId'  => $this->defaultCategoryId(),
                'defaultUnitId'      => $this->defaultUnitId(),
            ])
            ->header('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * Show product
     */
    public function show($id)
    {
        // Read from tbl_product
        $product = DB::table('tbl_product')->where('ID', $id)->first();

        if (!$product) {
            abort(404, 'Product not found in CRM');
        }

        // Hydrate model
        $productModel = new ProductModel();
        $productModel->setRawAttributes((array)$product, true);
        $productModel->exists = true;

        $this->hydrateCategoryUnitDisplayNames($productModel);

        return view('pages.dashboards.Product.products-form', [
            'mode'               => 'show',
            'product'            => $productModel,
            'categories'         => $this->lookupCategories(),
            'units'              => $this->lookupUnits(),
            'defaultCategoryId'  => $this->defaultCategoryId(),
            'defaultUnitId'      => $this->defaultUnitId(),
        ]);
    }

    /**
     * Edit form
     */
    public function edit($id)
    {
        // Read from tbl_product
        $product = DB::table('tbl_product')->where('ID', $id)->first();

        if (!$product) {
            abort(404, 'Product not found in CRM');
        }

        // Hydrate model
        $productModel = new ProductModel();
        $productModel->setRawAttributes((array)$product, true);
        $productModel->exists = true;

        return view('pages.dashboards.Product.products-form', [
            'mode'               => 'edit',
            'product'            => $productModel,
            'categories'         => $this->lookupCategories(),
            'units'              => $this->lookupUnits(),
            'defaultCategoryId'  => $this->defaultCategoryId(),
            'defaultUnitId'      => $this->defaultUnitId(),
        ]);
    }

    /**
     * Update product
     */
    public function edit_transaction(Request $request, $id)
    {
        // Get existing
        $product = DB::table('tbl_product')->where('ID', $id)->first();

        if (!$product) {
            if ($request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json(['status' => 'error', 'message' => 'Product not found.'], 404);
            }
            return redirect()->back()->with('error', 'Product not found.');
        }

        // Format inputs (normalize empty Category/Unit for modal)
        $codeInput = strtoupper(trim($request->Code ?? ''));
        if ($codeInput === '') {
            $codeInput = strtoupper(trim((string) ($product->Code ?? '')));
        }

        $request->merge([
            'Name'     => trim($request->Name ?? ''),
            'Code'     => $codeInput,
            'Category' => $request->input('Category') === '' || $request->input('Category') === null ? null : $request->input('Category'),
            'Unit'     => $request->input('Unit') === '' ? 0 : $request->input('Unit'),
            'IsFixedValue' => $request->boolean('IsFixedValue') ? 1 : 0,
        ]);

        // Validate
        try {
            $validated = $request->validate([
            // Basic Info
            'Name'            => ['required', 'string', 'min:2', 'max:200'],
            'Code'            => ['required', 'string', 'max:50', Rule::unique('tbl_product', 'Code')->ignore($id, 'ID')],
            'Type'            => ['nullable', 'string', 'max:50'],
            'Category'        => ['nullable', 'integer'],
            'SaleInformation' => ['nullable', 'string', 'max:500'],
            'Image'           => ['nullable', 'string'],

            // Pricing
            'SalePrice'       => ['required', 'numeric', 'min:0'],
            'Cost'            => ['nullable', 'numeric', 'min:0'],
            'TPRate'          => ['nullable', 'numeric', 'min:0'],
            'IsFixedValue'    => ['required', 'integer', 'in:0,1'],
            'Weight'          => ['nullable', 'numeric', 'min:0'],

            // Stock
            'CurrentQuantity' => ['nullable', 'numeric', 'min:0'],
            'LowStockLevel'   => ['nullable', 'numeric', 'min:0'],
            'Unit'            => ['nullable', 'integer'],

            'Status'          => ['required', 'integer', 'in:0,1'],
        ], [
            'Name.required'          => 'Product name is required.',
            'Name.min'               => 'Product name must be at least 2 characters.',
            'Code.required'          => 'Product code is required.',
            'Code.unique'            => 'This product code is already registered.',
            'Category.required'      => 'Category is required.',
            'SalePrice.required'     => 'Sale price is required.',
            'SalePrice.numeric'      => 'Sale price must be a valid number.',
            'SalePrice.min'          => 'Sale price cannot be negative.',
            'IsFixedValue.required'  => 'Fixed value selection is required.',
            'Status.required'        => 'Status is required.',
            'Status.in'              => 'Invalid status value.',
        ]);

            $isFixedValue = (int) $request->input('IsFixedValue', 0) === 1;
            $tpRate = (float) ($request->input('TPRate', 0) ?: 0);

            if ($isFixedValue && $tpRate <= 0) {
                throw ValidationException::withMessages([
                    'TPRate' => 'FixedNotifiedValueOrRetailPrice must be greater than 0.00 when IsFixedValue is checked.',
                ]);
            }

            if (! $isFixedValue && abs($tpRate) > 0.0000001) {
                throw ValidationException::withMessages([
                    'TPRate' => 'FixedNotifiedValueOrRetailPrice must be 0.00 when IsFixedValue is unchecked.',
                ]);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'status'  => 'error',
                    'errors'  => $e->errors(),
                    'message' => 'Please fix the highlighted fields.',
                ], 422);
            }
            throw $e;
        }

        try {
            // Generate UUID
            $requestId = (string) Str::uuid();
            $userId    = auth()->id() ?? 1;
            $now       = now()->toDateTimeString();

            // Prepare data for new temp_product structure (edit flow needs a row with TID = $id and RequestID)
            $data = [
                'TID'             => $id,
                'VoucherID'       => $product->VoucherID ?? 0,
                'ProductID'       => $id,
                'Name'            => $request->Name,
                'Code'            => $request->Code,
                'Type'            => $request->Type,
                'CategoryID'      => $request->Category ?? null,
                'CategoryName'    => null,
                'CurrentQuantity' => $product->CurrentQuantity ?? 0,
                'Unit'            => $request->Unit ?? 0,
                'UnitName'        => null,
                'SalePrice'       => $request->SalePrice ?? 0,
                'Cost'            => $product->Cost ?? 0,
                'TPRate'          => ((int) $request->input('IsFixedValue', 0) === 1) ? ($request->TPRate ?? 0) : 0,
                'IsFixedRate'     => (int) $request->input('IsFixedValue', 0),
                'Weight'          => $request->Weight ?? 0,
                'Image'           => $request->Image ?? $product->Image ?? null,
                'HsCode'          => $request->HsCode ?? $product->HsCode ?? null,
                'UOM'             => $request->input('UOM', $request->input('UomID', $request->input('uom', $product->UOM ?? null))),
                'SaleType'        => $request->SaleType ?: ($product->SaleType ?? null),
                'SaleTypeToRate'  => $request->SaleTypeToRate ?: ($product->SaleTypeToRate ?? null),
                'SroSchedule'     => $request->SROSchedule ?: ($product->SroSchedule ?? null),
                'SroItem'         => $request->SROItem ?: ($product->SroItem ?? null),
                'SaleInformation' => $request->SaleInformation ?? $product->SaleInformation ?? null,
                'Status'          => $request->Status ?? ($product->Status ?? 1),
                'CreatedBy'       => (int)($product->CreatedBy ?? $userId),
                'CreatedOn'       => $product->CreatedOn ?? $now,
                'UpdatedBy'       => (int)$userId,
                'UpdatedOn'       => $now,
                'ErrMsg'          => null,
                'ExcelRow'        => null,
                'Flag'            => 0,
                'RequestID'       => $requestId,
            ];

            // Insert into temp (edit: no row with ID=$id exists in temp; we must insert one so model can read it)
            $nextTempId = DB::table('temp_product')->max('ID');
            $nextTempId = $nextTempId ? (int)$nextTempId + 1 : 1;
            $data['ID'] = $nextTempId;
            DB::table('temp_product')->insert($data);

            // Execute model transaction (runs query inside model)
            $editResult = ProductModel::edit_transaction(['RequestID' => $requestId]);
            if (is_array($editResult) && ($editResult['status'] ?? false) === false) {
                $message = $editResult['message'] ?? 'Unable to update product.';
                if ($request->header('X-Requested-With') === 'XMLHttpRequest') {
                    return response()->json(['status' => 'error', 'message' => $message], 422);
                }

                return redirect()->back()->with('error', $message)->withInput();
            }

            $this->syncProductLowStockLevel((int) $id, $request);

            if ($request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json(['status' => 'success', 'message' => 'Product updated successfully!']);
            }
            return redirect()
                ->route('products.index')
                ->with('success', 'Product updated successfully with Request ID: ' . $requestId);

        } catch (\Exception $e) {
            \Log::error('Edit transaction failed', [
                'error'      => $e->getMessage(),
                'product_id' => $id,
                'trace'      => $e->getTraceAsString()
            ]);
            if ($request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()], 500);
            }
            return redirect()
                ->back()
                ->with('error', 'Error updating product: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Archive product
     */
    public function delete_transaction($id)
    {
        try {
            // Read from tbl_product
            $product = DB::table('tbl_product')->where('ID', $id)->first();

            if (!$product) {
                return redirect()->back()->with('error', 'Product not found.');
            }

            $requestId = (string) Str::uuid();
            $userId    = auth()->id() ?? 1;
            $now       = now()->toDateTimeString();

            // Insert a row into temp_product so model's delete_transaction can read it (no row with ID=$id exists in temp)
            $nextTempId = DB::table('temp_product')->max('ID');
            $nextTempId = $nextTempId ? (int)$nextTempId + 1 : 1;
            DB::table('temp_product')->insert([
                'ID'         => $nextTempId,
                'TID'        => $id,
                'RequestID'  => $requestId,
                'Code'       => $product->Code ?? '',
                'Name'       => $product->Name ?? '',
                'Status'     => 2,
                'CreatedBy'  => (int)($product->CreatedBy ?? $userId),
                'CreatedOn'  => $product->CreatedOn ?? $now,
                'UpdatedBy'  => (int)$userId,
                'UpdatedOn'  => $now,
            ]);

            // Execute model transaction (runs query inside model)
            ProductModel::delete_transaction(['RequestID' => $requestId]);

            return redirect()
                ->route('products.index')
                ->with('success', 'Product archived successfully!');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error deleting product: ' . $e->getMessage());
        }
    }

    /**
     * AJAX: Load HS Code metadata (UOM list + description) from core_digital_invoicing.
     *
     * We don't assume exact column names; instead we discover them via information_schema
     * so it works even if columns are e.g. HSCODE / HS_CODE / UOM_CODE / ITEM_DESC, etc.
     *
     * Uses the landlord connection: tenant DBs are often on another host and cannot see
     * `core_digital_invoicing`, which lives on the central server.
     */
    public function hsCodeInfo(Request $request)
    {
        $code = trim((string) $request->query('code', ''));

        if ($code === '') {
            return response()->json([
                'status'      => 'error',
                'uoms'        => [],
                'description' => null,
                'message'     => 'HS Code is required.',
            ], 422);
        }

        $codeVariants = $this->hsCodeLookupVariants($code);

        try {
            $db            = $this->fbrConnection();
            $schema        = 'core_digital_invoicing';
            $uomBaseTable  = 'fbr_hscode_uom';
            $descBaseTable = 'fbr_item_desc_code';
            $uomTable      = $schema . '.' . $uomBaseTable;
            $descTable     = $schema . '.' . $descBaseTable;

            // Discover column names dynamically from information_schema
            $uomColumns = $db->table('information_schema.columns')
                ->where('table_schema', $schema)
                ->where('table_name', $uomBaseTable)
                ->pluck('column_name');

            $uomCodeCol = null;
            $uomValCol  = null;
            $uomIdCol   = null;

            foreach ($uomColumns as $col) {
                $lc = strtolower($col);
                if ($uomCodeCol === null && (strpos($lc, 'hscode') !== false || strpos($lc, 'hs_code') !== false || strpos($lc, 'hs code') !== false)) {
                    $uomCodeCol = $col;
                }
                if ($uomIdCol === null && (strpos($lc, 'uom') !== false && strpos($lc, 'id') !== false)) {
                    $uomIdCol = $col;
                }
            }

            foreach ($uomColumns as $col) {
                $lc = strtolower($col);
                if (strpos($lc, 'uom') !== false && (strpos($lc, 'desc') !== false || strpos($lc, 'name') !== false)) {
                    $uomValCol = $col;
                    break;
                }
            }
            if ($uomValCol === null) {
                foreach ($uomColumns as $col) {
                    $lc = strtolower($col);
                    if (strpos($lc, 'desc') !== false || strpos($lc, 'description') !== false) {
                        $uomValCol = $col;
                        break;
                    }
                }
            }

            if ($uomIdCol === null) {
                $uomIdCol = 'uoM_ID';
            }

            $descColumns = $db->table('information_schema.columns')
                ->where('table_schema', $schema)
                ->where('table_name', $descBaseTable)
                ->pluck('column_name');

            $descCodeCol = null;
            $descValCol  = null;

            foreach ($descColumns as $col) {
                $lc = strtolower($col);
                if ($descCodeCol === null && (strpos($lc, 'hscode') !== false || strpos($lc, 'hs_code') !== false || strpos($lc, 'hs code') !== false)) {
                    $descCodeCol = $col;
                }
            }

            foreach ($descColumns as $col) {
                $lc = strtolower($col);
                if ($descCodeCol !== null && strcasecmp($col, $descCodeCol) === 0) {
                    continue;
                }
                if (strpos($lc, 'item') !== false && (strpos($lc, 'desc') !== false || strpos($lc, 'description') !== false)) {
                    $descValCol = $col;
                    break;
                }
            }
            if ($descValCol === null) {
                foreach ($descColumns as $col) {
                    $lc = strtolower($col);
                    if ($descCodeCol !== null && strcasecmp($col, $descCodeCol) === 0) {
                        continue;
                    }
                    if (strpos($lc, 'desc') !== false || strpos($lc, 'description') !== false) {
                        $descValCol = $col;
                        break;
                    }
                }
            }

            $uoms        = [];
            $description = null;

            if ($uomCodeCol && $uomValCol && $uomIdCol) {
                $rows = $db->table(DB::raw($uomTable))
                    ->whereIn($uomCodeCol, $codeVariants)
                    ->select([$uomIdCol . ' as id', $uomValCol . ' as text'])
                    ->get();

                $uoms = $rows
                    ->map(function ($row) {
                        return [
                            'id'   => $row->id,
                            'text' => $row->text,
                        ];
                    })
                    ->unique('id')
                    ->values()
                    ->all();
            }

            if ($descCodeCol && $descValCol) {
                $description = $db->table(DB::raw($descTable))
                    ->whereIn($descCodeCol, $codeVariants)
                    ->value($descValCol);
            }

            return response()->json([
                'status'      => 'success',
                'uoms'        => $uoms,
                'description' => $description ?? '',
            ]);
        } catch (\Exception $e) {
            \Log::error('HS Code lookup failed', [
                'code'  => $code,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status'      => 'error',
                'uoms'        => [],
                'description' => null,
                'message'     => 'Server error while loading HS Code information.',
            ], 500);
        }
    }

    /**
     * FBR reference data (`core_digital_invoicing`) is on the landlord MySQL instance.
     */
    private function fbrConnection()
    {
        return DB::connection('landlord');
    }

    /**
     * @return list<string>
     */
    private function hsCodeLookupVariants(string $code): array
    {
        $code = trim($code);
        $variants = [$code];
        $digits = preg_replace('/\D/', '', $code);
        if ($digits !== '' && ! in_array($digits, $variants, true)) {
            $variants[] = $digits;
        }
        if (strlen($digits) === 8) {
            $formatted = substr($digits, 0, 4) . '.' . substr($digits, 4, 4);
            if (! in_array($formatted, $variants, true)) {
                $variants[] = $formatted;
            }
        }

        return array_values(array_unique($variants));
    }

    /**
     * AJAX: Load Sale Types (transaction types) from core_digital_invoicing.fbr_trans_type_code.
     *
     * Returns a list of { id, text } where:
     *  - id   = transactioN_TYPE_ID (or closest match discovered from schema)
     *  - text = transactioN_DESC (or closest match discovered from schema)
     */
    public function saleTypes(Request $request)
    {
        try {
            $db        = $this->fbrConnection();
            $schema    = 'core_digital_invoicing';
            $baseTable = 'fbr_trans_type_code';
            $fullTable = $schema . '.' . $baseTable;

            // Discover column names dynamically from information_schema
            $columns = $db->table('information_schema.columns')
                ->where('table_schema', $schema)
                ->where('table_name', $baseTable)
                ->pluck('column_name');

            $idCol   = null;
            $descCol = null;

            foreach ($columns as $col) {
                $lc = strtolower($col);

                if ($idCol === null && (strpos($lc, 'type') !== false && strpos($lc, 'id') !== false)) {
                    $idCol = $col;
                }

                if ($descCol === null && strpos($lc, 'desc') !== false) {
                    $descCol = $col;
                }
            }

            // Fallback to explicit names if discovery fails
            if ($idCol === null) {
                $idCol = 'transactioN_TYPE_ID';
            }
            if ($descCol === null) {
                $descCol = 'transactioN_DESC';
            }

            $items = $db->table(DB::raw($fullTable))
                ->select([$idCol . ' as id', $descCol . ' as text'])
                ->orderBy($descCol)
                ->get();

            return response()->json([
                'status' => 'success',
                'items'  => $items,
            ]);
        } catch (\Exception $e) {
            \Log::error('Sale Types lookup failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Server error while loading sale types.',
                'items'   => [],
            ], 500);
        }
    }

    /**
     * AJAX: Load Sale Type To Rate options from core_digital_invoicing.fbr_sale_type_to_rate.
     * Filtered by transactioN_TYPE_ID (from Sale Type dropdown). Returns ratE_ID as value, ratE_DESC as display.
     */
    public function saleTypeToRates(Request $request)
    {
        $transactionTypeId = trim((string) $request->query('transaction_type_id', ''));

        if ($transactionTypeId === '') {
            return response()->json([
                'status' => 'success',
                'items'  => [],
            ]);
        }

        try {
            $db        = $this->fbrConnection();
            $schema    = 'core_digital_invoicing';
            $baseTable = 'fbr_sale_type_to_rate';
            $fullTable = $schema . '.' . $baseTable;

            $columns = $db->table('information_schema.columns')
                ->where('table_schema', $schema)
                ->where('table_name', $baseTable)
                ->pluck('column_name');

            $typeIdCol = null;
            $rateIdCol = null;
            $rateDescCol = null;
            $ratePctCol = null;
            $rateValueCol = null;
            $rateTypeCol = null;

            foreach ($columns as $col) {
                $lc = strtolower($col);
                if ($typeIdCol === null && (strpos($lc, 'type') !== false && strpos($lc, 'id') !== false)) {
                    $typeIdCol = $col;
                }
                if ($rateIdCol === null && (strpos($lc, 'rate') !== false && strpos($lc, 'id') !== false)) {
                    $rateIdCol = $col;
                }
                if ($rateDescCol === null && (strpos($lc, 'rate') !== false && strpos($lc, 'desc') !== false)) {
                    $rateDescCol = $col;
                }
                if ($ratePctCol === null && (strpos($lc, 'rate') !== false && strpos($lc, 'id') === false && strpos($lc, 'desc') === false && strpos($lc, 'type') === false)) {
                    $ratePctCol = $col;
                }
                if ($rateValueCol === null && (strpos($lc, 'rate') !== false && strpos($lc, 'value') !== false)) {
                    $rateValueCol = $col;
                }
                if ($rateTypeCol === null && (strpos($lc, 'rate') !== false && strpos($lc, 'type') !== false)) {
                    $rateTypeCol = $col;
                }
            }

            if ($typeIdCol === null) {
                $typeIdCol = 'transactioN_TYPE_ID';
            }
            if ($rateIdCol === null) {
                $rateIdCol = 'ratE_ID';
            }
            if ($rateDescCol === null) {
                $rateDescCol = 'ratE_DESC';
            }

            $selectCols = [$rateIdCol . ' as id', $rateDescCol . ' as text'];
            if ($ratePctCol !== null) {
                $selectCols[] = $ratePctCol . ' as rate_pct';
            }
            if ($rateValueCol !== null) {
                $selectCols[] = $rateValueCol . ' as rate_value';
            }
            if ($rateTypeCol !== null) {
                $selectCols[] = $rateTypeCol . ' as rate_type';
            }

            $rows = $db->table(DB::raw($fullTable))
                ->where($typeIdCol, $transactionTypeId)
                ->select($selectCols)
                ->orderBy($rateDescCol)
                ->get();

            $items = $rows->map(function ($row) use ($rateDescCol) {
                $ratePct = null;
                if (isset($row->rate_pct) && (is_numeric($row->rate_pct) || preg_match('/^\d+(\.\d+)?/', (string) $row->rate_pct, $m))) {
                    $ratePct = is_numeric($row->rate_pct) ? (float) $row->rate_pct : (float) $m[0];
                }
                if ($ratePct === null && !empty($row->text)) {
                    if (preg_match('/^(\d+(?:\.\d+)?)\s*%?/', trim((string) $row->text), $m)) {
                        $ratePct = (float) $m[1];
                    }
                }
                $rateValue = null;
                if (isset($row->rate_value) && (is_numeric($row->rate_value) || preg_match('/^\d+(\.\d+)?/', (string) $row->rate_value, $m))) {
                    $rateValue = is_numeric($row->rate_value) ? (float) $row->rate_value : (float) $m[0];
                }
                if ($rateValue === null && $ratePct !== null) {
                    $rateValue = $ratePct;
                }

                return [
                    'id'       => $row->id,
                    'text'     => $row->text,
                    'rate_pct' => $ratePct !== null ? $ratePct : '',
                    'rate_value' => $rateValue !== null ? $rateValue : '',
                    'rate_type' => isset($row->rate_type) ? (string) $row->rate_type : '',
                ];
            });

            return response()->json([
                'status' => 'success',
                'items'  => $items,
            ]);
        } catch (\Exception $e) {
            \Log::error('Sale Type To Rate lookup failed', [
                'transaction_type_id' => $transactionTypeId,
                'error'               => $e->getMessage(),
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Server error while loading sale type to rate.',
                'items'   => [],
            ], 500);
        }
    }

    /**
     * AJAX: Load SRO Schedule options from core_digital_invoicing.fbr_sro_schedule.
     * Filtered by ratE_ID (from Sale Type To Rate dropdown). Returns srO_ID as value, srO_DESC as display.
     */
    public function sroSchedules(Request $request)
    {
        $rateId = trim((string) $request->query('rate_id', ''));

        if ($rateId === '') {
            return response()->json([
                'status' => 'success',
                'items'  => [],
            ]);
        }

        try {
            $db        = $this->fbrConnection();
            $schema    = 'core_digital_invoicing';
            $baseTable = 'fbr_sro_schedule';
            $fullTable = $schema . '.' . $baseTable;

            $columns = $db->table('information_schema.columns')
                ->where('table_schema', $schema)
                ->where('table_name', $baseTable)
                ->pluck('column_name');

            $rateIdCol  = null;
            $sroIdCol   = null;
            $sroDescCol = null;

            foreach ($columns as $col) {
                $lc = strtolower($col);
                if ($rateIdCol === null && (strpos($lc, 'rate') !== false && strpos($lc, 'id') !== false)) {
                    $rateIdCol = $col;
                }
                if ($sroIdCol === null && (strpos($lc, 'sro') !== false && strpos($lc, 'id') !== false)) {
                    $sroIdCol = $col;
                }
                if ($sroDescCol === null && (strpos($lc, 'sro') !== false && strpos($lc, 'desc') !== false)) {
                    $sroDescCol = $col;
                }
            }

            if ($rateIdCol === null) {
                $rateIdCol = 'ratE_ID';
            }
            if ($sroIdCol === null) {
                $sroIdCol = 'srO_ID';
            }
            if ($sroDescCol === null) {
                $sroDescCol = 'srO_DESC';
            }

            $items = $db->table(DB::raw($fullTable))
                ->where($rateIdCol, $rateId)
                ->select([$sroIdCol . ' as id', $sroDescCol . ' as text'])
                ->orderBy($sroDescCol)
                ->get();

            return response()->json([
                'status' => 'success',
                'items'  => $items,
            ]);
        } catch (\Exception $e) {
            \Log::error('SRO Schedule lookup failed', [
                'rate_id' => $rateId,
                'error'   => $e->getMessage(),
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Server error while loading SRO schedule.',
                'items'   => [],
            ], 500);
        }
    }

    /**
     * AJAX: Load SRO Item options from core_digital_invoicing.fbr_sro_item.
     * Filtered by srO_ID (from SRO Schedule dropdown). Returns srO_ITEM_ID as value, srO_ITEM_DESC as display.
     */
    public function sroItems(Request $request)
    {
        $sroId = trim((string) $request->query('sro_id', ''));

        if ($sroId === '') {
            return response()->json([
                'status' => 'success',
                'items'  => [],
            ]);
        }

        try {
            $db        = $this->fbrConnection();
            $schema    = 'core_digital_invoicing';
            $baseTable = 'fbr_sro_item';
            $fullTable = $schema . '.' . $baseTable;

            $columns = $db->table('information_schema.columns')
                ->where('table_schema', $schema)
                ->where('table_name', $baseTable)
                ->pluck('column_name');

            $sroIdCol     = null;
            $sroItemIdCol = null;
            $sroItemDescCol = null;

            foreach ($columns as $col) {
                $lc = strtolower($col);
                if ($sroIdCol === null && (strpos($lc, 'sro') !== false && strpos($lc, 'id') !== false && strpos($lc, 'item') === false)) {
                    $sroIdCol = $col;
                }
                if ($sroItemIdCol === null && (strpos($lc, 'sro') !== false && strpos($lc, 'item') !== false && strpos($lc, 'id') !== false)) {
                    $sroItemIdCol = $col;
                }
                if ($sroItemDescCol === null && (strpos($lc, 'sro') !== false && strpos($lc, 'item') !== false && strpos($lc, 'desc') !== false)) {
                    $sroItemDescCol = $col;
                }
            }

            if ($sroIdCol === null) {
                $sroIdCol = 'srO_ID';
            }
            if ($sroItemIdCol === null) {
                $sroItemIdCol = 'srO_ITEM_ID';
            }
            if ($sroItemDescCol === null) {
                $sroItemDescCol = 'srO_ITEM_DESC';
            }

            $items = $db->table(DB::raw($fullTable))
                ->where($sroIdCol, $sroId)
                ->select([$sroItemIdCol . ' as id', $sroItemDescCol . ' as text'])
                ->orderBy($sroItemDescCol)
                ->get();

            return response()->json([
                'status' => 'success',
                'items'  => $items,
            ]);
        } catch (\Exception $e) {
            \Log::error('SRO Item lookup failed', [
                'sro_id' => $sroId,
                'error'  => $e->getMessage(),
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Server error while loading SRO item.',
                'items'   => [],
            ], 500);
        }
    }

    /**
     * UOM text for display (same as Product Details / Add–Edit view mode: fbr_hscode_uom via uoM_ID + HsCode).
     */
    private function resolveUomLabelForProduct(object $product, $db = null): string
    {
        $dash = '—';
        $uomId = $product->UomID ?? $product->UOM ?? null;
        if ($uomId === null || $uomId === '') {
            return '-';
        }

        try {
            $conn   = $db ?? $this->fbrConnection();
            $hsCode = trim((string) ($product->HsCode ?? ''));
            $uomText = $this->fbrLookupUomLabel($conn, $uomId, $hsCode);

            return $uomText !== null && $uomText !== '' ? (string) $uomText : $dash;
        } catch (\Throwable $e) {
            \Log::debug('resolveUomLabelForProduct failed', ['e' => $e->getMessage()]);

            return $dash;
        }
    }

    /**
     * Sale type label (SaleTypeID → fbr_trans_type_code), same as Product Details.
     */
    private function resolveSaleTypeLabelForProduct(object $product, $db = null): string
    {
        $dash        = '—';
        $saleTypeId = $product->SaleTypeID ?? $product->SaleType ?? null;
        if ($saleTypeId === null || $saleTypeId === '') {
            return '-';
        }

        try {
            $conn = $db ?? $this->fbrConnection();
            $t    = $this->fbrLookupTransTypeDesc($conn, $saleTypeId);

            return $t !== null && $t !== '' ? (string) $t : $dash;
        } catch (\Throwable $e) {
            \Log::debug('resolveSaleTypeLabelForProduct failed', ['e' => $e->getMessage()]);

            return $dash;
        }
    }

    /**
     * Sale type to rate label (SaleTypeToRateID), same as Product Details.
     */
    private function resolveSaleTypeToRateLabelForProduct(object $product, $db = null): string
    {
        $dash    = '—';
        $rateId = $product->SaleTypeToRateID ?? $product->SaleTypeToRate ?? null;
        if ($rateId === null || $rateId === '') {
            return '-';
        }

        try {
            $conn = $db ?? $this->fbrConnection();
            $t    = $this->fbrLookupSaleTypeToRateDesc($conn, $rateId);

            return $t !== null && $t !== '' ? (string) $t : $dash;
        } catch (\Throwable $e) {
            \Log::debug('resolveSaleTypeToRateLabelForProduct failed', ['e' => $e->getMessage()]);

            return $dash;
        }
    }

    /**
     * Resolve FBR / HS-related IDs on a product to labels for read-only Product Details (same DB as dropdowns).
     *
     * @return array{uom: string, sale_type: string, sale_type_to_rate: string, sro_schedule: string, sro_item: string}
     */
    private function resolveProductTaxDisplayLabels(ProductModel $product): array
    {
        $dash = '—';
        $out  = [
            'uom'                 => '-',
            'sale_type'           => '-',
            'sale_type_to_rate'   => '-',
            'sro_schedule'        => '-',
            'sro_item'            => '-',
        ];

        try {
            $db = $this->fbrConnection();

            $out['uom']                 = $this->resolveUomLabelForProduct($product, $db);
            $out['sale_type']           = $this->resolveSaleTypeLabelForProduct($product, $db);
            $out['sale_type_to_rate']   = $this->resolveSaleTypeToRateLabelForProduct($product, $db);

            $sroSchedId = $product->SroScheduleID ?? $product->SROSchedule ?? null;
            if ($sroSchedId !== null && $sroSchedId !== '') {
                $t = $this->fbrLookupSroScheduleDesc($db, $sroSchedId);
                $out['sro_schedule'] = $t !== null && $t !== '' ? $t : $dash;
            }

            $sroItemId = $product->SroItemID ?? $product->SROItem ?? null;
            if ($sroItemId !== null && $sroItemId !== '') {
                $t = $this->fbrLookupSroItemDesc($db, $sroItemId);
                $out['sro_item'] = $t !== null && $t !== '' ? $t : $dash;
            }
        } catch (\Throwable $e) {
            \Log::debug('resolveProductTaxDisplayLabels failed', ['e' => $e->getMessage()]);
        }

        return $out;
    }

    private function fbrLookupUomLabel($db, $uomId, string $hsCode): ?string
    {
        $schema       = 'core_digital_invoicing';
        $uomBaseTable = 'fbr_hscode_uom';
        $uomTable     = $schema . '.' . $uomBaseTable;

        $uomColumns = $db->table('information_schema.columns')
            ->where('table_schema', $schema)
            ->where('table_name', $uomBaseTable)
            ->pluck('column_name');

        $uomCodeCol = null;
        $uomValCol  = null;
        $uomIdCol   = null;

        foreach ($uomColumns as $col) {
            $lc = strtolower($col);
            if ($uomCodeCol === null && (strpos($lc, 'hscode') !== false || strpos($lc, 'hs_code') !== false || strpos($lc, 'hs code') !== false)) {
                $uomCodeCol = $col;
            }
            if ($uomIdCol === null && (strpos($lc, 'uom') !== false && strpos($lc, 'id') !== false)) {
                $uomIdCol = $col;
            }
        }

        foreach ($uomColumns as $col) {
            $lc = strtolower($col);
            if (strpos($lc, 'uom') !== false && (strpos($lc, 'desc') !== false || strpos($lc, 'name') !== false)) {
                $uomValCol = $col;
                break;
            }
        }
        if ($uomValCol === null) {
            foreach ($uomColumns as $col) {
                $lc = strtolower($col);
                if (strpos($lc, 'desc') !== false || strpos($lc, 'description') !== false) {
                    $uomValCol = $col;
                    break;
                }
            }
        }

        if ($uomIdCol === null) {
            $uomIdCol = 'uoM_ID';
        }

        if ($uomCodeCol && $uomValCol && $hsCode !== '') {
            $variants = $this->hsCodeLookupVariants($hsCode);
            $row      = $db->table(DB::raw($uomTable))
                ->whereIn($uomCodeCol, $variants)
                ->where($uomIdCol, $uomId)
                ->value($uomValCol);
            if ($row !== null && $row !== '') {
                return (string) $row;
            }
        }

        if ($uomValCol && $uomIdCol) {
            $row = $db->table(DB::raw($uomTable))
                ->where($uomIdCol, $uomId)
                ->value($uomValCol);
            if ($row !== null && $row !== '') {
                return (string) $row;
            }
        }

        return null;
    }

    private function fbrLookupTransTypeDesc($db, $typeId): ?string
    {
        $schema    = 'core_digital_invoicing';
        $baseTable = 'fbr_trans_type_code';
        $fullTable = $schema . '.' . $baseTable;

        $columns = $db->table('information_schema.columns')
            ->where('table_schema', $schema)
            ->where('table_name', $baseTable)
            ->pluck('column_name');

        $idCol   = null;
        $descCol = null;

        foreach ($columns as $col) {
            $lc = strtolower($col);
            if ($idCol === null && (strpos($lc, 'type') !== false && strpos($lc, 'id') !== false)) {
                $idCol = $col;
            }
            if ($descCol === null && strpos($lc, 'desc') !== false) {
                $descCol = $col;
            }
        }

        if ($idCol === null) {
            $idCol = 'transactioN_TYPE_ID';
        }
        if ($descCol === null) {
            $descCol = 'transactioN_DESC';
        }

        $v = $db->table(DB::raw($fullTable))->where($idCol, $typeId)->value($descCol);

        return $v !== null ? (string) $v : null;
    }

    private function fbrLookupSaleTypeToRateDesc($db, $rateId): ?string
    {
        $schema    = 'core_digital_invoicing';
        $baseTable = 'fbr_sale_type_to_rate';
        $fullTable = $schema . '.' . $baseTable;

        $columns = $db->table('information_schema.columns')
            ->where('table_schema', $schema)
            ->where('table_name', $baseTable)
            ->pluck('column_name');

        $rateIdCol   = null;
        $rateDescCol = null;

        foreach ($columns as $col) {
            $lc = strtolower($col);
            if ($rateIdCol === null && (strpos($lc, 'rate') !== false && strpos($lc, 'id') !== false)) {
                $rateIdCol = $col;
            }
            if ($rateDescCol === null && (strpos($lc, 'rate') !== false && strpos($lc, 'desc') !== false)) {
                $rateDescCol = $col;
            }
        }

        if ($rateIdCol === null) {
            $rateIdCol = 'ratE_ID';
        }
        if ($rateDescCol === null) {
            $rateDescCol = 'ratE_DESC';
        }

        $v = $db->table(DB::raw($fullTable))->where($rateIdCol, $rateId)->value($rateDescCol);

        return $v !== null ? (string) $v : null;
    }

    private function fbrLookupSroScheduleDesc($db, $sroId): ?string
    {
        $schema    = 'core_digital_invoicing';
        $baseTable = 'fbr_sro_schedule';
        $fullTable = $schema . '.' . $baseTable;

        $columns = $db->table('information_schema.columns')
            ->where('table_schema', $schema)
            ->where('table_name', $baseTable)
            ->pluck('column_name');

        $sroIdCol   = null;
        $sroDescCol = null;

        foreach ($columns as $col) {
            $lc = strtolower($col);
            if ($sroIdCol === null && (strpos($lc, 'sro') !== false && strpos($lc, 'id') !== false)) {
                $sroIdCol = $col;
            }
            if ($sroDescCol === null && (strpos($lc, 'sro') !== false && strpos($lc, 'desc') !== false)) {
                $sroDescCol = $col;
            }
        }

        if ($sroIdCol === null) {
            $sroIdCol = 'srO_ID';
        }
        if ($sroDescCol === null) {
            $sroDescCol = 'srO_DESC';
        }

        $v = $db->table(DB::raw($fullTable))->where($sroIdCol, $sroId)->value($sroDescCol);

        return $v !== null ? (string) $v : null;
    }

    private function fbrLookupSroItemDesc($db, $itemId): ?string
    {
        $schema    = 'core_digital_invoicing';
        $baseTable = 'fbr_sro_item';
        $fullTable = $schema . '.' . $baseTable;

        $columns = $db->table('information_schema.columns')
            ->where('table_schema', $schema)
            ->where('table_name', $baseTable)
            ->pluck('column_name');

        $sroItemIdCol   = null;
        $sroItemDescCol = null;

        foreach ($columns as $col) {
            $lc = strtolower($col);
            if ($sroItemIdCol === null && (strpos($lc, 'sro') !== false && strpos($lc, 'item') !== false && strpos($lc, 'id') !== false)) {
                $sroItemIdCol = $col;
            }
            if ($sroItemDescCol === null && (strpos($lc, 'sro') !== false && strpos($lc, 'item') !== false && strpos($lc, 'desc') !== false)) {
                $sroItemDescCol = $col;
            }
        }

        if ($sroItemIdCol === null) {
            $sroItemIdCol = 'srO_ITEM_ID';
        }
        if ($sroItemDescCol === null) {
            $sroItemDescCol = 'srO_ITEM_DESC';
        }

        $v = $db->table(DB::raw($fullTable))->where($sroItemIdCol, $itemId)->value($sroItemDescCol);

        return $v !== null ? (string) $v : null;
    }

    /**
     * Export CSV
     */
    public function exportCSV(Request $request)
    {
        $filename = 'products-export-' . date('Y-m-d-His') . '.csv';

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        // Get filtered data
        $filters = [
            'search'     => $request->input('search'),
            'start_date' => $request->input('start_date'),
            'end_date'   => $request->input('end_date'),
        ];

        $products = ProductModel::getlistingdata($filters);

        $callback = function() use ($products) {
            $file = fopen('php://output', 'w');

            // Add BOM
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header
            fputcsv($file, [
                'ID',
                'Code',
                'Name',
                'Category',
                'Sale Price',
                'Cost',
                'Current Quantity',
                'Stock Status',
                'Status',
                'Created Date'
            ]);

            foreach ($products as $product) {
                $stockStatus = match((int)$product->StockStatus) {
                    0 => 'Low Stock',
                    1 => 'In Stock',
                    2 => 'Out of Stock',
                    default => 'Unknown'
                };

                fputcsv($file, [
                    $product->ID,
                    $product->Code,
                    $product->Name,
                    $product->Category,
                    number_format($product->SalePrice, 2),
                    number_format($product->Cost, 2),
                    $product->CurrentQuantity,
                    $stockStatus,
                    $product->Status == 1 ? 'Active' : 'Inactive',
                    $product->CreatedOn,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * JSON: categories + units for selects (tbl_category, tbl_Unit).
     */
    public function lookupsCategoriesUnits()
    {
        return response()->json([
            'status'     => 'success',
            'categories' => $this->lookupCategories()->map(fn ($r) => [
                'id'   => (int) $r->id,
                'name' => (string) ($r->CategoryName ?? ''),
            ])->values(),
            'units' => $this->lookupUnits()->map(fn ($r) => [
                'id'   => (int) $r->id,
                'name' => (string) ($r->UnitName ?? ''),
            ])->values(),
        ]);
    }

    /**
     * Quick-add category (product form / modal).
     */
    public function storeQuickCategory(Request $request)
    {
        try {
            $validated = $request->validate([
                'CategoryName' => ['required', 'string', 'min:1', 'max:100'],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status'  => 'error',
                'errors'  => $e->errors(),
                'message' => 'Please fix the highlighted fields.',
            ], 422);
        }

        $table = $this->categoryLookupTable();
        if ($table === null) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Category table (tbl_category) not found on this company database. Create it on the tenant DB or run migrations.',
            ], 422);
        }

        $userId = auth()->id();
        $now    = now()->toDateTimeString();
        $nextId = $this->nextTablePrimaryKey($table);

        try {
            DB::table($table)->insert([
                'id'           => $nextId,
                'CategoryName' => trim($validated['CategoryName']),
                'Statusi'      => 1,
                'CreatedBy'    => $userId,
                'UpdateBy'     => $userId,
                'UpdatedOn'    => $now,
            ]);
        } catch (\Exception $e) {
            \Log::error('storeQuickCategory failed', ['e' => $e->getMessage()]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Could not save category.',
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'item'   => [
                'id'   => $nextId,
                'name' => trim($validated['CategoryName']),
            ],
        ]);
    }

    /**
     * Quick-add unit of measure (product form / modal).
     */
    public function storeQuickUnit(Request $request)
    {
        try {
            $validated = $request->validate([
                'UnitName' => ['required', 'string', 'min:1', 'max:100'],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status'  => 'error',
                'errors'  => $e->errors(),
                'message' => 'Please fix the highlighted fields.',
            ], 422);
        }

        $table = $this->unitLookupTable();
        if ($table === null) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unit table (tbl_Unit) not found on this company database. Create it on the tenant DB or run migrations.',
            ], 422);
        }

        $userId = auth()->id();
        $now    = now()->toDateTimeString();
        $nextId = $this->nextTablePrimaryKey($table);

        try {
            DB::table($table)->insert([
                'id'        => $nextId,
                'UnitName'  => trim($validated['UnitName']),
                'Statusi'   => 1,
                'CreatedBy' => $userId,
                'UpdateBy'  => $userId,
                'UpdatedOn' => $now,
            ]);
        } catch (\Exception $e) {
            \Log::error('storeQuickUnit failed', ['e' => $e->getMessage()]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Could not save unit.',
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'item'   => [
                'id'   => $nextId,
                'name' => trim($validated['UnitName']),
            ],
        ]);
    }

    private function lookupCategories()
    {
        $table = $this->categoryLookupTable();
        if ($table === null) {
            return collect();
        }

        try {
            $q = DB::table($table)
                ->where(function ($query) {
                    $query->where('Statusi', 1)->orWhereNull('Statusi');
                });
            if ($this->tableHasColumn($table, 'IsDefault')) {
                $q->orderByDesc('IsDefault');
            }
            $q->orderBy('CategoryName');

            return $q->get();
        } catch (\Throwable $e) {
            \Log::warning('lookupCategories failed', ['e' => $e->getMessage()]);

            return collect();
        }
    }

    private function lookupUnits()
    {
        $table = $this->unitLookupTable();
        if ($table === null) {
            return collect();
        }

        try {
            $q = DB::table($table)
                ->where(function ($query) {
                    $query->where('Statusi', 1)->orWhereNull('Statusi');
                });
            if ($this->tableHasColumn($table, 'IsDefault')) {
                $q->orderByDesc('IsDefault');
            }
            $q->orderBy('UnitName');

            return $q->get();
        } catch (\Throwable $e) {
            \Log::warning('lookupUnits failed', ['e' => $e->getMessage()]);

            return collect();
        }
    }

    private function defaultCategoryId(): ?int
    {
        $table = $this->categoryLookupTable();
        if ($table === null || ! $this->tableHasColumn($table, 'IsDefault')) {
            return null;
        }

        try {
            $row = DB::table($table)
                ->where(function ($query) {
                    $query->where('Statusi', 1)->orWhereNull('Statusi');
                })
                ->where('IsDefault', 1)
                ->orderBy('id')
                ->first(['id']);

            return ($row && isset($row->id)) ? (int) $row->id : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function defaultUnitId(): ?int
    {
        $table = $this->unitLookupTable();
        if ($table === null || ! $this->tableHasColumn($table, 'IsDefault')) {
            return null;
        }

        try {
            $row = DB::table($table)
                ->where(function ($query) {
                    $query->where('Statusi', 1)->orWhereNull('Statusi');
                })
                ->where('IsDefault', 1)
                ->orderBy('id')
                ->first(['id']);

            return ($row && isset($row->id)) ? (int) $row->id : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function tableHasColumn(string $table, string $column): bool
    {
        try {
            return Schema::hasColumn($table, $column);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * First matching physical table name (handles Windows/Linux case differences).
     *
     * @param  list<string>  $candidates
     */
    private function firstExistingTable(array $candidates): ?string
    {
        foreach ($candidates as $name) {
            try {
                if (Schema::hasTable($name)) {
                    return $name;
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        return null;
    }

    private function categoryLookupTable(): ?string
    {
        return $this->firstExistingTable(['tbl_category', 'tbl_Category', 'Tbl_category']);
    }

    private function unitLookupTable(): ?string
    {
        return $this->firstExistingTable(['tbl_Unit', 'tbl_unit', 'Tbl_Unit', 'tbl_units']);
    }

    /**
     * Fill CategoryName / UnitName on the product for read-only views when tbl_product only stores IDs.
     */
    private function hydrateCategoryUnitDisplayNames(ProductModel $product): void
    {
        $attrs = $product->getAttributes();

        $catId = $attrs['CategoryID'] ?? $attrs['categoryid'] ?? $attrs['Category'] ?? $product->CategoryID ?? $product->Category ?? null;
        if ($catId !== null && $catId !== '' && (string) $catId !== '0') {
            $existing = $attrs['CategoryName'] ?? $attrs['categoryname'] ?? $product->CategoryName ?? '';
            if ($existing === null || $existing === '') {
                $table = $this->categoryLookupTable();
                if ($table) {
                    try {
                        $name = DB::table($table)->where('id', $catId)->value('CategoryName');
                        if ($name !== null && $name !== '') {
                            $product->CategoryName = $name;
                        }
                    } catch (\Throwable $e) {
                        \Log::debug('hydrateCategoryUnitDisplayNames category', ['e' => $e->getMessage()]);
                    }
                }
            }
        }

        $unitId = $attrs['Unit'] ?? $attrs['unit'] ?? $product->Unit ?? null;
        if ($unitId !== null && $unitId !== '' && (int) $unitId !== 0) {
            $existingUnit = $attrs['UnitName'] ?? $attrs['unitname'] ?? $product->UnitName ?? '';
            if ($existingUnit === null || $existingUnit === '') {
                $table = $this->unitLookupTable();
                if ($table) {
                    try {
                        $name = DB::table($table)->where('id', $unitId)->value('UnitName');
                        if ($name !== null && $name !== '') {
                            $product->UnitName = $name;
                        }
                    } catch (\Throwable $e) {
                        \Log::debug('hydrateCategoryUnitDisplayNames unit', ['e' => $e->getMessage()]);
                    }
                }
            }
        }
    }

    private function nextTablePrimaryKey(string $table): int
    {
        $max = DB::table($table)->max('id');

        return $max ? (int) $max + 1 : 1;
    }

    /**
     * First non-empty description column from listing row (handles PDO case variants).
     */
    private function productListingDesc(object $row, array $keys): string
    {
        foreach ($keys as $key) {
            $v = data_get($row, $key);
            if ($v !== null && $v !== '') {
                return (string) $v;
            }
        }

        return '—';
    }

    /**
     * Persist low-stock threshold and refresh computed StockStatus (column may be absent on older tenants).
     */
    private function syncProductLowStockLevel(int $productId, Request $request): void
    {
        if ($productId <= 0 || ! Schema::hasColumn('tbl_product', 'LowStockLevel')) {
            if ($productId > 0 && Schema::hasColumn('tbl_product', 'StockStatus')) {
                app(StockService::class)->refreshStockStatus($productId);
            }

            return;
        }

        $level = $request->input('LowStockLevel');
        DB::table('tbl_product')
            ->where('ID', $productId)
            ->update([
                'LowStockLevel' => ($level !== null && $level !== '') ? $level : 0,
            ]);

        if (Schema::hasColumn('tbl_product', 'StockStatus')) {
            app(StockService::class)->refreshStockStatus($productId);
        }
    }

    /**
     * Download Excel import sample template for Products.
     */
    public function downloadImportTemplate()
    {
        // Fetch Distinct Lookup Lists from Master/FBR Database
        $uomList = \Illuminate\Support\Facades\DB::connection('landlord')->table('core_digital_invoicing.fbr_uom')
            ->distinct()
            ->whereNotNull('description')
            ->whereRaw("TRIM(description) <> ''")
            ->orderBy('description')
            ->pluck('description')
            ->toArray();

        $transList = \Illuminate\Support\Facades\DB::connection('landlord')->table('core_digital_invoicing.fbr_trans_type_code')
            ->distinct()
            ->whereNotNull('transactioN_DESC')
            ->whereRaw("TRIM(transactioN_DESC) <> ''")
            ->orderBy('transactioN_DESC')
            ->pluck('transactioN_DESC')
            ->toArray();

        $ratesList = \Illuminate\Support\Facades\DB::connection('landlord')->table('core_digital_invoicing.fbr_sale_type_to_rate')
            ->distinct()
            ->whereNotNull('ratE_DESC')
            ->whereRaw("TRIM(ratE_DESC) <> ''")
            ->orderBy('ratE_DESC')
            ->pluck('ratE_DESC')
            ->toArray();

        $sroList = \Illuminate\Support\Facades\DB::connection('landlord')->table('core_digital_invoicing.fbr_sro_schedule')
            ->distinct()
            ->whereNotNull('srO_DESC')
            ->whereRaw("TRIM(srO_DESC) <> ''")
            ->orderBy('srO_DESC')
            ->pluck('srO_DESC')
            ->toArray();

        $sroItemList = \Illuminate\Support\Facades\DB::connection('landlord')->table('core_digital_invoicing.fbr_sro_item')
            ->distinct()
            ->whereNotNull('srO_ITEM_DESC')
            ->whereRaw("TRIM(srO_ITEM_DESC) <> ''")
            ->orderBy('srO_ITEM_DESC')
            ->pluck('srO_ITEM_DESC')
            ->toArray();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Products Import');

        // Create Hidden Lookup Sheet
        $lookupSheet = $spreadsheet->createSheet();
        $lookupSheet->setTitle('Lookup_Data');
        $lookupSheet->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_VERYHIDDEN);

        $lookupSheet->setCellValue('A1', 'UOM');
        foreach ($uomList as $i => $v) { $lookupSheet->setCellValue('A' . ($i + 2), $v); }
        if (!empty($uomList)) {
            $spreadsheet->addNamedRange(new \PhpOffice\PhpSpreadsheet\NamedRange('LST_UOM_PRD', $lookupSheet, '$A$2:$A$' . (count($uomList) + 1)));
        }

        $lookupSheet->setCellValue('B1', 'SaleType');
        foreach ($transList as $i => $v) { $lookupSheet->setCellValue('B' . ($i + 2), $v); }
        if (!empty($transList)) {
            $spreadsheet->addNamedRange(new \PhpOffice\PhpSpreadsheet\NamedRange('LST_SALETYPE_PRD', $lookupSheet, '$B$2:$B$' . (count($transList) + 1)));
        }

        $lookupSheet->setCellValue('C1', 'Rate');
        foreach ($ratesList as $i => $v) { $lookupSheet->setCellValue('C' . ($i + 2), $v); }
        if (!empty($ratesList)) {
            $spreadsheet->addNamedRange(new \PhpOffice\PhpSpreadsheet\NamedRange('LST_RATES_PRD', $lookupSheet, '$C$2:$C$' . (count($ratesList) + 1)));
        }

        $lookupSheet->setCellValue('D1', 'SroSchedule');
        foreach ($sroList as $i => $v) { $lookupSheet->setCellValue('D' . ($i + 2), $v); }
        if (!empty($sroList)) {
            $spreadsheet->addNamedRange(new \PhpOffice\PhpSpreadsheet\NamedRange('LST_SRO_PRD', $lookupSheet, '$D$2:$D$' . (count($sroList) + 1)));
        }

        $lookupSheet->setCellValue('E1', 'SroItem');
        foreach ($sroItemList as $i => $v) { $lookupSheet->setCellValue('E' . ($i + 2), $v); }
        if (!empty($sroItemList)) {
            $spreadsheet->addNamedRange(new \PhpOffice\PhpSpreadsheet\NamedRange('LST_SROITEM_PRD', $lookupSheet, '$E$2:$E$' . (count($sroItemList) + 1)));
        }

        // Headers
        $headers = [
            'Product Code *',
            'Product Name *',
            'Product Type',
            'Sale Price *',
            'Fixed Notified Value (TP Rate)',
            'HS Code',
            'UOM',
            'Sale Type',
            'Sale Type Rate',
            'SRO Schedule',
            'SRO Item Serial No',
            'Product Description'
        ];

        $sheet->fromArray([$headers], NULL, 'A1');

        // Base header styling (Dark Teal background)
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '136056']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => 'D1D5DB']]]
        ];
        $sheet->getStyle('A1:L1')->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(28);

        // Highlight Required Fields
        $requiredStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => '713F12'], 'size' => 11],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEF08A']],
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM, 'color' => ['rgb' => 'EAB308']]]
        ];
        $sheet->getStyle('A1')->applyFromArray($requiredStyle); // Product Code *
        $sheet->getStyle('B1')->applyFromArray($requiredStyle); // Product Name *
        $sheet->getStyle('D1')->applyFromArray($requiredStyle); // Sale Price *

        // Format text/code columns and numeric columns for rows 2 to 500
        $sheet->getStyle('A2:C500')->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);
        $sheet->getStyle('F2:L500')->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);
        $sheet->getStyle('D2:E500')->getNumberFormat()->setFormatCode('0.00'); // SalePrice, TPRate

        // Sample Data Rows
        $sampleData = [
            [
                'PRD-000001', 'Paracetamol 500mg Tablets', 'Product', 150.00, 0.00, 
                '3004.9099', 'Numbers, pieces, units', 'Goods at standard rate (default)', '18%', 
                '', '', 'Pain relief paracetamol tablets 500mg blister pack'
            ],
            [
                'PRD-000002', 'Consultation & Technical Services', 'Services', 2500.00, 0.00, 
                '9815.9000', 'Numbers, pieces, units', 'Services at Standard Rate', '16%', 
                '', '', 'Professional consultation service hourly rate'
            ],
            [
                'PRD-000003', 'Industrial Raw Sugar (Bag 50kg)', 'Product', 6500.00, 120.00, 
                '1701.9910', 'KG', 'Goods at Reduced Rate', '1%', 
                'EIGHTH SCHEDULE Table 1', '70', 'Refined industrial white sugar 50kg bag'
            ]
        ];

        foreach ($sampleData as $rowIndex => $rowValues) {
            $r = $rowIndex + 2;
            foreach ($rowValues as $colIndex => $val) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1);
                if (in_array($colLetter, ['D', 'E']) && is_numeric($val) && $val !== '') {
                    $sheet->setCellValue("{$colLetter}{$r}", (float)$val);
                } else {
                    $sheet->setCellValueExplicit("{$colLetter}{$r}", (string)$val, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                }
            }
        }

        // Data Validation Dropdowns (Rows 2 to 500)
        for ($row = 2; $row <= 500; $row++) {
            // Col C: Product Type
            $valType = $sheet->getCell("C{$row}")->getDataValidation();
            $valType->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
            $valType->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
            $valType->setAllowBlank(true);
            $valType->setShowDropDown(true);
            $valType->setFormula1('"Product,Services"');

            // Col G: UOM
            if (!empty($uomList)) {
                $valUom = $sheet->getCell("G{$row}")->getDataValidation();
                $valUom->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                $valUom->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
                $valUom->setAllowBlank(true);
                $valUom->setShowDropDown(true);
                $valUom->setFormula1('LST_UOM_PRD');
            }

            // Col H: Sale Type
            if (!empty($transList)) {
                $valSaleType = $sheet->getCell("H{$row}")->getDataValidation();
                $valSaleType->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                $valSaleType->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
                $valSaleType->setAllowBlank(true);
                $valSaleType->setShowDropDown(true);
                $valSaleType->setFormula1('LST_SALETYPE_PRD');
            }

            // Col I: Sale Type Rate
            if (!empty($ratesList)) {
                $valRate = $sheet->getCell("I{$row}")->getDataValidation();
                $valRate->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                $valRate->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
                $valRate->setAllowBlank(true);
                $valRate->setShowDropDown(true);
                $valRate->setFormula1('LST_RATES_PRD');
            }

            // Col J: SRO Schedule
            if (!empty($sroList)) {
                $valSro = $sheet->getCell("J{$row}")->getDataValidation();
                $valSro->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                $valSro->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
                $valSro->setAllowBlank(true);
                $valSro->setShowDropDown(true);
                $valSro->setFormula1('LST_SRO_PRD');
            }

            // Col K: SRO Item Serial No
            if (!empty($sroItemList)) {
                $valSroItem = $sheet->getCell("K{$row}")->getDataValidation();
                $valSroItem->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                $valSroItem->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
                $valSroItem->setAllowBlank(true);
                $valSroItem->setShowDropDown(true);
                $valSroItem->setFormula1('LST_SROITEM_PRD');
            }
        }

        // Auto size columns
        foreach (range('A', 'L') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'Product_Import_Template.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Import products from Excel or CSV
     */
    public function importProducts(Request $request)
    {
        $request->validate([
            'import_file' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240',
        ], [
            'import_file.required' => 'Please select an Excel or CSV file to import.',
            'import_file.mimes'    => 'Import file must be an Excel workbook (.xlsx, .xls) or CSV file (.csv).',
            'import_file.max'      => 'Import file must not exceed 10 MB.',
        ]);

        try {
            $requestId = (string) Str::uuid();
            $userId = auth()->id() ?? 1;
            $now = now()->toDateTimeString();

            $import = new \App\Imports\ProductImport();
            \Maatwebsite\Excel\Facades\Excel::import($import, $request->file('import_file'));
            $rows = $import->mappedRows();

            if (empty($rows)) {
                $msg = 'No data rows found in the import file.';
                if ($request->expectsJson()) {
                    return response()->json(['status' => 'error', 'message' => $msg], 422);
                }
                return redirect()->back()->with('error', $msg);
            }

            // Fetch current maximum ID in temp_product and tbl_product to assign sequential IDs
            $maxId = (int) (DB::table('temp_product')->max('ID') ?? 0);
            $maxProdId = (int) (DB::table('tbl_product')->max('ID') ?? 0);
            $currentId = max($maxId, $maxProdId);

            $insertRows = [];
            foreach ($rows as $index => $row) {
                $currentId++;
                $code = trim((string)($row['Code'] ?? ''));
                if ($code === '') {
                    $code = 'PRD-' . str_pad((string)$currentId, 6, '0', STR_PAD_LEFT);
                }

                $tpRate = (float)($row['TPRate'] ?? 0);
                $isFixedRate = ($tpRate > 1) ? 1 : 0;

                $insertRows[] = [
                    'ID'                 => $currentId,
                    'TID'                => $currentId,
                    'ExcelRow'           => $row['RowNo'] ?? ($index + 2),
                    'VoucherID'          => 0,
                    'Name'               => $row['Name'] ?? null,
                    'Code'               => $code,
                    'Type'               => $row['Type'] ?? 'Product',
                    'CategoryID'         => 1,
                    'CurrentQuantity'    => 0.0,
                    'Unit'               => 1,
                    'SalePrice'          => (float)($row['SalePrice'] ?? 0),
                    'Cost'               => (float)($row['Cost'] ?? 0),
                    'TPRate'             => $tpRate,
                    'IsFixedRate'        => $isFixedRate,
                    'Weight'             => (float)($row['Weight'] ?? 0),
                    'Image'              => null,
                    'HsCode'             => $row['HsCode'] ?? null,
                    'UOM'                => $row['UOM'] ?? null,
                    'Uom_Desc'           => $row['Uom_Desc'] ?? null,
                    'SaleType'           => $row['SaleType'] ?? null,
                    'SaleTypeDesc'       => $row['SaleTypeDesc'] ?? null,
                    'SaleTypeToRate'     => $row['SaleTypeToRate'] ?? null,
                    'SaleTypeToRateDesc' => $row['SaleTypeToRateDesc'] ?? null,
                    'SroSchedule'        => $row['SroSchedule'] ?? null,
                    'SroScheduleDesc'    => $row['SroScheduleDesc'] ?? null,
                    'SroItem'            => $row['SroItem'] ?? null,
                    'SroItemSerialNo'    => $row['SroItemSerialNo'] ?? null,
                    'SaleInformation'    => $row['SaleInformation'] ?? ($row['Name'] ?? $code),
                    'Status'             => 1,
                    'CreatedBy'          => (int)$userId,
                    'CreatedOn'          => $now,
                    'UpdatedBy'          => (int)$userId,
                    'UpdatedOn'          => $now,
                    'ErrMsg'             => null,
                    'Flag'               => 0,
                    'RequestID'          => $requestId,
                ];
            }

            foreach (array_chunk($insertRows, 200) as $chunk) {
                DB::table('temp_product')->insert($chunk);
            }

            $saveResult = ProductModel::save_transaction(['RequestID' => $requestId]);
            if (is_array($saveResult) && ($saveResult['status'] ?? false) === false) {
                $errorMsg = $saveResult['message'] ?? 'Unable to import products.';
                if ($request->expectsJson()) {
                    return response()->json([
                        'status' => 'error', 
                        'message' => $errorMsg, 
                        'errors' => $saveResult['errors'] ?? [$errorMsg]
                    ], 422);
                }
                return redirect()->back()->with('error', $errorMsg);
            }

            $successMsg = count($insertRows).' Product(s) imported successfully!';
            if ($request->expectsJson()) {
                return response()->json(['status' => 'success', 'message' => $successMsg]);
            }

            return redirect()->route('products.index')->with('success', $successMsg);
        } catch (\Throwable $e) {
            \Log::error('Product import failed', ['error' => $e->getMessage()]);
            $err = 'Import failed: '.$e->getMessage();
            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => $err], 500);
            }
            return redirect()->back()->with('error', $err);
        }
    }
}
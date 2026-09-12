<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    protected function routePrefix(): string
    {
        return 'customers';
    }

    protected function listView(): string
    {
        return 'pages.dashboards.Customer.customers-list';
    }

    protected function formView(): string
    {
        return 'pages.dashboards.Customer.customers-form';
    }

    protected function partialView(): string
    {
        return 'pages.dashboards.Customer._customer-form-partial';
    }

    /** @return list<int> */
    protected function partyTypesForList(): array
    {
        return [Customer::PARTY_TYPE_CUSTOMER, Customer::PARTY_TYPE_BOTH];
    }

    protected function partyTypeForCreate(): int
    {
        return Customer::PARTY_TYPE_CUSTOMER;
    }

    protected function includeNullPartyTypeInList(): bool
    {
        return true;
    }

    protected function accountNoPrefix(): string
    {
        return 'CUST';
    }

    protected function entitySingular(): string
    {
        return 'Customer';
    }

    protected function listVariableName(): string
    {
        return 'customers';
    }

    protected function exportFilenamePrefix(): string
    {
        return 'customers-export';
    }

    protected function listingFilters(Request $request): array
    {
        return array_merge([
            'search' => $request->input('search'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'party_types' => $this->partyTypesForList(),
            'party_include_null' => $this->includeNullPartyTypeInList(),
        ], $this->extraListingFilters($request));
    }

    /** @return array<string, mixed> */
    protected function extraListingFilters(Request $request): array
    {
        return [];
    }

    protected function partyRoute(string $name, mixed $parameters = []): string
    {
        return route($this->routePrefix().'.'.$name, $parameters);
    }

    protected function findPartyRecord(int|string $id): ?object
    {
        $query = DB::table('tbl_customer')->where('ID', $id);
        $types = $this->partyTypesForList();
        if ($this->includeNullPartyTypeInList()) {
            $query->where(function ($q) use ($types) {
                $q->whereIn('PartyType', $types)->orWhereNull('PartyType');
            });
        } else {
            $query->whereIn('PartyType', $types);
        }

        return $query->first();
    }

    /**
     * Display a listing of customers
     */
    public function index(Request $request)
    {
        $filters = $this->listingFilters($request);

        // Validation
        $rules = [];
        $messages = [];

        // Date rules
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $rules['start_date'] = 'date';
            $rules['end_date'] = 'date|after_or_equal:start_date';
            $messages['end_date.after_or_equal'] = 'End date must be after or equal to start date.';
        }

        // Search validation
        if ($request->filled('search')) {
            $rules['search'] = 'string|max:100|regex:/^[a-zA-Z0-9\s\-\.\,\@]+$/';
            $messages['search.regex'] = 'Search term contains invalid characters.';
            $messages['search.max'] = 'Search term is too long (max 100 chars).';
        }

        if (!empty($rules)) {
            $request->validate($rules, $messages);
        }

        $customers = Customer::getlistingdata($filters)->paginate(15);
        $listKey = $this->listVariableName();

        return view($this->listView(), [$listKey => $customers]);
    }
    
    /**
     * Create form
     */
    public function create()
    {
        return view($this->formView(), [
            'mode' => 'create',
            'customer' => new Customer(),
            'provinces' => $this->provinceOptions(),
        ]);
    }
    
    /**
     * Store customer
     */
    public function save_transaction(Request $request)
    {
        // Format inputs (empty optional fields → null for validation)
        $firstName = trim($request->FirstName ?? '');
        $lastName  = trim($request->LastName ?? '');
        $email     = strtolower(trim($request->Email ?? ''));
        $mobile    = preg_replace('/[^0-9+]/', '', $request->Mobile ?? '');
        $request->merge([
            'Title'     => $request->filled('Title') && $request->Title !== '' ? $request->Title : null,
            'FirstName' => $firstName === '' ? null : $firstName,
            'LastName'  => $lastName === '' ? null : $lastName,
            'Email'     => $email === '' ? null : $email,
            'Mobile'    => $mobile === '' ? null : $mobile,
            'CNIC'      => trim($request->CNIC ?? '') === '' ? null : trim($request->CNIC ?? ''),
            'NTN'       => trim($request->NTN ?? '') === '' ? null : trim($request->NTN ?? ''),
            'Phone'     => $request->Phone ? preg_replace('/[^0-9+]/', '', $request->Phone) : null,
        ]);
        
        // Handle Billing Address
        if ($request->has('SameAsBilling') && $request->SameAsBilling == '1') {
            $request->merge([
                'BillingAddress' => $request->Address,
                'BillingCity' => $request->City,
                'BillingCountry' => $request->Country,
                'BillingCounty' => $request->County,
                'BillingPostalCode' => $request->PostalCode,
            ]);
        }

        if ((int) $request->input('IsFiler', 0) === 1) {
            $request->merge([
                'SalesTaxWithheldAtSource' => null,
                'ExtraTax' => '0.00',
                'FurtherTax' => '0.00',
                'FedPayable' => '0.00',
            ]);
        }

        $request->merge([
            'IsAdvancedTax' => $request->boolean('IsAdvancedTax') ? 1 : 0,
        ]);
        // ── Validate ──────────────────────────────────────────
try {
    $validated = $request->validate([
        'Title'             => ['nullable', 'string', Rule::in(['Mr', 'Mrs', 'Miss', 'Ms', 'Dr', 'Prof', 'Engr'])],
        'FirstName'         => ['nullable', 'string', 'min:2', 'max:100', 'regex:/^[a-zA-Z\s\'-]+$/'],
        'LastName'          => ['nullable', 'string', 'min:2', 'max:100', 'regex:/^[a-zA-Z\s\'-]+$/'],
        'BusinessName'      => ['nullable', 'string', 'max:200', 'regex:/^[a-zA-Z0-9\s&.,\'()-]+$/'],
        'DisplayName'       => 'nullable|string|max:200',
        'Email'             => ['nullable', 'email', 'max:500', Rule::unique('tbl_customer', 'Email')],
        'Mobile'            => ['nullable', 'string', 'regex:/^(\+92|0)?[0-9]{10}$/', Rule::unique('tbl_customer', 'Mobile')],
        'Phone'             => ['nullable', 'string', 'regex:/^(\+92|0)?[0-9]{9,11}$/'],
        'Website'           => ['nullable', 'url', 'max:200', 'regex:/^https?:\/\//'],
        'Address'           => 'nullable|string|max:500',
        'City'              => ['nullable', 'string', 'max:100', 'regex:/^[a-zA-Z\s]+$/'],
        'County'            => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z\s]+$/'],
        'PostalCode'        => ['nullable', 'string', 'regex:/^[0-9]{5}$/'],
        'Country'           => 'nullable|string|max:100',
        'BillingAddress'    => 'nullable|string|max:500',
        'BillingCity'       => ['nullable', 'string', 'max:100', 'regex:/^[a-zA-Z\s]+$/'],
        'BillingCounty'     => ['required_if:SameAsBilling,0', 'nullable', 'string', 'max:100', 'regex:/^[a-zA-Z\s]+$/'],
        'BillingPostalCode' => ['nullable', 'string', 'regex:/^[0-9]{5}$/'],
        'BillingCountry'    => 'nullable|string|max:100',
        'SameAsBilling'     => 'nullable|boolean',
        'NTN'               => 'nullable|string|max:50',
        'SalesTaxNumber'    => ['nullable', 'string', 'max:50', 'regex:/^[A-Z0-9\-]{5,50}$/', 'unique:tbl_customer,SalesTaxNumber'],
        'IsFiler'           => 'nullable|boolean',
        'RegisterType'      => 'nullable|string|max:25',
        'FilerStatus'       => 'nullable|string|max:25',
        'FilerStatusDate'   => 'nullable|date',
        'IsAdvancedTax'     => 'nullable|boolean',
        'AdvanceTaxRate'    => $this->advanceTaxRateValidationRules(),
        'CNIC'              => ['nullable', 'string', 'regex:/^[0-9]{5}-[0-9]{7}-[0-9]$/', Rule::unique('tbl_customer', 'CNIC')],
        'CreditLimit'       => 'nullable|numeric|min:0|max:999999999999.99',
        'SalesTaxWithheldAtSource' => 'nullable|string|max:50',
        'ExtraTax'          => 'nullable|numeric|min:0|max:999.99',
        'FurtherTax'        => 'nullable|numeric|min:0|max:999.99',
        'FedPayable'        => 'nullable|numeric|min:0|max:999.99',
        'Notes'             => 'nullable|string|max:500',
        'Status'            => 'nullable|integer|in:0,1',
    ], [
        'Title.in'                     => 'Invalid title selected.',
        'FirstName.min'                => 'First name must be at least 2 characters.',
        'FirstName.regex'              => 'First name can only contain letters, spaces, hyphens and apostrophes.',
        'LastName.min'                 => 'Last name must be at least 2 characters.',
        'LastName.regex'               => 'Last name can only contain letters, spaces, hyphens and apostrophes.',
        'BusinessName.regex'           => 'Business name contains invalid characters.',
        'Email.email'                  => 'Please enter a valid email address.',
        'Email.unique'                 => 'This email address is already registered.',
        'Mobile.regex'                 => 'Mobile must be Pakistan format (e.g., 03001234567).',
        'Mobile.unique'                => 'This mobile number is already registered.',
        'Phone.regex'                  => 'Phone number must be in Pakistan format.',
        'Website.url'                  => 'Please enter a valid website URL.',
        'Website.regex'                => 'Website must start with http:// or https://',
        'County.required'              => 'State / Province is required.',
        'City.regex'                   => 'City name can only contain letters and spaces.',
        'County.regex'                 => 'State/Province can only contain letters and spaces.',
        'PostalCode.regex'             => 'Postal code must be exactly 5 digits.',
        'BillingCounty.required_if'    => 'Billing state/province is required.',
        'BillingCity.regex'            => 'Billing city can only contain letters and spaces.',
        'BillingCounty.regex'          => 'Billing state/province can only contain letters and spaces.',
        'BillingPostalCode.regex'      => 'Billing postal code must be exactly 5 digits.',
        'SalesTaxNumber.regex'         => 'Sales Tax Number format is invalid.',
        'SalesTaxNumber.unique'        => 'This Sales Tax Number is already registered.',
        'CNIC.regex'                   => 'CNIC must be in format: 12345-1234567-1',
        'CNIC.unique'                  => 'This CNIC is already registered.',
        'AdvanceTaxRate.required_if'   => 'Advance tax rate is required when advanced tax is enabled.',
        'AdvanceTaxRate.gt'            => 'Advance tax rate must be greater than 0.00.',
        'CreditLimit.numeric'          => 'Credit limit must be a valid number.',
        'CreditLimit.min'              => 'Credit limit cannot be negative.',
        'Status.in'                    => 'Invalid status value.',
    ]);
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
            $userId = auth()->id() ?? 1;
            $now = now()->toDateTimeString(); // SQL format: YYYY-MM-DD HH:MM:SS
            
            $displayName = $request->DisplayName ?: $request->BusinessName;
            $fullBillingAddress = $request->BillingAddress . ', ' . $request->BillingCity . ', ' . $request->BillingCountry;

            // Advanced tax → temp_customer (Add Customer modal + standalone create)
            $isAdvancedTax = (int) $request->input('IsAdvancedTax', 0) === 1 ? 1 : 0;
            $advanceTaxRate = $isAdvancedTax === 1
                ? round((float) $request->input('AdvanceTaxRate', 0), 2)
                : 0.00;

            // Calculate ID
            $maxId = DB::table('temp_customer')->max('ID');
            $nextId = $maxId ? $maxId + 1 : 1;

            // Prepare data strictly matching new temp_customer structure
            $data = [
                'ID'                => $nextId,
                'TID'               => $nextId,
                'IsArchive'         => 0,
                'VoucherID'         => 0,
                'AccountNo'         => $this->accountNoPrefix().'-'.str_pad((string) $nextId, 6, '0', STR_PAD_LEFT),
                'PartyType'         => $this->partyTypeForCreate(),
                'Title'             => $request->Title,
                'FirstName'         => $request->FirstName,
                'LastName'          => $request->LastName,
                'BusinessName'      => $request->BusinessName,
                'DisplayName'       => $displayName,
                'Email'             => $request->Email,
                'Mobile'            => $request->Mobile,
                'Phone'             => $request->Phone ?? '',
                'Website'           => $request->Website ?? '',
                'Address'           => $request->Address,
                'PartyBalance'      => 0.00,
                'City'              => $request->City,
                'County'            => $request->County,
                'PostalCode'        => $request->PostalCode,
                'Country'           => $request->Country,
                'SameAsBilling'     => $request->input('SameAsBilling', 0),
                'BillingAddress'    => $request->BillingAddress,
                'BillingCity'       => $request->BillingCity,
                'BillingCounty'     => $request->BillingCounty,
                'BillingCountry'    => $request->BillingCountry,
                'BillingPostalCode' => $request->BillingPostalCode,
                'FullBillingAddress'=> $fullBillingAddress,
                'NTN'               => $request->NTN,
                'SalesTaxNumber'    => $request->SalesTaxNumber ?? '',
                'CNIC'              => $request->CNIC,
                'IsFiler'           => $request->input('IsFiler', 0),
                'RegisterType'      => $this->nullableFbrField($request->input('RegisterType')),
                'FilerStatus'       => $this->nullableFbrField($request->input('FilerStatus')),
                'FilerStatusDate'   => $this->nullableFbrDate($request->input('FilerStatusDate')),
                'IsAdvanceTax'     => $isAdvancedTax,
                'AdvanceTax'       => $advanceTaxRate,
                'Notes'             => $request->Notes,
                'SalesTaxWithheldAtSource' => $request->input('SalesTaxWithheldAtSource'),
                'ExtraTax'          => (float) ($request->input('ExtraTax', 0) ?: 0),
                'FurtherTax'        => (float) ($request->input('FurtherTax', 0) ?: 0),
                'FedPayable'        => (float) ($request->input('FedPayable', 0) ?: 0),
                'Status'            => (int) $request->input('Status', 1),
                'CreatedBy'         => (int)$userId,
                'CreatedOn'         => $now,
                'UpdatedBy'         => (int)$userId,
                'UpdatedOn'         => $now,
                'IsDashboard'       => 0,
                'RequestID'         => $requestId,
            ];

            // Insert into temp_customer (staging before Customer::save_transaction → tbl_customer)
            \Log::info('Inserting Customer Data', ['data' => $data]);
            DB::table('temp_customer')->insert($data);

            // Execute model transaction (runs query inside model)
            $saveResult = Customer::save_transaction(['RequestID' => $requestId]);
            if (is_array($saveResult) && ($saveResult['status'] ?? false) === false) {
                $message = $saveResult['message'] ?? 'Unable to add '.$this->entitySingular().'.';
                if ($request->header('X-Requested-With') === 'XMLHttpRequest') {
                    return response()->json(['status' => 'error', 'message' => $message], 422);
                }

                return redirect()->back()->with('error', $message)->withInput();
            }

            if ($request->header('X-Requested-With') === 'XMLHttpRequest' || $request->expectsJson()) {
                $newId = DB::table('tbl_customer')->where('AccountNo', $data['AccountNo'])->value('ID');
                $custRow = $newId ? DB::table('tbl_customer')->where('ID', $newId)->first() : null;
                $payload = ['status' => 'success', 'message' => $this->entitySingular().' created successfully!'];
                if ($custRow) {
                    $label = trim($custRow->BusinessName ?? '')
                        ?: trim($custRow->DisplayName ?? '')
                        ?: trim(trim(($custRow->FirstName ?? '') . ' ' . ($custRow->LastName ?? '')))
                        ?: ('Customer #' . $custRow->ID);
                    $payload['customer'] = [
                        'id'                   => (int) $custRow->ID,
                        'label'                => $label,
                        'is_filer'             => (int) ($custRow->IsFiler ?? 0) ? 1 : 0,
                        'sales_tax_withheld'   => $custRow->SalesTaxWithheldAtSource ?? 0,
                        'extra_tax'            => $custRow->ExtraTax ?? 0,
                        'further_tax'          => $custRow->FurtherTax ?? 0,
                        'fed_payable'          => $custRow->FedPayable ?? 0,
                        'is_advance_tax'       => (int) ($custRow->IsAdvanceTax ?? 0) === 1 ? 1 : 0,
                        'advance_tax'          => (float) ($custRow->AdvanceTax ?? 0),
                    ];
                }

                return response()->json($payload);
            }
        return redirect()->to($this->partyRoute('index'))->with('success', $this->entitySingular().' created successfully!');

        } catch (\Exception $e) {
           if ($request->header('X-Requested-With') === 'XMLHttpRequest') {
    return response()->json(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()], 500);
}
return redirect()->back()->with('error', 'Error: ' . $e->getMessage())->withInput();
        }

    }
    
    /**
     * Show customer
     */
    public function show($id)
    {
        $customer = $this->findPartyRecord($id);

        if (! $customer) {
            abort(404, $this->entitySingular().' not found in CRM');
        }

        $customerModel = new Customer();
        $customerModel->setRawAttributes((array) $customer, true);
        $customerModel->exists = true;

        return view($this->formView(), [
            'mode' => 'show',
            'customer' => $customerModel,
            'provinces' => $this->provinceOptions(),
        ]);
    }
    
    /**
     * Edit form
     */
    public function edit($id)
    {
        $customer = $this->findPartyRecord($id);

        if (! $customer) {
            abort(404, $this->entitySingular().' not found in CRM');
        }

        $customerModel = new Customer();
        $customerModel->setRawAttributes((array) $customer, true);
        $customerModel->exists = true;

        return view($this->formView(), [
            'mode' => 'edit',
            'customer' => $customerModel,
            'provinces' => $this->provinceOptions(),
        ]);
    }

    /**
 * Load any mode into modal via AJAX
 */
public function modal(Request $request, $id = null)
{
    $mode        = $request->query('mode', 'create');
    $module      = $this->routePrefix() === 'suppliers' ? 'Suppliers' : 'Customers';
    $permissions = session('selected_tenant.permissions', []);

    if ($mode === 'edit' && (int)($permissions[$module]['Modify'] ?? 0) !== 1) {
        if ($id && (int)($permissions[$module]['ViewOnly'] ?? 0) === 1) {
            $mode = 'show';
        } else {
            return response('<div class="alert alert-danger m-3">Access Denied: You do not have permission to edit '.$this->entitySingular().'.</div>', 403);
        }
    }
    if ($mode === 'create' && (int)($permissions[$module]['Add'] ?? 0) !== 1) {
        return response('<div class="alert alert-danger m-3">Access Denied: You do not have permission to add '.$this->entitySingular().'.</div>', 403);
    }
    if ($mode === 'show' && (int)($permissions[$module]['ViewOnly'] ?? 0) !== 1) {
        return response('<div class="alert alert-danger m-3">Access Denied: You do not have permission to view '.$this->entitySingular().'.</div>', 403);
    }

    $customer = null;

    if ($id && in_array($mode, ['edit', 'show'])) {
        $customer = $this->findPartyRecord($id);

        if (! $customer) {
            return response('<div class="alert alert-danger m-3">'.$this->entitySingular().' not found.</div>', 404);
        }

        $customerModel = new Customer();
        $customerModel->setRawAttributes((array) $customer, true);
        $customerModel->exists = true;
        $customer = $customerModel;
    }

    return response()
        ->view($this->partialView(), [
            'mode'     => $mode,
            'customer' => $customer ?? new Customer(),
            'inModal'  => true,
            'provinces'=> $this->provinceOptions(),
        ])
        ->header('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate')
        ->header('Pragma', 'no-cache')
        ->header('Expires', '0');
}

    /**
     * AJAX: verify customer NTN with FBR (registration type + ATL status).
     */
    public function verifyFbr(Request $request)
    {
        $request->validate([
            'ntn' => 'required|string|max:50',
            'filer_status_date' => 'nullable|date',
        ]);

        $result = Customer::verify_fbr(
            (string) $request->input('ntn'),
            $request->input('filer_status_date')
        );

        if (! ($result['success'] ?? false)) {
            return response()->json([
                'status' => 'error',
                'message' => $result['message'] ?? 'FBR verification failed.',
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => $result['message'] ?? 'FBR verification completed.',
            'register_type' => $result['register_type'] ?? null,
            'filer_status' => $result['filer_status'] ?? null,
            'filer_status_date' => $result['filer_status_date'] ?? null,
            'is_filer' => $result['is_filer'] ?? null,
            'registration_no' => $result['registration_no'] ?? null,
        ]);
    }

    /**
     * When Advanced Tax is off, skip rate rules (form may still post 0.00 from UI).
     *
     * @return list<\Illuminate\Contracts\Validation\ValidationRule|string>
     */
    private function advanceTaxRateValidationRules(): array
    {
        return [
            'nullable',
            Rule::excludeIf(fn () => ! request()->boolean('IsAdvancedTax')),
            'required_if:IsAdvancedTax,1',
            'numeric',
            'gt:0',
        ];
    }

    private function nullableFbrField(mixed $value): ?string
    {
        $s = trim((string) ($value ?? ''));

        return $s === '' ? null : $s;
    }

    private function nullableFbrDate(mixed $value): ?string
    {
        $s = trim((string) ($value ?? ''));
        if ($s === '') {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($s)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Province dropdown options from core_digital_invoicing.fbr_province.
     *
     * @return \Illuminate\Support\Collection<int, string>
     */
    private function provinceOptions()
    {
        try {
            return DB::connection('landlord')
                ->table('core_digital_invoicing.fbr_province')
                ->whereNotNull('stateProvinceDesc')
                ->select('stateProvinceDesc')
                ->orderBy('stateProvinceDesc')
                ->pluck('stateProvinceDesc')
                ->map(fn ($v) => trim((string) $v))
                ->filter(fn ($v) => $v !== '')
                ->unique()
                ->values();
        } catch (\Throwable $e) {
            \Log::warning('provinceOptions load failed', ['error' => $e->getMessage()]);
            return collect(['Punjab', 'Sindh', 'Balochistan', 'KPK', 'Gilgit Baltistan', 'Islamabad', 'AJK']);
        }
    }
    
    /**
     * Update customer
     */
    public function edit_transaction(Request $request, $id)
    {
        $customer = $this->findPartyRecord($id);

        if (! $customer) {
            return redirect()->back()->with('error', $this->entitySingular().' not found.');
        }
        
        // Format inputs (empty optional fields → null for validation)
        $firstName = trim($request->FirstName ?? '');
        $lastName  = trim($request->LastName ?? '');
        $email     = strtolower(trim($request->Email ?? ''));
        $mobile    = preg_replace('/[^0-9+]/', '', $request->Mobile ?? '');
        $request->merge([
            'Title'     => $request->filled('Title') && $request->Title !== '' ? $request->Title : null,
            'FirstName' => $firstName === '' ? null : $firstName,
            'LastName'  => $lastName === '' ? null : $lastName,
            'Email'     => $email === '' ? null : $email,
            'Mobile'    => $mobile === '' ? null : $mobile,
            'CNIC'      => trim($request->CNIC ?? '') === '' ? null : trim($request->CNIC ?? ''),
            'NTN'       => trim($request->NTN ?? '') === '' ? null : trim($request->NTN ?? ''),
            'Phone'     => $request->Phone ? preg_replace('/[^0-9+]/', '', $request->Phone) : null,
        ]);
    
        // Handle Billing Address
        if ($request->has('SameAsBilling') && $request->SameAsBilling == '1') {
            $request->merge([
                'BillingAddress' => $request->Address,
                'BillingCity' => $request->City,
                'BillingCountry' => $request->Country,
                'BillingCounty' => $request->County,
                'BillingPostalCode' => $request->PostalCode,
            ]);
        }

        if ((int) $request->input('IsFiler', 0) === 1) {
            $request->merge([
                'SalesTaxWithheldAtSource' => null,
                'ExtraTax' => '0.00',
                'FurtherTax' => '0.00',
                'FedPayable' => '0.00',
            ]);
        }

        $request->merge([
            'IsAdvancedTax' => $request->boolean('IsAdvancedTax') ? 1 : 0,
        ]);
     // ── Validate ──────────────────────────────────────────
try {
    $validated = $request->validate([
        'Title'             => ['nullable', 'string', Rule::in(['Mr', 'Mrs', 'Miss', 'Ms', 'Dr', 'Prof', 'Engr'])],
        'FirstName'         => ['nullable', 'string', 'min:2', 'max:100', 'regex:/^[a-zA-Z\s\'-]+$/'],
        'LastName'          => ['nullable', 'string', 'min:2', 'max:100', 'regex:/^[a-zA-Z\s\'-]+$/'],
        'BusinessName'      => ['nullable', 'string', 'max:200', 'regex:/^[a-zA-Z0-9\s&.,\'()-]+$/'],
        'DisplayName'       => 'nullable|string|max:200',
        'Email'             => ['nullable', 'email', 'max:500', Rule::unique('tbl_customer', 'Email')->ignore($id, 'ID')],
        'Mobile'            => ['nullable', 'string', 'regex:/^(\+92|0)?[0-9]{10}$/', Rule::unique('tbl_customer', 'Mobile')->ignore($id, 'ID')],
        'Phone'             => ['nullable', 'string', 'regex:/^(\+92|0)?[0-9]{9,11}$/'],
        'Website'           => ['nullable', 'url', 'max:200', 'regex:/^https?:\/\//'],
        'Address'           => 'nullable|string|max:500',
        'City'              => ['nullable', 'string', 'max:100', 'regex:/^[a-zA-Z\s]+$/'],
        'County'            => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z\s]+$/'],
        'PostalCode'        => ['nullable', 'string', 'regex:/^[0-9]{5}$/'],
        'Country'           => 'nullable|string|max:100',
        'BillingAddress'    => 'nullable|string|max:500',
        'BillingCity'       => ['nullable', 'string', 'max:100', 'regex:/^[a-zA-Z\s]+$/'],
        'BillingCounty'     => ['required_if:SameAsBilling,0', 'nullable', 'string', 'max:100', 'regex:/^[a-zA-Z\s]+$/'],
        'BillingPostalCode' => ['nullable', 'string', 'regex:/^[0-9]{5}$/'],
        'BillingCountry'    => 'nullable|string|max:100',
        'SameAsBilling'     => 'nullable|boolean',
        'NTN'               => 'nullable|string|max:50',
        'SalesTaxNumber'    => [
            'nullable',
            'string',
            'max:50',
            'regex:/^[A-Z0-9\-]{5,50}$/',
            Rule::unique('tbl_customer', 'SalesTaxNumber')->where('Status', 1)->ignore((int) $id, 'ID'),
        ],
        'IsFiler'           => 'nullable|boolean',
        'RegisterType'      => 'nullable|string|max:25',
        'FilerStatus'       => 'nullable|string|max:25',
        'FilerStatusDate'   => 'nullable|date',
        'IsAdvancedTax'     => 'nullable|boolean',
        'AdvanceTaxRate'    => $this->advanceTaxRateValidationRules(),
        'CNIC'              => ['nullable', 'string', 'regex:/^[0-9]{5}-[0-9]{7}-[0-9]$/', Rule::unique('tbl_customer', 'CNIC')->ignore($id, 'ID')],
        'CreditLimit'       => 'nullable|numeric|min:0|max:999999999999.99',
        'PaymentTerms'      => 'nullable|string|in:Cash,Net 15,Net 30,Net 45,Net 60,Due on Receipt,COD',
        'SalesTaxWithheldAtSource' => 'nullable|string|max:50',
        'ExtraTax'          => 'nullable|numeric|min:0|max:999.99',
        'FurtherTax'        => 'nullable|numeric|min:0|max:999.99',
        'FedPayable'        => 'nullable|numeric|min:0|max:999.99',
        'Notes'             => 'nullable|string|max:500',
        'Status'            => 'nullable|integer|in:0,1',
    ], [
        'Title.in'                     => 'Invalid title selected.',
        'FirstName.min'                => 'First name must be at least 2 characters.',
        'FirstName.regex'              => 'First name can only contain letters, spaces, hyphens and apostrophes.',
        'LastName.min'                 => 'Last name must be at least 2 characters.',
        'LastName.regex'               => 'Last name can only contain letters, spaces, hyphens and apostrophes.',
        'BusinessName.regex'           => 'Business name contains invalid characters.',
        'Email.email'                  => 'Please enter a valid email address.',
        'Email.unique'                 => 'This email address is already registered.',
        'Mobile.regex'                 => 'Mobile must be Pakistan format (e.g., 03001234567).',
        'Mobile.unique'                => 'This mobile number is already registered.',
        'Phone.regex'                  => 'Phone number must be in Pakistan format.',
        'Website.url'                  => 'Please enter a valid website URL.',
        'Website.regex'                => 'Website must start with http:// or https://',
        'County.required'              => 'State / Province is required.',
        'City.regex'                   => 'City name can only contain letters and spaces.',
        'County.regex'                 => 'State/Province can only contain letters and spaces.',
        'PostalCode.regex'             => 'Postal code must be exactly 5 digits.',
        'BillingCounty.required_if'    => 'Billing state/province is required.',
        'BillingCity.regex'            => 'Billing city can only contain letters and spaces.',
        'BillingCounty.regex'          => 'Billing state/province can only contain letters and spaces.',
        'BillingPostalCode.regex'      => 'Billing postal code must be exactly 5 digits.',
        'SalesTaxNumber.regex'         => 'Sales Tax Number format is invalid.',
        'SalesTaxNumber.unique'        => 'This Sales Tax Number is already registered.',
        'CNIC.regex'                   => 'CNIC must be in format: 12345-1234567-1',
        'CNIC.unique'                  => 'This CNIC is already registered.',
        'AdvanceTaxRate.required_if'   => 'Advance tax rate is required when advanced tax is enabled.',
        'AdvanceTaxRate.gt'            => 'Advance tax rate must be greater than 0.00.',
        'CreditLimit.numeric'          => 'Credit limit must be a valid number.',
        'CreditLimit.min'              => 'Credit limit cannot be negative.',
        'PaymentTerms.in'              => 'Invalid payment terms selected.',
        'Status.in'                    => 'Invalid status value.',
    ]);
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
            $userId = auth()->id() ?? 1;
            $now = now()->toDateTimeString();
            
            // Prepare values
            $displayName = $request->DisplayName ?: $request->BusinessName;
            $fullBillingAddress = $request->BillingAddress . ', ' . 
                                 $request->BillingCity . ', ' . 
                                 $request->BillingCountry;

            // Advanced tax → temp_customer (Edit Customer modal + standalone edit)
            $isAdvancedTax = (int) $request->input('IsAdvancedTax', 0) === 1 ? 1 : 0;
            $advanceTaxRate = $isAdvancedTax === 1
                ? round((float) $request->input('AdvanceTaxRate', 0), 2)
                : 0.00;

            // Prepare data for temp_customer (edit flow needs a row with TID = $id and RequestID)
            $data = [
                'TID'               => $id,
                'IsArchive'         => 0,
                'VoucherID'         => $customer->VoucherID ?? 0,
                'AccountNo'         => $customer->AccountNo ?? '0',
                'PartyType'         => $customer->PartyType ?? $this->partyTypeForCreate(),
                'Title'             => $request->Title,
                'FirstName'         => $request->FirstName,
                'LastName'          => $request->LastName,
                'BusinessName'      => $request->BusinessName,
                'DisplayName'       => $displayName,
                'Email'             => $request->Email,
                'Mobile'            => $request->Mobile,
                'Phone'             => $request->Phone ?? '',
                'Website'           => $request->Website ?? '',
                'Address'           => $request->Address,
                'PartyBalance'      => $customer->PartyBalance ?? 0.00,
                'City'              => $request->City,
                'County'            => $request->County,
                'PostalCode'        => $request->PostalCode,
                'Country'           => $request->Country,
                'SameAsBilling'     => $request->input('SameAsBilling', 0),
                'BillingAddress'    => $request->BillingAddress,
                'BillingCity'       => $request->BillingCity,
                'BillingCounty'     => $request->BillingCounty,
                'BillingCountry'    => $request->BillingCountry,
                'BillingPostalCode' => $request->BillingPostalCode,
                'FullBillingAddress'=> $fullBillingAddress,
                'NTN'               => $request->NTN,
                'SalesTaxNumber'    => $request->SalesTaxNumber ?? '',
                'CNIC'              => $request->CNIC,
                'IsFiler'           => $request->input('IsFiler', 0),
                'RegisterType'      => $this->nullableFbrField($request->input('RegisterType')),
                'FilerStatus'       => $this->nullableFbrField($request->input('FilerStatus')),
                'FilerStatusDate'   => $this->nullableFbrDate($request->input('FilerStatusDate')),
                'IsAdvanceTax'     => $isAdvancedTax,
                'AdvanceTax'       => $advanceTaxRate,
                'Notes'             => $request->Notes,
                'SalesTaxWithheldAtSource' => $request->input('SalesTaxWithheldAtSource'),
                'ExtraTax'          => (float) ($request->input('ExtraTax', 0) ?: 0),
                'FurtherTax'        => (float) ($request->input('FurtherTax', 0) ?: 0),
                'FedPayable'        => (float) ($request->input('FedPayable', 0) ?: 0),
                'Status'            => (int) $request->input('Status', 1),
                'CreatedBy'         => (int)($customer->CreatedBy ?? $userId),
                'CreatedOn'         => $customer->CreatedOn ?? $now,
                'UpdatedBy'         => (int)$userId,
                'UpdatedOn'         => $now,
                'IsDashboard'       => 0,
                'RequestID'         => $requestId,
            ];

            // Insert into temp_customer (staging before Customer::edit_transaction → tbl_customer)
            $nextTempId = DB::table('temp_customer')->max('ID');
            $nextTempId = $nextTempId ? (int)$nextTempId + 1 : 1;
            $data['ID'] = $nextTempId;
            DB::table('temp_customer')->insert($data);

            // Execute model transaction (runs query inside model)
            $editResult = Customer::edit_transaction(['RequestID' => $requestId]);
            if (is_array($editResult) && ($editResult['status'] ?? false) === false) {
                $message = $editResult['message'] ?? 'Unable to update '.$this->entitySingular().'.';
                if ($request->header('X-Requested-With') === 'XMLHttpRequest') {
                    return response()->json(['status' => 'error', 'message' => $message], 422);
                }

                return redirect()->back()->with('error', $message)->withInput();
            }

           if ($request->header('X-Requested-With') === 'XMLHttpRequest') {
    return response()->json(['status' => 'success', 'message' => $this->entitySingular().' updated successfully!']);
}
return redirect()->to($this->partyRoute('index'))->with('success', $this->entitySingular().' updated successfully!');
                
        } catch (\Exception $e) {
            \Log::error('Edit transaction failed', [
                'error' => $e->getMessage(),
                'customer_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            if ($request->header('X-Requested-With') === 'XMLHttpRequest') {
    return response()->json(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()], 500);
}
return redirect()->back()->with('error', 'Error: ' . $e->getMessage())->withInput();
        }
    }
    
    /**
     * Archive customer
     */
    public function delete_transaction($id)
    {
        try {
            $customer = $this->findPartyRecord($id);

            if (! $customer) {
                return redirect()->back()->with('error', $this->entitySingular().' not found.');
            }
            
            $requestId = (string) Str::uuid();
            $userId = auth()->id() ?? 1;
            $now = now()->toDateTimeString();

            // Insert a row into temp_customer so model's delete_transaction can read it (no row with ID=$id exists in temp)
            $nextTempId = DB::table('temp_customer')->max('ID');
            $nextTempId = $nextTempId ? (int)$nextTempId + 1 : 1;
            DB::table('temp_customer')->insert([
                'ID' => $nextTempId,
                'TID' => $id,
                'RequestID' => $requestId,
                'AccountNo' => $customer->AccountNo ?? '0',
                'PartyType' => $customer->PartyType ?? $this->partyTypeForCreate(),
                'Title' => $customer->Title ?? 'Mr',
                'FirstName' => $customer->FirstName ?? '',
                'Status' => 2,
                'CreatedBy' => (int)($customer->CreatedBy ?? $userId),
                'CreatedOn' => $customer->CreatedOn ?? $now,
                'UpdatedBy' => (int)$userId,
                'UpdatedOn' => $now,
            ]);

            // Execute model transaction (runs query inside model)
            Customer::delete_transaction(['RequestID' => $requestId]);
            
            return redirect()
                ->to($this->partyRoute('index'))
                ->with('success', $this->entitySingular().' archived successfully!');
                
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error deleting customer: ' . $e->getMessage());
        }
    }

    /**
     * Export CSV
     */
    public function exportCSV(Request $request)
    {
        $filename = $this->exportFilenamePrefix().'-'.date('Y-m-d-His').'.csv';
        
        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $filters = $this->listingFilters($request);

        $customers = Customer::getlistingdata($filters)->get();
        
        $callback = function() use ($customers) {
            $file = fopen('php://output', 'w');
            
            // Add BOM
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Header
            fputcsv($file, [
                'ID', 
                'Account No',
                'Name', 
                'Business Name',
                'Email',
                'Mobile',
                'Address', 
                'City',
                'Balance',
                'Status',
                'Created Date'
            ]);

            foreach ($customers as $customer) {
                // Name
                $name = $customer->DisplayName ?? ($customer->FirstName . ' ' . $customer->LastName);
                
                fputcsv($file, [
                    $customer->ID,
                    $customer->AccountNo,
                    $name,
                    $customer->BusinessName,
                    $customer->Email,
                    $customer->Mobile,
                    $customer->Address,
                    $customer->City,
                    number_format($customer->PartyBalance, 2),
                    $customer->Status == 1 ? 'Active' : 'Inactive',
                    $customer->CreatedOn
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Download customer import template
     */
    public function downloadImportTemplate()
    {
        // 1. Load FBR Provinces from core_digital_invoicing.fbr_province
        $provinceList = [];
        try {
            $provinceList = \Illuminate\Support\Facades\DB::connection('landlord')->table('core_digital_invoicing.fbr_province')
                ->distinct()
                ->whereNotNull('stateProvinceDesc')
                ->whereRaw("TRIM(stateProvinceDesc) <> ''")
                ->orderBy('stateProvinceDesc')
                ->pluck('stateProvinceDesc')
                ->toArray();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to load FBR provinces for template: ' . $e->getMessage());
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($this->entitySingular().'s');

        // Create Hidden Lookup Sheet for Provinces
        if (!empty($provinceList)) {
            $lookupSheet = $spreadsheet->createSheet();
            $lookupSheet->setTitle('Lookup_Data');
            $lookupSheet->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_VERYHIDDEN);

            $lookupSheet->setCellValue('A1', 'Province');
            foreach ($provinceList as $i => $v) {
                $lookupSheet->setCellValue('A' . ($i + 2), $v);
            }
            $spreadsheet->addNamedRange(new \PhpOffice\PhpSpreadsheet\NamedRange('LST_PROVINCES', $lookupSheet, '$A$2:$A$' . (count($provinceList) + 1)));
        }

        // Headers (without SameAsBilling/Billing columns, FilerStatus, FilerStatusDate, IsAdvanceTax; AdvanceTax renamed to CustomerAdvWHT)
        $headers = [
            $this->entitySingular().' Code *', 'Title', 'FirstName', 'LastName', 'BusinessName *', 'DisplayName', 
            'Email', 'Mobile', 'Phone', 'Website', 'Address', 'City', 'Province *', 'PostalCode', 'Country', 
            'NTN', 'SalesTaxNumber', 'CNIC', 'CustomerAdvWHT', 
            'RegisterType', 'Notes', 'Status',
            'SalesTaxWithheldAtSource', 'ExtraTax %', 'FurtherTax %', 'FedTax %'
        ];

        $sheet->fromArray([$headers], NULL, 'A1');

        // Base header styling (Dark Teal background)
        $sheet->getStyle('A1:Z1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '136056']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => 'D1D5DB']]]
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);

        // Required columns yellow highlighting: A1 (Code *), E1 (BusinessName *), M1 (Province *)
        $requiredStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => '713F12'], 'size' => 11],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEF08A']], // Distinct yellow
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM, 'color' => ['rgb' => 'EAB308']]]
        ];
        $sheet->getStyle('A1')->applyFromArray($requiredStyle); // Code *
        $sheet->getStyle('E1')->applyFromArray($requiredStyle); // BusinessName *
        $sheet->getStyle('M1')->applyFromArray($requiredStyle); // Province *

        // Sample Data Rows
        $prefix = $this->accountNoPrefix();
        $sampleData = [
            [
                $prefix.'-000001', 'Mr', 'Salman', 'Khalid', 'MK SUPER MARKET', 'MK SUPER MARKET', 
                'salman@example.com', '03001234567', '04231234567', 'https://example.com', 'Main Boulevard, Gulberg', 'Lahore', 'PUNJAB', '54000', 'Pakistan', 
                '7020599', '44444', '35202-1234567-1', 0.00, 
                'Registered', 'Sample Active Filer', 'Active',
                0.00, 0.00, 0.00, 0.00
            ],
            [
                $prefix.'-000002', 'Engr', 'Khurram', 'Jafar', 'Jaffery Soft', 'Jaffery Soft', 
                'khurram@example.com', '03211234567', '', '', 'Blue Area', 'Islamabad', 'CAPITAL TERRITORY', '44000', 'Pakistan', 
                '', '', '', 0.00, 
                'Un-Registered', 'Sample Non-Filer', 'InActive',
                0.00, 0.00, 0.00, 0.00
            ],
            [
                $prefix.'-000003', 'Mr', 'Bilal', 'Tariq', 'Bilal Enterprises', 'Bilal Enterprises', 
                'bilal@example.com', '03331234567', '', '', 'Korangi Industrial Area', 'Karachi', 'SINDH', '74900', 'Pakistan', 
                '8988888', '999999', '42101-1234567-1', 1.50, 
                'Registered', 'Sample with Advance Tax', 'Active',
                50.00, 2.00, 1.00, 1.00
            ]
        ];

        // Format text/code columns and numeric columns for rows 2 to 500
        $sheet->getStyle('A2:R500')->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);
        $sheet->getStyle('T2:V500')->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);
        $sheet->getStyle('S2:S500')->getNumberFormat()->setFormatCode('0.00'); // CustomerAdvWHT
        $sheet->getStyle('W2:Z500')->getNumberFormat()->setFormatCode('0.00'); // SalesTaxWithheld, ExtraTax, FurtherTax, FedTax

        foreach ($sampleData as $rowIndex => $rowValues) {
            $r = $rowIndex + 2;
            foreach ($rowValues as $colIndex => $val) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1);
                if (in_array($colLetter, ['S', 'W', 'X', 'Y', 'Z']) && is_numeric($val) && $val !== '') {
                    $sheet->setCellValue("{$colLetter}{$r}", (float)$val);
                } else {
                    $sheet->setCellValueExplicit("{$colLetter}{$r}", (string)$val, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                }
            }
        }

        // Data Validation Dropdowns (Rows 2 to 500)
        for ($row = 2; $row <= 500; $row++) {
            // Col M: Province *
            if (!empty($provinceList)) {
                $valProv = $sheet->getCell("M{$row}")->getDataValidation();
                $valProv->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                $valProv->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
                $valProv->setAllowBlank(true);
                $valProv->setShowDropDown(true);
                $valProv->setFormula1('LST_PROVINCES');
            }

            // Col T: RegisterType (Registered, Un-Registered)
            $valRegType = $sheet->getCell("T{$row}")->getDataValidation();
            $valRegType->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
            $valRegType->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
            $valRegType->setAllowBlank(true);
            $valRegType->setShowDropDown(true);
            $valRegType->setFormula1('"Registered,Un-Registered"');

            // Col V: Status (Active, InActive)
            $valStatus = $sheet->getCell("V{$row}")->getDataValidation();
            $valStatus->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
            $valStatus->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
            $valStatus->setAllowBlank(true);
            $valStatus->setShowDropDown(true);
            $valStatus->setFormula1('"Active,InActive"');
        }

        // Auto size columns
        foreach (range('A', 'Z') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = $this->entitySingular().'_Import_Template.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Import customers from Excel or CSV
     */
    public function importCustomers(Request $request)
    {
        $request->validate([
            'import_file' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240',
        ], [
            'import_file.required' => 'Please select an Excel or CSV file to import.',
            'import_file.mimes' => 'Import file must be an Excel workbook (.xlsx, .xls) or CSV file (.csv).',
            'import_file.max' => 'Import file must not exceed 10 MB.',
        ]);

        try {
            $requestId = (string) Str::uuid();
            $userId = auth()->id() ?? 1;
            $now = now()->toDateTimeString();

            $import = new \App\Imports\CustomerImport($this->partyTypeForCreate());
            \Maatwebsite\Excel\Facades\Excel::import($import, $request->file('import_file'));
            $rows = $import->mappedRows();

            if (empty($rows)) {
                $msg = 'No data rows found in the import file.';
                if ($request->expectsJson()) {
                    return response()->json(['status' => 'error', 'message' => $msg], 422);
                }
                return redirect()->back()->with('error', $msg);
            }

            // Fetch current maximum ID in temp_customer and tbl_customer to assign sequential IDs
            $maxId = (int) (DB::table('temp_customer')->max('ID') ?? 0);
            $maxCustId = (int) (DB::table('tbl_customer')->max('ID') ?? 0);
            $currentId = max($maxId, $maxCustId);

            $insertRows = [];
            foreach ($rows as $index => $row) {
                $currentId++;
                $accountNo = trim((string)($row['AccountNo'] ?? ''));
                if ($accountNo === '') {
                    $accountNo = $this->accountNoPrefix().'-'.str_pad((string)$currentId, 6, '0', STR_PAD_LEFT);
                }

                $regType = strtolower(trim((string)($row['RegisterType'] ?? '')));
                $isFiler = in_array($regType, ['registered', '1', 'yes', 'true', 'filer']) ? 1 : 0;

                $insertRows[] = [
                    'ID'                       => $currentId,
                    'TID'                      => $currentId,
                    'ExcelRow'                 => $row['RowNo'] ?? ($index + 2),
                    'IsArchive'                => 0,
                    'VoucherID'                => 0,
                    'AccountNo'                => $accountNo,
                    'PartyType'                => $row['PartyType'] ?? $this->partyTypeForCreate(),
                    'Title'                    => $row['Title'] ?? null,
                    'FirstName'                => $row['FirstName'] ?? null,
                    'LastName'                 => $row['LastName'] ?? null,
                    'BusinessName'             => $row['BusinessName'] ?? null,
                    'DisplayName'              => $row['DisplayName'] ?? $row['BusinessName'],
                    'Email'                    => $row['Email'] ?? null,
                    'Mobile'                   => $row['Mobile'] ?? null,
                    'Phone'                    => $row['Phone'] ?? '',
                    'Website'                  => $row['Website'] ?? '',
                    'Address'                  => $row['Address'] ?? null,
                    'PartyBalance'             => 0.00,
                    'City'                     => $row['City'] ?? null,
                    'County'                   => $row['County'] ?? null,
                    'PostalCode'               => $row['PostalCode'] ?? null,
                    'Country'                  => $row['Country'] ?? 'Pakistan',
                    'SameAsBilling'            => $row['SameAsBilling'] ?? 1,
                    'BillingAddress'           => $row['BillingAddress'] ?? null,
                    'BillingCity'              => $row['BillingCity'] ?? null,
                    'BillingCounty'            => $row['BillingCounty'] ?? null,
                    'BillingCountry'           => $row['BillingCountry'] ?? 'Pakistan',
                    'BillingPostalCode'        => $row['BillingPostalCode'] ?? null,
                    'FullBillingAddress'       => $row['FullBillingAddress'] ?? null,
                    'NTN'                      => $row['NTN'] ?? null,
                    'SalesTaxNumber'           => $row['SalesTaxNumber'] ?? '',
                    'CNIC'                     => $row['CNIC'] ?? null,
                    'IsFiler'                  => $isFiler,
                    'RegisterType'             => $row['RegisterType'] ?? null,
                    'FilerStatus'              => $row['FilerStatus'] ?? null,
                    'FilerStatusDate'          => $row['FilerStatusDate'] ?? null,
                    'IsAdvanceTax'             => $row['IsAdvanceTax'] ?? 0,
                    'AdvanceTax'               => $row['AdvanceTax'] ?? 0.00,
                    'Notes'                    => $row['Notes'] ?? null,
                    'SalesTaxWithheldAtSource' => $row['SalesTaxWithheldAtSource'] ?? null,
                    'ExtraTax'                 => (float)($row['ExtraTax'] ?? 0),
                    'FurtherTax'               => (float)($row['FurtherTax'] ?? 0),
                    'FedPayable'               => (float)($row['FedPayable'] ?? 0),
                    'Status'                   => 1,
                    'CreatedBy'                => (int)$userId,
                    'CreatedOn'                => $now,
                    'UpdatedBy'                => (int)$userId,
                    'UpdatedOn'                => $now,
                    'IsDashboard'              => 0,
                    'RequestID'                => $requestId,
                ];
            }

            foreach (array_chunk($insertRows, 200) as $chunk) {
                DB::table('temp_customer')->insert($chunk);
            }

            $saveResult = Customer::save_transaction(['RequestID' => $requestId]);
            if (is_array($saveResult) && ($saveResult['status'] ?? false) === false) {
                $errorMsg = $saveResult['message'] ?? 'Unable to import '.$this->entitySingular().'.';
                if ($request->expectsJson()) {
                    return response()->json([
                        'status' => 'error', 
                        'message' => $errorMsg, 
                        'errors' => $saveResult['errors'] ?? [$errorMsg]
                    ], 422);
                }
                return redirect()->back()->with('error', $errorMsg);
            }

            $successMsg = count($insertRows).' '.$this->entitySingular().'(s) imported successfully!';
            if ($request->expectsJson()) {
                return response()->json(['status' => 'success', 'message' => $successMsg]);
            }

            return redirect()->route($this->routePrefix().'.index')->with('success', $successMsg);
        } catch (\Throwable $e) {
            \Log::error('Customer import failed', ['error' => $e->getMessage()]);
            $err = 'Import failed: '.$e->getMessage();
            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => $err], 500);
            }
            return redirect()->back()->with('error', $err);
        }
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\BusinessInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BusinessInfoController extends Controller
{
    /**
     * No dedicated Business page: open modal from any page. Redirect /business to previous page.
     */
    public function index(Request $request)
    {
        return redirect()->back();
    }

    /**
     * Store Business Info (create) via temp_business_info.
     */
    public function save_transaction(Request $request)
    {
        // Normalize inputs
        $request->merge([
            'FirstName' => trim($request->FirstName ?? ''),
            'LastName'  => trim($request->LastName ?? ''),
            'Email'     => strtolower(trim($request->Email ?? '')),
            'Mobile'    => preg_replace('/[^0-9+]/', '', $request->Mobile ?? ''),
            'Phone'     => $request->Phone ? preg_replace('/[^0-9+]/', '', $request->Phone) : null,
        ]);

        // Validate
        try {
            $validated = $request->validate([
                'Title'        => 'required|string|in:Mr,Mrs,Miss,Ms,Dr,Prof,Engr',
                'FirstName'    => ['required','string','min:2','max:100'],
                'LastName'     => ['required','string','min:2','max:100'],
                'BusinessName' => ['nullable','string','max:200'],
                'Email'        => ['required','email','max:500','unique:tbl_business_info,Email'],
                'Mobile'       => ['required','string','max:100','unique:tbl_business_info,Mobile'],
                'Phone'        => ['nullable','string','max:100'],
                'Website'      => ['nullable','url','max:200'],
                'Address'      => 'nullable|string|max:500',
                'NTN'          => ['nullable','string','max:20','unique:tbl_business_info,NTN'],
                'SalesTaxNumber'=> ['nullable','string','max:50','unique:tbl_business_info,SalesTaxNumber'],
                'CNIC'         => ['nullable','string','max:50','unique:tbl_business_info,CNIC'],
                'IsFiler'      => 'nullable|boolean',
                'Notes'        => 'nullable|string|max:500',
                'Status'       => 'required|integer|in:0,1',
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
            $requestId = (string) Str::uuid();
            $userId    = auth()->id() ?? 1;
            $now       = now()->toDateTimeString();

            $displayName = $request->BusinessName
                ?: trim(($request->FirstName ?? '') . ' ' . ($request->LastName ?? ''));

            // Next temp ID
            $maxId  = DB::table('temp_business_info')->max('ID');
            $nextId = $maxId ? $maxId + 1 : 1;

            // Prepare data for temp_business_info (matching tbl_business_info columns plus temp metadata)
            $data = [
                'ID'            => $nextId,
                'TID'           => $nextId,
                'AccountNo'     => 'BIZ-' . str_pad($nextId, 6, '0', STR_PAD_LEFT),
                'Title'         => $request->Title,
                'FirstName'     => $request->FirstName,
                'LastName'      => $request->LastName,
                'BusinessName'  => $request->BusinessName,
                'Email'         => $request->Email,
                'Mobile'        => $request->Mobile,
                'Phone'         => $request->Phone ?? '',
                'Website'       => $request->Website ?? '',
                'Address'       => $request->Address,
                'NTN'           => $request->NTN,
                'SalesTaxNumber'=> $request->SalesTaxNumber ?? '',
                'CNIC'          => $request->CNIC,
                'IsFiler'       => $request->input('IsFiler', 1),
                'Notes'         => $request->Notes,
                'Status'        => $request->Status,
                'IsDashboard'   => 0,
                'IsArchive'     => 0,
                'CreatedBy'     => (int) $userId,
                'CreatedOn'     => $now,
                'UpdatedBy'     => (int) $userId,
                'UpdatedOn'     => $now,
                'RequestID'     => $requestId,
            ];

            DB::table('temp_business_info')->insert($data);

            BusinessInfo::save_transaction(['RequestID' => $requestId]);

            if ($request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json(['status' => 'success', 'message' => 'Business info created successfully!']);
            }

            return redirect()->route('business.index')->with('success', 'Business info created successfully!');
        } catch (\Exception $e) {
            if ($request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Load form into modal (create / edit / show).
     */
    public function modal(Request $request, $id = null)
    {
        $mode    = $request->query('mode', 'create');
        $record  = null;

        if ($id && in_array($mode, ['edit', 'show'])) {
            $row = DB::table('tbl_business_info')->where('ID', $id)->first();
            if (!$row) {
                return response('<div class="alert alert-danger m-3">Business record not found.</div>', 404);
            }

            $record = new BusinessInfo();
            $record->setRawAttributes((array) $row, true);
            $record->exists = true;
        }

        return view('pages.dashboards.Business._business-form-partial', [
            'mode'    => $mode,
            'record'  => $record ?? new BusinessInfo(),
            'inModal' => true,
        ]);
    }

    /**
     * Edit transaction via temp_business_info.
     */
    public function edit_transaction(Request $request, $id)
    {
        $existing = DB::table('tbl_business_info')->where('ID', $id)->first();
        if (!$existing) {
            return redirect()->back()->with('error', 'Business record not found.');
        }

        // Normalize
        $request->merge([
            'FirstName' => trim($request->FirstName ?? ''),
            'LastName'  => trim($request->LastName ?? ''),
            'Email'     => strtolower(trim($request->Email ?? '')),
            'Mobile'    => preg_replace('/[^0-9+]/', '', $request->Mobile ?? ''),
            'Phone'     => $request->Phone ? preg_replace('/[^0-9+]/', '', $request->Phone) : null,
        ]);

        try {
            $validated = $request->validate([
                'Title'        => 'required|string|in:Mr,Mrs,Miss,Ms,Dr,Prof,Engr',
                'FirstName'    => ['required','string','min:2','max:100'],
                'LastName'     => ['required','string','min:2','max:100'],
                'BusinessName' => ['nullable','string','max:200'],
                'Email'        => ['required','email','max:500', Rule::unique('tbl_business_info','Email')->ignore($id, 'ID')],
                'Mobile'       => ['required','string','max:100', Rule::unique('tbl_business_info','Mobile')->ignore($id, 'ID')],
                'Phone'        => ['nullable','string','max:100'],
                'Website'      => ['nullable','url','max:200'],
                'Address'      => 'nullable|string|max:500',
                'NTN'          => ['nullable','string','max:20', Rule::unique('tbl_business_info','NTN')->ignore($id, 'ID')->whereNotNull('NTN')],
                'SalesTaxNumber'=> ['nullable','string','max:50', Rule::unique('tbl_business_info','SalesTaxNumber')->ignore($id, 'ID')->whereNotNull('SalesTaxNumber')],
                'CNIC'         => ['nullable','string','max:50', Rule::unique('tbl_business_info','CNIC')->ignore($id, 'ID')],
                'IsFiler'      => 'nullable|boolean',
                'Notes'        => 'nullable|string|max:500',
                'Status'       => 'required|integer|in:0,1',
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
            $requestId = (string) Str::uuid();
            $userId    = auth()->id() ?? 1;
            $now       = now()->toDateTimeString();

            $displayName = $request->BusinessName
                ?: trim(($request->FirstName ?? '') . ' ' . ($request->LastName ?? ''));

            $data = [
                'TID'           => $id,
                'AccountNo'     => $existing->AccountNo ?? ('BIZ-' . str_pad($id, 6, '0', STR_PAD_LEFT)),
                'Title'         => $request->Title,
                'FirstName'     => $request->FirstName,
                'LastName'      => $request->LastName,
                'BusinessName'  => $request->BusinessName,
                'Email'         => $request->Email,
                'Mobile'        => $request->Mobile,
                'Phone'         => $request->Phone ?? '',
                'Website'       => $request->Website ?? '',
                'Address'       => $request->Address,
                'NTN'           => $request->NTN,
                'SalesTaxNumber'=> $request->SalesTaxNumber ?? '',
                'CNIC'          => $request->CNIC,
                'IsFiler'       => $request->input('IsFiler', 1),
                'Notes'         => $request->Notes,
                'Status'        => $request->Status,
                'IsDashboard'   => $existing->IsDashboard ?? 0,
                'IsArchive'     => $existing->IsArchive ?? 0,
                'CreatedBy'     => $existing->CreatedBy ?? $userId,
                'CreatedOn'     => $existing->CreatedOn ?? $now,
                'UpdatedBy'     => (int) $userId,
                'UpdatedOn'     => $now,
                'RequestID'     => $requestId,
            ];

            $nextTempId = DB::table('temp_business_info')->max('ID');
            $nextTempId = $nextTempId ? $nextTempId + 1 : 1;
            $data['ID'] = $nextTempId;

            DB::table('temp_business_info')->insert($data);

            BusinessInfo::edit_transaction(['RequestID' => $requestId]);

            if ($request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json(['status' => 'success', 'message' => 'Business info updated successfully!']);
            }

            return redirect()->route('business.index')->with('success', 'Business info updated successfully!');
        } catch (\Exception $e) {
            if ($request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Archive Business Info.
     */
    public function delete_transaction($id)
    {
        try {
            $existing = DB::table('tbl_business_info')->where('ID', $id)->first();
            if (!$existing) {
                return redirect()->back()->with('error', 'Business record not found.');
            }

            $requestId = (string) Str::uuid();
            $userId    = auth()->id() ?? 1;
            $now       = now()->toDateTimeString();

            $nextTempId = DB::table('temp_business_info')->max('ID');
            $nextTempId = $nextTempId ? $nextTempId + 1 : 1;

            DB::table('temp_business_info')->insert([
                'ID'        => $nextTempId,
                'TID'       => $id,
                'AccountNo' => $existing->AccountNo ?? '',
                'Title'     => $existing->Title ?? '',
                'FirstName' => $existing->FirstName ?? '',
                'IsArchive' => 1,
                'Status'    => 0,
                'CreatedBy' => (int) ($existing->CreatedBy ?? $userId),
                'CreatedOn' => $existing->CreatedOn ?? $now,
                'UpdatedBy' => (int) $userId,
                'UpdatedOn' => $now,
                'RequestID' => $requestId,
            ]);

            BusinessInfo::delete_transaction(['RequestID' => $requestId]);

            return redirect()->route('business.index')->with('success', 'Business info archived successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error archiving business info: ' . $e->getMessage());
        }
    }
}


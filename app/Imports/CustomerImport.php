<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class CustomerImport implements ToCollection, WithCalculatedFormulas, WithMultipleSheets
{
    /** @var list<array<string, mixed>> */
    private array $rows = [];
    private array $validationErrors = [];
    private int $defaultPartyType;

    /**
     * WithMultipleSheets: only process the first sheet.
     */
    public function sheets(): array
    {
        return [0 => $this];
    }

    public function __construct(int $defaultPartyType = 1)
    {
        $this->defaultPartyType = $defaultPartyType;
    }

    public function collection(Collection $rows): void
    {
        if ($rows->isEmpty()) {
            return;
        }

        // Detect header row
        $firstRow = $rows->first()->values()->all();
        $headerMap = $this->buildHeaderMap($firstRow);
        $hasHeaders = !empty($headerMap);

        $startIndex = $hasHeaders ? 1 : 0;

        for ($i = $startIndex; $i < $rows->count(); $i++) {
            $rowValues = $rows[$i]->values()->all();
            if ($this->rowIsEmpty($rowValues)) {
                continue;
            }

            $mapped = $hasHeaders 
                ? $this->mapWithHeaders($rowValues, $headerMap, $i + 1)
                : $this->mapPositional($rowValues, $i + 1);

            if ($mapped !== null) {
                $this->rows[] = $mapped;
            }
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function mappedRows(): array
    {
        return $this->rows;
    }

    /**
     * @return list<string>
     */
    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    private function buildHeaderMap(array $firstRow): array
    {
        $map = [];
        $knownKeys = [
            'accountno' => ['customercode', 'customer_code', 'customer code', 'customer code *', 'customerno', 'customer_no', 'customer no', 'suppliercode', 'supplier_code', 'supplier code', 'supplier code *', 'supplierno', 'supplier_no', 'supplier no', 'registrationnontn', 'registrationno/ntn', 'registrationno', 'regno', 'reg_no', 'registration number', 'accountno', 'account_no', 'account number', 'account#', 'code', 'code *'],
            'partytype' => ['partytype', 'party_type', 'type'],
            'title' => ['title', 'salutation'],
            'firstname' => ['firstname', 'first_name', 'first name'],
            'lastname' => ['lastname', 'last_name', 'last name'],
            'businessname' => ['businessname', 'business_name', 'business name', 'company', 'company name', 'customer name', 'client name'],
            'displayname' => ['displayname', 'display_name', 'display name'],
            'email' => ['email', 'email address'],
            'mobile' => ['mobile', 'cell', 'mobile no', 'mobile_no'],
            'phone' => ['phone', 'telephone', 'landline'],
            'website' => ['website', 'url', 'web'],
            'address' => ['address', 'street', 'street address'],
            'city' => ['city', 'town'],
            'county' => ['county', 'province', 'state', 'state/province'],
            'postalcode' => ['postalcode', 'postal_code', 'postal code', 'zip', 'zipcode'],
            'country' => ['country'],
            'sameasbilling' => ['sameasbilling', 'same_as_billing', 'same as billing'],
            'billingaddress' => ['billingaddress', 'billing_address', 'billing address'],
            'billingcity' => ['billingcity', 'billing_city', 'billing city'],
            'billingcounty' => ['billingcounty', 'billing_county', 'billing county', 'billingprovince', 'billing_province', 'billing province'],
            'billingpostalcode' => ['billingpostalcode', 'billing_postal_code', 'billing postal code', 'billingzip'],
            'billingcountry' => ['billingcountry', 'billing_country', 'billing country'],
            'fullbillingaddress' => ['fullbillingaddress', 'full_billing_address', 'full billing address'],
            'ntn' => ['ntn', 'ntn number', 'ntn_number'],
            'salestaxnumber' => ['salestaxnumber', 'salestaxno', 'strn', 'sales tax number', 'sales_tax_number'],
            'cnic' => ['cnic', 'cnic no', 'cnic_no', 'national id'],
            'isadvancetax' => ['isadvancetax', 'is_advance_tax', 'is advance tax', 'advance tax applicable'],
            'advancetax' => ['customeradvwht', 'customer_adv_wht', 'customer adv wht', 'supplieradvwht', 'supplier_adv_wht', 'supplier adv wht', 'advwht', 'adv_wht', 'adv wht', 'advancetax', 'advance_tax', 'advance tax rate', 'advance tax'],
            'isfiler' => ['isfiler', 'is_filer', 'is filer', 'filer status type'],
            'registertype' => ['registertype', 'register_type', 'register type', 'registration type'],
            'filerstatus' => ['filerstatus', 'filer_status', 'filer status'],
            'filerstatusdate' => ['filerstatusdate', 'filer_status_date', 'filer status date'],
            'notes' => ['notes', 'remarks', 'description'],
            'salestaxwithheldatsource' => ['salestaxwithheldatsource', 'sales_tax_withheld', 'sales tax withheld'],
            'extratax' => ['extratax%', 'extratax %', 'extratax', 'extra_tax', 'extra tax', 'extra tax %'],
            'furthertax' => ['furthertax%', 'furthertax %', 'furthertax', 'further_tax', 'further tax', 'further tax %'],
            'fedpayable' => ['fedtax%', 'fedtax %', 'fedtax', 'fed_tax', 'fed tax', 'fed tax %', 'fedpayable%', 'fedpayable %', 'fedpayable', 'fed_payable', 'fed payable', 'fed payable %'],
            'status' => ['status', 'active status', 'is_active'],
        ];

        foreach ($firstRow as $idx => $val) {
            if ($val === null) continue;
            $clean = strtolower(trim((string)$val));
            $cleanAlpha = preg_replace('/[^a-z0-9]/', '', $clean);

            foreach ($knownKeys as $canonical => $aliases) {
                foreach ($aliases as $alias) {
                    $aliasAlpha = preg_replace('/[^a-z0-9]/', '', $alias);
                    if ($cleanAlpha === $aliasAlpha) {
                        $map[$canonical] = $idx;
                        break 2;
                    }
                }
            }
        }

        // Consider it a valid header row if it matched at least BusinessName or AccountNo
        if (isset($map['businessname']) || isset($map['accountno'])) {
            return $map;
        }

        return [];
    }

    private function mapWithHeaders(array $values, array $headerMap, int $rowNum): ?array
    {
        $accountNo = $this->getHeaderValue($values, $headerMap, 'accountno');
        $businessName = $this->getHeaderValue($values, $headerMap, 'businessname');
        $province = $this->getHeaderValue($values, $headerMap, 'county');

        if (empty($businessName) && empty($accountNo)) {
            return null; // Ignore completely blank row
        }

        $title = $this->getHeaderValue($values, $headerMap, 'title');
        $firstName = $this->getHeaderValue($values, $headerMap, 'firstname');
        $lastName = $this->getHeaderValue($values, $headerMap, 'lastname');
        $displayName = $this->getHeaderValue($values, $headerMap, 'displayname') ?: $businessName ?: trim($firstName . ' ' . $lastName);
        $email = $this->getHeaderValue($values, $headerMap, 'email');
        $mobile = $this->getHeaderValue($values, $headerMap, 'mobile');
        $phone = $this->getHeaderValue($values, $headerMap, 'phone');
        $website = $this->getHeaderValue($values, $headerMap, 'website');
        $address = $this->getHeaderValue($values, $headerMap, 'address');
        $city = $this->getHeaderValue($values, $headerMap, 'city');
        $postalCode = $this->getHeaderValue($values, $headerMap, 'postalcode');
        $country = $this->getHeaderValue($values, $headerMap, 'country') ?: 'Pakistan';

        $sameAsBilling = $this->parseBoolean($this->getHeaderValue($values, $headerMap, 'sameasbilling'), 1);
        $billingAddress = $sameAsBilling ? $address : ($this->getHeaderValue($values, $headerMap, 'billingaddress') ?: $address);
        $billingCity = $sameAsBilling ? $city : ($this->getHeaderValue($values, $headerMap, 'billingcity') ?: $city);
        $billingCounty = $sameAsBilling ? $province : ($this->getHeaderValue($values, $headerMap, 'billingcounty') ?: $province);
        $billingPostalCode = $sameAsBilling ? $postalCode : ($this->getHeaderValue($values, $headerMap, 'billingpostalcode') ?: $postalCode);
        $billingCountry = $sameAsBilling ? $country : ($this->getHeaderValue($values, $headerMap, 'billingcountry') ?: $country);

        $fullBillingAddress = trim(implode(', ', array_filter([$billingAddress, $billingCity, $billingCounty, $billingCountry])));

        $ntn = $this->getHeaderValue($values, $headerMap, 'ntn');
        $salesTaxNumber = $this->getHeaderValue($values, $headerMap, 'salestaxnumber');
        $cnic = $this->getHeaderValue($values, $headerMap, 'cnic');

        $advanceTax = (float)($this->getHeaderValue($values, $headerMap, 'advancetax') ?: 0.0);
        $isAdvanceTax = ($advanceTax > 0) ? 1 : $this->parseBoolean($this->getHeaderValue($values, $headerMap, 'isadvancetax'), 0);

        $registerType = $this->getHeaderValue($values, $headerMap, 'registertype');
        $regClean = strtolower(trim((string)($registerType ?? '')));
        $isFiler = in_array($regClean, ['registered', '1', 'yes', 'true', 'filer']) 
            ? 1 
            : $this->parseBoolean($this->getHeaderValue($values, $headerMap, 'isfiler'), 0);
        $filerStatus = $this->getHeaderValue($values, $headerMap, 'filerstatus');
        $filerStatusDate = $this->parseDate($this->getHeaderValue($values, $headerMap, 'filerstatusdate'));

        $notes = $this->getHeaderValue($values, $headerMap, 'notes');
        $salesTaxWithheld = $this->getHeaderValue($values, $headerMap, 'salestaxwithheldatsource');
        $extraTax = (float)($this->getHeaderValue($values, $headerMap, 'extratax') ?: 0.0);
        $furtherTax = (float)($this->getHeaderValue($values, $headerMap, 'furthertax') ?: 0.0);
        $fedPayable = (float)($this->getHeaderValue($values, $headerMap, 'fedpayable') ?: 0.0);

        // Status & PartyType
        $status = $this->parseStatus($this->getHeaderValue($values, $headerMap, 'status'), 1);
        $partyType = $this->defaultPartyType;

        return [
            'RowNo' => $rowNum,
            'AccountNo' => $accountNo,
            'PartyType' => $partyType,
            'Title' => $title,
            'FirstName' => $firstName,
            'LastName' => $lastName,
            'BusinessName' => $businessName,
            'DisplayName' => $displayName,
            'Email' => $email,
            'Mobile' => $mobile,
            'Phone' => $phone ?: '',
            'Website' => $website ?: '',
            'Address' => $address,
            'City' => $city,
            'County' => $province,
            'PostalCode' => $postalCode,
            'Country' => $country,
            'SameAsBilling' => $sameAsBilling,
            'BillingAddress' => $billingAddress,
            'BillingCity' => $billingCity,
            'BillingCounty' => $billingCounty,
            'BillingPostalCode' => $billingPostalCode,
            'BillingCountry' => $billingCountry,
            'FullBillingAddress' => $fullBillingAddress,
            'NTN' => $ntn,
            'SalesTaxNumber' => $salesTaxNumber ?: '',
            'CNIC' => $cnic,
            'IsAdvanceTax' => $isAdvanceTax,
            'AdvanceTax' => $advanceTax,
            'IsFiler' => $isFiler,
            'RegisterType' => $registerType,
            'FilerStatus' => $filerStatus,
            'FilerStatusDate' => $filerStatusDate,
            'Notes' => $notes,
            'SalesTaxWithheldAtSource' => $salesTaxWithheld,
            'ExtraTax' => $extraTax,
            'FurtherTax' => $furtherTax,
            'FedPayable' => $fedPayable,
            'Status' => $status,
            'PartyBalance' => 0.00,
            'IsArchive' => 0,
            'VoucherID' => 0,
            'IsDashboard' => 0,
        ];
    }

    private function mapPositional(array $values, int $rowNum): ?array
    {
        $accountNo = $this->cellString($values, 0);
        $businessName = $this->cellString($values, 1);
        $province = $this->cellString($values, 2);

        if (empty($businessName) && empty($accountNo)) {
            return null;
        }

        return [
            'RowNo' => $rowNum,
            'AccountNo' => $accountNo,
            'PartyType' => $this->defaultPartyType,
            'Title' => null,
            'FirstName' => null,
            'LastName' => null,
            'BusinessName' => $businessName,
            'DisplayName' => $businessName,
            'Email' => $this->cellString($values, 3),
            'Mobile' => $this->cellString($values, 4),
            'Phone' => $this->cellString($values, 5) ?: '',
            'Website' => '',
            'Address' => $this->cellString($values, 6),
            'City' => $this->cellString($values, 7),
            'County' => $province,
            'PostalCode' => $this->cellString($values, 8),
            'Country' => 'Pakistan',
            'SameAsBilling' => 1,
            'BillingAddress' => $this->cellString($values, 6),
            'BillingCity' => $this->cellString($values, 7),
            'BillingCounty' => $province,
            'BillingPostalCode' => $this->cellString($values, 8),
            'BillingCountry' => 'Pakistan',
            'FullBillingAddress' => trim(implode(', ', array_filter([$this->cellString($values, 6), $this->cellString($values, 7), $province, 'Pakistan']))),
            'NTN' => $this->cellString($values, 9),
            'SalesTaxNumber' => $this->cellString($values, 10) ?: '',
            'CNIC' => $this->cellString($values, 11),
            'IsAdvanceTax' => 0,
            'AdvanceTax' => 0.00,
            'IsFiler' => 0,
            'RegisterType' => null,
            'FilerStatus' => null,
            'FilerStatusDate' => null,
            'Notes' => null,
            'SalesTaxWithheldAtSource' => null,
            'ExtraTax' => 0.00,
            'FurtherTax' => 0.00,
            'FedPayable' => 0.00,
            'Status' => $this->parseStatus($this->cellString($values, 12), 1),
            'PartyBalance' => 0.00,
            'IsArchive' => 0,
            'VoucherID' => 0,
            'IsDashboard' => 0,
        ];
    }

    private function getHeaderValue(array $values, array $headerMap, string $key): ?string
    {
        if (!isset($headerMap[$key])) {
            return null;
        }
        $idx = $headerMap[$key];
        return $this->cellString($values, $idx);
    }

    private function cellString(array $values, int $idx): ?string
    {
        if (!array_key_exists($idx, $values)) {
            return null;
        }
        $raw = $values[$idx];
        if ($raw === null) return null;
        $clean = trim((string)$raw);
        return $clean === '' ? null : $clean;
    }

    private function parseBoolean(?string $val, int $default = 0): int
    {
        if ($val === null || $val === '') {
            return $default;
        }
        $v = strtolower(trim($val));
        if (in_array($v, ['1', 'yes', 'y', 'true', 'active', 'filer'])) {
            return 1;
        }
        if (in_array($v, ['0', 'no', 'n', 'false', 'inactive', 'non-filer'])) {
            return 0;
        }
        return $default;
    }

    private function parseStatus(?string $val, int $default = 1): int
    {
        if ($val === null || $val === '') {
            return $default;
        }
        $v = strtolower(trim($val));
        if ($v === '0' || $v === 'inactive' || $v === 'in-active' || $v === 'disabled') {
            return 0;
        }
        if ($v === '1' || $v === 'active' || $v === 'enabled') {
            return 1;
        }
        return is_numeric($v) ? (int)$v : $default;
    }

    private function parseDate(?string $val): ?string
    {
        if (!$val) return null;
        try {
            $parsed = \Carbon\Carbon::parse($val);
            return $parsed->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function rowIsEmpty(array $values): bool
    {
        foreach ($values as $v) {
            if ($v !== null && trim((string)$v) !== '') {
                return false;
            }
        }
        return true;
    }
}

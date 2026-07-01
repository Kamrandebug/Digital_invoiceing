<?php

namespace App\Http\Controllers;

use App\Models\Customer;

class SupplierController extends CustomerController
{
    protected function routePrefix(): string
    {
        return 'suppliers';
    }

    protected function listView(): string
    {
        return 'pages.dashboards.Supplier.suppliers-list';
    }

    protected function formView(): string
    {
        return 'pages.dashboards.Supplier.suppliers-form';
    }

    protected function partialView(): string
    {
        return 'pages.dashboards.Supplier._supplier-form-partial';
    }

    /** @return list<int> */
    protected function partyTypesForList(): array
    {
        return [Customer::PARTY_TYPE_SUPPLIER, Customer::PARTY_TYPE_BOTH];
    }

    protected function partyTypeForCreate(): int
    {
        return Customer::PARTY_TYPE_SUPPLIER;
    }

    protected function includeNullPartyTypeInList(): bool
    {
        return false;
    }

    protected function accountNoPrefix(): string
    {
        return 'SUPP';
    }

    protected function entitySingular(): string
    {
        return 'Supplier';
    }

    protected function listVariableName(): string
    {
        return 'suppliers';
    }

    protected function exportFilenamePrefix(): string
    {
        return 'suppliers-export';
    }
}

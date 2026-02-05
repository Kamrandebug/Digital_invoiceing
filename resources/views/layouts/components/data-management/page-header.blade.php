{{--
    Reusable Page Header Component
    
    Purpose: Displays "Add" and "Import" buttons at the top of data management pages
    
    Usage:
    <x-layouts.components.data-management.page-header addButtonText="Add Customer" />
    
    Props:
    - addButtonText (optional): Text for the add button (default: "Add")
--}}

@props(['addButtonText' => 'Add'])

<div class="header-actions mb-4">
    <div class="action-buttons">
        <button class="btn-add-customer">
            <i class="fas fa-plus"></i> {{ $addButtonText }}
        </button>
        <button class="btn-import">
            <i class="fas fa-file-import"></i> Import
        </button>
    </div>
</div>
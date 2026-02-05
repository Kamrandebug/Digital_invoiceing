{{--
    Reusable Search & Filters Component
    
    Purpose: Provides search box, date filters, and clear button
    
    Usage:
    <x-layouts.components.data-management.search-filters 
        searchPlaceholder="Search customers..." 
        :showDateFilters="true" 
    />
    
    Props:
    - searchPlaceholder (optional): Placeholder text for search input
    - showDateFilters (optional): Show/hide date range filters (default: true)
--}}

@props(['searchPlaceholder' => 'Search...', 'showDateFilters' => true])

<div class="search-filters-section">
    <div class="search-box">
        <i class="fas fa-search"></i>
        <input type="text" placeholder="{{ $searchPlaceholder }}">
    </div>

    @if($showDateFilters)
    <div class="date-filters">
        <div class="date-input">
            <label>Start Date</label>
            <input type="date">
        </div>
        <div class="date-input">
            <label>End Date</label>
            <input type="date">
        </div>
    </div>
    @endif

    <button class="btn-clear-filters">
        <i class="fas fa-times"></i> Clear Filters
    </button>

    <div class="search-right">
        <label>Search:</label>
        <input type="text">
    </div>
</div>
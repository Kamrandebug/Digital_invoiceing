{{--
    Reusable Pagination Component
    
    Purpose: Displays pagination controls and "Showing X to Y of Z entries"
    
    Usage:
    <x-layouts.components.data-management.pagination 
        from="1" 
        to="10" 
        total="15" 
    />
    
    Props:
    - from (optional): Start number (default: 1)
    - to (optional): End number (default: 10)
    - total (optional): Total entries (default: 0)
--}}

@props(['from' => 1, 'to' => 10, 'total' => 0])

<div class="pagination">
    <span>Showing {{ $from }} to {{ $to }} of {{ $total }} entries</span>
    <div class="page-buttons">
        <button>Previous</button>
        <button class="active">1</button>
        <button>2</button>
        <button>Next</button>
    </div>
</div>
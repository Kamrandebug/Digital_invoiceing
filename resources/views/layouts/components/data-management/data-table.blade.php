{{--
    Reusable Data Table Component
    
    Purpose: Displays a table with dynamic columns and rows
    
    Usage:
    <x-layouts.components.data-management.data-table :columns="[
        ['label' => 'ID', 'sortable' => true],
        ['label' => 'Name', 'sortable' => true],
        ['label' => 'Address']
    ]">
        <tr>
            <td>Row content here</td>
        </tr>
    </x-layouts.components.data-management.data-table>
    
    Props:
    - columns (required): Array of column definitions
      - label: Column header text
      - sortable (optional): Show sort icon
      - info (optional): Show info icon
    
    Slot:
    - {{ $slot }} - The table rows go here
--}}

@props(['columns' => []])

<div class="table-wrapper">
    <table class="customers-table">
        <thead>
            <tr>
                <th><input type="checkbox"></th>
                @foreach($columns as $column)
                    <th>
                        {{ $column['label'] }}
                        @if(isset($column['sortable']) && $column['sortable'])
                            <i class="fas fa-sort"></i>
                        @endif
                        @if(isset($column['info']) && $column['info'])
                            <i class="fas fa-info-circle"></i>
                        @endif
                    </th>
                @endforeach
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            {{ $slot }}
        </tbody>
    </table>
</div>
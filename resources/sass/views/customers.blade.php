@extends('layouts.master')

@section('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/customer-management.css') }}">
@endsection

@section('content')
<div class="customer-management-container">
    <div class="header">
        <h1>Customer Management</h1>
        <div class="action-buttons">
            <button><i class="fas fa-plus"></i> Add Customer</button>
            <button><i class="fas fa-file-upload"></i> Import</button>
        </div>
    </div>

    <div class="search-filters-section">
        <div class="search-box">
            <input type="text" placeholder="Search customers...">
        </div>

        <div class="date-filters">
            <div>
                <label>Start Date</label>
                <input type="date">
            </div>
            <div>
                <label>End Date</label>
                <input type="date">
            </div>
        </div>

        <button><i class="fas fa-times"></i> Clear Filters</button>

        <div class="search-right">
            <label>Search:</label>
            <input type="text">
        </div>
    </div>

    <div class="export-buttons">
        <button><i class="fas fa-download"></i> Export CSV</button>
        <button><i class="fas fa-print"></i> Print</button>
    </div>

    <div class="customers-table-wrapper">
        <table class="customers-table">
            <thead>
                <tr>
                    <th><input type="checkbox"></th>
                    <th>ID <i class="fas fa-sort"></i></th>
                    <th>Name <i class="fas fa-sort"></i></th>
                    <th>Address</th>
                    <th>Contact Number</th>
                    <th>Closing Balance <i class="fas fa-info-circle" title="Current balance"></i></th>
                    <th>Opening Balance Date <i class="fas fa-info-circle" title="Date of opening balance"></i></th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <!-- Row 1 -->
                <tr>
                    <td><input type="checkbox"></td>
                    <td>161</td>
                    <td>Sukkur General Store</td>
                    <td>Civil Lines, Sukkur</td>
                    <td>3355578001</td>
                    <td class="balance-positive">2200.00 PKR</td>
                    <td>N/A</td>
                    <td>
                        <button class="action-btn btn-edit"><i class="fas fa-pencil-alt"></i></button>
                        <button class="action-btn btn-info"><i class="fas fa-clipboard-list"></i></button>
                        <button class="action-btn btn-delete"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
                <!-- Row 2 -->
                <tr>
                    <td><input type="checkbox"></td>
                    <td>147</td>
                    <td>Ahmad Electronics</td>
                    <td>Shop #15, Main Market, Lahore</td>
                    <td>3001234567</td>
                    <td class="balance-positive">5000.00 PKR</td>
                    <td>N/A</td>
                    <td>
                        <button class="action-btn btn-edit"><i class="fas fa-pencil-alt"></i></button>
                        <button class="action-btn btn-info"><i class="fas fa-clipboard-list"></i></button>
                        <button class="action-btn btn-delete"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
                <!-- Row 3 -->
                <tr>
                    <td><input type="checkbox"></td>
                    <td>148</td>
                    <td>Karachi Traders</td>
                    <td>Plot 45, Industrial Area, Karachi</td>
                    <td>3211234567</td>
                    <td class="balance-zero">0.00 PKR</td>
                    <td>N/A</td>
                    <td>
                        <button class="action-btn btn-edit"><i class="fas fa-pencil-alt"></i></button>
                        <button class="action-btn btn-info"><i class="fas fa-clipboard-list"></i></button>
                        <button class="action-btn btn-delete"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
                <!-- Row 4 -->
                <tr>
                    <td><input type="checkbox"></td>
                    <td>149</td>
                    <td>Islamabad Office Solutions</td>
                    <td>F-8 Markaz, Islamabad</td>
                    <td>3331234567</td>
                    <td class="balance-positive">6300.00 PKR</td>
                    <td>N/A</td>
                    <td>
                        <button class="action-btn btn-edit"><i class="fas fa-pencil-alt"></i></button>
                        <button class="action-btn btn-info"><i class="fas fa-clipboard-list"></i></button>
                        <button class="action-btn btn-delete"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
                <!-- Row 5 -->
                <tr>
                    <td><input type="checkbox"></td>
                    <td>150</td>
                    <td>Faisalabad Furniture Co.</td>
                    <td>Jhang Road, Faisalabad</td>
                    <td>3041234567</td>
                    <td class="balance-zero">0.00 PKR</td>
                    <td>N/A</td>
                    <td>
                        <button class="action-btn btn-edit"><i class="fas fa-pencil-alt"></i></button>
                        <button class="action-btn btn-info"><i class="fas fa-clipboard-list"></i></button>
                        <button class="action-btn btn-delete"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
                <!-- Row 6 -->
                <tr>
                    <td><input type="checkbox"></td>
                    <td>151</td>
                    <td>Multan Medical Store</td>
                    <td>Nawan Shehr, Multan</td>
                    <td>3061234567</td>
                    <td class="balance-positive">1500.00 PKR</td>
                    <td>N/A</td>
                    <td>
                        <button class="action-btn btn-edit"><i class="fas fa-pencil-alt"></i></button>
                        <button class="action-btn btn-info"><i class="fas fa-clipboard-list"></i></button>
                        <button class="action-btn btn-delete"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
                <!-- Row 7 -->
                <tr>
                    <td><input type="checkbox"></td>
                    <td>152</td>
                    <td>Peshawar Auto Parts</td>
                    <td>University Road, Peshawar</td>
                    <td>3451234567</td>
                    <td class="balance-zero">0.00 PKR</td>
                    <td>N/A</td>
                    <td>
                        <button class="action-btn btn-edit"><i class="fas fa-pencil-alt"></i></button>
                        <button class="action-btn btn-info"><i class="fas fa-clipboard-list"></i></button>
                        <button class="action-btn btn-delete"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
                <!-- Row 8 -->
                <tr>
                    <td><input type="checkbox"></td>
                    <td>153</td>
                    <td>Quetta Hardware Store</td>
                    <td>Jinnah Road, Quetta</td>
                    <td>3361234567</td>
                    <td class="balance-positive">3000.00 PKR</td>
                    <td>N/A</td>
                    <td>
                        <button class="action-btn btn-edit"><i class="fas fa-pencil-alt"></i></button>
                        <button class="action-btn btn-info"><i class="fas fa-clipboard-list"></i></button>
                        <button class="action-btn btn-delete"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
                <!-- Row 9 -->
                <tr>
                    <td><input type="checkbox"></td>
                    <td>154</td>
                    <td>Sialkot Sports</td>
                    <td>Paris Road, Sialkot</td>
                    <td>3241234567</td>
                    <td class="balance-zero">0.00 PKR</td>
                    <td>N/A</td>
                    <td>
                        <button class="action-btn btn-edit"><i class="fas fa-pencil-alt"></i></button>
                        <button class="action-btn btn-info"><i class="fas fa-clipboard-list"></i></button>
                        <button class="action-btn btn-delete"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
                <!-- Row 10 -->
                <tr>
                    <td><input type="checkbox"></td>
                    <td>155</td>
                    <td>Gujranwala Textiles</td>
                    <td>GT Road, Gujranwala</td>
                    <td>3004567890</td>
                    <td class="balance-positive">4000.00 PKR</td>
                    <td>N/A</td>
                    <td>
                        <button class="action-btn btn-edit"><i class="fas fa-pencil-alt"></i></button>
                        <button class="action-btn btn-info"><i class="fas fa-clipboard-list"></i></button>
                        <button class="action-btn btn-delete"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
                <!-- Row 11 -->
                <tr>
                    <td><input type="checkbox"></td>
                    <td>156</td>
                    <td>Rawalpindi Electronics</td>
                    <td>Murree Road, Rawalpindi</td>
                    <td>3123456789</td>
                    <td class="balance-zero">0.00 PKR</td>
                    <td>N/A</td>
                    <td>
                        <button class="action-btn btn-edit"><i class="fas fa-pencil-alt"></i></button>
                        <button class="action-btn btn-info"><i class="fas fa-clipboard-list"></i></button>
                        <button class="action-btn btn-delete"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
                <!-- Row 12 -->
                <tr>
                    <td><input type="checkbox"></td>
                    <td>157</td>
                    <td>Bahawalpur Fabrics</td>
                    <td>Circular Road, Bahawalpur</td>
                    <td>3012345678</td>
                    <td class="balance-positive">1200.00 PKR</td>
                    <td>N/A</td>
                    <td>
                        <button class="action-btn btn-edit"><i class="fas fa-pencil-alt"></i></button>
                        <button class="action-btn btn-info"><i class="fas fa-clipboard-list"></i></button>
                        <button class="action-btn btn-delete"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
                <!-- Row 13 -->
                <tr>
                    <td><input type="checkbox"></td>
                    <td>158</td>
                    <td>Sargodha Mobiles</td>
                    <td>University Road, Sargodha</td>
                    <td>3023456789</td>
                    <td class="balance-zero">0.00 PKR</td>
                    <td>N/A</td>
                    <td>
                        <button class="action-btn btn-edit"><i class="fas fa-pencil-alt"></i></button>
                        <button class="action-btn btn-info"><i class="fas fa-clipboard-list"></i></button>
                        <button class="action-btn btn-delete"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
                <!-- Row 14 -->
                <tr>
                    <td><input type="checkbox"></td>
                    <td>159</td>
                    <td>Hyderabad Autos</td>
                    <td>Auto Bahn Road, Hyderabad</td>
                    <td>3034567890</td>
                    <td class="balance-positive">2500.00 PKR</td>
                    <td>N/A</td>
                    <td>
                        <button class="action-btn btn-edit"><i class="fas fa-pencil-alt"></i></button>
                        <button class="action-btn btn-info"><i class="fas fa-clipboard-list"></i></button>
                        <button class="action-btn btn-delete"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
                <!-- Row 15 -->
                <tr>
                    <td><input type="checkbox"></td>
                    <td>160</td>
                    <td>Larkana General Store</td>
                    <td>VIP Road, Larkana</td>
                    <td>3045678901</td>
                    <td class="balance-zero">0.00 PKR</td>
                    <td>N/A</td>
                    <td>
                        <button class="action-btn btn-edit"><i class="fas fa-pencil-alt"></i></button>
                        <button class="action-btn btn-info"><i class="fas fa-clipboard-list"></i></button>
                        <button class="action-btn btn-delete"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="pagination">
        <span>Showing 1 to 10 of 15 entries</span>
        <div>
            <button>Previous</button>
            <button class="active">1</button>
            <button>2</button>
            <button>Next</button>
        </div>
    </div>
</div>
@endsection

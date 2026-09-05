<?php

namespace App\Http\Controllers;

use App\Imports\BulkInvoiceImport;
use App\Models\BulkInvoiceModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class BulkInvoiceController extends Controller
{
    /**
     * Display listing of Bulk Invoicing Requests
     */
    public function index(Request $request)
    {
        $filters = [
            'search'      => $request->input('search'),
            'start_date'  => $request->input('start_date'),
            'end_date'    => $request->input('end_date'),
            'source_type' => $request->input('source_type'),
        ];

        // Summary Counters
        $summary = DB::connection('tenant')->table('tbl_bulk_invoice_request')
            ->where('Status', '!=', 2)
            ->selectRaw('
                COUNT(*) as total_batches,
                COALESCE(SUM(TotalInvoices), 0) as total_invoices,
                COALESCE(SUM(TotalAmount), 0) as total_amount,
                COALESCE(SUM(TotalTax), 0) as total_tax,
                COALESCE(SUM(FbrPostedCount), 0) as total_fbr_posted
            ')
            ->first();

        $batches = BulkInvoiceModel::getListingData($filters)->paginate(20);

        return view('pages.dashboards.Invoice.bulk-invoices-list', compact('batches', 'summary', 'filters'));
    }

    /**
     * Download pre-formatted 21-column Excel template for Bulk Invoicing
     */
    public function downloadImportTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Invoice Import');

        // 21 Columns (14 Required with *, 7 Optional)
        $headers = [
            'InvoiceType *',
            'InvoiceDate *',
            'InvoiceRefNo *',
            'SaleOfTerm *',
            'CustomerCode *',
            'ProductCode *',
            'HsCode *',
            'ProductDescription *',
            'SaleType *',
            'FBR Rate *',
            'Quantity *',
            'UOM *',
            'ValueSalesExcludingST *',
            'FixedNotifiedValueOrRetailPrice',
            'SalesTaxApplicable *',
            'ExtraTax',
            'FurtherTax',
            'SalesTaxWithheldAtSource',
            'SroSchedule',
            'SroItemSerialNo',
            'PetroleumLevyOn',
        ];

        $sheet->fromArray([$headers], NULL, 'A1');

        // Base Header Style: Dark Teal (#136056)
        $baseHeaderStyle = [
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '136056']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D1D5DB']]],
        ];
        $sheet->getStyle('A1:U1')->applyFromArray($baseHeaderStyle);
        $sheet->getRowDimension(1)->setRowHeight(28);

        // Required Style: Yellow Highlight (#FEF08A)
        $requiredStyle = [
            'font'    => ['bold' => true, 'color' => ['rgb' => '713F12'], 'size' => 11],
            'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEF08A']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => 'EAB308']]],
        ];

        $requiredCols = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'O'];
        foreach ($requiredCols as $col) {
            $sheet->getStyle("{$col}1")->applyFromArray($requiredStyle);
        }

        // Sample Data Rows
        $sampleData = [
            [
                'Sale Invoice', '2026-03-01', 'INV-REF-001', 'CASH', '1000001',
                'PRD-000001', '2710.1931', 'High Speed Diesel (HSD)', 'Petroleum Products', '18.00%',
                100.00, 'Liter', 1000.00, '', 180.00, '', '', '', '', '', 'No Levy',
            ],
            [
                'Sale Invoice', '2026-03-01', 'INV-REF-001', 'CASH', '1000001',
                'PRD-000002', '3004.9099', 'Paracetamol 500mg Tablets', 'Goods at standard rate (default)', '18.00%',
                50.00, 'Packs', 7500.00, '', 1350.00, '', '', '', '', '', 'No Levy',
            ],
            [
                'Sale Invoice', '2026-03-02', 'INV-REF-002', 'CREDIT', '1000002',
                'PRD-000003', '1701.9910', 'Industrial Raw Sugar (Bag 50kg)', 'Goods at Reduced Rate', '1.00%',
                20.00, 'KG', 130000.00, 120.00, 1300.00, '', '', '', 'EIGHTH SCHEDULE Table 1', '70', 'No Levy',
            ],
        ];

        $sheet->fromArray($sampleData, NULL, 'A2');

        // Number formatting
        $sheet->getStyle('K2:K4')->getNumberFormat()->setFormatCode('0.00'); // Quantity
        $sheet->getStyle('M2:R4')->getNumberFormat()->setFormatCode('0.00'); // Values & Taxes

        // Auto size columns
        foreach (range('A', 'U') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'Invoice_Import_Template.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Process bulk invoice Excel/CSV upload
     */
    public function importInvoices(Request $request)
    {
        $request->validate([
            'import_file' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240',
            'batch_title' => 'nullable|string|max:150',
        ], [
            'import_file.required' => 'Please select an Excel or CSV file to import.',
            'import_file.mimes'    => 'Import file must be an Excel workbook (.xlsx, .xls) or CSV file (.csv).',
            'import_file.max'      => 'Import file must not exceed 10 MB.',
        ]);

        try {
            $requestId = (string) Str::uuid();
            $userId = auth()->id() ?? 1;
            $now = now()->toDateTimeString();

            $file = $request->file('import_file');
            $originalName = $file->getClientOriginalName();
            $batchTitle = trim((string) $request->input('batch_title')) ?: ('Import: ' . pathinfo($originalName, PATHINFO_FILENAME));

            $import = new BulkInvoiceImport();
            Excel::import($import, $file);
            $rows = $import->mappedRows();

            if (empty($rows)) {
                $msg = 'No valid data rows found in the imported file.';
                if ($request->expectsJson()) {
                    return response()->json(['status' => 'error', 'message' => $msg], 422);
                }
                return redirect()->back()->with('error', $msg);
            }

            // Stage rows into tbl_temp_invoice_detail
            $insertRows = [];
            foreach ($rows as $index => $row) {
                $insertRows[] = [
                    'ExcelRow'                        => $row['RowNo'] ?? ($index + 2),
                    'InvoiceType'                     => $row['InvoiceType'] ?? 'Sale Invoice',
                    'InvoiceDate'                     => $row['InvoiceDate'] ?? date('Y-m-d'),
                    'DocNo'                           => $row['DocNo'] ?? null,
                    'InvoiceRefNo'                    => $row['InvoiceRefNo'] ?? null,
                    'SaleOfTerm'                      => $row['SaleOfTerm'] ?? 'CASH',
                    'CustomerCode'                    => $row['CustomerCode'] ?? null,
                    'ProductCode'                     => $row['ProductCode'] ?? null,
                    'HsCode'                          => $row['HsCode'] ?? null,
                    'ProductDescription'              => $row['ProductDescription'] ?? null,
                    'SaleTypeDesc'                    => $row['SaleTypeDesc'] ?? null,
                    'SaleTypeToRateDesc'              => $row['SaleTypeToRateDesc'] ?? null,
                    'Quantity'                        => (float) ($row['Quantity'] ?? 0),
                    'Uom_Desc'                        => $row['Uom_Desc'] ?? null,
                    'ValueSalesExcludingST'           => (float) ($row['ValueSalesExcludingST'] ?? 0),
                    'FixedNotifiedValueOrRetailPrice' => $row['FixedNotifiedValueOrRetailPrice'] ?? null,
                    'SalesTaxApplicable'              => (float) ($row['SalesTaxApplicable'] ?? 0),
                    'ExtraTax'                        => (float) ($row['ExtraTax'] ?? 0),
                    'FurtherTax'                      => (float) ($row['FurtherTax'] ?? 0),
                    'SalesTaxWithheldAtSource'        => (float) ($row['SalesTaxWithheldAtSource'] ?? 0),
                    'SroScheduleDesc'                 => $row['SroScheduleDesc'] ?? null,
                    'SroItemSerialNo'                 => $row['SroItemSerialNo'] ?? null,
                    'PetroleumLevyOn'                 => $row['PetroleumLevyOn'] ?? 'No Levy',
                    'TotalValues'                     => (float) ($row['ValueSalesExcludingST'] ?? 0) + (float) ($row['SalesTaxApplicable'] ?? 0),
                    'Statusi'                         => 5,
                    'CreatedBy'                       => $userId,
                    'CreatedOn'                       => $now,
                    'RequestID'                       => $requestId,
                ];
            }

            foreach (array_chunk($insertRows, 200) as $chunk) {
                DB::connection('tenant')->table('tbl_temp_invoice_detail')->insert($chunk);
            }

            // Run resolution, validation, and migration to live tables
            $result = BulkInvoiceModel::save_bulk_import_transaction($requestId, $batchTitle, $originalName, $userId);

            if (! $result['status']) {
                if ($request->expectsJson()) {
                    return response()->json(['status' => 'error', 'message' => $result['message']], 422);
                }
                return redirect()->back()->with('error', $result['message']);
            }

            $successMsg = "Successfully imported {$batchTitle} into Bulk Invoices.";
            if ($request->expectsJson()) {
                return response()->json(['status' => 'success', 'message' => $successMsg]);
            }
            return redirect()->route('bulk-invoices.index')->with('success', $successMsg);

        } catch (\Throwable $e) {
            \Log::error('BulkInvoiceController@importInvoices exception: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $errorMsg = 'Import failed: ' . $e->getMessage();
            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => $errorMsg], 500);
            }
            return redirect()->back()->with('error', $errorMsg);
        }
    }

    /**
     * Get all invoices and line items for a specific bulk batch (for AJAX modal view)
     */
    public function getBatchInvoices(string $requestId)
    {
        $batch = DB::connection('tenant')->table('tbl_bulk_invoice_request')
            ->where('RequestID', $requestId)
            ->first();

        if (!$batch) {
            return response()->json(['status' => 'error', 'message' => 'Batch not found.'], 404);
        }

        $invoices = DB::connection('tenant')->table('tbl_sales_invoice_header as h')
            ->leftJoin('tbl_customer as c', 'c.ID', '=', 'h.CustomerID')
            ->where('h.RequestID', $requestId)
            ->select(
                'h.ID as HeaderID',
                'h.DocNo',
                'h.InvoiceDate',
                'h.SaleOfTerm',
                'h.TotalValues',
                'h.ValueSalesExcludingST',
                'h.SalesTaxApplicable',
                'h.FbrTransactionID',
                'h.FbrResponseJson',
                'h.Statusi',
                'h.Status',
                'c.AccountNo as CustomerCode',
                'c.NTN as CustomerNTN',
                DB::raw("COALESCE(NULLIF(TRIM(c.BusinessName), ''), NULLIF(TRIM(c.DisplayName), ''), TRIM(CONCAT(COALESCE(c.FirstName, ''), ' ', COALESCE(c.LastName, '')))) as CustomerName")
            )
            ->orderBy('h.ID', 'asc')
            ->get();

        $headerIds = $invoices->pluck('HeaderID')->toArray();

        $details = DB::connection('tenant')->table('tbl_sales_invoice_detail as d')
            ->leftJoin('tbl_product as p', 'p.ID', '=', 'd.ProductID')
            ->whereIn('d.InvoiceID', $headerIds)
            ->select(
                'd.ID as DetailID',
                'd.InvoiceID',
                'd.ProductDescription',
                'd.HsCode',
                'd.Uom_Desc',
                'd.Quantity',
                'd.Rate',
                'd.Amount',
                'd.GSTAmount',
                'd.TotalValues',
                'd.SaleTypeDesc',
                'd.SaleTypeToRateDesc',
                'p.Code as ProductCode',
                'p.Name as ProductName'
            )
            ->orderBy('d.ID', 'asc')
            ->get()
            ->groupBy('InvoiceID');

        foreach ($invoices as $inv) {
            $inv->items = $details->get($inv->HeaderID, collect());
            $inv->items_count = count($inv->items);
        }

        return response()->json([
            'status'   => 'success',
            'batch'    => $batch,
            'invoices' => $invoices,
        ]);
    }

    /**
     * Rollback / Delete entire batch of invoices
     */
    public function rollbackBatch(string $requestId)
    {
        $userId = auth()->id() ?? 1;
        $result = BulkInvoiceModel::rollback_batch($requestId, $userId);

        if (! $result['status']) {
            return redirect()->back()->with('error', $result['message']);
        }

        return redirect()->route('bulk-invoices.index')->with('success', $result['message']);
    }
}

<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$controller = new \App\Http\Controllers\CustomerController();
$response = $controller->downloadImportTemplate();

ob_start();
$response->sendContent();
$content = ob_get_clean();

$tmpFile = __DIR__ . '/test_output_advwht.xlsx';
file_put_contents($tmpFile, $content);

$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($tmpFile);
$sheet = $spreadsheet->getSheetByName('Customers');
echo "Highest Column: " . $sheet->getHighestColumn() . "\n";
$headers = $sheet->toArray()[0];
echo "Headers count: " . count($headers) . "\n";
print_r($headers);

$valM = $sheet->getCell('M2')->getDataValidation();
echo "Col M (Province) formula: " . $valM->getFormula1() . "\n";

$valT = $sheet->getCell('T2')->getDataValidation();
echo "Col T (RegisterType) formula: " . $valT->getFormula1() . "\n";

$valV = $sheet->getCell('V2')->getDataValidation();
echo "Col V (Status) formula: " . $valV->getFormula1() . "\n";

// Test importing this template with CustomerImport
$import = new \App\Imports\CustomerImport();
\Maatwebsite\Excel\Facades\Excel::import($import, $tmpFile);
$rows = $import->mappedRows();
echo "\nCustomerImport mapped rows: " . count($rows) . "\n";
foreach ($rows as $r) {
    echo "Row {$r['RowNo']}: Code={$r['AccountNo']}, AdvanceTax={$r['AdvanceTax']}, IsAdvanceTax={$r['IsAdvanceTax']}, RegType={$r['RegisterType']}, Status={$r['Status']}\n";
}

@unlink($tmpFile);

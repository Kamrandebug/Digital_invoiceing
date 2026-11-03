<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$cols = DB::connection('landlord')->select("SELECT ID, FieldKey, OriginalLabel, CustomLabel FROM core_digital_invoicing.tbl_print_template_columns WHERE TemplateID = 1 ORDER BY OriginalDisplayOrder ASC");
foreach ($cols as $c) {
    echo "{$c->ID} | {$c->FieldKey} | {$c->OriginalLabel} | {$c->CustomLabel}\n";
}


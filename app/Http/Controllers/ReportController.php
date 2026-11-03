<?php

namespace App\Http\Controllers;

use App\Models\ReportModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReportController extends Controller
{
    /**
     * Reports Center Landing Page (`/reports`)
     * Displays all reports (`tbl_module.IsReport = 1`) permitted for the logged-in user.
     */
    public function index()
    {
        $reports = ReportModel::getPermittedReports();

        return view('pages.dashboards.Reports.index', [
            'reports' => $reports,
        ]);
    }

    /**
     * Legacy `/reports/sale` route redirect or alias to dynamic Sale Report (`ReportID = 10`).
     */
    public function saleReportRedirect(Request $request)
    {
        return redirect()->route('reports.show', array_merge(['reportId' => 10], $request->query()));
    }

    /**
     * Resolve and prepare full dynamic report payload for `show`, `printView`, and `exportCsv`.
     */
    private function buildReportPayload(Request $request, int $reportId): array
    {
        ReportModel::ensureTenantReportSchemaAndDefaults($reportId);

        $report = DB::connection('tenant')->table('tbl_reports')
            ->where('ReportID', $reportId)
            ->where('Status', 1)
            ->first();

        if (! $report) {
            abort(404, 'Report configuration not found.');
        }

        // Load all visible columns ordered by CustomDisplayOrder -> OriginalDisplayOrder
        $allColumns = DB::connection('tenant')->table('tbl_reports_detail')
            ->where('ReportID', $reportId)
            ->where('IsVisible', 1)
            ->orderByRaw('COALESCE(CustomDisplayOrder, OriginalDisplayOrder, ID) ASC')
            ->get()
            ->map(function ($col) {
                $col->EffectiveLabel  = trim((string) ($col->CustomLabel ?? '')) !== '' ? $col->CustomLabel : $col->OrignalLabel;
                $col->EffectiveIsOn   = (int) ($col->CustomIsOn ?? $col->OrignalIsOn ?? 1);
                $col->EffectiveOrder  = (int) ($col->CustomDisplayOrder ?? $col->OriginalDisplayOrder ?? 1);
                $col->EffectiveAlign    = $col->CustomAlignment ?: ($col->Alignment ?: ($col->OriginalAlignment ?: 'left'));
                $col->EffectiveFormat   = $col->CustomColumnFormat ?: ($col->ColumnFormat ?: ($col->OriginalColumnFormat ?: 'text'));
                $col->EffectiveWidthMin = (int) ($col->CustomWidthMin ?: ($col->WidthMin ?: ($col->OriginalWidthMin ?: 80)));
                $col->EffectiveWidthMax = (int) ($col->CustomWidthMax ?: ($col->WidthMax ?: ($col->OriginalWidthMax ?: 160)));
                return $col;
            })
            ->values();

        // Active (turned ON) columns for rendering the table
        $activeColumns = $allColumns->filter(fn ($c) => $c->EffectiveIsOn === 1)->values();

        // Map column name => effective label
        $colLabelMap = [];
        foreach ($allColumns as $c) {
            $colLabelMap[$c->ColumName] = $c->EffectiveLabel;
        }

        // Groupable columns (all visible columns; non-total dimension columns listed first)
        $groupableColumns = $allColumns->filter(fn ($c) => (int) ($c->IsTotal ?? 0) === 0)->values();

        // Load dynamic report filter parameters from tbl_reports_parameters
        $parameters = ReportModel::getReportParameters($reportId);

        // Extract filter values from request (falling back to DefaultValue in tbl_reports_parameters)
        $filters = [];
        foreach ($parameters as $param) {
            $paramKey = (string) $param->Parameter;
            $val      = $request->input($paramKey, $param->DefaultValue ?? '');
            $filters[$paramKey] = $val;
        }

        // Extract Grouping (Dropdown 1: `group_by`, Dropdown 2: `multi_level`)
        $hasGroupQuery = $request->has('group_by') || $request->has('multi_level');
        $groupByCol    = '';
        $multiLevelCols = [];

        if ($hasGroupQuery) {
            $groupByCol = trim((string) $request->input('group_by', ''));
            $rawMulti   = $request->input('multi_level', []);
            if (is_string($rawMulti)) {
                $rawMulti = array_filter(array_map('trim', explode(',', $rawMulti)));
            } elseif (is_array($rawMulti)) {
                $rawMulti = array_filter(array_map('trim', $rawMulti));
            } else {
                $rawMulti = [];
            }
            $multiLevelCols = array_values(array_diff($rawMulti, [$groupByCol, '']));
        } elseif (! empty($report->RowGroupColumnName)) {
            $savedGroups = array_values(array_filter(array_map('trim', explode(',', (string) $report->RowGroupColumnName))));
            $groupByCol  = $savedGroups[0] ?? '';
            $multiLevelCols = array_slice($savedGroups, 1);
        }

        // Validate group columns exist in $colLabelMap
        if ($groupByCol !== '' && ! isset($colLabelMap[$groupByCol])) {
            $groupByCol = '';
        }
        $multiLevelCols = array_values(array_filter($multiLevelCols, fn ($c) => isset($colLabelMap[$c]) && $c !== $groupByCol));

        $groupCols = [];
        if ($groupByCol !== '') {
            $groupCols[] = $groupByCol;
        }
        foreach ($multiLevelCols as $mc) {
            if (! in_array($mc, $groupCols, true)) {
                $groupCols[] = $mc;
            }
        }

        // Dynamically invoke the report function on ReportModel
        $modelName = trim((string) ($report->ReportModel ?: 'ReportModel'));
        $modelClass = str_starts_with($modelName, 'App\\') ? $modelName : ('App\\Models\\' . $modelName);
        $functionName = trim((string) ($report->ReportFunction ?: 'get_sale_report'));

        if (! class_exists($modelClass) || ! method_exists($modelClass, $functionName)) {
            abort(500, "Report function {$modelClass}::{$functionName}() is not defined.");
        }

        $rows = $modelClass::$functionName($filters);

        // Extract Order By (Column Header Sorting)
        $sortBy  = trim((string) $request->input('sort_by', ''));
        $sortDir = strtolower(trim((string) $request->input('sort_dir', 'asc')));
        if (! in_array($sortDir, ['asc', 'desc'], true)) {
            $sortDir = 'asc';
        }
        if ($sortBy !== '' && ! isset($colLabelMap[$sortBy])) {
            $sortBy = '';
        }
        if ($sortBy !== '') {
            $sortColObj = $allColumns->firstWhere('ColumName', $sortBy);
            $sortFormat = $sortColObj ? $sortColObj->EffectiveFormat : 'text';
            $rows       = ReportModel::sortReportRows($rows, $sortBy, $sortDir, $sortFormat);
        }

        // Calculate Grand Totals and Multi-Level Group Tree
        $grandTotals = ReportModel::calculateTotals($rows, $activeColumns);
        $groupTree   = ! empty($groupCols)
            ? ReportModel::buildGroupTree($rows, $activeColumns, $groupCols, $colLabelMap)
            : [];

        return [
            'report'           => $report,
            'reportId'         => $reportId,
            'allColumns'       => $allColumns,
            'activeColumns'    => $activeColumns,
            'groupableColumns' => $groupableColumns,
            'colLabelMap'      => $colLabelMap,
            'parameters'       => $parameters,
            'filters'          => $filters,
            'groupByCol'       => $groupByCol,
            'multiLevelCols'   => $multiLevelCols,
            'groupCols'        => $groupCols,
            'sortBy'           => $sortBy,
            'sortDir'          => $sortDir,
            'rows'             => $rows,
            'groupTree'        => $groupTree,
            'grandTotals'      => $grandTotals,
        ];
    }

    /**
     * Single Unified Dynamic Report Viewer (`/reports/{reportId}`)
     */
    public function show(Request $request, int $reportId)
    {
        $payload = $this->buildReportPayload($request, $reportId);

        return view('pages.dashboards.Reports.report-template', $payload);
    }

    /**
     * Save Report Customization (`tbl_reports` + `tbl_reports_detail`)
     * Uses `tbl_temp_reports_detail` + `RequestID` + `ReportModel::edit_transaction($requestID)`.
     */
    public function saveSettings(Request $request, int $reportId)
    {
        ReportModel::ensureTenantReportSchemaAndDefaults($reportId);

        $customReportName = trim((string) $request->input('CustomName', ''));
        $printOrientation = in_array($request->input('PrintOrientation'), ['Portrait', 'Landscape'], true)
            ? $request->input('PrintOrientation')
            : 'Landscape';
        $rowGroupCol      = trim((string) $request->input('RowGroupColumnName', ''));
        $submittedCols    = $request->input('columns', []);

        if (empty($submittedCols) || ! is_array($submittedCols)) {
            return response()->json([
                'status'  => false,
                'message' => 'No column configuration received.',
            ], 422);
        }

        $requestID = 'RPT-' . strtoupper(Str::random(10));
        $userId    = (int) (session('selected_tenant.user_id') ?: auth()->id() ?: 1);
        $now       = now();

        $tempRows = [];
        $orderCounter = 1;

        foreach ($submittedCols as $colData) {
            $colName = trim((string) ($colData['ColumName'] ?? ''));
            if ($colName === '') {
                continue;
            }
            $align = strtolower(trim((string) ($colData['CustomAlignment'] ?? 'left')));
            if (! in_array($align, ['left', 'center', 'right'], true)) {
                $align = 'left';
            }

            $widthMin = isset($colData['CustomWidthMin']) && $colData['CustomWidthMin'] !== '' ? (int) $colData['CustomWidthMin'] : null;
            $widthMax = isset($colData['CustomWidthMax']) && $colData['CustomWidthMax'] !== '' ? (int) $colData['CustomWidthMax'] : null;

            $tempRows[] = [
                'RequestID'          => $requestID,
                'ReportID'           => $reportId,
                'CustomReportName'   => $customReportName,
                'PrintOrientation'   => $printOrientation,
                'RowGroupColumnName' => $rowGroupCol !== '' ? $rowGroupCol : null,
                'ColumName'          => $colName,
                'CustomDisplayOrder' => (int) ($colData['CustomDisplayOrder'] ?? $orderCounter),
                'CustomLabel'        => trim((string) ($colData['CustomLabel'] ?? '')),
                'CustomIsOn'         => ! empty($colData['CustomIsOn']) ? 1 : 0,
                'IsTotal'            => ! empty($colData['IsTotal']) ? 1 : 0,
                'CustomWidthMin'     => $widthMin,
                'CustomWidthMax'     => $widthMax,
                'CustomAlignment'    => $align,
                'CreatedBy'          => $userId,
                'CreatedOn'          => $now,
                'UpdatedBy'          => $userId,
                'UpdatedOn'          => $now,
            ];
            $orderCounter++;
        }

        DB::connection('tenant')->table('tbl_temp_reports_detail')->insert($tempRows);

        $result = ReportModel::edit_transaction($requestID);

        if (! $result['status']) {
            return response()->json($result, 422);
        }

        return response()->json($result);
    }

    /**
     * Reset Report Settings to Original Master Values from `core_digital_invoicing`.
     */
    public function resetSettings(Request $request, int $reportId)
    {
        $userId = (int) (session('selected_tenant.user_id') ?: auth()->id() ?: 1);
        $result = ReportModel::reset_transaction($reportId, $userId);

        return response()->json($result);
    }

    /**
     * Print / PDF View (`/reports/{reportId}/print`)
     */
    public function printView(Request $request, int $reportId)
    {
        $payload = $this->buildReportPayload($request, $reportId);
        $payload['printed_at'] = now();

        return view('pages.dashboards.Reports.report-print', $payload);
    }

    /**
     * Dynamic CSV Export (`/reports/{reportId}/export`)
     * Exports active columns in custom display order, including group headers, subtotals, and grand totals.
     */
    public function exportCsv(Request $request, int $reportId)
    {
        $payload       = $this->buildReportPayload($request, $reportId);
        $report        = $payload['report'];
        $activeColumns = $payload['activeColumns'];
        $rows          = $payload['rows'];
        $groupTree     = $payload['groupTree'];
        $grandTotals   = $payload['grandTotals'];
        $groupCols     = $payload['groupCols'];

        $slugName = Str::slug($report->CustomName ?: $report->ReportName ?: 'report');
        $filename = $slugName . '-' . date('Y-m-d-His') . '.csv';

        $headers = [
            'Content-type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename={$filename}",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () use ($activeColumns, $rows, $groupTree, $grandTotals, $groupCols) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Header row
            $headerLabels = $activeColumns->map(fn ($c) => $c->EffectiveLabel)->toArray();
            fputcsv($file, $headerLabels);

            $writeDetailRows = function ($detailRows) use ($file, $activeColumns) {
                foreach ($detailRows as $r) {
                    $line = [];
                    foreach ($activeColumns as $col) {
                        $val = $r->{$col->ColumName} ?? '';
                        $line[] = ReportModel::formatValue($val, $col->EffectiveFormat);
                    }
                    fputcsv($file, $line);
                }
            };

            $writeSubtotalRow = function (string $label, array $totals) use ($file, $activeColumns) {
                $line = [];
                foreach ($activeColumns as $idx => $col) {
                    if ($idx === 0) {
                        $line[] = $label;
                    } elseif ((int) ($col->IsTotal ?? 0) === 1) {
                        $line[] = ReportModel::formatValue($totals[$col->ColumName] ?? 0, $col->EffectiveFormat);
                    } else {
                        $line[] = '';
                    }
                }
                fputcsv($file, $line);
            };

            $writeGroupNodes = null;
            $writeGroupNodes = function (array $nodes) use (&$writeGroupNodes, $writeDetailRows, $writeSubtotalRow, $file, $activeColumns) {
                foreach ($nodes as $node) {
                    $indent = str_repeat('  ', max(0, $node['level'] - 1));
                    $headerLine = array_fill(0, $activeColumns->count(), '');
                    $headerLine[0] = "{$indent}[{$node['columnLabel']}: {$node['value']} ({$node['count']} records)]";
                    fputcsv($file, $headerLine);

                    if (! empty($node['children'])) {
                        $writeGroupNodes($node['children']);
                    } else {
                        $writeDetailRows($node['rows']);
                    }

                    $writeSubtotalRow("{$indent}Subtotal — {$node['columnLabel']}: {$node['value']} ({$node['count']})", $node['totals']);
                }
            };

            if (! empty($groupCols) && ! empty($groupTree)) {
                $writeGroupNodes($groupTree);
            } else {
                $writeDetailRows($rows);
            }

            // Overall Grand Total row
            $writeSubtotalRow('Grand Total (' . ($grandTotals['__count'] ?? 0) . ' records)', $grandTotals);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}


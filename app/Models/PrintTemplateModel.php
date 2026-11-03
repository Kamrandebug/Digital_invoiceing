<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PrintTemplateModel extends Model
{
    use HasFactory;

    protected $table = 'tbl_print_templates';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    /**
     * Quoted active tenant database name for cross-DB / set-based SQL statements.
     */
    public static function tenantDatabaseQuoted(): string
    {
        $tenantDb = session('selected_tenant.dbname')
            ?: (DB::connection('tenant')->getDatabaseName() ?: config('database.connections.tenant.database'));
        $tenantDb = trim((string) $tenantDb);

        if ($tenantDb === '' || ! preg_match('/^[A-Za-z0-9_]+$/', $tenantDb)) {
            throw new \RuntimeException('Invalid tenant database name in session.');
        }

        return '`' . str_replace('`', '``', $tenantDb) . '`';
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  INVOICE PRINT DATA & TEMPLATE QUERIES
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Fetch all data required to print an invoice:
     * 1. Invoice Header + Customer Display Name (`tbl_sales_invoice_header` + `tbl_customer`)
     * 2. Invoice Line Items + Product Details + Unit Name (`tbl_sales_invoice_detail` + `tbl_product` + `tbl_Unit`)
     * 3. Seller Business Profile (`tbl_business_info`)
     * 4. Buyer Profile (`tbl_customer`)
     * 5. Print Templates, Active Template, Fields (`tbl_print_template_fields`), and Columns (`tbl_print_template_columns`)
     */
    public static function getInvoicePrintData(int $invoiceId, int $moduleId = 4, ?int $requestedTemplateId = null): ?array
    {
        // 1. Invoice Header Query
        $invoiceRows = DB::connection('tenant')->select("
            SELECT
                h.*,
                CASE
                    WHEN cust.ID IS NULL THEN '—'
                    WHEN TRIM(CONCAT(COALESCE(cust.FirstName, ''), ' ', COALESCE(cust.LastName, ''))) <> ''
                        THEN TRIM(CONCAT(COALESCE(cust.FirstName, ''), ' ', COALESCE(cust.LastName, '')))
                    WHEN COALESCE(TRIM(cust.BusinessName), '') <> ''
                        THEN TRIM(cust.BusinessName)
                    ELSE CONCAT('Customer #', cust.ID)
                END AS CustomerDisplayName
            FROM `tbl_sales_invoice_header` h
            LEFT JOIN `tbl_customer` cust ON h.`CustomerID` = cust.`ID`
            WHERE h.`ID` = ?
              AND h.`Status` != 2
            LIMIT 1
        ", [$invoiceId]);

        if (empty($invoiceRows)) {
            return null;
        }
        $invoice = $invoiceRows[0];

        // 2. Invoice Line Items Query (prepared columns matching tbl_print_template_columns.FieldKey)
        $detailInvoiceId = $invoice->InvoiceID ?? $invoice->ID;
        $items = collect(DB::connection('tenant')->select("
            SELECT
                d.ID,
                d.InvoiceID,
                d.ProductID,
                d.Unit AS raw_Unit,

                -- Raw numeric fields for footer totals and calculation
                COALESCE(d.Quantity, 0) AS raw_Quantity,
                COALESCE(CASE WHEN d.Unit > 1 AND d.FBRQty IS NOT NULL THEN d.FBRQty ELSE d.Quantity END, 0) AS raw_FbrQty,
                COALESCE(d.Amount, 0) AS raw_Amount,
                COALESCE(d.DiscountAmount, d.Discount, 0) AS raw_Discount,
                COALESCE(d.FixedNotifiedValueOrRetailPrice, 0) AS raw_FixedNotifiedValue,
                COALESCE(d.GSTAmount, d.SalesTaxApplicable, 0) AS raw_SalesTaxAmount,
                COALESCE(d.ExtraTax, 0) AS raw_ExtraTax,
                COALESCE(d.FurtherTax, 0) AS raw_FurtherTax,
                COALESCE(d.FedPayable, 0) AS raw_FedPayable,
                COALESCE(d.TotalValues, 0) AS raw_TotalValues,

                -- 1. Atomic Description Columns
                COALESCE(NULLIF(TRIM(CONVERT(p.Name USING utf8mb4)), ''), '—') AS ProductName,
                COALESCE(NULLIF(TRIM(CONVERT(d.HsCode USING utf8mb4)), ''), '—') AS HsCode,
                COALESCE(NULLIF(TRIM(CONVERT(d.ProductDescription USING utf8mb4)), ''), '—') AS ProductDescription,

                -- 2. Combined Description Column (with HTML <strong> & sub-details)
                CONCAT(
                    CASE
                        WHEN TRIM(COALESCE(CONVERT(p.Name USING utf8mb4), '')) <> ''
                            THEN CONCAT('<strong>', TRIM(CONVERT(p.Name USING utf8mb4)), '</strong>')
                        ELSE ''
                    END,
                    CASE
                        WHEN TRIM(COALESCE(CONVERT(d.HsCode USING utf8mb4), '')) <> ''
                            THEN CONCAT(' <span class=\"text-muted\" style=\"font-size:10.5px;\">(HS Code: ', TRIM(CONVERT(d.HsCode USING utf8mb4)), ')</span>')
                        ELSE ''
                    END,
                    CASE
                        WHEN TRIM(COALESCE(CONVERT(d.ProductDescription USING utf8mb4), '')) <> ''
                             AND TRIM(COALESCE(CONVERT(d.ProductDescription USING utf8mb4), '')) <> TRIM(COALESCE(CONVERT(p.Name USING utf8mb4), ''))
                            THEN CONCAT('<div class=\"text-muted mt-1\" style=\"font-size:11.5px; line-height:1.35;\">', TRIM(CONVERT(d.ProductDescription USING utf8mb4)), '</div>')
                        ELSE ''
                    END
                ) AS CombinedDescription,

                -- 3. Units
                CASE
                    WHEN d.Unit > 1
                         AND TRIM(COALESCE(CONVERT(u.UnitName USING utf8mb4), '')) <> ''
                         AND TRIM(CONVERT(u.UnitName USING utf8mb4)) NOT IN ('No Unit', '—', '-')
                        THEN TRIM(CONVERT(u.UnitName USING utf8mb4))
                    WHEN TRIM(COALESCE(CONVERT(d.Uom_Desc USING utf8mb4), '')) <> ''
                         AND TRIM(CONVERT(d.Uom_Desc USING utf8mb4)) NOT IN ('No Unit', '—', '-')
                        THEN TRIM(CONVERT(d.Uom_Desc USING utf8mb4))
                    ELSE '—'
                END AS Unit,

                CASE
                    WHEN d.Unit > 1
                         AND TRIM(COALESCE(CONVERT(u.UnitName USING utf8mb4), '')) <> ''
                         AND TRIM(CONVERT(u.UnitName USING utf8mb4)) NOT IN ('No Unit', '—', '-')
                        THEN TRIM(CONVERT(u.UnitName USING utf8mb4))
                    ELSE '—'
                END AS CustomUnit,

                CASE
                    WHEN TRIM(COALESCE(CONVERT(d.Uom_Desc USING utf8mb4), '')) <> ''
                         AND TRIM(CONVERT(d.Uom_Desc USING utf8mb4)) NOT IN ('No Unit', '—', '-')
                        THEN TRIM(CONVERT(d.Uom_Desc USING utf8mb4))
                    ELSE '—'
                END AS HsUnit,

                -- 4. Quantities (Combined Qty+Unit & Numeric)
                CASE
                    WHEN d.Quantity = FLOOR(d.Quantity) THEN FORMAT(d.Quantity, 0)
                    ELSE FORMAT(d.Quantity, 2)
                END AS Quantity,

                CONCAT(
                    CASE
                        WHEN d.Quantity = FLOOR(d.Quantity) THEN FORMAT(d.Quantity, 0)
                        ELSE FORMAT(d.Quantity, 2)
                    END,
                    CASE
                        WHEN d.Unit > 1
                             AND TRIM(COALESCE(CONVERT(u.UnitName USING utf8mb4), '')) <> ''
                             AND TRIM(CONVERT(u.UnitName USING utf8mb4)) NOT IN ('No Unit', '—', '-')
                            THEN CONCAT(' <small class=\"text-muted ms-1\">', TRIM(CONVERT(u.UnitName USING utf8mb4)), '</small>')
                        WHEN TRIM(COALESCE(CONVERT(d.Uom_Desc USING utf8mb4), '')) <> ''
                             AND TRIM(CONVERT(d.Uom_Desc USING utf8mb4)) NOT IN ('No Unit', '—', '-')
                            THEN CONCAT(' <small class=\"text-muted ms-1\">', TRIM(CONVERT(d.Uom_Desc USING utf8mb4)), '</small>')
                        ELSE ''
                    END
                ) AS QtyWithUnit,

                CASE
                    WHEN (CASE WHEN d.Unit > 1 AND d.FBRQty IS NOT NULL THEN d.FBRQty ELSE d.Quantity END) = FLOOR(CASE WHEN d.Unit > 1 AND d.FBRQty IS NOT NULL THEN d.FBRQty ELSE d.Quantity END)
                        THEN FORMAT(CASE WHEN d.Unit > 1 AND d.FBRQty IS NOT NULL THEN d.FBRQty ELSE d.Quantity END, 0)
                    ELSE FORMAT(CASE WHEN d.Unit > 1 AND d.FBRQty IS NOT NULL THEN d.FBRQty ELSE d.Quantity END, 2)
                END AS FbrQty,

                CONCAT(
                    CASE
                        WHEN (CASE WHEN d.Unit > 1 AND d.FBRQty IS NOT NULL THEN d.FBRQty ELSE d.Quantity END) = FLOOR(CASE WHEN d.Unit > 1 AND d.FBRQty IS NOT NULL THEN d.FBRQty ELSE d.Quantity END)
                            THEN FORMAT(CASE WHEN d.Unit > 1 AND d.FBRQty IS NOT NULL THEN d.FBRQty ELSE d.Quantity END, 0)
                        ELSE FORMAT(CASE WHEN d.Unit > 1 AND d.FBRQty IS NOT NULL THEN d.FBRQty ELSE d.Quantity END, 2)
                    END,
                    CASE
                        WHEN TRIM(COALESCE(CONVERT(d.Uom_Desc USING utf8mb4), '')) <> ''
                             AND TRIM(CONVERT(d.Uom_Desc USING utf8mb4)) NOT IN ('No Unit', '—', '-')
                            THEN CONCAT(' <small class=\"text-muted ms-1\">', TRIM(CONVERT(d.Uom_Desc USING utf8mb4)), '</small>')
                        ELSE ''
                    END
                ) AS FbrQtyWithUnit,

                -- 5. Pricing & Taxes
                FORMAT(COALESCE(d.Rate, 0), 2) AS Rate,
                FORMAT(COALESCE(d.DiscountAmount, d.Discount, 0), 2) AS Discount,
                FORMAT(COALESCE(d.Amount, 0), 2) AS ValueExclTax,
                FORMAT(COALESCE(d.FixedNotifiedValueOrRetailPrice, 0), 2) AS FixedNotifiedValue,
                COALESCE(NULLIF(TRIM(CONVERT(d.SaleTypeToRateDesc USING utf8mb4)), ''), '—') AS TaxRate,
                FORMAT(COALESCE(d.GSTAmount, d.SalesTaxApplicable, 0), 2) AS SalesTaxAmount,
                FORMAT(COALESCE(d.ExtraTax, 0), 2) AS ExtraTax,
                FORMAT(COALESCE(d.FurtherTax, 0), 2) AS FurtherTax,
                FORMAT(COALESCE(d.FedPayable, 0), 2) AS FedPayable,
                FORMAT(COALESCE(d.TotalValues, 0), 2) AS TotalInclTax

            FROM `tbl_sales_invoice_detail` d
            LEFT JOIN `tbl_product` p ON d.`ProductID` = p.`ID`
            LEFT JOIN `tbl_Unit` u ON d.`Unit` = u.`id`
            WHERE d.`InvoiceID` = ?
              AND d.`Status` != 2
            ORDER BY d.`ID` ASC
        ", [$detailInvoiceId]));

        // 3. Seller Profile Query (`tbl_business_info`)
        $sellerRows = DB::connection('tenant')->select("
            SELECT *
            FROM `tbl_business_info`
            WHERE `IsArchive` = 0
            ORDER BY `ID` ASC
            LIMIT 1
        ");
        $seller = $sellerRows[0] ?? null;

        // 4. Buyer Profile Query (`tbl_customer`)
        $buyer = null;
        if (! empty($invoice->CustomerID)) {
            $buyerRows = DB::connection('tenant')->select("
                SELECT *
                FROM `tbl_customer`
                WHERE `ID` = ?
                LIMIT 1
            ", [(int) $invoice->CustomerID]);
            $buyer = $buyerRows[0] ?? null;
        }

        // 5. Print Templates, Active Template, Fields & Columns
        $allTemplates    = self::getTemplates($moduleId);
        $template        = self::getActiveTemplate($moduleId, $requestedTemplateId);
        $templateFields  = $template ? self::getTemplateFields((int) $template->ID) : collect();
        $templateColumns = $template ? self::getTemplateColumns((int) $template->ID) : collect();

        return [
            'invoice'         => $invoice,
            'items'           => $items,
            'seller'          => $seller,
            'buyer'           => $buyer,
            'template'        => $template,
            'allTemplates'    => $allTemplates,
            'templateFields'  => $templateFields,
            'templateColumns' => $templateColumns,
        ];
    }

    /**
     * Get all active templates for a module (e.g. ModuleID = 4 for Sales Invoice).
     */
    public static function getTemplates(int $moduleId = 4): Collection
    {
        return collect(DB::connection('tenant')->select("
            SELECT *
            FROM `tbl_print_templates`
            WHERE `ModuleID` = ?
              AND `IsActive` = 1
            ORDER BY `IsDefault` DESC, `ID` ASC
        ", [$moduleId]));
    }

    /**
     * Get the active template by ID or default for the module.
     */
    public static function getActiveTemplate(int $moduleId = 4, ?int $templateId = null): ?object
    {
        if ($templateId !== null && $templateId > 0) {
            $rows = DB::connection('tenant')->select("
                SELECT *
                FROM `tbl_print_templates`
                WHERE `ModuleID` = ?
                  AND `ID` = ?
                  AND `IsActive` = 1
                LIMIT 1
            ", [$moduleId, $templateId]);

            if (! empty($rows)) {
                return $rows[0];
            }
        }

        $rows = DB::connection('tenant')->select("
            SELECT *
            FROM `tbl_print_templates`
            WHERE `ModuleID` = ?
              AND `IsActive` = 1
            ORDER BY `IsDefault` DESC, `ID` ASC
            LIMIT 1
        ", [$moduleId]);

        return $rows[0] ?? null;
    }

    /**
     * Get all header/seller/buyer/summary/notes fields for a template, ordered by CustomDisplayOrder.
     */
    public static function getTemplateFields(int $templateId): Collection
    {
        return collect(DB::connection('tenant')->select("
            SELECT *
            FROM `tbl_print_template_fields`
            WHERE `TemplateID` = ?
            ORDER BY `CustomDisplayOrder` ASC, `ID` ASC
        ", [$templateId]));
    }

    /**
     * Get all line-item table columns for a template, ordered by CustomDisplayOrder.
     */
    public static function getTemplateColumns(int $templateId): Collection
    {
        return collect(DB::connection('tenant')->select("
            SELECT *
            FROM `tbl_print_template_columns`
            WHERE `TemplateID` = ?
            ORDER BY `CustomDisplayOrder` ASC, `ID` ASC
        ", [$templateId]));
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  TEMP TABLE VALIDATION & SET-BASED TRANSACTIONS
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Validate staged rows in `tbl_temp_print_template_fields` and `tbl_temp_print_template_columns`.
     */
    private static function runTempValidation(string $requestID, string $tenantDbQuoted, array $headerData): ?array
    {
        $templateName = trim((string) ($headerData['TemplateName'] ?? ''));
        $customTitle  = trim((string) ($headerData['CustomTitle'] ?? ''));

        if ($templateName === '') {
            return ['status' => false, 'message' => 'Template Name is required.'];
        }
        if ($customTitle === '') {
            return ['status' => false, 'message' => 'Invoice Document Title is required.'];
        }

        // 1. Validate fields temp table
        DB::connection('landlord')->statement("
            UPDATE {$tenantDbQuoted}.`tbl_temp_print_template_fields` uu
            JOIN (
                SELECT t.ID,
                    CASE
                        WHEN COALESCE(TRIM(t.CustomLabel), '') = '' THEN 'Field label cannot be empty'
                        WHEN t.CustomFontSize < 8 OR t.CustomFontSize > 28 THEN 'Field font size must be between 8px and 28px'
                        ELSE 'Pass'
                    END AS ErrorMessage
                FROM {$tenantDbQuoted}.`tbl_temp_print_template_fields` t
                WHERE t.RequestID = ?
            ) u ON u.ID = uu.ID
            SET uu.ErrorMessage = u.ErrorMessage
        ", [$requestID]);

        $fieldErrors = DB::connection('landlord')->selectOne("
            SELECT
                IFNULL(COUNT(*), 0) AS cnt,
                GROUP_CONCAT(DISTINCT t.ErrorMessage ORDER BY t.ErrorMessage SEPARATOR ', ') AS ErrMsg
            FROM {$tenantDbQuoted}.`tbl_temp_print_template_fields` t
            WHERE t.ErrorMessage IS NOT NULL
              AND t.ErrorMessage <> 'Pass'
              AND t.RequestID = ?
        ", [$requestID]);

        if ((int) ($fieldErrors->cnt ?? 0) > 0) {
            return [
                'status'  => false,
                'message' => $fieldErrors->ErrMsg ?: 'Invalid header/seller/buyer field settings.',
            ];
        }

        // 2. Validate columns temp table
        DB::connection('landlord')->statement("
            UPDATE {$tenantDbQuoted}.`tbl_temp_print_template_columns` uu
            JOIN (
                SELECT t.ID,
                    CASE
                        WHEN COALESCE(TRIM(t.CustomLabel), '') = '' THEN 'Column header label cannot be empty'
                        WHEN CAST(REPLACE(COALESCE(t.CustomWidthMin, '30'), 'px', '') AS UNSIGNED) < 25 THEN CONCAT('Min width must be at least 25px for ', COALESCE(t.CustomLabel, 'column'))
                        WHEN CAST(REPLACE(COALESCE(t.CustomWidthMax, '100'), 'px', '') AS UNSIGNED) < CAST(REPLACE(COALESCE(t.CustomWidthMin, '30'), 'px', '') AS UNSIGNED) THEN CONCAT('Max width cannot be less than Min width for ', COALESCE(t.CustomLabel, 'column'))
                        WHEN NOT EXISTS (
                            SELECT 1 FROM {$tenantDbQuoted}.`tbl_temp_print_template_columns` t2
                            WHERE t2.RequestID = t.RequestID AND t2.CustomIsOn IN (1, 2)
                        ) THEN 'At least one line-item column must be visible (Show or Auto-Hide)'
                        ELSE 'Pass'
                    END AS ErrorMessage
                FROM {$tenantDbQuoted}.`tbl_temp_print_template_columns` t
                WHERE t.RequestID = ?
            ) u ON u.ID = uu.ID
            SET uu.ErrorMessage = u.ErrorMessage
        ", [$requestID]);

        $colErrors = DB::connection('landlord')->selectOne("
            SELECT
                IFNULL(COUNT(*), 0) AS cnt,
                GROUP_CONCAT(DISTINCT t.ErrorMessage ORDER BY t.ErrorMessage SEPARATOR ', ') AS ErrMsg
            FROM {$tenantDbQuoted}.`tbl_temp_print_template_columns` t
            WHERE t.ErrorMessage IS NOT NULL
              AND t.ErrorMessage <> 'Pass'
              AND t.RequestID = ?
        ", [$requestID]);

        if ((int) ($colErrors->cnt ?? 0) > 0) {
            return [
                'status'  => false,
                'message' => $colErrors->ErrMsg ?: 'Invalid line-item column settings.',
            ];
        }

        return null;
    }

    /**
     * Normalize header settings payload.
     */
    private static function normalizeHeaderData(array $headerData, int $userId): array
    {
        $orientation = strtolower(trim((string) ($headerData['PrintOrientation'] ?? 'portrait')));
        if (! in_array($orientation, ['portrait', 'landscape'], true)) {
            $orientation = 'portrait';
        }

        $paperSize = trim((string) ($headerData['PaperSize'] ?? 'A4'));
        if (! in_array($paperSize, ['A4', 'Letter', 'A5'], true)) {
            $paperSize = 'A4';
        }

        $themeStyle = strtolower(trim((string) ($headerData['ThemeStyle'] ?? 'classic')));
        if (! in_array($themeStyle, ['classic', 'modern', 'compact'], true)) {
            $themeStyle = 'classic';
        }

        $accentColor = trim((string) ($headerData['AccentColor'] ?? '#1e3a5f'));
        if (! preg_match('/^#[0-9A-Fa-f]{3,6}$/', $accentColor)) {
            $accentColor = '#1e3a5f';
        }

        $baseFontSize  = max(9, min(16, (int) ($headerData['BaseFontSize'] ?? 12)));
        $logoMaxHeight = max(40, min(200, (int) ($headerData['LogoMaxHeight'] ?? 105)));
        $topMarginPx   = max(10, min(250, (int) ($headerData['TopMarginPx'] ?? 35)));

        return [
            'TemplateName'        => trim((string) ($headerData['TemplateName'] ?? 'Standard Tax Invoice')),
            'CustomTitle'         => trim((string) ($headerData['CustomTitle'] ?? 'Sales Tax Invoice')),
            'IsDefault'           => ! empty($headerData['IsDefault']) ? 1 : 0,
            'PrintOrientation'    => $orientation,
            'PaperSize'           => $paperSize,
            'ThemeStyle'          => $themeStyle,
            'AccentColor'         => $accentColor,
            'FontFamily'          => trim((string) ($headerData['FontFamily'] ?? 'Arial, sans-serif')),
            'BaseFontSize'        => $baseFontSize,
            'ShowLogo'            => ! empty($headerData['ShowLogo']) ? 1 : 0,
            'LogoMaxHeight'       => $logoMaxHeight,
            'TopMarginPx'         => $topMarginPx,
            'ShowSellerHeader'    => ! empty($headerData['ShowSellerHeader']) ? 1 : 0,
            'SellerHeading'       => trim((string) ($headerData['SellerHeading'] ?? 'Seller')) ?: 'Seller',
            'BuyerHeading'        => trim((string) ($headerData['BuyerHeading'] ?? 'Buyer')) ?: 'Buyer',
            'ShowDraftWatermark'  => ! empty($headerData['ShowDraftWatermark']) ? 1 : 0,
            'ShowAmountInWords'   => ! empty($headerData['ShowAmountInWords']) ? 1 : 0,
            'ShowBankDetails'     => ! empty($headerData['ShowBankDetails']) ? 1 : 0,
            'BankDetailsText'     => trim((string) ($headerData['BankDetailsText'] ?? '')),
            'ShowSignatureBox'    => ! empty($headerData['ShowSignatureBox']) ? 1 : 0,
            'SignatureLabelLeft'  => trim((string) ($headerData['SignatureLabelLeft'] ?? 'Prepared / Checked By')),
            'SignatureLabelRight' => trim((string) ($headerData['SignatureLabelRight'] ?? 'Authorized Signature & Stamp')),
            'UpdatedOn'           => now(),
            'UpdatedBy'           => $userId,
        ];
    }

    /**
     * Validate temp rows and perform set-based UPDATE on `tbl_print_templates`,
     * `tbl_print_template_fields`, and `tbl_print_template_columns`.
     */
    public static function edit_transaction(string $requestID, int $templateId, array $headerData, int $userId = 1): array
    {
        $tenantDbQuoted  = self::tenantDatabaseQuoted();
        $validationError = self::runTempValidation($requestID, $tenantDbQuoted, $headerData);
        if ($validationError !== null) {
            return $validationError;
        }

        $tpl = DB::connection('tenant')->table('tbl_print_templates')->where('ID', $templateId)->first();
        if (! $tpl) {
            return ['status' => false, 'message' => 'Print template not found.'];
        }

        $normalized = self::normalizeHeaderData($headerData, $userId);

        if ($normalized['IsDefault'] === 1) {
            DB::connection('tenant')->table('tbl_print_templates')
                ->where('ModuleID', $tpl->ModuleID)
                ->update(['IsDefault' => 0]);
        } else {
            $otherDefaults = DB::connection('tenant')->table('tbl_print_templates')
                ->where('ModuleID', $tpl->ModuleID)
                ->where('ID', '!=', $templateId)
                ->where('IsActive', 1)
                ->where('IsDefault', 1)
                ->count();
            if ($otherDefaults === 0) {
                $normalized['IsDefault'] = 1;
            }
        }

        DB::connection('tenant')->table('tbl_print_templates')
            ->where('ID', $templateId)
            ->update($normalized);

        // Set-based UPDATE on tbl_print_template_fields from tbl_temp_print_template_fields
        DB::connection('landlord')->statement("
            UPDATE {$tenantDbQuoted}.`tbl_print_template_fields` f
            INNER JOIN {$tenantDbQuoted}.`tbl_temp_print_template_fields` t
                ON f.ID = t.FieldID AND f.TemplateID = t.TemplateID
            SET
                f.CustomLabel        = TRIM(t.CustomLabel),
                f.CustomIsOn         = t.CustomIsOn,
                f.CustomDisplayOrder = t.CustomDisplayOrder,
                f.CustomFontSize     = t.CustomFontSize,
                f.IsBold             = t.IsBold,
                f.UpdatedOn          = NOW(),
                f.UpdatedBy          = ?
            WHERE t.RequestID = ? AND f.TemplateID = ?
        ", [$userId, $requestID, $templateId]);

        // Set-based UPDATE on tbl_print_template_columns from tbl_temp_print_template_columns
        DB::connection('landlord')->statement("
            UPDATE {$tenantDbQuoted}.`tbl_print_template_columns` c
            INNER JOIN {$tenantDbQuoted}.`tbl_temp_print_template_columns` t
                ON c.ID = t.ColumnID AND c.TemplateID = t.TemplateID
            SET
                c.CustomLabel        = TRIM(t.CustomLabel),
                c.CustomIsOn         = t.CustomIsOn,
                c.CustomDisplayOrder = t.CustomDisplayOrder,
                c.CustomWidthMin     = t.CustomWidthMin,
                c.CustomWidthMax     = t.CustomWidthMax,
                c.CustomAlignment    = t.CustomAlignment,
                c.IsTotal            = t.IsTotal,
                c.UpdatedOn          = NOW(),
                c.UpdatedBy          = ?
            WHERE t.RequestID = ? AND c.TemplateID = ?
        ", [$userId, $requestID, $templateId]);

        // Cleanup temp rows
        DB::connection('landlord')->statement("DELETE FROM {$tenantDbQuoted}.`tbl_temp_print_template_fields` WHERE RequestID = ?", [$requestID]);
        DB::connection('landlord')->statement("DELETE FROM {$tenantDbQuoted}.`tbl_temp_print_template_columns` WHERE RequestID = ?", [$requestID]);

        return [
            'status'      => true,
            'template_id' => $templateId,
            'message'     => 'Print template settings saved successfully.',
        ];
    }

    /**
     * Clone/Save current settings as a brand-new Template in the tenant database.
     */
    public static function save_as_new_template(string $requestID, int $sourceTemplateId, array $headerData, int $userId = 1): array
    {
        $tenantDbQuoted  = self::tenantDatabaseQuoted();
        $validationError = self::runTempValidation($requestID, $tenantDbQuoted, $headerData);
        if ($validationError !== null) {
            return $validationError;
        }

        $sourceTpl = DB::connection('tenant')->table('tbl_print_templates')->where('ID', $sourceTemplateId)->first();
        if (! $sourceTpl) {
            return ['status' => false, 'message' => 'Source print template not found.'];
        }

        $normalized = self::normalizeHeaderData($headerData, $userId);

        if ($normalized['IsDefault'] === 1) {
            DB::connection('tenant')->table('tbl_print_templates')
                ->where('ModuleID', $sourceTpl->ModuleID)
                ->update(['IsDefault' => 0]);
        }

        $newTemplateId = DB::connection('tenant')->table('tbl_print_templates')->insertGetId(array_merge($normalized, [
            'ModuleID'        => $sourceTpl->ModuleID,
            'BaseTemplateID'  => $sourceTpl->BaseTemplateID ?? 1,
            'OriginalTitle'   => $sourceTpl->OriginalTitle ?? 'Sales Tax Invoice',
            'IsSystemDefault' => 0,
            'IsActive'        => 1,
            'CreatedOn'       => now(),
            'CreatedBy'       => $userId,
        ]));

        // Insert fields for the new template by joining source fields with temp fields
        DB::connection('landlord')->statement("
            INSERT INTO {$tenantDbQuoted}.`tbl_print_template_fields`
            (`TemplateID`, `SectionGroup`, `FieldKey`, `OriginalLabel`, `CustomLabel`, `OriginalIsOn`, `CustomIsOn`, `OriginalDisplayOrder`, `CustomDisplayOrder`, `OriginalFontSize`, `CustomFontSize`, `IsBold`, `UpdatedOn`, `UpdatedBy`)
            SELECT
                ?,
                f.SectionGroup,
                f.FieldKey,
                f.OriginalLabel,
                COALESCE(TRIM(t.CustomLabel), f.CustomLabel),
                f.OriginalIsOn,
                COALESCE(t.CustomIsOn, f.CustomIsOn),
                f.OriginalDisplayOrder,
                COALESCE(t.CustomDisplayOrder, f.CustomDisplayOrder),
                f.OriginalFontSize,
                COALESCE(t.CustomFontSize, f.CustomFontSize),
                COALESCE(t.IsBold, f.IsBold),
                NOW(),
                ?
            FROM {$tenantDbQuoted}.`tbl_print_template_fields` f
            LEFT JOIN {$tenantDbQuoted}.`tbl_temp_print_template_fields` t
                ON t.FieldID = f.ID AND t.RequestID = ?
            WHERE f.TemplateID = ?
            ORDER BY f.ID
        ", [$newTemplateId, $userId, $requestID, $sourceTemplateId]);

        // Insert columns for the new template by joining source columns with temp columns
        DB::connection('landlord')->statement("
            INSERT INTO {$tenantDbQuoted}.`tbl_print_template_columns`
            (`TemplateID`, `FieldKey`, `OriginalLabel`, `CustomLabel`, `OriginalIsOn`, `CustomIsOn`, `OriginalDisplayOrder`, `CustomDisplayOrder`, `OriginalWidthMin`, `CustomWidthMin`, `OriginalWidthMax`, `CustomWidthMax`, `OriginalAlignment`, `CustomAlignment`, `IsTotal`, `UpdatedOn`, `UpdatedBy`)
            SELECT
                ?,
                c.FieldKey,
                c.OriginalLabel,
                COALESCE(TRIM(t.CustomLabel), c.CustomLabel),
                c.OriginalIsOn,
                COALESCE(t.CustomIsOn, c.CustomIsOn),
                c.OriginalDisplayOrder,
                COALESCE(t.CustomDisplayOrder, c.CustomDisplayOrder),
                c.OriginalWidthMin,
                COALESCE(t.CustomWidthMin, c.CustomWidthMin),
                c.OriginalWidthMax,
                COALESCE(t.CustomWidthMax, c.CustomWidthMax),
                c.OriginalAlignment,
                COALESCE(t.CustomAlignment, c.CustomAlignment),
                COALESCE(t.IsTotal, c.IsTotal),
                NOW(),
                ?
            FROM {$tenantDbQuoted}.`tbl_print_template_columns` c
            LEFT JOIN {$tenantDbQuoted}.`tbl_temp_print_template_columns` t
                ON t.ColumnID = c.ID AND t.RequestID = ?
            WHERE c.TemplateID = ?
            ORDER BY c.ID
        ", [$newTemplateId, $userId, $requestID, $sourceTemplateId]);

        // Cleanup temp rows
        DB::connection('landlord')->statement("DELETE FROM {$tenantDbQuoted}.`tbl_temp_print_template_fields` WHERE RequestID = ?", [$requestID]);
        DB::connection('landlord')->statement("DELETE FROM {$tenantDbQuoted}.`tbl_temp_print_template_columns` WHERE RequestID = ?", [$requestID]);

        return [
            'status'      => true,
            'template_id' => $newTemplateId,
            'message'     => 'New template "' . $normalized['TemplateName'] . '" created successfully.',
        ];
    }

    /**
     * Reset a template back to the Master defaults in `core_digital_invoicing`.
     */
    public static function reset_transaction(int $templateId, int $userId = 1): array
    {
        try {
            $tenantDbQuoted = self::tenantDatabaseQuoted();

            $tpl = DB::connection('tenant')->table('tbl_print_templates')->where('ID', $templateId)->first();
            if (! $tpl) {
                return ['status' => false, 'message' => 'Print template not found.'];
            }

            $baseId = (int) ($tpl->BaseTemplateID ?: 1);

            // Ensure master template exists in core_digital_invoicing (fallback to ID 1 if needed)
            $masterExists = DB::connection('landlord')
                ->table('core_digital_invoicing.tbl_print_templates')
                ->where('ID', $baseId)
                ->exists();

            if (! $masterExists) {
                $baseId = 1;
            }

            DB::connection('landlord')->beginTransaction();

            // 1. Update header from core_digital_invoicing.tbl_print_templates
            DB::connection('landlord')->statement("
                UPDATE {$tenantDbQuoted}.`tbl_print_templates` t
                INNER JOIN `core_digital_invoicing`.`tbl_print_templates` m
                    ON m.ID = ?
                SET
                    t.TemplateName        = CASE WHEN t.IsSystemDefault = 1 THEN m.TemplateName ELSE t.TemplateName END,
                    t.OriginalTitle       = m.OriginalTitle,
                    t.CustomTitle         = m.OriginalTitle,
                    t.PrintOrientation    = m.PrintOrientation,
                    t.PaperSize           = m.PaperSize,
                    t.ThemeStyle          = m.ThemeStyle,
                    t.AccentColor         = m.AccentColor,
                    t.FontFamily          = m.FontFamily,
                    t.BaseFontSize        = m.BaseFontSize,
                    t.ShowLogo            = m.ShowLogo,
                    t.LogoMaxHeight       = m.LogoMaxHeight,
                    t.TopMarginPx         = m.TopMarginPx,
                    t.ShowSellerHeader    = m.ShowSellerHeader,
                    t.SellerHeading       = m.SellerHeading,
                    t.BuyerHeading        = m.BuyerHeading,
                    t.ShowDraftWatermark  = m.ShowDraftWatermark,
                    t.ShowAmountInWords   = m.ShowAmountInWords,
                    t.ShowBankDetails     = m.ShowBankDetails,
                    t.BankDetailsText     = m.BankDetailsText,
                    t.ShowSignatureBox    = m.ShowSignatureBox,
                    t.SignatureLabelLeft  = m.SignatureLabelLeft,
                    t.SignatureLabelRight = m.SignatureLabelRight,
                    t.UpdatedOn           = NOW(),
                    t.UpdatedBy           = ?
                WHERE t.ID = ?
            ", [$baseId, $userId, $templateId]);

            // 2. Fully restore Fields: DELETE existing then INSERT clean defaults from core_digital_invoicing
            DB::connection('landlord')->statement("
                DELETE FROM {$tenantDbQuoted}.`tbl_print_template_fields`
                WHERE `TemplateID` = ?
            ", [$templateId]);

            DB::connection('landlord')->statement("
                INSERT INTO {$tenantDbQuoted}.`tbl_print_template_fields`
                    (`TemplateID`, `SectionGroup`, `FieldKey`, `OriginalLabel`, `CustomLabel`, `OriginalIsOn`, `CustomIsOn`, `OriginalDisplayOrder`, `CustomDisplayOrder`, `OriginalFontSize`, `CustomFontSize`, `IsBold`, `UpdatedOn`, `UpdatedBy`)
                SELECT
                    ?, `SectionGroup`, `FieldKey`, `OriginalLabel`, `OriginalLabel`, `OriginalIsOn`, `OriginalIsOn`, `OriginalDisplayOrder`, `OriginalDisplayOrder`, `OriginalFontSize`, `OriginalFontSize`, `IsBold`, NOW(), ?
                FROM `core_digital_invoicing`.`tbl_print_template_fields`
                WHERE `TemplateID` = ?
                ORDER BY `OriginalDisplayOrder` ASC, `ID` ASC
            ", [$templateId, $userId, $baseId]);

            // 3. Fully restore Columns: DELETE existing then INSERT clean defaults from core_digital_invoicing
            DB::connection('landlord')->statement("
                DELETE FROM {$tenantDbQuoted}.`tbl_print_template_columns`
                WHERE `TemplateID` = ?
            ", [$templateId]);

            DB::connection('landlord')->statement("
                INSERT INTO {$tenantDbQuoted}.`tbl_print_template_columns`
                    (`TemplateID`, `FieldKey`, `OriginalLabel`, `CustomLabel`, `OriginalIsOn`, `CustomIsOn`, `OriginalDisplayOrder`, `CustomDisplayOrder`, `OriginalWidthMin`, `CustomWidthMin`, `OriginalWidthMax`, `CustomWidthMax`, `OriginalAlignment`, `CustomAlignment`, `IsTotal`, `UpdatedOn`, `UpdatedBy`)
                SELECT
                    ?, `FieldKey`, `OriginalLabel`, `OriginalLabel`, `OriginalIsOn`, `OriginalIsOn`, `OriginalDisplayOrder`, `OriginalDisplayOrder`, `OriginalWidthMin`, `OriginalWidthMin`, `OriginalWidthMax`, `OriginalWidthMax`, `OriginalAlignment`, `OriginalAlignment`, `IsTotal`, NOW(), ?
                FROM `core_digital_invoicing`.`tbl_print_template_columns`
                WHERE `TemplateID` = ?
                ORDER BY `OriginalDisplayOrder` ASC, `ID` ASC
            ", [$templateId, $userId, $baseId]);

            DB::connection('landlord')->commit();

            return [
                'status'      => true,
                'template_id' => $templateId,
                'message'     => 'Print template has been reset to original defaults.',
            ];
        } catch (\Throwable $e) {
            DB::connection('landlord')->rollBack();

            return [
                'status'  => false,
                'message' => 'Failed to reset print template: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Delete a user-created custom template (`IsSystemDefault = 0`).
     */
    public static function delete_template(int $templateId): array
    {
        $tpl = DB::connection('tenant')->table('tbl_print_templates')->where('ID', $templateId)->first();
        if (! $tpl) {
            return ['status' => false, 'message' => 'Template not found.'];
        }
        if ((int) ($tpl->IsSystemDefault ?? 0) === 1) {
            return ['status' => false, 'message' => 'The system default template cannot be deleted. Use "Reset to Default" instead.'];
        }

        DB::connection('tenant')->table('tbl_print_template_fields')->where('TemplateID', $templateId)->delete();
        DB::connection('tenant')->table('tbl_print_template_columns')->where('TemplateID', $templateId)->delete();
        DB::connection('tenant')->table('tbl_print_templates')->where('ID', $templateId)->delete();

        $defaultExists = DB::connection('tenant')->table('tbl_print_templates')
            ->where('ModuleID', $tpl->ModuleID)
            ->where('IsActive', 1)
            ->where('IsDefault', 1)
            ->exists();

        if (! $defaultExists) {
            $firstTpl = DB::connection('tenant')->table('tbl_print_templates')
                ->where('ModuleID', $tpl->ModuleID)
                ->where('IsActive', 1)
                ->orderByDesc('IsSystemDefault')
                ->orderBy('ID')
                ->first();
            if ($firstTpl) {
                DB::connection('tenant')->table('tbl_print_templates')
                    ->where('ID', $firstTpl->ID)
                    ->update(['IsDefault' => 1]);
            }
        }

        $fallback = self::getActiveTemplate((int) $tpl->ModuleID);

        return [
            'status'      => true,
            'template_id' => $fallback->ID ?? 1,
            'message'     => 'Custom template deleted successfully.',
        ];
    }

    /**
     * Convert a numeric PKR amount into English words (e.g. "PKR Twelve Thousand Five Hundred Only").
     */
    public static function amountInWordsPKR(float $amount): string
    {
        $amount = round(max(0, $amount), 2);
        $whole  = (int) floor($amount);
        $paisa  = (int) round(($amount - $whole) * 100);

        if ($whole === 0 && $paisa === 0) {
            return 'PKR Zero Only';
        }

        $ones = [
            0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four',
            5 => 'Five', 6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine',
            10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 14 => 'Fourteen',
            15 => 'Fifteen', 16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen', 19 => 'Nineteen',
        ];
        $tens = [
            0 => '', 2 => 'Twenty', 3 => 'Thirty', 4 => 'Forty', 5 => 'Fifty',
            6 => 'Sixty', 7 => 'Seventy', 8 => 'Eighty', 9 => 'Ninety',
        ];

        $convertBelowThousand = function (int $n) use ($ones, $tens): string {
            $parts = [];
            if ($n >= 100) {
                $parts[] = $ones[intdiv($n, 100)] . ' Hundred';
                $n %= 100;
            }
            if ($n >= 20) {
                $t = $tens[intdiv($n, 10)];
                $o = $ones[$n % 10];
                $parts[] = $o !== '' ? ($t . '-' . $o) : $t;
            } elseif ($n > 0) {
                $parts[] = $ones[$n];
            }
            return implode(' ', $parts);
        };

        $scales = [
            1000000000 => 'Billion',
            1000000    => 'Million',
            1000       => 'Thousand',
            1          => '',
        ];

        $words = [];
        $rem = $whole;
        foreach ($scales as $divisor => $scaleName) {
            if ($rem >= $divisor) {
                $chunk = intdiv($rem, $divisor);
                $rem %= $divisor;
                if ($chunk > 0) {
                    $chunkWords = $convertBelowThousand($chunk);
                    $words[] = $scaleName !== '' ? ($chunkWords . ' ' . $scaleName) : $chunkWords;
                }
            }
        }

        $result = 'PKR ' . implode(' ', $words);
        if ($paisa > 0) {
            $result .= ' and ' . $convertBelowThousand($paisa) . ' Paisa';
        }

        return $result . ' Only';
    }
}

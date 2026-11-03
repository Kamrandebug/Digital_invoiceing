/* ============================================================================
   DYNAMIC REPORTING ENGINE — DATABASE SCHEMA & SEED SCRIPT
   1. Master Database (`core_digital_invoicing`): Holds Original Report Metadata
   2. Tenant Database (`tenant_*`): Holds Active/Customized Report Metadata + Temp Table
   ============================================================================ */

-- ============================================================================
-- PART 1: MASTER DATABASE (`core_digital_invoicing`)
-- ============================================================================
USE `core_digital_invoicing`;

CREATE TABLE IF NOT EXISTS `tbl_reports` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `ReportID` int(11) NOT NULL,
  `ReportName` varchar(250) DEFAULT NULL,
  `CustomName` varchar(250) DEFAULT NULL,
  `ReportType` tinyint(1) DEFAULT 0,
  `RowGroupColumnName` varchar(250) DEFAULT NULL,
  `OrderColumnID` int(11) DEFAULT NULL,
  `PrintOrientation` enum('Portrait','Landscape') DEFAULT 'Portrait',
  `ReportFunction` varchar(250) DEFAULT NULL,
  `ReportModel` varchar(250) DEFAULT NULL,
  `LetterHeadPath` varchar(500) DEFAULT NULL,
  `LetterHeadBase64` longtext DEFAULT NULL,
  `EmailCC` varchar(250) DEFAULT NULL,
  `EmailTemplateID` int(11) DEFAULT NULL,
  `Status` int(1) DEFAULT 1,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `Idx_ReportID` (`ReportID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `tbl_reports_detail` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `ReportID` int(11) NOT NULL,
  `ColumName` varchar(100) NOT NULL,
  `OriginalDisplayOrder` int(11) DEFAULT NULL,
  `CustomDisplayOrder` int(11) DEFAULT NULL,
  `OrignalLabel` varchar(100) DEFAULT NULL,
  `OrignalIsOn` int(1) DEFAULT NULL,
  `CustomLabel` varchar(100) DEFAULT NULL,
  `CustomIsOn` int(1) DEFAULT NULL,
  `IsTotal` tinyint(1) DEFAULT 0,
  `IsRunningTotal` tinyint(1) DEFAULT 0,
  `IsVisible` tinyint(1) DEFAULT 1,
  `RowBalanceColumn` varchar(100) DEFAULT NULL,
  `OriginalWidthMin` int(3) DEFAULT NULL,
  `CustomWidthMin` int(3) DEFAULT NULL,
  `OriginalWidthMax` int(3) DEFAULT NULL,
  `CustomWidthMax` int(3) DEFAULT NULL,
  `OriginalAlignment` enum('left','right','center') DEFAULT 'left',
  `CustomAlignment` enum('left','right','center') DEFAULT NULL,
  `AvailableMaxWidth` int(3) DEFAULT NULL,
  `OriginalColumnFormat` varchar(20) DEFAULT NULL,
  `CustomColumnFormat` varchar(20) DEFAULT NULL,
  `WidthMin` int(3) DEFAULT NULL,
  `WidthMax` int(3) DEFAULT NULL,
  `Alignment` enum('left','right','center') DEFAULT 'left',
  `ColumnFormat` varchar(20) DEFAULT NULL,
  `CreatedOn` timestamp NOT NULL DEFAULT current_timestamp(),
  `CreatedBy` int(11) NOT NULL DEFAULT 1,
  `UpdatedOn` datetime DEFAULT NULL,
  `UpdatedBy` int(11) DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `unq_idx` (`ReportID`,`ColumName`),
  KEY `IndxReportID` (`ReportID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `tbl_reports_parameters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ReportID` int(11) DEFAULT NULL,
  `Parameter` varchar(100) DEFAULT NULL,
  `CustomLabel` varchar(100) DEFAULT NULL,
  `IsMultiSelection` tinyint(1) DEFAULT 0,
  `IsMandatory` tinyint(1) DEFAULT 0,
  `DefaultValue` varchar(50) DEFAULT NULL,
  `ParameterIsON` tinyint(1) DEFAULT NULL,
  `functionName` varchar(250) DEFAULT NULL,
  `DisplayOrder` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ReportIdParamIndx` (`ReportID`,`Parameter`),
  KEY `ReportIndx` (`ReportID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `tbl_reports_parameters_print` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `ReportID` int(11) DEFAULT NULL,
  `Parameter` varchar(100) DEFAULT NULL,
  `OriginalColumnID` tinyint(1) DEFAULT NULL,
  `CustomColumnID` tinyint(1) DEFAULT NULL,
  `OriginalDisplayOrder` int(11) DEFAULT NULL,
  `CustomDisplayOrder` int(11) DEFAULT NULL,
  `OriginalLabel` varchar(100) DEFAULT NULL,
  `CustomLabel` varchar(100) DEFAULT NULL,
  `OriginalIsOn` tinyint(1) DEFAULT 0,
  `CustomIsOn` tinyint(1) DEFAULT 0,
  `OriginalFontSize` varchar(4) DEFAULT '12px',
  `CustomFontSize` varchar(4) DEFAULT '12px',
  `FunctionName` varchar(250) DEFAULT NULL,
  `ControlName` varchar(250) DEFAULT NULL,
  `IsPrintDetails` tinyint(1) DEFAULT 0,
  `ColumnGroupID` int(5) DEFAULT 0,
  `UpdatedOn` datetime DEFAULT NULL,
  `UpdatedBy` int(11) DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `ReportIdParamIndx` (`ReportID`,`Parameter`),
  KEY `ReportIndx` (`ReportID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Seed Master Default for Sale Report (ReportID = 10)
INSERT INTO `core_digital_invoicing`.`tbl_reports`
(`ReportID`, `ReportName`, `CustomName`, `ReportType`, `RowGroupColumnName`, `OrderColumnID`, `PrintOrientation`, `ReportFunction`, `ReportModel`, `Status`)
VALUES
(10, 'Sale Report', 'Sale Report', 0, NULL, 1, 'Landscape', 'get_sale_report', 'ReportModel', 1)
ON DUPLICATE KEY UPDATE
  `ReportName` = VALUES(`ReportName`),
  `PrintOrientation` = VALUES(`PrintOrientation`),
  `ReportFunction` = VALUES(`ReportFunction`),
  `ReportModel` = VALUES(`ReportModel`),
  `Status` = VALUES(`Status`);

INSERT INTO `core_digital_invoicing`.`tbl_reports_detail`
(`ReportID`, `ColumName`, `OriginalDisplayOrder`, `CustomDisplayOrder`, `OrignalLabel`, `OrignalIsOn`, `CustomLabel`, `CustomIsOn`, `IsTotal`, `IsRunningTotal`, `IsVisible`, `OriginalWidthMin`, `CustomWidthMin`, `OriginalWidthMax`, `CustomWidthMax`, `OriginalAlignment`, `CustomAlignment`, `OriginalColumnFormat`, `CustomColumnFormat`, `WidthMin`, `WidthMax`, `Alignment`, `ColumnFormat`, `CreatedBy`)
VALUES
(10, 'DocNo',               1,  1,  'Doc No',            1, 'Doc No',            1, 0, 0, 1, 90,  90,  140, 140, 'left',  'left',  'text',    'text',    90,  140, 'left',  'text',    1),
(10, 'InvoiceDate',         2,  2,  'Invoice Date',      1, 'Invoice Date',      1, 0, 0, 1, 90,  90,  120, 120, 'left',  'left',  'date',    'date',    90,  120, 'left',  'date',    1),
(10, 'InvoiceStatus',       3,  3,  'Invoice Status',    1, 'Invoice Status',    1, 0, 0, 1, 110, 110, 160, 160, 'left',  'left',  'text',    'text',    110, 160, 'left',  'text',    1),
(10, 'CustomerDisplayName', 4,  4,  'Customer',          1, 'Customer',          1, 0, 0, 1, 140, 140, 220, 220, 'left',  'left',  'text',    'text',    140, 220, 'left',  'text',    1),
(10, 'product_name',        5,  5,  'Product',           1, 'Product',           1, 0, 0, 1, 130, 130, 200, 200, 'left',  'left',  'text',    'text',    130, 200, 'left',  'text',    1),
(10, 'line_description',    6,  6,  'Description',       1, 'Description',       1, 0, 0, 1, 140, 140, 240, 240, 'left',  'left',  'text',    'text',    140, 240, 'left',  'text',    1),
(10, 'HsCode',              7,  7,  'HS Code',           1, 'HS Code',           1, 0, 0, 1, 85,  85,  120, 120, 'left',  'left',  'text',    'text',    85,  120, 'left',  'text',    1),
(10, 'Uom_Desc',            8,  8,  'UOM',               1, 'UOM',               1, 0, 0, 1, 70,  70,  110, 110, 'left',  'left',  'text',    'text',    70,  110, 'left',  'text',    1),
(10, 'Quantity',            9,  9,  'Qty',               1, 'Qty',               1, 1, 0, 1, 70,  70,  100, 100, 'right', 'right', 'integer', 'integer', 70,  100, 'right', 'integer', 1),
(10, 'Rate',                10, 10, 'Rate',              1, 'Rate',              1, 0, 0, 1, 85,  85,  120, 120, 'right', 'right', 'decimal', 'decimal', 85,  120, 'right', 'decimal', 1),
(10, 'Amount',              11, 11, 'Amount',            1, 'Amount',            1, 1, 0, 1, 95,  95,  140, 140, 'right', 'right', 'decimal', 'decimal', 95,  140, 'right', 'decimal', 1),
(10, 'DiscountAmount',      12, 12, 'Discount',          1, 'Discount',          1, 1, 0, 1, 80,  80,  120, 120, 'right', 'right', 'decimal', 'decimal', 80,  120, 'right', 'decimal', 1),
(10, 'GSTAmount',           13, 13, 'GST',               1, 'GST',               1, 1, 0, 1, 85,  85,  120, 120, 'right', 'right', 'decimal', 'decimal', 85,  120, 'right', 'decimal', 1),
(10, 'ExtraTax',            14, 14, 'Extra Tax',         1, 'Extra Tax',         1, 1, 0, 1, 80,  80,  120, 120, 'right', 'right', 'decimal', 'decimal', 80,  120, 'right', 'decimal', 1),
(10, 'FurtherTax',          15, 15, 'Further Tax',       1, 'Further Tax',       1, 1, 0, 1, 85,  85,  120, 120, 'right', 'right', 'decimal', 'decimal', 85,  120, 'right', 'decimal', 1),
(10, 'FedPayable',          16, 16, 'FED Payable',       1, 'FED Payable',       1, 1, 0, 1, 85,  85,  120, 120, 'right', 'right', 'decimal', 'decimal', 85,  120, 'right', 'decimal', 1),
(10, 'TotalValues',         17, 17, 'Total (Incl. Tax)', 1, 'Total (Incl. Tax)', 1, 1, 0, 1, 100, 100, 150, 150, 'right', 'right', 'decimal', 'decimal', 100, 150, 'right', 'decimal', 1),
(10, 'FbrTransactionID',    18, 18, 'FBR Txn ID',        1, 'FBR Txn ID',        1, 0, 0, 1, 120, 120, 180, 180, 'left',  'left',  'text',    'text',    120, 180, 'left',  'text',    1)
ON DUPLICATE KEY UPDATE
  `OriginalDisplayOrder` = VALUES(`OriginalDisplayOrder`),
  `OrignalLabel`         = VALUES(`OrignalLabel`),
  `OrignalIsOn`          = VALUES(`OrignalIsOn`),
  `IsTotal`              = VALUES(`IsTotal`),
  `OriginalAlignment`    = VALUES(`OriginalAlignment`),
  `OriginalColumnFormat` = VALUES(`OriginalColumnFormat`);

INSERT INTO `core_digital_invoicing`.`tbl_reports_parameters`
(`ReportID`, `Parameter`, `CustomLabel`, `IsMultiSelection`, `IsMandatory`, `DefaultValue`, `ParameterIsON`, `functionName`, `DisplayOrder`)
VALUES
(10, 'customer_id',    'Customer',       0, 0, NULL, 1, 'load_customer',       1),
(10, 'product_id',     'Product',        0, 0, NULL, 1, 'load_product',        2),
(10, 'invoice_status', 'Invoice Status', 0, 0, NULL, 1, 'load_invoice_status', 3),
(10, 'start_date',     'Start Date',     0, 0, NULL, 1, 'daterange',           4),
(10, 'end_date',       'End Date',       0, 0, NULL, 1, 'daterange',           5)
ON DUPLICATE KEY UPDATE
  `CustomLabel`   = VALUES(`CustomLabel`),
  `ParameterIsON` = VALUES(`ParameterIsON`),
  `functionName`  = VALUES(`functionName`),
  `DisplayOrder`  = VALUES(`DisplayOrder`);


-- ============================================================================
-- PART 2: TENANT DATABASE (`tenant_*`)
-- ============================================================================
-- Replace `tenant_7af65445_2e20_42fb_868f_813b981a578a` with target tenant DB name

CREATE TABLE IF NOT EXISTS `tbl_reports` LIKE `core_digital_invoicing`.`tbl_reports`;
CREATE TABLE IF NOT EXISTS `tbl_reports_detail` LIKE `core_digital_invoicing`.`tbl_reports_detail`;
CREATE TABLE IF NOT EXISTS `tbl_reports_parameters` LIKE `core_digital_invoicing`.`tbl_reports_parameters`;
CREATE TABLE IF NOT EXISTS `tbl_reports_parameters_print` LIKE `core_digital_invoicing`.`tbl_reports_parameters_print`;

-- Temp Table for RequestID + Set-Based SQL Validation & Update (`edit_transaction`)
CREATE TABLE IF NOT EXISTS `tbl_temp_reports_detail` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `RequestID` varchar(100) NOT NULL,
  `ReportID` int(11) NOT NULL,
  `CustomReportName` varchar(250) DEFAULT NULL,
  `PrintOrientation` enum('Portrait','Landscape') DEFAULT 'Portrait',
  `RowGroupColumnName` varchar(250) DEFAULT NULL,
  `ColumName` varchar(100) NOT NULL,
  `CustomDisplayOrder` int(11) DEFAULT NULL,
  `CustomLabel` varchar(100) DEFAULT NULL,
  `CustomIsOn` int(1) DEFAULT 1,
  `IsTotal` tinyint(1) DEFAULT 0,
  `CustomWidthMin` int(3) DEFAULT NULL,
  `CustomWidthMax` int(3) DEFAULT NULL,
  `CustomAlignment` enum('left','right','center') DEFAULT 'left',
  `CustomColumnFormat` varchar(20) DEFAULT NULL,
  `ErrMsg` varchar(500) DEFAULT NULL,
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedOn` datetime DEFAULT current_timestamp(),
  `UpdatedBy` int(11) DEFAULT NULL,
  `UpdatedOn` datetime DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `Idx_RequestID` (`RequestID`),
  KEY `Idx_ReportID_Col` (`ReportID`, `ColumName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add `IsReport = 1` entry in `tbl_module` for Sale Report (ID = 10)
INSERT INTO `tbl_module`
(`ID`, `Name`, `Description`, `CreatedOn`, `CreatedBy`, `Status`, `Level`, `CurrentPage`, `MainModuleID`, `ModuleURL`, `MainModuleIcon`, `IsReport`, `IsGlobalSetting`, `DisplayOrder`)
VALUES
(10, 'Sale Report', 'Detailed line-level sales and FBR tax report', NOW(), 1, 1, 1, 'sale-report', 4, '/reports/10', 'ri-file-chart-line', 1, 0, 1)
ON DUPLICATE KEY UPDATE
  `Name`           = VALUES(`Name`),
  `Description`    = VALUES(`Description`),
  `CurrentPage`    = VALUES(`CurrentPage`),
  `MainModuleID`   = VALUES(`MainModuleID`),
  `ModuleURL`      = VALUES(`ModuleURL`),
  `MainModuleIcon` = VALUES(`MainModuleIcon`),
  `IsReport`       = 1,
  `Status`         = 1;

-- Copy Default Report Rows from `core_digital_invoicing` into Tenant DB
INSERT IGNORE INTO `tbl_reports`
SELECT * FROM `core_digital_invoicing`.`tbl_reports` WHERE `ReportID` = 10;

INSERT IGNORE INTO `tbl_reports_detail`
SELECT * FROM `core_digital_invoicing`.`tbl_reports_detail` WHERE `ReportID` = 10;

INSERT IGNORE INTO `tbl_reports_parameters`
SELECT * FROM `core_digital_invoicing`.`tbl_reports_parameters` WHERE `ReportID` = 10;


-- ============================================================================
-- PART 3: REFERENCE TRANSACTION QUERIES (Used in ReportModel.php)
-- ============================================================================
/*
-- 3A. Set-Based Validation Query on `tbl_temp_reports_detail` (by RequestID):
UPDATE `tbl_temp_reports_detail` uu
JOIN (
    SELECT t.ID,
        CASE
            WHEN COALESCE(TRIM(t.CustomReportName), '') = '' THEN 'Report Name cannot be empty'
            WHEN COALESCE(TRIM(t.CustomLabel), '') = '' THEN CONCAT('Display Name is required for column ', t.ColumName)
            WHEN t.CustomWidthMin IS NOT NULL AND t.CustomWidthMin < 30 THEN CONCAT('Min Width must be at least 30px for column ', t.ColumName)
            WHEN t.CustomWidthMax IS NOT NULL AND t.CustomWidthMin IS NOT NULL AND t.CustomWidthMax < t.CustomWidthMin THEN CONCAT('Max Width cannot be less than Min Width for column ', t.ColumName)
            WHEN NOT EXISTS (
                SELECT 1 FROM `tbl_reports_detail` d
                WHERE d.ReportID = t.ReportID AND d.ColumName = t.ColumName
            ) THEN CONCAT('Invalid report column: ', t.ColumName)
            WHEN NOT EXISTS (
                SELECT 1 FROM `tbl_temp_reports_detail` t2
                WHERE t2.RequestID = t.RequestID AND t2.CustomIsOn = 1
            ) THEN 'At least one column must be turned ON'
            ELSE 'Pass'
        END AS ErrMsg
    FROM `tbl_temp_reports_detail` t
    WHERE t.RequestID = :requestID
) u ON u.ID = uu.ID
SET uu.ErrMsg = u.ErrMsg;

-- 3B. Set-Based Update (`edit_transaction`) from `tbl_temp_reports_detail` to Tenant Tables:
UPDATE `tbl_reports` r
INNER JOIN (
    SELECT
        ReportID,
        MAX(TRIM(CustomReportName)) AS CustomReportName,
        MAX(PrintOrientation) AS PrintOrientation,
        MAX(RowGroupColumnName) AS RowGroupColumnName
    FROM `tbl_temp_reports_detail`
    WHERE RequestID = :requestID
    GROUP BY ReportID
) t ON r.ReportID = t.ReportID
SET
    r.CustomName         = t.CustomReportName,
    r.PrintOrientation   = COALESCE(t.PrintOrientation, r.PrintOrientation),
    r.RowGroupColumnName = t.RowGroupColumnName;

UPDATE `tbl_reports_detail` d
INNER JOIN `tbl_temp_reports_detail` t
    ON d.ReportID = t.ReportID AND d.ColumName = t.ColumName
SET
    d.CustomLabel        = TRIM(t.CustomLabel),
    d.CustomDisplayOrder = t.CustomDisplayOrder,
    d.CustomIsOn         = t.CustomIsOn,
    d.IsTotal            = COALESCE(t.IsTotal, d.IsTotal, 0),
    d.CustomWidthMin     = COALESCE(t.CustomWidthMin, d.CustomWidthMin, d.OriginalWidthMin),
    d.WidthMin           = COALESCE(t.CustomWidthMin, d.WidthMin, d.OriginalWidthMin),
    d.CustomWidthMax     = COALESCE(t.CustomWidthMax, d.CustomWidthMax, d.OriginalWidthMax),
    d.WidthMax           = COALESCE(t.CustomWidthMax, d.WidthMax, d.OriginalWidthMax),
    d.CustomAlignment    = COALESCE(t.CustomAlignment, d.CustomAlignment, d.OriginalAlignment),
    d.Alignment          = COALESCE(t.CustomAlignment, d.Alignment, d.OriginalAlignment),
    d.UpdatedBy          = t.UpdatedBy,
    d.UpdatedOn          = t.UpdatedOn
WHERE t.RequestID = :requestID;

-- 3C. Reset to Default (`reset_transaction`) from Master `core_digital_invoicing` to Tenant DB:
UPDATE `tbl_reports` t
INNER JOIN `core_digital_invoicing`.`tbl_reports` m
    ON t.ReportID = m.ReportID
SET
    t.ReportName         = m.ReportName,
    t.CustomName         = m.ReportName,
    t.ReportType         = m.ReportType,
    t.RowGroupColumnName = m.RowGroupColumnName,
    t.OrderColumnID      = m.OrderColumnID,
    t.PrintOrientation   = m.PrintOrientation,
    t.ReportFunction     = m.ReportFunction,
    t.ReportModel        = m.ReportModel
WHERE t.ReportID = :reportId;

UPDATE `tbl_reports_detail` t
INNER JOIN `core_digital_invoicing`.`tbl_reports_detail` m
    ON t.ReportID = m.ReportID AND t.ColumName = m.ColumName
SET
    t.OriginalDisplayOrder = m.OriginalDisplayOrder,
    t.CustomDisplayOrder   = m.OriginalDisplayOrder,
    t.OrignalLabel         = m.OrignalLabel,
    t.CustomLabel          = m.OrignalLabel,
    t.OrignalIsOn          = m.OrignalIsOn,
    t.CustomIsOn           = m.OrignalIsOn,
    t.IsTotal              = m.IsTotal,
    t.IsRunningTotal       = m.IsRunningTotal,
    t.IsVisible            = m.IsVisible,
    t.OriginalWidthMin     = m.OriginalWidthMin,
    t.CustomWidthMin       = m.OriginalWidthMin,
    t.OriginalWidthMax     = m.OriginalWidthMax,
    t.CustomWidthMax       = m.OriginalWidthMax,
    t.OriginalAlignment    = m.OriginalAlignment,
    t.CustomAlignment      = m.OriginalAlignment,
    t.OriginalColumnFormat = m.OriginalColumnFormat,
    t.CustomColumnFormat   = m.OriginalColumnFormat,
    t.WidthMin             = m.WidthMin,
    t.WidthMax             = m.WidthMax,
    t.Alignment            = m.OriginalAlignment,
    t.ColumnFormat         = m.OriginalColumnFormat,
    t.UpdatedOn            = NOW(),
    t.UpdatedBy            = :userId
WHERE t.ReportID = :reportId;
*/

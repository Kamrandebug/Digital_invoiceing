-- =================================================================================
-- DYNAMIC PRINT TEMPLATES ENGINE - DATABASE SCHEMA & SEED SCRIPT
-- Supports Multi-Template Document Printing (Sales Invoice ModuleID = 4)
--
-- PART 1: Run on Master Database (`core_digital_invoicing`)
-- PART 2: Run on Tenant Database (e.g. `tenant_7af65445_2e20_42fb_868f_813b981a578a`)
-- =================================================================================


-- =================================================================================
-- PART 1: MASTER DATABASE (`core_digital_invoicing`)
-- =================================================================================
-- USE `core_digital_invoicing`;

CREATE TABLE IF NOT EXISTS `tbl_print_templates` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ModuleID` int NOT NULL DEFAULT '4',
  `BaseTemplateID` int NOT NULL DEFAULT '1',
  `TemplateName` varchar(150) NOT NULL DEFAULT 'Standard Tax Invoice',
  `OriginalTitle` varchar(150) NOT NULL DEFAULT 'Sales Tax Invoice',
  `CustomTitle` varchar(150) NOT NULL DEFAULT 'Sales Tax Invoice',
  `IsDefault` tinyint(1) NOT NULL DEFAULT '1',
  `IsSystemDefault` tinyint(1) NOT NULL DEFAULT '1',
  `PrintOrientation` varchar(20) NOT NULL DEFAULT 'portrait',
  `PaperSize` varchar(20) NOT NULL DEFAULT 'A4',
  `ThemeStyle` varchar(30) NOT NULL DEFAULT 'classic',
  `AccentColor` varchar(20) NOT NULL DEFAULT '#1e3a5f',
  `FontFamily` varchar(100) NOT NULL DEFAULT 'Arial, sans-serif',
  `BaseFontSize` int NOT NULL DEFAULT '12',
  `ShowLogo` tinyint(1) NOT NULL DEFAULT '1',
  `LogoMaxHeight` int NOT NULL DEFAULT '105',
  `TopMarginPx` int NOT NULL DEFAULT '35',
  `ShowSellerHeader` tinyint(1) NOT NULL DEFAULT '1',
  `SellerHeading` varchar(100) NOT NULL DEFAULT 'Seller',
  `BuyerHeading` varchar(100) NOT NULL DEFAULT 'Buyer',
  `ShowDraftWatermark` tinyint(1) NOT NULL DEFAULT '0',
  `ShowAmountInWords` tinyint(1) NOT NULL DEFAULT '0',
  `ShowBankDetails` tinyint(1) NOT NULL DEFAULT '0',
  `BankDetailsText` text,
  `ShowSignatureBox` tinyint(1) NOT NULL DEFAULT '0',
  `SignatureLabelLeft` varchar(100) DEFAULT 'Prepared / Checked By',
  `SignatureLabelRight` varchar(100) DEFAULT 'Authorized Signature & Stamp',
  `IsActive` tinyint(1) NOT NULL DEFAULT '1',
  `CreatedOn` datetime DEFAULT CURRENT_TIMESTAMP,
  `CreatedBy` int DEFAULT NULL,
  `UpdatedOn` datetime DEFAULT NULL,
  `UpdatedBy` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `idx_print_templates_module` (`ModuleID`, `IsActive`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `tbl_print_template_fields` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `TemplateID` int NOT NULL DEFAULT '1',
  `SectionGroup` varchar(30) NOT NULL,
  `FieldKey` varchar(60) NOT NULL,
  `OriginalLabel` varchar(150) NOT NULL,
  `CustomLabel` varchar(150) NOT NULL,
  `OriginalIsOn` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1=Show, 2=Auto-Hide if Empty/Zero, 0=Hide',
  `CustomIsOn` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1=Show, 2=Auto-Hide if Empty/Zero, 0=Hide',
  `OriginalDisplayOrder` int NOT NULL DEFAULT '1',
  `CustomDisplayOrder` int NOT NULL DEFAULT '1',
  `OriginalFontSize` int NOT NULL DEFAULT '12',
  `CustomFontSize` int NOT NULL DEFAULT '12',
  `IsBold` tinyint(1) NOT NULL DEFAULT '0',
  `UpdatedOn` datetime DEFAULT NULL,
  `UpdatedBy` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `idx_print_fields_template` (`TemplateID`, `SectionGroup`, `CustomDisplayOrder`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `tbl_print_template_columns` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `TemplateID` int NOT NULL DEFAULT '1',
  `FieldKey` varchar(60) NOT NULL,
  `OriginalLabel` varchar(150) NOT NULL,
  `CustomLabel` varchar(150) NOT NULL,
  `OriginalIsOn` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1=Show, 2=Auto-Hide if Zero, 0=Hide',
  `CustomIsOn` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1=Show, 2=Auto-Hide if Zero, 0=Hide',
  `OriginalDisplayOrder` int NOT NULL DEFAULT '1',
  `CustomDisplayOrder` int NOT NULL DEFAULT '1',
  `OriginalWidthMin` varchar(20) NOT NULL DEFAULT '60px',
  `CustomWidthMin` varchar(20) NOT NULL DEFAULT '60px',
  `OriginalWidthMax` varchar(20) NOT NULL DEFAULT '150px',
  `CustomWidthMax` varchar(20) NOT NULL DEFAULT '150px',
  `OriginalAlignment` varchar(15) NOT NULL DEFAULT 'left',
  `CustomAlignment` varchar(15) NOT NULL DEFAULT 'left',
  `IsTotal` tinyint(1) NOT NULL DEFAULT '0',
  `UpdatedOn` datetime DEFAULT NULL,
  `UpdatedBy` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `idx_print_cols_template` (`TemplateID`, `CustomDisplayOrder`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ---------------------------------------------------------------------------------
-- Seed Default Sales Invoice Print Template (`ID = 1`, `ModuleID = 4`)
-- ---------------------------------------------------------------------------------
DELETE FROM `tbl_print_templates` WHERE `ID` = 1;
INSERT INTO `tbl_print_templates`
(`ID`, `ModuleID`, `BaseTemplateID`, `TemplateName`, `OriginalTitle`, `CustomTitle`, `IsDefault`, `IsSystemDefault`, `PrintOrientation`, `PaperSize`, `ThemeStyle`, `AccentColor`, `FontFamily`, `BaseFontSize`, `ShowLogo`, `LogoMaxHeight`, `TopMarginPx`, `ShowSellerHeader`, `SellerHeading`, `BuyerHeading`, `ShowDraftWatermark`, `ShowAmountInWords`, `ShowBankDetails`, `BankDetailsText`, `ShowSignatureBox`, `SignatureLabelLeft`, `SignatureLabelRight`, `IsActive`, `CreatedOn`, `CreatedBy`)
VALUES
(1, 4, 1, 'Standard Tax Invoice', 'Sales Tax Invoice', 'Sales Tax Invoice', 1, 1, 'portrait', 'A4', 'classic', '#1e3a5f', 'Arial, sans-serif', 12, 1, 105, 35, 1, 'Seller', 'Buyer', 0, 0, 0, NULL, 0, 'Prepared / Checked By', 'Authorized Signature & Stamp', 1, NOW(), 1);

DELETE FROM `tbl_print_template_fields` WHERE `TemplateID` = 1;
INSERT INTO `tbl_print_template_fields`
(`TemplateID`, `SectionGroup`, `FieldKey`, `OriginalLabel`, `CustomLabel`, `OriginalIsOn`, `CustomIsOn`, `OriginalDisplayOrder`, `CustomDisplayOrder`, `OriginalFontSize`, `CustomFontSize`, `IsBold`, `UpdatedOn`, `UpdatedBy`)
VALUES
-- Section: Document Meta (Top Right)
(1, 'meta', 'DocNo',            'Invoice / Doc No:',  'Invoice / Doc No:',  1, 1, 1, 1, 12, 12, 0, NOW(), 1),
(1, 'meta', 'DocNo2',           'Doc2 No:',           'Doc2 No:',           2, 2, 2, 2, 12, 12, 0, NOW(), 1),
(1, 'meta', 'DocNo3',           'Doc3 No:',           'Doc3 No:',           2, 2, 3, 3, 12, 12, 0, NOW(), 1),
(1, 'meta', 'InvoiceDate',      'Invoice Date:',      'Invoice Date:',      1, 1, 4, 4, 12, 12, 0, NOW(), 1),
(1, 'meta', 'SaleOfTerm',       'Sale of Term:',      'Sale of Term:',      2, 2, 5, 5, 12, 12, 0, NOW(), 1),
(1, 'meta', 'FbrTransactionID', 'FBR Invoice:',       'FBR Invoice:',       1, 1, 6, 6, 12, 12, 1, NOW(), 1),

-- Section: Seller Info (Left Column)
(1, 'seller', 'SellerBusinessName', 'Business Name',      'Business Name',      1, 1, 1, 1, 12, 12, 1, NOW(), 1),
(1, 'seller', 'SellerAddress',      'Address:',           'Address:',           2, 2, 2, 2, 10, 10, 0, NOW(), 1),
(1, 'seller', 'SellerProvince',     'Province:',          'Province:',          2, 2, 3, 3, 10, 10, 0, NOW(), 1),
(1, 'seller', 'SellerPhone',        'Phone:',             'Phone:',             2, 2, 4, 4, 10, 10, 0, NOW(), 1),
(1, 'seller', 'SellerNTN',          'Registration / NTN:','Registration / NTN:',2, 2, 5, 5, 10, 10, 0, NOW(), 1),
(1, 'seller', 'SellerSTN',          'STN:',               'STN:',               2, 2, 6, 6, 10, 10, 0, NOW(), 1),
(1, 'seller', 'SellerEmail',        'Email:',             'Email:',             2, 2, 7, 7, 10, 10, 0, NOW(), 1),

-- Section: Buyer Info (Right Column)
(1, 'buyer', 'BuyerBusinessName', 'Business Name',      'Business Name',      1, 1, 1, 1, 12, 12, 1, NOW(), 1),
(1, 'buyer', 'BuyerAddress',      'Address:',           'Address:',           2, 2, 2, 2, 10, 10, 0, NOW(), 1),
(1, 'buyer', 'BuyerProvince',     'Province:',          'Province:',          2, 2, 3, 3, 10, 10, 0, NOW(), 1),
(1, 'buyer', 'BuyerPhone',        'Phone:',             'Phone:',             2, 2, 4, 4, 10, 10, 0, NOW(), 1),
(1, 'buyer', 'BuyerNTN',          'Registration / NTN:','Registration / NTN:',2, 2, 5, 5, 10, 10, 0, NOW(), 1),
(1, 'buyer', 'BuyerSTN',          'STN:',               'STN:',               2, 2, 6, 6, 10, 10, 0, NOW(), 1),
(1, 'buyer', 'BuyerEmail',        'Email:',             'Email:',             2, 2, 7, 7, 10, 10, 0, NOW(), 1),

-- Section: Summary Totals (Bottom Right)
(1, 'summary', 'TotalExclTax',    'Total Amount Excluding ST:', 'Total Amount Excluding ST:', 1, 1, 1, 1, 12, 12, 0, NOW(), 1),
(1, 'summary', 'Discount',        'Discount:',                  'Discount:',                  2, 2, 2, 2, 12, 12, 0, NOW(), 1),
(1, 'summary', 'TotalSalesTax',   'Total Sales Tax:',           'Total Sales Tax:',           1, 1, 3, 3, 12, 12, 0, NOW(), 1),
(1, 'summary', 'ExtraTax',        'Extra Tax:',                 'Extra Tax:',                 2, 2, 4, 4, 12, 12, 0, NOW(), 1),
(1, 'summary', 'FurtherTax',      'Further Tax:',               'Further Tax:',               2, 2, 5, 5, 12, 12, 0, NOW(), 1),
(1, 'summary', 'FedPayable',      'FED Payable:',               'FED Payable:',               2, 2, 6, 6, 12, 12, 0, NOW(), 1),
(1, 'summary', 'TotalInclTax',    'Total Inc. All Taxes:',      'Total Inc. All Taxes:',      1, 1, 7, 7, 13, 13, 1, NOW(), 1),
(1, 'summary', 'CustomerWht236g', 'Customer WHT 236G:',         'Customer WHT 236G:',         2, 2, 8, 8, 12, 12, 0, NOW(), 1),
(1, 'summary', 'GrandTotal',      'Grand Total:',               'Grand Total:',               2, 2, 9, 9, 13, 13, 1, NOW(), 1),

-- Section: Footer Notes
(1, 'notes', 'SellerNotes',  'Notes:',         'Notes:',         2, 2, 1, 1, 10, 10, 0, NOW(), 1),
(1, 'notes', 'InvoiceNotes', 'Invoice Notes:', 'Invoice Notes:', 2, 2, 2, 2, 12, 12, 0, NOW(), 1);

DELETE FROM `tbl_print_template_columns` WHERE `TemplateID` = 1;
INSERT INTO `tbl_print_template_columns`
(`TemplateID`, `FieldKey`, `OriginalLabel`, `CustomLabel`, `OriginalIsOn`, `CustomIsOn`, `OriginalDisplayOrder`, `CustomDisplayOrder`, `OriginalWidthMin`, `CustomWidthMin`, `OriginalWidthMax`, `CustomWidthMax`, `OriginalAlignment`, `CustomAlignment`, `IsTotal`, `UpdatedOn`, `UpdatedBy`)
VALUES
(1, 'SrNo',                '#',                        '#',                        1, 1,  1,  1, '30px',  '30px',  '45px',  '45px',  'center', 'center', 0, NOW(), 1),
-- Description Options (Combined vs. Atomic)
(1, 'CombinedDescription', 'Description of Goods',     'Description of Goods',     1, 1,  2,  2, '140px', '140px', '260px', '260px', 'left',   'left',   0, NOW(), 1),
(1, 'ProductName',         'Product Name',             'Product Name',             0, 0,  3,  3, '100px', '100px', '180px', '180px', 'left',   'left',   0, NOW(), 1),
(1, 'HsCode',              'HS Code',                  'HS Code',                  0, 0,  4,  4, '70px',  '70px',  '100px', '100px', 'center', 'center', 0, NOW(), 1),
(1, 'ProductDescription',  'Goods of Sale / Details',  'Goods of Sale / Details',  0, 0,  5,  5, '120px', '120px', '220px', '220px', 'left',   'left',   0, NOW(), 1),

-- Quantities & Units Options (Combined Qty+Unit vs. Atomic)
(1, 'FbrQtyWithUnit',      'HS Qty & Unit',            'Qty of HS Code',           2, 2,  6,  6, '65px',  '65px',  '95px',  '95px',  'right',  'right',  1, NOW(), 1),
(1, 'FbrQty',              'Qty of HS Code (Numeric)', 'Qty of HS Code',           0, 0,  7,  7, '55px',  '55px',  '80px',  '80px',  'right',  'right',  1, NOW(), 1),
(1, 'HsUnit',              'HS Unit',                  'HS Unit',                  0, 0,  8,  8, '45px',  '45px',  '75px',  '75px',  'center', 'center', 0, NOW(), 1),
(1, 'QtyWithUnit',         'Qty & Unit',               'Qty & Unit',               1, 1,  9,  9, '65px',  '65px',  '95px',  '95px',  'right',  'right',  1, NOW(), 1),
(1, 'Quantity',            'Qty (Numeric)',            'Qty',                      0, 0, 10, 10, '45px',  '45px',  '75px',  '75px',  'right',  'right',  1, NOW(), 1),
(1, 'Unit',                'Unit',                     'Unit',                     0, 0, 11, 11, '45px',  '45px',  '75px',  '75px',  'center', 'center', 0, NOW(), 1),
(1, 'CustomUnit',          'Custom Unit',              'Custom Unit',              0, 0, 12, 12, '45px',  '45px',  '75px',  '75px',  'center', 'center', 0, NOW(), 1),

-- Pricing & Taxes
(1, 'Rate',                'Unit Rate',                'Unit Rate',                1, 1, 13, 13, '65px',  '65px',  '100px', '100px', 'right',  'right',  0, NOW(), 1),
(1, 'Discount',            'Discount',                 'Discount',                 0, 0, 14, 14, '60px',  '60px',  '90px',  '90px',  'right',  'right',  1, NOW(), 1),
(1, 'ValueExclTax',        'Value Excel ST',           'Value Excel ST',           1, 1, 15, 15, '75px',  '75px',  '115px', '115px', 'right',  'right',  1, NOW(), 1),
(1, 'FixedNotifiedValue',  'Fixed Notified Value',     'Fixed Notified Value',     2, 2, 16, 16, '65px',  '65px',  '100px', '100px', 'right',  'right',  1, NOW(), 1),
(1, 'TaxRate',             'Sales Tax Rate',           'Sales Tax Rate',           1, 1, 17, 17, '55px',  '55px',  '80px',  '80px',  'center', 'center', 0, NOW(), 1),
(1, 'SalesTaxAmount',      'Sales Tax Amount',         'Sales Tax Amount',         1, 1, 18, 18, '75px',  '75px',  '110px', '110px', 'right',  'right',  1, NOW(), 1),
(1, 'ExtraTax',            'Extra Tax',                'Extra Tax',                0, 0, 19, 19, '60px',  '60px',  '90px',  '90px',  'right',  'right',  1, NOW(), 1),
(1, 'FurtherTax',          'Further Tax',              'Further Tax',              0, 0, 20, 20, '60px',  '60px',  '90px',  '90px',  'right',  'right',  1, NOW(), 1),
(1, 'FedPayable',          'FED Payable',              'FED Payable',              0, 0, 21, 21, '60px',  '60px',  '90px',  '90px',  'right',  'right',  1, NOW(), 1),
(1, 'TotalInclTax',        'Value Inclusive Sales Tax','Value Inclusive Sales Tax',1, 1, 22, 22, '80px',  '80px',  '120px', '120px', 'right',  'right',  1, NOW(), 1);


-- =================================================================================
-- PART 2: TENANT DATABASE ( includes `tbl_temp_print_*` for RequestID validation )
-- =================================================================================
-- USE `tenant_7af65445_2e20_42fb_868f_813b981a578a`;

CREATE TABLE IF NOT EXISTS `tbl_temp_print_template_fields` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `RequestID` varchar(64) NOT NULL,
  `FieldID` int NOT NULL,
  `TemplateID` int NOT NULL,
  `CustomLabel` varchar(150) DEFAULT NULL,
  `CustomIsOn` tinyint(1) NOT NULL DEFAULT '1',
  `CustomDisplayOrder` int NOT NULL DEFAULT '1',
  `CustomFontSize` int NOT NULL DEFAULT '12',
  `IsBold` tinyint(1) NOT NULL DEFAULT '0',
  `ErrorMessage` text,
  `CreatedOn` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`),
  KEY `idx_temp_print_fields_req` (`RequestID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `tbl_temp_print_template_columns` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `RequestID` varchar(64) NOT NULL,
  `ColumnID` int NOT NULL,
  `TemplateID` int NOT NULL,
  `CustomLabel` varchar(150) DEFAULT NULL,
  `CustomIsOn` tinyint(1) NOT NULL DEFAULT '1',
  `CustomDisplayOrder` int NOT NULL DEFAULT '1',
  `CustomWidthMin` varchar(20) DEFAULT '60px',
  `CustomWidthMax` varchar(20) DEFAULT '150px',
  `CustomAlignment` varchar(15) DEFAULT 'left',
  `IsTotal` tinyint(1) NOT NULL DEFAULT '0',
  `ErrorMessage` text,
  `CreatedOn` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`),
  KEY `idx_temp_print_cols_req` (`RequestID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

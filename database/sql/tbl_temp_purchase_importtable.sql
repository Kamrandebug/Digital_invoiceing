-- Purchase Ledger import staging table (tenant database)
-- Run this script on your tenant DB, e.g.:
--   USE your_tenant_database_name;
--   SOURCE tbl_temp_purchase_importtable.sql;

CREATE TABLE IF NOT EXISTS `tbl_temp_purchase_importtable` (
    `ID` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `InvoiceNo` VARCHAR(100) NULL,
    `InvoiceType` VARCHAR(100) NULL,
    `InvoiceDate` VARCHAR(50) NULL,
    `BuyerRegistrationNo` VARCHAR(50) NULL,
    `BuyerName` VARCHAR(255) NULL,
    `TaxpayerType` VARCHAR(50) NULL,
    `SellerRegistrationNo` VARCHAR(50) NULL,
    `SellerName` VARCHAR(255) NULL,
    `SaleType` VARCHAR(100) NULL,
    `SaleOriginationProvince` VARCHAR(100) NULL,
    `Quantity` DECIMAL(18, 4) NOT NULL DEFAULT 0.0000,
    `ProductDescription` VARCHAR(500) NULL,
    `HsCode` VARCHAR(50) NULL,
    `Rate` VARCHAR(50) NULL,
    `Uom` VARCHAR(50) NULL,
    `ValueSalesExcludingST` DECIMAL(18, 2) NOT NULL DEFAULT 0.00,
    `SalesTaxFedInStMode` DECIMAL(18, 2) NOT NULL DEFAULT 0.00,
    `FixedNotifiedValueOrRetailPrice` DECIMAL(18, 2) NOT NULL DEFAULT 0.00,
    `RowNo` INT UNSIGNED NULL,
    `RequestID` CHAR(36) NULL,
    `ErrMsg` VARCHAR(500) NULL,
    `CreatedBy` BIGINT UNSIGNED NULL,
    `CreatedOn` DATETIME NULL,
    `UpdatedBy` BIGINT UNSIGNED NULL,
    `UpdatedOn` DATETIME NULL,
    PRIMARY KEY (`ID`),
    KEY `tbl_temp_purchase_importtable_requestid_index` (`RequestID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional rollback:
-- DROP TABLE IF EXISTS `tbl_temp_purchase_importtable`;

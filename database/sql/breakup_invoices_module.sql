-- ============================================================================
-- Breakup Invoices Module Registration (For Current Tenant DB)
-- ============================================================================

INSERT INTO `tbl_module` (
    `Name`, 
    `Description`, 
    `CreatedOn`, 
    `CreatedBy`, 
    `Status`, 
    `Level`, 
    `CurrentPage`, 
    `MainModuleID`, 
    `ModuleURL`, 
    `MainModuleIcon`, 
    `IsReport`, 
    `IsGlobalSetting`, 
    `DisplayOrder`
)
SELECT 
    'Breakup Invoices',
    'Breakup / Split Invoice Generator',
    NOW(),
    1,
    1,
    1,
    'breakup-invoices',
    COALESCE(
        (SELECT `MainModuleID` FROM (SELECT `MainModuleID` FROM `tbl_module` WHERE `CurrentPage` IN ('bulk-invoices', 'invoices') LIMIT 1) AS t_main),
        2
    ),
    'breakup-invoices',
    'ri-split-cells-horizontal',
    0,
    0,
    COALESCE(
        (SELECT `DisplayOrder` + 1 FROM (SELECT `DisplayOrder` FROM `tbl_module` WHERE `CurrentPage` IN ('bulk-invoices', 'invoices') ORDER BY `DisplayOrder` DESC LIMIT 1) AS t_order),
        10
    )
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `tbl_module` WHERE `CurrentPage` = 'breakup-invoices' OR `Name` = 'Breakup Invoices'
);


<?php

$tables = [

'stock_assembly_map' => "
	assembly_content_id I4 PRIMARY,
	item_content_id I4 PRIMARY,
	item_position N(10,3),
	quantity_value N(10,3) DEFAULT 1,
	quantity_item C(8) DEFAULT 'SGL'
",

];

global $gBitInstaller;

foreach( array_keys( $tables ) AS $tableName ) {
	$gBitInstaller->registerSchemaTable( STOCK_PKG_NAME, $tableName, $tables[$tableName] );
}


$gBitInstaller->registerPackageInfo( STOCK_PKG_NAME, [
	'description' => 'Stock manages manufacturing assemblies and components with supplier, quantity, and specification tracking.',
	'license'     => '<a href="http://www.gnu.org/licenses/licenses.html#LGPL">LGPL</a>',
] );


// ### Default Preferences
$gBitInstaller->registerPreferences( STOCK_PKG_NAME, [
	[ STOCK_PKG_NAME, 'stock_menu_text',                      'Stock'  ],
	[ STOCK_PKG_NAME, 'stock_show_public_on_upload',          'n'      ],
	[ STOCK_PKG_NAME, 'stock_show_all_to_admins',             'n'      ],
	// Assembly list display
	[ STOCK_PKG_NAME, 'stock_list_title',                     'y'      ],
	[ STOCK_PKG_NAME, 'stock_list_thumbnail',                 'y'      ],
	[ STOCK_PKG_NAME, 'stock_list_user',                      'y'      ],
	[ STOCK_PKG_NAME, 'stock_list_created',                   'y'      ],
	[ STOCK_PKG_NAME, 'stock_list_lastmodif',                 'n'      ],
	[ STOCK_PKG_NAME, 'stock_list_hits',                      'n'      ],
	// Assembly view display
	[ STOCK_PKG_NAME, 'stock_gallery_list_image_titles',      'y'      ],
	[ STOCK_PKG_NAME, 'stock_gallery_list_image_descriptions','y'      ],
	[ STOCK_PKG_NAME, 'stock_gallery_default_sort_mode',      ''       ],
	// Component list display
	[ STOCK_PKG_NAME, 'stock_item_list_desc',                 'y'      ],
	[ STOCK_PKG_NAME, 'stock_item_list_date',                 'n'      ],
	[ STOCK_PKG_NAME, 'stock_item_list_creator',              'n'      ],
	[ STOCK_PKG_NAME, 'stock_item_list_hits',                 'n'      ],
	[ STOCK_PKG_NAME, 'stock_item_list_attid',                'n'      ],
	// Pagination defaults
	[ STOCK_PKG_NAME, 'default_gallery_pagination',           'position_number' ],
	[ STOCK_PKG_NAME, 'rows_per_page',                        '5'      ],
	[ STOCK_PKG_NAME, 'cols_per_page',                        '3'      ],
	[ STOCK_PKG_NAME, 'total_per_page',                       '20'     ],
	// Movement statuses
	[ STOCK_PKG_NAME, 'stock_movement_statuses',         'draft,pending,complete,cancelled' ],
	[ STOCK_PKG_NAME, 'stock_movement_status_draft',     'Draft'     ],
	[ STOCK_PKG_NAME, 'stock_movement_status_pending',   'Pending'   ],
	[ STOCK_PKG_NAME, 'stock_movement_status_complete',  'Complete'  ],
	[ STOCK_PKG_NAME, 'stock_movement_status_cancelled', 'Cancelled' ],
] );

// ### Default User Permissions
$gBitInstaller->registerUserPermissions( STOCK_PKG_NAME, [
	[ 'p_stock_view',    'Can view stock assemblies and components',    'registered', STOCK_PKG_NAME ],
	[ 'p_stock_create',  'Can create stock assemblies and components', 'editors',    STOCK_PKG_NAME ],
	[ 'p_stock_update',  'Can update stock assemblies and components', 'editors',    STOCK_PKG_NAME ],
	[ 'p_stock_expunge', 'Can delete stock records',                  'admin',      STOCK_PKG_NAME ],
	[ 'p_stock_admin',   'Can administer stock',                      'admin',      STOCK_PKG_NAME ],
] );

// ### Register content types
$gBitInstaller->registerContentObjects( STOCK_PKG_NAME, [
	'StockAssembly'  => STOCK_PKG_CLASS_PATH.'StockAssembly.php',
	'StockComponent' => STOCK_PKG_CLASS_PATH.'StockComponent.php',
	'StockMovement'  => STOCK_PKG_CLASS_PATH.'StockMovement.php',
] );

// ### Requirements
$gBitInstaller->registerRequirements( STOCK_PKG_NAME, [
	'liberty' => [ 'min' => '5.0.0' ],
] );

// ### Xref seed data
// liberty_xref_group: x_group, content_type_guid, title, sort_order, role_id, type_href
// liberty_xref_item:  item, content_type_guid, x_group, cross_ref_title, multiple, role_id, cross_ref_href, template, data

$X = BIT_DB_PREFIX;

$xrefTypes = [];
$xrefItems = [];

// ── stockcomponent xref groups (supplier=1, quantity=2, values=3) ─────────────
$xrefTypes[] = "INSERT INTO `{$X}liberty_xref_group` (`x_group`,`content_type_guid`,`title`,`sort_order`,`role_id`,`type_href`,`template`) VALUES ('supplier','stockcomponent','Supplier',         1,3,'','sup')";
$xrefTypes[] = "INSERT INTO `{$X}liberty_xref_group` (`x_group`,`content_type_guid`,`title`,`sort_order`,`role_id`,`type_href`,`template`) VALUES ('quantity','stockcomponent','Quantity',         2,3,'','')";
$xrefTypes[] = "INSERT INTO `{$X}liberty_xref_group` (`x_group`,`content_type_guid`,`title`,`sort_order`,`role_id`,`type_href`,`template`) VALUES ('values',  'stockcomponent','Values',           3,3,'','')";

// ── stockassembly xref groups (kitlocker=1, supplier=2, quantity/BOM=3) ─────────
$xrefTypes[] = "INSERT INTO `{$X}liberty_xref_group` (`x_group`,`content_type_guid`,`title`,`sort_order`,`role_id`,`type_href`,`template`) VALUES ('kitlocker','stockassembly','KitLocker Details',1,3,'','')";
$xrefTypes[] = "INSERT INTO `{$X}liberty_xref_group` (`x_group`,`content_type_guid`,`title`,`sort_order`,`role_id`,`type_href`,`template`) VALUES ('supplier', 'stockassembly','Supplier',         2,3,'','')";
$xrefTypes[] = "INSERT INTO `{$X}liberty_xref_group` (`x_group`,`content_type_guid`,`title`,`sort_order`,`role_id`,`type_href`,`template`) VALUES ('quantity', 'stockassembly','Bill of Material', 3,3,'','bom')";

// ── xref items shared across both content types ───────────────────────────────
foreach( [ 'stockcomponent', 'stockassembly' ] as $guid ) {
	// Supplier sources — multi=1 (multiple suppliers per item)
	$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('#SUP','{$guid}','supplier','Supplier',        1,3,'../contact/?content_id=','sup', NULL)";
	$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('#PN', '{$guid}','supplier','Part Number',      1,3,'',                      'text',NULL)";
	$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('#PR', '{$guid}','supplier','Price',            1,3,'',                      'text',NULL)";
	$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('#URL','{$guid}','supplier','Supplier URL',     1,3,'',                      'text',NULL)";

	// Quantity sources — selectable types multi=0
	// stockassembly uses 'bom'/'bompck' templates (BOM grid); stockcomponent uses 'text'/'value'
	$sglTpl = ( $guid === 'stockassembly' ) ? 'bom'    : 'text';
	$pckTpl = ( $guid === 'stockassembly' ) ? 'bompck' : 'value';
	$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('SGL','{$guid}','quantity','Single unit',     0,3,'','{$sglTpl}',NULL)";
	$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('PCK','{$guid}','quantity','Pack',             0,3,'','{$pckTpl}',NULL)";
	$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('SHT','{$guid}','quantity','Sheet (H x W)',    0,3,'','{$sglTpl}',NULL)";
	$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('VOL','{$guid}','quantity','Volume',           0,3,'','{$sglTpl}',NULL)";
}

// Values sources — stockcomponent only; starter catalogue, all multi=0, add more via admin
foreach( [ 'stockcomponent' ] as $guid ) {
	$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('RES','{$guid}','values','Resistance',       0,3,'','text',NULL)";
	$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('CAP','{$guid}','values','Capacitance',      0,3,'','text',NULL)";
	$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('VLT','{$guid}','values','Voltage',          0,3,'','text',NULL)";
	$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('TOL','{$guid}','values','Tolerance',        0,3,'','text',NULL)";
	$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('PWR','{$guid}','values','Power',            0,3,'','text',NULL)";
	$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('CUR','{$guid}','values','Current',          0,3,'','text',NULL)";
	$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('FRQ','{$guid}','values','Frequency',        0,3,'','text',NULL)";
	$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('IND','{$guid}','values','Inductance',       0,3,'','text',NULL)";
	$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('TMP','{$guid}','values','Temperature',      0,3,'','text',NULL)";
	$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('PKG','{$guid}','values','Package/Footprint', 0,3,'','text',NULL)";
}

// stockassembly kitlocker items — KitLocker-specific fields
$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('KLID', 'stockassembly','kitlocker','Kitlocker ID Code',       1,3,'','text',NULL)";
$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('KLPR', 'stockassembly','kitlocker','Kitlocker Price',         2,3,'','text',NULL)";
$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('KL3M', 'stockassembly','kitlocker','Kitlocker 3 Month Sales', 3,3,'','text',NULL)";
$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('KLURL','stockassembly','kitlocker','Details URL',             4,3,'','text',NULL)";
$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('KLSGL','stockassembly','kitlocker','Kitlocker Stock',         2,3,'','text',NULL)";

// stockmovement xref groups
$xrefTypes[] = "INSERT INTO `{$X}liberty_xref_group` (`x_group`,`content_type_guid`,`title`,`sort_order`,`role_id`,`type_href`) VALUES ('reference','stockmovement','Reference',1,3,'')";
$xrefTypes[] = "INSERT INTO `{$X}liberty_xref_group` (`x_group`,`content_type_guid`,`title`,`sort_order`,`role_id`,`type_href`,`template`) VALUES ('quantity','stockmovement','Items',2,3,'','bom')";

// reference items — REQN=out, TRANS=in from elf, ORDER=in from supplier
$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('REQN', 'stockmovement','reference','Requisition',1,3,'','text',NULL)";
$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('TRANS','stockmovement','reference','Transfer',    1,3,'','text',NULL)";
$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('ORDER','stockmovement','reference','Order',        1,3,'','text',NULL)";

// quantity items — multiple=1, one row per component line; bom/bompck templates
$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('SGL','stockmovement','quantity','Single unit',1,3,'','bom',   NULL)";
$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('PCK','stockmovement','quantity','Pack',        1,3,'','bompck',NULL)";
$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('SHT','stockmovement','quantity','Sheet',       1,3,'','bom',   NULL)";
$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('VOL','stockmovement','quantity','Volume',       1,3,'','bom',   NULL)";

$gBitInstaller->registerSchemaDefault( STOCK_PKG_NAME, array_merge( $xrefTypes, $xrefItems ) );

// ### Requirements
$gBitInstaller->registerRequirements( STOCK_PKG_NAME, [
	'liberty' => [ 'min' => '5.0.1' ],
	'contact' => [ 'min' => '5.0.2' ]
] );
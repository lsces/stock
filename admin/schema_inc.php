<?php

$tables = [

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

// ── 'stock' package-level groups — shared across stockassembly and stockcomponent ──
// sort_order=1: stgrp (group tags, multi-valued KLGnn items)
// sort_order=3: supplier (replaces per-type duplicates)
$xrefTypes[] = "INSERT INTO `{$X}liberty_xref_group` (`x_group`,`content_type_guid`,`title`,`sort_order`,`role_id`,`type_href`,`template`) VALUES ('stgrp',   'stock','Stock Groups', 1,3,'','')";
$xrefTypes[] = "INSERT INTO `{$X}liberty_xref_group` (`x_group`,`content_type_guid`,`title`,`sort_order`,`role_id`,`type_href`,`template`) VALUES ('supplier', 'stock','Supplier',     3,3,'','sup')";

// KLG01–KLG28 — Kitlocker group tags (multiple=0 per item; multiple items per record = multi-group tagging)
$klGroups = [
	'KLG01'=>'General Kits',       'KLG02'=>'DCC Kits',            'KLG03'=>'CBUS Explorer',
	'KLG04'=>'Tools',               'KLG05'=>'Miscellaneous',       'KLG06'=>'ICs',
	'KLG07'=>'General Kit PCBs',    'KLG08'=>'Clearance',           'KLG09'=>'Power Supplies',
	'KLG10'=>'CBUS PCBs',           'KLG11'=>'SMD Starter Kits',    'KLG12'=>'Servo Mounts',
	'KLG13'=>'Pocket Money Projects','KLG14'=>'DCC PCBs',           'KLG15'=>'PICs',
	'KLG16'=>'CBUS Advanced',       'KLG17'=>'Test Modules',        'KLG18'=>'Test Module PCBs',
	'KLG19'=>'PMK PCBs',            'KLG20'=>"CBUS Beginner's Packs",'KLG21'=>'Pocket Money Kits',
	'KLG22'=>'EzyBus',              'KLG23'=>'DCC Traction',        'KLG24'=>'Bitz-in-Bag kits',
	'KLG25'=>'Connector Boards',    'KLG26'=>'CBUS Basic',          'KLG27'=>'ATC',
	'KLG28'=>'POSTAGE',
];
foreach( $klGroups as $item => $title ) {
	$safeTitle = str_replace( "'", "''", $title );
	$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('{$item}','stock','stgrp','{$safeTitle}',0,3,'','',NULL)";
}

// Supplier items — defined once at package level (identical templates for SA and SC)
$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('#SUP','stock','supplier','Supplier',    1,3,'../contact/?content_id=','sup', NULL)";
$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('#URL','stock','supplier','Supplier URL', 1,3,'',                      'text',NULL)";

// ── stockcomponent-specific groups (sort_order=2: quantity, sort_order=4: values) ─────
$xrefTypes[] = "INSERT INTO `{$X}liberty_xref_group` (`x_group`,`content_type_guid`,`title`,`sort_order`,`role_id`,`type_href`,`template`) VALUES ('quantity','stockcomponent','Quantity',2,3,'','')";
$xrefTypes[] = "INSERT INTO `{$X}liberty_xref_group` (`x_group`,`content_type_guid`,`title`,`sort_order`,`role_id`,`type_href`,`template`) VALUES ('values',  'stockcomponent','Values',  4,3,'','')";

// stockcomponent quantity types — different templates from SA (text/value not bom/bomprt)
$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('SGL','stockcomponent','quantity','Single unit',  0,3,'','text', NULL)";
$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('PRT','stockcomponent','quantity','Part',0,3,'','value',NULL)";
$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('PCK','stockcomponent','quantity','Pack size',     0,3,'','value',NULL)";
$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('SHT','stockcomponent','quantity','Sheet', 0,3,'','text', NULL)";
$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('VOL','stockcomponent','quantity','Volume',        0,3,'','text', NULL)";

// ── 'stock' package-level kitlocker group (sort_order=2) — shared across SA and SC ───
$xrefTypes[] = "INSERT INTO `{$X}liberty_xref_group` (`x_group`,`content_type_guid`,`title`,`sort_order`,`role_id`,`type_href`,`template`) VALUES ('kitlocker','stock','KitLocker Details',2,3,'','')";

// ── stockassembly-specific group (sort_order=4: BOM) ────────────────────────────────
$xrefTypes[] = "INSERT INTO `{$X}liberty_xref_group` (`x_group`,`content_type_guid`,`title`,`sort_order`,`role_id`,`type_href`,`template`) VALUES ('quantity','stockassembly','Bill of Material',4,3,'','bom')";

// stockassembly quantity types — BOM grid templates
$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('SGL','stockassembly','quantity','Single unit',  0,3,'','bom',   NULL)";
$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('PRT','stockassembly','quantity','Part',0,3,'','bomprt',NULL)";
$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('SHT','stockassembly','quantity','Sheet', 0,3,'','bom',   NULL)";
$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('VOL','stockassembly','quantity','Volume',        0,3,'','bom',   NULL)";

// Values items — stockcomponent only; starter catalogue, all multi=0, add more via admin
$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('RES','stockcomponent','values','Resistance',       0,3,'','text',NULL)";
$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('CAP','stockcomponent','values','Capacitance',      0,3,'','text',NULL)";
$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('VLT','stockcomponent','values','Voltage',          0,3,'','text',NULL)";
$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('TOL','stockcomponent','values','Tolerance',        0,3,'','text',NULL)";
$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('PWR','stockcomponent','values','Power',            0,3,'','text',NULL)";
$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('CUR','stockcomponent','values','Current',          0,3,'','text',NULL)";
$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('FRQ','stockcomponent','values','Frequency',        0,3,'','text',NULL)";
$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('IND','stockcomponent','values','Inductance',       0,3,'','text',NULL)";
$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('TMP','stockcomponent','values','Temperature',      0,3,'','text',NULL)";
$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('PKG','stockcomponent','values','Package/Footprint', 0,3,'','text',NULL)";

// kitlocker items — package-level ('stock'), shared across SA and SC; all multiple=0 (one value per record)
$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('KLID', 'stock','kitlocker','Kitlocker ID Code',       0,3,'','text',NULL)";
$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('KLPR', 'stock','kitlocker','Kitlocker Price',         0,3,'','text',NULL)";
$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('KL3M', 'stock','kitlocker','Kitlocker 3 Month Sales', 0,3,'','text',NULL)";
$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('KLURL','stock','kitlocker','Details URL',             0,3,'','text',NULL)";
$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('KLSGL','stock','kitlocker','Kitlocker Stock',         0,3,'','text',NULL)";

// stockmovement xref groups
$xrefTypes[] = "INSERT INTO `{$X}liberty_xref_group` (`x_group`,`content_type_guid`,`title`,`sort_order`,`role_id`,`type_href`) VALUES ('reference','stockmovement','Reference',1,3,'')";
$xrefTypes[] = "INSERT INTO `{$X}liberty_xref_group` (`x_group`,`content_type_guid`,`title`,`sort_order`,`role_id`,`type_href`,`template`) VALUES ('assembly','stockmovement','Assembly',1,3,'','assembly')";
$xrefTypes[] = "INSERT INTO `{$X}liberty_xref_group` (`x_group`,`content_type_guid`,`title`,`sort_order`,`role_id`,`type_href`,`template`) VALUES ('quantity','stockmovement','Items',2,3,'','bom')";

// assembly item — links the movement to its assembly or component (multiple=1 for future multi-item reqns)
$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('ASSEMBLY','stockmovement','assembly','Assembly',1,3,'','assembly',NULL)";

// reference items — REQN=out to kitlocker, PBLD=out prebuild, TRANS=in from elf, ORDER=in from supplier
$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('REQN', 'stockmovement','reference','Requisition',1,3,'','text',NULL)";
$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('PBLD','stockmovement','reference','Prebuild',     1,3,'','text',NULL)";
$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('TRANS','stockmovement','reference','Transfer',    1,3,'','text',NULL)";
$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('ORDER','stockmovement','reference','Order',        1,3,'','text',NULL)";

// quantity items — multiple=1, one row per component line; bom/bomprt templates
$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('SGL','stockmovement','quantity','Single unit',  1,3,'','bom',   NULL)";
$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('PRT','stockmovement','quantity','Part',1,3,'','bomprt',NULL)";
$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('SHT','stockmovement','quantity','Sheet',         1,3,'','bom',   NULL)";
$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('VOL','stockmovement','quantity','Volume',         1,3,'','bom',   NULL)";

$gBitInstaller->registerSchemaDefault( STOCK_PKG_NAME, array_merge( $xrefTypes, $xrefItems ) );

// ### Requirements
$gBitInstaller->registerRequirements( STOCK_PKG_NAME, [
	'liberty' => [ 'min' => '5.0.1' ],
	'contact' => [ 'min' => '5.0.1' ]
] );
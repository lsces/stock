<?php

$tables = [

'stock_assembly' => "
	assembly_id I4 PRIMARY,
	content_id I4,
	rows_per_page I4,
	cols_per_page I4,
	preview_content_id I4,
	component_comment C(1)
",

'stock_assembly_component_map' => "
	assembly_content_id I4 NOTNULL,
	item_content_id I4 NOTNULL,
	item_position N(10,3),
	quantity_value N(10,3) DEFAULT 1,
	quantity_item C(8) DEFAULT 'SGL'
",

'stock_component' => "
	component_id I4 PRIMARY,
	content_id I4 NOTNULL
",

'stock_movement' => "
	movement_id  I4 PRIMARY,
	content_id   I4 NOTNULL,
	direction    C(1) DEFAULT 'O',
	status       C(20) DEFAULT 'draft',
	parent_id    I4
",

'stock_movement_item' => "
	movement_content_id  I4 NOTNULL,
	item_content_id      I4 NOTNULL,
	item_position        N(10,3),
	quantity_value       N(10,3) DEFAULT 1,
	quantity_item      C(8) DEFAULT 'SGL'
",

];

global $gBitInstaller;

foreach( array_keys( $tables ) AS $tableName ) {
	$gBitInstaller->registerSchemaTable( STOCK_PKG_NAME, $tableName, $tables[$tableName] );
}

$indices = [
	'stock_assembly_id_idx'       => [ 'table' => 'stock_assembly',               'cols' => 'assembly_id',   'opts' => null ],
	'stock_assembly_content_idx'  => [ 'table' => 'stock_assembly',               'cols' => 'content_id',    'opts' => [ 'UNIQUE' ] ],
	'stock_map_assembly_idx'      => [ 'table' => 'stock_assembly_component_map', 'cols' => 'assembly_content_id', 'opts' => null ],
	'stock_map_item_idx'          => [ 'table' => 'stock_assembly_component_map', 'cols' => 'item_content_id',     'opts' => null ],
	'stock_component_id_idx'           => [ 'table' => 'stock_component',      'cols' => 'component_id',          'opts' => null ],
	'stock_component_content_idx'      => [ 'table' => 'stock_component',      'cols' => 'content_id',            'opts' => [ 'UNIQUE' ] ],
	'stock_movement_content_idx'       => [ 'table' => 'stock_movement',       'cols' => 'content_id',            'opts' => [ 'UNIQUE' ] ],
	'stock_movement_parent_idx'        => [ 'table' => 'stock_movement',       'cols' => 'parent_id',             'opts' => null ],
	'stock_movement_item_movement_idx' => [ 'table' => 'stock_movement_item',  'cols' => 'movement_content_id',   'opts' => null ],
	'stock_movement_item_item_idx'     => [ 'table' => 'stock_movement_item',  'cols' => 'item_content_id',       'opts' => null ],
];
$gBitInstaller->registerSchemaIndexes( STOCK_PKG_NAME, $indices );

$gBitInstaller->registerPackageInfo( STOCK_PKG_NAME, [
	'description' => 'Stock manages manufacturing assemblies and components with supplier, quantity, and specification tracking.',
	'license'     => '<a href="http://www.gnu.org/licenses/licenses.html#LGPL">LGPL</a>',
] );

// ### Sequences
$gBitInstaller->registerSchemaSequences( STOCK_PKG_NAME, [
	'stock_assembly_id_seq'  => [ 'start' => 1 ],
	'stock_component_id_seq' => [ 'start' => 1 ],
	'stock_movement_id_seq'  => [ 'start' => 1 ],
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
	[ 'p_stock_list_assemblies', 'Can list stock assemblies',                    'basic',      STOCK_PKG_NAME ],
	[ 'p_stock_view',            'Can view stock assemblies and components',      'basic',      STOCK_PKG_NAME ],
	[ 'p_stock_create',          'Can create stock assemblies and components',    'registered', STOCK_PKG_NAME ],
	[ 'p_stock_update',          'Can update stock assemblies and components',    'editors',    STOCK_PKG_NAME ],
	[ 'p_stock_admin',           'Can administer stock',                         'editors',    STOCK_PKG_NAME ],
	[ 'p_stock_create_public_gal','Can create public assemblies',                'editors',    STOCK_PKG_NAME ],
] );

// ### Register content types
$gBitInstaller->registerContentObjects( STOCK_PKG_NAME, [
	'StockAssembly'  => STOCK_PKG_CLASS_PATH.'StockAssembly.php',
	'StockComponent' => STOCK_PKG_CLASS_PATH.'StockComponent.php',
] );

// ### Requirements
$gBitInstaller->registerRequirements( STOCK_PKG_NAME, [
	'liberty' => [ 'min' => '5.0.0' ],
] );

// ### Xref seed data
// liberty_xref_group: x_group, content_type_guid, title, sort_order, role_id, type_href
// liberty_xref_item:  item, content_type_guid, x_group, cross_ref_title, multiple, role_id, cross_ref_href, template, data

$X = BIT_DB_PREFIX;

// Helper to generate x_group rows for both content types
$xrefTypes = [];
$xrefItems = [];

foreach( [ 'stockcomponent', 'stockassembly' ] as $guid ) {
	$xrefTypes[] = "INSERT INTO `{$X}liberty_xref_group` (`x_group`,`content_type_guid`,`title`,`sort_order`,`role_id`,`type_href`) VALUES ('supplier','{$guid}','Supplier',  1,3,'')";
	$xrefTypes[] = "INSERT INTO `{$X}liberty_xref_group` (`x_group`,`content_type_guid`,`title`,`sort_order`,`role_id`,`type_href`) VALUES ('quantity','{$guid}','Quantity',  2,3,'')";
	$xrefTypes[] = "INSERT INTO `{$X}liberty_xref_group` (`x_group`,`content_type_guid`,`title`,`sort_order`,`role_id`,`type_href`) VALUES ('values',  '{$guid}','Values',    3,3,'')";

	// Supplier sources — multi=1 (multiple suppliers per item)
	$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('#SUP','{$guid}','supplier','Supplier',        1,3,'../contact/?content_id=','text',NULL)";
	$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('#PN', '{$guid}','supplier','Part Number',      1,3,'',                      'text',NULL)";
	$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('#PR', '{$guid}','supplier','Price',            1,3,'',                      'text',NULL)";
	$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('#URL','{$guid}','supplier','Supplier URL',     1,3,'',                      'text',NULL)";

	// Quantity sources — selectable types multi=0, movement multi=1
	// entry_date on liberty_xref timestamps each MOV row automatically
	$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('SGL','{$guid}','quantity','Single unit',     0,3,'','text',NULL)";
	$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('PCK','{$guid}','quantity','Pack',             0,3,'','text',NULL)";
	$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('SHT','{$guid}','quantity','Sheet (H x W)',    0,3,'','text',NULL)";
	$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('VOL','{$guid}','quantity','Volume',           0,3,'','text',NULL)";
	$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('MOV','{$guid}','quantity','Stock movement',   1,3,'','text',NULL)";

	// Values sources — starter catalogue, all multi=0, add more via admin
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

// stockassembly kitlocker group — price and datasheet URL from KitLocker
$xrefTypes[] = "INSERT INTO `{$X}liberty_xref_group` (`x_group`,`content_type_guid`,`title`,`sort_order`,`role_id`,`type_href`) VALUES ('kitlocker','stockassembly','KitLocker Details',1,3,'')";
$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('KLPR', 'stockassembly','kitlocker','Kitlocker Price',0,3,'','text',NULL)";
$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('KLURL','stockassembly','kitlocker','Details URL',    0,3,'','text',NULL)";

// stockmovement xref — requisition reference links
$xrefTypes[] = "INSERT INTO `{$X}liberty_xref_group` (`x_group`,`content_type_guid`,`title`,`sort_order`,`role_id`,`type_href`) VALUES ('reference','stockmovement','Reference',1,3,'')";
$xrefItems[] = "INSERT INTO `{$X}liberty_xref_item` (`item`,`content_type_guid`,`x_group`,`cross_ref_title`,`multiple`,`role_id`,`cross_ref_href`,`template`,`data`) VALUES ('REQN','stockmovement','reference','Requisition',1,3,'','text',NULL)";

$gBitInstaller->registerSchemaDefault( STOCK_PKG_NAME, array_merge( $xrefTypes, $xrefItems ) );

// ### Requirements
$gBitInstaller->registerRequirements( STOCK_PKG_NAME, [
	'liberty' => [ 'min' => '5.0.1' ],
	'contact' => [ 'min' => '5.0.2' ]
] );
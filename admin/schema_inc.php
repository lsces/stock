<?php

$tables = [

'stock_assembly' => "
	assembly_id I4 PRIMARY,
	content_id I4,
	rows_per_page I4,
	cols_per_page I4,
	thumbnail_size C(32),
	preview_content_id I4,
	component_comment C(1)
",

'stock_assembly_component_map' => "
	assembly_content_id I4 NOTNULL,
	item_content_id I4 NOTNULL,
	item_position F
",

'stock_component' => "
	component_id I4 PRIMARY,
	content_id I4 NOTNULL,
	photo_date I8,
	width I4,
	height I4
",
];

global $gBitInstaller;

foreach( array_keys( $tables ) AS $tableName ) {
	$gBitInstaller->registerSchemaTable( STOCK_PKG_NAME, $tableName, $tables[$tableName] );
}

$indices = [
	'stock_assembly_id_idx'      => [ 'table' => 'stock_assembly', 'cols' => 'assembly_id', 'opts' => null ],
	'stock_assembly_content_idx' => [ 'table' => 'stock_assembly', 'cols' => 'content_id', 'opts' => [ 'UNIQUE' ] ],
	'stock_component_id_idx'     => [ 'table' => 'stock_component', 'cols' => 'component_id', 'opts' => null ],
	'stock_component_content_idx'=> [ 'table' => 'stock_component', 'cols' => 'content_id', 'opts' => [ 'UNIQUE' ] ],
];
$gBitInstaller->registerSchemaIndexes( STOCK_PKG_NAME, $indices );

$gBitInstaller->registerPackageInfo( STOCK_PKG_NAME, [
	'description' => "Stock is a package for managing manufacturing assemblies and components",
	'license' => '<a href="http://www.gnu.org/licenses/licenses.html#LGPL">LGPL</a>',
] );

// ### Sequences
$sequences = [
	'stock_assembly_id_seq' => [ 'start' => 1 ],
	'stock_component_id_seq' => [ 'start' => 1 ],
];
$gBitInstaller->registerSchemaSequences( STOCK_PKG_NAME, $sequences );

// ### Default Preferences
$gBitInstaller->registerPreferences( STOCK_PKG_NAME, [
	[ STOCK_PKG_NAME, 'stock_list_title','y'],
	[ STOCK_PKG_NAME, 'stock_list_created','y'],
	[ STOCK_PKG_NAME, 'stock_list_user','y'],
	[ STOCK_PKG_NAME, 'stock_list_hits','y'],
	[ STOCK_PKG_NAME, 'stock_list_thumbnail','y'],
	[ STOCK_PKG_NAME, 'stock_list_thumbnail_size','small'],
	[ STOCK_PKG_NAME, 'stock_assembly_list_title','y'],
	[ STOCK_PKG_NAME, 'stock_assembly_list_description','y'],
	[ STOCK_PKG_NAME, 'stock_assembly_list_component_titles','y'],
	[ STOCK_PKG_NAME, 'stock_assembly_default_rows_per_page','5'],
	[ STOCK_PKG_NAME, 'stock_assembly_default_cols_per_page','3'],
	[ STOCK_PKG_NAME, 'stock_assembly_default_thumbnail_size','small'],
	[ STOCK_PKG_NAME, 'stock_component_list_title','y'],
	[ STOCK_PKG_NAME, 'stock_component_list_description','y'],
	[ STOCK_PKG_NAME, 'stock_component_default_thumbnail_size','medium'],
	[ STOCK_PKG_NAME, 'stock_menu_text','Stock Assemblies'],
	[ STOCK_PKG_NAME, 'stock_show_public_on_upload','n'],
	[ STOCK_PKG_NAME, 'stock_show_all_to_admins','n'],
] );

// ### Default User Permissions
$gBitInstaller->registerUserPermissions( STOCK_PKG_NAME, [
	['p_stock_list_assemblies', 'Can list stock assemblies', 'basic', STOCK_PKG_NAME],
	['p_stock_view', 'Can view stock assemblies', 'basic', STOCK_PKG_NAME],
	['p_stock_create', 'Can create a stock assembly', 'registered', STOCK_PKG_NAME],
	['p_stock_update', 'Can update stock assembly', 'editors', STOCK_PKG_NAME],
	['p_stock_upload', 'Can upload components to assembly', 'registered', STOCK_PKG_NAME],
	['p_stock_admin', 'Can admin stock assemblies', 'editors', STOCK_PKG_NAME],
	['p_stock_upload_nonimages', 'Can upload non-image files', 'editors', STOCK_PKG_NAME],
	['p_stock_change_thumb_size', 'Can set the thumbnail size for an assembly', 'editors', STOCK_PKG_NAME],
	['p_stock_create_public_gal', 'Can create public assemblies any user can load components into', 'editors', STOCK_PKG_NAME],
	['p_stock_download_assembly_arc', 'Can download an archived copy of stock assembly', 'registered', STOCK_PKG_NAME],
] );

if( defined( 'RSS_PKG_NAME' )) {
	$gBitInstaller->registerPreferences( STOCK_PKG_NAME, [
		[ RSS_PKG_NAME, STOCK_PKG_NAME.'_rss', 'y'],
	]);
}

// ### Register content types
$gBitInstaller->registerContentObjects( STOCK_PKG_NAME, [
	'StockAssembly'=>STOCK_PKG_CLASS_PATH.'StockAssembly.php',
	'StockComponent'=>STOCK_PKG_CLASS_PATH.'StockComponent.php',
] );

// Requirements
$gBitInstaller->registerRequirements( STOCK_PKG_NAME, [
	'liberty' => [ 'min' => '5.0.0' ],
]);

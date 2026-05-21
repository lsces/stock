<?php

$tables = [

'stock_gallery' => "
	gallery_id I4 PRIMARY,
	content_id I4,
	rows_per_page I4,
	cols_per_page I4,
	thumbnail_size C(32),
	preview_content_id I4,
	image_comment C(1)
",

'stock_gallery_image_map' => "
	gallery_content_id I4 NOTNULL,
	item_content_id I4 NOTNULL,
	item_position F
",

'stock_image' => "
	image_id I4 PRIMARY,
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
	'stock_gallery_id_idx'      => [ 'table' => 'stock_gallery', 'cols' => 'gallery_id', 'opts' => null ],
	'stock_gallery_content_idx' => [ 'table' => 'stock_gallery', 'cols' => 'content_id', 'opts' => [ 'UNIQUE' ] ],
	'stock_image_id_idx'        => [ 'table' => 'stock_image', 'cols' => 'image_id', 'opts' => null ],
	'stock_image_content_idx'   => [ 'table' => 'stock_image', 'cols' => 'content_id', 'opts' => [ 'UNIQUE' ] ],
];
$gBitInstaller->registerSchemaIndexes( STOCK_PKG_NAME, $indices );

$gBitInstaller->registerPackageInfo( STOCK_PKG_NAME, [
	'description' => "FishEye is a package for creating image galleries",
	'license' => '<a href="http://www.gnu.org/licenses/licenses.html#LGPL">LGPL</a>',
] );

// ### Sequences
$sequences = [
	'stock_gallery_id_seq' => [ 'start' => 1 ],
	'stock_image_id_seq' => [ 'start' => 1 ],
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
	[ STOCK_PKG_NAME, 'stock_gallery_list_title','y'],
	[ STOCK_PKG_NAME, 'stock_gallery_list_description','y'],
	[ STOCK_PKG_NAME, 'stock_gallery_list_image_titles','y'],
	[ STOCK_PKG_NAME, 'stock_gallery_default_rows_per_page','5'],
	[ STOCK_PKG_NAME, 'stock_gallery_default_cols_per_page','3'],
	[ STOCK_PKG_NAME, 'stock_gallery_default_thumbnail_size','small'],
	[ STOCK_PKG_NAME, 'stock_image_list_title','y'],
	[ STOCK_PKG_NAME, 'stock_image_list_description','y'],
	[ STOCK_PKG_NAME, 'stock_image_default_thumbnail_size','medium'],
	[ STOCK_PKG_NAME, 'stock_menu_text','Image Galleries'],
	// more intuitive if we can see all galleries we can upload images to
	[ STOCK_PKG_NAME, 'stock_show_public_on_upload','n'],
	[ STOCK_PKG_NAME, 'stock_show_all_to_admins','n'],
] );

// ### Default User Permissions
$gBitInstaller->registerUserPermissions( STOCK_PKG_NAME, [
	['p_stock_list_galleries', 'Can list image galleries', 'basic', STOCK_PKG_NAME],
	['p_stock_view', 'Can view image galleries', 'basic', STOCK_PKG_NAME],
	['p_stock_create', 'Can create an image gallery', 'registered', STOCK_PKG_NAME],
	['p_stock_update', 'Can update image gallery', 'editors', STOCK_PKG_NAME],
	['p_stock_upload', 'Can upload images to gallery', 'registered', STOCK_PKG_NAME],
	['p_stock_admin', 'Can admin image galleries', 'editors', STOCK_PKG_NAME],
	['p_stock_upload_nonimages', 'Can upload non_image files', 'editors', STOCK_PKG_NAME],
	['p_stock_change_thumb_size', 'Can set the thumbnail size for a gallery', 'editors', STOCK_PKG_NAME],
	['p_stock_create_public_gal', 'Can create public galleries any user can load images into', 'editors', STOCK_PKG_NAME],
	['p_stock_download_gallery_arc',' Can download an archived copy of Stock gallery', 'registered', STOCK_PKG_NAME],
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


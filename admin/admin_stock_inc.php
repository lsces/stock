<?php

use Bitweaver\KernelTools;

$formGalleryGeneral = [
	"stock_menu_text" => [
		'label' => 'Menu Text',
		'note' => '',
		'type' => 'text',
	],
	"stock_show_public_on_upload" => [
		'label' => 'Show Public Assemblies on Upload',
		'note' => 'Enable this if you want to have all public assemblies visible when adding components. This might cause problems on large sites with many public assemblies.',
		'type' => 'checkbox',
	],
	"stock_show_all_to_admins" => [
		'label' => 'Show all Assemblies to Administrators',
		'note' => 'This will allow assembly admins to move components between all assemblies.',
		'type' => 'checkbox',
	],
];
$gBitSmarty->assign('formGalleryGeneral', $formGalleryGeneral);

$formGalleryListLists = [
	"stock_list_title" => [
		'label' => 'Assembly title',
		'note' => 'List the title of the assembly.',
	],
	"stock_list_thumbnail" => [
		'label' => 'Thumbnail',
		'note' => 'Display a cover thumbnail associated with an assembly',
	],
	"stock_list_description" => [
		'label' => 'Description',
		'note' => 'List the description of an assembly',
	],
	"stock_list_user" => [
		'label' => 'Creator',
		'note' => 'List the name of the user who created the assembly',
	],
	"stock_list_hits" => [
		'label' => 'Hits',
		'note' => 'List number of hits this assembly has received',
	],
	"stock_list_created" => [
		'label' => 'Creation date',
		'note' => 'List the creation date of the assembly',
	],
	"stock_list_lastmodif" => [
		'label' => 'Last modification',
		'note' => 'List date this assembly was last modified',
	],
];
$gBitSmarty->assign('formGalleryListLists', $formGalleryListLists);

$formGalleryLists = [
	"stock_gallery_list_title" => [
		'label' => 'Assembly title',
		'note' => 'When viewing an assembly, display the title',
	],
	"stock_gallery_list_description" => [
		'label' => 'Assembly description',
		'note' => 'When viewing an assembly, display the description below the title',
	],
	"stock_gallery_list_image_titles" => [
		'label' => 'Component titles',
		'note' => 'Show component titles in grid layouts',
	],
	"stock_gallery_list_image_descriptions" => [
		'label' => 'Component descriptions',
		'note' => 'Show component descriptions in grid layouts',
	],
];
$gBitSmarty->assign( 'formGalleryLists', $formGalleryLists );

$formImageLists = [
	"stock_item_list_desc" => [
		'label' => 'Component description',
		'note' => 'Show component description in list and position views',
	],
	"stock_item_list_date" => [
		'label' => 'Created date',
		'note' => 'Show the date the component was created',
	],
	"stock_item_list_creator" => [
		'label' => 'Creator',
		'note' => 'Show the name of the user who created the component',
	],
	"stock_item_list_hits" => [
		'label' => 'Views',
		'note' => 'Show the view count for each component',
	],
	"stock_item_list_attid" => [
		'label' => 'Plugin tag',
		'note' => 'Show the wiki plugin tag that can be used to embed this component',
	],
];
$gBitSmarty->assign( 'formImageLists', $formImageLists );

use Bitweaver\Stock\StockAssembly;

$gBitSmarty->assign( 'galleryPaginationTypes', StockAssembly::getAllLayouts() );

$sortOptions = [
	''                      => KernelTools::tra( 'None' ),
	'lc.title_desc'         => KernelTools::tra( 'Title' ).         ' - '.KernelTools::tra( 'descending' ),
	'lc.title_asc'          => KernelTools::tra( 'Title' ).         ' - '.KernelTools::tra( 'ascending' ),
	'lc.created_desc'       => KernelTools::tra( 'Created' ).       ' - '.KernelTools::tra( 'descending' ),
	'lc.created_asc'        => KernelTools::tra( 'Created' ).       ' - '.KernelTools::tra( 'ascending' ),
	'lc.last_modified_desc' => KernelTools::tra( 'Last Modified' ). ' - '.KernelTools::tra( 'descending' ),
	'lc.last_modified_asc'  => KernelTools::tra( 'Last Modified' ). ' - '.KernelTools::tra( 'ascending' ),
];
$gBitSmarty->assign( 'sortOptions', $sortOptions );

if (!empty($_REQUEST['stockAdminSubmit'])) {
	foreach ($formGalleryGeneral as $item => $data) {
		if( $data['type'] == 'checkbox' ) {
			simple_set_toggle($item, STOCK_PKG_NAME);
		} else {
			$gBitSystem->storeConfig($item, $_REQUEST[$item], STOCK_PKG_NAME);
		}
	}

	foreach ($formGalleryListLists as $item => $data) {
		simple_set_toggle($item, STOCK_PKG_NAME);
	}

	foreach ($formGalleryLists as $item => $data) {
		simple_set_toggle($item, STOCK_PKG_NAME);
	}

	foreach( [ 'default_assembly_pagination', 'rows_per_page', 'cols_per_page', 'total_per_page', 'lines_per_page', 'stock_gallery_default_sort_mode' ] as $key ) {
		$gBitSystem->storeConfig($key, $_REQUEST[$key], STOCK_PKG_NAME);
	}

	foreach ($formImageLists as $item => $data) {
		simple_set_toggle( $item, STOCK_PKG_NAME );
	}
}

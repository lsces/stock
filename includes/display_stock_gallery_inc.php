<?php
/**
 * @package stock
 * @subpackage functions
 */

use Bitweaver\KernelTools;

$displayHash = [ 'perm_name' => 'p_stock_view' ];
$gContent->invokeServices( 'content_display_function', $displayHash );

$listHash = $_REQUEST;
$listHash['max_records'] = $gContent->mInfo["images_per_page"] ?? $max_records;

switch( $gContent->getLayout() ) {
	case 'auto_flow':
		$gBitThemes->loadCss( STOCK_PKG_PATH."css/div_layout.css", true );
		break;
}

$gContent->loadComponents( $listHash );
$gContent->loadParentAssemblies();
$gContent->addHit();

$gBitSmarty->assign( 'listInfo', $listHash['listInfo'] );

$gBitSystem->setBrowserTitle( $gContent->getTitle().' '.KernelTools::tra('Gallery') );
$gBitSystem->display( $gContent->getRenderTemplate() , null, [ 'display_mode' => 'display' ] );

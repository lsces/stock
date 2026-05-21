<?php
/**
 * @package stock
 * @subpackage functions
 */

use \Bitweaver\HttpStatusCodes;
use Bitweaver\KernelTools;

if( !$gContent->isValid() ) {
	$gBitSystem->fatalError( KernelTools::tra( "No image exists with the given ID" ) ,'error.tpl', '', HttpStatusCodes::HTTP_GONE );
}

$displayHash = [ 'perm_name' => 'p_stock_view' ];
$gContent->invokeServices( 'content_display_function', $displayHash );

// Get the proper thumbnail size to display on this page
if( empty( $_REQUEST['size'] )) {
	$_REQUEST['size'] = $gBitSystem->getConfig( 'stock_image_default_thumbnail_size', STOCK_DEFAULT_THUMBNAIL_SIZE );
}

$gBitSystem->setBrowserTitle( $gContent->getTitle() );
if( $gBitThemes->isAjaxRequest() ) {
	$gBitSmarty->display( $gContent->getRenderTemplate() );
} else {
	$gBitSystem->display( $gContent->getRenderTemplate() , null, [ 'display_mode' => 'display' ] );
}


<?php
/**
 * @version $Header: /cvsroot/bitweaver/_bit_stock/view_image_details.php,v 1.1 2010/11/11 22:58:23 spiderr Exp $
 * @package stock
 * @subpackage functions
 */

/**
 * required setup
 */
require_once '../kernel/includes/setup_inc.php';

$gBitSystem->verifyPackage( 'stock' );

global $gBitSystem, $gDebug;

include_once STOCK_PKG_INCLUDE_PATH.'image_lookup_inc.php';

$gContent->invokeServices( 'content_display_function', $displayHash );
$gContent->addHit();

if( is_object( $gGallery ) && $gGallery->isCommentable() ) {
	$commentsParentId = $gContent->mContentId;
	$comments_vars = [ 'stockcomponent' ];
	$comments_prefix_var='stockcomponent:';
	$comments_object_var='stockcomponent';
	$comments_return_url = STOCK_PKG_URL."view_image.php?image_id=".$gContent->mContentId;
	include_once LIBERTY_PKG_INCLUDE_PATH.'comments_inc.php';
}

$gBitSmarty->display( 'bitpackage:stock/view_image_details.tpl' );

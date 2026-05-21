<?php
/**
 * @version $Header$
 * @package stock
 * @subpackage functions
 */

use Bitweaver\KernelTools;
use Bitweaver\HttpStatusCodes;

/**
 * required setup
 */
require_once '../kernel/includes/setup_inc.php';

$gBitSystem->verifyPackage( 'stock' );

global $gBitSystem, $stockErrors, $stockWarnings, $stockSuccess;

include_once STOCK_PKG_INCLUDE_PATH.'gallery_lookup_inc.php';

if( $gContent && $gContent->isValid() ) {
	$gBitSystem->setCanonicalLink( $gContent->getDisplayUrl() );
}

global $gHideModules;
$gHideModules = $gBitSystem->isFeatureActive( 'stock_gallery_hide_modules' );

if ( !$gContent->isValid() ) {
	if ( !empty( $_REQUEST['gallery_id'] ) ) {
		$gBitSystem->fatalError( KernelTools::tra('No gallery exists with the given ID'), null, null, HttpStatusCodes::HTTP_NOT_FOUND );
	}
	// No gallery was indicated so we will redirect to the browse galleries page
	KernelTools::bit_redirect( STOCK_PKG_URL."list_galleries.php", HttpStatusCodes::HTTP_FOUND );
	die;
}

if( $gContent->isCommentable() ) {
	$commentsParentId = $gContent->mContentId;
	$comments_vars = [ 'stockassembly' ];
	$comments_prefix_var='stockassembly:';
	$comments_object_var='stockassembly';
	$comments_return_url = $_SERVER['SCRIPT_NAME']."?gallery_id=".$gContent->mGalleryId;
	include_once LIBERTY_PKG_INCLUDE_PATH.'comments_inc.php';
}

if (!empty($_REQUEST['download'])){
	// Checked against global users group assignment so that feature can be restricted on a group level.
	// If content was checked, user would always have permission to do this.
	$gContent->verifyUserPermission('p_stock_download_gallery_arc');
	$gContent->download();
} else {
	require_once STOCK_PKG_INCLUDE_PATH.'display_stock_gallery_inc.php';
}

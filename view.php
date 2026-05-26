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

include_once STOCK_PKG_INCLUDE_PATH.'assembly_lookup_inc.php';

if( $gContent && $gContent->isValid() ) {
	$gBitSystem->setCanonicalLink( $gContent->getDisplayUrl() );
}

global $gHideModules;
$gHideModules = $gBitSystem->isFeatureActive( 'stock_gallery_hide_modules' );

if ( !$gContent->isValid() ) {
	if ( !empty( $_REQUEST['assembly_id'] ) ) {
		$gBitSystem->fatalError( KernelTools::tra('No gallery exists with the given ID'), null, null, HttpStatusCodes::HTTP_NOT_FOUND );
	}
	// No gallery was indicated so we will redirect to the browse galleries page
	KernelTools::bit_redirect( STOCK_PKG_URL."list_assemblies.php", HttpStatusCodes::HTTP_FOUND );
	die;
}

if( $gContent->isCommentable() ) {
	$commentsParentId = $gContent->mContentId;
	$comments_vars = [ 'stockassembly' ];
	$comments_prefix_var='stockassembly:';
	$comments_object_var='stockassembly';
	$comments_return_url = $_SERVER['SCRIPT_NAME']."?assembly_id=".$gContent->mAssemblyId;
	include_once LIBERTY_PKG_INCLUDE_PATH.'comments_inc.php';
}

require_once STOCK_PKG_INCLUDE_PATH.'display_stock_assembly_inc.php';

<?php
/**
 * @package stock
 * @subpackage functions
 */

/**
 * required setup
 */

require_once '../kernel/includes/setup_inc.php';
global $gBitSystem, $gDebug;

$gBitSystem->verifyPackage( 'stock' );

if( !empty( $_REQUEST['highlight'] ) ) {
	$gBitSmarty->assign( 'highlight', $_REQUEST['highlight'] );
}

include_once STOCK_PKG_INCLUDE_PATH.'component_lookup_inc.php';

if( $gContent && $gContent->isValid() ) {
	$gBitSystem->setCanonicalLink( $gContent->getDisplayUrl() );
}

if( is_object( $gGallery ) && $gGallery->isCommentable() ) {
	$commentsParentId = $gContent->mContentId;
	$comments_vars = [ 'stockcomponent' ];
	$comments_prefix_var='stockcomponent:';
	$comments_object_var='stockcomponent';
	$comments_return_url = $_SERVER['SCRIPT_NAME']."?component_id=" . $gContent->mComponentId ?? $gContent->mContentId;
	include_once LIBERTY_PKG_INCLUDE_PATH.'comments_inc.php';
}

$gContent->addHit();

$gContent->mInfo['stockcomponent_types'] = $gContent->getXrefGroupList();

require_once STOCK_PKG_INCLUDE_PATH.'display_stock_component_inc.php';

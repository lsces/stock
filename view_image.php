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

if( !empty( $_REQUEST['size'] ) ) {
	// nuke old values if set
	$_COOKIE['stockviewsize'] = null;
	setcookie( 'stockviewsize', $_REQUEST['size'], 0, $gBitSystem->getConfig( 'cookie_path', BIT_ROOT_URL ), $gBitSystem->getConfig( 'cookie_domain', '.'.$_SERVER['SERVER_NAME'] ) );
}

if( !empty( $_REQUEST['refresh'] ) ) {
	$gBitSmarty->assign( 'refresh', '?refresh='.time() );
}
if( !empty( $_REQUEST['highlight'] ) ) {
	$gBitSmarty->assign( 'highlight', $_REQUEST['highlight'] );
}

include_once STOCK_PKG_INCLUDE_PATH.'image_lookup_inc.php';

if( $gContent && $gContent->isValid() ) {
	$gBitSystem->setCanonicalLink( $gContent->getDisplayUrl() );
}

global $gHideModules;
$gHideModules = $gBitSystem->isFeatureActive( 'stock_image_hide_modules' );

if( is_object( $gGallery ) && $gGallery->isCommentable() ) {
	$commentsParentId = $gContent->mContentId;
	$comments_vars = [ 'stockcomponent' ];
	$comments_prefix_var='stockcomponent:';
	$comments_object_var='stockcomponent';
	$comments_return_url = $_SERVER['SCRIPT_NAME']."?component_id=" . $gContent->mComponentId ?? $gContent->mContentId;
	include_once LIBERTY_PKG_INCLUDE_PATH.'comments_inc.php';
}

$gContent->addHit();

// this will let LibertyMime know that we want to display the original image
if( $gContent->hasUpdatePermission() || $gGallery && $gGallery->getPreference( 'link_original_images' )) {
	$gContent->mInfo['image_file']['original'] = true;
}

if( $gContent->hasUpdatePermission() ) {
	if( !empty( $_REQUEST['rethumb'] ) ) {
		$gContent->generateThumbnails( false, true );
	}
}

require_once STOCK_PKG_INCLUDE_PATH.'display_stock_image_inc.php';

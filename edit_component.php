<?php
/**
 * @package stock
 * @subpackage functions
 */

namespace Bitweaver\Stock;

use Bitweaver\KernelTools;
use Bitweaver\HttpStatusCodes;
use Bitweaver\Liberty\LibertyContent;

require_once '../kernel/includes/setup_inc.php';

global $gBitSystem;

include_once STOCK_PKG_INCLUDE_PATH.'component_lookup_inc.php';

// A content_id was given but didn't resolve to a real record — that's "not found", not an
// invitation to silently fall into create-new mode. No content_id at all is the real
// create-new case.
if( !empty( $_REQUEST['content_id'] ) && !$gContent->isValid() ) {
	$gBitSystem->fatalError( KernelTools::tra( 'No component exists with the given ID' ), null, null, HttpStatusCodes::HTTP_NOT_FOUND );
}

if( $gContent->isValid() ) {
	$gContent->verifyUpdatePermission();
} else {
	$gBitSystem->verifyPermission( 'p_stock_create' );
}

if( !empty($_REQUEST['save']) ) {
	$isNew = !$gContent->isValid();
	if( $gContent->store( $_REQUEST ) ) {
		$gContent->load();
		if( empty( $gContent->mErrors ) ) {
			$url = $isNew
				? STOCK_PKG_URL.'edit_component.php?content_id='.$gContent->mContentId
				: $gContent->getDisplayUrl();
			header( 'Location: '.$url );
			die;
		}
	}
} elseif( !empty($_REQUEST['delete']) ) {
	// Confirmation happens client-side (view_component.tpl's onclick="return
	// confirm(...)" — same lightweight pattern kernel's admin menu/module-config/
	// layout delete links use, see food package's own delete branches for the
	// same convention) rather than a server-rendered confirmDialog() round-trip
	// — no extra choices to offer here, so a second full page load would just
	// be unnecessary friction.
	$gContent->verifyUserPermission( KernelTools::tra('You do not have permission to delete this component.') );
	if( $gContent->expunge() ) {
		header( 'Location: '.STOCK_PKG_URL );
		die;
	}
}

$gBitSmarty->assign( 'errors', $gContent->mErrors );

if( !$gContent->isValid() && !empty( $_REQUEST['title'] ) ) {
	$gContent->mInfo['title'] = trim( $_REQUEST['title'] );
}

$gContent->loadXrefInfo();
if( isset( $gContent->mXrefInfo->mGroups['stgrp'] ) ) {
	if( isset( $gContent->mXrefInfo->mGroups['kitlocker'] ) ) {
		$gContent->mXrefInfo->mGroups['kitlocker']->mXrefs = array_merge(
			$gContent->mXrefInfo->mGroups['kitlocker']->mXrefs,
			$gContent->mXrefInfo->mGroups['stgrp']->mXrefs
		);
	}
	unset( $gContent->mXrefInfo->mGroups['stgrp'] );
}
$gBitSmarty->assign( 'gXrefInfo', $gContent->mXrefInfo );

$isKitlocker = $gContent->mContentId ? LibertyContent::hasXrefItem( $gContent->mContentId, 'KLID' ) : false;
$gBitSmarty->assign( 'isKitlocker', $isKitlocker );

$gContent->invokeServices( 'content_edit_function' );

$gBitSystem->display( 'bitpackage:stock/edit_component.tpl', KernelTools::tra('Edit Component: ').$gContent->getTitle(), [ 'display_mode' => 'edit' ] );

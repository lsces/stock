<?php
/**
 * @package stock
 * @subpackage functions
 */

namespace Bitweaver\Stock;

use Bitweaver\KernelTools;
use Bitweaver\HttpStatusCodes;

require_once '../kernel/includes/setup_inc.php';

global $gBitSystem, $gBitUser;

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
		if( !empty( $_REQUEST['gallery_additions'] ) ) {
			$gContent->addToAssemblies( $_REQUEST['gallery_additions'] );
		}
		if( empty( $gContent->mErrors ) ) {
			$url = $isNew
				? STOCK_PKG_URL.'edit_component.php?content_id='.$gContent->mContentId
				: $gContent->getDisplayUrl();
			header( 'Location: '.$url );
			die;
		}
	}
} elseif( !empty($_REQUEST['delete']) ) {
	$gContent->verifyUserPermission( KernelTools::tra('You do not have permission to delete this component.') );

	if( !empty( $_REQUEST['cancel'] ) ) {
		// user cancelled
	} elseif( empty( $_REQUEST['confirm'] ) ) {
		$formHash['delete']       = true;
		$formHash['content_id'] = $gContent->mContentId;
		$gBitSystem->confirmDialog( $formHash,
			[
				'confirm_item' => $gContent->getTitle(),
				'warning'      => KernelTools::tra('Are you sure you want to delete this component?').' ('.$gContent->getTitle().') '.KernelTools::tra('It will be removed from all assemblies to which it belongs.'),
				'error'        => KernelTools::tra('This cannot be undone!'),
			],
		);
	} else {
		if( $gContent->expunge() ) {
			header( 'Location: '.STOCK_PKG_URL );
			die;
		}
	}
}

$gBitSmarty->assign( 'errors', $gContent->mErrors );

$gContent->loadParentAssemblies();

$gStockAssembly = new StockAssembly();
$getHash = [ 'user_id' => $gBitUser->mUserId ];
if( $gContent->mContentId ) {
	$getHash['contain_item'] = $gContent->mContentId;
}
if( $gBitSystem->isFeatureActive( 'stock_show_all_to_admins' ) && $gBitUser->hasPermission( 'p_stock_admin' ) ) {
	unset( $getHash['user_id'] );
}
$galleryTree = $gStockAssembly->generateList( $getHash, [ 'name' => 'assembly_content_id', 'id' => 'gallerylist', 'item_attributes' => [ 'class' => 'listingtitle' ], 'radio_checkbox' => true ], true );
$gBitSmarty->assign( 'galleryTree', $galleryTree );
$gBitSmarty->assign( 'requested_gallery', !empty($_REQUEST['assembly_content_id']) ? $_REQUEST['assembly_content_id'] : null );

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

$isKitlocker = $gContent->mContentId ? (bool)$gBitDb->getOne(
	"SELECT COUNT(*) FROM `".BIT_DB_PREFIX."liberty_xref` WHERE `content_id`=? AND `item`='KLID'",
	[ $gContent->mContentId ]
) : false;
$gBitSmarty->assign( 'isKitlocker', $isKitlocker );

$gContent->invokeServices( 'content_edit_function' );

$gBitSystem->display( 'bitpackage:stock/edit_component.tpl', KernelTools::tra('Edit Component: ').$gContent->getTitle(), [ 'display_mode' => 'edit' ] );

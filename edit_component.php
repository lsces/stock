<?php
/**
 * @package stock
 * @subpackage functions
 */

namespace Bitweaver\Stock;

use Bitweaver\KernelTools;

require_once '../kernel/includes/setup_inc.php';

global $gBitSystem, $gBitUser;

include_once STOCK_PKG_INCLUDE_PATH.'component_lookup_inc.php';

if( $gContent->isValid() ) {
	$gContent->verifyUpdatePermission();
} else {
	$gBitSystem->verifyPermission( 'p_stock_create' );
}

if( !empty($_REQUEST['save']) ) {
	if( empty($_REQUEST['assembly_id']) && empty($_REQUEST['component_id']) ) {
		$gBitSmarty->assign( 'msg', KernelTools::tra('No assembly or component was specified') );
		$gBitSystem->display( 'error.tpl', null, [ 'display_mode' => 'edit' ] );
		die;
	}

	if( $gContent->store( $_REQUEST ) ) {
		$gContent->load();
		if( !empty( $_REQUEST['gallery_additions'] ) ) {
			$gContent->addToAssemblies( $_REQUEST['gallery_additions'] );
		}
		if( empty( $gContent->mErrors ) ) {
			header( 'Location: '.$gContent->getDisplayUrl() );
			die;
		}
	}
} elseif( !empty($_REQUEST['delete']) ) {
	$gContent->verifyUserPermission( KernelTools::tra('You do not have permission to delete this component.') );

	if( !empty( $_REQUEST['cancel'] ) ) {
		// user cancelled
	} elseif( empty( $_REQUEST['confirm'] ) ) {
		$formHash['delete']       = true;
		$formHash['component_id'] = $gContent->mComponentId;
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
$galleryTree = $gStockAssembly->generateList( $getHash, [ 'name' => 'assembly_id', 'id' => 'gallerylist', 'item_attributes' => [ 'class' => 'listingtitle' ], 'radio_checkbox' => true ], true );
$gBitSmarty->assign( 'galleryTree', $galleryTree );
$gBitSmarty->assign( 'requested_gallery', !empty($_REQUEST['assembly_id']) ? $_REQUEST['assembly_id'] : null );

$gContent->mInfo['stockcomponent_types'] = $gContent->getXrefGroupList();

$gContent->invokeServices( 'content_edit_function' );

$gBitSystem->display( 'bitpackage:stock/edit_component.tpl', KernelTools::tra('Edit Component: ').$gContent->getTitle(), [ 'display_mode' => 'edit' ] );

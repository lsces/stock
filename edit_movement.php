<?php
/**
 * Create or edit a stock movement. Handles header save, item add/remove, and process.
 *
 * @package stock
 */

namespace Bitweaver\Stock;

require_once '../kernel/includes/setup_inc.php';

global $gBitSystem, $gBitSmarty, $gBitDb;

include_once STOCK_PKG_INCLUDE_PATH.'movement_lookup_inc.php';

if( $gContent->isValid() ) {
	$gContent->verifyUpdatePermission();
} else {
	$gBitSystem->verifyPermission( 'p_stock_create' );
}

$errors = [];

if( !empty( $_REQUEST['save'] ) ) {
	if( $gContent->store( $_REQUEST ) ) {
		$gContent->load();
		header( 'Location: '.STOCK_PKG_URL.'edit_movement.php?movement_id='.$gContent->mMovementId );
		die;
	}
	$errors = $gContent->mErrors;

} elseif( !empty( $_REQUEST['add_item'] ) && $gContent->isValid() ) {
	$componentTitle = trim( $_REQUEST['component_title'] ?? '' );
	$qty            = is_numeric( $_REQUEST['qty'] ?? '' ) ? (float)$_REQUEST['qty'] : 0;
	$qtySrc         = in_array( $_REQUEST['qty_src'] ?? '', [ 'SGL', 'PCK', 'SHT', 'VOL' ] )
	                  ? $_REQUEST['qty_src'] : 'SGL';

	if( empty( $componentTitle ) ) {
		$errors[] = 'Component title is required.';
	} elseif( $qty <= 0 ) {
		$errors[] = 'Quantity must be greater than zero.';
	} else {
		$contentId = $gBitDb->getOne(
			"SELECT sc.`content_id`
			 FROM `".BIT_DB_PREFIX."stock_component` sc
			 INNER JOIN `".BIT_DB_PREFIX."liberty_content` lc ON lc.`content_id` = sc.`content_id`
			 WHERE lc.`title` = ?",
			[ $componentTitle ]
		);
		if( !$contentId ) {
			$errors[] = "Component '$componentTitle' not found.";
		} else {
			$gContent->addItem( (int)$contentId, $qty, $qtySrc );
			$gContent->load();
		}
	}

} elseif( !empty( $_REQUEST['process'] ) && $gContent->isValid() ) {
	if( !$gContent->processMovement() ) {
		$errors = $gContent->mErrors;
	} else {
		$gContent->load();
	}

} elseif( !empty( $_REQUEST['delete'] ) ) {
	$gBitSystem->verifyPermission( 'p_stock_admin' );

	if( !empty( $_REQUEST['cancel'] ) ) {
		header( 'Location: '.STOCK_PKG_URL.'edit_movement.php?movement_id='.$gContent->mMovementId );
		die;
	} elseif( empty( $_REQUEST['confirm'] ) ) {
		$formHash['delete']      = true;
		$formHash['movement_id'] = $gContent->mMovementId;
		$gBitSystem->confirmDialog( $formHash,
			[
				'confirm_item' => $gContent->getTitle(),
				'warning'      => 'Are you sure you want to delete this movement? ('.$gContent->getTitle().')',
				'error'        => 'This cannot be undone!',
			],
		);
	} else {
		$gContent->expunge();
		header( 'Location: '.STOCK_PKG_URL );
		die;
	}

} else {
	// Check for remove_item_NNNN submit buttons
	foreach( $_REQUEST as $key => $val ) {
		if( str_starts_with( $key, 'remove_item_' ) ) {
			$itemContentId = (int)substr( $key, 12 );
			$gContent->removeItem( $itemContentId );
			$gContent->load();
			break;
		}
	}
}

$isComplete = ( ( $gContent->mInfo['status'] ?? '' ) === 'complete' );
$isPending  = ( ( $gContent->mInfo['status'] ?? '' ) === 'pending' );

$gBitSmarty->assign( 'errors',     $errors );
$gBitSmarty->assign( 'isComplete', $isComplete );
$gBitSmarty->assign( 'isPending',  $isPending );
$gBitSmarty->assign( 'qtySrcOptions', [
	'SGL' => 'Single unit',
	'PCK' => 'Pack',
	'SHT' => 'Sheet',
	'VOL' => 'Volume',
] );

$gBitSystem->display( 'bitpackage:stock/edit_movement.tpl', 'Edit Movement: '.$gContent->getTitle(), [ 'display_mode' => 'edit' ] );

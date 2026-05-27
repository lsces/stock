<?php
/**
 * Create or edit a stock movement. Handles header save, item add/remove,
 * CSV upload, and process.
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
		header( 'Location: '.STOCK_PKG_URL.'list_movements.php' );
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
			header( 'Location: '.STOCK_PKG_URL.'edit_movement.php?movement_id='.$gContent->mMovementId );
			die;
		}
	}

} elseif( !empty( $_REQUEST['upload_csv'] ) && $gContent->isValid() ) {
	$file = $_FILES['csv_file'] ?? null;
	if( !$file || $file['error'] !== UPLOAD_ERR_OK ) {
		$errors[] = 'No file uploaded or upload error.';
	} else {
		$handle = fopen( $file['tmp_name'], 'r' );
		if( $handle === false ) {
			$errors[] = 'Could not read uploaded file.';
		} else {
			$csvLoaded  = 0;
			$csvSkipped = 0;
			$csvErrors  = [];
			$rowNum     = 0;
			while( ( $data = fgetcsv( $handle, 1000, ',', '"', '' ) ) !== false ) {
				$rowNum++;
				$componentTitle = trim( $data[0] ?? '' );
				$qty            = isset( $data[2] ) && is_numeric( trim( $data[2] ) ) ? (float)trim( $data[2] ) : null;

				if( empty( $componentTitle ) ) {
					$csvSkipped++;
					continue;
				}
				if( $qty === null || $qty <= 0 ) {
					$csvSkipped++;
					$csvErrors[] = "Row $rowNum: '$componentTitle' — invalid or zero quantity, skipped.";
					continue;
				}

				$contentId = $gBitDb->getOne(
					"SELECT sc.`content_id`
					 FROM `".BIT_DB_PREFIX."stock_component` sc
					 INNER JOIN `".BIT_DB_PREFIX."liberty_content` lc ON lc.`content_id` = sc.`content_id`
					 WHERE lc.`title` = ?",
					[ $componentTitle ]
				);
				if( !$contentId ) {
					$csvSkipped++;
					$csvErrors[] = "Row $rowNum: '$componentTitle' not found, skipped.";
					continue;
				}

				$gContent->addItem( (int)$contentId, $qty, 'SGL' );
				$csvLoaded++;
			}
			fclose( $handle );

			$gBitSmarty->assign( 'csvLoaded',  $csvLoaded );
			$gBitSmarty->assign( 'csvSkipped', $csvSkipped );
			$gBitSmarty->assign( 'csvErrors',  $csvErrors );
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
		header( 'Location: '.STOCK_PKG_URL.'list_movements.php' );
		die;
	}

} else {
	foreach( $_REQUEST as $key => $val ) {
		if( str_starts_with( $key, 'remove_item_' ) ) {
			$itemContentId = (int)substr( $key, 12 );
			$gContent->removeItem( $itemContentId );
			header( 'Location: '.STOCK_PKG_URL.'edit_movement.php?movement_id='.$gContent->mMovementId );
			die;
		}
	}
}

$isComplete = ( ( $gContent->mInfo['status'] ?? '' ) === 'complete' );
$isPending  = ( ( $gContent->mInfo['status'] ?? '' ) === 'pending' );

$gBitSmarty->assign( 'errors',     $errors );
$gBitSmarty->assign( 'isComplete', $isComplete );
$gBitSmarty->assign( 'isPending',  $isPending );
$gBitSmarty->assign( 'statusOptions', [
	'draft'     => 'Draft',
	'pending'   => 'Pending',
	'cancelled' => 'Cancelled',
] );
$gBitSmarty->assign( 'qtySrcOptions', [
	'SGL' => 'Single unit',
	'PCK' => 'Pack',
	'SHT' => 'Sheet',
	'VOL' => 'Volume',
] );

$gBitSystem->display( 'bitpackage:stock/edit_movement.tpl', 'Edit Movement: '.$gContent->getTitle(), [ 'display_mode' => 'edit' ] );

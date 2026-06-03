<?php
/**
 * @package stock
 * @subpackage functions
 */

namespace Bitweaver\Stock;

use Bitweaver\KernelTools;

require_once '../kernel/includes/setup_inc.php';

global $gBitSystem, $gBitSmarty, $gBitUser, $gBitDb;

include_once STOCK_PKG_INCLUDE_PATH.'movement_lookup_inc.php';

if( $gContent->isValid() ) {
	$gContent->verifyUpdatePermission();
} else {
	$gBitSystem->verifyPermission( 'p_stock_create' );
}

// TODO: derive from liberty_xref_item WHERE content_type_guid='stockcomponent' AND x_group='quantity'
$qtyTypes = [ 'SGL', 'PCK', 'SHT', 'VOL' ];

// Movement reference types from DB — drives the type selector on create
$refTypes = $gBitDb->getAssoc(
	"SELECT xi.`item`, xi.`cross_ref_title`
	 FROM `".BIT_DB_PREFIX."liberty_xref_item` xi
	 JOIN `".BIT_DB_PREFIX."liberty_xref_group` xg ON xg.`x_group` = xi.`x_group` AND xg.`content_type_guid` = xi.`content_type_guid`
	 WHERE xi.`content_type_guid` = '".STOCKMOVEMENT_CONTENT_TYPE_GUID."' AND xi.`x_group` = 'reference'
	 ORDER BY xi.`item`"
);

if( !empty( $_REQUEST['fSave'] ) ) {
	$isNew = !$gContent->isValid();
	if( $gContent->store( $_REQUEST ) ) {
		if( $isNew && !empty( $_REQUEST['movement_type'] ) && isset( $refTypes[$_REQUEST['movement_type']] ) ) {
			$typeHash = [ 'content_id' => $gContent->mContentId, 'item' => $_REQUEST['movement_type'], 'fAddXref' => 1 ];
			$gContent->storeXref( $typeHash );
		}
		if( $isNew && !empty( $_FILES['csv_file']['tmp_name'] ) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK ) {
			$csvResult = $gContent->importCsv( $qtyTypes );
			$gBitSmarty->assign( 'csvLoaded',  $csvResult['loaded'] );
			$gBitSmarty->assign( 'csvSkipped', $csvResult['skipped'] );
			$gBitSmarty->assign( 'csvErrors',  $csvResult['errors'] );
		} else {
			header( 'Location: '.$gContent->getDisplayUrl() );
			die;
		}
	}

} elseif( !empty( $_REQUEST['fReceived'] ) && $gContent->isValid() ) {
	$gContent->markReceived();
	header( 'Location: '.$gContent->getDisplayUrl() );
	die;

} elseif( !empty( $_REQUEST['upload_csv'] ) && $gContent->isValid() ) {
	$file = $_FILES['csv_file'] ?? null;
	if( !$file || $file['error'] !== UPLOAD_ERR_OK ) {
		$gContent->mErrors[] = KernelTools::tra( 'No file uploaded or upload error.' );
	} else {
		$csvResult = $gContent->importCsv( $qtyTypes );
		$gBitSmarty->assign( 'csvLoaded',  $csvResult['loaded'] );
		$gBitSmarty->assign( 'csvSkipped', $csvResult['skipped'] );
		$gBitSmarty->assign( 'csvErrors',  $csvResult['errors'] );
	}

} elseif( !empty( $_REQUEST['delete'] ) ) {
	$gBitSystem->verifyPermission( 'p_stock_admin' );
	if( !empty( $_REQUEST['cancel'] ) ) {
		header( 'Location: '.STOCK_PKG_URL.'edit_movement.php?content_id='.$gContent->mContentId );
		die;
	} elseif( empty( $_REQUEST['confirm'] ) ) {
		$gBitSystem->confirmDialog(
			[ 'delete' => true, 'content_id' => $gContent->mContentId ],
			[
				'confirm_item' => $gContent->getTitle(),
				'warning'      => KernelTools::tra( 'Are you sure you want to delete this movement?' ).' ('.$gContent->getTitle().')',
				'error'        => KernelTools::tra( 'This cannot be undone!' ),
			]
		);
	} else {
		$gContent->expunge();
		header( 'Location: '.STOCK_PKG_URL.'list_movements.php' );
		die;
	}
}

if( $gContent->isValid() ) {
	$gContent->mInfo['movement_xref_groups'] = $gContent->getXrefGroupList();
}

$gBitSmarty->assign( 'refTypes', $refTypes );
$gBitSmarty->assign( 'errors',   $gContent->mErrors );

$gBitSystem->display( 'bitpackage:stock/edit_movement.tpl', KernelTools::tra( 'Edit Movement: ' ).$gContent->getTitle(), [ 'display_mode' => 'edit' ] );

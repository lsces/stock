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

function stockProcessMovementCsv( StockMovement $pContent, array $pQtyTypes ): array {
	global $gBitDb;
	$result = [ 'loaded' => 0, 'skipped' => 0, 'errors' => [] ];

	$file = $_FILES['csv_file'] ?? null;
	if( !$file || $file['error'] !== UPLOAD_ERR_OK ) {
		return $result;
	}
	$handle = fopen( $file['tmp_name'], 'r' );
	if( $handle === false ) {
		return $result;
	}

	$nextXorder = (int)$gBitDb->getOne(
		"SELECT COALESCE( MAX(x.`xorder`) + 1, 1 ) FROM `".BIT_DB_PREFIX."liberty_xref` x
		 WHERE x.`content_id` = ? AND x.`item` IN ('SGL','PCK','SHT','VOL')",
		[ $pContent->mContentId ]
	) ?: 1;

	$rowNum = 0;
	while( ( $data = fgetcsv( $handle, 1000, ',', '"', '' ) ) !== false ) {
		$rowNum++;
		if( $rowNum === 1 ) {
			$from    = trim( $data[0] ?? '' );
			$ref     = trim( $data[1] ?? '' );
			$dateStr = trim( $data[2] ?? '' );
			if( $ref !== '' ) {
				$existingXrefId = $gBitDb->getOne(
					"SELECT `xref_id` FROM `".BIT_DB_PREFIX."liberty_xref`
					 WHERE `content_id` = ? AND `item` IN ('REQN','TRANS','ORDER') ORDER BY `xorder`",
					[ $pContent->mContentId ]
				);
				$refHash = [ 'content_id' => $pContent->mContentId, 'item' => 'TRANS', 'xkey' => $ref, 'edit' => $from ];
				$existingXrefId ? $refHash['xref_id'] = $existingXrefId : $refHash['fAddXref'] = 1;
				$pContent->storeXref( $refHash );
			}
			if( $dateStr !== '' ) {
				$parts = explode( '/', $dateStr );
				if( count( $parts ) === 3 ) {
					$year = (int)$parts[2] < 100 ? 2000 + (int)$parts[2] : (int)$parts[2];
					$ts   = mktime( 0, 0, 0, (int)$parts[1], (int)$parts[0], $year );
					if( $ts ) {
						$gBitDb->query( "UPDATE `".BIT_DB_PREFIX."liberty_content` SET `event_time` = ? WHERE `content_id` = ?", [ $ts, $pContent->mContentId ] );
					}
				}
			}
			continue;
		}

		$componentName = trim( $data[0] ?? '' );
		$qty           = (float)trim( $data[1] ?? '' );
		$qtyOverride   = strtoupper( trim( $data[2] ?? '' ) );

		if( $componentName === '' ) { $result['skipped']++; continue; }
		if( $qty <= 0 ) {
			$result['errors'][] = "Row $rowNum: '$componentName' — invalid quantity, skipped.";
			$result['skipped']++;
			continue;
		}

		$contentId = $gBitDb->getOne(
			"SELECT lc.`content_id` FROM `".BIT_DB_PREFIX."liberty_content` lc
			 WHERE lc.`content_type_guid` = 'stockcomponent' AND lc.`title` = ?",
			[ $componentName ]
		);
		if( !$contentId ) {
			$result['errors'][] = "Row $rowNum: '$componentName' not found, skipped.";
			$result['skipped']++;
			continue;
		}

		$qtySrc = in_array( $qtyOverride, $pQtyTypes ) ? $qtyOverride : null;
		if( !$qtySrc ) {
			$placeholders = implode( ',', array_fill( 0, count( $pQtyTypes ), '?' ) );
			$qtySrc = $gBitDb->getOne(
				"SELECT x.`item` FROM `".BIT_DB_PREFIX."liberty_xref` x
				 WHERE x.`content_id` = ? AND x.`item` IN ($placeholders) ORDER BY x.`xorder`",
				array_merge( [ (int)$contentId ], $pQtyTypes )
			) ?: 'SGL';
		}

		$pContent->storeXref( [ 'content_id' => $pContent->mContentId, 'item' => $qtySrc, 'xref' => (int)$contentId, 'xkey' => $qty, 'xorder' => $nextXorder ] );
		$nextXorder++;
		$result['loaded']++;
	}
	fclose( $handle );
	$pContent->load();
	return $result;
}

if( !empty( $_REQUEST['fSave'] ) ) {
	$isNew = !$gContent->isValid();
	if( $gContent->store( $_REQUEST ) ) {
		if( $isNew && !empty( $_REQUEST['movement_type'] ) && isset( $refTypes[$_REQUEST['movement_type']] ) ) {
			$typeHash = [ 'content_id' => $gContent->mContentId, 'item' => $_REQUEST['movement_type'], 'fAddXref' => 1 ];
			$gContent->storeXref( $typeHash );
		}
		if( $isNew && !empty( $_FILES['csv_file']['tmp_name'] ) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK ) {
			$csvResult = stockProcessMovementCsv( $gContent, $qtyTypes );
			$gBitSmarty->assign( 'csvLoaded',  $csvResult['loaded'] );
			$gBitSmarty->assign( 'csvSkipped', $csvResult['skipped'] );
			$gBitSmarty->assign( 'csvErrors',  $csvResult['errors'] );
		} else {
			header( 'Location: '.STOCK_PKG_URL.'edit_movement.php?content_id='.$gContent->mContentId );
			die;
		}
	}

} elseif( !empty( $_REQUEST['fReceived'] ) && $gContent->isValid() ) {
	$gContent->markReceived();
	header( 'Location: '.STOCK_PKG_URL.'edit_movement.php?content_id='.$gContent->mContentId );
	die;

} elseif( !empty( $_REQUEST['upload_csv'] ) && $gContent->isValid() ) {
	$file = $_FILES['csv_file'] ?? null;
	if( !$file || $file['error'] !== UPLOAD_ERR_OK ) {
		$gContent->mErrors[] = KernelTools::tra( 'No file uploaded or upload error.' );
	} else {
		$csvResult = stockProcessMovementCsv( $gContent, $qtyTypes );
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

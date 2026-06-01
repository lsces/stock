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
		// On creation, immediately store the chosen movement type xref
		if( $isNew && !empty( $_REQUEST['movement_type'] ) && isset( $refTypes[$_REQUEST['movement_type']] ) ) {
			$typeHash = [
				'content_id' => $gContent->mContentId,
				'item'       => $_REQUEST['movement_type'],
				'fAddXref'   => 1,
			];
			$gContent->storeXref( $typeHash );
		}
		header( 'Location: '.STOCK_PKG_URL.'edit_movement.php?content_id='.$gContent->mContentId );
		die;
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
		$handle = fopen( $file['tmp_name'], 'r' );
		if( $handle === false ) {
			$gContent->mErrors[] = KernelTools::tra( 'Could not read uploaded file.' );
		} else {
			$csvLoaded  = 0;
			$csvSkipped = 0;
			$csvErrors  = [];
			$rowNum     = 0;

			// Seed xorder from any existing movement items so re-uploads append cleanly
			$nextXorder = (int)$gBitDb->getOne(
				"SELECT COALESCE( MAX(x.`xorder`) + 1, 1 ) FROM `".BIT_DB_PREFIX."liberty_xref` x
				 WHERE x.`content_id` = ? AND x.`item` IN ('SGL','PCK','SHT','VOL')",
				[ $gContent->mContentId ]
			) ?: 1;

			while( ( $data = fgetcsv( $handle, 1000, ',', '"', '' ) ) !== false ) {
				$rowNum++;

				if( $rowNum === 1 ) {
					// Header: from, ref, start_date (dd/mm/yy)
					$from    = trim( $data[0] ?? '' );
					$ref     = trim( $data[1] ?? '' );
					$dateStr = trim( $data[2] ?? '' );

					if( $ref !== '' ) {
						// Update existing reference xref if already set (type selected on create), else insert
						$existingXrefId = $gBitDb->getOne(
							"SELECT `xref_id` FROM `".BIT_DB_PREFIX."liberty_xref`
							 WHERE `content_id` = ? AND `item` IN ('REQN','TRANS','ORDER')
							 ORDER BY `xorder`",
							[ $gContent->mContentId ]
						);
						$refHash = [
							'content_id' => $gContent->mContentId,
							'item'       => 'TRANS',
							'xkey'       => $ref,
							'edit'       => $from,
						];
						if( $existingXrefId ) {
							$refHash['xref_id'] = $existingXrefId;
						} else {
							$refHash['fAddXref'] = 1;
						}
						$gContent->storeXref( $refHash );
					}

					if( $dateStr !== '' ) {
						$parts = explode( '/', $dateStr );
						if( count( $parts ) === 3 ) {
							$year = (int)$parts[2] < 100 ? 2000 + (int)$parts[2] : (int)$parts[2];
							$ts   = mktime( 0, 0, 0, (int)$parts[1], (int)$parts[0], $year );
							if( $ts ) {
								$gBitDb->query(
									"UPDATE `".BIT_DB_PREFIX."liberty_content` SET `event_time` = ? WHERE `content_id` = ?",
									[ $ts, $gContent->mContentId ]
								);
							}
						}
					}
					continue;
				}

				// Data rows: component name, quantity, [optional qty type]
				$componentName = trim( $data[0] ?? '' );
				$rawQty        = trim( $data[1] ?? '' );
				$qtyOverride   = strtoupper( trim( $data[2] ?? '' ) );

				if( $componentName === '' ) {
					$csvSkipped++;
					continue;
				}

				// Take numeric part before any space
				$qty = (float)$rawQty;
				if( $qty <= 0 ) {
					$csvErrors[] = "Row $rowNum: '$componentName' — invalid quantity '$rawQty', skipped.";
					$csvSkipped++;
					continue;
				}

				$contentId = $gBitDb->getOne(
					"SELECT lc.`content_id`
					 FROM `".BIT_DB_PREFIX."liberty_content` lc
					 WHERE lc.`content_type_guid` = 'stockcomponent'
					 AND lc.`title` = ?",
					[ $componentName ]
				);
				if( !$contentId ) {
					$csvErrors[] = "Row $rowNum: '$componentName' not found, skipped.";
					$csvSkipped++;
					continue;
				}

				// Qty type: use CSV column 3 if valid, else read from component's existing xref
				$qtySrc = in_array( $qtyOverride, $qtyTypes ) ? $qtyOverride : null;
				if( !$qtySrc ) {
					$placeholders = implode( ',', array_fill( 0, count( $qtyTypes ), '?' ) );
					$qtySrc = $gBitDb->getOne(
						"SELECT x.`item` FROM `".BIT_DB_PREFIX."liberty_xref` x
						 WHERE x.`content_id` = ? AND x.`item` IN ($placeholders)
						 ORDER BY x.`xorder`",
						array_merge( [ (int)$contentId ], $qtyTypes )
					) ?: 'SGL';
				}

				$itemHash = [
					'content_id' => $gContent->mContentId,
					'item'       => $qtySrc,
					'xref'       => (int)$contentId,
					'xkey'       => $qty,
					'xorder'     => $nextXorder,
				];
				$gContent->storeXref( $itemHash );
				$nextXorder++;
				$csvLoaded++;
			}
			fclose( $handle );
			$gContent->load();

			$gBitSmarty->assign( 'csvLoaded',  $csvLoaded );
			$gBitSmarty->assign( 'csvSkipped', $csvSkipped );
			$gBitSmarty->assign( 'csvErrors',  $csvErrors );
		}
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

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

// Helper: parse dd/mm/yy or dd/mm/yyyy → Unix timestamp, or 0
function parseMovementDate( string $s ): int {
	$parts = explode( '/', trim( $s ) );
	if( count( $parts ) !== 3 ) return 0;
	$year = (int)$parts[2] < 100 ? 2000 + (int)$parts[2] : (int)$parts[2];
	return (int)mktime( 0, 0, 0, (int)$parts[1], (int)$parts[0], $year );
}

if( !empty( $_REQUEST['fSave'] ) ) {
	$isNew = !$gContent->isValid();
	if( $gContent->store( $_REQUEST ) ) {
		// Reference xref — create or update type/key/from
		if( !empty( $_REQUEST['movement_type'] ) && isset( $refTypes[$_REQUEST['movement_type']] ) ) {
			$existingRef = $gBitDb->getRow(
				"SELECT `xref_id` FROM `".BIT_DB_PREFIX."liberty_xref`
				 WHERE `content_id`=? AND `item` IN ('REQN','TRANS','ORDER') ORDER BY `xorder`",
				[ $gContent->mContentId ]
			);
			$refHash = [
				'content_id' => $gContent->mContentId,
				'item'       => $_REQUEST['movement_type'],
				'xkey'       => trim( $_REQUEST['ref_key'] ?? '' ),
				'edit'       => trim( $_REQUEST['ref_from'] ?? '' ),
			];
			if( !empty( $_REQUEST['ref_contact_id'] ) && is_numeric( $_REQUEST['ref_contact_id'] ) ) {
				$refHash['xref'] = (int)$_REQUEST['ref_contact_id'];
			}
			$existingRef ? $refHash['xref_id'] = $existingRef['xref_id'] : $refHash['fAddXref'] = 1;
			$gContent->storeXref( $refHash );
		}
		// Ordered date → xref.start_date
		if( !empty( $_REQUEST['ordered_date'] ) && ($ts = parseMovementDate( $_REQUEST['ordered_date'] )) ) {
			$gBitDb->query(
				"UPDATE `".BIT_DB_PREFIX."liberty_xref` SET `start_date`=?
				 WHERE `content_id`=? AND `item` IN ('REQN','TRANS','ORDER')",
				[ date( 'Y-m-d H:i:s', $ts ), $gContent->mContentId ]
			);
		}
		// Received date → lc.event_time
		if( !empty( $_REQUEST['received_date'] ) && ($ts = parseMovementDate( $_REQUEST['received_date'] )) ) {
			$gBitDb->query(
				"UPDATE `".BIT_DB_PREFIX."liberty_content` SET `event_time`=? WHERE `content_id`=?",
				[ $ts, $gContent->mContentId ]
			);
			$gContent->mInfo['event_time'] = $ts;
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
		$origName = preg_replace( '/[^a-zA-Z0-9_-]/', '_', pathinfo( $file['name'], PATHINFO_FILENAME ) );
		copy( $file['tmp_name'], STOCK_IMPORT_PATH . $origName . '_move_' . $gContent->mContentId . '.csv' );
		$csvResult = $gContent->importCsv( $qtyTypes );
		$gBitSmarty->assign( 'csvLoaded',  $csvResult['loaded'] );
		$gBitSmarty->assign( 'csvSkipped', $csvResult['skipped'] );
		$gBitSmarty->assign( 'csvErrors',  $csvResult['errors'] );
	}

} elseif( !empty( $_REQUEST['fAddAssembly'] ) && $gContent->isValid() ) {
	$targetContentId = isset( $_REQUEST['assembly_content_id'] ) && is_numeric( $_REQUEST['assembly_content_id'] )
	                   ? (int)$_REQUEST['assembly_content_id'] : 0;
	$kitCount        = isset( $_REQUEST['kit_count'] ) && is_numeric( $_REQUEST['kit_count'] ) && (float)$_REQUEST['kit_count'] > 0
	                   ? (float)$_REQUEST['kit_count'] : 1;
	if( $targetContentId ) {
		$targetRow = $gBitDb->getRow(
			"SELECT `content_type_guid`, `title` FROM `".BIT_DB_PREFIX."liberty_content`
			 WHERE `content_id` = ? AND `content_type_guid` IN ('stockassembly','stockcomponent')",
			[ $targetContentId ]
		);
		if( $targetRow ) {
			$assemblyHash = [
				'content_id' => $gContent->mContentId,
				'item'       => 'ASSEMBLY',
				'xref'       => $targetContentId,
				'xkey'       => (string)$kitCount,
				'xkey_ext'   => $targetRow['title'],
				'edit'       => $targetRow['content_type_guid'],
				'fAddXref'   => 1,
			];
			$gContent->storeXref( $assemblyHash );
			if( $targetRow['content_type_guid'] === 'stockassembly' ) {
				$gContent->explodeFromAssembly( $targetContentId, $kitCount );
			} else {
				$nextXorder = (int)$gBitDb->getOne(
					"SELECT COALESCE( MAX(x.`xorder`) + 1, 1 ) FROM `".BIT_DB_PREFIX."liberty_xref` x
					 WHERE x.`content_id` = ? AND x.`item` IN ('SGL','PCK','SHT','VOL')",
					[ $gContent->mContentId ]
				) ?: 1;
				$qtyHash = [
					'content_id' => $gContent->mContentId,
					'item'       => 'SGL',
					'xref'       => $targetContentId,
					'xkey'       => (string)$kitCount,
					'xorder'     => $nextXorder,
					'fAddXref'   => 1,
				];
				$gContent->storeXref( $qtyHash );
			}
		}
	}
	header( 'Location: '.STOCK_PKG_URL.'edit_movement.php?content_id='.$gContent->mContentId );
	die;

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
	$gContent->loadXrefInfo();
	$gBitSmarty->assign( 'gXrefInfo', $gContent->mXrefInfo );
}

// Pre-format dates as dd/mm/yyyy for form fields
$orderedDateVal  = !empty( $gContent->mInfo['ref_start_date'] )
	? date( 'd/m/Y', strtotime( $gContent->mInfo['ref_start_date'] ) ) : '';
$receivedDateVal = !empty( $gContent->mInfo['event_time'] ) && $gContent->mInfo['event_time'] > 0
	? date( 'd/m/Y', (int)$gContent->mInfo['event_time'] ) : '';
$gBitSmarty->assign( 'orderedDateVal',   $orderedDateVal );
$gBitSmarty->assign( 'receivedDateVal',  $receivedDateVal );
$gBitSmarty->assign( 'contactLookupUrl', CONTACT_PKG_URL.'includes/lookup_contact.php' );

$isReqn = ( ( $gContent->mInfo['ref_type'] ?? '' ) === 'REQN' );
if( $isReqn ) {
	$assembly      = new StockAssembly();
	$asmHash       = [ 'show_empty' => true, 'sort_mode' => 'title_asc', 'max_records' => 1000 ];
	$assemblyList  = $assembly->getList( $asmHash );
	$component     = new StockComponent();
	$compHash      = [ 'kitlocker_only' => true, 'sort_mode' => 'title_asc', 'max_records' => 1000 ];
	$componentList = $component->getList( $compHash );
	$itemList = array_merge( array_values( $assemblyList ), array_values( $componentList ) );
	usort( $itemList, fn( $a, $b ) => strcasecmp( $a['title'], $b['title'] ) );
	$itemIds = array_column( $itemList, 'content_id' );
	$klidMap = [];
	if( $itemIds ) {
		$klidRows = $gBitDb->getAll(
			"SELECT x.`content_id`, x.`xkey` FROM `".BIT_DB_PREFIX."liberty_xref` x
			 WHERE x.`item` = 'KLID' AND x.`content_id` IN (".implode( ',', array_fill( 0, count( $itemIds ), '?' ) ).")",
			$itemIds
		);
		foreach( $klidRows as $r ) { $klidMap[$r['content_id']] = $r['xkey']; }
	}
	$gBitSmarty->assign( 'itemListJson', json_encode(
		array_map( fn( $i ) => [ 'id' => (int)$i['content_id'], 'text' => $i['title'], 'klid' => $klidMap[$i['content_id']] ?? '' ], $itemList )
	) );
}
$gBitSmarty->assign( 'isReqn',   $isReqn );
$gBitSmarty->assign( 'refTypes', $refTypes );
$gBitSmarty->assign( 'errors',   $gContent->mErrors );

$gBitSystem->display( 'bitpackage:stock/edit_movement.tpl', KernelTools::tra( 'Edit Movement: ' ).$gContent->getTitle(), [ 'display_mode' => 'edit' ] );

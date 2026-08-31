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

global $gBitSystem, $gBitSmarty, $gBitUser, $gBitDb;

include_once STOCK_PKG_INCLUDE_PATH.'movement_lookup_inc.php';

// A content_id was given but didn't resolve to a real record — that's "not found", not an
// invitation to silently fall into create-new mode. No content_id at all is the real
// create-new case.
if( !empty( $_REQUEST['content_id'] ) && !$gContent->isValid() ) {
	$gBitSystem->fatalError( KernelTools::tra( 'No movement exists with the given ID' ), null, null, HttpStatusCodes::HTTP_NOT_FOUND );
}

if( $gContent->isValid() ) {
	$gContent->verifyUpdatePermission();
} else {
	$gBitSystem->verifyPermission( 'p_stock_create' );
}

// TODO: derive from liberty_xref_item WHERE content_type_guid='stockcomponent' AND x_group='quantity'
$qtyTypes = [ 'SGL', 'PRT', 'SHT', 'VOL' ];

// Movement reference types from DB — drives the type selector on create
$refTypes = $gBitDb->getAssoc(
	"SELECT xi.`item`, xi.`cross_ref_title`
	 FROM `".BIT_DB_PREFIX."liberty_xref_item` xi
	 JOIN `".BIT_DB_PREFIX."liberty_xref_group` xg ON xg.`x_group` = xi.`x_group` AND xg.`content_type_guid` = xi.`content_type_guid`
	 WHERE xi.`content_type_guid` = '".STOCKMOVEMENT_CONTENT_TYPE_GUID."' AND xi.`x_group` = 'reference'
	   AND xi.`item` IN ('ORDER','TRANS')
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
				 WHERE `content_id`=? AND `item` IN ('REQN','TRANS','ORDER','PBLD') ORDER BY `xorder`",
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
			LibertyContent::upsertXrefByContentId( $gContent->mContentId, [ 'REQN', 'TRANS', 'ORDER', 'PBLD' ], [
				'start_date' => date( 'Y-m-d H:i:s', $ts ),
			] );
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
				// Stamp every exploded BOM line with the ASSEMBLY xref's own entry_date so
				// rescaleFromAssembly() can later tell this assembly's lines apart from
				// another assembly's, should the movement ever gain a second one.
				$assemblyEntryDate = $gContent->mInfo['xref_store']['entry_date'] ?? null;
				$gContent->explodeFromAssembly( $targetContentId, $kitCount, $assemblyEntryDate );
			} else {
				// Same entry_date stamp as the assembly branch — keeps this component's own
				// SGL line correctly scoped for syncComponentQuantity() to find later.
				$componentEntryDate = $gContent->mInfo['xref_store']['entry_date'] ?? null;
				$nextXorder = (int)$gBitDb->getOne(
					"SELECT COALESCE( MAX(x.`xorder`) + 1, 1 ) FROM `".BIT_DB_PREFIX."liberty_xref` x
					 WHERE x.`content_id` = ? AND x.`item` IN ('SGL','PRT','SHT','VOL')",
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
				if( $componentEntryDate !== null ) {
					$qtyHash['entry_date'] = $componentEntryDate;
				}
				$gContent->storeXref( $qtyHash );
			}
		}
	}
	header( 'Location: '.STOCK_PKG_URL.'edit_movement.php?content_id='.$gContent->mContentId );
	die;

} elseif( !empty( $_REQUEST['fAdjustAssembly'] ) && $gContent->isValid() ) {
	$adjXrefId   = isset( $_REQUEST['xref_id'] ) && is_numeric( $_REQUEST['xref_id'] ) ? (int)$_REQUEST['xref_id'] : 0;
	$newKitCount = isset( $_REQUEST['new_kit_count'] ) && is_numeric( $_REQUEST['new_kit_count'] )
	               ? max( 1, (float)$_REQUEST['new_kit_count'] ) : 0;
	if( $adjXrefId && $newKitCount > 0 ) {
		// Find the assembly xref via the object layer
		$gContent->loadXrefInfo();
		$asmGroup  = $gContent->mXrefInfo->mGroups['assembly'] ?? null;
		$foundXref = null;
		if( $asmGroup ) {
			foreach( $asmGroup->mXrefs as $xr ) {
				if( (int)$xr['xref_id'] === $adjXrefId ) {
					$foundXref = $xr;
					break;
				}
			}
		}
		if( $foundXref ) {
			$asmContentId = (int)$foundXref['xref'];
			$oldKitCount  = (float)$foundXref['xkey'];
			if( abs( $newKitCount - $oldKitCount ) > 0.0001 && $asmContentId ) {
				if( ($foundXref['data'] ?? '') === 'stockcomponent' ) {
					$gContent->syncComponentQuantity( $asmContentId, $newKitCount, $foundXref['entry_date'] ?? null );
				} else {
					$gContent->rescaleFromAssembly( $asmContentId, $newKitCount, $foundXref['entry_date'] ?? null );
				}
				// Update the ASSEMBLY xref kit count — pass all fields so verify() can
				// identify the item correctly for the UPDATE path; xorder must be passed
				// explicitly or verify() zeroes it on the UPDATE path.
				$kitHash = [
					'xref_id'    => $adjXrefId,
					'content_id' => $gContent->mContentId,
					'item'       => 'ASSEMBLY',
					'xref'       => $asmContentId,
					'xkey'       => (string)$newKitCount,
					'xkey_ext'   => (string)$foundXref['xkey_ext'],
					'edit'       => (string)$foundXref['data'],
					'xorder'     => (int)$foundXref['xorder'],
				];
				$gContent->storeXref( $kitHash );
			}
		}
	}
	header( 'Location: '.STOCK_PKG_URL.'edit_movement.php?content_id='.$gContent->mContentId );
	die;

} elseif( !empty( $_REQUEST['fConvertToRequisition'] ) && $gContent->isValid() ) {
	// PBLD -> REQN: a prebuild delivered to the kitlocker becomes a requisition. Only
	// changes the reference xref's item/xkey (RQ number replaces the prebuild name) —
	// user_id is deliberately left untouched (stock-total implications not yet worked out).
	$rqNumber = trim( $_REQUEST['rq_number'] ?? '' );
	if( $rqNumber !== '' && ( $gContent->mInfo['ref_type'] ?? '' ) === 'PBLD' ) {
		$refRow = $gBitDb->getRow(
			"SELECT `xref_id`, `xorder` FROM `".BIT_DB_PREFIX."liberty_xref`
			 WHERE `content_id` = ? AND `item` = 'PBLD'",
			[ $gContent->mContentId ]
		);
		if( $refRow ) {
			$refHash = [
				'xref_id'    => $refRow['xref_id'],
				'content_id' => $gContent->mContentId,
				'item'       => 'REQN',
				'xkey'       => $rqNumber,
				'xorder'     => (int)$refRow['xorder'],
			];
			$gContent->storeXref( $refHash );
			$gBitDb->query(
				"UPDATE `".BIT_DB_PREFIX."liberty_content` SET `title` = ? WHERE `content_id` = ?",
				[ $rqNumber, $gContent->mContentId ]
			);
		}
	}
	header( 'Location: '.STOCK_PKG_URL.'edit_movement.php?content_id='.$gContent->mContentId );
	die;

} elseif( !empty( $_REQUEST['delete'] ) ) {
	// Confirmation happens client-side (view_movement.tpl's onclick="return
	// confirm(...)" — same lightweight pattern used elsewhere, see
	// edit_component.php's identical delete branch) rather than a
	// server-rendered confirmDialog() round-trip — no extra choices to offer.
	$gBitSystem->verifyPermission( 'p_stock_admin' );
	$gContent->expunge();
	header( 'Location: '.STOCK_PKG_URL.'list_movements.php' );
	die;
}

$assemblyTabs = [];
if( $gContent->isValid() ) {
	$gContent->loadXrefInfo();
	$gBitSmarty->assign( 'gXrefInfo', $gContent->mXrefInfo );

	// One tab per real assembly on this movement — shows only that assembly's own BOM
	// lines (matched by the shared entry_date stamped at explosion time), separate from
	// the flat cross-assembly "Items" tab.
	$asmGroup = $gContent->mXrefInfo->mGroups['assembly'] ?? null;
	if( $asmGroup ) {
		foreach( $asmGroup->mXrefs as $asmXref ) {
			if( ( $asmXref['data'] ?? '' ) !== 'stockassembly' ) {
				continue;
			}
			$entryDate = $asmXref['entry_date'] ?? null;
			$bindVars  = [ $gContent->mContentId ];
			$entrySql  = 'AND x.`entry_date` ';
			if( $entryDate !== null ) {
				$entrySql   .= '= ?';
				$bindVars[]  = $entryDate;
			} else {
				$entrySql .= 'IS NULL';
			}
			$lines = $gBitDb->getAll(
				"SELECT x.`xref_id`, x.`item`, x.`xref`, x.`xkey`, lc.`title` AS component_title
				 FROM `".BIT_DB_PREFIX."liberty_xref` x
				 LEFT JOIN `".BIT_DB_PREFIX."liberty_content` lc ON lc.`content_id` = x.`xref`
				 WHERE x.`content_id` = ? AND x.`item` IN ('SGL','PRT','SHT','VOL') $entrySql
				 ORDER BY x.`xorder`",
				$bindVars
			);
			$assemblyTabs[] = [
				'xref_id'    => (int)$asmXref['xref_id'],
				'content_id' => (int)$asmXref['xref'],
				'title'      => $asmXref['xkey_ext'],
				'kit_count'  => $asmXref['xkey'],
				'lines'      => $lines,
			];
		}
	}
}
$gBitSmarty->assign( 'assemblyTabs', $assemblyTabs );

// Pre-format dates as dd/mm/yyyy for form fields
$orderedDateVal  = !empty( $gContent->mInfo['ref_start_date'] )
	? date( 'd/m/Y', strtotime( $gContent->mInfo['ref_start_date'] ) ) : '';
$receivedDateVal = !empty( $gContent->mInfo['event_time'] ) && $gContent->mInfo['event_time'] > 0
	? date( 'd/m/Y', (int)$gContent->mInfo['event_time'] ) : '';
$gBitSmarty->assign( 'orderedDateVal',   $orderedDateVal );
$gBitSmarty->assign( 'receivedDateVal',  $receivedDateVal );
$gBitSmarty->assign( 'contactLookupUrl', CONTACT_PKG_URL.'includes/lookup_contact.php' );

$refType = $gContent->mInfo['ref_type'] ?? '';
$isReqn  = $refType === 'REQN';
$isPbld  = $refType === 'PBLD';
$isBuild = in_array( $refType, [ 'REQN', 'PBLD' ] );
if( $isBuild ) {
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
$gBitSmarty->assign( 'isPbld',   $isPbld );
$gBitSmarty->assign( 'isBuild',  $isBuild );
$gBitSmarty->assign( 'refType',  $refType );
$gBitSmarty->assign( 'refTypes', $refTypes );
$gBitSmarty->assign( 'errors',   $gContent->mErrors );

$gBitSystem->display( 'bitpackage:stock/edit_movement.tpl', KernelTools::tra( 'Edit Movement: ' ).$gContent->getTitle(), [ 'display_mode' => 'edit' ] );

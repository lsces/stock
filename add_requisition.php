<?php
/**
 * @package stock
 * @subpackage functions
 */

namespace Bitweaver\Stock;

use Bitweaver\KernelTools;

require_once '../kernel/includes/setup_inc.php';

global $gBitSystem, $gBitSmarty, $gBitUser, $gBitDb;

$gBitSystem->verifyPermission( 'p_stock_create' );

if( !empty( $_REQUEST['fCancel'] ) ) {
	header( 'Location: '.STOCK_PKG_URL.'list_stock.php' );
	die;
}

if( !empty( $_REQUEST['fCreate'] ) ) {
	$targetContentId = isset( $_REQUEST['assembly_content_id'] ) && is_numeric( $_REQUEST['assembly_content_id'] )
	                   ? (int)$_REQUEST['assembly_content_id'] : null;
	$kitCount        = isset( $_REQUEST['kit_count'] ) && is_numeric( $_REQUEST['kit_count'] ) && (float)$_REQUEST['kit_count'] > 0
	                   ? (float)$_REQUEST['kit_count'] : 1;

	$title = trim( $_REQUEST['title'] ?? '' );

	if( !$targetContentId ) {
		$errors[] = KernelTools::tra( 'Please select an assembly or component.' );
	} elseif( $title === '' ) {
		$errors[] = KernelTools::tra( 'Please enter an RQ number.' );
	} else {
		$targetRow = $gBitDb->getRow(
			"SELECT `content_type_guid`, `title` FROM `".BIT_DB_PREFIX."liberty_content`
			 WHERE `content_id` = ? AND `content_type_guid` IN ('stockassembly','stockcomponent')",
			[ $targetContentId ]
		);
		$targetGuid  = $targetRow['content_type_guid'] ?? null;
		$targetTitle = $targetRow['title'] ?? '';
		if( !$targetGuid ) {
			$errors[] = KernelTools::tra( 'Item not found.' );
		} else {
			$movement  = new StockMovement();
			$paramHash = [
				'title'             => $title,
				'content_type_guid' => STOCKMOVEMENT_CONTENT_TYPE_GUID,
			];
			if( $movement->store( $paramHash ) ) {
				$reqnHash = [
					'content_id' => $movement->mContentId,
					'item'       => 'REQN',
					'xkey'       => $title,
					'fAddXref'   => 1,
				];
				$orderedDateStr = trim( $_REQUEST['ordered_date'] ?? '' );
				if( $orderedDateStr !== '' ) {
					$parts = explode( '/', $orderedDateStr );
					if( count( $parts ) === 3 ) {
						$year = (int)$parts[2] < 100 ? 2000 + (int)$parts[2] : (int)$parts[2];
						$ts   = mktime( 0, 0, 0, (int)$parts[1], (int)$parts[0], $year );
						if( $ts ) $reqnHash['start_date'] = $ts;
					}
				}
				$movement->storeXref( $reqnHash );
				$assemblyHash = [
					'content_id' => $movement->mContentId,
					'item'       => 'ASSEMBLY',
					'xref'       => $targetContentId,
					'xkey'       => (string)$kitCount,
					'xkey_ext'   => $targetTitle,
					'edit'       => $targetGuid,
					'fAddXref'   => 1,
				];
				$movement->storeXref( $assemblyHash );
				if( $targetGuid === 'stockassembly' ) {
					$movement->explodeFromAssembly( $targetContentId, $kitCount );
				} else {
					// Single component — one SGL quantity row
					$qtyHash = [
						'content_id' => $movement->mContentId,
						'item'       => 'SGL',
						'xref'       => $targetContentId,
						'xkey'       => (string)$kitCount,
						'xorder'     => 1,
						'fAddXref'   => 1,
					];
					$movement->storeXref( $qtyHash );
				}
				header( 'Location: '.STOCK_PKG_URL.'edit_movement.php?content_id='.$movement->mContentId );
				die;
			}
			$errors = $movement->mErrors;
		}
	}
}

// Assemblies and components merged into one flat alphabetical list
$assembly     = new StockAssembly();
$asmHash      = [ 'show_empty' => true, 'sort_mode' => 'title_asc', 'max_records' => 1000 ];
$assemblyList = $assembly->getList( $asmHash );

$component     = new StockComponent();
$compHash      = [ 'kitlocker_only' => true, 'sort_mode' => 'title_asc', 'max_records' => 1000 ];
$componentList = $component->getList( $compHash );

$itemList = array_merge( array_values( $assemblyList ), array_values( $componentList ) );
usort( $itemList, fn( $a, $b ) => strcasecmp( $a['title'], $b['title'] ) );

// Pre-select if coming from list_stock
$preselect = isset( $_REQUEST['assembly_content_id'] ) && is_numeric( $_REQUEST['assembly_content_id'] )
             ? (int)$_REQUEST['assembly_content_id'] : null;
$kitCount  = isset( $_REQUEST['kit_count'] ) && is_numeric( $_REQUEST['kit_count'] )
             ? (float)$_REQUEST['kit_count'] : 1;

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
$itemListJson = json_encode( array_map(
	fn( $i ) => [ 'id' => (int)$i['content_id'], 'text' => $i['title'], 'klid' => $klidMap[$i['content_id']] ?? '' ],
	$itemList
) );
$preselectTitle = '';
if( $preselect ) {
	foreach( $itemList as $item ) {
		if( (int)$item['content_id'] === $preselect ) { $preselectTitle = $item['title']; break; }
	}
}
$gBitSmarty->assign( 'itemListJson',   $itemListJson );
$gBitSmarty->assign( 'preselect',      $preselect );
$gBitSmarty->assign( 'preselectTitle', $preselectTitle );
$gBitSmarty->assign( 'kitCount',       $kitCount );
$gBitSmarty->assign( 'todayFormatted', date( 'd/m/Y' ) );
$gBitSmarty->assign( 'errors',         $errors ?? [] );

$gBitSystem->display( 'bitpackage:stock/add_requisition.tpl', KernelTools::tra( 'Create Requisition' ), [ 'display_mode' => 'edit' ] );

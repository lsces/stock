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
	header( 'Location: '.STOCK_PKG_URL.'list_movements.php' );
	die;
}

if( !empty( $_REQUEST['fCreate'] ) ) {
	$targetContentId = isset( $_REQUEST['assembly_content_id'] ) && is_numeric( $_REQUEST['assembly_content_id'] )
	                   ? (int)$_REQUEST['assembly_content_id'] : null;
	$kitCount        = isset( $_REQUEST['kit_count'] ) && is_numeric( $_REQUEST['kit_count'] ) && (float)$_REQUEST['kit_count'] > 0
	                   ? (float)$_REQUEST['kit_count'] : 1;
	$title           = trim( $_REQUEST['title'] ?? '' );
	$rqRef           = trim( $_REQUEST['rq_ref'] ?? '' );

	if( !$targetContentId ) {
		$errors[] = KernelTools::tra( 'Please select an assembly.' );
	} elseif( $title === '' ) {
		$errors[] = KernelTools::tra( 'Please enter a build reference.' );
	} else {
		$targetRow = $gBitDb->getRow(
			"SELECT `title` FROM `".BIT_DB_PREFIX."liberty_content`
			 WHERE `content_id` = ? AND `content_type_guid` = 'stockassembly'",
			[ $targetContentId ]
		);
		if( !$targetRow ) {
			$errors[] = KernelTools::tra( 'Assembly not found.' );
		} else {
			$movement  = new StockMovement();
			$paramHash = [
				'title'             => $title,
				'content_type_guid' => STOCKMOVEMENT_CONTENT_TYPE_GUID,
				'edit'              => $rqRef,
			];
			if( $movement->store( $paramHash ) ) {
				$pbldHash = [
					'content_id' => $movement->mContentId,
					'item'       => 'PBLD',
					'xkey'       => $title,
					'fAddXref'   => 1,
				];
				$movement->storeXref( $pbldHash );
				$assemblyHash = [
					'content_id' => $movement->mContentId,
					'item'       => 'ASSEMBLY',
					'xref'       => $targetContentId,
					'xkey'       => (string)$kitCount,
					'xkey_ext'   => $targetRow['title'],
					'edit'       => 'stockassembly',
					'fAddXref'   => 1,
				];
				$movement->storeXref( $assemblyHash );
				$movement->explodeFromAssembly( $targetContentId, $kitCount );
				header( 'Location: '.STOCK_PKG_URL.'edit_movement.php?content_id='.$movement->mContentId );
				die;
			}
			$errors = $movement->mErrors;
		}
	}
}

$assembly     = new StockAssembly();
$asmHash      = [ 'show_empty' => true, 'sort_mode' => 'title_asc', 'max_records' => 1000 ];
$assemblyList = $assembly->getList( $asmHash );

$preselect = isset( $_REQUEST['assembly_content_id'] ) && is_numeric( $_REQUEST['assembly_content_id'] )
             ? (int)$_REQUEST['assembly_content_id'] : null;
$kitCount  = isset( $_REQUEST['kit_count'] ) && is_numeric( $_REQUEST['kit_count'] )
             ? (float)$_REQUEST['kit_count'] : 1;

$itemIds = array_column( $assemblyList, 'content_id' );
$klidMap = [];
if( $itemIds ) {
	$klidRows = $gBitDb->getAll(
		"SELECT x.`content_id`, x.`xkey` FROM `".BIT_DB_PREFIX."liberty_xref` x
		 WHERE x.`item` = 'KLID' AND x.`content_id` IN (".implode( ',', array_fill( 0, count( $itemIds ), '?' ) ).")",
		$itemIds
	);
	foreach( $klidRows as $r ) { $klidMap[$r['content_id']] = $r['xkey']; }
}
$itemListJson = json_encode( array_values( array_map(
	fn( $i ) => [ 'id' => (int)$i['content_id'], 'text' => $i['title'], 'klid' => $klidMap[$i['content_id']] ?? '' ],
	$assemblyList
) ) );
$preselectTitle = '';
if( $preselect ) {
	foreach( $assemblyList as $item ) {
		if( (int)$item['content_id'] === $preselect ) { $preselectTitle = $item['title']; break; }
	}
}

$gBitSmarty->assign( 'itemListJson',   $itemListJson );
$gBitSmarty->assign( 'preselect',      $preselect );
$gBitSmarty->assign( 'preselectTitle', $preselectTitle );
$gBitSmarty->assign( 'kitCount',       $kitCount );
$gBitSmarty->assign( 'errors',         $errors ?? [] );

$gBitSystem->display( 'bitpackage:stock/add_prebuild.tpl', KernelTools::tra( 'Create Prebuild' ), [ 'display_mode' => 'edit' ] );

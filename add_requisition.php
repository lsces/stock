<?php
/**
 * @package stock
 * @subpackage functions
 */

namespace Bitweaver\Stock;

use Bitweaver\KernelTools;

require_once '../kernel/includes/setup_inc.php';

global $gBitSystem, $gBitSmarty, $gBitUser;

$gBitSystem->verifyPermission( 'p_stock_create' );

if( !empty( $_REQUEST['fCancel'] ) ) {
	header( 'Location: '.STOCK_PKG_URL.'list_stock.php' );
	die;
}

if( !empty( $_REQUEST['fCreate'] ) ) {
	$assemblyContentId = isset( $_REQUEST['assembly_content_id'] ) && is_numeric( $_REQUEST['assembly_content_id'] )
	                     ? (int)$_REQUEST['assembly_content_id'] : null;
	$kitCount          = isset( $_REQUEST['kit_count'] ) && is_numeric( $_REQUEST['kit_count'] ) && (float)$_REQUEST['kit_count'] > 0
	                     ? (float)$_REQUEST['kit_count'] : 1;

	$title = trim( $_REQUEST['title'] ?? '' );

	if( !$assemblyContentId ) {
		$errors[] = KernelTools::tra( 'Please select an assembly.' );
	} elseif( $title === '' ) {
		$errors[] = KernelTools::tra( 'Please enter an RQ number.' );
	} else {
		// Verify assembly exists without relying on content type registry
		global $gBitDb;
		$assemblyExists = $gBitDb->getOne(
			"SELECT `content_id` FROM `".BIT_DB_PREFIX."liberty_content`
			 WHERE `content_id` = ? AND `content_type_guid` = 'stockassembly'",
			[ $assemblyContentId ]
		);
		if( !$assemblyExists ) {
			$errors[] = KernelTools::tra( 'Assembly not found.' );
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
				$movement->storeXref( $reqnHash );
				$assemblyHash = [
					'content_id' => $movement->mContentId,
					'item'       => 'ASSEMBLY',
					'xref'       => $assemblyContentId,
					'fAddXref'   => 1,
				];
				$movement->storeXref( $assemblyHash );
				$movement->explodeFromAssembly( $assemblyContentId, $kitCount );
				header( 'Location: '.STOCK_PKG_URL.'edit_movement.php?content_id='.$movement->mContentId );
				die;
			}
			$errors = $movement->mErrors;
		}
	}
}

// Assembly list for selector
$assembly  = new StockAssembly();
$listHash  = [ 'show_empty' => true, 'sort_mode' => 'title_asc', 'max_records' => 500 ];
$assemblyList = $assembly->getList( $listHash );

// Pre-select if coming from list_stock
$preselect = isset( $_REQUEST['assembly_content_id'] ) && is_numeric( $_REQUEST['assembly_content_id'] )
             ? (int)$_REQUEST['assembly_content_id'] : null;
$kitCount  = isset( $_REQUEST['kit_count'] ) && is_numeric( $_REQUEST['kit_count'] )
             ? (float)$_REQUEST['kit_count'] : 1;

$gBitSmarty->assign( 'assemblyList',      $assemblyList );
$gBitSmarty->assign( 'preselect',         $preselect );
$gBitSmarty->assign( 'kitCount',          $kitCount );
$gBitSmarty->assign( 'errors',            $errors ?? [] );

$gBitSystem->display( 'bitpackage:stock/add_requisition.tpl', KernelTools::tra( 'Create Requisition' ), [ 'display_mode' => 'edit' ] );

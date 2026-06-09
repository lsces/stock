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
	header( 'Location: '.STOCK_PKG_URL.'list_stock.php?shortages=1' );
	die;
}

$errors   = [];
$fromPost = false;

if( !empty( $_POST['fCreate'] ) ) {
	$fromPost        = true;
	$refKey          = trim( $_POST['ref_key'] ?? '' );
	$supplierCid     = isset( $_POST['supplier_contact_id'] ) && is_numeric( $_POST['supplier_contact_id'] )
	                   ? (int)$_POST['supplier_contact_id'] : 0;
	$componentIds    = array_map( 'intval', (array)( $_POST['component_id'] ?? [] ) );
	$qtyTypesPost    = (array)( $_POST['qty_type'] ?? [] );
	$qtysPost        = (array)( $_POST['qty'] ?? [] );
	$orderedDatePost = trim( $_POST['ordered_date'] ?? '' );

	if( $refKey === '' ) {
		$errors[] = KernelTools::tra( 'Please enter an order reference.' );
	} elseif( !array_filter( $componentIds ) ) {
		$errors[] = KernelTools::tra( 'No lines to order.' );
	} else {
		$movement  = new StockMovement();
		$paramHash = [
			'title'             => $refKey,
			'content_type_guid' => STOCKMOVEMENT_CONTENT_TYPE_GUID,
		];
		if( $movement->store( $paramHash ) ) {
			$refHash = [
				'content_id' => $movement->mContentId,
				'item'       => 'ORDER',
				'xkey'       => $refKey,
				'fAddXref'   => 1,
			];
			if( $supplierCid ) {
				$refHash['xref'] = $supplierCid;
			}
			if( $orderedDatePost !== '' ) {
				$parts = explode( '/', $orderedDatePost );
				if( count( $parts ) === 3 ) {
					$year = (int)$parts[2] < 100 ? 2000 + (int)$parts[2] : (int)$parts[2];
					$ts   = (int)mktime( 0, 0, 0, (int)$parts[1], (int)$parts[0], $year );
					if( $ts ) $refHash['start_date'] = $ts;
				}
			}
			$movement->storeXref( $refHash );

			$xorder = 1;
			foreach( $componentIds as $i => $cid ) {
				if( !$cid ) continue;
				$qty   = (float)( $qtysPost[$i] ?? 0 );
				if( $qty <= 0 ) continue;
				$qtype = in_array( $qtyTypesPost[$i] ?? '', [ 'SGL', 'PCK', 'SHT', 'VOL' ] )
				         ? $qtyTypesPost[$i] : 'SGL';
				$lineHash = [
					'content_id' => $movement->mContentId,
					'item'       => $qtype,
					'xref'       => $cid,
					'xkey'       => (string)$qty,
					'xorder'     => $xorder++,
					'fAddXref'   => 1,
				];
				$movement->storeXref( $lineHash );
			}
			header( 'Location: '.STOCK_PKG_URL.'edit_movement.php?content_id='.$movement->mContentId );
			die;
		}
		$errors = array_merge( $errors, $movement->mErrors );
	}
}

$lines = [];

if( $fromPost ) {
	$cids  = array_map( 'intval', (array)( $_POST['component_id'] ?? [] ) );
	$types = (array)( $_POST['qty_type'] ?? [] );
	$qs    = (array)( $_POST['qty'] ?? [] );
	$meta  = [];
	if( $cids ) {
		$metaRows = $gBitDb->getAll(
			"SELECT lc.`content_id`, lc.`title`,
				(SELECT FIRST 1 sup.`xkey` FROM `".BIT_DB_PREFIX."liberty_xref` sup
				 WHERE sup.`content_id` = lc.`content_id` AND sup.`item` = '#SUP'
				 ORDER BY sup.`xorder`) AS part_number
			 FROM `".BIT_DB_PREFIX."liberty_content` lc
			 WHERE lc.`content_id` IN (".implode( ',', array_fill( 0, count( $cids ), '?' ) ).")",
			$cids
		);
		foreach( $metaRows as $r ) {
			$meta[$r['content_id']] = [ 'title' => $r['title'], 'part_number' => $r['part_number'] ];
		}
	}
	foreach( $cids as $i => $cid ) {
		if( !$cid ) continue;
		$qtype   = in_array( $types[$i] ?? '', [ 'SGL', 'PCK', 'SHT', 'VOL' ] ) ? $types[$i] : 'SGL';
		$lines[] = [
			'component_id' => $cid,
			'title'        => $meta[$cid]['title'] ?? '',
			'part_number'  => $meta[$cid]['part_number'] ?? '',
			'qty_type'     => $qtype,
			'qty'          => (float)( $qs[$i] ?? 0 ),
		];
	}
	$supplierVal    = trim( $_POST['ref_from'] ?? '' );
	$supplierCidVal = (int)( $_POST['supplier_contact_id'] ?? 0 );
	$refKeyVal      = trim( $_POST['ref_key'] ?? '' );
	$orderedDateVal = trim( $_POST['ordered_date'] ?? date( 'd/m/Y' ) );
} else {
	$assemblyContentId = isset( $_REQUEST['assembly_content_id'] ) && is_numeric( $_REQUEST['assembly_content_id'] )
	                     ? (int)$_REQUEST['assembly_content_id'] : null;
	$kitCount          = isset( $_REQUEST['kit_count'] ) && is_numeric( $_REQUEST['kit_count'] ) && (float)$_REQUEST['kit_count'] > 0
	                     ? (float)$_REQUEST['kit_count'] : 1;
	$find              = trim( $_REQUEST['find'] ?? '' );

	$X        = BIT_DB_PREFIX;
	$bindVars = [];

	if( $assemblyContentId ) {
		$findSql = $find !== '' ? " AND UPPER(lc.`title`) LIKE ?" : '';
		if( $find !== '' ) $bindVars[] = '%'.strtoupper( $find ).'%';

		$query = "SELECT lc.`content_id`, lc.`title`,
						bom.`item` AS qty_type,
						CAST(bom.`xkey` AS DOUBLE PRECISION) AS bom_qty,
						(SELECT FIRST 1 sup.`xkey`
						 FROM `{$X}liberty_xref` sup
						 WHERE sup.`content_id` = lc.`content_id` AND sup.`item` = '#SUP'
						 ORDER BY sup.`xorder`) AS part_number,
						(SELECT FIRST 1 CAST(pk.`xkey` AS DOUBLE PRECISION)
						 FROM `{$X}liberty_xref` pk
						 WHERE pk.`content_id` = lc.`content_id` AND pk.`item` = 'PCK') AS pack_size,
						(SELECT SUM( CASE WHEN EXISTS (
								SELECT 1 FROM `{$X}liberty_xref` r
								WHERE r.`content_id` = mx.`content_id` AND r.`item` IN ('TRANS','ORDER')
							) THEN CAST(mx.`xkey` AS DOUBLE PRECISION)
							  ELSE -CAST(mx.`xkey` AS DOUBLE PRECISION) END )
						 FROM `{$X}liberty_xref` mx
						 INNER JOIN `{$X}liberty_content` mc ON mc.`content_id` = mx.`content_id`
						 	AND mc.`content_type_guid` = 'stockmovement'
						 WHERE mx.`xref` = lc.`content_id`
						   AND mx.`item` = bom.`item`
						   AND mx.`xkey` SIMILAR TO '[0-9]+(\.[0-9]+)?') AS stock_level
				FROM `{$X}liberty_content` lc
					INNER JOIN `{$X}liberty_xref` bom ON bom.`content_id` = ?
						AND bom.`item` IN ('SGL','PCK','SHT','VOL')
						AND bom.`xref` = lc.`content_id`
				WHERE lc.`content_type_guid` = 'stockcomponent'
				$findSql
				ORDER BY bom.`xorder`";
		$bindVars[] = $assemblyContentId;
	} else {
		$whereSql = '';
		if( $find !== '' ) {
			$whereSql   = " AND UPPER(lc.`title`) LIKE ?";
			$bindVars[] = '%'.strtoupper( $find ).'%';
		}
		$query = "SELECT lc.`content_id`, lc.`title`,
						x.`item` AS qty_type,
						CAST(NULL AS DOUBLE PRECISION) AS bom_qty,
						(SELECT FIRST 1 sup.`xkey`
						 FROM `{$X}liberty_xref` sup
						 WHERE sup.`content_id` = lc.`content_id` AND sup.`item` = '#SUP'
						 ORDER BY sup.`xorder`) AS part_number,
						(SELECT FIRST 1 CAST(pk.`xkey` AS DOUBLE PRECISION)
						 FROM `{$X}liberty_xref` pk
						 WHERE pk.`content_id` = lc.`content_id` AND pk.`item` = 'PCK') AS pack_size,
						SUM( CASE WHEN EXISTS (
							SELECT 1 FROM `{$X}liberty_xref` r
							WHERE r.`content_id` = x.`content_id` AND r.`item` IN ('TRANS','ORDER')
						) THEN CAST(x.`xkey` AS DOUBLE PRECISION)
						  ELSE -CAST(x.`xkey` AS DOUBLE PRECISION) END ) AS stock_level
				FROM `{$X}liberty_content` lc
					INNER JOIN `{$X}liberty_xref` x ON x.`xref` = lc.`content_id`
						AND x.`item` IN ('SGL','PCK','SHT','VOL')
						AND x.`xkey` SIMILAR TO '[0-9]+(\.[0-9]+)?'
					INNER JOIN `{$X}liberty_content` mc ON mc.`content_id` = x.`content_id`
						AND mc.`content_type_guid` = 'stockmovement'
				WHERE lc.`content_type_guid` = 'stockcomponent'
				$whereSql
				GROUP BY lc.`content_id`, lc.`title`, x.`item`
				ORDER BY lc.`title`, x.`item`";
	}

	$rows      = $gBitDb->query( $query, $bindVars );
	$stockList = [];
	foreach( $rows as $row ) {
		$cid   = $row['content_id'];
		$level = $row['stock_level'] !== null ? (float)$row['stock_level'] : 0.0;
		if( !isset( $stockList[$cid] ) ) {
			$stockList[$cid] = [
				'content_id'  => $cid,
				'title'       => $row['title'],
				'part_number' => $row['part_number'],
			];
		}
		$stockList[$cid]['stock'][$row['qty_type']] = [
			'level'    => $level,
			'bom_qty'  => $row['bom_qty'] !== null ? (float)$row['bom_qty'] : null,
		];
	}

	foreach( $stockList as $comp ) {
		foreach( $comp['stock'] as $qtype => $row ) {
			$shortage = $assemblyContentId && $row['bom_qty'] !== null
				? $row['bom_qty'] * $kitCount - $row['level']
				: -$row['level'];
			if( $shortage <= 0 ) continue;
			$lines[] = [
				'component_id' => $comp['content_id'],
				'title'        => $comp['title'],
				'part_number'  => $comp['part_number'],
				'qty_type'     => $qtype,
				'qty'          => $shortage,
			];
		}
	}

	$supplierVal    = '';
	$supplierCidVal = 0;
	$refKeyVal      = '';
	$orderedDateVal = date( 'd/m/Y' );
}

$gBitSmarty->assign( 'lines',            $lines );
$gBitSmarty->assign( 'supplierVal',      $supplierVal );
$gBitSmarty->assign( 'supplierCidVal',   $supplierCidVal );
$gBitSmarty->assign( 'refKeyVal',        $refKeyVal );
$gBitSmarty->assign( 'orderedDateVal',   $orderedDateVal );
$gBitSmarty->assign( 'contactLookupUrl', CONTACT_PKG_URL.'includes/lookup_contact.php' );
$gBitSmarty->assign( 'errors',           $errors );

$gBitSystem->display( 'bitpackage:stock/add_order.tpl', KernelTools::tra( 'Create Order' ), [ 'display_mode' => 'edit' ] );

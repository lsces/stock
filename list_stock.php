<?php
/**
 * @package stock
 * @subpackage functions
 */

namespace Bitweaver\Stock;

require_once '../kernel/includes/setup_inc.php';

global $gBitSystem, $gBitSmarty, $gBitDb;

$gBitSystem->verifyPermission( 'p_stock_view' );

$find              = trim( $_REQUEST['find'] ?? '' );
$hideZero          = empty( $_REQUEST['show_zero'] );
$assemblyContentId = isset( $_REQUEST['assembly_content_id'] ) && is_numeric( $_REQUEST['assembly_content_id'] )
                     ? (int)$_REQUEST['assembly_content_id'] : null;
$kitCount          = isset( $_REQUEST['kit_count'] ) && is_numeric( $_REQUEST['kit_count'] ) && (float)$_REQUEST['kit_count'] > 0
                     ? (float)$_REQUEST['kit_count'] : 1;

$X = BIT_DB_PREFIX;

$joinSql  = '';
$whereSql = '';
$bindVars = [];

if( $assemblyContentId ) {
	// Filter to components on this assembly's BOM via liberty_xref
	$joinSql .= " INNER JOIN `{$X}liberty_xref` bom ON bom.`content_id` = ?
					AND bom.`item` IN ('SGL','PCK','SHT','VOL')
					AND bom.`xref` = lc.`content_id`";
	$bindVars[] = $assemblyContentId;
}

if( $find !== '' ) {
	$whereSql .= " AND UPPER(lc.`title`) LIKE ?";
	$bindVars[] = '%'.strtoupper( $find ).'%';
}

$bomQtySelect  = $assemblyContentId
	? ", MAX(CAST(bom.`xkey` AS DOUBLE PRECISION)) AS bom_qty"
	: ", CAST(NULL AS DOUBLE PRECISION) AS bom_qty";

$bomQtyGroup   = $assemblyContentId ? "" : "";

// Stock level per component per qty type, signed by movement direction
$query = "SELECT lc.`content_id`, lc.`title`, lc.`data`,
				x.`item` AS qty_type
				$bomQtySelect,
				(SELECT FIRST 1 sup.`xkey`
				 FROM `{$X}liberty_xref` sup
				 WHERE sup.`content_id` = lc.`content_id` AND sup.`item` = '#SUP'
				 ORDER BY sup.`xorder`) AS part_number,
				SUM( CASE WHEN EXISTS (
					SELECT 1 FROM `{$X}liberty_xref` r
					WHERE r.`content_id` = x.`content_id` AND r.`item` IN ('TRANS','ORDER')
				) THEN CAST(x.`xkey` AS DOUBLE PRECISION)
				  ELSE -CAST(x.`xkey` AS DOUBLE PRECISION) END ) AS stock_level
		FROM `{$X}liberty_content` lc
			$joinSql
			INNER JOIN `{$X}liberty_xref` x ON x.`xref` = lc.`content_id`
				AND x.`item` IN ('SGL','PCK','SHT','VOL')
			INNER JOIN `{$X}liberty_content` mc ON mc.`content_id` = x.`content_id`
				AND mc.`content_type_guid` = 'stockmovement'
		WHERE lc.`content_type_guid` = 'stockcomponent'
		$whereSql
		GROUP BY lc.`content_id`, lc.`title`, lc.`data`, x.`item`
		ORDER BY lc.`title`, x.`item`";

$rows = $gBitDb->query( $query, $bindVars );

// Group by component for display
$stockList = [];
foreach( $rows as $row ) {
	$cid = $row['content_id'];
	if( !isset( $stockList[$cid] ) ) {
		$stockList[$cid] = [
			'content_id'  => $cid,
			'title'       => $row['title'],
			'data'        => $row['data'],
			'part_number' => $row['part_number'],
			'display_url' => STOCK_PKG_URL.'view_component.php?content_id='.$cid,
			'stock'       => [],
		];
	}
	$level = (float)$row['stock_level'];
	if( !$hideZero || $level != 0 ) {
		$stockList[$cid]['stock'][$row['qty_type']] = [
			'level'   => $level,
			'bom_qty' => $row['bom_qty'] !== null ? (float)$row['bom_qty'] : null,
		];
	}
}

// Drop components with no stock rows after zero filter
if( $hideZero ) {
	$stockList = array_filter( $stockList, fn($c) => !empty( $c['stock'] ) );
}

// Assembly selector list
$assembly = new StockAssembly();
$listHash = [ 'show_empty' => true, 'sort_mode' => 'title_asc', 'max_records' => 500 ];
$assemblyList = $assembly->getList( $listHash );

// Load selected assembly title for display
$assemblyTitle = '';
if( $assemblyContentId && isset( $assemblyList[$assemblyContentId] ) ) {
	$assemblyTitle = $assemblyList[$assemblyContentId]['title'];
}

$gBitSmarty->assign( 'stockList',          $stockList );
$gBitSmarty->assign( 'assemblyList',        $assemblyList );
$gBitSmarty->assign( 'assemblyContentId',   $assemblyContentId );
$gBitSmarty->assign( 'assemblyTitle',       $assemblyTitle );
$gBitSmarty->assign( 'find',               $find );
$gBitSmarty->assign( 'showZero',           !$hideZero );
$gBitSmarty->assign( 'showBom',            (bool)$assemblyContentId );
$gBitSmarty->assign( 'kitCount',           $kitCount );

$gBitSystem->display( 'bitpackage:stock/list_stock.tpl', 'Stock Levels', [ 'display_mode' => 'list' ] );

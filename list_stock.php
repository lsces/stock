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
$showShortages     = !empty( $_REQUEST['shortages'] );
$assemblyContentId = isset( $_REQUEST['assembly_content_id'] ) && is_numeric( $_REQUEST['assembly_content_id'] )
                     ? (int)$_REQUEST['assembly_content_id'] : null;
$kitCount          = isset( $_REQUEST['kit_count'] ) && is_numeric( $_REQUEST['kit_count'] ) && (float)$_REQUEST['kit_count'] > 0
                     ? (float)$_REQUEST['kit_count'] : 1;
$filterUserId      = isset( $_REQUEST['user_id'] ) && is_numeric( $_REQUEST['user_id'] )
                     ? (int)$_REQUEST['user_id'] : null;
$filterUserName    = '';
if( $filterUserId ) {
	$uRow = $gBitDb->getRow(
		"SELECT `login`, `real_name` FROM `".BIT_DB_PREFIX."users_users` WHERE `user_id` = ?",
		[ $filterUserId ]
	);
	$filterUserName = $uRow['real_name'] ?: $uRow['login'] ?: '';
}

$X = BIT_DB_PREFIX;

$bindVars = [];

if( $assemblyContentId ) {
	// BOM view: start from BOM items so components with no movements still appear.
	// Bind var order must match ? positions in the SQL: user_id (in subquery SELECT) first,
	// then assemblyContentId (in FROM JOIN), then find (in WHERE).
	$userSubSql = '';
	if( $filterUserId ) {
		$userSubSql = " AND mc.`user_id` = ?";
		$bindVars[] = $filterUserId;
	}
	$findSql = $find !== '' ? " AND UPPER(lc.`title`) LIKE ?" : '';

	$query = "SELECT lc.`content_id`, lc.`title`, lc.`data`,
					bom.`item` AS qty_type,
					CASE WHEN bom.`xkey` SIMILAR TO '[0-9]+(\.[0-9]+)?' THEN CAST(bom.`xkey` AS DOUBLE PRECISION) ELSE NULL END AS bom_qty,
					bom.`xorder` AS bom_xorder,
					(SELECT FIRST 1 sup.`xkey`
					 FROM `{$X}liberty_xref` sup
					 WHERE sup.`content_id` = lc.`content_id` AND sup.`item` = '#SUP'
					 ORDER BY sup.`xorder`) AS part_number,
					(SELECT FIRST 1 CAST(pk.`xkey` AS DOUBLE PRECISION)
					 FROM `{$X}liberty_xref` pk
					 WHERE pk.`content_id` = lc.`content_id` AND pk.`item` = 'PRT'
					   AND pk.`xkey` SIMILAR TO '[0-9]+(\.[0-9]+)?') AS part_size,
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
					   AND mx.`xkey` SIMILAR TO '[0-9]+(\.[0-9]+)?'
					   $userSubSql) AS stock_level
			FROM `{$X}liberty_content` lc
				INNER JOIN `{$X}liberty_xref` bom ON bom.`content_id` = ?
					AND bom.`item` IN ('SGL','PRT','SHT','VOL')
					AND bom.`xref` = lc.`content_id`
			WHERE lc.`content_type_guid` = 'stockcomponent'
			$findSql
			ORDER BY bom.`xorder`";
	$bindVars[] = $assemblyContentId;
	if( $find !== '' ) $bindVars[] = '%'.strtoupper( $find ).'%';
} else {
	// General list: only components with movement history
	$whereSql = '';
	if( $filterUserId ) {
		$whereSql .= " AND mc.`user_id` = ?";
		$bindVars[] = $filterUserId;
	}
	if( $find !== '' ) {
		$whereSql .= " AND UPPER(lc.`title`) LIKE ?";
		$bindVars[] = '%'.strtoupper( $find ).'%';
	}

	$query = "SELECT lc.`content_id`, lc.`title`, lc.`data`,
					x.`item` AS qty_type,
					CAST(NULL AS DOUBLE PRECISION) AS bom_qty,
					CAST(NULL AS INTEGER) AS bom_xorder,
					(SELECT FIRST 1 sup.`xkey`
					 FROM `{$X}liberty_xref` sup
					 WHERE sup.`content_id` = lc.`content_id` AND sup.`item` = '#SUP'
					 ORDER BY sup.`xorder`) AS part_number,
					(SELECT FIRST 1 CAST(pk.`xkey` AS DOUBLE PRECISION)
					 FROM `{$X}liberty_xref` pk
					 WHERE pk.`content_id` = lc.`content_id` AND pk.`item` = 'PRT'
					   AND pk.`xkey` SIMILAR TO '[0-9]+(\.[0-9]+)?') AS part_size,
					SUM( CASE WHEN EXISTS (
						SELECT 1 FROM `{$X}liberty_xref` r
						WHERE r.`content_id` = x.`content_id` AND r.`item` IN ('TRANS','ORDER')
					) THEN CAST(x.`xkey` AS DOUBLE PRECISION)
					  ELSE -CAST(x.`xkey` AS DOUBLE PRECISION) END ) AS stock_level
			FROM `{$X}liberty_content` lc
				INNER JOIN `{$X}liberty_xref` x ON x.`xref` = lc.`content_id`
					AND x.`item` IN ('SGL','PRT','SHT','VOL')
					AND x.`xkey` SIMILAR TO '[0-9]+(\.[0-9]+)?'
				INNER JOIN `{$X}liberty_content` mc ON mc.`content_id` = x.`content_id`
					AND mc.`content_type_guid` = 'stockmovement'
			WHERE lc.`content_type_guid` = 'stockcomponent'
			$whereSql
			GROUP BY lc.`content_id`, lc.`title`, lc.`data`, x.`item`
			ORDER BY lc.`title`, x.`item`";
}

$rows = $gBitDb->query( $query, $bindVars );

// Group by component for display
$stockList = [];
foreach( $rows as $row ) {
	$cid   = $row['content_id'];
	$level = $row['stock_level'] !== null ? (float)$row['stock_level'] : 0.0;

	if( !isset( $stockList[$cid] ) ) {
		$bomXorder = $row['bom_xorder'] !== null ? (int)$row['bom_xorder'] : null;
		$stockList[$cid] = [
			'content_id'  => $cid,
			'title'       => $row['title'],
			'data'        => $row['data'],
			'part_number' => $row['part_number'],
			'display_url' => STOCK_PKG_URL.'view_component.php?content_id='.$cid,
			'bom_group'   => $bomXorder !== null ? (int)floor( $bomXorder / 1000 ) : null,
			'stock'       => [],
		];
	}

	$stockList[$cid]['stock'][$row['qty_type']] = [
		'level'     => $level,
		'bom_qty'   => $row['bom_qty'] !== null ? (float)$row['bom_qty'] : null,
		'part_size' => $row['part_size'] !== null ? (float)$row['part_size'] : null,
	];
}

if( $showShortages ) {
	$stockList = array_filter( $stockList, function( $comp ) use ( $kitCount, $assemblyContentId ) {
		foreach( $comp['stock'] as $row ) {
			$short = $assemblyContentId && $row['bom_qty'] !== null
				? ( $row['level'] - $row['bom_qty'] * $kitCount ) < 0
				: $row['level'] < 0;
			if( $short ) return true;
		}
		return false;
	} );
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

// KLID map for assembly autocomplete
$asmIds = array_keys( $assemblyList );
$asmKlidMap = [];
if( $asmIds ) {
	$klidRows = $gBitDb->getAll(
		"SELECT x.`content_id`, x.`xkey` FROM `".BIT_DB_PREFIX."liberty_xref` x
		 WHERE x.`item` = 'KLID' AND x.`content_id` IN (".implode( ',', array_fill( 0, count( $asmIds ), '?' ) ).")",
		$asmIds
	);
	foreach( $klidRows as $r ) { $asmKlidMap[$r['content_id']] = $r['xkey']; }
}
$assemblyListJson = json_encode( array_values( array_map(
	fn( $i ) => [ 'id' => (int)$i['content_id'], 'text' => $i['title'], 'klid' => $asmKlidMap[$i['content_id']] ?? '' ],
	$assemblyList
) ) );

if( $showShortages && isset( $_REQUEST['format'] ) && $_REQUEST['format'] === 'csv' ) {
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="shortages.csv"' );
	$out = fopen( 'php://output', 'w' );
	fputcsv( $out, [ 'Part No', 'Qty' ], ',', '"', '' );
	foreach( $stockList as $comp ) {
		if( empty( $comp['part_number'] ) ) continue;
		foreach( $comp['stock'] as $qtype => $row ) {
			$qty = $qtype === 'PRT' && $row['part_size'] > 0
				? abs( $row['level'] ) / $row['part_size']
				: abs( $row['level'] );
			fputcsv( $out, [ $comp['part_number'], $qty ], ',', '"', '' );
		}
	}
	fclose( $out );
	exit;
}

$gBitSmarty->assign( 'stockList',          $stockList );
$gBitSmarty->assign( 'assemblyListJson',    $assemblyListJson );
$gBitSmarty->assign( 'assemblyContentId',   $assemblyContentId );
$gBitSmarty->assign( 'assemblyTitle',       $assemblyTitle );
$gBitSmarty->assign( 'find',               $find );
$gBitSmarty->assign( 'showBom',            (bool)$assemblyContentId );
$gBitSmarty->assign( 'showShortages',      $showShortages );
$gBitSmarty->assign( 'kitCount',           $kitCount );
$gBitSmarty->assign( 'filterUserId',       $filterUserId );
$gBitSmarty->assign( 'filterUserName',     $filterUserName );

$gBitSystem->display( 'bitpackage:stock/list_stock.tpl', 'Stock Levels', [ 'display_mode' => 'list' ] );

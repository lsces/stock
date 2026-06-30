<?php
/**
 * @package stock
 */

namespace Bitweaver\Stock;

require_once '../kernel/includes/setup_inc.php';

global $gBitSystem, $gBitSmarty, $gBitDb;

$gBitSystem->verifyPermission( 'p_stock_view' );

$partContentId = isset( $_REQUEST['part_content_id'] ) && is_numeric( $_REQUEST['part_content_id'] )
	? (int)$_REQUEST['part_content_id'] : null;

$partTitle = '';
$partType  = '';
$partSize  = null;
if( $partContentId ) {
	$pRow = $gBitDb->getRow(
		"SELECT `title`, `content_type_guid` FROM `".BIT_DB_PREFIX."liberty_content` WHERE `content_id` = ?",
		[ $partContentId ]
	);
	$partTitle = $pRow['title'] ?? '';
	$partType  = match( $pRow['content_type_guid'] ?? '' ) {
		'stockassembly'  => 'assembly',
		'stockcomponent' => 'component',
		default          => '',
	};
	if( $partType === 'component' ) {
		$ps = $gBitDb->getOne(
			"SELECT CAST(x.`xkey` AS DOUBLE PRECISION) FROM `".BIT_DB_PREFIX."liberty_xref` x
			 WHERE x.`content_id` = ? AND x.`item` = 'PRT'",
			[ $partContentId ]
		);
		$partSize = $ps ? (float)$ps : null;
	}
}

$listHash = $_REQUEST;
if( $partContentId && $partType === 'assembly' ) {
	$listHash['assembly_content_id'] = $partContentId;
} elseif( $partContentId && $partType === 'component' ) {
	$listHash['component_content_id'] = $partContentId;
}
unset( $listHash['part_content_id'] );

$movement     = new StockMovement();
$movementList = $movement->getList( $listHash );

$filterUserId   = isset( $_REQUEST['user_id'] ) && is_numeric( $_REQUEST['user_id'] ) ? (int)$_REQUEST['user_id'] : null;
$filterUserName = '';
if( $filterUserId ) {
	$uRow = $gBitDb->getRow(
		"SELECT `login`, `real_name` FROM `".BIT_DB_PREFIX."users_users` WHERE `user_id` = ?",
		[ $filterUserId ]
	);
	$filterUserName = $uRow['real_name'] ?: $uRow['login'] ?: '';
}

$listHash['listInfo']['parameters'] = array_filter( [
	'part_content_id' => $partContentId ?: '',
	'ref_type'        => $_REQUEST['ref_type'] ?? '',
	'user_id'         => $filterUserId ?: '',
] );
$gBitSmarty->assign( 'listInfo',       $listHash['listInfo'] );
$gBitSmarty->assign( 'movementList',   $movementList );
$gBitSmarty->assign( 'filterType',     $_REQUEST['ref_type'] ?? '' );
$gBitSmarty->assign( 'partContentId',  $partContentId );
$gBitSmarty->assign( 'partTitle',      $partTitle );
$gBitSmarty->assign( 'partType',       $partType );
$gBitSmarty->assign( 'partSize',       $partSize );
$gBitSmarty->assign( 'filterUserId',   $filterUserId );
$gBitSmarty->assign( 'filterUserName', $filterUserName );

$partQtyNet = null;
if( $partContentId ) {
	$partQtyNet = $gBitDb->getOne(
		"SELECT SUM( CASE WHEN r.`item` IN ('REQN','PBLD')
		                  THEN -CAST(x.`xkey` AS DOUBLE PRECISION)
		                  ELSE  CAST(x.`xkey` AS DOUBLE PRECISION) END )
		 FROM `".BIT_DB_PREFIX."liberty_xref` x
		 JOIN `".BIT_DB_PREFIX."liberty_xref` r
		      ON r.`content_id` = x.`content_id` AND r.`item` IN ('REQN','PBLD','TRANS','ORDER')
		 WHERE x.`xref` = ? AND x.`item` IN ('SGL','PRT','SHT','VOL')",
		[ $partContentId ]
	);
	$partQtyNet = $partQtyNet !== null ? (float)$partQtyNet : null;
}
$gBitSmarty->assign( 'partQtyNet', $partQtyNet );

$gBitSystem->display( 'bitpackage:stock/list_movements.tpl', 'Movements', [ 'display_mode' => 'list' ] );

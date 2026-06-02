<?php
/**
 * @package stock
 */

namespace Bitweaver\Stock;

require_once '../kernel/includes/setup_inc.php';

global $gBitSystem, $gBitSmarty, $gBitDb;

$gBitSystem->verifyPermission( 'p_stock_view' );

$componentContentId = isset( $_REQUEST['component_content_id'] ) && is_numeric( $_REQUEST['component_content_id'] )
	? (int)$_REQUEST['component_content_id'] : null;

$movement     = new StockMovement();
$listHash     = $_REQUEST;
$movementList = $movement->getList( $listHash );

$componentTitle = '';
if( $componentContentId ) {
	$componentTitle = $gBitDb->getOne(
		"SELECT `title` FROM `".BIT_DB_PREFIX."liberty_content` WHERE `content_id` = ?",
		[ $componentContentId ]
	) ?: '';
}

$gBitSmarty->assign( 'listInfo',           $listHash['listInfo'] );
$gBitSmarty->assign( 'movementList',       $movementList );
$gBitSmarty->assign( 'filterType',         $_REQUEST['ref_type'] ?? '' );
$gBitSmarty->assign( 'assemblyContentId',  isset( $_REQUEST['assembly_content_id'] ) && is_numeric( $_REQUEST['assembly_content_id'] ) ? (int)$_REQUEST['assembly_content_id'] : null );
$gBitSmarty->assign( 'componentContentId', $componentContentId );
$gBitSmarty->assign( 'componentTitle',     $componentTitle );

$gBitSystem->display( 'bitpackage:stock/list_movements.tpl', 'Movements', [ 'display_mode' => 'list' ] );

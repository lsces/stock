<?php
/**
 * @package stock
 */

namespace Bitweaver\Stock;

require_once '../kernel/includes/setup_inc.php';

global $gBitSystem, $gBitSmarty;

$gBitSystem->verifyPermission( 'p_stock_view' );

$movement     = new StockMovement();
$listHash     = $_REQUEST;
$movementList = $movement->getList( $listHash );

$gBitSmarty->assign( 'listInfo',           $listHash['listInfo'] );
$gBitSmarty->assign( 'movementList',       $movementList );
$gBitSmarty->assign( 'filterType',         $_REQUEST['ref_type'] ?? '' );
$gBitSmarty->assign( 'assemblyContentId',  isset( $_REQUEST['assembly_content_id'] ) && is_numeric( $_REQUEST['assembly_content_id'] ) ? (int)$_REQUEST['assembly_content_id'] : null );

$gBitSystem->display( 'bitpackage:stock/list_movements.tpl', 'Movements', [ 'display_mode' => 'list' ] );

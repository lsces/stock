<?php
/**
 * @package stock
 * @subpackage functions
 */

namespace Bitweaver\Stock;

require_once '../kernel/includes/setup_inc.php';

global $gBitSystem, $gBitSmarty;

$gBitSystem->verifyPermission( 'p_stock_view' );

$component = new StockComponent();

if( !empty( $_REQUEST['user_id'] ) && is_numeric( $_REQUEST['user_id'] ) ) {
	$gBitSmarty->assign( 'gQueryUserId', $_REQUEST['user_id'] );
}

$componentList = $component->getList( $_REQUEST );
$component->invokeServices( 'content_list_function', $_REQUEST );
$gBitSmarty->assign( 'listInfo', $_REQUEST['listInfo'] );
$gBitSmarty->assign( 'componentList', $componentList );

$gBitSmarty->assign( 'gDefaultCenter', 'bitpackage:stock/list_components.tpl' );
$gBitSystem->display( 'bitpackage:kernel/dynamic.tpl', 'List Components', [ 'display_mode' => 'list' ] );

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

if( !empty( $_REQUEST['find'] ) ) {
	$_REQUEST['search'] = $_REQUEST['find'];
}

// Default on unless the filter form has been submitted (sentinel field present)
if( !isset( $_REQUEST['hide_kitlocker'] ) && empty( $_REQUEST['filter_submitted'] ) ) {
	$_REQUEST['hide_kitlocker'] = 1;
}

$componentList = $component->getList( $_REQUEST );
$component->invokeServices( 'content_list_function', $_REQUEST );
$_REQUEST['listInfo']['parameters'] = array_filter( [
	'user_id'          => !empty( $_REQUEST['user_id'] ) ? (int)$_REQUEST['user_id'] : '',
	'hide_kitlocker'   => $_REQUEST['hide_kitlocker']   ?? '',
	'filter_submitted' => $_REQUEST['filter_submitted'] ?? '',
] );
$gBitSmarty->assign( 'listInfo', $_REQUEST['listInfo'] );
$gBitSmarty->assign( 'componentList', $componentList );
$gBitSmarty->assign( 'hideKitlocker', (bool)$_REQUEST['hide_kitlocker'] );

$gBitSmarty->assign( 'gDefaultCenter', 'bitpackage:stock/list_components.tpl' );
$gBitSystem->display( 'bitpackage:kernel/dynamic.tpl', 'List Components', [ 'display_mode' => 'list' ] );

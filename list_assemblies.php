<?php
/**
 * @package stock
 * @subpackage functions
 */

namespace Bitweaver\Stock;

require_once '../kernel/includes/setup_inc.php';

global $gBitSystem, $gBitSmarty, $gStockAssembly;

$gStockAssembly = new StockAssembly();

$_REQUEST['root_only']   = true;
$_REQUEST['show_empty']  = true;

if (!empty($_REQUEST['user_id']) && is_numeric($_REQUEST['user_id'])) {
	if( $_REQUEST['user_id'] == $gBitUser->mUserId ) {
		$_REQUEST['show_empty'] = true;
	}
	$gBitSmarty->assign('gQueryUserId', $_REQUEST['user_id']);
	$template = 'user_galleries.tpl';
} else {
	$template = 'list_assemblies_simple.tpl';
}

$galleryList = $gStockAssembly->getList( $_REQUEST );
$gStockAssembly->invokeServices( 'content_list_function', $_REQUEST );
$gBitSmarty->assign( 'listInfo', $_REQUEST['listInfo'] );
$gBitSmarty->assign( 'galleryList', $galleryList );

$gDefaultCenter = "bitpackage:stock/$template";
$gBitSmarty->assign( 'gDefaultCenter', $gDefaultCenter );
$gBitSystem->display( 'bitpackage:kernel/dynamic.tpl', 'List Assemblies', [ 'display_mode' => 'list' ] );

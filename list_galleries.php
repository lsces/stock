<?php
/**
 * @version $Header$
 * @package stock
 * @subpackage functions
 */

/**
 * required setup
 */
namespace Bitweaver\Stock;

require_once '../kernel/includes/setup_inc.php';

global $gBitSystem, $gBitSmarty, $gStockAssembly;

//$gBitSystem->verifyPermission( 'p_stock_list_galleries' );

require_once LIBERTY_PKG_PATH.'plugins/mime.image.php';
$gStockAssembly = new StockAssembly();

/* Get a list of galleries which matches the input parameters (default is to list every gallery in the system) */
$_REQUEST['root_only'] = true;
/* Process the input parameters this page accepts */
if (!empty($_REQUEST['user_id']) && is_numeric($_REQUEST['user_id'])) {
	if( $_REQUEST['user_id'] == $gBitUser->mUserId ) {
		$_REQUEST['show_empty'] = true;
	}
	$gBitSmarty->assign('gQueryUserId', $_REQUEST['user_id']);
	$template = 'user_galleries.tpl';
} else {
	$template = 'list_galleries2.tpl';
}

$_REQUEST['thumbnail_size'] = $gBitSystem->getConfig( 'stock_list_thumbnail_size', 'small' );

$galleryList = $gStockAssembly->getList( $_REQUEST );
$gStockAssembly->invokeServices( 'content_list_function', $_REQUEST );
// Pagination Data
$gBitSmarty->assign( 'listInfo', $_REQUEST['listInfo'] );
$gBitSmarty->assign( 'galleryList', $galleryList );

// Display the template
$gDefaultCenter = "bitpackage:stock/$template";
$gBitSmarty->assign( 'gDefaultCenter', $gDefaultCenter );
$gBitSystem->display( 'bitpackage:kernel/dynamic.tpl', 'List Galleries' , [ 'display_mode' => 'list' ] );

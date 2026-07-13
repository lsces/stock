<?php
/**
 * @package stock
 * @subpackage functions
 */

namespace Bitweaver\Stock;

require_once '../kernel/includes/setup_inc.php';

global $gBitSystem, $gBitSmarty, $gStockAssembly;

$gStockAssembly = new StockAssembly();

if( !empty( $_REQUEST['gallery_id'] ) && is_numeric( $_REQUEST['gallery_id'] ) ) {
	$parentAssembly = new StockAssembly( (int)$_REQUEST['gallery_id'] );
	$parentAssembly->load();
	if( $parentAssembly->isValid() ) {
		$_REQUEST['parent_content_id'] = $parentAssembly->mContentId;
		$_REQUEST['show_empty'] = true;
		$gBitSmarty->assign( 'parentAssembly', $parentAssembly );
	}
} else {
	$_REQUEST['show_empty']    = true;
	$_REQUEST['no_thumbnails'] = true;
}

if (!empty($_REQUEST['user_id']) && is_numeric($_REQUEST['user_id'])) {
	if( $_REQUEST['user_id'] == $gBitUser->mUserId ) {
		$_REQUEST['show_empty'] = true;
	}
	$gBitSmarty->assign('gQueryUserId', $_REQUEST['user_id']);
	$template = 'user_galleries.tpl';
} else {
	$template = 'list_assemblies.tpl';
}

if( empty( $_REQUEST['sort_mode'] ) ) {
	$_REQUEST['sort_mode'] = 'title_asc';
}

$galleryList = $gStockAssembly->getList( $_REQUEST );
$gStockAssembly->invokeServices( 'content_list_function', $_REQUEST );

if( $template === 'user_galleries.tpl' && $galleryList ) {
	$gBitSmarty->assign( 'shortageComponents', $gStockAssembly->getShortageComponents( array_keys( $galleryList ) ) );
}
$_REQUEST['listInfo']['parameters'] = array_filter( [
	'user_id'    => !empty( $_REQUEST['user_id'] )    ? (int)$_REQUEST['user_id']    : '',
	'gallery_id' => !empty( $_REQUEST['gallery_id'] ) ? (int)$_REQUEST['gallery_id'] : '',
] );
$gBitSmarty->assign( 'listInfo', $_REQUEST['listInfo'] );

$gBitSmarty->assign( 'galleryList', $galleryList );

$gDefaultCenter = "bitpackage:stock/$template";
$gBitSmarty->assign( 'gDefaultCenter', $gDefaultCenter );
$gBitSystem->display( 'bitpackage:kernel/dynamic.tpl', 'List Assemblies', [ 'display_mode' => 'list' ] );

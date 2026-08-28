<?php
/**
 * @package stock
 * @subpackage functions
 */

global $gContent, $gGallery;
use \Bitweaver\Stock\StockComponent;
use \Bitweaver\Stock\StockAssembly;

$gContent = new StockComponent(
	!empty( $_REQUEST['content_id'] ) ? (int)$_REQUEST['content_id'] : null
);
$gContent->load();

if( !empty($_REQUEST['assembly_content_id']) && is_numeric($_REQUEST['assembly_content_id']) ) {
	$gGallery = StockAssembly::lookup( [ 'content_id' => (int)$_REQUEST['assembly_content_id'] ] );
	$gBitSmarty->assign( 'gGallery', $gGallery );
	$gBitSmarty->assign( 'assemblyContentId', $_REQUEST['assembly_content_id'] );
}

$gContent->verifyViewPermission();

$gBitSmarty->assign( 'gContent', $gContent );

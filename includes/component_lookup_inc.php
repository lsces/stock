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

if( !empty( $_REQUEST['gallery_path'] ) ) {
	$_REQUEST['gallery_path'] = rtrim( $_REQUEST['gallery_path'], '/' );
	$gContent->setGalleryPath( $_REQUEST['gallery_path'] );
	$tail = strrpos( $_REQUEST['gallery_path'], '/' );
	$_REQUEST['assembly_content_id'] = substr( $_REQUEST['gallery_path'], $tail + 1 );
}
if( empty( $_REQUEST['assembly_content_id'] ) ) {
	if( $parents = $gContent->getParentAssemblies() ) {
		$gal = current( $parents );
		$gContent->setGalleryPath( '/'.$gal['content_id'] );
		$_REQUEST['assembly_content_id'] = $gal['content_id'];
	}
}
if( !empty($_REQUEST['assembly_content_id']) && is_numeric($_REQUEST['assembly_content_id']) ) {
	$gGallery = StockAssembly::lookup( [ 'content_id' => (int)$_REQUEST['assembly_content_id'] ] );
	$gBitSmarty->assign( 'gGallery', $gGallery );
	$gBitSmarty->assign( 'assemblyContentId', $_REQUEST['assembly_content_id'] );
}

$gContent->verifyViewPermission();

$gBitSmarty->assign( 'gContent', $gContent );

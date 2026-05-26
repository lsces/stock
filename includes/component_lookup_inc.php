<?php
/**
 * @version $Header$
 * @package stock
 * @subpackage functions
 */

global $gContent, $gGallery;
use \Bitweaver\Stock\StockComponent;
use \Bitweaver\Stock\StockAssembly;
$gContent = new StockComponent(
	!empty( $_REQUEST['component_id'] ) ? (int)$_REQUEST['component_id'] : null,
	!empty( $_REQUEST['content_id'] )   ? (int)$_REQUEST['content_id']   : null
);
$gContent->load();

if( !empty( $_REQUEST['gallery_path'] ) ) {
	$_REQUEST['gallery_path'] = rtrim( $_REQUEST['gallery_path'], '/' );
	$gContent->setGalleryPath( $_REQUEST['gallery_path'] );
	$matches = [];
	$tail = strrpos( $_REQUEST['gallery_path'], '/' );
	$_REQUEST['assembly_id'] = substr( $_REQUEST['gallery_path'], $tail + 1 );
}
if( empty( $_REQUEST['assembly_id'] ) ) {
	if( $parents = $gContent->getParentAssemblies() ) {
		$gal = current( $parents );
		$gContent->setGalleryPath( '/'.$gal['assembly_id'] );
		$_REQUEST['assembly_id'] = $gal['assembly_id'];
	}
}
// the image is considered the primary content, however the gallery is useful
if( !empty($_REQUEST['assembly_id']) && is_numeric($_REQUEST['assembly_id']) ) {
	$gGallery = StockAssembly::lookup( $_REQUEST );
$gBitSmarty->assign('gGallery', $gGallery);
	$gBitSmarty->assign('assemblyId', $_REQUEST['assembly_id']);
}

// This user does not own this gallery and they have not been granted the permission to edit this gallery
$gContent->verifyViewPermission();

$gBitSmarty->assign('gContent', $gContent);
$gBitSmarty->assign('imageId', $gContent->mComponentId );

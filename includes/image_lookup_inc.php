<?php
/**
 * @version $Header$
 * @package stock
 * @subpackage functions
 */

global $gContent, $gGallery;
use \Bitweaver\Stock\StockComponent;
use \Bitweaver\Stock\StockAssembly;
;
if( $gContent = StockComponent::lookup( $_REQUEST ) ) {
	// nothing to do. ::lookup will do a full load
} else {
	$gContent = new StockComponent();
	$imageId = null;
}

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
	if( is_object( $gGallery ) && $gGallery->isValid() ) {
		$gGallery->loadCurrentComponent( $gContent->mComponentId );
	}
	$gBitSmarty->assign('gGallery', $gGallery);
	$gBitSmarty->assign('assemblyId', $_REQUEST['assembly_id']);
}

// This user does not own this gallery and they have not been granted the permission to edit this gallery
$gContent->verifyViewPermission();

$gBitSmarty->assign('gContent', $gContent);
$gBitSmarty->assign('imageId', $gContent->mComponentId );

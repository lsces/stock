<?php
/**
 * @package stock
 * @subpackage functions
 */

global $gContent;
use \Bitweaver\Stock\StockAssembly;

$lookup = [];

if( !$gContent = StockAssembly::lookup( $_REQUEST ) ) {
	$gContent = new StockAssembly();
	$galleryId = null;
}

if( !empty( $_REQUEST['gallery_path'] ) ) {
	$gContent->setGalleryPath( $_REQUEST['gallery_path'] );
}

$gBitSmarty->assign('gContent', $gContent);
$gBitSmarty->assign('galleryId', $gContent->mGalleryId);


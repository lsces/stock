<?php
/**
 * @package stock
 * @subpackage functions
 */

global $gContent;
use \Bitweaver\Stock\StockAssembly;

$lookup = [];

$gContent = new StockAssembly(
	!empty( $_REQUEST['assembly_id'] ) ? (int)$_REQUEST['assembly_id'] : null,
	!empty( $_REQUEST['content_id'] )  ? (int)$_REQUEST['content_id']  : null
);
$gContent->load();

if( !empty( $_REQUEST['gallery_path'] ) ) {
	$gContent->setGalleryPath( $_REQUEST['gallery_path'] );
}

$gBitSmarty->assign('gContent', $gContent);
$gBitSmarty->assign('assemblyId', $gContent->mAssemblyId);


<?php
/**
 * @package stock
 * @subpackage functions
 */

global $gContent;
use \Bitweaver\Stock\StockAssembly;

$gContent = new StockAssembly(
	!empty( $_REQUEST['content_id'] ) ? (int)$_REQUEST['content_id'] : null
);
$gContent->load();

$gBitSmarty->assign('gContent', $gContent);
$gBitSmarty->assign('assemblyContentId', $gContent->mContentId);

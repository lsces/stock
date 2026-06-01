<?php
/**
 * @package stock
 * @subpackage functions
 */

global $gContent;
use \Bitweaver\Stock\StockMovement;

$gContent = new StockMovement(
	!empty( $_REQUEST['content_id'] ) ? (int)$_REQUEST['content_id'] : null
);
$gContent->load();

$gBitSmarty->assign( 'gContent', $gContent );

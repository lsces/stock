<?php
/**
 * @package stock
 * @subpackage functions
 */

global $gContent;
use \Bitweaver\Stock\StockMovement;

$gContent = new StockMovement(
	!empty( $_REQUEST['movement_id'] ) ? (int)$_REQUEST['movement_id'] : null,
	!empty( $_REQUEST['content_id'] )  ? (int)$_REQUEST['content_id']  : null
);
$gContent->load();

$gBitSmarty->assign( 'gContent',   $gContent );
$gBitSmarty->assign( 'movementId', $gContent->mMovementId );

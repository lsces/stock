<?php
/**
 * @package stock
 */

namespace Bitweaver\Stock;

require_once '../kernel/includes/setup_inc.php';

global $gBitSystem, $gBitSmarty;

$gBitSystem->verifyPackage( 'stock' );
$gBitSystem->verifyPermission( 'p_stock_view' );

include_once STOCK_PKG_INCLUDE_PATH.'movement_lookup_inc.php';

if( !$gContent->isValid() ) {
	$gBitSystem->display( 'error.tpl', 'Movement not found' );
	die;
}

$gBitSystem->setCanonicalLink( $gContent->getDisplayUrl() );

$gContent->loadXrefInfo();
$gBitSmarty->assign( 'gXrefInfo', $gContent->mXrefInfo );
$refType = $gContent->mInfo['ref_type'] ?? '';
$gBitSmarty->assign( 'isReqn',  $refType === 'REQN' );
$gBitSmarty->assign( 'isPbld',  $refType === 'PBLD' );
$gBitSmarty->assign( 'isBuild', in_array( $refType, [ 'REQN', 'PBLD' ] ) );

$gBitSystem->display( 'bitpackage:stock/view_movement.tpl', $gContent->getTitle() );

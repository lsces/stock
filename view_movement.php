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
$gBitSmarty->assign( 'isReqn', ( ( $gContent->mInfo['ref_type'] ?? '' ) === 'REQN' ) );

$gBitSystem->display( 'bitpackage:stock/view_movement.tpl', $gContent->getTitle() );

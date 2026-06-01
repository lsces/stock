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

$gBitSystem->display( 'bitpackage:stock/view_movement.tpl', $gContent->getTitle() );

<?php
/**
 * Read-only printable BOM for an assembly. Requires p_stock_view only.
 *
 * @package stock
 */

namespace Bitweaver\Stock;

require_once '../kernel/includes/setup_inc.php';

use Bitweaver\KernelTools;

global $gBitSystem, $gBitSmarty;

$gBitSystem->verifyPermission( 'p_stock_view' );

include_once STOCK_PKG_INCLUDE_PATH.'assembly_lookup_inc.php';

if( !$gContent->isValid() ) {
	$gBitSystem->fatalError( 'Assembly not found.' );
}

$gContent->loadXrefInfo();
$gBitSmarty->assign( 'gXrefInfo', $gContent->mXrefInfo );

$gBitSystem->display( 'bitpackage:stock/print_bom.tpl', $gContent->getTitle().' — '.KernelTools::tra('Parts List'), [ 'display_mode' => 'display' ] );

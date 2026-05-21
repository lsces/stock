<?php
/**
 * @version $Header$
 * @package stock
 * @subpackage functions
 */

/**
 * required setup
 */
namespace Bitweaver\Stock;

require_once '../kernel/includes/setup_inc.php';

global $gBitSystem, $gBitSmarty;

$gStockAssembly = new StockAssembly();
$galleryList = $gStockAssembly->getList( $_REQUEST );
$gBitSmarty->assign( 'galleryList', $galleryList );

$gBitSystem->display( "bitpackage:stock/browse_galleries.tpl" , null, [ 'display_mode' => 'display' ] );

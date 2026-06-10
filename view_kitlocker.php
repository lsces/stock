<?php
/**
 * Kitlocker group gallery — shows all stgrp items as a browsable grid.
 * @package stock
 */

namespace Bitweaver\Stock;

use Bitweaver\KernelTools;

require_once '../kernel/includes/setup_inc.php';

$gBitSystem->verifyPackage( 'stock' );

global $gBitSystem, $gBitSmarty, $gBitDb;

$stgrpItems = $gBitDb->getAll(
	"SELECT xi.`item`, xi.`cross_ref_title`, xi.`data`,
		(SELECT COUNT(*) FROM `".BIT_DB_PREFIX."liberty_xref` x
		 INNER JOIN `".BIT_DB_PREFIX."liberty_content` lc ON lc.`content_id` = x.`content_id`
		 WHERE x.`item` = xi.`item`
		   AND lc.`content_type_guid` IN ('stockassembly','stockcomponent')) AS item_count
	 FROM `".BIT_DB_PREFIX."liberty_xref_item` xi
	 WHERE xi.`x_group` = 'stgrp' AND xi.`content_type_guid` = 'stock'
	 ORDER BY xi.`item`"
);

$gBitSmarty->assign( 'stgrpItems', $stgrpItems );

$gBitSystem->setBrowserTitle( KernelTools::tra( 'Kitlocker' ) );
$gBitSystem->display( 'bitpackage:stock/stock_fixed_grid_inc.tpl', null, [ 'display_mode' => 'display' ] );

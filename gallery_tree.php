<?php
/**
 * @version $Header$
 * @package stock
 * @subpackage functions
 */

/**
 * required setup
 */
require_once '../kernel/includes/setup_inc.php';

global $gBitSystem;

$gBitSystem->display("bitpackage:stock/gallery_tree.tpl", null, [ 'display_mode' => 'display' ] );

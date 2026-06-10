<?php
/**
 * Combined kitlocker listing — assemblies and components filtered by stgrp tag.
 * @package stock
 */

namespace Bitweaver\Stock;

use Bitweaver\KernelTools;

require_once '../kernel/includes/setup_inc.php';

$gBitSystem->verifyPackage( 'stock' );

global $gBitSystem, $gBitSmarty, $gBitDb;

$stgrp = !empty( $_REQUEST['stgrp'] ) ? trim( $_REQUEST['stgrp'] ) : null;

$X        = BIT_DB_PREFIX;
$bindVars = [];
$whereSql = "AND lc.`content_type_guid` IN ('stockassembly','stockcomponent')";

if( $stgrp ) {
	$whereSql .= " AND EXISTS (SELECT 1 FROM `{$X}liberty_xref` sx WHERE sx.`content_id` = lc.`content_id` AND sx.`item` = ?)";
	$bindVars[] = $stgrp;
	$groupTitle = $gBitDb->getOne(
		"SELECT `cross_ref_title` FROM `{$X}liberty_xref_item` WHERE `item` = ? AND `x_group` = 'stgrp'",
		[ $stgrp ]
	);
} else {
	$whereSql .= " AND EXISTS (SELECT 1 FROM `{$X}liberty_xref` sx
		INNER JOIN `{$X}liberty_xref_item` xi ON xi.`item` = sx.`item` AND xi.`x_group` = 'stgrp'
		WHERE sx.`content_id` = lc.`content_id`)";
	$groupTitle = null;
}

$items = $gBitDb->getAll(
	"SELECT lc.`content_id`, lc.`title`, lc.`data`, lc.`content_type_guid`,
		(SELECT FIRST 1 x.`xkey` FROM `{$X}liberty_xref` x
		 WHERE x.`content_id` = lc.`content_id` AND x.`item` = 'KLID') AS klid,
		(SELECT FIRST 1 x.`xkey` FROM `{$X}liberty_xref` x
		 WHERE x.`content_id` = lc.`content_id` AND x.`item` = 'KLPR') AS klpr
	 FROM `{$X}liberty_content` lc
	 WHERE 1=1 $whereSql
	 ORDER BY lc.`title`",
	$bindVars
);

$gBitSmarty->assign( 'kitlockerItems', $items );
$gBitSmarty->assign( 'stgrp',          $stgrp );
$gBitSmarty->assign( 'groupTitle',     $groupTitle );

$pageTitle = $groupTitle ?: KernelTools::tra( 'Kitlocker' );
$gBitSystem->setBrowserTitle( $pageTitle );
$gBitSystem->display( 'bitpackage:stock/list_kitlocker.tpl', null, [ 'display_mode' => 'list' ] );

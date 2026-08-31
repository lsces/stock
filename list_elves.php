<?php
/**
 * List kit elves — contact persons with a linked user account.
 * Shows assembly and movement counts with links to filtered stock views.
 *
 * Filter is deliberately just "has a linked user account" — no contact
 * 'type' tag involved. Only actual kit elves are expected to be registered
 * site users for the foreseeable future, so this is enough on its own; if
 * that stops being true, don't hardcode a specific item code (e.g. 'P02')
 * here to narrow it further — that's a per-site value (label AND meaning
 * both vary by install, see contact/admin/schema_inc.php's own P01/P02
 * comment), it'd need a kernel_config setting exposed on stock's own admin
 * page (see admin_stock.tpl's existing settings for the convention), not a
 * literal in this query.
 *
 * @package stock
 */

namespace Bitweaver\Stock;

require_once '../kernel/includes/setup_inc.php';

$gBitSystem->verifyPackage( 'stock' );
$gBitSystem->verifyPermission( 'p_contact_view' );

global $gBitSystem, $gBitSmarty, $gBitDb;

$X = BIT_DB_PREFIX;

$rs = $gBitDb->query(
	"SELECT con.`content_id`, con.`role_id` AS user_id,
		lc.`title` AS display_name,
		uu.`login` AS linked_user_login,
		uu.`real_name` AS linked_user_name,
		(SELECT COUNT(*) FROM `{$X}liberty_content` ac
		 WHERE ac.`content_type_guid` = 'stockassembly' AND ac.`user_id` = con.`role_id`) AS assembly_count,
		(SELECT COUNT(*) FROM `{$X}liberty_content` mc
		 WHERE mc.`content_type_guid` = 'stockmovement' AND mc.`user_id` = con.`role_id`) AS movement_count
	 FROM `{$X}contact` con
	 INNER JOIN `{$X}liberty_content` lc ON lc.`content_id` = con.`content_id`
	 INNER JOIN `{$X}users_users` uu ON uu.`user_id` = con.`role_id`
	 WHERE con.`role_id` IS NOT NULL
	 ORDER BY lc.`title`"
);

$elves = [];
while( $row = $rs->fetchRow() ) {
	$elves[] = $row;
}

$gBitSmarty->assign( 'elves', $elves );
$gBitSystem->setBrowserTitle( 'Kit Elves' );
$gBitSystem->display( 'bitpackage:stock/list_elves.tpl', null, [ 'display_mode' => 'list' ] );
